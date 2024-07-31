    <?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Data\Cache;
use  \kb\service\Settings;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 */

$this->setFrameMode(true);

//beauty box
global $USER;
if (!$USER->IsAdmin() && intval($arResult['ID']) == \Belleyou\BeautyBox::BEAUTY_BOX_PROD_MAIN_ID) {
    LocalRedirect('/', false, '301 Moved permanently');
}

//custom
//$availableColorPreviewPicturesAr = [];
foreach ($arResult['OFFERS'] as &$offer) {
    // узнаем id-хи доступных цветов и достаем для них картинки,
/*    if (!$availableColorPreviewPicturesAr[$offer['TREE']['PROP_401']]) {
        $availableColorPreviewPicturesAr[$offer['TREE']['PROP_401']] = CFile::ResizeImageGet($offer['DETAIL_PICTURE'], array('width'=>60, 'height'=>90), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];
    }*/
    // в каждый оффер кладем буквенное название цвета
    $offer['COLOR_NAME'] = $arResult['SKU_PROPS']['_COLOR']['VALUES'][intval($offer['TREE']['PROP_401'])]['NAME'];

    // в каждый оффер делаем ресайз картинок
    foreach ($offer['MORE_PHOTO'] as $photo) {
        //$offer['MORE_PHOTO_RESIZE'][] = CFile::ResizeImageGet($photo['ID'], array('width' => 1200, 'height' => 1800), BX_RESIZE_IMAGE_EXACT, true);
        $offer['MORE_PHOTO_RESIZE'][] = CFile::ResizeImageGet($photo['ID'], array('width' => 620, 'height' => 929), BX_RESIZE_IMAGE_EXACT, true);
    }

}
unset($offer);
//*custom

$templateLibrary = array('popup', 'fx', 'ui.fonts.opensans');
$currencyList = '';

if (!empty($arResult['CURRENCIES']))
{
    $templateLibrary[] = 'currency';
    $currencyList = CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true);
}

$haveOffers = !empty($arResult['OFFERS']);

if (!$haveOffers) {
    echo '<div class="product-container" style="color: red; justify-content: flex-start;">&nbsp; Товар недоступен!</div>';
    die();
}

$templateData = [
    'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'],
    'TEMPLATE_LIBRARY' => $templateLibrary,
    'CURRENCIES' => $currencyList,
    'ITEM' => [
        'ID' => $arResult['ID'],
        'IBLOCK_ID' => $arResult['IBLOCK_ID'],
    ],
];
if ($haveOffers)
{
    $templateData['ITEM']['OFFERS_SELECTED'] = $arResult['OFFERS_SELECTED'];
    $templateData['ITEM']['JS_OFFERS'] = $arResult['JS_OFFERS'];
}
unset($currencyList, $templateLibrary);

$mainId = $this->GetEditAreaId($arResult['ID']);
$itemIds = array(
    'ID' => $mainId,
    'DISCOUNT_PERCENT_ID' => $mainId.'_dsc_pict',
    'STICKER_ID' => $mainId.'_sticker',
    //'BIG_SLIDER_ID' => $mainId.'_big_slider',
    'BIG_IMG_CONT_ID' => $mainId.'_bigimg_cont',
    //'SLIDER_CONT_ID' => $mainId.'_slider_cont',
    'OLD_PRICE_ID' => $mainId.'_old_price',
    'PRICE_ID' => $mainId.'_price',
    'DESCRIPTION_ID' => $mainId.'_description',
    'DISCOUNT_PRICE_ID' => $mainId.'_price_discount',
    'PRICE_TOTAL' => $mainId.'_price_total',
    //'SLIDER_CONT_OF_ID' => $mainId.'_slider_cont_',
    'QUANTITY_ID' => $mainId.'_quantity',
    'QUANTITY_DOWN_ID' => $mainId.'_quant_down',
    'QUANTITY_UP_ID' => $mainId.'_quant_up',
    'QUANTITY_MEASURE' => $mainId.'_quant_measure',
    'QUANTITY_LIMIT' => $mainId.'_quant_limit',
    'BUY_LINK' => $mainId.'_buy_link',
    'ADD_BASKET_LINK' => $mainId.'_add_basket_link',
    'BASKET_ACTIONS_ID' => $mainId.'_basket_actions',
    'NOT_AVAILABLE_MESS' => $mainId.'_not_avail',
    'COMPARE_LINK' => $mainId.'_compare_link',
    'TREE_ID' => $mainId.'_skudiv',
    'DISPLAY_PROP_DIV' => $mainId.'_sku_prop',
    'DISPLAY_MAIN_PROP_DIV' => $mainId.'_main_sku_prop',
    'OFFER_GROUP' => $mainId.'_set_group_',
    'BASKET_PROP_DIV' => $mainId.'_basket_prop',
    'SUBSCRIBE_LINK' => $mainId.'_subscribe',
    'TABS_ID' => $mainId.'_tabs',
    'TAB_CONTAINERS_ID' => $mainId.'_tab_containers',
    'SMALL_CARD_PANEL_ID' => $mainId.'_small_card_panel',
    'TABS_PANEL_ID' => $mainId.'_tabs_panel'
);
$obName = $templateData['JS_OBJ'] = 'ob'.preg_replace('/[^a-zA-Z0-9_]/', 'x', $mainId);
$name = !empty($arResult['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'])
    ? $arResult['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']
    : $arResult['NAME'];
$title = !empty($arResult['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'])
    ? $arResult['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE']
    : $arResult['NAME'];
$alt = !empty($arResult['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'])
    ? $arResult['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT']
    : $arResult['NAME'];

if ($haveOffers)
{
    $actualItem = $arResult['OFFERS'][$arResult['OFFERS_SELECTED']] ?? reset($arResult['OFFERS']);
}
else
{
    $actualItem = $arResult;
}

$skuProps = array();
$price = $actualItem['ITEM_PRICES'][$actualItem['ITEM_PRICE_SELECTED']];
$currentOfferId = $actualItem['ID'];

{
    $google_content = [
        'item_id' => $arResult['ID'],
        'item_name' => $arResult['PROPERTIES']['SAYT_NAIMENOVANIE_DLYA_SAYTA']['VALUE'],
        'item_brand' => 'belle you',
        'price' => $price['PRICE'],
        'item_category' => $arResult['SECTION']['NAME'],
        'item_variant' => $arResult['NAME'],
        'currency' => $currencyList[0]['CURRENCY'] ?? 'RUB',
        'quantity' => 1,
    ];

    /*$vk_content = [
        'products' => [['id' => $arResult['JS_OFFERS'][0]['ID']]],
    ];*/

   /* $ttq_content = [
        'content_id' => $arResult['XML_ID'],
        'content_name' => $arResult['NAME'],
        'content_type' => 'product',
        'price' => $price['PRICE'],
        'currency' => $currencyList[0]['CURRENCY'] ?? 'RUB',
    ];

    $ttq_content['content_id'] = $arResult['JS_OFFERS'][0]['ID'];
    $ttq_content = json_encode($ttq_content);*/

    $itemView = json_encode([        
        "event" => "view_item",
        "event_category" => "ecommerce",
        "event_label" => "view_item",
        "ecommerce" => ["items" => [ $google_content ]]
    ]);

    $add2cart = json_encode([        
        "event" => "add_to_cart",
        "event_category" => "ecommerce",
        "event_label" => "add_to_cart",
        "ecommerce" => ["items" => [ $google_content ]]
    ]);
    
    //для rr соберем id офферов у товаров
    $rrOfersIds = [];
    foreach ($arResult['OFFERS'] as $offer) {
        $rrOfersIds[] = $offer['ID'];
    }
    $rrOfersIds = json_encode($rrOfersIds);

    /*  $vk_content = json_encode($vk_content);*/?>

    <script type="text/javascript">
        $(document).ready(function(){
            var _tmr = window._tmr || (window._tmr = []);
            _tmr.push({
                type: 'itemView',
                productid: '<?=$currentOfferId?>',
                pagetype: 'product',
                list: 1,
                totalvalue: '<?=$price['PRICE']?>'
            });

            _tmr.push({"type":"reachGoal","id":3251356,"goal":"view_product"});
        });
    </script>

    <div class="i-flocktory" data-fl-action="track-item-view" data-fl-item-id="<?=$currentOfferId?>" data-fl-item-category_id="<?=$arResult['IBLOCK_SECTION_ID']?>" data-fl-item-available="true"></div>

<?echo <<<JS
<script>
    BX.ready(function() {
        let currentOfferId = 0;
        BX.addCustomEvent('onCatalogElementChangeOffer', function(e) {
            currentOfferId = e.newId;
            //console.log('Current offer id is', currentOfferId);
        });
        
        ym(24428327,'reachGoal','PROSMOTR');
        
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({$itemView});
        
        gtag("event", "pageView", {
            event_category: 'behavior',
            event_label: 'product'
        });
        
        //retailrocket - Трекинг-код просмотра карточки товара
        (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
            try{ rrApi.groupView({$rrOfersIds}); } catch(e) {}
        })

        \$('#{$itemIds['BASKET_ACTIONS_ID']}').on('click', function() {
            window.dataLayer = window.dataLayer || [];
            dataLayer.push({$add2cart});
        });
        
        // \$('#{itemIds['ADD_BASKET_LINK']}').on('click', function() {
           /* ym(24428327,'reachGoal','KORZINA');*/
            
            //gtag(<?=//add2cart?>);
           
           /* let content = <?=ttq_content?>;
            content.quantity = 1;*/
            
           /* window.flocktory = window.flocktory || [];
            window.flocktory.push(['addToCart', {
                item: {
                    "id": "{currentOfferId}",
                    "price": {price['PRICE']},
                    "count": 1,
                    "categoryId": "{arResult['IBLOCK_SECTION_ID']}"
                }
            }]);*/
            
            //retailrocket
            /*(window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
                try { rrApi.addToBasket(currentOfferId); } catch(e) {}
            });*/
            //!retailrocket
            
            /*setTimeout(function() {
                VK.Retargeting.Event('add_to_cart');                       
                VK.Retargeting.Add(49531461);
            }, 1500); */           
        //});
        
       /* \$('.product-list1__favorite, .js-action-favorite').click(function (){  // content-information1__add-wishlist
            setTimeout(function() {            
                VK.Retargeting.ProductEvent(132039, "add_to_wishlist", <?=vk_content?>);
            }, 1500);
        });*/
        
       /* setTimeout(function() {
            VK.Retargeting.Event('view_product');
            VK.Retargeting.Add(49531512);
        }, 2000);  */      
    });
</script>
JS;
}

$measureRatio = $actualItem['ITEM_MEASURE_RATIOS'][$actualItem['ITEM_MEASURE_RATIO_SELECTED']]['RATIO'];
$showDiscount = $price['PERCENT'] > 0;

if ($arParams['SHOW_SKU_DESCRIPTION'] === 'Y')
{
    $skuDescription = false;
    foreach ($arResult['OFFERS'] as $offer)
    {
        if ($offer['DETAIL_TEXT'] != '' || $offer['PREVIEW_TEXT'] != '')
        {
            $skuDescription = true;
            break;
        }
    }
    $showDescription = $skuDescription || !empty($arResult['PREVIEW_TEXT']) || !empty($arResult['DETAIL_TEXT']);
}
else
{
    $showDescription = !empty($arResult['PREVIEW_TEXT']) || !empty($arResult['DETAIL_TEXT']);
}

$showBuyBtn = in_array('BUY', $arParams['ADD_TO_BASKET_ACTION']);
$showAddBtn = in_array('ADD', $arParams['ADD_TO_BASKET_ACTION']);
$showSubscribe = $arParams['PRODUCT_SUBSCRIPTION'] === 'Y' && ($arResult['PRODUCT']['SUBSCRIBE'] === 'Y' || $haveOffers);

$productType = $arResult['PRODUCT']['TYPE'];

