<?php
namespace Maksv\Binance;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class Exchange
{
    const SYMBOLS_STOP_LIST_MAIN = ['SOLUSDT', 'BTCUSDT', 'ETHUSDT'];
    const SYMBOLS_STOP_LIST1 = ['USDCUSDT', 'USDEUSDT', 'USTCUSDT'];

    public function __construct() {}

    public static function binanceSummaryVolumeExchange(
        $devMode = false,
        $cacheTime = 240,
        $useCache = false
    )
    {
        $marketMode = 'binance';
        $timeMark = date("d.m.y H:i:s");
        devlogs("start -" . ' - ' . $timeMark, $marketMode . '/summaryVolumeExchange');

        // проверяем не запускался ли только что обмен
        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/timestampVolume.json'), true);
            if ($lastTimestapJson['TIMESTAMP'] && ((time() - $lastTimestapJson['TIMESTAMP']) < 150)) {
                devlogs("end, timestamp dif -" . ' - ' . $timeMark, $marketMode . '/summaryVolumeExchange');
                return false;
            } else {
                file_put_contents(
                    $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/timestampVolume.json',
                    json_encode(['TIMESTAMP' => time(), "TIMEMARK" => $timeMark])
                );
            }
        }

        // Загрузка существующих данных
        $dataFile = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/summaryVolumeExchange.json';
        $existingData = file_exists($dataFile) ?
            json_decode(file_get_contents($dataFile), true)['RESPONSE_EXCHENGE'] ?? [] : [];

        // Инициализация API
        $binanceApiOb = new \Maksv\Binance\BinanceFutures();
        $binanceApiOb->openConnection();

        // Получение списка символов
        $exchangeBybitSymbolsList = json_decode(
            file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/'.$marketMode.'Exchange/derivativeBaseCoin.json'),
            true
        )['RESPONSE_EXCHENGE'] ?? [];

        $processedSymbols = 0;
        foreach ($exchangeBybitSymbolsList as $symbol) {
            //if ($devMode && $processedSymbols >= 60) break;

            if (
                !isset($symbol['symbol'])
                || !is_string($symbol['symbol'])
                || $symbol['status'] !== 'TRADING'
                //|| preg_match('/^(ETHUSDT-|ETH-|BTCUSDT-|BTC-|SOLUSDT-)/', $symbol['symbol'])
                || !in_array($symbol['quoteAsset'], ['USDT'])
                || in_array($symbol['symbol'], self::SYMBOLS_STOP_LIST1)
                || in_array($symbol['symbol'], self::SYMBOLS_STOP_LIST_MAIN)

            ) {
                $continueSymbols .= $symbol['symbol'] . ', ';
                // Удаляем прежние данные по этому символу, чтобы он не остался в $existingData
                if ($existingData[$symbol['symbol']]) {
                    unset($existingData[$symbol['symbol']]);
                }
                continue;
            }

            $symbolName = $symbol['symbol'];
            $currentData = $existingData[$symbolName]['resBinance'] ?? [];

            // Получение новых сделок
            $tradesHistoryResp = $binanceApiOb->tradesHistory($symbolName, 1000, $useCache, $cacheTime);
            $tradesHistoryList = $tradesHistoryResp ?? [];

            $intervalData = [];
            foreach ($tradesHistoryList as $tradeItem) {
                $tradeTimestamp = (int)($tradeItem['time'] / 1000);

                // Расчет 5-минутного интервала:
                $minutes = (int)date('i', $tradeTimestamp);
                $roundedMinutes = floor($minutes / 5) * 5;
                $intervalStart = strtotime(date(sprintf('Y-m-d H:%02d:00', $roundedMinutes), $tradeTimestamp));
                $intervalDuration = 300; // 5 минут = 300 секунд

                if (!isset($intervalData[$intervalStart])) {
                    $intervalData[$intervalStart] = [
                        'buyVolume' => 0,
                        'sellVolume' => 0,
                        'sumVolume' => 0,
                        'startTime_gmt' => \Maksv\Bybit\Bybit::gmtTimeByTimestamp($intervalStart * 1000),
                        'startTime' => $intervalStart,
                        'endTime' => $intervalStart + $intervalDuration,
                        'endTime_gmt' => \Maksv\Bybit\Bybit::gmtTimeByTimestamp(($intervalStart + $intervalDuration) * 1000)
                    ];
                }

                $size = (float)$tradeItem['qty'];
                $intervalData[$intervalStart][$tradeItem['isBuyerMaker'] === true ? 'sellVolume' : 'buyVolume'] += $size;
                $intervalData[$intervalStart]['sumVolume'] += $size;
            }

            // Слияние с существующими данными
            $currentDataMap = [];
            foreach ($currentData as $item) {
                $currentDataMap[$item['startTime']] = $item;
            }
            foreach ($intervalData as $startTime => $newInterval) {
                $newInterval['last_edit'] = date("d.m.y H:i:s");
                if (isset($currentDataMap[$startTime])) {
                    if (
                        $newInterval['buyVolume'] > $currentDataMap[$startTime]['buyVolume']
                        || $newInterval['sellVolume'] > $currentDataMap[$startTime]['sellVolume']
                        || $newInterval['sumVolume'] > $currentDataMap[$startTime]['sumVolume']
                    ) {
                        $currentDataMap[$startTime] = $newInterval;
                    }
                } else {
                    $currentDataMap[$startTime] = $newInterval;
                }
            }

            // Конвертируем обратно в массив и сортируем по времени (от новых к старым)
            $currentData = array_values($currentDataMap);
            usort($currentData, function ($a, $b) {
                return $b['startTime'] - $a['startTime'];
            });
            // Ограничиваем количество интервалов
            $currentData = array_slice($currentData, 0, 302);
            $currentData = \Maksv\TechnicalAnalysis::calculateDelta($currentData);

            // Сохранение обновленных данных для текущего символа
            $existingData[$symbolName] = [
                'resBybit' => [],
                'resBinance' => $currentData,
                'resSummary' => []
            ];

            $processedSymbols++;

            // Если обработали 50 символов, сохраняем текущие данные в файл
            if ($processedSymbols % 50 == 0) {
                $timeMark = date("d.m.y H:i:s");
                $output = [
                    "TIMEMARK" => $timeMark,
                    "RESPONSE_EXCHENGE" => $existingData,
                    "EXCHANGE_CODE" => $marketMode,
                ];
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/summaryVolumeExchange.json', json_encode($output));
                devlogs("save (processedSymbols " . $processedSymbols . " - " . $timeMark, $marketMode . '/summaryVolumeExchange');
            }
        }

