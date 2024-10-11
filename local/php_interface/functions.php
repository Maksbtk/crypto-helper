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
function devlogs($data, $type){
    $root = Bitrix\Main\Application::getDocumentRoot();
    file_put_contents("$root/devlogs/$type.txt", print_r($data, true)."\n", FILE_APPEND);
}


function agentBybitResp30m()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if (in_array($minute, [30, 31]) || (!in_array($hour, [23, 3, 7, 11, 15, 19]) && in_array($minute, [0, 1])))
        agentBybitV5RespTimeFrame('30m', 2.5);

    return "agentBybitResp30m();";
}

function agentBybitResp1h()
{
    $minute = (int)date('i');
    $hour = (int)date('H');

    if (!in_array($hour, [23, 3, 7, 11, 15, 19]) && in_array($minute, [0, 1]))
        agentBybitV5RespTimeFrame('1h', 2);

    return "agentBybitResp1h();";
}

function agentBybitResp4h()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if (in_array($hour, [23, 3, 7, 11, 15, 19]) && in_array($minute, [0, 1]))
        agentBybitV5RespTimeFrame('4h', 2);

    return "agentBybitResp4h();";
}

function agentBybitResp1d()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if ($hour == 3 && in_array($minute, [0, 1]))
        agentBybitV5RespTimeFrame('1d', 2);

    return "agentBybitResp1d();";
}

