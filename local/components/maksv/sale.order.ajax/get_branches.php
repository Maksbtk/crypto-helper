<?php
use Bitrix\Main;
use Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');

if ($_GET['cityId'])
    $cityId = intval($_GET['cityId']);
else
    $cityId = 150;// мск

$iblockId = 20;

$cityName = $_GET['cityName'] ?? null;
$arFilters = ['IBLOCK_ID' => $iblockId, "NAME" => "%$cityName%"];
if(!is_string($cityName) or empty($cityName)){
    unset($arFilters['PROPERTY_ADDRESS_VALUE']);
}
$sort = ['SORT' => 'ASC'];
$arRes = CIBlockSection::GetList($sort, $arFilters);
$arSectionIds = [];
while($next = $arRes->GetNext()){
    $arSectionIds[] = intval($next['ID']);
}

$filters = [
    'IBLOCK_ID' => $iblockId,
    'SECTION_ID' => $arSectionIds,
];
$query = CIBlockElement::GetList([], $filters);

$branches = [];
$i = 0;
while ($next = $query->GetNext()){
    if($i++ >= 200){
        break;
    }
    // todo get props
    $propsQuery = CIBlockElement::GetProperty($iblockId, intval($next['ID']));
    $props = [];
    while($prop = $propsQuery->GetNext()){
        $props[$prop['CODE']] = $prop;
    }
    $next['PROPERTIES'] = $props;
    $branches[] = $next;
}

?>

