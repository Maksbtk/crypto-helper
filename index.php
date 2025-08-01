<?
use Bitrix\Main\Page\Asset;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Инструмент для торговли на бирже - Crypto helper");
$APPLICATION->SetTitle("Инструмент для торговли на бирже - Crypto helper");

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/adaptiveTables.css?v1", true);
Asset::getInstance()->addJs("https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js?v=3", true);
Asset::getInstance()->addJs("https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js?v=3", true);
Asset::getInstance()->addJs("https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.1/dist/chartjs-adapter-moment.min.js?v=3", true);
?>

<?php

$APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "main_slider",
    array(
        "ACTIVE_DATE_FORMAT" => "d.m.Y",
        "ADD_SECTIONS_CHAIN" => "Y",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_ADDITIONAL" => "",
        "AJAX_OPTION_HISTORY" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "CACHE_FILTER" => "N",
        "CACHE_GROUPS" => "Y",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "A",
        "CHECK_DATES" => "Y",
        "DETAIL_URL" => "",
        "DISPLAY_BOTTOM_PAGER" => "Y",
        "DISPLAY_DATE" => "Y",
        "DISPLAY_NAME" => "Y",
        "DISPLAY_PICTURE" => "Y",
        "DISPLAY_PREVIEW_TEXT" => "Y",
        "DISPLAY_TOP_PAGER" => "N",
        "FIELD_CODE" => array(
            0 => "DETAIL_PICTURE",
            1 => "",
        ),
        "FILTER_NAME" => "",
        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
        "IBLOCK_ID" => "1",
        "IBLOCK_TYPE" => "content",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
        "INCLUDE_SUBSECTIONS" => "Y",
        "MESSAGE_404" => "",
        "NEWS_COUNT" => "20",
        "PAGER_BASE_LINK_ENABLE" => "N",
        "PAGER_DESC_NUMBERING" => "N",
        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
        "PAGER_SHOW_ALL" => "N",
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_TEMPLATE" => ".default",
        "PAGER_TITLE" => "Новости",
        "PARENT_SECTION" => "",
        "PARENT_SECTION_CODE" => "",
        "PREVIEW_TRUNCATE_LEN" => "",
        "PROPERTY_CODE" => array(
            0 => "LINK",
            1 => "SUBTITLE",
            2 => "BUTTON_TEXT",
            3 => "",
        ),
        "SET_BROWSER_TITLE" => "N",
        "SET_LAST_MODIFIED" => "N",
        "SET_META_DESCRIPTION" => "N",
        "SET_META_KEYWORDS" => " N",
        "SET_STATUS_404" => "N",
        "SET_TITLE" => "N",
        "SHOW_404" => "N",
        "SORT_BY1" => "ACTIVE_FROM",
        "SORT_BY2" => "SORT",
        "SORT_ORDER1" => "DESC",
        "SORT_ORDER2" => "ASC",
        "STRICT_SECTION_CHECK" => "N",
        "COMPONENT_TEMPLATE" => ""
    ),
    false
);?>

<?php
// 1) Сбор всех квартальных файлов, фильтр за 3 мес — без изменений
$statDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/stat';
$allTrades = [];
if (is_dir($statDir)) {
    foreach (glob("$statDir/trades_*.json") as $file) {
        $arr = json_decode(file_get_contents($file), true);
        if (is_array($arr)) {
            $allTrades = array_merge($allTrades, $arr);
        }
    }
}
$threeMonthsAgoMs = (time() - 90*24*3600) * 1000;
$recent = array_filter($allTrades, fn($t)=> isset($t['timestamp']) && $t['timestamp'] >= $threeMonthsAgoMs);

// 2) Сгруппировать по дню (чтобы расчёт кумулятивки был стабильным по календарным дням)
$daily = [];
foreach ($recent as $t) {
    $day = gmdate('Y-m-d', intval($t['timestamp']/1000));
    if (!isset($daily[$day])) $daily[$day] = 0;
    $daily[$day] += $t['profitPercent'];
}