$arParams['MESS_BTN_BUY'] = $arParams['MESS_BTN_BUY'] ?: Loc::getMessage('CT_BCE_CATALOG_BUY');
$arParams['MESS_BTN_ADD_TO_BASKET'] = $arParams['MESS_BTN_ADD_TO_BASKET'] ?: Loc::getMessage('CT_BCE_CATALOG_ADD');

if ($arResult['MODULES']['catalog'] && $arResult['PRODUCT']['TYPE'] === ProductTable::TYPE_SERVICE)
{
    $arParams['~MESS_NOT_AVAILABLE'] = $arParams['~MESS_NOT_AVAILABLE_SERVICE']
        ?: Loc::getMessage('CT_BCE_CATALOG_NOT_AVAILABLE_SERVICE')
    ;
    $arParams['MESS_NOT_AVAILABLE'] = $arParams['MESS_NOT_AVAILABLE_SERVICE']
        ?: Loc::getMessage('CT_BCE_CATALOG_NOT_AVAILABLE_SERVICE')
    ;
}
else
{
    $arParams['~MESS_NOT_AVAILABLE'] = $arParams['~MESS_NOT_AVAILABLE']
        ?: Loc::getMessage('CT_BCE_CATALOG_NOT_AVAILABLE')
    ;
    $arParams['MESS_NOT_AVAILABLE'] = $arParams['MESS_NOT_AVAILABLE']
        ?: Loc::getMessage('CT_BCE_CATALOG_NOT_AVAILABLE')
    ;
}

$arParams['MESS_BTN_COMPARE'] = $arParams['MESS_BTN_COMPARE'] ?: Loc::getMessage('CT_BCE_CATALOG_COMPARE');
$arParams['MESS_PRICE_RANGES_TITLE'] = $arParams['MESS_PRICE_RANGES_TITLE'] ?: Loc::getMessage('CT_BCE_CATALOG_PRICE_RANGES_TITLE');
$arParams['MESS_DESCRIPTION_TAB'] = $arParams['MESS_DESCRIPTION_TAB'] ?: Loc::getMessage('CT_BCE_CATALOG_DESCRIPTION_TAB');
$arParams['MESS_PROPERTIES_TAB'] = $arParams['MESS_PROPERTIES_TAB'] ?: Loc::getMessage('CT_BCE_CATALOG_PROPERTIES_TAB');
$arParams['MESS_COMMENTS_TAB'] = $arParams['MESS_COMMENTS_TAB'] ?: Loc::getMessage('CT_BCE_CATALOG_COMMENTS_TAB');
$arParams['MESS_SHOW_MAX_QUANTITY'] = $arParams['MESS_SHOW_MAX_QUANTITY'] ?: Loc::getMessage('CT_BCE_CATALOG_SHOW_MAX_QUANTITY');
$arParams['MESS_RELATIVE_QUANTITY_MANY'] = $arParams['MESS_RELATIVE_QUANTITY_MANY'] ?: Loc::getMessage('CT_BCE_CATALOG_RELATIVE_QUANTITY_MANY');
$arParams['MESS_RELATIVE_QUANTITY_FEW'] = $arParams['MESS_RELATIVE_QUANTITY_FEW'] ?: Loc::getMessage('CT_BCE_CATALOG_RELATIVE_QUANTITY_FEW');

$positionClassMap = array(
    'left' => 'product-item-label-left',
    'center' => 'product-item-label-center',
    'right' => 'product-item-label-right',
    'bottom' => 'product-item-label-bottom',
    'middle' => 'product-item-label-middle',
    'top' => 'product-item-label-top'
);

$discountPositionClass = 'product-item-label-big';
if ($arParams['SHOW_DISCOUNT_PERCENT'] === 'Y' && !empty($arParams['DISCOUNT_PERCENT_POSITION']))
{
    foreach (explode('-', $arParams['DISCOUNT_PERCENT_POSITION']) as $pos)
    {
        $discountPositionClass .= isset($positionClassMap[$pos]) ? ' '.$positionClassMap[$pos] : '';
    }
}

$labelPositionClass = 'product-item-label-big';
if (!empty($arParams['LABEL_PROP_POSITION']))
{
    foreach (explode('-', $arParams['LABEL_PROP_POSITION']) as $pos)
    {
        $labelPositionClass .= isset($positionClassMap[$pos]) ? ' '.$positionClassMap[$pos] : '';
    }
}

$userIsAuth = $USER->IsAuthorized()
?>

<?###SALE DATA
$show_sale = false;
$show_presale = false;
if(Settings::SALE == 1){
    if(Settings::CLOSED_SALE == 1){
        if($USER->isAuthorized()){
            $show_sale = true;
        }                  
    }elseif(Settings::PRESALE == 1){
        if($USER->isAuthorized()){
            $show_presale = true;
        }else{
            $show_sale = true;    
        }  
    }
    else{
        $show_sale = true;
    }                 
}

if($show_sale){
    $kbSaleProdArray = Settings::SALE_PRODUCT_ARRAY;
}elseif($show_presale){
    $kbSaleProdArray = Settings::PRESALE_PRODUCT_ARRAY;    
}

if (($show_sale || $show_presale) && $kbSaleProdArray) {
    $ids15 = ($kbSaleProdArray['15']) ? $kbSaleProdArray['15'] : [];
    $ids20 = ($kbSaleProdArray['20']) ? $kbSaleProdArray['20'] : [];
    $ids25 = ($kbSaleProdArray['25']) ? $kbSaleProdArray['25'] : [];
    $ids30 = ($kbSaleProdArray['30']) ? $kbSaleProdArray['30'] : [];
    $ids35 = ($kbSaleProdArray['35']) ? $kbSaleProdArray['35'] : [];
    $ids40 = ($kbSaleProdArray['40']) ? $kbSaleProdArray['40'] : [];
    $ids45 = ($kbSaleProdArray['45']) ? $kbSaleProdArray['45'] : [];
    $ids50 = ($kbSaleProdArray['50']) ? $kbSaleProdArray['50'] : [];
    $ids55 = ($kbSaleProdArray['55']) ? $kbSaleProdArray['55'] : [];
    $ids60 = ($kbSaleProdArray['60']) ? $kbSaleProdArray['60'] : [];
}?>

