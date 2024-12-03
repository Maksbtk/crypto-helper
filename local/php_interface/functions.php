<?php
use Bitrix\Main\Loader;
use \Bitrix\Main,
    \Bitrix\Sale\Internals;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Sale\Payment;
use Bitrix\Sale;
use \Bitrix\Main\Context;

\CModule::IncludeModule('highloadblock');
\CModule::IncludeModule('iblock');


#Логирование
/*function devlogs($data, $type){
    $root = Bitrix\Main\Application::getDocumentRoot();
    file_put_contents("$root/devlogs/$type.txt", print_r($data, true)."\n", FILE_APPEND);
}*/

function devlogs($data, $type)
{
    $root = Bitrix\Main\Application::getDocumentRoot();

    if (!is_dir("$root/devlogs/$type/"))
        mkdir("$root/devlogs/$type/");

    file_put_contents("$root/devlogs/$type/" . date("Ym") . ".txt", print_r($data, true)."\n", FILE_APPEND);
}

function agentBybitRespDev()
{
    \Maksv\Exchange::bybitExchange('1h', 0.3, true);
    //agentBybitV5RespTimeFrame('1h', 0.2, true);
    return "agentBybitRespDev();";
}

function agentBybitResp30m()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

   /* if (
        (in_array($minute, [30, 31]))
        //|| (!in_array($hour, [23, 3, 7, 11, 15, 19]) && in_array($minute, [0, 1]))
        //|| (in_array($hour, [3]) && in_array($minute, [7]))
    )
        agentBybitV5RespTimeFrame('30m', 0.7);*/

    return "agentBybitResp30m();";
}

function agentBybitResp1h()
{
    $minute = (int)date('i');
    $hour = (int)date('H');

    if (in_array($minute, [0, 1]) || in_array($minute, [30, 31]))
        \Maksv\Exchange::bybitExchange('1h', 0.3);

    return "agentBybitResp1h();";
}

function agentBybitResp4h()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if (in_array($minute, [0, 1]))
        \Maksv\Exchange::bybitExchange('4h', 0.8);

    return "agentBybitResp4h();";
}

function agentBybitResp1d()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if (in_array($minute, [31, 32]))
        \Maksv\Exchange::bybitExchange('1d', 0.9);

    return "agentBybitResp1d();";
}

