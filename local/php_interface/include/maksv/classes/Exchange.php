<?php
namespace Maksv;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class Exchange
{
    public function __construct(){}

    public static function bybitExchange($timeFrame = '30m',  $oiLimit = 1.7, $devMode = false)
    {
        $timeMark = date("d.m.y H:i:s");
        devlogs("start" . ' - ' . $timeMark, 'bybitExchange'.$timeFrame);
        $res = ['symbols' => [],];

        // проверяем не запускался ли только что обмен
        if (!$devMode) {
            $lastTimestapJson = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/timestap.json'), true);
            if ($lastTimestapJson['TIMESTAP'] && ((time() - $lastTimestapJson['TIMESTAP']) < 600) && !$devMode) {
                devlogs("end, timestap dif -" . ' - ' . $timeMark, 'bybitExchange' . $timeFrame);
                return;
            } else {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/timestap.json', json_encode(['TIMESTAP' => time(), "TIMEMARK" => $timeMark]));
            }
        }
        $marketVolumesJson['RESPONSE_EXCHENGE'] = [];

        $bybitApiOb = new \Maksv\Bybit();
        $bybitApiOb->openConnection();

        //получаем контракты, которые будем анализировать
        $exchangeSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/exchangeSymbolsList.json'), true)['RESPONSE_EXCHENGE'];
        if (!$exchangeSymbolsList || $timeFrame == '1d') {
            $exchangeSymbolsResp = $bybitApiOb->getSymbolsV5("linear");
            if ($exchangeSymbolsResp['result']['list']) {
                $dataSymInfo = [
                    "TIMEMARK" => $timeMark,
                    "RESPONSE_EXCHENGE" => $exchangeSymbolsResp['result']['list'],
                    "EXCHANGE_CODE" => 'bybit'
                ];
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json', json_encode($dataSymInfo));
                $exchangeSymbolsList = $exchangeSymbolsResp['result']['list'];
            }
        }

        $countReq = $analysisSymbolCount = 0;
        $actualOpportunities = [
            'allPump' => [],
            'allDump' => [],
            'pump' => [],
            'dump' => [],
        ];

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
                        $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['timestapOI'] = $timestapOI;
                        $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['openInterest'] = $openInterest;

                        if (
                            in_array($symbolName, ['BTCUSDT', 'ETHUSDT'])
                            || ($openInterest >= $oiLimit)
                            || ($openInterest <= -1)
                        ) {
                            $analysisSymbolCount++;
                            $priceChange = $lastClosePrice = 0;
                            $barsCount = 202;

                            // получаем свечи для определения тренда
                            $kline = $bybitApiOb->klineV5("linear", $symbolName, $timeFrame, $barsCount);
                            $crossMA = false;
                            $closePrices = [];
                            if ($kline['result'] && $kline['result']['list']) {
                                $klineList = array_reverse($kline['result']['list']);

                                //Анализ по скользяшим
                                foreach ($klineList as $klineItem)
                                    $closePrices[] = $klineItem[4];

                                $crossMA200 = \Maksv\TechnicalAnalysis::checkMACross($closePrices, 20, 200, 20, 2) ?? false;
                                $crossMA = \Maksv\TechnicalAnalysis::checkMACross($closePrices, 5, 20, 20, 2) ?? false;

                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['crossMA200'] = $crossMA200;
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['crossMA'] = $crossMA;

                                $crossMAVal = 0;
                                if ($crossMA['is_reversal'] && $crossMA['isUptrend'])
                                    $crossMAVal = 1;
                                else if ($crossMA['is_reversal'] && !$crossMA['isUptrend'])
                                    $crossMAVal = 2;

                                if (!$crossMA200)
                                    devlogs('ERR ' . $symbolName . ' | err - crossMA200' . ' | timeMark - ' . date("d.m.y H:i:s"), 'bybitExchange' . $timeFrame);

                                if (!$crossMA)
                                    devlogs('ERR ' . $symbolName . ' | err - crossMA' . ' | timeMark - ' . date("d.m.y H:i:s"), 'bybitExchange' . $timeFrame);

                                $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                                if ($prevKline) {
                                    $priceChange = round((floatval($prevKline[4]) / (floatval($prevKline[1]) / 100)) - 100, 2);
                                    $lastClosePrice = floatval($prevKline[4]);
                                }

                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['lastClosePrice'] = $lastClosePrice;
                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['priceChange'] = $priceChange;

                                $candles = array_map(function ($k) {
                                    return [
                                        'h' => floatval($k[2]), // High price
                                        'l' => floatval($k[3]), // Low price
                                        'c' => floatval($k[4])  // Close price
                                    ];
                                }, $klineList);
                                //$marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['supertrendCandles'] = $candles;

                                // анализ по Supertrend
                                $supertrendData = \Maksv\StrategyBuilder::calculateSupertrend($candles, 10, 3) ?? false; // длина 10, фактор 3*/

                                if (!$supertrendData)
                                    devlogs('ERR ' . $symbolName . ' | err - supertrendData' . ' | timeMark - ' . date("d.m.y H:i:s"), 'bybitExchange' . $timeFrame);

                                $actualSupertrend  = false;
                                if ($supertrendData && is_array($supertrendData))
                                    $actualSupertrend = $supertrendData[array_key_last($supertrendData)] ??  false;

                                $supertrendVal = 0;
                                if ($actualSupertrend) {
                                    if ($actualSupertrend['is_reversal'] && $actualSupertrend['isUptrend'])
                                        $supertrendVal = 1;
                                    else if ($actualSupertrend['is_reversal'] && $actualSupertrend['isUptrend'])
                                        $supertrendVal = 2;
                                }

                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualSupertrend'] = $actualSupertrend;

                                // анализ по стахостическому индексу относительной силы
                                $stochasticOscillatorData = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles, 14, 14, 3, 3) ?? false;

                                if (!$stochasticOscillatorData)
                                    devlogs('ERR ' . $symbolName . ' | err - stochasticOscillatorData' . ' | timeMark - ' . date("d.m.y H:i:s"), 'bybitExchange' . $timeFrame);

                                $actualStochastic = false;
                                if ($stochasticOscillatorData && is_array($stochasticOscillatorData))
                                    $actualStochastic = $stochasticOscillatorData[array_key_last($stochasticOscillatorData)];

                                $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['actualStochastic'] = $actualStochastic;

                                //уровни
                                $levels = false;
                                $orderBook = $bybitApiOb->orderBookV5('linear', $symbolName, 1000);
                                if ($orderBook['result'] && is_array($orderBook['result'])) {
                                    $findLevelsRes = \Maksv\TechnicalAnalysis::findLevels($orderBook['result'], 200, 0.01);
                                    $levels = [
                                        'lower' => array_slice($findLevelsRes['lower'], 0, 5),
                                        'upper' => array_slice($findLevelsRes['upper'], 0, 5),
                                    ];
                                }

                                $opportunityData = [
                                    'symbolName' => $symbolName,
                                    'lastClosePrice' => $lastClosePrice,
                                    'lastOpenInterest' => $openInterest,
                                    'lastPriceChange' => $priceChange,
                                    'timestapOI' => $timestapOI,

                                    'lastSupertrend' => $actualSupertrend,
                                    'supertrendVal' => $supertrendVal,

                                    'lastCrossMA' => $crossMA,
                                    'lastCrossMA200' => $crossMA200,
                                    'crossMAVal' => $crossMAVal,

                                    'actualStochastic' => $actualStochastic,

                                    'levels' => $levels,

                                    'timeMark' => date("H:i"),
                                    'snapTimeMark' => date("H:i"),
                                    'timeFrame' => $timeFrame,
                                    'anomalyOI' => ($openInterest >= 3),
                                ];

                                if (in_array($symbolName, ['BTCUSDT', 'ETHUSDT'])) {
                                    $actualOpportunities['headCoin'][$symbolName] = $opportunityData;
                                    continue;
                                }

                                $longTrendRule = $shortTrendRule = false;
                                if ($crossMA200) {
                                    $longTrendRule = $crossMA200['isUptrend'];
                                    $shortTrendRule = !$crossMA200['isUptrend'];
                                } else if ($actualSupertrend) {
                                    $longTrendRule = $actualSupertrend['isUptrend'];
                                    $shortTrendRule = !$actualSupertrend['isUptrend'];
                                }

                                //alerts
                                if (
                                    (
                                        $actualStochastic['isLong']
                                        && $crossMA['bollinger']['%B'] < 0.3
                                        && $openInterest > 0
                                    )
                                ) {
                                    $opportunityData['strategy'] = 'stochRSI, BOLL';
                                    $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                }

                                if (
                                    (
                                        $actualStochastic['isShort']
                                        && $crossMA['bollinger']['%B'] > 0.7
                                        && $openInterest < 0
                                    )
                                ) {
                                    $opportunityData['strategy'] = 'stochRSI, BOLL';
                                    $actualOpportunities['allDump'][$symbolName] = $opportunityData;
                                }

                                //alerts Cross
                                if (
                                    (
                                        $actualSupertrend['isUptrend']
                                        && $crossMA['isUptrend']
                                        && $crossMA['is_reversal']
                                        && $openInterest > 0
                                    )
                                ) {
                                    $opportunityData['strategy'] = 'MA20xEMA5';
                                    $actualOpportunities['allPump'][$symbolName] = $opportunityData;
                                }

                                if (
                                    (
                                        !$actualSupertrend['isUptrend']
                                        && !$crossMA['isUptrend']
                                        && $crossMA['is_reversal']
                                        && $openInterest < 0
                                    )
                                ) {
                                    $opportunityData['strategy'] = 'MA20xEMA5';
                                    $actualOpportunities['allDump'][$symbolName] = $opportunityData;
                                }

                                //master, test
                                if (
                                    (
                                        $longTrendRule
                                        && $actualStochastic['isLong']
                                        && $crossMA['bollinger']['%B'] < 0.3
                                        && $openInterest > 0
                                    )
                                ) {
                                    $opportunityData['strategy'] = 'stochRSI, BOLL, MA200';
                                    $actualOpportunities['pump'][$symbolName] = $opportunityData;
                                }

                                if (
                                    (
                                        $shortTrendRule
                                        && $actualStochastic['isShort']
                                        && $crossMA['bollinger']['%B'] > 0.7
                                        && $openInterest < 0
                                    )
                                ) {
                                    $opportunityData['strategy'] = 'stochRSI, BOLL, MA200';
                                    $actualOpportunities['dump'][$symbolName] = $opportunityData;
                                }

                            } else {
                                devlogs('ERR ' . $symbolName . ' | err - kline' . ' | timeMark - ' . date("d.m.y H:i:s"), 'bybitExchange' . $timeFrame);
                                continue;
                            }
                            $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['timeMark'] = date("H:i");
                            $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['timeStamp'] =  time();
                            $marketVolumesJson['RESPONSE_EXCHENGE'][$symbolName]['timeFrame'] =  $timeFrame;
                        }

                    } else {
                        devlogs('ERR ' .$symbolName.  ' | err - OI' . ' | timeMark - ' . date("d.m.y H:i:s"), 'bybitExchange'.$timeFrame);
                    }

                } catch (Exception $e) {
                    devlogs('ERR ' .$symbolName. ' countReq - ' . $countReq . ' | err text - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp'.$timeFrame);
                }

                $res['symbols'][$symbolName] = $symbol;
                usleep(50000);
            }

            /*if ($countReq >= 5)
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
            devlogs('ERR - alert sort Err | timeMark - ' . date("d.m.y H:i:s"), 'bybitExchange'.$timeFrame);
        }

        try {
            uasort($actualOpportunities['pump'], function ($a, $b) {
                return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
            });

            uasort($actualOpportunities['dump'], function ($a, $b) {
                return $b['lastOpenInterest'] <=> $a['lastOpenInterest'];
            });
        } catch (Exception $e) {
            devlogs('ERR - test Err | timeMark - ' . date("d.m.y H:i:s"), 'bybitExchange'.$timeFrame);
        }

        devlogs('analysis symbols count  - ' . $analysisSymbolCount, 'bybitExchange'.$timeFrame);
        devlogs('count symbols - ' . $countReq, 'bybitExchange'.$timeFrame);

        $timeMark = date("d.m.y H:i:s");
        devlogs('step2 - ' . $timeMark, 'bybitExchange'.$timeFrame);

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

        //actualMarketVolumes
        $timeMark = date("d.m.y H:i:s");
        $actualMarketVolumes = [
            "TIMEMARK" => $timeMark,
            "STRATEGIES" => $actualOpportunities,
            "EXCHANGE_CODE" => 'bybit'
        ];

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/' . $timeFrame . '/actualMarketVolumes.json', json_encode($actualMarketVolumes));
        \Maksv\DataOperation::sendInfoMessage($actualOpportunities, $timeFrame);

        //
        //if ($actualOpportunities['pump'] || $actualOpportunities['dump'])
        \Maksv\DataOperation::sendSignalMessage($actualOpportunities['pump'], $actualOpportunities['dump'], '@infoCryptoHelperDev', $timeFrame);
        //

        devlogs('end - ' . date("d.m.y H:i:s"), 'bybitExchange'.$timeFrame);
        $bybitApiOb->closeConnection();

        try {
            devlogs($timeFrame . ' Warn SUPERTRAND | reversal flag  - ' . $actualOpportunities['headCoin']['BTCUSDT']['superTrandReversalFlag'] . ' | is up trend - ' .  $actualOpportunities['headCoin']['BTCUSDT']['lastSupertrend']['isUptrend'] . ' | timeMark - ' . date("d.m.y H:i:s"), 'trendWarning');

            if ($actualOpportunities['headCoin']['BTCUSDT'] && $actualOpportunities['headCoin']['BTCUSDT']['superTrandReversalFlag'] === true)
                \Maksv\DataOperation::sendTrendWarning($actualOpportunities['headCoin']['BTCUSDT'], 'SUPERTRAND', $actualOpportunities['headCoin']['BTCUSDT']['lastSupertrend']['isUptrend'], '@infoCryptoHelperTrend', $timeFrame);

        } catch (Exception $e) {
            devlogs('ERR - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'trendWarning');
        }

        return "bybitExchange".$timeFrame."();";
    }

}