<div class="product-container bx-<?=$arParams['TEMPLATE_THEME']?>" id="<?=$itemIds['ID']?>"
     itemscope itemtype="http://schema.org/Product">
    <?/*<div class="product-container">*/?>

    <button class="product-back__mobile js-mobile-back-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
    <div class="product-buttons__mobile">
        <button class="product-add-to-favorite__mobile <?if($userIsAuth):?>  js-check-wishlist-button<?endif;?>"
                data-id="<?=$arResult['ID']?>"
                <?if(!$userIsAuth):?> data-popup="popup-go-to-auth"<?endif;?>
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                <path stroke="#A0BCD2" stroke-linecap="round" stroke-linejoin="round" d="M15.023.53c-2.047-.23-4.25.912-5.024 2.618C9.225 1.442 7 .301 4.976.53 2.28.823.553 3.57 1.099 6.22c.744 3.604 4.801 5.214 8.9 9.281 4.081-4.067 8.156-5.683 8.9-9.282.547-2.65-1.182-5.395-3.876-5.689z">

                </path>
            </svg>
        </button>
        <button class="product-copy-link__mobile">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#A0BCD2" stroke-linecap="round" stroke-linejoin="round" d="M9 15l6-6M6.137 11l-1.716 1.716a4.853 4.853 0 1 0 6.863 6.863L13 17.863M11 6.137l1.716-1.716a4.853 4.853 0 1 1 6.863 6.863L17.863 13"/>
            </svg>
            <span class="product-copy-link-label" style="display: none;">Ссылка скопирована</span>
        </button>
    </div>

    <meta itemprop="image" content="<?=$arResult["PREVIEW_PICTURE"]["SRC"]?>">
    
    <?// картинки?>
    <div class="product-images-box">
        <div class="product-images-list"> <?//product-images-slider?>
            <?if (!$actualItem['MORE_PHOTO_RESIZE']):?>
                <div class="product-image-wrapper">
                    <img src="<?=SITE_TEMPLATE_PATH?>/img/img-no-photo.svg"  alt="no_photo">
                </div>
            <?endif;?>
        </div>
    </div>

    <?// описание?>
    <div class="product-desc-box">
        <div class="product-desc-box-sticky">
            <header class="product-desc-header">
                <?/*<span class="product-label-online-only">Доступно к покупке только онлайн</span>*/?>
                <h1 class="product-desc-title" itemprop="name"><?=$arResult['PROPERTIES']['SAYT_NAIMENOVANIE_DLYA_SAYTA']['VALUE']?></h1>
                
                <?if(in_array($arResult['ID'],$ids15) || in_array($arResult['ID'],$ids20)|| in_array($arResult['ID'],$ids25) || in_array($arResult['ID'],$ids30) || in_array($arResult['ID'],$ids35) || in_array($arResult['ID'],$ids40) || in_array($arResult['ID'],$ids45) || in_array($arResult['ID'],$ids50) || in_array($arResult['ID'],$ids55) || in_array($arResult['ID'],$ids60)){?>
                    <?if (in_array($arResult['ID'],$ids15)) {$sale_price = floor($price['RATIO_BASE_PRICE'] - (($price['RATIO_BASE_PRICE']/100)*15));}?>
                    <?if (in_array($arResult['ID'],$ids20)) {$sale_price = floor($price['RATIO_BASE_PRICE'] - (($price['RATIO_BASE_PRICE']/100)*20));}?>
                    <?if (in_array($arResult['ID'],$ids25)) {$sale_price = floor($price['RATIO_BASE_PRICE'] - (($price['RATIO_BASE_PRICE']/100)*25));}?>
                    <?if (in_array($arResult['ID'],$ids30)) {$sale_price = floor($price['RATIO_BASE_PRICE'] - (($price['RATIO_BASE_PRICE']/100)*30));}?>
                    <?if (in_array($arResult['ID'],$ids35)) {$sale_price = floor($price['RATIO_BASE_PRICE'] - (($price['RATIO_BASE_PRICE']/100)*35));}?>
                    <?if (in_array($arResult['ID'],$ids40)) {$sale_price = floor($price['RATIO_BASE_PRICE'] - (($price['RATIO_BASE_PRICE']/100)*40));}?>
                    <?if (in_array($arResult['ID'],$ids45)) {$sale_price = floor($price['RATIO_BASE_PRICE'] - (($price['RATIO_BASE_PRICE']/100)*45));}?>
                    <?if (in_array($arResult['ID'],$ids50)) {$sale_price = floor($price['RATIO_BASE_PRICE'] - (($price['RATIO_BASE_PRICE']/100)*50));}?>
                    <?if (in_array($arResult['ID'],$ids55)) {$sale_price = floor($price['RATIO_BASE_PRICE'] - (($price['RATIO_BASE_PRICE']/100)*55));}?>
                    <?if (in_array($arResult['ID'],$ids60)) {$sale_price = floor($price['RATIO_BASE_PRICE'] - (($price['RATIO_BASE_PRICE']/100)*60));}?>

                    <div class="proudct-price-box">
                        <span class="current-price" id="<?=$itemIds['DISCOUNT_PRICE_ID']?>"><?= number_format($sale_price, 0, ' ', ' ') ?> ₽</span>
                        <span class="old-price" id="<?=$itemIds['PRICE_ID']?>"><?=$actualItem['ITEM_PRICES'][0]['PRINT_RATIO_PRICE']?></span>
                    </div>
                    
                    <?if($arResult['ID'] !== 91985 && $arResult['ID'] !== 92402 && $arResult['ID'] !== 92404 && $arResult['ID'] !== 92406){?>
                        <span class="product-divide-price" data-popup="popup-product-podeli">Или 4 платежа по <a class="js-podeli_price"><?= str_replace('.', ',', $sale_price / 4) . ' '?> ₽</a></span>                           
                    <?}?>
                <?}else{?>                
                    <div class="proudct-price-box">
                        <span class="current-price" id="<?=$itemIds['PRICE_ID']?>"><?=$actualItem['ITEM_PRICES'][0]['PRINT_RATIO_PRICE']?></span>
                    </div>
                    
                    <?if($arResult['ID'] !== 91985 && $arResult['ID'] !== 92402 && $arResult['ID'] !== 92404 && $arResult['ID'] !== 92406){?>
                        <span class="product-divide-price" data-popup="popup-product-podeli">Или 4 платежа по <a class="js-podeli_price"><?= str_replace('.', ',', $actualItem['ITEM_PRICES'][0]['RATIO_BASE_PRICE'] / 4) . ' '?> ₽</a></span>
                    <?}?>
                <?}?>                
            </header>

            <?// Торговые предложения?>
            <div id="<?=$itemIds['TREE_ID']?>">
            <?php
            foreach ($arParams['PRODUCT_INFO_BLOCK_ORDER'] as $blockName)
            {
                switch ($blockName)
                {
                    case 'sku':
                        if ($haveOffers && !empty($arResult['OFFERS_PROP']))
                        {
                            ?>
                            <?/*<div id="<?=$itemIds['TREE_ID']?>">*/?>
                            <?/*<div>*/?>
                                <?php
                                $reversedProps = array_reverse($arResult['SKU_PROPS']);
                                foreach ($reversedProps as $skuProperty)
                                {
                                    if (!isset($arResult['OFFERS_PROP'][$skuProperty['CODE']]))
                                        continue;

                                    $propertyId = $skuProperty['ID'];
                                    $skuProps[] = array(
                                        'ID' => $propertyId,
                                        'SHOW_MODE' => $skuProperty['SHOW_MODE'],
                                        'VALUES' => $skuProperty['VALUES'],
                                        'VALUES_COUNT' => $skuProperty['VALUES_COUNT']
                                    );
                                    ?>
                                    <?if ($skuProperty['CODE'] == '_COLOR'):?>
                                    <div class="product-color-box" data-entity="sku-line-block">
                                        <div class="h3 product-color-label">Цвет: <span class="product-color-current"><?=$actualItem['COLOR_NAME']?></span></div>
                                        <?if ($arResult["OTHER_COLORS"] ):?>
                                            <ul class="product-color-slider">
                                                <?php/*
                                                foreach ($skuProperty['VALUES'] as &$value)
                                                {
                                                    if($key > 0){
                                                        $value['NAME'] = htmlspecialcharsbx($value['NAME']);
                                                        ?>

                                                        <li class="product-color-item" title="<?=$value['NAME']?>"
                                                            data-treevalue="<?=$propertyId?>_<?=$value['ID']?>"
                                                            data-onevalue="<?=$value['ID']?>" data-type="color"
                                                            style="display: none;"
                                                        >
                                                            <a href="javascript:void(0);" data-value="<?=$value['NAME']?>" title="<?=$value['NAME']?>">

                                                                <?if($availableColorPreviewPicturesAr[intval($value['ID'])]):?>
                                                                    <img src="<?=$availableColorPreviewPicturesAr[intval($value['ID'])]?>" width="60" height="90" alt="<?=$value['NAME']?>">
                                                                <?else:?>
                                                                    <img src="<?=$value['PICT']['SRC']?>" width="60" height="90" alt="<?=$value['NAME']?>">
                                                                <?endif;?>
                                                            </a>
                                                        </li>

                                                        <?
                                                        }
                                                }
                                                */?>
                                                <?php

                                                foreach ($arResult["OTHER_COLORS"] as $proId => &$value)
                                                {
                                                    $value['NAME'] = htmlspecialcharsbx($value['NAME']);
                                                    //$colorSkuId = array_key_first($skuProperty['VALUES']);
                                                    ?>

                                                    <li class="product-color-item<?if($value["AVAILABLE"] == "N"){echo " unavailable";}?><?if($value["CODE"] == $arResult["CODE"]){echo " current";}?>" title="<?=$value['NAME']?>"
                                                        <?/*data-treevalue="<?=$propertyId?>_<?=$colorSkuId?>"
                                                        data-onevalue="<?=$colorSkuId?>" data-type="color"*/?>
                                                    >
                                                        <a href="<?if(intval($arResult['ID']) != $proId):?><?=$value['LINK']?><?else:?>javascript:void(0);<?endif;?>" title="<?=$value['NAME']?>">
                                                            <img src="<?=$value['PICT']?>" width="60" height="90" alt="<?=$value['NAME']?>">
                                                        </a>
                                                    </li>

                                                    <?
                                                }
                                                ?>
                                            </ul>
                                        <?endif;?>
                                    </div>
                                    <?elseif ($skuProperty['CODE'] == '_SIZE'):?>
                                        <?php
                                        $curPage = $APPLICATION->GetCurPage(false);

                                        $sizeChart = 'popup-sizechart-sg2';
                                        if (stripos($curPage, 'kolgotki_i_noski') !== false)
                                        {
                                            $sizeChart = 'popup-sizechart-socks';
                                        }
                                        elseif (stripos($curPage, 'bodi') !== false || stripos($arResult['NAME'], 'боди') !== false || stripos($arResult['PROPERTIES']['SAYT_NAIMENOVANIE_DLYA_SAYTA']['VALUE'], 'боди') !== false)
                                        {
                                            $sizeChart = 'popup-sizechart-body';
                                        }
                                        elseif (stripos($arResult['NAME'], 'belle you mama') !== false || stripos($arResult['PROPERTIES']['SAYT_NAIMENOVANIE_DLYA_SAYTA']['VALUE'], 'belle you mama') !== false)
                                        {
                                            $sizeChart = 'popup-sizechart-mama';
                                        }
                                        elseif (stripos($curPage, 'muzhchinam') !== false)
                                        {
                                            $sizeChart = 'popup-sizechart-pants-man';
                                        }
                                        elseif (stripos($curPage, 'podarochnaya-upakovka') !== false || stripos($curPage, 'sertifikaty') !== false ||  (stripos($curPage, 'aksessuary') !== false && !$arResult['PROPERTIES']['SHORTCODE']['VALUE']))
                                        {
                                            $sizeChart = '';
                                        }
                                        elseif (stripos($arResult['PROPERTIES']['SHORTCODE']['VALUE'], 'SG2') !== false)
                                        {
                                            $sizeChart = 'popup-sizechart-sg2';
                                        }
                                        elseif (stripos($arResult['PROPERTIES']['SHORTCODE']['VALUE'], 'SG1') !== false || stripos($arResult['PROPERTIES']['SHORTCODE']['VALUE'], 'SG3') !== false)
                                        {
                                            $sizeChart = 'popup-sizechart-sg1_sg3';
                                        }

                                    ?>
                                        <div class="product-size-box" data-entity="sku-line-block">
                                            <div class="h3 product-size-title">Размер</div>
                                            <?if ($sizeChart):?>
                                                <a class="product-size-chart-link" data-popup="<?=$sizeChart?>">Таблица размеров</a>
                                            <?endif;?>
                                            <ul class="product-sizes-list" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                                                <meta itemprop="price" content="<?=$actualItem['ITEM_PRICES'][0]['PRICE']?>">
                                                <meta itemprop="priceCurrency" content="RUB">
                                                <?php
                                                foreach ($skuProperty['VALUES'] as &$value)
                                                {
                                                    if($value['ID'] == 0) continue;
                                                    $value['NAME'] = htmlspecialcharsbx($value['NAME']);
                                                    $value['NAME'] = trim($value['NAME']);
                                                    ?>

                                                    <li class="product-size <?if (strlen($value['NAME']) >= 7):?>custom-width<?endif;?>" title="<?=$value['NAME']?>"
                                                        data-treevalue="<?=$propertyId?>_<?=$value['ID']?>"
                                                        data-onevalue="<?=$value['ID']?>" data-type="size"
                                                        itemprop="name"
                                                    >
                                                        <?=$value['NAME']?>
                                                    </li>

                                                    <?
                                                }
                                                ?>
                                            </ul>
                                            <?
                                            $prodQuantity = 'В наличии мало';
                                            if (intval($actualItem['CATALOG_QUANTITY']) >= 10) {
                                                $prodQuantity = 'В наличии много';
                                            }
                                            if (intval($actualItem['CATALOG_QUANTITY']) <= 0) {
                                                $prodQuantity = 'Нет в наличии';
                                            }
                                            ?>
                                            <div class="product-size-selected-amount js-quantity_selected"><?=$prodQuantity?></div>
                                        </div>
                                    <?endif;?>
                                    <?php
                                }
                                ?>
                            <?/*</div>*/?>
                            <?php
                        }
                        break;
                }
            }
            ?>
            </div>

            <div class="product-add-to-basket-box" data-entity="main-button-container">

                <?//тут должен был быть product-size-box?>

                <div class="product-actions-box">
                    <?if ($showAddBtn)
                    {?>
                        <div id="<?=$itemIds['BASKET_ACTIONS_ID']?>" style="width: 100%;">
                            <a class="button product-buy-button" <?/*data-popup="popup-add-to-basket"*/?> id="<?=$itemIds['ADD_BASKET_LINK']?>"
                               href="javascript:void(0);">
                                в корзину
                            </a>
                        </div>
                    <?}?>

                    <?php
                    if ($showSubscribe)
                    {
                        $APPLICATION->IncludeComponent(
                            'bitrix:catalog.product.subscribe',
                            'main',
                            array(
                                'CUSTOM_SITE_ID' => $arParams['CUSTOM_SITE_ID'] ?? null,
                                'PRODUCT_ID' => $arResult['ID'],
                                'BUTTON_ID' => $itemIds['SUBSCRIBE_LINK'],
                                'BUTTON_CLASS' => 'button button-secondary product-alert-in-stock',
                                'DEFAULT_DISPLAY' => !$actualItem['CAN_BUY'],
                                'MESS_BTN_SUBSCRIBE' => $arParams['~MESS_BTN_SUBSCRIBE'],
                            ),
                            $component,
                            array('HIDE_ICONS' => 'Y')
                        );
                    }?>

                    <button class="button button-secondary product-addtofavorite-button <?if($userIsAuth):?> js-check-wishlist-button<?endif;?>"
                            data-id="<?=$arResult['ID']?>"
                        <?if(!$userIsAuth):?> data-popup="popup-go-to-auth"<?endif;?>
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16"><path stroke="#A0BCD2" stroke-linecap="round" stroke-linejoin="round" d="M15.023.53c-2.047-.23-4.25.912-5.024 2.618C9.225 1.442 7 .301 4.976.53 2.28.823.553 3.57 1.099 6.22c.744 3.604 4.801 5.214 8.9 9.281 4.081-4.067 8.156-5.683 8.9-9.282.547-2.65-1.182-5.395-3.876-5.689z"></path></svg>
                    </button>
                </div>
            </div>

            <div class="product-about-box">
                <div class="h3 product-about-title">О товаре</div>
                
                <?/*<div class="product-feed-short">
                    <div class="feed-short-rating rating-4"></div>
                    <a class="feed-short-link" data-popup="popup-product-feedback">14 отзывов</a>
                </div>*/?>
                
                <div class="product-about-text" itemprop="description">
                    <p><?=$arResult['PROPERTIES']['SAYT_NAIMENOVANIE_DLYA_SAYTA']['VALUE']?></p>
                    <?if ($arResult['PROPERTIES']['CML2_ARTICLE']['VALUE']):?>
                        <p itemprop="model">Артикул – <?=$arResult['PROPERTIES']['CML2_ARTICLE']['VALUE']?></p>
                    <?endif;?>

                    <?if ($actualItem['PROPERTIES']['SAYT_PARAMETRYMODELI']['VALUE'] && strlen($actualItem['PROPERTIES']['SAYT_PARAMETRYMODELI']['VALUE']) > 5 ):?>
                        <p class="js-model-params_text">Параметры модели – <span class="js-model-params"><?=$actualItem['PROPERTIES']['SAYT_PARAMETRYMODELI']['VALUE']?></span></p>
                        <?/*<p>Размер на модели – XS/S</p>*/?>
                    <?endif;?>



                </div>
                
                <div class="product-about-addtional">
                    <?if(!empty($arResult['DETAIL_TEXT'])){?>
                        <a class="product-about-addtional-link" data-popup="popup-product-description">Обмеры и описание</a>
                    <?}?>
                    <?if ((!empty($arResult["PROPERTIES"]['SAYT_SOSTAV']['VALUE']) && $arResult["PROPERTIES"]['SAYT_SOSTAV']['VALUE'] !== "-") || (!empty($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE']) && $arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'] !== "-")) { ?>
                        <a class="product-about-addtional-link" data-popup="popup-product-care">Состав и уход</a>
                    <?}?>
                    <a class="product-about-addtional-link" data-popup="popup-product-return">Возврат</a>
                    <?/*<a class="product-about-addtional-link" data-popup="popup-product-in-stores">Наличие в магазинах</a>*/?>
                </div>
            </div>

            <?
            //Идеально подходит

            $color = $actualItem['COLOR_NAME'];

            $article = $arResult['PROPERTIES']['CML2_ARTICLE']['VALUE'];
            $perfectFitIds = [];

            if (!empty($color) && !empty($article)) {
                $cacheId = md5(serialize([$color,$article]));
                $cache = Cache::createInstance();
                if ($this->arParams["CACHE_TIME"] == 'Y' && $cache->initCache($this->arParams['CACHE_TIME'], 'ProductPerfectFitIds|' . $USER->GetUserGroupString() . '|' . $cacheId)) {
                    $perfectFitIds = $cache->getVars();
                } elseif ($cache->startDataCache()) {

                    $hlblPerfectFit = "76";
                    $hlblockPerfectFit = Bitrix\Highloadblock\HighloadBlockTable::getById($hlblPerfectFit)->fetch();
                    $entityPerfectFit = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblockPerfectFit);
                    $entity_data_class_perfect_fit = $entityPerfectFit->getDataClass();
                    $rsData = $entity_data_class_perfect_fit::getList([
                        "select" => ["*"],
                        "order" => ["ID" => "ASC"],
                        "filter" => ["UF_ARTICLE" => $article, "UF_COLOR_RUS" => $color]
                    ]);
                    $data = [];
                    while ($arData = $rsData->Fetch()) {
                        $data[] = $arData;
                    }

                    $articles = [];
                    if (!empty($data[0]["UF_ART_SET1"])) {
                        $articles[] = $data[0]["UF_ART_SET1"];
                    }
                    if (!empty($data[0]["UF_ART_SET2"])) {
                        $articles[] = $data[0]["UF_ART_SET2"];
                    }
                    if (!empty($data[0]["UF_ART_SET3"])) {
                        $articles[] = $data[0]["UF_ART_SET3"];
                    }
                    if (!empty($data[0]["UF_ART_SET4"])) {
                        $articles[] = $data[0]["UF_ART_SET4"];
                    }
                    if (!empty($data[0]["UF_ART_SET5"])) {
                        $articles[] = $data[0]["UF_ART_SET5"];
                    }
                }

                if (!empty($articles)) {
                    $itemsObj = CIBlockElement::GetList(
                        ["SORT" => "ASC"],
                        [
                            "IBLOCK_ID" => "2",
                            "ACTIVE" => "Y",
                            "PROPERTY_CML2_ARTICLE" => $articles,
                            "NAME" => '%'.$color.'%'
                        ],
                        false,
                        false,
                        ["ID"]
                    );
                    while ($arItem = $itemsObj->GetNext()) {
                        $perfectFitIds[] = $arItem['ID'];
                    }
                }


                $cache->endDataCache($perfectFitIds);
            }
            ?>

            <?php if (!empty($perfectFitIds)){
                //Идеально подходит
                $perfectFitProdSliderFilter = ["IBLOCK_ID" => "2", "ACTIVE"=>"Y", 'ID' => $perfectFitIds, /*'!PREVIEW_PICTURE' => false*/];
                $APPLICATION->IncludeComponent(
                    "belleyou:product.slider",
                    "perfect.fit",
                    array(
                        "CACHE_TIME" => "21600",
                        "CACHE_TYPE" => "Y",
                        "COMPONENT_TEMPLATE" => "perfect.fit",
                        "PROD_COUNT" => "4",
                        "FILTER" => $perfectFitProdSliderFilter,
                        "SHOW_MORE_PICTURE" => SITE_TEMPLATE_PATH."/demo-pics/pi-see-all_mobile.jpg",
                        "BLOCK_TITLE" => 'Идеально подходит',
                        'ELEMENT_SORT' => ["RAND" => "ASC"],
                    ),
                    false
                );
            }
            ?>

        </div>
    </div><!-- end описание -->
