<?
use Bitrix\Main\Page\Asset;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
global $USER;

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/adaptiveTables.css?v=2", true);
?>
<div class="h2">Master сигналы</div>

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
                    <th>5m</th>
                    <th>15m</th>
                    <th>30m</th>
                    <th>1h</th>
                    <th>4h</th>
                    <th>1d</th>
                </tr>
                </thead>
                <tbody class="js-open_interests-tbody">

                <tr style="pointer-events: none;">
                    <td colspan="7" class="js-open_interests-loader_internal">
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
                    <th>5m</th>
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
                <button class="timeframe-button" data-timeframe="5m">5m</button>
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
        var ResultJS = <?=CUtil::PhpToJSObject($arResult['ITEMS'], false, false, true)?>;
        console.log('ResultJS');
        console.log(ResultJS);
    </script>
<?$delistArr = $arResult['ITEMS'][0]['INFO']['DELISTING'] ?? [];?>
<?if ($delistArr && !$_GET['signals']):?>
    <? foreach ($delistArr as $delistItem):?>
            <?=$delistItem?><br>
    <?endforeach;?>
<?endif;?>
<? foreach ($arResult['ITEMS'] as $strtagyItem):?>
<section class="page-startegy_table">
    <div class="table-info_block">
            <div class="">
                <span class="js-timeMark-actuality">
                    <?=$strtagyItem['FORMATTED_NAME']?>
                </span>

                <?php if ($strtagyItem['INFO']['BTC_INFO']['infoText']): ?>
                    <div class="btc-info-wrapper">
                        <a class="info-icon">BTC</a>

                        <div class="btc-info-block">
                            <?= nl2br(htmlspecialchars($strtagyItem['INFO']['BTC_INFO']['infoText'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mobile-table">
            <table class="iksweb js-strategy_table">
                <thead>
                <tr>
                    <th>Направление</th>
                    <th>Контракт</th>
                    <th>Targets</th>
                    <th>TA</th>
                    <th>P / OI</th>
                </tr>
                </thead>
                <tbody class="js-strategy-tbody">
                <?$signalsCode = $arParams['MAIN_CODE'];?>
                <?if (($strtagyItem['STRATEGIES'][$signalsCode . 'Pump'] && is_array($strtagyItem['STRATEGIES'][$signalsCode . 'Pump']) && count($strtagyItem['STRATEGIES'][$signalsCode . 'Pump']) >= 1) ||
                    ($strtagyItem['STRATEGIES'][$signalsCode . 'Dump'] && is_array($strtagyItem['STRATEGIES'][$signalsCode . 'Dump']) && count($strtagyItem['STRATEGIES'][$signalsCode . 'Dump']) >= 1)):?>
                    <?foreach ($strtagyItem['STRATEGIES'][$signalsCode . 'Pump'] as $item):?>
                        <tr data-val="<?=$item['symbolName']?>">
                            <td data-name="trand" class="green-bg">
                                long
                                <br><?=$item['strategy']?>
                            </td>
                            <td data-name="symbolName">
                                <?=$item['symbolName']?>

                                <?if ($item['oiLimits']['longOiLimit']):?>
                                    <br>oi limit: <?=round($item['oiLimits']['longOiLimit'], 2)?>
                                <?endif;?>
                                <?if ($item['actualAdx1h']):?>
                                    <br>adx 1h: dir <?=$item['actualAdx1h']['adxDirection']['adxDir']?> / trend <?=$item['actualAdx1h']['trendDirection']['trendDir']?> (<?= round($item['actualAdx1h']['adx'], 1)?>)
                                <?endif;?>
                                <?if ($item['calculateRiskTargetsWithATR']['tpMultipliers']):?>
                                    <br>atr m: <?=implode(', ',$item['calculateRiskTargetsWithATR']['tpMultipliers'])?>
                                <?endif;?>
                                <?if ($item['resML']['totalMl']):?>
                                    <br>ML: <?=$item['resML']['totalMl'];?> (<?=$item['resML']['signalMl'];?>/<?=$item['resML']['marketMl'];?>)
                                <?endif;?>
                            </td>
                            <td data-name="targets">
                                <?if ($item['actualClosePrice']):?>
                                    Entry Target <?=$item['actualClosePrice']?><br>
                                <?endif;?>
                                <?if ($item['recommendedEntry']):?>
                                    (Recommended entry <?=$item['recommendedEntry']?>)<br>
                                <?endif;?>
                                <?if ($item['tpCount']['longTpCount']):?>
                                    (Recommended tp count <?=$item['tpCount']['longTpCount']?>)<br>
                                <?endif;?>

                                <?if ($item['calculateRiskTargetsWithATR']['riskPercent']):?>
                                    (risk <?=$item['calculateRiskTargetsWithATR']['riskPercent']?>)<br>
                                <?endif;?>


                                <?if ($item['TP']):?>
                                    <br>Profit Targets:<br>
                                    <?foreach ($item['TP'] as $key => $tpVal):?>
                                        <div <?if ($item['priceAnalysis']['target_price'] && $item['priceAnalysis']['target_price'] >= floatval($tpVal)):?>class="green-bg"<?endif;?>><?=$key+1?>. <?=$tpVal?></div>
                                    <?endforeach;?>
                                    <br>
                                <?endif;?>
                                <?if ($item['SL']):?>
                                    <div <?if ($item['priceAnalysis']['sl_hit']):?>class="red-bg"<?endif;?>>Stop Loss <?=$item['SL']?></div><br>
                                <?endif;?>
                                <?if ($item['priceAnalysis']['realized_percent_change']):?>
                                    <div class="<?if ($item['priceAnalysis']['realized_percent_change'] > 0):?>green-bg<?elseif($item['priceAnalysis']['realized_percent_change'] < 0):?>red-bg<?endif;?>">Profit <?=$item['priceAnalysis']['realized_percent_change']?>%</div><br>
                                <?endif;?>
                                <?if ($item['priceAnalysis']['percent_change']):?>
                                    <div class="<?if ($item['priceAnalysis']['percent_change'] > 2):?>green-bg<?elseif($item['priceAnalysis']['percent_change'] < -2):?>red-bg<?endif;?>">price change <?=$item['priceAnalysis']['percent_change']?>%</div><br>
                                <?endif;?>
                            </td>
                            <td data-name="ta">
                                macd<br>
                                <?if ($item['actualMacdDivergence']['longDivergenceTypeAr']['regular']):?>
                                    regular
                                <?elseif($item['actualMacdDivergence']['longDivergenceTypeAr']['hidden']):?>
                                    hidden
                                <?endif;?>
                                <?if ($item['actualMacdDivergence']['longDivergenceDistance']):?>
                                    (<?=$item['actualMacdDivergence']['longDivergenceDistance']?>)
                                <?endif;?>
                                <?if ($item['actualMacdDivergence']['inputParams']):?>
                                    <br><?=$item['actualMacdDivergence']['inputParams']?>
                                <?endif;?>

                                <br><br>ma26
                                <div <?if ($item['crossMAVal'] == 1):?>class="green-bg"<?endif;?>>
                                    <?if($item['lastCrossMA']['isUptrend']):?>up<?else:?>down<?endif;?><br>
                                    <?=round($item['lastCrossMA']['sma'], 5)?>
                                    <?if ($item['crossMAVal'] != 0):?><br>MA26 x EMA9: <?=$item['lastCrossMA']['cross'] ?? $item['crossMA']?><?endif;?>
                                </div>
                            </td>
                            <td data-name="OI" <?if ($item['anomalyOI']):?>class="green-bg"<?endif;?>>
                                <?=$item['lastPriceChange']?> / <?=$item['lastOpenInterest'];?>
                                <br>

                                <?$summOi = $item['summaryOpenInterest'];?>
                                <br>OI Bybit <?=$summOi['summaryOIBybit'] ?? '-'?>
                                <br>OI Binance <?=$summOi['summaryOIBinance'] ?? '-'?>
                                <br>OI Okx <?=$summOi['summaryOIOkx'] ?? '-'?>

                            </td>
                        </tr>
                    <?endforeach;?>
                    <?foreach ($strtagyItem['STRATEGIES'][$signalsCode . 'Dump'] as $item):?>
                        <tr data-val="<?=$item['symbolName']?>">
                            <td data-name="trade" class="red-bg">
                                short
                                <br><?=$item['strategy']?>
                            </td>
                            <td data-name="symbolName">
                                <?=$item['symbolName']?>
                                <?if ($item['oiLimits']['shortOiLimit']):?>
                                    <br>oi limit: <?=round($item['oiLimits']['shortOiLimit'], 2)?>
                                <?endif;?>
                                <?if ($item['actualAdx1h']):?>
                                    <br>adx 1h: dir <?=$item['actualAdx1h']['adxDirection']['adxDir']?> / trend <?=$item['actualAdx1h']['trendDirection']['trendDir']?> (<?= round($item['actualAdx1h']['adx'], 1)?>)
                                <?endif;?>
                                <?if ($item['calculateRiskTargetsWithATR']['tpMultipliers']):?>
                                    <br>atr m: <?=implode(', ',$item['calculateRiskTargetsWithATR']['tpMultipliers'])?>
                                <?endif;?>
                                <?if ($item['resML']['totalMl']):?>
                                    <br>ML: <?=$item['resML']['totalMl'];?> (<?=$item['resML']['signalMl'];?>/<?=$item['resML']['marketMl'];?>)
                                <?endif;?>
                            </td>
                            <td data-name="targets">
                                <?if ($item['actualClosePrice']):?>
                                    Entry Target <?=$item['actualClosePrice']?><br>
                                <?endif;?>
                                <?if ($item['recommendedEntry']):?>
                                    (Recommended entry <?=$item['recommendedEntry']?>)<br>
                                <?endif;?>
                                <?if ($item['tpCount']['shortTpCount']):?>
                                    (Recommended tp count <?=$item['tpCount']['shortTpCount']?>)<br>
                                <?endif;?>
                                <?if ($item['calculateRiskTargetsWithATR']['riskPercent']):?>
                                    (risk <?=$item['calculateRiskTargetsWithATR']['riskPercent']?>)<br>
                                <?endif;?>


                                <?if ($item['TP']):?>
                                    <br>Profit Targets:<br>
                                    <?foreach ($item['TP'] as $key => $tpVal):?>
                                        <div <?if ($item['priceAnalysis']['target_price'] && $item['priceAnalysis']['target_price'] <= floatval($tpVal)):?>class="green-bg"<?endif;?>><?=$key+1?>. <?=$tpVal?></div>
                                    <?endforeach;?>
                                    <br>
                                <?endif;?>
                                <?if ($item['SL']):?>
                                    <div <?if ($item['priceAnalysis']['sl_hit']):?>class="red-bg"<?endif;?>>Stop Loss <?=$item['SL']?></div><br>
                                <?endif;?>
                                <?if ($item['priceAnalysis']['realized_percent_change']):?>
                                    <div class="<?if ($item['priceAnalysis']['realized_percent_change'] > 0):?>green-bg<?elseif($item['priceAnalysis']['realized_percent_change'] < 0):?>red-bg<?endif;?>">Profit <?=$item['priceAnalysis']['realized_percent_change']?>%</div><br>
                                <?endif;?>
                                <?if ($item['priceAnalysis']['percent_change']):?>
                                    <div class="<?if ($item['priceAnalysis']['percent_change'] > 2):?>green-bg<?elseif($item['priceAnalysis']['percent_change'] < -2):?>red-bg<?endif;?>">price change <?=$item['priceAnalysis']['percent_change']?>%</div><br>
                                <?endif;?>
                            </td>
                            <td data-name="ta">
                                macd<br>
                                <?if ($item['actualMacdDivergence']['shortDivergenceTypeAr']['regular']):?>
                                    regular
                                <?elseif($item['actualMacdDivergence']['shortDivergenceTypeAr']['hidden']):?>
                                    hidden
                                <?endif;?>
                                <?if ($item['actualMacdDivergence']['shortDivergenceDistance']):?>
                                    (<?=$item['actualMacdDivergence']['shortDivergenceDistance']?>)
                                <?endif;?>
                                <?if ($item['actualMacdDivergence']['inputParams']):?>
                                    <br><?=$item['actualMacdDivergence']['inputParams']?>
                                <?endif;?>

                                <br><br>ma26
                                <div <?if ($item['crossMAVal'] == 2):?>class="red-bg"<?endif;?>>
                                    <?if($item['lastCrossMA']['isUptrend']):?>up<?else:?>down<?endif;?><br>
                                    <?=round($item['lastCrossMA']['sma'], 5)?>
                                    <?if ($item['crossMAVal'] != 0):?><br>MA26 x EMA9: <?=$item['lastCrossMA']['cross'] ?? $item['crossMA']?><?endif;?>
                                </div>
                            </td>
                            <td data-name="OI" <?if ($item['anomalyOI']):?>class="red-bg"<?endif;?>>
                                <?=$item['lastPriceChange']?> / <?=$item['lastOpenInterest'];?>
                                <br>
                                <?$summOi = $item['summaryOpenInterest'];?>
                                <br>OI Bybit <?=$summOi['summaryOIBybit'] ?? '-'?>
                                <br>OI Binance <?=$summOi['summaryOIBinance'] ?? '-'?>
                                <br>OI Okx <?=$summOi['summaryOIOkx'] ?? '-'?>
                            </td>
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

