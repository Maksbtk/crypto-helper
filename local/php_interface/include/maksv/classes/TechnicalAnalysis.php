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
        if (count($prices) < max($longPeriod, $bollingerLength)) {
            return false;
        }

        $prices = array_slice($prices, -max($longPeriod, $bollingerLength));

        $sma = array_sum(array_slice($prices, -$longPeriod)) / $longPeriod;
        $ema = self::calculateEMA($prices, $shortPeriod);
        $previousSMA = array_sum(array_slice($prices, -(($longPeriod + 1)), $longPeriod)) / $longPeriod;
        $previousEMA = self::calculateEMA(array_slice($prices, 0, -1), $shortPeriod);
        $currentPrice = end($prices);
        $isUptrend = $currentPrice > $sma;

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
                'bollinger' => [
                    'middle_band' => $bollingerSMA,
                    'upper_band' => $upperBand,
                    'lower_band' => $lowerBand,
                    '%B' => $bollingerPercentB,
                ],
            ];
        }
    }

/*    public static function checkMACross(array $prices, int $shortPeriod = 12, int $longPeriod = 26, int $bollingerLength = 20, float $bollingerFactor = 2)
    {
        // Проверяем, что передано достаточно данных
        if (count($prices) < max($longPeriod, $bollingerLength)) {
            //throw new \Exception("Недостаточно данных для расчета.");
            return false;
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
    }*/

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

    public static function calculateStochasticRSI(array $candles, int $rsiPeriod = 14, int $stochPeriod = 14, int $smoothK = 3, int $smoothD = 3): array
    {
        $closingPrices = array_column($candles, 'c');

        // Проверка на наличие достаточного количества данных
        $minRequired = $rsiPeriod + $stochPeriod + max($smoothK, $smoothD) - 1;
        if (count($candles) < $minRequired) {
            return [];
        }

        // 1. Расчёт RSI
        $rsi = [];
        $gains = [];
        $losses = [];
        for ($i = 1; $i < count($closingPrices); $i++) {
            $change = $closingPrices[$i] - $closingPrices[$i - 1];
            $gains[] = max($change, 0);
            $losses[] = max(-$change, 0);
        }

        $avgGain = array_sum(array_slice($gains, 0, $rsiPeriod)) / $rsiPeriod;
        $avgLoss = array_sum(array_slice($losses, 0, $rsiPeriod)) / $rsiPeriod;

        for ($i = $rsiPeriod; $i < count($gains); $i++) {
            $avgGain = (($avgGain * ($rsiPeriod - 1)) + $gains[$i]) / $rsiPeriod;
            $avgLoss = (($avgLoss * ($rsiPeriod - 1)) + $losses[$i]) / $rsiPeriod;

            $rs = $avgLoss == 0 ? 100 : $avgGain / $avgLoss;
            $rsi[] = $avgLoss == 0 ? 100 : 100 - (100 / (1 + $rs));
        }

        // 2. Расчёт Stochastic RSI
        $stochRSI = [];
        for ($i = $stochPeriod - 1; $i < count($rsi); $i++) {
            $minRSI = min(array_slice($rsi, $i - $stochPeriod + 1, $stochPeriod));
            $maxRSI = max(array_slice($rsi, $i - $stochPeriod + 1, $stochPeriod));
            $stochRSI[] = ($maxRSI - $minRSI == 0) ? 0 : ($rsi[$i] - $minRSI) / ($maxRSI - $minRSI) * 100;
        }

        // 3. Сглаживание %K
        $smoothedK = [];
        for ($i = $smoothK - 1; $i < count($stochRSI); $i++) {
            $smoothedK[] = array_sum(array_slice($stochRSI, $i - $smoothK + 1, $smoothK)) / $smoothK;
        }

        // 4. Сглаживание %D
        $smoothedD = [];
        for ($i = $smoothD - 1; $i < count($smoothedK); $i++) {
            $smoothedD[] = array_sum(array_slice($smoothedK, $i - $smoothD + 1, $smoothD)) / $smoothD;
        }

        // 5. Формирование результата
        $result = [];
        for ($i = max($rsiPeriod + $stochPeriod + $smoothK - 1, $rsiPeriod + $stochPeriod + $smoothD - 1); $i < count($candles); $i++) {
            $indexK = $i - ($rsiPeriod + $stochPeriod + $smoothK - 1);
            $indexD = $i - ($rsiPeriod + $stochPeriod + $smoothD - 1);

            $smoothedKVal = $smoothedK[$indexK] ?? null;
            $smoothedDVal = $smoothedD[$indexD - 2] ?? null;

            $previewSmoothedKVal = $smoothedK[$indexK - 1] ?? null;
            $previewSmoothedDVal = $smoothedD[$indexD - 3] ?? null;

            $isKDLong = isset($previewSmoothedKVal, $previewSmoothedDVal) && $previewSmoothedKVal > $previewSmoothedDVal && $smoothedKVal > $smoothedDVal && $smoothedKVal <= 20;
            $isKBorderLong = isset($previewSmoothedKVal) && $previewSmoothedKVal < 20 && $smoothedKVal >= 20;

            $isKDShort = isset($previewSmoothedKVal, $previewSmoothedDVal) && $previewSmoothedKVal < $previewSmoothedDVal && $smoothedKVal < $smoothedDVal && $smoothedKVal >= 80;
            $isKBorderShort = isset($previewSmoothedKVal) && $previewSmoothedKVal > 80 && $smoothedKVal <= 80;

            $result[] = [
                'close' => $candles[$i]['c'],
                '%K' => $smoothedKVal,
                '%D' => $smoothedDVal,
                'isLong' => $isKDLong || $isKBorderLong,
                'isShort' => $isKDShort || $isKBorderShort,
               /* 'isKDLong' => $isKDLong,
                'isKBorderLong' => $isKBorderLong,
                'isKDShort' => $isKDShort,
                'isKBorderShort' => $isKBorderShort,*/
            ];
        }

        return $result;
    }

    /*   public static function calculateStochasticRSI(array $candles, int $rsiPeriod = 14, int $stochPeriod = 14, int $smoothK = 3, int $smoothD = 3): array
        {
            $closingPrices = array_column($candles, 'c');

            // Проверка на наличие достаточного количества данных
            $minRequired = $rsiPeriod + $stochPeriod + max($smoothK, $smoothD) - 1;
            if (count($candles) < $minRequired) {
                //throw new \Exception("Недостаточно данных для расчёта Stochastic RSI.");
                false;
            }

            // 1. Расчёт RSI
            $rsi = [];
            $gains = [];
            $losses = [];
            for ($i = 1; $i < count($closingPrices); $i++) {
                $change = $closingPrices[$i] - $closingPrices[$i - 1];
                $gains[] = max($change, 0);
                $losses[] = max(-$change, 0);
            }

            $avgGain = array_sum(array_slice($gains, 0, $rsiPeriod)) / $rsiPeriod;
            $avgLoss = array_sum(array_slice($losses, 0, $rsiPeriod)) / $rsiPeriod;

            for ($i = $rsiPeriod; $i < count($gains); $i++) {
                $avgGain = (($avgGain * ($rsiPeriod - 1)) + $gains[$i]) / $rsiPeriod;
                $avgLoss = (($avgLoss * ($rsiPeriod - 1)) + $losses[$i]) / $rsiPeriod;

                $rs = $avgLoss == 0 ? 100 : $avgGain / $avgLoss;
                $rsi[] = $avgLoss == 0 ? 100 : 100 - (100 / (1 + $rs));
            }

            // 2. Расчёт Stochastic RSI
            $stochRSI = [];
            for ($i = $stochPeriod - 1; $i < count($rsi); $i++) {
                $minRSI = min(array_slice($rsi, $i - $stochPeriod + 1, $stochPeriod));
                $maxRSI = max(array_slice($rsi, $i - $stochPeriod + 1, $stochPeriod));
                $stochRSI[] = ($maxRSI - $minRSI == 0) ? 0 : ($rsi[$i] - $minRSI) / ($maxRSI - $minRSI) * 100;
            }

            // 3. Сглаживание %K
            $smoothedK = [];
            for ($i = $smoothK - 1; $i < count($stochRSI); $i++) {
                $smoothedK[] = array_sum(array_slice($stochRSI, $i - $smoothK + 1, $smoothK)) / $smoothK;
            }

            // 4. Сглаживание %D
            $smoothedD = [];
            for ($i = $smoothD - 1; $i < count($smoothedK); $i++) {
                $smoothedD[] = array_sum(array_slice($smoothedK, $i - $smoothD + 1, $smoothD)) / $smoothD;
            }

            // 5. Формирование результата
            $result = [];
            for ($i = max($rsiPeriod + $stochPeriod + $smoothK - 1, $rsiPeriod + $stochPeriod + $smoothD - 1); $i < count($candles); $i++) {
                $indexK = $i - ($rsiPeriod + $stochPeriod + $smoothK - 1);
                $indexD = $i - ($rsiPeriod + $stochPeriod + $smoothD - 1);

                $smoothedKVal = $smoothedK[$indexK] ?? null;
                $smoothedDVal = $smoothedD[$indexD-2] ?? null;

                $previewSmoothedKVal = $smoothedK[$indexK-1] ?? null;
                $previewSmoothedDVal = $smoothedD[$indexD-3] ?? null;

                $overboughtVal = 75;
                $oversoldVal = 25;

                $result[] = [
                    'close' => $candles[$i]['c'] ?? $candles[$i - 1]['c'], // Если цена закрытия отсутствует, используем предыдущую
                    '%K' => $smoothedKVal,
                    '%D' => $smoothedDVal,//$smoothedD[$indexD-2] ?? null,
                    'isOverbought' => isset($smoothedKVal) && $smoothedKVal > $overboughtVal,
                    'isOversold' => isset($smoothedKVal) && $smoothedKVal < $oversoldVal,
                    'isLong' => isset($smoothedKVal, $smoothedDVal) && $smoothedKVal < $oversoldVal && $smoothedKVal > $smoothedDVal,
                    'isShort' => isset($smoothedKVal, $smoothedDVal) && $smoothedKVal > $overboughtVal && $smoothedKVal < $smoothedDVal,
                ];
            }

            return $result;
        }*/

    /**
    30m	1.5% - 2%	5 - 10
    1h	2% - 3%	5 - 8
    4h	3% - 5%	4 - 6
    1d	4% - 6%	3 - 5
    */
    public static function detectOrderBlocks(array $candles, float $flatRangePercent = 2.5, int $minCandleCount = 5): array {
        $zones = [];
        $currentZone = null;

        for ($i = 0; $i < count($candles); $i++) {
            $candle = $candles[$i];
            $candleRange = $candle['h'] - $candle['l'];

            if (!$currentZone) {
                $currentZone = [
                    'start' => $i,
                    'end' => $i,
                    'low' => $candle['l'],
                    'high' => $candle['h'],
                    'volume' => $candle['v']
                ];
                continue;
            }

            // Фиксация флэта при малом изменении цены
            if (($candleRange / $currentZone['low']) * 100 <= $flatRangePercent) {
                $currentZone['end'] = $i;
                $currentZone['volume'] += $candle['v'];
                $currentZone['low'] = min($currentZone['low'], $candle['l']);
                $currentZone['high'] = max($currentZone['high'], $candle['h']);
            } else {
                if (($currentZone['end'] - $currentZone['start'] + 1) >= $minCandleCount) {
                    $zones[] = $currentZone;
                }
                $currentZone = null;
            }
        }

        if ($currentZone && ($currentZone['end'] - $currentZone['start'] + 1) >= $minCandleCount) {
            $zones[] = $currentZone;
        }

        return self::mergeAndSortZones($zones);
    }

    private static function mergeAndSortZones(array $zones): array {
        usort($zones, fn($a, $b) => $a['low'] <=> $b['low']);
        $mergedZones = [];
        $prevZone = null;

        foreach ($zones as $zone) {
            if (!$prevZone) {
                $prevZone = $zone;
                continue;
            }

            if ($zone['low'] <= $prevZone['high'] && ($zone['high'] - $prevZone['low']) <= 0.02 * $prevZone['low']) {
                $prevZone['high'] = max($prevZone['high'], $zone['high']);
                $prevZone['volume'] += $zone['volume'];
            } else {
                $mergedZones[] = $prevZone;
                $prevZone = $zone;
            }
        }

        if ($prevZone) {
            $mergedZones[] = $prevZone;
        }

        return $mergedZones;
    }



}