</div>


<?php
///////////////


?>

    <?//modal?>

    <div class="popup popup-add-to-basket">
        <div class="popup__backdrop button-close-popup_" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup button-close-popup_" data-close-popup></button>
                <div class="h2 popup-title">Добавлено в корзину</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="popup-product-added-items" <?/*style="margin-bottom: 150px;"*/?>>
                    </div>
                </div>

                <footer class="popup-sticky-footer">
                    <button class="button button-secondary button-close-popup_" data-close-popup>Продолжить покупки</button>
                    <?#BOX FIX KILL ME?>
                    <!--<a href="/cart/?reload=y" class="button">Перейти в корзину</a>-->
                    <a href="/cart/" class="button">Перейти в корзину</a>
                </footer>
            </div>
        </div>
    </div>

    <!-- обмеры и описание -->
    <div class="popup popup-product-description">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">обмеры и описание</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-product">
                        <p><?=$arResult['DETAIL_TEXT']?></p>
                        <div>                        
                            <p><b>Сервис "belle you в подарок"</b></p>

                            <p>По вашему запросу мы можем:</p>
                            <ul style="list-style: disc;">
                                <li>упаковать изделия в несколько разных <a href="https://belleyou.ru/catalog/podarochnaya-upakovka/">коробок</a></li>
                                <li>упаковать изделия в <a href="https://belleyou.ru/catalog/podarochnaya-upakovka/">подарочную коробку</a>&nbsp;(Подарочная упаковка приобретается отдельно. Добавьте, пожалуйста,&nbsp;<a href="https://belleyou.ru/catalog/podarochnaya-upakovka/">подарочную коробку</a>&nbsp;в заказ).</li>
                                <li>убрать бирки с ценой и товарный чек из упаковки</li>
                                <li>не звонить получателю заранее и сохранить ваш заказ в тайне до момента его вручения</li>
                            </ul>
                            <p>
                                Просто укажите в комментарии к заказу ваши пожелания и мы их исполним!<br>
                                Не забудьте дополнительно оставить свои контакты в комментарии к заказу.
                            </p>                       
                        </div>                        
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <!-- состав и уход -->
    <?if ((!empty($arResult["PROPERTIES"]['SAYT_SOSTAV']['VALUE']) && $arResult["PROPERTIES"]['SAYT_SOSTAV']['VALUE'] !== "-") || (!empty($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE']) && $arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'] !== "-")) { ?>
        <div class="popup popup-product-care">
            <div class="popup__backdrop" data-close-popup></div>
            <div class="popup-body">
                <header class="popup-header">
                    <button class="button-close-popup" data-close-popup></button>
                    <div class="h2 popup-title">состав и уход</div>
                </header>
                <div class="popup-content">
                    <div class="popup-content-inner">
                        <div class="in-popup-product">
                            <p><?= $arResult["PROPERTIES"]['SAYT_SOSTAV']['VALUE'] ?></p><br/>

                            <?$uhod_descr = str_replace(["#delicate_washing_30#","#dont_iron#","#dont_bleach#","#without_soap#","#without_dry_cleaning#","#dont_dry#","#drying_vertically#","#dont_wash_hands#","#without_rinseaid#","#cleaning_without_water#","#ironing_150#","#wash_wrongside#","#one_color#"],"",$arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE']);?>
                            <?if(!empty(trim($uhod_descr))){?>
                                <p><?= $uhod_descr; ?></p>
                            <?}?>
                            
                            <ul class="item-care-list">
                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#delicate_washing_30#") !== false) { ?>
                                    <li class="item-care item-care__1">Деликатная стирка при 30 градусах</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#dont_iron#") !== false) { ?>
                                    <li class="item-care item-care__2">Не гладить</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#dont_bleach#") !== false) { ?>
                                    <li class="item-care item-care__3">Не отбеливать</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#without_soap#") !== false) { ?>
                                    <li class="item-care item-care__4">Не стирать мылом</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#without_dry_cleaning#") !== false) { ?>
                                    <li class="item-care item-care__5">Не подвергать химчистке</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#dont_dry#") !== false) { ?>
                                    <li class="item-care item-care__6">Не использовать машинную сушку</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#drying_vertically#") !== false) { ?>
                                    <li class="item-care item-care__7">Сушить вертикально вдали от отопительных приборов</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#dont_wash_hands#") !== false) { ?>
                                    <li class="item-care item-care__8">Не стирать руками</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#without_rinseaid#") !== false) { ?>
                                    <li class="item-care item-care__9">Не применять смягчающий ополаскиватель</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#cleaning_without_water#") !== false) { ?>
                                    <li class="item-care item-care__10">Использовать сухую чистку</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#ironing_150#") !== false) { ?>
                                    <li class="item-care item-care__11">Гладить при температуре до 150 градусов</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#wash_wrongside#") !== false) { ?>
                                    <li class="item-care item-care__12">Стирать, вывернув наизнанку, с вещами похожего цвета</li>
                                <?php } ?>

                                <?php if (strpos($arResult["PROPERTIES"]['SAYT_UKHODZAIZDELIEM']['VALUE'], "#one_color#") !== false) { ?>
                                    <li class="item-care item-care__13">Не стирать светлые изделия с цветными</li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    <?}?>

    <!-- Возврат -->
    <div class="popup popup-product-return">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Возврат</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-product">
                        <p>Товар надлежащего качества можно вернуть при условии, что он не был в употреблении и сохранил все свои потребительские свойства, в течение 7 дней, не считая дня получения заказа.</p>
                        <p><a href="/for-customers/refund/" class="button">правила возврата</a></p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?/*
    <!-- наличие в магазинах -->
    <div class="popup popup-product-in-stores">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">наличие в магазинах</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">

                    <div class="in-popup-product">
                        <div class="h3 in-popup-city-title">ваш город</div>
                        <div class="dropdown dropdown-city">
                            <div class="dropdown-select">Москва</div>
                            <ul class="dropdown-box">
                                <li class="dropdown-option" data-label="1">Москва</li>
                                <li class="dropdown-option" data-label="2">Санкт-Петербург</li>
                                <li class="dropdown-option" data-label="3">Калуга</li>
                                <li class="dropdown-option" data-label="4">Новосибирск</li>
                            </ul>
                        </div>

                        <div class="in-popup-shops-list">
                            <div class="in-popup-shop">
                                <h4 class=shop-title>ТРЦ «Океания»</h4>
                                <a href="" class="shop-phone">+7 (499) 653-66-22</a>
                                <div class="shop-address">м. Славянский бульвар, Кутузовский проспект, 57. <br>
                                    1 этаж, рядом с 12storeez Men и AKHMADULLINA DREAMS</div>
                                <ul class="shop-sizes-list">
                                    <li><span class="shop-size">XS/S</span> <span class="shop-amount">Мало</span></li>
                                    <li><span class="shop-size">M/L</span> <span class="shop-amount">Мало</span></li>
                                    <li class="unavailable"><span class="shop-size">XL/XXL</span ><span class="shop-amount">Нет в наличии</span></li>
                                    <li class="unavailable"><span class="shop-size">3XL/4XL</span ><span class="shop-amount">Нет в наличии</span></li>
                                </ul>
                            </div>
                            <div class="in-popup-shop">
                                <h4 class=shop-title>ТРЦ «Павелецкая Плаза»</h4>
                                <a href="" class="shop-phone">+7 (499) 553-00-20</a>
                                <div class="shop-address">м. Павелецкая, Павелецкая площадь, 3. <br>-1 этаж, напротив Falconeri, Baldinini.</div>
                                <ul class="shop-sizes-list">
                                    <li><span class="shop-size">XS/S</span> <span class="shop-amount">Мало</span></li>
                                    <li><span class="shop-size">M/L</span> <span class="shop-amount"></span></li>
                                    <li><span class="shop-size">XL/XXL</span ><span class="shop-amount"></span></li>
                                    <li class="finished"><span class="shop-size">3XL/4XL</span ><span class="shop-amount">Нет в наличии</span></li>
                                </ul>
                            </div>
                            <div class="in-popup-shop">
                                <h4 class=shop-title>ТРЦ «Океания»</h4>
                                <a href="" class="shop-phone">+7 (499) 653-66-22</a>
                                <div class="shop-address">м. Славянский бульвар, Кутузовский проспект, 57. <br>
                                    1 этаж, рядом с 12storeez Men и AKHMADULLINA DREAMS</div>
                                <ul class="shop-sizes-list">
                                    <li><span class="shop-size">XS/S</span> <span class="shop-amount">Мало</span></li>
                                    <li><span class="shop-size">M/L</span> <span class="shop-amount">Мало</span></li>
                                    <li class="finished"><span class="shop-size">XL/XXL</span ><span class="shop-amount">Нет в наличии</span></li>
                                    <li class="finished"><span class="shop-size">3XL/4XL</span ><span class="shop-amount">Нет в наличии</span></li>
                                </ul>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
    */?>

    <!-- подели -->
    <div class="popup popup-product-podeli">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Оплата по частям</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-product">
                        <img src="<?=SITE_TEMPLATE_PATH?>/img/podeli.jpg" alt="">
                        <div class="podeli-footer">
                            <p>Подробнее на <a target="_blank" href="https://podeli.ru" rel="nofollow">podeli.ru</a></p>
                            <p>ООО А-4 Технологии, ОГРН 1227700064734</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?/*пример формы подписки
    <form id="bx-catalog-subscribe-form" style="display: none;">
        <input type="hidden" name="manyContact" value="N">
        <input type="hidden" name="sessid" value="cd71d2ac3f680a1afd26bec435bed8c8">
        <input type="hiddQen" name="itemId" value="34683">
        <input type="hidden" name="landingId" value="0">
        <input type="hidden" name="siteId" value="s1">
        <input type="hidden" name="contactFormSubmit" value="Y">
        <p id="bx-catalog-subscribe-form-notify"></p>
        <div id="bx-catalog-subscribe-form-container-1" class="bx-catalog-subscribe-form-container" style="">
            <div class="bx-catalog-subscribe-form-container-label">Укажите Ваш Email: </div>
            <div class="bx-catalog-subscribe-form-container-input">
                <input data-id="1" id="userContact" class="" type="text" name="contact[1][user]">
            </div>
        </div>
    </form>*/?>

    <?// сообщить о поступлении?>
    <div class="popup popup-product-back-in-stock">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">уведомить меня</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-product js-subscribe-step-email" style="display: none;">
                        <?/*<p>Вас интересует размер 3XL/4XL.</p>*/?>
                        <p>Оставьте свою электронную почту и мы сообщим, когда товар появиться в наличии.</p>
                        <p class="error_text js-subscribe-error" style="display: none;">Ошибка!</p>
                        <form action="" class="alert-back-in-stock">
                            <input type="hidden" name="manyContact" value="N">
                            <input type="hidden" name="sessid" value="<?=bitrix_sessid()?>">
                            <input type="hidden" name="itemId" value="<?=$actualItem['ID']?>">
                            <input type="hidden" name="landingId" value="0">
                            <input type="hidden" name="siteId" value="s1">
                            <input type="hidden" name="contactFormSubmit" value="Y">
                            <div class="form-row">
                                <input type="text"  data-id="1" class="form-input" placeholder="Эл. почта" id="userContact" name="contact[1][user]">
                            </div>
                            <div class="form-row">
                                <button type="button" class="button form-button button-product-size-subscribe">уведомить</button>
                            </div>
                        </form>
                    </div>

                    <div class="back-in-stock_subscribe-success js-subscribe-step-success" style="display: none;">
                        <p></p>
                        <?/*<p>Спасибо, запрос оформлен!</p>
                        <p>Если ваш размер снова поступит в продажу, мы сообщим об этом, отправив письмо на электронную почту test@test.ru</p>*/?>
                        <button  class="button" data-close-popup>продолжить покупки</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php /*
    <!-- выбрать размер mobile -->
    <div class="popup popup-vertical popup-product-size">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <button class="button-close-popup" data-close-popup></button>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="h2 popup-title">Выберите размер</div>
                    <a class="product-size-chart-link" data-popup="popup-sizechart-clothes">Таблица размеров</a>
                    <ul class="popup-sizes-list">
                        <li><span class="size">XS/S</span> <span class="size-amount">12шт. в наличии</span></li>
                        <li><span class="size">M/L</span> <span class="size-amount">Скоро закончится</span></li>
                        <li><span class="size">XL/XXL</span><span class="size-amount">26шт. в наличии</span></li>
                        <li class="unavailable" data-popup="popup-product-back-in-stock"><span class="size">3XL/4XL</span><span class="size-amount">Нет в наличии</span></li>
                    </ul>

                </div>
            </div>
        </div>
    </div>
    */?>

    <?/*<!-- отзывы -->
    <link rel="stylesheet" href="css/popup-item-review.css">
    <div class="popup popup-product-feedback">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">отзывы</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="popup-product-score">
                        <div class="product-score-total">4,2 / 5</div>
                        <div class="feed-short-rating rating-4"></div>
                        <span class="feed-short-link">14 отзывов</span>
                    </div>

                    <div class="popup-product-feedbacks-list">

                        <div class="popup-product-feedback-item">
                            <header class="feedback-item-header">
                                <h4 class="feedback-name">Елизавета</h4>
                                <span class="feedback-date">21.02.2023</span>
                                <div class="feed-short-rating rating-5"></div>
                            </header>
                            <div class="feedback-text">Прекрасный топ! На ОГ 88 см размер S/M комфортен и невесом. Белый просвечивает,но меня этот факт совсем не смущает! Не первая покупка данного производителя и я очень давольна! Для практик йогой 👌 и благодарю за отличную доставку!</div>
                            <footer class="feedback-item-footer">
                                <div class="popup-product-feedback-item-color"><span>Цвет:</span> серо-бежевый</div>
                                <div class="popup-product-feedback-item-size"><span>Размер:</span> S/М</div>
                                <div class="feeback-item-useful">
                                    <p class="feeback-item-useful-label">Отзыв был полезен?</p>
                                    <div class="feeback-item-useful-answers">
                                        <p><a class="answer">Да</a> <span>(0)</span></p>
                                        <p><a class="answer">Нет</a> <span>(0)</span></p>
                                    </div>
                                </div>
                            </footer>
                        </div>

                        <div class="popup-product-feedback-item">
                            <header class="feedback-item-header">
                                <h4 class="feedback-name">Аня</h4>
                                <span class="feedback-date">21.02.2023</span>
                                <div class="feed-short-rating rating-5"></div>
                            </header>
                            <div class="feedback-text">Прекрасный топ! На ОГ 88 см размер S/M комфортен и невесом. Белый просвечивает,но меня этот факт совсем не смущает! Не первая покупка данного производителя и я очень давольна! Для практик йогой 👌 и благодарю за отличную доставку!</div>
                            <footer class="feedback-item-footer">
                                <div class="popup-product-feedback-item-color"><span>Цвет:</span> серо-бежевый</div>
                                <div class="popup-product-feedback-item-size"><span>Размер:</span> S/М</div>
                                <div class="feeback-item-useful">
                                    <p class="feeback-item-useful-label">Отзыв был полезен?</p>
                                    <div class="feeback-item-useful-answers">
                                        <p><a class="answer">Да</a> <span>(0)</span></p>
                                        <p><a class="answer">Нет</a> <span>(0)</span></p>
                                    </div>
                                </div>
                            </footer>
                        </div>

                        <div class="popup-product-feedback-item">
                            <header class="feedback-item-header">
                                <h4 class="feedback-name">Светлана</h4>
                                <span class="feedback-date">18.10.2022</span>
                                <div class="feed-short-rating rating-5"></div>
                            </header>
                            <div class="feedback-text">Прекрасный топ! На ОГ 88 см размер S/M комфортен и невесом. Белый просвечивает,но меня этот факт совсем не смущает! Не первая покупка данного производителя и я очень давольна! Для практик йогой 👌 и благодарю за отличную доставку!</div>
                            <footer class="feedback-item-footer">
                                <div class="popup-product-feedback-item-color"><span>Цвет:</span> серо-бежевый</div>
                                <div class="popup-product-feedback-item-size"><span>Размер:</span> S/М</div>
                                <div class="feeback-item-useful">
                                    <p class="feeback-item-useful-label">Отзыв был полезен?</p>
                                    <div class="feeback-item-useful-answers">
                                        <p><a class="answer">Да</a> <span>(0)</span></p>
                                        <p><a class="answer">Нет</a> <span>(0)</span></p>
                                    </div>
                                </div>
                            </footer>
                        </div>
                    </div>
                </div>
                <footer class="popup-sticky-footer">
                    <button class="button" data-popup="popup-product-send-feedback">Оставить отзыв</button>
                </footer>
            </div>
        </div>
    </div>

    <!-- оставить отзыв -->
    <div class="popup popup-product-send-feedback">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <button class="button-back-popup" data-popup="popup-product-feedback"></button>
                <div class="h3 popup-title">Мой отзыв</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="send-feedback-form">
                        <header class="send-feedback-header">
                            <img src="<?=SITE_TEMPLATE_PATH?>/demo-pics/product1-451.jpg" width="80" height="120" alt="Товар" class="send-feedback-item-picture">
                            <h4 class="send-feedback-item-title">Топ–бюстьe invisible</h4>
                            <span class="send-feedback-item-color">Цвет: серо-бежевый</span>
                        </header>
                        <form action="">
                            <div class="send-feedback-score">
                                <h4 class="send-feedback-title">общая оценка</h4>
                                <div class="feed-short-rating rating-5"></div>
                            </div>
                            <div class="send-feedback-item-size">
                                <h4 class="send-feedback-title">размер</h4>
                                <ul class="product-sizes-list">
                                    <li class="product-size">XS/S</li>
                                    <li class="product-size">M/L</li>
                                    <li class="product-size">XL/XXL</li>
                                    <li class="product-size">3XL/4XL</li>
                                </ul>
                            </div>
                            <div class="send-feedback-item-accordance">
                                <h4 class="send-feedback-title">соответствие описанию</h4>

                                <ul class="send-feedback-accordance-radiogroup">
                                    <li><input class="input-radio" type="radio" name="accordance" id="radio1"><label class="label-radio" for="radio1">Да</label></li>
                                    <li><input class="input-radio" type="radio" name="accordance" id="radio2"><label class="label-radio" for="radio2">Нет</label></li>
                                    <li><input class="input-radio" type="radio" name="accordance" id="radio3"><label class="label-radio" for="radio3">Частично</label></li>
                                </ul>
                            </div>

                            <div class="send-feedback-item-text">
                                <h4 class="send-feedback-title">Немного о товаре</h4>
                                <span class="feedback-text-counter">85/600</span>
                                <textarea class="form-textarea" name="" id="" cols="30" rows="10">

              </textarea>
                            </div>
                        </form>
                    </div>
                </div>

                <footer class="popup-sticky-footer">
                    <button class="button" data-close-popup>отправить</button>
                </footer>
            </div>

        </div>
    </div>*/?>

    <?php // Таблицы размеров ?>
    <?php //  \Bitrix\Main\Page\Asset::getInstance()->addCss(SITE_TEMPLATE_PATH."/css/popup-sizechart.css?v=6",true);?>
    <link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/popup-sizechart.css?v=7">

    <?php //popup-sizechart-universal1?>
    <div class="popup popup-sizechart-sg2">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Таблица размеров</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-sizechart clothes">
                        <div class="h3">Белье и одежда (см)</div>
                        <div class="sizechart-table-wrapper">
                            <table class="table-sizes table-sizes-clothes">
                                <tr>
                                    <th>Размер RU</th>
                                    <td>42</td>
                                    <td>44</td>
                                    <td>46</td>
                                    <td>48</td>
                                    <td>50</td>
                                    <td>52</td>
                                </tr>
                                <tr>
                                    <th>Размер EU</th>
                                    <td>XS</td>
                                    <td>S</td>
                                    <td>M</td>
                                    <td>L</td>
                                    <td>XL</td>
                                    <td>2XL</td>
                                </tr>
                                <tr>
                                    <th>Грудь</th>
                                    <td>84</td>
                                    <td>88</td>
                                    <td>92</td>
                                    <td>96</td>
                                    <td>100</td>
                                    <td>104</td>
                                </tr>
                                <tr>
                                    <th>Талия</th>
                                    <td>64</td>
                                    <td>68</td>
                                    <td>72</td>
                                    <td>76</td>
                                    <td>80</td>
                                    <td>84</td>
                                </tr>
                                <tr>
                                    <th>Бедра</th>
                                    <td>92</td>
                                    <td>96</td>
                                    <td>100</td>
                                    <td>104</td>
                                    <td>108</td>
                                    <td>112</td>
                                </tr>
                                <tr>
                                    <th>Рост</th>
                                    <td>164</td>
                                    <td>170</td>
                                    <td>170</td>
                                    <td>170</td>
                                    <td>170</td>
                                    <td>170</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php //popup-sizechart-universal2?>
    <div class="popup popup-sizechart-sg1_sg3">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Таблица размеров</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-sizechart clothes">
                        <div class="h3">Белье и одежда (см)</div>
                        <div class="sizechart-table-wrapper">
                            <table class="table-sizes table-sizes-clothes-short">
                                <tr>
                                    <th>Размер RU</th>
                                    <td>42-44</td>
                                    <td>46-48</td>
                                    <td>50-52</td>
                                    <td>54-56</td>
                                </tr>
                                <tr>
                                    <th>Размер EU</th>
                                    <td>XS-S</td>
                                    <td>M-L</td>
                                    <td>XL-2XL</td>
                                    <td>3XL-4XL</td>
                                </tr>
                                <tr>
                                    <th>Грудь</th>
                                    <td>84-92</td>
                                    <td>92-100</td>
                                    <td>100-108</td>
                                    <td>108-116</td>
                                </tr>
                                <tr>
                                    <th>Талия</th>
                                    <td>64-72</td>
                                    <td>72-80</td>
                                    <td>80-88</td>
                                    <td>88-96</td>
                                </tr>
                                <tr>
                                    <th>Бедра</th>
                                    <td>92-100</td>
                                    <td>100-108</td>
                                    <td>108-116</td>
                                    <td>116-124</td>
                                </tr>
                                <tr>
                                    <th>Рост</th>
                                    <td>164</td>
                                    <td>170</td>
                                    <td>170</td>
                                    <td>170</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php //Белье и одежда (см) ?>
    <?/*
    <div class="popup popup-vertical popup-sizechart-clothes">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Таблица размеров</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-sizechart clothes">
                        <div class="h3">Белье и одежда (см)</div>
                        <div class="sizechart-table-wrapper">
                            <table class="table-sizes table-sizes-clothes">
                                <tr>
                                    <th>Размер RU</th>
                                    <td>42/44</td>
                                    <td>44/46</td>
                                    <td>46/48</td>
                                    <td>48/50</td>
                                    <td>50/52</td>
                                    <td>52/54</td>
                                    <td>54/56</td>
                                    <td>56/58</td>
                                    <td>58</td>
                                </tr>
                                <tr>
                                    <th>Размер EU</th>
                                    <td>XS/S</td>
                                    <td>S/M</td>
                                    <td>M/L</td>
                                    <td>L/XL</td>
                                    <td>XL/2XL</td>
                                    <td>2XL/3XL</td>
                                    <td>3XL/4XL</td>
                                    <td>4XL/5XL</td>
                                    <td>5XL</td>
                                </tr>
                                <tr>
                                    <th>Грудь</th>
                                    <td>84-88</td>
                                    <td>88-92</td>
                                    <td>92-96</td>
                                    <td>96-100</td>
                                    <td>100-104</td>
                                    <td>104-108</td>
                                    <td>108-112</td>
                                    <td>112-116</td>
                                    <td>116</td>
                                </tr>
                                <tr>
                                    <th>Талия</th>
                                    <td>64-68</td>
                                    <td>68-72</td>
                                    <td>72-76</td>
                                    <td>76-80</td>
                                    <td>80-84</td>
                                    <td>84-88</td>
                                    <td>88-92</td>
                                    <td>92-96</td>
                                    <td>96</td>
                                </tr>
                                <tr>
                                    <th>Бедра</th>
                                    <td>90-94</td>
                                    <td>94-98</td>
                                    <td>98-102</td>
                                    <td>102-106</td>
                                    <td>106-110</td>
                                    <td>110-114</td>
                                    <td>114-118</td>
                                    <td>118-122</td>
                                    <td>122</td>
                                </tr>
                                <tr>
                                    <th>Рост</th>
                                    <td>158-164</td>
                                    <td>158-164</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php //Белье и одежда sg2. Зачем-то переносим со старого сайта?>
    <div class="popup popup-vertical popup-sizechart-sg2">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Таблица размеров</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-sizechart clothes">
                        <div class="h3">Белье и одежда (см)</div>
                        <div class="sizechart-table-wrapper">
                            <table class="table-sizes table-sizes-clothes">
                                <tr>
                                    <th>Размер RU</th>
                                    <td>42</td>
                                    <td>44</td>
                                    <td>46</td>
                                    <td>48</td>
                                    <td>50</td>
                                    <td>52</td>
                                    <td>54</td>
                                    <td>56</td>
                                    <td>58</td>
                                </tr>
                                <tr>
                                    <th>Размер EU</th>
                                    <td>XS</td>
                                    <td>S</td>
                                    <td>M</td>
                                    <td>L</td>
                                    <td>XL</td>
                                    <td>2XL</td>
                                    <td>3XL</td>
                                    <td>4XL</td>
                                    <td>5XL</td>
                                </tr>
                                <tr>
                                    <th>Грудь</th>
                                    <td>84</td>
                                    <td>88</td>
                                    <td>92</td>
                                    <td>96</td>
                                    <td>100</td>
                                    <td>104</td>
                                    <td>108</td>
                                    <td>112</td>
                                    <td>116</td>
                                </tr>
                                <tr>
                                    <th>Талия</th>
                                    <td>64</td>
                                    <td>68</td>
                                    <td>72</td>
                                    <td>76</td>
                                    <td>80</td>
                                    <td>84</td>
                                    <td>88</td>
                                    <td>92</td>
                                    <td>96</td>
                                </tr>
                                <tr>
                                    <th>Бедра</th>
                                    <td>90</td>
                                    <td>94</td>
                                    <td>98</td>
                                    <td>102</td>
                                    <td>106</td>
                                    <td>110</td>
                                    <td>114</td>
                                    <td>118</td>
                                    <td>122</td>
                                </tr>
                                <tr>
                                    <th>Рост</th>
                                    <td>158-164</td>
                                    <td>158-164</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                    <td>164-170</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    */?>
    <?// для беременных?>
    <div class="popup popup-sizechart-mama">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Таблица размеров</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-sizechart pregnant">
                        <div class="h3">Белье и одежда</div>
                        <table class="table-sizes table-sizes-socks">
                            <tr>
                                <th>Размер RU</th>
                                <td>42/44</td>
                                <td>46/48</td>
                                <td>50/52</td>
                            </tr>
                            <tr>
                                <th>Размер EU</th>
                                <td>XS/S</td>
                                <td>M/L</td>
                                <td>XL/2XL</td>
                            </tr>
                            <tr>
                                <th>Грудь</th>
                                <td>82-96</td>
                                <td>90-106</td>
                                <td>96-114</td>
                            </tr>
                            <tr>
                                <th>Талия</th>
                                <td>62-78</td>
                                <td>70-86</td>
                                <td>78-94</td>
                            </tr>
                            <tr>
                                <th>Бедра</th>
                                <td>88-104</td>
                                <td>96-112</td>
                                <td>104-120</td>
                            </tr>
                        </table>


                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Носки -->
    <div class="popup popup-sizechart-socks">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Таблица размеров</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-sizechart socks">
                        <div class="h3">носки</div>
                        <table class="table-sizes table-sizes-socks">
                            <tr>
                                <th>Размер</th>
                                <td>23</td>
                                <td>25</td>
                                <td>OS</td>
                            <tr>
                                <th>Размер обуви</th>
                                <td>35-37</td>
                                <td>38-40</td>
                                <td>35-40</td>
                            <tr>
                                <th>Стопа</th>
                                <td>21,3-23,3</td>
                                <td>23,3-25,3</td>
                                <td>21,3-25,3</td>
                            </tr>
                        </table>

                        <div class="h3">Колготки</div>
                        <table class="table-sizes table-sizes-pants">
                            <tr>
                                <th>Размер</th>
                                <td>2</td>
                                <td>3</td>
                                <td>4</td>
                            </tr>
                            <tr>
                                <th>Рост</th>
                                <td>150-165</td>
                                <td>165-175</td>
                                <td>175-180</td>
                            </tr>
                            <tr>
                                <th>Бедра</th>
                                <td>90-94</td>
                                <td>94-98</td>
                                <td>98-102</td>
                            </tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Боди (см) -->
    <div class="popup popup-sizechart-body">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Таблица размеров</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-sizechart body">
                        <div class="h3">Боди (см)</div>
                        <table class="table-sizes table-sizes-body">
                            <tr>
                                <th>Размер RU</th>
                                <td>42</td>
                                <td>44</td>
                                <td>46</td>
                                <td>48</td>
                            <tr>
                                <th>Размер EU</th>
                                <td>XS</td>
                                <td>S</td>
                                <td>M</td>
                                <td>L</td>
                            <tr>
                                <th>Грудь</th>
                                <td>84</td>
                                <td>88</td>
                                <td>92</td>
                                <td>96</td>
                            </tr>
                            <tr>
                                <th>Талия</th>
                                <td>64</td>
                                <td>68</td>
                                <td>72</td>
                                <td>76</td>
                            </tr>
                            <tr>
                                <th>Бедра</th>
                                <td>90</td>
                                <td>94</td>
                                <td>98</td>
                                <td>102</td>
                            </tr>
                            <tr>
                                <th>Рост</th>
                                <td>158-164</td>
                                <td>164-170</td>
                                <td>164-171</td>
                                <td>164-172</td>
                            </tr>
                            <tr>
                                <th>Чашечка</th>
                                <td>70B</td>
                                <td>75B</td>
                                <td>80B</td>
                                <td>85B</td>
                            </tr>
                            <tr>
                                <th>Смежные размеры</th>
                                <td>65C</td>
                                <td>70C</td>
                                <td>75C</td>
                                <td>80C</td>
                            </tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?/*
    <!-- Invisible (см) -->
    <div class="popup popup-sizechart-invisible">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Таблица размеров</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-sizechart invisible">
                        <div class="h3">Invisible (см)</div>
                        <table class="table-sizes table-sizes-invisible">
                            <tr>
                                <th>Размер RU</th>
                                <td>42/44</td>
                                <td>46/48</td>
                                <td>50/52</td>
                            </tr>
                            <tr>
                                <th>Размер EU</th>
                                <td>XS/S</td>
                                <td>M/L</td>
                                <td>XL/2XL</td>
                            </tr>
                            <tr>
                                <th>Грудь</th>
                                <td>82-98</td>
                                <td>90-106</td>
                                <td>98-114</td>
                            </tr>
                            <tr>
                                <th>Талия</th>
                                <td>62-78</td>
                                <td>70-86</td>
                                <td>78-94</td>
                            </tr>
                            <tr>
                                <th>Бедра</th>
                                <td>88-104</td>
                                <td>96-112</td>
                                <td>104-120</td>
                            </tr>
                            <tr>
                                <th>Рост</th>
                                <td>158-164</td>
                                <td>164-170</td>
                                <td>164-170</td>
                            </tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
    */?>
    <!-- Трусы мужские -->
    <div class="popup popup-sizechart-pants-man">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <header class="popup-header">
                <button class="button-close-popup" data-close-popup></button>
                <div class="h2 popup-title">Таблица размеров</div>
            </header>
            <div class="popup-content">
                <div class="popup-content-inner">
                    <div class="in-popup-sizechart pants-man" style="padding-bottom: 150px;">
                        <div class="h3">Белье и одежда</div>
                        <div class="sizechart-table-wrapper">
                            <table class="table-sizes table-sizes-pants-man">
                                <tbody>
                                <tr>
                                    <th>Размер RU</th>
                                    <td>44</td>
                                    <td>46</td>
                                    <td>48</td>
                                    <td>50</td>
                                    <td>52</td>
                                    <td>54</td>
                                    <td>56</td>
                                    <td>58</td>
                                    <td>60</td>
                                </tr>
                                <tr>
                                    <th>Размер EU</th>
                                    <td>XS</td>
                                    <td>S</td>
                                    <td>M</td>
                                    <td>L</td>
                                    <td>XL</td>
                                    <td>2XL</td>
                                    <td>3XL</td>
                                    <td>4XL</td>
                                    <td>5XL</td>
                                </tr>
                                <tr>
                                    <th>Обхват<br> груди (см)</th>
                                    <td>88</td>
                                    <td>92</td>
                                    <td>96</td>
                                    <td>100</td>
                                    <td>104</td>
                                    <td>108</td>
                                    <td>112</td>
                                    <td>116</td>
                                    <td>120</td>
                                </tr>
                                <tr>
                                    <th>Обхват<br> талии (см)</th>
                                    <td>76</td>
                                    <td>80</td>
                                    <td>84</td>
                                    <td>88</td>
                                    <td>92</td>
                                    <td>96</td>
                                    <td>100</td>
                                    <td>104</td>
                                    <td>108</td>
                                </tr>
                                <tr>
                                    <th>Обхват<br> бедер (см)</th>
                                    <td>88</td>
                                    <td>92</td>
                                    <td>96</td>
                                    <td>100</td>
                                    <td>104</td>
                                    <td>108</td>
                                    <td>112</td>
                                    <td>116</td>
                                    <td>120</td>
                                </tr>
                                <tr>
                                    <th>Рост<br> (см)</th>
                                    <td>182</td>
                                    <td>182</td>
                                    <td>182</td>
                                    <td>182</td>
                                    <td>182</td>
                                    <td>182</td>
                                    <td>182</td>
                                    <td>182</td>
                                    <td>182</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="h3" style="margin-top: 20px;">носки</div>
                        <?/*<div class="sizechart-table-wrapper">*/?>
                            <table class="table-sizes table-sizes-socks">
                                <tr>
                                    <th>Размер</th>
                                    <td>27</td>
                                    <td>29</td>

                                <tr>
                                    <th>Размер обуви</th>
                                    <td>42-43</td>
                                    <td>44-45</td>

                                <tr>
                                    <th>Стопа</th>
                                    <td>26,0-27,3</td>
                                    <td>27,3-28,8</td>

                                </tr>
                            </table>
                        <?/*</div>*/?>

                    </div>
                </div>

            </div>
        </div>
    </div>
    <?//*modal?>

<?php
if ($haveOffers)
{
    $offerIds = array();
    $offerCodes = array();

    $useRatio = $arParams['USE_RATIO_IN_RANGES'] === 'Y';

    foreach ($arResult['JS_OFFERS'] as $ind => &$jsOffer)
    {

        //если остаток у товара меньше 3, то делаем товар не доступным
       /* if (intval($jsOffer['MAX_QUANTITY']) <= 3) {
            $jsOffer['REAL_MAX_QUANTITY'] = $jsOffer['MAX_QUANTITY'];
            $jsOffer['MAX_QUANTITY'] = '0';
            $jsOffer['CAN_BUY'] = false;
        }*/

        $offerIds[] = (int)$jsOffer['ID'];
        $offerCodes[] = $jsOffer['CODE'];

        $fullOffer = $arResult['OFFERS'][$ind];
        $measureName = $fullOffer['ITEM_MEASURE']['TITLE'];

        $jsOffer['MODEL_PARAMS_TEXT'] = $fullOffer['PROPERTIES']['SAYT_PARAMETRYMODELI']['VALUE'];
        $jsOffer['COLOR_NAME'] = $fullOffer['COLOR_NAME'];
        $jsOffer['MORE_PHOTO_RESIZE'] = $fullOffer['MORE_PHOTO_RESIZE'];

        $strAllProps = '';
        $strMainProps = '';
        $strPriceRangesRatio = '';
        $strPriceRanges = '';

        if ($arResult['SHOW_OFFERS_PROPS'])
        {
            if (!empty($jsOffer['DISPLAY_PROPERTIES']))
            {
                foreach ($jsOffer['DISPLAY_PROPERTIES'] as $property)
                {
                    $current = '<dt>'.$property['NAME'].'</dt><dd>'.(
                        is_array($property['VALUE'])
                            ? implode(' / ', $property['VALUE'])
                            : $property['VALUE']
                        ).'</dd>';
                    $strAllProps .= $current;

                    if (isset($arParams['MAIN_BLOCK_OFFERS_PROPERTY_CODE'][$property['CODE']]))
                    {
                        $strMainProps .= $current;
                    }
                }

                unset($current);
            }
        }

        if ($arParams['USE_PRICE_COUNT'] && count($jsOffer['ITEM_QUANTITY_RANGES']) > 1)
        {
            $strPriceRangesRatio = '('.Loc::getMessage(
                    'CT_BCE_CATALOG_RATIO_PRICE',
                    array('#RATIO#' => ($useRatio
                            ? $fullOffer['ITEM_MEASURE_RATIOS'][$fullOffer['ITEM_MEASURE_RATIO_SELECTED']]['RATIO']
                            : '1'
                        ).' '.$measureName)
                ).')';

            foreach ($jsOffer['ITEM_QUANTITY_RANGES'] as $range)
            {
                if ($range['HASH'] !== 'ZERO-INF')
                {
                    $itemPrice = false;

                    foreach ($jsOffer['ITEM_PRICES'] as $itemPrice)
                    {
                        if ($itemPrice['QUANTITY_HASH'] === $range['HASH'])
                        {
                            break;
                        }
                    }

                    if ($itemPrice)
                    {
                        $strPriceRanges .= '<dt>'.Loc::getMessage(
                                'CT_BCE_CATALOG_RANGE_FROM',
                                array('#FROM#' => $range['SORT_FROM'].' '.$measureName)
                            ).' ';

                        if (is_infinite($range['SORT_TO']))
                        {
                            $strPriceRanges .= Loc::getMessage('CT_BCE_CATALOG_RANGE_MORE');
                        }
                        else
                        {
                            $strPriceRanges .= Loc::getMessage(
                                'CT_BCE_CATALOG_RANGE_TO',
                                array('#TO#' => $range['SORT_TO'].' '.$measureName)
                            );
                        }

                        $strPriceRanges .= '</dt><dd>'.($useRatio ? $itemPrice['PRINT_RATIO_PRICE'] : $itemPrice['PRINT_PRICE']).'</dd>';
                    }
                }
            }

            unset($range, $itemPrice);
        }

        $jsOffer['DISPLAY_PROPERTIES'] = $strAllProps;
        $jsOffer['DISPLAY_PROPERTIES_MAIN_BLOCK'] = $strMainProps;
        $jsOffer['PRICE_RANGES_RATIO_HTML'] = $strPriceRangesRatio;
        $jsOffer['PRICE_RANGES_HTML'] = $strPriceRanges;
    }

    $templateData['OFFER_IDS'] = $offerIds;
    $templateData['OFFER_CODES'] = $offerCodes;
    unset($jsOffer, $strAllProps, $strMainProps, $strPriceRanges, $strPriceRangesRatio, $useRatio);

    $jsParams = array(
        'CONFIG' => array(
            'USE_SUBSCRIBE' => $showSubscribe,
            'USE_CATALOG' => $arResult['CATALOG'],
            'SHOW_QUANTITY' => $arParams['USE_PRODUCT_QUANTITY'],
            'SHOW_PRICE' => true,
            'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'] === 'Y',
            'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'] === 'Y',
            'USE_PRICE_COUNT' => $arParams['USE_PRICE_COUNT'],
            'DISPLAY_COMPARE' => $arParams['DISPLAY_COMPARE'],
            'SHOW_SKU_PROPS' => $arResult['SHOW_OFFERS_PROPS'],
            'OFFER_GROUP' => $arResult['OFFER_GROUP'],
            'MAIN_PICTURE_MODE' => $arParams['DETAIL_PICTURE_MODE'],
            'ADD_TO_BASKET_ACTION' => $arParams['ADD_TO_BASKET_ACTION'],
            'SHOW_CLOSE_POPUP' => $arParams['SHOW_CLOSE_POPUP'] === 'Y',
            'SHOW_MAX_QUANTITY' => $arParams['SHOW_MAX_QUANTITY'],
            'RELATIVE_QUANTITY_FACTOR' => $arParams['RELATIVE_QUANTITY_FACTOR'],
            'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'],
            'USE_STICKERS' => true,
            //'SHOW_SLIDER' => $arParams['SHOW_SLIDER'],
            //'SLIDER_INTERVAL' => $arParams['SLIDER_INTERVAL'],
            'ALT' => $alt,
            'TITLE' => $title,
            'MAGNIFIER_ZOOM_PERCENT' => 200,
            'USE_ENHANCED_ECOMMERCE' => $arParams['USE_ENHANCED_ECOMMERCE'],
            'DATA_LAYER_NAME' => $arParams['DATA_LAYER_NAME'],
            'BRAND_PROPERTY' => !empty($arResult['DISPLAY_PROPERTIES'][$arParams['BRAND_PROPERTY']])
                ? $arResult['DISPLAY_PROPERTIES'][$arParams['BRAND_PROPERTY']]['DISPLAY_VALUE']
                : null,
            'SHOW_SKU_DESCRIPTION' => $arParams['SHOW_SKU_DESCRIPTION'],
            'DISPLAY_PREVIEW_TEXT_MODE' => $arParams['DISPLAY_PREVIEW_TEXT_MODE']
        ),
        'PRODUCT_TYPE' => $arResult['PRODUCT']['TYPE'],
        'VISUAL' => $itemIds,
        'DEFAULT_PICTURE' => array(
            'PREVIEW_PICTURE' => $arResult['DEFAULT_PICTURE'],
            'DETAIL_PICTURE' => $arResult['DEFAULT_PICTURE']
        ),
        'PRODUCT' => array(
            'ID' => $arResult['ID'],
            'ACTIVE' => $arResult['ACTIVE'],
            'NAME' => $arResult['~NAME'],
            'CATEGORY' => $arResult['CATEGORY_PATH'],
            'DETAIL_TEXT' => $arResult['DETAIL_TEXT'],
            'DETAIL_TEXT_TYPE' => $arResult['DETAIL_TEXT_TYPE'],
            'PREVIEW_TEXT' => $arResult['PREVIEW_TEXT'],
            'PREVIEW_TEXT_TYPE' => $arResult['PREVIEW_TEXT_TYPE']
        ),
        'BASKET' => array(
            'QUANTITY' => $arParams['PRODUCT_QUANTITY_VARIABLE'],
            'BASKET_URL' => $arParams['BASKET_URL'],
            'SKU_PROPS' => $arResult['OFFERS_PROP_CODES'],
            'ADD_URL_TEMPLATE' => $arResult['~ADD_URL_TEMPLATE'],
            'BUY_URL_TEMPLATE' => $arResult['~BUY_URL_TEMPLATE']
        ),
        'OFFERS' => $arResult['JS_OFFERS'],
        'OFFER_SELECTED' => $arResult['OFFERS_SELECTED'],
        'TREE_PROPS' => $skuProps
    );

}
else
{
    $emptyProductProperties = empty($arResult['PRODUCT_PROPERTIES']);
    if ($arParams['ADD_PROPERTIES_TO_BASKET'] === 'Y' && !$emptyProductProperties)
    {
        ?>
        <div id="<?=$itemIds['BASKET_PROP_DIV']?>" style="display: none;">
            <?php
            if (!empty($arResult['PRODUCT_PROPERTIES_FILL']))
            {
                foreach ($arResult['PRODUCT_PROPERTIES_FILL'] as $propId => $propInfo)
                {
                    ?>
                    <input type="hidden" name="<?=$arParams['PRODUCT_PROPS_VARIABLE']?>[<?=$propId?>]" value="<?=htmlspecialcharsbx($propInfo['ID'])?>">
                    <?php
                    unset($arResult['PRODUCT_PROPERTIES'][$propId]);
                }
            }

            $emptyProductProperties = empty($arResult['PRODUCT_PROPERTIES']);
            if (!$emptyProductProperties)
            {
                ?>
                <table>
                    <?php
                    foreach ($arResult['PRODUCT_PROPERTIES'] as $propId => $propInfo)
                    {
                        ?>
                        <tr>
                            <td><?=$arResult['PROPERTIES'][$propId]['NAME']?></td>
                            <td>
                                <?php
                                if (
                                    $arResult['PROPERTIES'][$propId]['PROPERTY_TYPE'] === 'L'
                                    && $arResult['PROPERTIES'][$propId]['LIST_TYPE'] === 'C'
                                )
                                {
                                    foreach ($propInfo['VALUES'] as $valueId => $value)
                                    {
                                        ?>
                                        <label>
                                            <input type="radio" name="<?=$arParams['PRODUCT_PROPS_VARIABLE']?>[<?=$propId?>]"
                                                   value="<?=$valueId?>" <?=($valueId == $propInfo['SELECTED'] ? '"checked"' : '')?>>
                                            <?=$value?>
                                        </label>
                                        <br>
                                        <?php
                                    }
                                }
                                else
                                {
                                    ?>
                                    <select name="<?=$arParams['PRODUCT_PROPS_VARIABLE']?>[<?=$propId?>]">
                                        <?php
                                        foreach ($propInfo['VALUES'] as $valueId => $value)
                                        {
                                            ?>
                                            <option value="<?=$valueId?>" <?=($valueId == $propInfo['SELECTED'] ? '"selected"' : '')?>>
                                                <?=$value?>
                                            </option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <?php
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <?php
            }
            ?>
        </div>
        <?php
    }
    $jsParams = array(
        'CONFIG' => array(
            'USE_CATALOG' => $arResult['CATALOG'],
            'SHOW_QUANTITY' => $arParams['USE_PRODUCT_QUANTITY'],
            'SHOW_PRICE' => !empty($arResult['ITEM_PRICES']),
            'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'] === 'Y',
            'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'] === 'Y',
            'USE_PRICE_COUNT' => $arParams['USE_PRICE_COUNT'],
            'DISPLAY_COMPARE' => $arParams['DISPLAY_COMPARE'],
            'MAIN_PICTURE_MODE' => $arParams['DETAIL_PICTURE_MODE'],
            'ADD_TO_BASKET_ACTION' => $arParams['ADD_TO_BASKET_ACTION'],
            'SHOW_CLOSE_POPUP' => $arParams['SHOW_CLOSE_POPUP'] === 'Y',
            'SHOW_MAX_QUANTITY' => $arParams['SHOW_MAX_QUANTITY'],
            'RELATIVE_QUANTITY_FACTOR' => $arParams['RELATIVE_QUANTITY_FACTOR'],
            'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'],
            'USE_STICKERS' => true,
            'USE_SUBSCRIBE' => $showSubscribe,
            'ALT' => $alt,
            'TITLE' => $title,
            'MAGNIFIER_ZOOM_PERCENT' => 200,
            'USE_ENHANCED_ECOMMERCE' => $arParams['USE_ENHANCED_ECOMMERCE'],
            'DATA_LAYER_NAME' => $arParams['DATA_LAYER_NAME'],
            'BRAND_PROPERTY' => !empty($arResult['DISPLAY_PROPERTIES'][$arParams['BRAND_PROPERTY']])
                ? $arResult['DISPLAY_PROPERTIES'][$arParams['BRAND_PROPERTY']]['DISPLAY_VALUE']
                : null
        ),
        'VISUAL' => $itemIds,
        'PRODUCT_TYPE' => $arResult['PRODUCT']['TYPE'],
        'PRODUCT' => array(
            'ID' => $arResult['ID'],
            'ACTIVE' => $arResult['ACTIVE'],
            'PICT' => reset($arResult['MORE_PHOTO']),
            'NAME' => $arResult['~NAME'],
            'SUBSCRIPTION' => true,
            'ITEM_PRICE_MODE' => $arResult['ITEM_PRICE_MODE'],
            'ITEM_PRICES' => $arResult['ITEM_PRICES'],
            'ITEM_PRICE_SELECTED' => $arResult['ITEM_PRICE_SELECTED'],
            'ITEM_QUANTITY_RANGES' => $arResult['ITEM_QUANTITY_RANGES'],
            'ITEM_QUANTITY_RANGE_SELECTED' => $arResult['ITEM_QUANTITY_RANGE_SELECTED'],
            'ITEM_MEASURE_RATIOS' => $arResult['ITEM_MEASURE_RATIOS'],
            'ITEM_MEASURE_RATIO_SELECTED' => $arResult['ITEM_MEASURE_RATIO_SELECTED'],
            'SLIDER_COUNT' => $arResult['MORE_PHOTO_COUNT'],
            'SLIDER' => $arResult['MORE_PHOTO'],
            'CAN_BUY' => $arResult['CAN_BUY'],
            'CHECK_QUANTITY' => $arResult['CHECK_QUANTITY'],
            'QUANTITY_FLOAT' => is_float($arResult['ITEM_MEASURE_RATIOS'][$arResult['ITEM_MEASURE_RATIO_SELECTED']]['RATIO']),
            'MAX_QUANTITY' => $arResult['PRODUCT']['QUANTITY'],
            'STEP_QUANTITY' => $arResult['ITEM_MEASURE_RATIOS'][$arResult['ITEM_MEASURE_RATIO_SELECTED']]['RATIO'],
            'CATEGORY' => $arResult['CATEGORY_PATH']
        ),
        'BASKET' => array(
            'ADD_PROPS' => $arParams['ADD_PROPERTIES_TO_BASKET'] === 'Y',
            'QUANTITY' => $arParams['PRODUCT_QUANTITY_VARIABLE'],
            'PROPS' => $arParams['PRODUCT_PROPS_VARIABLE'],
            'EMPTY_PROPS' => $emptyProductProperties,
            'BASKET_URL' => $arParams['BASKET_URL'],
            'ADD_URL_TEMPLATE' => $arResult['~ADD_URL_TEMPLATE'],
            'BUY_URL_TEMPLATE' => $arResult['~BUY_URL_TEMPLATE']
        )
    );
    unset($emptyProductProperties);
}

