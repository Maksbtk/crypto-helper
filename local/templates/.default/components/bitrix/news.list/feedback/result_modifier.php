<?php
//сформируем общую оценку по всем активным элементам
$allRatingSum = 0;
$allCountFeedback = 0;

foreach ($arResult['ITEMS'] as $item) {
    $allRatingSum+= intval($item['PROPERTIES']['RATING']['VALUE']);
    $allCountFeedback++;
}

/*$resOb = CIBlockElement::GetList(
    ['created_date' => 'desc'],
    ['IBLOCK_ID' => $arResult['ID'], "ACTIVE"=>"Y"],
    false,
    false,
    ['PROPERTY_RATING']
);

while($el = $resOb->fetch()) {
    $allRatingSum+= intval($el['PROPERTY_RATING_VALUE']);
    $allCountFeedback++;
}
echo '<pre>'; var_dump($arResult); echo '</pre>';
*/

$arResult["GENERAL_RATE"] = number_format($allRatingSum/$allCountFeedback, 1, '.', '');
if (!$arResult["GENERAL_RATE"])
    $arResult["GENERAL_RATE"] = 0;

$arResult["COUNT_FEEDBACK_ALL"] = $allCountFeedback;

