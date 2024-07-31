<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
//require_once ($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/belleyou/autoload.php');

use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\ProductTable;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 *
 *  _________________________________________________________________________
 * |	Attention!
 * |	The following comments are for system use
 * |	and are required for the component to work correctly in ajax mode:
 * |	<!-- items-container -->
 * |	<!-- pagination-container -->
 * |	<!-- component-end -->
 */

$this->setFrameMode(true);

if (!empty($arResult['NAV_RESULT']))
{
	$navParams =  array(
		'NavPageCount' => $arResult['NAV_RESULT']->NavPageCount,
		'NavPageNomer' => $arResult['NAV_RESULT']->NavPageNomer,
		'NavNum' => $arResult['NAV_RESULT']->NavNum
	);
}
else
{
	$navParams = array(
		'NavPageCount' => 1,
		'NavPageNomer' => 1,
		'NavNum' => $this->randString()
	);
}

$showTopPager = false;
$showBottomPager = false;
$showLazyLoad = false;

if ($arParams['PAGE_ELEMENT_COUNT'] > 0 && $navParams['NavPageCount'] > 1)
{
	$showTopPager = $arParams['DISPLAY_TOP_PAGER'];
	$showBottomPager = $arParams['DISPLAY_BOTTOM_PAGER'];
	$showLazyLoad = $arParams['LAZY_LOAD'] === 'Y' && $navParams['NavPageNomer'] != $navParams['NavPageCount'];
}

$templateLibrary = array('popup', 'ajax', 'fx');
$currencyList = '';

if (!empty($arResult['CURRENCIES']))
{
	$templateLibrary[] = 'currency';
	$currencyList = CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true);
}

$templateData = array(
	'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'],
	'TEMPLATE_LIBRARY' => $templateLibrary,
	'CURRENCIES' => $currencyList,
	'USE_PAGINATION_CONTAINER' => $showTopPager || $showBottomPager,
);
unset($currencyList, $templateLibrary);

$elementEdit = CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_EDIT');
$elementDelete = CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_DELETE');
$elementDeleteParams = array('CONFIRM' => GetMessage('CT_BCS_TPL_ELEMENT_DELETE_CONFIRM'));

$positionClassMap = array(
	'left' => 'product-item-label-left',
	'center' => 'product-item-label-center',
	'right' => 'product-item-label-right',
	'bottom' => 'product-item-label-bottom',
	'middle' => 'product-item-label-middle',
	'top' => 'product-item-label-top'
);

$discountPositionClass = '';
if ($arParams['SHOW_DISCOUNT_PERCENT'] === 'Y' && !empty($arParams['DISCOUNT_PERCENT_POSITION']))
{
	foreach (explode('-', $arParams['DISCOUNT_PERCENT_POSITION']) as $pos)
	{
		$discountPositionClass .= isset($positionClassMap[$pos]) ? ' '.$positionClassMap[$pos] : '';
	}
}

$labelPositionClass = '';
if (!empty($arParams['LABEL_PROP_POSITION']))
{
	foreach (explode('-', $arParams['LABEL_PROP_POSITION']) as $pos)
	{
		$labelPositionClass .= isset($positionClassMap[$pos]) ? ' '.$positionClassMap[$pos] : '';
	}
}

