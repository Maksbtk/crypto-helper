<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(["ui.fonts.ruble", "ui.fonts.opensans"]);

/**
 * @var array $arParams
 * @var array $arResult
 * @var string $templateFolder
 * @var string $templateName
 * @var CMain $APPLICATION
 * @var CBitrixBasketComponent $component
 * @var CBitrixComponentTemplate $this
 * @var array $giftParameters
 */

$documentRoot = Main\Application::getDocumentRoot();

$arParams['BEAUTY_BOX_ENABLED'] = \Belleyou\BeautyBox::BEAUTY_BOX_ENABLED ?? 'N';
$arParams['BEAUTY_BOX_PROD_ID'] = \Belleyou\BeautyBox::BEAUTY_BOX_PROD_ID ?? false;
$arParams['BEAUTY_BOX_LIMIT_PRICE'] = \Belleyou\BeautyBox::LIMIT_PRICE ?? false;

if (!isset($arParams['DISPLAY_MODE']) || !in_array($arParams['DISPLAY_MODE'], array('extended', 'compact')))
{
	$arParams['DISPLAY_MODE'] = 'extended';
}

$arParams['USE_DYNAMIC_SCROLL'] = isset($arParams['USE_DYNAMIC_SCROLL']) && $arParams['USE_DYNAMIC_SCROLL'] === 'N' ? 'N' : 'Y';
$arParams['SHOW_FILTER'] = isset($arParams['SHOW_FILTER']) && $arParams['SHOW_FILTER'] === 'N' ? 'N' : 'Y';

$arParams['PRICE_DISPLAY_MODE'] = isset($arParams['PRICE_DISPLAY_MODE']) && $arParams['PRICE_DISPLAY_MODE'] === 'N' ? 'N' : 'Y';

if (!isset($arParams['TOTAL_BLOCK_DISPLAY']) || !is_array($arParams['TOTAL_BLOCK_DISPLAY']))
{
	$arParams['TOTAL_BLOCK_DISPLAY'] = array('top');
}

if (empty($arParams['PRODUCT_BLOCKS_ORDER']))
{
	$arParams['PRODUCT_BLOCKS_ORDER'] = 'props,sku,columns';
}

if (is_string($arParams['PRODUCT_BLOCKS_ORDER']))
{
	$arParams['PRODUCT_BLOCKS_ORDER'] = explode(',', $arParams['PRODUCT_BLOCKS_ORDER']);
}

$arParams['USE_PRICE_ANIMATION'] = isset($arParams['USE_PRICE_ANIMATION']) && $arParams['USE_PRICE_ANIMATION'] === 'N' ? 'N' : 'Y';
$arParams['EMPTY_BASKET_HINT_PATH'] = isset($arParams['EMPTY_BASKET_HINT_PATH']) ? (string)$arParams['EMPTY_BASKET_HINT_PATH'] : '/';
$arParams['USE_ENHANCED_ECOMMERCE'] = isset($arParams['USE_ENHANCED_ECOMMERCE']) && $arParams['USE_ENHANCED_ECOMMERCE'] === 'Y' ? 'Y' : 'N';
$arParams['DATA_LAYER_NAME'] = isset($arParams['DATA_LAYER_NAME']) ? trim($arParams['DATA_LAYER_NAME']) : 'dataLayer';
$arParams['BRAND_PROPERTY'] = isset($arParams['BRAND_PROPERTY']) ? trim($arParams['BRAND_PROPERTY']) : '';


\CJSCore::Init(array('fx', 'popup', 'ajax'));
Main\UI\Extension::load(['ui.mustache']);

$this->addExternalJs($templateFolder.'/js/action-pool.js');
$this->addExternalJs($templateFolder.'/js/filter.js');
$this->addExternalJs($templateFolder.'/js/component.js');

$mobileColumns = isset($arParams['COLUMNS_LIST_MOBILE'])
	? $arParams['COLUMNS_LIST_MOBILE']
	: $arParams['COLUMNS_LIST'];
$mobileColumns = array_fill_keys($mobileColumns, true);

$jsTemplates = new Main\IO\Directory($documentRoot.$templateFolder.'/js-templates');
/** @var Main\IO\File $jsTemplate */
foreach ($jsTemplates->getChildren() as $jsTemplate)
{
	include($jsTemplate->getPath());
}

$displayModeClass = $arParams['DISPLAY_MODE'] === 'compact' ? ' basket-items-list-wrapper-compact' : '';

