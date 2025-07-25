<?

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Page\Asset;

define('NEED_AUTH', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/maksv-statPage.css?v=1");

$exchangeIblockID = isset($_GET['exchange']) ? intval($_GET['exchange']) : 9;
$categorySectionID = isset($_GET['category']) ? intval($_GET['category']) : 10;
$dataSource = isset($_GET['dataSource']) ? strval($_GET['dataSource']) : 'n';

$marketMap = [
    9 => 'beta forever',
];

$categoriesMap = [
    9 => [
        10 => 'normal ml',
        11 => 'high ml',
    ],
];

//получаем контракты, которые будем анализировать
$exchangeBybitSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
$exchangeBinanceSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
$exchangeOkxSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/okxExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
$exchangeBingxSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bingxExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];

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
$bingxSymbolsList = array_column(
    array_map(function($item) {
        return [
            $item['symbol'],
            str_replace('-', '', $item['symbol'])
        ];
    }, $exchangeBingxSymbolsList),
    1,
    0
);

$dataSourceArr = [
    'bybit' => $bybitSymbolsList,
    'binance' => $binanceSymbolsList ,
    'okx' => $okxSymbolsList,
    'bingx' => $bingxSymbolsList
];

global $APPLICATION;

$APPLICATION->SetPageProperty("title", "stat " . $marketMap[$exchangeIblockID] . ' ' . $categoriesMap[$exchangeIblockID][$categorySectionID]);
$APPLICATION->SetTitle("stat " . $marketMap[$exchangeIblockID] . ' ' . $categoriesMap[$exchangeIblockID][$categorySectionID]);

global $USER;
$errors = [];

$defaultStartDate = new \DateTime('now');
$defaultStartDate->modify('-2 days')->setTime(0, 0, 0);

$defaultEndDate = new \DateTime('now');
$defaultEndDate->setTime(23, 59, 0);

// нужный формат для datetime-local
$defaultStartStr = $defaultStartDate->format('Y-m-d\TH:i');
$defaultEndStr = $defaultEndDate->format('Y-m-d\TH:i');

// Получаем параметры из GET-запроса (если имеются)
$tpCountGeneral = isset($_GET['tpCountGeneral']) ? intval($_GET['tpCountGeneral']) : 0;  // сколько всего тейк профитов у сделки  (false)
$deposit = isset($_GET['deposit']) ? intval($_GET['deposit']) : 100;  //сумма на которая используется в сделке  ()
$shiftSL = isset($_GET['shiftSL']) ? intval($_GET['shiftSL']) : false;  // передвигание стопа после достижения какого тейк профита  ()

if ($tpCountGeneral > 0 && $tpCountGeneral <= $shiftSL) {
    $shiftSL = false;
    $errors[] = 'сдвиг стопа не соответствует настройке количества тейк профитов';
}

$portionWeight = [0.5, 0.5];
if ($tpCountGeneral > 0) {
    // Рассчитываем значение одной доли
    $weightValue = 1 / $tpCountGeneral;

    // Заполняем массив равными значениями
    for ($i = 0; $i < $tpCountGeneral; $i++) {
        $portionWeight[] = $weightValue;
    }
}

$riskFilter = isset($_GET['riskFilter']) ? round(floatval($_GET['riskFilter']), 1) : 7;
$leverege = isset($_GET['leverege']) ? intval($_GET['leverege']) : 1;  // плечо котое используется вв сделке  ()

$defaultTpSrearAr = [1.9, 2.6, 3.4, 5.6, 6.9];
$tpStrategyAr = [
    0 => ['NAME' => 'default', 'VALUE' => false],
    1 => ['NAME' => '1.1, 2.2, 3.6, 6, 8.4', 'VALUE' => [1.1, 2.2, 3.6, 6, 8.4]],
    3 => ['NAME' => '1.4, 2.6, 3.4, 5.6, 6.9', 'VALUE' => [1.4, 2.6, 3.4, 5.6, 6.9]],
    4 => ['NAME' => '1.8, 2.6, 3.4, 5.6, 6.9', 'VALUE' => [1.8, 2.6, 3.4, 5.6, 6.9]],
    5 => ['NAME' => '1.9, 2.6, 3.4, 5.6, 6.9', 'VALUE' => [1.9, 2.6, 3.4, 5.6, 6.9]],
    6 => ['NAME' => '1.9, 2.7, 3.4, 5.6, 6.9', 'VALUE' => [1.9, 2.7, 3.4, 5.6, 6.9]],
    7 => ['NAME' => '1.9, 2.9, 3.9, 5.9, 6.9', 'VALUE' => [1.9, 2.9, 3.9, 5.9, 6.9]],
    8 => ['NAME' => '2.3, 2.9, 3.3, 5.6, 6.9', 'VALUE' => [2.3, 2.9, 3.3, 5.6, 6.9]],
    9 => ['NAME' => '2.2, 3.4, 4.9, 5.6, 6.9', 'VALUE' => [2.2, 3.4, 4.9, 5.6, 6.9]],
    10 => ['NAME' => '2.5, 3.4, 5.5, 7.4, 9.9', 'VALUE' => [2.5, 3.4, 5.5, 7.4, 9.9]],
    11 => ['NAME' => '3.5, 4.3, 5.2, 6.3, 7.2', 'VALUE' => [3.5, 4.3, 5.2, 6.3, 7.2]],
    12 => ['NAME' => '2.6, 3.0, 3.5, 4.3, 5.2', 'VALUE' => [2.6, 3.0, 3.5, 4.3, 5.2]],
    13 => ['NAME' => '2.6, 3.4, 4.3, 7.4, 9.9', 'VALUE' => [2.6, 3.4, 4.3, 7.4, 9.9]],
    14 => ['NAME' => '2.6, 3.5, 4.3, 7.4, 9.9', 'VALUE' => [2.6, 3.5, 4.3, 7.4, 9.9]],
    15 => ['NAME' => '2.6, 3.6, 4.3, 7.4, 9.9', 'VALUE' => [2.6, 3.6, 4.3, 7.4, 9.9]],
    16 => ['NAME' => '2.6, 3.7, 4.3, 7.4, 9.9', 'VALUE' => [2.6, 3.7, 4.3, 7.4, 9.9]],
    17 => ['NAME' => '2.6, 3.7, 4.3, 7.4, 9.9', 'VALUE' => [2.6, 3.7, 4.3, 7.4, 9.9]],
    19 => ['NAME' => '2.9, 3.5, 4.3, 5.4, 6.3', 'VALUE' => [2.9, 3.4, 4.3, 5.4, 6.3]],
    18 => ['NAME' => '3.4, 4.3, 5.3, 6.4, 7.3', 'VALUE' => [3.4, 4.3, 5.3, 6.4, 7.3]],
];

