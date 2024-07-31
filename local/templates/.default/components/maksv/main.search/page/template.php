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

$arElements = $arResult['MAIN_SEARCH']['search']['elementsId'];
?>
<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/page-catalog.css">

<header class="page-catalog-header no-scroll">
    <h1 class="page-catalog-title">Результаты поиска <sup class="page-catalog-title__items-counter"></sup></h1>
    <div class="page-catalog-search-text">
        <? if (($arResult['MAIN_SEARCH']['firstNotFound']) && (count($arResult['MAIN_SEARCH']['search']['speller']) > 0) && ($arResult['MAIN_SEARCH']['search']['countSuggestion'] > 0)):?>
            <?// если опечатка в запросе?>
            <p>Результаты поиска по вашему запросу “<?=$arResult['MAIN_SEARCH']['firstNotFound']?>”</p>
            <p>Возможно вы имели в виду “<?=$arResult['MAIN_SEARCH']['search']['speller'][0]?>”<br> Если нет, скорректируйте свой поисковый запрос.</p>
            <?$arElements = $arResult['MAIN_SEARCH']['search']['elementsId'];?>
        <? elseif ($arResult['MAIN_SEARCH']['search']['countSuggestion'] == 0):?>
            <?// если запрос не дал результатов
            $arElements = $arResult['MAIN_SEARCH']['recommendationIds'];
            ?>
            <p>Ничего не нашли по запросу “<?=$arResult['MAIN_SEARCH']['firstNotFound']?>”. <br>Скорректируйте свой поисковый запрос или посмотрите наши рекомендации.</p>
        <? else: ?>
            <?
            $arElements = $arResult['MAIN_SEARCH']['search']['elementsId'];
            $arElements = \Belleyou\BeautyBox::productArWithoutBox($arElements);
            ?>
            <?/*<p>Найдено <span class="js_search-page-count"><?=intval($arResult['MAIN_SEARCH']['search']['countSuggestion'])?></span> <?=num_word(intval($arResult['MAIN_SEARCH']['search']['countSuggestion']), array('товар', 'товара', 'товаров'), false)?> по запросу “<?=$_GET['q']?>”</p>*/?>
            <p>Результаты поиска по запросу “<?=$_GET['q']?>”</p>
        <? endif; ?>
    </div>
</header>

<?php
$arElements = \Belleyou\BeautyBox::productArWithoutBox($arElements);

global $searchFilter;
$searchFilter['ID'] = $arElements;
$searchFilter['!PREVIEW_PICTURE'] = false;

