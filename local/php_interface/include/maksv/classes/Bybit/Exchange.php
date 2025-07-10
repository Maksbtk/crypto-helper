<?php

namespace Maksv\Bybit;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;

/**
 *
 * Параметр	Минимум (консервативно)	Источник
 * 24h объём торгов	$1 млн – $2 млн	TradingView Screener
 * fxonbit.com
 * 24h объём торгов	≥ $5 млн	Hummingbot / Prop-funds
 * fxonbit.com
 * Открытый интерес	≥ $10 млн – $20 млн	Prop-фонды (Bookmap)
 * bookmap.com
 * Рыночная капитализация spot	≥ $50 млн – $100 млн	CEX best practices
 */

class Exchange
{
    const SYMBOLS_STOP_LIST_MAIN = ['SOLUSDT', 'BTCUSDT', 'ETHUSDT'];
    const SYMBOLS_STOP_LIST1 = [
        'BTCUSDT-04APR25', 'BTCUSDT-11APR25', 'BTCUSDT-21MAR25',
        'BTCUSDT-25APR25', 'ETHUSDT-04APR25', 'ETHUSDT-11APR25', 'ETHUSDT-21MAR25',
        'ETHUSDT-25APR25', 'SOLUSDT-04APR25', 'SOLUSDT-11APR25', 'ETHUSDT-30MAY25',
        'ETHUSDT-30MAY25', 'BTCUSDT-26SEP25', 'BTCUSDT-27JUN25', 'BTCUSDT-30MAY25',
        'BTCUSDT-18APR25', 'SOLUSDT-25APR25', 'ETHUSDT-26SEP25', 'ETHUSDT-02MAY25',
        'ETHUSDT-09MAY25', 'BTCUSDT-27MAR26', 'BTCUSDT-02MAY25', 'BTCUSDT-26DEC25',
        'ETHUSDT-27JUN25', 'SOLUSDT-02MAY25', 'USDCUSDT', 'USDEUSDT', 'USTCUSDT',
    ];

    public function __construct() {}

