<?php

namespace Maksv\Bybit;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;

/**
 * Параметр    Минимум (консервативно)    Источник
 * 24h объём торгов    $1 млн – $2 млн    TradingView Screener
 * fxonbit.com
 * 24h объём торгов    ≥ $5 млн    Hummingbot / Prop-funds
 * fxonbit.com
 * Открытый интерес    ≥ $10 млн – $20 млн    Prop-фонды (Bookmap)
 * bookmap.com
 * Рыночная капитализация spot    ≥ $50 млн – $100 млн    CEX best practices
 */

class Exchange
{
    const SYMBOLS_STOP_LIST_MAIN = ['SOLUSDT', 'BTCUSDT', 'ETHUSDT', 'BRUSDT', 'USD1USDT', 'BADGERUSDT', 'A8USDT'];
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

    public static function screenerMFI(
        $devMode = false,
        $interval = '15m',
    )
    {

        $marketMode = 'bybit';
        $contracts = [
            [
                'name' => "BTCUSDT",//81%
                'mfiFastMa' => 10,
                'mfiSlowMa' => 44,
                'adxThreshold' => 31,
                'tpAtrMultipliers' => [1.8, 1.8, 4],
                'slAtrMultiplier' => 4.2,
                'trendMa' => 27,
                'scale' => 2,
                'maxLeverage' => 100,
            ],
            [
                'name' => "ETHUSDT",//70%
                'mfiFastMa' => 9,
                'mfiSlowMa' => 36,
                'adxThreshold' => 25,
                'tpAtrMultipliers' => [2.1, 3, 3.3],
                'slAtrMultiplier' => 3.7,
                'trendMa' => 30,
                'scale' => 2,
                'maxLeverage' => 100,
            ],
            [
                'name' => "XRPUSDT", //80%
                'mfiFastMa' => 9,
                'mfiSlowMa' => 36,
                'adxThreshold' => 30,
                'tpAtrMultipliers' => [2.2, 2.2, 4],
                'slAtrMultiplier' => 4,
                'trendMa' => 33,
                'scale' => 4,
                'maxLeverage' => 75,
            ],
            [
                'name' => "SOLUSDT",//74%
                'mfiFastMa' => 10,
                'mfiSlowMa' => 37,
                'adxThreshold' => 25,
                'tpAtrMultipliers' => [2.2, 2.2, 3],
                'slAtrMultiplier' => 4,
                'trendMa' => 38,
                'scale' => 2,
                'maxLeverage' => 100,
            ],
            [
                'name' => "LTCUSDT", //85%
                'mfiFastMa' => 11,
                'mfiSlowMa' => 44,
                'adxThreshold' => 29,
                'tpAtrMultipliers' => [1.9, 1.9, 3],
                'slAtrMultiplier' => 3.7,
                'trendMa' => 29,
                'scale' => 2,
                'maxLeverage' => 50,
            ],
            /*[
                'name' => "AVAXUSDT", //~89%
                'mfiFastMa' => 9,
                'mfiSlowMa' => 44,
                'adxThreshold' => 32,
                'tpAtrMultipliers' => [1.6, 1.6, 3],
                'slAtrMultiplier' => 4,
                'trendMa' => 22,
                'scale' => 3,
                'maxLeverage' => 50,
            ],*/
        ];

        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/screenerMFI/' . $interval . '/timestamp.json'), true);
            if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 120)) {
                devlogs("end, timestap dif -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screenerMFI' . $interval);
                return;
            } else {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/screenerMFI/' . $interval . '/timestamp.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => date("d.m.y H:i:s")]));
            }
        }
        devlogs("start -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screenerMFI' . $interval);

        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();
        $analyzeCnt = $cntSuccess = 0;

        $bybitScreenerMFIIblockId = 3;
        $bybitScreenerMFISectionCode = 'screenerMFI';
        $latestScreener = \Maksv\DataOperation::getLatestScreener($bybitScreenerMFIIblockId, $bybitScreenerMFISectionCode, 2);
        $marketInfo = \Maksv\Helpers\Trading::checkMarketImpulsInfo();

        foreach ($contracts as $symbol) {
            $analyzeCnt++;
            $screenerData = $res['screenerPump'] = $res['screenerDump'] = [];

            try {

                $screenerData['marketCode'] = $marketMode;
                $screenerData['contactSettings'] = $symbol;
                $screenerData['latestScreener'] = $latestScreener;

                $symbolName = $screenerData['symbolName'] = $symbol['name'];
                $symbolScale = $screenerData['symbolScale'] = intval($symbol['scale']) ?? 6;
                $symbolMaxLeverage = $screenerData['symbolMaxLeverage'] = floatval($symbol['maxLeverage']) ?? 10;
                $screenerData['interval'] = $interval;

                /*if ($latestScreener[$symbolName])
                    continue;*/

                $kline15m = $bybitApiOb->klineV5("linear", $symbolName, $interval, 402, true, 120);
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

                    $lastIndex = array_key_last($candles15m);
                    $actualClosePrice = $screenerData['actualClosePrice'] = $screenerData['entryTarget'] = $candles15m[$lastIndex]['c'] ?? false;
                    $screenerData['candles15m'] = array_slice($candles15m, -30);

                    $screenerData['actualAdx'] = $actualAdx = [];
                    try {
                        $adxData = \Maksv\TechnicalAnalysis::calculateADX($candles15m);
                        $screenerData['actualAdx'] = $actualAdx = $adxData[array_key_last($adxData)];
                        $screenerData['prevAdx'] = $prevAdx = $adxData[array_key_last($adxData) - 1];
                    } catch (\Exception $e) {
                        devlogs('ERR | err ' . $symbolName . ' - actualAdx 15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screenerMFI' . $interval);
                    }

                    $screenerData['actualMfi'] = $actualMfi = [];
                    try {
                        $mfiData = \Maksv\TechnicalAnalysis::calculateMFI($candles15m, $symbol['mfiFastMa'], $symbol['mfiSlowMa']);
                        $screenerData['actualMfi'] = $actualMfi = $mfiData[array_key_last($mfiData)];
                    } catch (\Exception $e) {
                        devlogs('ERR | ' . $symbolName . ' err - actualMfi 15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screenerMFI' . $interval);
                    }

                    $screenerData['actualMa'] = $actualMa = [];
                    try {
                        $screenerData['actualMa'] = $actualMa = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 3, $symbol['trendMa'], 20, 2) ?? [];
                    } catch (\Exception $e) {
                        devlogs('ERR | ' . $symbolName . ' err - ma 15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screenerMFI' . $interval);
                    }

                    $screenerData['actualATR'] = $actualATR = [];
                    try {
                        // Рассчитываем ATR по свечам
                        $ATRData = \Maksv\TechnicalAnalysis::calculateATR($candles15m);
                        $screenerData['actualATR'] = $actualATR = $ATRData[array_key_last($ATRData)] ?? null;
                    } catch (\Exception $e) {
                        devlogs('ERR | ' . $symbolName . ' err - atr 15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screenerMFI' . $interval);
                    }

                    /*$actualAdx['adx'] = 60;
                    $actualMfi['isLong'] = true;
                    $actualMa['isUptrend'] = true;*/

                    if (
                        $actualAdx['adx'] >= $symbol['adxThreshold']
                        && $prevAdx['adx'] >= $symbol['adxThreshold']
                        && $actualMfi['isLong']
                        && $actualMa['isUptrend']
                    ) {
                        $cntSuccess++;
                        $screenerData['isLong'] = true;
                        $screenerData['strategy'] = $symbolName . '/mfi/ma/adx';
                        $screenerData['SL'] = $screenerData['TP'] = $screenerData['recommendedEntry'] = false;

                        $processed = \Maksv\Helpers\Trading::processSignalMfi(
                            'long',
                            floatval($actualATR['atr']),
                            floatval($actualClosePrice),
                            $candles15m,
                            $symbolScale,
                            $symbol['tpAtrMultipliers'],
                            $symbol['slAtrMultiplier'],
                        );


                        //if ($processed !== false) {
                        if ($processed !== false) {
                            $screenerData = array_merge($screenerData, $processed);
                            $res['screenerPump'][$symbolName] = $screenerData;
                        } else {
                            $continueSymbols .= $symbolName . ', ';
                        }

                    } else if (
                        $actualAdx['adx'] >= $symbol['adxThreshold']
                        && $prevAdx['adx'] >= $symbol['adxThreshold']
                        && $actualMfi['isShort']
                        && !$actualMa['isUptrend']
                    ) {
                        $cntSuccess++;
                        $screenerData['isLong'] = false;
                        $screenerData['strategy'] = $symbolName . '/mfi/ma/adx';
                        $screenerData['SL'] = $screenerData['TP'] = $screenerData['recommendedEntry'] = false;

                        $processed = \Maksv\Helpers\Trading::processSignalMfi(
                            'short',
                            floatval($actualATR['atr']),
                            floatval($actualClosePrice),
                            $candles15m,
                            $symbolScale,
                            $symbol['tpAtrMultipliers'],
                            $symbol['slAtrMultiplier'],
                        );

                        if ($processed !== false) {
                            $screenerData = array_merge($screenerData, $processed);
                            $res['screenerDump'][$symbolName] = $screenerData;
                        } else {
                            $continueSymbols .= $symbolName . ', ';
                        }
                    }

                }
            } catch (\Exception $e) {
                devlogs('ERR ' . $symbolName . ' | err -' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screenerMFI' . $interval);
            }

            if ($res['screenerPump'] || $res['screenerDump']) {
                $cntSuccess++;

                $screenerData['resML']['mlBoard'] = $mlBoard = 0.75;
                if ($screenerData['isLong']) {
                    $screenerData['resML']['mlBoard'] = $mlBoard = 0.75;
                    $screenerData['marketMLName'] = 'longMl';
                    $actualStrategyName = 'screenerPump';
                } else {
                    $screenerData['resML']['mlBoard'] = $mlBoard = 0.75;
                    $screenerData['marketMLName'] = 'shortMl';
                    $actualStrategyName = 'screenerDump';
                }

                $screenerData['resML']['marketMl'] = $marketMl = $marketInfo[$screenerData['marketMLName']]['probabilities'][1] ?? false;
                $screenerData['resML']['signalMl'] = $signalMl = $screenerData['actualMlModel']['probabilities'][1] ?? false;
                $screenerData['resML']['totalMl'] = $totalMl = false;
                if ($marketMl && $signalMl) {
                    $screenerData['resML']['totalMl'] = $totalMl = ($marketMl + $signalMl) / 2;
                }

                $screenerData['leverage'] = '10X';
                $tpCount = $screenerData['tpCount']['longTpCount'] = $screenerData['tpCount']['shortTpCount'] = 2;
                $screenerData['TP'] = array_slice($screenerData['TP'], 0, $tpCount);

                //после всех мутаций снимаем копию
                $res[$actualStrategyName][$symbolName] = $screenerData;

                if (!$latestScreener[$symbolName]) {

                    if ($signalMl >= $mlBoard) {
                        \Maksv\DataOperation::sendScreener($screenerData, true, '@infoCryptoHelperScreenerMFI');
                    } else {
                        devlogs(
                            ' skip mfi Screener tg, ml reason  | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
                            $marketMode . '/screenerMFI' . $interval
                        );
                    }

                    //cохраняем отдельно по бирже
                    $actualStrategy = [
                        "TIMEMARK" => date("d.m.y H:i:s"),
                        "STRATEGIES" => $res,
                        "INFO" => [
                            'BTC_INFO' => $marketInfo,
                        ],
                        "EXCHANGE_CODE" => 'screenerMFI'
                    ];

                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/screenerMFI/' . $interval . '/actualStrategy.json', json_encode($actualStrategy));
                    try {
                        $writeRes = \Maksv\DataOperation::saveSignalToIblock($interval, 'bybit', 'screenerMFI');
                        devlogs('screener write' . $writeRes['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screenerMFI' . $interval);
                    } catch (\Exception $e) {
                        $errText = 'ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s");
                        \Maksv\DataOperation::sendErrorInfoMessage($errText, 'screener', $marketMode . '/screenerMFI' . $interval);
                        devlogs($errText, $marketMode . '/screenerMFI' . $interval);
                    }
                } else {
                    devlogs(
                        ' skip signal, latest reason  | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
                        $marketMode . '/screenerMFI' . $interval
                    );
                }


            }
        }

        $bybitApiOb->closeConnection();

        devlogs("end (analyzeCnt " . $analyzeCnt . ") (cntSuccess " . $cntSuccess . ") -" . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screenerMFI' . $interval);
        devlogs('_____________________________________', $marketMode . '/screenerMFI' . $interval);

        return $res;
    }
    
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
            sleep(20);
            devlogs('sleep 20' . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
        } else {
            sleep(10);
            devlogs('sleep 10' . ' - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
        }

        //получаем контракты, которые будем анализировать
        $exchangeBybitSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeBinanceSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeOkxSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/okxExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];

        $binanceSymbolsList = array_column($exchangeBinanceSymbolsList, 'symbol') ?? [];
        $bybitSymbolsList = array_column($exchangeBybitSymbolsList, 'symbol') ?? [];
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

        $betaForeverIblockId = 9;
        $betaForeverSectionCode = 'normal_ml';
        $latestScreenerBetaForever = \Maksv\DataOperation::getLatestScreener($betaForeverIblockId, $betaForeverSectionCode);

        $analyzeCnt = $cnt = $cntSuccess = 0;

        $dataFileSeparateVolume = $_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/summaryVolumeExchange.json';
        $existingDataSeparateVolume = file_exists($dataFileSeparateVolume) ? json_decode(file_get_contents($dataFileSeparateVolume), true)['RESPONSE_EXCHENGE'] ?? [] : [];
        $separateVolumes = $analyzeVolumeSignalRes ?? [];
        //$btcInfo = self::checkBtcImpulsInfo();
        $btcInfo = \Maksv\Helpers\Trading::checkMarketImpulsInfo();

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
                if ($cnt % 12 === 0)
                    $latestScreener = \Maksv\DataOperation::getLatestScreener($bybitScreenerIblockId);

                if ($cnt % 12 === 0)
                    $latestScreenerBetaForever = \Maksv\DataOperation::getLatestScreener($betaForeverIblockId, $betaForeverSectionCode);

                if ($cnt % 40 === 0)
                    $btcInfo = \Maksv\Helpers\Trading::checkMarketImpulsInfo();

                $screenerData['latestScreener'] = $latestScreener;
                $screenerData['latestScreenerBetaForever'] = $latestScreenerBetaForever;

                /*if ($latestScreener[$symbolName]) {
                    $repeatSymbols .= $symbolName . ',';
                    continue;
                }*/

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

                $summaryOpenInterestOb = \Maksv\Bybit\Exchange::getSummaryOpenInterest($symbolName, $binanceApiOb, $bybitApiOb, $okxApiOb, $binanceSymbolsList, $bybitSymbolsList, $okxSymbolsList, $intervalsOImap[$interval]);
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
                    $screenerData['actualMacdDivergence'] = $actualMacdDivergence = \Maksv\Helpers\Trading::checkMultiMACD(
                        $candles15m,
                        '15m',
                        ['5m' => 11, '15m' => 11, '30m' => 11, '1h' => 14, '4h' => 8, '1d' => 6]
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
                        $ma400His = \Maksv\TechnicalAnalysis::getMACrossHistory($candles15m, 12, 400, 10) ?? [];
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

                $kline1h = $bybitApiOb->klineV5("linear", $symbolName, '1h', $barsCount, true, 120);
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
                    //$continueSymbols .= $symbol['symbol'] . ', ';
                    //devlogs('CONTINUE ' . $symbolName . ' |  adx 1h ' . $actualAdx1h['adx'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
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

                //$maDistance = 2.5;
                $maDistance = \Maksv\Helpers\Trading::getMaDistance($actualClosePrice) ?? 2.5;

                if (
                    (($summaryOIBybit >= $longOiLimit) && ($summaryOI >= 0.01))
                    && $btcInfo['isLong']
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
                    && (!$actualMacdDivergence['shortDivergenceTypeAr']['regular']/* && !$actualMacdDivergence['shortDivergenceTypeAr']['hidden']*/)
                ) {
                    $screenerData['isLong'] = true;

                    // помечаем стратегию
                    if ($actualMacd['isLong']) {
                        if ($actualMacdDivergence['longDivergenceTypeAr']['regular']/* || $actualMacdDivergence['longDivergenceTypeAr']['hidden']*/)
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
                    && (!$actualMacdDivergence['longDivergenceTypeAr']['regular']/* && !$actualMacdDivergence['longDivergenceTypeAr']['hidden']*/)
                ) {
                    $screenerData['isLong'] = false;

                    if ($actualMacd['isShort']) {
                        if ($actualMacdDivergence['shortDivergenceTypeAr']['regular'] /*|| $actualMacdDivergence['shortDivergenceTypeAr']['hidden']*/)
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
                        $separateVolumes = array_reverse(\Maksv\Helpers\Trading::aggregateSumVolume5mTo15m($separateVolumes)) ?? [];
                        $cvdChartGen = new \Maksv\Charts\CvdChartGenerator();
                        $screenerData['tempChartPath'][] = $tempCVDChartPath = $chartsDir . time() . '_' . $interval . '_cvd' . '.png';
                        $cvdChartGen->generateChart($separateVolumes, $symbolName, '15m', $tempCVDChartPath);
                    } catch (\Throwable $e) {
                        devlogs('ERR ' . $symbolName . ' | err - PriceChartGenerator' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                    }

                    $screenerData['resML']['mlBoard'] = $mlBoard = 0.71;
                    if ($screenerData['isLong']) {
                        $screenerData['resML']['mlBoard'] = $mlBoard = 0.74; // 0.73
                        $screenerData['marketMLName'] = 'longMl';
                        $actualStrategyName = 'screenerPump';
                    } else {
                        $screenerData['resML']['mlBoard'] = $mlBoard = 0.74; // 0.73
                        $screenerData['marketMLName'] = 'shortMl';
                        $actualStrategyName = 'screenerDump';
                    }

                    $screenerData['resML']['marketMl'] = $marketMl = $btcInfo[$screenerData['marketMLName']]['probabilities'][1] ?? false;
                    $screenerData['resML']['signalMl'] = $signalMl = $screenerData['actualMlModel']['probabilities'][1] ?? false;
                    $screenerData['resML']['totalMl'] = $totalMl = false;
                    if ($marketMl && $signalMl) {
                        $screenerData['resML']['totalMl'] = $totalMl = ($marketMl + $signalMl) / 2;
                    }

                    //после всех мутаций снимаем копию
                    $res[$actualStrategyName][$symbolName] = $screenerData;

                    //screener
                    if (
                        !$latestScreener[$symbolName]
                        && $marketMl > 0.65
                        && $signalMl > 0.65
                    ) {
                        \Maksv\DataOperation::sendScreener($screenerData, true, '@infoCryptoHelperScreener');

                        //cохраняем отдельно по бирже
                        $actualStrategy = [
                            "TIMEMARK" => date("d.m.y H:i:s"),
                            "STRATEGIES" => $res,
                            "INFO" => [
                                'BTC_INFO' => $btcInfo,
                            ],
                            "EXCHANGE_CODE" => 'screener'
                        ];

                        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketMode . 'Exchange/screener/' . $interval . '/actualStrategy.json', json_encode($actualStrategy));
                        try {
                            $writeRes = \Maksv\DataOperation::saveSignalToIblock($interval, 'bybit', 'screener');
                            devlogs('screener write' . $writeRes['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), $marketMode . '/screener' . $interval);
                        } catch (\Exception $e) {
                            $errText = 'ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s");
                            \Maksv\DataOperation::sendErrorInfoMessage($errText, 'screener', $marketMode . '/screener' . $interval);
                            devlogs($errText, $marketMode . '/screener' . $interval);
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
                        //&& !$latestScreener[$symbolName]
                        && !$latestScreenerBetaForever[$symbolName]
                        //&& $interval != '5m'
                    ) {
                        devlogs(
                            'normal ml approve | ' . $totalMl . ' > ' . $mlBoard . ' | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
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
                                'BTC_INFO' => $btcInfo,
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
                            'normal ml skip | total ' . $totalMl . ' | ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"),
                            $marketMode . '/screener' . $interval
                        );
                    }

                    //high ml
                    $screenerData = $res[$actualStrategyName][$symbolName];

                    if (
                        $marketMl
                        && $signalMl
                        && $totalMl
                        && $marketMl > 0.65
                        && $signalMl > 0.65
                        && $totalMl >= 0.79
                        //&& !$latestScreener[$symbolName]
                        && !$latestScreenerBetaForever[$symbolName]
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
                                'BTC_INFO' => $btcInfo,
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
                sleep(10);
                devlogs('sleep 10' . ' - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
            } else {
                sleep(5);
                devlogs('sleep 5' . ' - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
            }
        }

        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();
        $binanceApiOb = new \Maksv\Binance\BinanceFutures();
        $binanceApiOb->openConnection();
        $okxApiOb = new \Maksv\Okx\OkxFutures();
        $okxApiOb->openConnection();
        $bingxApiOb = new \Maksv\Bingx\BingxFutures();
        $bingxApiOb->openConnection();

        //получаем контракты, которые будем анализировать
        $exchangeBybitSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeBinanceSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeOkxSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/okxExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeBingxSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bingxExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];

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
            $cmc = new \Maksv\Coinmarketcap\Request();
            $capMap = [];  // здесь накопим [SYMBOL => marketCap]
            $chunks = array_chunk($allBaseCoins, 99);
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
                    "EXCHANGE_CODE" => 'okx'
                ];
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/okxExchange/derivativeBaseCoin.json', json_encode($dataOkxInfo));
                //$exchangeBinanceSymbolsList = $exchangeSymbolsResp['result']['list'];
            }

            //обновляем bingx файлик
            $bingxFuturesSymbols = $bingxApiOb->getFuturesContracts()['data'];
            if ($bingxFuturesSymbols) {
                $dataBingxInfo = [
                    "TIMEMARK" => $timeMark,
                    "RESPONSE_EXCHENGE" => $bingxFuturesSymbols,
                    "EXCHANGE_CODE" => 'bingX'
                ];
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bingxExchange/derivativeBaseCoin.json', json_encode($dataBingxInfo));
            }


            if ($timeFrame == '1d') {
                echo '<pre>';
                var_dump('tech OK');
                echo '</pre>';
                return true;
            }
        }

        $binanceSymbolsList = array_column($exchangeBinanceSymbolsList, 'symbol') ?? [];
        $bybitSymbolsList = array_column($exchangeBybitSymbolsList, 'symbol') ?? [];
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

        $btcInfo = \Maksv\Helpers\Trading::checkMarketImpulsInfo();

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
                if ($countReq % 40 === 0)
                    $btcInfo = \Maksv\Helpers\Trading::checkMarketImpulsInfo();

                //периодически обновляем данные
                if ($countReq % 15 === 0)
                    $latestSignals = \Maksv\DataOperation::getLatestSignals($timeFrame, 'master');

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
                    $summaryOpenInterestOb = self::getSummaryOpenInterest($symbolName, $binanceApiOb, $bybitApiOb, $okxApiOb, $binanceSymbolsList, $bybitSymbolsList, $okxSymbolsList, $intervalsOImap[$timeFrame]);
                    //$summaryOpenInterestOb = self::getSummaryOpenInterest($symbolName, $binanceApiOb, $bybitApiOb, $binanceSymbolsList, $bybitSymbolsList, $intervalsOImap[$timeFrame]);
                    if (/*$summaryOpenInterestOb['summaryOI'] || */ $summaryOpenInterestOb['summaryOIBybit']) {

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

                                } catch (\Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - macdData' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualMacd'] = $actualMacd;

                                $actualImpulsMacd = [];
                                try {
                                    $impulseMACD = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles) ?? false;
                                    if ($impulseMACD && is_array($impulseMACD))
                                        $actualImpulsMacd = $impulseMACD[array_key_last($impulseMACD)];
                                } catch (\Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - actualImpulsMacd' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualImpulsMacd'] = $actualImpulsMacd;

                                //ma26
                                $crossMA = $crossMA100 = $crossMA200 = $crossMA400 = [];
                                try {
                                    $crossMA = \Maksv\TechnicalAnalysis::checkMACross($candles, 9, 26, 20, 2) ?? [];
                                } catch (\Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - ma 26' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                $crossMAVal = 0;
                                if ($crossMA['is_reversal'] && $crossMA['isUptrend'])
                                    $crossMAVal = 1;
                                else if ($crossMA['is_reversal'] && !$crossMA['isUptrend'])
                                    $crossMAVal = 2;

                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['crossMA'] = $crossMA;

                                $actualStochastic = [];
                                try {
                                    $stochasticOscillatorData = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles) ?? false;
                                    if ($stochasticOscillatorData && is_array($stochasticOscillatorData))
                                        $actualStochastic = $stochasticOscillatorData[array_key_last($stochasticOscillatorData)];

                                } catch (\Exception $e) {
                                    devlogs('ERR | ' . $symbolName . ' actualStochastic' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

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
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualMacdDivergence'] = $actualMacdDivergence = \Maksv\Helpers\Trading::checkMultiMACD(
                                        $candles15m,
                                        '15m',
                                        ['5m' => 11, '15m' => 11, '30m' => 11, '1h' => 14, '4h' => 8, '1d' => 6]
                                    );
                                } catch (\Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - actualMacdDivergence' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                // supertrend 15m
                                $actualSupertrend15m = $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualSupertrend15m'] = [];
                                try {
                                    $supertrendData15m = \Maksv\TechnicalAnalysis::calculateSupertrend($candles15m, 10, 3) ?? false; // длина 10, фактор 3
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualSupertrend5m'] = $actualSupertrend15m = $supertrendData15m[array_key_last($supertrendData15m)] ?? false;
                                } catch (\Exception $e) {
                                    devlogs('ERR | err - Supertrend 15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                //atr 15m
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualATR'] = $actualATR = [];
                                try {
                                    // Рассчитываем ATR по свечам
                                    $ATRData = \Maksv\TechnicalAnalysis::calculateATR($candles15m);
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualATR'] = $actualATR = $ATRData[array_key_last($ATRData)] ?? null;
                                } catch (\Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - ATR' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                // other ma, always 15m candles
                                try {
                                    $crossMA100 = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 9, 100, 20, 2) ?? [];
                                } catch (\Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | err - ma 100' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['cross100MA'] = $crossMA100;

                                try {
                                    $crossMA200 = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 9, 200, 20, 2) ?? [];
                                } catch (\Exception $e) {
                                    devlogs('ERR ' . $symbolName . ' | ma 200 ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['cross200MA'] = $crossMA200;

                                try {
                                    $crossMA400 = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 9, 400, 20, 2) ?? [];
                                } catch (\Exception $e) {
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
                                } catch (\Exception $e) {
                                    devlogs('ERR | err - Supertrend 5m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                $actualAdx5m = false;
                                try {
                                    $adxData5m = \Maksv\TechnicalAnalysis::calculateADX($candles5m) ?? [];
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualAdx5m'] = $actualAdx5m = $adxData5m[array_key_last($adxData5m)];
                                } catch (\Exception $e) {
                                    devlogs('ERR | ' . $symbolName . ' adx 5m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }

                                if (
                                    $actualAdx5m
                                    && ($actualAdx5m['adx'] < 18)
                                ) {
                                    continue;
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
                                } catch (\Exception $e) {
                                    devlogs('ERR | ' . $symbolName . ' analyzeOrderBook' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                }*/

                                $actualAdx = false;
                                try {
                                    $adxData = \Maksv\TechnicalAnalysis::calculateADX($candles15m) ?? [];
                                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualAdx'] = $actualAdx = $adxData[array_key_last($adxData)];
                                } catch (\Exception $e) {
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
                                $kline1h = $bybitApiOb->klineV5("linear", $symbolName, '1h', $barsCount, true, 120);
                                $crossMA400_1h = $crossMA100_1h = [];


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

                                    } catch (\Exception $e) {
                                        devlogs('ERR | ' . $symbolName . ' actualStochastic1h' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                    }

                                    try {
                                        $adxData1h = \Maksv\TechnicalAnalysis::calculateADX($candles1h) ?? [];
                                        $actualAdx1h = $adxData1h[array_key_last($adxData1h)];
                                    } catch (\Exception $e) {
                                        devlogs('ERR | ' . $symbolName . ' err - adx 1h' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                    }

                                    if (is_array($candles1h) && count($candles1h) >= 802) {
                                        try {
                                            $crossMA400_1h = \Maksv\TechnicalAnalysis::checkMACross($candles1h, 9, 400, 20, 2) ?? [];
                                        } catch (\Exception $e) {
                                            devlogs('ERR | ' . $symbolName . '  err - ma400 candles1h' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                                        }
                                    }

                                    if (is_array($candles1h) && count($candles1h) >= 202) {
                                        try {
                                            $crossMA100_1h = \Maksv\TechnicalAnalysis::checkMACross($candles1h, 9, 100, 20, 2) ?? [];
                                        } catch (\Exception $e) {
                                            devlogs('ERR | ' . $symbolName . '  err - ma100 candles1h' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
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

                                    'lastCrossMA100_1h' => $crossMA100_1h,
                                    'lastCrossMA400_1h' => $crossMA400_1h,

                                    'candles15m' => array_slice($candles15m, -30),

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
                                    'actualStochastic' => $actualStochastic,
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
                                    ],

                                    'marketCode' => $marketCode,
                                    'marketInfo' => $btcInfo

                                ];

                                if (in_array($symbolName, ['BTCUSDT', 'ETHUSDT'])) {
                                    $actualOpportunities['headCoin'][$symbolName] = $opportunityData;
                                    continue;
                                }

                                $analysisSymbolCount++;
                                $analysisSymbols .= $symbolName . ' ';
                                //$maDistance = 2.5;
                                $maDistance = \Maksv\Helpers\Trading::getMaDistance($actualClosePrice) ?? 2.5;


                                //dev
                                /*   if (
                                       $btcInfo['isLong']
                                       && $analyzeVolumeSignalRes['isLong']
                                       && \Maksv\Helpers\Trading::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'long')
                                       && \Maksv\Helpers\Trading::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'long')
                                       && \Maksv\Helpers\Trading::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'long')
                                       && \Maksv\Helpers\Trading::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'long')
                                   ) {
                                       $opportunityData['strategy'] = 'dev';
                                       $actualOpportunities['masterPump'][$symbolName] = $opportunityData;
                                   }

                                   if (
                                       $btcInfo['isShort']
                                       && $analyzeVolumeSignalRes['isShort']
                                       && \Maksv\Helpers\Trading::checkMaCondition($crossMA,  $actualClosePrice, $maDistance, 'short')
                                       && \Maksv\Helpers\Trading::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'short')
                                       && \Maksv\Helpers\Trading::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'short')
                                       && \Maksv\Helpers\Trading::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'short')
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
                                    && (!$actualMacdDivergence['shortDivergenceTypeAr']['regular']/* && !$actualMacdDivergence['shortDivergenceTypeAr']['hidden']*/)
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100_1h, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400_1h, $actualClosePrice, $maDistance, 'long')
                                ) {
                                    /*if ($crossMA100['isLong']) {
                                        $opportunityData['strategy'] = 'MA100xEMA9/MACD';
                                        $actualOpportunities['masterPump'][$symbolName] = $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                    } else*/
                                    if ($crossMA200['isLong']) {
                                        $opportunityData['strategy'] = 'MA200xEMA9/MACD';
                                        $actualOpportunities['masterPump'][$symbolName] = $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                    } else if ($crossMA400['isLong']) {
                                        $opportunityData['strategy'] = 'MA400xEMA9/MACD';
                                        $actualOpportunities['masterPump'][$symbolName] = $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                    }
                                }

                                if (
                                    $timeFrame == '15m' // $ma400_1h $ma100_1h
                                    && $btcInfo['isShort']
                                    && $analyzeVolumeSignalRes['isShort']
                                    && (($summaryOIBybit <= $shortOiLimit) && ($summaryOI <= -0.01))
                                    && (!$actualMacdDivergence['longDivergenceTypeAr']['regular']/* && !$actualMacdDivergence['longDivergenceTypeAr']['hidden']*/)
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100_1h, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400_1h, $actualClosePrice, $maDistance, 'short')
                                ) {
                                    /*if ($crossMA100['isShort']) {
                                        $opportunityData['strategy'] = 'MA100xEMA9/MACD';
                                    } else */
                                    if ($crossMA200['isShort']) {
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
                                    && (!$actualMacdDivergence['shortDivergenceTypeAr']['regular']/* && !$actualMacdDivergence['shortDivergenceTypeAr']['hidden']*/)
                                    && (($summaryOIBybit >= $longOiLimit) && ($summaryOI >= 0.01))
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100_1h, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400_1h, $actualClosePrice, $maDistance, 'long')
                                ) {
                                    $opportunityData['strategy'] = 'macd/!d/MAfar';
                                    $actualOpportunities['masterPump'][$symbolName] = $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                }

                                if (
                                    $actualMacd['isShort']
                                    && $btcInfo['isShort']
                                    && $analyzeVolumeSignalRes['isShort']
                                    && (!$actualMacdDivergence['longDivergenceTypeAr']['regular']/* && !$actualMacdDivergence['longDivergenceTypeAr']['hidden']*/)
                                    && (($summaryOIBybit <= $shortOiLimit) && ($summaryOI <= -0.01))
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100_1h, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400_1h, $actualClosePrice, $maDistance, 'short')
                                ) {
                                    $opportunityData['strategy'] = 'macd/!d/MAfar';
                                    $actualOpportunities['masterDump'][$symbolName] = $actualOpportunities['allDump'][$symbolName] = $opportunityData;
                                }

                                //alerts, master MacdI, !divergence, direct, MAfar
                                if (
                                    $actualImpulsMacd['isLong']
                                    //&& $timeFrame != '30m'
                                    && $btcInfo['isLong']
                                    && $analyzeVolumeSignalRes['isLong']
                                    && (!$actualMacdDivergence['shortDivergenceTypeAr']['regular']/* && !$actualMacdDivergence['shortDivergenceTypeAr']['hidden']*/)
                                    && (($summaryOIBybit >= $longOiLimit) && ($summaryOI >= 0.01))
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100_1h, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400_1h, $actualClosePrice, $maDistance, 'long')
                                ) {
                                    $opportunityData['strategy'] = 'macdI/direct/!d/MAfar';
                                    $actualOpportunities['masterPump'][$symbolName] = $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                }

                                if (
                                    $actualImpulsMacd['isShort']
                                    //&& $timeFrame != '30m'
                                    && $btcInfo['isShort']
                                    && $analyzeVolumeSignalRes['isShort']
                                    && (!$actualMacdDivergence['longDivergenceTypeAr']['regular']/* && !$actualMacdDivergence['longDivergenceTypeAr']['hidden']*/)
                                    && (($summaryOIBybit <= $shortOiLimit) && ($summaryOI <= -0.01))
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100_1h, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400_1h, $actualClosePrice, $maDistance, 'short')
                                ) {
                                    $opportunityData['strategy'] = 'macdI/direct/!d/MAfar';
                                    $actualOpportunities['masterDump'][$symbolName] = $actualOpportunities['allDump'][$symbolName] = $opportunityData;
                                }

                                //alerts, master, macd cross and divergence
                                if (
                                    $actualMacd['isLong']
                                    && $btcInfo['isLong']
                                    && $analyzeVolumeSignalRes['isLong']
                                    && (($summaryOIBybit >= $longOiLimit) && ($summaryOI >= 0.01))
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100_1h, $actualClosePrice, $maDistance, 'long')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400_1h, $actualClosePrice, $maDistance, 'long')
                                    && (!$actualMacdDivergence['shortDivergenceTypeAr']['regular']/* && !$actualMacdDivergence['shortDivergenceTypeAr']['hidden']*/)
                                    && ($actualMacdDivergence['longDivergenceTypeAr']['regular']/* || $actualMacdDivergence['longDivergenceTypeAr']['hidden']*/)
                                ) {
                                    $opportunityData['strategy'] = 'macdD/macdC/MAfar';
                                    $actualOpportunities['allPump'][$symbolName] = $actualOpportunities['masterPump'][$symbolName] = $opportunityData;
                                }

                                if (
                                    $actualMacd['isShort']
                                    && $btcInfo['isShort']
                                    && $analyzeVolumeSignalRes['isShort']
                                    && (($summaryOIBybit <= $shortOiLimit) && ($summaryOI <= -0.01))
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA200, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA100_1h, $actualClosePrice, $maDistance, 'short')
                                    && \Maksv\Helpers\Trading::checkMaCondition($crossMA400_1h, $actualClosePrice, $maDistance, 'short')
                                    && (!$actualMacdDivergence['longDivergenceTypeAr']['regular']/* && !$actualMacdDivergence['longDivergenceTypeAr']['hidden']*/)
                                    && ($actualMacdDivergence['shortDivergenceTypeAr']['regular']/* || $actualMacdDivergence['shortDivergenceTypeAr']['hidden']*/)
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

                } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            devlogs('ERR - alert sort Err | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
        }

        try {
            uasort($actualOpportunities['masterPump'], function ($a, $b) {
                return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
            });

            uasort($actualOpportunities['masterDump'], function ($a, $b) {
                return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
            });
        } catch (\Exception $e) {
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
                //$btcInfo['risk'] = 3.5;
                $processed = \Maksv\Helpers\Trading::processSignal(
                    'long',
                    floatval($pump['actualATR']['atr']),
                    floatval($pump['actualClosePrice']),
                    $pump['candles15m'],
                    $pump['actualSupertrend5m'],
                    $pump['actualSupertrend15m'],
                    $pump['actualMacdDivergence'],
                    $pump['symbolScale'],
                    $pump['atrMultipliers'],
                    $pump['marketInfo'],//$btcInfo,
                    $pump['symbolName'],
                    "$marketCode/bybitExchange$timeFrame"
                );

                if ($processed === false) {
                    $unsetSymbols[] = $pump['symbolName'];
                    continue;
                }

                // Вешаем на $pump всё, что вернула функция
                $pump = array_merge($pump, $processed);

                $pump['resML']['totalMl'] = $totalMl = $marketMl = $signalMl = 0;
                $pump['resML']['marketMl'] = $marketMl = $btcInfo['longMl']['probabilities'][1] ?? 0;
                $pump['resML']['signalMl'] = $signalMl = $pump['actualMlModel']['probabilities'][1] ?? 0;
                if ($marketMl && $signalMl) {
                    $pump['resML']['totalMl'] = $totalMl = ($marketMl + $signalMl) / 2;
                }
                $pump['resML']['mlBoard'] = $mlBoard = $btcInfo['mlBoard'] ?? 0.71;

                /* if (
                     !$marketMl || !$signalMl || $totalMl
                     || $marketMl <= 0.65 || $signalMl <= 0.65 || $totalMl < $mlBoard
                 ) {
                     $unsetSymbols[] = $pump['symbolName'];
                     continue;
                 }*/

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
                //$btcInfo['risk'] = 3.5;
                $processed = \Maksv\Helpers\Trading::processSignal(
                    'short',
                    floatval($dump['actualATR']['atr']),
                    floatval($dump['actualClosePrice']),
                    $dump['candles15m'],
                    $dump['actualSupertrend5m'],
                    $dump['actualSupertrend15m'],
                    $dump['actualMacdDivergence'],
                    $dump['symbolScale'],
                    $dump['atrMultipliers'],
                    $dump['marketInfo'],//$btcInfo,
                    $dump['symbolName'],
                    "$marketCode/bybitExchange$timeFrame"
                );

                if ($processed === false) {
                    $unsetSymbols[] = $dump['symbolName'];
                    continue;
                }

                $dump = array_merge($dump, $processed);

                $dump['resML']['totalMl'] = $totalMl = $marketMl = $signalMl = 0;
                $dump['resML']['marketMl'] = $marketMl = $btcInfo['shortMl']['probabilities'][1] ?? 0;
                $dump['resML']['signalMl'] = $signalMl = $dump['actualMlModel']['probabilities'][1] ?? 0;
                if ($marketMl || $signalMl) {
                    $dump['resML']['totalMl'] = $totalMl = ($marketMl + $signalMl) / 2;
                }
                $dump['resML']['mlBoard'] = $mlBoard = $btcInfo['mlBoard'] ?? 0.71;

                /*if (
                    !$marketMl || !$signalMl || $totalMl
                    || $marketMl <= 0.65 || $signalMl <= 0.65 || $totalMl < $mlBoard
                ) {
                    $unsetSymbols[] = $pump['symbolName'];
                    continue;
                }*/
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
            } catch (\Exception $e) {
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
                } catch (\Exception $e) {
                    devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
                }
            }
        }

        devlogs('end - ' . date("d.m.y H:i:s"), $marketCode . '/bybitExchange' . $timeFrame);
        devlogs('_____________________________________', $marketCode . '/bybitExchange' . $timeFrame);
        $bybitApiOb->closeConnection();
        $binanceApiOb->closeConnection();
        $okxApiOb->closeConnection();
        $bingxApiOb->closeConnection();

        return "bybitExchange" . $timeFrame . "();";
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
        } catch (\Exception $e) {
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
            } catch (\Exception $e) {
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
        //$separateVolume = array_reverse(\Maksv\Helpers\Trading::aggregateSumVolume5mTo15m($volumesData[$symbolName]['resBybit'])) ?? [];

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
                } catch (\Exception $e) {
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

        if (in_array($tf, ['15m', '1h'])) {
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
    
    /*public static function getSummaryOpenInterest(
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
    }*/

    protected static function calculateOIChange($data)
    {
        if (count($data) < 2) return 0;
        $prev = reset($data)['openInterest'];
        $actual = end($data)['openInterest'];
        return $prev != 0 ? round((($actual - $prev) / $prev) * 100, 2) : 0;
    }

    public static function getSummaryOpenInterest(
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
                        devlogs("Err bybit oi process = 0  " . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterest');
                        devlogs($oiItem, 'getSummaryOpenInterest');
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
        $binanceSkippedFlag = false;
        if (in_array($symbolName, $binanceSymbolsList)) {
            $getOpenInterestHist = $binanceApiOb->getOpenInterestHist($symbolName, $startTime, $endTime, '5m', 120, $useCache, $cacheTime) ?? [];
            $resBinance['resp'] = $getOpenInterestHist;
            if (!empty($getOpenInterestHist)) {
                foreach ($getOpenInterestHist as $oiItem) {
                    try {
                        // сначала проверяем что элемент — массив и имеет числовой timestamp
                        if (!is_array($oiItem) || !isset($oiItem['timestamp'])) {
                            devlogs("Bad Binance OI item (not array / no numeric timestamp) for {$symbolName}", 'getSummaryOpenInterest');
                            devlogs($oiItem, 'getSummaryOpenInterest');
                            $binanceSkippedFlag = true; // помечаем как failed при первой плохой записи
                            continue;
                        }

                        // Безопасное приведение к числу
                        $timestamp = (double)$oiItem['timestamp'];

                        // Проверка open interest (как у тебя было)
                        if (empty($oiItem['sumOpenInterest']) || floatval($oiItem['sumOpenInterest']) == 0) {
                            devlogs("Err binance oi process = 0  " . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterest');
                            devlogs($oiItem, 'getSummaryOpenInterest');
                            $binanceSkippedFlag = true;
                            continue;
                        }

                        $resBinance['res'][$timestamp] = [
                            'datetime' => date("Y-m-d H:i:s", floor($timestamp / 1000)),
                            'timestamp' => $timestamp,
                            'openInterest' => floatval($oiItem['sumOpenInterest']),
                        ];
                    } catch (\Throwable $e) {
                        $errText = sprintf(
                            "ERR  | File: %s | Line: %d | Error: %s | Time: %s",
                            $e->getFile(),
                            $e->getLine(),
                            $e->getMessage(),
                            date("d.m.y H:i:s")
                        );
                        \Maksv\DataOperation::sendErrorInfoMessage($errText, 'getSummaryOpenInterest()', 'binance OI');
                        devlogs("ERR binance oi ts  " . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterest');
                        devlogs($oiItem, 'getSummaryOpenInterest');
                        $binanceSkippedFlag = true;
                        continue;
                    }
                }

            }
        }

        // --- Получение OI с Okx --- (сначала проверяем есть ли такой у okx)
        $resOkx['resp'] = $resOkx['res'] = [];
        $okxSkippedFlag = false;
        if (in_array($symbolName, $okxSymbolsList)) {
            $okxSymbolName = array_search($symbolName, $okxSymbolsList);
            if ($okxSymbolName !== false) {
                $oiHistRespOkx = $okxApiOb->getOpenInterestHist($okxSymbolName, $startTime, $endTime, '5m', 120, $useCache, $cacheTime)['data'] ?? [];
                $resOkx['resp'] = $oiHistRespOkx;
                if (!empty($oiHistRespOkx)) {
                    foreach ($oiHistRespOkx as $oiItem) {
                        $timestamp = (double)$oiItem[0];
                        if ((float)$oiItem[1] == 0) {
                            $okxSkippedFlag = true;
                            devlogs("Err okx oi process = 0  " . $okxSymbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterest');
                            devlogs($oiItem, 'getSummaryOpenInterest');
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
            $resBybit['res'] = [];
            $summaryOIBybit = 0;
            devlogs("Err bybit 100  " . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterest');
        }

        if (abs($summaryOIBinance) === 100 || $binanceSkippedFlag === true) {
            $resBinance['res'] = [];
            $summaryOIBinance = 0;
            devlogs("Err binance 100 or flag  " . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterest');
        }
        if (abs($summaryOIOkx) === 100 || $okxSkippedFlag === true) {
            $resOkx['res'] = [];
            $summaryOIOkx = 0;
            devlogs("Err okx 100 or flag" . $symbolName . " - " . date("d.m.y H:i:s"), 'getSummaryOpenInterest');
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
        $useCache = true
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
            foreach ($tradesHistoryList as $idx => $tradeItem) {
                try {
                    // Валидация: элемент должен быть массивом
                    if (!is_array($tradeItem)) {
                        devlogs("skip: tradeItem not array (type: " . gettype($tradeItem) . ") idx:$idx symbol:$symbolName", $marketMode . '/summaryVolumeExchange');
                        devlogs($tradeItem, $marketMode . '/summaryVolumeExchange');
                        continue;
                    }

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

                } catch (\Throwable $e) {
                    $errText = "Exception processing trade idx:$idx symbol:$symbolName message: " . $e->getMessage();
                    devlogs($errText, $marketMode . '/summaryVolumeExchange');
                    devlogs($tradeItem, $marketMode . '/summaryVolumeExchange');
                    \Maksv\DataOperation::sendErrorInfoMessage($errText, 'summaryVolumeExchange()', 'bybit volume item');
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
                'resBybit' => $currentData,
                'resBinance' => [],
                'resSummary' => []
            ];

            $processedSymbols++;

            // Если обработали n символов, сохраняем текущие данные в файл
            if ($processedSymbols % 100 == 0) {
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

    public static function analyzeSymbolPriceChange(
        $apiObAr,
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
        $bybitApiOb = $apiObAr['bybitApiOb'] ?? false;
        $binanceApiOb = $apiObAr['binanceApiOb'] ?? false;
        $okxApiOb = $apiObAr['okxApiOb'] ?? false;
        $bingxApiOb = $apiObAr['bingxApiOb'] ?? false;

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
            } else if ($market == 'bingx') {
                $kline = $bingxApiOb->getKlines($symbolName, '5m', 700, $startTime, null, true, $cacheTime);

                if (empty($kline) || !is_array($kline)) {
                    return [
                        'status' => false,
                        'message' => 'No data from API bingx ' . $symbolName
                    ];
                }

                usort($kline, fn($a, $b) => $a['time'] <=> $b['time']);
                $klineList = ($kline);

                $candles = array_map(function ($k) {
                    return [
                        't' => floatval($k['time']), // timestap
                        'o' => floatval($k['open']), // Open price
                        'h' => floatval($k['high']), // High price
                        'l' => floatval($k['low']), // Low price
                        'c' => floatval($k['close']), // Close price
                        'v' => floatval($k['volume'])  // Volume
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
            if ($lastTimestapJson['TIMESTAMP'] && ((time() - $lastTimestapJson['TIMESTAMP']) < 240)) {
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
