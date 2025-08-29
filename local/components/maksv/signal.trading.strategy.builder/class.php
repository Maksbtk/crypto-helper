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

        if(!$arParams["PAGE_COUNT"])
            $arParams["PAGE_COUNT"] = '3';

        if(!($arParams["MARKET_CODE"]))
            $arParams["MARKET_CODE"] = 'bybit';

        $iblockIdMap = [
            'bybit' => 3,
            'binance' => 7,
            'okx' => 8,
            'betaForever' => 9,
        ];

        $arParams["IBLOCK_ID"] = $iblockIdMap[$arParams["MARKET_CODE"]];

        if(!($arParams["MAIN_CODE"]))
            $arParams["MAIN_CODE"] = 'master';

        if(!($arParams["BETA_SECTION_CODE"]))
            $arParams["BETA_SECTION_CODE"] = 'normal_ml';

        if($arParams["PROFIT_FILTER"] == 'Y')
            $arParams["PROFIT_FILTER"] = true;
        else
            $arParams["PROFIT_FILTER"] = false;

        $arParams["OI_TIMEFRAMES"] = ['5m', '15m', '30m', '1h', '4h', '1d'];


        return $arParams;
    }

    public function bybitOIAction()
    {
        $exchangeBybitSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $exchangeBinanceSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
        $binanceSymbolsList = array_column($exchangeBinanceSymbolsList, 'symbol') ?? [];
        $bybitSymbolsList = array_column($exchangeBybitSymbolsList, 'symbol') ?? [];

        $bybitApiOb = new \Maksv\Bybit\Bybit($checkBybitConnect['UF_BYBIT_API_KEY'], $checkBybitConnect['UF_BYBIT_SECRET_KEY']);
        $bybitApiOb->openConnection();
        $binanceApiOb = new \Maksv\Binance\BinanceFutures();
        $binanceApiOb->openConnection();
        $okxApiOb = new \Maksv\Okx\OkxFutures();
        $okxApiOb->openConnection();
        $bingxApiOb = new \Maksv\Bingx\BingxFutures();
        $bingxApiOb->openConnection();

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

            $timeframeKeyMap = [
                '5m' => 'm5',
                '15m' => 'm15',
                '30m' => 'm30',
                '1h' => 'h1',
                '4h' => 'h4',
                '1d' => 'd1',
            ];

            $zoneCandles = [];
            $lastClosePrice = 0;
            $countCandles = 500;

            if ($_REQUEST['onlyZones'] == 'false') {

                foreach ($this->arParams["OI_TIMEFRAMES"] as $timeframe) {
                    $summaryOpenInterestOb = \Maksv\Bybit\Exchange::getSummaryOpenInterest($symbol, $binanceApiOb, $bybitApiOb, $binanceSymbolsList, $bybitSymbolsList, $timeframe);
                    $res['summaryOpenInterestOb'][$timeframeKeyMap[$timeframe]] = $summaryOpenInterestOb ?? [];
                    $res['OI'][$timeframeKeyMap[$timeframe]] = $summaryOpenInterestOb['summaryOI'] ?? 0;

                    if ($timeframe == $zonesTf || $timeframe == '15m' || $timeframe == '5m')
                        $countCandles = 462;

                    $kline = $bybitApiOb->klineV5("linear", $symbol, $timeframe, $countCandles, true, 60);
                    $crossMA = $sarData = false;
                    $priceChange = 0;
                    if ($kline['result'] && $kline['result']['list']) {
                        $klineList = array_reverse($kline['result']['list']);

                        $prevKline = $klineList[array_key_last($klineList) - 1] ?? false; //(смотрим на предыдущую свечу так как последняя - это еще не закрытая)
                        if ($prevKline) {
                            $priceChange = round(($prevKline[4] / ($prevKline[1] / 100)) - 100, 2);
                            $lastClosePrice = $prevKline[4];
                        }
                        $res['priceChange'][$timeframeKeyMap[$timeframe]] = $priceChange;

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

                        //MA x EMA
                        $res['crossMA'][$timeframeKeyMap[$timeframe]] = $res['crossHistoryMA'][$timeframeKeyMap[$timeframe]] = [];
                        try {
                            $crossHistoryMA = \Maksv\TechnicalAnalysis::getMACrossHistory($candles, 12, 200) ?? false;
                            $res['crossHistoryMA'][$timeframeKeyMap[$timeframe]] = $crossHistoryMA;//$crossHistoryMA[array_key_last($crossHistoryMA)];
                            $res['crossMA'][$timeframeKeyMap[$timeframe]] = $crossHistoryMA[array_key_last($crossHistoryMA)]['cross'];
                        } catch (Exception $e) {
                            $res['err'][$timeframeKeyMap[$timeframe]][] = $e->getMessage();
                        }

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

                            $priceIndexToleranceMap = ['5m' => 15, '15m' => 12, '30m' => 12, '1h' => 10, '4h' => 8, '1d' => 6];
                            //$macdData = \Maksv\TechnicalAnalysis::calculateMacdExt($candles, 5, "SMA",35, "SMA", 5, 'SMA', $priceIndexToleranceMap[$timeframe]) ?? false;
                            //$macdData = \Maksv\TechnicalAnalysis::calculateMacdExt($candles, 12, "EMA",26, "EMA", 9, 'EMA', $priceIndexToleranceMap[$timeframe], 'histogram') ?? false;
                            //$res['macdData'][$timeframeKeyMap[$timeframe]] = $macdData;
                            //$lastMacd = $macdData[array_key_last($macdData)];

                            $impulsMacdData = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles) ?? false;
                            $res['impulsMacd'][$timeframeKeyMap[$timeframe]] = $impulsMacdData;

                            $res['lastMacd'][$timeframeKeyMap[$timeframe]] = $lastMacd = \Maksv\Bybit\Exchange::checkMultiMACD(
                                $candles,
                                $timeframe,
                                ['5m' => 14, '15m' => 14, '30m' => 14, '1h' => 14, '4h' => 8, '1d' => 6]

                            );

                            /*$last20 = array_slice($candles, -30);
                            $candlesDev = array_slice($last20, 0, -13);
                            $res['$candlesDev'][$timeframeKeyMap[$timeframe]] = $candlesDev;*/

                            /*$determineEntryPoint = \Maksv\TechnicalAnalysis::determineEntryPoint(0.015, $candlesDev, 'long');
                            $res['determineEntryPoint'][$timeframeKeyMap[$timeframe]] = $determineEntryPoint;*/

                            /*$calculateRiskTargetsWithATR = \Maksv\TechnicalAnalysis::calculateRiskTargetsWithATR(0.01342399669999999948,    0.879, 0.957, 'short');
                            $res['calculateRiskTargetsWithATR'][$timeframeKeyMap[$timeframe]] = $calculateRiskTargetsWithATR;*/

                            $ADXData = \Maksv\TechnicalAnalysis::calculateADX($candles);
                            $res['ADXData'][$timeframeKeyMap[$timeframe]] = $ADXData;
                            //$res['actualADX'][$timeframeKeyMap[$timeframe]] = $ADXData[array_key_last($ADXData)];

                            $ATRData = \Maksv\TechnicalAnalysis::calculateATR($candles);
                            $res['ATRData'][$timeframeKeyMap[$timeframe]] = $ATRData;
                            $lastATR = $ATRData[array_key_last($ATRData)];
                            $res['lastATR'][$timeframeKeyMap[$timeframe]] = $lastATR;

                            $calculateSideVolumesRes = \Maksv\TechnicalAnalysis::calculateSideVolumes($candles);
                            $res['calculateSideVolumesRes'][$timeframeKeyMap[$timeframe]] = $calculateSideVolumesRes;
                            
                            /// анализ по стахостическому индексу относительной силы
                            $stochasticOscillatorData = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles) ?? false;
                            //$res['stochasticRsiData'][$timeframeKeyMap[$timeframe]] = $stochasticOscillatorData;

                            $lastStochastic = $stochasticOscillatorData[array_key_last($stochasticOscillatorData)];
                            //$res['stochasticOscillatorData'][$timeframeKeyMap[$timeframe]] = $stochasticOscillatorData;
                            $res['lastStochastic'][$timeframeKeyMap[$timeframe]] = $lastStochastic;

                            /*$volumeMA = \Maksv\TechnicalAnalysis::calculateVolumeMA($candles);
                            $res['volumeMA'][$timeframeKeyMap[$timeframe]] = $volumeMA;*/

                            /*$detectFlat = \Maksv\TechnicalAnalysis::detectFlat($candles);
                            $res['detectFlat'][$timeframeKeyMap[$timeframe]] = $detectFlat;*/

                            $analyzePivots = \Maksv\TechnicalAnalysis::analyzePivotsSimple($candles);
                            $res['analyzePivots'][$timeframeKeyMap[$timeframe]] = $analyzePivots;

                            $analyzeBol = \Maksv\TechnicalAnalysis::calculateBollingerBands($candles);
                            $res['analyzeBol'][$timeframeKeyMap[$timeframe]] = $analyzeBol;

                            $detectHeadAndShouldersRes = \Maksv\PatternDetector::detectHeadAndShoulders($candles, 3, 2.0, 0.5);
                            $res['detectHeadAndShouldersRes'][$timeframeKeyMap[$timeframe]] = $detectHeadAndShouldersRes;


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
                $kline = $bybitApiOb->klineV5("linear", $symbol, $zonesTf, $countCandles, true, 60);
                if ($kline['result'] && $kline['result']['list']) {
                    $klineList = array_reverse($kline['result']['list']);
                    $zoneCandles = array_map(function ($k) {
                        return [
                            't' => floatval($k[0]), // timestap
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
            $orderBook = $bybitApiOb->orderBookV5('linear', $symbol, 1000);

            $supportResistanceZonesRes = [
                'resistance' => [],
                'support' => [],
            ];

            $detectOrderBlocksRes = ['bullish' => [], 'bearish' => []];
            if ($orderBook['result']) {

                $res['orderBook'] = \Maksv\TechnicalAnalysis::analyzeOrderBook($orderBook) ?? [];

                try {
                    $findLevelsRes = \Maksv\TechnicalAnalysis::findLevels($orderBook['result'], 300, 0.001);
                    $zoneWidthPercentageMap = ['5m' => 0.2, '15m' => 0.3, '30m' => 0.35, '1h' => 0.4, '4h' => 0.5, '1d' => 1,];

                    if ($symbol == 'BTCUSDT') {
                        $zoneWidthPercentageMap = [
                            '5m' => 0.2,
                            '15m' => 0.3,
                            '30m' => 0.35,
                            '1h' => 0.35,
                            '4h' => 0.5,
                            '1d' => 1,
                        ];
                    }

                    $zoneBarsCountMap = [
                        '5m' => 250,
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

        $bybitApiOb->closeConnection();
        $binanceApiOb->closeConnection();
        $okxApiOb->closeConnection();
        $bingxApiOb->closeConnection();
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
            ->setPageSize($this->arParams['PAGE_COUNT'])
            ->initFromUri();

        $propertyStrategiesFileId = $this->getPropertyIdByCode($this->arParams["IBLOCK_ID"], 'STRATEGIES_FILE');
        $propertyTimeframeId = $this->getPropertyIdByCode($this->arParams["IBLOCK_ID"], 'TIMEFRAME');

        if (!$propertyStrategiesFileId || !$propertyTimeframeId) {
            throw new \Exception('Не удалось найти ID свойств STRATEGIES_FILE или TIMEFRAME');
        }

        if ($this->arParams['MAIN_CODE'] == 'all')
            $SECTION_CODE = 'alerts';
        else
            $SECTION_CODE = $this->arParams['MAIN_CODE'];

        if ($this->arParams["MARKET_CODE"] == 'betaForever')
            $SECTION_CODE = $this->arParams['BETA_SECTION_CODE'];

        $filter = [
            'IBLOCK_ID' => $this->arParams["IBLOCK_ID"],
            'ACTIVE' => 'Y',
            'SECTION.CODE' => $SECTION_CODE,
        ];

        $tfVariantAr = ['15m', '30m', '1h', '4h', '1d'];
        if ($_GET['tf'] && in_array($_GET['tf'], $tfVariantAr))
            $filter['=PROP_TIMEFRAME.VALUE'] = $_GET['tf'];

        $resDB = \Bitrix\Iblock\ElementTable::getList([
            'order' => ['ID' => 'DESC'],
            'filter' => $filter,
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

            $dateCreate = DateTime::createFromFormat('d.m.y H:i:s', $jsonContent['TIMEMARK']);
            $originalTime = $dateCreate->format('H:i');
            $dateCreate->modify('-3 hour');
            $formattedName = $originalTime . ' ' . $dateCreate->format('d.m') . ' (GMT ' . $dateCreate->format('H:i') . ') / ' . $timeframeValue;

            $res['ITEMS'][] = [
                "NAME" => $el['NAME'],
                "FORMATTED_NAME" => $formattedName,
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

        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();
        $binanceApiOb = new \Maksv\Binance\BinanceFutures();
        $binanceApiOb->openConnection();
        $okxApiOb = new \Maksv\Okx\OkxFutures();
        $okxApiOb->openConnection();
        $bingxApiOb = new \Maksv\Bingx\BingxFutures();
        $bingxApiOb->openConnection();

        $apiObAr = [
            'bybitApiOb' => $bybitApiOb,
            'binanceApiOb' => $binanceApiOb,
            'okxApiOb' => $okxApiOb,
            'bingxApiOb' => $bingxApiOb,
        ];

        // Проверяем только masterPump и masterDump $this->arParams['MAIN_CODE']
        foreach ([$this->arParams['MAIN_CODE'] . 'Pump', $this->arParams['MAIN_CODE'] . 'Dump'] as $type) {
            if (!isset($strategies[$type]) || !is_array($strategies[$type])) {
                continue;
            }

            foreach ($strategies[$type] as $name => &$signal) {
                if (!isset($signal['symbolName'])) {
                    continue; // Пропуск, если нет symbolName
                }

              /*  if (!$now instanceof DateTime || !$dateSignal instanceof DateTime)
                    continue;*/

                $diffHours = floatval(($now->getTimestamp() - $dateSignal->getTimestamp()) / 3600);
                if ($diffHours <= 0.5) {
                    $signal['priceAnalysis'] = [
                        'status' => false,
                        'message' => 'Skipped, Signal too recent'
                    ];
                    continue; // Пропуск, сигнал слишком свежий
                }

                // Ограничиваем анализ n часами с момента сигнала
                $symbolName = $name;//$signal['symbolName'];
                $startTime = $dateSignal->getTimestamp() * 1000; // Начало в миллисекундах
                $endTime = ($dateSignal->getTimestamp() + 36 * 3600) * 1000; // Конец через n часов в миллисекундах

                //$analysis = $this->analyzeSymbolPriceChange($symbolName, $startTime, $endTime, $type, $signal['actualClosePrice'] , $signal['SL'],  $signal['TP']);

                $direct = '';
                if (strpos($type, 'Dump') !== false) {
                    $direct = 'short';
                } elseif (strpos($type, 'Pump') !== false) {
                    $direct = 'long';
                }

                // Расчёт времени кеширования в зависимости от времени сигнала
                $currentTimeMs = round(microtime(true) * 1000); // текущее время в миллисекундах
                $ageMs = $currentTimeMs - $startTime;
                $oneHourMs = 3600 * 1000;
                $oneDayMs = 24 * $oneHourMs;

                if (!$signal['SL'] || !$signal['TP']) {
                    $processed = \Maksv\Helpers\Trading::processSignal(
                        $direct,
                        floatval($signal['actualATR']['atr']),
                        floatval($signal['actualClosePrice']),
                        $signal['candles15m'], //candles15
                        $signal['actualSupertrend5m'],
                        $signal['actualSupertrend15m'],
                        $signal['actualMacdDivergence'],
                        $signal['symbolScale'],
                        $signal['atrMultipliers'],
                        ['risk' => 6],
                        $symbolName,
                        "bybit/component"
                    );

                    if ($processed !== false) {
                        $signal = array_merge($signal, $processed);
                    }
                }

                $cacheTtl = 10 * 60;
                if ($ageMs < 2 * $oneHourMs) {
                    $cacheTtl = 5 * 60; // 10 минут
                } elseif ($ageMs < $oneDayMs) {
                    $cacheTtl = 6 * 3600; // 6 часов
                } elseif ($ageMs < 3 * $oneDayMs) {
                    $cacheTtl = 14 * 24 * 3600; // неделя
                } else {
                    $cacheTtl = 90 * 24 * 3600; // n месяц
                }

                $marketCode =  $signal['marketCode'] ?? $this->arParams["MARKET_CODE"];
                $analysis = \Maksv\Bybit\Exchange::analyzeSymbolPriceChange(
                    $apiObAr,
                    $symbolName,
                    $startTime,
                    $endTime,
                    $direct,
                    $signal['actualClosePrice'],
                    $signal['SL'],
                    $signal['TP'],
                    false,
                    $cacheTtl,
                    [],
                    $marketCode
                    );

                // Добавляем результаты анализа в сигнал
                $signal['priceAnalysis'] = $analysis;
            }
        }
        $bybitApiOb->closeConnection();
        $binanceApiOb->closeConnection();
        $okxApiOb->closeConnection();
        $bingxApiOb->closeConnection();

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
                    $bybitApiOb = new \Maksv\Bybit\Bybit(apiKey: $res['UF_BYBIT_API_KEY'], secretKey: $res['UF_BYBIT_SECRET_KEY']);
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