// api v5
function agentBybitV5RespTimeFrame($timeFrame = '30m',  $oiLimit = 1.7)
{
    $timeMark = date("d.m.y H:i:s");
    devlogs("start" . ' - ' . $timeMark, 'AgentBybitResp'.$timeFrame);
    $res = ['symbols' => [],];

    $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitV5Exchange/' . $timeFrame . '/timestap.json'), true);
    if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 600)) {
        devlogs("end, timestap dif -" . ' - ' . $timeMark, 'AgentBybitResp'.$timeFrame);
        return;
    } else {
        file_put_contents($_SERVER['DOCUMENT_ROOT'] .'/upload/bybitV5Exchange/' . $timeFrame . '/timestap.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => $timeMark]));
    }

    $marketVolumesJson['RESPONSE_EXCHENGE'] = [];

    $bybitApiOb = new \Maksv\Bybit();
    $bybitApiOb->openConnection();

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
            && !in_array($symbol['baseCoin'], ['USDE', 'USDC'])
        ) {

            $countReq++;
            try {
                $tradesHistory = [];
                $klineHistory = [];
                $symbolName = $symbol['symbol'];


                $openInterest = 0;
                $openInterestResp = $bybitApiOb->openInterest($symbolName, 'linear', $timeFrame, '2');
                if ($openInterestResp['result']['list'] && is_array($openInterestResp['result']['list']) && count($openInterestResp['result']['list']) >= 2) {
                    $lastInterest = $openInterestResp['result']['list'][0]['openInterest'];
                    $prevInterest = $openInterestResp['result']['list'][1]['openInterest'];
                    $openInterest = round(($lastInterest / ($prevInterest / 100)) - 100, 2);

                    if (
                        in_array($symbolName, ['BTCUSDT', 'ETHUSDT']) // если есть движняк по OI то дальше анализируем контракт
                        || ($openInterest >= $oiLimit || $openInterest <= -$oiLimit)
                    ) {
                        $analysisSymbolCount++;
                        $priceChange = $lastClosePrice = 0;
                        $rsi = false;
                        $divergences = false;
                        $barsCount = 28;
                        $kline = $bybitApiOb->klineV5("linear", $symbolName, $timeFrame, $barsCount);

                        $crossMA = $sarData = false;
                        if ($kline['result'] && $kline['result']['list']) {
                            $klineList = array_reverse($kline['result']['list']);
                            foreach ($klineList as $klineItem)
                                $klineHistory['klineСlosePriceList'][] = $klineItem[4];

                            $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                            if ($prevKline) {
                                $priceChange = round(($prevKline[4] / ($prevKline[1] / 100)) - 100, 2);
                                $lastClosePrice = $prevKline[4];
                            }

                            //MA x EMA
                            $crossMA = \Maksv\StrategyBuilder::checkMACross($klineHistory['klineСlosePriceList']) ?? false;

                            //SAR
                            $sarCandles = array_map(function ($k) {
                                return [
                                    'h' => floatval($k[2]),
                                    'l' => floatval($k[3])
                                ];
                            }, $klineList);
                            $sarData = \Maksv\StrategyBuilder::calculateSARWithTrend($sarCandles);
                        }

                        $symbol['tradesHistory'] = $tradesHistory ?? [];
                        $symbol['kline'] = $kline['result']['list'] ?? [];

                        if ($crossMA && $sarData) {

                            $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName] = [
                                'klineHistory' => $klineHistory,
                                'rsi' => $rsi,
                                'openInterest' => $openInterest,
                                'priceChange' => $priceChange,
                                'crossMA' => $crossMA,
                                'sarData' => $sarData,
                                'divergences' => $divergences,
                                'tradesHistory' => $tradesHistory,
                                'timeMark' => date("H:i"),
                                'timeStamp' => time(),
                                'timeFrame' => $timeFrame,
                                'lastClosePrice' => $lastClosePrice,
                            ];

                        } else {
                            if (!$crossMA)
                                devlogs('ERR ' . $symbolName . ' | err - crossMA' . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp' . $timeFrame);

                            if (!$sarData)
                                devlogs('ERR ' . $symbolName . ' | err - sarData' . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp' . $timeFrame);
                        }
                    }

                } else {
                    devlogs('ERR ' .$symbolName.  ' | err - OI' . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
                }

                // devlogs( 'countReq - ' . $countReq . ' | foreach  symbol- ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
            } catch (Exception $e) {
                devlogs('ERR ' .$symbolName. ' countReq - ' . $countReq . ' | err text - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
            }

            $res['symbols'][$symbolName] = $symbol;
            usleep(100000);
        }
    }
    unset($symbol);


    devlogs('analysis symbols count  - ' . $analysisSymbolCount, 'AgentBybitResp'.$timeFrame);
    devlogs('count symbols - ' . $countReq, 'AgentBybitResp'.$timeFrame);

    $timeMark = date("d.m.y H:i:s");
    devlogs('end - ' . $timeMark, 'AgentBybitResp'.$timeFrame);

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

        \Maksv\DataOperation::sendSignalMessage($opportunitiesRes['pump'], $opportunitiesRes['dump'], '@infoCryptoHelper' . $timeFrame);
        \Maksv\DataOperation::sendInfoMessage($opportunitiesRes, $timeFrame);

        if ($opportunitiesRes['masterPump'] || $opportunitiesRes['masterDump']) {
            try {

                /*$parentTimFrameMap = [
                    '30m' => '1h',
                    '1h' => '4h',
                    '4h' => '1d',
                ];*/

                $parentTimFrameMap = [
                    '30m' => '1d',
                    '1h' => '1d',
                    '4h' => '1d',
                ];

                foreach ($opportunitiesRes['masterPump'] as &$masterPump) {

                    if ($timeFrame == '1d')
                        continue;

                    $masterPump['secondFilter'] = false;
                    $levels = false;
                    $orderBook = $bybitApiOb->orderBookV5('linear', $masterPump['symbolName'], 1000);
                    if ($orderBook['result'] && is_array($orderBook['result'])) {
                        $levels = \Maksv\StrategyBuilder::findLevels($orderBook['result'], 5);
                    }
                    $masterPump['levels'] = $levels;

                    $actualSAR = $crossMA = false;
                    $priceChange = $lastClosePrice = 0;
                    $barsCount = 28;
                    $kline = $bybitApiOb->klineV5("linear", $masterPump['symbolName'], $parentTimFrameMap[$timeFrame], $barsCount);
                    if ($kline['result'] && $kline['result']['list']) {
                        $klineList = array_reverse($kline['result']['list']);

                        $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                        if ($prevKline) {
                            $priceChange = round(($prevKline[4] / ($prevKline[1] / 100)) - 100, 2);
                            $lastClosePrice = $prevKline[4];
                        }

                        //MA x EMA
                        $crossMA = \Maksv\StrategyBuilder::checkMACross($klineHistory['klineСlosePriceList']) ?? false;

                        //SAR
                        $sarCandles = array_map(function ($k) {
                            return [
                                'h' => floatval($k[2]),
                                'l' => floatval($k[3])
                            ];
                        }, $klineList);

                        $sarData = \Maksv\StrategyBuilder::calculateSARWithTrend($sarCandles) ?? false;
                        if ($sarData && is_array($sarData)) {
                            //$masterPump['parent'][$parentTimFrameMap[$timeFrame]]['sarData'] = array_slice($sarData, -5);
                            $actualSAR = $sarData[array_key_last($sarData)];
                        }
                    }
                    //$masterPump['parent'][$parentTimFrameMap[$timeFrame]]['sarData'] = array_slice($sarData, -5);
                    $masterPump['parent'][$parentTimFrameMap[$timeFrame]]['priceChange'] = $priceChange;
                    $masterPump['parent'][$parentTimFrameMap[$timeFrame]]['actualSAR'] = $actualSAR;

                    $openInterest = 0;
                    $openInterestResp = $bybitApiOb->openInterest($masterPump['symbolName'], 'linear', $parentTimFrameMap[$timeFrame], '2');
                    if ($openInterestResp['result']['list'] && is_array($openInterestResp['result']['list']) && count($openInterestResp['result']['list']) >= 2) {
                        $lastInterest = $openInterestResp['result']['list'][0]['openInterest'];
                        $prevInterest = $openInterestResp['result']['list'][1]['openInterest'];
                        $openInterest = round(($lastInterest / ($prevInterest / 100)) - 100, 2);
                    }
                    $masterPump['parent'][$parentTimFrameMap[$timeFrame]]['openInterest'] = $openInterest;

                    if (
                        ($openInterest && $priceChange && $actualSAR && $crossMA && $lastClosePrice)
                        && (
                            ((($priceChange > 0 && $openInterest > 1) || ($priceChange < 0 && $openInterest < -1)) && $actualSAR['isUptrend'])
                            || ($actualSAR['isUptrend'] && $actualSAR['is_reversal'] && $lastClosePrice >= $crossMA['crossMA']['sma'])
                        )
                    ) {
                        $masterPump['secondFilter'] = true;
                    }
                }
                unset($masterPump);

                foreach ($opportunitiesRes['masterDump'] as &$masterDump) {
                    
                    if ($timeFrame == '1d')
                        continue;

                    $masterDump['secondFilter'] = false;
                    $levels = false;
                    $orderBook = $bybitApiOb->orderBookV5('linear', $masterDump['symbolName'], 1000);
                    if ($orderBook['result'] && is_array($orderBook['result'])) {
                        $levels = \Maksv\StrategyBuilder::findLevels($orderBook['result'], 5);
                    }
                    $masterDump['levels'] = $levels;

                    $actualSAR = $crossMA = false;
                    $priceChange = $lastClosePrice = 0;
                    $barsCount = 28;
                    $kline = $bybitApiOb->klineV5("linear", $masterDump['symbolName'], $parentTimFrameMap[$timeFrame], $barsCount);
                    if ($kline['result'] && $kline['result']['list']) {
                        $klineList = array_reverse($kline['result']['list']);

                        $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                        if ($prevKline) {
                            $priceChange = round(($prevKline[4] / ($prevKline[1] / 100)) - 100, 2);
                            $lastClosePrice = $prevKline[4];
                        }

                        //MA x EMA
                        $crossMA = \Maksv\StrategyBuilder::checkMACross($klineHistory['klineСlosePriceList']) ?? false;

                        //SAR
                        $sarCandles = array_map(function ($k) {
                            return [
                                'h' => floatval($k[2]),
                                'l' => floatval($k[3])
                            ];
                        }, $klineList);

                        $sarData = \Maksv\StrategyBuilder::calculateSARWithTrend($sarCandles) ?? false;
                        if ($sarData && is_array($sarData)) {
                            //$masterDump['parent'][$parentTimFrameMap[$timeFrame]]['sarData'] = array_slice($sarData, -5);
                            $actualSAR = $sarData[array_key_last($sarData)];
                        }
                    }
                    //$masterDump['parent'][$parentTimFrameMap[$timeFrame]]['sarData'] = array_slice($sarData, -5);
                    $masterDump['parent'][$parentTimFrameMap[$timeFrame]]['priceChange'] = $priceChange;
                    $masterDump['parent'][$parentTimFrameMap[$timeFrame]]['actualSAR'] = $actualSAR;

                    $openInterest = 0;
                    $openInterestResp = $bybitApiOb->openInterest($masterDump['symbolName'], 'linear', $parentTimFrameMap[$timeFrame], '2');
                    if ($openInterestResp['result']['list'] && is_array($openInterestResp['result']['list']) && count($openInterestResp['result']['list']) >= 2) {
                        $lastInterest = $openInterestResp['result']['list'][0]['openInterest'];
                        $prevInterest = $openInterestResp['result']['list'][1]['openInterest'];
                        $openInterest = round(($lastInterest / ($prevInterest / 100)) - 100, 2);
                    }
                    $masterDump['parent'][$parentTimFrameMap[$timeFrame]]['openInterest'] = $openInterest;

                    if (
                        ($openInterest && $priceChange && $actualSAR && $crossMA && $lastClosePrice)
                        && (
                            ((($priceChange > 0 && $openInterest < -1) || ($priceChange < 0 && $openInterest > 1)) && !$actualSAR['isUptrend'])
                            || (!$actualSAR['isUptrend'] && $actualSAR['is_reversal'] && $lastClosePrice <= $crossMA['crossMA']['sma'])
                        )
                    ) {
                        $masterDump['secondFilter'] = true;
                    }
                }
                unset($masterDump);

            } catch (Exception $e) {
                devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitRespMaster');
            }

            $data = [
                "TIMEMARK" => $timeMark,
                "STRATEGIES" => $opportunitiesRes,
                //"SYMBOLS" => $actualOpportunities,
                "EXCHANGE_CODE" => 'bybit'
            ];

            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . 'bybitV5' . 'Exchange/'.$timeFrame.'/actualMarketVolumes.json', json_encode($data));

            \Maksv\DataOperation::sendSignalMessage($opportunitiesRes['masterPump'], $opportunitiesRes['masterDump'], '@cryptoHelperMaster', $timeFrame);
            try {
                $writeRes = \Maksv\DataOperation::saveSignalToIblock($timeFrame, 'bybit', true);
                devlogs($timeFrame . ' master write' . $writeRes['data'] . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitRespMaster');
            } catch (Exception $e) {
                devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitRespMaster');
            }
        }

        $data = [
            "TIMEMARK" => $timeMark,
            "STRATEGIES" => $opportunitiesRes,
            //"SYMBOLS" => $actualOpportunities,
            "EXCHANGE_CODE" => 'bybit'
        ];

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . 'bybitV5' . 'Exchange/'.$timeFrame.'/actualMarketVolumes.json', json_encode($data));

        try {
            \Maksv\DataOperation::saveSignalToIblock($timeFrame , 'bybit');
        } catch (Exception $e) {
            devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
        }

    }

    $bybitApiOb->closeConnection();
    return "agentBybitResp".$timeFrame."();";
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

