<?
use Bitrix\Main\Page\Asset;

define('NEED_AUTH', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "ЛК CH");
$APPLICATION->SetTitle("Личные данные - Интернет-магазин belle you");

//стили форм авторизации
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/forms-auth.css");
//стили попапов авторизации
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/popup-auth.css");
//стили страниц личного кабинета
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-profile.css?v=3", true);
?>

<div class="profile-wrapper">
    <?
    //меню (тянется из /user/) и аватарка с функционалом ее замены
    $APPLICATION->IncludeComponent("bitrix:menu", "user.sidebar", Array(
        "ALLOW_MULTI_SELECT" => "N",	// Разрешить несколько активных пунктов одновременно
        "CHILD_MENU_TYPE" => "left",	// Тип меню для остальных уровней
        "DELAY" => "N",	// Откладывать выполнение шаблона меню
        "MAX_LEVEL" => "1",	// Уровень вложенности меню
        "MENU_CACHE_GET_VARS" => array(	// Значимые переменные запроса
            0 => "",
        ),
        "MENU_CACHE_TIME" => "1800",	// Время кеширования (сек.)
        "MENU_CACHE_TYPE" => "N",	// Тип кеширования
        "MENU_CACHE_USE_GROUPS" => "Y",	// Учитывать права доступа
        "ROOT_MENU_TYPE" => "left",	// Тип меню для первого уровня
        "USE_EXT" => "N",	// Подключать файлы с именами вида .тип_меню.menu_ext.php
    ), false);
    ?>

    <?$APPLICATION->IncludeComponent(
        "bitrix:main.profile",
        "user.profile",
        Array(
            "CHECK_RIGHTS" => "N",
            "SEND_INFO" => "N",
            "SET_TITLE" => "N",
            "USER_PROPERTY" => [],
            "USER_PROPERTY_NAME" => ""
        )
    );?>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>