<?php
namespace Maksv;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class StrategyBuilder
{
    public function __construct(){}

    protected static function parsePair($pair) {
        // General case for pairs ending with common quote currencies
        if (preg_match('/^([A-Z0-9]+)(BTC|ETH|USDT|USDC|USDE|FDUSD|TUSD|TRY|EUR)$/', $pair, $matches))
        //if (preg_match('/^([A-Z0-9]+)(BTC|ETH|USDT|USDC|USDE)$/', $pair, $matches))
        {
            return [$matches[1], $matches[2]];
        }
        // Handle pairs with less common formats
        elseif (preg_match('/^([A-Z0-9]+)([A-Z0-9]+)$/', $pair, $matches))
        {
            return [$matches[1], $matches[2]];
        }
        else {
            throw new \Exception("Unknown pair format: $pair");
        }
    }

    protected static function fiilOpportunities($pair1, $price1, $pair2, $price2, $pair3, $price3, $profit,$profitPercent)
    {
        $opportunities = [
            'pair1' => $pair1,
            'price1' => $price1,
            'pair2' => $pair2,
            'price2' => $price2,
            'pair3' => $pair3,
            'price3' => $price3,
            'profit' => round($profit, 2),
            'profitPercent' => round($profitPercent, 3),
        ];

        return $opportunities;
    }

