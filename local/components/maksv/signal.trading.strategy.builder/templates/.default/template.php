<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
global $USER;
?>
<div class="h2">Master сигналы</div>

<?php

?>
<section class="page-startegy_table js_open_interests-section">
    <? /*<<div class="h3">Анализ</div>
     <div class="table-open_interests">
        div class="h2">OI</div>
        <button class="button js-update_button">Обновить</button>
    </div>*/?>
    <?$arParams['BYBIT_IS_CONNECT'] = true?>
    <?if ($arParams['BYBIT_IS_CONNECT']):?>
        <div class="table-info_block">
            <form class="js-oi-symbol_form">
                <div class="form-row">
                    <label for="oi-symbol">Введите текст и нажмите Enter</label>
                    <input type="text" class="form-input" placeholder="Контракт" name="oi-symbol" id="oi-symbol">
                </div>
            </form>

            <div class="table_loading_block js-loader_oi_external" style="display: none">
                <div class="">Загрузка</div>
                <div class="spinner"></div>
            </div>
            <div class="actuality-block js-oi_actuality"><span class="js-timeMark-oi_actuality">...</span></div>
        </div>

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
    <div class="table-info_block js-lvls" style=" display: none; margin-top: 10px;">
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
                    <th>Торговая пара</th>
                    <th>MA x EMA</th>
                    <th>SAR</th>
                    <th>P / OI</th>
                    <th>Profit levels</th>
                </tr>
                </thead>
                <tbody class="js-strategy-tbody">
                <?if (($strtagyItem['STRATEGIES']['masterPump'] && is_array($strtagyItem['STRATEGIES']['masterPump']) && count($strtagyItem['STRATEGIES']['masterPump']) >= 1) ||
                    ($strtagyItem['STRATEGIES']['masterDump'] && is_array($strtagyItem['STRATEGIES']['masterDump']) && count($strtagyItem['STRATEGIES']['masterDump']) >= 1)):?>
                    <?foreach ($strtagyItem['STRATEGIES']['masterPump'] as $item):?>
                        <tr data-val="<?=$item['symbolName']?>">
                            <td data-name="trand" class="green-bg">long<br><?if ($item['secondFilter']):?>Parent approve<?endif;?></td>
                            <td data-name="symbolName"><?=$item['symbolName']?></td>
                            <td data-name="crossMA" <?if ($item['crossMAVal'] == 1):?>class="green-bg"<?endif;?>><?=$item['crossMA']?></td>
                            <td data-name="SAR" <?if ($item['lastSAR']['is_reversal']):?>class="green-bg"<?endif;?>><?=$item['lastSAR']['trend']?> <?if($item['lastSAR']['is_reversal']):?>reversal<?endif;?><br><?=round($item['lastSAR']['sar_value'], 6);?></td>
                            <td data-name="OI" <?if ($item['anomalyOI']):?>class="green-bg"<?endif;?>><?=$item['lastPriceChange']?> / <?=$item['lastOpenInterest'];?></td>
                            <td data-name="profitLvls">
                                <?if ($item['levels']['upper']):?>
                                    <? foreach ($item['levels']['upper'] as $upper):?>
                                        <?=$upper['price']?><!-----><?/*=$upper['volume']*/?><br>
                                    <?endforeach;?>
                                <?else:?>
                                    -
                                <?endif;?>
                            </td>
                        </tr>
                    <?endforeach;?>
                    <?foreach ($strtagyItem['STRATEGIES']['masterDump'] as $item):?>
                        <tr data-val="<?=$item['symbolName']?>">
                            <td data-name="trade" class="red-bg">short<br><?if ($item['secondFilter']):?>Parent approve<?endif;?></td>
                            <td data-name="symbolName"><?=$item['symbolName']?></td>
                            <td data-name="crossMA" <?if ($item['crossMAVal'] == 2):?>class="red-bg"<?endif;?>><?=$item['crossMA']?></td>
                            <td data-name="SAR" <?if ($item['lastSAR']['is_reversal'] == 2):?>class="red-bg"<?endif;?>><?=$item['lastSAR']['trend']?> <?if($item['lastSAR']['is_reversal']):?>reversal<?endif;?><br><?=round($item['lastSAR']['sar_value'], 6);?></td>
                            <td data-name="OI" <?if ($item['anomalyOI']):?>class="red-bg"<?endif;?>><?=$item['lastPriceChange']?> / <?=$item['lastOpenInterest'];?></td>
                            <td data-name="profitLvls">
                                <?if ($item['levels']['lower']):?>
                                    <? foreach ($item['levels']['lower'] as $lower):?>
                                        <?=$lower['price']?><!-----><?/*=$lower['volume']*/?><br>
                                    <?endforeach;?>
                                <?else:?>
                                -
                                <?endif;?>
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

