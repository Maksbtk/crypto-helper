<?
use Bitrix\Main\Page\Asset;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Инструмент для торговли на бирже - Crypto helper");
$APPLICATION->SetTitle("Инструмент для торговли на бирже - Crypto helper");

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/adaptiveTables.css?v1", true);
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

/*try {
    $binanceOb = new \Maksv\Binance();
    $resp = $binanceOb->getDepth('FITFIUSDT', 1000);
    echo '<pre>'; var_dump($resp['bids']); echo '</pre>';

} catch (Exception $e) {
    echo '<pre>'; var_dump($e); echo '</pre>';

}*/

/*$binanceOb = new \Maksv\Binance();
$resp = $binanceOb->getDepth('FITFIUSDT', 1000);*/


/*$bybitApiOb = new \Maksv\Bybit(apiKey : 'QOvYCttBiD4d9m7lNn' , secretKey : 'qOBEvTvgFDthGFTRl97Vokq4lHo2KNW4wnLT');
$bybitApiOb->openConnection();

$getServerTime =  $bybitApiOb->getServerTime();
echo '<pre>'; var_dump($getServerTime); echo '</pre>';
echo '<pre>'; var_dump($getServerTime['retMsg'] == 'OK'); echo '</pre>';

$bybitApiOb->closeConnection();*/


?>

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
                        <div class="h2 product-banner-name">binance screener</div>
                        <a href="/user/binanceScreener/" class="product-banner-link">Перейти</a>
                    </div>
                </div>
            </li>
            <li class="product-banner-item">
                <div class="product-banner-item__inner">
                    <img class="product-banner-img" src="/local/templates/maksv/demo-pics/ban3.jpg?v=5" alt="CH">
                    <div class="product-banner-content">
                        <div class="h2 product-banner-name">bybit screener</div>
                        <a href="/user/bybitScreener/" class="product-banner-link">Перейти</a>
                    </div>
                </div>
            </li>
        </ul>
    </section>

<?/*
    <section style="margin: 0px 15px;display: flex;flex-direction: column;justify-content: center;align-items: center;">

        <div class="h1">Bybit BTCUSDT, ETHUSDT</div>
        <br>
        <?$marketCode = 'bybit';?>
        <?$headCoinMap = ['BTCUSDT', 'ETHUSDT'];?>
        <?foreach ($headCoinMap as $coin):?>
            <div class="mobile-table">
                <table class="iksweb js-open_interests_table">
                    <thead>
                        <tr>
                            <th><?=$coin?></th>
                            <?$tfMap = ['30m', '1h', '4h', '1d'];?>
                            <? foreach ($tfMap as $tf):?>
                                <th><?=$tf?></th>
                            <?endforeach;?>
                        </tr>
                    </thead>
                    <tbody>
                            <tr>
                                <td>Price / OI</td>
                                <?foreach ($tfMap as $tf):?>
                                    <?$actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'Exchange/'.$tf.'/actualMarketVolumes.json'), true))['STRATEGIES']['headCoin'] ?? [];?>
                                    <td><?=$actualSymbolsAr[$coin]['lastPriceChange']?> / <?=$actualSymbolsAr[$coin]['lastOpenInterest']?></td>
                                <?endforeach;?>
                            </tr>
                            <tr>
                                <td>Supertrand</td>
                                <?foreach ($tfMap as $tf):?>
                                    <?$actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'Exchange/'.$tf.'/actualMarketVolumes.json'), true))['STRATEGIES']['headCoin'] ?? [];?>
                                    <td>
                                        <?=$actualSymbolsAr[$coin]['lastSupertrend']['trend']?>
                                        <?if ($actualSymbolsAr[$coin]['lastSupertrend']['is_reversal']):?>
                                            <br>reversal
                                        <?endif;?>
                                    </td>
                                <?endforeach;?>
                            </tr>
                            <tr>
                                <td>SAR</td>
                                <?foreach ($tfMap as $tf):?>
                                    <?$actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'Exchange/'.$tf.'/actualMarketVolumes.json'), true))['STRATEGIES']['headCoin'] ?? [];?>
                                    <td>
                                        <?=$actualSymbolsAr[$coin]['lastSAR']['trend']?>
                                        <?if ($actualSymbolsAr[$coin]['lastSAR']['is_reversal']):?>
                                            <br>reversal
                                        <?endif;?>
                                    </td>
                                <?endforeach;?>
                            </tr>
                    </tbody>
                </table>
            </div>
            <br>
        <?endforeach;?>
    </section> <?*/?>

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


    $checkMarketImpulsInfo = \Maksv\Bybit\Exchange::checkMarketImpulsInfo();

    $bybitApiOb->closeConnection();
    $binanceApiOb->closeConnection();
    $okxApiOb->closeConnection();
    ?>
<script>
    var devRes = {
        longV: <?=CUtil::PhpToJSObject($signals['long'], false, false, true)?>,
        shortV: <?=CUtil::PhpToJSObject($signals['short'], false, false, true)?>,
        $okxSymbolsList: <?=CUtil::PhpToJSObject($okxSymbolsList, false, false, true)?>,
    }
    console.log('devRes', devRes);
</script>
<?endif;?>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>