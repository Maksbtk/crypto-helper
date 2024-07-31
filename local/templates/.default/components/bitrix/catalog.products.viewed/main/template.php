<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 */

$this->setFrameMode(true);

$itemsIds = [];
foreach ($arResult['ITEMS'] as $item) {
    $itemsIds[] = $item['ID'];
}
//echo '<pre>'; var_dump($arResult['ITEMS']); echo '</pre>';
$viewedProdSliderFilter = ["IBLOCK_ID" => "2", "ACTIVE"=>"Y", "ID" => $itemsIds];
$APPLICATION->IncludeComponent(
    "belleyou:product.slider",
    "viewed",
    array(
        //"CACHE_TIME" => "21600",
        "CACHE_TYPE" => "N",
        "COMPONENT_TEMPLATE" => "viewed",
        "PROD_COUNT" => "5",
        "FILTER" => $viewedProdSliderFilter,
        "SHOW_MORE_PICTURE" => SITE_TEMPLATE_PATH."/demo-pics/pi-see-all_mobile.jpg",
        "BLOCK_TITLE" => 'вы недавно смотрели',
    ),
    false
);?>
