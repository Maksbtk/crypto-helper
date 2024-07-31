<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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
$this->setFrameMode(true);?>

<section id="catalogFilter" class="belleyou-filter">
  <div class="belleyou-filter__backdrop" data-close-filter=""></div>      
  <div class="belleyou-filter-body">      
    <div class="belleyou-filter-content">
      <button id="catalogSearchHideButton" class="belleyou-filter-сlose" data-close-filter="">Закрыть</button>

      <div class="belleyou-filter-content__inner">
        <h3 class="belleyou-filter-title">Фильтр</h3>
        
        <div class="smartfilter-body">
          <div class="smartfilter-section smartfilter-section-color">
            <header class="smartfilter-section-header">
              <h3 class="smartfilter-section-title">Цвет</h3>
              <p class="smartfilter-selected-items"></p>
            </header>
            <div class="smartfilter-section-body">
              <ul class="smartfilter-list">
                <li class="smartfilter-list-item" id="item1">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" checked data-type="color" data-value="" value="" name="" id="color2">
                    <label class="label-checkbox" data-role="" for="color2">
                      <span class="smartfilter-item-color-preview with-border" style="background-color: #fff;"></span>
                      <span class="smartfilter-item-label">белый</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">5</span>
                </li>
                <li class="smartfilter-list-item" id="item2">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="color" data-value="" value="Y" name="" id="color1">
                    <label class="label-checkbox" data-role="" for="color1">
                      <span class="smartfilter-item-color-preview" style="background-color: #D1B993;"></span>
                      <span class="smartfilter-item-label">бежевый</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">3</span>
                </li>
                <li class="smartfilter-list-item item-amount-zero" id="item3">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="color" data-value="" value="" name="" id="color3">
                    <label class="label-checkbox" data-role="" for="color3">
                      <span class="smartfilter-item-color-preview" style="background-color: #ccc;"></span>
                      <span class="smartfilter-item-label">серый</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">0</span>
                </li>
                <li class="smartfilter-list-item" id="item4">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="color" data-value="" value="Y" name="" id="color4">
                    <label class="label-checkbox" data-role="" for="color4">
                      <span class="smartfilter-item-color-preview" style="background-color: #000;"></span>
                      <span class="smartfilter-item-label">черный</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">2</span>
                </li>
              </ul>
            </div>
          </div>

          <div class="smartfilter-section smartfilter-section-size">
            <header class="smartfilter-section-header">
              <h3 class="smartfilter-section-title">Размер</h3>
              <p class="smartfilter-selected-items">
                <!--span class="smartfilter-selected-item">XS</span-->
              </p>
            </header>
            <div class="smartfilter-section-body">
              <ul class="smartfilter-list">
                <li class="smartfilter-list-item" id="item11">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="size" data-value="" value="" name="" id="size1">
                    <label class="label-checkbox" data-role="" for="size1">
                      <span class="smartfilter-item-label">XS</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">5</span>
                </li>
                <li class="smartfilter-list-item" id="item12">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="size" data-value="" value="" name="" id="size2">
                    <label class="label-checkbox" data-role="" for="size2">
                      <span class="smartfilter-item-label">S</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">2</span>
                </li>
                <li class="smartfilter-list-item" id="item13">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="size" data-value="" value="" name="" id="size3">
                    <label class="label-checkbox" data-role="" for="size3">
                      <span class="smartfilter-item-label">M</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">1</span>
                </li>
                <li class="smartfilter-list-item" id="item14">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="size" data-value="" value="" name="" id="size4">
                    <label class="label-checkbox" data-role="" for="size4">
                      <span class="smartfilter-item-label">L</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">1</span>
                </li>
                <li class="smartfilter-list-item item-amount-zero" id="item15">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="size" data-value="" value="" name="" id="size5">
                    <label class="label-checkbox" data-role="" for="size5">
                      <span class="smartfilter-item-label">XL</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">0</span>
                </li>
              </ul>
            </div>
          </div>

          <div class="smartfilter-section smartfilter-section-type">
            <header class="smartfilter-section-header">
              <h3 class="smartfilter-section-title">Тип</h3>
              <p class="smartfilter-selected-items">
                <!--span class="smartfilter-selected-item">Велосипедки с высокой посадкой</span>
                <span class="smartfilter-selected-item">Легинсы с высокой посадкой</span-->
              </p>
            </header>
            <div class="smartfilter-section-body">
              <ul class="smartfilter-list">
                <li class="smartfilter-list-item" id="item21">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="type" data-value="" value="" name="" id="type21">
                    <label class="label-checkbox" data-role="" for="type21">
                      <span class="smartfilter-item-label">Боди на широких бретелях</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">5</span>
                </li>
                <li class="smartfilter-list-item" id="item22">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="type" data-value="" value="" name="" id="type22">
                    <label class="label-checkbox" data-role="" for="type22">
                      <span class="smartfilter-item-label">Брюки домашние</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">1</span>
                </li>
                <li class="smartfilter-list-item" id="item23">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="type" data-value="" value="" name="" id="type23">
                    <label class="label-checkbox" data-role="" for="type23">
                      <span class="smartfilter-item-label">Велосипедки с высокой посадкой</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">1</span>
                </li>
                <li class="smartfilter-list-item" id="item24">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="type" data-value="" value="" name="" id="type24">
                    <label class="label-checkbox" data-role="" for="type24">
                      <span class="smartfilter-item-label">Кардиган</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">1</span>
                </li>
                <li class="smartfilter-list-item" id="item25">
                  <div class="smartfilter-item-checkbox">
                    <input type="checkbox" class="input-checkbox" data-type="type" data-value="" value="" name="" id="type25">
                    <label class="label-checkbox" data-role="" for="type25">
                      <span class="smartfilter-item-label">Легинсы с высокой посадкой</span>
                    </label>
                  </div>
                  <span class="smartfilter-item-counter" data-role="">1</span>
                </li>
              </ul>
            </div>
          </div>
                        
      </div>  
    </div
    >
    <footer class="belleyou-filter-sticky-footer">
      <button class="button button-close-filter" data-close-filter>Закрыть</button>
      <div class="buttons-filter-checked" style="display: none;">
        <button class="button button-secondary button-clear-filter">Очистить</button>
        <button class="button button-show-results" data-close-filter>показать 46 товаров</button>
      </div>
    </footer>
  </div>  
