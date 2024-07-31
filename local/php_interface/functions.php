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

#делаем запрос на биржу
function AgentBybitResp() {
    $timeMark = date("d.m.y H:i:s");
    devlogs('start - ' . $timeMark, 'AgentBybitResp');

    $res = [
        'symbols' => [],
        'prices' => []
    ];

    $bybitApiOb = new \Maksv\Bybit();
    $bybitApiOb->openConnection();

    $symbols = $bybitApiOb->getSymbols() ?? [];
    $res['symbols'] = $symbols['result']['list'];

    $prices = [];
    $countReq = 0;
    foreach ($symbols['result']['list'] as $symbol)
    {
        $countReq++;

        /*$symbolName = $symbol['name'];
        $price_data = $bybitApiOb->getSpotPrice($symbolName);
        $prices[$symbolName] = floatval($price_data['result']['price']);
        $countReq++;
        if ($countReq > 50)
            break;*/
        try
        {
            $symbolName = $symbol['name'];
            $deph = $bybitApiOb->getDepth($symbolName, 1);
            if ($deph['result'] && $deph['result']['bids'] && $deph['result']['asks'])
            {
                $prices[$symbolName] = [
                    'buyPrice' => floatval($deph['result']['bids'][0][0]),
                    'sellPrice' => floatval($deph['result']['asks'][0][0])
                ];
            }
          /* if ($countReq > 50)
                break;*/

           //devlogs( 'countReq - ' . $countReq . ' | foreach  symbol- ' . $symbolName . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp');
        }
        catch (Exception $e)
        {
            devlogs( 'ERR countReq - ' . $countReq . ' | err text - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitResp');
        }
    }
    $res['prices'] = $prices;
    devlogs('count prices - ' . count($prices), 'AgentBybitResp');

    $bybitApiOb->closeConnection();

    $timeMark = date("d.m.y H:i:s");
    devlogs('end - ' . $timeMark, 'AgentBybitResp');

    $data = [
        "TIMEMARK" => $timeMark,
        "RESPONSE_EXCHENGE" => $res,
        "EXCHANGE_CODE" => 'bybit'
    ];

    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/exchangeResponse.json', json_encode($data));

    if ($res['prices'])
        \Maksv\StrategyBuilder::findArbitrageOpportunities($res['prices'], false, true, 'bybit');

    return "AgentBybitResp();";
}

#делаем запрос на биржу
function AgentBybitActualPricesResp() {
    for ($i = 1; $i <= 7; $i++) {
        $timeMark = date("d.m.y H:i:s");
        devlogs('start - ' . $timeMark, 'AgentBybitActualPricesResp');

        $res = [];
        $jsonData = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/actualSymbols.json'),true);

        $res['symbols'] = $jsonData['SYMBOLS'];
        $bybitApiOb = new \Maksv\Bybit();
        $bybitApiOb->openConnection();
        $prices = [];

        $countReq = 0;
        foreach ($res['symbols'] as $symbol) {
            /* $symbolName = $symbol['name'];
            $price_data = $bybitApiOb->getSpotPrice($symbolName);
            $prices[$symbolName] = floatval($price_data['result']['price']);
            $countReq++;
             if ($countReq > 50)
                 break;*/

            $countReq++;
            try
            {
                $symbolName = $symbol['name'];
                $deph = $bybitApiOb->getDepth($symbolName, 1);
                if ($deph['result'] && $deph['result']['bids'] && $deph['result']['asks'])
                {
                    $prices[$symbolName] = [
                        'buyPrice' => floatval($deph['result']['bids'][0][0]),
                        'sellPrice' => floatval($deph['result']['asks'][0][0])
                    ];
                }
                /*if ($countReq > 1300)
                    break;

                devlogs( 'countReq - ' . $countReq . ' | foreach  symbol- ' . $symbol['symbol'] . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitActualPricesResp');*/
            }
            catch (Exception $e)
            {
                devlogs( 'ERR countReq - ' . $countReq . ' | err text - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBybitActualPricesResp');
            }

        }
        $res['prices'] = $prices;
        devlogs('count prices - ' . count($prices), 'AgentBybitActualPricesResp');

        $bybitApiOb->closeConnection();

        $timeMark = date("d.m.y H:i:s");
        devlogs('end - ' . $timeMark, 'AgentBybitActualPricesResp');

        $data = [
            "TIMEMARK" => $timeMark,
            "RESPONSE_EXCHENGE" => $res,
            "EXCHANGE_CODE" => 'bybit'
        ];

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/actualPrices.json', json_encode($data));

        sleep(8);
    }

    return "AgentBybitActualPricesResp();";
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
    $res['symbols'] = $symbols['symbols'];

    $prices = [];
    $countReq = 0;
    foreach ($symbols['symbols'] as $symbol)
    {
        if ($symbol['status'] != 'BREAK'
            && !in_array($symbol['quoteAsset'], ['EUR', 'USD', 'TRY', 'JPY', 'BRL'])
            && !in_array($symbol['baseAsset'], ['EUR', 'USD', 'TRY', 'JPY', 'BRL']))
        {
            try
            {
                //$prices[$symbol['symbol']] = floatval($binanceOb->getAvgPrice($symbol['symbol'])['price']);
                $deph = $binanceOb->getDepth($symbol['symbol']);
                if ($deph && $deph['bids'] && $deph['asks'])
                {
                    $prices[$symbol['symbol']] = [
                        'buyPrice' => floatval($deph['bids'][0][0]),
                        'sellPrice' => floatval($deph['asks'][0][0])
                    ];
                }

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
        usleep(500000);
    }

    $res['prices'] = $prices;
    devlogs('count prices - ' . count($prices), 'AgentBinanceResp');


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

function AgentBinanceActualPricesResp() {
    for ($i = 1; $i <= 4; $i++) {
        $timeMark = date("d.m.y H:i:s");
        devlogs('start - ' . $timeMark, 'AgentBinanceActualPricesResp');

        $res = [];
        $jsonData = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/actualSymbols.json'),true);

        $res['symbols'] = $jsonData['SYMBOLS'];

        $binanceOb = new \Maksv\Binance();
        $prices = [];
        $countReq = 0;
        foreach ($res['symbols'] as $symbol) {
            try
            {
                //$prices[$symbol['name']] = floatval($binanceOb->getAvgPrice($symbol['name'])['price']);
                $deph = $binanceOb->getDepth($symbol['name']);
                if ($deph && $deph['bids'] && $deph['asks'])
                {
                    $prices[$symbol['name']] = [
                        'buyPrice' => floatval($deph['bids'][0][0]),
                        'sellPrice' => floatval($deph['asks'][0][0])
                    ];
                }
                /*$countReq++;
                if ($countReq > 2)
                    break;*/

            }
            catch (Exception $e)
            {
                devlogs( 'ERR countReq - ' . $countReq . ' | err text - ' . $e->getMessage() . ' | timeMark - ' . date("d.m.y H:i:s"), 'AgentBinanceActualPricesResp');
            }
            usleep(500000);
        }
        $res['prices'] = $prices;
        devlogs('count prices - ' . count($prices), 'AgentBinanceActualPricesResp');

        $timeMark = date("d.m.y H:i:s");
        devlogs('end - ' . $timeMark, 'AgentBinanceActualPricesResp');

        $data = [
            "TIMEMARK" => $timeMark,
            "RESPONSE_EXCHENGE" => $res,
            "EXCHANGE_CODE" => 'binance'
        ];

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/actualPrices.json', json_encode($data));

        sleep(15);
    }

    return "AgentBinanceActualPricesResp();";
}