if ($arParams['DISPLAY_COMPARE'])
{
    $jsParams['COMPARE'] = array(
        'COMPARE_URL_TEMPLATE' => $arResult['~COMPARE_URL_TEMPLATE'],
        'COMPARE_DELETE_URL_TEMPLATE' => $arResult['~COMPARE_DELETE_URL_TEMPLATE'],
        'COMPARE_PATH' => $arParams['COMPARE_PATH']
    );
}

$jsParams["IS_FACEBOOK_CONVERSION_CUSTOMIZE_PRODUCT_EVENT_ENABLED"] =
    $arResult["IS_FACEBOOK_CONVERSION_CUSTOMIZE_PRODUCT_EVENT_ENABLED"]
;

?>
    <script>
        BX.message({
            ECONOMY_INFO_MESSAGE: '<?=GetMessageJS('CT_BCE_CATALOG_ECONOMY_INFO2')?>',
            TITLE_ERROR: '<?=GetMessageJS('CT_BCE_CATALOG_TITLE_ERROR')?>',
            TITLE_BASKET_PROPS: '<?=GetMessageJS('CT_BCE_CATALOG_TITLE_BASKET_PROPS')?>',
            BASKET_UNKNOWN_ERROR: '<?=GetMessageJS('CT_BCE_CATALOG_BASKET_UNKNOWN_ERROR')?>',
            BTN_SEND_PROPS: '<?=GetMessageJS('CT_BCE_CATALOG_BTN_SEND_PROPS')?>',
            BTN_MESSAGE_DETAIL_BASKET_REDIRECT: '<?=GetMessageJS('CT_BCE_CATALOG_BTN_MESSAGE_BASKET_REDIRECT')?>',
            BTN_MESSAGE_CLOSE: '<?=GetMessageJS('CT_BCE_CATALOG_BTN_MESSAGE_CLOSE')?>',
            BTN_MESSAGE_DETAIL_CLOSE_POPUP: '<?=GetMessageJS('CT_BCE_CATALOG_BTN_MESSAGE_CLOSE_POPUP')?>',
            TITLE_SUCCESSFUL: '<?=GetMessageJS('CT_BCE_CATALOG_ADD_TO_BASKET_OK')?>',
            COMPARE_MESSAGE_OK: '<?=GetMessageJS('CT_BCE_CATALOG_MESS_COMPARE_OK')?>',
            COMPARE_UNKNOWN_ERROR: '<?=GetMessageJS('CT_BCE_CATALOG_MESS_COMPARE_UNKNOWN_ERROR')?>',
            COMPARE_TITLE: '<?=GetMessageJS('CT_BCE_CATALOG_MESS_COMPARE_TITLE')?>',
            BTN_MESSAGE_COMPARE_REDIRECT: '<?=GetMessageJS('CT_BCE_CATALOG_BTN_MESSAGE_COMPARE_REDIRECT')?>',
            PRODUCT_GIFT_LABEL: '<?=GetMessageJS('CT_BCE_CATALOG_PRODUCT_GIFT_LABEL')?>',
            PRICE_TOTAL_PREFIX: '<?=GetMessageJS('CT_BCE_CATALOG_MESS_PRICE_TOTAL_PREFIX')?>',
            RELATIVE_QUANTITY_MANY: '<?=CUtil::JSEscape($arParams['MESS_RELATIVE_QUANTITY_MANY'])?>',
            RELATIVE_QUANTITY_FEW: '<?=CUtil::JSEscape($arParams['MESS_RELATIVE_QUANTITY_FEW'])?>',
            SITE_ID: '<?=CUtil::JSEscape($component->getSiteId())?>'
        });

        var <?=$obName?> = new JCCatalogElement(<?=CUtil::PhpToJSObject($jsParams, false, true)?>);
    </script>
    <script>
        var jsArResultCountersOb = {
            googleAdd2cart: '<?=$add2cart?>',
            iblockSectionId: <?=$arResult['IBLOCK_SECTION_ID']?>,
            //JS_OFFERS: <?//=CUtil::PhpToJSObject($arResult['JS_OFFERS'], false, false, true)?>,
            //sizes: <?//=CUtil::PhpToJSObject($arResult['SKU_PROPS']['_SIZE']['VALUES'], false, false, true)?>,
            /*ACTUAL_ITEM: <?//=CUtil::PhpToJSObject($actualItem, false, false, true)?>,
            arResMoer: <?//=CUtil::PhpToJSObject($arResult['PROPERTIES']['MORE_PHOTO'], false, false, true)?>,
            sizes: <?//=CUtil::PhpToJSObject($arResult['SKU_PROPS']['_SIZE']['VALUES'], false, false, true)?>,
            colors: <?//=CUtil::PhpToJSObject($arResult['SKU_PROPS']['_COLOR']['VALUES'], false, false, true)?>,*/
        }
        /*console.log('jsArResultCountersOb')
        console.log(jsArResultCountersOb)*/
    </script>
<?php
unset($actualItem, $itemIds, $jsParams);