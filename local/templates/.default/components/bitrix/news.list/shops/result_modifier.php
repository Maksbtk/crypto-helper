<?php
$cityAr = [];
$sectionsDB = CIBlockSection::GetList(['sort'=>'asc'], ['IBLOCK_ID'=>$arResult['ID'], 'GLOBAL_ACTIVE'=>'Y'], true);
while($section = $sectionsDB->GetNext())
{
    $cityAr[intval($section['ID'])] = $section['NAME'];
}
$arResult['CITYS'] = $cityAr;

// собираем все координаты города в массив чтобы потом подставить их в MAP_DATA
foreach ($arResult['ITEMS'] as $shop) {

    $arTmp = explode(',', $shop['PROPERTIES']['COORDINATES']['VALUE']);

    //Подготовка карты
    $arResult['POSITION']['yandex_scale'] = $arParams["DEF_MAP_SIZE"]; // Подбираем размер карты, чтобы поместились все маркеры

    // В yandex_lat и yandex_lon заносим координаты центральной точки карты
    $arResult['POSITION']['yandex_lat'] = $arTmp[0];
    $arResult['POSITION']['yandex_lon'] = $arTmp[1];

    //Собираем маркеры
    $arResult['POSITION']['PLACEMARKS'][] = array(
        'LON' => $arTmp[1], // LON и LAT - координаты маркера
        'LAT' => $arTmp[0],
        'TEXT' => $shop['PROPERTIES']['ADDRESS']['VALUE'].html_entity_decode('<br>'.$shop['PROPERTIES']['WORK_TIME']['VALUE']),
    );
}

