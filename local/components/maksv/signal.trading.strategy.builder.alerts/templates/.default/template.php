<?
use Bitrix\Main\Page\Asset;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
global $USER;

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/adaptiveTables.css?v1", true);
?>
<div class="h2">all сигналы</div>

<?php

?>
<section class="page-startegy_table js_open_interests-section">
    <? /*<<div class="h3">Анализ</div> //
     <div class="table-open_interests">
        div class="h2">OI</div>
        <button class="button js-update_button">Обновить</button>
    </div>*/?>

    <div class="table-info_block">
        <form class="js-oi-symbol_form">
            <div class="form-row">
                <label for="oi-symbol">Введите текст и нажмите Enter</label>
                <div class="input-wrapper">
                    <input type="text" class="form-input" placeholder="Контракт" name="oi-symbol" id="oi-symbol">
                    <span class="clear-icon">&times;</span>
                    <div class="suggestions-box" id="suggestions-box"></div>
                </div>
            </div>
        </form>
        <?/*
        <form class="js-oi-symbol_form">
            <div class="form-row">
                <label for="oi-symbol">Введите текст и нажмите Enter</label>
                <input type="text" class="form-input" placeholder="Контракт" name="oi-symbol" id="oi-symbol">
            </div>
        </form>
        */?>
        <div class="table_loading_block js-loader_oi_external" style="display: none">
            <div class="">Загрузка</div>
            <div class="spinner"></div>
        </div>
        <div class="actuality-block js-oi_actuality"><span class="js-timeMark-oi_actuality">...</span></div>
    </div>

    <?$arParams['BYBIT_IS_CONNECT'] = true?>
    <?if ($arParams['BYBIT_IS_CONNECT']):?>
        <div class="open_interests_loading_block error_text" style="display: none;">
        </div>

        <div class="mobile-table">
            <table class="iksweb js-open_interests_table">
                <thead>
                <tr>
                    <th class="js_contract_name">-</th>
                    <th>15m</th>
                    <th>30m</th>
                    <th>1h</th>
                    <th>4h</th>
                    <th>1d</th>
                </tr>
                </thead>
                <tbody class="js-open_interests-tbody">

                <tr style="pointer-events: none;">
                    <td colspan="6" class="js-open_interests-loader_internal">
                        <div class="table_loading_block" style="">
                            <div class="h3">Выберете контракт</div>
                        </div>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>
    <?else:?>
        <div class="h3">Price / OI / MAxEMa / SAR</div>
        <div class="mobile-table">
            <table class="iksweb js-open_interests_table">
                <thead>
                <tr>
                    <th class="js_contract_name">-</th>
                    <th>15m</th>
                    <th>30m</th>
                    <th>1h</th>
                    <th>4h</th>
                    <th>1d</th>
                </tr>
                </thead>
                <tbody class="js-open_interests-tbody">

                <tr style="pointer-events: none;">
                    <td colspan="6" class="js-open_interests-loader_internal">
                        <div class="table_loading_block" style="">
                            <div class="h4">Заполните поля Bybit api в личном кабинете для использования таблицы технического анализа</div>
                        </div>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>
    <?endif;?>

    <div class="table-info_block js-zones" style=" display: none; margin-top: 10px;">
        <div class="table-info_block-load_zone">
            <div class="timeframe-selector">
                <button class="timeframe-button" data-timeframe="15m">15m</button>
                <button class="timeframe-button" data-timeframe="30m">30m</button>
                <button class="timeframe-button" data-timeframe="1h">1h</button>
                <button class="timeframe-button" data-timeframe="4h">4h</button>
                <button class="timeframe-button active" data-timeframe="1d">1d</button>
            </div>
            <div class="table_loading_block js-loader_oi_external" style="display: none">
                <div class="spinner"></div>
            </div>
        </div>
        <div class="mobile-table">
            <table class="iksweb js-symbol_zones_table">
                <tbody class="js-symbol_zones-tbody">
                <tr style="pointer-events: none;" class="js-upperZones">
                    <td class="green-bg" >...</td>
                </tr>
                <tr style="pointer-events: none;" class="js-lowerZones">
                    <td class="red-bg">...</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-info_block js-orderBlock" style=" display: none; margin-top: 240px;">
        <div class="mobile-table">
            <table class="iksweb js-symbol_orderBlock_table">
                <tbody class="js-symbol_orderBlock-tbody">
                <tr style="pointer-events: none;" class="js-upperOrderBlock">
                    <td class="green-bg" >...</td>
                </tr>
                <tr style="pointer-events: none;" class="js-lowerOrderBlock">
                    <td class="red-bg">...</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>


    <div class="table-info_block js-lvls" style=" display: none; margin-top: 240px;">
        <div class="mobile-table">
            <table class="iksweb js-symbol_levels_table">
                <tbody class="js-symbol_levels-tbody">
                <tr style="pointer-events: none;" class="js-upperlvls">
                    <td class="green-bg" >...</td>
                </tr>
                <tr style="pointer-events: none;" class="js-lowerlvls">
                    <td class="red-bg">...</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <button class="button_fixed-bottom button white-color-font button-white-border button-small-font js-go_up-page">В начало</button>
