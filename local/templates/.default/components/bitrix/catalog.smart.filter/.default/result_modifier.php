<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
//require_once ($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/belleyou/autoload.php');

foreach ($arResult['ITEMS'] as $key => $item) {
    // #5789 - В фильтре оставить только Цвет и Размер
    if (empty($item['VALUES']) || !in_array($item['CODE'], ['COLOR', 'SIZE', 'SAYT_DOPOLNITELNYYTIPKATEGORIITOVARA'])) {
        unset($arResult['ITEMS'][$key]);
    }
    
    //Переименовываем свойство здесь во избежание затирания названия в ИБ со стороны 1С
    if($item["NAME"] == "(Сайт) ДополнительныйТипКатегорииТовара"){
        $arResult['ITEMS'][$key]["NAME"] = "Тип";    
    }

    //соберем id цветов, чтобы достать их коды в справочнике, и далее подставить картинки к цветам
    if($item["CODE"] == "COLOR")  {
        foreach ($item['VALUES'] as $values) {
            $colorIds[] = $values['FACET_VALUE'];
        }
    }
}

$colorAssistOb = new Belleyou\ColorAssistant();
$arResult['SORTED_COLOR_BY_XMLIDs'] = $colorAssistOb->getСolorXmlidsByIds($colorIds);

