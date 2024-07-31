<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();


$arComponentParameters = array(
    "GROUPS" => array(
        "PARAMS" => array(
            "NAME" => 'Параметры'
        ),
    ),
    "PARAMETERS" => array(
        "CACHE_TIME" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Время кеширования (сек.):',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "36000",
            "COLS" => 25
        ),

        "PROD_COUNT" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Количество товаров',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "5",
            "COLS" => 25
        ),

        "BLOCK_TITLE" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Заголовок блока',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "Товары",
            "COLS" => 25
        ),

        "SECTION_URL" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'ссылка на раздел',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "/catalog/kollektsii/",
            "COLS" => 25
        ),

        "COLLECTION_CODE" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Символьный код коллекции',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "bamboo-lounge",
            "COLS" => 25
        ),
    )

);
?>