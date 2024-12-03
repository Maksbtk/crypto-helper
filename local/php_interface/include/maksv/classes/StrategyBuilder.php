<?php
namespace Maksv;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class StrategyBuilder
{
    public function __construct(){}
    
    public static function findPumpOrDumpOpportunities($actualSymbolsAr = [], $timeFrame = '4h', $marketCode = 'bybit', $thresholdPercent = 10)
    {
        $err = [];
        $actualOpportunities = [
            'pump' => [],
            'dump' => [],
            'masterDump' => [],
            'masterPump' => []
        ];

        if (!$actualSymbolsAr)
            $actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'V5Exchange/'.$timeFrame.'/marketVolumes.json'), true))['RESPONSE_EXCHENGE'] ?? [];

        $timeMark = date("d.m.y H:i:s");
        devlogs($timeFrame. ' start - ' . $timeMark, $marketCode . 'findPumpOrDumpOpportunities');

        $actualOpportunities['allDump'] = $actualOpportunities['allPump'] = $actualOpportunities['pump'] = $actualOpportunities['dump'] = [];
        foreach ($actualSymbolsAr as $symbolName => $symbolVolumes)
        {
            $anomalyThresholdOI = 7;
            $trendReversalRange = 1;
            if ($timeFrame == '30m') {
                $anomalyThresholdOI = 8;
                $trendReversalRange = 5;
            } else if ($timeFrame == '1h') {
                $anomalyThresholdOI = 15;
                $trendReversalRange = 4;
            } else if ($timeFrame == '4h') {
                $anomalyThresholdOI = 15;
                $trendReversalRange = 3;
            } else if ($timeFrame == '1d') {
                $anomalyThresholdOI = 15;
                $trendReversalRange = 2;
            }

            $actualSnap = $symbolVolumes ?? false;
            if ($actualSnap) {

                $actualCrossMA = $actualSnap['crossHistoryMA'][array_key_last($actualSnap['crossHistoryMA'])];
                $crossMAVal = 0;
                if ($actualCrossMA['is_reversal'] && $actualCrossMA['isUptrend'])
                    $crossMAVal = 1;
                else if ($actualCrossMA['is_reversal'] && !$actualCrossMA['isUptrend'])
                    $crossMAVal = 2;

                $MAReversalFlag = self::checkRecentTrendReversal($actualSnap['crossHistoryMA'], $trendReversalRange) ?? false;

                $actualSAR = $sarReversalFlag = false;
                if ($actualSnap['sarData'] && is_array($actualSnap['sarData']))
                    $actualSAR = $actualSnap['sarData'][array_key_last($actualSnap['sarData'])];

                $sarVal = 0;
                if ($actualSAR) {
                    if ($actualSAR['is_reversal'] && $actualSAR['trend'] == 'up')
                        $sarVal = 1;
                    else if ($actualSAR['is_reversal'] && $actualSAR['trend'] == 'down')
                        $sarVal = 2;
                }

                $sarReversalFlag = self::checkRecentTrendReversal($actualSnap['supertrendData'], $trendReversalRange);

                $actualSupertrend = $superTrandReversalFlag = false;
                if ($actualSnap['supertrendData'] && is_array($actualSnap['supertrendData']))
                    $actualSupertrend = $actualSnap['supertrendData'][array_key_last($actualSnap['supertrendData'])];

                $supertrendVal = 0;
                if ($actualSupertrend) {
                    if ($actualSupertrend['is_reversal'] && $actualSupertrend['isUptrend'])
                        $supertrendVal = 1;
                    else if ($actualSupertrend['is_reversal'] && $actualSupertrend['isUptrend'])
                        $supertrendVal = 2;
                }

                if (in_array($symbolName, ['BTCUSDT', 'ETHUSDT']))
                    $trendReversalRange = 3;

                $superTrandReversalFlag = self::checkRecentTrendReversal($actualSnap['supertrendData'], $trendReversalRange);

                $actualStochastic = false;
                if ($actualSnap['stochasticOscillatorData'] && is_array($actualSnap['stochasticOscillatorData']))
                    $actualStochastic = $actualSnap['stochasticOscillatorData'][array_key_last($actualSnap['stochasticOscillatorData'])];

                $opportunitieData = [
                    'symbolName' => $symbolName,
                    'lastRsi' => $actualSnap['rsi'],
                    'lastClosePrice' => $actualSnap['lastClosePrice'],
                    'lastOpenInterest' => $actualSnap['openInterest'],
                    'lastPriceChange' => $actualSnap['priceChange'],
                    'timestapOI' => $actualSnap['timestapOI'],
                    'lastSAR' => $actualSAR,
                    'sarReversalFlag' => $sarReversalFlag,
                    'sarVal' => $sarVal,
                    'actualStochastic' => $actualStochastic,
                    'lastSupertrend' => $actualSupertrend,
                    'supertrendVal' => $supertrendVal,
                    'superTrandReversalFlag' => $superTrandReversalFlag,
                    'crossMAData' => $actualSnap['crossHistoryMA'],
                    'lastCrossMA' => $actualCrossMA,
                    'crossMAVal' => $crossMAVal,
                    'MAReversalFlag' => $MAReversalFlag,
                    'timeMark' => $timeMark,
                    'snapTimeMark' => $actualSnap['timeMark'],
                    'timeFrame' => $timeFrame,
                    'anomalyOI' => ($actualSnap['openInterest'] >= $anomalyThresholdOI),
                ];

                if (in_array($symbolName, ['BTCUSDT', 'ETHUSDT'])) {
                    $actualOpportunities['headCoin'][$symbolName] = $opportunitieData;
                    continue;
                }

                //master
                if (
                    (
                        in_array($timeFrame, ['1h', '1d', '4h', '30m'])
                        && ($actualSnap['openInterest'] > 0)
                        && ((
                            $actualCrossMA['sma'] <= $actualSnap['lastClosePrice']
                            && $actualCrossMA['sma'] > $actualCrossMA['ema']
                            && ($actualSupertrend['isUptrend'])
                            && !$actualCrossMA['is_reversal']
                            ) || (
                            $actualSupertrend['isUptrend']
                            && $actualCrossMA['is_reversal']
                            && $actualCrossMA['isUptrend']
                        ))
                    )
                ) {
                    $actualOpportunities['masterPump'][$symbolName] = $opportunitieData;
                }

                if (
                    (
                        in_array($timeFrame, ['1d', '4h', '1h', '30m'])
                        && ($actualSnap['openInterest'] > 0)
                        && (
                            (
                                $actualCrossMA['sma'] >= $actualSnap['lastClosePrice']
                                && $actualCrossMA['sma'] < $actualCrossMA['ema']
                                && !$actualSupertrend['isUptrend']
                                && !$actualCrossMA['is_reversal']
                            ) || (
                                !$actualSupertrend['isUptrend']
                                && $actualCrossMA['is_reversal']
                                && !$actualCrossMA['isUptrend']
                            )
                        )
                    )
                ) {
                    $actualOpportunities['masterDump'][$symbolName] = $opportunitieData;
                }

                //test
                if (
                    (
                        in_array($timeFrame, ['1h', '1d', '4h', '30m'])
                        && ($actualSnap['openInterest'] > 0)
                        && $actualStochastic['%K'] <= 30
                        && ((
                                $actualCrossMA['sma'] <= $actualSnap['lastClosePrice']
                                && $actualCrossMA['sma'] > $actualCrossMA['ema']
                                && ($actualSupertrend['isUptrend'])
                                && !$actualCrossMA['is_reversal']
                            ) || (
                                $actualSupertrend['isUptrend']
                                && $actualCrossMA['is_reversal']
                                && $actualCrossMA['isUptrend']
                            ))
                    )
                ) {
                    $actualOpportunities['pump'][$symbolName] = $opportunitieData;
                }

                if (
                    (
                        in_array($timeFrame, ['1d', '4h', '1h', '30m'])
                        && ($actualSnap['openInterest'] > 0)
                        && $actualStochastic['%K'] >= 70
                        && ((
                                $actualCrossMA['sma'] >= $actualSnap['lastClosePrice']
                                && $actualCrossMA['sma'] < $actualCrossMA['ema']
                                && !$actualSupertrend['isUptrend']
                                && !$actualCrossMA['is_reversal']
                            ) || (
                                !$actualSupertrend['isUptrend']
                                && $actualCrossMA['is_reversal']
                                && !$actualCrossMA['isUptrend']
                            ))
                    )
                ) {
                    $actualOpportunities['dump'][$symbolName] = $opportunitieData;
                }

                //alerts
                if (
                    (
                        in_array($timeFrame, ['30m', '1h', '4h', '1d'])
                        && $actualStochastic['isLong']
                        && ($actualSupertrend['isUptrend'])
                        && $actualCrossMA['bollinger']['%B'] < 0.27
                    )
                ) {
                    $actualOpportunities['allPump'][$symbolName] = $opportunitieData;
                }

                if (
                    (
                        in_array($timeFrame, ['30m', '1h', '4h', '1d'])
                        && $actualStochastic['isShort']
                        && (!$actualSupertrend['isUptrend'])
                        && $actualCrossMA['bollinger']['%B'] > 0.73
                    )
                ) {
                    $actualOpportunities['allDump'][$symbolName] = $opportunitieData;
                }

            }
        }

        // Функция для сортировки массива pump с сохранением ключей
        uasort($actualOpportunities['allPump'], function ($a, $b) {
            return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
        });

        // Функция для сортировки массива dump с сохранением ключей
        uasort($actualOpportunities['allDump'], function ($a, $b) {
            return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
        });


        // Функция для сортировки массива pump с сохранением ключей
        uasort($actualOpportunities['pump'], function ($a, $b) {
            // Первым делом сортируем по lastOpenInterest (большее значение имеет приоритет)
            if ($a['lastOpenInterest'] != $b['lastOpenInterest']) {
                return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
            }

            if ($a['lastPriceChange'] != $b['lastPriceChange']) {
                return $a['lastPriceChange'] <=> $b['lastPriceChange'];
            }

            return $b['buyChangePercent'] <=> $a['buyChangePercent'];
        });

        // Функция для сортировки массива dump с сохранением ключей
        uasort($actualOpportunities['dump'], function ($a, $b) {
            // Первым делом сортируем по lastOpenInterest (большее значение имеет приоритет)
            if ($a['lastOpenInterest'] != $b['lastOpenInterest']) {
                return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
            }

            // Если значения lastPriceChange одинаковы, сортируем по crossMAVal (2 имеет приоритет)
            if ($a['crossMAVal'] != $b['crossMAVal']) {
                return ($b['crossMAVal'] == 2) ? 1 : -1;
            }
            
            return $b['sellChangePercent'] <=> $a['sellChangePercent'];
        });

        uasort($actualOpportunities['masterPump'], function ($a, $b) {
            return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
        });

        uasort($actualOpportunities['masterDump'], function ($a, $b) {
            return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
        });

     
        devlogs($timeFrame. ' count pump - ' . count($actualOpportunities['pump']), $marketCode . 'findPumpOrDumpOpportunities');
        devlogs($timeFrame. ' count dump - ' . count($actualOpportunities['dump']), $marketCode . 'findPumpOrDumpOpportunities');

        if (!empty($err))
            devlogs($timeFrame. ' err - ' . implode('; ', $err), $marketCode . 'findPumpOrDumpOpportunities');

        devlogs($timeFrame. ' end - ' . $timeMark, $marketCode . 'findPumpOrDumpOpportunities');

        return $actualOpportunities;
    }

    public static function checkRecentTrendReversal($trendData, $trendReversalRange = 3) {
        // Получаем последние $trendReversalRange элементов массива
        $recentData = array_slice($trendData, -$trendReversalRange);

        // Проходим по последним данным и проверяем наличие разворота
        foreach ($recentData as $candle) {
            if ($candle['is_reversal'] === true) {
                return true; // Если есть разворот, возвращаем true
            }
        }

        // Если разворота не найдено, возвращаем false
        return false;
    }

     /**
     * Функция для расчета RSI (Relative Strength Index)
     *
     * @param array $prices Массив реальных значений (например, цены закрытия).
     * @param int $period Номер периода для расчета (например, 14).
     * @return float Значение RSI для указанного периода.
     */
    public static function calculateRSI(array $prices, int $period = 14) {
        // Проверяем, что массив достаточно длинный для расчета RSI
        if (count($prices) < $period + 1) {
            throw new \Exception("Недостаточно данных для расчета RSI.");
        }

        // Берем последние $period+1 элементов, чтобы рассчитать первые изменения
        $recentPrices = array_slice($prices, -($period + 1));

        $gains = [];
        $losses = [];

        // Вычисляем приросты и убытки
        for ($i = 1; $i < count($recentPrices); $i++) {
            $change = $recentPrices[$i] - $recentPrices[$i - 1];
            if ($change > 0) {
                $gains[] = $change;
                $losses[] = 0;
            } else {
                $gains[] = 0;
                $losses[] = abs($change);
            }
        }

        // Рассчитываем средние приросты и убытки
        $avgGain = array_sum($gains) / $period;
        $avgLoss = array_sum($losses) / $period;

        // Вычисляем RS и RSI
        $rs = ($avgLoss == 0) ? 0 : $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));

        return round($rsi, 2);
    }

    // Функция для поиска дивергенции или конвергенции
    public static function detectDivergence(array $prices, array $rsiValues) {
        $divergence = 'no divergence';

        // Поиск локальных максимумов и минимумов
        $pricePeaks = self::findPeaks($prices);
        $rsiPeaks = self::findPeaks($rsiValues);

        // Сравнение последних двух максимумов и минимумов для определения дивергенции
        if (count($pricePeaks) >= 2 && count($rsiPeaks) >= 2) {
            $lastPricePeak = end($pricePeaks);
            $secondLastPricePeak = prev($pricePeaks);

            $lastRSIPeak = end($rsiPeaks);
            $secondLastRSIPeak = prev($rsiPeaks);

            // Проверка на медвежью дивергенцию
            if ($lastPricePeak['value'] > $secondLastPricePeak['value'] && $lastRSIPeak['value'] < $secondLastRSIPeak['value']) {
                $divergence = 'bear divergence';
            }

            // Проверка на бычью дивергенцию
            if ($lastPricePeak['value'] < $secondLastPricePeak['value'] && $lastRSIPeak['value'] > $secondLastRSIPeak['value']) {
                $divergence = 'bull divergence';
            }
        }

        return $divergence;
    }

    public static function findPeaks(array $values) {
        $peaks = [];

        // Начинаем со второго элемента и заканчиваем предпоследним, чтобы избежать выхода за границы массива
        for ($i = 1; $i < count($values) - 1; $i++) {
            if ($values[$i] > $values[$i - 1] && $values[$i] > $values[$i + 1]) {
                // Локальный максимум
                $peaks[] = ['index' => $i, 'value' => $values[$i]];
            } elseif ($values[$i] < $values[$i - 1] && $values[$i] < $values[$i + 1]) {
                // Локальный минимум
                $peaks[] = ['index' => $i, 'value' => $values[$i]];
            }
        }

        return $peaks;
    }

    public static function getMACrossHistory(array $prices, int $shortPeriod = 12, int $longPeriod = 26, int $n = 10) {
        // Проверяем, что передано достаточно данных для расчета и для формирования истории на n свечей
        if (count($prices) < $longPeriod + $n - 1) {
            throw new \Exception("Недостаточно данных для расчета скользящих средних на $n свечей.");
        }

        $crossHistory = [];

        // Проходим по последним n свечам, начиная от конца массива
        for ($i = $n; $i > 0; $i--) {
            // Берем подмассив, заканчивающийся на текущей свече, и передаем в checkMACross
            $currentSlice = array_slice($prices, 0, count($prices) - $i + 1);

            // Вызываем метод checkMACross и получаем результат для текущей свечи
            $crossData = self::checkMACross($currentSlice, $shortPeriod, $longPeriod);

            // Добавляем результат к истории
            $crossHistory[] = $crossData;
        }

        return $crossHistory;
    }

    public static function checkMACross(array $prices, int $shortPeriod = 12, int $longPeriod = 26, int $bollingerLength = 20, float $bollingerFactor = 2)
    {
        // Проверяем, что передано достаточно данных
        if (count($prices) < max($longPeriod, $bollingerLength)) {
            throw new \Exception("Недостаточно данных для расчета.");
        }

        // Берем последние элементы для расчетов
        $prices = array_slice($prices, -max($longPeriod, $bollingerLength));

        // Вычисляем SMA для длинного периода
        $sma = array_sum(array_slice($prices, -$longPeriod)) / $longPeriod;

        // Вычисляем EMA для короткого периода
        $ema = self::calculateEMA($prices, $shortPeriod);

        // Проверяем пересечение
        $previousSMA = array_sum(array_slice($prices, -(($longPeriod + 1)), $longPeriod)) / $longPeriod;
        $previousEMA = self::calculateEMA(array_slice($prices, 0, -1), $shortPeriod);

        // Определяем текущую цену
        $currentPrice = end($prices); // Последний элемент массива
        $isUptrend = $currentPrice > $sma;

        // Вычисляем Bollinger Bands
        $bollingerPrices = array_slice($prices, -$bollingerLength); // Последние $bollingerLength значений
        $bollingerSMA = array_sum($bollingerPrices) / $bollingerLength;

        // Вычисляем стандартное отклонение
        $variance = array_sum(array_map(function ($price) use ($bollingerSMA) {
                return pow($price - $bollingerSMA, 2);
            }, $bollingerPrices)) / $bollingerLength;

        $stdDev = sqrt($variance);

        // Верхняя и нижняя полосы
        $upperBand = $bollingerSMA + ($bollingerFactor * $stdDev);
        $lowerBand = $bollingerSMA - ($bollingerFactor * $stdDev);

        // Логика пересечения SMA и EMA
        if ($previousEMA <= $previousSMA && $ema > $sma) {
            return [
                'cross' => 'bull cross',
                'sma' => $sma,
                'ema' => $ema,
                'isUptrend' => $isUptrend,
                'is_reversal' => true,
                'bollinger' => [
                    'middle_band' => $bollingerSMA,
                    'upper_band' => $upperBand,
                    'lower_band' => $lowerBand,
                ],
            ];
        } elseif ($previousEMA >= $previousSMA && $ema < $sma) {
            return [
                'cross' => 'bear cross',
                'sma' => $sma,
                'ema' => $ema,
                'isUptrend' => $isUptrend,
                'is_reversal' => true,
                'bollinger' => [
                    'middle_band' => $bollingerSMA,
                    'upper_band' => $upperBand,
                    'lower_band' => $lowerBand,
                ],
            ];
        } else {
            return [
                'cross' => 'no cross',
                'sma' => $sma,
                'ema' => $ema,
                'isUptrend' => $isUptrend,
                'is_reversal' => false,
                'bollinger' => [
                    'middle_band' => $bollingerSMA,
                    'upper_band' => $upperBand,
                    'lower_band' => $lowerBand,
                ],
            ];
        }
    }

    protected static function calculateEMA(array $prices, int $period) {
        $k = 2 / ($period + 1);
        $ema = $prices[0];
        for ($i = 1; $i < count($prices); $i++) {
            $ema = $prices[$i] * $k + $ema * (1 - $k);
        }
        return $ema;
    }

    public static function calculateSARWithTrend($candles, $initialSAR = 0.02, $step = 0.02, $maxAF = 0.2)
    {
        $sar = [];
        $isUptrend = true; // Начальный тренд восходящий
        $af = $initialSAR; // Коэффициент ускорения
        $ep = $candles[0]['h']; // Extreme Price (максимум для восходящего тренда)
        $trendChanges = []; // Массив для записи разворотов тренда

        foreach ($candles as $i => $candle) {
            if ($i === 0) {
                // Инициализация первого значения SAR
                $sar[] = [
                    'sar_value' => $candle['l'], // Начальный SAR - low для восходящего тренда
                    'trend' => 'up', // Начальный тренд - восходящий
                    'is_reversal' => false // Первый бар не может быть разворотом
                ];
                continue;
            }

            $prevSAR = $sar[$i - 1]['sar_value'];
            $sarValue = $prevSAR + $af * ($ep - $prevSAR); // Расчет SAR

            // Проверка на разворот тренда
            if ($isUptrend) {
                if ($candle['l'] < $sarValue) { // Если текущий low меньше SAR, то разворот в нисходящий тренд
                    $isUptrend = false;
                    $sarValue = $ep; // Сбрасываем SAR на предыдущий EP
                    $ep = $candle['l']; // Новый EP - low
                    $af = $initialSAR; // Сброс AF
                    $trendChanges[] = ['index' => $i, 'new_trend' => 'down'];
                } else {
                    if ($candle['h'] > $ep) { // Обновляем максимум (EP)
                        $ep = $candle['h'];
                        $af = min($af + $step, $maxAF); // Увеличиваем AF
                    }
                }
            } else {
                if ($candle['h'] > $sarValue) { // Если текущий high больше SAR, то разворот в восходящий тренд
                    $isUptrend = true;
                    $sarValue = $ep; // Сбрасываем SAR на предыдущий EP
                    $ep = $candle['h']; // Новый EP - high
                    $af = $initialSAR; // Сброс AF
                    $trendChanges[] = ['index' => $i, 'new_trend' => 'up'];
                } else {
                    if ($candle['l'] < $ep) { // Обновляем минимум (EP)
                        $ep = $candle['l'];
                        $af = min($af + $step, $maxAF); // Увеличиваем AF
                    }
                }
            }

            // Записываем текущее значение SAR и тренд
            $sar[] = [
                'sar_value' => $sarValue,
                'trend' => $isUptrend ? 'up' : 'down',
                'isUptrend' => $isUptrend,
                'is_reversal' => end($trendChanges)['index'] === $i
            ];
        }

        if (!$sar) {
            $sar[] = [
                'sar_value' => 0,
                'trend' => '-',
                'isUptrend' => '-',
                'is_reversal' => '-',
            ];
        }

        return $sar;
    }

    public static function calculateSupertrend($candles, $length = 10, $factor = 3, $useEMA = false)
    {
        // Проверка на пустой массив свечей
        if (empty($candles)) {
            return [
                [
                    'value' => 0,
                    'trend' => '-',
                    'isUptrend' => false,
                    'is_reversal' => false,
                ],
            ];
        }

        $supertrend = [];
        $tr = [];
        $atr = [];
        $atrMultiplier = $factor;

        // Step 1: Calculate True Range (TR)
        for ($i = 0; $i < count($candles); $i++) {
            if ($i == 0) {
                $tr[] = 0;
                continue;
            }

            $highLow = $candles[$i]['h'] - $candles[$i]['l'];
            $highClose = abs($candles[$i]['h'] - $candles[$i - 1]['c']);
            $lowClose = abs($candles[$i]['l'] - $candles[$i - 1]['c']);
            $tr[] = max($highLow, $highClose, $lowClose);
        }

        // Step 2: Calculate ATR (SMA or EMA)
        if ($useEMA) {
            // Exponential Moving Average (EMA)
            $multiplier = 2 / ($length + 1);
            for ($i = 0; $i < count($tr); $i++) {
                if ($i < $length) {
                    $atr[] = 0; // ATR undefined for first $length candles
                } elseif ($i == $length) {
                    // Initial EMA is SMA of first $length TR values
                    $atr[] = array_sum(array_slice($tr, 0, $length)) / $length;
                } else {
                    // EMA calculation
                    $atr[] = ($tr[$i] - $atr[$i - 1]) * $multiplier + $atr[$i - 1];
                }
            }
        } else {
            // Simple Moving Average (SMA)
            for ($i = 0; $i < count($tr); $i++) {
                if ($i < $length) {
                    $atr[] = 0; // ATR undefined for first $length candles
                } else {
                    $atr[] = array_sum(array_slice($tr, $i - $length + 1, $length)) / $length;
                }
            }
        }

        // Step 3: Calculate Supertrend
        $prevUpperBand = null;
        $prevLowerBand = null;
        $prevTrend = 'up';

        for ($i = $length; $i < count($candles); $i++) {
            $basicUpperBand = (($candles[$i]['h'] + $candles[$i]['l']) / 2) + ($atrMultiplier * $atr[$i]);
            $basicLowerBand = (($candles[$i]['h'] + $candles[$i]['l']) / 2) - ($atrMultiplier * $atr[$i]);

            // Adjust bands according to the trend
            if ($prevTrend === 'up' && $basicLowerBand < $prevLowerBand) {
                $basicLowerBand = $prevLowerBand;
            }
            if ($prevTrend === 'down' && $basicUpperBand > $prevUpperBand) {
                $basicUpperBand = $prevUpperBand;
            }

            // Determine trend
            if ($candles[$i]['c'] > $basicUpperBand) {
                $supertrendValue = $basicLowerBand;
                $trend = 'up';
            } elseif ($candles[$i]['c'] < $basicLowerBand) {
                $supertrendValue = $basicUpperBand;
                $trend = 'down';
            } else {
                // Maintain previous trend
                $supertrendValue = $prevTrend === 'up' ? $basicLowerBand : $basicUpperBand;
                $trend = $prevTrend;
            }

            $isReversal = $prevTrend !== $trend;

            // Record calculated values
            $supertrend[] = [
                'value' => $supertrendValue,
                'trend' => $trend,
                'isUptrend' => $trend === 'up',
                'is_reversal' => $isReversal,
            ];

            // Update for next iteration
            $prevUpperBand = $basicUpperBand;
            $prevLowerBand = $basicLowerBand;
            $prevTrend = $trend;
        }

        // If there are no valid values, provide default output
        if (empty($supertrend)) {
            $supertrend[] = [
                'value' => 0,
                'trend' => '-',
                'isUptrend' => false,
                'is_reversal' => false,
            ];
        }

        return $supertrend;
    }

    public static function findLevels($orderBook, $topN = 3, $deviationPercent = 0.3) {
        $bids = [];
        $asks = [];

        // Самая высокая цена покупок и самая низкая цена продаж
        $maxBidPrice = (float) $orderBook['b'][0][0];
        $minAskPrice = (float) $orderBook['a'][0][0];

        // Рассчитываем предельные уровни
        $minAskPriceLimit = $minAskPrice * (1 + ($deviationPercent / 100));
        $maxBidPriceLimit = $maxBidPrice * (1 - ($deviationPercent / 100));

        // Проходим по покупкам (bids)
        foreach ($orderBook['b'] as $bid) {
            $price = (float) $bid[0];
            $volume = (float) $bid[1];
            if ($price <= $maxBidPriceLimit) {
                $bids[] = [
                    'price' => $price,
                    'volume' => round($volume),
                    'percent_from_last_close' => round((($maxBidPrice - $price) / $maxBidPrice) * 100, 2) // Отклонение от maxBidPrice
                ];
            }
        }

        // Проходим по продажам (asks)
        foreach ($orderBook['a'] as $ask) {
            $price = (float) $ask[0];
            $volume = (float) $ask[1];
            if ($price >= $minAskPriceLimit) {
                $asks[] = [
                    'price' => $price,
                    'volume' => round($volume),
                    'percent_from_last_close' => round((($price - $minAskPrice) / $minAskPrice) * 100, 2) // Отклонение от minAskPrice
                ];
            }
        }

        // Объединение похожих уровней
        $bids = self::mergeSimilarLevels($bids, 'bids');
        $asks = self::mergeSimilarLevels($asks, 'asks');

        // Сортировка по объему
        usort($bids, function($a, $b) {
            return $b['volume'] <=> $a['volume'];
        });
        usort($asks, function($a, $b) {
            return $b['volume'] <=> $a['volume'];
        });

        // Возвращаем топ-N уровней
        return [
            'upper' => array_slice($asks, 0, $topN),
            'lower' => array_slice($bids, 0, $topN)
        ];
    }

    // Функция для объединения похожих уровней
    public static function mergeSimilarLevels($levels, $type) {
        $merged = [];

        foreach ($levels as $level) {
            $found = false;
            foreach ($merged as &$mergedLevel) {
                // Получаем динамическую точность для цены уровня
                $precision = self::getDynamicPrecision($mergedLevel['price']);
                if (abs($mergedLevel['price'] - $level['price']) <= $precision) {
                    $mergedLevel['volume'] += $level['volume'];
                    $mergedLevel['volume'] = round($mergedLevel['volume']);  // Округляем сумму объемов
                    if ($type === 'asks') {
                        $mergedLevel['price'] = min($mergedLevel['price'], $level['price']); // Берем минимальную цену для верхних уровней
                    } else {
                        $mergedLevel['price'] = max($mergedLevel['price'], $level['price']); // Берем максимальную цену для нижних уровней
                    }
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $merged[] = $level;
            }
        }

        return $merged;
    }

    // Функция для вычисления динамической точности на основе цены
    public static function getDynamicPrecision($price) {
        if ($price > 10000) {
            return 0.5;  // Высокие цены (например, BTC) — точность 0.1
        } elseif ($price > 100) {
            return 0.1; //
        } /*elseif ($price > 1000) {
            return 0.35; // Средние цены — точность 0.1
        } */elseif ($price > 1) {
            return 0.007;
        } elseif ($price > 0.5) {
            return 0.008;
        }  elseif ($price > 0.01) {
            return 0.0002;
        }  elseif ($price > 0.001) {
            return 0.00002;
        } else {
            return 0.00001;
        }
    }

}
