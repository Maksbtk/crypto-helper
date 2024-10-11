<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;
use  \kb\service\Settings;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $item
 * @var array $actualItem
 * @var array $minOffer
 * @var array $itemIds
 * @var array $price
 * @var array $measureRatio
 * @var bool $haveOffers
 * @var bool $showSubscribe
 * @var array $morePhoto
 * @var bool $showSlider
 * @var bool $itemHasDetailUrl
 * @var string $imgTitle
 * @var string $productTitle
 * @var string $buttonSizeClass
 * @var string $discountPositionClass
 * @var string $labelPositionClass
 * @var CatalogSectionComponent $component
 */

if ($haveOffers)
{
	$showDisplayProps = !empty($item['DISPLAY_PROPERTIES']);
	$showProductProps = $arParams['PRODUCT_DISPLAY_MODE'] === 'Y' && $item['OFFERS_PROPS_DISPLAY'];
	$showPropsBlock = $showDisplayProps || $showProductProps;
	$showSkuBlock = $arParams['PRODUCT_DISPLAY_MODE'] === 'Y' && !empty($item['OFFERS_PROP']);
}
else
{
	$showDisplayProps = !empty($item['DISPLAY_PROPERTIES']);
	$showProductProps = $arParams['ADD_PROPERTIES_TO_BASKET'] === 'Y' && !empty($item['PRODUCT_PROPERTIES']);
	$showPropsBlock = $showDisplayProps || $showProductProps;
	$showSkuBlock = false;
}

?>

<?
$colors = $arParams['SORTED_COLOR'];

foreach($item["OFFERS"] as $key => $curr_offer){
    if($curr_offer["PRODUCT"]["AVAILABLE"] == "Y"){
        $pk = $key;
    }
}

$userIsAuth = $USER->IsAuthorized();

#$detailPageUrlWithOfferId = $item['DETAIL_PAGE_URL'].'?pid='.$item['OFFERS'][$pk]['ID'];


?>

<div class="product-media-wrapper">
    <a class="product-link" href="<?=$item['DETAIL_PAGE_URL']?>" title="<?=$productTitle?>">
        <?
        if(!empty($item["OFFERS"][$pk]['DETAIL_PICTURE']['ID'])){
            $image = CFile::ResizeImageGet($item["OFFERS"][$pk]['DETAIL_PICTURE']['ID'], ["width" => 451, "height" => 678], BX_RESIZE_IMAGE_EXACT, true);
        }else{
            $image = CFile::ResizeImageGet($item['PREVIEW_PICTURE']['ID'], ["width" => 451, "height" => 678], BX_RESIZE_IMAGE_EXACT, true);
        }
        #
        #$image["src"] = str_replace('http:', 'https:', $image["src"]);        
        ?>
        
        <div class="product-picture-wrapper">
            <img class="product-picture" src="<?=$image["src"]?>" alt="<?=$productTitle?>">
        </div>
    </a>
    <a class="button-add-to-favorite <?if($userIsAuth):?>  js-check-wishlist-button<?endif;?>"
       data-id="<?=$item['ID']?>"
       <?if(!$userIsAuth):?> data-popup="popup-go-to-auth"<?endif;?>
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16"><path stroke="#A0BCD2" stroke-linecap="round" stroke-linejoin="round" d="M15.023.53c-2.047-.23-4.25.912-5.024 2.618C9.225 1.442 7 .301 4.976.53 2.28.823.553 3.57 1.099 6.22c.744 3.604 4.801 5.214 8.9 9.281 4.081-4.067 8.156-5.683 8.9-9.282.547-2.65-1.182-5.395-3.876-5.689z"/></svg>
    </a>
   <?/* <div class="product-labels">
        <span class="product-label">-15%</span>

    </div>*/?>
</div>
                      
<div class="product-info-wrapper">
    <a class="product-link" href="<?=$item['DETAIL_PAGE_URL']?>" title="<?=$productTitle?>"><div class="h4 product-name"><?=$item['PROPERTIES']['SAYT_NAIMENOVANIE_DLYA_SAYTA']['VALUE']?></div></a>
    <?//echo '<pre>'; var_dump($price); echo '</pre>';?>
    <?if(empty($price)){
        $pricePrint = $item["OFFERS"][0]["ITEM_PRICES"][0]["PRINT_RATIO_PRICE"];
    }else{
        $pricePrint = $price['PRINT_RATIO_PRICE'];
    }?>
    
    <?if(!empty($item['OFFERS'][0]["ITEM_PRICES"][0]["PRICE"])){
        $item['MIN_PRICE']['VALUE'] = $item['OFFERS'][0]["ITEM_PRICES"][0]["PRICE"];
    }elseif(!empty($item['ITEM_PRICES'][0]['PRICE'])){
        $item['MIN_PRICE']['VALUE'] = $item['ITEM_PRICES'][0]['PRICE'];
    }else{
        $item['MIN_PRICE']['VALUE'] = $item['PROPERTIES']['MINIMUM_PRICE']['VALUE'];    
    }
    
    $minPrice[] = $item['MIN_PRICE']['VALUE'];?>

    <div class="product-pricebox">
        <span class="proudct-current-price"><?=$pricePrint?></span>
        <!--            <span class="proudct-current-price"><?/*= number_format($sale_price, 0, ' ', ' ') */?> ₽</span>
-->
    </div>
    
</div>