///////////////////////////
#делаем запрос на биржу api v3
function agentBybitRespTimeFrame($timeFrame = '30m')
{
    $timeMark = date("d.m.y H:i:s");
    devlogs("start" . ' - ' . $timeMark, 'AgentBybitResp'.$timeFrame);

    $res = [
        'symbols' => [],
        /*'prices' => []*/
    ];

    $marketVolumesJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/marketVolumes.json'), true);

    $bybitApiOb = new \Maksv\Bybit();
    $bybitApiOb->openConnection();

    $derivativeBaseCoins = $derivativesInfoList = [];
    /*$derivativesInfo = $bybitApiOb->derivativesInfo('', 'linear');
    if ($derivativesInfo['result']['list']) {
        $dataDerInfo= [
            "TIMEMARK" => $timeMark,
            "RESPONSE_EXCHENGE" => $derivativesInfo['result']['list'],
            "EXCHANGE_CODE" => 'bybit'
        ];
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json', json_encode($dataDerInfo));
        $derivativesInfoList = $derivativesInfo['result']['list'];
    } else {*/
        $derivativesInfoList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'];
    //}

    foreach ($derivativesInfoList as $derivativeEl) {
        if ($derivativeEl['quoteCoin'] == 'USDT')
            $derivativeBaseCoins[] = $derivativeEl['baseCoin'];
    }

    $res['derivativeBaseCoin'] = $derivativeBaseCoins;

    $symbols = $bybitApiOb->getSymbols() ?? [];
    $countReq = 0;
    foreach ($symbols['result']['list'] as &$symbol) {
        if (
            $symbol['showStatus'] == '1'
            /*&& $symbol['innovation'] == '1'*/
            && in_array($symbol['quoteCoin'], ['USDT'/*, 'USDC', 'USDE', 'FDUSD', 'TUSD'*/])
            && in_array($symbol['baseCoin'], $derivativeBaseCoins)
            && !in_array($symbol['baseCoin'], ['USDE', 'USDC'])
        ) {

           /* if ($timeFrame == '1d'
                && !in_array($symbol['baseCoin'], ['XRP', 'LTC', 'ETH', 'BTC', 'EOS', 'DOGE' , 'XLM',
                    'DOT', 'AAVE', 'CHZ', 'ETC', 'GRT', 'MANA', 'AXS', 'DYDX', 'SOLU', 'OMG', 'SUSHI',
                    'MATIC', 'WOO', 'AGLD', 'ZRX', 'FTT', 'MKR', 'COMPU', 'TON', 'EGLD', 'WIF', 'TIA',
                    '1000PEPE', 'RENDER', 'JUPU', 'NOT', 'NEO', 'LDO', 'JASMY', 'ONDO', 'SEI', 'KAS',
                    'CORE', 'PYTH', 'IMX', 'STX', 'SATS', 'POPCAT', 'HNT', 'BEAM', 'BCH', 'AVAX', 'UNI',
                    'FLR', 'ADA', 'FLOW', 'XTZ', 'SHIB', 'FLOKI', 'ATOM' , 'GALA', 'ALGO', 'RUNE', 'CHEEL']))  {
                continue;
            }*/

            $countReq++;
            try {
                $tradesHistory = [];
                $klineHistory = [];
                $symbolName = $symbol['name'];

                $trades = $bybitApiOb->tradesHistory($symbolName, 100);
                if (!$trades['result']['list']) {
                    sleep(2);
                    $trades = $bybitApiOb->tradesHistory($symbolName, 100);
                }

                if ($trades['result']['list']) {
                    //$tradesHistory['list'] = $trades['result']['list'];
                    foreach ($trades['result']['list'] as $order) {
                        if ($order['isBuyerMaker'] == 1)
                            $tradesHistory['volume']['buy'] += floatval($order['price']) * floatval($order['qty']);
                        else
                            $tradesHistory['volume']['sell'] += floatval($order['price']) * floatval($order['qty']);
                    }

                    $volumesData = $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName] ?? false;
                    if ($volumesData && is_array($volumesData)) {
                        $firstVolume = $volumesData[array_key_last($volumesData)]['tradesHistory']['volume'] ?? false;
                        if ($firstVolume['buy'] &&  $firstVolume['sell'] && $tradesHistory['volume']['buy'] && $tradesHistory['volume']['sell'])  {
                            $tradesHistory['buyChange'] = ($tradesHistory['volume']['buy'] / ($firstVolume['buy'] / 100)) - 100;
                            $tradesHistory['sellChange'] = ($tradesHistory['volume']['sell'] / ($firstVolume['sell'] / 100)) - 100;
                        }
                    }
                }

                $priceChange = 0;
                $rsi = false;
                $divergences = false;
                $timeframeVal = $timeFrame;
                $barsCount = 28 + 0;

                $kline = $bybitApiOb->kline($symbolName, $timeframeVal, $barsCount);
                if (!$kline['result']['list']) {
                    sleep(2);
                    $kline = $bybitApiOb->kline($symbolName, $timeframeVal, $barsCount);
                }

                $crossMA = $sarData = false;
                if ($kline['result'] && $kline['result']['list'])
                {
                    foreach ($kline['result']['list'] as $klineItem) {
                        $klineHistory['klineСlosePriceList'][] = $klineItem['c'];
                    }

                    //$prevKline = $kline['result']['list'][array_key_last($kline['result']['list']) - 1] ?? false;
                    $prevKline = $kline['result']['list'][array_key_last($kline['result']['list'])] ?? false;
                    if ($prevKline)
                        $priceChange = round(($prevKline['c'] / ($prevKline['o'] / 100)) - 100, 2);
                    
                    //анализируем по индикаторам
                    //$rsiPeriod = 14;
                    //$rsi = \Maksv\StrategyBuilder::calculateRSI($klineHistory['klineСlosePriceList'], $rsiPeriod);
                    $crossMA = \Maksv\StrategyBuilder::checkMACross($klineHistory['klineСlosePriceList']) ?? '';

                    //SAR
                    $sarCandles = array_map(function($k) {
                        return [
                            'h' => floatval($k['h']),
                            'l' => floatval($k['l'])
                        ];
                    }, $kline['result']['list']);
                    $sarData = \Maksv\StrategyBuilder::calculateSARWithTrend($sarCandles);
                }

                $symbol['tradesHistory'] = $tradesHistory ?? [];
                $symbol['kline'] = $kline['result']['list'] ?? [];

                if ($marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName] && is_array($marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName])) {
                    while (count($marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]) >= 10/*$barsCount*/)
                        array_shift($marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]);
                }

                if ($crossMA && $tradesHistory && $sarData) {

                    $openInterest = 0;
                    //$openInterestResp = $bybitApiOb->openInterest($symbolName, 'linear', $timeFrame, '2');
                    $openInterestResp = $bybitApiOb->openInterest($symbolName, 'linear', $timeFrame, '2');
                    if ($openInterestResp['result']['list'] && is_array($openInterestResp['result']['list']) && count($openInterestResp['result']['list']) >= 2) {
                        $lastInterest = $openInterestResp['result']['list'][0]['openInterest'];
                        $prevInterest = $openInterestResp['result']['list'][1]['openInterest'];
                        $openInterest = round(($lastInterest / ($prevInterest / 100)) - 100, 2);
                    } else {
                        //devlogs($openInterestResp, 'openInterestResp' . $timeFrame);
                    }

                    $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName][] = [
                        'klineHistory' => $klineHistory,
                        'rsi' => $rsi,
                        'openInterest' => $openInterest,
                        'priceChange' => $priceChange,
                        'crossMA' => $crossMA,
                        'sarData' => $sarData,
                        'divergences' => $divergences,
                        'tradesHistory' => $tradesHistory,
                        'timeMark' => date("H:i"),
                        'timeStamp' => time(),
                    ];
                } else {
                    if (!$crossMA)
                        devlogs('ERR ' .$symbolName.  ' | err - crossMA' . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);

                    if (!$tradesHistory)
                        devlogs('ERR ' .$symbolName.  ' | err - tradesHistory' . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);

                    if (!$sarData)
                        devlogs('ERR ' .$symbolName.  ' | err - sarData' . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);

                    unset($marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]);
                }

               /* if ($timeFrame == '30m' && $countReq > 50)
                      break;*/

                // devlogs( 'countReq - ' . $countReq . ' | foreach  symbol- ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
            } catch (Exception $e) {
                devlogs('ERR ' .$symbolName. ' countReq - ' . $countReq . ' | err text - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
            }

            $res['symbols'][$symbolName] = $symbol;
            usleep(100000);
        } else if ($marketVolumesJson['RESPONSE_EXCHENGE'][$symbol['name']]) {
            unset($marketVolumesJson['RESPONSE_EXCHENGE'][$symbol['name']]);
        }
    }
    unset($symbol);
    
    foreach ($marketVolumesJson['RESPONSE_EXCHENGE'] as $symbolName => $shots) {
        $shot = $shots[array_key_last($shots)];
        $endTime = time();
        $startTimeMap = [
            '30m' => $endTime - 2700, // - 45м
            '1h' => $endTime - 5400, // -1.5ч
            '4h' => $endTime - 21600, // -6x
            '1d' => $endTime - 129600, // -36ч
        ];
        if (!$shot['timeStamp'] || $shot['timeStamp'] < $startTimeMap[$timeFrame]) {
            unset($marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]);
            devlogs('WARN | clean ' .$symbolName. ' shots' . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
        }
    }

    devlogs('count symbols - ' . $countReq, 'AgentBybitResp'.$timeFrame);

    $bybitApiOb->closeConnection();

    $timeMark = date("d.m.y H:i:s");
    devlogs('end - ' . $timeMark, 'AgentBybitResp'.$timeFrame);

    $data = [
        "TIMEMARK" => $timeMark,
        "RESPONSE_EXCHENGE" => $res,
        "EXCHANGE_CODE" => 'bybit'
    ];

    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/exchangeResponse.json', json_encode($data));

    $marketVolumesJson['TIMEMARK'] = $timeMark;
    $marketVolumesJson['EXCHANGE_CODE'] = 'bybit';

    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/marketVolumes.json', json_encode($marketVolumesJson));

    if ($marketVolumesJson['RESPONSE_EXCHENGE']) {

        $opportunitiesRes = \Maksv\StrategyBuilder::findPumpOrDumpOpportunities($marketVolumesJson['RESPONSE_EXCHENGE'], $timeFrame, 'bybit');

        $data = [
            "TIMEMARK" => $timeMark,
            "STRATEGIES" => $opportunitiesRes,
            //"SYMBOLS" => $actualOpportunities,
            "EXCHANGE_CODE" => 'bybit'
        ];
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . 'bybit' . 'Exchange/'.$timeFrame.'/actualMarketVolumes.json', json_encode($data));

        \Maksv\DataOperation::sendSignalMessage($opportunitiesRes, '@infoCryptoHelper'.$timeFrame);
        \Maksv\DataOperation::sendInfoMessage($opportunitiesRes, $timeFrame);

        try {
            \Maksv\DataOperation::saveSignalToIblock($timeFrame , 'bybit');
        } catch (Exception $e) {
            devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
        }
    }

    return "agentBybitResp".$timeFrame."();";
}

