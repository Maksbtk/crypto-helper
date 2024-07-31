<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Sale;
use Bitrix\Main\Loader;

Loader::includeModule("iblock");

$basketIds = [];
$offerIds = [];

foreach($arResult["BASKET"] as $b_prods){
    $offerIds[] = $b_prods['PRODUCT_ID'];    
}

/** @var \kb\catalog\CatalogManager $catalogManager */
$catalogManager = \kb\Container::get('CatalogManager');

/** @var \kb\reference\ColorsManager $colorsManager */
$colorsManager = \kb\Container::get('ColorsManager');
$allColors = $colorsManager->getListIndexedById();


$prods = $catalogManager->getProdIdsByOffersIds(array_unique($offerIds), false);
$arResult['PRODUCTS'] = $catalogManager->getProductsList(['ID' => array_unique(array_values($prods))], [], ['*', 'PROPERTY_article'], false);
$offers = $catalogManager->getOffers(['ID' => array_unique($offerIds)], false);

$arResult['OFFER_PROD_MATRIX'] = $prods;

array_map(
    function(&$product) use(&$arResult, $offers){
        $pict = null;
        if ($product['PREVIEW_PICTURE']) {
            $pict = CFile::GetFileArray($product['PREVIEW_PICTURE']);
        } elseif ($product['DETAIL_PICTURE']) {
            $pict = CFile::GetFileArray($product['DETAIL_PICTURE']);
        }

        if ($pict) {
            $product['PICT'] = $pict['SRC'];
        }

        $product['ARTICLE'] = $product['PROPERTY_ARTICLE_VALUE'];

        $arResult['PRODUCTS'][$product['ID']] = $product;
    },
    $arResult['PRODUCTS']
);

$colors = [];
array_map(
    function($offer) use($allColors, &$colors) {
        $colors[$offer['ID']] = $allColors[$offer['PROPERTY__COLOR_VALUE']]['DETAIL_PICTURE']['SRC'];
    },
    $offers);

$arResult['COLORS'] = $colors;

$cp = $this->__component; 
if (is_object($cp)) {
    $cp->SetResultCacheKeys(array(
        "ID"
    ));
}

foreach ($arResult["BASKET"] as $key => $basketItem) {
    $product = CCatalogProduct::GetByID($basketItem["PRODUCT_ID"]);
    $arResult["BASKET"][$key]["AVAILABLE_QUANTITY"] = $product["QUANTITY"];
    $arResult["BASKET"][$key]["AVAILABLE"] = $product["AVAILABLE"];
}
?>