// api v5
function agentBybitV5RespTimeFrame($timeFrame = '30m',  $oiLimit = 1.7, $devMode = false)
{
    $timeMark = date("d.m.y H:i:s");
    devlogs("start" . ' - ' . $timeMark, 'AgentBybitResp'.$timeFrame);
    $res = ['symbols' => [],];

    // проверяем не запускался ли только что обмен
    if (!$devMode) {
        $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitV5Exchange/' . $timeFrame . '/timestap.json'), true);
        if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 600) && !$devMode) {
            devlogs("end, timestap dif -" . ' - ' . $timeMark, 'AgentBybitResp' . $timeFrame);
            return;
        } else {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitV5Exchange/' . $timeFrame . '/timestap.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => $timeMark]));
        }
    }
    $marketVolumesJson['RESPONSE_EXCHENGE'] = [];

    $bybitApiOb = new \Maksv\Bybit();
    $bybitApiOb->openConnection();

    //получаем контракты, которые будем анализировать
    $exchangeSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitV5Exchange/exchangeSymbolsList.json'), true)['RESPONSE_EXCHENGE'];
    if (!$exchangeSymbolsList || $timeFrame == '1d') {
        $exchangeSymbolsResp = $bybitApiOb->getSymbolsV5("linear");
        if ($exchangeSymbolsResp['result']['list']) {
            $dataSymInfo = [
                "TIMEMARK" => $timeMark,
                "RESPONSE_EXCHENGE" => $exchangeSymbolsResp['result']['list'],
                "EXCHANGE_CODE" => 'bybit'
            ];
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitV5Exchange/derivativeBaseCoin.json', json_encode($dataSymInfo));
            $exchangeSymbolsList = $exchangeSymbolsResp['result']['list'];
        }
    }

    $countReq = $analysisSymbolCount = 0;
    foreach ($exchangeSymbolsList as &$symbol) {
        if (
            $symbol['status'] == 'Trading'
            && in_array($symbol['quoteCoin'], ['USDT'/*, 'USDC', 'USDE', 'FDUSD', 'TUSD'*/])
            && !in_array($symbol['baseCoin'], ['FDUSD', 'USDE', 'USDC'])
        ) {
            $countReq++;
            try {
                $klineHistory = [];
                $symbolName = $symbol['symbol'];
                $openInterest = 0;
                $timestapOI = false;

                $timeFrameOiMap = [
                    '30m' => '15m',
                    '1h' => '15m',
                    '4h' => '30m',
                    '1d' => '30m',
                ];

                //сначала фильтруем контракты по открытому интересу
                $openInterestResp = $bybitApiOb->openInterest($symbolName, 'linear', $timeFrameOiMap[$timeFrame], '2');
                if ($openInterestResp['result']['list'] && is_array($openInterestResp['result']['list']) && count($openInterestResp['result']['list']) >= 2) {
                    $lastInterest = $openInterestResp['result']['list'][0]['openInterest'];
                    $prevInterest = $openInterestResp['result']['list'][1]['openInterest'];
                    $openInterest = round(($lastInterest / ($prevInterest / 100)) - 100, 2);
                    $symbol['timestapOI'] = $timestapOI = date("d.m H:i", $openInterestResp['result']['list'][1]['timestamp'] / 1000) . ' - ' . date("d.m H:i", $openInterestResp['result']['list'][0]['timestamp'] / 1000);

                    if (
                        in_array($symbolName, ['BTCUSDT', 'ETHUSDT'])
                        || ($openInterest >= $oiLimit /*|| $openInterest <= -$oiLimit*/)
                    ) {
                        $analysisSymbolCount++;
                        $priceChange = $lastClosePrice = 0;
                        $rsi = false;
                        $barsCount = 100;

                        // получаем свечи для определения тренда
                        $kline = $bybitApiOb->klineV5("linear", $symbolName, $timeFrame, $barsCount);
                        $supertrendData = $crossHistoryMA = false;
                        if ($kline['result'] && $kline['result']['list']) {
                            $klineList = array_reverse($kline['result']['list']);
                            foreach ($klineList as $klineItem)
                                $klineHistory['klineСlosePriceList'][] = $klineItem[4];

                            $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                            if ($prevKline) {
                                $priceChange = round((floatval($prevKline[4]) / (floatval($prevKline[1]) / 100)) - 100, 2);
                                $lastClosePrice = floatval($prevKline[4]);
                            }

                            //MA x EMA
                            $crossHistoryMA = \Maksv\TechnicalAnalysis::getMACrossHistory($klineHistory['klineСlosePriceList'], 5, 20, 11) ?? false;
                            //$crossMA = \Maksv\TechnicalAnalysis::checkMACross($klineHistory['klineСlosePriceList'], 5, 20) ?? false;

                            //SAR
                            $sarCandles = array_map(function ($k) {
                                return [
                                    'h' => floatval($k[2]),
                                    'l' => floatval($k[3])
                                ];
                            }, $klineList);
                            $sarData = \Maksv\TechnicalAnalysis::calculateSARWithTrend($sarCandles);

                            // Supertrend candles
                            $supertrendCandles = array_map(function ($k) {
                                return [
                                    'h' => floatval($k[2]), // High price
                                    'l' => floatval($k[3]), // Low price
                                    'c' => floatval($k[4])  // Close price
                                ];
                            }, $klineList);
                            $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($supertrendCandles, 10, 3) ?? false; // длина 10, фактор 3


                            //$stochasticOscillatorData = \Maksv\TechnicalAnalysis::calculateStochasticOscillator($supertrendCandles, 14, 3, 1);
                            $stochasticOscillatorData = \Maksv\TechnicalAnalysis::calculateStochasticRSI($supertrendCandles, 14, 14, 3, 3);
                        }

                        $symbol['kline'] = $kline['result']['list'] ?? [];

                        if (!$crossHistoryMA)
                            devlogs('ERR ' . $symbolName . ' | err - crossHistoryMA' . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp' . $timeFrame);

                        if (!$supertrendData)
                            devlogs('ERR ' . $symbolName . ' | err - $supertrendData' . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp' . $timeFrame);

                        $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName] = [
                            'klineHistory' => $klineHistory,
                            'rsi' => $rsi,
                            'openInterest' => $openInterest,
                            'priceChange' => $priceChange,
                            'crossHistoryMA' => $crossHistoryMA,
                            'sarData' => $sarData,
                            'supertrendData' => $supertrendData,
                            'stochasticOscillatorData' => $stochasticOscillatorData,
                            'timeMark' => date("H:i"),
                            'timeStamp' => time(),
                            'timeFrame' => $timeFrame,
                            'lastClosePrice' => $lastClosePrice,
                            'timestapOI' => $timestapOI,
                        ];
                    }

                } else {
                    devlogs('ERR ' .$symbolName.  ' | err - OI' . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
                }

            } catch (Exception $e) {
                devlogs('ERR ' .$symbolName. ' countReq - ' . $countReq . ' | err text - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
            }

            $res['symbols'][$symbolName] = $symbol;
            usleep(50000);
        }
    }
    unset($symbol);


    devlogs('analysis symbols count  - ' . $analysisSymbolCount, 'AgentBybitResp'.$timeFrame);
    devlogs('count symbols - ' . $countReq, 'AgentBybitResp'.$timeFrame);

    $timeMark = date("d.m.y H:i:s");
    devlogs('step2 - ' . $timeMark, 'AgentBybitResp'.$timeFrame);

    $data = [
        "TIMEMARK" => $timeMark,
        "RESPONSE_EXCHENGE" => $res,
        "EXCHANGE_CODE" => 'bybit',
        'TIMEFRAME' => $timeFrame
    ];

    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitV5Exchange/' . $timeFrame . '/exchangeResponse.json', json_encode($data));

    $marketVolumesJson['TIMEMARK'] = $timeMark;
    $marketVolumesJson['EXCHANGE_CODE'] = 'bybit';
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitV5Exchange/' . $timeFrame . '/marketVolumes.json', json_encode($marketVolumesJson));

    if ($marketVolumesJson['RESPONSE_EXCHENGE']) {

        $opportunitiesRes = \Maksv\StrategyBuilder::findPumpOrDumpOpportunities($marketVolumesJson['RESPONSE_EXCHENGE'], $timeFrame, 'bybit');
        \Maksv\DataOperation::sendInfoMessage($opportunitiesRes, $timeFrame);

        if ($opportunitiesRes['masterPump'] || $opportunitiesRes['masterDump']) {

            $bybitApproveExchangeRes = bybitApproveExchange($bybitApiOb, $timeFrame, $opportunitiesRes['masterPump'], $opportunitiesRes['masterDump']);
            $opportunitiesRes['masterPump'] = $bybitApproveExchangeRes['pump'];
            $opportunitiesRes['masterDump'] = $bybitApproveExchangeRes['dump'];

            foreach ($opportunitiesRes['masterPump'] as $symbolName => $pump) {
                if (/*!$pump['filter']['1h'] ||*/ !$pump['filter']['4h'] || !$pump['filter']['1d'])
                    unset($opportunitiesRes['masterPump'][$symbolName]);
            }

            foreach ($opportunitiesRes['masterDump'] as $symbolName => $dump) {
                if (/*!$dump['filter']['1h'] ||*/ !$dump['filter']['4h'] || !$dump['filter']['1d'])
                    unset($opportunitiesRes['masterDump'][$symbolName]);
            }

            if ($opportunitiesRes['masterPump'] || $opportunitiesRes['masterDump']) {
                $timeMark = date("d.m.y H:i:s");
                $data = [
                    "TIMEMARK" => $timeMark,
                    "STRATEGIES" => $opportunitiesRes,
                    //"SYMBOLS" => $actualOpportunities,
                    "EXCHANGE_CODE" => 'bybit'
                ];

                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . 'bybitV5' . 'Exchange/' . $timeFrame . '/actualMarketVolumes.json', json_encode($data));
                \Maksv\DataOperation::sendSignalMessage($opportunitiesRes['masterPump'], $opportunitiesRes['masterDump'], '@cryptoHelperMaster', $timeFrame);

                try {
                    $writeRes = \Maksv\DataOperation::saveSignalToIblock($timeFrame, 'bybit', true);
                    devlogs($timeFrame . ' master write' . $writeRes['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitRespMaster');
                } catch (Exception $e) {
                    devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitRespMaster');
                }
            }
        }

        devlogs('step3 - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);

        //\Maksv\DataOperation::sendInfoMessage($opportunitiesRes, $timeFrame);
        if ($opportunitiesRes['pump'] || $opportunitiesRes['dump']) {
            $bybitApproveExchangeRes = bybitApproveExchange($bybitApiOb, $timeFrame, $opportunitiesRes['pump'], $opportunitiesRes['dump']);
            $opportunitiesRes['pump'] = $bybitApproveExchangeRes['pump'];
            $opportunitiesRes['dump'] = $bybitApproveExchangeRes['dump'];

           /* foreach ($opportunitiesRes['pump'] as $symbolName => $pump) {
                if (!$pump['filter']['1h'] || !$pump['filter']['4h'] || !$pump['filter']['1d'])
                    unset($opportunitiesRes['pump'][$symbolName]);
            }

            foreach ($opportunitiesRes['dump'] as $symbolName => $dump) {
                if (!$dump['filter']['1h'] || !$dump['filter']['4h'] || !$dump['filter']['1d'])
                    unset($opportunitiesRes['dump'][$symbolName]);
            }*/

            if ($opportunitiesRes['pump'] || $opportunitiesRes['dump']) {
                \Maksv\DataOperation::sendSignalMessage($opportunitiesRes['pump'], $opportunitiesRes['dump'], '@infoCryptoHelperDev', $timeFrame);
            }
        }


        //check reversal local BTC trend
        //if ($timeFrame == '1h') {
        try {
            devlogs($timeFrame . ' Warn SUPERTRAND | reversal flag  - ' . $opportunitiesRes['headCoin']['BTCUSDT']['superTrandReversalFlag'] . ' | is up trend - ' .  $opportunitiesRes['headCoin']['BTCUSDT']['lastSupertrend']['isUptrend'] . ' | timeMark - ' . date("d.m.y H:i:s"), 'trendWarning');

            if ($opportunitiesRes['headCoin']['BTCUSDT'] && $opportunitiesRes['headCoin']['BTCUSDT']['superTrandReversalFlag'] === true)
                \Maksv\DataOperation::sendTrendWarning($opportunitiesRes['headCoin']['BTCUSDT'], 'SUPERTRAND', $opportunitiesRes['headCoin']['BTCUSDT']['lastSupertrend']['isUptrend'], '@infoCryptoHelperTrend', $timeFrame);

        } catch (Exception $e) {
            devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'trendWarning');
        }
        //}

    }

    devlogs('end - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
    $bybitApiOb->closeConnection();
    return "agentBybitResp".$timeFrame."();";
}

function bybitApproveExchange($bybitApiOb, $timeFrame, $pumpAr = [], $dumpAr = []) {

    try {

        $parentTimeFrameMapMatrix = [
            '30m' => ['1h', '4h', '1d', '1w'],
            '1h' => ['4h', '1d', '1w'],
            '4h' => ['1h', '1d', '1w'],
            '1d' => ['1h', '4h', '1w'],
        ];

        foreach ($pumpAr as &$masterPump) {

            /*if ($timeFrame == '1d')
                continue;*/

            $levels = false;
            $orderBook = $bybitApiOb->orderBookV5('linear', $masterPump['symbolName'], 1000);
            if ($orderBook['result'] && is_array($orderBook['result'])) {
                //$levels = \Maksv\TechnicalAnalysis::findLevels($orderBook['result'], 5);
                $findLevelsRes = \Maksv\TechnicalAnalysis::findLevels($orderBook['result'], 200, 0.01);
                $levels = [
                    'lower' => array_slice($findLevelsRes['lower'], 0, 5),
                    'upper' => array_slice($findLevelsRes['upper'], 0, 5),
                ];
            }
            $masterPump['levels'] = $levels;
            $masterPump['filter'][$timeFrame] = $masterPump['approve'][$timeFrame]['filter'] = true;

            foreach ($parentTimeFrameMapMatrix[$timeFrame] as $tf) {

                try {
                    $masterPump['filter'][$tf] = false;
                    $masterPump['approve'][$tf]['filter'] = false;

                    $actualSAR = $actualSupertrend = $crossMA = false;
                    $priceChange = $lastClosePrice = 0;
                    $barsCount = 100;
                    $kline = $bybitApiOb->klineV5("linear", $masterPump['symbolName'], $tf, $barsCount);
                    if ($kline['result'] && $kline['result']['list']) {
                        $klineList = array_reverse($kline['result']['list']);

                        $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                        if ($prevKline) {
                            $priceChange = round((floatval($prevKline[4]) / (floatval($prevKline[1]) / 100)) - 100, 2);
                            //$lastClosePrice = floatval($prevKline[4]);
                        }
                        $lastClosePrice = $masterPump['lastClosePrice'];

                        //MA x EMA
                        foreach ($klineList as $klineItem)
                            $klineHistory['klineСlosePriceList'][] = $klineItem[4];

                        $crossMA = \Maksv\TechnicalAnalysis::checkMACross($klineHistory['klineСlosePriceList'], 5, 20) ?? false;

                        //SAR
                        $sarCandles = array_map(function ($k) {
                            return [
                                'h' => floatval($k[2]),
                                'l' => floatval($k[3])
                            ];
                        }, $klineList);

                        $sarData = \Maksv\TechnicalAnalysis::calculateSARWithTrend($sarCandles) ?? false;
                        if ($sarData && is_array($sarData)) {
                            $actualSAR = $sarData[array_key_last($sarData)];
                        }

                        // Supertrend candles
                        $supertrendCandles = array_map(function ($k) {
                            return [
                                'h' => floatval($k[2]), // High price
                                'l' => floatval($k[3]), // Low price
                                'c' => floatval($k[4]) // Close price
                            ];
                        }, $klineList);
                        $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($supertrendCandles, 10, 3) ?? false; // длина 10, фактор 3
                        if ($supertrendData && is_array($supertrendData)) {
                            $actualSupertrend = $supertrendData[array_key_last($supertrendData)];
                        }
                    }
                    $masterPump['approve'][$tf]['crossMA'] = $crossMA;
                    $masterPump['approve'][$tf]['priceChange'] = $priceChange;
                    //$masterPump['approve'][$tf]['actualSAR'] = $actualSAR;
                    $masterPump['approve'][$tf]['actualSupertrend'] = $actualSupertrend;
                    $masterPump['approve'][$tf]['lastClosePrice'] = $lastClosePrice;

                    $openInterest = 0;
                    $masterPump['approve'][$tf]['openInterest'] = $openInterest;

                    if (
                        (
                            in_array($tf, ['1d', '1w'])
                            //&& $actualSAR['isUptrend']
                            //&& $actualSupertrend['isUptrend']
                            && ($lastClosePrice >= $crossMA['sma'])
                        )
                        || (
                            in_array($tf, ['30m', '1h', '4h'])
                            && $actualSupertrend['isUptrend']
                            //&& $actualSAR['isUptrend']
                        )
                    ) {
                        $masterPump['filter'][$tf] = true;
                        $masterPump['approve'][$tf]['filter'] = true;
                    }

                    if (in_array($tf, [/*'1d',*/ '1w']) && !$crossMA) {
                        $masterPump['filter'][$tf] = true;
                        $masterPump['approve'][$tf]['filter'] = true;
                    }

                } catch (Exception $e) {
                    devlogs( $masterPump['symbolName'] . '-' .$tf . ' second filter     ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'bybitApproveExchange');
                }
            }

        }
        unset($masterPump);

        foreach ($dumpAr as &$masterDump) {

            $levels = false;
            $orderBook = $bybitApiOb->orderBookV5('linear', $masterDump['symbolName'], 1000);
            if ($orderBook['result'] && is_array($orderBook['result'])) {
                //$levels = \Maksv\TechnicalAnalysis::findLevels($orderBook['result'], 5);
                $findLevelsRes = \Maksv\TechnicalAnalysis::findLevels($orderBook['result'], 200, 0.01);
                $levels = [
                    'lower' => array_slice($findLevelsRes['lower'], 0, 5),
                    'upper' => array_slice($findLevelsRes['upper'], 0, 5),
                ];
            }
            $masterDump['levels'] = $levels;
            $masterDump['filter'][$timeFrame] = $masterDump['approve'][$timeFrame]['filter'] = true;

            foreach ($parentTimeFrameMapMatrix[$timeFrame] as $tf) {

                try {
                    $masterDump['filter'][$tf] = false;
                    $masterDump['approve'][$tf]['filter'] = false;

                    $actualSAR = $actualSupertrend = $crossMA = false;
                    $priceChange = $lastClosePrice = 0;
                    $barsCount = 100;
                    $kline = $bybitApiOb->klineV5("linear", $masterDump['symbolName'], $tf, $barsCount);
                    if ($kline['result'] && $kline['result']['list']) {
                        $klineList = array_reverse($kline['result']['list']);

                        $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                        if ($prevKline) {
                            $priceChange = round((floatval($prevKline[4]) / (floatval($prevKline[1]) / 100)) - 100, 2);
                        }
                        $lastClosePrice = $masterDump['lastClosePrice'];

                        //MA x EMA
                        foreach ($klineList as $klineItem)
                            $klineHistory['klineСlosePriceList'][] = $klineItem[4];

                        $crossMA = \Maksv\TechnicalAnalysis::checkMACross($klineHistory['klineСlosePriceList'], 5, 20) ?? false;

                        //SAR
                        $sarCandles = array_map(function ($k) {
                            return [
                                'h' => floatval($k[2]),
                                'l' => floatval($k[3])
                            ];
                        }, $klineList);

                        $sarData = \Maksv\TechnicalAnalysis::calculateSARWithTrend($sarCandles) ?? false;
                        if ($sarData && is_array($sarData)) {
                            $actualSAR = $sarData[array_key_last($sarData)];
                        }

                        // Supertrend candles
                        $supertrendCandles = array_map(function ($k) {
                            return [
                                'h' => floatval($k[2]), // High price
                                'l' => floatval($k[3]), // Low price
                                'c' => floatval($k[4])  // Close price
                            ];
                        }, $klineList);
                        $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($supertrendCandles, 10, 3) ?? false; // длина 10, фактор 3
                        if ($supertrendData && is_array($supertrendData)) {
                            $actualSupertrend = $supertrendData[array_key_last($supertrendData)];
                        }
                    }

                    $masterDump['approve'][$tf]['crossMA'] = $crossMA;
                    $masterDump['approve'][$tf]['priceChange'] = $priceChange;
                    //$masterDump['parent'][$tf]['actualSAR'] = $actualSAR;
                    $masterDump['approve'][$tf]['lastClosePrice'] = $lastClosePrice;

                    $openInterest = 0;
                    $masterDump['approve'][$tf]['openInterest'] = $openInterest;

                    if (
                        (
                            in_array($tf, ['1d', '1w'])
                            //&& !$actualSupertrend['isUptrend']
                            //&& !$actualSAR['isUptrend']
                            && ($lastClosePrice <= $crossMA['sma'])
                        ) || (
                            in_array($tf, ['30m', '1h', '4h'])
                            && !$actualSupertrend['isUptrend']
                            //&& !$actualSAR['isUptrend']
                        )
                    ) {
                        $masterDump['filter'][$tf] = true;
                        $masterDump['approve'][$tf]['filter'] = true;
                    }

                    if (in_array($tf, [/*'1d',*/ '1w']) && !$crossMA) {
                        $masterDump['filter'][$tf] = true;
                        $masterDump['approve'][$tf]['filter'] = true;
                    }

                } catch (Exception $e) {
                    devlogs( $masterDump['symbolName'] . '-' .$tf . ' second filter     ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'bybitApproveExchange');
                }
            }
        }
        unset($masterDump);

    } catch (Exception $e) {
        devlogs('ERR 23 ' . $timeFrame . ' - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'bybitApproveExchange');
    }

    return ['pump' => $pumpAr, 'dump' => $dumpAr];
}

function encryptMessage($message, $shift) {
    $result = '';
    for ($i = 0; $i < strlen($message); $i++) {
        $charCode = ord($message[$i]);
        $result .= chr($charCode + $shift);
    }
    return $result;
}

function decryptMessage($encryptedMessage, $shift = 3) {
    $result = '';
    for ($i = 0; $i < strlen($encryptedMessage); $i++) {
        $charCode = ord($encryptedMessage[$i]);
        $result .= chr($charCode - $shift);
    }
    return $result;
}

function getBasketCnt(): int
{
    \CModule::IncludeModule('sale');

    $dbBasketItems = \CSaleBasket::GetList(
        array(
            "NAME" => "ASC",
            "ID" => "ASC"
        ),
        array(
            "FUSER_ID" => \CSaleBasket::GetBasketUserID(),
            "LID" => SITE_ID,
            "ORDER_ID" => "NULL"
        ),
        false,
        false,
        array("ID","QUANTITY")
    );
    $cnt = 0;
    while($bItem = $dbBasketItems->Fetch()) {
        $cnt += $bItem['QUANTITY'];
    }

    return $cnt;
}