</section>

<?
global $smart_filter_items_key;
$smart_filter_items_key = 1;
if (!function_exists('printFilterItem')) {
    function printFilterItem($arItem) {
        global $smart_filter_items_key;
        $type = $arItem['CODE'];
        $name = '';
        switch ($type){
            case '_COLOR':
                $type = 'color';
                $name = 'Цвет';
                break;
            case '_SIZE':
                $type = 'size';
                $name = 'Размер';
                break;
            case 'DOPOLNITELNYY_TIP':
                $type = 'type';
                $name = 'Тип';
                break;
        }
        ?>
        
        <!-- new options layout -->
        <div id="filterItem<?=ucfirst($type);?>" class="catalog-filter__filter-box">
            <div class="filter__title-box _filter-toggle">
                <h4 class="filter__title"><?=$name?></h4>
                <svg width="12" height="7" viewBox="0 0 12 7" fill="none">
                    <path d="M11.0209 1.35355C11.2161 1.15829 11.2161 0.841709 11.0209 0.646447C10.8256 0.451184 10.509 0.451184 10.3138 0.646447L11.0209 1.35355ZM6.00065 5.66667L5.6471 6.02022C5.74087 6.11399 5.86804 6.16667 6.00065 6.16667C6.13326 6.16667 6.26044 6.11399 6.35421 6.02022L6.00065 5.66667ZM1.68754 0.646447C1.49228 0.451184 1.17569 0.451184 0.980431 0.646447C0.785169 0.841709 0.785169 1.15829 0.980431 1.35355L1.68754 0.646447ZM10.3138 0.646447L5.6471 5.31311L6.35421 6.02022L11.0209 1.35355L10.3138 0.646447ZM6.3542 5.31311L1.68754 0.646447L0.980431 1.35355L5.6471 6.02022L6.3542 5.31311Z" />
                </svg>
                <p class="filter-selected-list__mobile" data-type="<?=$type?>"></p>
            </div>

            <div class="filter-positions__box">
                <div class="filter-positions__inner">
                    <div class="filter-options__box">
                        <div class="filter-select-all">
                            <a class="filter-select-all__link _check-all">Выбрать все</a>
                        </div>
                        <ul class="filter-list">
                            <?foreach($arItem["VALUES"] as $val => $ar){?>
                                <?php
                                $disabled = $ar['ELEMENT_COUNT'] <= 0;
                                $classes = 'filter-list-item';
                                if($ar['CHECKED']) $classes .= ' selected';
                                if($disabled) $classes .= ' item-amount-zero';
                                ?>
                                <li class="<?=$classes?>" id="item<?=$smart_filter_items_key?>">
                                    <div class="mod1 form-checkbox">
                                        <input
                                                type="checkbox"
                                                class="input-checkbox"
                                                data-type="<?=$type?>"
                                                data-value="<?=$ar['URL_ID']?>"
                                                value="<? echo $ar["HTML_VALUE"] ?>"
                                                name="<? echo $ar["CONTROL_NAME"] ?>"
                                                id="<? echo $ar["CONTROL_ID"] ?>"
                                                <? echo ($ar["DISABLED"] || $disabled) ? 'disabled="disabled"': '' ?>
                                                <? echo $ar["CHECKED"]? 'checked="checked"': '' ?>
                                        />                                        
                                        <label 
                                        data-role="label_<?=$ar["CONTROL_ID"]?>"
                                        class="<? echo $ar["DISABLED"] ? 'filter-disabled': '' ?> check2__label"
                                        for="<? echo $ar["CONTROL_ID"] ?>" style="<?if($type == "color"){?>padding-left: 60px;<?}else{?>padding-left: 30px;<?}?>"
                                        <?if(!$ar["DISABLED"]){?>
                                            onclick="smartFilter.click(this)"
                                            onChange="smartFilter.click(this)"
                                        <?}?>                                        
                                        >
                                            <?if($type == "color"){?>
                                                 <?// $ar["IMG"] = str_replace('http:', 'https:', $ar["IMG"]); ?>
                                                <span class="filter__color-pic" style="background-image: url('<?=$ar["IMG"]?>'); position: absolute; left: 33px"></span>
                                                <?=mb_strtolower($ar["VALUE"]);?>
                                            <?}else{?>
                                                <?=$ar["VALUE"];?>
                                            <?}?>
                                        </label>
                                    </div>
                                    <span class="filter-list-item__counter" data-role="count_<?=$ar["CONTROL_ID"]?>"><?=$ar['ELEMENT_COUNT']?></span>
                                </li>
                                <?$smart_filter_items_key++;
                            }?>
                        </ul>
                    </div>
                </div>
                <div class="filter-apply__box">
                    <a class="button filter-apply__button">Применить</a>
                </div>
            </div>

        </div>
    <?php
    }
}