        // Финальная запись, если осталось меньше 50 символов в конце
        $timeMark = date("d.m.y H:i:s");
        $output = [
            "TIMEMARK" => $timeMark,
            "RESPONSE_EXCHENGE" => $existingData,
            "EXCHANGE_CODE" => $marketMode,
        ];
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/summaryVolumeExchange.json', json_encode($output));

        $binanceApiOb->closeConnection();

        //devlogs("continueSymbols " . $continueSymbols, $marketMode . '/summaryVolumeExchange');
        devlogs("end (cnt " . $processedSymbols . " - " . $timeMark, $marketMode . '/summaryVolumeExchange');
        devlogs("______________________________", $marketMode . '/summaryVolumeExchange');

        return true;
    }

    //анализ OI для поиска нужно лимита изменения
    public static function oiBorderExchange(
        string $timeFrame = '5m',
        // 6) Настройки окна и порогов
        int    $barsCount = 500,
        int    $oiWindow = 3, // 3 * 5m = 15 минут
        int    $priceFutureWindow = 48,   // 24 * 5m = 2 часа
        float  $pumpThreshold = 2.5,  // % роста цены
        float  $dumpThreshold = -2.5, // % падения цены
        bool   $devMode = false,

    ) {
        if ($barsCount > 500) {
            $barsCount = 500;
        }

        $marketCode = 'binance';
        $timeMark = date("d.m.y H:i:s");

        // 0) Подгружаем старые накопленные события (если есть)
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/upload/{$marketCode}Exchange/{$timeFrame}/oiBorderExchange.json";
        $existing = file_exists($filePath)
            ? (json_decode(file_get_contents($filePath), true)['RESPONSE'] ?? [])
            : [];

        $res = [];

        // проверяем не запускался ли только что обмен
        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/upload/{$marketCode}Exchange/{$timeFrame}/oiBorderTimestamp.json"), true);
            if ($lastTimestapJson['TIMESTAMP'] && ((time() - $lastTimestapJson['TIMESTAMP']) < 360)) {
                devlogs("end, timestamp dif -" . ' - ' . $timeMark, "{$marketCode}/oiBorder{$timeFrame}");
                return;
            } else {
                file_put_contents(
                    $_SERVER['DOCUMENT_ROOT'] . "/upload/{$marketCode}Exchange/{$timeFrame}/oiBorderTimestamp.json",
                    json_encode(['TIMESTAMP' => time(), "TIMEMARK" => $timeMark])
                );
            }
        }
        devlogs("Start oiBorderExchange batch - {$timeMark}", "{$marketCode}/oiBorder{$timeFrame}");

        $binanceApiOb = new \Maksv\Binance\BinanceFutures();
        $binanceApiOb->openConnection();

        // 2) Список символов
        $symbolsList = json_decode(
            file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/'.$marketCode.'Exchange/derivativeBaseCoin.json'),
            true
        )['RESPONSE_EXCHENGE'] ?? [];

        $processed = 0;
        foreach ($symbolsList as $meta) {
            if (
                !isset($meta['symbol'])
                || !is_string($meta['symbol'])
                || $meta['status'] !== 'TRADING'
                || !in_array($meta['quoteAsset'], ['USDT'])
                || in_array($meta['symbol'], self::SYMBOLS_STOP_LIST1)
                || in_array($meta['symbol'], self::SYMBOLS_STOP_LIST_MAIN)
            ) {
                if ($existing[$meta['symbol']]) {
                    unset($existing[$meta['symbol']]);
                }
                continue;
            }
            $symbol = $meta['symbol'];
            $processed++;

            // 3) Собираем barsCount свечей и OI
            $kline = $binanceApiOb->kline($symbol, $timeFrame, $barsCount, false, false, true, 300);
            if (empty($kline)) {
                devlogs("No candles for {$symbol}", "{$marketCode}/oiBorder{$timeFrame}");
                continue;
            }
            usort($kline, fn($a, $b) => $a[0] <=> $b[0]);
            $priceData = array_column(($kline), 4, 0);

            $oiData = [];
            $batch = $binanceApiOb->getOpenInterestHist($symbol, false, false, $timeFrame, $barsCount, true, 300) ?? [];
            if (!empty($batch) && is_array($batch)) {
                foreach ($batch as $item) {
                    $oiData[(int)$item['timestamp']] = (float)$item['sumOpenInterest'];
                }
            }

            if (count($priceData) < 4 || count($oiData) < 4) {
                devlogs("Not enough data for {$symbol}", "{$marketCode}/oiBorder{$timeFrame}");
                continue;
            }

            // 4) Синхронизация по timestamp
            $common = array_intersect_key($priceData, $oiData);
            ksort($common);
            $tsList = array_keys($common);

            // 5) Загружаем старые события в ассоц. мапы по ключу 'startTimestamp'
            $pumpMap = [];
            $dumpMap = [];
            if (!empty($existing[$symbol]['pumpEvents'])) {
                foreach ($existing[$symbol]['pumpEvents'] as $ev) {
                    $pumpMap[$ev['startTimestamp']] = $ev;
                }
            }
            if (!empty($existing[$symbol]['dumpEvents'])) {
                foreach ($existing[$symbol]['dumpEvents'] as $ev) {
                    $dumpMap[$ev['startTimestamp']] = $ev;
                }
            }

            $newUpOi = [];
            $newDownOi = [];

            // 7) Находим новые события и добавляем в мапы
            for ($i = $oiWindow; $i + $priceFutureWindow < count($tsList); $i++) {
                $tPrev = $tsList[$i - $oiWindow];
                $tCurr = $tsList[$i];
                $tFut = $tsList[$i + $priceFutureWindow];

                $pctOi = ($oiData[$tCurr] - $oiData[$tPrev]) / max($oiData[$tPrev], 1) * 100;
                $pctFut = ($priceData[$tFut] - $priceData[$tCurr]) / max($priceData[$tCurr], 1) * 100;

                // pump
                if ($pctOi > 0 && $pctFut >= $pumpThreshold) {
                    $newUpOi[] = $pctOi;
                    $startKey = $startTimestamp = strval($tPrev);
                    if (!isset($pumpMap[$startKey])) {
                        $pumpMap[$startKey] = [
                            'startTimestamp' => $startTimestamp,
                            'start' => date("H:i d.m", $tPrev / 1000),
                            'end' => date("H:i d.m", $tFut / 1000),
                            'oiChange' => round($pctOi, 3),
                            'priceChange' => round($pctFut, 3),
                        ];
                    }
                }
                // dump
                if ($pctOi < 0 && $pctFut <= $dumpThreshold) {
                    $newDownOi[] = $pctOi;
                    $startKey = $startTimestamp = strval($tPrev);
                    if (!isset($dumpMap[$startKey])) {
                        $dumpMap[$startKey] = [
                            'startTimestamp' => $startTimestamp,
                            'start' => date("H:i d.m", $tPrev / 1000),
                            'end' => date("H:i d.m", $tFut / 1000),
                            'oiChange' => round($pctOi, 3),
                            'priceChange' => round($pctFut, 3),
                        ];
                    }
                }
            }

            // 8) Обрезаем мапы до 50 элементов и приводим к списку
            $pumpEvents = array_slice($pumpMap, -80, 80, true);
            $dumpEvents = array_slice($dumpMap, -80, 80, true);

            // 9) Считаем границы по всем накопленным событиям
            $allUpOi = array_column($pumpEvents, 'oiChange');
            $allDownOi = array_column($dumpEvents, 'oiChange');
            $borderLong = !empty($allUpOi) ? array_sum($allUpOi) / count($allUpOi) : 0;
            $borderShort = !empty($allDownOi) ? array_sum($allDownOi) / count($allDownOi) : 0;

            // 10) Сохранение по символу
            $res[$symbol] = [
                'borderLong' => round($borderLong, 3),
                'borderShort' => round($borderShort, 3),
                'samplesUp' => count($allUpOi),
                'samplesDown' => count($allDownOi),
                'pumpEvents' => ($pumpEvents),
                'dumpEvents' => ($dumpEvents),
            ];

            //if ($processed > 20) break;
        }

        // 11) Агрегированная статистика
        $sumL = $cntL = $sumS = $cntS = 0;
        foreach ($res as $d) {
            if ($d['borderLong'] != 0) {
                $sumL += $d['borderLong'];
                $cntL++;
            }
            if ($d['borderShort'] != 0) {
                $sumS += $d['borderShort'];
                $cntS++;
            }
        }
        $info = [
            'avgBorderLong' => $cntL ? round($sumL / $cntL, 3) : 0,
            'avgBorderShort' => $cntS ? round($sumS / $cntS, 3) : 0,
            'countLong' => $cntL,
            'countShort' => $cntS,
        ];

        // 12) Завершение и запись
        devlogs("Processed {$processed} symbols", "{$marketCode}/oiBorder{$timeFrame}");
        devlogs("End oiBorderExchange - " . date("d.m.y H:i:s"), "{$marketCode}/oiBorder{$timeFrame}");
        devlogs("___________________________", "{$marketCode}/oiBorder{$timeFrame}");

        $output = [
            'TIMESTAMP' => time(),
            'TIMEMARK' => date("d.m.y H:i:s"),
            'RESPONSE' => $res,
            'INFO' => $info,
        ];
        $resOutput = file_put_contents($filePath, json_encode($output));

        $binanceApiOb->closeConnection();
        return $filePath;
    }

    //обмен по основной стратегии
    public static function screener($interval = '15m', $longOiLimit = 0.99, $shortOiLimit = -0.99, $devMode = false)
    {
        $currentLongOiLimit = $longOiLimit;
        $currentShortOiLimit = $shortOiLimit;

        $marketMode = 'binance';
        // проверяем не запускался ли только что обмен
        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/'.$marketMode.'Exchange/screener/' . $interval . '/timestap.json'), true);
            if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 120)) {
                devlogs("end, timestap dif -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                return;
            } else {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/'.$marketMode.'Exchange/screener/' . $interval . '/timestap.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => date("d.m.y H:i:s")]));
            }
        }
        devlogs("start -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);

        if ($interval == '15m') {
            sleep(15);
            devlogs('sleep 15' . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
        }

        //получаем контракты, которые будем анализировать
        $exchangeBybitSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeBinanceSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeOkxSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/okxExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];

        $binanceSymbolsList = array_column($exchangeBinanceSymbolsList, 'symbol') ?? [];
        $bybitSymbolsList = array_column($exchangeBybitSymbolsList, 'symbol') ?? [];
        $okxSymbolsList = array_column(
            array_map(function($item) {
                $cleanId = str_replace('-' . $item['instType'], '', $item['instId']);
                return [
                    $item['instId'],
                    str_replace('-', '', $cleanId)
                ];
            }, $exchangeOkxSymbolsList),
            1,
            0
        );

        if (!$binanceSymbolsList) {
            devlogs("err, binanceSymbolsList -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
            return;
        }

        if (!$bybitSymbolsList)
            devlogs("err, bybitSymbolsList -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);

        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();
        $binanceApiOb = new \Maksv\Binance\BinanceFutures();
        $binanceApiOb->openConnection();
        $okxApiOb = new \Maksv\Okx\OkxFutures();
        $okxApiOb->openConnection();

        $binanceScreenerIblockId = 7;
        $latestScreener = \Maksv\DataOperation::getLatestScreener($binanceScreenerIblockId);

        $betaForeverScreenerIblockId = 9;
        $latestScreenerBetaForever = \Maksv\DataOperation::getLatestScreener($betaForeverScreenerIblockId);

        $analyzeCnt = $cnt = $cntSuccess = 0;

        $dataFileSeparateVolume = $_SERVER['DOCUMENT_ROOT'] . '/upload/'.$marketMode.'Exchange/summaryVolumeExchange.json';
        $existingDataSeparateVolume = file_exists($dataFileSeparateVolume) ? json_decode(file_get_contents($dataFileSeparateVolume), true)['RESPONSE_EXCHENGE'] ?? [] : [];
        $separateVolumes = $analyzeVolumeSignalRes ?? [];

        $marketInfo = \Maksv\Bybit\Exchange::checkMarketImpulsInfo();
        $analyzeSymbols = $repeatSymbols = '';

        $oiBorderExchangeFile = $_SERVER['DOCUMENT_ROOT'] . '/upload/'.$marketMode.'Exchange/15m/oiBorderExchange.json';
        $oiBorderExchangeFileData = file_exists($oiBorderExchangeFile) ? json_decode(file_get_contents($oiBorderExchangeFile), true) ?? [] : [];
        $oiBorderExchangeList = $oiBorderExchangeFileData['RESPONSE'];
        $oiBorderExchangeInfo = $oiBorderExchangeFileData['INFO'];

        foreach ($exchangeBinanceSymbolsList as &$symbol) {

            try {
                $screenerData = $res['screenerPump'] = $res['screenerDump'] = [];
                $screenerData['marketCode'] = $marketMode;

                $symbolName = $screenerData['symbolName'] = $symbol['symbol'];
                $symbolScale = $screenerData['symbolScale'] = intval($symbol['pricePrecision']) ?? 6;
                $symbolMaxLeverage = $screenerData['symbolMaxLeverage'] = floatval($symbol['leverageFilter']['maxLeverage']) ?? 10;

                $screenerData['interval'] = $interval;

                //dev $marketInfo['isLong'] = true; $marketInfo['risk'] = 5;
                if (!$marketInfo['isShort'] && !$marketInfo['isLong'])
                    continue;

                if (!$existingDataSeparateVolume[$symbolName]['resBinance'])
                    continue;

                $separateVolumes = array_reverse($existingDataSeparateVolume[$symbolName]['resBinance']) ?? [];
                //$analyzeVolumeSignalRes = \Maksv\TechnicalAnalysis::analyzeVolumeSignal($separateVolumes, 5, 0.2, 0.55) ?? [];
                $analyzeFastVolumeSignalRes = \Maksv\TechnicalAnalysis::analyzeVolumeSignal($separateVolumes, 3, 0.39, 0.55);

                if (!$analyzeFastVolumeSignalRes['isLong'] && !$analyzeFastVolumeSignalRes['isShort'])
                    continue;

                if (
                    ($marketInfo['isShort'] && $analyzeFastVolumeSignalRes['isLong'])
                    || ($marketInfo['isLong'] && $analyzeFastVolumeSignalRes['isShort'])
                )
                    continue;

                $screenerData['analyzeVolume'] = $symbol['analyze'] = $analyzeFastVolumeSignalRes;
                $cnt++;

                //периодически обновляем данные
                if ($cnt % 20 === 0)
                    $latestScreener = \Maksv\DataOperation::getLatestScreener($binanceScreenerIblockId);

                if ($cnt % 15 === 0)
                    $latestScreenerBetaForever = \Maksv\DataOperation::getLatestScreener($betaForeverScreenerIblockId);

                if ($cnt % 50 === 0)
                    $marketInfo = \Maksv\Bybit\Exchange::checkMarketImpulsInfo();

                //dev $marketInfo['isLong'] = true; $marketInfo['risk'] = 5;

                $screenerData['latestScreener'] = $latestScreener;
                $screenerData['latestScreenerBetaForever'] = $latestScreenerBetaForever;

                if ($latestScreener[$symbolName]) {
                    $repeatSymbols .= $symbolName . ',';
                    continue;
                }
                //devlogs("c3.1 ",  $marketMode . '/screener' . $interval);

                if (
                    !isset($symbol['symbol'])
                    || !is_string($symbol['symbol'])
                    || preg_match('/^(ETHUSDT-|ETH-|BTCUSDT-|BTC-|SOLUSDT-)/', $symbol['symbol'])
                    || !in_array($symbol['quoteAsset'], ['USDT'])
                    || in_array($symbol['symbol'], self::SYMBOLS_STOP_LIST1)
                    || in_array($symbol['symbol'], self::SYMBOLS_STOP_LIST_MAIN)

                ) {
                    $continueSymbols .= $symbol['symbol'] . ', ';
                    continue;
                }

                $intervalsOImap = [
                    '5m' => '15m',
                    '15m' => '15m',
                    '30m' => '15m',
                    '1h' => '30m',
                    '4h' => '30m',
                    '1d' => '30m',
                ];

                $summaryOpenInterestOb = \Maksv\Bybit\Exchange::getSummaryOpenInterestDev($symbolName, $binanceApiOb, $bybitApiOb, $okxApiOb, $binanceSymbolsList, $bybitSymbolsList, $okxSymbolsList, $intervalsOImap[$interval]);
                //$summaryOpenInterestOb = \Maksv\Bybit\Exchange::getSummaryOpenInterest($symbolName, $binanceApiOb, $bybitApiOb, $binanceSymbolsList, $bybitSymbolsList, $intervalsOImap[$interval]);
                if (!$summaryOpenInterestOb['summaryOIBinance']) {
                    //devlogs('ERR ' . $symbolName . ' | err - oi ('.$summaryOpenInterestOb['summaryOI'].') ('.$summaryOpenInterestOb['summaryOIBybit'].')' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener'.$interval);
                    //devlogs($summaryOpenInterestOb, $marketMode . '/screener'.$interval);
                    continue;
                }

                $summaryOIBinance = $screenerData['summaryOIBinance'] = $summaryOpenInterestOb['summaryOIBinance'] ?? 0;
                $summaryOIBybit = $screenerData['summaryOIBybit'] = $summaryOpenInterestOb['summaryOIBybit'] ?? 0;
                $summaryOIOkx = $screenerData['summaryOIOkx'] = $summaryOpenInterestOb['summaryOIOkx'] ?? 0;
                $summaryOI = $screenerData['summaryOI'] = $summaryOpenInterestOb['summaryOI'] ?? 0;

                //проверяем есть ли вычисленная граница открытого интереса
                $longOiLimit = $currentLongOiLimit;
                $shortOiLimit = $currentShortOiLimit;
                if (isset($oiBorderExchangeList[$symbolName])) {

                    $borderLong = floatval($oiBorderExchangeList[$symbolName]['borderLong']) ?? 0;
                    $avgBorderLong = floatval($oiBorderExchangeInfo['avgBorderLong']) ?? 0;
                    if ($borderLong && $borderLong > 0.3)
                        $longOiLimit = $borderLong;
                    elseif ($avgBorderLong && $avgBorderLong > 0.3)
                        $longOiLimit = $avgBorderLong;

                    $borderShort = floatval($oiBorderExchangeList[$symbolName]['borderShort']);
                    $avgBorderShort = floatval($oiBorderExchangeInfo['avgBorderShort']);
                    if ($borderShort && $borderShort < -0.3)
                        $shortOiLimit = $borderShort;
                    elseif ($avgBorderShort && $avgBorderShort < -0.3)
                        $shortOiLimit = $avgBorderShort;

                    if ($longOiLimit < 0.7)
                        $longOiLimit = $longOiLimit * 1.5;
                    else if ($longOiLimit < 1)
                        $longOiLimit = $longOiLimit * 1.2;

                    if ($shortOiLimit > -0.7)
                        $shortOiLimit = $shortOiLimit * 1.7;
                    else if ($shortOiLimit > -1)
                        $shortOiLimit = $shortOiLimit * 1.2;;

                }
                $screenerData['oiLimits'] = ['longOiLimit' => $longOiLimit, 'shortOiLimit' => $shortOiLimit];

                if (
                    !($summaryOIBinance >= $longOiLimit)
                    && !($summaryOIBinance <= $shortOiLimit)
                ) {
                    //devlogs('ERR ' . $symbolName . ' | err - OI lim' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener'.$interval);
                    continue;
                }

                //$priceChangeRange = false;
                $barsCount = 802;
                $kline = $binanceApiOb->kline($symbolName, $interval, $barsCount, false, false, true, 120);
                if (empty($kline) || !is_array($kline)) {
                    devlogs('ERR ' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    continue;
                }

                usort($kline, fn($a, $b) => $a[0] <=> $b[0]);
                $klineList = ($kline);
                $candles = array_map(function ($k) {
                    return [
                        't' => floatval($k[0]), // timestap
                        'o' => floatval($k[1]), // Open price
                        'h' => floatval($k[2]), // High price
                        'l' => floatval($k[3]), // Low price
                        'c' => floatval($k[4]), // Close price
                        'v' => floatval($k[5])  // Volume
                    ];
                }, $klineList);

                $lastIndex = array_key_last($candles);
                //цена
                $priceChange = $screenerData['priceChange'] = round(((floatval($candles[$lastIndex - 1]['c']) - floatval($candles[$lastIndex - 1]['o'])) / floatval($candles[$lastIndex - 1]['o'])) * 100, 2);
                $absPriceChangeRange = $screenerData['priceChangeRange'] = abs(round(((floatval($candles[$lastIndex - 1]['c']) - floatval($candles[$lastIndex - 3]['o'])) / floatval($candles[$lastIndex - 3]['o'])) * 100, 2));
                $priceChangeRange = $screenerData['priceChangeRange'] = (round(((floatval($candles[$lastIndex - 1]['c']) - floatval($candles[$lastIndex - 3]['o'])) / floatval($candles[$lastIndex - 3]['o'])) * 100, 2));
                $actualClosePrice = $screenerData['actualClosePrice'] = $screenerData['entryTarget'] = $candles[$lastIndex]['c'] ?? false;

                /*if ($absPriceChangeRange > 3)
                   continue;*/

                //объемы
                $volumeMA = \Maksv\TechnicalAnalysis::calculateVolumeMA($candles, 5, 2) ?? false;
                $actualVolumeMA = false;
                if ($volumeMA && is_array($volumeMA)) {
                    $actualVolumeMA = $screenerData['actualvolumeMA'] = $volumeMA[array_key_last($volumeMA) - 1] ?? false;
                }

                $screenerData['isVolumeIncreasing'] = $isVolumeIncreasing = $actualVolumeMA['isUptrend'] ?? false;
                $screenerData['volumeChangePercent'] = $volumeChangePercent = $actualVolumeMA['changePercent'] ?? false;

                if ($actualVolumeMA['isFlat'] || $actualVolumeMA['flatDistance'] !== false)
                    $screenerData['volumeIsFlat'] = true;

                $screenerData['actualMacd'] = $actualMacd = [];
                try {
                    $macdData = \Maksv\TechnicalAnalysis::analyzeMACD($candles) ?? false;
                    $screenerData['actualMacd'] = $actualMacd = $macdData[array_key_last($macdData)] ?? false;
                } catch (Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - actualMacd' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                $screenerData['actualImpulsMacd'] = $actualImpulsMacd = [];
                try {
                    $impulseMACD = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles) ?? false;
                    if ($impulseMACD && is_array($impulseMACD))
                        $screenerData['actualImpulsMacd'] = $actualImpulsMacd = $impulseMACD[array_key_last($impulseMACD)];
                } catch (Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - actualImpulsMacd' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }


                $candles5m = [];
                $kline5m = $binanceApiOb->kline($symbolName, '5m', $barsCount, false, false, true, 120);
                if (empty($kline5m) || !is_array($kline5m)) {
                    devlogs('ERR 2' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    //continue;
                } else {
                    usort($kline5m, fn($a, $b) => $a[0] <=> $b[0]);
                    $kline5mList = ($kline5m);
                    $candles5m = array_map(function ($k) {
                        return [
                            't' => floatval($k[0]), // timestap
                            'o' => floatval($k[1]), // Open price
                            'h' => floatval($k[2]), // High price
                            'l' => floatval($k[3]), // Low price
                            'c' => floatval($k[4]), // Close price
                            'v' => floatval($k[5])  // Volume
                        ];
                    }, $kline5mList);
                }

                $actualSupertrend5m = [];
                try {
                    $supertrendData5m = \Maksv\TechnicalAnalysis::calculateSupertrend($candles5m, 10, 3) ?? false; // длина 10, фактор 3
                    $screenerData['actualSupertrend5m'] = $actualSupertrend5m = $supertrendData5m[array_key_last($supertrendData5m)] ?? false;
                    if (!$actualSupertrend5m)
                        devlogs('ERR ' . $symbolName . ' | err - actualSupertrend5m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);

                } catch (Exception $e) {
                    devlogs('ERR | err - Supertrend 5m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                $candles15m = $candles;
                if ($interval != '15m') {
                    $kline15m = $binanceApiOb->kline($symbolName, '15m', $barsCount, false, false, true, 120);
                    if (empty($kline15m) || !is_array($kline15m)) {
                        devlogs('ERR 3' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                        //continue;
                    } else {
                        usort($kline15m, fn($a, $b) => $a[0] <=> $b[0]);
                        $kline15mList = ($kline15m);
                        $candles15m = array_map(function ($k) {
                            return [
                                't' => floatval($k[0]), // timestap
                                'o' => floatval($k[1]), // Open price
                                'h' => floatval($k[2]), // High price
                                'l' => floatval($k[3]), // Low price
                                'c' => floatval($k[4]), // Close price
                                'v' => floatval($k[5])  // Volume
                            ];
                        }, $kline15mList);

                    }
                }
                $screenerData['candles15m'] = array_slice($candles15m, -30);

                $screenerData['actualMacdDivergence'] = $actualMacdDivergence = [];
                try {
                    $screenerData['actualMacdDivergence'] = $actualMacdDivergence = \Maksv\Bybit\Exchange::checkMultiMACD(
                        $candles15m,
                        '15m',
                        ['5m' => 14, '15m' => 14, '30m' => 14, '1h' => 14, '4h' => 8, '1d' => 6]
                    );
                } catch (Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - actualMacdDivergence' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                $actualSupertrend15m = $screenerData['actualSupertrend15m'] = [];
                try {
                    $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($candles15m, 10, 3) ?? false; // длина 10, фактор 3
                    $screenerData['actualSupertrend15m'] = $actualSupertrend15m = $supertrendData[array_key_last($supertrendData)] ?? false;
                } catch (Exception $e) {
                    devlogs('ERR | err - Supertrend 15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                //если лонг, а тренды все down
                if (
                    $analyzeFastVolumeSignalRes['isLong']
                    && ($actualSupertrend15m && !$actualSupertrend15m['isUptrend'])
                    && ($actualSupertrend5m && !$actualSupertrend5m['isUptrend'])
                ) {
                    //devlogs('dev | ' . $symbolName . ' long down down' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener'.$interval);
                    continue;
                }

                // если шорт, а тернды все up
                if (
                    $analyzeFastVolumeSignalRes['isShort']
                    && ($actualSupertrend15m && $actualSupertrend15m['isUptrend'])
                    && ($actualSupertrend5m && $actualSupertrend5m['isUptrend'])
                ) {
                    //devlogs('dev | ' . $symbolName . ' short up up' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener'.$interval);
                    continue;
                }

                $maHis = $ma50His = $ma100His = $ma200His = $ma400His = [];
                $ma26 = $ma50 = $ma100 = $ma200 = $ma400 = [];
                if (is_array($candles) && count($candles) >= 52) {
                    try {
                        $maHis = \Maksv\TechnicalAnalysis::getMACrossHistory($candles, 9, 26, 102) ?? [];
                        $ma26 = $maHis[array_key_last($maHis)];
                    } catch (Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - ma26' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }
                if (!$ma26)
                    devlogs('ERR ' . $symbolName . ' | err - ma26' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);


                if (is_array($candles) && count($candles) >= 102) {
                    try {
                        $ma50His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles, 12, 50, 102) ?? [];
                        $ma50 = $ma50His[array_key_last($ma50His)];
                    } catch (Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - ma50' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }

                if (is_array($candles15m) && count($candles15m) >= 202) {
                    try {
                        $ma100His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles15m, 9, 100, 102) ?? [];
                        $ma100 = $ma100His[array_key_last($ma100His)];
                    } catch (Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - ma100 candles15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }
                if (!$ma100)
                    devlogs('ERR ' . $symbolName . ' | err - ma100' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);


                if (is_array($candles15m) && count($candles15m) >= 402) {
                    try {
                        $ma200His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles15m, 9, 200, 102) ?? [];
                        $ma200 = $ma200His[array_key_last($ma200His)];
                    } catch (Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - ma200' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }

                if (is_array($candles15m) && count($candles15m) >= 802) {
                    try {
                        $ma400His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles15m, 9, 400, 10) ?? [];
                        $ma400 = $ma400His[array_key_last($ma400His)];
                    } catch (Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - ma400 candles15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }

                $screenerData['maAr'] = $maAr = [
                    'ma26' => $maHis,
                    'ma50' => $ma50His,
                    'ma100' => $ma100His,
                    'ma200' => $ma200His,
                    'ma400' => $ma400His,
                ];

                $screenerData['ma400'] = $ma400;
                $screenerData['ma200'] = $ma200;
                $screenerData['ma50'] = $ma50;
                $screenerData['ma100'] = $ma100;
                $screenerData['ma26'] = $ma26;

                $actualATR = [];
                try {
                    // Рассчитываем ATR по свечам
                    $ATRData = \Maksv\TechnicalAnalysis::calculateATR($candles15m);
                    $screenerData['actualATR'] = $actualATR = $ATRData[array_key_last($ATRData)] ?? null;
                } catch (Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - atr' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                $actualAdx = false;
                try {
                    $adxData = \Maksv\TechnicalAnalysis::calculateADX($candles15m) ?? [];
                    $screenerData['actualAdx'] = $actualAdx = $adxData[array_key_last($adxData)];
                } catch (Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - adx' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                if (
                    $actualAdx
                    && (
                        $actualAdx['adx'] < 20
                        || ($actualAdx['adx'] < 26 && $actualAdx['adxDirection']['isDownDir'] === true)
                    )
                ) {
                    devlogs('CONTINUE ' . $symbolName . ' |  adx 15m ' . $actualAdx['adx'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    continue;
                }

                $candles1h = [];
                $actualAdx1h = false;
                $kline1h = $binanceApiOb->kline($symbolName, '1h', 100, false, false, true, 120);
                if (empty($kline1h) || !is_array($kline1h)) {
                    devlogs('ERR 4' . $symbolName . ' | err - klinen 1h' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    //continue;
                } else {
                    usort($kline1h, fn($a, $b) => $a[0] <=> $b[0]);
                    $kline1hList = ($kline1h);
                    $candles1h = array_map(function ($k) {
                        return [
                            't' => floatval($k[0]), // timestap
                            'o' => floatval($k[1]), // Open price
                            'h' => floatval($k[2]), // High price
                            'l' => floatval($k[3]), // Low price
                            'c' => floatval($k[4]), // Close price
                            'v' => floatval($k[5])  // Volume
                        ];
                    }, $kline1hList);

                    try {
                        $stochasticOscillatorData1h = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles1h) ?? false;
                        if ($stochasticOscillatorData1h && is_array($stochasticOscillatorData1h))
                            $screenerData['actualStochastic1h'] = $actualStochastic1h = $stochasticOscillatorData1h[array_key_last($stochasticOscillatorData1h)];

                    } catch (Exception $e) {
                        devlogs('ERR err - stoch 1h ' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }

                    try {
                        $adxData1h = \Maksv\TechnicalAnalysis::calculateADX($candles1h) ?? [];
                        $screenerData['actualAdx1h'] = $actualAdx1h = $adxData1h[array_key_last($adxData1h)];
                    } catch (Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - adx 1h' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }

                if (
                    $actualAdx1h
                    && (
                        $actualAdx1h['adx'] < 20
                        || ($actualAdx1h['adx'] < 26 && $actualAdx1h['adxDirection']['isDownDir'] === true)
                    )
                ) {
                    $continueSymbols .= $symbol['symbol'] . ', ';
                    devlogs('CONTINUE ' . $symbolName . ' |  adx 1h ' . $actualAdx1h['adx'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    continue;
                }

                //risk/profit
                $atrMultipliers = $marketInfo['atrMultipliers'];
                if (!$atrMultipliers || !is_array($atrMultipliers)) $atrMultipliers = [2.3, 2.9, 3.3];

                $longTpCount = $marketInfo['longTpCount'] ?? 3;
                $shortTpCount = $marketInfo['shortTpCount'] ?? 3;

                $screenerData['tpCount'] = [
                    'longTpCount' => $longTpCount,
                    'shortTpCount' => $shortTpCount,
                ];

                $screenerData['atrMultipliers'] = $atrMultipliers;

                $maDistance = 2.5;
                if (
                    ($summaryOIBinance >= $longOiLimit)
                    && $marketInfo['isLong']
                    && $analyzeFastVolumeSignalRes['isLong']
                    && (
                        $actualMacd['isLong']
                        || ($actualImpulsMacd['isLong'] && $interval != '30m')
                        || ($ma100['isLong'] && $interval == '15m')
                        || ($ma200['isLong'] && $interval == '15m')
                        || ($ma400['isLong'] && $interval == '15m')
                    )
                    && \Maksv\Bybit\Exchange::checkMaCondition($ma26,  $actualClosePrice, $maDistance, 'long')
                    && \Maksv\Bybit\Exchange::checkMaCondition($ma100, $actualClosePrice, $maDistance, 'long')
                    && \Maksv\Bybit\Exchange::checkMaCondition($ma200, $actualClosePrice, $maDistance, 'long')
                    && \Maksv\Bybit\Exchange::checkMaCondition($ma400, $actualClosePrice, $maDistance, 'long')
                    && (!$actualMacdDivergence['shortDivergenceTypeAr']['regular'] && !$actualMacdDivergence['shortDivergenceTypeAr']['hidden'])
                ) {
                    $screenerData['isLong'] = true;

                    // помечаем стратегию
                    if ($actualMacd['isLong']) {
                        if ($actualMacdDivergence['longDivergenceTypeAr']['regular'] || $actualMacdDivergence['longDivergenceTypeAr']['hidden'])
                            $screenerData['strategy'] = 'macdD/macdC/MAfar';
                        else
                            $screenerData['strategy'] = 'macd/!d/MAfar';
                    } else if ($actualImpulsMacd['isLong']) {
                        $screenerData['strategy'] = 'macdI/direct/!d/MAfar';
                    } else if ($ma100['isLong']) {
                        $screenerData['strategy'] = 'MA100xEMA9/MACD';
                    } else if ($ma200['isLong']) {
                        $screenerData['strategy'] = 'MA200xEMA9/MACD';
                    } else if ($ma400['isLong']) {
                        $screenerData['strategy'] = 'MA400xEMA9/MACD';
                    }

                    // обнуляем поля перед вызовом processSignal
                    $screenerData['SL'] = $screenerData['TP'] = $screenerData['recommendedEntry'] = false;

                    // вызываем общую функцию вместо дублирования логики
                    $processed = \Maksv\Bybit\Exchange::processSignal(
                        'long',
                        floatval($actualATR['atr']),
                        floatval($actualClosePrice),
                        $candles15m,
                        $actualSupertrend5m,
                        $actualSupertrend15m,
                        $actualMacdDivergence,
                        $symbolScale,
                        $atrMultipliers,
                        $marketInfo,                  // содержит параметры риска и флаги isLong/isShort
                        $symbolName,
                        "$marketMode/screener$interval"
                    );

                    if ($processed !== false) {
                        $screenerData = array_merge($screenerData, $processed);
                        $res['screenerPump'][$symbolName] = $screenerData;
                    } else {
                        $continueSymbols .= $symbol['symbol'] . ', ';
                    }

                } else if (
                    ($summaryOIBinance <= $shortOiLimit)
                    && $marketInfo['isShort']
                    && $analyzeFastVolumeSignalRes['isShort']
                    && (
                        $actualMacd['isShort']
                        || ($actualImpulsMacd['isShort'] && $interval != '30m')
                        || ($ma100['isShort'] && $interval == '15m')
                        || ($ma200['isShort'] && $interval == '15m')
                        || ($ma400['isShort'] && $interval == '15m')
                    )
                    && \Maksv\Bybit\Exchange::checkMaCondition($ma26,  $actualClosePrice, $maDistance, 'short')
                    && \Maksv\Bybit\Exchange::checkMaCondition($ma100, $actualClosePrice, $maDistance, 'short')
                    && \Maksv\Bybit\Exchange::checkMaCondition($ma200, $actualClosePrice, $maDistance, 'short')
                    && \Maksv\Bybit\Exchange::checkMaCondition($ma400, $actualClosePrice, $maDistance, 'short')
                    && (!$actualMacdDivergence['longDivergenceTypeAr']['regular'] && !$actualMacdDivergence['longDivergenceTypeAr']['hidden'])
                ) {
                    $screenerData['isLong'] = false;

                    if ($actualMacd['isShort']) {
                        if ($actualMacdDivergence['shortDivergenceTypeAr']['regular'] || $actualMacdDivergence['shortDivergenceTypeAr']['hidden'])
                            $screenerData['strategy'] = 'macdD/macdC/MAfar';
                        else
                            $screenerData['strategy'] = 'macd/!d/MAfar';
                    } else if ($actualImpulsMacd['isShort']) {
                        $screenerData['strategy'] = 'macdI/direct/!d/MAfar';
                    } else if ($ma100['isShort']) {
                        $screenerData['strategy'] = 'MA100xEMA9/MACD';
                    } else if ($ma200['isShort']) {
                        $screenerData['strategy'] = 'MA200xEMA9/MACD';
                    } else if ($ma400['isShort']) {
                        $screenerData['strategy'] = 'MA400xEMA9/MACD';
                    }

                    // обнуляем поля перед вызовом processSignal
                    $screenerData['SL'] = $screenerData['TP'] = $screenerData['recommendedEntry'] = false;

                    // вызываем общую функцию с direction = 'short'
                    $processed = \Maksv\Bybit\Exchange::processSignal(
                        'short',
                        floatval($actualATR['atr']),
                        floatval($actualClosePrice),
                        $candles15m,
                        $actualSupertrend5m,
                        $actualSupertrend15m,
                        $actualMacdDivergence,
                        $symbolScale,
                        $atrMultipliers,
                        $marketInfo,
                        $symbolName,
                        "$marketMode/screener$interval"
                    );
                    
                    if ($processed !== false) {
                        $screenerData = array_merge($screenerData, $processed);
                        $res['screenerDump'][$symbolName] = $screenerData;
                    } else {
                        $continueSymbols .= $symbol['symbol'] . ', ';
                    }
                }

                $analyzeCnt++;
                $analyzeSymbols .= $symbolName . ', ';
                //devlogs("next step (cnt " . $cnt . ") (symbol " . $symbolName . ") -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener'.$interval);

                if ($res['screenerPump'] || $res['screenerDump']) {
                    $cntSuccess++;

                    $chartsDir = $_SERVER["DOCUMENT_ROOT"] . '/upload/charts/';
                    if (!is_dir($chartsDir))
                        mkdir($chartsDir);

                    try {
                        //график по цене
                        $priceChartGen = new \Maksv\Charts\PriceChartGenerator(); // можно указать свои размеры, если нужно
                        $screenerData['tempChartPath'][] = $tempPriceChartPath = $chartsDir . time() . '_' . $interval . '_price' . '.png';
                        $priceChartGen->generateChart($candles, $symbolName, $interval, $tempPriceChartPath, $maAr);

                        //график по объемам
                        $volumeChartGen = new \Maksv\Charts\VolumeChartGenerator(); // можно указать свои размеры, если нужно
                        $screenerData['tempChartPath'][] = $tempVolumeChartPath = $chartsDir . time() . '_' . $interval . '_volume' . '.png';
                        $volumeChartGen->generateChart($candles, $volumeMA, $symbolName, $interval, $tempVolumeChartPath);

                        //график cvd
                        $separateVolumes = array_reverse(\Maksv\Bybit\Exchange::aggregateSumVolume5mTo15m($separateVolumes)) ?? [];
                        $cvdChartGen = new \Maksv\Charts\CvdChartGenerator();
                        $screenerData['tempChartPath'][] = $tempCVDChartPath = $chartsDir . time() . '_' . $interval . '_cvd' . '.png';
                        $cvdChartGen->generateChart($separateVolumes, $symbolName, '15m', $tempCVDChartPath);
                    } catch (\Throwable $e) {
                        devlogs('ERR ' . $symbolName . ' | err - PriceChartGenerator' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }

                    if ($screenerData['isLong']){
                        $screenerData['marketMLName'] = 'longMl';
                        $actualStrategyName = 'screenerPump';
                    } else {
                        $screenerData['marketMLName'] = 'shortMl';
                        $actualStrategyName = 'screenerDump';
                    }

                    $screenerData['resML']['marketMl'] = $marketMl = $marketInfo[$screenerData['marketMLName']]['probabilities'][1] ?? false;
                    $screenerData['resML']['signalMl'] = $signalMl = $screenerData['actualMlModel']['probabilities'][1] ?? false;
                    $screenerData['resML']['totalMl'] = $totalMl = false;
                    if ($marketMl && $signalMl) {
                        $screenerData['resML']['totalMl'] = $totalMl = ($marketMl + $signalMl) / 2;
                    }

                    \Maksv\DataOperation::sendScreener($screenerData, true, '@infoCryptoHelperScreenerBinance');

                    foreach ($screenerData['tempChartPath'] as $path)
                        unlink($path);

                    $screenerData['tempChartPath'] = [];
                    $screenerData['mlBoard'] = $mlBoard = $marketInfo['mlBoard'] ?? 0.71;

                    //после всех мутаций снимаем копию
                    $res[$actualStrategyName][$symbolName] = $screenerData;

                    if (
                        $marketMl
                        && $signalMl
                        && $totalMl
                        && $marketMl > 0.65
                        && $signalMl > 0.65
                        && $totalMl >= $mlBoard
                        && !$latestScreenerBetaForever[$symbolName]
                        //&& $interval != '5m'
                    ) {
                        devlogs(
                            'ml approve | ' . $totalMl . ' > ' . $mlBoard . ' | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
                            $marketMode . '/screener' . $interval
                        );

                        //Prophet AI //сбор статистики
                        $screenerData['leverage'] = '5x';
                        \Maksv\DataOperation::sendScreener($screenerData, false, '@cryptoHelperProphetAi');

                        //мой бот для торговли bybit // check ML
                        $screenerData['leverage'] = '10x';
                        if ($screenerData['isLong'])
                            $screenerData['TP'] = array_slice($screenerData['calculateRiskTargetsWithATR']['takeProfits'], 0, $longTpCount);
                        else
                            $screenerData['TP'] = array_slice($screenerData['calculateRiskTargetsWithATR']['takeProfits'], 0, $shortTpCount);

                        if (!is_array($screenerData['TP']) || count($screenerData['TP']) == 0)
                            $screenerData['TP']  = $screenerData['calculateRiskTargetsWithATR']['takeProfits'];

                        \Maksv\DataOperation::sendScreener($screenerData, false, '@cryptoHelperCornixTreadingBot');

                        //сохраняем в beta forever
                        $actualStrategyBeta = [
                            "TIMEMARK" => date("d.m.y H:i:s"),
                            "STRATEGIES" => $res,
                            "INFO" => [
                                'BTC_INFO' => $marketInfo,
                            ],
                            "EXCHANGE_CODE" => 'screener'
                        ];

                        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/'.$marketMode.'Exchange/screener/' . $interval . '/actualStrategyBeta.json', json_encode($actualStrategyBeta));
                        try {
                            $writeResBeta = \Maksv\DataOperation::saveSignalToIblock($interval, $marketMode, 'screenerB', $marketMode);
                            devlogs('screener beta forever write' . $writeResBeta['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                        } catch (Exception $e) {
                            $errText = 'ERR beta forever write - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s");
                            \Maksv\DataOperation::sendErrorInfoMessage($errText, 'screener', $marketMode . '/screener' . $interval);
                            devlogs($errText, $marketMode . '/screener' . $interval);
                        }

                    } else  {
                        devlogs(
                            'ML skip | ' . $signalMl . ' ' . $marketMl . ' ' . $totalMl . ' | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
                            $marketMode . '/screener' . $interval
                        );
                        $continueSymbols .= $symbol['symbol'] . ', ';
                    }

                    $actualStrategy = [
                        "TIMEMARK" => date("d.m.y H:i:s"),
                        "STRATEGIES" => $res,
                        "INFO" => [
                            'BTC_INFO' => $marketInfo,
                        ],
                        "EXCHANGE_CODE" => 'screener'
                    ];
                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/'.$marketMode.'Exchange/screener/' . $interval . '/actualStrategy.json', json_encode($actualStrategy));
                    try {
                        $writeRes = \Maksv\DataOperation::saveSignalToIblock($interval, $marketMode, 'screener');
                        devlogs('screener write' . $writeRes['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    } catch (Exception $e) {
                        devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }

                /*if ($devMode && $cnt >= 50)
                    break;*/
            } catch (Exception $e) {
                devlogs('ERR ' . $symbolName . ' | err -' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
            }
        }
        $bybitApiOb->closeConnection();
        $binanceApiOb->closeConnection();
        $okxApiOb->closeConnection();
        devlogs($marketInfo['infoText'], $marketMode . '/screener' . $interval);
        devlogs('repeatSymbols - ' . $repeatSymbols, $marketMode . '/screener' . $interval);
        devlogs('analyzeSymbols - ' . $analyzeSymbols, $marketMode . '/screener' . $interval);
        devlogs('continueSymb - ' . $continueSymbols, $marketMode . '/screener' . $interval);
        devlogs("end (cnt " . $cnt . ") (analyzeCnt " . $analyzeCnt . ") (cntSuccess " . $cntSuccess . ") -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
        devlogs('_____________________________________', $marketMode . '/screener' . $interval);

        $cntInfo = [
            'count' => $cnt,
            'analysisCount' => $analyzeCnt,
            'analysisSymbols' => $analyzeSymbols,
            'continueSymb' => $continueSymbols,
        ];

        if ($interval == '15m')
            \Maksv\DataOperation::sendInfoMessage([], $interval, $marketInfo, $cntInfo, true, 'BINANCE');

        return $res;
    }

}
