<?
use Bitrix\Main\Page\Asset;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Инструмент для торговли на бирже - Crypto helper");
$APPLICATION->SetTitle("Инструмент для торговли на бирже - Crypto helper");
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
<?php /*
    <link rel="stylesheet" href="/local/templates/maksv/css/mainpage-product-banners.css">
    <section class="mainpage-product-banners">
        <ul class="product-banners-list">
            <li class="product-banner-item">
                <div class="product-banner-item__inner">
                    <img class="product-banner-img" src="https://backend-web.storage.yandexcloud.net/resize_cache/119561/4a1300e106d1fbf13a15a8002bfe6337/iblock/c76/c761efd441f4c01ccadf8d179ca21cc2/a94763b5cb84b1ba78ed1fbe103adbd3.jpg" alt="Трусы">
                    <div class="product-banner-content">
                        <div class="h2 product-banner-name">Трусы</div>
                        <a href="/catalog/nizhnee_bele/trusy/" class="product-banner-link">Выбрать</a>
                    </div>
                </div>
            </li>
            <li class="product-banner-item">
                <div class="product-banner-item__inner">
                    <img class="product-banner-img" src="https://backend-web.storage.yandexcloud.net/resize_cache/119830/4a1300e106d1fbf13a15a8002bfe6337/iblock/e4f/e4f8b2da18bf75a7d2747d9300bd93d9/5c26412fff787b25ed407728502e6fb0.jpg" alt="Одежда">
                    <div class="product-banner-content">
                        <div class="h2 product-banner-name">Одежда</div>
                        <a href="/catalog/odezhda/" class="product-banner-link">Выбрать</a>
                    </div>
                </div>
            </li>
            <li class="product-banner-item">
                <div class="product-banner-item__inner">
                    <img class="product-banner-img" src="https://backend-web.storage.yandexcloud.net/resize_cache/119560/4a1300e106d1fbf13a15a8002bfe6337/iblock/c59/c598fc9ed4f31d5f5fc866cdf2475ff3/2b9a2f2fa0c2a451907740b1a199efd8.jpg" alt="Топы и бюстгальтеры">
                    <div class="product-banner-content">
                        <div class="h2 product-banner-name">Топы и бюстгальтеры</div>
                        <a href="/catalog/nizhnee_bele/topy/" class="product-banner-link">Выбрать</a>
                    </div>
                </div>
            </li>
        </ul>
    </section>*/?>

    <section style="margin: 0px 30px">
        <div class="h1">Bybit BTCUSDT, ETHUSDT</div>
        <br>

        <?$symbol = 'BTCUSDT';?>
        <div class="h2"><?=$symbol?></div>
        <?
        $marketCode = 'bybit';
        $timeFrame = '1h';
        $actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'V5Exchange/'.$timeFrame.'/marketVolumes.json'), true))['RESPONSE_EXCHENGE'] ?? [];
        $opportunitiesRes = \Maksv\StrategyBuilder::findPumpOrDumpOpportunities($actualSymbolsAr, $timeFrame, 'bybit');
        ?>
        <div class="h3"><?=$timeFrame?> <?=\Maksv\DataOperation::actualDateFormatted($opportunitiesRes['headCoin'][$symbol]['snapTimeMark'])?></div>
        <div>trend - <?=$opportunitiesRes['headCoin'][$symbol]['lastSAR']['trend']?><?if ($opportunitiesRes['headCoin'][$symbol]['lastSAR']['is_reversal']):?>, reversal<?endif;?> . <?=$opportunitiesRes['headCoin'][$symbol]['crossMA']?>. P <?=$opportunitiesRes['headCoin'][$symbol]['lastPriceChange']?>%. OI <?=$opportunitiesRes['headCoin'][$symbol]['lastOpenInterest']?>%</div>
        <br>
        <?
        $timeFrame = '4h';
        $actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'V5Exchange/'.$timeFrame.'/marketVolumes.json'), true))['RESPONSE_EXCHENGE'] ?? [];
        $opportunitiesRes = \Maksv\StrategyBuilder::findPumpOrDumpOpportunities($actualSymbolsAr, $timeFrame, 'bybit');
        ?>
        <div class="h3"><?=$timeFrame?> <?=\Maksv\DataOperation::actualDateFormatted($opportunitiesRes['headCoin'][$symbol]['snapTimeMark'])?></div>
        <div>trend - <?=$opportunitiesRes['headCoin'][$symbol]['lastSAR']['trend']?><?if ($opportunitiesRes['headCoin'][$symbol]['lastSAR']['is_reversal']):?>, reversal<?endif;?> . <?=$opportunitiesRes['headCoin'][$symbol]['crossMA']?>. P <?=$opportunitiesRes['headCoin'][$symbol]['lastPriceChange']?>%. OI <?=$opportunitiesRes['headCoin'][$symbol]['lastOpenInterest']?>%</div>
        <br>
        <?
        $timeFrame = '1d';
        $actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'V5Exchange/'.$timeFrame.'/marketVolumes.json'), true))['RESPONSE_EXCHENGE'] ?? [];
        $opportunitiesRes = \Maksv\StrategyBuilder::findPumpOrDumpOpportunities($actualSymbolsAr, $timeFrame, 'bybit');
        ?>
        <div class="h3"><?=$timeFrame?> <?=\Maksv\DataOperation::actualDateFormatted($opportunitiesRes['headCoin'][$symbol]['snapTimeMark'])?></div>
        <div>trend - <?=$opportunitiesRes['headCoin'][$symbol]['lastSAR']['trend']?><?if ($opportunitiesRes['headCoin'][$symbol]['lastSAR']['is_reversal']):?>, reversal<?endif;?> . <?=$opportunitiesRes['headCoin'][$symbol]['crossMA']?>. P <?=$opportunitiesRes['headCoin'][$symbol]['lastPriceChange']?>%. OI <?=$opportunitiesRes['headCoin'][$symbol]['lastOpenInterest']?>%</div>
        <br>
        <?$symbol = 'ETHUSDT';?>
        <div class="h2"><?=$symbol?></div>
        <?
        $marketCode = 'bybit';
        $timeFrame = '1h';
        $actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'V5Exchange/'.$timeFrame.'/marketVolumes.json'), true))['RESPONSE_EXCHENGE'] ?? [];
        $opportunitiesRes = \Maksv\StrategyBuilder::findPumpOrDumpOpportunities($actualSymbolsAr, $timeFrame, 'bybit');
        ?>
        <div class="h3"><?=$timeFrame?> <?=\Maksv\DataOperation::actualDateFormatted($opportunitiesRes['headCoin'][$symbol]['snapTimeMark'])?></div>
        <div>trend - <?=$opportunitiesRes['headCoin'][$symbol]['lastSAR']['trend']?><?if ($opportunitiesRes['headCoin'][$symbol]['lastSAR']['is_reversal']):?>, reversal<?endif;?> . <?=$opportunitiesRes['headCoin'][$symbol]['crossMA']?>. P <?=$opportunitiesRes['headCoin'][$symbol]['lastPriceChange']?>%. OI <?=$opportunitiesRes['headCoin'][$symbol]['lastOpenInterest']?>%</div>
        <br>
        <?
        $timeFrame = '4h';
        $actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'V5Exchange/'.$timeFrame.'/marketVolumes.json'), true))['RESPONSE_EXCHENGE'] ?? [];
        $opportunitiesRes = \Maksv\StrategyBuilder::findPumpOrDumpOpportunities($actualSymbolsAr, $timeFrame, 'bybit');
        ?>
        <div class="h3"><?=$timeFrame?> <?=\Maksv\DataOperation::actualDateFormatted($opportunitiesRes['headCoin'][$symbol]['snapTimeMark'])?></div>
        <div>trend - <?=$opportunitiesRes['headCoin'][$symbol]['lastSAR']['trend']?><?if ($opportunitiesRes['headCoin'][$symbol]['lastSAR']['is_reversal']):?>, reversal<?endif;?> . <?=$opportunitiesRes['headCoin'][$symbol]['crossMA']?>. P <?=$opportunitiesRes['headCoin'][$symbol]['lastPriceChange']?>%. OI <?=$opportunitiesRes['headCoin'][$symbol]['lastOpenInterest']?>%</div>
        <br>
        <?
        $timeFrame = '1d';
        $actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $marketCode . 'V5Exchange/'.$timeFrame.'/marketVolumes.json'), true))['RESPONSE_EXCHENGE'] ?? [];
        $opportunitiesRes = \Maksv\StrategyBuilder::findPumpOrDumpOpportunities($actualSymbolsAr, $timeFrame, 'bybit');
        ?>
        <div class="h3"><?=$timeFrame?> <?=\Maksv\DataOperation::actualDateFormatted($opportunitiesRes['headCoin'][$symbol]['snapTimeMark'])?></div>
        <div>trend - <?=$opportunitiesRes['headCoin'][$symbol]['lastSAR']['trend']?><?if ($opportunitiesRes['headCoin'][$symbol]['lastSAR']['is_reversal']):?>, reversal<?endif;?> . <?=$opportunitiesRes['headCoin'][$symbol]['crossMA']?>. P <?=$opportunitiesRes['headCoin'][$symbol]['lastPriceChange']?>%. OI <?=$opportunitiesRes['headCoin'][$symbol]['lastOpenInterest']?>%</div>
        <br>
    </section>
    <h1 class="main-title">Инструмент для торговли на бирже - Crypto helper</h1>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>