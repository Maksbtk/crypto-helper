<?
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Page\Asset;

define('NEED_AUTH', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Статистика");
$APPLICATION->SetTitle("Статистика");

global $USER;

$defaultStartDate = new \DateTime('now');
$defaultStartDate->modify('-2 days')->setTime(0, 0, 0);

$defaultEndDate   = new \DateTime('now');
$defaultEndDate  ->setTime(23, 59, 0);

// нужный формат для datetime-local
$defaultStartStr = $defaultStartDate->format('Y-m-d\TH:i');
$defaultEndStr   = $defaultEndDate  ->format('Y-m-d\TH:i');

// Получаем параметры из GET-запроса (если имеются)
$exchangeIblockID = isset($_GET['exchange']) ? intval($_GET['exchange']) : 3;  // вариант пока только bybit (id инфоблока - 3)
$marketMap = [
    3 => 'bybit',
    7 => 'binance',
];

$categorySectionID = isset($_GET['category']) ? intval($_GET['category']) : 7;  // по умолчанию  (id=5)
$tpCountGeneral = isset($_GET['tpCountGeneral']) ? intval($_GET['tpCountGeneral']) : 0;  // сколько всего тейк профитов у сделки  (false)
$deposit = isset($_GET['deposit']) ? intval($_GET['deposit']) : 100;  //сумма на которая используется в сделке  ()
$shiftSL = isset($_GET['shiftSL']) ? intval($_GET['shiftSL']) : false;  // передвигание стопа после достижения какого тейк профита  ()

if ($tpCountGeneral > 0 && $tpCountGeneral <= $shiftSL)
    $shiftSL = false;

$riskFilter = isset($_GET['riskFilter']) ? round(floatval($_GET['riskFilter']), 1) : 7;
$leverege = isset($_GET['leverege']) ? intval($_GET['leverege']) : 1;  // плечо котое используется вв сделке  ()

$defaultTpSrearAr = [1.9, 2.6, 3.4, 5.6, 6.9];
$tpStrategyAr = [
    0 => ['NAME' => 'default' , 'VALUE' => false],
    1 => ['NAME' => '1.1, 2.2, 3.6, 6, 8.4' , 'VALUE' => [1.1, 2.2, 3.6, 6, 8.4]],
    3 => ['NAME' => '1.4, 2.6, 3.4, 5.6, 6.9' , 'VALUE' => [1.4, 2.6, 3.4, 5.6, 6.9]],
    4 => ['NAME' => '1.8, 2.6, 3.4, 5.6, 6.9' , 'VALUE' => [1.8, 2.6, 3.4, 5.6, 6.9]],
    5 => ['NAME' => '1.9, 2.6, 3.4, 5.6, 6.9' , 'VALUE' => [1.9, 2.6, 3.4, 5.6, 6.9]],
    6 => ['NAME' => '1.9, 2.7, 3.4, 5.6, 6.9' , 'VALUE' => [1.9, 2.7, 3.4, 5.6, 6.9]],
    7 => ['NAME' => '1.9, 2.9, 3.9, 5.9, 6.9' , 'VALUE' => [1.9, 2.9, 3.9, 5.9, 6.9]],
    8 => ['NAME' => '2.3, 2.9, 3.3, 5.6, 6.9' , 'VALUE' => [2.3, 2.9, 3.3, 5.6, 6.9]],
    9 => ['NAME' => '2.2, 3.4, 4.9, 5.6, 6.9' , 'VALUE' => [2.2, 3.4, 4.9, 5.6, 6.9]],
    10 => ['NAME' => '2.5, 3.4, 5.5, 7.4, 9.9' , 'VALUE' => [2.5, 3.4, 5.5, 7.4, 9.9]],
    11 => ['NAME' => '3.5, 4.3, 5.2, 6.3, 7.2' , 'VALUE' => [3.5, 4.3, 5.2, 6.3, 7.2]],
    12 => ['NAME' => '2.6, 3.0, 3.5, 4.3, 5.2' , 'VALUE' => [2.6, 3.0, 3.5, 4.3, 5.2]],
    13 => ['NAME' => '2.6, 3.4, 4.3, 7.4, 9.9' , 'VALUE' => [2.6, 3.4, 4.3, 7.4, 9.9]],
    14 => ['NAME' => '2.6, 3.5, 4.3, 7.4, 9.9' , 'VALUE' => [2.6, 3.5, 4.3, 7.4, 9.9]],
    15 => ['NAME' => '2.6, 3.6, 4.3, 7.4, 9.9' , 'VALUE' => [2.6, 3.6, 4.3, 7.4, 9.9]],
    16 => ['NAME' => '2.6, 3.7, 4.3, 7.4, 9.9' , 'VALUE' => [2.6, 3.7, 4.3, 7.4, 9.9]],
    17 => ['NAME' => '2.6, 3.7, 4.3, 7.4, 9.9' , 'VALUE' => [2.6, 3.7, 4.3, 7.4, 9.9]],
    18 => ['NAME' => '3.4, 4.3, 5.3, 6.4, 7.3' , 'VALUE' => [3.4, 4.3, 5.3, 6.4, 7.3]],
];

$tpFilter = isset($_GET['tpFilter']) ? intval($_GET['tpFilter']) : 0;
$selectedTpStrategy = $tpStrategyAr[$tpFilter];
$tpFilterAr = ['SELECTED_TP_STRATEGY' => $selectedTpStrategy, 'TP_FILTER' => $tpFilter];
if (!$selectedTpStrategy['VALUE'])
    $selectedTpStrategy['VALUE'] = $defaultTpSrearAr;

//echo '<pre>'; var_dump($tpFilterAr['TP_FILTER']); echo '</pre>';

$directionFilter = isset($_GET['directionFilter']) ? $_GET['directionFilter'] : false;
$tfFilter = isset($_GET['tfFilter']) ? $_GET['tfFilter'] : false;
$entryFilter = isset($_GET['entryFilter']) ? $_GET['entryFilter'] : 'y';
$strategyFilter = isset($_GET['strategyFilter']) ? $_GET['strategyFilter'] : false;

// читаем из GET или ставим дефолт
$startDateStr = isset($_GET['start_date'])
    ? trim($_GET['start_date'])
    : $defaultStartStr;
$endDateStr   = isset($_GET['end_date'])
    ? trim($_GET['end_date'])
    : $defaultEndStr;

$startDatePHP = new \DateTime($startDateStr);
$endDatePHP   = new \DateTime($endDateStr);

// Рассчитываем разницу между датами
/*$interval = $startDatePHP->diff($endDatePHP);
if ($interval->days > 14) {
    echo('<div style="margin: 20px; 20px; color: red;">Ошибка: диапазон дат не должен превышать 14 дней.<a href="/stat/"> Назад</a></div>');
    die;
}*/

$startDate = Bitrix\Main\Type\DateTime::createFromPhp($startDatePHP);
$endDate   = Bitrix\Main\Type\DateTime::createFromPhp($endDatePHP);

//$endDate->add('1 day'); // включаем конечный день в фильтр

// Массив для результатов
$finalResults = [];

// Если форма отправлена (при наличии хотя бы одного GET-параметра)
if(!empty($_GET)) {

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
        ["DATE_CREATE" => "ASC"],
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
    // Перебор результатов
    while($arItem = $res->GetNext()) {

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
        if(file_exists($fullPath)){
            $jsonContent = file_get_contents($fullPath);

            $decoded = Json::decode($jsonContent);

            // Проверяем наличие ключа STRATEGIES в декодированном файле
            if(isset($decoded["STRATEGIES"])){
                $strategies = $decoded["STRATEGIES"];

                // Определяем префикс в ключе стратегии в зависимости от выбранной категории
                // Если выбрана master (id=5) – ключи будут masterPump и masterDump,
                // если screener (id=7) – предполагаем, что ключи screenrPump и screenrDump
                if($categorySectionID == 5){
                    $keyPump = "masterPump";
                    $keyDump = "masterDump";
                } elseif(in_array($categorySectionID, [7, 8])){
                    $keyPump = "screenerPump";
                    $keyDump = "screenerDump";
                } else {
                    $keyPump = "";
                    $keyDump = "";
                }

                $market = $marketMap[$exchangeIblockID];
                // Функция для обработки стратегии – обрабатывает элементы массива стратегии
                $processStrategies = function($strategyArray, $typeKey) use ($arItem, $startTime, $endTime, $market, $bybitApiOb, $binanceApiOb, $tpCountGeneral, $deposit, $shiftSL, $tpFilterAr,  &$decoded, &$candlesUpdated, &$finalResults, &$lastSignalTimes) {
                    // Значение направления: Pump -> long, Dump -> short
                    $direction = (stripos($typeKey, "Pump") !== false) ? "long" : "short";

                    // Перебираем каждый элемент массива стратегии
                    foreach($strategyArray as $strategy) {

                        // Получаем символ
                        $symbolName = isset($strategy["symbolName"]) ? $strategy["symbolName"] : "";

                        $candles = false;
                        $savedCandles = $decoded["CANDLES_HIST"][$symbolName] ?? false;
                        if ($savedCandles && is_array($savedCandles) && count($savedCandles) > 100) {
                            $candles = $savedCandles;
                        }

                        // Пропуск сигнала, если для этого символа уже был сигнал менее 2 часов назад
                        if (!empty($symbolName) && isset($lastSignalTimes[$symbolName])) {
                            if (($startTime - $lastSignalTimes[$symbolName]) < (2 * 3600 * 1000)) {
                                continue; // пропускаем этот элемент
                            }
                        }

                        // Обновляем время последнего сигнала для данного символа
                        if (!empty($symbolName)) {
                            $lastSignalTimes[$symbolName] = $startTime;
                        }

                        // Получаем остальные параметры стратегии
                        $actualClosePrice = isset($strategy["actualClosePrice"]) ? $strategy["actualClosePrice"] : false;
                        $sl = isset($strategy["SL"]) ? $strategy["SL"] : false;
                        $tp = isset($strategy["TP"]) ? $strategy["TP"] : false; // Ожидается, что $tp – массив цен тейк-профитов

                        if (!$sl || !$tp || !$actualClosePrice)
                            continue;

                        //если указан фильтр по стратегии формирования TP, то делаем перерасчет
                        if ($tpFilterAr['TP_FILTER'] && $tpFilterAr['TP_FILTER'] != 0 && $tpFilterAr['SELECTED_TP_STRATEGY']['VALUE']) { //$tpStrategyAr

                            $calculateRiskTargetsWithATR = \Maksv\TechnicalAnalysis::calculateRiskTargetsWithATR(
                                floatval($strategy['actualATR']['atr']),
                                floatval($actualClosePrice),
                                $sl,
                                $direction,
                                8,
                                1.1,
                                $tpFilterAr['SELECTED_TP_STRATEGY']['VALUE']
                            );
                            $tp = $calculateRiskTargetsWithATR['takeProfits'];
                        }

                        if ((!$tpCountGeneral || $tpCountGeneral == 0) && $strategy['tpCount']['longTpCount'] && $strategy['tpCount']['shortTpCount']) {
                            if ($direction == 'long') {
                                $tpCountGeneral = $strategy['tpCount']['longTpCount'];
                            } else {
                                $tpCountGeneral = $strategy['tpCount']['shortTpCount'];
                            }
                        } else if ((!$tpCountGeneral || $tpCountGeneral == 0) && !$strategy['tpCount']['longTpCount']) {
                            if ($direction == 'long') {
                                $tpCountGeneral = 2;
                            } else {
                                $tpCountGeneral = 1;
                            }
                        }

                        // если задан фильтр по количеству TP
                        if (isset($tpCountGeneral))
                            $tp = array_slice($tp, 0,  $tpCountGeneral);

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
                        /*$bybitApiOb,
                           $binanceApiOb,
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
                           $market = 'bybit'*/
                        $priceAnalysis = \Maksv\Bybit\Exchange::analyzeSymbolPriceChange(
                            $bybitApiOb,
                            $binanceApiOb,
                            $symbolName,
                            $startTime,
                            $endTime,
                            $direction,
                            $actualClosePrice,
                            $sl,
                            $tp,
                            $shiftSL,
                            $cacheTtl,
                            $candles,
                            $market
                        );

                        /*if (!$priceAnalysis['entry_touched'])
                            continue;*/

                        // Приводим значение realized_percent_change к float
                        $rpch = floatval(isset($priceAnalysis["realized_percent_change"]) ? $priceAnalysis["realized_percent_change"] : 0);

                        //сохраняем сечки в бд
                        $threeDaysMs = 3 * 24 * 3600 * 1000;
                        $hasOldCandles = !empty($decoded["CANDLES_HIST"][$symbolName]);
                        if (($now - $startTime) > $threeDaysMs && !$hasOldCandles) {
                            // сохраняем полученные свечи в память
                            $decoded["CANDLES_HIST"][$symbolName] = $priceAnalysis['candles'];
                            $candlesUpdated = true;
                        }

                        $startRisk = round(abs($actualClosePrice - $sl) / $actualClosePrice * 100, 2);
                        // Если поле updated_sl присутствует и отличается от исходного SL – использовать его для расчета риска
                        if (isset($priceAnalysis['updated_sl']) && $priceAnalysis['updated_sl'] !== false) {
                            $slForRiskCalc = $priceAnalysis['updated_sl'];
                        } else {
                            $slForRiskCalc = $sl;
                        }

                        $riskPercent = round(abs($actualClosePrice - $slForRiskCalc) / $actualClosePrice * 100, 2);
                        // Расчёт итоговой процентной прибыли (profit_percent) и прибыли в валютном выражении (profit)
                        $profit_percent = 0;

                        // Расчёт итоговой процентной прибыли (profit_percent) и прибыли по депозиту (profit)
                        if ($priceAnalysis["tp_count"] > 0) {
                            // Если достигнуто больше или равно запланированному количеству тейков, считаем "полностью успешную" сделку.
                            if ($priceAnalysis["tp_count"] >= $tpCountGeneral) {
                                $reachedTP = $tpCountGeneral;
                                $portionWeight = 1 / $tpCountGeneral;
                                $profit_percent = 0;
                                // Берем первые $tpCountGeneral тейков
                                $tpHitAr = array_slice($tp, 0, $tpCountGeneral);
                                foreach ($tpHitAr as $tpPrice) {
                                    if ($direction == 'long') {
                                        $profitForTp = (($tpPrice - $actualClosePrice) / $actualClosePrice) * 100;
                                    } else { // для short
                                        $profitForTp = (($actualClosePrice - $tpPrice) / $actualClosePrice) * 100;
                                    }
                                    $profit_percent += $profitForTp * $portionWeight;
                                }
                                $profit_percent = round($profit_percent, 2);
                            } else {
                                // Если достигнуто меньше, чем запланировано (например, 2 из 3),
                                // то вычисляем взвешенную прибыль для достигнутых тейков...
                                $reachedTP    = $priceAnalysis["tp_count"];
                                $portionWeight = 1 / $tpCountGeneral;
                                $profit_percent = 0;
                                $tpHitAr = array_slice($tp, 0, $reachedTP);
                                foreach ($tpHitAr as $tpPrice) {
                                    if ($direction == 'long') {
                                        $profitForTp = (($tpPrice - $actualClosePrice) / $actualClosePrice) * 100;
                                    } else {
                                        $profitForTp = (($actualClosePrice - $tpPrice) / $actualClosePrice) * 100;
                                    }
                                    $profit_percent += $profitForTp * $portionWeight;
                                }

                                // —————— Накладываем убыток по недостигнутой части только если стоп‑лосс был пробит:
                                $unreached = $tpCountGeneral - $reachedTP;
                                if ($priceAnalysis['sl_hit']) {
                                    $profit_percent += $unreached * (-$riskPercent * $portionWeight);
                                }
                                // ——————————————————————————————————————————————————————————————

                                $profit_percent = round($profit_percent, 2);
                            }
                        } else {
                            // Если ни один тейк не достигнут, используем значение realized_percent_change
                            $profit_percent = $rpch;
                        }

                        // Вычисляем абсолютную прибыль по депозиту: profit = deposit * (profit_percent / 100)
                        $profit = round($deposit * ($profit_percent / 100), 2);

                        unset($strategy['maAr']);
                        unset($strategy['priceChange']);
                        unset($strategy['latestScreener']);
                        unset($strategy['actualMacdDivergence']['extremes']);

                        // Формируем результирующий элемент массива
                        $finalResults[] = [
                            "date" => $arItem["DATE_CREATE"],
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
                            'priceAnalysis' => $priceAnalysis
                        ];
                    }
                };

                // Если в файле присутствуют стратегии Pump – обрабатываем
                if(!empty($keyPump) && isset($strategies[$keyPump]) && is_array($strategies[$keyPump])){
                    $processStrategies($strategies[$keyPump], $keyPump);
                }

                // Если в файле присутствуют стратегии Dump – обрабатываем
                if(!empty($keyDump) && isset($strategies[$keyDump]) && is_array($strategies[$keyDump])){
                    $processStrategies($strategies[$keyDump], $keyDump);
                }

                if ($candlesUpdated) {
                    $newJson = Json::encode(
                        $decoded,
                        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                    );
                    file_put_contents($fullPath, $newJson);
                }
            }
        }
    }
    $bybitApiOb->closeConnection();
    $binanceApiOb->closeConnection();
}

?>

<!-- HTML-разметка страницы -->
<style>
    /* Ваши существующие стили */
    .red-bg {
        background-color: rgba(255, 3, 3, 0.46);
    }
    .green-bg {
        background-color: rgb(0 102 51 / 66%);
    }
    .solid-border-top-td {
        border-top: 3px solid #000;
    }
    .solid-border-red-td {
        border: 3px solid #E22B2B;
    }
    .solid-border-green-td {
        border: 3px solid #3BC915;
    }
    .stat-wrapper {
        margin: 0px 10px 10px 10px;
        font-family: Arial, sans-serif;
    }

    /* Стили для таблицы (оставляем без изменений) */
    .table-container {
        max-height: 400px;
        overflow-y: auto;
        overflow-x: auto;
    }
    table thead th {
        position: sticky;
        top: 0;
        background-color: var(--bg-color-main, #007BFF);
        color: #fff;
        z-index: 2;
        padding: 10px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }

    /* Новый стиль для закреплённого футера */
    table tfoot tr {
        position: sticky;
        bottom: 0;
        z-index: 2;
        background-color: var(--bg-color-main, #007BFF);
        color: #fff;
    }
    table tfoot td {
        border-top: 3px solid #000;
        padding: 5px;
    }

    /* Стили для формы с фильтрами (без изменений, как вы задали) */
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        padding: 15px;
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .filter-form > div {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 10px;
    }
    .filter-main .form-group,
    .filter-trader .form-group,
    .filter-date .form-group,
    .button-footer .form-group {
        flex: 1 1 200px;
        display: flex;
        flex-direction: column;
    }
    .filter-form label {
        margin-bottom: 5px;
        font-weight: bold;
        font-size: 14px;
    }
    .filter-form input,
    .filter-form select {
        padding: 8px 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    .filter-form input:focus,
    .filter-form select:focus {
        outline: none;
        border-color: var(--bg-color-main, #007BFF);
    }
    .filter-form button {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        background-color: var(--bg-color-main, #007BFF);
        color: #fff;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .filter-form button:hover {
        background-color: #006ae6;
    }

    /* Новые стили для блока мини-фильтров (filter-footer) и кнопки (button-footer) */
    .filter-footer {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 10px;
    }

    .filter-footer .form-group {
        display: flex;
        flex-direction: column;
    }
    .filter-footer select,
    .filter-footer input {
        padding: 6px 8px;
        font-size: 13px;
    }
    .button-footer {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: flex-end;
        align-items: center;
    }
    label.info {
        margin-top: 5px;
        margin-bottom: 0px;
        font-weight: 100;
    }

    /* Адаптивные стили для мобильных устройств */
    @media (max-width: 767px) {
        .filter-form {
            padding: 10px;
            gap: 10px;
        }
        .filter-form > div {
            flex-direction: row;
            gap: 10px;
        }
        .filter-form .form-group {
            flex: 1 1 100%;
        }
        /* При узком экране выравниваем мини-фильтры и кнопку по центру */
        .filter-footer, .button-footer {
            justify-content: center;
        }
        .filter-footer {
            grid-template-columns: 1fr;
        }
    }

    /* Стили для блока ИИ-анализа — отступы совпадают с .stat-wrapper */
    .ai-analyze-wrapper {
        margin: 0px 10px 20px 10px;
    }
    #aiAnalyzeResult textarea {
        box-sizing: border-box; /* чтобы width:100% не выходил за границы */
    }

    .btnAiAnalyzeBtn {
        padding:8px 16px; border:none; border-radius:4px;
        background:var(--bg-color-main,#007BFF); color:#fff;
        cursor:pointer;
    }

    /* На мобильных экранах */
    @media (max-width: 767px) {
        .ai-analyze-wrapper {
            margin: 0px 10px 20px 10px;
        }
        #aiAnalyzeResult textarea {
            font-size: 14px;
        }
    }
</style>

<div class="stat-wrapper">
    <!-- Форма с фильтрами -->
    <form method="GET" id="statsFilterForm" class="filter-form">
        <div class="filter-main">
            <div class="form-group">
                <label for="exchange">Биржа:</label>
                <select name="exchange" id="exchange">
                    <?foreach ($marketMap as $marketKey => $marketVal):?>
                        <option value="<?=$marketKey?>" <?=($exchangeIblockID == $marketKey ? "selected" : "")?>><?=$marketVal?></option>
                    <?endforeach;?>
                </select>
            </div>
            <div class="form-group">
                <label for="category">Категория:</label>
                <select name="category" id="category">
                    <?
                    $categoriesMap = [
                        3 => [
                            7 => 'screener',
                            5 => 'master',
                        ],
                        7 => [
                            8 => 'screener',
                        ],
                    ];
                    ?>
                    <? foreach ($categoriesMap[intval($exchangeIblockID)] as $mapKey => $mapVal):?>
                        <option value="<?=$mapKey?>" <?=($categorySectionID == $mapKey ? "selected" : "")?>><?=$mapVal?></option>
                    <?endforeach;?>
                    <?/*<option value="5" <?=($categorySectionID == 5 ? "selected" : "")?>>master</option>
                    <option value="7" <?=($categorySectionID == 7 ? "selected" : "")?>>screener bybit</option>
                    <option value="8" <?=($categorySectionID == 8 ? "selected" : "")?>>screener binance</option>*/?>
                </select>
            </div>
        </div>

        <div class="filter-date">
            <div class="form-group">
                <label for="start_date">Дата начала:</label>
                <input type="datetime-local" name="start_date" id="start_date" value="<?=($startDateStr)?>">
            </div>
            <div class="form-group">
                <label for="end_date">Дата окончания:</label>
                <input type="datetime-local" name="end_date" id="end_date" value="<?=($endDateStr)?>">
                <?/*<label class="info" for="start_date">Диапазон не более 7 дней</label>*/?>
            </div>
        </div>

        <div class="filter-trader">
            <div class="form-group">
                <label for="tpCountGeneral">Количество TP:</label>
                <select name="tpCountGeneral" id="tpCountGeneral">
                    <?$tpCountGeneralAr = [1, 2, 3, 4, 5]; ?>
                    <option value="0" <?=($tpCountGeneral === false ? "selected" : "")?>>default</option>
                    <?foreach ($tpCountGeneralAr as $tpCountGen):?>
                        <option value="<?=$tpCountGen?>" <?=($tpCountGeneral == $tpCountGen ? "selected" : "")?>><?=$tpCountGen?></option>
                    <?endforeach;?>
                </select>
            </div>
            <div class="form-group">
                <label for="deposit">Сумма в сделке $:</label>
                <input type="number" name="deposit" id="deposit" value="<?=($deposit)?>">
            </div>
            <div class="form-group">
                <label for="leverege">Плечо:</label>
                <select name="leverege" id="leverege">
                    <?$leveregeValAr = [1, 5, 10, 15, 20]; ?>
                    <?foreach ($leveregeValAr as $lev):?>
                        <option value="<?=$lev?>" <?=($leverege == $lev ? "selected" : "")?>><?=$lev?></option>
                    <?endforeach;?>
                </select>
            </div>
            <div class="form-group">
                <label for="shiftSL">Сдвиг SL в безубыток после достижения TP:</label>
                <select name="shiftSL" id="shiftSL">
                    <option value="" <?=($shiftSL == false ? "selected" : "")?>>Не двигать</option>
                    <option value="1" <?=($shiftSL == 1 ? "selected" : "")?>>1</option>
                    <option value="2" <?=($shiftSL == 2 ? "selected" : "")?>>2</option>
                </select>
            </div>
        </div>

        <div class="filter-footer">
            <div class="form-group">
                <label for="riskFilter">Фильтр по риску, %:</label>
                <select name="riskFilter" id="riskFilter">
                    <?$riskFilterAr = range(2, 10, 0.5); ?>
                    <?foreach ($riskFilterAr as $riskFilterVal):?>
                        <option value="<?=$riskFilterVal?>" <?=($riskFilter == $riskFilterVal ? "selected" : "")?>><?=$riskFilterVal?></option>
                    <?endforeach;?>
                </select>
            </div>
            <div class="form-group">
                <label for="tpFilter">Множители ATR</label>
                <select name="tpFilter" id="tpFilter">
                    <?//echo '<pre>'; var_dump($tpFilter); echo '</pre>'; ?>
                    <?//echo '<pre>'; var_dump($tpKey); echo '</pre>'; ?>
                    <?foreach ($tpStrategyAr as $tpKey => $tpStrategyVal):?>
                        <option value="<?=$tpKey?>" <?=($tpFilter == $tpKey ? "selected" : "")?>><?=$tpStrategyVal['NAME']?></option>
                    <?endforeach;?>
                </select>
            </div>
            <div class="form-group">
                <label for="riskFilter">long/short:</label>
                <select name="directionFilter" id="directionFilter">
                    <?$directionFilterAr = ['long', 'short'];?>
                    <option value="" <?=($directionFilter ? "selected" : "")?>>Все</option>
                    <?foreach ($directionFilterAr as $directionFilterVal):?>
                        <option value="<?=$directionFilterVal?>" <?=($directionFilter == $directionFilterVal ? "selected" : "")?>><?=$directionFilterVal?></option>
                    <?endforeach;?>
                </select>
            </div>
            <div class="form-group">
                <label for="tfFilter">timeframe:</label>
                <select name="tfFilter" id="tfFilter">
                    <?$tfFilterAr = ['5m', '15m', '30m'];?>
                    <option value="" <?=($tfFilter ? "selected" : "")?>>Все</option>
                    <?foreach ($tfFilterAr as $tfFilterArVal):?>
                        <option value="<?=$tfFilterArVal?>" <?=($tfFilter == $tfFilterArVal ? "selected" : "")?>><?=$tfFilterArVal?></option>
                    <?endforeach;?>
                </select>
            </div>
            <div class="form-group">
                <label for="entryFilter">Вход по рынку:</label>
                <select name="entryFilter" id="entryFilter">
                    <option value="n" <?=($entryFilter == 'n' ? "selected" : "")?>>Нет</option>
                    <option value="y" <?=($entryFilter == 'y' ? "selected" : "")?>>Да</option>
                </select>
            </div>
            <?
            $strategyFilterAr = [];
            foreach ($finalResults as $result) {
                if ($result['strategy'])
                    $strategyFilterAr[] = $result['strategy'];

            }
            $strategyFilterAr = array_unique($strategyFilterAr);
            ?>
            <?if ($strategyFilterAr):?>
                <div class="form-group">
                    <label for="strategyFilter">Стратегия:</label>
                    <select name="strategyFilter" id="strategyFilter">
                        <option value="" <?=($strategyFilter ? "selected" : "")?>>Все</option>
                        <?foreach ($strategyFilterAr as $strategyFilterVal):?>
                            <option value="<?=$strategyFilterVal?>" <?=($strategyFilter == $strategyFilterVal ? "selected" : "")?>><?=$strategyFilterVal?></option>
                        <?endforeach;?>
                    </select>
                </div>
            <?else:?>
                <?$strategyFilter = false;?>
            <?endif;?>

        </div>

        <div class="button-footer">
            <div class="form-group">
                <button type="submit">Фильтровать</button>
            </div>
        </div>
    </form>
    <br>

    <!-- Таблица с результатами -->
    <? if(!empty($finalResults)): ?>
        <div class="table-container">
            <table border="1" cellspacing="0" cellpadding="5">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>long/short</th>
                    <th>Symbol Name</th>
                    <th>TP count</th>
                    <th>risk %</th>
                    <th>realized %</th>
                    <th>profit %</th>
                    <th>profit $</th>
                </tr>
                </thead>
                <tbody>
                <?$cntSignals = 0;?>
                <?$cntSignalsProfit = 0;?>
                <?$cntSignalsRisk = 0;?>
                <?$rpchSum = 0;?>
                <?$profitPercentSum = 0;?>
                <?$profitSum = 0;?>
                <? foreach($finalResults as $result): ?>

                    <? if ($result["startRisk"] >= $riskFilter) continue; ?>
                    <? if ($directionFilter && $result["direction"] != $directionFilter) continue; ?>
                    <? if ($tfFilter && $result["tf"] != $tfFilter) continue; ?>
                    <? if ($strategyFilter && $result["strategy"] != $strategyFilter) continue; ?>

                    <? if ($entryFilter == 'n' && !$result["entry_touched"]) continue; ?>

                    <?/* if (
                        !$result["allInfo"]['actualImpulsMacd']
                        || (
                            ($result["direction"] == 'short' && $result["allInfo"]['actualImpulsMacd']['impulse_macd'] != 0)
                            || ($result["direction"] == 'long' && $result["allInfo"]['actualImpulsMacd']['impulse_macd'] != 0)
                        )
                    ) continue; */?>

                    <? //if ($result["tf"] == '30m' && $result["direction"] == 'short' ) continue; ?>

                    <?$cntSignals += 1;?>
                    <? if ($result["profit"] > 0) { $cntSignalsProfit++; } elseif ($result["profit"] < 0) { $cntSignalsRisk++; } ?>

                    <?$rpchSum += floatval($result["realized_percent_change"]); ?>
                    <?$profitPercentSum += floatval($result["profit_percent"]); ?>
                    <?$profitSum += floatval($result["profit"]); ?>

                    <tr class="<? if ($result["profit"] < 0): ?>red-bg<? elseif ($result["profit"] > 0): ?>green-bg<? endif ?>">
                        <td><?=($result["date"])?></td>
                        <td><?=($result["direction"])?> <?=($result['tf'])?></td>
                        <td>
                            <?=($result["symbolName"])?>
                            <? if($result['strategy']): ?>
                                <br><?=($result['strategy'])?>
                            <? endif; ?>
                        </td>
                        <td><?=($result["tpCount"])?></td>
                        <td <? if ($result["startRisk"] >= 3): ?>class="solid-border-red-td"<? endif ?>><?=($result["startRisk"] * $leverege)?></td>
                        <td><?=($result["realized_percent_change"] * $leverege)?></td>
                        <td><?=($result["profit_percent"] * $leverege)?></td>
                        <td><?=($result["profit"] * $leverege)?></td>
                    </tr>
                <? endforeach; ?>
                </tbody>
                <tfoot>
                <tr>
                    <td class="solid-border-top-td">count <?=$cntSignals?> (<?=$cntSignalsProfit?>\<?=$cntSignalsRisk?>)</td>
                    <td class="solid-border-top-td">leverege <?=$leverege?></td>
                    <td class="solid-border-top-td"></td>
                    <td class="solid-border-top-td"></td>
                    <td class="solid-border-top-td"></td>
                    <td class="solid-border-top-td"><?=$rpchSum * $leverege?> %</td>
                    <td class="solid-border-top-td"><?=$profitPercentSum * $leverege?> %</td>
                    <td class="solid-border-top-td"><?=$profitSum * $leverege?> $</td>
                </tr>
                </tfoot>
            </table>
        </div>
    <? else: ?>
        <p>Требуется настройка фильтра</p>
    <? endif; ?>
</div>

<?if ($USER->IsAdmin()):?>
    <div class="ai-analyze-wrapper">
        <button class="btnAiAnalyzeBtn" id="btnAiAnalyze">Анализ ИИ</button>
        <button class="btnAiAnalyzeBtn" id="btnAiAnalyzeLosses" style="margin-left:10px;">Анализ убыточных</button>
        <div id="aiAnalyzeResult"></div>
    </div>
<?endif;?>
<script>
    $(document).ready(function(){
        var finalResults = {
            res: <?=CUtil::PhpToJSObject($finalResults, false, false, true)?>,
            selectedTpStrategy: <?=CUtil::PhpToJSObject($selectedTpStrategy, false, false, true)?>,
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
                success: function(resp) {
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
                error: function() {
                    $('#aiAnalyzeResult').html('<span style="color:red;">Серверная ошибка</span>');
                }
            });
        }

        $('#btnAiAnalyze').on('click', function(){
            sendAIAnalysis(
                function(trade) {
                    return trade.profit !== 0;
                },
                'Анализ всех сделок:'
            );
        });

        $('#btnAiAnalyzeLosses').on('click', function(){
            sendAIAnalysis(
                function(trade) {
                    /*console.log('trade.profit', trade.profit < 0)*/
                    return trade.profit < 0;
                },
                'Анализ убыточных сделок на основе технического анализа allInfo:'
            );
        });

        // Если потребуется ajax-подгрузка формы, можно сделать так:
        $("#statsFilterForm").on("submit", function(e){
            window.siteShowPrelouder();
            // Например, отменяем стандартную отправку формы и делаем ajax-запрос
            // e.preventDefault();
            // var formData = $(this).serialize();
            // $.get(window.location.pathname, formData, function(data){
            //     // Обновляем блок со статистикой (здесь нужно реализовать парсинг и вставку данных)
            // });
        });

        $('#exchange').change(function() {
            $("#statsFilterForm").submit();
        });

        // Форматируем Date → "YYYY-MM-DDThh:mm"
        /*function formatDateTime(dt) {
            const Y = dt.getFullYear();
            const M = String(dt.getMonth()+1).padStart(2,'0');
            const D = String(dt.getDate()    ).padStart(2,'0');
            const h = String(dt.getHours()   ).padStart(2,'0');
            const m = String(dt.getMinutes() ).padStart(2,'0');
            return `${Y}-${M}-${D}T${h}:${m}`;
        }

        // Парсим строку из datetime-local в локальный Date
        function parseLocalDateTime(str) {
            // ожидаем "YYYY-MM-DDThh:mm"
            var [date, time] = str.split('T');
            var [Y, M, D] = date.split('-').map(Number);
            var [h, m]   = time.split(':').map(Number);
            return new Date(Y, M-1, D, h, m);
        }


        function validateDateRange() {
            console.log('→ validateDateRange fired');
            var startStr = $('#start_date').val();
            var endStr   = $('#end_date'  ).val();
            console.log('   start:', startStr, 'end:', endStr);

            if (!startStr || !endStr) return;

            var startDate = parseLocalDateTime(startStr);
            var endDate   = parseLocalDateTime(endStr);
            var diffDays  = (endDate - startDate) / (1000 * 3600 * 24);

            console.log('   diffDays =', diffDays);
            if (diffDays > 7) {
                //alert('Диапазон не более n дней.');
                var newEnd = new Date(startDate);
                newEnd.setDate(newEnd.getDate() + 14);
                $('#end_date').val(formatDateTime(newEnd));
            }
        }

        var timer;
        $('#start_date, #end_date')
            // при потере фокуса
            .off('blur', validateDateRange)
            .on ('blur', validateDateRange)
            // при вводе — ждём 500 мс «паузы»
            .off('input')
            .on ('input', function(){
                clearTimeout(timer);
                timer = setTimeout(validateDateRange, 500);
            });*/

        /*$('#start_date, #end_date')
            .off('change', validateDateRange)
            .on('change', validateDateRange);*/

        // Проверяем диапазон при изменении значений в обоих инпутах
        //$('#start_date, #end_date').on('change', validateDateRange());
    });
</script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
