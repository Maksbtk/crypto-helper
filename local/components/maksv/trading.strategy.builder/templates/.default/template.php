<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
global $USER;
?>

    <section class="page-startegy_table">
        <div class="table-actuality_block">
            <div class="h2">Главная таблица. <?=$arParams['MARKET_CODE']?> api.</div>
            <?/*<button class="button js-update_button">Обновить</button>*/?>
        </div>
        <div class="table-info_block">
            <div class="">Актуальность - <span class="js-timeMark-actuality"><?=$arResult['LUST_TIME_MARK']?></span></div>
            <div class="table_loading_block js-loader_external" style="display: none">
                <?/*<div class="">Обновление</div>*/?>
                <div class="spinner"></div>
            </div>
        </div>
        <div class="table_loading_block error_text" style="display: none;">
        </div>
        
        <div class="mobile-table">
            <table class="iksweb js-strategy_table">
                <thead>
                <tr>
                    <th>Пара 1</th>
                    <th>Пара 2</th>
                    <th>Пара 3</th>
                    <th>Профит, usdt</th>
                    <th>Профит, %</th>
                </tr>
                </thead>
                <tbody class="js-strategy-tbody">
                <?if ($arResult['STRATEGIES'] && is_array($arResult['STRATEGIES']) && count($arResult['STRATEGIES']) >= 1):?>
                    <? foreach ($arResult['STRATEGIES'] as $item):?>
                        <?
                        $profitClass = '';
                        if ($item['profit'] >= 0.6)
                            $profitClass = 'green-bg';
                        elseif ($item['profit'] >= 0.3 && $item['profit'] < 0.6)
                            $profitClass = 'yellow-bg';
                        elseif ($item['profit'] < 3)
                            $profitClass = 'red-bg';
                        ?>
                        <tr data-val="<?=$item['pair1'] . ',' . $item['pair2'] . ',' . $item['pair3']?>">
                            <td data-name="pair1"><?=$item['pair1']?></td>
                            <td data-name="pair2"><?=$item['pair2']?></td>
                            <td data-name="pair3"><?=$item['pair3']?></td>
                            <td data-name="profit" class="<?=$profitClass?>"><?=round($item['profit'], 2); ?></td>
                            <td data-name="profitPercent" class="<?=$profitClass?>"><?=round($item['profitPercent'], 3); ?></td>
                        </tr>
                    <?endforeach;?>
                <?else:?>
                    <tr style="pointer-events: none;">
                        <td colspan="5" class="js-loader_internal">
                            <div class="table_loading_block" style="">
                                <div class="h3">После обновления тут появятся данные</div>
                            </div>
                        </td>
                    </tr>
                <?endif;?>
                </tbody>
            </table>
        </div>
    </section>
    <br>
    <section class="page-startegy_table">
        <div class="table-actuality_block">
            <div class="h2">Рабочая стратегия</div>
            <?/*<button class="button js-update_button">Обновить</button>*/?>
        </div>
        <div class="table-info_block">
            <div class="actuality-block js-work_actuality">Актуальность - <span class="js-timeMark-work_actuality">...</span></div>
            <div class="table_loading_block js-loader_work_external" style="display: none">
                <div class="">Обновление</div>
                <div class="spinner"></div>
            </div>
        </div>

        <div class="work-table_loading_block error_text" style="display: none;">
        </div>

        <div class="mobile-table">
            <table class="iksweb js-work-strategy_table">
                <thead>
                <tr>
                    <th>Пара 1</th>
                    <th>Пара 2</th>
                    <th>Пара 3</th>
                    <th>Профит, usdt</th>
                    <th>Профит, %</th>
                </tr>
                </thead>
                <tbody class="js-work-strategy-tbody">

                <tr style="pointer-events: none;">
                    <td colspan="5" class="js-work-loader_internal">
                        <div class="table_loading_block" style="">
                            <div class="h3">Выберете стратегию в главной таблице</div>
                        </div>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>
        <?
        //echo '<pre>'; var_dump($arResult['STRATEGIES']); echo '</pre>';
        ?>
        <button class="button_fixed-bottom js-go_up-page">В начало</button>
    </section>

    <script>
        var jsArResultStrategyComponent = {
            arParams: <?=CUtil::PhpToJSObject($arParams, false, false, true)?>,
            //symbols: <?//=CUtil::PhpToJSObject($arResult['SYMBOLS'], false, false, true)?>,
            //symbols: <?//=CUtil::PhpToJSObject($arResult['SYMBOLS'], false, false, true)?>,
            //strategies: <?//=CUtil::PhpToJSObject($arResult['STRATEGIES'], false, false, true)?>,
        }
        console.log('jsArResultStrategyComponent')
        console.log(jsArResultStrategyComponent)
    </script>