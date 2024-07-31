<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

// отделяем выбранный товар от остальных в корзине
$selectedElement = array();

/*$reversRows = array_reverse($arResult['GRID']['ROWS'], true);
foreach ($reversRows as $key => $value) {
    if ($value["PRODUCT_ID"] == $arParams['SELECTED_VALUE']["PROD_SKU_ID"] && $value["PROPERTY__SIZE_VALUE"] == $arParams['SELECTED_VALUE']["PROP_12"]) {
        $selectedElement = $value;
        unset($arResult['GRID']['ROWS'][$key]);
        break;
    }
}

$arResult['GRID']['SELECTED_ELEMENT'] = $selectedElement;*/
