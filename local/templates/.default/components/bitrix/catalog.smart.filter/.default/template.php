<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Iblock\SectionPropertyTable;

$this->setFrameMode(true);?>

<div class="belleyou-filter-body bx-filter">      
    <div class="belleyou-filter-content">
        <button id="catalogSearchHideButton" class="belleyou-filter-сlose" data-close-filter="">Закрыть</button>
        <form name="<?echo $arResult["FILTER_NAME"]."_form"?>" action="<?echo $arResult["FORM_ACTION"]?>" method="get" class="smartfilter" id="catalogFilterForm">
            <?foreach($arResult["HIDDEN"] as $arItem):?>
            <input type="hidden" name="<?echo $arItem["CONTROL_NAME"]?>" id="<?echo $arItem["CONTROL_ID"]?>" value="<?echo $arItem["HTML_VALUE"]?>" />
            <?endforeach;?>        
            <div class="belleyou-filter-content__inner">
                <div class="belleyou-filter-title">Фильтр</div>
                
                <div class="smartfilter-body">
                    <?$arResult["ITEMS_"] = array_reverse($arResult["ITEMS"], true);
                    foreach($arResult["ITEMS_"] as $key => $arItem){
                        if(
                            empty($arItem["VALUES"])
                            || isset($arItem["PRICE"])
                        )
                            continue;

                        if (
                            $arItem["DISPLAY_TYPE"] === SectionPropertyTable::NUMBERS_WITH_SLIDER
                            && ($arItem["VALUES"]["MAX"]["VALUE"] - $arItem["VALUES"]["MIN"]["VALUE"] <= 0)
                        )
                            continue;
                        ?>

                        <div class="smartfilter-section smartfilter-section-<?if($arItem["NAME"] == "Цвет"){?>color<?}elseif($arItem["NAME"] == "Размер"){?>size<?}else{?>type<?}?>">
                            <header class="smartfilter-section-header">
                                <div class="smartfilter-section-title"><?=$arItem["NAME"]?></div>
                                <p class="smartfilter-selected-items">
                                    <?foreach($arItem["VALUES"] as $val => $ar):?>
                                        <?if($ar["CHECKED"]){?><span class="smartfilter-selected-item"><?=$ar["VALUE"];?></span><?}?>
                                    <?endforeach;?>
                                </p>
                            </header>
                            <div class="smartfilter-section-body">
                                <ul class="smartfilter-list">
                                    <?foreach($arItem["VALUES"] as $val => $ar):?>
                                        <li class="smartfilter-list-item <?if($ar["CHECKED"]){echo 'filter-checked';}?>" id="item11">
                                            <div class="smartfilter-item-checkbox">
                                                <input
                                                    type="checkbox"
                                                    class="input-checkbox"
                                                    value="<? echo $ar["HTML_VALUE"] ?>"
                                                    name="<? echo $ar["CONTROL_NAME"] ?>"
                                                    id="<? echo $ar["CONTROL_ID"] ?>"
                                                    <? echo $ar["CHECKED"]? 'checked="checked"': '' ?>
                                                    onclick="smartFilter.click(this)"
                                                    <?if($arItem["NAME"] == "Цвет"){?>data-type="color"<?}?>
                                                    <?if($arItem["NAME"] == "Размер"){?>data-type="size"<?}?>
                                                    <?if($arItem["NAME"] == "Тип"){?>data-type="type"<?}?>
                                                />

                                                <label class="label-checkbox <? echo $ar["DISABLED"] ? 'disabled': '' ?>" data-role="label_<?=$ar["CONTROL_ID"]?>" for="<? echo $ar["CONTROL_ID"] ?>">
                                                    <?if($arItem["NAME"] == "Цвет" && $arResult['SORTED_COLOR_BY_XMLIDs'][$ar['FACET_VALUE']]){?>
                                                        <span class="smartfilter-item-color-preview with-border" style="background: url('<?=$arResult['SORTED_COLOR_BY_XMLIDs'][$ar['FACET_VALUE']]?>') no-repeat;"></span>
                                                    <?}?>
                                                    <span class="smartfilter-item-label"><?=$ar["VALUE"];?></span>
                                                </label>
                                            </div>
                                                                                        
                                            <span class="smartfilter-item-counter" data-role="count_<?=$ar["CONTROL_ID"]?>"><? echo $ar["ELEMENT_COUNT"]; ?></span>
                                        </li>
                                    <?endforeach;?>
                                </ul>
                            </div>
                        </div>
                    <?}?>                    
                </div>  
            </div>
            <footer class="belleyou-filter-sticky-footer">
                <?php
                $page_wf = $APPLICATION->GetCurPage(false);
                $page_parts = explode('filter',$page_wf);
                $clear_page = $page_parts[0];
                ?>            
            
                <div class="buttons-filter-checked" id="modef">
                    <?if(!empty($page_parts[1])){?>
                        <a href="<?=$clear_page?>" id="del_filter" class="button button-secondary button-clear-filter">Очистить</a>
                    <?}?>
                    
                    <?if(stristr($arResult["FILTER_URL"],"filter/clear/apply/") !== false){
                        $filterUrl = explode("filter/clear/apply/",$arResult["FILTER_URL"])[0];    
                    }else{
                        $filterUrl = $arResult["FILTER_URL"];   
                    }?>
                    
                    <a href="<?echo $filterUrl?>" id="set_filter" class="button button-show-results" data-close-filter>показать <span id="modef_num"><?=(int)($arResult["ELEMENT_COUNT"] ?? 0)?></span> товаров</a>
                </div>
            </footer>
        </form>
    </div>  