</section>
<br>
<?if ($arResult['ITEMS']):?>
    <script>
        var ResultJS = {
            ITEMS: <?=CUtil::PhpToJSObject($arResult['ITEMS'], false, false, true)?>,
        }
        console.log('ResultJS');
        console.log(ResultJS);
    </script>
    <? foreach ($arResult['ITEMS'] as $strtagyItem):?>
        <section class="page-startegy_table">
            <div class="table-info_block">
                <div class=""><span class="js-timeMark-actuality"><?=$strtagyItem['NAME']?></span></div>
            </div>

            <div class="mobile-table">
                <table class="iksweb js-strategy_table">
                    <thead>
                    <tr>
                        <th>Сделка</th>
                        <th>Контракт</th>
                        <th>Поддержка/<br>Сопротивление</th>
                        <th>MA26 trend</th>
                        <th>MACD<br>дивергенция</th>
                        <th>P / OI</th>
                        <th>Изменение<br>цены</th>
                    </tr>
                    </thead>
                    <tbody class="js-strategy-tbody">
                    <?if (($strtagyItem['STRATEGIES']['allPump'] && is_array($strtagyItem['STRATEGIES']['allPump']) && count($strtagyItem['STRATEGIES']['allPump']) >= 1) ||
                        ($strtagyItem['STRATEGIES']['allDump'] && is_array($strtagyItem['STRATEGIES']['allDump']) && count($strtagyItem['STRATEGIES']['allDump']) >= 1)):?>
                        <?foreach ($strtagyItem['STRATEGIES']['allPump'] as $item):?>
                            <tr data-val="<?=$item['symbolName']?>">
                                <td data-name="trand" class="green-bg">
                                    long<br>
                                    <?=$item['strategy']?>
                                </td>
                                <td data-name="symbolName"><?=$item['symbolName']?></td>
                                <td data-name="trendApprove" <?if (false):?>class="green-bg"<?endif;?>>
                                    <?if ($item['orderBlockLongZone'] && is_array($item['orderBlockLongZone'])):?>
                                        <?=round($item['orderBlockLongZone']['distance'],2)?> %<br><?=$item['orderBlockLongZone']['lower']?> - <?=$item['orderBlockLongZone']['upper']?>
                                    <?else:?>
                                        -
                                    <?endif;?>
                                </td>
                                <td data-name="crossMA" <?if ($item['crossMAVal'] == 1):?>class="green-bg"<?endif;?>>
                                    <?if($item['lastCrossMA']['isUptrend'] === true):?>up<?else:?>up<?endif;?><br>
                                    <?=round($item['lastCrossMA']['sma'], 5)?>
                                    <?if ($item['crossMAVal'] != 0):?><br>MA26 x EMA9: <?=$item['lastCrossMA']['cross'] ?? $item['crossMA']?><?endif;?>
                                </td>
                                <td data-name="macd">
                                    <?if ($item['actualMacdDivergence']['longDivergenceTypeAr']['regular']):?>
                                        regular
                                    <?elseif($item['actualMacdDivergence']['longDivergenceTypeAr']['hidden']):?>
                                        hidden
                                    <?endif;?>
                                    <?if ($item['actualMacdDivergence']['divergenceDistance']):?>
                                        (<?=$item['actualMacdDivergence']['divergenceDistance']?>)
                                    <?endif;?>                                </td>
                                <td data-name="OI" <?if ($item['anomalyOI']):?>class="green-bg"<?endif;?>><?=$item['lastPriceChange']?> / <?=$item['lastOpenInterest'];?></td>
                                <td data-name="priceChange" <?if ($item['priceAnalysis']['percent_change'] > 3):?>class="green-bg"<?endif;?>><?if ($item['priceAnalysis']['percent_change']):?><?=$item['priceAnalysis']['percent_change']?> %<?else:?>-<?endif;?></td>
                            </tr>
                        <?endforeach;?>
                        <?foreach ($strtagyItem['STRATEGIES']['allDump'] as $item):?>
                            <tr data-val="<?=$item['symbolName']?>">
                                <td data-name="trade" class="red-bg">
                                    short<br><?=$item['strategy']?>
                                </td>
                                <td data-name="symbolName"><?=$item['symbolName']?></td>
                                <td data-name="trendApprove" <?if (false):?>class="red-bg"<?endif;?>>
                                    <?if ($item['orderBlockShortZone'] && is_array($item['orderBlockShortZone'])):?>
                                        <?=round($item['orderBlockShortZone']['distance'],2)?> %<br><?=$item['orderBlockShortZone']['lower']?> - <?=$item['orderBlockShortZone']['upper']?>
                                    <?else:?>
                                        -
                                    <?endif;?>
                                </td>
                                <td data-name="crossMA" <?if ($item['crossMAVal'] == 2):?>class="red-bg"<?endif;?>>
                                    <?if($item['lastCrossMA']['isUptrend'] === true):?>up<?else:?>down<?endif;?><br>
                                    <?=round($item['lastCrossMA']['sma'], 5)?>
                                    <?if ($item['crossMAVal'] != 0):?><br>MA26 x EMA9: <?=$item['lastCrossMA']['cross'] ?? $item['crossMA']?><?endif;?>
                                </td>
                                <td data-name="macd">
                                    <?if ($item['actualMacdDivergence']['shortDivergenceTypeAr']['regular']):?>
                                        regular
                                    <?elseif($item['actualMacdDivergence']['shortDivergenceTypeAr']['hidden']):?>
                                        hidden
                                    <?endif;?>
                                    <?if ($item['actualMacdDivergence']['divergenceDistance']):?>
                                        (<?=$item['actualMacdDivergence']['divergenceDistance']?>)
                                    <?endif;?>
                                </td>
                                <td data-name="OI" <?if ($item['anomalyOI']):?>class="red-bg"<?endif;?>><?=$item['lastPriceChange']?> / <?=$item['lastOpenInterest'];?></td>
                                <td data-name="priceChange" <?if ($item['priceAnalysis']['status'] && $item['priceAnalysis']['percent_change'] < -3):?>class="red-bg"<?endif;?>><?if ($item['priceAnalysis']['percent_change']):?><?=$item['priceAnalysis']['percent_change']?> %<?else:?>-<?endif;?></td>
                            </tr>
                        <?endforeach;?>
                    <?else:?>
                        <tr style="pointer-events: none;">
                            <td colspan="6" class="js-loader_internal">
                                <div class="table_loading_block" style="">
                                    <div class="h3">Сейчас пусто</div>
                                </div>
                            </td>
                        </tr>
                    <?endif;?>
                    </tbody>
                </table>
            </div>
        </section>
    <?endforeach;?>
    <?/*$APPLICATION->IncludeComponent(
    "bitrix:system.pagenavigation",
    "signals",
    array(
        "NAV_OBJECT" => $arResult['NAV'],
       // "SEF_MODE" => "Y",
    ),
    false
);*/?>

    <?$APPLICATION->IncludeComponent(
        "bitrix:main.pagenavigation",
        "modern",
        array(
            "NAV_OBJECT" => $arResult['NAV'],
            // "SEF_MODE" => "Y",
        ),
        false
    );?>
<?endif;?>