$tpFilter = isset($_GET['tpFilter']) ? intval($_GET['tpFilter']) : 0;
$selectedTpStrategy = $tpStrategyAr[$tpFilter];
$tpFilterAr = ['SELECTED_TP_STRATEGY' => $selectedTpStrategy, 'TP_FILTER' => $tpFilter];
if (!$selectedTpStrategy['VALUE'])
    $selectedTpStrategy['VALUE'] = $defaultTpSrearAr;


$directionFilter = isset($_GET['directionFilter']) ? $_GET['directionFilter'] : false;
$tfFilter = isset($_GET['tfFilter']) ? $_GET['tfFilter'] : false;
$entryFilter = isset($_GET['entryFilter']) ? $_GET['entryFilter'] : 'y';
$strategyFilter = isset($_GET['strategyFilter']) ? $_GET['strategyFilter'] : false;

$mlFilter = isset($_GET['mlFilter']) ? $_GET['mlFilter'] : 'n';
//$mlMarketFilter = isset($_GET['mlMarketFilter']) ? $_GET['mlMarketFilter'] : '0.51';

$updateTargetsFilter = isset($_GET['updateTargetsFilter']) ? $_GET['updateTargetsFilter'] : 'n';
$updateMlFilter = isset($_GET['updateMlFilter']) ? $_GET['updateMlFilter'] : 'n';
$updateMarketMlFilter = isset($_GET['updateMarketMlFilter']) ? $_GET['updateMarketMlFilter'] : 'n';


// читаем из GET или ставим дефолт
$startDateStr = isset($_GET['start_date'])
    ? trim($_GET['start_date'])
    : $defaultStartStr;
$endDateStr = isset($_GET['end_date'])
    ? trim($_GET['end_date'])
    : $defaultEndStr;

$startDatePHP = new \DateTime($startDateStr);
$endDatePHP = new \DateTime($endDateStr);

// Рассчитываем разницу между датами

$startDate = Bitrix\Main\Type\DateTime::createFromPhp($startDatePHP);
$endDate = Bitrix\Main\Type\DateTime::createFromPhp($endDatePHP);

//$endDate->add('1 day'); // включаем конечный день в фильтр

// Массив для результатов
$finalResults = [];

