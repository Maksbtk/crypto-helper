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

        if(!($arParams["MAIN_CODE"]))
            $arParams["MAIN_CODE"] = 'master';

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

        $zonesTf = '1h';
        if ($_REQUEST['zoneTf'])
            $zonesTf = $_REQUEST['zoneTf'];

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

            $zoneCandles = [];
            $lastClosePrice = 0;
            $countCandles = 252;

            if ($_REQUEST['onlyZones'] == 'false') {
                foreach ($this->arParams["OI_TIMEFRAMES"] as $timeframe) {

                    $openInterest = 0;
                    $openInterestResp = $bybitApiOb->openInterest($symbol, 'linear', $timeframe, '2', true, 120);
                    if ($openInterestResp['result']['list'] && is_array($openInterestResp['result']['list']) && count($openInterestResp['result']['list']) >= 2) {
                        $lastInterest = $openInterestResp['result']['list'][0]['openInterest'];
                        $prevInterest = $openInterestResp['result']['list'][1]['openInterest'];
                        $openInterest = round(($lastInterest / ($prevInterest / 100)) - 100, 2);

                        $res['timestapOI'][$timeframeKeyMap[$timeframe]] = date("d.m H:i", $openInterestResp['result']['list'][1]['timestamp'] / 1000) . ' - ' . date("d.m H:i", $openInterestResp['result']['list'][0]['timestamp'] / 1000);
                        //$res['OIList'][$timeframeKeyMap[$timeframe]] = $openInterestResp['result']['list'];
                        $res['OI'][$timeframeKeyMap[$timeframe]] = $openInterest;
                    } else {
                        $err = 'Не удалось получить OI для ' . $symbol;
                        break;
                    }

                    if ($timeframe == $zonesTf)
                        $countCandles = 362;

                    $kline = $bybitApiOb->klineV5("linear", $symbol, $timeframe, $countCandles, true, 900);
                    $crossMA = $sarData = false;
                    $priceChange = 0;
                    if ($kline['result'] && $kline['result']['list']) {
                        $klineList = array_reverse($kline['result']['list']);
                        foreach ($klineList as $klineItem)
                            $klineHistory['klineСlosePriceList'][] = $klineItem[4];

                        $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                        if ($prevKline) {
                            $priceChange = round(($prevKline[4] / ($prevKline[1] / 100)) - 100, 2);
                            if ($timeframe == '15m') {
                                $lastClosePrice = $prevKline[4];
                            }
                        }
                        $res['priceChange'][$timeframeKeyMap[$timeframe]] = $priceChange;
                        //$res['klineListDev'][$timeframeKeyMap[$timeframe]] = $priceChange;

                        //MA x EMA
                        //$crossMA = \Maksv\TechnicalAnalysis::checkMACross($klineHistory['klineСlosePriceList'], 5, 20) ?? '';
                        $crossHistoryMA = \Maksv\TechnicalAnalysis::getMACrossHistory($klineHistory['klineСlosePriceList'], 5, 20, 11) ?? false;
                        $res['crossHistoryMA'][$timeframeKeyMap[$timeframe]] = $crossHistoryMA[array_key_last($crossHistoryMA)];
                        $res['crossMA'][$timeframeKeyMap[$timeframe]] = $crossHistoryMA[array_key_last($crossHistoryMA)]['cross'];

                        //SAR
                        $sarCandles = array_map(function ($k) {
                            return [
                                'h' => floatval($k[2]),
                                'l' => floatval($k[3])
                            ];
                        }, $klineList);

                        $sarData = \Maksv\TechnicalAnalysis::calculateSARWithTrend($sarCandles);
                        $lastSar = $sarData[array_key_last($sarData)];
                        if ($lastSar['is_reversal'])
                            $lastSar['trend'] .= ' reversal';

                        $res['sarData'][$timeframeKeyMap[$timeframe]] = $lastSar;

                        $candles = array_map(function ($k) {
                            return [
                                'o' => floatval($k[1]), // Open price
                                'h' => floatval($k[2]), // High price
                                'l' => floatval($k[3]), // Low price
                                'c' => floatval($k[4]), // Close price
                                'v' => floatval($k[5])  // Volume
                            ];
                        }, $klineList);

                        if ($timeframe == $zonesTf) {
                            $zoneCandles = $candles;
                        }

                        $res['supertrendData'][$timeframeKeyMap[$timeframe]] = $res['lastStochastic'][$timeframeKeyMap[$timeframe]] = $res['macdData'][$timeframeKeyMap[$timeframe]] = $res['simpleTrendLineData'][$timeframeKeyMap[$timeframe]] = [];
                        try {
                            $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($candles, 10, 3) ?? false; // длина 10, фактор 3
                            $lastSupertrend = $supertrendData[array_key_last($supertrendData)];
                            if ($lastSupertrend['is_reversal'])
                                $lastSupertrend['trend'] .= ' reversal';

                            $res['supertrendData'][$timeframeKeyMap[$timeframe]] = $lastSupertrend;

                            $macdData = \Maksv\TechnicalAnalysis::calculateMacdExt($candles, 5, "SMA",35, "SMA", 5, 'SMA') ?? false;
                            //$res['macdData'][$timeframeKeyMap[$timeframe]] = $macdData;

                            $lastMacd = $macdData[array_key_last($macdData)];
                            $res['lastMacd'][$timeframeKeyMap[$timeframe]] = $lastMacd;

                            // анализ по стахостическому индексу относительной силы
                            $stochasticOscillatorData = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles) ?? false;
                            //$res['stochasticRsiData'][$timeframeKeyMap[$timeframe]] = $stochasticOscillatorData;

                            $lastStochastic = $stochasticOscillatorData[array_key_last($stochasticOscillatorData)];
                            $res['stochasticOscillatorData'][$timeframeKeyMap[$timeframe]] = $stochasticOscillatorData;
                            $res['lastStochastic'][$timeframeKeyMap[$timeframe]] = $lastStochastic;

                            $ATRData = \Maksv\TechnicalAnalysis::calculateATR($candles);
                            $lastATR = $ATRData[array_key_last($ATRData)];
                            $res['lastATR'][$timeframeKeyMap[$timeframe]] = $lastATR;

                        } catch (Exception $e) {
                            $res['err'][$timeframeKeyMap[$timeframe]][] = $e->getMessage();
                        }

                    } else {
                        $err = 'Не удалось получить Цены для ' . $symbol;
                        break;
                    }
                }
            } else {
                $countCandles = 362;
                $kline = $bybitApiOb->klineV5("linear", $symbol, $zonesTf, $countCandles, true, 900);
                if ($kline['result'] && $kline['result']['list']) {
                    $klineList = array_reverse($kline['result']['list']);
                    $zoneCandles = array_map(function ($k) {
                        return [
                            'o' => floatval($k[1]), // Open price
                            'h' => floatval($k[2]), // High price
                            'l' => floatval($k[3]), // Low price
                            'c' => floatval($k[4]), // Close price
                            'v' => floatval($k[5])  // Volume
                        ];
                    }, $klineList);
                }

            }

            $levels = false;
            $orderBook = $bybitApiOb->orderBookV5('linear', $symbol, 10);

            $supportResistanceZonesRes = [
                'resistance' => [],
                'support' => [],
            ];

            if ($orderBook['result']) {
                try {
                    $findLevelsRes = \Maksv\TechnicalAnalysis::findLevels($orderBook['result'], 300, 0.001);

                    $zoneWidthPercentageMap = ['15m' => 0.3, '30m' => 0.35, '1h' => 0.4, '4h' => 0.5, '1d' => 1,];

                    if ($symbol == 'BTCUSDT') {
                        $zoneWidthPercentageMap = [
                            '15m' => 0.3,
                            '30m' => 0.35,
                            '1h' => 0.35,
                            '4h' => 0.5,
                            '1d' => 1,
                        ];
                    }

                    $zoneBarsCountMap = [
                        '15m' => 200,
                        '30m' => 200,
                        '1h' => 200,
                        '4h' => 150,
                        '1d' => 100,
                    ];

                    $zoneCandles = array_slice($zoneCandles, -$zoneBarsCountMap[$zonesTf]);
                    $supportResistanceZonesRes = \Maksv\TechnicalAnalysis::findSupportResistanceZones($zoneCandles, $zoneWidthPercentageMap[$zonesTf]/*, $orderBook['result']*/);
                    $detectOrderBlocksRes = \Maksv\TechnicalAnalysis::detectOrderBlocks($zoneCandles, [], -1);


                } catch (Exception $e) {
                    $res['err'][] = $e->getMessage();
                }

                $levels = [
                    'lower' => array_slice($findLevelsRes['lower'], 0, 100) ?? [],
                    'upper' => array_slice($findLevelsRes['upper'], 0, 100) ?? [],
                ];
            } else {
                $err = 'Не удалось получить уровни ' . $symbol;
            }

            $res['levels'] = $levels;
            $res['supportResistanceZonesRes'] = $supportResistanceZonesRes;
            $res['detectOrderBlocksRes'] = $detectOrderBlocksRes;
            $res['onlyZones'] = $_REQUEST['onlyZones'];
            $res['zonesTf'] = $zonesTf;

        } else {
            $err = 'проблемы с bybit api';

        }

        if ($res['OI'] || $res['supportResistanceZonesRes']['resistance']|| $res['supportResistanceZonesRes']['support']) {
            $res['timeMark'] = date("H:i:s");
            $res['success'] = true;
        }

        if ($err) {
            $res['message'] = $err;
            //$res['message2'] = $orderBook['result'];
        }

        return $res;
    }

    protected function getPropertyIdByCode($iblockId, $code)
    {
        $property = \Bitrix\Iblock\PropertyTable::getList([
            'filter' => ['IBLOCK_ID' => $iblockId, 'CODE' => $code],
            'select' => ['ID']
        ])->fetch();

        return $property ? $property['ID'] : null;
    }

    protected function getSignals($market = 'bybit', $sectionId = 'master')
    {
        \Bitrix\Main\Loader::includeModule('iblock');

        $res = [];
        $nav = new \Bitrix\Main\UI\PageNavigation("signals");
        $nav->allowAllRecords(true)
            ->setPageSize(3)
            ->initFromUri();

        $propertyStrategiesFileId = $this->getPropertyIdByCode(3, 'STRATEGIES_FILE');
        $propertyTimeframeId = $this->getPropertyIdByCode(3, 'TIMEFRAME');

        if (!$propertyStrategiesFileId || !$propertyTimeframeId) {
            throw new \Exception('Не удалось найти ID свойств STRATEGIES_FILE или TIMEFRAME');
        }

        if ($this->arParams['MAIN_CODE'] == 'all')
            $SECTION_CODE = 'alerts';
        else
            $SECTION_CODE = $this->arParams['MAIN_CODE'];

        $resDB = \Bitrix\Iblock\ElementTable::getList([
            'order' => ['ID' => 'DESC'],
            'filter' => [
                'IBLOCK_ID' => 3,
                'ACTIVE' => 'Y',
                'SECTION.CODE' => $SECTION_CODE
            ],
            'runtime' => [
                'SECTION' => [
                    'data_type' => '\Bitrix\Iblock\Section',
                    'reference' => ['this.IBLOCK_SECTION_ID' => 'ref.ID'],
                    'join_type' => 'LEFT'
                ],
                'PROP_STRATEGIES_FILE' => [
                    'data_type' => '\Bitrix\Iblock\ElementPropertyTable',
                    'reference' => [
                        'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                        'ref.IBLOCK_PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $propertyStrategiesFileId) // ID свойства STRATEGIES_FILE
                    ],
                    'join_type' => 'LEFT'
                ],
                'PROP_TIMEFRAME' => [
                    'data_type' => '\Bitrix\Iblock\ElementPropertyTable',
                    'reference' => [
                        'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                        'ref.IBLOCK_PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $propertyTimeframeId) // ID свойства TIMEFRAME
                    ],
                    'join_type' => 'LEFT'
                ],
            ],
            'select' => [
                'NAME',
                'PROP_STRATEGIES_FILE_VALUE' => 'PROP_STRATEGIES_FILE.VALUE', // Значение STRATEGIES_FILE
                'PROP_TIMEFRAME_VALUE' => 'PROP_TIMEFRAME.VALUE',           // Значение TIMEFRAME
                'ID',
                'DATE_CREATE'
            ],
            'count_total' => true,
            'offset' => $nav->getOffset(),
            'limit' => $nav->getLimit(),
        ]);

        $nav->setRecordCount($resDB->getCount());
        while($el = $resDB->fetch()) {
            $jsonPath = \CFile::GetPath($el['PROP_STRATEGIES_FILE_VALUE']);
            $timeframeValue = $el['PROP_TIMEFRAME_VALUE'];
            $jsonContent = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $jsonPath), true);

            // Обработка masterPump и masterDump $this->arParams['MAIN_CODE']
            if (isset($jsonContent['STRATEGIES'])) {
                $this->processStrategies($jsonContent['STRATEGIES'], $el['DATE_CREATE']);
            }

            $res['ITEMS'][] = [
                "NAME" => $el['NAME'],
                "DATE_CREATE" => $el['DATE_CREATE'],
                "ID" => $el['ID'],
                "FILE_PATH" => $jsonPath,
                "TIMEFRAME" => $timeframeValue,
                "TIMEMARK" => $jsonContent['TIMEMARK'],
                "STRATEGIES" => $jsonContent['STRATEGIES'],
                "INFO" => $jsonContent['INFO'],
                //"TIMEFRAME" => $jsonContent['TIMEFRAME'],
            ];
        }
        $res['NAV'] = $nav;

        return $res;
    }

    protected function processStrategies(&$strategies, $dateCreate)
    {
        $dateSignal = \DateTime::createFromFormat('d.m.Y H:i:s', $dateCreate);
        $now = new \DateTime();
        $result = [];

        // Проверяем только masterPump и masterDump $this->arParams['MAIN_CODE']
        foreach ([$this->arParams['MAIN_CODE'] . 'Pump', $this->arParams['MAIN_CODE'] . 'Dump'] as $type) {
            if (!isset($strategies[$type]) || !is_array($strategies[$type])) {
                continue;
            }

            foreach ($strategies[$type] as &$signal) {
                if (!isset($signal['symbolName'])) {
                    continue; // Пропуск, если нет symbolName
                }

                $diffHours = ($now->getTimestamp() - $dateSignal->getTimestamp()) / 3600;
                if ($diffHours <= 2) {
                    $signal['priceAnalysis'] = [
                        'status' => false,
                        'message' => 'Skipped, Signal too recent'
                    ];
                    continue; // Пропуск, сигнал слишком свежий
                }

                // Ограничиваем анализ 48 часами с момента сигнала
                $symbolName = $signal['symbolName'];
                $startTime = $dateSignal->getTimestamp() * 1000; // Начало в миллисекундах
                $endTime = ($dateSignal->getTimestamp() + 48 * 3600) * 1000; // Конец через 48 часов в миллисекундах

                $analysis = $this->analyzeSymbolPriceChange($symbolName, $startTime, $endTime, $type);

                // Добавляем результаты анализа в сигнал
                $signal['priceAnalysis'] = $analysis;
            }
        }
    }

    protected function analyzeSymbolPriceChange($symbolName, $startTime, $endTime, $type, $actualClosePrice = false)
    {
        $bybitApiOb = new \Maksv\Bybit();
        $bybitApiOb->openConnection();

        // Запрашиваем свечи
        $kline = $bybitApiOb->klineTimeV5("linear", $symbolName, $startTime, $endTime, '5m', 1000, true, 3600);
        $bybitApiOb->closeConnection();

        if (!$kline['result'] || empty($kline['result']['list'])) {
            return [
                'status' => false,
                'message' => 'No data from API'
            ];
        }

        $klineList = array_reverse($kline['result']['list']);
        $candles = array_map(function ($k) {
            return [
                'o' => floatval($k[1]), // Open price
                'h' => floatval($k[2]), // High price
                'l' => floatval($k[3]), // Low price
                'c' => floatval($k[4]), // Close price
            ];
        }, $klineList);

        // Найдём самую высокую или самую низкую цену в зависимости от типа
        $targetPrice = ($type === $this->arParams['MAIN_CODE'] . 'Pump')
            ? max(array_column($candles, 'h')) // Самая высокая цена
            : min(array_column($candles, 'l')); // Самая низкая цена

        $firstCandle = reset($candles);

        $lastPrice = $firstCandle['o'];
        if ($actualClosePrice)
            $lastPrice = $actualClosePrice;

        // Рассчёт изменения цены
        $priceChange = (($targetPrice - $lastPrice) / $lastPrice) * 100;
        $direction = $priceChange > 0 ? 'up' : 'down';

        return [
            'status' => true,
            'direction' => $direction,
            'percent_change' => round($priceChange, 2),
            'target_price' => $targetPrice,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
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

        $arResUser = \CUser::GetList(false, false, ["ID" => $USER->GetID()], []);//['UF_BYBIT_SECRET_KEY', 'UF_BYBIT_API_KEY']
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
        $this->arResult = $this->getSignals($this->arParams['MARKET_CODE'], $this->arParams['MAIN_CODE']);
        $this->includeComponentTemplate();
    }
}