    public static function findArbitrageOpportunities($prices, $useProfitFilter = true, $writeActualSymbols = false, $marketCode = '')
    {
        $opportunities = [];
        $initial_amount = 100; // Начальная сумма в USDT

        $actualSymbolsAr = [];
        // Проходим по всем торговым парам
        foreach ($prices as $pair1 => $price1) {

            // Получаем базовую и котируемую монеты для пары 1
            list($base1, $quote1) = StrategyBuilder::parsePair($pair1);

            // Если котируемая монета пары 1 - USDT, начинаем анализировать арбитраж
            //if ($quote1 === 'USDT') {
            if (in_array($quote1, ['USDT', 'USDC', 'USDE','FDUSD','TUSD']) && !in_array($base1, ['EUR'])) {

                if (is_array($price1))
                    $price1 = $price1['sellPrice'];
                    //$price1 = $price1['buyPrice'];

                // Рассчитываем количество котируемой монеты, которую мы можем купить за начальную сумму
                $quote1Amount = $initial_amount / $price1; //99.8

                // Проходим по всем остальным торговым парам
                foreach ($prices as $pair2 => $price2) {
                    // Получаем базовую и котируемую монеты для пары 2
                    list($base2, $quote2) = StrategyBuilder::parsePair($pair2);

                    // Если базовая монета пары 2 совпадает с котируемой монетой первой пары, продолжаем анализ
                    if ($quote2 === $base1) {

                        if (is_array($price2))
                            $price2 = $price2['sellPrice'];//buyPrice sellPrice
                            //$price2 = $price2['buyPrice'];//buyPrice sellPrice
                            //$price2 = ($price2['sellPrice'] + $price2['buyPrice']) / 2;//buyPrice sellPrice

                        // Рассчитываем количество базовой монеты, которую мы можем получить за котируемую монету пары 1
                        //$price2 =  ($price2 / 100) * 100.5;
                        $base2Amount = $quote1Amount / $price2; // ≈0.02825

                        // Проходим по всем остальным торговым парам
                        foreach ($prices as $pair3 => $price3) {
                            // Получаем базовую и котируемую монеты для пары 3
                            list($base3, $quote3) = StrategyBuilder::parsePair($pair3);

                            // Если котируемая монета пары 2 совпадает с базовой монетой пары 3, продолжаем анализ
                            if ($base3 === $base2 && in_array($quote3, ['USDC', 'USDT', 'USDE','FDUSD','TUSD']) && $quote1 == $quote3) {

                                if (is_array($price3))
                                    $price3 = $price3['buyPrice'];
                                    //$price3 = $price3['sellPrice'];

                                // Рассчитываем профит
                                $finalAmount = $base2Amount * $price3;
                                $profit = $finalAmount - $initial_amount;
                                $profitPercent = ($profit / $initial_amount) * 100;

                                // Добавляем арбитражную возможность в результаты, если профит положительный
                                if ($useProfitFilter && $profit >= 0.1 /*&& $profit < 10*/)
                                {
                                    $opportunities[] = StrategyBuilder::fiilOpportunities($pair1, $price1, $pair2, $price2, $pair3, $price3, $profit, $profitPercent);
                                    if ($writeActualSymbols) {
                                        $actualSymbolsAr[] = $pair1;
                                        $actualSymbolsAr[] = $pair2;
                                        $actualSymbolsAr[] = $pair3;
                                    }
                                }
                                else if (!$useProfitFilter/* && $profit > -0.1*/)
                                {
                                    $opportunities[] = StrategyBuilder::fiilOpportunities($pair1, $price1, $pair2, $price2, $pair3, $price3, $profit, $profitPercent);
                                    if ($writeActualSymbols) {
                                        $actualSymbolsAr[] = $pair1;
                                        $actualSymbolsAr[] = $pair2;
                                        $actualSymbolsAr[] = $pair3;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($writeActualSymbols && $marketCode)
            StrategyBuilder::putActualSymbolsToJson($actualSymbolsAr, $marketCode);

        usort($opportunities, function ($a, $b) {
              return $b['profitPercent'] <=> $a['profitPercent'];
        });

        return $opportunities;
    }


    protected static function putActualSymbolsToJson($actualSymbolsAr, $marketCode)
    {
        $timeMark = date("d.m.y H:i:s");
        devlogs('start - ' . $timeMark, $marketCode . 'PutActualSymbolsToJson');

        $actualSymbolsAr = array_unique($actualSymbolsAr);

        /*$jsonDataMainExchange = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'Exchange/exchangeResponse.json'),true) ?? [];

        $putActualSymbolsAr = [];
        foreach ($actualSymbolsAr as $symbol)
        {
            $putActualSymbolsAr[] = [
                "name" => $symbol,
                'scale' => $jsonDataMainExchange['RESPONSE_EXCHENGE']['symbols'][$symbol]['scale']
            ];
        }*/

        $actualSymbolsAr = array_map(function($value) {
            return ["name" => $value];
        }, $actualSymbolsAr);

        $data = [
            "TIMEMARK" => $timeMark,
            //"SYMBOLS" => $putActualSymbolsAr,
            "SYMBOLS" => $actualSymbolsAr,
            "EXCHANGE_CODE" => $marketCode . '_actual_symbols'
        ];

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'Exchange/actualSymbols.json', json_encode($data));
    }


    public static function findPumpOrDumpOpportunities($actualSymbolsAr = [], $timeFrame = '4h', $marketCode = 'bybit', $thresholdPercent = 10)
    {
        $err = [];
        //$actualOpportunities= [];
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
            $anomalyThresholdOI = 10;
            if ($timeFrame == '30m') {
                $anomalyThresholdOI = 15;
            } else if ($timeFrame == '1h') {
                $anomalyThresholdOI = 15;
            } else if ($timeFrame == '4h') {
                $anomalyThresholdOI = 15;
            } else if ($timeFrame == '1d') {
                $anomalyThresholdOI = 15;
            }

            $actualSnap = $symbolVolumes ?? false;
            if ($actualSnap) {

                $buyChange = $actualSnap['tradesHistory']['buyChange'] ?? 0;
                $sellChange = $actualSnap['tradesHistory']['sellChange'] ?? 0;

                $crossMAVal = 0;
                if ($actualSnap['crossMA']['cross'] == 'bull cross')
                    $crossMAVal = 1;
                else if ($actualSnap['crossMA']['cross'] == 'bear cross')
                    $crossMAVal = 2;

                $actualSAR = false;
                if ($actualSnap['sarData'] && is_array($actualSnap['sarData']))
                    $actualSAR = $actualSnap['sarData'][array_key_last($actualSnap['sarData'])];

                $sarVal = 0;
                if ($actualSAR) {
                    if ($actualSAR['is_reversal'] && $actualSAR['trend'] == 'up')
                        $sarVal = 1;
                    else if ($actualSAR['is_reversal'] && $actualSAR['trend'] == 'down')
                        $sarVal = 2;
                }

                $opportunitieData = [
                    'symbolName' => $symbolName,
                    'lastRsi' => $actualSnap['rsi'],
                    'lastClosePrice' => $actualSnap['lastClosePrice'],
                    'lastOpenInterest' => $actualSnap['openInterest'],
                    'lastPriceChange' => $actualSnap['priceChange'],
                    'lastSAR' => $actualSAR,
                    'sarVal' => $sarVal,
                    'crossMA' => $actualSnap['crossMA']['cross'],
                    'crossMAOb' => $actualSnap['crossMA'],
                    'crossMAVal' => $crossMAVal,
                    'divergences' => $actualSnap['divergences'],
                    //'divergencesVal' => $divergencesVal,
                    'buyChangePercent' => round($buyChange, 2),
                    'sellChangePercent' => round($sellChange, 2),
                    'timeMark' => $timeMark,
                    'snapTimeMark' => $actualSnap['timeMark'],
                    'timeFrame' => $timeFrame,
                    'anomalyOI' => ($actualSnap['openInterest'] >= $anomalyThresholdOI),
                ];

                //$actualOpportunities['allCoin'][$symbolName] = $opportunitieData;

                if (in_array($symbolName, ['BTCUSDT', 'ETHUSDT']))
                    $actualOpportunities['headCoin'][$symbolName] = $opportunitieData;

               /* // Формируем данные для pump
                if (
                    (in_array($crossMAVal, [1]) || in_array($sarVal, [1]))
                    //&& (floatval($actualSnap['priceChange']) <= $thresholdPricePercent)
                ) {
                    $actualOpportunities['pump'][$symbolName] = $opportunitieData;
                }*/

                if (
                    (
                        in_array($timeFrame, ['4h', '1d'])
                        && (
                            ((in_array($crossMAVal, [1]) || in_array($sarVal, [1])) && (($actualSnap['priceChange'] > 0 && $actualSnap['openInterest'] > 0) || ($actualSnap['priceChange'] < 0 && $actualSnap['openInterest'] < 0)))
                            || ($actualSnap['priceChange'] > 0 && $actualSnap['openInterest'] >= $anomalyThresholdOI)
                        )
                    )
                    || (
                        in_array($timeFrame, ['30m', '1h'])
                        && (($actualSnap['priceChange'] > 0 && $actualSnap['openInterest'] >= 0) || ($actualSnap['priceChange'] < 0 && $actualSnap['openInterest'] <= -0))
                        && (in_array($crossMAVal, [1]) || (in_array($sarVal, [1]) && $actualSnap['lastClosePrice'] >= $actualSnap['crossMA']['sma']))
                    )
                ) {
                    $actualOpportunities['masterPump'][$symbolName] = $opportunitieData;
                    $actualOpportunities['pump'][$symbolName] = $opportunitieData;
                }

                if (
                    (in_array($crossMAVal, [1]) || in_array($sarVal, [1]))
                    || (($actualSnap['priceChange'] > 0 && $actualSnap['openInterest'] >= 25) || $actualSnap['priceChange'] < 0 && $actualSnap['openInterest'] <= -5)
                ) {
                    $actualOpportunities['allPump'][$symbolName] = $opportunitieData;
                }

               /* // Формируем данные для dump
                if (
                    (in_array($crossMAVal, [2]) || in_array($sarVal, [2]))
                    //&& (floatval($actualSnap['priceChange']) >= -$thresholdPricePercent)
                ) {
                    $actualOpportunities['dump'][$symbolName] = $opportunitieData;
                }*/

                if (
                    (
                        in_array($timeFrame, ['4h', '1d'])
                        && (
                            ((in_array($crossMAVal, [2]) || in_array($sarVal, [2])) && (($actualSnap['priceChange'] < 0 && $actualSnap['openInterest'] > 0) || ($actualSnap['priceChange'] > 0 && $actualSnap['openInterest'] < 0)))
                            || ($actualSnap['priceChange'] < 0 && $actualSnap['openInterest'] >= $anomalyThresholdOI)
                        )
                    )
                    || (
                        in_array($timeFrame, ['30m', '1h'])
                        && (in_array($crossMAVal, [2]) || (in_array($sarVal, [2])  && $actualSnap['lastClosePrice'] <= $actualSnap['crossMA']['sma']))
                        && (($actualSnap['priceChange'] < 0 && $actualSnap['openInterest'] >= $anomalyThresholdOI) || ($actualSnap['priceChange'] > 0 && $actualSnap['openInterest'] <= -$anomalyThresholdOI))
                    )
                ) {
                    $actualOpportunities['masterDump'][$symbolName] = $opportunitieData;
                    $actualOpportunities['dump'][$symbolName] = $opportunitieData;
                }

                if (
                    (in_array($crossMAVal, [2]) || in_array($sarVal, [2]))
                    || (($actualSnap['priceChange'] < 0 && $actualSnap['openInterest'] >= 25) || ($actualSnap['priceChange'] > 0 && $actualSnap['openInterest'] <= -5))
                ) {
                    $actualOpportunities['allDump'][$symbolName] = $opportunitieData;
                }

            }
        }

        // Функция для сортировки массива pump с сохранением ключей
        uasort($actualOpportunities['allPump'], function ($a, $b) {
            if ($a['buyChangePercent'] == $b['buyChangePercent']) {
                return $a['sellChangePercent'] <=> $b['sellChangePercent'];
            }
            return $b['buyChangePercent'] <=> $a['buyChangePercent'];
        });

        // Функция для сортировки массива dump с сохранением ключей
        uasort($actualOpportunities['allDump'], function ($a, $b) {
            if ($a['sellChangePercent'] == $b['sellChangePercent']) {
                return $a['buyChangePercent'] <=> $b['buyChangePercent'];
            }
            return $b['sellChangePercent'] <=> $a['sellChangePercent'];
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

     
        devlogs($timeFrame. ' count pump - ' . count($actualOpportunities['pump']), $marketCode . 'findPumpOrDumpOpportunities');
        devlogs($timeFrame. ' count dump - ' . count($actualOpportunities['dump']), $marketCode . 'findPumpOrDumpOpportunities');
        //devlogs('count Opportunities - ' . count($actualOpportunities), $marketCode . 'findPumpOrDumpOpportunities');

        if (!empty($err))
            devlogs($timeFrame. ' err - ' . implode('; ', $err), $marketCode . 'findPumpOrDumpOpportunities');

        devlogs($timeFrame. ' end - ' . $timeMark, $marketCode . 'findPumpOrDumpOpportunities');

        return $actualOpportunities;
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

    public static function checkMACross(array $prices, int $shortPeriod = 12, int $longPeriod = 26) {
        // Проверяем, что передано достаточно данных
        if (count($prices) < $longPeriod) {
            throw new \Exception("Недостаточно данных для расчета скользящих средних.");
        }
        // Берем последние $period+1 элементов, чтобы рассчитать первые изменения
        $prices = array_slice($prices, -($longPeriod + 1));

        // Вычисляем SMA
        $sma = array_sum(array_slice($prices, -$longPeriod)) / $longPeriod;

        // Вычисляем EMA
        $ema = self::calculateEMA($prices, $shortPeriod);

        // Проверяем пересечение
        $previousSMA = array_sum(array_slice($prices, -(($longPeriod + 1)), $longPeriod)) / $longPeriod;
        $previousEMA = self::calculateEMA(array_slice($prices, 0, -1), $shortPeriod);

        if ($previousEMA <= $previousSMA && $ema > $sma) {
            //return 'bull cross';  // Бычье пересечение (возможный восходящий тренд)
            return ['cross' => 'bull cross', 'sma' => $sma,  'ema' => $ema, ]; // Бычье пересечение (возможный восходящий тренд)
        } elseif ($previousEMA >= $previousSMA && $ema < $sma) {
            //return 'bear cross';  // Медвежье пересечение (возможный нисходящий тренд)
            return ['cross' => 'bear cross', 'sma' => $sma,  'ema' => $ema, ];  // Медвежье пересечение (возможный нисходящий тренд)
        } else {
            //return 'no cross';  // Пересечения нет
            return ['cross' => 'no cross', 'sma' => $sma,  'ema' => $ema, ];  // Пересечения нет
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

        return $sar;
    }

    public static function findLevels($orderBook, $topN = 3) {
        // Создаем массивы для хранения цен и объемов для bid (покупок) и ask (продаж)
        $bids = [];
        $asks = [];

        // Находим самую высокую цену покупок (максимальный bid)
        $maxBidPrice = (float) $orderBook['b'][0][0];  // Предполагаем, что массив отсортирован по убыванию цены
        // Находим самую низкую цену продаж (минимальный ask)
        $minAskPrice = (float) $orderBook['a'][0][0];  // Предполагаем, что массив отсортирован по возрастанию цены

        // Рассчитываем допустимые уровни для поиска
        $minAskPriceLimit = $minAskPrice * 1.003;  // Для тейк-профита (цена выше на 0.5%)
        $maxBidPriceLimit = $maxBidPrice * 0.997;  // Для стоп-лосса (цена ниже на 0.5%)

        // Проходим по массиву bid (покупки) и добавляем данные в массив
        foreach ($orderBook['b'] as $bid) {
            $price = (float) $bid[0];  // Цена
            $volume = (float) $bid[1]; // Объём
            // Добавляем только те уровни, которые ниже максимального bid на хотя бы 0.5%
            if ($price <= $maxBidPriceLimit) {
                $bids[] = ['price' => $price, 'volume' => $volume];
            }
        }

        // Проходим по массиву ask (продажи) и добавляем данные в массив
        foreach ($orderBook['a'] as $ask) {
            $price = (float) $ask[0];  // Цена
            $volume = (float) $ask[1]; // Объём
            // Добавляем только те уровни, которые выше минимального ask на хотя бы 0.5%
            if ($price >= $minAskPriceLimit) {
                $asks[] = ['price' => $price, 'volume' => $volume];
            }
        }

        // Сортируем массивы bid и ask по объему в порядке убывания
        usort($bids, function($a, $b) {
            return $b['volume'] <=> $a['volume'];
        });
        usort($asks, function($a, $b) {
            return $b['volume'] <=> $a['volume'];
        });

        // Возвращаем топ-N уровней для тейк-профита (asks) и стоп-лосса (bids)
        return [
            'upper' => array_slice($asks, 0, $topN), // Тейк-профит уровни (asks)
            'lower' => array_slice($bids, 0, $topN)  // Стоп-лосс уровни (bids)
        ];
    }

}
