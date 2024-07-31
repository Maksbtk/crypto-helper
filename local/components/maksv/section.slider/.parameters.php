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
        
       /* "SECTION_COUNT" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Количество разделов',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "3",
            "COLS" => 25
        ),*/

        "SECTIONS_CODES" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Символьные коды разделов (например: odezhda,topy,trusy)',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "odezhda,topy,trusy",
            "COLS" => 40
        ),

        "BLOCK_TITLE" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Заголовок блока',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "Разделы",
            "COLS" => 25
        ),
    )

);
?>