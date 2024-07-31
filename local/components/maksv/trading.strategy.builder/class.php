<?php
//require_once ($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/belleyou/autoload.php');

/** @global CMain $APPLICATION */
/** @global CUser $USER */
use Bitrix\Main,
    Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache,
    Bitrix\Main\Application,
    Bitrix\Main\Engine\Contract\Controllerable;

use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

class StrategyBuilderComponent extends CBitrixComponent implements Controllerable
{

    public function __construct($component = null)
    {
        parent::__construct($component);

    }

    public function configureActions(): array
    {
        return [
            'updateJsonData' => [
                'prefilters' => []
            ],
            'updateBybitData' => [
                'prefilters' => []
            ],
        ];
    }

    public function onPrepareComponentParams($arParams)
    {
        if(!($arParams["CACHE_TIME"]))
            $arParams["CACHE_TIME"] = 36000;

        if(!($arParams["MARKET_CODE"]))
            $arParams["MARKET_CODE"] = 'bybit';

        if($arParams["PROFIT_FILTER"] == 'Y')
            $arParams["PROFIT_FILTER"] = true;
        else
            $arParams["PROFIT_FILTER"] = false;

        return $arParams;
    }


    public function updatebybitDataAction()
    {
        $res['success'] = false;

        $symbolsAr = explode(',', $_REQUEST['symbols']);

        $bybitApiOb = new \Maksv\Bybit();
        $bybitApiOb->openConnection();
        $prices = [];
        foreach ($symbolsAr as $key => $symbol) {
            $price_data = $bybitApiOb->getSpotPrice($symbol);
            $prices[$symbol] = floatval($price_data['result']['price']);
           /* $prices[] = [
                'symbol' => $symbol,
                'price' => floatval($price_data['result']['price'])
            ];*/
        }
        $bybitApiOb->closeConnection();

        $res['strategies'] = \Maksv\StrategyBuilder::findArbitrageOpportunities($prices, false) ?? [];
        $res['timeMark'] = date("d.m.y H:i:s");

        if ($res['strategies'])
            $res['success'] = true;
        else
            $res['message'] = 'По последней проверке не нашлось профитных стратегий, попробуйте позже';

        return $res;
    }

    public function updatebinanceDataAction()
    {
        $res['success'] = false;

        $symbolsAr = explode(',', $_REQUEST['symbols']);

        $binanceOb = new \Maksv\Binance();

        $prices = [];
        $countReq = 0;
        foreach ($symbolsAr as $symbol) {
            //$prices[$symbol] = floatval($binanceOb->getAvgPrice($symbol)['price']);

            $deph = $binanceOb->getDepth($symbol);
            if ($deph && $deph['bids'] && $deph['asks'])
            {
                
                $prices[$symbol] = [
                    'buyPrice' => floatval($deph['bids'][0][0]),
                    'sellPrice' => floatval($deph['asks'][0][0])
                ];
            }

            usleep(100000);
        }

        $res['strategies'] = \Maksv\StrategyBuilder::findArbitrageOpportunities($prices, false) ?? [];
        $res['timeMark'] = date("d.m.y H:i:s");

        if ($res['strategies'])
            $res['success'] = true;
        else
            $res['message'] = 'По выбранной стратеги больше нет профита';

        //$res['dev'] =  $_REQUEST;
        return $res;
    }

    public function updateJsonDataAction()
    {
        $res['success'] = false;
        $strategies = [];

        if ($_REQUEST['code'])
            $this->arParams['MARKET_CODE'] = $_REQUEST['code'];

        if ($_REQUEST['profitFilter'] == 'true')
            $this->arParams["PROFIT_FILTER"] = true;
        else if ($_REQUEST['profitFilter'] == 'false')
            $this->arParams["PROFIT_FILTER"] = false;

        $lustResponse = $this->getResp($this->arParams['MARKET_CODE'],'actualPrices') ?? [];
        if ($lustResponse['prices'] && is_array($lustResponse['prices']) && count($lustResponse['prices']) >= 1)
            $strategies = \Maksv\StrategyBuilder::findArbitrageOpportunities($lustResponse['prices'], $this->arParams["PROFIT_FILTER"]) ?? [];
        else
            $res['message'] = 'Не удалось получит цены для торговых пар';

        if ($strategies) {
            $res['success'] = true;
            $res['strategies'] = $strategies;
            $res['timeMark'] = $lustResponse['timeMark'];
        } else {
            $res['message'] = 'По какой то причине не удалось получить обновленные ценны по текущим стратегиям';
        }

        /*$res['dev'] = [
            '$_REQUEST' => $_REQUEST,
            '$this->arParams' => $this->arParams,
        ];*/

        return $res;
    }

    protected function getResp($market = 'bybit', $file = 'exchangeResponse')
    {

        $jsonData = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $market . 'Exchange' . '/' . $file . '.json'),true);
        $res = [
            'symbols' => $jsonData['RESPONSE_EXCHENGE']['symbols'],
            'prices' => $jsonData['RESPONSE_EXCHENGE']['prices'],
            'timeMark' => $jsonData['TIMEMARK'],
        ];

        return $res;
    }

    protected function getResult()
    {
        $lastResponse = $this->getResp($this->arParams['MARKET_CODE'], 'exchangeResponse');

        //$this->arResult['STRATEGIES'] = \Maksv\StrategyBuilder::findArbitrageOpportunities($lastResponse['prices'], $this->arParams["PROFIT_FILTER"]) ?? [];
        $this->arResult['STRATEGIES'] = \Maksv\StrategyBuilder::findArbitrageOpportunities($lastResponse['prices'], $this->arParams["PROFIT_FILTER"]) ?? [];
        $this->arResult['SYMBOLS'] = $lastResponse['symbols'];
        $this->arResult['LUST_TIME_MARK'] = $lastResponse['timeMark'];
    }

    public function executeComponent()
    {
        $this->getResult();
        $this->includeComponentTemplate();
    }
}
