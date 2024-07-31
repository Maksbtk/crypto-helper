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
            throw new Exception("Unknown pair format: $pair");
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

        $actualSymbolsAr = array_map(function($value) {
            return ["name" => $value];
        }, $actualSymbolsAr);

        $data = [
            "TIMEMARK" => $timeMark,
            "SYMBOLS" => $actualSymbolsAr,
            "EXCHANGE_CODE" => $marketCode . '_actual_symbols'
        ];

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'Exchange/actualSymbols.json', json_encode($data));
    }

}
