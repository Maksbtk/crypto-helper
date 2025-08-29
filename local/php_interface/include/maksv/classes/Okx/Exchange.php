<?php

namespace Maksv\Okx;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class Exchange
{
    const SYMBOLS_STOP_LIST_MAIN = ['SOL-USDT-SWAP', 'BTC-USDT-SWAP', 'ETH-USDT-SWAP'];
    const SYMBOLS_STOP_LIST1 = ['USDC-USDT-SWAP', 'USDE-USDT-SWAP', 'USTC-USDT-SWAP'];

    public function __construct()
    {
    }

    //обмен по основной стратегии
    public static function screener($interval = '15m', $longOiLimit = 0.99, $shortOiLimit = -0.99, $devMode = false)
    {
        $currentLongOiLimit = $longOiLimit;
        $currentShortOiLimit = $shortOiLimit;

        $marketMode = 'okx';
        // проверяем не запускался ли только что обмен
        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/screener/' . $interval . '/timestap.json'), true);
            if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 120)) {
                devlogs("end, timestap dif -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                return;
            } else {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/screener/' . $interval . '/timestap.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => date("d.m.y H:i:s")]));
            }
        }
        devlogs("start -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);

        if ($interval != '15m') {
            sleep(55);
            devlogs('sleep 55' . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
        } else {
            sleep(45);
            devlogs('sleep 45' . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
        }

        //получаем контракты, которые будем анализировать
        $exchangeOkxSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/okxExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeBybitSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeBinanceSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];

        $okxSymbolsList = array_column(
            array_map(function ($item) {
                $cleanId = str_replace('-' . $item['instType'], '', $item['instId']);
                return [
                    $item['instId'],
                    str_replace('-', '', $cleanId)
                ];
            }, $exchangeOkxSymbolsList),
            1,
            0
        );
        $binanceSymbolsList = array_column($exchangeBinanceSymbolsList, 'symbol') ?? [];
        $bybitSymbolsList = array_column($exchangeBybitSymbolsList, 'symbol') ?? [];

        if (!$okxSymbolsList) {
            devlogs("err, okxSymbolsList -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
            return;
        }

        if (!$binanceSymbolsList)
            devlogs("err, binanceSymbolsList -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);

        if (!$bybitSymbolsList)
            devlogs("err, bybitSymbolsList -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);

        $okxApiOb = new \Maksv\Okx\OkxFutures();
        $okxApiOb->openConnection();
        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();
        $binanceApiOb = new \Maksv\Binance\BinanceFutures();
        $binanceApiOb->openConnection();

        $okxScreenerIblockId = 8;
        $latestScreener = \Maksv\DataOperation::getLatestScreener($okxScreenerIblockId);

        $betaForeverIblockId = 9;
        $betaForeverSectionCode = 'normal_ml';
        $latestScreenerBetaForever = \Maksv\DataOperation::getLatestScreener($betaForeverIblockId, $betaForeverSectionCode);

        $betaForeverHighIblockId = 9;
        $betaForeverHighSectionCode = 'high_ml';
        $latestScreenerBetaForeverHigh = \Maksv\DataOperation::getLatestScreener($betaForeverHighIblockId, $betaForeverHighSectionCode);

        $analyzeCnt = $cnt = $cntSuccess = 0;

        $dataFileSeparateVolume = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/summaryVolumeExchange.json'; // '/upload/okxExchange/summaryVolumeExchange.json';
        $existingDataSeparateVolume = file_exists($dataFileSeparateVolume) ? json_decode(file_get_contents($dataFileSeparateVolume), true)['RESPONSE_EXCHENGE'] ?? [] : [];
        $separateVolumes = $analyzeVolumeSignalRes ?? [];

        $marketInfo = \Maksv\Helpers\Trading::checkMarketImpulsInfo();
        $analyzeSymbols = $repeatSymbols = '';

        $oiBorderExchangeFile = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/15m/oiBorderExchange.json'; // '/upload/okxExchange/15m/oiBorderExchange.json';
        $oiBorderExchangeFileData = file_exists($oiBorderExchangeFile) ? json_decode(file_get_contents($oiBorderExchangeFile), true) ?? [] : [];
        $oiBorderExchangeList = $oiBorderExchangeFileData['RESPONSE'];
        $oiBorderExchangeInfo = $oiBorderExchangeFileData['INFO'];

        foreach ($exchangeOkxSymbolsList as &$symbol) {
            try {
                $screenerData = $res['screenerPump'] = $res['screenerDump'] = [];
                $screenerData['marketCode'] = $marketMode;

                $symbolNameFormatted = $screenerData['symbolName'] = $okxSymbolsList[$symbol['instId']];
                $symbolName = $symbol['instId'];

                $symbolScale = $screenerData['symbolScale'] = countDecimalDigits($symbol['tickSz']) ?? 6;
                $symbolMaxLeverage = $screenerData['symbolMaxLeverage'] = floatval($symbol['lever']) ?? 10;

                $screenerData['interval'] = $interval;

                //dev $marketInfo['isLong'] = true; $marketInfo['risk'] = 5;
                if (!$marketInfo['isShort'] && !$marketInfo['isLong'])
                    continue;

                if (!$existingDataSeparateVolume[$symbolName]['resOkx'])
                    continue;

                $separateVolumes = array_reverse($existingDataSeparateVolume[$symbolName]['resOkx']) ?? [];
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
                if ($cnt % 12 === 0)
                    $latestScreener = \Maksv\DataOperation::getLatestScreener($okxScreenerIblockId);

                if ($cnt % 12 === 0)
                    $latestScreenerBetaForever = \Maksv\DataOperation::getLatestScreener($betaForeverIblockId, $betaForeverSectionCode);

                if ($cnt % 12 === 0)
                    $latestScreenerBetaForeverHigh = \Maksv\DataOperation::getLatestScreener($betaForeverHighIblockId, $betaForeverHighSectionCode);

                if ($cnt % 40 === 0)
                    $marketInfo = \Maksv\Helpers\Trading::checkMarketImpulsInfo();

                //dev $marketInfo['isLong'] = true; $marketInfo['risk'] = 5;

                $screenerData['latestScreener'] = $latestScreener;
                $screenerData['latestScreenerBetaForever'] = $latestScreenerBetaForever;
                $screenerData['latestScreenerBetaForeverHigh'] = $latestScreenerBetaForeverHigh;

                /*if ($latestScreener[$symbolNameFormatted]) {
                    $repeatSymbols .= $symbolName . ',';
                    continue;
                }*/

                if (
                    $symbol['state'] !== 'live'
                    || !in_array($symbol['settleCcy'], ['USDT'])
                    || in_array($symbol['instId'], self::SYMBOLS_STOP_LIST1)
                    || in_array($symbol['instId'], self::SYMBOLS_STOP_LIST_MAIN)
                ) {
                    $continueSymbols .= $symbolName . ', ';
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

                $summaryOpenInterestOb = \Maksv\Bybit\Exchange::getSummaryOpenInterest($symbolNameFormatted, $binanceApiOb, $bybitApiOb, $okxApiOb, $binanceSymbolsList, $bybitSymbolsList, $okxSymbolsList, $intervalsOImap[$interval]);
                if ($summaryOpenInterestOb['summaryOIOkx'] == 0 && $screenerData['summaryOI'] == 0) {
                    devlogs('ERR  ' . $symbolName . ' | err - oi 1 (' . $summaryOpenInterestOb['summaryOI'] . ') (' . $summaryOpenInterestOb['summaryOIBybit'] . ') (' . $summaryOpenInterestOb['summaryOIOkx'] . ')' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
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
                    if ($borderLong && $borderLong > 0.25)
                        $longOiLimit = $borderLong;
                    elseif ($avgBorderLong && $avgBorderLong > 0.25)
                        $longOiLimit = $avgBorderLong;

                    $borderShort = floatval($oiBorderExchangeList[$symbolName]['borderShort']);
                    $avgBorderShort = floatval($oiBorderExchangeInfo['avgBorderShort']);
                    if ($borderShort && $borderShort < -0.25)
                        $shortOiLimit = $borderShort;
                    elseif ($avgBorderShort && $avgBorderShort < -0.25)
                        $shortOiLimit = $avgBorderShort;

                    if ($longOiLimit < 0.7)
                        $longOiLimit = $longOiLimit * 1.4;
                    else if ($longOiLimit < 1)
                        $longOiLimit = $longOiLimit * 1.2;

                    if ($shortOiLimit > -0.7)
                        $shortOiLimit = $shortOiLimit * 1.6;
                    else if ($shortOiLimit > -1)
                        $shortOiLimit = $shortOiLimit * 1.2;

                }
                $screenerData['oiLimits'] = ['longOiLimit' => $longOiLimit, 'shortOiLimit' => $shortOiLimit];
                //dev $longOiLimit = 0.1; $shortOiLimit = -0.1;

                if (
                    !(($summaryOIOkx >= $longOiLimit) && ($summaryOI >= 0.01))
                    && !(($summaryOIOkx <= $shortOiLimit) && ($summaryOI <= -0.01))
                ) {
                    //devlogs('ERR ' . $symbolName . ' | err - OI 2' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener'.$interval);
                    continue;
                }

                //$priceChangeRange = false;
                $barsCount = 802;
                $kline = $okxApiOb->getCandles($symbolName, $interval, $barsCount, true, 120);
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
                } catch (\Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - actualMacd' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                $screenerData['actualImpulsMacd'] = $actualImpulsMacd = [];
                try {
                    $impulseMACD = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles) ?? false;
                    if ($impulseMACD && is_array($impulseMACD))
                        $screenerData['actualImpulsMacd'] = $actualImpulsMacd = $impulseMACD[array_key_last($impulseMACD)];
                } catch (\Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - actualImpulsMacd' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                $candles5m = [];
                $kline5m = $okxApiOb->getCandles($symbolName, '5m', $barsCount, true, 120);

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

                } catch (\Exception $e) {
                    devlogs('ERR | err - Supertrend 5m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                $actualAdx5m = false;
                try {
                    $adxData5m = \Maksv\TechnicalAnalysis::calculateADX($candles5m) ?? [];
                    $screenerData['actualAdx5m'] = $actualAdx5m = $adxData5m[array_key_last($adxData5m)];
                } catch (\Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - adx 5m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                if (
                    $actualAdx5m
                    && ($actualAdx5m['adx'] < 20)
                ) {
                    devlogs('CONTINUE ' . $symbolName . ' |  adx 5m ' . $actualAdx5m['adx'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    continue;
                }

                $candles15m = $candles;
                if ($interval != '15m') {
                    $kline15m = $okxApiOb->getCandles($symbolName, '15m', $barsCount, true, 120);
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
                    $screenerData['actualMacdDivergence'] = $actualMacdDivergence = \Maksv\Helpers\Trading::checkMultiMACD(
                        $candles15m,
                        '15m',
                        ['5m' => 14, '15m' => 14, '30m' => 14, '1h' => 14, '4h' => 8, '1d' => 6]
                    );
                } catch (\Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - actualMacdDivergence' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                $actualSupertrend15m = $screenerData['actualSupertrend15m'] = [];
                try {
                    $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($candles15m, 10, 3) ?? false; // длина 10, фактор 3
                    $screenerData['actualSupertrend15m'] = $actualSupertrend15m = $supertrendData[array_key_last($supertrendData)] ?? false;
                } catch (\Exception $e) {
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
                    } catch (\Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - ma26' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }
                if (!$ma26) {
                    devlogs('ERR continue ' . $symbolName . ' | err - ma26' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    continue;
                }

                if (is_array($candles) && count($candles) >= 102) {
                    try {
                        $ma50His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles, 12, 50, 102) ?? [];
                        $ma50 = $ma50His[array_key_last($ma50His)];
                    } catch (\Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - ma50' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }

                if (is_array($candles15m) && count($candles15m) >= 202) {
                    try {
                        $ma100His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles15m, 9, 100, 102) ?? [];
                        $ma100 = $ma100His[array_key_last($ma100His)];
                    } catch (\Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - ma100 candles15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }
                if (!$ma100)
                    devlogs('ERR ' . $symbolName . ' | err - ma100' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);


                if (is_array($candles15m) && count($candles15m) >= 402) {
                    try {
                        $ma200His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles15m, 9, 200, 102) ?? [];
                        $ma200 = $ma200His[array_key_last($ma200His)];
                    } catch (\Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - ma200' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }
                }

                if (is_array($candles15m) && count($candles15m) >= 802) {
                    try {
                        $ma400His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles15m, 9, 400, 10) ?? [];
                        $ma400 = $ma400His[array_key_last($ma400His)];
                    } catch (\Exception $e) {
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
                } catch (\Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - atr' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                $actualAdx = false;
                try {
                    $adxData = \Maksv\TechnicalAnalysis::calculateADX($candles15m) ?? [];
                    $screenerData['actualAdx'] = $actualAdx = $adxData[array_key_last($adxData)];
                } catch (\Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - adx' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                }

                if (
                    $actualAdx
                    && (
                        $actualAdx['adx'] < 20
                        || ($actualAdx['adx'] < 26 && $actualAdx['adxDirection']['isDownDir'] === true)
                    )
                ) {
                    //devlogs('CONTINUE ' . $symbolName . ' |  adx 15m ' . $actualAdx['adx'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    continue;
                }

                $candles1h = [];
                $actualAdx1h = false;
                $screenerData['ma400_1h'] = $ma400_1h = $screenerData['ma100_1h'] = $ma100_1h = [];

                $kline1h = $okxApiOb->getCandles($symbolName, '1H', $barsCount, true, 120);
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

                    } catch (\Exception $e) {
                        devlogs('ERR err - stoch 1h ' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }

                    try {
                        $adxData1h = \Maksv\TechnicalAnalysis::calculateADX($candles1h) ?? [];
                        $screenerData['actualAdx1h'] = $actualAdx1h = $adxData1h[array_key_last($adxData1h)];
                    } catch (\Exception $e) {
                        devlogs('ERR ' . $symbolName . ' | err - adx 1h' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }

                    if (is_array($candles1h) && count($candles1h) >= 802) {
                        try {
                            $ma400His_1h = \Maksv\TechnicalAnalysis::getMACrossHistory($candles1h, 12, 400, 10) ?? [];
                            $screenerData['ma400_1h'] = $ma400_1h = $ma400His_1h[array_key_last($ma400His_1h)];
                        } catch (\Exception $e) {
                            devlogs('ERR ' . $symbolName . ' | err - ma400 candles1h' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                        }
                    }

                    if (is_array($candles1h) && count($candles1h) >= 202) {
                        try {
                            $ma100His_1h = \Maksv\TechnicalAnalysis::getMACrossHistory($candles1h, 12, 100, 10) ?? [];
                            $screenerData['ma100_1h'] = $ma100_1h = $ma100His_1h[array_key_last($ma100His_1h)];
                        } catch (\Exception $e) {
                            devlogs('ERR ' . $symbolName . ' | err - ma100 candles1h' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                        }
                    }
                }

                if (
                    $actualAdx1h
                    && (
                        $actualAdx1h['adx'] < 20
                        || ($actualAdx1h['adx'] < 26 && $actualAdx1h['adxDirection']['isDownDir'] === true)
                    )
                ) {
                    //$continueSymbols .= $symbolName . ', ';
                    //devlogs('CONTINUE ' . $symbolName . ' |  adx 1h ' . $actualAdx1h['adx'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
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
                $maDistance = \Maksv\Helpers\Trading::getMaDistance($actualClosePrice) ?? 2.5;

                if (
                    (($summaryOIOkx >= $longOiLimit) && ($summaryOI >= 0.01))
                    && $marketInfo['isLong']
                    && $analyzeFastVolumeSignalRes['isLong']
                    && (
                        $actualMacd['isLong']
                        || ($actualImpulsMacd['isLong'] && $interval != '30m')
                        //|| ($ma100['isLong'] && $interval == '15m')
                        || ($ma200['isLong'] && $interval == '15m')
                        || ($ma400['isLong'] && $interval == '15m')
                    )
                    && \Maksv\Helpers\Trading::checkMaCondition($ma26, $actualClosePrice, $maDistance, 'long')
                    && \Maksv\Helpers\Trading::checkMaCondition($ma100, $actualClosePrice, $maDistance, 'long')
                    && \Maksv\Helpers\Trading::checkMaCondition($ma200, $actualClosePrice, $maDistance, 'long')
                    && \Maksv\Helpers\Trading::checkMaCondition($ma400, $actualClosePrice, $maDistance, 'long')
                    && \Maksv\Helpers\Trading::checkMaCondition($ma100_1h, $actualClosePrice, $maDistance, 'long')
                    && \Maksv\Helpers\Trading::checkMaCondition($ma400_1h, $actualClosePrice, $maDistance, 'long')
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
                    $processed = \Maksv\Helpers\Trading::processSignal(
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
                        $continueSymbols .= $symbolName . ', ';
                    }

                } else if (
                    (($summaryOIOkx <= $shortOiLimit) && ($summaryOI <= -0.01))
                    && $marketInfo['isShort']
                    && $analyzeFastVolumeSignalRes['isShort']
                    && (
                        $actualMacd['isShort']
                        || ($actualImpulsMacd['isShort'] && $interval != '30m')
                        //|| ($ma100['isShort'] && $interval == '15m')
                        || ($ma200['isShort'] && $interval == '15m')
                        || ($ma400['isShort'] && $interval == '15m')
                    )
                    && \Maksv\Helpers\Trading::checkMaCondition($ma26, $actualClosePrice, $maDistance, 'short')
                    && \Maksv\Helpers\Trading::checkMaCondition($ma100, $actualClosePrice, $maDistance, 'short')
                    && \Maksv\Helpers\Trading::checkMaCondition($ma200, $actualClosePrice, $maDistance, 'short')
                    && \Maksv\Helpers\Trading::checkMaCondition($ma400, $actualClosePrice, $maDistance, 'short')
                    && \Maksv\Helpers\Trading::checkMaCondition($ma100_1h, $actualClosePrice, $maDistance, 'short')
                    && \Maksv\Helpers\Trading::checkMaCondition($ma400_1h, $actualClosePrice, $maDistance, 'short')
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
                    $processed = \Maksv\Helpers\Trading::processSignal(
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
                        $continueSymbols .= $symbolName . ', ';
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
                        $separateVolumes = array_reverse(\Maksv\Helpers\Trading::aggregateSumVolume5mTo15m($separateVolumes)) ?? [];
                        $cvdChartGen = new \Maksv\Charts\CvdChartGenerator();
                        $screenerData['tempChartPath'][] = $tempCVDChartPath = $chartsDir . time() . '_' . $interval . '_cvd' . '.png';
                        $cvdChartGen->generateChart($separateVolumes, $symbolName, '15m', $tempCVDChartPath);
                    } catch (\Throwable $e) {
                        devlogs('ERR ' . $symbolName . ' | err - PriceChartGenerator' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }

                    $screenerData['mlBoard'] = $mlBoard = 0.71;
                    if ($screenerData['isLong']) {
                        $screenerData['mlBoard'] = $mlBoard = 0.74; //0.73
                        $screenerData['marketMLName'] = 'longMl';
                        $actualStrategyName = 'screenerPump';
                    } else {
                        $screenerData['mlBoard'] = $mlBoard = 0.74; //0.72
                        $screenerData['marketMLName'] = 'shortMl';
                        $actualStrategyName = 'screenerDump';
                    }

                    $screenerData['resML']['marketMl'] = $marketMl = $marketInfo[$screenerData['marketMLName']]['probabilities'][1] ?? false;
                    $screenerData['resML']['signalMl'] = $signalMl = $screenerData['actualMlModel']['probabilities'][1] ?? false;
                    $screenerData['resML']['totalMl'] = $totalMl = false;
                    if ($marketMl && $signalMl) {
                        $screenerData['resML']['totalMl'] = $totalMl = ($marketMl + $signalMl) / 2;
                    }

                    //после всех мутаций снимаем копию
                    $res[$actualStrategyName][$symbolName] = $screenerData;

                    //screener
                    if (
                        !$latestScreener[$symbolNameFormatted]
                        && $marketMl > 0.65
                        && $signalMl > 0.65
                    ) {
                        \Maksv\DataOperation::sendScreener($screenerData, true, '@infoCryptoHelperScreenerOkx');

                        $actualStrategy = [
                            "TIMEMARK" => date("d.m.y H:i:s"),
                            "STRATEGIES" => $res,
                            "INFO" => [
                                'BTC_INFO' => $marketInfo,
                            ],
                            "EXCHANGE_CODE" => 'screener'
                        ];
                        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/screener/' . $interval . '/actualStrategy.json', json_encode($actualStrategy));
                        try {
                            $writeRes = \Maksv\DataOperation::saveSignalToIblock($interval, $marketMode, 'screener');
                            devlogs('screener write' . $writeRes['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                        } catch (\Exception $e) {
                            devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                        }

                    } else {
                        devlogs(
                            ' main skip latestScreener  | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
                            $marketMode . '/screener' . $interval
                        );
                    }


                    foreach ($screenerData['tempChartPath'] as $path)
                        unlink($path);

                    $screenerData['tempChartPath'] = [];

                    //после всех мутаций снимаем копию
                    $res[$actualStrategyName][$symbolName] = $screenerData;

                    //normal ml
                    if (
                        $marketMl
                        && $signalMl
                        && $totalMl
                        && $marketMl > 0.65
                        && $signalMl > 0.65
                        && $totalMl >= $mlBoard
                        && !$latestScreenerBetaForever[$symbolNameFormatted]
                    ) {
                        devlogs(
                            'ml approve | ' . $totalMl . ' > ' . $mlBoard . ' | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
                            $marketMode . '/screener' . $interval
                        );

                        //Prophet AI //сбор статистики
                        $screenerData['leverage'] = '10X';
                        \Maksv\DataOperation::sendScreener($screenerData, false, '@cryptoHelperProphetAi');

                        //мой бот для торговли bybit // check ML
                        $screenerData['leverage'] = '10X';
                        if ($screenerData['isLong'])
                            $screenerData['TP'] = array_slice($screenerData['calculateRiskTargetsWithATR']['takeProfits'], 0, $longTpCount);
                        else
                            $screenerData['TP'] = array_slice($screenerData['calculateRiskTargetsWithATR']['takeProfits'], 0, $shortTpCount);

                        if (!is_array($screenerData['TP']) || count($screenerData['TP']) == 0)
                            $screenerData['TP'] = $screenerData['calculateRiskTargetsWithATR']['takeProfits'];

                        \Maksv\DataOperation::sendScreener($screenerData, false, '@cryptoHelperBetaForever');

                        //сохраняем в beta forever
                        $actualStrategyBeta = [
                            "TIMEMARK" => date("d.m.y H:i:s"),
                            "STRATEGIES" => $res,
                            "INFO" => [
                                'BTC_INFO' => $marketInfo,
                            ],
                            "EXCHANGE_CODE" => 'normal_ml'
                        ];

                        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/screener/' . $interval . '/actualStrategyBeta.json', json_encode($actualStrategyBeta));
                        try {
                            $writeResBeta = \Maksv\DataOperation::saveSignalToIblock($interval, $marketMode, 'normal_ml', $marketMode);
                            devlogs('screener beta forever write' . $writeResBeta['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                        } catch (\Exception $e) {
                            $errText = 'ERR beta forever write - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s");
                            \Maksv\DataOperation::sendErrorInfoMessage($errText, 'screener', $marketMode . '/screener' . $interval);
                            devlogs($errText, $marketMode . '/screener' . $interval);
                        }

                    } else {
                        devlogs(
                            'ML skip | total ' . $totalMl . ' | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
                            $marketMode . '/screener' . $interval
                        );
                        $continueSymbols .= $symbol['symbol'] . ', ';
                    }

                    //high ml
                    $screenerData = $res[$actualStrategyName][$symbolName];

                    if (
                        $marketMl
                        && $signalMl
                        && $totalMl
                        && $marketMl > 0.65
                        && $signalMl > 0.65
                        && $totalMl >= 0.80
                        && !$latestScreenerBetaForeverHigh[$symbolNameFormatted]
                    ) {
                        devlogs(
                            'high ml approve | ' . $totalMl . ' > ' . $mlBoard . ' | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
                            $marketMode . '/screener' . $interval
                        );

                        $screenerData['leverage'] = '10X';

                        if ($screenerData['isLong'])
                            $screenerData['TP'] = array_slice($screenerData['calculateRiskTargetsWithATR']['takeProfits'], 0, $longTpCount);
                        else
                            $screenerData['TP'] = array_slice($screenerData['calculateRiskTargetsWithATR']['takeProfits'], 0, $shortTpCount);

                        if (!is_array($screenerData['TP']) || count($screenerData['TP']) == 0)
                            $screenerData['TP'] = $screenerData['calculateRiskTargetsWithATR']['takeProfits'];

                        \Maksv\DataOperation::sendScreener($screenerData, false, '@cryptoHelperBetaForeverHigh');

                        //сохраняем в beta forever
                        $actualStrategyBetaHigh = [
                            "TIMEMARK" => date("d.m.y H:i:s"),
                            "STRATEGIES" => $res,
                            "INFO" => [
                                'BTC_INFO' => $marketInfo,
                            ],
                            "EXCHANGE_CODE" => 'high_ml'
                        ];

                        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/screener/' . $interval . '/actualStrategyBetaHigh.json', json_encode($actualStrategyBetaHigh));
                        try {
                            $writeResBeta = \Maksv\DataOperation::saveSignalToIblock($interval, $marketMode, 'high_ml', $marketMode);
                            devlogs('screener beta forever high write' . $writeResBeta['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                        } catch (\Exception $e) {
                            $errText = 'ERR beta forever high write - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s");
                            \Maksv\DataOperation::sendErrorInfoMessage($errText, 'screener', $marketMode . '/screener' . $interval);
                            devlogs($errText, $marketMode . '/screener' . $interval);
                        }

                    } else {
                        devlogs(
                            'high ml skip | total ' . $totalMl . ' | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
                            $marketMode . '/screener' . $interval
                        );
                    }

                }

                /*if ($devMode && $cnt >= 50)
                    break;*/
            } catch (\Exception $e) {
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
            \Maksv\DataOperation::sendInfoMessage([], $interval, $marketInfo, $cntInfo, true, 'OKX');

        return $res;
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

    )
    {
        if ($barsCount > 100) {
            $barsCount = 100;
        }

        $marketCode = 'okx';
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

        $okxApiOb = new \Maksv\Okx\OkxFutures();
        $okxApiOb->openConnection();

        // 2) Список символов
        $symbolsList = json_decode(
            file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'Exchange/derivativeBaseCoin.json'),
            true
        )['RESPONSE_EXCHENGE'] ?? [];

        $processed = 0;
        foreach ($symbolsList as $meta) {
            if (
                !isset($meta['instId'])
                || !is_string($meta['instId'])
                || $meta['state'] !== 'live'
                || !in_array($meta['settleCcy'], ['USDT'])
                || in_array($meta['instId'], self::SYMBOLS_STOP_LIST1)
                || in_array($meta['instId'], self::SYMBOLS_STOP_LIST_MAIN)
            ) {
                if ($existing[$meta['symbol']]) {
                    unset($existing[$meta['symbol']]);
                }
                continue;
            }
            $symbol = $meta['instId'];
            $processed++;

            // 3) Собираем barsCount свечей и OI
            $kline = $okxApiOb->getCandles($symbol, $timeFrame, $barsCount, true, 300);
            if (empty($kline)) {
                devlogs("No candles for {$symbol}", "{$marketCode}/oiBorder{$timeFrame}");
                continue;
            }
            /*$kline = array_map(function($item) {
                return array_map('floatval', $item);
            }, $kline);*/

            usort($kline, fn($a, $b) => $a[0] <=> $b[0]);
            $priceData = array_column(($kline), 4, 0);
            $actualClosePrice = $priceData[array_key_last($priceData)] ?? false;

            // вычисляем локальные пороги для текущего символа (если есть актуальная цена),
            // иначе оставляем пороги из аргументов метода
            $pumpThresholdLocal = $pumpThreshold;
            $dumpThresholdLocal = $dumpThreshold;
            if ($actualClosePrice !== false && is_numeric($actualClosePrice)) {
                $thr = \Maksv\Helpers\Trading::getPumpDumpThresholds((float)$actualClosePrice);
                $pumpThresholdLocal = $thr['pumpThreshold'];
                $dumpThresholdLocal = $thr['dumpThreshold'];
            }

            $oiData = [];
            $batch = $okxApiOb->getOpenInterestHist($symbol, false, false, $timeFrame, $barsCount, true, 300)['data'] ?? [];
            if (!empty($batch) && is_array($batch)) {
                foreach ($batch as $item) {
                    $oiData[(int)$item[0]] = (float)$item[1];
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
                if ($pctOi > 0 && $pctFut >= $pumpThresholdLocal) {
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
                if ($pctOi < 0 && $pctFut <= $dumpThresholdLocal) {
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
            $pumpEvents = array_slice($pumpMap, -100, 100, true);
            $dumpEvents = array_slice($dumpMap, -100, 100, true);

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

        $okxApiOb->closeConnection();
        return $filePath;
    }

    public static function okxSummaryVolumeExchange(
        $devMode = false,
        $cacheTime = 240,
        $useCache = false
    )
    {
        $marketMode = 'okx';
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
        $okxApiOb = new \Maksv\Okx\OkxFutures();
        $okxApiOb->openConnection();

        // Получение списка символов
        $exchangeSymbolsList = json_decode(
            file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/derivativeBaseCoin.json'),
            true
        )['RESPONSE_EXCHENGE'] ?? [];

        $processedSymbols = 0;
        foreach ($exchangeSymbolsList as $symbol) {
            //if ($devMode && $processedSymbols >= 60) break;

            if (
                !isset($symbol['instId'])
                || !is_string($symbol['instId'])
                || $symbol['state'] !== 'live'
                || !in_array($symbol['settleCcy'], ['USDT'])
                || in_array($symbol['instId'], self::SYMBOLS_STOP_LIST1)
                || in_array($symbol['instId'], self::SYMBOLS_STOP_LIST_MAIN)

            ) {
                $continueSymbols .= $symbol['instId'] . ', ';
                // Удаляем прежние данные по этому символу, чтобы он не остался в $existingData
                if ($existingData[$symbol['instId']]) {
                    unset($existingData[$symbol['instId']]);
                }
                continue;
            }

            $symbolName = $symbol['instId'];
            $currentData = $existingData[$symbolName]['resOkx'] ?? [];

            // Получение новых сделок
            $tradesHistoryResp = $okxApiOb->tradesHistory($symbolName, 1000, $useCache, $cacheTime);
            $tradesHistoryList = $tradesHistoryResp['data'] ?? [];

            $intervalData = [];
            foreach ($tradesHistoryList as $idx => $tradeItem) {
                try {
                    // Валидация: элемент должен быть массивом
                    if (!is_array($tradeItem)) {
                        devlogs("skip: tradeItem not array (type: " . gettype($tradeItem) . ") idx:$idx symbol:$symbolName", $marketMode . '/summaryVolumeExchange');
                        devlogs($tradeItem, $marketMode . '/summaryVolumeExchange');
                        continue;
                    }

                    $tradeTimestamp = (int)($tradeItem['ts'] / 1000);

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

                    $size = (float)$tradeItem['sz'];
                    $intervalData[$intervalStart][$tradeItem['side'] == 'sell' ? 'sellVolume' : 'buyVolume'] += $size;
                    $intervalData[$intervalStart]['sumVolume'] += $size;

                } catch (\Throwable $e) {
                    $errText = "Exception processing trade idx:$idx symbol:$symbolName message: " . $e->getMessage();
                    devlogs($errText, $marketMode . '/summaryVolumeExchange');
                    devlogs($tradeItem, $marketMode . '/summaryVolumeExchange');
                    \Maksv\DataOperation::sendErrorInfoMessage($errText, 'summaryVolumeExchange()', 'okx volume item');
                    continue;
                }
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
                'resBinance' => [],
                'resOkx' => $currentData,
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

        $okxApiOb->closeConnection();

        //devlogs("continueSymbols " . $continueSymbols, $marketMode . '/summaryVolumeExchange');
        devlogs("end (cnt " . $processedSymbols . " - " . $timeMark, $marketMode . '/summaryVolumeExchange');
        devlogs("______________________________", $marketMode . '/summaryVolumeExchange');

        return true;
    }
}