if (empty($arResult['ERROR_MESSAGE']))
{
	if ($arResult['BASKET_ITEM_MAX_COUNT_EXCEEDED'])
	{?>
		<div id="basket-item-message">
			<?=Loc::getMessage('SBB_BASKET_ITEM_MAX_COUNT_EXCEEDED', array('#PATH#' => $arParams['PATH_TO_BASKET']))?>
		</div>
		<?
	}?>
    
	<span id="basket-root" class="bx-basket bx-<?=$arParams['TEMPLATE_THEME']?> bx-step-opacity basket-box" style="opacity: 0;">
		<div class="basket-contents-box">
			<div class="basket-contents">
			    <table id="basket-item-table" style="width: 100%;"></table>
			</div>
		</div>

		<aside class="basket-sidebar">
			<div class="basket-sidebar-sticky" data-entity="basket-total-block"></div>
		</aside>
	</span>
    
    <div id="mindboxNotification"></div>
    
	<?
	if (!empty($arResult['CURRENCIES']) && Main\Loader::includeModule('currency'))
	{
		CJSCore::Init('currency');

		?>
		<script>
			BX.Currency.setCurrencies(<?=CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true)?>);
		</script>
		<?
	}

	$signer = new \Bitrix\Main\Security\Sign\Signer;
	$signedTemplate = $signer->sign($templateName, 'sale.basket.basket');
	$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.basket.basket');
	$messages = Loc::loadLanguageFile(__FILE__);
	?>
	<script>
		BX.message(<?=CUtil::PhpToJSObject($messages)?>);
		BX.Sale.BasketComponent.init({
			result: <?=CUtil::PhpToJSObject($arResult, false, false, true)?>,
			params: <?=CUtil::PhpToJSObject($arParams)?>,
			template: '<?=CUtil::JSEscape($signedTemplate)?>',
			signedParamsString: '<?=CUtil::JSEscape($signedParams)?>',
			siteId: '<?=CUtil::JSEscape($component->getSiteId())?>',
			siteTemplateId: '<?=CUtil::JSEscape($component->getSiteTemplateId())?>',
			templateFolder: '<?=CUtil::JSEscape($templateFolder)?>'
		});
	</script>
<?
}
elseif ($arResult['EMPTY_BASKET'])
{
	include(Main\Application::getDocumentRoot().$templateFolder.'/empty.php');
}
else
{
	ShowError($arResult['ERROR_MESSAGE']);
}

#GTAG
$needed_ID = array();
foreach($arResult['GRID']['ROWS'] as $cartProd){
    $needed_ID[] = $cartProd['PRODUCT_ID'];
    
    $tpData[$cartProd['PRODUCT_ID']]['QUANTITY'] = $cartProd['QUANTITY'];    
    $tpData[$cartProd['PRODUCT_ID']]['PRICE'] = $cartProd['PRICE'];    
}

if(!empty($needed_ID)){
    $requiredTP = [];
    $dbItemsXmlFoReq = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 3, "ID" => $needed_ID), false, false, array('ID','SECTION_ID','PROPERTY_CML2_LINK','PROPERTY_SAYT_NAIMENOVANIE_DLYA_SAYTA'));
    while ($item = $dbItemsXmlFoReq->Fetch()) {
        $requiredTP[] = $item['PROPERTY_CML2_LINK_VALUE'];
        
        $requiredTPData[$item['PROPERTY_CML2_LINK_VALUE']]['QUANTITY'] = $tpData[$item['ID']]['QUANTITY'];
        $requiredTPData[$item['PROPERTY_CML2_LINK_VALUE']]['PRICE'] = $tpData[$item['ID']]['PRICE'];
    }

    $requiredProds = [];
    $dbItemsXmlFoReq = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 2, "ID" => $requiredTP), false, false, array('ID','IBLOCK_SECTION_ID','NAME','PROPERTY_SAYT_NAIMENOVANIE_DLYA_SAYTA'));
    while ($item = $dbItemsXmlFoReq->Fetch()) {
        $requiredProds[] = $item;
        $sections[] = $item['IBLOCK_SECTION_ID'];
    }

    $SectList = CIBlockSection::GetList(array(), array("IBLOCK_ID" => 2,"ID" => $sections) ,false, array("ID","NAME"));
    while ($SectListGet = $SectList->GetNext())
    {
        $sectionsList[$SectListGet['ID']] = $SectListGet['NAME'];
    }
}  