//binance
#делаем запрос на биржу
function AgentBinanceResp() {
    $timeMark = date("d.m.y H:i:s");
    devlogs('start - ' . $timeMark, 'AgentBinanceResp');

    $res = [
        'symbols' => [],
        'prices' => []
    ];

    $binanceOb = new \Maksv\Binance();
    $symbols = $binanceOb->getSymbols();
    //$res['symbols'] = $symbols['symbols'];

    $prices = [];
    $countReq = 0;
    foreach ($symbols['symbols'] as $symbol)
    {
        if ($symbol['status'] != 'BREAK'
            && $symbol['quoteAsset'] == 'USDT'
            //&& !in_array($symbol['quoteAsset'], ['EUR', 'USD', 'TRY', 'JPY', 'BRL'])
            && !in_array($symbol['baseAsset'], ['EUR', 'USD', 'TRY', 'JPY', 'BRL']))
        {
            try
            {
                $res['symbols'][$symbol['symbol']] = $symbol;
                //$prices[$symbol['symbol']] = floatval($binanceOb->getAvgPrice($symbol['symbol'])['price']);
                /*$deph = $binanceOb->getDepth($symbol['symbol']);
                if ($deph && $deph['bids'] && $deph['asks'])
                {
                    $prices[$symbol['symbol']] = [
                        'buyPrice' => floatval($deph['bids'][0][0]),
                        'sellPrice' => floatval($deph['asks'][0][0])
                    ];
                }*/

                $countReq++;
                /*if ($countReq > 1300)
                    break;*/

                //devlogs( 'countReq - ' . $countReq . ' | foreach  symbol- ' . $symbol['symbol'] . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBinanceResp');

            }
            catch (Exception $e)
            {
                devlogs( 'ERR countReq - ' . $countReq . ' | err text - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBinanceResp');
            }
        }
        //usleep(500000);
    }

    //$res['prices'] = $prices;
    devlogs('count symbols - ' . $countReq, 'AgentBinanceResp');


    $timeMark = date("d.m.y H:i:s");
    devlogs('end - ' . $timeMark, 'AgentBinanceResp');

    $data = [
        "TIMEMARK" => $timeMark,
        "RESPONSE_EXCHENGE" => $res,
        "EXCHANGE_CODE" => 'binance'
    ];

    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/exchangeResponse.json', json_encode($data));

    if ($res['prices'])
        \Maksv\StrategyBuilder::findArbitrageOpportunities($res['prices'], true, true, 'binance');

    return "AgentBinanceResp();";
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
