<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();


$arComponentParameters = array(
    "GROUPS" => array(
        "PARAMS" => array(
            "NAME" => 'Параметры'
        ),
    ),
    "PARAMETERS" => array(

       /* "USE_CAPTCHA" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Использовать капчу? (Y/N)',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "Y",
            "COLS" => 25
        ),*/

        "SUBJECT_FROM" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Список тем обращения',
            "TYPE" => "STRING",
            "MULTIPLE" => "Y",
            "DEFAULT" => "",
            "COLS" => 25
        ),
        "EMAIL_SEND" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Отправлять email? (Y/N)',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
            "COLS" => 25
        ),
        "EMAIL_TO" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Кому отправлять email',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "my@email.com",
            "COLS" => 25
        ),
        "EVENT_NAME" => array(
            "PARENT" => "PARAMS",
            "NAME" => 'Название почтового события',
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => "",
            "COLS" => 25
        ),
    )

);
?>