<?php
namespace Maksv\MachineLearning;

class Assistant
{
    public function __construct(string $host = 'http://127.0.0.1:8000'){}

    /** @var string Базовый URL ML-сервиса */
    public static string $host = 'http://127.0.0.1:8000';

    /** @var string Префикс маршрутов (часть URL перед /train и /predict) */
    public static string $prefix = 'ml';

    public static $nBars = 30;
    public static $mBars = 100;
    public static $intervalMinutes = 15;
    public static $intervalMs = 15 * 60 * 1000; // 15 минут в миллисекундах

    public static function getTrainData () {
        $trainDataFile = $_SERVER["DOCUMENT_ROOT"] . '/'.self::$prefix.'/data/train_data.json';
        $allData = [];
        $errors = [];

        // 1) Загружаем существующие данные
        if (file_exists($trainDataFile)) {
            $json = @file_get_contents($trainDataFile);
            if ($json !== false) {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $allData = $decoded;
                } else {
                    $errors[] = "Не удалось декодировать JSON из {$trainDataFile}";
                }
            } else {
                $errors[] = "Не удалось прочитать файл {$trainDataFile}";
            }
        }

        $count = false;
        if (is_array($allData))
            $count = count($allData);

        return [
            'allData' => $allData,
            'count' => $count,
            'errors' => $errors,
        ];
    }
    
    /**
     * Собирает и сохраняет тренировочные данные в JSON-файл.
     * Возвращает статистику обработки сигналов и возможные ошибки.
     *
     * @param array  $finalResults Массив сигналов.
     * @param string $market       'bybit' или 'binance'.
     * @param object $bybitApiOb   Клиент Bybit API.
     * @param object $binanceApiOb Клиент Binance API.
     *
     * @return array {
     *   int   allCount,       // всего сигналов
     *   int   continueCount,  // сколько пропущено из-за дубликата
     *   int   procCount,      // сколько обработано
     *   array errors          // список сообщений об ошибках
     * }
     */
    public static function collectAndStoreTrainData(array $finalResults, string $market, $bybitApiOb, $binanceApiOb): array
    {
        $trainDataFile = $_SERVER["DOCUMENT_ROOT"] . '/'.self::$prefix.'/data/train_data.json';
        $allData = [];
        $errors = [];

        // 1) Загружаем существующие данные
        if (file_exists($trainDataFile)) {
            $json = @file_get_contents($trainDataFile);
            if ($json !== false) {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $allData = $decoded;
                } else {
                    $errors[] = "Не удалось декодировать JSON из {$trainDataFile}";
                }
            } else {
                $errors[] = "Не удалось прочитать файл {$trainDataFile}";
            }
        }

        $allCount = $procCount = $continueCount = 0;

        // 2) Обрабатываем каждый сигнал
        foreach ($finalResults as $res) {
            $allCount++;

            // 2.1) Парсим дату сигнала
            $dt = \DateTime::createFromFormat(
                'd.m.Y H:i:s',
                $res['date'],
                new \DateTimeZone('Europe/Amsterdam')
            );
            if ($dt === false) {
                $errors[] = "[{$res['symbolName']}] Неверный формат даты: {$res['date']}";
                continue;
            }
            $signalTimestampMs = $dt->getTimestamp() * 1000;
            $key = $res['symbolName'] . '_' . $signalTimestampMs;

            // 2.2) Пропускаем, если ключ уже есть
            if (isset($allData[$key])) {
                $continueCount++;
                continue;
            }
            $procCount++;

            // 2.3) Интервалы
            $startTimeMs = $signalTimestampMs - (self::$nBars * self::$intervalMs);
            $endTimeMs   = $signalTimestampMs + (self::$mBars * self::$intervalMs);
            $symbolName  = $res['symbolName'];
            $useCache    = true;
            $cacheTime   = 86400;
            $limit       = self::$nBars + self::$mBars + 10;

            // 2.4) Запрос сырых баров
            $rawBars = [];
            if ($market === 'bybit') {
                try {
                    $resp = $bybitApiOb->klineTimeV5(
                        "linear",
                        $symbolName,
                        $startTimeMs,
                        $endTimeMs,
                        '15m',
                        $limit,
                        $useCache,
                        $cacheTime
                    );
                } catch (\Exception $e) {
                    $errors[] = "[{$symbolName}] Ошибка Bybit API: " . $e->getMessage();
                    continue;
                }
                if (empty($resp['result']['list']) || !is_array($resp['result']['list'])) {
                    $errors[] = "[{$symbolName}] Нет данных от Bybit";
                    continue;
                }
                $rawBars = $resp['result']['list'];
                usort($rawBars, fn($a, $b) => $a[0] <=> $b[0]);

            } elseif ($market === 'binance') {
                try {
                    $bars = $binanceApiOb->kline(
                        $symbolName,
                        '15m',
                        $limit,
                        $startTimeMs,
                        $endTimeMs,
                        $useCache,
                        $cacheTime
                    );
                } catch (\Exception $e) {
                    $errors[] = "[{$symbolName}] Ошибка Binance API: " . $e->getMessage();
                    continue;
                }
                if (empty($bars) || !is_array($bars)) {
                    $errors[] = "[{$symbolName}] Нет данных от Binance";
                    continue;
                }
                $rawBars = $bars;
                usort($rawBars, fn($a, $b) => $a[0] <=> $b[0]);
            } else {
                $errors[] = "[{$symbolName}] Маркет '{$market}' не поддерживается";
                continue;
            }

            // 2.5) Преобразование в ассоц. формат
            $candlesAll = array_map(fn($bar) => [
                't' => floatval($bar[0]),
                'o' => floatval($bar[1]),
                'h' => floatval($bar[2]),
                'l' => floatval($bar[3]),
                'c' => floatval($bar[4]),
                'v' => floatval($bar[5]),
            ], $rawBars);

            // 2.6) Разделяем на “до” и “после”
            $historicalAll = [];
            $futureAll     = [];
            foreach ($candlesAll as $bar) {
                if ($bar['t'] < $signalTimestampMs) {
                    $historicalAll[] = [
                        'o' => $bar['o'],
                        'h' => $bar['h'],
                        'l' => $bar['l'],
                        'c' => $bar['c'],
                        'v' => $bar['v'],
                    ];
                } else {
                    $futureAll[] = [
                        'h' => $bar['h'],
                        'l' => $bar['l'],
                    ];
                }
            }

            // 2.7) Проверка количества баров
            if (count($historicalAll) < self::$nBars || count($futureAll) < self::$mBars) {
                $errors[] = "[{$symbolName}] Недостаточно баров (до: " . count($historicalAll) . ", после: " . count($futureAll) . ")";
                continue;
            }

            // 2.8) Вырезаем ровно нужное число баров
            $historical = array_slice($historicalAll, -self::$nBars);
            $future     = array_slice($futureAll, 0, self::$mBars);
            if (count($historical) !== self::$nBars || count($future) !== self::$mBars) {
                $errors[] = "[{$symbolName}] Ошибка при обрезке баров";
                continue;
            }

            // 2.9) Собираем trainSignal
            $entryPrice = floatval($res['allInfo']['entryTarget']);
            if ($entryPrice == 0) {
                $entryPrice = floatval($res['allInfo']['actualClosePrice']);
            }

            $tpsRaw = (array)$res['allInfo']['TP'];
            $tps    = array_map(fn($x) => floatval($x), $tpsRaw);
            if (count($tps) > 3) {
                $tps = array_slice($tps, 0, 3);
            }

            $slPrice = floatval($res['allInfo']['SL']);

            $trainSignal = [
                'candles'   => $historical,
                'entry'     => $entryPrice,
                'tps'       => $tps,
                'sl'        => $slPrice,
                'future'    => $future,
                'direction' => $res['direction'],
                'date'      => $dt->format('d.m.Y H:i:s'),
            ];

            // 2.10) Сохраняем под ключом
            $allData[$key] = $trainSignal;
        }

        // 3) Сохраняем JSON
        $jsonToSave = json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonToSave !== false) {
            @file_put_contents($trainDataFile, $jsonToSave, LOCK_EX);
        } else {
            $errors[] = "Не удалось закодировать JSON для {$trainDataFile}";
        }

        return [
            'allCount'      => $allCount,
            'continueCount' => $continueCount,
            'procCount'     => $procCount,
            'errors'        => $errors,
        ];
    }

    public static function trainFromFile(): array
    {
        $errors = [];
        $resp   = null;
        try {
            $trainDataFile = $_SERVER["DOCUMENT_ROOT"] . '/'.self::$prefix.'/data/train_data.json';
            $trainDataFileShort = 'data/train_data.json';

            $req  = new Request(self::$host, self::$prefix);
            //$resp = $req->trainFile($trainDataFile);
            $resp = $req->trainFile($trainDataFileShort);

        } catch (\Exception $e) {
            $errors[] = "Ошибка при File-тренировке: " . $e->getMessage();
        }

        return ['resp' => $resp, 'errors' => $errors];
    }

    /**
     * Считывает накопленные данные и отправляет батч в ML для тренировки.
     * Возвращает массив с батчем, ответом сервера и возможными ошибками.
     *
     * @param array  $ignored
     * @param string $ignored2
     * @param mixed  $ignored3
     * @param mixed  $ignored4
     *
     * @return array {
     *   array trainBatch,    // список всех записей для тренировки
     *   mixed resp,          // ответ от ML API
     *   int   trainCountAll, // общее число записей в JSON
     *   array errors         // ошибки чтения/декодирования/запроса
     * }
     */
    public static function trainRes(array $ignored = [], string $ignored2 = '', $ignored3 = null, $ignored4 = null): array
    {
        $trainDataFile = $_SERVER["DOCUMENT_ROOT"] . '/'.self::$prefix.'/data/train_data.json';
        $trainBatch    = [];
        $resp          = null;
        $errors        = [];
        $trainCountAll = 0;

        // 1) Читаем JSON
        if (!file_exists($trainDataFile)) {
            $errors[] = "Файл данных для обучения не найден: {$trainDataFile}";
            return ['trainBatch' => [], 'resp' => null, 'trainCountAll' => 0, 'errors' => $errors];
        }

        $json = @file_get_contents($trainDataFile);
        if ($json === false) {
            $errors[] = "Не удалось прочитать файл: {$trainDataFile}";
            return ['trainBatch' => [], 'resp' => null, 'trainCountAll' => 0, 'errors' => $errors];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || empty($decoded)) {
            $errors[] = "Нет данных для обучения (JSON пуст или некорректен)";
            return ['trainBatch' => [], 'resp' => null, 'trainCountAll' => 0, 'errors' => $errors];
        }

        // 2) Формируем trainBatch
        foreach ($decoded as $key => $item) {
            $trainCountAll++;
            $trainBatch[] = $item;
        }

        // 3) Отправляем батч в ML, если есть данные
        if (!empty($trainBatch)) {
            try {
                $ml   = new \Maksv\MachineLearning\Request(self::$host);
                $resp = $ml->train($trainBatch);
            } catch (\Exception $e) {
                $errors[] = "Ошибка при тренировке ML: " . $e->getMessage();
            }
        }

        return [
            'trainBatch'    => $trainBatch,
            'resp'          => $resp,
            'trainCountAll' => $trainCountAll,
            'errors'        => $errors,
        ];
    }

    /**
     * Делает предсказание для сигналов. Если в $res['allInfo']['candles15m'] есть
     * достаточное количество баров, использует их, иначе запрашивает бары с биржи.
     * Возвращает массив с результатами и ошибками.
     *
     * @param array  $finalResults Массив сигналов.
     * @param string $market       'bybit' или 'binance'.
     * @param object $bybitApiOb   Клиент Bybit API.
     * @param object $binanceApiOb Клиент Binance API.
     *
     * @return array {
     *   "<symbol>_<timestamp>" => [
     *     'symbolName' => string,
     *     'date'       => "d.m.Y H:i:s",
     *     'prediction' => mixed,   // ответ ML
     *     'errors'     => array   // ошибки, если есть
     *   ],
     *   ...
     * }
     */
    public static function predictRes(array $finalResults, string $market, $bybitApiOb, $binanceApiOb): array
    {
        $results = [];

        foreach ($finalResults as $res) {
            $symbolName = $res['symbolName'];
            $rawDate    = $res['date'];
            $entry = [
                'symbolName' => $symbolName,
                'date'       => $rawDate,
                'prediction' => null,
                'errors'     => [],
            ];

            // 1) Парсим дату
            $dt = \DateTime::createFromFormat(
                'd.m.Y H:i:s',
                $rawDate,
                new \DateTimeZone('Europe/Amsterdam')
            );
            if ($dt === false) {
                $entry['errors'][] = "Неверный формат даты: {$rawDate}";
                $results[$symbolName . '_invalid_date'] = $entry;
                continue;
            }
            $signalTimestampMs = $dt->getTimestamp() * 1000;

            // 2) Получаем ровно self::$nBars свечек
            $payloadCandles = [];
            $needApiFetch = true;

            if (
                isset($res['allInfo']['candles15m']) &&
                is_array($res['allInfo']['candles15m']) &&
                count($res['allInfo']['candles15m']) >= self::$nBars
            ) {
                // Берём последние nBars из переданных
                $candlesAll = $res['allInfo']['candles15m'];
                usort($candlesAll, fn($a, $b) => floatval($a['t']) <=> floatval($b['t']));
                $subset = array_slice($candlesAll, -self::$nBars);
                if (count($subset) === self::$nBars) {
                    // Преобразуем в формат ['o','h','l','c','v']
                    foreach ($subset as $bar) {
                        $payloadCandles[] = [
                            'o' => floatval($bar['o']),
                            'h' => floatval($bar['h']),
                            'l' => floatval($bar['l']),
                            'c' => floatval($bar['c']),
                            'v' => floatval($bar['v']),
                        ];
                    }
                    $needApiFetch = false;
                }
            }

            if ($needApiFetch) {
                // 2.1) Запрашиваем бары с биржи
                $endTimeMs   = $signalTimestampMs;
                $startTimeMs = $signalTimestampMs - (self::$nBars * self::$intervalMs);
                $barsData    = [];
                $errorsLocal = [];

                if ($market === 'bybit') {
                    $limit = self::$nBars;
                    try {
                        $resp = $bybitApiOb->klineTimeV5(
                            "linear",
                            $symbolName,
                            $startTimeMs,
                            $endTimeMs,
                            '15m',
                            $limit,
                            true,
                            86400
                        );
                    } catch (\Exception $e) {
                        $errorsLocal[] = "Ошибка Bybit API: " . $e->getMessage();
                        $entry['errors'] = array_merge($entry['errors'], $errorsLocal);
                        $results[$symbolName . '_' . $signalTimestampMs] = $entry;
                        continue;
                    }
                    if (empty($resp['result']['list']) || !is_array($resp['result']['list'])) {
                        $errorsLocal[] = "Нет данных от Bybit";
                        $entry['errors'] = array_merge($entry['errors'], $errorsLocal);
                        $results[$symbolName . '_' . $signalTimestampMs] = $entry;
                        continue;
                    }
                    foreach ($resp['result']['list'] as $b) {
                        $barsData[] = [
                            floatval($b[0]), // t
                            floatval($b[1]), // o
                            floatval($b[2]), // h
                            floatval($b[3]), // l
                            floatval($b[4]), // c
                            floatval($b[5]), // v
                        ];
                    }
                }
                elseif ($market === 'binance') {
                    $limit = self::$nBars;
                    try {
                        $bars = $binanceApiOb->kline(
                            $symbolName,
                            '15m',
                            $limit,
                            $startTimeMs,
                            $endTimeMs,
                            true,
                            604800
                        );
                    } catch (\Exception $e) {
                        $errorsLocal[] = "Ошибка Binance API: " . $e->getMessage();
                        $entry['errors'] = array_merge($entry['errors'], $errorsLocal);
                        $results[$symbolName . '_' . $signalTimestampMs] = $entry;
                        continue;
                    }
                    if (empty($bars) || !is_array($bars)) {
                        $errorsLocal[] = "Нет данных от Binance";
                        $entry['errors'] = array_merge($entry['errors'], $errorsLocal);
                        $results[$symbolName . '_' . $signalTimestampMs] = $entry;
                        continue;
                    }
                    foreach ($bars as $b) {
                        $barsData[] = [
                            floatval($b[0]),
                            floatval($b[1]),
                            floatval($b[2]),
                            floatval($b[3]),
                            floatval($b[4]),
                            floatval($b[5]),
                        ];
                    }
                }
                else {
                    $errorsLocal[] = "Маркет '{$market}' не поддерживается";
                    $entry['errors'] = array_merge($entry['errors'], $errorsLocal);
                    $results[$symbolName . '_' . $signalTimestampMs] = $entry;
                    continue;
                }

                usort($barsData, fn($a, $b) => $a[0] <=> $b[0]);
                $filtered = array_filter($barsData, fn($bar) => floatval($bar[0]) < $signalTimestampMs);
                if (count($filtered) < self::$nBars) {
                    $errorsLocal[] = "Получено " . count($filtered) . " баров, нужно " . self::$nBars;
                    $entry['errors'] = array_merge($entry['errors'], $errorsLocal);
                    $results[$symbolName . '_' . $signalTimestampMs] = $entry;
                    continue;
                }
                if (count($filtered) > self::$nBars) {
                    $filtered = array_slice($filtered, -self::$nBars);
                }
                foreach ($filtered as $b) {
                    $payloadCandles[] = [
                        'o' => floatval($b[1]),
                        'h' => floatval($b[2]),
                        'l' => floatval($b[3]),
                        'c' => floatval($b[4]),
                        'v' => floatval($b[5]),
                    ];
                }
            }

            // 3) TP/SL и direction
            $entryPrice = floatval($res['allInfo']['entryTarget']);
            if ($entryPrice == 0) {
                $entryPrice = floatval($res['allInfo']['actualClosePrice']);
            }
            $tpsRaw = (array)$res['allInfo']['TP'];
            $tps    = array_map(fn($x) => floatval($x), $tpsRaw);
            if (count($tps) > 3) {
                $tps = array_slice($tps, 0, 3);
            }
            $slPrice = floatval($res['allInfo']['SL']);

            $payload = [
                'candles'   => $payloadCandles,
                'entry'     => $entryPrice,
                'tps'       => $tps,
                'sl'        => $slPrice,
                'direction' => $res['direction'],
            ];

            // 4) Вызываем ML-предсказание
            try {
                $ml       = new \Maksv\MachineLearning\Request(self::$host);
                $response = $ml->predict($payload);
                $response['symbolName'] = $symbolName;
                $response['date']       = $dt->format('d.m.Y H:i:s');
                $entry['prediction']    = $response;
            } catch (\Exception $e) {
                $entry['errors'][] = "Ошибка ML: " . $e->getMessage();
            }

            $results[$symbolName . '_' . $dt->getTimestamp()] = $entry;
        }

        return $results;
    }
}
