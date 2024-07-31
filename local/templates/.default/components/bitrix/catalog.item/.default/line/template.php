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

###SALE DATA
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
}
##
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
    <div class="product-labels">
        <?if($show_sale || $show_presale){?>
            <?if (in_array($item['ID'],$ids15)) {?><span class="product-label">-15%</span><?}?>
            <?if (in_array($item['ID'],$ids20)) {?><span class="product-label">-20%</span><?}?>
            <?if (in_array($item['ID'],$ids25)) {?><span class="product-label">-25%</span><?}?>
            <?if (in_array($item['ID'],$ids30)) {?><span class="product-label">-30%</span><?}?>
            <?if (in_array($item['ID'],$ids35)) {?><span class="product-label">-35%</span><?}?>
            <?if (in_array($item['ID'],$ids40)) {?><span class="product-label">-40%</span><?}?>
            <?if (in_array($item['ID'],$ids45)) {?><span class="product-label">-45%</span><?}?>
            <?if (in_array($item['ID'],$ids50)) {?><span class="product-label">-50%</span><?}?>
            <?if (in_array($item['ID'],$ids55)) {?><span class="product-label">-55%</span><?}?>
            <?if (in_array($item['ID'],$ids60)) {?><span class="product-label">-60%</span><?}?>
        <?}?>
    </div>
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
    
    <?if(in_array($item['ID'],$ids15) || in_array($item['ID'],$ids20)|| in_array($item['ID'],$ids25) || in_array($item['ID'],$ids30) || in_array($item['ID'],$ids35) || in_array($item['ID'],$ids40) || in_array($item['ID'],$ids45) || in_array($item['ID'],$ids50) || in_array($item['ID'],$ids55) || in_array($item['ID'],$ids60)){?>
        <?if (in_array($item['ID'],$ids15)) {$sale_price = floor($item['MIN_PRICE']['VALUE'] - (($item['MIN_PRICE']['VALUE']/100)*15));}?>
        <?if (in_array($item['ID'],$ids20)) {$sale_price = floor($item['MIN_PRICE']['VALUE'] - (($item['MIN_PRICE']['VALUE']/100)*20));}?>
        <?if (in_array($item['ID'],$ids25)) {$sale_price = floor($item['MIN_PRICE']['VALUE'] - (($item['MIN_PRICE']['VALUE']/100)*25));}?>
        <?if (in_array($item['ID'],$ids30)) {$sale_price = floor($item['MIN_PRICE']['VALUE'] - (($item['MIN_PRICE']['VALUE']/100)*30));}?>
        <?if (in_array($item['ID'],$ids35)) {$sale_price = floor($item['MIN_PRICE']['VALUE'] - (($item['MIN_PRICE']['VALUE']/100)*35));}?>
        <?if (in_array($item['ID'],$ids40)) {$sale_price = floor($item['MIN_PRICE']['VALUE'] - (($item['MIN_PRICE']['VALUE']/100)*40));}?>
        <?if (in_array($item['ID'],$ids45)) {$sale_price = floor($item['MIN_PRICE']['VALUE'] - (($item['MIN_PRICE']['VALUE']/100)*45));}?>
        <?if (in_array($item['ID'],$ids50)) {$sale_price = floor($item['MIN_PRICE']['VALUE'] - (($item['MIN_PRICE']['VALUE']/100)*50));}?>
        <?if (in_array($item['ID'],$ids55)) {$sale_price = floor($item['MIN_PRICE']['VALUE'] - (($item['MIN_PRICE']['VALUE']/100)*55));}?>
        <?if (in_array($item['ID'],$ids60)) {$sale_price = floor($item['MIN_PRICE']['VALUE'] - (($item['MIN_PRICE']['VALUE']/100)*60));}?>
    
        <div class="product-pricebox">
            <span class="proudct-old-price"><?=$pricePrint?></span>
            <span class="proudct-current-price"><?= number_format($sale_price, 0, ' ', ' ') ?> ₽</span>            
        </div>              
    <?}else{?>
        <div class="product-pricebox">
            <span class="proudct-current-price"><?=$pricePrint?></span>            
        </div>
    <?}?>        

    <!-- Цвета -->
    <?if(!in_array(intval($item['ID']),\Belleyou\ColorAssistant::SERT_PROD_IDS)): //если не сертификаты?>
        <div class="product-colors-sheme">
            <ul class="product-colors-list">
                <?$colorCnt = 1;
                foreach($colors as $colorCode => $color){
                    if($colorCnt == 4){break;}?>
                    <li class="product-color product-color__with-border">
                        <a href="<?=$color['DETAIL_PAGE_URL']?>" target="_blank" style="background: url('/upload/colors/<?=$color['COLOR_CODE']?>.jpg') no-repeat;" title="<?=$color['NAME']?>"></a>
                    </li>
                    <?$colorCnt++;
                }?>
            </ul>
            <?if(count($colors) > 3){
                $totalColorCnt = count($colors)-3;?>
                <span class="product-more-colors-label">+<?=num_word($totalColorCnt, array('цвет', 'цвета', 'цветов'))?></span>
            <?}?>
        </div>
    <?endif;?>
</div>