// Если форма отправлена (при наличии хотя бы одного GET-параметра)
if (!empty($_GET)) {

    // Опции для фильтра (фильтруем по инфоблоку, разделу и дате создания)
    $arFilter = [
        "IBLOCK_ID" => $exchangeIblockID,
        "SECTION_ID" => $categorySectionID,
        ">=DATE_CREATE" => $startDate,
        "<=DATE_CREATE" => $endDate,
        "ACTIVE" => "Y",
    ];

    // Выборка полей - включая ID, DATE_CREATE и нужное свойство STRATEGIES_FILE
    $arSelect = [
        "ID",
        "NAME",
        "DATE_CREATE",
        "PROPERTY_STRATEGIES_FILE"
    ];

    // Используем старое API CIBlockElement для выборки
    $res = CIBlockElement::GetList(
        ["TIMESTAMP_X" => "ASC"],
        $arFilter,
        false,
        false,
        $arSelect
    );
    $lastSignalTimes = [];

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

    // Перебор результатов
    while ($arItem = $res->GetNext()) {

        // Флаг, что в этом проходе добавлялись свечи
        $candlesUpdated = false;

        // Приводим дату создания в миллисекунды (unix timestamp * 1000)
        $creationTs = MakeTimeStamp($arItem["DATE_CREATE"]);
        $startTime = $creationTs * 1000;
        $endTime = ($creationTs + 48 * 3600) * 1000;

        // Получаем значение свойства с JSON-файлом, содержащим пути к стратегиям
        $jsonFilePath = CFile::GetPath($arItem["PROPERTY_STRATEGIES_FILE_VALUE"]);

        // Формируем полный путь к файлу (если путь относительный, то дописываем DOCUMENT_ROOT)
        $fullPath = $_SERVER["DOCUMENT_ROOT"] . $jsonFilePath;

        // Если файл существует, читаем и декодируем JSON
        if (file_exists($fullPath)) {
            $jsonContent = file_get_contents($fullPath);

            $decoded = Json::decode($jsonContent);

            // Проверяем наличие ключа STRATEGIES в декодированном файле
            if (isset($decoded["STRATEGIES"])) {
                $strategies = $decoded["STRATEGIES"];

                // Определяем префикс в ключе стратегии в зависимости от выбранной категории
                // Если выбрана master (id=5) – ключи будут masterPump и masterDump,
                // если screener (id=7) – предполагаем, что ключи screenrPump и screenrDump
                if ($categorySectionID == 5) {
                    $keyPump = "masterPump";
                    $keyDump = "masterDump";
                } elseif (in_array($categorySectionID, [7, 8, 9, 10, 11])) {
                    $keyPump = "screenerPump";
                    $keyDump = "screenerDump";
                } elseif (in_array($categorySectionID, [6])) {
                    $keyPump = "allPump";
                    $keyDump = "allDump";
                } else {
                    $keyPump = "";
                    $keyDump = "";
                    $errors[] = 'не удалось найти ключ категории (' . $arItem['ID'] . '): ';
                }


                $market = $dataSource;
                $processedFilters = [
                    'market' => $market,
                    'dataSourceArr' => $dataSourceArr,
                    'tpCountGeneral' => $tpCountGeneral,
                    'deposit' => $deposit,
                    'shiftSL' => $shiftSL,
                    'tpFilterAr' => $tpFilterAr,
                    'updateTargetsFilter' => $updateTargetsFilter,
                    'updateMarketMlFilter' => $updateMarketMlFilter,
                ];
                // Функция для обработки стратегии – обрабатывает элементы массива стратегии
                $processStrategies = function ($strategyArray, $typeKey) use ($arItem, $startTime, $endTime, $processedFilters, $apiObAr,  &$errors,  &$decoded, &$candlesUpdated, &$finalResults, &$lastSignalTimes, &$portionWeight) {
                    // Значение направления: Pump -> long, Dump -> short
                    $direction = (stripos($typeKey, "Pump") !== false) ? "long" : "short";
                    $reverseDirection = (stripos($typeKey, "Pump") !== false) ? "short" : "long";
                    $arItem['marketMl'] = $decoded['INFO']["BTC_INFO"][$direction . 'Ml'];
                    $arItem['reverseMarketMl'] = $decoded['INFO']["BTC_INFO"][$reverseDirection . 'Ml'];
                    $marketImpulsInfo = $decoded['INFO']["BTC_INFO"]['marketImpulsInfo'];


                    // Перебираем каждый элемент массива стратегии
                    foreach ($strategyArray as $sname => $strategy) {
                        // Получаем символ
                        $symbolName = $sname ?? $strategy["symbolName"];

                        if ($processedFilters['market'] == 'bybit' && $strategy['marketCode'] == 'okx') {
                            $symbolName = $strategy["symbolName"];

                        } else if ($processedFilters['market'] == 'binance' && $strategy['marketCode'] == 'okx') {
                            $symbolName = $strategy["symbolName"];

                        } else if ($processedFilters['market'] == 'okx' && (in_array($strategy['marketCode'] , ['bybit', 'binance']))) {
                            $quote = 'USDT';
                            $base = substr($strategy["symbolName"], 0, -strlen($quote));
                            $symbolName =  $base . '-' . $quote . '-SWAP';
                        } elseif ($processedFilters['market'] === 'bingx') {
                            if (in_array($strategy['marketCode'], ['bybit','binance'])) {
                                $symbolName = str_replace('USDT', '-USDT', $strategy['symbolName']);
                                //$symbolName = str_replace(['-'], '', $strategy['symbolName']);
                            } elseif ($strategy['marketCode'] === 'okx') {
                                $symbolName = str_replace('USDT', '-USDT', $strategy['symbolName']);
                                //$symbolName = str_replace('USDT', '-USDT-SWAP', $strategy['symbolName']);
                            }
                        }
                        //echo '<pre>'; var_dump($strategy['marketCode'] . ' - ' . $symbolName); echo '</pre>';

                        if ($processedFilters['market'] == 'n') {
                            $processedFilters['market'] = $strategy['marketCode'];
                        } else if (
                                $processedFilters['dataSourceArr'][$processedFilters['market']]
                                && !in_array($strategy["symbolName"], $processedFilters['dataSourceArr'][$processedFilters['market']])) {
                            $errors[] = 'торговый контракт отсутствует в выбранном источнике данных | ' . $arItem['ID'] . ' | ' . $symbolName;
                            continue;
                        }
                        $candles = false;
                        /*$savedCandles = false;//$decoded["CANDLES_HIST"][$symbolName] ?? false;
                        if ($savedCandles && is_array($savedCandles) && count($savedCandles) > 200) {
                            $candles = $savedCandles;
                        }*/

                        // Пропуск сигнала, если для этого символа уже был сигнал менее 2 часов назад
                        if (!empty($symbolName) && isset($lastSignalTimes[$symbolName])) {
                            if (($startTime - $lastSignalTimes[$symbolName]) < (2 * 3600 * 1000)) {
                                $errors[] = 'повтор сигнала | iblock ' . $arItem['ID'] . ' | ' . $symbolName;
                                continue; // пропускаем этот элемент
                            }
                        }

                        // Обновляем время последнего сигнала для данного символа
                        if (!empty($symbolName)) {
                            $lastSignalTimes[$symbolName] = $startTime;
                        }

                        // если не расчитаны для сигнала тейки и стопы или же не стоит фильтр принудительного перерасчета
                        if (
                            (!$strategy['SL'] || !$strategy['TP'] || $processedFilters['updateTargetsFilter'] == 'y')
                            && $strategy['atrMultipliers']
                        ) {

                            if (!$processedFilters['tpFilterAr']['SELECTED_TP_STRATEGY']['VALUE'])
                                $thisAtrMultipliers = $strategy['atrMultipliers'];
                            else
                                $thisAtrMultipliers = $processedFilters['tpFilterAr']['SELECTED_TP_STRATEGY']['VALUE'];

                            $processed = \Maksv\Bybit\Exchange::processSignal(
                                $direction,
                                floatval($strategy['actualATR']['atr']),
                                floatval($strategy['actualClosePrice']),
                                $strategy['candles15m'] ?? [], //candles15
                                $strategy['actualSupertrend5m'],
                                [],//$strategy['actualSupertrend15m'],
                                $strategy['actualMacdDivergence'],
                                $strategy['symbolScale'] ?? 6,
                                $thisAtrMultipliers,
                                ['risk' => $marketImpulsInfo['risk']],//['risk' => 10],
                                $symbolName,
                                "stat",
                                false
                            );

                            if ($processed['SL'] && $processed['TP']) {
                                $strategy['SL'] = $processed['SL'];
                                $strategy['TP'] = $processed['TP'];
                            } else {
                                $errors[] = 'не удалось сделать перерасчет таргетов | iblock ' . $arItem['ID'] . ' | ' . $symbolName;
                            }
                        }

                        // Получаем остальные параметры стратегии
                        $actualClosePrice = isset($strategy["actualClosePrice"]) ? $strategy["actualClosePrice"] : false;
                        $sl = isset($strategy["SL"]) ? $strategy["SL"] : false;
                        $tp = isset($strategy["TP"]) ? $strategy["TP"] : false; // Ожидается, что $tp – массив цен тейк-профитов

                        if (!$sl || !$tp || !$actualClosePrice) {
                            $errors[] = 'при повторной проверке не были найдены таргеты | iblock ' . $arItem['ID'] . ' | ' . $symbolName;
                            continue;
                        }

                        //если указан фильтр по стратегии формирования TP, то делаем перерасчет
                        if ($processedFilters['tpFilterAr']['TP_FILTER'] && $processedFilters['tpFilterAr']['TP_FILTER'] != 0 && $processedFilters['tpFilterAr']['SELECTED_TP_STRATEGY']['VALUE']) { //$tpStrategyAr

                            $calculateRiskTargetsWithATR = \Maksv\TechnicalAnalysis::calculateRiskTargetsWithATR(
                                floatval($strategy['actualATR']['atr']),
                                floatval($actualClosePrice),
                                $sl, // не имеет значения в данном случае
                                $direction,
                                8,
                                1.1, // не имеет значения  в данном случае
                                $processedFilters['tpFilterAr']['SELECTED_TP_STRATEGY']['VALUE']
                            );
                            $tp = $calculateRiskTargetsWithATR['takeProfits'];
                        }

                        if ((!$processedFilters['tpCountGeneral'] || $processedFilters['tpCountGeneral'] == 0) && $strategy['tpCount']['longTpCount'] && $strategy['tpCount']['shortTpCount']) {
                            if ($direction == 'long') {
                                $processedFilters['tpCountGeneral'] = $strategy['tpCount']['longTpCount'];
                            } else {
                                $processedFilters['tpCountGeneral'] = $strategy['tpCount']['shortTpCount'];
                            }
                        } else if ((!$processedFilters['tpCountGeneral'] || $processedFilters['tpCountGeneral'] == 0) && !$strategy['tpCount']['longTpCount']) {
                            if ($direction == 'long') {
                                $processedFilters['tpCountGeneral'] = 2;
                            } else {
                                $processedFilters['tpCountGeneral'] = 1;
                            }
                        }

                        // если задан фильтр по количеству TP
                        if (isset($processedFilters['tpCountGeneral']))
                            $tp = array_slice($tp, 0, $processedFilters['tpCountGeneral']);

                        // Расчёт времени кеширования в зависимости от времени сигнала
                        $now = round(microtime(true) * 1000); // текущее время в миллисекундах
                        $ageMs = $now - $startTime;
                        $oneHourMs = 3600 * 1000;
                        $oneDayMs = 24 * $oneHourMs;

                        if ($ageMs < 2 * $oneHourMs) {
                            $cacheTtl = 5 * 60;
                        } elseif ($ageMs < 4 * $oneHourMs) {
                            $cacheTtl = 10 * 60;
                        } elseif ($ageMs < 6 * $oneHourMs) {
                            $cacheTtl = 30 * 60;
                        } elseif ($ageMs < $oneDayMs) {
                            $cacheTtl = 6 * 3600;
                        } elseif ($ageMs < 3 * $oneDayMs) {
                            $cacheTtl = 14 * 24 * 3600;
                        } else {
                            $cacheTtl = 90 * 24 * 3600;
                        }
                        // Анализ изменения
                        // цены (функция возвращает массив с tp_count и realized_percent_change)

                        $priceAnalysis = \Maksv\Bybit\Exchange::analyzeSymbolPriceChange(
                            $apiObAr,
                            $symbolName,
                            $startTime,
                            $endTime,
                            $direction,
                            $actualClosePrice,
                            $sl,
                            $tp,
                            $processedFilters['shiftSL'],
                            $cacheTtl,
                            $candles,
                            $processedFilters['market']
                        );

                        if ($priceAnalysis['status'] === false){
                            $errors[] = 'не удалось рассчитать доходность сигнала из за проблем с апи ' . $priceAnalysis['message'] . '  id ' . $arItem['ID'] . ' | ' . $symbolName;
                            //continue;
                        }

                        /*if (!$priceAnalysis['entry_touched'])
                            continue;*/

                        // Приводим значение realized_percent_change к float
                        $rpch = floatval(isset($priceAnalysis["realized_percent_change"]) ? $priceAnalysis["realized_percent_change"] : 0);

                        //сохраняем сечки в бд
                       /* $threeDaysMs = 3 * 24 * 3600 * 1000;
                        $hasOldCandles = !empty($decoded["CANDLES_HIST"][$symbolName]);
                        if (($now - $startTime) > $threeDaysMs && !$hasOldCandles) {
                            // сохраняем полученные свечи в память
                            $decoded["CANDLES_HIST"][$symbolName] = $priceAnalysis['candles'];
                            $candlesUpdated = true;
                        }*/

                        $startRisk = round(abs($actualClosePrice - $sl) / $actualClosePrice * 100, 2);
                        // Если поле updated_sl присутствует и отличается от исходного SL – использовать его для расчета риска
                        if (isset($priceAnalysis['updated_sl']) && $priceAnalysis['updated_sl'] !== false) {
                            $slForRiskCalc = $priceAnalysis['updated_sl'];
                        } else {
                            $slForRiskCalc = $sl;
                        }

                        $riskPercent = round(abs($actualClosePrice - $slForRiskCalc) / $actualClosePrice * 100, 2);

                        if ($processedFilters['tpCountGeneral'] == 1) {
                            $portionWeight = [1];
                        } else if ($processedFilters['tpCountGeneral'] == 2) {
                            $portionWeight = [0.65, 0.35];
                        } else if ($processedFilters['tpCountGeneral'] == 3) {
                            $portionWeight = [0.34, 0.33, 0.33];
                        } else if ($processedFilters['tpCountGeneral'] == 4) {
                            $portionWeight = [0.25, 0.25, 0.25, 0.25];
                        } else if ($processedFilters['tpCountGeneral'] == 5) {
                            $portionWeight = [0.20, 0.20, 0.20, 0.20, 0.20];
                        }

                        // potential Profit
                        //$profit_percent_potential = 0;
                        //$reachedTPPotential = $processedFilters['tpCountGeneral'];
                        //$portionWeightPotential = 1 / $processedFilters['tpCountGeneral'];
                        $profit_percent_potential = 0;
                        // Берем первые $processedFilters['tpCountGeneral'] тейков
                        $tpHitAr = array_slice($tp, 0, $processedFilters['tpCountGeneral']);
                        foreach ($tpHitAr as $key => $tpPrice) {
                            if ($direction == 'long') {
                                $profitForTpPotential = (($tpPrice - $actualClosePrice) / $actualClosePrice) * 100;
                            } else { // для short
                                $profitForTpPotential = (($actualClosePrice - $tpPrice) / $actualClosePrice) * 100;
                            }
                            //$profit_percent_potential += $profitForTpPotential * $portionWeightPotential;
                            $profit_percent_potential += $profitForTpPotential * $portionWeight[$key];
                        }
                        $profit_percent_potential = round($profit_percent_potential, 2);
                        //-! potential Profit

                        // Расчёт итоговой процентной прибыли (profit_percent) и прибыли в валютном выражении (profit)
                        $profit_percent = 0;

                        // Расчёт итоговой процентной прибыли (profit_percent) и прибыли по депозиту (profit)
                        if ($priceAnalysis["tp_count"] > 0) {
                            // Если достигнуто больше или равно запланированному количеству тейков, считаем "полностью успешную" сделку.
                            if ($priceAnalysis["tp_count"] >= $processedFilters['tpCountGeneral']) {
                                $reachedTP = $processedFilters['tpCountGeneral'];
                                //$portionWeight = 1 / $processedFilters['tpCountGeneral'];
                                $profit_percent = 0;
                                // Берем первые $processedFilters['tpCountGeneral'] тейков
                                $tpHitAr = array_slice($tp, 0, $processedFilters['tpCountGeneral']);
                                foreach ($tpHitAr as $key => $tpPrice) {
                                    if ($direction == 'long') {
                                        $profitForTp = (($tpPrice - $actualClosePrice) / $actualClosePrice) * 100;
                                    } else { // для short
                                        $profitForTp = (($actualClosePrice - $tpPrice) / $actualClosePrice) * 100;
                                    }
                                    //$profit_percent += $profitForTp * $portionWeight;
                                    $profit_percent += $profitForTp * $portionWeight[$key];
                                }
                                $profit_percent = round($profit_percent, 2);
                            } else {
                                // Если достигнуто меньше, чем запланировано (например, 2 из 3),
                                // то вычисляем взвешенную прибыль для достигнутых тейков...
                                $reachedTP = $priceAnalysis["tp_count"];
                                //$portionWeight = 1 / $processedFilters['tpCountGeneral'];
                                $profit_percent = 0;
                                $tpHitAr = array_slice($tp, 0, $reachedTP);
                                foreach ($tpHitAr as $key => $tpPrice) {
                                    if ($direction == 'long') {
                                        $profitForTp = (($tpPrice - $actualClosePrice) / $actualClosePrice) * 100;
                                    } else {
                                        $profitForTp = (($actualClosePrice - $tpPrice) / $actualClosePrice) * 100;
                                    }
                                    //$profit_percent += $profitForTp * $portionWeight;
                                    $profit_percent += $profitForTp * $portionWeight[$key];
                                }

                                // —————— Накладываем убыток по недостигнутой части только если стоп‑лосс был пробит:
                               /* $unreached = $processedFilters['tpCountGeneral'] - $reachedTP;
                                if ($priceAnalysis['sl_hit']) {
                                    $profit_percent += $unreached * (-$riskPercent * $portionWeight);
                                }*/
                                // Накладываем убыток по недостигнутым тейкам, если сработал SL
                                if ($priceAnalysis['sl_hit']) {
                                    // индекс первого «недостигнутого» тейка
                                    $startIdx = $reachedTP;
                                    $totalTps = $processedFilters['tpCountGeneral'];
                                    for ($i = $startIdx; $i < $totalTps; $i++) {
                                        // используем соответствующий вес для каждого недостигнутого тейка
                                        $w = isset($portionWeight[$i]) ? $portionWeight[$i] : (1 / $totalTps);
                                        $profit_percent += -$riskPercent * $w;
                                    }
                                }
                                // ——————————————————————————————————————————————————————————————

                                $profit_percent = round($profit_percent, 2);
                            }
                        } else {
                            // Если ни один тейк не достигнут, используем значение realized_percent_change
                            $profit_percent = $rpch;
                        }

                        // Вычисляем абсолютную прибыль по депозиту: profit = deposit * (profit_percent / 100)
                        $profit = round($processedFilters['deposit'] * ($profit_percent / 100), 2);

                        unset($strategy['maAr']);
                        unset($strategy['priceChange']);
                        unset($strategy['latestScreener']);
                        unset($strategy['actualMacdDivergence']['extremes']);


                        // Формируем результирующий элемент массива
                        $finalResults[] = [
                            "date" => $arItem["DATE_CREATE"],
                            "marketMl" => $arItem["marketMl"],
                            //"reverseMarketMl" => $arItem["reverseMarketMl"],
                            "direction" => $direction,
                            "strategy" => $strategy['strategy'],
                            "tf" => $strategy['interval'],
                            "symbolName" => $symbolName,
                            "tpCount" => $priceAnalysis["tp_count"],
                            "risk" => $riskPercent,
                            "startRisk" => $startRisk,
                            "realized_percent_change" => $rpch,
                            "profit_percent" => $profit_percent,
                            "profit" => $profit,
                            "entry_touched" => $priceAnalysis['entry_touched'],
                            'candlesUpdated' => $candlesUpdated,
                            'allInfo' => $strategy,
                            'marketImpulsInfo' => $marketImpulsInfo,
                            'priceAnalysis' => $priceAnalysis,
                            'profit_percent_potential' => $profit_percent_potential,
                            'decoded' => $decoded
                        ];
                    }
                };

                // Если в файле присутствуют стратегии Pump – обрабатываем
                if (!empty($keyPump) && isset($strategies[$keyPump]) && is_array($strategies[$keyPump])) {
                    $processStrategies($strategies[$keyPump], $keyPump);
                }

                // Если в файле присутствуют стратегии Dump – обрабатываем
                if (!empty($keyDump) && isset($strategies[$keyDump]) && is_array($strategies[$keyDump])) {
                    $processStrategies($strategies[$keyDump], $keyDump);
                }

                if ($candlesUpdated) {
                    $newJson = Json::encode(
                        $decoded,
                        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                    );
                    file_put_contents($fullPath, $newJson);
                }
            } else {
                $errors[] = 'не удалось найти сигналы (' . $arItem['ID'] . '): ';
            }
        } else {
            $errors[] = 'не удалось физически найти файл (' . $arItem['ID'] . '): ' . $fullPath;
        }
    }

    //ml /
    global $USER;

    $predictRes = \Maksv\MachineLearning\Assistant::predictResBatch($finalResults, $marketMap[$exchangeIblockID], $bybitApiOb, $binanceApiOb, $okxApiOb) ?? [];
    $predictMarketRes = \Maksv\MachineLearning\Assistant::predictResBatch($finalResults, $marketMap[$exchangeIblockID], $bybitApiOb, $binanceApiOb, $okxApiOb, true) ?? [];

    $bybitApiOb->closeConnection();
    $binanceApiOb->closeConnection();
    $okxApiOb->closeConnection();
    $bingxApiOb->closeConnection();
} else {
    $errors[] = 'нет гет параметров';
}
?>