<?php
//$arShopsFilter = ["SECTION_ID" => $cityId];
//$APPLICATION->IncludeComponent("bitrix:news.list", "shops", Array(
//    'CURRENT_CITY_ID' => $cityId,
//    "ACTIVE_DATE_FORMAT" => "d.m.Y",	// Формат показа даты
//    "ADD_SECTIONS_CHAIN" => "N",	// Включать раздел в цепочку навигации
//    "AJAX_MODE" => "N",	// Включить режим AJAX
//    "AJAX_OPTION_ADDITIONAL" => "",	// Дополнительный идентификатор
//    "AJAX_OPTION_HISTORY" => "N",	// Включить эмуляцию навигации браузера
//    "AJAX_OPTION_JUMP" => "N",	// Включить прокрутку к началу компонента
//    "AJAX_OPTION_STYLE" => "Y",	// Включить подгрузку стилей
//    "CACHE_FILTER" => "N",	// Кешировать при установленном фильтре
//    "CACHE_GROUPS" => "Y",	// Учитывать права доступа
//    "CACHE_TIME" => "36000000",	// Время кеширования (сек.)
//    "CACHE_TYPE" => "A",	// Тип кеширования
//    "CHECK_DATES" => "Y",	// Показывать только активные на данный момент элементы
//    "DETAIL_URL" => "",	// URL страницы детального просмотра (по умолчанию - из настроек инфоблока)
//    "DISPLAY_BOTTOM_PAGER" => "Y",	// Выводить под списком
//    "DISPLAY_DATE" => "Y",	// Выводить дату элемента
//    "DISPLAY_NAME" => "Y",	// Выводить название элемента
//    "DISPLAY_PICTURE" => "Y",	// Выводить изображение для анонса
//    "DISPLAY_PREVIEW_TEXT" => "Y",	// Выводить текст анонса
//    "DISPLAY_TOP_PAGER" => "N",	// Выводить над списком
//    "FIELD_CODE" => array(	// Поля
//        0 => "ID",
//        1 => "CODE",
//        2 => "XML_ID",
//        3 => "NAME",
//        4 => "TAGS",
//        5 => "SORT",
//        6 => "PREVIEW_TEXT",
//        7 => "PREVIEW_PICTURE",
//        8 => "DETAIL_TEXT",
//        9 => "DETAIL_PICTURE",
//        10 => "DATE_ACTIVE_FROM",
//        11 => "ACTIVE_FROM",
//        12 => "DATE_ACTIVE_TO",
//        13 => "ACTIVE_TO",
//        14 => "SHOW_COUNTER",
//        15 => "SHOW_COUNTER_START",
//        16 => "IBLOCK_TYPE_ID",
//        17 => "IBLOCK_ID",
//        18 => "IBLOCK_CODE",
//        19 => "IBLOCK_NAME",
//        20 => "IBLOCK_EXTERNAL_ID",
//        21 => "DATE_CREATE",
//        22 => "CREATED_BY",
//        23 => "CREATED_USER_NAME",
//        24 => "TIMESTAMP_X",
//        25 => "MODIFIED_BY",
//        26 => "USER_NAME",
//        27 => "",
//    ),
//    "FILTER_NAME" => "arShopsFilter",	// Фильтр
//    "HIDE_LINK_WHEN_NO_DETAIL" => "N",	// Скрывать ссылку, если нет детального описания
//    "IBLOCK_ID" => "20",	// Код информационного блока
//    "IBLOCK_TYPE" => "content",	// Тип информационного блока (используется только для проверки)
//    "INCLUDE_IBLOCK_INTO_CHAIN" => "N",	// Включать инфоблок в цепочку навигации
//    "INCLUDE_SUBSECTIONS" => "Y",	// Показывать элементы подразделов раздела
//    "MESSAGE_404" => "",	// Сообщение для показа (по умолчанию из компонента)
//    "NEWS_COUNT" => "20",	// Количество новостей на странице
//    "PAGER_BASE_LINK_ENABLE" => "N",	// Включить обработку ссылок
//    "PAGER_DESC_NUMBERING" => "N",	// Использовать обратную навигацию
//    "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",	// Время кеширования страниц для обратной навигации
//    "PAGER_SHOW_ALL" => "N",	// Показывать ссылку "Все"
//    "PAGER_SHOW_ALWAYS" => "N",	// Выводить всегда
//    "PAGER_TEMPLATE" => ".default",	// Шаблон постраничной навигации
//    "PAGER_TITLE" => "Новости",	// Название категорий
//    "PARENT_SECTION" => "",	// ID раздела
//    "PARENT_SECTION_CODE" => "",	// Код раздела
//    "PREVIEW_TRUNCATE_LEN" => "",	// Максимальная длина анонса для вывода (только для типа текст)
//    "PROPERTY_CODE" => array(	// Свойства
//        0 => "ADDRESS",
//        1 => "ADDRESS_ON_MAP",
//        2 => "WORK_TIME",
//        3 => "ALERT",
//        4 => "COORDINATES",
//        5 => "REFERENCE_PLACE",
//        6 => "SMALL_NAME",
//        7 => "PHONE",
//        8 => "",
//    ),
//    "SET_BROWSER_TITLE" => "N",	// Устанавливать заголовок окна браузера
//    "SET_LAST_MODIFIED" => "N",	// Устанавливать в заголовках ответа время модификации страницы
//    "SET_META_DESCRIPTION" => "N",	// Устанавливать описание страницы
//    "SET_META_KEYWORDS" => "N",	// Устанавливать ключевые слова страницы
//    "SET_STATUS_404" => "N",	// Устанавливать статус 404
//    "SET_TITLE" => "N",	// Устанавливать заголовок страницы
//    "SHOW_404" => "N",	// Показ специальной страницы
//    "SORT_BY1" => "ACTIVE_FROM",	// Поле для первой сортировки новостей
//    "SORT_BY2" => "SORT",	// Поле для второй сортировки новостей
//    "SORT_ORDER1" => "DESC",	// Направление для первой сортировки новостей
//    "SORT_ORDER2" => "ASC",	// Направление для второй сортировки новостей
//    "STRICT_SECTION_CHECK" => "N",	// Строгая проверка раздела для показа списка
//
//    //кастомные параметры для зума карты
//    "DEF_MAP_SIZE" => "10",	// дефолтный зум карты
//    "DETAIL_MAP_SIZE" => "14",	// зум открытого магазина
//),
//    false
//);
?>

<ul class="shops-content shops-list" id="checkout-shops-list">
    <?php foreach ($branches as $branch) : ?>
        <?php
        $branchData = [
            'ID' => $branch['ID'],
            'NAME' => $branch['NAME'],
            'PHONE' => $branch['PROPERTIES']['PHONE']['VALUE'],
            'ADDRESS' => $branch['PROPERTIES']['ADDRESS']['VALUE'],
            'WORK_TIME' => html_entity_decode($branch['PROPERTIES']['WORK_TIME']['VALUE']),
        ];
        ?>
    <li class="shop-item">
        <a href="javascript:void(0)" class="shop-link" data-branch-id="<?=$branch['ID']?>" data-branch="<?= htmlentities(base64_encode(json_encode($branchData))) ?>">
            <h3 class="shop-name"><?=$branch['NAME']?></h3>
            <p class="shop-phone"><?=$branch['PROPERTIES']['PHONE']['VALUE']?></p>
            <div class="shop-address">
                <p><?=$branch['PROPERTIES']['ADDRESS']['VALUE']?></p>
            </div>
            <div class="shop-open-hours">
                <p><?=html_entity_decode($branch['PROPERTIES']['WORK_TIME']['VALUE'])?></p>
            </div>
        </a>
    </li>
    <?php endforeach; ?>

</ul>

<?php

//echo '<pre>';
//var_dump($branches);
//echo '</pre>';
