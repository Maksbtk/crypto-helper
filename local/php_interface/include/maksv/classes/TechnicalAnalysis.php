<?php
namespace Maksv;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class TechnicalAnalysis
{
    public function __construct(){}

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

    public static function getMACrossHistory(array $prices, int $shortPeriod = 9, int $longPeriod = 26, int $n = 10) {
        // Проверяем, что передано достаточно данных для расчета и для формирования истории на n свечей
        if (count($prices) < $longPeriod + $n - 1) {
            return [];
            //throw new \Exception("Недостаточно данных для расчета скользящих средних на $n свечей.");
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

    public static function checkMACross(array $prices, int $shortPeriod = 9, int $longPeriod = 26, int $bollingerLength = 20, float $bollingerFactor = 2)
    {
        if (count($prices) < max($longPeriod, $bollingerLength)) {
            return false;
        }

        $milliseconds = $prices[array_key_last($prices)]['t'];

        // Извлекаем цены закрытия и оставляем последние max(longPeriod, bollingerLength) значений
        $closePrices = array_column($prices, 'c');
        $closePrices = array_slice($closePrices, -max($longPeriod, $bollingerLength));

        if (empty($closePrices) || count($closePrices) < $shortPeriod) {
            return false;
        }

        // SMA для последних longPeriod значений
        $sma = array_sum(array_slice($closePrices, -$longPeriod)) / $longPeriod;

        // Inline расчет EMA для последних shortPeriod значений
        $k = 2 / ($shortPeriod + 1);
        $ema = $closePrices[0];
        for ($i = 1; $i < count($closePrices); $i++) {
            $ema = $closePrices[$i] * $k + $ema * (1 - $k);
        }

        // Предыдущие SMA и EMA (без последнего значения)
        $previousSMA = array_sum(array_slice($closePrices, -(($longPeriod + 1)), $longPeriod)) / $longPeriod;
        $prevPrices = array_slice($closePrices, 0, -1);
        $prevK = 2 / ($shortPeriod + 1);
        $previousEMA = $prevPrices[0];
        for ($i = 1; $i < count($prevPrices); $i++) {
            $previousEMA = $prevPrices[$i] * $prevK + $previousEMA * (1 - $prevK);
        }

        $currentPrice = end($closePrices);
        $prevPrice = $closePrices[array_key_last($closePrices) - 1];
        $isUptrend = $prevPrice > $sma;
        //$isUptrend = $ema > $sma;

        $isShort = ($previousEMA >= $previousSMA && $ema < $sma);
        $isLong  = ($previousEMA <= $previousSMA && $ema > $sma);

        // Формирование timestamp
        $seconds = $milliseconds / 1000;
        $microseconds = ($milliseconds % 1000) * 1000;
        $date = \DateTime::createFromFormat('U.u', sprintf('%.6F', $seconds));
        $date->modify("+$microseconds microseconds");
        $timestamp = $date->format("H:i m.d");

        $inputParams = [
            'shortPeriod' => $shortPeriod,
            'longPeriod' => $longPeriod,
            //int $shortPeriod = 9, int $longPeriod = 26,
        ];
        if ($previousEMA <= $previousSMA && $ema > $sma) {
            return [
                'cross' => 'bull cross',
                'sma' => $sma,
                'ema' => $ema,
                'isUptrend' => $isUptrend,
                'is_reversal' => true,
                'isLong' => true,
                'isShort' => false,
                'timestamp_gmt' => $timestamp,
                'timestamp' => $milliseconds,
                'inputParams' => $inputParams,
            ];
        } elseif ($previousEMA >= $previousSMA && $ema < $sma) {
            return [
                'cross' => 'bear cross',
                'sma' => $sma,
                'ema' => $ema,
                'isUptrend' => $isUptrend,
                'is_reversal' => true,
                'isLong' => false,
                'isShort' => true,
                'timestamp_gmt' => $timestamp,
                'timestamp' => $milliseconds,
                'inputParams' => $inputParams,
            ];
        } else {
            return [
                'cross' => 'no cross',
                'sma' => $sma,
                'ema' => $ema,
                'isUptrend' => $isUptrend,
                'is_reversal' => false,
                'isLong' => $isLong,
                'isShort' => $isShort,
                'timestamp_gmt' => $timestamp,
                'timestamp' => $milliseconds,
                'inputParams' => $inputParams,
            ];
        }
    }

    /**
     * Рассчет полос Боллинджера и ширины в процентах
     *
     * @param array $candles Массив свечей, каждая свеча содержит ключи 't','o','h','l','c','v'
     * @param int   $length  Период скользящей средней (по умолчанию 20)
     * @param int   $factor  Коэффициент стандартного отклонения (по умолчанию 2)
     *
     * @return array Возвращает ассоциативный массив с ключами:
     *               - 'middle_band' (float) — скользящая средняя
     *               - 'upper_band'  (float) — верхняя полоса
     *               - 'lower_band'  (float) — нижняя полоса
     *               - 'bandwidth_pct' (float) — ширина полосы в процентах от средней
     *               - 'error'       (string) — сообщение об ошибке (при недостатке данных)
     */
    public static function calculateBollingerBands(array $candles, int $length = 20, int $factor = 2): array {
        $total = count($candles);
        $results = [];

        if ($total < $length) {
            return ['error' => "Недостаточно данных: требуется {$length} свечей, передано {$total}"];
        }

        // Проходим по каждой свече, начиная с индекса length-1
        for ($i = $length - 1; $i < $total; $i++) {
            // Берём окно последних $length свечей до текущей (включительно)
            $window = array_slice($candles, $i - $length + 1, $length);
            $closes = array_column($window, 'c');

            // Скользящая средняя
            $sma = array_sum($closes) / $length;

            // Стандартное отклонение
            $variance = 0.0;
            foreach ($closes as $price) {
                $variance += ($price - $sma) ** 2;
            }
            $variance /= $length;
            $stdDev = sqrt($variance);

            // Полосы
            $upper = $sma + $factor * $stdDev;
            $lower = $sma - $factor * $stdDev;

            // Текущая цена закрытия
            $currentPrice = $candles[$i]['c'];
            $oscillatorPrice = $currentPrice - $sma;

            // Ширина полос в % от SMA
            $bandwidthPct = ($upper - $lower) / $sma * 100;

            // %B осциллятор
            $percentB = ($upper !== $lower)
                ? ($currentPrice - $lower) / ($upper - $lower) * 100
                : 0.0;



            //timestamp;
            $milliseconds = $candles[$i]['t'];
            $seconds = $milliseconds / 1000;
            $timestamp = date("H:i d.m", $seconds);
            // Сохраняем результат
            $results[] = [
                't'             => $timestamp,
                'price'         => [
                    'middle_band' => $sma,
                    'upper_band'  => $upper,
                    'lower_band'  => $lower,
                    'oscillator'  => $oscillatorPrice,
                ],
                'percent'       => [
                    'oscillator'  => $percentB,
                ],
                'bandwidth_pct' => $bandwidthPct,
            ];
        }

        return $results;
    }


    public static function checkMACrossDev(array $prices, int $shortPeriod = 9, int $longPeriod = 26, int $bollingerLength = 20, float $bollingerFactor = 2)
    {
        if (count($prices) < max($longPeriod, $bollingerLength)) {
            return false;
        }

        $milliseconds = $prices[array_key_last($prices)]['t'];

        $prices = array_column($prices, 'c');
        $prices = array_slice($prices, -max($longPeriod, $bollingerLength));

        if (empty($prices) || !is_array($prices) || count($prices) < $shortPeriod) {
            return false;
        }

        $sma = array_sum(array_slice($prices, -$longPeriod)) / $longPeriod;
        $ema = self::calculateEMA($prices, $shortPeriod);
        $previousSMA = array_sum(array_slice($prices, -(($longPeriod + 1)), $longPeriod)) / $longPeriod;
        $previousEMA = self::calculateEMA(array_slice($prices, 0, -1), $shortPeriod);
        $currentPrice = end($prices);
        $prevPrice = $prices[array_key_last($prices) - 1];
        $isUptrend = $prevPrice > $sma;

        $isShort = ($previousEMA >= $previousSMA && $ema < $sma) /*|| ($sma >= $prevPrice && $sma < $ema)*/;
        $isLong = ($previousEMA <= $previousSMA && $ema > $sma) /*|| ($sma <= $prevPrice && $sma > $ema)*/;
        //$isUptrend = $ema >= $sma;

        // Расчет полос Боллинджера
        $bollingerPrices = array_slice($prices, -$bollingerLength);
        $bollingerSMA = array_sum($bollingerPrices) / $bollingerLength;
        $variance = array_sum(array_map(function ($price) use ($bollingerSMA) {
                return pow($price - $bollingerSMA, 2);
            }, $bollingerPrices)) / $bollingerLength;
        $stdDev = sqrt($variance);
        $upperBand = $bollingerSMA + ($bollingerFactor * $stdDev);
        $lowerBand = $bollingerSMA - ($bollingerFactor * $stdDev);

        // Расчет %B
        $bollingerPercentB = ($currentPrice - $lowerBand) / ($upperBand - $lowerBand);

        //timestamp
        $seconds = $milliseconds / 1000;
        $microseconds = ($milliseconds % 1000) * 1000;

        $date = \DateTime::createFromFormat('U.u', sprintf('%.6F', $seconds));
        $date->modify("+$microseconds microseconds");
        $timestamp =  $date->format("H:i m.d");

        if ($previousEMA <= $previousSMA && $ema > $sma) {
            return [
                'cross' => 'bull cross',
                'sma' => $sma,
                'ema' => $ema,
                'isUptrend' => $isUptrend,
                'is_reversal' => true,
                'isLong' => true,
                'isShort' => false,
                'timestamp_gmt' => $timestamp,
                'timestamp' => $milliseconds,
                'bollinger' => [
                    'middle_band' => $bollingerSMA,
                    'upper_band' => $upperBand,
                    'lower_band' => $lowerBand,
                    '%B' => $bollingerPercentB,
                ],
            ];
        } elseif ($previousEMA >= $previousSMA && $ema < $sma) {
            return [
                'cross' => 'bear cross',
                'sma' => $sma,
                'ema' => $ema,
                'isUptrend' => $isUptrend,
                'is_reversal' => true,
                'isLong' => false,
                'isShort' => true,
                'timestamp_gmt' => $timestamp,
                'timestamp' => $milliseconds,
                'bollinger' => [
                    'middle_band' => $bollingerSMA,
                    'upper_band' => $upperBand,
                    'lower_band' => $lowerBand,
                    '%B' => $bollingerPercentB,
                ],
            ];
        } else {
            return [
                'cross' => 'no cross',
                'sma' => $sma,
                'ema' => $ema,
                'isUptrend' => $isUptrend,
                'is_reversal' => false,
                'isLong' => $isLong,
                'isShort' => $isShort,
                'timestamp_gmt' => $timestamp,
                'timestamp' => $milliseconds,
                'bollinger' => [
                    'middle_band' => $bollingerSMA,
                    'upper_band' => $upperBand,
                    'lower_band' => $lowerBand,
                    '%B' => $bollingerPercentB,
                ],
            ];
        }
    }

    protected static function calculateEMA(array $prices, int $period) {
        if (empty($prices)) {
            throw new \InvalidArgumentException("calculateEMA: prices array is empty");
        }
        $k = 2 / ($period + 1);
        $ema = $prices[0];
        for ($i = 1; $i < count($prices); $i++) {
            if (!is_numeric($prices[$i])) {
                throw new \UnexpectedValueException("calculateEMA: Non-numeric value in prices array");
            }
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

    public static function findLevels($orderBook, $topN = 3, $deviationPercent = 0.3)
    {
        $bids = [];
        $asks = [];

        // Самая высокая цена покупок и самая низкая цена продаж
        $maxBidPrice = (float) $orderBook['b'][0][0];
        $minAskPrice = (float) $orderBook['a'][0][0];

        // Рассчитываем предельные уровни
        $minAskPriceLimit = $minAskPrice * (1 + ($deviationPercent / 100));
        $maxBidPriceLimit = $maxBidPrice * (1 - ($deviationPercent / 100));

        $totalBidVolume = 0;
        $totalAskVolume = 0;

        // Проходим по покупкам (bids)
        foreach ($orderBook['b'] as $bid) {
            $price = (float) $bid[0];
            $volume = (float) $bid[1];
            if ($price <= $maxBidPriceLimit) {
                $bids[] = [
                    'price' => $price,
                    'volume' => $volume,
                    'distance' => round((($maxBidPrice - $price) / $maxBidPrice) * 100, 2) ?? 0 // Отклонение от maxBidPrice
                ];
                $totalBidVolume += $volume;
            }
        }

        // Проходим по продажам (asks)
        foreach ($orderBook['a'] as $ask) {
            $price = (float) $ask[0];
            $volume = (float) $ask[1];
            if ($price >= $minAskPriceLimit) {
                $asks[] = [
                    'price' => $price,
                    'volume' => $volume,
                    'distance' => round((($price - $minAskPrice) / $minAskPrice) * 100, 2) ?? 0 // Отклонение от minAskPrice
                ];
                $totalAskVolume += $volume;
            }
        }

        // Вычисляем volume в процентах от общего объема
        foreach ($bids as &$bid) {
            $bid['volume_percent'] = $totalBidVolume > 0 ? round(($bid['volume'] / $totalBidVolume) * 100, 2) : 0;
        }
        foreach ($asks as &$ask) {
            $ask['volume_percent'] = $totalAskVolume > 0 ? round(($ask['volume'] / $totalAskVolume) * 100, 2) : 0;
        }

        // Объединение похожих уровней
        $bids = self::mergeSimilarLevels($bids, 'bids');
        $asks = self::mergeSimilarLevels($asks, 'asks');

        // Сортировка по возрастанию
        usort($bids, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });
        usort($asks, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        // Возвращаем топ-N уровней
        return [
            'upper' => array_slice($asks, 0, $topN),
            'lower' => array_slice($bids, 0, $topN)
        ];
    }

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

    public static function getDynamicPrecision($price) {
         if ($price > 10000) {
             return 0.5;  // Высокие цены (например, BTC) — точность 0.1
         } elseif ($price > 100) {
             return 0.1; //
         } elseif ($price > 1) {
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

    public static function calculateStochasticOscillator(array $candles, int $k = 14, int $d = 1, int $smooth = 3, $impulsePercent = 0.5): array
    {
        // Проверяем, что данных достаточно для расчета
        if (count($candles) < $k + $smooth + $d) {
            throw new \Exception("Недостаточно данных для расчета стохастического осциллятора.");
        }

        $stochasticValues = [];

        // Рассчитываем %K (несглаженный)
        for ($i = $k - 1; $i < count($candles); $i++) {
            $periodCandles = array_slice($candles, $i - $k + 1, $k);
            $high = max(array_column($periodCandles, 'h')); // Максимум за период
            $low = min(array_column($periodCandles, 'l'));  // Минимум за период
            $close = $candles[$i]['c'];                    // Цена закрытия текущей свечи

            $kValue = $high === $low ? 100 : (($close - $low) / ($high - $low)) * 100;
            $stochasticValues[] = $kValue;
        }

        // Сглаживаем %K (smooth)
        $smoothedK = [];
        for ($i = $smooth - 1; $i < count($stochasticValues); $i++) {
            $smoothedK[] = array_sum(array_slice($stochasticValues, $i - $smooth + 1, $smooth)) / $smooth;
        }

        // Рассчитываем %D (скользящее среднее для %K)
        $smoothedD = [];
        for ($i = $d - 1; $i < count($smoothedK); $i++) {
            $smoothedD[] = array_sum(array_slice($smoothedK, $i - $d + 1, $d)) / $d;
        }

        // Формируем результат
        $result = [];
        for ($i = max($k - 1 + $smooth - 1, $k - 1 + $smooth - 1 + $d - 1); $i < count($candles); $i++) {
            $indexK = $i - ($k - 1 + $smooth - 1);
            $indexD = $i - ($k - 1 + $smooth - 1 + $d - 1);

            $currentK = $smoothedK[$indexK];
            $currentD = $smoothedD[$indexD];

            // Определяем, находятся ли значения в зоне перекупленности или перепроданности
            $isOverbought = $currentK > 80;
            $isOversold = $currentK < 20;

            // Проверяем пересечение %K и %D для сигналов
            $previousK = $smoothedK[$indexK - 1] ?? null;
            $previousD = $smoothedD[$indexD - 1] ?? null;

            $isLong = $isShort = false;
            $isConfirmed = false;

            if ($previousK !== null && $previousD !== null) {
                if ($previousK < $previousD && $currentK > $currentD && $isOversold) {
                    $isLong = true; // Сигнал на покупку
                } elseif ($previousK > $previousD && $currentK < $currentD && $isOverbought) {
                    $isShort = true; // Сигнал на продажу
                }

                // Подтверждение импульса через изменение цены
                $priceChange = ($candles[$i]['c'] - $candles[$i - 3]['c']) / $candles[$i - 3]['c'] * 100;
                $isConfirmed = $priceChange > $impulsePercent || $priceChange < -$impulsePercent; // Условие импульса
            }

            //todo: isLong и isShort переделать
            $result[] = [
                'close' => $candles[$i]['c'],  // Цена закрытия текущей свечи
                '%K' => $currentK,             // Текущее значение %K
                '%D' => $currentD,             // Текущее значение %D
                'isOverbought' => $isOverbought, // Перекупленность
                'isOversold' => $isOversold,   // Перепроданность
                'isLong' => $isLong,            // Сигнал ('buy', 'sell', или null)
                'isShort' => $isShort,      // Сигнал ('buy', 'sell', или null)
                'impulseConfirmed' => $isConfirmed, // Подтвержденный сигнал
            ];
        }

        return $result;
    }

    /**
     * Рассчитывает Stochastic RSI и генерирует торговые сигналы по пересечениям и зонам перекупленности/перепроданности.
     *
     * @param array $candles       Массив свечей в формате [ ['t'=>timestamp, 'o'=>open, 'h'=>high, 'l'=>low, 'c'=>close, …], … ].
     * @param int   $rsiPeriod     Период расчёта RSI (по умолчанию 14).
     * @param int   $stochPeriod   Период для расчёта стохастика по RSI (по умолчанию 14).
     * @param int   $smoothK       Период сглаживания %K (по умолчанию 3).
     * @param int   $smoothD       Период сглаживания %D (по умолчанию 3).
     * @param float $stepKD        Минимальный дельта‑порог между %K и %D для учёта гистограммы (по умолчанию 2.5).
     * @param float $stepK         Минимальный шаг изменения %K для сигнала (по умолчанию 3.0).
     *
     * @return array Массив записей вида:
     *               [
     *                 'close'   => float,    // цена закрытия свечи
     *                 '%K'      => float,    // текущее значение %K
     *                 '%prevK'  => float,    // предыдущее значение %K
     *                 '%D'      => float,    // текущее значение %D
     *                 '%prevD'  => float,    // предыдущее значение %D
     *                 'hist'    => float,    // %K − %D
     *                 'isLong'  => bool,     // сигнал на лонг
     *                 'isShort' => bool,     // сигнал на шорт
     *               ]
     */
    public static function calculateStochasticRSI(
        array $candles,
        int $rsiPeriod = 14,
        int $stochPeriod = 14,
        int $smoothK = 3,
        int $smoothD = 3,
        float $stepKD = 2.5, // порог для разницы %K и %D
        float $stepK = 3.0,
    ): array {
        $closingPrices = array_column($candles, 'c');
        // Проверка данных
        $minRequired = $rsiPeriod + $stochPeriod + max($smoothK, $smoothD) - 1;
        if (count($candles) < $minRequired) {
            return [];
        }

        // 1. RSI
        $gains = [];
        $losses = [];
        for ($i = 1; $i < count($closingPrices); $i++) {
            $change = $closingPrices[$i] - $closingPrices[$i - 1];
            $gains[] = max($change, 0);
            $losses[] = max(-$change, 0);
        }
        $avgGain = array_sum(array_slice($gains, 0, $rsiPeriod)) / $rsiPeriod;
        $avgLoss = array_sum(array_slice($losses, 0, $rsiPeriod)) / $rsiPeriod;
        $rsi = [];
        for ($i = $rsiPeriod; $i < count($gains); $i++) {
            $avgGain = (($avgGain * ($rsiPeriod - 1)) + $gains[$i]) / $rsiPeriod;
            $avgLoss = (($avgLoss * ($rsiPeriod - 1)) + $losses[$i]) / $rsiPeriod;
            $rs = $avgLoss == 0 ? 100 : $avgGain / $avgLoss;
            $rsi[] = $avgLoss == 0 ? 100 : 100 - (100 / (1 + $rs));
        }

        // 2. Stoch RSI
        $stochRSI = [];
        for ($i = $stochPeriod - 1; $i < count($rsi); $i++) {
            $slice = array_slice($rsi, $i - $stochPeriod + 1, $stochPeriod);
            $minRSI = min($slice);
            $maxRSI = max($slice);
            $stochRSI[] = ($maxRSI - $minRSI == 0)
                ? 0
                : ($rsi[$i] - $minRSI) / ($maxRSI - $minRSI) * 100;
        }

        // 3. Smooth %K
        $smoothedK = [];
        for ($i = $smoothK - 1; $i < count($stochRSI); $i++) {
            $smoothedK[] = array_sum(array_slice($stochRSI, $i - $smoothK + 1, $smoothK)) / $smoothK;
        }

        // 4. Smooth %D
        $smoothedD = [];
        for ($i = $smoothD - 1; $i < count($smoothedK); $i++) {
            $smoothedD[] = array_sum(array_slice($smoothedK, $i - $smoothD + 1, $smoothD)) / $smoothD;
        }

        $result = [];
        $offset = max($rsiPeriod + $stochPeriod + $smoothK - 1, $rsiPeriod + $stochPeriod + $smoothD - 1);
        for ($i = $offset; $i < count($candles); $i++) {
            $idxK = $i - ($rsiPeriod + $stochPeriod + $smoothK - 1);
            $idxD = $i - ($rsiPeriod + $stochPeriod + $smoothD - 1);
            $curK = $smoothedK[$idxK] ?? null;
            $curD = $smoothedD[$idxD - ($smoothK - 1)] ?? null;
            $prevK = $smoothedK[$idxK - 1] ?? null;
            $prevD = $smoothedD[$idxD - $smoothK] ?? null;

            // Расчёт гистограммы StochRSI как разница %K и %D
            $hist = isset($curK, $curD) ? $curK - $curD : null;
            $histPrev = isset($prevK, $prevD) ? $prevK - $prevD : null;

            // Условия на длинную позицию:
            $isKDLong    = isset($prevK, $prevD, $curK, $curD) &&
                $prevK < $prevD &&    // пересечение %K вверх через %D
                $curK > $curD &&    // пересечение %K вверх через %D
                $hist !== null &&
                $hist >= $stepKD &&   // достаточный гист. импульс
                $curK <= 79;          // не в перекупленности

            $isKLong     = isset($prevK, $prevD, $curK, $curD) &&
                $prevK > $prevD &&     // %K выше прошл.%D
                $curK > $curD &&     // %K выше прошл.%D
                $curK > $prevK &&     // %K выше прошл.%D
                $hist !== null &&
                $hist >= ($stepKD * 1.3) &&
                $curK <= 79;

            $isHalfLong  = isset($prevK, $prevD, $curK, $curD) &&
                $curK > $prevK &&    // был крест, но не чувствителен к %D текущему
                $curK > 50 &&
                $prevK < 50 &&
                $hist !== null &&
                $hist >= $stepKD;

            // Аналогично для короткой позиции:
            $isKDShort   = isset($prevK, $prevD, $curK, $curD) &&
                $curK < $prevK &&
                $prevK > $prevD &&
                $curK < $curD &&
                $hist !== null &&
                $hist <= -$stepKD &&  // отрицательный импульс
                $curK >= 21;

            $isKShort    = isset($prevK, $prevD, $curK, $curD) &&
                $curK < $prevK &&
                $curK < $curD &&
                $prevK < $prevD &&
                $hist !== null &&
                $hist <= (-$stepKD * 1.3) &&
                $curK >= 21;

            $isHalfShort = isset($prevK, $prevD, $curK, $curD) &&
                $curK < $prevK &&
                $curK < 50 &&
                $prevK > 50 &&
                $hist !== null &&
                $hist <= -$stepKD;

            $result[] = [
                'close'    => $candles[$i]['c'],
                '%K'       => $curK,
                '%prevK'       => $prevK,
                '%D'       => $curD,
                '%prevD'       => $prevD,
                'hist'     => $hist,
                'isLong'   => $isKDLong || $isKLong || $isHalfLong,
                'isShort'  => $isKDShort || $isKShort || $isHalfShort,
            ];
        }

        return $result;
    }

    /**
    30m: 200-300 свечей (примерно 2-3 недели данных).
    1h: 200 свечей (около 2 недель).
    4h: 150 свечей (порядка 3 недель).
    1d: 100 свечей (3-4 месяца).

    $zoneWidthPercentage
    1m	0.05% – 0.1%
    5m	0.1% – 0.3%
    15m	0.3% – 0.5%
    1h	0.5% – 1.0%
    4h	1.0% – 2.0%
    1d	2.0% – 5.0%
    1w	5.0% – 10.0%
    */
    public static function findSupportResistanceZones(array $candles, float $zoneWidthPercentage, array $orderBook = []): array
    {
        // Проверяем входные данные
        $totalCandles = count($candles);
        if ($totalCandles < 20) {
            throw new \Exception("Недостаточно данных для анализа зон.");
        }

        if ($orderBook) {
            // Извлечение максимального бида и минимального аска
            $maxBidPrice = (float)$orderBook['b'][0][0];
            $minAskPrice = (float)$orderBook['a'][0][0];
        } else {
            $maxBidPrice = $minAskPrice = $candles[array_key_last($candles)]['c'];
        }

        // Ищем зоны
        $zones = [];
        $localExtremes = [];
        for ($i = 1; $i < $totalCandles - 1; $i++) {
            $prevCandle = $candles[$i - 1];
            $currentCandle = $candles[$i];
            $nextCandle = $candles[$i + 1];

            // Локальный максимум
            if ($currentCandle['h'] > $prevCandle['h'] && $currentCandle['h'] > $nextCandle['h']) {
                $localExtremes[] = ['type' => 'resistance', 'value' => $currentCandle['h'], 'index' => $i];
            }

            // Локальный минимум
            if ($currentCandle['l'] < $prevCandle['l'] && $currentCandle['l'] < $nextCandle['l']) {
                $localExtremes[] = ['type' => 'support', 'value' => $currentCandle['l'], 'index' => $i];
            }
        }

        // Группируем экстремумы в зоны
        foreach ($localExtremes as $extreme) {
            $zoneMatched = false;
            $zoneWidth = $extreme['value'] * ($zoneWidthPercentage / 100);

            foreach ($zones as &$zone) {
                if (
                    ($extreme['value'] >= $zone['lower'] - $zoneWidth) &&
                    ($extreme['value'] <= $zone['upper'] + $zoneWidth)
                ) {
                    $zone['lower'] = min($zone['lower'], $extreme['value']);
                    $zone['upper'] = max($zone['upper'], $extreme['value']);
                    $zone['volume'] += $candles[$extreme['index']]['v'];
                    $zone['hits']++;
                    $zoneMatched = true;
                    break;
                }
            }

            if (!$zoneMatched) {
                $zones[] = [
                    'lower' => $extreme['value'] - $zoneWidth,
                    'upper' => $extreme['value'] + $zoneWidth,
                    'volume' => $candles[$extreme['index']]['v'],
                    'hits' => 1
                ];
            }
        }

        // Разделение зон на поддержку и сопротивление
        $supportZones = [];
        $resistanceZones = [];
        foreach ($zones as $zone) {
            $isSupport = $zone['lower'] < $maxBidPrice;
            $isResistance = $zone['upper'] > $minAskPrice;

            $zone['distance'] = 0;
            if ($isSupport && $isResistance) {
                // Объединение зон
                $merged = false;
                foreach ($supportZones as &$supportZone) {
                    if (
                        abs($supportZone['lower'] - $zone['lower']) < ($zoneWidthPercentage / 100) &&
                        abs($supportZone['upper'] - $zone['upper']) < ($zoneWidthPercentage / 100)
                    ) {
                        $supportZone['hits'] += $zone['hits'];
                        $supportZone['volume'] += $zone['volume'];
                        $merged = true;
                        break;
                    }
                }
                if (!$merged) {
                    $supportZones[] = $zone;
                }
            } elseif ($isSupport) {
                $zone['distance'] = ($maxBidPrice - $zone['upper']) / $maxBidPrice * 100;
                $supportZones[] = $zone;
            } elseif ($isResistance) {
                $zone['distance'] = ($zone['lower'] - $minAskPrice) / $minAskPrice * 100;
                $resistanceZones[] = $zone;
            }
        }

        // Сортировка зон по расстоянию
        usort($supportZones, fn($a, $b) => $a['distance'] <=> $b['distance']);
        usort($resistanceZones, fn($a, $b) => $a['distance'] <=> $b['distance']);

        return [
            'support' => $supportZones,
            'resistance' => $resistanceZones,
        ];
    }

    public static function detectOrderBlocks(
        array $candles,
        array $orderBook = [],
        float $distanceTolerance = -0.5,
        int $periods = 5,
        float $threshold = 0.0,
        bool $useWicks = false,
        int $bullishExtension = 6,
        int $bearishExtension = 6,
    ): array {
        $totalCandles = count($candles);
        if ($totalCandles <= $periods) {
            throw new \Exception("Недостаточно данных для анализа ордерных блоков.");
        }

        $bullishBlocks = [];
        $bearishBlocks = [];

        if ($orderBook) {
            $maxBidPrice = (float)$orderBook['b'][0][0];
            $minAskPrice = (float)$orderBook['a'][0][0];
        } else {
            $maxBidPrice = $minAskPrice = $candles[array_key_last($candles)]['c'];
        }

        for ($i = $periods; $i < $totalCandles; $i++) {
            $obIndex = $i - $periods;
            $potentialOB = $candles[$obIndex];

            // Проверка на бычий ордерный блок
            if ($potentialOB['c'] < $potentialOB['o']) {
                $isBullishOB = true;
                for ($j = 1; $j <= $periods; $j++) {
                    if ($candles[$obIndex + $j]['c'] <= $candles[$obIndex + $j]['o']) {
                        $isBullishOB = false;
                        break;
                    }
                }
                $absMove = (abs($candles[$obIndex]['c'] - $candles[$obIndex + $periods]['c']) / $candles[$obIndex]['c']) * 100;
                if ($isBullishOB && $absMove >= $threshold) {
                    $upper = $useWicks ? $potentialOB['h'] : $potentialOB['o'];
                    $lower = $potentialOB['l'];
                    $average = ($upper + $lower) / 2;

                    $distance = (($maxBidPrice - $lower) / $lower) * 100;

                    if ($distance > $distanceTolerance && $absMove > 1) {
                        $bullishBlocks[] = [
                            'type' => 'bullish',
                            'upper' => $upper,
                            'lower' => $lower,
                            'average' => $average,
                            'distance' => $distance,
                            'strength' => $absMove,
                        ];
                    }
                }
            }

            // Проверка на медвежий ордерный блок
            if ($potentialOB['c'] > $potentialOB['o']) {
                $isBearishOB = true;
                for ($j = 1; $j <= $periods; $j++) {
                    if ($candles[$obIndex + $j]['c'] >= $candles[$obIndex + $j]['o']) {
                        $isBearishOB = false;
                        break;
                    }
                }
                $absMove = (abs($candles[$obIndex]['c'] - $candles[$obIndex + $periods]['c']) / $candles[$obIndex]['c']) * 100;
                if ($isBearishOB && $absMove >= $threshold) {
                    $upper = $potentialOB['h'];
                    $lower = $useWicks ? $potentialOB['l'] : $potentialOB['o'];
                    $average = ($upper + $lower) / 2;

                    $distance = (($upper - $minAskPrice) / $minAskPrice) * 100;

                    if ($distance > $distanceTolerance && $absMove > 1) {
                        $bearishBlocks[] = [
                            'type' => 'bearish',
                            'upper' => $upper,
                            'lower' => $lower,
                            'average' => $average,
                            'distance' => $distance,
                            'strength' => $absMove,
                        ];
                    }
                }
            }
        }

        // Сортировка зон
        usort($bullishBlocks, fn($a, $b) => $a['distance'] <=> $b['distance']);
        usort($bearishBlocks, fn($a, $b) => $a['distance'] <=> $b['distance']);

        return [
            'bullish' => array_slice($bullishBlocks, 0, $bullishExtension),
            'bearish' => array_slice($bearishBlocks, 0, $bearishExtension),
        ];
    }
    
    public static function analyzeMACD(array $candles, int $fastLength = 12, int $slowLength = 26, int $signalLength = 9): array
    {
        // Проверяем, что данных достаточно для расчета
        if (count($candles) < max($fastLength, $slowLength, $signalLength)) {
            return [];
            //throw new \Exception("Недостаточно данных для анализа MACD.");
        }

        // Извлекаем цены закрытия
        $closingPrices = array_column($candles, 'c');

        // Вспомогательная функция для расчета EMA
        $calculateEMAForSeries = function (array $prices, int $length): array {
            $ema = [];
            $multiplier = 2 / ($length + 1);
            $previousEMA = array_sum(array_slice($prices, 0, $length)) / $length;

            foreach ($prices as $i => $price) {
                if ($i < $length - 1) {
                    $ema[] = null; // Недостаточно данных для расчета
                } elseif ($i == $length - 1) {
                    $ema[] = $previousEMA; // Первое значение EMA
                } else {
                    $currentEMA = (($price - $previousEMA) * $multiplier) + $previousEMA;
                    $ema[] = $currentEMA;
                    $previousEMA = $currentEMA;
                }
            }

            return $ema;
        };

        // Вычисляем EMA для быстрого и медленного периодов
        $fastEMA = $calculateEMAForSeries($closingPrices, $fastLength);
        $slowEMA = $calculateEMAForSeries($closingPrices, $slowLength);

        // Вычисляем MACD Line
        $macdLine = [];
        foreach ($fastEMA as $i => $fast) {
            $macdLine[] = isset($slowEMA[$i]) ? $fast - $slowEMA[$i] : null;
        }

        // Вычисляем Signal Line (EMA от MACD Line)
        $signalLine = $calculateEMAForSeries($macdLine, $signalLength);

        // Вычисляем гистограмму MACD
        $histogram = [];
        for ($i = 0; $i < count($signalLine); $i++) {
            $histogram[] = $macdLine[$i] - $signalLine[$i];
        }

        // Анализ сигналов
        $result = [];
        for ($i = 1; $i < count($histogram); $i++) {
            $macdLineValue = $macdLine[$i] ?? null;
            $signalLineValue = $signalLine[$i] ?? null;
            $histogramValue = $histogram[$i] ?? null;

            $prevHistogramValue = $histogram[$i - 1] ?? null;
            $prevMacdLineValue = $macdLine[$i - 1] ?? null;
            $prevPrevMacdLineValue = $macdLine[$i - 2] ?? null;
            $prevPrevPrevMacdLineValue = $macdLine[$i - 3] ?? null;
            $prevSignalLineValue = $signalLine[$i - 1] ?? null;
            $prevPrevSignalLineValue = $signalLine[$i - 2] ?? null;

            // Лонг сигналы
            $isMACDApproachingSignalLong = $isMACDCrossSignalLong = $isMACDAboveZeroLong = $isMACDCrossAboveZeroLong = false;
            if ($prevMacdLineValue !== null && $prevSignalLineValue !== null && $macdLineValue !== null && $signalLineValue !== null && $prevPrevMacdLineValue !== null && $prevPrevSignalLineValue !== null) {
                $isMACDCrossSignalLong = ($prevMacdLineValue <= $prevSignalLineValue && $macdLineValue > $signalLineValue && $macdLineValue < 0) || ($prevPrevMacdLineValue <= $prevPrevSignalLineValue && $prevMacdLineValue > $prevSignalLineValue && $macdLineValue > $signalLineValue && $macdLineValue < 0);
                $isMACDAboveZeroLong = $prevMacdLineValue < 0 && $macdLineValue > 0 && $macdLineValue >= $signalLineValue;
                $isMACDCrossAboveZeroLong = ($prevMacdLineValue <= $prevSignalLineValue && $macdLineValue > $signalLineValue  && $macdLine > 0) || ($prevPrevMacdLineValue <= $prevPrevSignalLineValue && $prevMacdLineValue > $prevSignalLineValue && $macdLineValue > $signalLineValue  && $macdLine > 0);
                $isMACDApproachingSignalLong = $prevPrevMacdLineValue > $prevPrevPrevMacdLineValue &&  $prevMacdLineValue > $prevPrevMacdLineValue /*&& $macdLineValue > $signalLineValue*/;
            }

            // Шорт сигналы
            $isMACDApproachingSignalShort = $isMACDCrossSignalShort = $isMACDBelowZeroShort = $isMACDCrossBelowZeroShort = false;
            if ($prevMacdLineValue !== null && $prevSignalLineValue !== null && $macdLineValue !== null && $signalLineValue !== null && $prevPrevMacdLineValue !== null && $prevPrevSignalLineValue !== null) {
                $isMACDCrossSignalShort = ($prevMacdLineValue >= $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue > 0) || ($prevPrevMacdLineValue >= $prevPrevSignalLineValue && $prevMacdLineValue < $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue > 0);
                $isMACDBelowZeroShort = $prevMacdLineValue > 0 && $macdLineValue < 0 && $macdLineValue <= $signalLineValue;
                $isMACDCrossBelowZeroShort = ($prevMacdLineValue >= $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue < 0) || ($prevPrevMacdLineValue >= $prevPrevSignalLineValue && $prevMacdLineValue < $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue < 0);
                $isMACDApproachingSignalShort = $prevPrevMacdLineValue < $prevPrevPrevMacdLineValue && $prevMacdLineValue < $prevPrevMacdLineValue /*&& $macdLineValue < $signalLineValue*/;
            }

            $result[] = [
                'close' => $candles[$i]['c'],
                'macd_line' => $macdLineValue,
                'signal_line' => $signalLineValue,
                'histogram_value' => $histogramValue,
                'isLongDirection' => $isMACDApproachingSignalLong,
                'isShortDirection' => $isMACDApproachingSignalShort,
                'isLong' => $isMACDCrossSignalLong || $isMACDAboveZeroLong || $isMACDCrossAboveZeroLong,
                'isShort' => $isMACDCrossSignalShort || $isMACDBelowZeroShort || $isMACDCrossBelowZeroShort,
                'shortTypeAr' => [
                    'cross' => $isMACDCrossSignalShort,
                    'cross0' => $isMACDBelowZeroShort,
                    'crossB0' => $isMACDCrossBelowZeroShort,
                    'noCross' => $isMACDApproachingSignalShort,
                ],
                'longTypeAr' => [
                    'cross' => $isMACDCrossSignalLong,
                    'cross0' => $isMACDAboveZeroLong,
                    'crossA0' => $isMACDCrossAboveZeroLong,
                    'noCross' => $isMACDApproachingSignalLong,
                ],
                'params' => [
                    'fastLength' => $fastLength,
                    'slowLength' => $slowLength,
                    'signalLength' => $signalLength,
                ],
            ];
        }

        return $result;
    }

    //https://www.tradingview.com/script/soSwR4mX-MACD-Divergences-by-DaviddTech/
    public static function calculateMACDDivergences(
        array $candles,
        int $fastLength = 5,
        int $slowLength = 35,
        int $signalLength = 5,
        string $oscillatorType = 'SMA',
        string $signalLineType = 'SMA'
    ) {
        $result = [];

        $closePrices = array_column($candles, 'c');
        $macdLine = [];
        $signalLine = [];
        $histogram = [];

        // Вычисление MACD
        for ($i = 0; $i < count($closePrices); $i++) {
            $fastMa = self::calculateMa(array_slice($closePrices, max(0, $i - $fastLength + 1), $fastLength), $fastLength, $oscillatorType);
            $slowMa = self::calculateMa(array_slice($closePrices, max(0, $i - $slowLength + 1), $slowLength), $slowLength, $oscillatorType);
            //$fastMa = self::calculateMa($closePrices, $fastLength, $oscillatorType);
            //$slowMa = self::calculateMa($closePrices, $slowLength, $oscillatorType);

            $macd = $fastMa - $slowMa;
            $macdLine[] = $macd;

            $signal = self::calculateMa(array_slice($macdLine, max(0, $i - $signalLength + 1), $signalLength), $signalLength, $signalLineType);
            //$signal = self::calculateMa($macdLine, $signalLength, $signalLineType);
            $signalLine[] = $signal;

            $histogram[] = $macd - $signal;
        }

        // Поиск локальных экстремумов
        $priceLows = self::findLocalExtremes(array_column($candles, 'l'), 'low', 3);
        $priceHighs = self::findLocalExtremes(array_column($candles, 'h'), 'high', 3);
        $macdLows = self::findLocalExtremes($macdLine, 'low', 3);
        $macdHighs = self::findLocalExtremes($macdLine, 'high', 3);

        // Анализ дивергенций
        for ($i = 0; $i < count($closePrices); $i++) {
            //# ищем пересечения
            $macdLineValue = $macdLine[$i] ?? null;
            $signalLineValue = $signalLine[$i] ?? null;
            $histogramValue = $histogram[$i] ?? null;

            $prevHistogramValue = $histogram[$i - 1] ?? null;
            $prevMacdLineValue = $macdLine[$i - 1] ?? null;
            $prevPrevMacdLineValue = $macdLine[$i - 2] ?? null;
            $prevSignalLineValue = $signalLine[$i - 1] ?? null;
            $prevPrevSignalLineValue = $signalLine[$i - 2] ?? null;

            // Лонг сигналы
            $isMACDCrossSignalLong = $isMACDAboveZeroLong = $isMACDCrossAboveZeroLong = false;
            if ($prevMacdLineValue !== null && $prevSignalLineValue !== null && $macdLineValue !== null && $signalLineValue !== null && $prevPrevMacdLineValue !== null && $prevPrevSignalLineValue !== null) {
                $isMACDCrossSignalLong = ($prevMacdLineValue <= $prevSignalLineValue && $macdLineValue > $signalLineValue && $macdLineValue < 0) || ($prevPrevMacdLineValue <= $prevPrevSignalLineValue && $prevMacdLineValue > $prevSignalLineValue && $macdLineValue > $signalLineValue && $macdLineValue < 0);
                $isMACDAboveZeroLong = $prevPrevMacdLineValue <= 0 && $prevMacdLineValue > 0 && $macdLineValue > 0 && $macdLineValue >= $signalLineValue;
                $isMACDCrossAboveZeroLong = ($prevMacdLineValue <= $prevSignalLineValue && $macdLineValue > $signalLineValue  && $macdLine > 0) || ($prevPrevMacdLineValue <= $prevPrevSignalLineValue && $prevMacdLineValue > $prevSignalLineValue && $macdLineValue > $signalLineValue  && $macdLine > 0);
            }

            // Шорт сигналы
            $isMACDCrossSignalShort = $isMACDBelowZeroShort = $isMACDCrossBelowZeroShort = false;
            if ($prevMacdLineValue !== null && $prevSignalLineValue !== null && $macdLineValue !== null && $signalLineValue !== null && $prevPrevMacdLineValue !== null && $prevPrevSignalLineValue !== null) {
                $isMACDCrossSignalShort = ($prevMacdLineValue >= $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue > 0) || ($prevPrevMacdLineValue >= $prevPrevSignalLineValue && $prevMacdLineValue < $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue > 0);
                $isMACDBelowZeroShort = $prevPrevMacdLineValue >= 0 && $prevMacdLineValue < 0 && $macdLineValue < 0 && $macdLineValue <= $signalLineValue;
                $isMACDCrossBelowZeroShort = ($prevMacdLineValue >= $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue < 0) || ($prevPrevMacdLineValue >= $prevPrevSignalLineValue && $prevMacdLineValue < $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue < 0);
            }

            //# ищем дивергенцию
            $isRegularBullish = $isHiddenBullish = $isRegularBearish = $isHiddenBearish = false;

            // Погрешность для отклонения индексов (например, ±n)
            $priceIndexTolerance = 10;
            $indexTolerance = 4;

            // проверяем на бычью дивергенцию
            $priceLow1 = $priceLow2 = $macdLow1 = $macdLow2 = null;

            $reversPriceLows = array_reverse($priceLows);
            foreach ($reversPriceLows as $key => $priceLow) {
                if ($priceLow['index'] <= $i && abs($priceLow['index'] - $i) <= $priceIndexTolerance) {
                    $priceLow2 = $priceLow;
                    $priceLow1 = $reversPriceLows[$key + 1];
                    break;
                }
            }

            if ($priceLow1 != null && $priceLow2 != null) {
                foreach ($macdLows as $macdLow) {
                    if (abs($priceLow1['index'] - $macdLow['index']) <= $indexTolerance) {
                        $macdLow1 = $macdLow;
                    } else if (abs($priceLow2['index'] - $macdLow['index']) <= $indexTolerance) {
                        $macdLow2 = $macdLow;
                    }
                }

                if ($macdLow1 != null && $macdLow2 != null) {
                    if ($priceLow1['value'] > $priceLow2['value'] && $macdLow1['value'] < $macdLow2['value']) {
                        $isRegularBullish = true;
                    } else if ($priceLow1['value'] < $priceLow2['value'] && $macdLow1['value'] > $macdLow2['value']) {
                        $isHiddenBullish = true;
                    }
                }
            }

            // проверяем на медвежью дивергенцию
            $priceHigh1 = $priceHigh2 = $macdHigh1 = $macdHigh2 = null;

            $reversPriceHighs = array_reverse($priceHighs);
            foreach ($reversPriceHighs as $key => $priceHigh) {
                if ($priceHigh['index'] <= $i && abs($priceHigh['index'] - $i) <= $priceIndexTolerance) {
                    $priceHigh2 = $priceHigh;
                    $priceHigh1 = $reversPriceHighs[$key + 1];
                    break;
                }
            }

            if ($priceHigh1 != null && $priceHigh2 != null) {
                foreach ($macdHighs as $macdHigh) {
                    if (abs($priceHigh1['index'] - $macdHigh['index']) <= $indexTolerance) {
                        $macdHigh1 = $macdHigh;
                    } else if (abs($priceHigh2['index'] - $macdHigh['index']) <= $indexTolerance) {
                        $macdHigh2 = $macdHigh;
                    }
                }

                if ($macdHigh1 != null && $macdHigh2 != null) {
                    if ($priceHigh1['value'] < $priceHigh2['value'] && $macdHigh1['value'] > $macdHigh2['value']) {
                        $isRegularBearish = true;
                    } else if ($priceHigh1['value'] > $priceHigh2['value'] && $macdHigh1['value'] < $macdHigh2['value']) {
                        $isHiddenBearish = true;
                    }
                }
            }

            $result[] = [
                'close' => $closePrices[$i],
                'macd_line' => $macdLine[$i],
                'signal_line' => $signalLine[$i],
                'histogram_value' => $histogram[$i],
                'longDivergenceTypeAr' => [
                    'regular' => $isRegularBullish,
                    'hidden' => $isHiddenBullish,
                ],
                'shortDivergenceTypeAr' => [
                    'regular' => $isRegularBearish,
                    'hidden' => $isHiddenBearish,
                ],
                'shortCrossTypeAr' => [
                    'cross' => $isMACDCrossSignalShort,
                    'cross0' => $isMACDBelowZeroShort,
                    'crossB0' => $isMACDCrossBelowZeroShort,
                ],
                'longCrossTypeAr' => [
                    'cross' => $isMACDCrossSignalLong,
                    'cross0' => $isMACDAboveZeroLong,
                    'crossA0' => $isMACDCrossAboveZeroLong,
                ],
                'isShort' => ($isRegularBearish || $isHiddenBearish) && ($isMACDCrossSignalShort || $isMACDCrossBelowZeroShort),
                'isLong' => ($isRegularBullish || $isHiddenBullish) && ($isMACDCrossSignalLong || $isMACDCrossAboveZeroLong),
                'params' => [
                    'fastLength' => $fastLength,
                    'slowLength' => $slowLength,
                    'signalLength' => $signalLength,
                ],
                'extremes' => [
                    'priceLows' => $priceLows,
                    'priceHighs' => $priceHighs,
                    'macdLows' => $macdLows,
                    'macdHighs' => $macdHighs,
                    'selected' => [
                        'priceHigh1' => $priceHigh1,
                        'priceHigh2' => $priceHigh2,
                        'macdHigh1' => $macdHigh1,
                        'macdHigh2' => $macdHigh2,

                        'priceLow1' => $priceLow1,
                        'priceLow2' => $priceLow2,
                        'macdLow1' => $macdLow1,
                        'macdLow2' => $macdLow2,
                    ],

                ],
            ];
        }

        return $result;
    }

    private static function calculateMa(array $values, int $length, string $type): float
    {
        if (empty($values)) {
            return 0.0;
        }

        if ($type === 'SMA') {
            return array_sum($values) / count($values);
        }

        if ($type === 'EMA') {
            $k = 2 / ($length + 1);

            // Проверяем, достаточно ли значений для вычисления начального SMA
            if (count($values) < $length) {
                return array_sum($values) / count($values); // Если значений меньше, возвращаем их среднее
            }

            // Первое значение EMA инициализируется через SMA
            $ema = array_sum(array_slice($values, 0, $length)) / $length;

            // Вычисляем EMA для оставшихся значений
            for ($i = $length; $i < count($values); $i++) {
                $ema = $values[$i] * $k + $ema * (1 - $k);
            }

            return $ema;
        }

        throw new InvalidArgumentException('Unknown MA type');
    }

    // аналог метода из внутренней библиотеки php
    public static function calculateMacdExt(
        array $prices,
        int $fastPeriod = 5,
        string $fastMAType = 'SMA' ,
        int $slowPeriod = 35,
        string $slowMAType = 'SMA',
        int $signalPeriod = 5,
        string $signalMAType = 'SMA',
        int $priceIndexTolerance = 10,
        string $divergenceType = 'macdLine',
        int $indexTolerance = 5,
        int $widthTolerance = 6,
        int $extremesRange = 4,

    ): array
    {
        $closePrices = array_column($prices, 'c');

        // Проверка входных данных
        if (count($closePrices) < max($fastPeriod, $slowPeriod, $signalPeriod)) {
            return [];
            //throw new \Exception("Недостаточно данных для расчёта MACD");
        }

        // Функция для расчёта скользящих средних
        $calculateMA = function (array $data, int $period, string $type) {
            $result = [];
            $length = count($data);

            if ($type === 'SMA') {
                for ($i = 0; $i <= $length - $period; $i++) {
                    $result[] = array_sum(array_slice($data, $i, $period)) / $period;
                }
            } elseif ($type === 'EMA') {
                $multiplier = 2 / ($period + 1);
                $result[] = array_sum(array_slice($data, 0, $period)) / $period; // SMA для первого значения
                for ($i = $period; $i < $length; $i++) {
                    $result[] = ($data[$i] - end($result)) * $multiplier + end($result);
                }
            } else {
                throw new Exception("Неподдерживаемый тип MA: $type");
            }

            return $result;
        };

        // 1. Расчёт быстрого и медленного MA
        $fastMA = $calculateMA($closePrices, $fastPeriod, $fastMAType);
        $slowMA = $calculateMA($closePrices, $slowPeriod, $slowMAType);

        // Выравниваем массивы (медленное MA короче)
        $fastMA = array_slice($fastMA, count($fastMA) - count($slowMA));

        // 2. Расчёт линии MACD
        $macdLine = [];
        for ($i = 0; $i < count($slowMA); $i++) {
            $macdLine[] = $fastMA[$i] - $slowMA[$i];
        }

        // 3. Расчёт сигнальной линии
        $signalLine = $calculateMA($macdLine, $signalPeriod, $signalMAType);

        // Выравниваем массивы (MACD короче)
        $macdLine = array_slice($macdLine, count($macdLine) - count($signalLine));

        // 4. Расчёт гистограммы
        $histogram = [];
        for ($i = 0; $i < count($signalLine); $i++) {
            $histogram[] = $macdLine[$i] - $signalLine[$i];
        }

        $extremesPrices = array_slice($prices, -count($signalLine));
        // Поиск локальных экстремумов
        $priceLows = self::findLocalExtremes(array_column($extremesPrices, 'l'), 'low', $extremesRange);
        $priceHighs = self::findLocalExtremes(array_column($extremesPrices, 'h'), 'high', $extremesRange);

        $macdLows = $macdHighs = [];
        if ($divergenceType == 'macdLine') {
            $macdLows = self::findLocalExtremes($macdLine, 'low', $extremesRange);
            $macdHighs = self::findLocalExtremes($macdLine, 'high', $extremesRange);
        } else if ($divergenceType == 'histogram') {
            $macdLows = self::findLocalExtremes($histogram, 'low', $extremesRange);
            $macdHighs = self::findLocalExtremes($histogram, 'high', $extremesRange);
        }

        $res = [];
        for ($i = 0; $i < count($signalLine); $i++) {

            //# ищем пересечения
            $macdLineValue = $macdLine[$i] ?? null;
            $signalLineValue = $signalLine[$i] ?? null;
            $histogramValue = $histogram[$i] ?? null;

            $prevHistogramValue = $histogram[$i - 1] ?? null;
            $prevMacdLineValue = $macdLine[$i - 1] ?? null;
            $prevPrevMacdLineValue = $macdLine[$i - 2] ?? null;
            $prevSignalLineValue = $signalLine[$i - 1] ?? null;
            $prevPrevSignalLineValue = $signalLine[$i - 2] ?? null;

            // Лонг сигналы
            $isMACDCrossSignalLong = $isMACDAboveZeroLong = $isMACDCrossAboveZeroLong = false;
            if ($prevMacdLineValue !== null && $prevSignalLineValue !== null && $macdLineValue !== null && $signalLineValue !== null && $prevPrevMacdLineValue !== null && $prevPrevSignalLineValue !== null) {
                $isMACDCrossSignalLong = ($prevMacdLineValue <= $prevSignalLineValue && $macdLineValue > $signalLineValue && $macdLineValue < 0) || ($prevPrevMacdLineValue <= $prevPrevSignalLineValue && $prevMacdLineValue > $prevSignalLineValue && $macdLineValue > $signalLineValue && $macdLineValue < 0);
                $isMACDAboveZeroLong = false;// $prevMacdLineValue < 0 && $macdLineValue > 0 && $macdLineValue >= $signalLineValue;
                $isMACDCrossAboveZeroLong = ($prevMacdLineValue <= $prevSignalLineValue && $macdLineValue > $signalLineValue  && $macdLine > 0) || ($prevPrevMacdLineValue <= $prevPrevSignalLineValue && $prevMacdLineValue > $prevSignalLineValue && $macdLineValue > $signalLineValue  && $macdLine > 0);
            }

            // Шорт сигналы
            $isMACDCrossSignalShort = $isMACDBelowZeroShort = $isMACDCrossBelowZeroShort = false;
            if ($prevMacdLineValue !== null && $prevSignalLineValue !== null && $macdLineValue !== null && $signalLineValue !== null && $prevPrevMacdLineValue !== null && $prevPrevSignalLineValue !== null) {
                $isMACDCrossSignalShort = ($prevMacdLineValue >= $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue > 0) || ($prevPrevMacdLineValue >= $prevPrevSignalLineValue && $prevMacdLineValue < $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue > 0);
                $isMACDBelowZeroShort = false;//$prevMacdLineValue > 0 && $macdLineValue < 0 && $macdLineValue <= $signalLineValue;
                $isMACDCrossBelowZeroShort = ($prevMacdLineValue >= $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue < 0) || ($prevPrevMacdLineValue >= $prevPrevSignalLineValue && $prevMacdLineValue < $prevSignalLineValue && $macdLineValue < $signalLineValue && $macdLineValue < 0);
            }
            
            //# ищем дивергенцию
            $isRegularBullish = $isHiddenBullish = $isRegularBearish = $isHiddenBearish = false;

            // проверяем на бычью дивергенцию
            for ($tolerance = 1; $tolerance <= 3; $tolerance++) {
                list($priceLow1, $priceLow2, $macdLow1, $macdLow2) = self::findMACDDivergence(
                    $priceLows, $macdLows, $indexTolerance, $priceIndexTolerance, $i, $tolerance
                );

                if ($priceLow1 && $priceLow2 && $macdLow1 && $macdLow2) {
                    if ($priceLow1['value'] > $priceLow2['value'] && $macdLow1['value'] < $macdLow2['value']) {
                        $isRegularBullish = true;
                    } elseif ($priceLow1['value'] < $priceLow2['value'] && $macdLow1['value'] > $macdLow2['value']) {
                        $isHiddenBullish = true;
                    }
                }

                if (($isRegularBullish || $isHiddenBullish) && ($priceLow2['index'] - $priceLow1['index']) >= $widthTolerance) {
                    break;
                }
            }

            for ($tolerance = 1; $tolerance <= 3; $tolerance++) {
                list($priceHigh1, $priceHigh2, $macdHigh1, $macdHigh2) = self::findMACDDivergence(
                    $priceHighs, $macdHighs, $indexTolerance, $priceIndexTolerance, $i, $tolerance
                );

                if ($priceHigh1 && $priceHigh2 && $macdHigh1 && $macdHigh2) {
                    if ($priceHigh1['value'] < $priceHigh2['value'] && $macdHigh1['value'] > $macdHigh2['value']) {
                        $isRegularBearish = true;
                    } elseif ($priceHigh1['value'] > $priceHigh2['value'] && $macdHigh1['value'] < $macdHigh2['value']) {
                        $isHiddenBearish = true;
                    }
                }

                if (($isRegularBearish || $isHiddenBearish) && ($priceHigh2['index'] - $priceHigh1['index']) >= $widthTolerance) {
                    break;
                }
            }

            $longDivergenceDistance = $shortDivergenceDistance = false;
            if ($isRegularBullish || $isHiddenBullish)
                $longDivergenceDistance = count($signalLine) - intval($priceLow2['index']);

            if ($isRegularBearish || $isHiddenBearish)
                $shortDivergenceDistance = count($signalLine) - intval($priceHigh2['index']);

            $res[] = [
                //'close' => $closePrices[$i],
                'main_values' => [
                    'macd_line' => $macdLine[$i],
                    'signal_line' => $signalLine[$i],
                    'histogram_value' => $histogram[$i],
                ],
                'longDivergenceTypeAr' => [
                    'regular' => $isRegularBullish,
                    'hidden' => $isHiddenBullish,
                ],
                'shortDivergenceTypeAr' => [
                    'regular' => $isRegularBearish,
                    'hidden' => $isHiddenBearish,
                ],
                'longDivergenceDistance' => $longDivergenceDistance,
                'shortDivergenceDistance' => $shortDivergenceDistance,
                'shortCrossTypeAr' => [
                    'cross' => $isMACDCrossSignalShort,
                    'cross0' => $isMACDBelowZeroShort,
                    'crossB0' => $isMACDCrossBelowZeroShort,
                ],
                'longCrossTypeAr' => [
                    'cross' => $isMACDCrossSignalLong,
                    'cross0' => $isMACDAboveZeroLong,
                    'crossA0' => $isMACDCrossAboveZeroLong,
                ],
                'isShort' => ($isRegularBearish || $isHiddenBearish) && ($isMACDCrossSignalShort || $isMACDCrossBelowZeroShort),
                'isLong' => ($isRegularBullish || $isHiddenBullish) && ($isMACDCrossSignalLong || $isMACDCrossAboveZeroLong),
                'extremes' => [
                    //'extremesPrices' => $extremesPrices,
                    'priceLows' => $priceLows,
                    'priceHighs' => $priceHighs,
                    'macdLows' => $macdLows,
                    'macdHighs' => $macdHighs,
                    'selected' => [
                        'high' => [
                            'priceHigh1' => $priceHigh1,
                            'priceHigh2' => $priceHigh2,
                            'macdHigh1' => $macdHigh1,
                            'macdHigh2' => $macdHigh2,
                        ],
                        'low' => [
                            'priceLow1' => $priceLow1,
                            'priceLow2' => $priceLow2,
                            'macdLow1' => $macdLow1,
                            'macdLow2' => $macdLow2,
                        ],
                    ],

                ],
            ];

        }

        return $res ?? [];
    }

    private static function findMACDDivergence($priceExtremes, $macdExtremes, $indexTolerance, $priceIndexTolerance, $currentIndex, $priceStep = 1) {
        $price1 = $price2 = $macd1 = $macd2 = null;
        $reversedPriceExtremes = array_reverse($priceExtremes);

        foreach ($reversedPriceExtremes as $key => $priceExtreme) {
            if ($priceExtreme['index'] <= $currentIndex && abs($priceExtreme['index'] - $currentIndex) <= $priceIndexTolerance) {
                $price2 = $priceExtreme;
                $price1 = $reversedPriceExtremes[$key + $priceStep] ?? null;
                break;
            }
        }

        if ($price1 !== null && $price2 !== null) {
            foreach ($macdExtremes as $macdExtreme) {
                if (abs($price1['index'] - $macdExtreme['index']) <= $indexTolerance) {
                    $macd1 = $macdExtreme;
                } elseif (abs($price2['index'] - $macdExtreme['index']) <= $indexTolerance) {
                    $macd2 = $macdExtreme;
                }
            }
        }

        return [$price1, $price2, $macd1, $macd2];
    }

    public static function findLocalExtremes(array $values, string $type, int $range): array
    {
        $extremes = [];
        $count = count($values);

        for ($i = $range; $i < $count - $range; $i++) {
            $isExtreme = true;

            for ($j = $i - $range; $j <= $i + $range; $j++) {
                if ($j === $i) {
                    continue;
                }

                if (($type === 'low' && $values[$j] <= $values[$i]) ||
                    ($type === 'high' && $values[$j] >= $values[$i])) {
                    $isExtreme = false;
                    break;
                }
            }

            if ($isExtreme) {
                $extremes[] = [
                    'index' => $i,
                    'value' => $values[$i],
                ];
            }
        }

        return $extremes;
    }

    //https://www.tradingview.com/script/qt6xLfLi-Impulse-MACD-LazyBear/
    /**
     * Анализ Impulse MACD с определением тренда (strong/weak up/down).
     *
     * @param array $candles    Массив свечей с ключами 'h', 'l', 'c', 't'
     * @param int   $lengthMA   Период MA (по умолчанию 34)
     * @param int   $lengthSignal  Период сигнальной линии (по умолчанию 9)
     *
     * @return array Массив результатов с полями:
     *   - timestamp
     *   - close
     *   - impulse_macd
     *   - signal_line
     *   - histogram
     *   - trend       // 2: strong_up, 1: weak_up, -1: weak_down, -2: strong_down
     *
     * @throws \Exception Если недостаточно данных
     */
    public static function analyzeImpulseMACD(array $candles, int $lengthMA = 32, int $lengthSignal = 9, $rangeSignals = 3): array
    {
        $count = count($candles);
        if ($count < $lengthMA + $lengthSignal) {
            return [];
            //throw new \Exception("Недостаточно данных для расчета Impulse MACD.");
        }

        // HLC3: (High + Low + Close) / 3
        $hlc3   = array_map(fn($c) => ($c['h'] + $c['l'] + $c['c']) / 3, $candles);
        $highs  = array_column($candles, 'h');
        $lows   = array_column($candles, 'l');

        // SMMA (Wilders)
        $calculateSMMA = function(array $data, int $len): array {
            $smma = [];
            $prev = null;
            foreach ($data as $i => $val) {
                if ($i < $len - 1) {
                    $smma[] = null;
                } elseif ($i === $len - 1) {
                    $sma    = array_sum(array_slice($data, 0, $len)) / $len;
                    $smma[] = $sma;
                    $prev   = $sma;
                } else {
                    $cur    = ($prev * ($len - 1) + $val) / $len;
                    $smma[] = $cur;
                    $prev   = $cur;
                }
            }
            return $smma;
        };

        // SMA
        $calculateSMA = function(array $data, int $len): array {
            $sma = [];
            foreach ($data as $i => $val) {
                if ($i < $len - 1) {
                    $sma[] = null;
                } else {
                    $slice  = array_slice($data, $i - $len + 1, $len);
                    $sma[]  = array_sum($slice) / $len;
                }
            }
            return $sma;
        };

        // ZLEMA
        $calculateZLEMA = function(array $data, int $len): array {
            $zlema      = [];
            $multiplier = 2 / ($len + 1);
            $ema1 = $ema2 = null;

            foreach ($data as $i => $val) {
                if ($i < $len - 1) {
                    $zlema[] = null;
                } elseif ($i === $len - 1) {
                    $sum     = array_sum(array_slice($data, 0, $len));
                    $ema1    = $sum / $len;
                    $ema2    = $ema1;
                    $zlema[] = $ema1;
                } else {
                    $ema1    = (($val - $ema1) * $multiplier) + $ema1;
                    $ema2    = (($ema1 - $ema2) * $multiplier) + $ema2;
                    $zlema[] = $ema1 + ($ema1 - $ema2);
                }
            }
            return $zlema;
        };

        // Основные линии
        $hi = $calculateSMMA($highs, $lengthMA);
        $lo = $calculateSMMA($lows,  $lengthMA);
        $mi = $calculateZLEMA($hlc3,  $lengthMA);

        // Impulse MACD и сигнальная линия
        $md       = [];
        foreach ($mi as $i => $m) {
            if (isset($m, $hi[$i], $lo[$i])) {
                $md[] = $m > $hi[$i]
                    ? $m - $hi[$i]
                    : ($m < $lo[$i]
                        ? $m - $lo[$i]
                        : 0
                    );
            } else {
                $md[] = null;
            }
        }
        $signal    = $calculateSMA($md, $lengthSignal);

        // Гистограмма
        $histogram = array_map(fn($m, $s) => isset($m, $s) ? $m - $s : null, $md, $signal);


        // Рассчитываем серии подряд идущих положительных/отрицательных гистограмм
        $longStreaks = [];
        $shortStreaks = [];
        $currentLong = 0;
        $currentShort = 0;
        foreach ($histogram as $h) {
            if ($h !== null) {
                if ($h > 0) {
                    $currentLong++;
                    $currentShort = 0;
                } elseif ($h < 0) {
                    $currentShort++;
                    $currentLong = 0;
                } else {
                    $currentLong = 0;
                    $currentShort = 0;
                }
            } else {
                $currentLong = 0;
                $currentShort = 0;
            }
            $longStreaks[] = $currentLong;
            $shortStreaks[] = $currentShort;
        }

        // Формируем результат с определением тренда
        $result = [];
        foreach ($candles as $i => $c) {
            // Временная метка
            $ts = $c['t'] / 1000;
            $dt = \DateTime::createFromFormat('U.u', sprintf('%.6F', $ts));
            $dt->modify('+3 hours');


            // Определение тренда по HLC3 vs Mid/High/Low
            $trend = null;
            $longDirection = false;
            $shortDirection = false;
            $trendText = '';
            if (isset($mi[$i], $hi[$i], $lo[$i])) {
                $price = $hlc3[$i];
                if ($price > $mi[$i]) {
                    $trend = $price > $hi[$i] ? 2 : 1;
                    $longDirection = true;
                    $trendText = 'up';
                } else {
                    $trend = $price < $lo[$i] ? -2 : -1;
                    $shortDirection = true;
                    $trendText = 'down';
                }
            }

            // Определяем isLong и isShort по 2 барам гистограммы
            $isLong = false;
            $isShort = false;

            // текущая и предыдущая гистограммы
            $h0 = $histogram[$i]   ?? null;
            $h1 = $histogram[$i-1] ?? null;

            // Получаем текущие длины серий
            $currentLongStreak = $longStreaks[$i] ?? 0;
            $currentShortStreak = $shortStreaks[$i] ?? 0;

            if ($longDirection && $h0 > 0 && isset($h1) && $currentLongStreak <= $rangeSignals) {
                if (
                    ($h1 === 0 && $h0 > 0) // Вариант 1: предыдущая = 0, текущая > 0
                    || ($h1 > 0 && $h0 > $h1) // Вариант 2: оба >0 и текущая > предыдущей
                ) {
                    $isLong = true;
                }
            }
            elseif ($shortDirection && $h0 < 0 && isset($h1) && $currentShortStreak <= $rangeSignals) {
                if (
                    ($h1 === 0 && $h0 < 0) // Вариант 1: предыдущая = 0, текущая < 0
                    || ($h1 < 0 && $h0 < $h1) // Вариант 2: оба <0 и текущая < предыдущей
                ) {
                    $isShort = true;
                }
            }

            $result[] = [
                'timestamp'    => $dt->format('H:i d.m'),
                'isLong'        => $isLong,
                'isShort'        => $isShort,
                'close'        => $c['c'],
                'impulse_macd' => $md[$i]        ?? null,
                'signal_line'  => $signal[$i]    ?? null,
                'histogram'    => $histogram[$i] ?? null,
                'histogram_ar_3'    => [$h0, $h1],
                'trend'        => [
                    'longDirection' => $longDirection,
                    'shortDirection' => $shortDirection,
                    'trendVal' => $trend,
                    'trendText' => $trendText,
                ],
            ];
        }

        return $result;
    }
    
    public static function simpleTrendLine(array $candles, int $shortPeriod = 30, int $longPeriod = 100): array
    {
        // Шаг 1: Поиск экстремумов
        $priceLows = self::findLocalExtremes(array_column($candles, 'l'), 'low', 3);
        $priceHighs = self::findLocalExtremes(array_column($candles, 'h'), 'high', 3);

        $results = [];

        // Шаг 2: Обработка свечей
        for ($i = count($candles) - $longPeriod; $i < count($candles); $i++) {
            // Находим экстремумы для короткого и длинного периодов
            $shortLows = array_filter($priceLows, fn($low) => $low['index'] >= $i - $shortPeriod && $low['index'] <= $i);
            $shortHighs = array_filter($priceHighs, fn($high) => $high['index'] >= $i - $shortPeriod && $high['index'] <= $i);
            $longLows = array_filter($priceLows, fn($low) => $low['index'] >= $i - $longPeriod && $low['index'] <= $i);
            $longHighs = array_filter($priceHighs, fn($high) => $high['index'] >= $i - $longPeriod && $high['index'] <= $i);

            if (empty($shortLows) || empty($shortHighs) || empty($longLows) || empty($longHighs)) {
                continue; // Пропускаем итерацию, если данных недостаточно
            }

            // Находим экстремумы
            $lowestShort = min(array_column($shortLows, 'value'));
            $highestShort = max(array_column($shortHighs, 'value'));
            $lowestLong = min(array_column($longLows, 'value'));
            $highestLong = max(array_column($longHighs, 'value'));

            // Шаг 3: Определение тренда
            $trend = '';
            $trendPrice = 0;
            $currentCandle = $candles[$i];

            if ($lowestShort > $lowestLong && $highestShort > $highestLong) {
                // Восходящий тренд
                $trend = 'uptrend';
                // Линейная интерполяция на основе разницы между текущим минимумом и максимумом
                $trendPrice = $lowestShort + ($currentCandle['c'] - $lowestShort) * (($highestShort - $lowestShort) / ($highestShort - $lowestShort));
            } elseif ($lowestShort < $lowestLong && $highestShort < $highestLong) {
                // Нисходящий тренд
                $trend = 'downtrend';
                // Линейная интерполяция на основе разницы между текущим максимумом и минимумом
                $trendPrice = $highestShort - ($highestShort - $currentCandle['c']) * (($highestShort - $lowestShort) / ($highestShort - $lowestShort));
            } else {
                // Боковой тренд
                $trend = 'sideways';
                // Средняя цена между минимумом и максимумом
                $trendPrice = ($lowestShort + $highestShort) / 2;
            }

            // Шаг 4: Сохранение результата
            $results[] = [
                'index' => $i,
                'trend' => $trend,
                'price' => $trendPrice,
                'extremes' => [
                    'lowestShort' => $lowestShort,
                    'highestShort' => $highestShort,
                    'lowestLong' => $lowestLong,
                    'highestLong' => $highestLong,
                ],
            ];
        }

        return $results;
    }

    //переписанный с C++ метод из библиоеки https://www.php.net/manual/en/function.trader-atr.php
    public static function calculateATR(array $candles, int $period = 14, float $multiplier = 1.5, float $defaultFlatThreshold = 0.02): array {
        $atr = $res = [];
        $trueRanges = [];
        $prevClose = null;

        $count = count($candles);
        if ($count < $period) {
            return [];
            //throw new \InvalidArgumentException('Количество свечей должно быть не меньше заданного периода.');
        }

        // Вычисляем True Range (TR) для каждой свечи
        for ($i = 0; $i < $count; $i++) {
            $currentHigh = $candles[$i]['h'];
            $currentLow  = $candles[$i]['l'];
            $currentClose = $candles[$i]['c'];

            if ($prevClose === null) {
                $trueRange = $currentHigh - $currentLow;
            } else {
                $tr1 = $currentHigh - $currentLow;
                $tr2 = abs($currentHigh - $prevClose);
                $tr3 = abs($currentLow - $prevClose);
                $trueRange = max($tr1, $tr2, $tr3);
            }
            $trueRanges[] = $trueRange;
            $prevClose = $currentClose;
        }

        // Вычисляем ATR по методу Вайлдера
        for ($i = 0; $i < $count; $i++) {
            if ($i < $period) {
                $atr[$i] = null; // недостаточно свечей для первого ATR
            } elseif ($i == $period) {
                $atr[$i] = array_sum(array_slice($trueRanges, 0, $period)) / $period;
            } else {
                $atr[$i] = (($atr[$i - 1] * ($period - 1)) + $trueRanges[$i]) / $period;
            }
        }

        // --- Вычисляем адаптивный flatThreshold ---
        // Собираем все значения относительного ATR (ATR/Close) для свечей, где ATR вычислен
        $ratios = [];
        for ($i = $period; $i < $count; $i++) {
            $close = $candles[$i]['c'] ?? 0;
            if ($close > 0 && $atr[$i] !== null) {
                $ratios[] = $atr[$i] / $close;
            }
        }
        if (empty($ratios)) {
            $adaptiveThreshold = $defaultFlatThreshold;
        } else {
            sort($ratios);
            // Вычисляем 25-й процентиль
            $nRatios = count($ratios);
            $index = ($nRatios - 1) * 0.25;
            $lower = floor($index);
            $upper = ceil($index);
            if ($lower == $upper) {
                $adaptiveThreshold = $ratios[$lower];
            } else {
                $fraction = $index - $lower;
                $adaptiveThreshold = $ratios[$lower] + $fraction * ($ratios[$upper] - $ratios[$lower]);
            }
        }
        // --- Конец вычисления адаптивного flatThreshold ---

        // Теперь определяем, находится ли рынок во флёте, используя среднее относительное ATR за последние N свечей
        $N = 5; // количество свечей для усреднения
        for ($i = 0; $i < $count; $i++) {
            $currentClose = $candles[$i]['c'] ?? null;
            if ($atr[$i] === null || $currentClose === null) {
                $isFlat = false;
            } else {
                if ($i < $N - 1) {
                    $avgRelativeATR = $atr[$i] / $currentClose;
                } else {
                    $sumRelativeATR = 0;
                    for ($j = $i - $N + 1; $j <= $i; $j++) {
                        $sumRelativeATR += $atr[$j] / $candles[$j]['c'];
                    }
                    $avgRelativeATR = $sumRelativeATR / $N;
                }
                // Если среднее относительное ATR меньше адаптивного порога, рынок считается флэтовым
                $isFlat = $avgRelativeATR < $adaptiveThreshold;
            }

            // Для вычисления уровней TP используем предыдущую свечу, если она есть
            $prevCloseForTP = ($i > 0) ? $candles[$i - 1]['c'] : null;
            $prevAtr = ($i > 0) ? $atr[$i - 1] : null;

            $res[] = [
                'atr' => $atr[$i],
                'longTP' => ($prevCloseForTP !== null && $prevAtr !== null) ? $prevCloseForTP + ($prevAtr * $multiplier) : null,
                'shortTP' => ($prevCloseForTP !== null && $prevAtr !== null) ? $prevCloseForTP - ($prevAtr * $multiplier) : null,
                'isFlat' => $isFlat,
                'adaptiveFlatThreshold' => $adaptiveThreshold, // можно добавить для отладки
            ];
        }

        return $res;
    }

    // $flatThreshold :
    public static function calculateVolumeMA(
        $candles,
        $maLength = 10,
        $smaLength = 3,
        $flatLength = 85,
        $flatThreshold = 55,
        $lookback = 6
    ) {
        $volumeMA = [];    // Массив для хранения MA объема
        $smoothedMA = [];  // Массив для хранения сглаженной MA
        $result = [];      // Итоговый массив с данными

        // Рассчитываем MA объема для каждой свечи
        foreach ($candles as $index => $candle) {
            if ($index >= $maLength - 1) {
                // Берем объемы последних $maLength свечей
                $volumes = array_column(array_slice($candles, $index - $maLength + 1, $maLength), 'v');
                $volumeMA[$index] = array_sum($volumes) / $maLength; // Рассчитываем MA
            } else {
                $volumeMA[$index] = null;
            }
        }

        // Сглаживаем MA с помощью SMA
        foreach ($volumeMA as $index => $ma) {
            if ($index >= $maLength + $smaLength - 2) {
                // Берем последние $smaLength значений MA
                $maValues = array_slice($volumeMA, $index - $smaLength + 1, $smaLength);
                $smoothedMA[$index] = array_sum($maValues) / $smaLength; // Рассчитываем SMA
            } else {
                $smoothedMA[$index] = null;
            }
        }

        $isUptrend = false;
        $changePercent = 0;
        $flatFlags = [];

        // Определяем тренд и проверяем на флэт для каждой свечи
        foreach ($smoothedMA as $index => $sma) {
            // Определяем тренд на основе сравнения со значением на n свечи назад
            if ($index > $maLength + $smaLength - 2) {
                $maxChangePercent = 0;
                $start = max(0, $index - 4);
                // Проходим по всем парам от $start до текущей свечи (то есть, по индексам $i и $i+1)
                for ($i = $start; $i < $index; $i++) {
                    if (isset($smoothedMA[$i], $smoothedMA[$i + 1]) && $smoothedMA[$i] != 0) {
                        // Вычисляем процентное изменение от свечи с индексом $i к следующей
                        $currentChange = round((($smoothedMA[$i + 1] - $smoothedMA[$i]) / $smoothedMA[$i]) * 100, 2);
                        // Если свеча выросла (т.е. изменение положительное) и это изменение больше найденного ранее, обновляем значение
                        if ($currentChange > $maxChangePercent) {
                            $maxChangePercent = $currentChange;
                        }
                    }
                }
                $changePercent = $maxChangePercent;

                $prevPrevSMA = $smoothedMA[$index - 2];
                $prevSMA = $smoothedMA[$index - 1];

                if ($prevSMA > $prevPrevSMA) {
                    $trend = 'up';   // Тренд вверх
                    $isUptrend = true;
                } elseif ($prevSMA < $prevPrevSMA) {
                    $trend = 'down'; // Тренд вниз
                    $isUptrend = false;
                } else {
                    $isUptrend = false;
                    $trend = 'sideways'; // Без изменений
                }
            } else {
                $trend = null; // Для первых свечей тренд не определяем
            }

            // --- Проверка на флэт через стандартное отклонение объёма ---
            // Для определения флэта рассматриваем окно из последних $maLength значений объёма (volumeMA)
            $isFlat = false;
            //$flatLength = 36;
            if ($index >= $flatLength - 1) { // убеждаемся, что достаточно данных для окна
                $volumeWindow = array_slice($volumeMA, $index - $flatLength + 1, $flatLength);
                // Если в окне нет невычисленных значений
                if (!in_array(null, $volumeWindow, true)) {
                    // Вычисляем среднее значение объёма в окне
                    $meanVolume = array_sum($volumeWindow) / count($volumeWindow);
                    // Вычисляем дисперсию
                    $variance = 0;
                    foreach ($volumeWindow as $vol) {
                        $variance += pow($vol - $meanVolume, 2);
                    }
                    $variance /= count($volumeWindow);
                    // Стандартное отклонение
                    $stdDev = sqrt($variance);
                    $relativeStdDev = ($stdDev / $meanVolume) * 100;

                    // Если стандартное отклонение меньше порогового значения – считаем, что объём флэтовый.
                    if ($relativeStdDev < $flatThreshold) {
                        $isFlat = true;
                    }
                }
            }
            // -----------------------------------------------------------

            // Сохраняем результат флет-проверки для текущей свечи
            $flatFlags[$index] = $isFlat;

            // --- Дополнительный анализ: вычисление flatDistance ---
            // Если текущая свеча flat, flatDistance = 0.
            // Если не flat, то ищем среди предыдущих 10 свечей ближайшую, где isFlat == true.
            //$lookback = 8;
            $flatDistance = false;
            if ($isFlat) {
                $flatDistance = 0;
            } else {
                $flatDistance = false; // по умолчанию — flat-свеча не найдена
                // Идем по предыдущим свечам в обратном порядке (от текущей - 1 до текущей - $lookback)
                for ($j = $index - 1; $j >= max(0, $index - $lookback); $j--) {
                    if (isset($flatFlags[$j]) && $flatFlags[$j] === true) {
                        $flatDistance = $index - $j;
                        break;
                    }
                }
            }
            // -----------------------------------------------------------

            //timestamp
            $milliseconds = $candles[$index]['t'];
            $seconds = $milliseconds / 1000;
            $microseconds = ($milliseconds % 1000) * 1000;

            $date = \DateTime::createFromFormat('U.u', sprintf('%.6F', $seconds));
            $date->modify("+$microseconds microseconds");
            $timestamp =  $date->format("H:i m.d");

            // Добавляем данные в итоговый массив
            $result[] = [
                'candle'      => $candles[$index],
                'timestamp'      => $timestamp,
                'volume_ma'   => $volumeMA[$index],
                'smoothed_ma' => $smoothedMA[$index],
                'trend'       => $trend,
                'isUptrend'   => $isUptrend,
                'isFlat'      => $isFlat, // Признак флэта
                'flatDistance' => $flatDistance,
                'relativeStdDev' => $relativeStdDev,
                'changePercent'  => $changePercent,
            ];
        }

        return $result;
    }

    public static function detectFlat(
        array $candles,
        int   $flatLength = 100,
        float $flatThreshold = 2.1,
        int   $lookback = 6
    ): array
    {
        $results = [];
        $flatFlags = []; // Будем хранить флаг "isFlat" для каждой свечи

        $count = count($candles);
        for ($i = 0; $i < $count; $i++) {
            $stdDev = null;
            $relativeStdDev = null;
            $isFlat = false;

            // Проверяем, что достаточно данных для окна в $flatLength свечей
            if ($i >= $flatLength - 1) {
                // Берём срез последних $flatLength свечей (по индексам)
                $slice = array_slice($candles, $i - $flatLength + 1, $flatLength);

                // Извлекаем нужную метрику - например, цену закрытия (Close).
                // Если хотите считать по объёму, замените 'c' на 'v'.
                $values = array_column($slice, 'c');

                // Считаем среднее
                $mean = array_sum($values) / count($values);

                // Вычисляем дисперсию
                $variance = 0;
                foreach ($values as $val) {
                    $variance += pow($val - $mean, 2);
                }
                $variance /= count($values);

                // Стандартное отклонение
                $stdDev = sqrt($variance);

                // Относительное отклонение (в %)
                if ($mean != 0) {
                    $relativeStdDev = ($stdDev / $mean) * 100;
                } else {
                    $relativeStdDev = 0;
                }

                // Если относительное отклонение меньше порогового значения – считаем, что рынок флэтовый.
                if ($relativeStdDev < $flatThreshold) {
                    $isFlat = true;
                }
            }

            // Сохраняем флаг, чтобы потом искать flatDistance
            $flatFlags[$i] = $isFlat;

            // Вычисляем flatDistance
            // Если текущая свеча flat, flatDistance = 0.
            // Если не flat, смотрим в предыдущих $lookback свечах.
            $flatDistance = false;
            if ($isFlat) {
                $flatDistance = 0;
            } else {
                // Идём в обратном порядке (от текущей - 1 до текущей - $lookback)
                for ($j = $i - 1; $j >= max(0, $i - $lookback); $j--) {
                    if ($flatFlags[$j] === true) {
                        $flatDistance = $i - $j;
                        break;
                    }
                }
            }

            //timestamp
            $milliseconds = $candles[$i]['t'];
            $seconds = $milliseconds / 1000;
            $microseconds = ($milliseconds % 1000) * 1000;
            $date = \DateTime::createFromFormat('U.u', sprintf('%.6F', $seconds));
            $date->modify("+$microseconds microseconds");
            $timestamp =  $date->format("H:i m.d");

            $results[] = [
                'candle' => $candles[$i],
                'timestamp' => $timestamp,
                'stdDev' => $stdDev,
                'relativeStdDev' => $relativeStdDev,
                'isFlat' => $isFlat,
                'flatDistance' => $flatDistance,
            ];
        }

        return $results;
    }

    //volume delta (cvd)
    public static function calculateDelta(array $intervals): array {
        $result = [];
        $cumulative = 0;
        $intervals = array_reverse($intervals);

        foreach ($intervals as $interval) {
            $buyVolume = isset($interval['buyVolume']) ? floatval($interval['buyVolume']) : 0;
            $sellVolume = isset($interval['sellVolume']) ? floatval($interval['sellVolume']) : 0;
            $delta = $buyVolume - $sellVolume;
            $cumulative += $delta;
            $interval['delta'] = $delta;
            $interval['cvd'] = $cumulative;
            $result[] = $interval;
        }

        $result = array_reverse($result);
        return $result;
    }

    public static function analyzeVolumeSignal(array $volumes, int $n = 3, float $volumeGrowthThreshold = 1.5, float $dominanceThreshold = 0.7): array {
        $totalIntervals = count($volumes);

        $isLong = $isShort = false;
        // Если данных недостаточно для анализа (нужно как минимум 2*n интервалов)
        if ($totalIntervals < 2 * $n) {
            return [
                'signal' => 'neutral',
                'reason' => 'Not enough data',
                'isLong' => $isLong,
                'isShort' => $isShort
            ];
        }

        // Последние N интервалов (свежие данные)
        $recent = array_slice($volumes, -$n, $n);
        // Предыдущие N интервалов
        $past = array_slice($volumes, -2 * $n, $n);

        // Среднее суммарное значение за последние N интервалов
        $sumRecent = 0;
        foreach ($recent as $interval) {
            $sumRecent += $interval['sumVolume'];
        }
        $avgRecent = $sumRecent / $n;

        // Среднее суммарное значение за предыдущие N интервалов
        $sumPast = 0;
        foreach ($past as $interval) {
            $sumPast += $interval['sumVolume'];
        }
        $avgPast = $sumPast / $n;

        // Если прошлый средний объем равен нулю (не должно быть, но на всякий случай)
        if ($avgPast == 0) {
            return [
                'signal' => 'neutral',
                'reason' => 'avgPast is zero',
                'avgRecent' => $avgRecent,
                'avgPast' => $avgPast,
                'isLong' => $isLong,
                'isShort' => $isShort
            ];
        }

        $growth = round($avgRecent / $avgPast, 3);

        // Если рост объема недостаточный – сигнал нейтральный
        if ($growth < $volumeGrowthThreshold) {
            return [
                'signal' => 'neutral',
                'reason' => 'Insufficient volume growth',
                'avgRecent' => $avgRecent,
                'avgPast' => $avgPast,
                'growth' => $growth,
                'isLong' => $isLong,
                'isShort' => $isShort
            ];
        }

        // Суммируем объемы покупок и продаж за последние N интервалов
        $totalBuy = 0;
        $totalSell = 0;
        foreach ($recent as $interval) {
            $totalBuy += $interval['buyVolume'];
            $totalSell += $interval['sellVolume'];
        }
        $totalRecentVolume = $totalBuy + $totalSell;
        if ($totalRecentVolume == 0) {
            return [
                'signal' => 'neutral',
                'reason' => 'No recent volume',
                'totalBuy' => $totalBuy,
                'totalSell' => $totalSell,
                'isLong' => $isLong,
                'isShort' => $isShort
            ];
        }

        $buyRatio = round($totalBuy / $totalRecentVolume, 3);
        $sellRatio = round($totalSell / $totalRecentVolume, 3);

        $signal = "neutral";
        if ($buyRatio >= $dominanceThreshold) {
            $signal = "long";
            $isLong = true;
        } elseif ($sellRatio >= $dominanceThreshold) {
            $signal = "short";
            $isShort = true;
        }

        return [
            'isLong' => $isLong,
            'isShort' => $isShort,
            'signal' => $signal,
            'avgRecent' => $avgRecent,
            'avgPast' => $avgPast,
            'growth' => $growth,
            'totalBuy' => $totalBuy,
            'totalSell' => $totalSell,
            'buyRatio' => $buyRatio,
            'sellRatio' => $sellRatio,
            'volumeGrowthThreshold' => $volumeGrowthThreshold,
            'dominanceThreshold' => $dominanceThreshold
        ];
    }

    /**
     * Рассчитывает боковой объем (Volume Profile) по входному массиву свечей.
     *
     * @param array $candles Массив свечей, каждый элемент содержит:
     *                       't' => timestamp,
     *                       'o' => open,
     *                       'h' => high,
     *                       'l' => low,
     *                       'c' => close,
     *                       'v' => volume.
     * @param int $bins Количество ценовых бинов для разбиения диапазона (по умолчанию 50)
     * @return array Ассоциативный массив с рассчитанной информацией:
     *               - 'peakPrice' => средняя цена бина с максимальным объемом,
     *               - 'priceRange' => [binMin, binMax] для бина с максимальным объемом,
     *               - 'accumulatedVolume' => объем в этом бине,
     *               - 'totalVolume' => суммарный объем по всем свечам,
     *               - 'volumePercentage' => процент объема этого бина от общего объема,
     *               - 'bins' => массив объемов по каждому бину,
     *               - 'binSize' => размер одного ценового бина,
     *               - 'globalPriceRange' => [minPrice, maxPrice] по всем свечам.
     */
    public static function calculateSideVolumes(array $candles, int $bins = 50): array {
        // Определяем глобальный ценовой диапазон
        $minPrice = INF;
        $maxPrice = -INF;
        $totalVolume = 0;
        foreach ($candles as $candle) {
            $low = floatval($candle['l']);
            $high = floatval($candle['h']);
            $vol = floatval($candle['v']);
            if ($low < $minPrice) {
                $minPrice = $low;
            }
            if ($high > $maxPrice) {
                $maxPrice = $high;
            }
            $totalVolume += $vol;
        }

        // Вычисляем размер одного ценового бина
        $range = $maxPrice - $minPrice;
        if ($range <= 0) {
            $range = 1; // защитное значение
        }
        $binSize = $range / $bins;

        // Инициализируем массив для объемов по бинам
        $volumeBins = array_fill(0, $bins, 0);

        // Для каждой свечи добавляем объем в соответствующий бин.
        // Используем типичную цену: (high + low) / 2.
        foreach ($candles as $candle) {
            $typicalPrice = (floatval($candle['h']) + floatval($candle['l'])) / 2;
            $vol = floatval($candle['v']);
            $binIndex = (int)(($typicalPrice - $minPrice) / $binSize);
            if ($binIndex >= $bins) {
                $binIndex = $bins - 1;
            }
            $volumeBins[$binIndex] += $vol;
        }

        // Находим бин с максимальным объемом
        $maxBinVolume = max($volumeBins);
        $maxBinIndex = array_search($maxBinVolume, $volumeBins);
        $binMin = $minPrice + $maxBinIndex * $binSize;
        $binMax = $binMin + $binSize;
        $peakPrice = ($binMin + $binMax) / 2;
        $volumePercentage = ($totalVolume > 0) ? ($maxBinVolume / $totalVolume) * 100 : 0;

        return [
            'peakPrice' => $peakPrice,
            'priceRange' => [$binMin, $binMax],
            'accumulatedVolume' => $maxBinVolume,
            'totalVolume' => $totalVolume,
            'volumePercentage' => $volumePercentage,
            'bins' => $volumeBins,
            'binSize' => $binSize,
            'globalPriceRange' => [$minPrice, $maxPrice]
        ];
    }

    /**
     * Рассчитывает стоп‑лосс и 4 уровня тейк‑профитов для сделки, используя ATR и предыдущий экстремум.
     *
     * @param float $atr           Текущее значение ATR (рассчитанное по 15-минутным свечам)
     * @param float $entryPrice    Цена входа в сделку.
     * @param float $lastExtreme   Предыдущий экстремум (например, swing low для лонга или swing high для шорта)
     * @param string $direction    "long" или "short"
     * @param float $offsetPercent Отступ от экстремума в процентах (например, 0.5 означает 0.5%)
     * @param array $tpMultipliers Массив множителей для расчёта тейк‑профитов (по умолчанию [1,2,3,4])
     *
     * @return array Ассоциативный массив с ключами:
     *               'atr' => $atr,
     *               'stopLoss' => рассчитанный уровень стоп‑лосса,
     *               'risk' => абсолютное расстояние между entry и stopLoss,
     *               'takeProfits' => массив из 4 уровней тейк‑профита.
     */
    public static function calculateRiskTargetsATR(
        float $atr,
        float $entryPrice,
        float $lastExtreme,
        string $direction,
        float $offsetPercent = 0.5,
        array $tpMultipliers = [0.5, 1, 2, 3, 4]
    ): array {
        // Расчет базового стоп-лосса по экстремуму с отступом
        if (strtolower($direction) === "long") {
            $baseStopLoss = $lastExtreme * (1 - $offsetPercent / 100);
            $riskDistance = $entryPrice - $baseStopLoss;
            // Если риск меньше ATR, устанавливаем минимальный риск равный ATR
            if ($riskDistance < $atr) {
                $stopLoss = $entryPrice - $atr;
                $riskDistance = $atr;
            } else {
                $stopLoss = $baseStopLoss;
            }
        } elseif (strtolower($direction) === "short") {
            $baseStopLoss = $lastExtreme * (1 + $offsetPercent / 100);
            $riskDistance = $baseStopLoss - $entryPrice;
            if ($riskDistance < $atr) {
                $stopLoss = $entryPrice + $atr;
                $riskDistance = $atr;
            } else {
                $stopLoss = $baseStopLoss;
            }
        } else {
            return [];
            //throw new \InvalidArgumentException("Direction must be either 'long' or 'short'");
        }

        // Рассчитываем 4 уровня тейк-профитов на основе фиксированного риск-рэйта.
        $takeProfits = [];
        foreach ($tpMultipliers as $multiplier) {
            if (strtolower($direction) === "long") {
                $takeProfits[] = $entryPrice + $multiplier * $riskDistance;
            } else {
                $takeProfits[] = $entryPrice - $multiplier * $riskDistance;
            }
        }

        return [
            'atr' => $atr,
            'stopLoss' => $stopLoss,
            'risk' => $riskDistance,
            'takeProfits' => $takeProfits,
        ];
    }

    /**
     * Рассчитывает стоп‑лосс и 4 уровня тейк‑профита с использованием ATR.
     *
     * Входные параметры:
     * - $atr: текущее значение ATR (рассчитанное по 15-минутным свечам)
     * - $entryPrice: цена входа в сделку
     * - $lastExtreme: последний экстремум (swing low для long, swing high для short)
     * - $direction: "long" или "short"
     * - $offsetPercent: отступ от экстремума для стоп‑лосса в процентах (например, 0.5)
     * - $tpMultipliers: массив множителей для тейк‑профитов (по умолчанию [1, 2, 3, 4])
     *
     * @return array Ассоциативный массив с ключами:
     *               'atr' => $atr,
     *               'stopLoss' => рассчитанный уровень стоп‑лосса,
     *               'risk' => риск (расстояние между entry и stop‑лоссом),
     *               'takeProfits' => массив из n уровней тейк‑профита,
     *               'tpMultipliers' => использованные множители,
     *               'offsetPercent' => переданный отступ.
     */
    public static function calculateRiskTargetsWithATR(
        float $atr,
        float $entryPrice,
        float $lastExtreme,
        string $direction,
        int $scale = 6,
        float $offsetPercent = 0.5,
        ?array $tpMultipliers = null
    ): array {
        if ($tpMultipliers === null) {
            $tpMultipliers = [1.5, 3, 6, 9, 12];
        }

        $direction = strtolower($direction);
        if ($direction !== "long" && $direction !== "short") {
            return [];
            //throw new \InvalidArgumentException("Direction must be either 'long' or 'short'");
        }

        if ($direction === "long") {
            // Расчет базового стоп-лосса по экстремуму с отступом для long
            $baseStopLoss = $lastExtreme * (1 - $offsetPercent / 100);
            $risk = $entryPrice - $baseStopLoss;
            // Если риск меньше ATR, используем ATR
            if ($risk < $atr * 2) {
                $risk = $atr * 2;
                $stopLoss = $entryPrice - $atr * 2;
            } else {
                $stopLoss = $baseStopLoss;
            }
            // Тейк-профиты для long: entry + multiplier * ATR
            $takeProfits = [];
            foreach ($tpMultipliers as $multiplier) {
                $takeProfits[] = round(($entryPrice + $multiplier * $atr), $scale);
            }
        } else { // short
            // Расчет базового стоп-лосса для short: lastExtreme с отступом вверх
            $baseStopLoss = $lastExtreme * (1 + $offsetPercent / 100);
            $risk = $baseStopLoss - $entryPrice;
            if ($risk < $atr * 2) {
                $risk = $atr * 2;
                $stopLoss = $entryPrice + $atr * 2;
            } else {
                $stopLoss = $baseStopLoss;
            }
            // Тейк-профиты для short: entry - multiplier * ATR
            $takeProfits = [];
            foreach ($tpMultipliers as $multiplier) {
                $takeProfits[] = round(($entryPrice - $multiplier * $atr), $scale);
            }
        }

        // Расчет риска в процентах от стоп-лосса:
        // Абсолютное отклонение между точкой входа и стоп-лоссом делится на цену входа и умножается на 100
        $riskPercent = round((abs($entryPrice - $stopLoss) / $entryPrice) * 100, 2);

        return [
            'atr' => $atr,
            'stopLoss' => round($stopLoss, $scale),
            'risk' => $risk,
            'riskPercent' => $riskPercent,
            'takeProfits' => $takeProfits,
            'tpMultipliers' => $tpMultipliers,
            'offsetPercent' => $offsetPercent
        ];
    }

    /**
     * Рассчитывает стоп‑лосс и уровни тейк‑профита по фиксированным процентам.
     *
     * Для лонга:
     *   stopLoss = lastExtreme * (1 - offsetPercent/100)
     *   TP = entryPrice + (entryPrice * targetPercent/100)
     *
     * Для шорта:
     *   stopLoss = lastExtreme * (1 + offsetPercent/100)
     *   TP = entryPrice - (entryPrice * targetPercent/100)
     *
     * @param float $entryPrice    Цена входа.
     * @param float $lastExtreme   Последний экстремум (swing low для long, swing high для short).
     * @param string $direction    "long" или "short".
     * @param float $offsetPercent Процент отступа для стоп‑лосса (например, 0.5).
     * @param array|null $tpPercents Массив целевых процентов для тейк‑профитов (по умолчанию [1.5, 3, 6, 12, 24]).
     *
     * @return array Ассоциативный массив с ключами:
     *               'stopLoss' => уровень стоп‑лосса,
     *               'takeProfits' => массив уровней тейк‑профита,
     *               'tpPercents' => использованные процентные значения.
     */
    public static function calculateFixedRiskTargets(
        float $entryPrice,
        float $lastExtreme,
        string $direction,
        float $offsetPercent = 0.5,
        ?array $tpPercents = null
    ): array {
        if ($tpPercents === null) {
            $tpPercents = [1.5, 3, 6, 12, 24];
        }

        $direction = strtolower($direction);
        if ($direction !== "long" && $direction !== "short") {
            throw new \InvalidArgumentException("Direction must be 'long' or 'short'");
        }

        if ($direction === "long") {
            // Для лонга стоп-лосс рассчитывается как swing low с отступом вниз.
            $stopLoss = $lastExtreme * (1 - $offsetPercent / 100);
            // Тейк-профиты рассчитываются как entry + (entry * targetPercent/100)
            $takeProfits = [];
            foreach ($tpPercents as $percent) {
                $takeProfits[] = $entryPrice + ($entryPrice * $percent / 100);
            }
        } else { // short
            // Для шорта стоп-лосс рассчитывается как swing high с отступом вверх.
            $stopLoss = $lastExtreme * (1 + $offsetPercent / 100);
            // Тейк-профиты рассчитываются как entry - (entry * targetPercent/100)
            $takeProfits = [];
            foreach ($tpPercents as $percent) {
                $takeProfits[] = $entryPrice - ($entryPrice * $percent / 100);
            }
        }

        return [
            'stopLoss' => $stopLoss,
            'takeProfits' => $takeProfits,
            'tpPercents' => $tpPercents
        ];
    }

    /**
     * Анализирует последние свечи и определяет точку входа с учетом ATR.
     *
     * Для long (покупка):
     *   - swingExtreme: минимум (low) среди последних $lookback свечей (swing low).
     *   - recommendedEntry: swingLow + (atrMultiplier * ATR).
     *   - Нижняя граница overextension: swingLow - (overextensionThreshold * ATR).
     *     Если цена ниже этой границы, вход считается не оптимальным (слишком перепродан).
     *   - Если текущая цена находится между нижней границей и recommendedEntry,
     *     то точка входа считается хорошей.
     *
     * Для short (продажа):
     *   - swingExtreme: максимум (high) среди последних $lookback свечей (swing high).
     *   - recommendedEntry: swingHigh - (atrMultiplier * ATR).
     *   - Верхняя граница overextension: swingHigh + (overextensionThreshold * ATR).
     *     Если цена выше этой границы, вход считается не оптимальным (слишком перекуплен).
     *   - Если текущая цена находится между recommendedEntry и верхней границей,
     *     то точка входа считается хорошей.
     *
     * @param float  $atr                     Значение ATR.
     * @param array  $candles                 Массив свечей, каждая свеча должна содержать ключи 'h', 'l', 'c' и т.п.
     * @param string $direction               "long" или "short".
     * @param int    $lookback                Количество свечей для расчёта swing extreme (например, 5).
     * @param float  $overextensionThreshold  Порог для определения зоны overextension (например, 1.0).
     * @param float  $atrMultiplier           Множитель для расчёта рекомендуемой точки входа (например, 1.0).
     *
     * @return array Ассоциативный массив с рассчитанными значениями:
     *               - 'atr': переданное значение ATR.
     *               - 'currentPrice': цена закрытия последней свечи.
     *               - 'swingExtreme': swing low (для long) или swing high (для short).
     *               - 'recommendedEntry': оптимальная точка входа.
     *               - 'overextensionLevel': граница, определяющая зону overextension.
     *               - 'isEntryPointGood': true, если текущая цена находится в зоне оптимального входа.
     */
    public static function determineEntryPoint(
        float $atr,
        array $candles,
        string $direction = "long",
        int $lookback = 2,
        float $overextensionThreshold = 1.1,
        float $atrMultiplier = 0.3
    ): array {
        if (!$candles)
            return [];

        // Извлекаем последнюю свечу и получаем цену закрытия.
        $lastCandle = end($candles);
        $currentPrice = floatval($lastCandle['c']);

        $direction = strtolower($direction);
        if ($direction === "long") {
            // Для long: ищем swing low среди последних $lookback свечей.
            $recentCandles = array_slice($candles, -$lookback);
            $swingExtreme = min(array_map(function($c) {
                return floatval($c['l']);
            }, $recentCandles));

            // Рекомендуемая точка входа: swingLow + (atrMultiplier * ATR).
            $recommendedEntry = $swingExtreme + ($atrMultiplier * $atr);

            // Нижняя граница overextension: если цена слишком ниже swingLow.
            $overextensionLevel = $swingExtreme - ($overextensionThreshold * $atr);

            // Точка входа считается хорошей, если цена не ушла слишком далеко вниз,
            // то есть находится между нижней границей и рекомендуемой точкой входа.
            $isEntryPointGood = ($currentPrice >= $overextensionLevel && $currentPrice <= $recommendedEntry);
        } elseif ($direction === "short") {
            // Для short: ищем swing high среди последних $lookback свечей.
            $recentCandles = array_slice($candles, -$lookback);
            $swingExtreme = max(array_map(function($c) {
                return floatval($c['h']);
            }, $recentCandles));

            // Рекомендуемая точка входа: swingHigh - (atrMultiplier * ATR).
            $recommendedEntry = $swingExtreme - ($atrMultiplier * $atr);

            // Верхняя граница overextension: если цена слишком выше swingHigh.
            $overextensionLevel = $swingExtreme + ($overextensionThreshold * $atr);

            // Точка входа считается хорошей, если цена не ушла слишком далеко вверх,
            // то есть находится между рекомендуемой точкой входа и верхней границей.
            $isEntryPointGood = ($currentPrice >= $recommendedEntry && $currentPrice <= $overextensionLevel);
        } else {
            return [];
            //throw new \InvalidArgumentException("Direction must be 'long' or 'short'");
        }

        return [
            'atr' => $atr,
            'currentPrice' => $currentPrice,
            'swingExtreme' => $swingExtreme,
            'recommendedEntry' => $recommendedEntry,
            'overextensionLevel' => $overextensionLevel,
            'isEntryPointGood' => $isEntryPointGood,
        ];
    }

    /**
     * Анализирует стакан заявок и даёт рекомендацию по направлению входа.
     *
     * @param array $orderBook  Результат вызова Bybit API orderBookV5 (см. Bybit Docs)
     * @param float $depthPct  Глубина в процентах для фильтрации релевантных уровней (например, 0.005 = 0.5%)
     * @param float $thresholdRatio  Порог коэффициента дисбаланса для открытия позиции (по умолчанию 1.2)
     * @return array  [
     *   'sizeAsk' => float,     // общий объём асков
     *   'sizeBid' => float,     // общий объём бидов
     *   'nearAsks' => float,    // объём асков в пределах depthPct от цены
     *   'nearBids' => float,    // объём бидов в пределах depthPct от цены
     *   'imbalance' => float,   // простой ratio = nearBids / max(nearAsks,1)
     *   'imbalanceNorm' => float, // нормализованный дисбаланс
     *   'isLong' => bool,       // true — лонг допустим
     *   'isShort' => bool       // true — шорт допустим
     * ]
     */
    public static function analyzeOrderBook(
        array $orderBook,
        float $thresholdRatio = 1.2,
        float $depthPct = 0.02,
    ): array {
        // Инициализируем суммарные объёмы
        $sizeAsk = 0.0;
        $sizeBid = 0.0;
        // Объёмы в пределах depthPct от текущей цены
        $nearAsks = 0.0;
        $nearBids = 0.0;

        // Проверяем, что API вернул результат
        if (isset($orderBook['result']['a'], $orderBook['result']['b'])) {
            $currentPrice = $orderBook['result']['a'][0][0] ?? $orderBook['result']['b'][0][0];

            // Проходим по всем аскам (продажа)
            foreach ($orderBook['result']['a'] as [$price, $qty]) {
                $price = (float)$price;
                $qty   = (float)$qty;
                // Суммарный объём
                $sizeAsk += $price * $qty;
                // Фокус на близкой ликвидности (<= +depthPct от цены) :contentReference[oaicite:1]{index=1}
                if ($price <= $currentPrice * (1 + $depthPct)) {
                    $nearAsks += $price * $qty;
                }
            }
            // Проходим по всем бидам (покупка)
            foreach ($orderBook['result']['b'] as [$price, $qty]) {
                $price = (float)$price;
                $qty   = (float)$qty;
                $sizeBid += $price * $qty;
                // Фокус на близкой ликвидности (>= –depthPct от цены) :contentReference[oaicite:2]{index=2}
                if ($price >= $currentPrice * (1 - $depthPct)) {
                    $nearBids += $price * $qty;
                }
            }
        }

        // Простой коэффициент дисбаланса (bid/ask) :contentReference[oaicite:3]{index=3}
        $imbalanceLong = $nearAsks > 0
            ? $nearBids / $nearAsks
            : 0;

        // Нормализованная версия: (B-A)/(B+A) :contentReference[oaicite:4]{index=4}
        $sum = $nearBids + $nearAsks;
        $imbalanceNorm = $sum > 0
            ? ($nearBids - $nearAsks) / $sum
            : 0.0;

        // Решение по лонгу: допускаем, если объёмы спроса существенно превышают предложение
        $isLong  = $imbalanceLong >= $thresholdRatio;  // порог можно оптимизировать :contentReference[oaicite:5]{index=5}

        // Аналогично для шорта: считаем обратный коэффициент
        $imbalanceShort = $nearAsks > 0
            ? $nearAsks / ($nearBids ?: 1)
            : 0;
        $isShort = $imbalanceShort >= $thresholdRatio;

        return [
            'sizeAsk'        => $sizeAsk,
            'sizeBid'        => $sizeBid,
            'nearAsks'       => $nearAsks,
            'nearBids'       => $nearBids,
            'imbalanceLong'      => $imbalanceLong,
            'imbalanceShort'      => $imbalanceShort,
            'imbalanceNorm'  => $imbalanceNorm,
            'isLong'         => $isLong,
            'isShort'        => $isShort,
        ];
    }

    /**
     * Рассчитывает ADX и связанные параметры для заданных свечей.
     *
     * @param array<int, array{h: float, l: float, c: float}> $candles   Массив свечей с ключами 'h','l','c'.
     * @param int $period   Период сглаживания (Wilder), по умолчанию 14.
     * @return array<int, array<string, float|string>>  Массив, где ключ — индекс свечи, а значение — массив параметров:
     *      - diPlus: float,  DI+
     *      - diMinus: float, DI-
     *      - dx: float,     DX
     *      - adx: float,    ADX
     *      - adxDirection: string ('up','down','flat')
     *      - trendDirection: string ('up','down','flat') (DI-пересечение)
     */
    public static function calculateADX(array $candles, int $period = 14): array {
        $n = count($candles);
        if ($n <= $period * 2) {
            return [];
        }

        // Подготовка массивов
        $timastamps = array_column($candles, 't');
        $highs = array_column($candles, 'h');
        $lows  = array_column($candles, 'l');
        $closes= array_column($candles, 'c');

        // Инициализация TR, +DM, -DM
        $tr = $plusDM = $minusDM = array_fill(0, $n, 0.0);
        for ($i = 1; $i < $n; $i++) {
            $tr[$i] = max(
                $highs[$i] - $lows[$i],
                abs($highs[$i] - $closes[$i-1]),
                abs($lows[$i]  - $closes[$i-1])
            );

            $deltaUp   = $highs[$i] - $highs[$i-1];
            $deltaDown = $lows[$i-1]  - $lows[$i];
            $plusDM[$i]  = ($deltaUp > $deltaDown && $deltaUp > 0) ? $deltaUp : 0;
            $minusDM[$i] = ($deltaDown > $deltaUp && $deltaDown > 0) ? $deltaDown : 0;
        }

        // Первичные суммы для Wilder
        $sumTR = array_sum(array_slice($tr, 1, $period));
        $sumP  = array_sum(array_slice($plusDM, 1, $period));
        $sumM  = array_sum(array_slice($minusDM,1, $period));

        // Сглаженные значения
        $smTR = [$period => $sumTR];
        $smP  = [$period => $sumP];
        $smM  = [$period => $sumM];
        for ($i = $period + 1; $i < $n; $i++) {
            $smTR[$i] = $smTR[$i-1] - ($smTR[$i-1] / $period) + $tr[$i];
            $smP[$i]  = $smP[$i-1]  - ($smP[$i-1]  / $period) + $plusDM[$i];
            $smM[$i]  = $smM[$i-1]  - ($smM[$i-1]  / $period) + $minusDM[$i];
        }

        // DI и DX
        $diPlus  = [];
        $diMinus = [];
        $dx      = [];
        for ($i = $period; $i < $n; $i++) {
            $diPlus[$i]  = 100 * ($smP[$i]  / $smTR[$i]);
            $diMinus[$i] = 100 * ($smM[$i]  / $smTR[$i]);
            $sumDI = $diPlus[$i] + $diMinus[$i];
            $dx[$i] = ($sumDI == 0) ? 0 : (100 * abs($diPlus[$i] - $diMinus[$i]) / $sumDI);
        }

        // ADX
        $adx = [];
        $firstDXs = array_slice($dx, $period, $period);
        $adxFirst = array_sum($firstDXs) / $period;
        $adx[$period * 2 - 1] = $adxFirst;
        for ($i = $period * 2; $i < $n; $i++) {
            $adx[$i] = (($adx[$i-1] * ($period - 1)) + $dx[$i]) / $period;
        }

        // Сборка результата
        $result = [];
        for ($i = $period * 2 - 1; $i < $n; $i++) {
            // направление ADX

            $isUpDir = false;
            $isDownDir = false;
            $prevAdx = $adx[$i-1] ?? $adx[$i];
            if ($adx[$i] > $prevAdx) {
                $isUpDir = true;
                $adxDir = 'up';
            } elseif ($adx[$i] < $prevAdx) {
                $isDownDir = true;
                $adxDir = 'down';
            } else {
                $adxDir = 'flat';
            }

            $isUpTrend = false;
            $isDownTrend = false;
            // направление тренда по DI
            if ($diPlus[$i] > $diMinus[$i]) {
                $isUpTrend = true;
                $trendDir = 'up';
            } elseif ($diMinus[$i] > $diPlus[$i]) {
                $isDownTrend = true;
                $trendDir = 'down';
            } else {
                $trendDir = 'flat';
            }

            //timestamp
            $milliseconds = $timastamps[$i];
            $seconds = $milliseconds / 1000;
            $timestamp = date("H:i d.m", $seconds);

            $result[$i] = [
                $timestamp        => $timestamp,
                'diPlus'        => $diPlus[$i],
                'diMinus'       => $diMinus[$i],
                'dx'            => $dx[$i],
                'adx'           => $adx[$i],
                'adxDirection'  => [
                    'adxDir' => $adxDir,
                    'isUpDir' => $isUpDir,
                    'isDownDir' => $isDownDir
                ],
                'trendDirection' => [
                    'trendDir' => $trendDir,
                    'isUpTrend' => $isUpTrend,
                    'isDownTrend' => $isDownTrend,
                ],
            ];
        }

        return $result;
    }

    /**
     * https://www.tradingview.com/script/OxJJqZiN-Pivot-Points-High-Low-Missed-Reversal-Levels-LuxAlgo/
     * Анализ пивотов и “пропущенных” разворотов по массиву свечей.
     *
     * @param array $candles Массив свечей с полями ['t','o','h','l','c','v'].
     * @param int   $len     «Pivot Length» (количество баров слева и справа для пивота).
     * @return array Массив того же размера, где для каждой свечи индекс i есть:
     *   - 'time'             => исходный миллисекундный timestamp,
     *   - 'time_str'         => "YYYY-MM-DD HH:MM:SS",
     *   - 'formatted_time'   => "HH:MM",
     *   - 'is_pivot'         => bool, является ли эта свеча пивотом,
     *   - 'pivot_type'       => 'high'|'low'|null,
     *   - 'distance'         => кол-во баров от последнего пивота до i,
     *   - 'trend_dir'        => 'up'|'down'|null  (после пивота),
     *   - 'is_missed'        => bool, признак пропущенного разворота,
     *   - 'last_pivot_price' => цена последнего пивота (high или low),
     *   - 'last_pivot_idx'   => индекс последнего пивота
     */
    /**
     * Анализ пивотов и «пропущенных» разворотов (упрощённая, но надёжная версия).
     *
     * @param array $candles Массив свечей с полями ['t','o','h','l','c','v'].
     * @param int   $len     Pivot Length (кол‑во баров слева и справа для определения пивота).
     * @return array Массив того же размера, где для каждой свечи i:
     *   - time              : исходный миллисекундный timestamp
     *   - time_str          : «YYYY-MM-DD HH:MM:SS»
     *   - formatted_time    : «HH:MM»
     *   - is_pivot          : bool, является ли текущая свеча пивотом
     *   - pivot_type        : 'high'|'low'|null
     *   - is_missed         : bool, признак «пропущенного» разворота (повторная смена того же типа)
     *   - missed_price      : float|null, цена предыдущего пивота (уровень поддержки/сопротивления)
     *   - last_pivot_idx    : int|null, индекс последней свечи‑пивота
     *   - last_pivot_price  : float|null, цена последнего пивота
     *   - trend_dir         : 'up'|'down'|null, направление текущего тренда после пивота
     *   - distance          : int|null, бары с момента последнего пивота (0 на самом пивоте)
     */
    public static function analyzePivotsSimple(array $candles, int $length = 50): array {
        $n = count($candles);
        // Результат
        $res = array_fill(0, $n, [
            'time'=>null,
            'pivotHigh'=>false,
            'pivotLow'=>false,
            'missedHigh'=>false,
            'missedLow'=>false,
            'trend'=>null,
            'distance'=>null,
            'levelPrice'=>null,
        ]);

        // Распакуем для скорости
        $highs = array_column($candles, 'h');
        $lows  = array_column($candles, 'l');
        $times = array_column($candles, 't');

        // 1) Предвычислим pivotHigh/pivotLow по окну length
        $pivotHigh = array_fill(0, $n, false);
        $pivotLow  = array_fill(0, $n, false);
        for ($i = $length; $i < $n - $length; $i++) {
            $h = $highs[$i];
            $l = $lows[$i];
            $isH = true; $isL = true;
            for ($j = $i - $length; $j <= $i + $length; $j++) {
                if ($j === $i) continue;
                if ($highs[$j] >= $h) $isH = false;
                if ($lows[$j]  <= $l) $isL = false;
                if (!($isH || $isL)) break;
            }
            if ($isH) $pivotHigh[$i] = true;
            if ($isL) $pivotLow[$i]  = true;
        }

        // 2) Проходим по свечам и расставляем pivots и missed
        $os = null;             // 1 = последний pivotHigh, 0 = последний pivotLow
        $lastIdx  = null;       // индекс последнего pivot (или missed)
        $lastPrice= null;       // цена последнего уровня
        for ($i = 0; $i < $n; $i++) {
            // время в HH:MM
            $t = $times[$i];
            $ts = is_numeric($t) && strlen((string)$t)>10 ? intval($t/1000) : intval($t);
            $res[$i]['time'] = date('H:i', $ts);

            // обычный pivotHigh
            if ($pivotHigh[$i]) {
                // если os был 1 — подряд два high → missedLow на предыдущем уровне
                if ($os === 1 && $lastIdx !== null) {
                    $res[$lastIdx]['missedLow'] = true;
                    // заодно обновляем lastIdx/lastPrice
                    $lastIdx = $lastIdx;
                    $lastPrice = $lows[$lastIdx];
                }
                // ставим pivotHigh здесь
                $res[$i]['pivotHigh'] = true;
                $os = 1;
                $lastIdx = $i;
                $lastPrice = $highs[$i];
            }

            // обычный pivotLow
            if ($pivotLow[$i]) {
                if ($os === 0 && $lastIdx !== null) {
                    $res[$lastIdx]['missedHigh'] = true;
                    $lastIdx = $lastIdx;
                    $lastPrice = $highs[$lastIdx];
                }
                $res[$i]['pivotLow'] = true;
                $os = 0;
                $lastIdx = $i;
                $lastPrice = $lows[$i];
            }

            // 3) Заполняем trend/distance/levelPrice
            if ($lastIdx !== null) {
                $res[$i]['trend'] = $os === 1 ? 'up' : 'down';
                $res[$i]['distance'] = $i - $lastIdx;
                $res[$i]['levelPrice'] = $lastPrice;
            }
        }

        return $res;
    }

    /**
     * Находит последние Pivot High и Pivot Low в массиве свечей.
     * Pivot — это максимум/минимум среди lookback свечей слева и справа.
     */
    public static function findLastPivots(array $candles, int $lookback = 8): array {
        $n = count($candles);
        $pivotHighC = $pivotHigh = null;
        $pivotLowC = $pivotLow = null;

        for ($i = $n - $lookback - 1; $i >= $lookback; $i--) {
            $isHigh = true;
            $isLow = true;
            $high = $candles[$i]['h'];
            $low = $candles[$i]['l'];

            for ($j = $i - $lookback; $j <= $i + $lookback; $j++) {
                if ($j === $i) continue;
                if ($candles[$j]['h'] >= $high) $isHigh = false;
                if ($candles[$j]['l'] <= $low) $isLow = false;
                if (!($isHigh || $isLow)) break;
            }

            if ($isHigh && $pivotHigh === null) {
                $pivotHighC = $candles[$i];
                $pivotHigh = $i;
            }
            if ($isLow && $pivotLow === null) {
                $pivotLowC = $candles[$i];
                $pivotLow = $i;
            }
            if ($pivotHigh !== null && $pivotLow !== null) break;
        }

        return [
            'highIndex' => $pivotHigh,
            'pivotHighC' => $pivotHighC,
            'lowIndex' => $pivotLow,
            'pivotLowC' => $pivotLowC,
        ];
    }

    /**
     * Строит уровни Фибоначчи по найденным pivot-точкам.
     */
    public static function buildFibonacciLevels(array $candles, int $lookback = 8): array {
        $ratios = [0, 0.382, 0.618, 1, 1.618, 2.618, 3.618];

        $pivots = self::findLastPivots($candles, $lookback);
        if ($pivots['highIndex'] === null || $pivots['lowIndex'] === null) {
            return [];
        }

        $hi = $candles[$pivots['highIndex']]['h'];
        $lo = $candles[$pivots['lowIndex']]['l'];

        // определяем направление — восходящий или нисходящий тренд
        $isUptrend = $pivots['lowIndex'] < $pivots['highIndex'];

        $levels = [];
        $diff = abs($hi - $lo);

        foreach ($ratios as $r) {
            if ($isUptrend) {
                $levels["{$r}"] = $lo + $diff * $r;
            } else {
                $levels["{$r}"] = $hi - $diff * $r;
            }
        }

        // сортируем от большего к меньшему (вниз по графику)
       // arsort($levels);

        $res = [
            'isUptrend' => $isUptrend,
            'levels' => $levels,
            'pivots' => $pivots,
        ];

        return $res;
    }

    /**
     * John Carter Squeeze — проверяем, что полосы Боллинджера находятся внутри Keltner Channel
     *
     * @param array $candles   Массив свечей с ключами ['h','l','c','t'].
     * @param int   $bbPeriod  Период для SMA и StdDev в Bollinger Bands (по умолчанию 20).
     * @param float $bbStdDev  Множитель StdDev (по умолчанию 2).
     * @param int   $kcPeriod  Период для EMA и ATR в Keltner Channel (по умолчанию 20).
     * @param float $kcMul     Множитель ATR для построения KC (по умолчанию 1.5).
     * @return bool
     */
    public static function isBBSqueezeKC(array $candles, int $bbPeriod = 20, float $bbStdDev = 2, int $kcPeriod = 20, float $kcMul = 1.5): bool
    {
        $count = count($candles);
        if ($count < max($bbPeriod, $kcPeriod) + 1) {
            // не хватает данных
            return false;
        }

        // 1) Боллинджер: SMA и StdDev по закрытиям
        $closes = array_column($candles, 'c');
        $sliceClosesBB = array_slice($closes, -$bbPeriod);
        $sma = array_sum($sliceClosesBB) / $bbPeriod;
        $std = self::stats_standard_deviation($sliceClosesBB);

        $bbUpper = $sma + $bbStdDev * $std;
        $bbLower = $sma - $bbStdDev * $std;

        // 2) Keltner: EMA и ATR
        // Предполагаем, что calculateEMA возвращает массив, где последний элемент — текущая EMA.
        $ema = self::calculateEMA($closes, $kcPeriod);

        // ATR возвращает массив True Range усреднённый SMMA или EMA, последний элемент — текущий ATR
        $atrArr = self::calculateATR($candles, $kcPeriod);
        $atr = end($atrArr);

        $kcUpper = $ema + $kcMul * $atr['atr'];
        $kcLower = $ema - $kcMul * $atr['atr'];

        // Squeeze, если BB-диапазон полностью внутри KC
        return ($bbLower > $kcLower) && ($bbUpper < $kcUpper);
    }

    /**
     * Вычисляет стандартное отклонение массива чисел.
     *
     * @param float[] $data   Массив числовых значений.
     * @param bool    $sample Если true — разделить на (n−1) (выборочное С.О.), иначе на n (генеральное С.О.).
     * @return float          Стандартное отклонение.
     */
    public static function stats_standard_deviation(array $data, bool $sample = false): float
    {
        $n = count($data);
        if ($n === 0) {
            return 0.0;
        }

        // 1) Считаем среднее
        $mean = array_sum($data) / $n;

        // 2) Сумма квадратов отклонений
        $sumSq = 0.0;
        foreach ($data as $x) {
            $diff = $x - $mean;
            $sumSq += $diff * $diff;
        }

        // 3) Делим на n или n−1
        $divisor = $sample && $n > 1 ? ($n - 1) : $n;

        return sqrt($sumSq / $divisor);
    }

    /**
     * Проверка свечного истощения в конце тренда.
     *
     * @param array  $candles     Последние свечи в формате [['o','h','l','c','v'], …]
     * @param string $direction   'long'  — ищем разворот снизу (конец медвежьего тренда);
     *                            'short' — ищем разворот сверху (конец бычьего тренда).
     * @param float  $tailRatio   Во сколько раз хвост должен превышать тело (по умолчанию 2.0).
     * @return bool
     */
    public static function isExhaustion(array $candles, string $direction, float $tailRatio = 0.1): bool
    {

        // 2) Анализируем последнюю свечу
        $last = end($candles);
        $body = abs($last['c'] - $last['o']);
        if ($body == 0) {
            return false;
        }

        if ($direction === 'long') {
            // нижний хвост
            $lowerWick = min($last['o'], $last['c']) - $last['l'];
            // хвост ≥ tailRatio × тело и есть закрытие обратно в тело
            return $lowerWick > $tailRatio * $body
                && $last['c'] > $last['l'] + $lowerWick * 0.5;
        } else {
            // short: верхний хвост
            $upperWick = $last['h'] - max($last['o'], $last['c']);
            return $upperWick > $tailRatio * $body
                && $last['c'] < $last['h'] - $upperWick * 0.5;
        }
    }

    /**
     * Проверка объёмного спайка: объём последней свечи против среднего.
     *
     * @param array $candles      Массив свечей с ключом 'v' для объёма.
     * @param int   $lookbackVol  Сколько прошлых объёмов брать в усреднение (по умолчанию 10).
     * @param float $multiplier   Во сколько раз текущий объём должен быть выше среднего (по умолчанию 1.5).
     * @return bool
     */
    public static function isVolumeSpike(array $candles, int $lookbackVol = 5, float $multiplier = 0.1): bool
    {
        $vols = array_column($candles, 'v');
        $n = count($vols);
        if ($n < $lookbackVol + 1) {
            return false;
        }
        // Средний объём по последним lookbackVol свечам (кроме текущей)
        $slice = array_slice($vols, -$lookbackVol - 1, $lookbackVol);
        $avg   = array_sum($slice) / $lookbackVol;
        $lastV = end($vols);

        return $lastV >= $avg * $multiplier;
    }


    /**
     * Рассчитывает MFI и его скользящие средние (fast и slow),
     * а также направление тренда MFI: вверх или вниз.
     *
     * @param array $candles   Массив свечей в хронологическом порядке (от старых к новым),
     *                         каждая свеча — ассоциативный массив с ключами:
     *                           't' => timestamp в миллисекундах,
     *                           'h' => High,
     *                           'l' => Low,
     *                           'c' => Close,
     *                           'v' => Volume.
     * @return array           Массив тех же размеров, что и входной, где каждый элемент —
     *                         [
     *                           'timestamp' => 'YYYY-MM-DD HH:MM:SS',
     *                           'mfi'       => float|null,
     *                           'fast_mfi'  => float|null,
     *                           'slow_mfi'  => float|null,
     *                           'isUpDir'   => bool,  // fast_mfi > slow_mfi
     *                           'isDownDir' => bool,  // fast_mfi < slow_mfi
     *                         ]
     */
    public static function calculateMFI($candles): array
    {
        if (!$candles)
            return [];

        // Жестко задаём периоды
        $mfiPeriod  = 14;
        $fastPeriod = 2;
        $slowPeriod = 5;

        $count     = count($candles);
        $typPrice  = array_fill(0, $count, 0.0);
        $moneyFlow = array_fill(0, $count, 0.0);

        // 1) Типичная цена и raw money flow
        foreach ($candles as $i => $c) {
            $tp = ($c['h'] + $c['l'] + $c['c']) / 3.0;
            $typPrice[$i]  = $tp;
            $moneyFlow[$i] = $tp * $c['v'];
        }

        // 2) Положительный/отрицательный MF
        $posMF = array_fill(0, $count, 0.0);
        $negMF = array_fill(0, $count, 0.0);
        for ($i = 1; $i < $count; $i++) {
            if ($typPrice[$i] > $typPrice[$i - 1]) {
                $posMF[$i] = $moneyFlow[$i];
            } else {
                $negMF[$i] = $moneyFlow[$i];
            }
        }

        // 3) Вычисляем MFI
        $mfiArr = array_fill(0, $count, null);
        for ($i = $mfiPeriod; $i < $count; $i++) {
            $sumPos = array_sum(array_slice($posMF, $i - $mfiPeriod + 1, $mfiPeriod));
            $sumNeg = array_sum(array_slice($negMF, $i - $mfiPeriod + 1, $mfiPeriod));
            if ($sumNeg === 0.0) {
                $mfiArr[$i] = 100.0;
            } else {
                $ratio      = $sumPos / $sumNeg;
                $mfiArr[$i] = 100.0 - (100.0 / (1.0 + $ratio));
            }
        }

        // 4) Вспомогательная SMA-функция
        $calcSMA = function(array $arr, int $period): array {
            $n   = count($arr);
            $out = array_fill(0, $n, null);
            for ($j = $period - 1; $j < $n; $j++) {
                $slice = array_slice($arr, $j - $period + 1, $period);
                $vals  = array_filter($slice, fn($v) => $v !== null);
                if (count($vals) === $period) {
                    $out[$j] = array_sum($vals) / $period;
                }
            }
            return $out;
        };

        // 5) Скользящие fast и slow
        $fastArr = $calcSMA($mfiArr, $fastPeriod);
        $slowArr = $calcSMA($mfiArr, $slowPeriod);

        // 6) Формируем результат
        $result = [];
        foreach ($candles as $i => $c) {
            $tsSec = (int) floor($c['t'] / 1000);
            $dt    = (new \DateTime())->setTimestamp($tsSec);
            $tsStr = $dt->format('Y-m-d H:i:s');

            $fast = $fastArr[$i];
            $slow = $slowArr[$i];

            $result[] = [
                'timestamp' => $tsStr,
                'mfi'       => $mfiArr[$i],
                'fast_mfi'  => $fast,
                'slow_mfi'  => $slow,
                'isUpDir'   => $fast !== null && $slow !== null && $fast > $slow,
                'isDownDir' => $fast !== null && $slow !== null && $fast < $slow,
            ];
        }

        return $result;
    }

}