</div>

<script type="text/javascript">
    var smartFilter = new JCSmartFilter('<?echo CUtil::JSEscape($arResult["FORM_ACTION"])?>', '<?=CUtil::JSEscape($arParams["FILTER_VIEW_MODE"])?>', <?=CUtil::PhpToJSObject($arResult["JS_FILTER_PARAMS"])?>);

    $(".full-filter__button-show").on("click", function() {
        var color = "";
        var size = "";
        var type = "";
        
        document.cookie = "color_filter=" + color + "; max-age=31536000; path=/";
        document.cookie = "size_filter=" + size + "; max-age=31536000; path=/";
        document.cookie = "type_filter=" + type + "; max-age=31536000; path=/";

        $("#filterMainColorsList").children().each(function(index, item) {
            if ($(item).hasClass("selected")) {
                color += $(item).children("label").text().trim() + ",";
            }
        });

        $("#filterSizes").children().each(function(index, item) {
            if ($(item).hasClass("selected")) {
                size += $(item).children().text().trim() + ",";
            }
        });

        $("#filterTypes").children().each(function(index, item) {
            if ($(item).hasClass("selected")) {
                type += $(item).children().text().trim() + ",";
            }
        });

        document.cookie = "color_filter=" + color + "; max-age=31536000; path=/";
        document.cookie = "size_filter=" + size + "; max-age=31536000; path=/";
        document.cookie = "type_filter=" + type + "; max-age=31536000; path=/";
    });

    jQuery(document).ready($ => {

        $(document).on('submit', '#catalogFilterForm', e => {
            'use strict'
            //return
            e.preventDefault()
            e.stopPropagation()
            const current_url = '<?=$APPLICATION->GetCurPage(false)?>' + ''
            const root = current_url.replace(/\/filter(.*)(\?.*|$)?/, '')
            let get_params = current_url.replace(/^[^\?]*/, '')
            let url = []
            
            const types = ['color', 'size', 'type']
            types.forEach(type => {
                let selected = []
                $('input[data-type=' + type + ']:checked').map((i, input) => {
                    const value = $(input).attr('data-value')
                    selected.push(value)
                })
                if(selected.length > 0){
                    let domain = '_' + type
                    if(type === 'type'){
                        domain = 'dopolnitelnyy_tip'
                    }
                    url.push(domain + '-is-' + selected.join('-or-'))
                }
            })
            const sorting = $('input[name="sorting"]:checked').val()
            if(sorting){
                if(get_params.length)
                    get_params += '&'
                else
                    get_params = '?'
                get_params += 'sort=' + sorting
            }
            const final_url = url.length > 0
                    ? root + 'filter/' + url.join('/') + '/apply/' + get_params
                    : root + get_params
            //debugger
            if(final_url && final_url.length > 20) {
                location.href = final_url
            }
        })

        $(document).on('click', '.filter-apply__button, .filter-apply__button-mobile', e => $('#catalogFilterForm').submit())

        const refreshCount = () => smartFilter.click($('#catalogFilterForm input').get(0))

        refreshCount();
    })
</script>