if(!empty($requiredProds)){
    foreach($requiredProds as $prod){
        $google_content[] = [
            'item_id' => $prod['ID'],
            'item_name' => $prod['PROPERTY_SAYT_NAIMENOVANIE_DLYA_SAYTA_VALUE'],
            'item_brand' => 'belle you',
            'price' => $requiredTPData[$prod['ID']]['PRICE'],
            'item_category' => $sectionsList[$prod['IBLOCK_SECTION_ID']], 
            'item_variant' => $prod['NAME'],
            'currency' => 'RUB',
            'quantity' => $requiredTPData[$prod['ID']]['QUANTITY'],
        ];
    }

    $viewCart = json_encode([        
        "event" => "view_cart",
        "event_category" => "ecommerce",
        "event_label" => "view_cart",
        "ecommerce" => ["items" =>  $google_content ]
    ]);
}

###RTBHOUSE_CODE
$findPropByValueName = function($item, $propValueName) {
    $currentProperty = [];
    array_filter($item['SKU_DATA'], function($skuProp) use($propValueName, &$currentProperty) {
        $propData = array_filter($skuProp['VALUES'],
            function($val) use($propValueName) {
                return $val['NAME'] == $propValueName;}
        );

        if (!empty($propData)) {
            $currentProperty = current($propData);
        }
    });

    return $currentProperty;
};    

$cnt = 0;
$ids_ = "";
foreach($arResult['GRID']['ROWS'] as $prods){
    $mxResult = CCatalogSku::GetProductInfo($prods["PRODUCT_ID"]);
    
    if($cnt == 0){
        $ids_ .= "'".$mxResult["ID"]."'";    
    }else{
        $ids_ .= ","."'".$mxResult["ID"]."'";
    }
    $cnt++;         
}   

$skuTree = [];

array_map(function($item) use(&$skuTree, $findPropByValueName){
    $skuTree[$item['ID']] = array_map(function($prop) use($item, $findPropByValueName) {

        $currentProperty = $findPropByValueName($item, $prop['VALUE']);
        return [
            'prop' => $prop['CODE'],
            'value' => $currentProperty ? $currentProperty['ID'] : $prop['VALUE'],
        ];
    }, $item['PROPS']);
}, $arResult['GRID']['ROWS']);

$sum = 0;
$cur = '';

$fbq_list = [
    'content_ids' => array_values(
        array_map(
            function($item) use (&$sum, &$cur) {
                $sum += $item['FULL_PRICE'];
                $cur = $item['CURRENCY'];

                return $item['PRODUCT_ID'];
            },
            $arResult['GRID']['ROWS']
        )
    ),
    'currency' => $cur,
    'value' => $sum,
];

if ($fbq_list):
    ?>
    <script>
        BX.ready(function() {
            window.dataLayer = window.dataLayer || [];
            dataLayer.push(<?=$viewCart?>);
            
            $('.pay1').on('click', function() {
                console.log('Purchase', <?= json_encode($fbq_list) ?>);
                fbq('track', 'Purchase', <?= json_encode($fbq_list) ?>);
            });                
            
            $('.button-checkout').on('click', function() {
                gtag('event', 'checkout', {
                    event_category: 'ecommerce',
                    event_label: '1-step'
                });
                ym(251472448,'reachGoal','ecommerce__checkout__1-step');
            });
            
            var _tmr = window._tmr || (window._tmr = []);
            _tmr.push({
                type: 'itemView',
                productid: [<?=$ids_?>],
                pagetype: 'cart', 
                totalvalue: '<?=$sum?>'
            });                 

            ADMITAD = window.ADMITAD || {};
            ADMITAD.Invoice = ADMITAD.Invoice || {};
            ADMITAD.Invoice.broker = 'adm';
            ADMITAD.Invoice.category = '1';

            var orderedItem = [];

            <?php foreach($arResult['GRID']['ROWS'] as $item): ?>
                orderedItem.push({
                    Product: {
                        productID: '<?= (int) $item['ID'] ?>',
                        category: '1',
                        price: '<?= (int) $item['FULL_PRICE'] ?>',
                        priceCurrency: 'RUB',
                    },
                    orderQuantity: '<?= (int) $item['QUANTITY'] ?>',
                    additionalType: 'sale'
                });
            <?php endforeach ?>
        })
    </script>
<?php endif ?>

<?global $USER;
if($USER->isAdmin()){
    #if($_SESSION["SHOW_BOX_BLOCK"] == 1){?>
        <div class="basket-beautybox-label">Вам доступен beauty box <a href="javascript:void(0)" class="add-beautybox addBoxButton">Добавить в корзину</a></div>    
    <?#}?>


    <script type="text/javascript">
        $(document).ready(function(){
            $(".addBoxButton").on('click', function(){
                $.post( "/ajax/addBox.php", {id: 76243})
                .success(function() {
                    //setTimeout(function() {location.reload()}, 500);
                });       
            });    
        });
    </script>

<?}?>