    //обмен по основной стратегии
    public static function screener($interval = '15m', $longOiLimit = 1.49, $shortOiLimit = -1.49, $devMode = false)
    {
        $currentLongOiLimit = $longOiLimit;
        $currentShortOiLimit = $shortOiLimit;

        $marketMode = 'bybit';
        // проверяем не запускался ли только что обмен
        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/screener/' . $interval . '/timestap.json'), true);
            if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 120)) {
                devlogs("end, timestap dif -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                return;
            } else {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/screener/' . $interval . '/timestap.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => date("d.m.y H:i:s")]));
            }
        }
        devlogs("start -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);

        if ($interval != '15m') {
            sleep(50);
            devlogs('sleep 50' . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
        } else {
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

        if (!$bybitSymbolsList) {
            devlogs("err, bybitSymbolsList -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
            return;
        }

        /*$scaleList = [];
        foreach ($exchangeBybitSymbolsList as $item) {
            $scaleList[$item['symbol']] = (int)$item['priceScale'];
        }*/

        if (!$binanceSymbolsList)
            devlogs("err, binanceSymbolsList -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);

        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();
        $okxApiOb = new \Maksv\Okx\OkxFutures();
        $okxApiOb->openConnection();
        $binanceApiOb = new \Maksv\Binance\BinanceFutures();
        $binanceApiOb->openConnection();

        $bybitScreenerIblockId = 3;
        $latestScreener = \Maksv\DataOperation::getLatestScreener($bybitScreenerIblockId);

        $betaForeverScreenerIblockId = 9;
        $latestScreenerBetaForever = \Maksv\DataOperation::getLatestScreener($betaForeverScreenerIblockId);

        $analyzeCnt = $cnt = $cntSuccess = 0;

        $dataFileSeparateVolume = $_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/summaryVolumeExchange.json';
        $existingDataSeparateVolume = file_exists($dataFileSeparateVolume) ? json_decode(file_get_contents($dataFileSeparateVolume), true)['RESPONSE_EXCHENGE'] ?? [] : [];
        $separateVolumes = $analyzeVolumeSignalRes ?? [];
        //$btcInfo = self::checkBtcImpulsInfo();
        $btcInfo = self::checkMarketImpulsInfo();

        $analyzeSymbols = $repeatSymbols = '';

        $oiBorderExchangeFile = $_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/15m/oiBorderExchange.json';
        $oiBorderExchangeFileData = file_exists($oiBorderExchangeFile) ? json_decode(file_get_contents($oiBorderExchangeFile), true) ?? [] : [];
        $oiBorderExchangeList = $oiBorderExchangeFileData['RESPONSE'];
        $oiBorderExchangeInfo = $oiBorderExchangeFileData['INFO'];

        foreach ($exchangeBybitSymbolsList as &$symbol) {

            try {
                $screenerData = $res['screenerPump'] = $res['screenerDump'] = [];
                $screenerData['marketCode'] = $marketMode;

                $symbolName = $screenerData['symbolName'] = $symbol['symbol'];
                $symbolScale = $screenerData['symbolScale'] = intval($symbol['priceScale']) ?? 6;
                $symbolMaxLeverage = $screenerData['symbolMaxLeverage'] = floatval($symbol['leverageFilter']['maxLeverage']) ?? 10;

                $screenerData['interval'] = $interval;

                if (!$btcInfo['isShort'] && !$btcInfo['isLong'])
                    continue;

                if (!$existingDataSeparateVolume[$symbolName]['resBybit'])
                    continue;

                $separateVolumes = array_reverse($existingDataSeparateVolume[$symbolName]['resBybit']) ?? [];
                //$analyzeVolumeSignalRes = \Maksv\TechnicalAnalysis::analyzeVolumeSignal($separateVolumes, 5, 0.2, 0.55) ?? [];
                $analyzeFastVolumeSignalRes = \Maksv\TechnicalAnalysis::analyzeVolumeSignal($separateVolumes, 3, 0.39, 0.55);

                if (!$analyzeFastVolumeSignalRes['isLong'] && !$analyzeFastVolumeSignalRes['isShort'])
                    continue;

                if (
                    ($btcInfo['isShort'] && $analyzeFastVolumeSignalRes['isLong'])
                    || ($btcInfo['isLong'] && $analyzeFastVolumeSignalRes['isShort'])
                )
                    continue;

                $screenerData['analyzeVolume'] = $symbol['analyze'] = $analyzeFastVolumeSignalRes;
                $cnt++;

                //периодически обновляем данные
                if ($cnt % 20 === 0)
                    $latestScreener = \Maksv\DataOperation::getLatestScreener($bybitScreenerIblockId);

                if ($cnt % 15 === 0)
                    $latestScreenerBetaForever = \Maksv\DataOperation::getLatestScreener($betaForeverScreenerIblockId);

                if ($cnt % 50 === 0)
                    $btcInfo = self::checkMarketImpulsInfo();

                $screenerData['latestScreener'] = $latestScreener;
                $screenerData['latestScreenerBetaForever'] = $latestScreenerBetaForever;

                if ($latestScreener[$symbolName]) {
                    $repeatSymbols .= $symbolName . ',';
                    continue;
                }


                if (
                    !isset($symbol['symbol'])
                    || !is_string($symbol['symbol'])
                    || preg_match('/^(ETHUSDT-|ETH-|BTCUSDT-|BTC-|SOLUSDT-)/', $symbol['symbol'])
                    || !in_array($symbol['quoteCoin'], ['USDT'])
                    || in_array($symbol['baseCoin'], ['FTN', 'STPT', 'GOMINING', 'FDUSD', 'USDE', 'USDC'])
                    || in_array($symbol['symbol'], self::SYMBOLS_STOP_LIST1)
                ) {
                    //$continueSymbols .= $symbol['symbol'] . ', ';
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
                //$summaryOpenInterestOb = self::getSummaryOpenInterest($symbolName, $binanceApiOb, $bybitApiOb, $binanceSymbolsList, $bybitSymbolsList, $intervalsOImap[$interval]);
                if (!$summaryOpenInterestOb['summaryOI'] || !$summaryOpenInterestOb['summaryOIBybit']) {
                    //devlogs('ERR ' . $symbolName . ' | err - oi ('.$summaryOpenInterestOb['summaryOI'].') ('.$summaryOpenInterestOb['summaryOIBybit'].')' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener'.$interval);
                    //devlogs($summaryOpenInterestOb, $marketMode . '/screener'.$interval);
                    continue;
                }

                $summaryOIBybit = $screenerData['summaryOIBybit'] = $summaryOpenInterestOb['summaryOIBybit'] ?? 0;
                $summaryOIBinance = $screenerData['summaryOIBinance'] = $summaryOpenInterestOb['summaryOIBinance'] ?? 0;
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

               /* if (
                    !($summaryOIBybit >= $longOiLimit && (!$summaryOIBinance || $summaryOIBinance >= 0.1))
                    && !($summaryOI >= $longOiLimit)
                    && !($summaryOIBybit <= $shortOiLimit && (!$summaryOIBinance || $summaryOIBinance <= -0.1))
                ) {
                    //devlogs('ERR ' . $symbolName . ' | err - OI' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener'.$interval);
                    continue;
                }*/

                if (
                    !(($summaryOIBybit >= $longOiLimit) && ($summaryOI >= 0.01))
                    && !(($summaryOIBybit <= $shortOiLimit) && ($summaryOI <= -0.01))
                ) {
                    //devlogs('ERR ' . $symbolName . ' | err - OI 2' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener'.$interval);
                    continue;
                }

                //$priceChangeRange = false;
                $barsCount = 802;
                $kline = $bybitApiOb->klineV5("linear", $symbolName, $interval, $barsCount, true, 120);
                if (!$kline['result'] || !$kline['result']['list'] || !is_array($kline['result']['list'])) {
                    devlogs('ERR ' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    continue;
                }

                $klineList = array_reverse($kline['result']['list']);
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
                $kline5m = $bybitApiOb->klineV5("linear", $symbolName, '5m', $barsCount, true, 120);
                if (!$kline5m['result'] || !$kline5m['result']['list'] || !is_array($kline5m['result']['list'])) {
                    devlogs('ERR 2' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    //continue;
                } else {
                    $kline5mList = array_reverse($kline5m['result']['list']);
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
                    $kline15m = $bybitApiOb->klineV5("linear", $symbolName, '15m', $barsCount, true, 120);
                    if (!$kline15m['result'] || !$kline15m['result']['list'] || !is_array($kline15m['result']['list'])) {
                        devlogs('ERR 3' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                        //continue;
                    } else {
                        $kline15mList = array_reverse($kline15m['result']['list']);
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
                    $screenerData['actualMacdDivergence'] = $actualMacdDivergence = self::checkMultiMACD(
                        $candles15m,
                        '15m',
                        ['5m' => 11, '15m' => 11, '30m' => 11, '1h' => 14, '4h' => 8, '1d' => 6]
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
                        $ma400His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles15m, 12, 400, 10) ?? [];
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

                $kline1h = $bybitApiOb->klineV5("linear", $symbolName, '1h', 100, true, 120);
                if (!$kline1h['result'] || !$kline1h['result']['list'] || !is_array($kline1h['result']['list'])) {
                    devlogs('ERR 3' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    //continue;
                } else {
                    $kline1hList = array_reverse($kline1h['result']['list']);
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
                $atrMultipliers = $btcInfo['atrMultipliers'];
                if (!$atrMultipliers || !is_array($atrMultipliers)) $atrMultipliers = [2.3, 2.9, 3.3];

                $longTpCount = $btcInfo['longTpCount'] ?? 3;
                $shortTpCount = $btcInfo['shortTpCount'] ?? 3;

                $screenerData['tpCount'] = [
                    'longTpCount' => $longTpCount,
                    'shortTpCount' => $shortTpCount,
                ];

                $screenerData['atrMultipliers'] = $atrMultipliers;

                $maDistance = 2.5;
                if (
                    (($summaryOIBybit >= $longOiLimit) && ($summaryOI >= 0.01))
                    && $btcInfo['isLong']
                    && $analyzeFastVolumeSignalRes['isLong']
                    && (
                        $actualMacd['isLong']
                        || ($actualImpulsMacd['isLong'] && $interval != '30m')
                        || ($ma100['isLong'] && $interval == '15m')
                        || ($ma200['isLong'] && $interval == '15m')
                        || ($ma400['isLong'] && $interval == '15m')
                    )
                    && self::checkMaCondition($ma26,  $actualClosePrice, $maDistance, 'long')
                    && self::checkMaCondition($ma100, $actualClosePrice, $maDistance, 'long')
                    && self::checkMaCondition($ma200, $actualClosePrice, $maDistance, 'long')
                    && self::checkMaCondition($ma400, $actualClosePrice, $maDistance, 'long')
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

                    $screenerData['SL'] = $screenerData['TP'] = $screenerData['recommendedEntry'] = false;

                    $processed = self::processSignal(
                        'long',
                        floatval($actualATR['atr']),
                        floatval($actualClosePrice),
                        $candles15m,
                        $actualSupertrend5m,
                        $actualSupertrend15m,
                        $actualMacdDivergence,
                        $symbolScale,
                        $atrMultipliers,
                        $btcInfo,
                        $symbolName,
                        "$marketMode/screener$interval"
                    );

                    //if ($processed !== false) {
                    if ($processed !== false) {
                        $screenerData = array_merge($screenerData, $processed);
                        $res['screenerPump'][$symbolName] = $screenerData;
                    } else {
                        $continueSymbols .= $symbol['symbol'] . ', ';
                    }

                } else if (
                    (($summaryOIBybit <= $shortOiLimit) && ($summaryOI <= -0.01))
                    && $btcInfo['isShort']
                    && $analyzeFastVolumeSignalRes['isShort']
                    && (
                        $actualMacd['isShort']
                        || ($actualImpulsMacd['isShort'] && $interval != '30m')
                        || ($ma100['isShort'] && $interval == '15m')
                        || ($ma200['isShort'] && $interval == '15m')
                        || ($ma400['isShort'] && $interval == '15m')
                    )
                    && self::checkMaCondition($ma26,  $actualClosePrice, $maDistance, 'short')
                    && self::checkMaCondition($ma100, $actualClosePrice, $maDistance, 'short')
                    && self::checkMaCondition($ma200, $actualClosePrice, $maDistance, 'short')
                    && self::checkMaCondition($ma400, $actualClosePrice, $maDistance, 'short')
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

                    $screenerData['SL'] = $screenerData['TP'] = $screenerData['recommendedEntry'] = false;

                    $processed = self::processSignal(
                        'short',
                        floatval($actualATR['atr']),
                        floatval($actualClosePrice),
                        $candles15m,
                        $actualSupertrend5m,
                        $actualSupertrend15m,
                        $actualMacdDivergence,
                        $symbolScale,
                        $atrMultipliers,
                        $btcInfo,
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
                        $separateVolumes = array_reverse(self::aggregateSumVolume5mTo15m($separateVolumes)) ?? [];
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

                    $screenerData['resML']['marketMl'] = $marketMl = $btcInfo[$screenerData['marketMLName']]['probabilities'][1] ?? false;
                    $screenerData['resML']['signalMl'] = $signalMl = $screenerData['actualMlModel']['probabilities'][1] ?? false;
                    $screenerData['resML']['totalMl'] = $totalMl = false;
                    if ($marketMl && $signalMl) {
                        $screenerData['resML']['totalMl'] = $totalMl = ($marketMl + $signalMl) / 2;
                    }

                    \Maksv\DataOperation::sendScreener($screenerData, true, '@infoCryptoHelperScreener');

                    foreach ($screenerData['tempChartPath'] as $path)
                        unlink($path);

                    $screenerData['tempChartPath'] = [];
                    $screenerData['resML']['mlBoard'] = $mlBoard = $btcInfo['mlBoard'] ?? 0.71;

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
                                'BTC_INFO' => $btcInfo,
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

                    //cохраняем отдельно по бирже
                    $actualStrategy = [
                        "TIMEMARK" => date("d.m.y H:i:s"),
                        "STRATEGIES" => $res,
                        "INFO" => [
                            'BTC_INFO' => $btcInfo,
                        ],
                        "EXCHANGE_CODE" => 'screener'
                    ];

                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/'.$marketMode.'Exchange/screener/' . $interval . '/actualStrategy.json', json_encode($actualStrategy));
                    try {
                        $writeRes = \Maksv\DataOperation::saveSignalToIblock($interval, 'bybit', 'screener');
                        devlogs('screener write' . $writeRes['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    } catch (Exception $e) {
                        $errText = 'ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s");
                        \Maksv\DataOperation::sendErrorInfoMessage($errText, 'screener', $marketMode . '/screener' . $interval);
                        devlogs($errText, $marketMode . '/screener' . $interval);
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
        devlogs($btcInfo['infoText'], $marketMode . '/screener' . $interval);
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
            \Maksv\DataOperation::sendInfoMessage([], $interval, $btcInfo, $cntInfo, true, 'BYBIT');

        return $res;
    }

    //обмен по тестовым для сбора инфо по тестовым стратегиям
    public static function bybitExchange($timeFrame = '30m', $longOiLimit = 1.7, $shortOiLimit = -1.29, $devMode = false)
    {
        $currentLongOiLimit = $longOiLimit;
        $currentShortOiLimit = $shortOiLimit;

        $marketCode = 'bybit';

        $timeMark = date("d.m.y H:i:s");
        $res = ['symbols' => [],];
        //devlogs("start dev" . ' - ' .  date("d.m.y H:i:s"), 'bybitExchange' $marketCode . '/bybitExchange' . $timeFrame);

        // проверяем не запускался ли только что обмен
        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/timestap.json'), true);
            if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 180) && !$devMode) {
                devlogs("end, timestap dif -" . ' - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                return;
            } else {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/timestap.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => $timeMark]));
            }
        }

        devlogs("start" . ' - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
        $marketVolumesJson['RESPONSE_EXCHENGE'] = [];

        if (!$devMode) {
            if ($timeFrame != '15m') {
                sleep(60);
                devlogs('sleep 60' . ' - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
            } else {
                sleep(30);
                devlogs('sleep 30' . ' - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
            }
        }

        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();
        $binanceApiOb = new \Maksv\Binance\BinanceFutures();
        $binanceApiOb->openConnection();
        $okxApiOb = new \Maksv\Okx\OkxFutures();
        $okxApiOb->openConnection();

        //получаем контракты, которые будем анализировать
        $exchangeBybitSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeBinanceSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeOkxSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/okxExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];

        if (!$exchangeBybitSymbolsList || $timeFrame == '1d') {
            // Fetch all Bybit V5 symbols (handling pagination via cursor)
            $exchangeBybitSymbolsList = [];
            $cursor = '';

            do {
                // Request next page of symbols
                $exchangeSymbolsResp = $bybitApiOb->getSymbolsV5('linear', '', $cursor);

                // Check for a valid response
                if (empty($exchangeSymbolsResp['result']['list'])) {
                    break;
                }

                // Merge current page's symbols into the master list
                $exchangeBybitSymbolsList = array_merge(
                    $exchangeBybitSymbolsList,
                    $exchangeSymbolsResp['result']['list']
                );

                // Prepare cursor for next iteration (if provided)
                $cursor = $exchangeSymbolsResp['result']['nextPageCursor'] ?? '';

            } while (!empty($cursor));

            //получаем инфу по ои и объемам за 24 часа и пишем в резалт массив
            $tickersInfo = $bybitApiOb->getTickers('linear');
            if (!$tickersInfo['status']) {
                devlogs('ERR | bybit tickers er  | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
            } else {
                foreach ($exchangeBybitSymbolsList as &$bybitSymbolInfo) {
                    $bybitSymbolInfo['filterVal'] = [
                        'openInterestValue' => floatval($tickersInfo['result'][$bybitSymbolInfo['symbol']]['openInterestValue']),
                        'turnover24h' => floatval($tickersInfo['result'][$bybitSymbolInfo['symbol']]['turnover24h'])
                    ];
                }
                unset($bybitSymbolInfo);
            }
            // 2) Получаем tickers (OI и объемы)
            /*$tickersInfo = $bybitApiOb->getTickers('linear');
            if (empty($tickersInfo) || !isset($tickersInfo['result']['list'])) {
                devlogs('ERR | bybit tickers error', $marketCode . '/bybitExchange' . $timeFrame);
            } else {
                // Индексируем result.list по symbol для быстрого доступа
                $tickersBySymbol = [];
                foreach ($tickersInfo['result']['list'] as $t) {
                    $tickersBySymbol[$t['symbol']] = $t;
                }

                // 3) Встраиваем OI и turnover
                foreach ($exchangeBybitSymbolsList as &$bybitSymbolInfo) {
                    $sym = $bybitSymbolInfo['symbol'];
                    if (isset($tickersBySymbol[$sym])) {
                        $info = $tickersBySymbol[$sym];
                        $bybitSymbolInfo['filterVal'] = [
                            'openInterestValue' => (float)$info['openInterestValue'],
                            'turnover24h'       => (float)$info['turnover24h'],
                        ];
                    } else {
                        $bybitSymbolInfo['filterVal'] = [
                            'openInterestValue' => 0.0,
                            'turnover24h'       => 0.0,
                        ];
                    }
                }
                unset($bybitSymbolInfo);
            }*/

            // 4) Собираем все уникальные базовые монеты
            $allBaseCoins = array_unique(array_column($exchangeBybitSymbolsList, 'baseCoin'));

            // 5) Запрашиваем капитализации батчами по 99 монет через CoinMarketCap
            $cmc       = new \Maksv\Coinmarketcap\Request();
            $capMap    = [];  // здесь накопим [SYMBOL => marketCap]
            $chunks    = array_chunk($allBaseCoins, 99);
            foreach ($chunks as $chunk) {
                try {
                    // getMarketCaps умеет принять массив
                    $caps = $cmc->getMarketCaps($chunk, true, 3600);
                    // объединяем
                    $capMap = array_merge($capMap, $caps);
                } catch (\RuntimeException $e) {
                    // на случай ошибки просто пропускаем эти символы
                    devlogs('CMC marketCap error: ' . $e->getMessage(), $marketCode . '/bybitExchange' . $timeFrame);
                }
            }

            // 6) Встраиваем marketCap в каждый элемент
            foreach ($exchangeBybitSymbolsList as &$bybitSymbolInfo) {
                $base = $bybitSymbolInfo['baseCoin'];
                $bybitSymbolInfo['filterVal']['marketCap'] = $capMap[$base] ?? 0.0;
            }
            unset($bybitSymbolInfo);

            $dataBybitInfo = [
                'TIMEMARK' => $timeMark,
                'RESPONSE_EXCHENGE' => $exchangeBybitSymbolsList,
                'EXCHANGE_CODE' => 'bybit'
            ];

            // Write out JSON file
            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json',
                json_encode($dataBybitInfo)
            );

            //обновляем binance файлик
            $binanceFuturesSymbols = $binanceApiOb->getFuturesSymbols();
            if ($binanceFuturesSymbols) {
                $dataBinanceInfo = [
                    "TIMEMARK" => $timeMark,
                    "RESPONSE_EXCHENGE" => $binanceFuturesSymbols,
                    "EXCHANGE_CODE" => 'binance'
                ];
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/derivativeBaseCoin.json', json_encode($dataBinanceInfo));
                $exchangeBinanceSymbolsList = $exchangeSymbolsResp['result']['list'];
            }

            //обновляем окх файлик
            $okxFuturesSymbols = $okxApiOb->getFuturesSymbols();
            if ($okxFuturesSymbols) {
                $dataOkxInfo = [
                    "TIMEMARK" => $timeMark,
                    "RESPONSE_EXCHENGE" => $okxFuturesSymbols,
                    "EXCHANGE_CODE" => 'binance'
                ];
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/okxExchange/derivativeBaseCoin.json', json_encode($dataOkxInfo));
                $exchangeBinanceSymbolsList = $exchangeSymbolsResp['result']['list'];
            }

            if ($timeFrame == '1d') {
                echo '<pre>'; var_dump('tech OK'); echo '</pre>';
                return true;
            }
        }

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

        //bybit получаем последние новости, чтобы узнать есть ли делистинговые монеты
        $exchangeAnnouncements = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/announcement.json'), true)['RESPONSE_EXCHENGE'];
        if (!$exchangeAnnouncements || $timeFrame == '1d') {
            $getAnnouncement = $bybitApiOb->getAnnouncement();
            if ($getAnnouncement['result']['list']) {
                $dataBybitNewsInfo = [
                    "TIMEMARK" => $timeMark,
                    "RESPONSE_EXCHENGE" => $getAnnouncement['result']['list'],
                    "EXCHANGE_CODE" => 'bybit'
                ];
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/announcement.json', json_encode($dataBybitNewsInfo));
                $exchangeAnnouncements = $getAnnouncement['result']['list'];
            }
        }

        //сразу анализируем новости, создаем массив делистинговых монет
        $delistingAnnouncements = [];
        foreach ($exchangeAnnouncements as $announcement) {
            if (in_array('Derivatives', $announcement['tags']) && in_array('Delistings', $announcement['tags'])) {
                $delistingAnnouncements[] = $announcement['title'];
            }
        }

        //переходим к анализу контрактов
        $countReq = $analysisSymbolCount = 0;
        $analysisSymbols = '';
        $actualOpportunities = [
            'masterPump' => [],
            'masterDump' => [],
            'allPump' => [],
            'allDump' => [],
            'pump' => [],
            'dump' => [],
        ];

        $btcInfo = self::checkMarketImpulsInfoDev();

        $dataFileSeparateVolume = $_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/summaryVolumeExchange.json';
        $existingDataSeparateVolume = file_exists($dataFileSeparateVolume) ? json_decode(file_get_contents($dataFileSeparateVolume), true)['RESPONSE_EXCHENGE'] ?? [] : [];
        $separateVolumes = $analyzeVolumeSignalRes ?? [];

        $oiBorderExchangeFile = $_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/15m/oiBorderExchange.json';
        $oiBorderExchangeFileData = file_exists($oiBorderExchangeFile) ? json_decode(file_get_contents($oiBorderExchangeFile), true) ?? [] : [];
        $oiBorderExchangeList = $oiBorderExchangeFileData['RESPONSE'];
        $oiBorderExchangeInfo = $oiBorderExchangeFileData['INFO'];

        //получаем список последних сигналов
        $latestSignals = \Maksv\DataOperation::getLatestSignals($timeFrame, 'master');

        foreach ($exchangeBybitSymbolsList as &$symbol) {

            $symbolName = $symbol['symbol'];
            $symbolScale = intval($symbol['priceScale']) ?? 6;
            $symbolMaxLeverage = floatval($symbol['leverageFilter']['maxLeverage']) ?? 10;

            //$btcInfo['isLong'] = true;
            if (!$btcInfo['isShort'] && !$btcInfo['isLong'])
                continue;

            if ($latestSignals['repeatSymbols'][$symbolName])
                continue;

            if (!$existingDataSeparateVolume[$symbolName]['resBybit'])
                continue;

            $separateVolumes = array_reverse($existingDataSeparateVolume[$symbolName]['resBybit']) ?? [];
            $analyzeVolumeSignalRes = \Maksv\TechnicalAnalysis::analyzeVolumeSignal($separateVolumes, 3, 0.39, 0.55) ?? [];
            //$analyzeVolumeSignalRes = \Maksv\TechnicalAnalysis::analyzeVolumeSignal($separateVolumes, 3, 0.64, 0.64) ?? [];

            if (!$analyzeVolumeSignalRes['isLong'] && !$analyzeVolumeSignalRes['isShort'])
                continue;

            if (
                ($btcInfo['isShort'] && $analyzeVolumeSignalRes['isLong'])
                || ($btcInfo['isLong'] && $analyzeVolumeSignalRes['isShort'])
            )
                continue;

            $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['analyzeVolumeSignalRes'] = $analyzeVolumeSignalRes;

            $delistingFlag = false;
            foreach ($delistingAnnouncements as $announcement) {
                // Ищем шаблон "Delisting of СИМВОЛ" с возможными пробелами и разделителями
                if (preg_match('/Delisting of\s+([^\s]+)/i', $announcement, $matches)) {
                    // Нормализация символа: удаление пробелов, спецсимволов, приведение к верхнему регистру
                    $extractedSymbol = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($matches[1])));
                    $currentSymbol = strtoupper(preg_replace('/[^A-Z0-9]/', '', $symbol['symbol']));

                    if ($extractedSymbol === $currentSymbol) {
                        $delistingFlag = true;
                        $symbol['delisting'] = true;
                        break; // Прерываем цикл при первом совпадении
                    }
                }
            }

            // Проверяем возраст контракта
            $launchTimeMs = floatval($symbol['launchTime']); // Время листинга контракта (в миллисекундах)
            $launchTime = $launchTimeMs / 1000;    // Преобразуем в секунды
            $monthsAgo = strtotime('-1 months'); // Время n месяцев назад (в секундах)

            if (
                $launchTime <= $monthsAgo
                //&& !$delistingFlag
                && $symbol['status'] == 'Trading'
                && in_array($symbol['quoteCoin'], ['USDT'/*, 'USDC', 'USDE', 'FDUSD', 'TUSD'*/])
                && !in_array($symbol['baseCoin'], ['FTN', 'STPT', 'GOMINING', 'FDUSD', 'USDE', 'USDC'])
                && !preg_match('/^(ETHUSDT-|ETH-|BTCUSDT-|BTC-|SOLUSDT-)/', $symbol['symbol'])
                && !in_array($symbol['symbol'], self::SYMBOLS_STOP_LIST1)
            ) {
                $countReq++;

                //периодически обновляем данные
                if ($countReq % 50 === 0)
                    $btcInfo = self::checkMarketImpulsInfoDev();

                try {
                    $klineHistory = [];

                    $intervalsOImap = [
                        '5m' => '15m',
                        '15m' => '15m',
                        '30m' => '15m',
                        '1h' => '30m',
                        '4h' => '30m',
                        '1d' => '30m',
                    ];

                    //oi
                    $summaryOpenInterestOb = self::getSummaryOpenInterestDev($symbolName, $binanceApiOb, $bybitApiOb, $okxApiOb, $binanceSymbolsList, $bybitSymbolsList, $okxSymbolsList, $intervalsOImap[$timeFrame]);
                    //$summaryOpenInterestOb = self::getSummaryOpenInterest($symbolName, $binanceApiOb, $bybitApiOb, $binanceSymbolsList, $bybitSymbolsList, $intervalsOImap[$timeFrame]);
                    if (/*$summaryOpenInterestOb['summaryOI'] || */$summaryOpenInterestOb['summaryOIBybit']) {

                        $summaryOIBybit = $summaryOpenInterestOb['summaryOIBybit'];
                        $summaryOIBinance = $summaryOpenInterestOb['summaryOIBinance'];
                        $summaryOIOkx = $summaryOpenInterestOb['summaryOIOkx'] ?? 0;

                        $openInterest = $summaryOI = $summaryOpenInterestOb['summaryOI'] ?? $summaryOpenInterestOb['summaryOIBybit'];
                        $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['summaryOpenInterest'] = $summaryOpenInterestOb;

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

                        if (
                            in_array($symbolName, ['BTCUSDT', 'ETHUSDT'])
                            || ($summaryOIBybit >= $longOiLimit)
                            || ($summaryOIBybit <= $shortOiLimit)
                        ) {
                            $priceChange = $lastClosePrice = 0;
                            $barsCount = 802;

                            // main candles
                            $kline = $bybitApiOb->klineV5("linear", $symbolName, $timeFrame, $barsCount, true, 120);
                            if ($kline['result'] && $kline['result']['list']) {
                                $klineList = array_reverse($kline['result']['list']);

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

                                // price fields
                                $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                                if ($prevKline) {
                                    $priceChange = round((floatval($prevKline[4]) / (floatval($prevKline[1]) / 100)) - 100, 2);
                                    $lastClosePrice = floatval($prevKline[4]);
                                }

                                $actualClosePrice = false;
                                $actualKline = $klineList[array_key_last($klineList)] ?? false;
                                if ($actualKline)
                                    $actualClosePrice = floatval($actualKline[4]);

                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualClosePrice'] = $actualClosePrice ?? false;
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['lastClosePrice'] = $lastClosePrice;
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['priceChange'] = $priceChange;
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['candles'] = $candles;

                                //macd
                                $macdData = $actualMacd = [];
                                try {
                                    $macdData = \Maksv\TechnicalAnalysis::analyzeMACD($candles) ?? false;
                                    $actualMacd = false;
                                    if ($macdData && is_array($macdData))
                                        $actualMacd = $macdData[array_key_last($macdData)];

                                } catch (Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - macdData' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualMacd'] = $actualMacd;

                                $actualImpulsMacd = [];
                                try {
                                    $impulseMACD = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles) ?? false;
                                    if ($impulseMACD && is_array($impulseMACD))
                                        $actualImpulsMacd = $impulseMACD[array_key_last($impulseMACD)];
                                } catch (Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - actualImpulsMacd' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualImpulsMacd'] = $actualImpulsMacd;

                                //ma26
                                $crossMA = $crossMA100 = $crossMA200 = $crossMA400 = [];
                                try {
                                    $crossMA = \Maksv\TechnicalAnalysis::checkMACross($candles, 9, 26, 20, 2) ?? [];
                                } catch (Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - ma 26' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                $crossMAVal = 0;
                                if ($crossMA['is_reversal'] && $crossMA['isUptrend'])
                                    $crossMAVal = 1;
                                else if ($crossMA['is_reversal'] && !$crossMA['isUptrend'])
                                    $crossMAVal = 2;

                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['crossMA'] = $crossMA;

                                /*if (!$actualMacd['isLong'] && !$actualMacd['isShort'] && !$crossMA['isLong'] && !$crossMA['isShort'])
                                    continue;*/

                                //15m candles
                                $candles15m = [];
                                if ($timeFrame != '15m') {
                                    $kline15m = $bybitApiOb->klineV5("linear", $symbolName, '15m', $barsCount, true, 120);
                                    if ($kline15m['result'] && $kline15m['result']['list']) {
                                        $kline15mList = array_reverse($kline15m['result']['list']);
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
                                } else {
                                    $candles15m = $candles;
                                }

                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['candles15m'] = $candles15m;
                                if (!$candles15m)
                                    continue;

                                //divergence 15m
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualMacdDivergence'] = $actualMacdDivergence = $actualATR = [];
                                try {
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualMacdDivergence'] = $actualMacdDivergence = self::checkMultiMACD(
                                        $candles15m,
                                        '15m',
                                        ['5m' => 11, '15m' => 11, '30m' => 11, '1h' => 14, '4h' => 8, '1d' => 6]
                                    );
                                } catch (Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - actualMacdDivergence' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                // supertrend 15m
                                $actualSupertrend15m = $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualSupertrend15m'] = [];
                                try {
                                    $supertrendData15m = \Maksv\TechnicalAnalysis::calculateSupertrend($candles15m, 10, 3) ?? false; // длина 10, фактор 3
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualSupertrend5m'] = $actualSupertrend15m = $supertrendData15m[array_key_last($supertrendData15m)] ?? false;
                                } catch (Exception $e) {
                                    devlogs('ERR | err - Supertrend 15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                //atr 15m
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualATR'] = $actualATR = [];
                                try {
                                    // Рассчитываем ATR по свечам
                                    $ATRData = \Maksv\TechnicalAnalysis::calculateATR($candles15m);
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualATR'] = $actualATR = $ATRData[array_key_last($ATRData)] ?? null;
                                } catch (Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - ATR' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                // other ma, always 15m candles
                                try {
                                    $crossMA100 = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 9, 100, 20, 2) ?? [];
                                } catch (Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - ma 100' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['cross100MA'] = $crossMA100;

                                try {
                                    $crossMA200 = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 9, 200, 20, 2) ?? [];
                                } catch (Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | ma 200 ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['cross200MA'] = $crossMA200;

                                try {
                                    $crossMA400 = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 9, 400, 20, 2) ?? [];
                                } catch (Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | ma 400 ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['cross400MA'] = $crossMA400;

                                // 5m candles
                                $candles5m = $actualSupertrend5m = [];
                                $kline5m = $bybitApiOb->klineV5("linear", $symbolName, '5m', $barsCount, true, 120);
                                if ($kline5m['result'] && $kline5m['result']['list'] && is_array($kline5m['result']['list'])) {
                                    $kline5mList = array_reverse($kline5m['result']['list']);
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

                                    $actualCandle5m = $candles5m[array_key_last($candles5m)] ?? false;
                                    $actualClosePrice = floatval($actualCandle5m['c']);
                                }
                                //$marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['candles5m'] = $candles5m;

                                // supertrend 5m
                                try {
                                    $supertrendData5m = \Maksv\TechnicalAnalysis::calculateSupertrend($candles5m, 10, 3) ?? false; // длина 10, фактор 3
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualSupertrend5m'] = $actualSupertrend5m = $supertrendData5m[array_key_last($supertrendData5m)] ?? false;
                                } catch (Exception $e) {
                                    devlogs('ERR | err - Supertrend 5m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                //если лонг, а тренды все down
                                if (
                                    $analyzeVolumeSignalRes['isLong']
                                    && ($actualSupertrend15m && !$actualSupertrend15m['isUptrend'])
                                    && ($actualSupertrend5m && !$actualSupertrend5m['isUptrend'])
                                ) {
                                    //devlogs('dev | ' . $symbolName . ' long down down' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                    continue;
                                }

                                // если шорт, а тернды все up
                                if (
                                    $analyzeVolumeSignalRes['isShort']
                                    && ($actualSupertrend15m && $actualSupertrend15m['isUptrend'])
                                    && ($actualSupertrend5m && $actualSupertrend5m['isUptrend'])
                                ) {
                                    //devlogs('dev | ' . $symbolName . ' short up up' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                    continue;
                                }

                                /*$analyzeOrderBook = [];
                                try {
                                    $orderBook = $bybitApiOb->orderBookV5('linear', $symbolName, 1000, true);
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['analyzeOrderBook'] = $analyzeOrderBook = \Maksv\TechnicalAnalysis::analyzeOrderBook($orderBook) ?? [];
                                } catch (Exception $e) {
                                    devlogs('ERR | ' . $symbolName . ' analyzeOrderBook' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }*/

                                $actualAdx = false;
                                try {
                                    $adxData = \Maksv\TechnicalAnalysis::calculateADX($candles15m) ?? [];
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualAdx'] = $actualAdx = $adxData[array_key_last($adxData)];
                                } catch (Exception $e) {
                                    devlogs('ERR | ' . $symbolName . ' adx' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                if (
                                    $actualAdx
                                    && (
                                        $actualAdx['adx'] < 20
                                        || ($actualAdx['adx'] < 26 && $actualAdx['adxDirection']['isDownDir'] === true)
                                    )
                                ) {
                                    continue;
                                }

                                $actualStochastic1h = $actualAdx1h = [];
                                $kline1h = $bybitApiOb->klineV5("linear", $symbolName, '1h', 100, true, 120);
                                if (!$kline1h['result'] || !$kline1h['result']['list'] || !is_array($kline1h['result']['list'])) {
                                    devlogs('ERR 3 |  timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                    //continue;
                                } else {
                                    $kline1hList = array_reverse($kline1h['result']['list']);
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
                                            $actualStochastic1h = $stochasticOscillatorData1h[array_key_last($stochasticOscillatorData1h)];

                                    } catch (Exception $e) {
                                        devlogs('ERR | ' . $symbolName . ' actualStochastic1h' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                    }

                                    try {
                                        $adxData1h = \Maksv\TechnicalAnalysis::calculateADX($candles1h) ?? [];
                                        $actualAdx1h = $adxData1h[array_key_last($adxData1h)];
                                    } catch (Exception $e) {
                                        devlogs('ERR | ' . $symbolName . ' err - adx 1h' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                    }
                                }

                                if (
                                    $actualAdx1h
                                    && (
                                        $actualAdx1h['adx'] < 20
                                        || ($actualAdx1h['adx'] < 26 && $actualAdx1h['adxDirection']['isDownDir'] === true)
                                    )
                                ) {
                                    continue;
                                }

                                $atrMultipliers = $btcInfo['atrMultipliers'];
                                if (!$atrMultipliers || !is_array($atrMultipliers))
                                    $atrMultipliers = [2.3, 2.9, 3.3];

                                $longTpCount = $btcInfo['longTpCount'] ?? 3;
                                $shortTpCount = $btcInfo['shortTpCount'] ?? 3;

                                $opportunityData = [
                                    'symbolName' => $symbolName,
                                    'symbolScale' => $symbolScale,
                                    'symbolMaxLeverage' => $symbolMaxLeverage,

                                    'lastClosePrice' => $lastClosePrice,
                                    'actualClosePrice' => $actualClosePrice,
                                    'lastOpenInterest' => $openInterest,
                                    'summaryOpenInterest' => $summaryOpenInterestOb,
                                    'lastPriceChange' => $priceChange,
                                    //'timestampOI' => $timestampOI,

                                    'analyzeVolumeSignalRes' => $analyzeVolumeSignalRes,
                                    'actualAdx' => $actualAdx,

                                    'lastCrossMA' => $crossMA,
                                    'MA26' => $crossMA['isUptrend'] ? 'up' : 'down',

                                    'lastCrossMA400' => $crossMA400,
                                    'lastCrossMA200' => $crossMA200,
                                    'lastCrossMA100' => $crossMA100,
                                    'crossMAVal' => $crossMAVal,

                                    'candles15m' => array_slice($candles15m, -30),

                                    //'actualStochastic' => $actualStochastic,
                                    'actualATR' => $actualATR,
                                    'atrMultipliers' => $atrMultipliers,
                                    'tpCount' => [
                                        'longTpCount' => $longTpCount,
                                        'shortTpCount' => $shortTpCount,
                                    ],

                                    'actualSupertrend5m' => $actualSupertrend5m,
                                    'actualSupertrend15m' => $actualSupertrend15m,

                                    'actualMacd' => $actualMacd,
                                    'actualImpulsMacd' => $actualImpulsMacd,
                                    'actualMacdDivergence' => $actualMacdDivergence,
                                    'actualStochastic1h' => $actualStochastic1h,
                                    'actualAdx1h' => $actualAdx1h,

                                    'timeMark' => date("H:i"),
                                    'snapTimeMark' => date("H:i"),
                                    'timeFrame' => $timeFrame,
                                    'anomalyOI' => abs($openInterest >= 2),

                                    'leverage' => '10x',
                                    'interval' => $timeFrame,

                                    'oiLimits' => [
                                        'longOiLimit' => $longOiLimit,
                                        'shortOiLimit' => $shortOiLimit
                                    ]
                                ];

                                if (in_array($symbolName, ['BTCUSDT', 'ETHUSDT'])) {
                                    $actualOpportunities['headCoin'][$symbolName] = $opportunityData;
                                    continue;
                                }

                                $analysisSymbolCount++;
                                $analysisSymbols .= $symbolName . ' ';
                                $maDistance = 2.5;

                                //dev
                                /*   if (
                                       $btcInfo['isLong']
                                       && $analyzeVolumeSignalRes['isLong']
                                       && self::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'long')
                                       && self::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'long')
                                       && self::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'long')
                                       && self::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'long')
                                   ) {
                                       $opportunityData['strategy'] = 'dev';
                                       $actualOpportunities['masterPump'][$symbolName] = $opportunityData;
                                   }

                                   if (
                                       $btcInfo['isShort']
                                       && $analyzeVolumeSignalRes['isShort']
                                       && self::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'short')
                                       && self::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'short')
                                       && self::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'short')
                                       && self::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'short')
                                   ) {
                                       $opportunityData['strategy'] = 'dev';
                                       $actualOpportunities['masterPump'][$symbolName] = $opportunityData;
                                   }*/


                                //alerts, master Cross ma
                                if (
                                    $timeFrame == '15m'
                                    && $btcInfo['isLong']
                                    && $analyzeVolumeSignalRes['isLong']
                                    && (($summaryOIBybit >= $longOiLimit) && ($summaryOI >= 0.01))
                                    && (!$actualMacdDivergence['shortDivergenceTypeAr']['regular'] && !$actualMacdDivergence['shortDivergenceTypeAr']['hidden'])
                                    && self::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'long')
                                ) {
                                    if ($crossMA100['isLong']) {
                                        $opportunityData['strategy'] = 'MA100xEMA9/MACD';
                                        $actualOpportunities['masterPump'][$symbolName] = $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                    } else if ($crossMA200['isLong']) {
                                        $opportunityData['strategy'] = 'MA200xEMA9/MACD';
                                        $actualOpportunities['masterPump'][$symbolName] = $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                    } else if ($crossMA400['isLong']) {
                                        $opportunityData['strategy'] = 'MA400xEMA9/MACD';
                                        $actualOpportunities['masterPump'][$symbolName] = $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                    }
                                }

                                if (
                                    $timeFrame == '15m'
                                    && $btcInfo['isShort']
                                    && $analyzeVolumeSignalRes['isShort']
                                    && (($summaryOIBybit <= $shortOiLimit) && ($summaryOI <= -0.01))
                                    && (!$actualMacdDivergence['longDivergenceTypeAr']['regular'] && !$actualMacdDivergence['longDivergenceTypeAr']['hidden'])
                                    && self::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'short')                                ) {
                                    if ($crossMA100['isShort']) {
                                        $opportunityData['strategy'] = 'MA100xEMA9/MACD';
                                    } else if ($crossMA200['isShort']) {
                                        $opportunityData['strategy'] = 'MA200xEMA9/MACD';
                                        $actualOpportunities['masterDump'][$symbolName] = $actualOpportunities['allDump'][$symbolName] = $opportunityData;
                                    } else if ($crossMA400['isShort']) {
                                        $opportunityData['strategy'] = 'MA400xEMA9/MACD';
                                        $actualOpportunities['masterDump'][$symbolName] = $actualOpportunities['allDump'][$symbolName] = $opportunityData;
                                    }
                                }

                                //alerts, master Macd, !divergence, MAfar
                                if (
                                    $actualMacd['isLong']
                                    && $btcInfo['isLong']
                                    && $analyzeVolumeSignalRes['isLong']
                                    && (!$actualMacdDivergence['shortDivergenceTypeAr']['regular'] && !$actualMacdDivergence['shortDivergenceTypeAr']['hidden'])
                                    && (($summaryOIBybit >= $longOiLimit) && ($summaryOI >= 0.01))                                    && self::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'long')
                                ) {
                                    $opportunityData['strategy'] = 'macd/!d/MAfar';
                                    $actualOpportunities['masterPump'][$symbolName] = $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                }

                                if (
                                    $actualMacd['isShort']
                                    && $btcInfo['isShort']
                                    && $analyzeVolumeSignalRes['isShort']
                                    && (!$actualMacdDivergence['longDivergenceTypeAr']['regular'] && !$actualMacdDivergence['longDivergenceTypeAr']['hidden'])
                                    && (($summaryOIBybit <= $shortOiLimit) && ($summaryOI <= -0.01))
                                    && self::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'short')
                                ) {
                                    $opportunityData['strategy'] = 'macd/!d/MAfar';
                                    $actualOpportunities['masterDump'][$symbolName] = $actualOpportunities['allDump'][$symbolName] = $opportunityData;
                                }

                                //alerts, master MacdI, !divergence, direct, MAfar
                                if (
                                    $actualImpulsMacd['isLong']
                                    && $timeFrame != '30m'
                                    && $btcInfo['isLong']
                                    && $analyzeVolumeSignalRes['isLong']
                                    && (!$actualMacdDivergence['shortDivergenceTypeAr']['regular'] && !$actualMacdDivergence['shortDivergenceTypeAr']['hidden'])
                                    && (($summaryOIBybit >= $longOiLimit) && ($summaryOI >= 0.01))                                    && self::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'long')
                                ) {
                                    $opportunityData['strategy'] = 'macdI/direct/!d/MAfar';
                                    $actualOpportunities['masterPump'][$symbolName] = $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                }

                                if (
                                    $actualImpulsMacd['isShort']
                                    && $timeFrame != '30m'
                                    && $btcInfo['isShort']
                                    && $analyzeVolumeSignalRes['isShort']
                                    && (!$actualMacdDivergence['longDivergenceTypeAr']['regular'] && !$actualMacdDivergence['longDivergenceTypeAr']['hidden'])
                                    && (($summaryOIBybit <= $shortOiLimit) && ($summaryOI <= -0.01))
                                    && self::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'short')
                                ) {
                                    $opportunityData['strategy'] = 'macdI/direct/!d/MAfar';
                                    $actualOpportunities['masterDump'][$symbolName] = $actualOpportunities['allDump'][$symbolName] = $opportunityData;
                                }

                                //alerts, master, macd cross and divergence
                                if (
                                    $actualMacd['isLong']
                                    && $btcInfo['isLong']
                                    && $analyzeVolumeSignalRes['isLong']
                                    && (($summaryOIBybit >= $longOiLimit) && ($summaryOI >= 0.01))                                    && self::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'long')
                                    && self::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'long')
                                    && (!$actualMacdDivergence['shortDivergenceTypeAr']['regular'] && !$actualMacdDivergence['shortDivergenceTypeAr']['hidden'])
                                    && ($actualMacdDivergence['longDivergenceTypeAr']['regular'] || $actualMacdDivergence['longDivergenceTypeAr']['hidden'])
                                ) {
                                    $opportunityData['strategy'] = 'macdD/macdC/MAfar';
                                    $actualOpportunities['allPump'][$symbolName] = $actualOpportunities['masterPump'][$symbolName] = $opportunityData;
                                }

                                if (
                                    $actualMacd['isShort']
                                    && $btcInfo['isShort']
                                    && $analyzeVolumeSignalRes['isShort']
                                    && (($summaryOIBybit <= $shortOiLimit) && ($summaryOI <= -0.01))
                                    && self::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'short')
                                    && self::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'short')
                                    && (!$actualMacdDivergence['longDivergenceTypeAr']['regular'] && !$actualMacdDivergence['longDivergenceTypeAr']['hidden'])
                                    && ($actualMacdDivergence['shortDivergenceTypeAr']['regular'] || $actualMacdDivergence['shortDivergenceTypeAr']['hidden'])
                                ) {
                                    $opportunityData['strategy'] = 'macdD/macdC/MAfar';
                                    $actualOpportunities['allDump'][$symbolName] = $actualOpportunities['masterDump'][$symbolName] = $opportunityData;
                                }

                            } else {
                                devlogs('ERR ' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                continue;
                            }
                            $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['timeMark'] = date("H:i");
                            $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['timeStamp'] = time();
                            $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['timeFrame'] = $timeFrame;
                        }

                    } else {
                        devlogs('ERR ' . $symbolName . ' | err - OI' . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                    }

                } catch (Exception $e) {
                    devlogs('ERR ' . $symbolName . ' countReq - ' . $countReq . ' | err text - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                }

                $res['symbols'][$symbolName] = $symbol;
                usleep(10000);
            }

            /*if ($devMode && $countReq >= 50)
                break;*/
        }
        unset($symbol);

        try {
            uasort($actualOpportunities['allPump'], function ($a, $b) {
                return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
            });
            uasort($actualOpportunities['allDump'], function ($a, $b) {
                return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
            });
        } catch (Exception $e) {
            devlogs('ERR - alert sort Err | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
        }

        try {
            uasort($actualOpportunities['masterPump'], function ($a, $b) {
                return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
            });

            uasort($actualOpportunities['masterDump'], function ($a, $b) {
                return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
            });
        } catch (Exception $e) {
            devlogs('ERR - alfa Err | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
        }

        $cntInfo = [
            'count' => $countReq,
            'analysisCount' => $analysisSymbolCount,
            'analysisSymbols' => $analysisSymbols,
        ];
        devlogs('analysis symbols count  - ' . $analysisSymbolCount, $marketCode . '/bybitExchange' . $timeFrame);
        devlogs('analysis symbols   - ' . $analysisSymbols, $marketCode . '/bybitExchange' . $timeFrame);
        devlogs('count symbols - ' . $countReq, $marketCode . '/bybitExchange' . $timeFrame);

        $timeMark = date("d.m.y H:i:s");
        devlogs('step2 - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);

        //exchangeResponse
        $exchangeResponse = [
            "TIMEMARK" => $timeMark,
            "RESPONSE_EXCHENGE" => $res,
            "EXCHANGE_CODE" => 'bybit',
            'TIMEFRAME' => $timeFrame
        ];
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/exchangeResponse.json', json_encode($exchangeResponse));

        //marketVolumes
        $marketVolumesJson['TIMEMARK'] = $timeMark;
        $marketVolumesJson['EXCHANGE_CODE'] = 'bybit';
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/marketVolumes.json', json_encode($marketVolumesJson));

        //TP SL
        $unsetSymbols = [];
        if ($actualOpportunities['masterPump'] || $actualOpportunities['masterDump']) {
            foreach ($actualOpportunities['masterPump'] as &$pump) {
                // Помечаем направление
                $pump['isLong'] = true;
                // Сбрасываем старые поля
                $pump['SL'] = $pump['TP'] = $pump['recommendedEntry'] = false;

                // Вызываем единый метод обработки сигнала
                //$btcInfo['risk'] = 4.5;
                $processed = self::processSignal(
                    'long',
                    floatval($pump['actualATR']['atr']),
                    floatval($pump['actualClosePrice']),
                    $pump['candles15m'],
                    $pump['actualSupertrend5m'],
                    $pump['actualSupertrend15m'],
                    $pump['actualMacdDivergence'],
                    $pump['symbolScale'],
                    $pump['atrMultipliers'],
                    $btcInfo,
                    $pump['symbolName'],
                    "$marketCode/bybitExchange$timeFrame"
                );

                if ($processed === false) {
                    $unsetSymbols[] = $pump['symbolName'];
                    continue;
                }

                // Вешаем на $pump всё, что вернула функция
                $pump = array_merge($pump, $processed);
            }
            unset($pump);
            // Удаляем те символы, что не прошли по риску
            foreach ($unsetSymbols as $symbol) {
                if (isset($actualOpportunities['masterPump'][$symbol])) {
                    unset($actualOpportunities['masterPump'][$symbol]);
                }
            }

            $unsetSymbols = [];
            foreach ($actualOpportunities['masterDump'] as &$dump) {
                $dump['isLong'] = false;
                $dump['SL'] = $dump['TP'] = $dump['recommendedEntry'] = false;

                // То же самое, но передаём direction = 'short'
                //$btcInfo['risk'] = 4.5;
                $processed = self::processSignal(
                    'short',
                    floatval($dump['actualATR']['atr']),
                    floatval($dump['actualClosePrice']),
                    $dump['candles15m'],
                    $dump['actualSupertrend5m'],
                    $dump['actualSupertrend15m'],
                    $dump['actualMacdDivergence'],
                    $dump['symbolScale'],
                    $dump['atrMultipliers'],
                    $btcInfo,
                    $dump['symbolName'],
                    "$marketCode/bybitExchange$timeFrame"
                );

                if ($processed === false) {
                    $unsetSymbols[] = $dump['symbolName'];
                    continue;
                }

                $dump = array_merge($dump, $processed);
            }
            foreach ($unsetSymbols as $symbol) {
                if (isset($actualOpportunities['masterDump'][$symbol])) {
                    unset($actualOpportunities['masterDump'][$symbol]);
                }
            }
        }

        devlogs('step3 - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);

        $infoAr = [
            'DELISTING' => $delistingAnnouncements ?? [],
            'REPEAT_SYMBOLS' => $latestSignals['repeatSymbols'] ?? [],
            'BTC_INFO' => $btcInfo ?? [],
        ];

        //actualMarketVolumes
        $timeMark = date("d.m.y H:i:s");
        $actualMarketVolumes = [
            "TIMEMARK" => $timeMark,
            "STRATEGIES" => $actualOpportunities,
            'INFO' => $infoAr,
            "EXCHANGE_CODE" => 'bybit'
        ];

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/actualMarketVolumes.json', json_encode($actualMarketVolumes));

        //alfa/master
        if (!$devMode && ($actualOpportunities['masterPump'] || $actualOpportunities['masterDump'])) {
            \Maksv\DataOperation::sendSignalMessage($actualOpportunities['masterPump'], $actualOpportunities['masterDump'], $btcInfo, '@infoCryptoHelperDev', $timeFrame, $infoAr);
            try {
                $writeRes = \Maksv\DataOperation::saveSignalToIblock($timeFrame, 'bybit', 'master');
                devlogs($timeFrame . ' master write' . $writeRes['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
            } catch (Exception $e) {
                devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
            }
        }
        //

        //alerts
        if ($timeFrame == '15m') {
            \Maksv\DataOperation::sendInfoMessage($actualOpportunities, $timeFrame, $btcInfo, $cntInfo);
            if ($actualOpportunities['allPump'] || $actualOpportunities['allDump']) {
                try {
                    $writeRes = \Maksv\DataOperation::saveSignalToIblock($timeFrame, 'bybit', 'alerts');
                    devlogs($timeFrame . ' alerts write' . $writeRes['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                } catch (Exception $e) {
                    devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                }
            }
        }

        devlogs('end - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
        devlogs('_____________________________________', $marketCode . '/bybitExchange' . $timeFrame);
        $bybitApiOb->closeConnection();
        $binanceApiOb->closeConnection();
        $okxApiOb->closeConnection();

        return "bybitExchange" . $timeFrame . "();";
    }

    public static function getMarketInfo($symbol = 'others')
    {
        $devlogsCode = 'getMarketInfo';

        //избегаем одновременного вызова
        $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/traydingviewExchange/timestap.json'), true);
        if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 15)) {
            sleep(3);
        } else {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/traydingviewExchange/timestap.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => date("d.m.y H:i:s")]));
        }

        $cacheID = md5($devlogsCode. 'Cache|' . $symbol);
        $cache = \Bitrix\Main\Data\Cache::createInstance();

        if ($cache->initCache(35, $cacheID)) {
            $res = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            devlogs('start no cache.  timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);

            $backtraceStr = '';
            $backtraceAr = debug_backtrace();
            foreach ($backtraceAr as $key => $backtrace) {
                if ($backtrace['function'])
                    $backtraceStr .= ($key+1) . '. func ' . $backtrace['function'] . ' (' . $backtrace['line'] .  ')' . "\n";
                else
                    $backtraceStr .= ($key+1) . '. file ' . $backtrace['file'] . ' (' . $backtrace['line'] .  ')' . "\n";
            }

            $exec = new \Maksv\Traydingview\RequestExecutor();
            if (!$exec->execute($symbol)) {
                $errText = 'get others err, watch py script';
                $res['err'][] = 'get others, watch py script';
                \Maksv\DataOperation::sendErrorInfoMessage($errText, $backtraceStr, 'getMarketInfo');
                return $res;
            }

            $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/traydingviewExchange/total_ex_top10.json';
            $marketData = json_decode(file_get_contents($path), true) ?? [];
            $timestamp = $marketData['timestamp'] ?? 0;

            if (time() - $timestamp > 300) { // 5 минут = 300 секунд
                $errText = 'Data is older than 5 minutes';
                $res['err'][] = $errText;
                \Maksv\DataOperation::sendErrorInfoMessage($errText, $backtraceStr, 'getMarketInfo');
                return $res;
            }

            $res['marketReadDif'] = time() - $timestamp;
            $res['marketReadDifRule'] = time() - $timestamp > 100;
            $marketKlines = $marketData['data'];

            $klineList5m = $marketKlines['5m'] ?? [];
            if ($klineList5m && is_array($klineList5m) && count($klineList5m) > 80) {
                $candles5m = array_map(function ($k) {
                    return [
                        't' => floatval($k['datetime']), // timestap
                        'o' => floatval($k['open']), // Open price
                        'h' => floatval($k['high']), // High price
                        'l' => floatval($k['low']), // Low price
                        'c' => floatval($k['close']), // Close price
                        'v' => floatval($k['volume'])  // Volume
                    ];
                }, $klineList5m);

                try {
                    $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($candles5m, 10, 3) ?? false; // длина 10, фактор 3
                    $res['actualSupertrend5m'] = $actualSupertrend5m = $supertrendData[array_key_last($supertrendData) - 1] ?? false;
                } catch (Exception $e) {
                    devlogs('ERR | err - Supertrend 5m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $macdData5m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles5m, 12, 'EMA', 26, 'EMA', 9, 'EMA', 8, 'histogram') ?? false;
                    if ($macdData5m && is_array($macdData5m))
                        $res['actualMacd5m'] = $actualMacd5m = $macdData5m[array_key_last($macdData5m)];
                    unset($res['actualMacd5m']['extremes']);
                } catch (Exception $e) {
                    devlogs('ERR | err - macd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $stochasticOscillatorData5m = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles5m) ?? false;
                    if ($stochasticOscillatorData5m && is_array($stochasticOscillatorData5m))
                        /* $res['actualStochastic5m'] = */$actualStochastic5m = $stochasticOscillatorData5m[array_key_last($stochasticOscillatorData5m)];
                } catch (Exception $e) {
                    devlogs('ERR | err - stoch 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $actualMacdDivergence5m = self::checkMultiMACD(
                        $candles5m,
                        '5m',
                        ['5m' => 16, '15m' => 16, '30m' => 5, '1h' => 5, '4h' => 8, '1d' => 8],
                    );

                    $res['longDivergenceVal5m'] = $res['shortDivergenceVal5m'] = false;
                    if ($actualMacdDivergence5m['longDivergenceTypeAr']['regular']) {
                        $res['longDivergenceVal5m'] = true;
                        $res['longDivergenceText5m'] = 'oth bullish dever ' . $actualMacdDivergence5m['inputParams'] . ' (' . $actualMacdDivergence5m['longDivergenceDistance'] . '), 5m';
                    }

                    if ($actualMacdDivergence5m['shortDivergenceTypeAr']['regular']) {
                        $res['shortDivergenceVal5m'] = true;
                        $res['shortDivergenceText5m'] = 'oth bearish dever ' . $actualMacdDivergence5m['inputParams'] . ' (' . $actualMacdDivergence5m['shortDivergenceDistance'] . '), 5m';
                    }

                    //unset( $res['actualMacdDivergence5m']['extremes']);
                } catch (Exception $e) {
                    devlogs('ERR | err - checkMultiMACD 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $adxData5m = \Maksv\TechnicalAnalysis::calculateADX($candles5m);
                    $res['actualAdx5m'] = $actualAdx5m = $adxData5m[array_key_last($adxData5m)];
                } catch (Exception $e) {
                    devlogs('ERR | err - actualAdx5m 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $impulseMACD5m = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles5m) ?? false;
                    if ($impulseMACD5m && is_array($impulseMACD5m))
                        $res['actualImpulsMacd5m'] = $actualImpulsMacd5m = $impulseMACD5m[array_key_last($impulseMACD5m)];
                } catch (Exception $e) {
                    devlogs('ERR | err - actualImpulsMacd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }
            }


            $klineList15m = $marketKlines['15m'] ?? [];
            if ($klineList15m && is_array($klineList15m) && count($klineList15m) > 80) {

                $actualKline15m = $klineList15m[array_key_last($klineList15m)] ?? false;
                if ($actualKline15m)
                    $res['actualClosePrice15m'] = floatval($actualKline15m['close']);

                $candles15m = array_map(function ($k) {
                    return [
                        't' => floatval($k['datetime']), // timestap
                        'o' => floatval($k['open']), // Open price
                        'h' => floatval($k['high']), // High price
                        'l' => floatval($k['low']), // Low price
                        'c' => floatval($k['close']), // Close price
                        'v' => floatval($k['volume'])  // Volume
                    ];
                }, $klineList15m);

                $res['last30Candles15m'] = array_slice($candles15m, -30);

                try {
                    $impulseMACD15m = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles15m, 34, 9, 3) ?? false;
                    if ($impulseMACD15m && is_array($impulseMACD15m))
                        $res['actualImpulsMacd15m'] = $actualImpulsMacd15m = $impulseMACD15m[array_key_last($impulseMACD15m)];
                } catch (Exception $e) {
                    devlogs('ERR | err - actualImpulsMacd 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                $screenerData['actualMacd'] = $actualMacd = [];
                try {
                    $macdSimpleData15m = \Maksv\TechnicalAnalysis::analyzeMACD($candles15m) ?? false;
                    $res['actualSimpleMacd15m'] = $actualSimpleMacd15m = $macdSimpleData15m[array_key_last($macdSimpleData15m)] ?? false;
                } catch (Exception $e) {
                    devlogs('ERR | err - actualSimpleMacd15m 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $res['actualMacdDivergence15m'] = $actualMacdDivergence15m = self::checkMultiMACD(
                        $candles15m,
                        '15m',
                        ['5m' => 16, '15m' => 13, '30m' => 11, '1h' => 5, '4h' => 8, '1d' => 8],
                    );

                    $res['longDivergenceVal15m'] = $res['shortDivergenceVal15m'] = false;
                    if ($actualMacdDivergence15m['longDivergenceTypeAr']['regular']) {
                        $res['longDivergenceVal15m'] = true;
                        $res['longDivergenceText15m'] = 'oth bullish dever ' . $actualMacdDivergence15m['inputParams'] . ' (' . $actualMacdDivergence15m['longDivergenceDistance'] . '), 15m';

                    }

                    if ($actualMacdDivergence15m['shortDivergenceTypeAr']['regular']) {
                        $res['shortDivergenceVal15m'] = true;
                        $res['shortDivergenceText15m'] = 'oth bearish dever ' . $actualMacdDivergence15m['inputParams'] . ' (' . $actualMacdDivergence15m['shortDivergenceDistance'] . '), 15m';
                    }

                    //unset( $res['actualMacdDivergence15m']['extremes']);
                } catch (Exception $e) {
                    devlogs('ERR | err - checkMultiMACD ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($candles15m, 10, 3) ?? false; // длина 10, фактор 3
                    $res['actualSupertrend15m'] = $actualSupertrend15m = $supertrendData[array_key_last($supertrendData) - 1] ?? false;
                } catch (Exception $e) {
                    devlogs('ERR | err - Supertrend' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    //$macdData15m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles15m, 5, 'SMA', 35, 'SMA', 5,'SMA', 8, 'macdLine') ?? false;
                    $macdData15m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles15m, 12, 'EMA', 26, 'EMA', 9, 'EMA', 8, 'histogram') ?? false;
                    if ($macdData15m && is_array($macdData15m)) {
                        $res['actualMacd15m'] = $actualMacd15m = $macdData15m[array_key_last($macdData15m)];
                        unset($res['actualMacd15m']['extremes']);
                    }
                } catch (Exception $e) {
                    devlogs('ERR | err - macd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $stochasticOscillatorData15m = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles15m) ?? false;
                    if ($stochasticOscillatorData15m && is_array($stochasticOscillatorData15m))
                        $res['actualStochastic15m'] = $actualStochastic15m = $stochasticOscillatorData15m[array_key_last($stochasticOscillatorData15m)];

                } catch (Exception $e) {
                    devlogs('ERR | err - stoch 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $adxData15m = \Maksv\TechnicalAnalysis::calculateADX($candles15m);
                    $res['actualAdx15m'] = $actualAdx15m = $adxData15m[array_key_last($adxData15m)];
                } catch (Exception $e) {
                    devlogs('ERR | err - actualAdx5m 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $atrData15m = \Maksv\TechnicalAnalysis::calculateATR($candles15m);
                    $res['actualAtr15m'] = $actualAtr15m = $atrData15m[array_key_last($atrData15m)];
                } catch (Exception $e) {
                    devlogs('ERR | err - actualAtr15m 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $res['ma100_15m'] = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 9, 100, 20, 2) ?? [];
                } catch (Exception $e) {
                    devlogs('ERR | err - ma100 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $res['ma200_15m'] = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 9, 200, 20, 2) ?? [];
                } catch (Exception $e) {
                    devlogs('ERR | err - ma200 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $ATRData = \Maksv\TechnicalAnalysis::calculateATR($candles15m);
                    $res['actualATR'] = $ATRData[array_key_last($ATRData)] ?? null;
                } catch (Exception $e) {
                    devlogs('ERR | err - atr ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $res['fibonacciLevels15m'] = \Maksv\TechnicalAnalysis::buildFibonacciLevels($candles15m, 8);

                } catch (Exception $e) {
                    devlogs('ERR | err - fiba 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }
            }

            $klineList1h = $marketKlines['1h'] ?? [];
            if ($klineList1h && is_array($klineList1h) && count($klineList1h) > 80) {
                $candles1h = array_map(function ($k) {
                    return [
                        't' => floatval($k['datetime']), // timestap
                        'o' => floatval($k['open']), // Open price
                        'h' => floatval($k['high']), // High price
                        'l' => floatval($k['low']), // Low price
                        'c' => floatval($k['close']), // Close price
                        'v' => floatval($k['volume'])  // Volume
                    ];
                }, $klineList1h);

               /* try {
                    //$firstNCandles =  array_slice($candles4h, 0, 250);
                    $res['fibonacciLevels1h'] = \Maksv\TechnicalAnalysis::buildFibonacciLevels($candles1h, 8);

                } catch (Exception $e) {
                    devlogs('ERR | err - fiba 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }*/

                try {

                    /*$res['actualMacdDivergence1h'] =*/ $actualMacdDivergence1h = self::checkMultiMACD(
                        $candles1h,
                        '1h',
                        ['5m' => 16, '15m' => 16, '30m' => 11, '1h' => 6, '4h' => 8, '1d' => 8],
                    );

                    $res['longDivergenceVal1h'] = $res['shortDivergenceVal1h'] = false;
                    if ($actualMacdDivergence1h['longDivergenceTypeAr']['regular']) {
                        $res['longDivergenceVal1h'] = true;
                        $res['longDivergenceText1h'] = 'oth bullish dever ' . $actualMacdDivergence1h['inputParams'] . ' (' . $actualMacdDivergence1h['longDivergenceDistance'] . '), 1h';

                    }

                    if ($actualMacdDivergence1h['shortDivergenceTypeAr']['regular']) {
                        $res['shortDivergenceVal1h'] = true;
                        $res['shortDivergenceText1h'] = 'oth bearish dever ' . $actualMacdDivergence1h['inputParams'] . ' (' . $actualMacdDivergence1h['shortDivergenceDistance'] . '), 1h';
                    }

                    //unset( $res['actualMacdDivergence1h']['extremes']);
                } catch (Exception $e) {
                    devlogs('ERR | err - checkMultiMACD 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $stochasticOscillatorData1h = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles1h) ?? false;
                    if ($stochasticOscillatorData1h && is_array($stochasticOscillatorData1h))
                        $res['actualStochastic1h'] = $actualStochastic1h = $stochasticOscillatorData1h[array_key_last($stochasticOscillatorData1h)];

                } catch (Exception $e) {
                    devlogs('ERR | err - stoch 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $adxData1h = \Maksv\TechnicalAnalysis::calculateADX($candles1h);
                    $res['actualAdx1h'] = $actualAdx1h = $adxData1h[array_key_last($adxData1h)];
                } catch (Exception $e) {
                    devlogs('ERR | err - actualAdx5m 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $res['ma100_1h'] = \Maksv\TechnicalAnalysis::checkMACross($candles1h, 9, 100, 20, 2) ?? [];
                } catch (Exception $e) {
                    devlogs('ERR | err - ma100 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $impulseMACD1h = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles1h) ?? false;
                    if ($impulseMACD1h && is_array($impulseMACD1h))
                        $res['actualImpulsMacd1h'] = $actualImpulsMacd1h = $impulseMACD1h[array_key_last($impulseMACD1h)];
                } catch (Exception $e) {
                    devlogs('ERR | err - actualImpulsMacd 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }
            }

            $klineList4h = $marketKlines['4h'] ?? [];
            if ($klineList4h && is_array($klineList4h) && count($klineList4h) > 80) {
                $candles4h = array_map(function ($k) {
                    return [
                        't' => floatval($k['datetime']), // timestap
                        'o' => floatval($k['open']), // Open price
                        'h' => floatval($k['high']), // High price
                        'l' => floatval($k['low']), // Low price
                        'c' => floatval($k['close']), // Close price
                        'v' => floatval($k['volume'])  // Volume
                    ];
                }, $klineList4h);

                try {
                    $impulseMACD4h = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles4h, 34, 9, 3) ?? false;
                    if ($impulseMACD4h && is_array($impulseMACD4h))
                        $res['actualImpulsMacd4h'] = $actualImpulsMacd4h = $impulseMACD4h[array_key_last($impulseMACD4h)];
                } catch (Exception $e) {
                    devlogs('ERR | err - actualImpulsMacd  4h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    $res['ma100_4h'] = \Maksv\TechnicalAnalysis::checkMACross($candles4h, 9, 100, 20, 2) ?? [];
                } catch (Exception $e) {
                    devlogs('ERR | err - ma100 4h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }

                try {
                    //$firstNCandles =  array_slice($candles4h, 0, 250);
                    $res['fibonacciLevels4h'] = \Maksv\TechnicalAnalysis::buildFibonacciLevels($candles4h, 8);

                } catch (Exception $e) {
                    devlogs('ERR | err - fiba 4h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                }
            }

            devlogs('end no cache.  timeMark - ' . date("d.m.y H:i:s") . '_________________________', $devlogsCode);
            $cache->endDataCache($res);
        }

        return $res;
    }

    //#рынок //#others
    public static function checkMarketImpulsInfo()
    {
        $infoText = false;
        $res['isLong'] = $res['isShort'] = false;
        $res['atrMultipliers'] = false;
        $res['shortTpCount'] = $res['longTpCount'] = 1;
        $res['risk'] = 6;
        $maDistance = 2.5;

        $res['marketImpulsInfo'] = $marketImpulsInfo = self::getMarketInfo();
        $res['btcImpulsInfo'] = $btcImpulsInfo = self::checkBtcImpulsInfo();

        $infoText .= "\nmarket info:\n\n";
        //macd trend 5m text
        $marketTrendBoard = 150000000;
        $marketImpulsMacdVal = 210000000;
        $marketImpulseMacdTrendBoardVal = 100000000;
        $marketStrongImpulsMacdVal = 650000000;
        $marketImpulsRSIVal = 8;
        $atrBoard = 1600000000;

        $marketMidImpulsBoard = 2000000000;
        $marketImpulsBoard = 2500000000;
        //$marketImpulsBoard = 2900000000;

        $res['mlBoard'] = 0.72;
        //$res['mlMarketBoard'] = 0.7;

        //market impuls macd 4h text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd4h']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd4h']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd4h']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd4h']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd4h']['trend']['trendVal'] . '), 4h' . "\n";

        //market impuls macd 1h text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd1h']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd1h']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd1h']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd1h']['trend']['trendVal'] . '), 1h' . "\n";
        
        //market impuls macd 15m text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd15m']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd15m']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd15m']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd15m']['trend']['trendVal'] . '), 15m' . "\n";
        //market impuls macd 5m text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd5m']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd5m']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd5m']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd5m']['trend']['trendVal'] . '), 5m' . "\n\n";

        //adx 1h text
        $infoText .= 'adx trend ' . $marketImpulsInfo['actualAdx1h']['trendDirection']['trendDir'] . ', dir ' . $marketImpulsInfo['actualAdx1h']['adxDirection']['adxDir'] . ' (' . round($marketImpulsInfo['actualAdx1h']['adx'], 2) . '), 1h' . "\n";
        //adx 15m text
        $infoText .= 'adx trend ' . $marketImpulsInfo['actualAdx15m']['trendDirection']['trendDir'] . ', dir ' . $marketImpulsInfo['actualAdx15m']['adxDirection']['adxDir'] . ' (' . round($marketImpulsInfo['actualAdx15m']['adx'], 2) . '), 15m' . "\n";
        //adx 5m text
        $infoText .= 'adx trend ' . $marketImpulsInfo['actualAdx5m']['trendDirection']['trendDir'] . ', dir ' . $marketImpulsInfo['actualAdx5m']['adxDirection']['adxDir'] . ' (' . round($marketImpulsInfo['actualAdx5m']['adx'], 2) . '), 5m' . "\n\n";

        //stoch 1h text ($marketImpulsInfo['actualStochastic1h']['%K']
        $infoText .= 'stoch hist ' . round($marketImpulsInfo['actualStochastic1h']['hist'], 2) . ' (' . round($marketImpulsInfo['actualStochastic1h']['%K'], 2) . ', ' . round($marketImpulsInfo['actualStochastic1h']['%D'], 2) . '), 1h' . "\n";
        //stoch 15m text
        $infoText .= 'stoch hist ' . round($marketImpulsInfo['actualStochastic15m']['hist'], 2) . ' (' . round($marketImpulsInfo['actualStochastic15m']['%K'], 2) . ', ' . round($marketImpulsInfo['actualStochastic15m']['%D'], 2) . '), 15m' . "\n";

        //market
        if ($marketImpulsInfo['longDivergenceVal1h'] && $marketImpulsInfo['longDivergenceText1h'])
            $infoText .= $marketImpulsInfo['longDivergenceText1h'] . "\n";

        if ($marketImpulsInfo['shortDivergenceVal1h'] && $marketImpulsInfo['shortDivergenceText1h'])
            $infoText .= $marketImpulsInfo['shortDivergenceText1h'] . "\n";

        if ($marketImpulsInfo['longDivergenceVal15m'] && $marketImpulsInfo['longDivergenceText15m'])
                    $infoText .= $marketImpulsInfo['longDivergenceText15m'] . "\n";

       if ($marketImpulsInfo['shortDivergenceVal15m'] && $marketImpulsInfo['shortDivergenceText15m'])
                $infoText .= $marketImpulsInfo['shortDivergenceText15m'] . "\n";

        if ($marketImpulsInfo['longDivergenceVal5m'] && $marketImpulsInfo['longDivergenceText5m'])
            $infoText .= $marketImpulsInfo['longDivergenceText5m'] . "\n";

        if ($marketImpulsInfo['shortDivergenceVal5m'] && $marketImpulsInfo['shortDivergenceText5m'])
            $infoText .= $marketImpulsInfo['shortDivergenceText5m'] . "\n";

        //btc
        if ($btcImpulsInfo['longDivergenceVal15m'] && $btcImpulsInfo['longDivergenceText15m'])
            $infoText .= $btcImpulsInfo['longDivergenceText15m'] . "\n";

        if ($btcImpulsInfo['shortDivergenceVal15m'] && $btcImpulsInfo['shortDivergenceText15m'])
            $infoText .= $btcImpulsInfo['shortDivergenceText15m'] . "\n";

        if ($btcImpulsInfo['longDivergenceVal5m'] && $btcImpulsInfo['longDivergenceText5m'])
            $infoText .= $btcImpulsInfo['longDivergenceText5m'] . "\n";

        if ($btcImpulsInfo['shortDivergenceVal5m'] && $btcImpulsInfo['shortDivergenceText5m'])
            $infoText .= $btcImpulsInfo['shortDivergenceText5m'] . "\n";


        if (
            (
                $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketImpulseMacdTrendBoardVal
                || ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketMidImpulsBoard)
            )
            && (
                $marketImpulsInfo['actualImpulsMacd15m']['trend']['longDirection']
                && ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= $marketImpulsInfo['actualImpulsMacd15m']['signal_line'])
            )
            //&& $marketImpulsInfo['actualImpulsMacd5m']['trend']['longDirection']
            //&& ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > ($marketImpulsMacdVal / 5))
            //&& ($marketImpulsInfo['actualImpulsMacd15m']['histogram'] > ($marketImpulsMacdVal / 4))
        ) {
            $res['isLong'] = true;
            $res['mlBoard'] = 0.73;
            $res['atrMultipliers'] = [2.6, 3.0, 3.5];
            $res['risk'] = 4.1;
        } else if (
            (
                $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulseMacdTrendBoardVal
                || ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] < -$marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketMidImpulsBoard)
            )
            && (
                $marketImpulsInfo['actualImpulsMacd15m']['trend']['shortDirection']
                && ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= $marketImpulsInfo['actualImpulsMacd15m']['signal_line'])
            )
            //&& $marketImpulsInfo['actualImpulsMacd5m']['trend']['shortDirection']
            //&& ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] < -($marketImpulsMacdVal / 5))
            //&& ($marketImpulsInfo['actualImpulsMacd15m']['histogram'] < -($marketImpulsMacdVal / 4))

        ) {
            $res['isShort'] = true;
            $res['atrMultipliers'] = [2.6, 3.0, 3.5];
            $res['risk'] = 4.1;
        }

        // risk/profit rules long
        if ($res['isLong']) {

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= 0 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 400000000) {
                $res['risk'] = 3.8;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 15m\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 3.8;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " down adx 1h\n";
            }

          /*  if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] >= 2000000000) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 1h\n";
            }*/

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] >= 6000000000) {
                $res['risk'] = 3.5;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= 4000000000) {
                $res['risk'] = 3.5;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 15m\n";
            }

            if (
                $marketImpulsInfo['actualStochastic15m']['%K'] <= 54
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m stoch trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 0) {
                $res['risk'] = 3.5;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " down trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > 0 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 400000000) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 5m\n";
            }

            if ($marketImpulsInfo['shortDivergenceVal5m'] || $marketImpulsInfo['shortDivergenceVal15m']
                || $btcImpulsInfo['shortDivergenceVal5m'] || $btcImpulsInfo['shortDivergenceVal15m']
                || $marketImpulsInfo['shortDivergenceVal1h']
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " diver\n";
            }

            if (
                ($marketImpulsInfo['actualStochastic1h']['%K'] <= 53 && $marketImpulsInfo['actualStochastic1h']['hist'] < 4)
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 1h stoch trend\n";
            }


            if (
                $marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] == 0
                || $marketImpulsInfo['actualImpulsMacd4h']['impulse_macd'] == 0
            ) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 4h 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > 0 && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] <= ($marketImpulsMacdVal / 2)) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 5m\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_4h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 4h ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_1h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 1h ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma200_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 200 close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] > 6500000000) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > 0 && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] <= ($marketImpulsMacdVal / 2)) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist\n";
            }

            if (
                $marketImpulsInfo['actualAdx5m']['adx'] < 22
                || ($marketImpulsInfo['actualAdx5m']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx5m']['adx'] < 27)
            ) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 5m\n";
            }


            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx1h']['adx'] < 25) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $res['mlBoard'] = 0.73;
                $infoText .= 'risk ' . $res['risk'] . " down + low adx 1h (25)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 22) {
                $res['risk'] = 3;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (22)\n";
            }

            if (
                $marketImpulsInfo['actualAdx15m']['adx'] < 25
                && ($marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir'])
            ) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 15m\n";
            }

            if (abs($marketImpulsInfo['actualImpulsMacd1h']['histogram']) < 100000000) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 1h\n";
            }

            if (
                $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] == 0
            ) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 15m\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 22) {
                $res['risk'] = 2.4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (22)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 19 && $marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 1h (19)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 15) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (15)\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketMidImpulsBoard) {
                $res['risk'] = 2.4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 5m\n";
            }

           /* if ($marketImpulsInfo['actualAdx1h']['adx'] < 22 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > ($marketMidImpulsBoard / 2)) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                //$res['mlMarketBoard'] = 0.7;
                $infoText .= 'risk ' . $res['risk'] . " low adx + high impuls 5m\n";
            }*/

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > -500000000 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 0) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " trend close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > -750000000 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 0) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " 15m trend close\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 17 && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']) {
                $res['risk'] = 2;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (17)\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketImpulsBoard && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] < ($marketStrongImpulsMacdVal * 1.5)) { //btc. (others board ~ 2 900 000 000)
                $res['risk'] = 2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 2\n";
            }

        }

        // risk/profit rules short
        if ($res['isShort']) {

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= 0 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > -400000000) {
                $res['risk'] = 3.8;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 15m\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 3.8;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " down adx 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] <= -6000000000) {
                $res['risk'] = 3.5;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= -4000000000) {
                $res['risk'] = 3.5;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " high impuls macd line 15m\n";
            }

            if ($marketImpulsInfo['actualStochastic15m']['%K'] >= 47
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m stoch trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > 0) {
                $res['risk'] = 3.5;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " up trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 0 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > -400000000) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 5m\n";
            }

            if ($marketImpulsInfo['longDivergenceVal5m'] || $marketImpulsInfo['longDivergenceVal15m']
                || $btcImpulsInfo['longDivergenceVal5m'] || $btcImpulsInfo['longDivergenceVal15m']
                || $marketImpulsInfo['longDivergenceVal1h']
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " diver\n";
            }

            if (
                $marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] == 0
                || $marketImpulsInfo['actualImpulsMacd4h']['impulse_macd'] == 0
            ) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 4h 1h\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_4h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 4h ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_1h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 1h ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma200_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 200 close\n";
            }

            if (
                ($marketImpulsInfo['actualStochastic1h']['%K'] >= 47 && $marketImpulsInfo['actualStochastic1h']['hist'] > -4)
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 1h stoch trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] < -6500000000) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] < 0 && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] >= -($marketImpulsMacdVal / 2)) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist\n";
            }

            if (
                $marketImpulsInfo['actualAdx5m']['adx'] < 22
                || ($marketImpulsInfo['actualAdx5m']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx5m']['adx'] < 27)
            ) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 5m\n";
            }


            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx1h']['adx'] < 25) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $res['mlBoard'] = 0.73;
                $infoText .= 'risk ' . $res['risk'] . " down + low adx 1h (25)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 22) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $res['mlBoard'] = 0.73;
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (22)\n";
            }

            if (
                $marketImpulsInfo['actualAdx15m']['adx'] < 25
                && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']
            ) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 15m\n";
            }

            if (abs($marketImpulsInfo['actualImpulsMacd1h']['histogram']) < 100000000) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 1h\n";
            }

            if (
                $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] == 0
            ) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 15m\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 22) {
                $res['risk'] = 2.4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (22)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 19 && $marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 1h (19)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 15) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (15)\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketMidImpulsBoard) {
                $res['risk'] = 2.4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 5m\n";
            }

            /*if ($marketImpulsInfo['actualAdx1h']['adx'] < 22 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -($marketMidImpulsBoard / 2)) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                //$res['mlMarketBoard'] = 0.7;
                $infoText .= 'risk ' . $res['risk'] . " low adx + high impuls 5m\n";
            }*/

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 500000000 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > 0) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " trend close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 750000000 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > 0) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " 15m trend close\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 17 && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']) {
                $res['risk'] = 2;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (17)\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] < -($marketStrongImpulsMacdVal * 1.5)) { //btc. (others board ~ 2 900 000 000)
                $res['risk'] = 2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 2\n";
            }
        }

        // risk/profit rules all
        if ($res['isLong'] || $res['isShort']) {
            if (
                $marketImpulsInfo['actualAdx1h']['adx'] < 20
                || $marketImpulsInfo['actualAdx5m']['adx'] < 22
            ) {
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
            }
            
        }

        //tpRules
        if (
            $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > 0
            || $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= $marketImpulsInfo['actualImpulsMacd15m']['signal_line']
        ) {
            $res['shortTpCount'] = 1;
            $res['longTpCount'] = 2;
        } else if (
            $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 0
            || $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= $marketImpulsInfo['actualImpulsMacd15m']['signal_line']
        ) {
            $res['shortTpCount'] = 2;
            $res['longTpCount'] = 1;
        }

        $directionAr = ['long', 'short'];
        foreach ($directionAr as $directionItem) {
            if (!$res['atrMultipliers']) $res['atrMultipliers'] = [1.9, 2.6, 3.4];
            $atrMultipliersIncreased = array_map(fn($n) => $n * 1.1, $res['atrMultipliers'] );
            //$atrMultipliersIncreased =  [1.4, 2.6, 3.4];

            $res['processedMarket'] = $processedMarket = \Maksv\Bybit\Exchange::processSignal(
                $directionItem,
                floatval($marketImpulsInfo['actualATR']['atr']),
                floatval($marketImpulsInfo['actualClosePrice15m']),
                $marketImpulsInfo['last30Candles15m'],
                $marketImpulsInfo['actualSupertrend5m'],
                $marketImpulsInfo['actualSupertrend15m'],
                $marketImpulsInfo['actualMacdDivergence15m'],
                1,
                $atrMultipliersIncreased ,
                ['risk' => 10],
                'others',
                "getMarketInfo",
                true,
                false
            );
            $res[$directionItem.'Ml'] = $processedMarket['actualMlModel'];
        }

        $infoText .= "\nmarket:\n";
        $infoText .= 'direction ' . ($res['isLong'] ? 'Y' : 'N') . ' | ' . ($res['isShort'] ? 'Y' : 'N') . "\n";
        $infoText .= 'TP '. $res['longTpCount'].' | '.$res['shortTpCount'] . "\n";
        if ($res['longMl']['probabilities'][1] && $res['shortMl']['probabilities'][1]) $infoText .= 'ML predict ' . $res['longMl']['probabilities'][1] . '% | ' . $res['shortMl']['probabilities'][1] . "%\n";

        $infoText .= "\nsignals:\n";
        $infoText .= 'Risk ' . ($res['risk'] ? $res['risk'] : '-') . "\n";
        $infoText .= 'ML board ' . ($res['mlBoard'] ? $res['mlBoard'] : '-') . "\n";
        //$infoText .= 'mML board ' . ($res['mlMarketBoard'] ? $res['mlMarketBoard'] : '-') . "\n";
        $infoText .= "\n";

        $res['infoText'] = $infoText;
        return $res;
    }

    public static function checkMarketImpulsInfoDev()
    {
        $infoText = false;
        $res['isLong'] = $res['isShort'] = false;
        $res['atrMultipliers'] = false;
        $res['shortTpCount'] = $res['longTpCount'] = 1;
        $res['risk'] = 6;
        $maDistance = 2.5;

        $res['marketImpulsInfo'] = $marketImpulsInfo = self::getMarketInfo();
        $res['btcImpulsInfo'] = $btcImpulsInfo = self::checkBtcImpulsInfo();

        $infoText .= "\nmarket info:\n\n";
        //macd trend 5m text
        $marketTrendBoard = 150000000;
        $marketImpulsMacdVal = 210000000;
        $marketImpulseMacdTrendBoardVal = 100000000;
        $marketStrongImpulsMacdVal = 650000000;
        $marketImpulsRSIVal = 8;
        $atrBoard = 1600000000;

        $marketMidImpulsBoard = 2000000000;
        $marketImpulsBoard = 2500000000;
        //$marketImpulsBoard = 2900000000;

        $res['mlBoard'] = 0.72;
        //$res['mlMarketBoard'] = 0.7;

        //market impuls macd 4h text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd4h']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd4h']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd4h']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd4h']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd4h']['trend']['trendVal'] . '), 4h' . "\n";

        //market impuls macd 1h text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd1h']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd1h']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd1h']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd1h']['trend']['trendVal'] . '), 1h' . "\n";

        //market impuls macd 15m text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd15m']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd15m']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd15m']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd15m']['trend']['trendVal'] . '), 15m' . "\n";
        //market impuls macd 5m text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd5m']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd5m']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd5m']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd5m']['trend']['trendVal'] . '), 5m' . "\n\n";

        //adx 1h text
        $infoText .= 'adx trend ' . $marketImpulsInfo['actualAdx1h']['trendDirection']['trendDir'] . ', dir ' . $marketImpulsInfo['actualAdx1h']['adxDirection']['adxDir'] . ' (' . round($marketImpulsInfo['actualAdx1h']['adx'], 2) . '), 1h' . "\n";
        //adx 15m text
        $infoText .= 'adx trend ' . $marketImpulsInfo['actualAdx15m']['trendDirection']['trendDir'] . ', dir ' . $marketImpulsInfo['actualAdx15m']['adxDirection']['adxDir'] . ' (' . round($marketImpulsInfo['actualAdx15m']['adx'], 2) . '), 15m' . "\n";
        //adx 5m text
        $infoText .= 'adx trend ' . $marketImpulsInfo['actualAdx5m']['trendDirection']['trendDir'] . ', dir ' . $marketImpulsInfo['actualAdx5m']['adxDirection']['adxDir'] . ' (' . round($marketImpulsInfo['actualAdx5m']['adx'], 2) . '), 5m' . "\n\n";

        //stoch 1h text ($marketImpulsInfo['actualStochastic1h']['%K']
        $infoText .= 'stoch hist ' . round($marketImpulsInfo['actualStochastic1h']['hist'], 2) . ' (' . round($marketImpulsInfo['actualStochastic1h']['%K'], 2) . ', ' . round($marketImpulsInfo['actualStochastic1h']['%D'], 2) . '), 1h' . "\n";
        //stoch 15m text
        $infoText .= 'stoch hist ' . round($marketImpulsInfo['actualStochastic15m']['hist'], 2) . ' (' . round($marketImpulsInfo['actualStochastic15m']['%K'], 2) . ', ' . round($marketImpulsInfo['actualStochastic15m']['%D'], 2) . '), 15m' . "\n";

        //market
        if ($marketImpulsInfo['longDivergenceVal1h'] && $marketImpulsInfo['longDivergenceText1h'])
            $infoText .= $marketImpulsInfo['longDivergenceText1h'] . "\n";

        if ($marketImpulsInfo['shortDivergenceVal1h'] && $marketImpulsInfo['shortDivergenceText1h'])
            $infoText .= $marketImpulsInfo['shortDivergenceText1h'] . "\n";

        if ($marketImpulsInfo['longDivergenceVal15m'] && $marketImpulsInfo['longDivergenceText15m'])
            $infoText .= $marketImpulsInfo['longDivergenceText15m'] . "\n";

        if ($marketImpulsInfo['shortDivergenceVal15m'] && $marketImpulsInfo['shortDivergenceText15m'])
            $infoText .= $marketImpulsInfo['shortDivergenceText15m'] . "\n";

        if ($marketImpulsInfo['longDivergenceVal5m'] && $marketImpulsInfo['longDivergenceText5m'])
            $infoText .= $marketImpulsInfo['longDivergenceText5m'] . "\n";

        if ($marketImpulsInfo['shortDivergenceVal5m'] && $marketImpulsInfo['shortDivergenceText5m'])
            $infoText .= $marketImpulsInfo['shortDivergenceText5m'] . "\n";

        //btc
        if ($btcImpulsInfo['longDivergenceVal15m'] && $btcImpulsInfo['longDivergenceText15m'])
            $infoText .= $btcImpulsInfo['longDivergenceText15m'] . "\n";

        if ($btcImpulsInfo['shortDivergenceVal15m'] && $btcImpulsInfo['shortDivergenceText15m'])
            $infoText .= $btcImpulsInfo['shortDivergenceText15m'] . "\n";

        if ($btcImpulsInfo['longDivergenceVal5m'] && $btcImpulsInfo['longDivergenceText5m'])
            $infoText .= $btcImpulsInfo['longDivergenceText5m'] . "\n";

        if ($btcImpulsInfo['shortDivergenceVal5m'] && $btcImpulsInfo['shortDivergenceText5m'])
            $infoText .= $btcImpulsInfo['shortDivergenceText5m'] . "\n";


        if (
            (
                $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketImpulseMacdTrendBoardVal
                || ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketMidImpulsBoard)
            )
            && (
                $marketImpulsInfo['actualImpulsMacd15m']['trend']['longDirection']
                && ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= $marketImpulsInfo['actualImpulsMacd15m']['signal_line'])
            )
            //&& $marketImpulsInfo['actualImpulsMacd5m']['trend']['longDirection']
            //&& ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > ($marketImpulsMacdVal / 5))
            //&& ($marketImpulsInfo['actualImpulsMacd15m']['histogram'] > ($marketImpulsMacdVal / 4))
        ) {
            $res['isLong'] = true;
            $res['mlBoard'] = 0.73;
            $res['atrMultipliers'] = [2.6, 3.0, 3.5];
            $res['risk'] = 5;
        } else if (
            (
                $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulseMacdTrendBoardVal
                || ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] < -$marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketMidImpulsBoard)
            )
            && (
                $marketImpulsInfo['actualImpulsMacd15m']['trend']['shortDirection']
                && ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= $marketImpulsInfo['actualImpulsMacd15m']['signal_line'])
            )
            //&& $marketImpulsInfo['actualImpulsMacd5m']['trend']['shortDirection']
            //&& ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] < -($marketImpulsMacdVal / 5))
            //&& ($marketImpulsInfo['actualImpulsMacd15m']['histogram'] < -($marketImpulsMacdVal / 4))

        ) {
            $res['isShort'] = true;
            $res['atrMultipliers'] = [2.6, 3.0, 3.5];
            $res['risk'] = 5;
        }

        // risk/profit rules long
        if ($res['isLong']) {

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= 0 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 400000000) {
                $res['risk'] = 4.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 15m\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 4.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " down adx 1h\n";
            }

            /*  if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] >= 2000000000) {
                  $res['risk'] = 3.5;
                  $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                  $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 1h\n";
              }*/

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] >= 6000000000) {
                $res['risk'] = 4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= 4000000000) {
                $res['risk'] = 4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 15m\n";
            }

            if (
                $marketImpulsInfo['actualStochastic15m']['%K'] <= 54
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m stoch trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 0) {
                $res['risk'] = 4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " down trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > 0 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 400000000) {
                $res['risk'] = 4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 5m\n";
            }

            if ($marketImpulsInfo['shortDivergenceVal5m'] || $marketImpulsInfo['shortDivergenceVal15m']
                || $btcImpulsInfo['shortDivergenceVal5m'] || $btcImpulsInfo['shortDivergenceVal15m']
                || $marketImpulsInfo['shortDivergenceVal1h']
            ) {
                $res['risk'] = 4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " diver\n";
            }

            if (
                ($marketImpulsInfo['actualStochastic1h']['%K'] <= 53 && $marketImpulsInfo['actualStochastic1h']['hist'] < 4)
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 1h stoch trend\n";
            }


            if (
                $marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] == 0
                || $marketImpulsInfo['actualImpulsMacd4h']['impulse_macd'] == 0
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 4h 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > 0 && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] <= ($marketImpulsMacdVal / 2)) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 5m\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_4h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 4h ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_1h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 1h ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma200_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 200 close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] > 6500000000) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > 0 && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] <= ($marketImpulsMacdVal / 2)) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist\n";
            }

            if (
                $marketImpulsInfo['actualAdx5m']['adx'] < 22
                || ($marketImpulsInfo['actualAdx5m']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx5m']['adx'] < 27)
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 5m\n";
            }


            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx1h']['adx'] < 25) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $res['mlBoard'] = 0.73;
                $infoText .= 'risk ' . $res['risk'] . " down + low adx 1h (25)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 22) {
                $res['risk'] = 3.5;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (22)\n";
            }

            if (
                $marketImpulsInfo['actualAdx15m']['adx'] < 25
                && ($marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir'])
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 15m\n";
            }

            if (abs($marketImpulsInfo['actualImpulsMacd1h']['histogram']) < 100000000) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 1h\n";
            }

            if (
                $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] == 0
            ) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 15m\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 22) {
                $res['risk'] = 2.4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (22)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 19 && $marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 1h (19)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 15) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (15)\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketMidImpulsBoard) {
                $res['risk'] = 2.4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 5m\n";
            }

            /* if ($marketImpulsInfo['actualAdx1h']['adx'] < 22 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > ($marketMidImpulsBoard / 2)) {
                 $res['risk'] = 2.5;
                 $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                 //$res['mlMarketBoard'] = 0.7;
                 $infoText .= 'risk ' . $res['risk'] . " low adx + high impuls 5m\n";
             }*/

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > -500000000 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 0) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " trend close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > -750000000 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 0) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " 15m trend close\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 17 && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']) {
                $res['risk'] = 2;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (17)\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketImpulsBoard && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] < ($marketStrongImpulsMacdVal * 1.5)) { //btc. (others board ~ 2 900 000 000)
                $res['risk'] = 2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 2\n";
            }

        }

        // risk/profit rules short
        if ($res['isShort']) {

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= 0 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > -400000000) {
                $res['risk'] = 4.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 15m\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 4.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " down adx 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] <= -6000000000) {
                $res['risk'] = 4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= -4000000000) {
                $res['risk'] = 4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " high impuls macd line 15m\n";
            }

            if ($marketImpulsInfo['actualStochastic15m']['%K'] >= 47
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m stoch trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > 0) {
                $res['risk'] = 4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " up trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 0 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > -400000000) {
                $res['risk'] = 4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 5m\n";
            }

            if ($marketImpulsInfo['longDivergenceVal5m'] || $marketImpulsInfo['longDivergenceVal15m']
                || $btcImpulsInfo['longDivergenceVal5m'] || $btcImpulsInfo['longDivergenceVal15m']
                || $marketImpulsInfo['longDivergenceVal1h']
            ) {
                $res['risk'] = 4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " diver\n";
            }

            if (
                $marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] == 0
                || $marketImpulsInfo['actualImpulsMacd4h']['impulse_macd'] == 0
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 4h 1h\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_4h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 4h ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_1h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 1h ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma100_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 100 close\n";
            }

            if (!self::checkMaCondition($marketImpulsInfo['ma200_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 200 close\n";
            }

            if (
                ($marketImpulsInfo['actualStochastic1h']['%K'] >= 47 && $marketImpulsInfo['actualStochastic1h']['hist'] > -4)
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 1h stoch trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] < -6500000000) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] < 0 && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] >= -($marketImpulsMacdVal / 2)) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist\n";
            }

            if (
                $marketImpulsInfo['actualAdx5m']['adx'] < 22
                || ($marketImpulsInfo['actualAdx5m']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx5m']['adx'] < 27)
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 5m\n";
            }


            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx1h']['adx'] < 25) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $res['mlBoard'] = 0.73;
                $infoText .= 'risk ' . $res['risk'] . " down + low adx 1h (25)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 22) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $res['mlBoard'] = 0.73;
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (22)\n";
            }

            if (
                $marketImpulsInfo['actualAdx15m']['adx'] < 25
                && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']
            ) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 15m\n";
            }

            if (abs($marketImpulsInfo['actualImpulsMacd1h']['histogram']) < 100000000) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 1h\n";
            }

            if (
                $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] == 0
            ) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 15m\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 22) {
                $res['risk'] = 2.4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (22)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 19 && $marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 1h (19)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 15) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (15)\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketMidImpulsBoard) {
                $res['risk'] = 2.4;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 5m\n";
            }

            /*if ($marketImpulsInfo['actualAdx1h']['adx'] < 22 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -($marketMidImpulsBoard / 2)) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                //$res['mlMarketBoard'] = 0.7;
                $infoText .= 'risk ' . $res['risk'] . " low adx + high impuls 5m\n";
            }*/

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 500000000 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > 0) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " trend close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 750000000 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > 0) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " 15m trend close\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 17 && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']) {
                $res['risk'] = 2;
                $res['mlBoard'] = 0.73;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (17)\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] < -($marketStrongImpulsMacdVal * 1.5)) { //btc. (others board ~ 2 900 000 000)
                $res['risk'] = 2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 2\n";
            }
        }

        // risk/profit rules all
        if ($res['isLong'] || $res['isShort']) {
            if (
                $marketImpulsInfo['actualAdx1h']['adx'] < 20
                || $marketImpulsInfo['actualAdx5m']['adx'] < 22
            ) {
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
            }

        }

        //tpRules
        if (
            $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > 0
            || $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= $marketImpulsInfo['actualImpulsMacd15m']['signal_line']
        ) {
            $res['shortTpCount'] = 1;
            $res['longTpCount'] = 2;
        } else if (
            $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 0
            || $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= $marketImpulsInfo['actualImpulsMacd15m']['signal_line']
        ) {
            $res['shortTpCount'] = 2;
            $res['longTpCount'] = 1;
        }

        $directionAr = ['long', 'short'];
        foreach ($directionAr as $directionItem) {
            if (!$res['atrMultipliers']) $res['atrMultipliers'] = [1.9, 2.6, 3.4];
            $atrMultipliersIncreased = array_map(fn($n) => $n * 1.1, $res['atrMultipliers'] );
            //$atrMultipliersIncreased =  [1.4, 2.6, 3.4];

            $res['processedMarket'] = $processedMarket = \Maksv\Bybit\Exchange::processSignal(
                $directionItem,
                floatval($marketImpulsInfo['actualATR']['atr']),
                floatval($marketImpulsInfo['actualClosePrice15m']),
                $marketImpulsInfo['last30Candles15m'],
                $marketImpulsInfo['actualSupertrend5m'],
                $marketImpulsInfo['actualSupertrend15m'],
                $marketImpulsInfo['actualMacdDivergence15m'],
                1,
                $atrMultipliersIncreased ,
                ['risk' => 10],
                'others',
                "getMarketInfo",
                true,
                false
            );
            $res[$directionItem.'Ml'] = $processedMarket['actualMlModel'];
        }

        $infoText .= "\nmarket:\n";
        $infoText .= 'direction ' . ($res['isLong'] ? 'Y' : 'N') . ' | ' . ($res['isShort'] ? 'Y' : 'N') . "\n";
        $infoText .= 'TP '. $res['longTpCount'].' | '.$res['shortTpCount'] . "\n";
        if ($res['longMl']['probabilities'][1] && $res['shortMl']['probabilities'][1]) $infoText .= 'ML predict ' . $res['longMl']['probabilities'][1] . '% | ' . $res['shortMl']['probabilities'][1] . "%\n";

        $infoText .= "\nsignals:\n";
        $infoText .= 'Risk ' . ($res['risk'] ? $res['risk'] : '-') . "\n";
        $infoText .= 'ML board ' . ($res['mlBoard'] ? $res['mlBoard'] : '-') . "\n";
        //$infoText .= 'mML board ' . ($res['mlMarketBoard'] ? $res['mlMarketBoard'] : '-') . "\n";
        $infoText .= "\n";

        $res['infoText'] = $infoText;
        return $res;
    }

    public static function checkBtcImpulsInfo($trendBoard = 10)
    {
        $infoText = $actualAdx5m = $actualAdx15m = $actualImpulsMacd5m = $actualImpulsMacd15m = $actualSupertrend15m = $actualStochastic5m = $actualMacd15m = $actualStochastic15m = $actualMacdDivergence5m = $actualMacd5m = $actualMacdDivergence15m = false;
        $res['isLong'] = $res['isShort'] = false;
        $res['risk'] = $res['atrMultipliers'] = $res['shortTpCount'] = $res['longTpCount'] = false;

        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();

        $kline5m = $bybitApiOb->klineV5("linear", "BTCUSDT", '5m', 402, true, 120);
        if ($kline5m['result'] && $kline5m['result']['list']) {
            $klineList5m = array_reverse($kline5m['result']['list']);
            $candles5m = array_map(function ($k) {
                return [
                    't' => floatval($k[0]), // timestap
                    'o' => floatval($k[1]), // Open price
                    'h' => floatval($k[2]), // High price
                    'l' => floatval($k[3]), // Low price
                    'c' => floatval($k[4]), // Close price
                    'v' => floatval($k[5])  // Volume
                ];
            }, $klineList5m);

            try {
                //$macdData5m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles5m, 5, 'SMA', 35, 'SMA', 5,'SMA', 8, 'macdLine') ?? false;
                $macdData5m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles5m, 12, 'EMA', 26, 'EMA', 9, 'EMA', 8, 'histogram') ?? false;
                if ($macdData5m && is_array($macdData5m))
                    $actualMacd5m = $macdData5m[array_key_last($macdData5m)];

            } catch (Exception $e) {
                devlogs('ERR | err - macd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $stochasticOscillatorData5m = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles5m) ?? false;
                if ($stochasticOscillatorData5m && is_array($stochasticOscillatorData5m))
                    $actualStochastic5m = $stochasticOscillatorData5m[array_key_last($stochasticOscillatorData5m)];

            } catch (Exception $e) {
                devlogs('ERR | err - stoch 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $actualMacdDivergence5m = self::checkMultiMACD(
                    $candles5m,
                    '5m',
                    ['5m' => 10, '15m' => 10, '30m' => 10, '1h' => 7, '4h' => 8, '1d' => 8],
                );

                $res['longDivergenceVal5m'] = $res['shortDivergenceVal5m'] = false;
                if ($actualMacdDivergence5m['longDivergenceTypeAr']['regular']) {
                    $res['longDivergenceVal5m'] = true;
                    $res['longDivergenceText5m'] = 'btc bullish dever ' . $actualMacdDivergence5m['inputParams'] . ' (' . $actualMacdDivergence5m['longDivergenceDistance'] . '), 5m';
                }

                if ($actualMacdDivergence5m['shortDivergenceTypeAr']['regular']) {
                    $res['shortDivergenceVal5m'] = true;
                    $res['shortDivergenceText5m'] = 'btc bearish dever ' . $actualMacdDivergence5m['inputParams'] . ' (' . $actualMacdDivergence5m['shortDivergenceDistance'] . '), 5m';
                }
            } catch (Exception $e) {
                devlogs('ERR | err - checkMultiMACD 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $adxData5m = \Maksv\TechnicalAnalysis::calculateADX($candles5m);
                $actualAdx5m = $adxData5m[array_key_last($adxData5m)];
            } catch (Exception $e) {
                devlogs('ERR | err - actualAdx5m 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $impulseMACD5m = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles5m) ?? false;
                if ($impulseMACD5m && is_array($impulseMACD5m))
                    $res['actualImpulsMacd5m'] = $actualImpulsMacd5m = $impulseMACD5m[array_key_last($impulseMACD5m)];
            } catch (Exception $e) {
                devlogs('ERR | err - actualImpulsMacd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }
        }

        $kline15m = $bybitApiOb->klineV5("linear", "BTCUSDT", '15m', 402, true, 120);
        if ($kline15m['result'] && $kline15m['result']['list']) {
            $klineList15m = array_reverse($kline15m['result']['list']);
            $candles15m = array_map(function ($k) {
                return [
                    't' => floatval($k[0]), // timestap
                    'o' => floatval($k[1]), // Open price
                    'h' => floatval($k[2]), // High price
                    'l' => floatval($k[3]), // Low price
                    'c' => floatval($k[4]), // Close price
                    'v' => floatval($k[5])  // Volume
                ];
            }, $klineList15m);

            try {
                $impulseMACD15m = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles15m) ?? false;
                if ($impulseMACD15m && is_array($impulseMACD15m))
                    $res['actualImpulsMacd15m'] = $actualImpulsMacd15m = $impulseMACD15m[array_key_last($impulseMACD15m)];
            } catch (Exception $e) {
                devlogs('ERR | err - actualImpulsMacd 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $actualMacdDivergence15m = self::checkMultiMACD(
                    $candles15m,
                    '15m',
                    ['5m' => 11, '15m' => 11, '30m' => 11, '1h' => 8, '4h' => 8, '1d' => 8],
                );

                $res['longDivergenceVal15m'] = $res['shortDivergenceVal15m'] = false;
                if ($actualMacdDivergence15m['longDivergenceTypeAr']['regular']) {
                    $res['longDivergenceVal15m'] = true;
                    $res['longDivergenceText15m'] = 'btc bullish dever ' . $actualMacdDivergence15m['inputParams'] . ' (' . $actualMacdDivergence15m['longDivergenceDistance'] . '), 15m';

                }

                if ($actualMacdDivergence15m['shortDivergenceTypeAr']['regular']) {
                    $res['shortDivergenceVal15m'] = true;
                    $res['shortDivergenceText15m'] = 'btc bearish dever ' . $actualMacdDivergence15m['inputParams'] . ' (' . $actualMacdDivergence15m['shortDivergenceDistance'] . '), 15m';
                }
            } catch (Exception $e) {
                devlogs('ERR | err - checkMultiMACD ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($candles15m, 10, 3) ?? false; // длина 10, фактор 3
                $actualSupertrend15m = $supertrendData[array_key_last($supertrendData) - 1] ?? false;
            } catch (Exception $e) {
                devlogs('ERR | err - Supertrend' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                //$macdData15m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles15m, 5, 'SMA', 35, 'SMA', 5,'SMA', 8, 'macdLine') ?? false;
                $macdData15m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles15m, 12, 'EMA', 26, 'EMA', 9, 'EMA', 8, 'histogram') ?? false;
                if ($macdData15m && is_array($macdData15m))
                    $actualMacd15m = $macdData15m[array_key_last($macdData15m)];

            } catch (Exception $e) {
                devlogs('ERR | err - macd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $adxData15m = \Maksv\TechnicalAnalysis::calculateADX($candles15m);
                $actualAdx15m = $adxData15m[array_key_last($adxData15m)];
            } catch (Exception $e) {
                devlogs('ERR | err - actualAdx15m 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $stochasticOscillatorData15m = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles15m) ?? false;
                if ($stochasticOscillatorData15m && is_array($stochasticOscillatorData15m))
                    $actualStochastic15m = $stochasticOscillatorData15m[array_key_last($stochasticOscillatorData15m)];

            } catch (Exception $e) {
                devlogs('ERR | err - stoch 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }
        }

        //macd trend 15m text
        /*if ($actualMacd15m && $actualMacd15m['main_values']['macd_line'] > $trendBoard)
            $infoText .= 'local trend - up, (' . round($actualMacd15m['main_values']['macd_line'], 1) . ') 15m' . "\n";
        else if ($actualMacd15m && $actualMacd15m['main_values']['macd_line'] < -$trendBoard)
            $infoText .= 'local trend - down, (' . round($actualMacd15m['main_values']['macd_line'], 1) . ') 15m' . "\n";
        else
            $infoText .= 'local trend - neutral, (' . round($actualMacd15m['main_values']['macd_line'], 1) . ') 15m' . "\n";*/

        $infoText .= "\n" . 'btc info:' . "\n\n";

        //stoch 15m text
        $infoText .= 'stoch hist ' . round($actualStochastic15m['hist'], 2) . ' (' . round($actualStochastic15m['%K'], 2) . ', ' . round($actualStochastic15m['%D'], 2) . '), 15m' . "\n";
        //stoch 5m text
        $infoText .= 'stoch hist ' . round($actualStochastic5m['hist'], 2) . ' (' . round($actualStochastic5m['%K'], 2) . ', ' . round($actualStochastic5m['%D'], 2) . '), 5m' . "\n\n";

        //impuls macd 15m text
        $infoText .= 'impuls macd hist ' . round($actualImpulsMacd15m['histogram'], 1) . ' trend ' . ($actualImpulsMacd15m['trend']['trendText'])
            . ' (' . round($actualImpulsMacd15m['impulse_macd'], 2) . ', '
            . round($actualImpulsMacd15m['signal_line'], 2) . '), (' . $actualImpulsMacd5m['trend']['trendVal'] . '), 15m' . "\n";

        //impuls 5m text
        $infoText .= 'impuls macd hist ' . round($actualImpulsMacd5m['histogram'], 1) . ' trend ' . ($actualImpulsMacd5m['trend']['trendText'])
            . ' (' . round($actualImpulsMacd5m['impulse_macd'], 2) . ', '
            . round($actualImpulsMacd5m['signal_line'], 2) . '), (' . $actualImpulsMacd5m['trend']['trendVal'] . '), 5m' . "\n\n";

        //adx 15m text
        $infoText .= 'adx trend ' . $actualAdx15m['trendDirection']['trendDir'] . ', dir ' . $actualAdx15m['adxDirection']['adxDir'] . ' (' . round($actualAdx15m['adx'], 2) . '), 15m' . "\n";
        //adx 5m text
        $infoText .= 'adx trend ' . $actualAdx5m['trendDirection']['trendDir'] . ', dir ' . $actualAdx5m['adxDirection']['adxDir'] . ' (' . round($actualAdx5m['adx'], 2) . '), 5m' . "\n\n";

        //macd trend 5m text
        if ($actualMacd5m && $actualMacd5m['main_values']['macd_line'] > $trendBoard)
            $infoText .= 'local trend - up, (' . round($actualMacd5m['main_values']['macd_line'], 1) . ') 5m' . "\n\n";
        else if ($actualMacd5m && $actualMacd5m['main_values']['macd_line'] < -$trendBoard)
            $infoText .= 'local trend - down, (' . round($actualMacd5m['main_values']['macd_line'], 1) . ') 5m' . "\n\n";
        else
            $infoText .= 'local trend - neutral, (' . round($actualMacd5m['main_values']['macd_line'], 1) . ') 5m' . "\n\n";


        //divergence 5m text and val
        $shortDivergenceVal5m = $shortDivergenceVal15m = $longDivergenceVal5m = $longDivergenceVal15m = false;
        if ($res['longDivergenceVal5m']) {
            $longDivergenceVal5m = true;
            $infoText .= $res['longDivergenceText5m'] . "\n";
        }

        if ($res['shortDivergenceVal5m']) {
            $shortDivergenceVal5m = true;
            $infoText .= $res['shortDivergenceText5m'] . "\n";
        }

        //divergence 15m text and val
        if ($res['longDivergenceVal15m']) {
            $longDivergenceVal15m = true;
            $infoText .= $res['longDivergenceText15m'] . "\n";
        }

        if ($res['shortDivergenceVal15m']) {
            $shortDivergenceVal15m = true;
            $infoText .= $res['shortDivergenceText15m'] . "\n";
        }

        //main rules
        $impulseMacdTrendBoardVal = 3;

        $impulsMacdVal = 30;
        $strongImpulsMacdVal = 175;
        $impulsRSIVal = 8;
        if (
            ($actualImpulsMacd15m['impulse_macd'] > $impulseMacdTrendBoardVal || $actualImpulsMacd15m['impulse_macd'] < -$impulseMacdTrendBoardVal)
            && ($actualImpulsMacd5m['impulse_macd'] > $impulseMacdTrendBoardVal || $actualImpulsMacd5m['impulse_macd'] < -$impulseMacdTrendBoardVal)
        ) {
            if (
                (
                    $actualMacd5m['main_values']['macd_line'] > $trendBoard
                    || $actualImpulsMacd5m['impulse_macd'] > $impulseMacdTrendBoardVal
                    || $actualImpulsMacd5m['histogram'] > $strongImpulsMacdVal
                )
                && $actualImpulsMacd5m['trend']['longDirection']
                && (
                    ($actualMacd5m['main_values']['histogram_value'] > ($impulsMacdVal / 3))
                    || ($actualStochastic15m['hist'] > $impulsRSIVal)
                    || ($actualImpulsMacd5m['histogram'] > ($impulsMacdVal / 2))
                )
                && (
                    $actualAdx5m['adxDirection']['isUpDir'] && $actualAdx5m['trendDirection']['isUpTrend']
                    || $actualAdx5m['adxDirection']['isDownDir'] && $actualAdx5m['trendDirection']['isDownTrend']
                )

            ) {
                $res['isLong'] = true;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $res['risk'] = 3.5;
            } else if (
                (
                    $actualMacd5m['main_values']['macd_line'] < -$trendBoard
                    || $actualImpulsMacd5m['impulse_macd'] < -$impulseMacdTrendBoardVal
                    || $actualImpulsMacd5m['histogram'] < -$strongImpulsMacdVal
                )
                && $actualImpulsMacd5m['trend']['shortDirection']
                && (
                    ($actualMacd5m['main_values']['histogram_value'] < -($impulsMacdVal / 3))
                    || ($actualStochastic15m['hist'] < -$impulsRSIVal)
                    || ($actualImpulsMacd5m['histogram'] < -($impulsMacdVal / 2))
                )
                && (
                    $actualAdx5m['adxDirection']['isUpDir'] && $actualAdx5m['trendDirection']['isDownTrend']
                    || $actualAdx5m['adxDirection']['isDownDir'] && $actualAdx5m['trendDirection']['isUpTrend']
                )
            ) {
                $res['isShort'] = true;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $res['risk'] = 3.5;
            }
        }

        // risk/profit rules long
        if ($res['isLong']) {
            if ($actualImpulsMacd5m['impulse_macd'] > 250) {
                $res['risk'] = 2.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C1.1 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] > 0 && $actualImpulsMacd5m['impulse_macd'] < 75) {
                $res['risk'] = 2.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C2.1 \n";
            }

            if (
                $actualAdx15m['adx'] < 22
                || ($actualAdx15m['adx'] < 27 && $actualAdx15m['adxDirection']['isDownDir'])
            ) {
                $res['risk'] = 2.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C3.1\n";
            }

            if ($shortDivergenceVal5m || $shortDivergenceVal15m) {//
                $res['risk'] = 1.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C4.1 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] > -100 && $actualImpulsMacd5m['impulse_macd'] < 0) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C5.1 \n";
            }

            if ($actualImpulsMacd15m['impulse_macd'] > -100 && $actualImpulsMacd15m['impulse_macd'] < 0) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C6.1 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] > 400 && $actualImpulsMacd5m['histogram'] < $strongImpulsMacdVal) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C7.1 \n";
            }

            if ($actualImpulsMacd5m['histogram'] > 0 && $actualImpulsMacd5m['histogram'] <= ($impulsMacdVal / 2)) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C8.1\n";
            }
        }

        // risk/profit rules short
        if ($res['isShort']) {
            if ($actualImpulsMacd5m['impulse_macd'] < -250) {
                $res['risk'] = 2.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C1.2 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] < 0 && $actualImpulsMacd5m['impulse_macd'] > -75) {
                $res['risk'] = 2.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C2.2 \n";
            }

            if (
                $actualAdx15m['adx'] < 22
                || ($actualAdx15m['adx'] < 27 && $actualAdx15m['adxDirection']['isDownDir'])
            ) {
                $res['risk'] = 2.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C3.2\n";
            }

            if ($longDivergenceVal5m || $longDivergenceVal15m) {//
                $res['risk'] = 1.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C4.2 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] < 100 && $actualImpulsMacd5m['impulse_macd'] > 0) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C5.2 \n";
            }

            if ($actualImpulsMacd15m['impulse_macd'] < 100 && $actualImpulsMacd15m['impulse_macd'] > 0) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C6.2 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] < -400 && $actualImpulsMacd5m['histogram'] > -$strongImpulsMacdVal) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C7.2 \n";
            }

            if ($actualImpulsMacd5m['histogram'] < 0 && $actualImpulsMacd5m['histogram'] >= -($impulsMacdVal / 2)) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C8.2\n";
            }
        }

        // risk/profit rules all
        if ($res['isLong'] || $res['isShort']) {
            if ($actualImpulsMacd15m['histogram'] <= ($impulsMacdVal / 3) && $actualImpulsMacd15m['histogram'] >= -($impulsMacdVal / 3)) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C9\n";
            }

            if (
                $actualAdx15m['adx'] < 22
                || ($actualAdx15m['adx'] < 27 && $actualAdx15m['adxDirection']['isDownDir'])
            ) {
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
            }
        }

        if ($actualImpulsMacd15m['impulse_macd'] > 0) {
            $res['shortTpCount'] = 1;
            $res['longTpCount'] = 2;
        } else if ($actualImpulsMacd15m['impulse_macd'] < 0) {
            $res['shortTpCount'] = 2;
            $res['longTpCount'] = 1;
        }

        $infoText .= ($res['isLong'] ? 'Y' : 'N') . ' | ' . ($res['isShort'] ? 'Y' : 'N');
        $infoText .= "\n\n";

        /*$res['isLong'] = false;
        $res['isShort'] = false;*/
        $res['infoText'] = $infoText;
        $bybitApiOb->closeConnection();
        return $res;
    }

    /**
     * Функция для обработки одной “сигнальной” записи (будь то pump, dump или screenerData).
     *
     * @param string $direction Направление сигнала: 'long' или 'short'.
     * @param float $atr Значение ATR (например, floatval($item['actualATR']['atr'])).
     * @param float $closePrice Текущая цена закрытия (floatval(...)).
     * @param array $candles15m Массив свечей 15m (например, $item['candles15m'] или $candles15m из screener).
     * @param array $supertrend5m Данные Supertrend на 5m (массив с ключами 'isUptrend', 'value').
     * @param array $supertrend15m Данные Supertrend на 15m (массив с ключами 'isUptrend', 'value').
     * @param array $macdDivergence Данные MACD-дивергенции (массив с ключами
     *                                   ['extremes']['selected']['low']['priceLow2']['value']
     *                                   и (для short) ['extremes']['selected']['high']['priceHigh2']['value'],
     *                                   а также ['longDivergenceTypeAr'], ['shortDivergenceTypeAr'] для стратегий).
     * @param int $symbolScale Количество десятичных знаков (scale) у инструмента.
     * @param array $atrMultipliers Массив множителей ATR (например, [1.9, 2.6, 3.4] или [$item['atrMultipliers']]).
     * @param array $marketInfo Ассоциативный массив с текущими настройками риска, напр. ['risk' => 4, 'isLong'=>true, 'isShort'=>false].
     * @param string $symbolName Имя символа (например, BTCUSDT).
     * @param string $logContext Любая строка для логирования (например, "$marketCode/bybitExchange$timeFrame" или "$marketMode/screener$interval").
     *
     * @return array|false
     *   Если сигнал “проходит” все проверки риска, возвращает массив вида:
     *     [
     *       'determineEntryPoint'        => [...],        // результат TechnicalAnalysis::determineEntryPoint
     *       'recommendedEntry'           => float|false,  // рекомендуемая точка входа либо false
     *       'calculateRiskTargetsWithATR'=> [...],        // результат TechnicalAnalysis::calculateRiskTargetsWithATR
     *       'SL'                         => float,        // рассчитанный стоп-лосс
     *       'TP'                         => array,        // рассчитанные тейк-профиты (array of floats)
     *       'riskBoard'                  => int,          // значение $marketInfo['risk'] (или 4 по умолчанию)
     *       'actualMlModel'              => array,        // результат ML-прогноза (массив)
     *     ]
     *
     *   Если риск (riskPercent) выше threshold ($marketInfo['risk']), функция сразу вернёт false.
     */
    public static function processSignal(
        string $direction,
        float  $atr,
        float  $closePrice,
        array  $candles15m,
        array  $supertrend5m,
        array  $supertrend15m,
        array  $macdDivergence,
        int    $symbolScale,
        array  $atrMultipliers,
        array  $marketInfo,
        string $symbolName,
        string $logContext,
        bool $mlFlag = true,
        bool $mlFilter = true,
        bool $mlDevMode = false,
    )
    {
        // Инициализируем “пустой” результат
        $result = [
            'determineEntryPoint' => null,
            'recommendedEntry' => false,
            'calculateRiskTargetsWithATR' => null,
            'SL' => false,
            'TP' => false,
            'riskBoard' => null,
            'actualMlModel' => [],
        ];

        //
        // 1) Определяем точку входа через TechnicalAnalysis::determineEntryPoint
        //
        try {
            $determineEntryPoint = \Maksv\TechnicalAnalysis::determineEntryPoint($atr, $candles15m, $direction);
            $result['determineEntryPoint'] = $determineEntryPoint;

            if (!$determineEntryPoint['isEntryPointGood']) {
                // если точка входа “не годится”, сохраняем рекомендуемую (округлённую) или false
                $result['recommendedEntry'] = round($determineEntryPoint['recommendedEntry'], $symbolScale);
            }
        } catch (\Exception $e) {
            devlogs(
                "ERR $symbolName | err - determineEntryPoint: {$e->getMessage()} | timeMark - " .
                date("d.m.y H:i:s"),
                $logContext
            );
            // Если determineEntryPoint кинуло исключение, можно либо считать, что точка входа не найдена, либо возвращать false:
            // здесь просто оставляем recommendedEntry == false и продолжаем дальше.
        }

        //
        // 2) Считаем “родительский” стоп-лосс (slParent) и смещение slOffset
        //
        //    Для long: по приоритету берём Supertrend5m→Supertrend15m→экстремум дивергенции→ATR*2
        //    Для short: аналогично, но проверяем isUptrend == false и value
        //
        if ($direction === 'long') {
            // default: берем экстремум по дивергенции “low”
            $slParent = isset($macdDivergence['extremes']['selected']['low']['priceLow2']['value'])
                ? floatval($macdDivergence['extremes']['selected']['low']['priceLow2']['value'])
                : ($closePrice - ($atr * 2));
            $slOffset = 0.6;

            if (!empty($supertrend5m['isUptrend']) && $supertrend5m['value']) {
                $slParent = floatval($supertrend5m['value']);
                $slOffset = 1.6;
            } elseif (!empty($supertrend15m['isUptrend']) && $supertrend15m['value']) {
                $slParent = floatval($supertrend15m['value']);
            }

        } else { // short
            // default: берем экстремум по дивергенции “high”
            $slParent = isset($macdDivergence['extremes']['selected']['high']['priceHigh2']['value'])
                ? floatval($macdDivergence['extremes']['selected']['high']['priceHigh2']['value'])
                : ($closePrice + ($atr * 2));
            $slOffset = 0.6;

            if (isset($supertrend5m['isUptrend']) && !$supertrend5m['isUptrend'] && $supertrend5m['value']) {
                $slParent = floatval($supertrend5m['value']);
                $slOffset = 1.6;
            } elseif (isset($supertrend15m['isUptrend']) && !$supertrend15m['isUptrend'] && $supertrend15m['value']) {
                $slParent = floatval($supertrend15m['value']);
            }
        }
        //count 86 (68\17)
        //win 79.07%
        //115.2 $

        //
        //count 86 (69\15)
        //win 80.23%
        //123.1 $
        // 2.1) Если recommendedEntry задана, но slParent “мешает” (закладывается за цену входа), обнуляем recommendedEntry
        if ($result['recommendedEntry'] !== false) {
            if (
                ($direction === 'long' && $slParent >= $result['recommendedEntry'])
                || ($direction === 'short' && $slParent <= $result['recommendedEntry'])
            ) {
                $result['recommendedEntry'] = false;
            }
        }

        //
        // 3) Считаем стоп-лосс и тейк-профиты через TechnicalAnalysis::calculateRiskTargetsWithATR
        //
        try {
            $calculateRiskTargets = \Maksv\TechnicalAnalysis::calculateRiskTargetsWithATR(
                $atr,
                $closePrice,
                $slParent,
                $direction,
                $symbolScale,
                $slOffset,
                $atrMultipliers
            );

            // если риск (riskPercent) >= допустимого ($marketInfo['risk'] или по умолчанию) → сразу false
            $riskBoard = floatval($marketInfo['risk'] ?? 5);
            if (floatval($calculateRiskTargets['riskPercent']) >= $riskBoard) {
                devlogs(
                    "ERR $symbolName | RISK {$calculateRiskTargets['riskPercent']} >= $riskBoard | timeMark - " .
                    date("d.m.y H:i:s"),
                    $logContext
                );
                return false;
            }

            $result['calculateRiskTargetsWithATR'] = $calculateRiskTargets;
            $result['SL'] = floatval($calculateRiskTargets['stopLoss']);
            $result['TP'] = $calculateRiskTargets['takeProfits'];
            $result['riskBoard'] = $riskBoard;

        } catch (\Exception $e) {
            devlogs(
                "ERR $symbolName | err - calculateRiskTargetsWithATR: {$e->getMessage()} | timeMark - " .
                date("d.m.y H:i:s"),
                $logContext
            );
            return false;
        }

        //
        // 4) Прогноз ML (если нужно)
        //
        if ($mlFlag) {
            try {
                if (!empty($candles15m) && is_array($candles15m)) {
                    $mlCandles = $candles15m;
                    if (count($candles15m) > 30) {
                        // берём последние 30 свечей
                        $mlCandles = array_slice($candles15m, -30);
                    }

                    $entryPrice = $closePrice;
                    $tpsRaw = (array)$result['TP'];
                    $tps = array_map(fn($x) => floatval($x), $tpsRaw);
                    $slPrice = floatval($result['SL']);

                    $mlPayload = [
                        'candles' => $mlCandles,
                        'entry' => $entryPrice,
                        'tps' => $tps,
                        'sl' => $slPrice,
                        'direction' => $direction,
                    ];

                    $ml = new \Maksv\MachineLearning\Request('http://127.0.0.1:8000');
                    $result['actualMlModel'] = $ml->predict($mlPayload);

                    if ($mlDevMode) {
                        $mlDev = new \Maksv\MachineLearning\Request('http://127.0.0.1:8001', 'mlDev');
                        $result['actualMlModel'] = $mlDev->predict($mlPayload);
                    }

                    if ($mlFilter) {
                        $minMlBoard = 0.61;
                        if ($result['actualMlModel']['probabilities'][1] < $minMlBoard) {
                            devlogs(
                                "ERR $symbolName | ML {$result['actualMlModel']['probabilities'][1]} >= $minMlBoard | timeMark - " .
                                date("d.m.y H:i:s"),
                                $logContext
                            );
                            return false;
                        }
                    }

                } else {
                    $errText = "ERR $symbolName | err - ML candles absent or not array | timeMark - " . date("d.m.y H:i:s");
                    \Maksv\DataOperation::sendErrorInfoMessage($errText, 'processSignal', $logContext);
                    devlogs($errText, $logContext);
                }
            } catch (\Exception $e) {
                $errText = "ERR $symbolName | err - {$errText}: {$e->getMessage()} | timeMark - " . date("d.m.y H:i:s");
                \Maksv\DataOperation::sendErrorInfoMessage($errText, 'processSignal', $logContext);
                devlogs($errText, $logContext);
            }
        } else {
            //devlogs('WARN | skip ML', $logContext);
        }

        return $result;
    }

    /**
     * Хелпер для проверки условий по скользящим средним (MA) с учётом направления сигнала.
     *
     * @param array  $ma               Ассоциативный массив MA с ключами:
     *                                - 'isUptrend' => bool
     *                                - 'sma'       => float|string
     * @param float  $actualClosePrice Текущая цена закрытия
     * @param int    $maDistance       Порог в процентах (например, 3)
     * @param string $direction        Направление сигнала: 'long' или 'short'
     *
     * @return bool
     *   Для 'long':
     *     - если 'sma' не задана или равна нулю → true (пропускаем проверку);
     *     - если isUptrend == true → true;
     *     - иначе рассчитываем diffPercent = ((actualClosePrice - sma) / sma) * 100
     *       и возвращаем true, если diffPercent <= -$maDistance, иначе false.
     *
     *   Для 'short':
     *     - если 'sma' не задана или равна нулю → true;
     *     - если isUptrend == false → true;
     *     - иначе рассчитываем diffPercent = ((actualClosePrice - sma) / sma) * 100
     *       и возвращаем true, если diffPercent >= $maDistance, иначе false.
     */
    public static function checkMaCondition(array $ma, float $actualClosePrice, int $maDistance, string $direction): bool
    {
        // 1) Если 'sma' не указан или равен нулю → считаем условие выполненным (true)
        if (empty($ma['sma']) || floatval($ma['sma']) === 0.0) {
            return true;
        }

        $sma = floatval($ma['sma']);
        $diffPercent = (($actualClosePrice - $sma) / $sma) * 100.0;

        if ($direction === 'long') {
            // Для long: если уже в восходящем тренде → true
            if (!empty($ma['isUptrend'])) {
                return true;
            }
            // Иначе: проверяем, что цена упала относительно SMA на maDistance процентов либо больше
            return ($diffPercent <= -$maDistance);

        } elseif ($direction === 'short') {
            // Для short: если уже не в восходящем тренде → true
            if (empty($ma['isUptrend'])) {
                return true;
            }
            // Иначе: проверяем, что цена поднялась относительно SMA на maDistance процентов либо больше
            return ($diffPercent >= $maDistance);

        } else {
            // Неподдерживаемое направление — пропускаем проверку
            return true;
        }
    }

    public static function fearGreedExchange()
    {
        $timeMark = date("d.m.y H:i:s");
        $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/CoinMarketCupExchange/fearGreed/timestap.json'), true);
        if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 180)) {
            return;
        } else {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/CoinMarketCupExchange/fearGreed/timestap.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => $timeMark]));
        }

        $coinmarketcapOb = new \Maksv\Coinmarketcap\Request();
        $fearGreedLatestVal = ($coinmarketcapOb->fearGreedLatest())['data']['value'];
        \Maksv\DataOperation::sendFearGreedWarning($fearGreedLatestVal, '@infoCryptoHelperTrend');
    }

    public static function btcDOthersExchange()
    {

        $timeMark = date("d.m.y H:i:s");
        devlogs("start" . ' - ' . $timeMark, 'cpc/btcDOthersExchange');
        $res = [];

        $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/CoinMarketCupExchange/btcd/timestap.json'), true);
        if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 180)) {
            devlogs("end, timestap dif -" . ' - ' . $timeMark, 'cpc/btcDOthersExchange');
            return;
        } else {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/CoinMarketCupExchange/btcd/timestap.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => $timeMark]));
        }

        $coinmarketcapOb = new \Maksv\Coinmarketcap\Request();
        $resOB = $coinmarketcapOb->cryptocurrencyQuotesLatest('bitcoin');
        $actualQuote = $resOB['data'][1]['quote']['USDT'];
        $btcDVal = round($actualQuote['market_cap_dominance'], 2);
        $btcVal = round($actualQuote['price'], 1);

        $exhSnaps = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/CoinMarketCupExchange/btcd/historyExchange.json'), true)['RESPONSE_EXCHENGE'] ?? [];

        $tfMap = ['4h' => 0, '12h' => 3, '24h' => 6];
        foreach ($tfMap as $tf => $cnt) {
            $prevQuote = $exhSnaps[array_key_last($exhSnaps) - $cnt] ?? false;
            if ($prevQuote) {
                $others = '-';

                // Устанавливаем пороговые значения для анализа
                //$board = 0.3; // изменение цены биткоина
                $btcThreshold = 0.3; // изменение цены биткоина
                $btcThresholdStrong = 1.5; // изменение цены биткоина
                $btcDThreshold = 0.1; // изменение доминации биткоина

                // Рассчитываем изменения
                $btc = (($actualQuote['price'] - $prevQuote['price']) / $prevQuote['price']) * 100;
                // Процентное изменение цены биткоина относительно предыдущего значения
                $btcD = $actualQuote['market_cap_dominance'] - $prevQuote['market_cap_dominance'];
                // Изменение доминации биткоина на рынке (разница между текущим и предыдущим значением)

                // Логика определения направления для альткоинов ("others")
                if (abs($btc) < $btcThreshold && abs($btcD) < $btcDThreshold) {
                    // Если изменения цены биткоина и его доминации меньше пороговых значений
                    $others = 'neutral'; // Считаем, что изменений недостаточно для определения тренда
                } elseif ($btcD > $btcDThreshold) {
                    // Если доминация биткоина растёт и превышает порог
                    if ($btc > $btcThreshold) {
                        $others = 'down'; // Рост биткоина сопровождается потерей интереса к альткоинам
                    } elseif ($btc < -$btcThresholdStrong) {
                        $others = 'dump'; // Падение биткоина приводит к ослаблению рынка альткоинов
                    } elseif ($btc < -$btcThreshold) {
                        $others = 'down'; // Падение биткоина приводит к ослаблению рынка альткоинов
                    } else {
                        // Если изменения цены биткоина незначительные
                        $others = 'flat/down'; // Рост доминации биткоина, но стабильность в цене
                    }
                } elseif ($btcD < -$btcDThreshold) {
                    // Если доминация биткоина падает и превышает отрицательный порог
                    if ($btc > $btcThresholdStrong) {
                        $others = 'pump'; // Рост биткоина сопровождается увеличением интереса к альткоинам
                    } else if ($btc > $btcThreshold) {
                        $others = 'up'; // Рост биткоина сопровождается увеличением интереса к альткоинам
                    } elseif ($btc < -$btcThreshold) {
                        $others = 'flat/up'; // Падение доминации биткоина и снижение его цены приводит к стагнации
                    } else {
                        // Если изменения цены биткоина незначительные
                        $others = 'up'; // Снижение доминации биткоина даёт шанс для роста альткоинов
                    }
                } else {
                    // Если доминация биткоина изменяется в пределах пороговых значений
                    if ($btcD > $btcDThreshold) {
                        // Умеренный рост доминации биткоина
                        $others = 'flat/down'; // Альткоины теряют интерес
                    } elseif ($btcD < -$btcDThreshold) {
                        // Умеренное снижение доминации биткоина
                        $others = 'flat/up'; // Альткоины стабилизируются
                    } else {
                        $others = 'neutral'; // Считаем, что изменений недостаточно для определения тренда
                    }
                }

                $actualQuote['actual_calculate'] = [
                    'btc' => $btc,
                    'btcD' => $btcD,
                    'others' => $others,
                ];

                $res[$tf] = [
                    'btc' => round($btc, 2),
                    'btcD' => round($btcD, 2),
                    'others' => $others,
                    'timemark' => date("H:i"),
                    'btcDVal' => $btcDVal,
                    'btcVal' => $btcVal,
                ];
            }
        }

        $exhSnaps[] = $actualQuote;
        $exhSnaps = array_slice($exhSnaps, -40);

        $timeMark = date("d.m H:i");
        $exchangeResponse = [
            "TIMEMARK" => $timeMark,
            "RESPONSE_EXCHENGE" => $exhSnaps,
            "EXCHANGE_CODE" => 'bybit',
        ];
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/CoinMarketCupExchange/btcd/historyExchange.json', json_encode($exchangeResponse));

        $timeMark = date("d.m H:i");
        $exchangeResponse = [
            "TIMEMARK" => $timeMark,
            "RESPONSE_EXCHENGE" => $res,
            "EXCHANGE_CODE" => 'bybit',
        ];
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/CoinMarketCupExchange/btcd/res.json', json_encode($exchangeResponse));
        devlogs("end" . ' - ' . $timeMark, 'cpc/btcDOthersExchange');

        try {
            \Maksv\DataOperation::sendTrendWarning($res, $btcDVal, $btcVal, '@infoCryptoHelperTrend');
        } catch (Exception $e) {
            devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'cpc/btcDOthersExchange');
        }
    }

    public static function getLetestOrderBook($symbol)
    {
        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();

        $orderBook = $bybitApiOb->orderBookV5('linear', $symbol, 1000, true);
        $analyzeOrderBook = \Maksv\TechnicalAnalysis::analyzeOrderBook($orderBook) ?? [];

        $bybitApiOb->closeConnection();
        return $analyzeOrderBook;
    }

    public static function sendMarketCharts()
    {
        $data = [];

        $data['symbolName'] = $symbolName = 'BTCUSDT';
        $data['interval'] = $interval = '15m';

        $chartsDir = $_SERVER["DOCUMENT_ROOT"] . '/upload/charts/';
        if (!is_dir($chartsDir))
            mkdir($chartsDir);

        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();

        $kline = $bybitApiOb->klineV5("linear", $symbolName, $interval, 802, true, 120);
        if ($kline['result'] && $kline['result']['list']) {
            $klineList = array_reverse($kline['result']['list']);
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

            $maAr = $maHis = $ma100His = $ma200His = [];
            try {
                $maHis = \Maksv\TechnicalAnalysis::getMACrossHistory($candles, 9, 26, 102) ?? [];
                $ma100His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles, 12, 100, 102) ?? [];
                $ma200His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles, 12, 200, 202) ?? [];
            } catch (Exception $e) {
                devlogs('ERR ' . $symbolName . ' | err - cross' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'sendMarketCharts');
            }

            $maAr = [
                'ma26' => $maHis,
                'ma100' => $ma100His,
                'ma200' => $ma200His,
            ];

            //график по цене
            $priceChartGen = new \Maksv\Charts\PriceChartGenerator(); // можно указать свои размеры, если нужно
            $data['tempChartPath'][] = $tempPriceChartPath = $chartsDir . time() . '_' . $interval . 'btc_price' . '.png';
            $priceChartGen->generateChart($candles, $symbolName, $interval, $tempPriceChartPath, $maAr);
        }

        //$dataFileSeparateVolume = $_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/summaryVolumeExchange.json';
        //$existingDataSparateVolume = file_exists($dataFileSeparateVolume) ? json_decode(file_get_contents($dataFileSeparateVolume), true)['RESPONSE_EXCHENGE'] ?? [] : [];
        //$volumesData = $existingDataSparateVolume ?? [];
        //$separateVolume = array_reverse(self::aggregateSumVolume5mTo15m($volumesData[$symbolName]['resBybit'])) ?? [];

        //график по лонгам и шортам
        //$cvdChartGen = new \Maksv\Charts\CvdChartGenerator();
        //$data['tempChartPath'][] = $tempCVDChartPath = $chartsDir . time() . '_' . $interval . '_cvd' . '.png';
        //$cvdChartGen->generateChart($separateVolume, $symbolName, $interval, $tempCVDChartPath);


        $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/traydingviewExchange/total_ex_top10.json';
        $marketData = json_decode(file_get_contents($path), true) ?? [];
        $timestamp = $marketData['timestamp'] ?? 0;
        $marketKlines = $marketData['data'];

        if (time() - $timestamp > 300) { // 5 минут = 300 секунд
            $data['err'][] = 'Data is older than 5 minutes';
        } else {
            $klineListOth = $marketKlines[$interval] ?? [];
            if ($klineListOth && is_array($klineListOth) && count($klineListOth) > 80) {
                $candlesOth = array_map(function ($k) {
                    return [
                        't' => floatval($k['datetime']), // timestap
                        'o' => floatval($k['open']), // Open price
                        'h' => floatval($k['high']), // High price
                        'l' => floatval($k['low']), // Low price
                        'c' => floatval($k['close']), // Close price
                        'v' => floatval($k['volume'])  // Volume
                    ];
                }, $klineListOth);

                $maAr = $maHis = $ma100His = $ma200His = [];
                try {
                    $maHis = \Maksv\TechnicalAnalysis::getMACrossHistory($candlesOth, 9, 26, 102) ?? [];
                    $ma100His = \Maksv\TechnicalAnalysis::getMACrossHistory($candlesOth, 12, 100, 102) ?? [];
                    $ma200His = \Maksv\TechnicalAnalysis::getMACrossHistory($candlesOth, 12, 200, 202) ?? [];
                } catch (Exception $e) {
                    devlogs('ERR ' . $symbolName . ' | err - cross oth' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'sendMarketCharts');
                }

                $maAr = [
                    'ma26' => $maHis,
                    'ma100' => $ma100His,
                    'ma200' => $ma200His,
                ];

                //график по цене
                $priceChartGen = new \Maksv\Charts\PriceChartGenerator(); // можно указать свои размеры, если нужно
                $data['tempChartPath'][] = $tempPriceChartPath = $chartsDir . time() . '_' . $interval . 'oth_price' . '.png';
                $priceChartGen->generateChart($candlesOth, 'OTHERS', $interval, $tempPriceChartPath, $maAr);
            }
        }

        $dev['all'] = $data;
        $dev['err'] = $data['err'];

        \Maksv\DataOperation::sendMarketCharts($data, '@infoCryptoHelperTrend');
        foreach ($data['tempChartPath'] as $path)
            unlink($path);

        $bybitApiOb->closeConnection();
        return $dev;
    }

    public static function marketDivergenceCheck($tf = '1h', $devMode = false)
    {
        $timeMark = date("d.m.y H:i:s");
        // проверяем не запускался ли только что обмен
        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $tf . '/timestap_btc.json'), true);
            if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 180)) {
                // devlogs("end, timestap dif -" . ' - ' . $timeMark, 'marketDivergenceCheck');
                return;
            } else {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $tf . '/timestap_btc.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => $timeMark]));
            }
        }

        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();

        $barsCount = 802;
        // получаем свечи для определения
        $kline = $bybitApiOb->klineV5("linear", 'BTCUSDT', $tf, $barsCount);

        $macdParamsMap = [
            '12.26.9.EMA' => ['fastPeriod' => 12, 'fastMAType' => 'EMA', 'slowPeriod' => 26, 'slowMAType' => 'EMA', 'signalPeriod' => 9, 'signalMAType' => 'EMA', 'extremesType' => 'histogram'],
            '5.35.5.SMA' => ['fastPeriod' => 5, 'fastMAType' => 'SMA', 'slowPeriod' => 35, 'slowMAType' => 'SMA', 'signalPeriod' => 5, 'signalMAType' => 'SMA', 'extremesType' => 'macdLine'],
            '3.10.16.SMA' => ['fastPeriod' => 3, 'fastMAType' => 'SMA', 'slowPeriod' => 10, 'slowMAType' => 'SMA', 'signalPeriod' => 16, 'signalMAType' => 'SMA', 'extremesType' => 'macdLine'],
        ];
        $priceIndexToleranceMap = ['15m' => 7, '30m' => 8, '1h' => 10, '4h' => 10, '1d' => 7];

        $divergenceText = $candles = false;
        if ($kline['result'] && $kline['result']['list']) {
            $klineList = array_reverse($kline['result']['list']);

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

            foreach ($macdParamsMap as $type => $param) {
                $divergenceTextBtc = '';
                $macdDivergenceData = \Maksv\TechnicalAnalysis::calculateMacdExt($candles, $param['fastPeriod'], $param['fastMAType'], $param['slowPeriod'], $param['slowMAType'], $param['signalPeriod'], $param['signalMAType'], $priceIndexToleranceMap[$tf], $param['extremesType']) ?? false;

                $actualMacdDivergence = false;
                if ($macdDivergenceData && is_array($macdDivergenceData))
                    $actualMacdDivergence = $macdDivergenceData[array_key_last($macdDivergenceData)];

                $divergenceDistance = '-';
                if ($actualMacdDivergence) {
                    if ($actualMacdDivergence['longDivergenceTypeAr']['regular']) {
                        $divergenceTextBtc = 'btc long, regular, ';
                        $divergenceDistance = $actualMacdDivergence['longDivergenceDistance'];
                    } else if ($actualMacdDivergence['shortDivergenceTypeAr']['regular']) {
                        $divergenceTextBtc = 'btc short, regular, ';
                        $divergenceDistance = $actualMacdDivergence['shortDivergenceDistance'];
                    }

                    if ($divergenceTextBtc) {
                        $divergenceTextBtc .= $type . ' (' . $divergenceDistance . '), ' . $tf . "\n";
                        \Maksv\DataOperation::sendMarketDivergenceWarning($divergenceTextBtc, '@infoCryptoHelperTrend');
                    }
                }
            }
        }

        if (in_array($tf , ['15m', '1h'])) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/traydingviewExchange/total_ex_top10.json';
            $marketData = json_decode(file_get_contents($path), true) ?? [];
            $timestamp = $marketData['timestamp'] ?? 0;
            $marketKlines = $marketData['data'];

            if (time() - $timestamp > 300) { // 5 минут = 300 секунд
                $data['err'][] = 'Data is older than 5 minutes';
            } else {
                $klineListOth = $marketKlines[$tf] ?? [];
                if ($klineListOth && is_array($klineListOth) && count($klineListOth) > 80) {
                    $candlesOth = array_map(function ($k) {
                        return [
                            't' => floatval($k['datetime']), // timestap
                            'o' => floatval($k['open']), // Open price
                            'h' => floatval($k['high']), // High price
                            'l' => floatval($k['low']), // Low price
                            'c' => floatval($k['close']), // Close price
                            'v' => floatval($k['volume'])  // Volume
                        ];
                    }, $klineListOth);

                    foreach ($macdParamsMap as $type => $param) {
                        $divergenceTextOth = '';
                        $macdDivergenceData = \Maksv\TechnicalAnalysis::calculateMacdExt($candlesOth, $param['fastPeriod'], $param['fastMAType'], $param['slowPeriod'], $param['slowMAType'], $param['signalPeriod'], $param['signalMAType'], $priceIndexToleranceMap[$tf], $param['extremesType']) ?? false;

                        $actualMacdDivergence = false;
                        if ($macdDivergenceData && is_array($macdDivergenceData))
                            $actualMacdDivergence = $macdDivergenceData[array_key_last($macdDivergenceData)];

                        $divergenceDistance = '-';
                        if ($actualMacdDivergence) {
                            if ($actualMacdDivergence['longDivergenceTypeAr']['regular']) {
                                $divergenceTextOth = 'oth long, regular, ';
                                $divergenceDistance = $actualMacdDivergence['longDivergenceDistance'];
                            } else if ($actualMacdDivergence['shortDivergenceTypeAr']['regular']) {
                                $divergenceTextOth = 'oth short, regular, ';
                                $divergenceDistance = $actualMacdDivergence['shortDivergenceDistance'];
                            }

                            if ($divergenceTextOth) {
                                $divergenceTextOth .= $type . ' (' . $divergenceDistance . '), ' . $tf . "\n";
                                \Maksv\DataOperation::sendMarketDivergenceWarning($divergenceTextOth, '@infoCryptoHelperTrend');
                            }
                        }
                    }

                }
            }
        }

        $bybitApiOb->closeConnection();
        return $divergenceTextBtc . "\n" . $divergenceTextOth;
    }

    public static function checkMultiMACD(
        $candles = [],
        $tf = '15m',
        $priceIndexToleranceMap = ['5m' => 11, '15m' => 11, '30m' => 11, '1h' => 8, '4h' => 8, '1d' => 6],
    )
    {
        if (!is_array($candles) || count($candles) < 30)
            return false;

        $mainType = '5.35.5.SMA';
        $macdParamsMap = [
            $mainType => ['fastPeriod' => 5, 'fastMAType' => 'SMA', 'slowPeriod' => 35, 'slowMAType' => 'SMA', 'signalPeriod' => 5, 'signalMAType' => 'SMA', 'extremesType' => 'macdLine'],
            '12.26.9.EMA' => ['fastPeriod' => 12, 'fastMAType' => 'EMA', 'slowPeriod' => 26, 'slowMAType' => 'EMA', 'signalPeriod' => 9, 'signalMAType' => 'EMA', 'extremesType' => 'histogram'],
            '3.10.16.SMA' => ['fastPeriod' => 3, 'fastMAType' => 'SMA', 'slowPeriod' => 10, 'slowMAType' => 'SMA', 'signalPeriod' => 16, 'signalMAType' => 'SMA', 'extremesType' => 'macdLine'],
        ];

        $actualMacdDivergence = false;
        $actualMacdDivergenceAr = false;
        $notFoundFlag = true;
        foreach ($macdParamsMap as $type => $param) {
            $macdDivergenceData = \Maksv\TechnicalAnalysis::calculateMacdExt($candles, $param['fastPeriod'], $param['fastMAType'], $param['slowPeriod'], $param['slowMAType'], $param['signalPeriod'], $param['signalMAType'], $priceIndexToleranceMap[$tf], $param['extremesType']) ?? false;

            if ($macdDivergenceData && is_array($macdDivergenceData))
                $actualMacdDivergenceAr[$type] = $actualMacdDivergence = $macdDivergenceData[array_key_last($macdDivergenceData)];

            $actualMacdDivergenceAr[$type]['inputParams'] = $actualMacdDivergence['inputParams'] = $type;
            if (
                $actualMacdDivergence
                && (
                    $actualMacdDivergence['longDivergenceTypeAr']['regular']
                    || $actualMacdDivergence['longDivergenceTypeAr']['hidden']
                    || $actualMacdDivergence['shortDivergenceTypeAr']['regular']
                    || $actualMacdDivergence['shortDivergenceTypeAr']['hidden']
                )
            ) {
                $notFoundFlag = false;
                break;
            }
        }

        if ($notFoundFlag)
            $actualMacdDivergence = $actualMacdDivergenceAr[$mainType];

        return $actualMacdDivergence;
    }

    public static function getSummaryOpenInterest(
        $symbolName,
        $binanceApiOb,
        $bybitApiOb,
        $binanceSymbolsList = [],
        $bybitSymbolsList = [],
        $interval = '30m',
        $useCache = true,
        $cacheTime = 120
    )
    {
        $res = [];
        $intervals = [
            '10m' => 780000,  //
            '15m' => 1080000,  // 17 минут
            '30m' => 1980000,  // 35 минут
            '1h' => 3900000,  // 1 час 5 минут
            '4h' => 14700000, // 4 часа 5 минут
            '1d' => 86700000, // 1 день 5 минут
        ];

        $endTime = round(microtime(true) * 1000);
        $startTime = $endTime - $intervals[$interval]; // Начало интервала

        $resBybit['resp'] = $resBybit['res'] = [];
        if (in_array($symbolName, $bybitSymbolsList)) {
            // --- Получение OI с Bybit ---
            $openInterestResp = $bybitApiOb->openInterestByTime($symbolName, $startTime, $endTime, 'linear', '5m', 120, $useCache, $cacheTime);
            $resBybit['resp'] = $openInterestResp;

            if (!empty($openInterestResp['result']['list'])) {
                foreach ($openInterestResp['result']['list'] as $oiItem) {
                    $timestamp = (double)$oiItem['timestamp'];
                    $resBybit['res'][$timestamp] = [
                        'datetime' => date("Y-m-d H:i:s", floor($timestamp / 1000)),
                        'timestamp' => $timestamp,
                        'openInterest' => (float)$oiItem['openInterest'],
                    ];
                }
            }
        }

        // --- Получение OI с Binance --- (сначала проверяем есть ли такой у бинанса)
        $resBinance['resp'] = $resBinance['res'] = [];
        if (in_array($symbolName, $binanceSymbolsList)) {
            $getOpenInterestHist = $binanceApiOb->getOpenInterestHist($symbolName, $startTime, $endTime, '5m', 120, $useCache, $cacheTime) ?? [];
            $resBinance['resp'] = $getOpenInterestHist;
            if (!empty($getOpenInterestHist)) {
                foreach ($getOpenInterestHist as $oiItem) {
                    $timestamp = (double)$oiItem['timestamp'];
                    $resBinance['res'][$timestamp] = [
                        'datetime' => date("Y-m-d H:i:s", floor($timestamp / 1000)),
                        'timestamp' => $timestamp,
                        'openInterest' => (float)$oiItem['sumOpenInterest'],
                    ];
                }
            }
        }

        if ($resBybit['res'])
            ksort($resBybit['res']);

        if ($resBinance['res'])
            ksort($resBinance['res']);

        // --- Формирование итогового массива ---
        $allTimestamps = array_unique(array_merge(array_keys($resBybit['res']), array_keys($resBinance['res'])));
        if ($allTimestamps)
            ksort($allTimestamps);

        $resSummary = [];
        foreach ($allTimestamps as $timestamp) {
            $oiBybit = $resBybit['res'][$timestamp]['openInterest'] ?? 0;
            $oiBinance = $resBinance['res'][$timestamp]['openInterest'] ?? 0;
            $resSummary[$timestamp] = [
                'datetime' => date("Y-m-d H:i:s", floor($timestamp / 1000)),
                'timestamp' => $timestamp,
                'openInterest' => $oiBybit + $oiBinance,
            ];
        }

        // --- Расчет изменения OI ---
        $summaryOIBybit = self::calculateOIChange($resBybit['res']);
        $summaryOIBinance = self::calculateOIChange($resBinance['res']);
        $summaryOI = self::calculateOIChange($resSummary);

        // --- Итоговый результат ---
        $res['resBybit'] = $resBybit;
        $res['resBinance'] = $resBinance;
        $res['allTimestamps'] = $allTimestamps;
        $res['resSummary'] = $resSummary;
        $res['summaryOIBybit'] = $summaryOIBybit;
        $res['summaryOIBinance'] = $summaryOIBinance;
        $res['summaryOI'] = $summaryOI;

        return $res;
    }

    protected static function calculateOIChange($data)
    {
        if (count($data) < 2) return 0;
        $prev = reset($data)['openInterest'];
        $actual = end($data)['openInterest'];
        return $prev != 0 ? round((($actual - $prev) / $prev) * 100, 2) : 0;
    }

    public static function getSummaryOpenInterestDev(
        $symbolName,
        $binanceApiOb,
        $bybitApiOb,
        $okxApiOb,
        $binanceSymbolsList = [],
        $bybitSymbolsList = [],
        $okxSymbolsList = [],
        $interval = '30m',
        $useCache = true,
        $cacheTime = 120
    )
    {
        $res = [];
        $intervals = [
            '10m' => 780000,  //
            '15m' => 1080000,  // 17 минут
            '30m' => 1980000,  // 35 минут
            '1h' => 3900000,  // 1 час 5 минут
            '4h' => 14700000, // 4 часа 5 минут
            '1d' => 86700000, // 1 день 5 минут
        ];

        $endTime = round(microtime(true) * 1000);
        $startTime = $endTime - $intervals[$interval]; // Начало интервала

        // --- Получение OI с Bybit ---
        $resBybit['resp'] = $resBybit['res'] = [];
        if (in_array($symbolName, $bybitSymbolsList)) {
            $openInterestResp = $bybitApiOb->openInterestByTime($symbolName, $startTime, $endTime, 'linear', '5m', 120, $useCache, $cacheTime);
            $resBybit['resp'] = $openInterestResp;

            if (!empty($openInterestResp['result']['list'])) {
                foreach ($openInterestResp['result']['list'] as $oiItem) {
                    $timestamp = (double)$oiItem['timestamp'];

                    if ((float)$oiItem['openInterest'] == 0) {
                        devlogs("Err bybit oi process = 0  " . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterestDev');
                        devlogs($oiItem, 'getSummaryOpenInterestDev');
                    } else {
                        $resBybit['res'][$timestamp] = [
                            'datetime' => date("Y-m-d H:i:s", floor($timestamp / 1000)),
                            'timestamp' => $timestamp,
                            'openInterest' => (float)$oiItem['openInterest'],
                        ];
                    }

                }
            }
        }

        // --- Получение OI с Binance --- (сначала проверяем есть ли такой у бинанса)
        $resBinance['resp'] = $resBinance['res'] = [];
        if (in_array($symbolName, $binanceSymbolsList)) {
            $getOpenInterestHist = $binanceApiOb->getOpenInterestHist($symbolName, $startTime, $endTime, '5m', 120, $useCache, $cacheTime) ?? [];
            $resBinance['resp'] = $getOpenInterestHist;
            if (!empty($getOpenInterestHist)) {
                foreach ($getOpenInterestHist as $oiItem) {
                    $timestamp = (double)$oiItem['timestamp'];

                    if ((float)$oiItem['sumOpenInterest'] == 0) {
                        devlogs("Err binance oi process = 0  " . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterestDev');
                        devlogs($oiItem, 'getSummaryOpenInterestDev');
                    } else {
                        $resBinance['res'][$timestamp] = [
                            'datetime' => date("Y-m-d H:i:s", floor($timestamp / 1000)),
                            'timestamp' => $timestamp,
                            'openInterest' => (float)$oiItem['sumOpenInterest'],
                        ];
                    }

                }
            }
        }
        
        // --- Получение OI с Okx --- (сначала проверяем есть ли такой у okx)
        $resOkx['resp'] = $resOkx['res'] = [];
        if (in_array($symbolName, $okxSymbolsList)) {
            $okxSymbolName = array_search($symbolName, $okxSymbolsList);
            if ($okxSymbolName !== false) {
                $oiHistRespOkx = $okxApiOb->getOpenInterestHist($okxSymbolName, $startTime, $endTime, '5m', 120, $useCache, $cacheTime)['data'] ?? [];
                $resOkx['resp'] = $oiHistRespOkx;
                if (!empty($oiHistRespOkx)) {
                    foreach ($oiHistRespOkx as $oiItem) {
                        $timestamp = (double)$oiItem[0];
                        if ((float)$oiItem[1] == 0) {
                            devlogs("Err okx oi process = 0  " . $okxSymbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterestDev');
                            devlogs($oiItem, 'getSummaryOpenInterestDev');
                        } else {
                            $resOkx['res'][$timestamp] = [
                                'datetime' => date("Y-m-d H:i:s", floor($timestamp / 1000)),
                                'timestamp' => $timestamp,
                                'openInterest' => (float)$oiItem[1],
                            ];
                        }
                    }
                }
            }
        }

        if ($resBybit['res'])
            ksort($resBybit['res']);

        if ($resBinance['res'])
            ksort($resBinance['res']);

        if ($resOkx['res'])
            ksort($resOkx['res']);

        // --- Формирование итогового массива ---
        $allTimestamps = array_unique(array_merge(
            array_keys($resBybit['res']),
            array_keys($resBinance['res']),
            array_keys($resOkx['res'])
        ));

        if ($allTimestamps)
            ksort($allTimestamps);

        // --- Расчет изменения OI ---
        $summaryOIBybit = self::calculateOIChange($resBybit['res']);
        $summaryOIBinance = self::calculateOIChange($resBinance['res']);
        $summaryOIOkx = self::calculateOIChange($resOkx['res']);

        // Если изменение по бирже ровно ±100%, считаем данные «грязными» и их не учитываем:
        if (abs($summaryOIBybit) === 100) {
            $resBybit['res']   = [];
            $summaryOIBybit    = 0;
            devlogs("Err bybit 100  " . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterestDev');
        }

        if (abs($summaryOIBinance) === 100) {
            $resBinance['res'] = [];
            $summaryOIBinance  = 0;
            devlogs("Err binance 100  " . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterestDev');
        }
        if (abs($summaryOIOkx) === 100) {
            $resOkx['res']     = [];
            $summaryOIOkx      = 0;
            devlogs("Err okx 100  " . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterestDev');
        }

        $resSummary = [];
        foreach ($allTimestamps as $timestamp) {
            $oiBybit = $resBybit['res'][$timestamp]['openInterest'] ?? 0;
            $oiBinance = $resBinance['res'][$timestamp]['openInterest'] ?? 0;
            $oiOkx = $resOkx['res'][$timestamp]['openInterest'] ?? 0;
            $resSummary[$timestamp] = [
                'datetime' => date("Y-m-d H:i:s", floor($timestamp / 1000)),
                'timestamp' => $timestamp,
                'openInterest' => $oiBybit + $oiBinance + $oiOkx,
            ];
        }
        $summaryOI = self::calculateOIChange($resSummary);

        // --- Итоговый результат ---
        $res['resBybit'] = $resBybit;
        $res['resBinance'] = $resBinance;
        $res['resOkx'] = $resOkx;

        $res['allTimestamps'] = $allTimestamps;
        $res['resSummary'] = $resSummary;
        $res['summaryOIBybit'] = $summaryOIBybit;
        $res['summaryOIBinance'] = $summaryOIBinance;
        $res['summaryOIOkx'] = $summaryOIOkx;

        $res['summaryOI'] = $summaryOI;

        return $res;
    }

    public static function bybitSummaryVolumeExchange(
        $devMode = false,
        $cacheTime = 240,
        $useCache = false
    )
    {
        $marketMode = 'bybit';
        $timeMark = date("d.m.y H:i:s");
        devlogs("start -" . ' - ' . $timeMark, $marketMode . '/summaryVolumeExchange');

        // проверяем не запускался ли только что обмен
        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/timestampVolume.json'), true);
            if ($lastTimestapJson['TIMESTAMP'] && ((time() - $lastTimestapJson['TIMESTAMP']) < 150)) {
                devlogs("end, timestamp dif -" . ' - ' . $timeMark, $marketMode . '/summaryVolumeExchange');
                return;
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
        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();
        /*$binanceApiOb = new \Maksv\Binance\BinanceFutures();
        $binanceApiOb->openConnection();*/

        // Получение списка символов
        $exchangeBybitSymbolsList = json_decode(
            file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'),
            true
        )['RESPONSE_EXCHENGE'] ?? [];
        $bybitSymbolsList = array_column($exchangeBybitSymbolsList, 'symbol') ?? [];

        $processedSymbols = 0;
        foreach ($exchangeBybitSymbolsList as $symbol) {
            //if ($devMode && $processedSymbols >= 20) break;

            if (
                !isset($symbol['symbol'])
                || !is_string($symbol['symbol'])
                || $symbol['status'] !== 'Trading'
                || preg_match('/^(ETHUSDT-|ETH-|BTCUSDT-|BTC-|SOLUSDT-)/', $symbol['symbol'])
                || !in_array($symbol['quoteCoin'], ['USDT'])
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
            $currentData = $existingData[$symbolName]['resBybit'] ?? [];

            // Получение новых сделок
            $tradesHistoryResp = $bybitApiOb->tradesHistory($symbolName, 1000, 'linear', $useCache, $cacheTime);
            $tradesHistoryList = $tradesHistoryResp['result']['list'] ?? [];

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

                $size = (float)$tradeItem['size'];
                $intervalData[$intervalStart][$tradeItem['side'] === 'Buy' ? 'buyVolume' : 'sellVolume'] += $size;
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
                'resBybit' => $currentData,
                'resBinance' => [],
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

        $bybitApiOb->closeConnection();
        //$binanceApiOb->closeConnection();

        //devlogs("continueSymbols " . $continueSymbols, $marketMode . '/summaryVolumeExchange');
        devlogs("end (cnt " . $processedSymbols . " - " . $timeMark, $marketMode . '/summaryVolumeExchange');
        devlogs("______________________________", $marketMode . '/summaryVolumeExchange');

        return $output;
    }

    public static function aggregateSumVolume5mTo15m(array $fiveMinIntervals): array
    {
        $result = [];

        foreach ($fiveMinIntervals as $interval) {
            $start = $interval['startTime'];  // предполагается, что время в секундах
            $minute = (int)date('i', $start);
            // Определяем начало 15-минутного блока: округляем вниз до ближайшего кратного 15
            $groupMinute = floor($minute / 15) * 15;
            // Определяем начало группы (текущая дата, час и округленная минута)
            $groupStart = strtotime(date(sprintf('Y-m-d H:%02d:00', $groupMinute), $start));
            // Ключ группы – строка, например: "2023-03-15 14:00"
            $groupKey = date('Y-m-d H', $groupStart) . ':' . sprintf('%02d', $groupMinute);

            // Если группы еще нет, создаем ее с фиксированными границами 15 минут
            if (!isset($result[$groupKey])) {
                $result[$groupKey] = [
                    'buyVolume' => 0,
                    'sellVolume' => 0,
                    'sumVolume' => 0,
                    'startTime_gmt' => \Maksv\Bybit\Bybit::gmtTimeByTimestamp($groupStart * 1000),
                    'startTime' => $groupStart,
                    'endTime' => $groupStart + 900,  // фиксированно 15 минут (900 секунд)
                    'endTime_gmt' => \Maksv\Bybit\Bybit::gmtTimeByTimestamp(($groupStart + 900) * 1000),
                    'last_edit' => date("d.m.y H:i:s")
                ];
            }

            // Добавляем объемы из 5-минутного интервала в группу
            $result[$groupKey]['buyVolume'] += $interval['buyVolume'];
            $result[$groupKey]['sellVolume'] += $interval['sellVolume'];
            $result[$groupKey]['sumVolume'] += $interval['sumVolume'];
            // Обновляем время редактирования
            $result[$groupKey]['last_edit'] = date("d.m.y H:i:s");
        }

        // Приводим результат к индексированному массиву и сортируем по времени (от свежих к старым)
        $aggregated = array_values($result);
        usort($aggregated, function ($a, $b) {
            return $b['startTime'] - $a['startTime'];
        });

        $aggregated = \Maksv\TechnicalAnalysis::calculateDelta($aggregated);

        return $aggregated;
    }

    public static function analyzeSymbolPriceChange(
        $bybitApiOb,
        $binanceApiOb,
        $okxApiOb,
        $symbolName,
        $startTime,
        $endTime,
        $type,
        $actualClosePrice = false,
        $sl = false,
        $tp = false,
        $shiftSL = false,
        $cacheTime = 0,
        $candles = [],
        $market = 'bybit')
    {
        if (!$candles) {

            if ($market == 'bybit') {
                $kline = $bybitApiOb->klineTimeV5("linear", $symbolName, $startTime, $endTime, '5m', 1000, true, $cacheTime);
                if (!$kline['result'] || empty($kline['result']['list'])) {
                    return [
                        'status' => false,
                        'message' => 'No data from API bybit'
                    ];
                }
                $klineList = array_reverse($kline['result']['list']);
                $candles = array_map(function ($k) {
                    return [
                        't' => floatval($k[0]),
                        'o' => floatval($k[1]),
                        'h' => floatval($k[2]),
                        'l' => floatval($k[3]),
                        'c' => floatval($k[4]),
                        'v' => floatval($k[5])
                    ];
                }, $klineList);
            } else if ($market == 'binance') {
                $kline = $binanceApiOb->kline($symbolName, '5m', 1000, $startTime, $endTime, true, $cacheTime);
                if (empty($kline) || !is_array($kline)) {
                    return [
                        'status' => false,
                        'message' => 'No data from API binance'
                    ];
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

            } else if ($market == 'okx') {
                $kline = $okxApiOb->getCandlesHist($symbolName, '5m', $startTime, $endTime, true, $cacheTime);
                if (empty($kline) || !is_array($kline)) {
                    return [
                        'status' => false,
                        'message' => 'No data from API okx'
                    ];
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
            } else {
                return [
                    'status' => false,
                    'message' => 'No data from API okx'
                ];
            }
        }

        $firstCandle = reset($candles);
        $lastPrice = $firstCandle['o'];
        if ($actualClosePrice) {
            $lastPrice = $actualClosePrice;
        }

        $slHit = false;
        $effectiveTargetPrice = null;
        $realizedPercentChange = null;
        $tpCount = 0;
        // Новый флаг, который будет true, если цена до достижения первого TP коснулась точки входа
        $entryTouched = false;

        // Для long: проходим свечи, обновляем TP и считаем их количество
        if ($sl !== false) {
            foreach ($candles as $candle) {
                if ($type == 'long') {
                    // Обновление максимума
                    if ($effectiveTargetPrice === null || $candle['h'] > $effectiveTargetPrice) {
                        $effectiveTargetPrice = $candle['h'];
                    }
                    // Если еще не достигнут первый TP, проверяем касание точки входа
                    if ($tpCount == 0 && !$entryTouched && $candle['l'] <= $actualClosePrice) {
                        $entryTouched = true;
                    }

                    // Проверка тейк-профитов
                    if ($tp && is_array($tp)) {
                        foreach ($tp as $keyTp => $tpLevel) {
                            // Если свеча достигла уровня TP и этот уровень выше уже засчитанных
                            if ($candle['h'] >= $tpLevel && ($keyTp + 1) > $tpCount) {
                                $tpCount = $keyTp + 1;
                                if ($shiftSL && $tpCount >= $shiftSL && isset($tp[$shiftSL - 1])) {
                                    $sl = $lastPrice;  // переводим SL в BE
                                }
                                // Обновляем прибыль по TP на основании последнего достигнутого уровня
                                $realizedPercentChange = (($tpLevel - $lastPrice) / $lastPrice) * 100;
                            }
                        }
                    }

                    // Проверка SL – если пробился, сделка закрывается
                    if ($candle['l'] <= $sl) {
                        $slHit = true;
                        if (!$realizedPercentChange) {
                            $realizedPercentChange = (($sl - $lastPrice) / $lastPrice) * 100;
                        }
                        break; // Выходим, так как SL сработал
                    }
                } else { // Аналогичная логика для short
                    if ($effectiveTargetPrice === null || $candle['l'] < $effectiveTargetPrice) {
                        $effectiveTargetPrice = $candle['l'];
                    }
                    // Для short: если цена до первого TP поднялась до $actualClosePrice, считаем, что зацепилась
                    if ($tpCount == 0 && !$entryTouched && $candle['h'] >= $actualClosePrice) {
                        $entryTouched = true;
                    }

                    if ($tp && is_array($tp)) {
                        foreach ($tp as $keyTp => $tpLevel) {
                            if ($candle['l'] <= $tpLevel && ($keyTp + 1) > $tpCount) {
                                $tpCount = $keyTp + 1;
                                if ($shiftSL && $tpCount >= $shiftSL && isset($tp[$shiftSL - 1])) {
                                    $sl = $lastPrice;
                                }
                                $realizedPercentChange = (($lastPrice - $tpLevel) / $lastPrice) * 100;
                            }
                        }
                    }

                    if ($candle['h'] >= $sl) {
                        $slHit = true;
                        if (!$realizedPercentChange) {
                            $realizedPercentChange = (($lastPrice - $sl) / $lastPrice) * 100;
                        }
                        break;
                    }
                }
            }
        } else {
            // Если SL не указан, просто берём максимум/минимум
            if ($type == 'long') {
                $effectiveTargetPrice = max(array_column($candles, 'h'));
            } else {
                $effectiveTargetPrice = min(array_column($candles, 'l'));
            }
        }

        // Рассчитываем итоговое изменение цены
        if ($type == 'long') {
            $priceChange = (($effectiveTargetPrice - $lastPrice) / $lastPrice) * 100;
            $direction = ($priceChange >= 0) ? 'up' : 'down';
        } else {
            $priceChange = (($effectiveTargetPrice - $lastPrice) / $lastPrice) * 100;
            $direction = ($priceChange >= 0) ? 'up' : 'down';
        }

        return [
            'status' => true,
            'direction' => $direction,
            'percent_change' => round($priceChange, 2),
            'realized_percent_change' => round($realizedPercentChange, 2), // Фактически достигнутое изменение
            'target_price' => $effectiveTargetPrice,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'sl_hit' => $slHit,
            'tp_count' => $tpCount,
            'updated_sl' => $sl,  // обновлённый SL по условию shiftSL
            'entry_touched' => $entryTouched,  // новый параметр: касалась ли цена точки входа до первого TP
            'candles' => $candles,
            'market' => $market
        ];
    }

    //анализ OI для поиска нужно лимита изменения
    public static function oiBorderExchange(
        string $timeFrame = '5m',
        // 6) Настройки окна и порогов
        int    $barsCount = 720,
        int    $oiWindow = 3, // 3 * 5m = 15 минут
        int    $priceFutureWindow = 48,   // 24 * 5m = 2 часа
        float  $pumpThreshold = 2.5,  // % роста цены
        float  $dumpThreshold = -2.5, // % падения цены
        bool   $devMode = false,

    )
    {
        $marketCode = 'bybit';
        $timeMark = date("d.m.y H:i:s");

        // 0) Подгружаем старые накопленные события (если есть)
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/upload/bybitExchange/{$timeFrame}/oiBorderExchange.json";
        $existing = file_exists($filePath)
            ? (json_decode(file_get_contents($filePath), true)['RESPONSE'] ?? [])
            : [];

        $res = [];

        // проверяем не запускался ли только что обмен
        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/upload/bybitExchange/{$timeFrame}/oiBorderTimestamp.json"), true);
            if ($lastTimestapJson['TIMESTAMP'] && ((time() - $lastTimestapJson['TIMESTAMP']) < 360)) {
                devlogs("end, timestamp dif -" . ' - ' . $timeMark, "{$marketCode}/oiBorder{$timeFrame}");
                return;
            } else {
                file_put_contents(
                    $_SERVER['DOCUMENT_ROOT'] . "/upload/bybitExchange/{$timeFrame}/oiBorderTimestamp.json",
                    json_encode(['TIMESTAMP' => time(), "TIMEMARK" => $timeMark])
                );
            }
        }
        devlogs("Start oiBorderExchange batch - {$timeMark}", "{$marketCode}/oiBorder{$timeFrame}");


        $bybit = new \Maksv\Bybit\Bybit();
        $bybit->openConnection();

        // 2) Список символов
        $symbolsList = json_decode(
            file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'),
            true
        )['RESPONSE_EXCHENGE'] ?? [];

        $processed = 0;

        foreach ($symbolsList as $meta) {
            if (
                empty($meta['symbol']) ||
                !is_string($meta['symbol']) ||
                preg_match('/^(ETHUSDT-|ETH-|BTCUSDT-|BTC-|SOLUSDT-)/', $meta['symbol'])
                || ($meta['quoteCoin'] ?? '') !== 'USDT'
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
            $kline = $bybit->klineV5('linear', $symbol, $timeFrame, $barsCount, true, 300);
            if (empty($kline['result']['list'])) {
                devlogs("No candles for {$symbol}", "{$marketCode}/oiBorder{$timeFrame}");
                continue;
            }
            $priceData = array_column(array_reverse($kline['result']['list']), 4, 0);

            $oiData = [];
            $cursor = '';
            while (count($oiData) < $barsCount) {
                $batch = $bybit->openInterest($symbol, 'linear', $timeFrame, 200, $cursor, true, 300);
                if (empty($batch['result']['list'])) break;
                foreach ($batch['result']['list'] as $item) {
                    $oiData[(int)$item['timestamp']] = (float)$item['openInterest'];
                    if (count($oiData) >= $barsCount) break;
                }
                $cursor = $batch['result']['nextPageCursor'] ?? '';
                if (!$cursor) break;
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

            // 8) Обрезаем мапы до 80 элементов и приводим к списку
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

            //if ($processed > 40) break;
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
        file_put_contents($filePath, json_encode($output));

        $bybit->closeConnection();
        return $res;
    }


    public static function gmtTimeByTimestamp($milliseconds)
    {
        $seconds = $milliseconds / 1000;
        $microseconds = ($milliseconds % 1000) * 1000;
        $date = \DateTime::createFromFormat('U.u', sprintf('%.6F', $seconds));
        $date->modify("+$microseconds microseconds");
        $timestamp = $date->format("H:i d.m") ?? false;
        return $timestamp;
    }
}
