<?
use Bitrix\Main\Application;
//use Bitrix\Main\Web\Cookie;
use Bitrix\Main\Page\Asset;
//define('NEED_AUTH', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Купить подписку");
$APPLICATION->SetTitle("Купить подписку");

global $USER;
$application = Application::getInstance();
$context = $application->getContext();

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/forms-auth.css");
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/products-list.css");
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-profile.css");
//Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/css/jquery-dropdown.js");

?>
    <div class="profile-wrapper">
	 <?$APPLICATION->IncludeComponent(
	"bitrix:menu",
	"user.sidebar",
	Array(
		"ALLOW_MULTI_SELECT" => "N",
		"CHILD_MENU_TYPE" => "left",
		"DELAY" => "N",
		"MAX_LEVEL" => "1",
		"MENU_CACHE_GET_VARS" => array(0=>"",),
		"MENU_CACHE_TIME" => "1800",
		"MENU_CACHE_TYPE" => "N",
		"MENU_CACHE_USE_GROUPS" => "Y",
		"ROOT_MENU_TYPE" => "left",
		"USE_EXT" => "N"
	)
);?> <?$APPLICATION->IncludeComponent(
	"maksv:catalog.section",
	"user.subscriptions",
	Array(
		"ACTION_VARIABLE" => "action",
		"ADD_PICT_PROP" => "-",
		"ADD_PROPERTIES_TO_BASKET" => "Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"ADD_TO_BASKET_ACTION" => "BUY",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"BACKGROUND_IMAGE" => "-",
		"BASKET_URL" => "/user/cart/",
		"BROWSER_TITLE" => "-",
		"BY_LINK" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "86400",
		"CACHE_TYPE" => "A",
		"COMPATIBLE_MODE" => "Y",
		"CONVERT_CURRENCY" => "Y",
		"CURRENCY_ID" => "RUB",
		"CUSTOM_FILTER" => "{\"CLASS_ID\":\"CondGroup\",\"DATA\":{\"All\":\"AND\",\"True\":\"True\"},\"CHILDREN\":[]}",
		"DETAIL_URL" => "",
		"DISABLE_INIT_JS_IN_COMPONENT" => "N",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"DISPLAY_COMPARE" => "N",
		"DISPLAY_TOP_PAGER" => "N",
		"ELEMENT_SORT_FIELD" => "sort",
		"ELEMENT_SORT_FIELD2" => "id",
		"ELEMENT_SORT_ORDER" => "asc",
		"ELEMENT_SORT_ORDER2" => "desc",
		"ENLARGE_PRODUCT" => "STRICT",
		"FILTER_NAME" => "arrFilter",
		"HIDE_NOT_AVAILABLE" => "N",
		"HIDE_NOT_AVAILABLE_OFFERS" => "N",
		"IBLOCK_ID" => "4",
		"IBLOCK_TYPE" => "catalog",
		"INCLUDE_SUBSECTIONS" => "Y",
		"LABEL_PROP" => array(),
		"LAZY_LOAD" => "Y",
		"LINE_ELEMENT_COUNT" => "3",
		"LOAD_ON_SCROLL" => "Y",
		"MESSAGE_404" => "",
		"MESS_BTN_ADD_TO_BASKET" => "В корзину",
		"MESS_BTN_BUY" => "Купить",
		"MESS_BTN_DETAIL" => "Подробнее",
		"MESS_BTN_LAZY_LOAD" => "Показать ещё",
		"MESS_BTN_SUBSCRIBE" => "Подписаться",
		"MESS_NOT_AVAILABLE" => "Нет в наличии",
		"META_DESCRIPTION" => "-",
		"META_KEYWORDS" => "-",
		"OFFERS_FIELD_CODE" => array(0=>"NAME",1=>"",),
		"OFFERS_LIMIT" => "0",
		"OFFERS_SORT_FIELD2" => "SCALED_PRICE_2",
		"OFFERS_SORT_ORDER" => "asc",
		"OFFERS_SORT_ORDER2" => "asc",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "Подписки",
		"PAGE_ELEMENT_COUNT" => "18",
		"PARTIAL_PRODUCT_PROPERTIES" => "N",
		"PRICE_CODE" => array("base"),
		"PRICE_VAT_INCLUDE" => "Y",
		"PRODUCT_BLOCKS_ORDER" => "price,props,sku,quantityLimit,quantity,buttons",
		"PRODUCT_DISPLAY_MODE" => "Y",
		"PRODUCT_ID_VARIABLE" => "id",
		"PRODUCT_PROPS_VARIABLE" => "prop",
		"PRODUCT_QUANTITY_VARIABLE" => "quantity",
		"PRODUCT_ROW_VARIANTS" => "[{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false}]",
		"PRODUCT_SUBSCRIPTION" => "Y",
		"RCM_PROD_ID" => $_REQUEST["PRODUCT_ID"],
		"RCM_TYPE" => "personal",
		"SECTION_CODE" => "",
		"SECTION_ID" => "",
		"SECTION_ID_VARIABLE" => "SECTION_ID",
		"SECTION_URL" => "",
		"SECTION_USER_FIELDS" => array("",""),
		"SEF_MODE" => "N",
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SHOW_ALL_WO_SECTION" => "Y",
		"SHOW_CLOSE_POPUP" => "N",
		"SHOW_DISCOUNT_PERCENT" => "N",
		"SHOW_FROM_SECTION" => "N",
		"SHOW_MAX_QUANTITY" => "N",
		"SHOW_OLD_PRICE" => "Y",
		"SHOW_PRICE_COUNT" => "",
		"SHOW_SLIDER" => "N",
		"TEMPLATE_THEME" => "blue",
		"USE_ENHANCED_ECOMMERCE" => "N",
		"USE_FILTER" => "Y",
		"USE_MAIN_ELEMENT_SECTION" => "N",
		"USE_PRICE_COUNT" => "N",
		"USE_PRODUCT_QUANTITY" => "N"
	)
);?> <?/*<section class="profile-section profile-section-favorites">
                <div class="profile-user-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" fill="none" viewBox="0 0 48 48"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M31.091 19.85c.748-1.328 1.371-2.527 1.756-3.44 1.868-4.431.021-9.528-4.493-11.608-4.514-2.079-9.047.077-11.037 4.24-3.804-2.61-8.879-2.227-11.677 1.847-2.799 4.073-1.92 9.395 1.913 12.3 1.74 1.317 5.18 3.26 8.419 4.988M32.594 23.5c-.844-4.536-4.703-7.853-9.563-6.952-4.86.902-8.001 5.286-7.344 10.05.528 3.827 3.44 12.807 4.566 16.19.153.462.23.692.382.853.132.14.308.242.496.287.215.05.454.002.93-.096 3.492-.717 12.726-2.684 16.304-4.14 4.454-1.814 6.753-6.724 5.031-11.386-1.721-4.662-6.451-6.343-10.802-4.806z"/></svg>
                    <h2>вы еще ничего не добавили</h2>
                    <p>Добавляйте понравившиеся товары в Избранное, чтобы посмотреть или купить их позже</p>
                    <a href="/catalog/novinki/" class="button">перейти в каталог</a>
                </div>
            </section>*/?>
</div>
 <!-- component-end --> <?//modal mini basket/ выносим максимально ниже чтоб верстка не ломалась?>
<div class="popup popup-add-to-basket">
	<div class="popup__backdrop button-close-popup_" data-close-popup="">
	</div>
	<div class="popup-body">
		<div class="popup-header">
 <button class="button-close-popup button-close-popup_" data-close-popup=""></button>
			<h2 class="popup-title">Добавлено в корзину</h2>
		</div>
		<div class="popup-content">
			<div class="popup-content-inner">
				<div class="popup-product-added-items" style="margin-bottom: 150px;">
				</div>
			</div>
 <footer class="popup-sticky-footer"> <button class="button button-secondary button-close-popup_" data-close-popup="">Продолжить покупки</button> <a href="/cart/" class="button">посмотреть корзину</a> </footer>
		</div>
	</div>
</div>
<br><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>