// 3) Преобразовать в кумулятивную кривую
$cumulative = 0;
$equityPercentPoints = [];
// сортируем ключи дней
$days = array_keys($daily);
sort($days);
foreach ($days as $day) {
    $cumulative += $daily[$day];
    // делаем метку «начало дня»
    $dt = DateTime::createFromFormat('Y-m-d', $day, new DateTimeZone('UTC'));
    $label = $dt->format('d.m.Y') . ' 00:00:00';
    $equityPercentPoints[] = [
        't' => $label,
        'y' => round($cumulative, 2),
    ];
}

// 4) Передаём в JS
?>

    <style>
        .chart-wrapper {
            padding: 0 35px;
            margin-bottom: 120px;
        }
        @media only screen and (max-width: 1023px) {
            .chart-wrapper {
                padding: 0 10px;
                margin-bottom: 40px;
            }
        }
    </style>
    <div class="chart-wrapper">
        <h2>Кривая баланса (%)</h2>
        <div id="sidebar">
            <label for="tfSelect">Интервал агрегации</label>
            <select id="tfSelect">
                <option value="day">1 день</option>
                <option value="3day" selected>3 дня</option>
                <option value="week">1 неделя</option>
                <option value="month">1 месяц</option>
            </select>
        </div><br>
        <canvas id="mainChart" style=" height: 350px;width: 100%;"></canvas>
    </div>
    <script>
        // rawData из PHP: уже агрегировано по дням
        const rawEquityPct = <?= json_encode($equityPercentPoints, JSON_UNESCAPED_SLASHES) ?>;

        // Инициализация Chart.js
        const ctx = document.getElementById('mainChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Equity %',
                    data: aggregate(rawEquityPct, '3day'),
                    parsing: { xAxisKey: 't', yAxisKey: 'y' },
                    borderColor: 'rgba(0, 102, 51, 0.7)',
                    backgroundColor: 'rgba(0, 102, 51, 0.1)',
                    fill: true,
                    tension: 0.2,
                    pointRadius: 6,
                    borderWidth: 2,
                    clip: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            parser: 'DD.MM.YYYY HH:mm:ss',
                            unit: 'day',
                            displayFormats: { day: 'DD.MM' }
                        },
                        grid: { color: '#eee' }
                    },
                    y: {
                        title: { display: true, text: 'Профит (%)' },
                        grid: { color: '#eee' }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: { label: ctx => ctx.parsed.y.toFixed(2) + ' %' }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Агрегация по выбранному интервалу (1 день, 3 дня, неделя, месяц)
        function aggregate(data, tf) {
            const grouped = {};
            data.forEach(pt => {
                let m = moment(pt.t, 'DD.MM.YYYY HH:mm:ss');
                switch (tf) {
                    case '3day':
                        m.startOf('day');
                        const days = Math.floor(m.diff(moment(0), 'days'));
                        m = moment(0).add(Math.floor(days/3)*3, 'days');
                        break;
                    case 'week':  m.startOf('week');   break;
                    case 'month': m.startOf('month');  break;
                    case 'day':   m.startOf('day');    break;
                }
                grouped[m.valueOf()] = {
                    t: m.format('DD.MM.YYYY HH:mm:ss'),
                    y: pt.y  // кумулятивка здесь не меняется, просто рисуем сплайн по точкам
                };
            });
            return Object.values(grouped)
                .sort((a,b)=> moment(a.t,'DD.MM.YYYY HH:mm:ss') - moment(b.t,'DD.MM.YYYY HH:mm:ss'));
        }

        // Селектор интервалов
        document.getElementById('tfSelect').addEventListener('change', e => {
            const tf = e.target.value;
            chart.data.datasets[0].data = aggregate(rawEquityPct, tf);
            chart.update();
        });
    </script>

    <link rel="stylesheet" href="/local/templates/maksv/css/mainpage-product-banners.css">
    <section class="mainpage-product-banners">
        <ul class="product-banners-list">
            <li class="product-banner-item">
                <div class="product-banner-item__inner">
                    <img class="product-banner-img" src="/local/templates/maksv/demo-pics/ban1.jpg?v=5" alt="CH">
                    <div class="product-banner-content">
                        <div class="h2 product-banner-name">Тестовый доступ</div>
                        <a href="/info/" class="product-banner-link">Узнать</a>
                    </div>
                </div>
            </li>
            <li class="product-banner-item">
                <div class="product-banner-item__inner">
                    <img class="product-banner-img" src="/local/templates/maksv/demo-pics/ban2.jpg?v=5" alt="CH">
                    <div class="product-banner-content">
                        <div class="h2 product-banner-name">statistics beta</div>
                        <a href="/stat/" class="product-banner-link">Перейти</a>
                    </div>
                </div>
            </li>
            <li class="product-banner-item">
                <div class="product-banner-item__inner">
                    <img class="product-banner-img" src="/local/templates/maksv/demo-pics/ban3.jpg?v=5" alt="CH">
                    <div class="product-banner-content">
                        <div class="h2 product-banner-name">statistics ml</div>
                        <a href="/stat/ml.php" class="product-banner-link">Перейти</a>
                    </div>
                </div>
            </li>
        </ul>
    </section>

    <?
    $cmcExchenge = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/CoinMarketCupExchange/btcd/res.json'), true)) ?? [];
    $cmcExchangeRes = $cmcExchenge['RESPONSE_EXCHENGE'] ?? [];
    $cmcExchengeTimemark = $cmcExchsnge['TIMEMARK'] ?? [];
    ?>

    <?if ($cmcExchangeRes):?>
        <section style="margin: 0px 15px;display: flex;flex-direction: column;justify-content: center;align-items: center;">
            <div class="h1">BTC DOMINATION / OTHERS</div>
            <br>
            <div class="mobile-table">
                <table class="iksweb js-open_interests_table">
                    <thead>
                    <tr>
                        <th>period</th>
                        <th><?=$cmcExchengeTimemark?></th>
                        <th>BTC D</th>
                        <th>BTC </th>
                        <th>OTHERS</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?foreach ($cmcExchangeRes as $th => $resItem):?>
                        <tr>
                            <td><?=$th?></td>
                            <td><?=$resItem['timemark']?></td>
                            <td><?=$resItem['btcD']?></td>
                            <td><?=$resItem['btc']?></td>
                            <td><?=$resItem['others']?></td>
                        </tr>
                    <?endforeach;?>
                    </tbody>
                </table>
            </div>
        </section>
    <?endif;?>

    <br><br>
    <h1 class="main-title">Инструмент для торговли на бирже - Crypto helper</h1>
<?global $USER;?>
<?if ($USER->IsAdmin()):?>
    <div style="display: flex;justify-content: center;">
        <?=nl2br(\Maksv\Bybit\Exchange::checkBtcImpulsInfo()['infoText'])?>
    </div>
    <br><br>
    <div style="display: flex;justify-content: center;">
        <?=nl2br(\Maksv\Bybit\Exchange::checkMarketImpulsInfo()['infoText'])?>
    </div>

<?php

//$dataFileSeparateVolume = $_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/summaryVolumeExchange.json';
    $dataFileSeparateVolume = $_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/summaryVolumeExchange.json';
    $existingDataSparateVolume = file_exists($dataFileSeparateVolume) ? json_decode(file_get_contents($dataFileSeparateVolume), true)['RESPONSE_EXCHENGE'] ?? [] : [];
    $volumesData = $existingDataSparateVolume ?? [];
    $volumes = [];
    foreach ($volumesData as $symbol => $volume)
        $volumes[$symbol] = $volume['resBinance'];
    //$volumes[$symbol] = $volume['resBybit'];


    $startTime = date("H:i:s");
    $signals = ['long' => [], 'short' => []];
    $volumesBTC = [];
    foreach ($volumes as $symbol => $volume) {
        $volume = array_reverse($volume);

        if ($symbol == 'BTCUSDT') {
            $volumesBTC = $volume;
        }

        $analyzeVolumeSignalRes = \Maksv\TechnicalAnalysis::analyzeVolumeSignal($volume, 3, 0.49, 0.55);
        //$analyzeVolumeSignalRes = \Maksv\TechnicalAnalysis::analyzeVolumeSignal($volume, 3, 0.49, 0.55);
        $analyzeVolumeSignalRes['symbol'] = $symbol;

        if ($analyzeVolumeSignalRes['isLong'])
            $signals['long'][$symbol] = $analyzeVolumeSignalRes;
        else if ($analyzeVolumeSignalRes['isShort'])
            $signals['short'][$symbol] = $analyzeVolumeSignalRes;
        else if (!$analyzeVolumeSignalRes['isLong'] && !$analyzeVolumeSignalRes['isShort'])
            $signals['neutral'][$symbol] = $analyzeVolumeSignalRes;

    }
    uasort($signals['long'], function ($a, $b) {
        return $b['growth'] <=> $a['growth'];
    });
    uasort($signals['short'], function ($a, $b) {
        return $b['growth'] <=> $a['growth'];
    });
    $endTime = date("H:i:s");

    /*$coinmarketcapOb = new \Maksv\Coinmarketcap\Request();
    $others15m = $coinmarketcapOb->getTotalExTop10_5m(200);*/


    $bybitApiOb = new \Maksv\Bybit\Bybit();
    $bybitApiOb->openConnection();
    $binanceApiOb = new \Maksv\Binance\BinanceFutures();
    $binanceApiOb->openConnection();
    $okxApiOb = new \Maksv\Okx\OkxFutures();
    $okxApiOb->openConnection();
    $bingxApiOb = new \Maksv\Bingx\BingxFutures();
    $bingxApiOb->openConnection();

    $cmc = new \Maksv\Coinmarketcap\Request();

    //получаем контракты, которые будем анализировать
    $exchangeBybitSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/bybitExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
    $exchangeBinanceSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/binanceExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];
    $exchangeOkxSymbolsList = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/okxExchange/derivativeBaseCoin.json'), true)['RESPONSE_EXCHENGE'] ?? [];

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

    //$summaryOpenInterestOb = \Maksv\Bybit\Exchange::getSummaryOpenInterestDev('BTCUSDT', $binanceApiOb, $bybitApiOb, $okxApiOb, $binanceSymbolsList, $bybitSymbolsList, $okxSymbolsList, '15m');

    $intervals = [
        '15m' => 1080000,  // 17 минут
    ];
    $endTime = round(microtime(true) * 1000);
    $startTime = $endTime - $intervals['15m']; // Начало интервала
    //$oiHistRespOkx = $okxApiOb->getOpenInterestHist('RVN-USDT-SWAP', $startTime, $endTime, '5m', 120, false, 1)['data'] ?? [];

    //$tradesHistoryResp = $okxApiOb->tradesHistory('TRUMP-USDT-SWAP', 1000);
    //$rsOi = \Maksv\Bybit\Exchange::getSummaryOpenInterestDev('ZETAUSDT', $binanceApiOb, $bybitApiOb, $okxApiOb, $binanceSymbolsList, $bybitSymbolsList, $okxSymbolsList, '15m');
    //$batchOI = $okxApiOb->getOpenInterestHist('ZETA-USDT-SWAP', false, false, '15m', 500, true, 300)['data'] ?? [];
    //$candles = $okxApiOb->getCandles('FLM-USDT-SWAP', '15m', 802, false);

    /*$creationTs = MakeTimeStamp("28.06.2025 16:37:04");   // в секундах
    $startMs    = $creationTs * 1000;
    $endMs      = ($creationTs + 48*3600) * 1000;          // +48 часов
    $candlesHist = $okxApiOb->getCandlesHist('DOOD-USDT-SWAP', '5m', $startMs, $endMs, false, 120);*/

    //$getKlines = $bingxApiOb->getKlines("WIF-USDT",'5m', 100,null, null, false);
    //$getFuturesContracts = $bingxApiOb->getFuturesContracts();
    //$tradesHistoryResp = $bingxApiOb->tradesHistory('AVAX-USDT', 1000);

    $bybitApiOb->closeConnection();
    $binanceApiOb->closeConnection();
    $okxApiOb->closeConnection();
    $bingxApiOb->closeConnection();
    ?>
<script>
    var devRes = {
        longV: <?=CUtil::PhpToJSObject($signals['long'], false, false, true)?>,
        shortV: <?=CUtil::PhpToJSObject($signals['short'], false, false, true)?>,
        //$rsOi: <?//=CUtil::PhpToJSObject($rsOi, false, false, true)?>,
    }
    console.log('devRes', devRes);
</script>
<?endif;?>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>