<div class="stat-wrapper">
    <!-- Форма с фильтрами -->
    <form method="GET" id="statsFilterForm" class="filter-form">
        <div class="filter-main">
            <div class="form-group">
                <label for="category">Beta Forever:</label>
                <select name="category" id="category">
                    <?/* foreach ($marketMap as $marketKey => $marketVal): ?>
                        <option value="<?= $marketKey ?>" <?= ($exchangeIblockID == $marketKey ? "selected" : "") ?>><?= $marketVal ?> <?=$categoriesMap[$marketKey][$categorySectionID]?></option>
                    <? endforeach; */?>

                    <? foreach ($categoriesMap[$exchangeIblockID] as $categoriesMapKey => $categoriesMapVal): ?>
                        <option value="<?= $categoriesMapKey ?>" <?= ($categorySectionID == $categoriesMapKey ? "selected" : "") ?>><?= $categoriesMapVal ?> </option>
                    <? endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="dataSource">Источник данных:</label>
                <select name="dataSource" id="dataSource">
                    <option value="n" <?= ($dataSource == 'n' ? "selected" : "") ?>>Все сигналы</option>
                    <? foreach ($dataSourceArr as $dataSourceVal => $dataSourceSymbols): ?>
                        <option value="<?= $dataSourceVal ?>" <?= ($dataSource == $dataSourceVal ? "selected" : "") ?>><?= $dataSourceVal ?></option>
                    <? endforeach; ?>
                </select>
            </div>
        </div>

        <div class="filter-date">
            <div class="form-group">
                <label for="start_date">Дата начала:</label>
                <input type="datetime-local" name="start_date" id="start_date" value="<?= ($startDateStr) ?>">
            </div>
            <div class="form-group">
                <label for="end_date">Дата окончания:</label>
                <input type="datetime-local" name="end_date" id="end_date" value="<?= ($endDateStr) ?>">
            </div>
        </div>

        <div class="filter-trader">
            <div class="form-group">
                <label for="tpCountGeneral">Количество TP:</label>
                <select name="tpCountGeneral" id="tpCountGeneral">
                    <? $tpCountGeneralAr = [1, 2, 3, 4, 5]; ?>
                    <option value="0" <?= ($tpCountGeneral === false ? "selected" : "") ?>>default</option>
                    <? foreach ($tpCountGeneralAr as $tpCountGen): ?>
                        <option value="<?= $tpCountGen ?>" <?= ($tpCountGeneral == $tpCountGen ? "selected" : "") ?>><?= $tpCountGen ?></option>
                    <? endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="deposit">Сумма в сделке $:</label>
                <input type="number" name="deposit" id="deposit" value="<?= ($deposit) ?>">
            </div>
            <div class="form-group">
                <label for="leverege">Плечо:</label>
                <select name="leverege" id="leverege">
                    <? $leveregeValAr = [1, 5, 10, 15, 20]; ?>
                    <? foreach ($leveregeValAr as $lev): ?>
                        <option value="<?= $lev ?>" <?= ($leverege == $lev ? "selected" : "") ?>><?= $lev ?></option>
                    <? endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="shiftSL">Сдвиг SL в безубыток после достижения TP:</label>
                <select name="shiftSL" id="shiftSL">
                    <option value="" <?= ($shiftSL == false ? "selected" : "") ?>>Не двигать</option>
                    <option value="1" <?= ($shiftSL == 1 ? "selected" : "") ?>>1</option>
                    <option value="2" <?= ($shiftSL == 2 ? "selected" : "") ?>>2</option>
                </select>
            </div>
        </div>

        <div class="filter-footer">
            <div class="form-group">
                <label for="riskFilter">Фильтр по риску, %:</label>
                <select name="riskFilter" id="riskFilter">
                    <? $riskFilterAr = range(2, 10, 0.5); ?>
                    <? foreach ($riskFilterAr as $riskFilterVal): ?>
                        <option value="<?= $riskFilterVal ?>" <?= ($riskFilter == $riskFilterVal ? "selected" : "") ?>><?= $riskFilterVal ?></option>
                    <? endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tpFilter">Множители ATR</label>
                <select name="tpFilter" id="tpFilter">
                    <? foreach ($tpStrategyAr as $tpKey => $tpStrategyVal): ?>
                        <option value="<?= $tpKey ?>" <?= ($tpFilter == $tpKey ? "selected" : "") ?>><?= $tpStrategyVal['NAME'] ?></option>
                    <? endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="riskFilter">long/short:</label>
                <select name="directionFilter" id="directionFilter">
                    <? $directionFilterAr = ['long', 'short']; ?>
                    <option value="" <?= ($directionFilter ? "selected" : "") ?>>Все</option>
                    <? foreach ($directionFilterAr as $directionFilterVal): ?>
                        <option value="<?= $directionFilterVal ?>" <?= ($directionFilter == $directionFilterVal ? "selected" : "") ?>><?= $directionFilterVal ?></option>
                    <? endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="entryFilter">Вход по рынку:</label>
                <select name="entryFilter" id="entryFilter">
                    <option value="n" <?= ($entryFilter == 'n' ? "selected" : "") ?>>Нет</option>
                    <option value="y" <?= ($entryFilter == 'y' ? "selected" : "") ?>>Да</option>
                </select>
            </div>
            <? $mlStep = 0.001; ?>

            <div class="form-group">
                <label for="mlFilter">ml фильтр:</label>
                <select name="mlFilter" id="mlFilter">
                    <option value="n" <?= ($mlFilter == 'n' ? "selected" : "") ?>>нет</option>
                    <? $mlFilterAr = range(0.7, 0.85, 0.01); ?>
                    <? foreach ($mlFilterAr as $mlFilterArVal): ?>
                        <?$mlFilterArVal = round($mlFilterArVal, 2);?>
                        <option value="<?= $mlFilterArVal ?>" <?= ($mlFilter == $mlFilterArVal ? "selected" : "") ?>><?= $mlFilterArVal ?></option>
                    <? endforeach; ?>
                </select>
            </div>

        </div>

        <div class="button-footer">
            <div class="form-group">
                <button type="submit">Фильтровать</button>
            </div>
        </div>
    </form>
    <br>

    <!-- Таблица с результатами -->
    <? if (!empty($finalResults)): ?>
        <div class="table-container">
            <table border="1" cellspacing="0" cellpadding="5">
                <thead>
                <tr>
                    <th>Дата</th>
                    <th>Направление</th>
                    <th>Инфо о сделке</th>
                    <th>Количество TP</th>
                    <th>Риск %</th>
                    <th>RR соотношение</th>
                    <th>Профит %</th>
                    <th>Профит $</th>
                </tr>
                </thead>
                <tbody>
                <? $cntSignals = 0; ?>
                <? $cntOpenSignals = 0; ?>
                <? $cntClosedSignals = 0; ?>
                <? $cntSignalsProfit = 0; ?>
                <? $cntSignalsRisk = 0; ?>
                <? $rpchSum = 0; ?>
                <? $profitPercentSum = 0; ?>
                <? $normalizedRrProfitSum = 0; ?>
                <? $profitSum = 0; ?>


                <? foreach ($finalResults as $result): ?>

                    <? if ($result["startRisk"] >= $riskFilter) continue; ?>
                    <? if ($directionFilter && $result["direction"] != $directionFilter) continue; ?>
                    <? if ($tfFilter && $result["tf"] != $tfFilter) continue; ?>
                    <? if ($strategyFilter && $result["strategy"] != $strategyFilter) continue; ?>

                    <? if ($entryFilter == 'n' && !$result["entry_touched"]) continue; ?>

                    <? if (
                        $result['allInfo']['actualAdx1h']
                        &&
                        (
                            $result['allInfo']['actualAdx1h']['adx'] < 20
                            || (
                                $result['allInfo']['actualAdx1h']['adx'] < 26
                                && $result['allInfo']['actualAdx1h']['adxDirection']['isDownDir'] === true
                            )
                        )
                    ) continue; ?>

                    <? if (
                        $result['allInfo']['actualAdx']
                        && ($result['allInfo']['actualAdx']['adx'] < 20)
                        || (
                            $result['allInfo']['actualAdx']['adx'] < 26
                            && $result['allInfo']['actualAdx']['adxDirection']['isDownDir'] === true
                        )
                    ) continue; ?>

                    <?
                    //ml signal
                    //if (!$predict && $result['allInfo']['actualMlModel'])

                    $predict['prediction'] = $result['allInfo']['actualMlModel'];
                    if ($updateMlFilter == 'y') {
                        $dt = \DateTime::createFromFormat(
                            'd.m.Y H:i:s',
                            $result["date"],
                            new \DateTimeZone('Europe/Amsterdam')
                        );
                        $signalTimestamp = $dt->getTimestamp();
                        $predict = $predictRes[$result["symbolName"] . '_' . $signalTimestamp] ?? false;

                    }

                    //ml signal filter
                    if ($predict['prediction']['probabilities'][1] && $predict['prediction']['probabilities'][0]) {
                        $mlRelative = $predict['prediction']['probabilities'][1] / $predict['prediction']['probabilities'][0];
                    }
                    ?>

                    <?
                    // ml market
                    $mlMarketRelative = 0;

                    $predictMarket['prediction'] = $result['marketMl'];
                    if ($updateMarketMlFilter == 'y') {
                        $dt = \DateTime::createFromFormat(
                            'd.m.Y H:i:s',
                            $result["date"],
                            new \DateTimeZone('Europe/Amsterdam')
                        );
                        $signalTimestamp = $dt->getTimestamp();
                        $predictMarket = $predictMarketRes[$result["symbolName"] . '_' . $signalTimestamp] ?? false;

                    }

                    if ($predictMarket['prediction']['probabilities'][0] && $predictMarket['prediction']['probabilities'][1]) {
                        $mlMarketRelative = $predictMarket['prediction']['probabilities'][1] / $predictMarket['prediction']['probabilities'][0];

                    }
                    ?>

                    <?
                    $marketMl = $predictMarket['prediction']['probabilities'][1] ?? false;
                    $signalMl = $predict['prediction']['probabilities'][1] ?? false;
                    $totalMl = (($predict['prediction']['probabilities'][1] + $predictMarket['prediction']['probabilities'][1]) / 2) ?? false;
                    if (
                        $mlFilter != 'n'
                        && (
                            $marketMl < 0.65
                            || $signalMl < 0.65
                            || ($totalMl < floatval($mlFilter))
                        )
                    ) continue;
                    ?>

                    <?
                    // Рассчитываем коэффициент для прибыли относительно риска 1
                    $normalizedRrProfit = round($result['profit_percent_potential'] / $result['startRisk'], 2);
                    $normalizedRrProfitSum += $normalizedRrProfit;
                    $rrRatioString = "1 / " . $normalizedRrProfit; // Результат: "1 / 2.14"

                    //if ($normalizedRrProfit <= 0.1) continue
                    ?>

                    <? $cntSignals += 1; ?>
                    <? if ($result["profit"] > 0) {
                        $cntSignalsProfit++;
                        $cntClosedSignals++;
                    } elseif ($result["profit"] < 0) {
                        $cntSignalsRisk++;
                        $cntClosedSignals++;
                    } else {
                        $cntOpenSignals++;
                    } ?>

                    <? //$rpchSum += floatval($result["realized_percent_change"]); ?>
                    <? $profitPercentSum += floatval($result["profit_percent"]); ?>
                    <? $profitSum += floatval($result["profit"]); ?>

                    <tr class="<? if ($result["profit"] < 0): ?>red-bg<? elseif ($result["profit"] > 0): ?>green-bg<? endif ?>">
                        <td><?= ($result["date"]) ?></td>
                        <td>
                            <?= ($result["direction"]) ?>
                        </td>
                        <td>
                            <?= ($result["symbolName"]) ?>
                            <br><?=strtoupper($result['allInfo']['marketCode']);?>

                            <? if ($totalMl && $signalMl && $marketMl): ?>
                                <br>
                                ML: <?=$totalMl?> (<?=$signalMl?>/<?=$marketMl?>)
                            <? endif; ?>

                        </td>
                        <td><?= ($result["tpCount"]) ?></td>
                        <td <? if ($result["startRisk"] >= 3): ?>class="solid-border-red-td"<? endif ?>><?= ($result["startRisk"] * $leverege) ?></td>
                        <td>
                            <?= ($rrRatioString) ?>
                        </td>
                        <td><?= ($result["profit_percent"] * $leverege) ?></td>
                        <td><?= ($result["profit"] * $leverege) ?></td>
                    </tr>
                <? endforeach; ?>
                </tbody>
                <tfoot>
                <tr>
                    <td class="solid-border-top-td">
                        всего <?=$cntSignals?><br>
                        открытые <?=$cntOpenSignals?><br>
                        закрытые <?= $cntClosedSignals ?> (<?= $cntSignalsProfit ?>\<?= $cntSignalsRisk ?>)<br>
                    </td>
                    <td class="solid-border-top-td">
                        TP вес (<?
                        $formattedWeights = [];
                        foreach ($portionWeight as $weight) {
                            $formattedWeights[] = $weight * 100 . '%';
                        }
                        echo implode(' | ', $formattedWeights);
                        ?>)<br>
                        <?
                        if ($cntClosedSignals !== 0)
                            $winRate = $cntSignalsProfit / ($cntClosedSignals / 100) ?? 0;
                        else
                            $winRate = 0;

                        ?>
                        винрейт <?= round($winRate, 2) ?>%<br>
                        профит <?=round($profitSum * $leverege, 2)?> $
                    </td>
                    <td class="solid-border-top-td"></td>
                    <td class="solid-border-top-td"></td>
                    <td class="solid-border-top-td"></td>
                    <td class="solid-border-top-td">
                        <?
                        if ($cntSignals !== 0)
                            $sumRatioRR = round($normalizedRrProfitSum / $cntSignals, 2);
                        else
                            $sumRatioRR = 0;
                        ?>
                        1 / <?=$sumRatioRR?>
                    </td>
                    <td class="solid-border-top-td"><?= $profitPercentSum * $leverege ?> %</td>
                    <td class="solid-border-top-td">
                        <?= round($profitSum * $leverege, 2)?> $ <br>
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    <? else: ?>
        <p>Требуется настройка фильтра</p>
    <? endif; ?>

    <? if (!empty($errors)): ?>
        <div class="table-container" style="margin-top: 20px;">
            <table border="1" cellspacing="0" cellpadding="5" class="error-table">
                <thead>
                <tr>
                    <th style="display: flex;">Ошибки</th>
                </tr>
                </thead>
                <tbody>
                <? foreach ($errors as $keyErr => $err): ?>
                    <tr>
                        <td class="error-bg"><?=$keyErr+1?>. <?= $err ?></td>
                    </tr>
                <? endforeach; ?>
                </tbody>
            </table>
        </div>
    <? endif; ?>