if (!function_exists('printFilters')) {
    function printFilters($filters){
        foreach ($filters as $key=>$arItem) {
            if (empty($arItem["VALUES"])) continue;

            if (isset($arItem["PRICE"])) {
                continue;
            }

            if ($arItem["CODE"] === 'MINIMUM_PRICE') {
                printFilterPriceItem($arItem);
            } else {
                if (
                    $arItem["DISPLAY_TYPE"] == "A"
                    && ( $arItem["VALUES"]["MAX"]["VALUE"] - $arItem["VALUES"]["MIN"]["VALUE"] <= 0)
                )
                    continue;

                printFilterItem($arItem);
            }
        }
    }
}
if (empty(array_filter($arResult["ITEMS"], function($filter){
    return !empty($filter['VALUES']);
}))) {
    return;
}
?>

<div class="catalog-filter">
    <header class="catalog-filter-header">
        <?$selected_props = 0;
        foreach($arResult["ITEMS"] as $key => $item){
            foreach($item["VALUES"] as $filter_prop){
                if($filter_prop["CHECKED"] == 1){
                    $selected_props++;
                }
            }
        }?>
        <h3 class="catalog-filter-header__title">Фильтр <?=$selected_props?></h3>
        <span class="catalog-filter__button-close">x</span>
    </header>
    <div class="catalog-filter-body">
        <form name="<? echo $arResult["FILTER_NAME"] . "_form" ?>"
              action="<? echo $arResult["FORM_ACTION"] ?>"
              method="get"
              class="smartfilter"
              id="catalogFilterForm"
            <? /*id="catalog-filter-form"*/ ?>>
            <input type="hidden" name="AJAX_CALL" value="Y">
            <input type="hidden" name="bxajaxid" value="<?= $_REQUEST['bxajaxid'] ?>">
            <? foreach ($arResult["HIDDEN"] as $arItem) { ?>
                <input type="hidden"
                       name="<? echo $arItem["CONTROL_NAME"] ?>"
                       id="<? echo $arItem["CONTROL_ID"] ?>"
                       value="<? echo $arItem["HTML_VALUE"] ?>"/>
            <? } ?>
            <div class="catalog-filter__filters-line">

                <?php printFilters($arResult['ITEMS']); ?>

            </div>
            <input
                    style="display: none"
                    type="submit"
                    id="set_filter modef"
                    name="set_filter"
                    value="<?= GetMessage("CT_BCSF_SET_FILTER") ?>"
            />
        </form>
    </div>

    <!-- кнопки только для мобильной версии -->
    <footer class="catalog-filter-footer">
        <a href="<?=$clear_page?>" class="button_secondary catalog-filter__button filter-clear__button-mobile">Очистить</a>
        <a class="button catalog-filter__button filter-apply__button-mobile">Применить</a>
    </footer>
    <!--end кнопки только для мобильной версии -->
</div>

<?php
$page_wf = $APPLICATION->GetCurPage(false);
$page_parts = explode('filter',$page_wf);
$clear_page = $page_parts[0];
?>

<div class="catalog-filter__selected-filters" style="<?if(strpos($_SERVER['REQUEST_URI'], '/filter/') !== false){?>height: 35px;<?}else{?>display: none;<?}?>">
    <a href="<?=$clear_page?>" class="filter-clear-link _clear-all">Очистить</a>
    <ul class="filter-selected-list"></ul>
    <a href="<?= $arResult["FILTER_URL"]?>" class="filter-full-link _filter-full"></a>
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

        refreshCount()
    })
</script>
<style>
    .catalog-filter__filtered-items-count, .catalog-filter__total-items {
        display: none;
    }
</style>
