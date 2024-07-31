<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

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
