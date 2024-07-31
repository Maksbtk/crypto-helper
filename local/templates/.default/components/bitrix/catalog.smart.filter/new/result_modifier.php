<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


//xmp($arResult);
/** @var \kb\reference\ColorsManager $colorsManager */
$colorsManager = \kb\Container::get('ColorsManager');
$existColors = $colorsManager->getListIndexedById();

array_walk(
    $arResult['ITEMS'][\kb\service\Settings::PROPERTY_COLOR_ID]['VALUES'],
    function(&$val, $id) use($existColors) {
        $val['IMG'] = $existColors[$id]['DETAIL_PICTURE']['SRC'];
    }
);


foreach ($arResult['ITEMS'] as $key => $item) {
    // #5789 - В фильтре оставить только Цвет и Размер
    if (empty($item['VALUES']) || !in_array($item['CODE'], ['_COLOR', '_SIZE', 'DOPOLNITELNYY_TIP'])) {
        unset($arResult['ITEMS'][$key]);
    }
    
    //Переименовываем свойство здесь во избежание затирания названия в ИБ со стороны 1С
    if($item["NAME"] == "Дополнительный тип"){
        $arResult['ITEMS'][$key]["NAME"] = "Тип";    
    }
}

//xmp($arResult['ITEMS'][\kb\service\Settings::PROPERTY_COLOR_ID]);