if (!empty($searchFilter['ID']) && is_array($searchFilter['ID']))
{

    $sort = '';
    $order = '';
   /* switch ($_REQUEST['sort']) {
        case 'price-asc':
            $sort  = 'PROPERTY_MINIMUM_PRICE';
            $order = 'asс';

            break;
        case 'price-desc':
            $sort  = 'PROPERTY_MINIMUM_PRICE';
            $order = 'desc';

            break;
        case 'new':
            $sort  = 'propertysort_new';
            $order = 'desc,nulls';

            break;
        default:
            $sort  = 'SORT';
            $order = 'asc';
    }*/

    $APPLICATION->IncludeComponent(
        "belleyou:catalog.section",
        "",
        array(
            "IBLOCK_TYPE" => "catalog",
            "IBLOCK_ID" => "2",
            "TEMPLATE_THEME" => "site",
            //??
            //"HIDE_NOT_AVAILABLE" => "Y",
            "HIDE_NOT_AVAILABLE" => "N",

            "IS_SEARCH_PAGE" => "Y",
            "BASKET_URL" => "/personal/cart/",
            "ACTION_VARIABLE" => "action",
            "PRODUCT_ID_VARIABLE" => "id",
            "SECTION_ID_VARIABLE" => "SECTION_ID",
            "PRODUCT_QUANTITY_VARIABLE" => "quantity",
            "PRODUCT_PROPS_VARIABLE" => "prop",
            "SEF_MODE" => "Y",
            "SEF_FOLDER" => "/catalog/",
            "AJAX_MODE" => "N",
            "AJAX_OPTION_JUMP" => "N",
            "AJAX_OPTION_STYLE" => "Y",
            "AJAX_OPTION_HISTORY" => "N",
            "CACHE_TYPE" => "A",
            "CACHE_TIME" => "86400",
            "CACHE_FILTER" => "Y",
            "CACHE_GROUPS" => "Y",
            "SET_TITLE" => "Y",
            "ADD_SECTION_CHAIN" => "Y",
            "ADD_ELEMENT_CHAIN" => "Y",
            "SET_STATUS_404" => "Y",
            "DETAIL_DISPLAY_NAME" => "N",
            "USE_ELEMENT_COUNTER" => "N",
            "USE_FILTER" => "Y",
            "FILTER_NAME" => "searchFilter",
            "FILTER_VIEW_MODE" => "HORIZONTAL",
            "USE_COMPARE" => "N",
            "PRICE_CODE" => array(
                0 => "Розничная цена",
            ),
            "USE_PRICE_COUNT" => "N",
            "SHOW_PRICE_COUNT" => "1",
            "PRICE_VAT_INCLUDE" => "Y",
            "PRICE_VAT_SHOW_VALUE" => "N",
            "PRODUCT_PROPERTIES" => "",
            "USE_PRODUCT_QUANTITY" => "N",
            "CONVERT_CURRENCY" => "N",
            "QUANTITY_FLOAT" => "N",
            "OFFERS_CART_PROPERTIES" => array(
                0 => "RUSSKIY_TSVET",
                1 => "RAZMER",
            ),
            "SHOW_TOP_ELEMENTS" => "N",
            "SECTION_COUNT_ELEMENTS" => "N",
            "SECTION_TOP_DEPTH" => "5",
            "SECTIONS_VIEW_MODE" => "TILE",
            "SECTIONS_SHOW_PARENT_NAME" => "N",
            "PAGE_ELEMENT_COUNT" => "16",
            "LINE_ELEMENT_COUNT" => "4",
            "ELEMENT_SORT_ARRAY" => array(
                "CATALOG_AVAILABLE" => "DESC",
                "PROPERTY_soon" => "DESC",
                "SORT" => "ASC",
            ),
            "ELEMENT_SORT_FIELD" => $sort,
            "ELEMENT_SORT_ORDER" => $order,
            "ELEMENT_SORT_FIELD2" => "PROPERTY_new",
            "ELEMENT_SORT_ORDER2" => "desc,nulls",
            "LIST_PROPERTY_CODE" => array(
                0 => "NEWPRODUCT",
                1 => "SALELEADER",
                2 => "SPECIALOFFER",
                3 => "",
            ),
            "INCLUDE_SUBSECTIONS" => "Y",
            "LIST_META_KEYWORDS" => "-",
            "LIST_META_DESCRIPTION" => "-",
            "LIST_BROWSER_TITLE" => "-",
            "LIST_OFFERS_FIELD_CODE" => array(
                0 => "NAME",
                1 => "PREVIEW_PICTURE",
                2 => "DETAIL_PICTURE",
                3 => "",
            ),
            "LIST_OFFERS_PROPERTY_CODE" => array(
                0 => "RUSSKIY_TSVET",
                1 => "ROSSIYSKIY_RAZMER",
            ),
            "SKU_PROPS" => array(
                0 => "RUSSKIY_TSVET",
                1 => "RAZMER",
            ),
            "LIST_OFFERS_LIMIT" => "0",
            "SECTION_BACKGROUND_IMAGE" => "-",
            "DETAIL_PROPERTY_CODE" => array(
                0 => "RUSSKIY_TSVET",
                1 => "RAZMER",
            ),
            "DETAIL_META_KEYWORDS" => "-",
            "DETAIL_META_DESCRIPTION" => "-",
            "DETAIL_BROWSER_TITLE" => "-",
            "DETAIL_OFFERS_FIELD_CODE" => array(
                0 => "NAME",
                1 => "",
            ),
            "DETAIL_OFFERS_PROPERTY_CODE" => array(
                0 => "MORE_PHOTO",
                1 => "RUSSKIY_TSVET",
            ),
            "DETAIL_BACKGROUND_IMAGE" => "-",
            "LINK_IBLOCK_TYPE" => "",
            "LINK_IBLOCK_ID" => "",
            "LINK_PROPERTY_SID" => "",
            "LINK_ELEMENTS_URL" => "link.php?PARENT_ELEMENT_ID=#ELEMENT_ID#",
            "USE_ALSO_BUY" => "N",
            "ALSO_BUY_ELEMENT_COUNT" => "",
            "ALSO_BUY_MIN_BUYES" => "",
            "OFFERS_SORT_FIELD" => "sort",
            "OFFERS_SORT_FIELD2" => "SCALED_PRICE_2",
            "OFFERS_SORT_ORDER" => "asc",
            "OFFERS_SORT_ORDER2" => "asc",
            "PAGER_TEMPLATE" => "catalog",
            "DISPLAY_TOP_PAGER" => "N",

            //??
            "DISPLAY_BOTTOM_PAGER" => "Y",
            //"DISPLAY_BOTTOM_PAGER" => "N",

            "PAGER_TITLE" => "Товары",
            "PAGER_SHOW_ALWAYS" => "N",
            "PAGER_DESC_NUMBERING" => "N",
            "PAGER_DESC_NUMBERING_CACHE_TIME" => "86400",
            "PAGER_SHOW_ALL" => "N",
            "ADD_PICT_PROP" => "MORE_PHOTO",
            "LABEL_PROP" => array(
            ),
            "PRODUCT_DISPLAY_MODE" => "N",
            "OFFER_ADD_PICT_PROP" => "MORE_PHOTO",
            "OFFER_TREE_PROPS" => array(
                0 => "RUSSKIY_TSVET",
                1 => "RAZMER",
            ),
            "SHOW_DISCOUNT_PERCENT" => "Y",
            "SHOW_OLD_PRICE" => "Y",
            "MESS_BTN_BUY" => "Купить",
            "MESS_BTN_ADD_TO_BASKET" => "В корзину",
            "MESS_BTN_COMPARE" => "Сравнение",
            "MESS_BTN_DETAIL" => "Подробнее",
            "MESS_NOT_AVAILABLE" => "Нет в наличии",
            "DETAIL_USE_VOTE_RATING" => "N",
            "DETAIL_VOTE_DISPLAY_AS_RATING" => "rating",
            "DETAIL_USE_COMMENTS" => "Y",
            "DETAIL_BLOG_USE" => "Y",
            "DETAIL_VK_USE" => "N",
            "DETAIL_FB_USE" => "N",
            "AJAX_OPTION_ADDITIONAL" => "",
            "USE_STORE" => "N",
            "BIG_DATA_RCM_TYPE" => "personal",
            "FIELDS" => array(
                0 => "STORE",
                1 => "SCHEDULE",
            ),
            "USE_MIN_AMOUNT" => "N",
            "STORE_PATH" => "/store/#store_id#",
            "MAIN_TITLE" => "Наличие на складах",
            "MIN_AMOUNT" => "0",
            "DETAIL_BRAND_USE" => "N",
            "DETAIL_BRAND_PROP_CODE" => "BRAND_REF",
            "COMPATIBLE_MODE" => "N",
            "SIDEBAR_SECTION_SHOW" => "N",
            "SIDEBAR_DETAIL_SHOW" => "N",
            "SIDEBAR_PATH" => "",
            "COMPONENT_TEMPLATE" => "main",
            "HIDE_NOT_AVAILABLE_OFFERS" => "N",
            "COMMON_SHOW_CLOSE_POPUP" => "N",
            "PRODUCT_SUBSCRIPTION" => "Y",
            "DISCOUNT_PERCENT_POSITION" => "bottom-right",
            "SHOW_MAX_QUANTITY" => "N",
            "MESS_NOT_AVAILABLE_SERVICE" => "Недоступно",
            "MESS_BTN_SUBSCRIBE" => "Сообщить о поступлении",
            "USER_CONSENT" => "N",
            "USER_CONSENT_ID" => "0",
            "USER_CONSENT_IS_CHECKED" => "Y",
            "USER_CONSENT_IS_LOADED" => "N",
            "USE_MAIN_ELEMENT_SECTION" => "N",
            "DETAIL_STRICT_SECTION_CHECK" => "N",
            "SET_LAST_MODIFIED" => "N",
            "ADD_SECTIONS_CHAIN" => "Y",
            "USE_SALE_BESTSELLERS" => "N",
            "FILTER_HIDE_ON_MOBILE" => "N",
            "INSTANT_RELOAD" => "N",
            "ADD_PROPERTIES_TO_BASKET" => "Y",
            "PARTIAL_PRODUCT_PROPERTIES" => "N",
            "USE_COMMON_SETTINGS_BASKET_POPUP" => "N",
            "COMMON_ADD_TO_BASKET_ACTION" => "ADD",
            "TOP_ADD_TO_BASKET_ACTION" => "ADD",
            "SECTION_ADD_TO_BASKET_ACTION" => "ADD",
            "DETAIL_ADD_TO_BASKET_ACTION" => array(
                0 => "ADD",
            ),
            "DETAIL_ADD_TO_BASKET_ACTION_PRIMARY" => array(
            ),
            "SEARCH_PAGE_RESULT_COUNT" => "50",
            "SEARCH_RESTART" => "N",
            "SEARCH_NO_WORD_LOGIC" => "Y",
            "SEARCH_USE_LANGUAGE_GUESS" => "Y",
            "SEARCH_CHECK_DATES" => "Y",
            "SEARCH_USE_SEARCH_RESULT_ORDER" => "N",
            "SECTIONS_HIDE_SECTION_NAME" => "Y",
            "LIST_PRODUCT_BLOCKS_ORDER" => "price,props,sku,quantityLimit,quantity,buttons",
            "LIST_PRODUCT_ROW_VARIANTS" => "[{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false}]",
            "LIST_ENLARGE_PRODUCT" => "STRICT",
            "LIST_SHOW_SLIDER" => "N",
            "DETAIL_SET_CANONICAL_URL" => "N",
            "DETAIL_CHECK_SECTION_ID_VARIABLE" => "N",
            "SHOW_DEACTIVATED" => "N",
            "SHOW_SKU_DESCRIPTION" => "N",
            "DETAIL_MAIN_BLOCK_OFFERS_PROPERTY_CODE" => array(
                0 => "RAZMER",
                1 => "RUSSKIY_TSVET",
            ),
            "DETAIL_BLOG_URL" => "catalog_comments",
            "DETAIL_BLOG_EMAIL_NOTIFY" => "N",
            "DETAIL_IMAGE_RESOLUTION" => "16by9",
            "DETAIL_PRODUCT_INFO_BLOCK_ORDER" => "sku,props",
            "DETAIL_PRODUCT_PAY_BLOCK_ORDER" => "rating,price,priceRanges,quantityLimit,quantity,buttons",
            "DETAIL_SHOW_SLIDER" => "N",
            "DETAIL_DETAIL_PICTURE_MODE" => array(
                0 => "POPUP",
                1 => "MAGNIFIER",
            ),
            "DETAIL_ADD_DETAIL_TO_SLIDER" => "N",
            "DETAIL_DISPLAY_PREVIEW_TEXT_MODE" => "E",
            "MESS_PRICE_RANGES_TITLE" => "Цены",
            "MESS_DESCRIPTION_TAB" => "Описание",
            "MESS_PROPERTIES_TAB" => "Характеристики",
            "MESS_COMMENTS_TAB" => "Комментарии",
            "DETAIL_SHOW_POPULAR" => "Y",
            "DETAIL_SHOW_VIEWED" => "Y",
            "USE_GIFTS_DETAIL" => "N",
            "USE_GIFTS_SECTION" => "N",
            "USE_GIFTS_MAIN_PR_SECTION_LIST" => "N",
            "USE_BIG_DATA" => "N",
            "USE_ENHANCED_ECOMMERCE" => "N",
            "PAGER_BASE_LINK_ENABLE" => "N",
            "LAZY_LOAD" => "Y",
            "MESS_BTN_LAZY_LOAD" => "Показать ещё",
            "LOAD_ON_SCROLL" => "N",
            "SHOW_404" => "N",
            "MESSAGE_404" => "",
            "DISABLE_INIT_JS_IN_COMPONENT" => "N",
            "DETAIL_SET_VIEWED_IN_COMPONENT" => "N",
            "SEF_URL_TEMPLATES" => array(
                "sections" => "",
                "section" => "#SECTION_CODE_PATH#/",
                "element" => "#SECTION_CODE_PATH#/#ELEMENT_CODE#/",
                "compare" => "compare/",
                "smart_filter" => "#SECTION_CODE_PATH#/filter/#SMART_FILTER_PATH#/apply/",
            )
        ),
        array('HIDE_ICONS' => 'Y')
    );
}?>

