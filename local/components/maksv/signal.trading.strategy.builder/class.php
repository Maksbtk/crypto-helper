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

class SignalStrategyBuilderComponent extends CBitrixComponent implements Controllerable
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
            'bybitOI' => [
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

        $arParams["OI_TIMEFRAMES"] = ['15m', '30m', '1h', '4h', '1d'];

        return $arParams;
    }

    public function bybitOIAction()
    {
        $res['success'] = false;
        $err = false;
        $symbol = $_REQUEST['symbols'];
        $res['OI'] = [];
        $res['symbol'] = $symbol;

        //$checkBybitConnect = $this->checkBybitConnect();
        $checkBybitConnect['BYBIT_IS_CONNECT'] = true;
        $res['checkBybitConnect'] = $checkBybitConnect;
        if ($checkBybitConnect['BYBIT_IS_CONNECT']) {

            $bybitApiOb = new \Maksv\Bybit($checkBybitConnect['UF_BYBIT_API_KEY'], $checkBybitConnect['UF_BYBIT_SECRET_KEY']);
            $bybitApiOb->openConnection();

            $timeframeKeyMap = [
                '15m' => 'm15',
                '30m' => 'm30',
                '1h' => 'h1',
                '4h' => 'h4',
                '1d' => 'd1',
            ];

            foreach ($this->arParams["OI_TIMEFRAMES"] as $timeframe) {

                $openInterest = 0;
                $openInterestResp = $bybitApiOb->openInterest($symbol, 'linear', $timeframe, '2');
                if ($openInterestResp['result']['list'] && is_array($openInterestResp['result']['list']) && count($openInterestResp['result']['list']) >= 2) {
                    $lastInterest = $openInterestResp['result']['list'][0]['openInterest'];
                    $prevInterest = $openInterestResp['result']['list'][1]['openInterest'];
                    $openInterest = round(($lastInterest / ($prevInterest / 100)) - 100, 2);

                    $res['OI'][$timeframeKeyMap[$timeframe]] = $openInterest;
                } else {
                    $err = 'Не удалось получить OI для ' . $symbol;
                }

                $kline = $bybitApiOb->klineV5("linear", $symbol, $timeframe, 28);
                $crossMA = $sarData = false;
                if ($kline['result'] && $kline['result']['list']) {
                    $klineList = array_reverse($kline['result']['list']);
                    foreach ($klineList as $klineItem)
                        $klineHistory['klineСlosePriceList'][] = $klineItem[4];

                    $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                    if ($prevKline)
                        $priceChange = round(($prevKline[4] / ($prevKline[1] / 100)) - 100, 2);

                    $res['priceChange'][$timeframeKeyMap[$timeframe]] = $priceChange;

                    //MA x EMA
                    $crossMA = \Maksv\StrategyBuilder::checkMACross($klineHistory['klineСlosePriceList']) ?? '';
                    $res['crossMA'][$timeframeKeyMap[$timeframe]] = $crossMA['cross'];

                    //SAR
                    $sarCandles = array_map(function ($k) {
                        return [
                            'h' => floatval($k[2]),
                            'l' => floatval($k[3])
                        ];
                    }, $klineList);

                    $sarData = \Maksv\StrategyBuilder::calculateSARWithTrend($sarCandles);
                    $lastSar = $sarData[array_key_last($sarData)];
                    if ($lastSar['is_reversal'])
                        $lastSar['trend'] .= ' reversal';

                    $res['sarData'][$timeframeKeyMap[$timeframe]] = $lastSar;

                } else {
                    $err = 'Не удалось получить Цены для ' . $symbol;
                }
            }

            $levels = false;
            $orderBook = $bybitApiOb->orderBookV5('linear', $symbol, 1000);
            if ($orderBook['result'])
                $levels = \Maksv\StrategyBuilder::findLevels($orderBook['result'], 7);
            else {
                $err = 'Не удалось получить уровни ' . $symbol;
            }

            $res['levels'] = $levels;
        } else {
            $err = 'проблемы с bybit api';
        }

        if ($res['OI']) {
            $res['timeMark'] = date("H:i:s");
            $res['success'] = true;
        }

        if ($err) {
            $res['message'] = $err;
            //$res['message2'] = $orderBook['result'];
        }

        return $res;
    }

    protected function getSignals($market = 'bybit', $timeframe = 'master')
    {
        \Bitrix\Main\Loader::includeModule('iblock');

        $res = [];
        $nav = new \Bitrix\Main\UI\PageNavigation("signals");
        $nav->allowAllRecords(true)
            ->setPageSize(5)
            ->initFromUri();

        $resDB = \Bitrix\Iblock\ElementTable::getList(
            array(
                'order' => ['ID' => 'DESC'],
                "filter" => ["IBLOCK_ID" => 3 ,  "ACTIVE" => "Y",  'SECTION.CODE' => $timeframe ],
                'runtime' => [
                    'SECTION' => [
                        'data_type' => '\Bitrix\Iblock\Section',
                        'reference' => ['this.IBLOCK_SECTION_ID' => 'ref.ID'],
                        'join_type' => 'LEFT'
                    ],
                    'PROP' => [
                        'data_type' => '\Bitrix\Iblock\ElementPropertyTable',
                        'reference' => ['this.ID' => 'ref.IBLOCK_ELEMENT_ID'],
                        'join_type' => 'INNER'
                    ],
                ],
                'select' => ['NAME', 'PROP.VALUE', 'ID'],
                "count_total" => true,
                "offset" => $nav->getOffset(),
                "limit" => $nav->getLimit(),
            )
        );

        $nav->setRecordCount($resDB->getCount());
        while($el = $resDB->fetch()) {
            $jsonPath = \CFile::GetPath($el['IBLOCK_ELEMENT_PROP_VALUE']);
            $jsonContent = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $jsonPath), true);
            $res['ITEMS'][] = [
                "NAME" => $el['NAME'],
                "ID" => $el['ID'],
                "FILE_PATH" => $jsonPath,
                "TIMEMARK" => $jsonContent['TIMEMARK'],
                "STRATEGIES" => $jsonContent['STRATEGIES'],
                "TIMEFRAME" => $jsonContent['TIMEFRAME'],
            ];
        }
        $res['NAV'] = $nav;




        return $res;
    }

    protected function checkBybitConnect()
    {
        global $USER;
        global $DB;

        $res = [];
        /*$cache = Cache::createInstance();
        if ($cache->initCache(3600 * 5, md5('CH|checkBybitConnect' .$USER->GetID()))) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {*/

            $arResUser = CUser::GetList(false, false, ["ID" => $USER->GetID()], []);//['UF_BYBIT_SECRET_KEY', 'UF_BYBIT_API_KEY']
            if ($resUser = $arResUser->Fetch()) {
                $res['UF_BYBIT_SECRET_KEY'] = $resUser['UF_BYBIT_SECRET_KEY'];
                $res['UF_BYBIT_API_KEY'] = $resUser['UF_BYBIT_API_KEY'];
                $res['BYBIT_IS_CONNECT'] = false;

                if ($res['UF_BYBIT_SECRET_KEY'] && $res['UF_BYBIT_API_KEY']) {
                    $bybitApiOb = new \Maksv\Bybit(apiKey: $res['UF_BYBIT_API_KEY'], secretKey: $res['UF_BYBIT_SECRET_KEY']);
                    $bybitApiOb->openConnection();
                    $checkConnect = $bybitApiOb->getSymbolsV5('spot', 'BTCUSDT');

                    $res['checkConnect'] = $checkConnect;
                    if ($checkConnect || $checkConnect['retMsg'] == 'OK')
                        $res['BYBIT_IS_CONNECT'] = true;

                    $bybitApiOb->closeConnection();
                }
            }
        /*    $cache->endDataCache($res);
        }*/

        $this->arParams['UF_BYBIT_SECRET_KEY'] = $res['UF_BYBIT_SECRET_KEY'];
        $this->arParams['UF_BYBIT_API_KEY'] = $res['UF_BYBIT_API_KEY'];
        $this->arParams['BYBIT_IS_CONNECT'] = $res['BYBIT_IS_CONNECT'];
        $this->arParams['checkConnect'] = $res['checkConnect'];

        return $res;
    }

    public function executeComponent()
    {
        //$this->checkBybitConnect();
        //echo '<pre>'; var_dump($this->arParams); echo '</pre>';
        $this->arResult = $this->getSignals($this->arParams['MARKET_CODE'], 'master');
        $this->includeComponentTemplate();
    }
}