</div>
<?/*<div class="ai-analyze-wrapper">
        <button class="btnAiAnalyzeBtn" id="btnAiAnalyze">Анализ ИИ</button>
        <button class="btnAiAnalyzeBtn" id="btnAiAnalyzeLosses" style="margin-left:10px;">Анализ убыточных</button>
        <div id="aiAnalyzeResult"></div>
    </div>/*/?>
<script>
    $(document).ready(function () {
        var finalResults = {
            res: <?=CUtil::PhpToJSObject($finalResults, false, false, true)?>,
            selectedTpStrategy: <?=CUtil::PhpToJSObject($selectedTpStrategy, false, false, true)?>,

            errors: <?=CUtil::PhpToJSObject($errors, false, false, true)?>,
        }
        console.log('finalResults', finalResults);

        function sendAIAnalysis(filterFunc, promptIntro) {
            var trades = finalResults.res.filter(filterFunc);
            if (!trades.length) {
                alert('Нет сделок для анализа');
                return;
            }
            var payload = {
                filters: {
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    riskFilter: $('#riskFilter').val(),
                    tpCountGeneral: $('#tpCountGeneral').val(),
                    tpFilter: finalResults.selectedTpStrategy,
                    direction: $('#directionFilter').val(),
                    amountInTrade: $('#deposit').val(),
                    tf: $('#tfFilter').val(),
                    entry: $('#entryFilter').val(),
                    strategy: $('#strategyFilter').val() || null,
                    moveeSLafterReachingTP: $('#shiftSL').val() || 'не сдвигать SL',
                },
                trades: trades,
                aiModel: 'deepseek',//'gpt',
                promptIntro: promptIntro,
            };

            $('#aiAnalyzeResult').html('<em>Идёт запрос к ИИ…</em>');
            $.ajax({
                url: '/ajax/aiStatAnalyze.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function (resp) {
                    console.log('trades:', trades);
                    console.log('Prompt:', resp.messages[1].content);

                    if (resp.error) {
                        $('#aiAnalyzeResult').html('<span style="color:red;">Ошибка: ' + resp.error + '</span>');
                    } else {
                        $('#aiAnalyzeResult').html(
                            '<textarea readonly style="width:100%;height:300px;padding:10px;border:1px solid #ccc;border-radius:4px;">'
                            + "\n" + resp.analysis
                            + '</textarea>'
                        );
                    }
                },
                error: function () {
                    $('#aiAnalyzeResult').html('<span style="color:red;">Серверная ошибка</span>');
                }
            });
        }

        $('#btnAiAnalyze').on('click', function () {
            sendAIAnalysis(
                function (trade) {
                    return trade.profit !== 0;
                },
                'Анализ всех сделок:'
            );
        });

        $('#btnAiAnalyzeLosses').on('click', function () {
            sendAIAnalysis(
                function (trade) {
                    /*console.log('trade.profit', trade.profit < 0)*/
                    return trade.profit < 0;
                },
                'Анализ убыточных сделок на основе технического анализа allInfo:'
            );
        });

        // Если потребуется ajax-подгрузка формы, можно сделать так:
        $("#statsFilterForm").on("submit", function (e) {
            window.siteShowPrelouder();
          
        });

        $('#exchange').change(function () {
            $("#statsFilterForm").submit();
        });

    });
</script>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