$arParams['~MESS_BTN_BUY'] = ($arParams['~MESS_BTN_BUY'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_BTN_BUY');
$arParams['~MESS_BTN_DETAIL'] = ($arParams['~MESS_BTN_DETAIL'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_BTN_DETAIL');
$arParams['~MESS_BTN_COMPARE'] = ($arParams['~MESS_BTN_COMPARE'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_BTN_COMPARE');
$arParams['~MESS_BTN_SUBSCRIBE'] = ($arParams['~MESS_BTN_SUBSCRIBE'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_BTN_SUBSCRIBE');
$arParams['~MESS_BTN_ADD_TO_BASKET'] = ($arParams['~MESS_BTN_ADD_TO_BASKET'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_BTN_ADD_TO_BASKET');
$arParams['~MESS_NOT_AVAILABLE'] = ($arParams['~MESS_NOT_AVAILABLE'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_PRODUCT_NOT_AVAILABLE');
$arParams['~MESS_NOT_AVAILABLE_SERVICE'] = ($arParams['~MESS_NOT_AVAILABLE_SERVICE'] ?? '') ?: Loc::getMessage('CP_BCS_TPL_MESS_PRODUCT_NOT_AVAILABLE_SERVICE');
$arParams['~MESS_SHOW_MAX_QUANTITY'] = ($arParams['~MESS_SHOW_MAX_QUANTITY'] ?? '') ?: Loc::getMessage('CT_BCS_CATALOG_SHOW_MAX_QUANTITY');
$arParams['~MESS_RELATIVE_QUANTITY_MANY'] = ($arParams['~MESS_RELATIVE_QUANTITY_MANY'] ?? '') ?: Loc::getMessage('CT_BCS_CATALOG_RELATIVE_QUANTITY_MANY');
$arParams['MESS_RELATIVE_QUANTITY_MANY'] = ($arParams['MESS_RELATIVE_QUANTITY_MANY'] ?? '') ?: Loc::getMessage('CT_BCS_CATALOG_RELATIVE_QUANTITY_MANY');
$arParams['~MESS_RELATIVE_QUANTITY_FEW'] = ($arParams['~MESS_RELATIVE_QUANTITY_FEW'] ?? '') ?: Loc::getMessage('CT_BCS_CATALOG_RELATIVE_QUANTITY_FEW');
$arParams['MESS_RELATIVE_QUANTITY_FEW'] = ($arParams['MESS_RELATIVE_QUANTITY_FEW'] ?? '') ?: Loc::getMessage('CT_BCS_CATALOG_RELATIVE_QUANTITY_FEW');

$arParams['MESS_BTN_LAZY_LOAD'] = $arParams['MESS_BTN_LAZY_LOAD'] ?: Loc::getMessage('CT_BCS_CATALOG_MESS_BTN_LAZY_LOAD');

$obName = 'ob'.preg_replace('/[^a-zA-Z0-9_]/', 'x', $this->GetEditAreaId($navParams['NavNum']));
$containerName = 'container-'.$navParams['NavNum'];

$_SESSION["KKEY"] = 0;

//Open Graph микроразметка
$assets = \Bitrix\Main\Page\Asset::getInstance();
$sectionValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($arResult['IBLOCK_ID'], $arResult["ID"]);
$sectionDescription = ($sectionValues->getValues())['SECTION_META_DESCRIPTION'];

$assets->addString('<meta property="og:site_name" content="Интернет-магазин belle you" />');
$assets->addString('<meta property="og:type" content="product.group"/>');
$assets->addString('<meta property="og:title" content="' . $arResult["NAME"] . '"/>');
$assets->addString('<meta property="og:image" content="https://belleyou.ru/images/belleyouOG.png"/>');
$assets->addString('<meta property="og:url" content="https://belleyou.ru' . $arResult['SECTION_PAGE_URL'] . '"/>');
$assets->addString('<meta property="og:description" content="' . $sectionDescription . '"/>');
?>

<!--Блок капсулы-->
<?
$capsule = false;
$ar_capsule_result = CIBlockSection::GetList(Array("SORT" => "ASC"), Array("IBLOCK_ID" => 2, "ID" => $arResult['ID']),false, Array("UF_CAPSULE_TITLE","UF_CAPSULE_DESCR","UF_CAPSULE_IMAGE","UF_CAPSULE_IMAGE_M")); 
if($capsule_res = $ar_capsule_result->GetNext()){
    if(!empty($capsule_res["UF_CAPSULE_IMAGE_M"])){
        $capsule["TITLE"] = $capsule_res["UF_CAPSULE_TITLE"];
        $capsule["DESCRIPTION"] = $capsule_res["UF_CAPSULE_DESCR"];
        $capsule["IMAGE_X1"] = CFile::GetPath($capsule_res["UF_CAPSULE_IMAGE_M"]);
        $capsule["IMAGE_X2"] = CFile::GetPath($capsule_res["UF_CAPSULE_IMAGE"]);
    }
}?>

<?if(!empty($capsule)){?>
    <div class="page-catalog-capsule-intro">      
        <picture>
            <source media="(max-width: 1023px)" srcset="<?=$capsule["IMAGE_X1"]?>">
            <img class="page-catalog-capsule-pic" src="<?=$capsule["IMAGE_X2"]?>" width="916" height="612" alt="<?=$capsule["TITLE"]?>">
        </picture>
        <h2 class="page-catalog-capsule-title"><?=$capsule["TITLE"]?></h2>      
        <p class="page-catalog-capsule-desc"><?=$capsule["DESCRIPTION"]?></p>
    </div>
<?}?>

<div data-entity="<?=$containerName?>" class="belleyou-content-layout">
	<?
	if (!empty($arResult['ITEMS']) && !empty($arResult['ITEM_ROWS']))
	{
        $generalParams = [
			'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
			'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
			'SHOW_MAX_QUANTITY' => $arParams['SHOW_MAX_QUANTITY'],
			'RELATIVE_QUANTITY_FACTOR' => $arParams['RELATIVE_QUANTITY_FACTOR'],
			'MESS_SHOW_MAX_QUANTITY' => $arParams['~MESS_SHOW_MAX_QUANTITY'],
			'MESS_RELATIVE_QUANTITY_MANY' => $arParams['~MESS_RELATIVE_QUANTITY_MANY'],
			'MESS_RELATIVE_QUANTITY_FEW' => $arParams['~MESS_RELATIVE_QUANTITY_FEW'],
			'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
			'USE_PRODUCT_QUANTITY' => $arParams['USE_PRODUCT_QUANTITY'],
			'PRODUCT_QUANTITY_VARIABLE' => $arParams['PRODUCT_QUANTITY_VARIABLE'],
			'ADD_TO_BASKET_ACTION' => $arParams['ADD_TO_BASKET_ACTION'],
			'ADD_PROPERTIES_TO_BASKET' => $arParams['ADD_PROPERTIES_TO_BASKET'],
			'PRODUCT_PROPS_VARIABLE' => $arParams['PRODUCT_PROPS_VARIABLE'],
			'SHOW_CLOSE_POPUP' => $arParams['SHOW_CLOSE_POPUP'],
			'DISPLAY_COMPARE' => $arParams['DISPLAY_COMPARE'],
			'COMPARE_PATH' => $arParams['COMPARE_PATH'],
			'COMPARE_NAME' => $arParams['COMPARE_NAME'],
			'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
			'PRODUCT_BLOCKS_ORDER' => $arParams['PRODUCT_BLOCKS_ORDER'],
			'LABEL_POSITION_CLASS' => $labelPositionClass,
			'DISCOUNT_POSITION_CLASS' => $discountPositionClass,
			'SLIDER_INTERVAL' => $arParams['SLIDER_INTERVAL'],
			'SLIDER_PROGRESS' => $arParams['SLIDER_PROGRESS'],
			'~BASKET_URL' => $arParams['~BASKET_URL'],
			'~ADD_URL_TEMPLATE' => $arResult['~ADD_URL_TEMPLATE'],
			'~BUY_URL_TEMPLATE' => $arResult['~BUY_URL_TEMPLATE'],
			'~COMPARE_URL_TEMPLATE' => $arResult['~COMPARE_URL_TEMPLATE'],
			'~COMPARE_DELETE_URL_TEMPLATE' => $arResult['~COMPARE_DELETE_URL_TEMPLATE'],
			'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'],
			'USE_ENHANCED_ECOMMERCE' => $arParams['USE_ENHANCED_ECOMMERCE'],
			'DATA_LAYER_NAME' => $arParams['DATA_LAYER_NAME'],
			'BRAND_PROPERTY' => $arParams['BRAND_PROPERTY'],
			'MESS_BTN_BUY' => $arParams['~MESS_BTN_BUY'],
			'MESS_BTN_DETAIL' => $arParams['~MESS_BTN_DETAIL'],
			'MESS_BTN_COMPARE' => $arParams['~MESS_BTN_COMPARE'],
			'MESS_BTN_SUBSCRIBE' => $arParams['~MESS_BTN_SUBSCRIBE'],
			'MESS_BTN_ADD_TO_BASKET' => $arParams['~MESS_BTN_ADD_TO_BASKET'],
		];

		$areaIds = [];
		$itemParameters = [];

        $arProdIds = [];
        foreach ($arResult['ITEMS'] as $item){
            $arProdIds[] = $item['ID'];
        }
        $colorAssistOb = new Belleyou\ColorAssistant();
        $sortedColors = $colorAssistOb->getСolorList($arProdIds);

        foreach ($arResult['ITEMS'] as $item)
		{
            $uniqueId = $item['ID'].'_'.md5($this->randString().$component->getAction());
			$areaIds[$item['ID']] = $this->GetEditAreaId($uniqueId);
			$this->AddEditAction($uniqueId, $item['EDIT_LINK'], $elementEdit);
			$this->AddDeleteAction($uniqueId, $item['DELETE_LINK'], $elementDelete, $elementDeleteParams);

			$itemParameters[$item['ID']] = [
                'SORTED_COLOR' => $sortedColors[$item['PROPERTIES']['CML2_ARTICLE']['VALUE']],
                'SKU_PROPS' => $arResult['SKU_PROPS'][$item['IBLOCK_ID']],
				'MESS_NOT_AVAILABLE' => ($arResult['MODULES']['catalog'] && $item['PRODUCT']['TYPE'] === ProductTable::TYPE_SERVICE
					? $arParams['~MESS_NOT_AVAILABLE_SERVICE']
					: $arParams['~MESS_NOT_AVAILABLE']
				),
			];
        }
		?>
		<!-- items-container -->
        
        <style type="text/css">
            /*.products-list__full{margin-bottom: 0px !important;}*/
        </style>

        <ul class="products-list products-list__full" data-entity="items-row">
            <?$b_key = 0;
            foreach ($arResult['ITEMS'] as $item){
                $b_key++;?>

                <?
                $bannerImgUrl = '/images/catalog_sale.jpg';
                if($b_key == 6 && $arParams['IS_NOT_SALE_SECTION'] && file_exists($_SERVER["DOCUMENT_ROOT"] . $bannerImgUrl)){?>
                    <li class="product-item product-banner">                      
                        <a href="/catalog/sale/" class="product-banner-inner" style="background-image: url('<?=$bannerImgUrl?>'); background-size: cover;background-repeat: no-repeat;">
                        </a> 
                    </li>                
                <?}?>
                       
			    <li class="product-item" data-entity="item">
                    <?
                    $APPLICATION->IncludeComponent(
                        'bitrix:catalog.item',
                        '',
                        array(
                            'RESULT' => array(
                                'ITEM' => $item,
                                'AREA_ID' => $areaIds[$item['ID']],
                                'TYPE' => 'line',
                                'BIG_LABEL' => 'N',
                                'BIG_DISCOUNT_PERCENT' => 'N',
                                'BIG_BUTTONS' => 'N',
                                'SCALABLE' => 'N',
                            ),
                            'PARAMS' => $generalParams + $itemParameters[$item['ID']]
                            + array(
                                'SKU_PROPS' => $arResult['SKU_PROPS'][$item['IBLOCK_ID']],
                                'CURRENT_SECTION' => (int)$arResult['ID'],
                                #'PAGE_BANNER' => $banner,
                            ),
                        ),
                        $component,
                        array('HIDE_ICONS' => 'Y')
                    );
                    ?>
                </li>            
		    <?}?>
        </ul>
        
		<?unset($rowItems);
		unset($itemParameters);
		unset($areaIds);
		unset($generalParams);?>

		<!-- items-container -->
    <?}else{
        if (strpos($curr_page, 'filter') !== false) {
            $newUrl = explode("filter",$curr_page);
            LocalRedirect($newUrl[0], false, '301 Moved permanently');
        }    
    }?>
</div>

<div class="page-catalog-navigation" style="margin-top: 60px;">
    <?if ($showLazyLoad){?>    
        <div class="catalog-show-more bx-<?=$arParams['TEMPLATE_THEME']?>" id="js-show-more-container">
            <div class="button button-secondary button-show-more" id="js-show-more" data-use="show-more-<?=$navParams['NavNum']?>">Показать еще</div>
        </div>
    <?}?>
    
    <?if ($showBottomPager){?>    
        <div class="catalog-pagination" data-pagination-num="<?=$navParams['NavNum']?>">
            <!-- pagination-container -->
            <?=$arResult['NAV_STRING']?>
            <!-- pagination-container -->
        </div>        
    <?}?>
</div>

<?
$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedTemplate = $signer->sign($templateName, 'catalog.section');
$signedParams = $signer->sign(base64_encode(serialize($arResult['ORIGINAL_PARAMETERS'])), 'catalog.section');

/*Описание страницы*/
if (!isset($arParams['HIDE_SECTION_DESCRIPTION']) || $arParams['HIDE_SECTION_DESCRIPTION'] !== 'Y'){?>
    <?if (stristr(htmlspecialchars($arResult['DESCRIPTION']), 'collection__product-banner-link') === false) {?>
        <div class="page-catalog-description">
            <?=$arResult['DESCRIPTION'] ?? ''?>
        </div>
    <?}?>
<?}?>
<script>
	BX.message({
		BTN_MESSAGE_BASKET_REDIRECT: '<?=GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_BASKET_REDIRECT')?>',
		BASKET_URL: '<?=$arParams['BASKET_URL']?>',
		ADD_TO_BASKET_OK: '<?=GetMessageJS('ADD_TO_BASKET_OK')?>',
		TITLE_ERROR: '<?=GetMessageJS('CT_BCS_CATALOG_TITLE_ERROR')?>',
		TITLE_BASKET_PROPS: '<?=GetMessageJS('CT_BCS_CATALOG_TITLE_BASKET_PROPS')?>',
		TITLE_SUCCESSFUL: '<?=GetMessageJS('ADD_TO_BASKET_OK')?>',
		BASKET_UNKNOWN_ERROR: '<?=GetMessageJS('CT_BCS_CATALOG_BASKET_UNKNOWN_ERROR')?>',
		BTN_MESSAGE_SEND_PROPS: '<?=GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_SEND_PROPS')?>',
		BTN_MESSAGE_CLOSE: '<?=GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_CLOSE')?>',
		BTN_MESSAGE_CLOSE_POPUP: '<?=GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_CLOSE_POPUP')?>',
		COMPARE_MESSAGE_OK: '<?=GetMessageJS('CT_BCS_CATALOG_MESS_COMPARE_OK')?>',
		COMPARE_UNKNOWN_ERROR: '<?=GetMessageJS('CT_BCS_CATALOG_MESS_COMPARE_UNKNOWN_ERROR')?>',
		COMPARE_TITLE: '<?=GetMessageJS('CT_BCS_CATALOG_MESS_COMPARE_TITLE')?>',
		PRICE_TOTAL_PREFIX: '<?=GetMessageJS('CT_BCS_CATALOG_PRICE_TOTAL_PREFIX')?>',
		RELATIVE_QUANTITY_MANY: '<?=CUtil::JSEscape($arParams['MESS_RELATIVE_QUANTITY_MANY'])?>',
		RELATIVE_QUANTITY_FEW: '<?=CUtil::JSEscape($arParams['MESS_RELATIVE_QUANTITY_FEW'])?>',
		BTN_MESSAGE_COMPARE_REDIRECT: '<?=GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_COMPARE_REDIRECT')?>',
		BTN_MESSAGE_LAZY_LOAD: '<?=CUtil::JSEscape($arParams['MESS_BTN_LAZY_LOAD'])?>',
		BTN_MESSAGE_LAZY_LOAD_WAITER: '<?=GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_LAZY_LOAD_WAITER')?>',
		SITE_ID: '<?=CUtil::JSEscape($component->getSiteId())?>'
	});
	var <?=$obName?> = new JCCatalogSectionComponent({
		siteId: '<?=CUtil::JSEscape($component->getSiteId())?>',
		componentPath: '<?=CUtil::JSEscape($componentPath)?>',
		navParams: <?=CUtil::PhpToJSObject($navParams)?>,
		deferredLoad: false, // enable it for deferred load
		initiallyShowHeader: '<?=!empty($arResult['ITEM_ROWS'])?>',
		bigData: <?=CUtil::PhpToJSObject($arResult['BIG_DATA'])?>,
		lazyLoad: !!'<?=$showLazyLoad?>',
		loadOnScroll: !!'<?=($arParams['LOAD_ON_SCROLL'] === 'Y')?>',
		template: '<?=CUtil::JSEscape($signedTemplate)?>',
		ajaxId: '<?=CUtil::JSEscape($arParams['AJAX_ID'] ?? '')?>',
		parameters: '<?=CUtil::JSEscape($signedParams)?>',
		container: '<?=$containerName?>'
	});
</script>
<!-- component-end -->
