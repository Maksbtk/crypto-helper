<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Page\AssetLocation;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UserTable;

global $APPLICATION, $USER;
?>

<!DOCTYPE html>
<html lang="ru" prefix="og: https://ogp.me/ns#">
    <head>
        <?
        $userIsAuth = $USER->IsAuthorized();
        if ($userIsAuth){
            $idUser = $USER->GetID();
            $rsUser = CUser::GetByID($idUser);
            $arUser = $rsUser->Fetch();
        }

        $unix = time();
        $mainPage = $APPLICATION->getCurDir() === '/';
        $curPage = $APPLICATION->GetCurPage(true);
        $curPageShort = $APPLICATION->GetCurPage(false);

       ?>

        <?if($curPageShort == "/catalog/" || $curPageShort == "/index"){?>
            <meta name="robots" content="noindex, nofollow" />
            
            <?LocalRedirect('https://'.$_SERVER["SERVER_NAME"].'/', false, '301 Moved permanently');
        }?>    
    
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
        <meta name="p:domain_verify" content="29dc8c5dd9f5715eab6e06717673a4f8"/>
        
        <meta http-equiv="cache-control" content="no-cache">
        <meta http-equiv="expires" content="0">        
        

                

        <?
        $APPLICATION->ShowMeta("robots");
        $APPLICATION->ShowMeta("description");

        $APPLICATION->ShowCSS();
        $APPLICATION->ShowHeadStrings();
        $APPLICATION->ShowHeadScripts();?>
        
        <?$checkpage = $APPLICATION->GetCurPage();?>

        <?
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-3.6.4.min.js");
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/maksv-cookie.js");
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/maksv-prelouder.js?v=1");
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-site-header.js");
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-site-footer.js");
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-favorites.js");
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-sidebar.js");
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-popup.js");
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-dropdown.js");

        //предзагрузка основного шрифта
        Asset::getInstance()->addString('<link rel="preload" href="'.SITE_TEMPLATE_PATH.'/fonts/TT_maksv_Next_Regular.woff2" as="font">');
        Asset::getInstance()->addString('<meta name=”viewport” content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />');

        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/normalize.css");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/fonts.css");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/defaults.css");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/maksv-prelouder.css");

        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/maksv-header.css");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/maksv-sidebar.css");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/maksv-searchbar.css");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/maksv-content.css");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/maksv-footer.css");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/maksv-breadcrumbs.css");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/maksv-popup.css?v=1");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/products-list.css");

        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-slick.min.js");
        Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-product-slider.js");

        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/jquery-slick-slider.css");
        Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/section-product-slider.css?v=1");
        ?>


        <title itemscope itemtype="http://schema.org/WPHeader"><?$APPLICATION->ShowTitle()?></title>       
    </head>

    <body<?if($mainPage && !$_REQUEST["ORDER_ID"]){?> class="mainpage"<?}?>>

        <?$APPLICATION->ShowPanel()?>
        


        <div class="maksv-layout">
            <header class="maksv-header <?if($mainPage){?>maksv-header__mainpage maksv-header__transparent<?}?>">
                <?/*<noindex>
                    <div class="maksv-header-notification">
                        <!--<a class="maksv-header-notification__link" href="javascript:void(0)">Бесплатная доставка по России от 5000 ₽</a>-->
                        <a class="maksv-header-notification__link notification__link_pc" href="/for-customers/shops/" target="_blank">Открываем флагман в Столешниковом переулке, д.9 — ждем вас с 7 мая.</a>
                        <a class="maksv-header-notification__link notification__link_mob" href="/for-customers/shops/" target="_blank">Скоро открытие! Столешников переулок, д.9.</a>
                    </div>
                </noindex>*/?>

                <?
                //строка нотификации над шапкой
                /*$APPLICATION->IncludeComponent(
                    "bitrix:news.detail",
                    "notification.line",
                    Array(
                        "ACTIVE_DATE_FORMAT" => "d.m.Y",
                        "ADD_ELEMENT_CHAIN" => "N",
                        "ADD_SECTIONS_CHAIN" => "N",
                        "AJAX_MODE" => "N",
                        "AJAX_OPTION_ADDITIONAL" => "",
                        "AJAX_OPTION_HISTORY" => "N",
                        "AJAX_OPTION_JUMP" => "N",
                        "AJAX_OPTION_STYLE" => "Y",
                        "BROWSER_TITLE" => "-",
                        "CACHE_GROUPS" => "Y",
                        "CACHE_TIME" => "18000",
                        "CACHE_TYPE" => "A",
                        "CHECK_DATES" => "Y",
                        "DETAIL_URL" => "",
                        "DISPLAY_BOTTOM_PAGER" => "Y",
                        "DISPLAY_DATE" => "Y",
                        "DISPLAY_NAME" => "Y",
                        "DISPLAY_PICTURE" => "Y",
                        "DISPLAY_PREVIEW_TEXT" => "Y",
                        "DISPLAY_TOP_PAGER" => "N",
                        "ELEMENT_CODE" => "",
                        "ELEMENT_ID" => "105003",
                        "FIELD_CODE" => array("",""),
                        "IBLOCK_ID" => "46",
                        "IBLOCK_TYPE" => "content",
                        "IBLOCK_URL" => "",
                        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
                        "MESSAGE_404" => "",
                        "META_DESCRIPTION" => "-",
                        "META_KEYWORDS" => "-",
                        "PAGER_BASE_LINK_ENABLE" => "N",
                        "PAGER_SHOW_ALL" => "N",
                        "PAGER_TEMPLATE" => ".default",
                        "PAGER_TITLE" => "Страница",
                        "PROPERTY_CODE" => array("MOBILE_TEXT","LINK",""),
                        "SET_BROWSER_TITLE" => "N",
                        "SET_CANONICAL_URL" => "N",
                        "SET_LAST_MODIFIED" => "N",
                        "SET_META_DESCRIPTION" => "N",
                        "SET_META_KEYWORDS" => "N",
                        "SET_STATUS_404" => "N",
                        "SET_TITLE" => "N",
                        "SHOW_404" => "N",
                        "STRICT_SECTION_CHECK" => "N",
                        "USE_PERMISSIONS" => "N",
                        "USE_SHARE" => "N"
                    )
                );*/?>

                <nav class="maksv-header-menu">
                    <ul class="header-service-menu">
                        <li class="header-service-menu__item header-service-menu__item-catalog">
                            <a id="catalogShowButton" href="javascript:void(0)">
                                <svg class="icn-catalog-mobile" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M18.272 17.197L22 21M2 3h18.823M2 10.2h4.706M2 17.4h4.706m12.941-3.6c0 2.651-2.107 4.8-4.706 4.8s-4.706-2.149-4.706-4.8S12.342 9 14.941 9c2.6 0 4.706 2.149 4.706 4.8z"/></svg>
                                <span>Меню</span>
                            </a>
                            <a id="catalogCloseButton" data-close-sidebar href="javascript:void(0)" style="display: none;">
                                <svg class="icn-catalog-close" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M12 12L4 4M12 4l-8 8"/></svg>
                                <span>Закрыть</span>
                            </a>
                        </li>
                        <?/*<li class="header-service-menu__item header-service-menu__item-site">
                            <span>RU</span> | <a class="header-service-menu__link-international" href="https://maksv.com/" target="_blank">COM</a>
                            <!--<div class="menu__change-site hidden">
                            <div class="menu__change-site-box">Вы уверены, что хотите перейти на международную версию сайта? <a href="https://maksv.com/" target="_blank">Перейти</a></div>
                            </div>-->
                        </li>*/?>
                    </ul>            

                    <a href="/" class="maksv-header__logo">

                        <svg width="50" height="30" viewBox="0 0 65 37" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M30.3182 12H26.0909C25.8409 10.7841 25.4034 9.71591 24.7784 8.79545C24.1648 7.875 23.4148 7.10227 22.5284 6.47727C21.6534 5.84091 20.6818 5.36364 19.6136 5.04545C18.5455 4.72727 17.4318 4.56818 16.2727 4.56818C14.1591 4.56818 12.2443 5.10227 10.5284 6.17045C8.82386 7.23864 7.46591 8.8125 6.45455 10.892C5.45455 12.9716 4.95455 15.5227 4.95455 18.5455C4.95455 21.5682 5.45455 24.1193 6.45455 26.1989C7.46591 28.2784 8.82386 29.8523 10.5284 30.9205C12.2443 31.9886 14.1591 32.5227 16.2727 32.5227C17.4318 32.5227 18.5455 32.3636 19.6136 32.0455C20.6818 31.7273 21.6534 31.2557 22.5284 30.6307C23.4148 29.9943 24.1648 29.2159 24.7784 28.2955C25.4034 27.3636 25.8409 26.2955 26.0909 25.0909H30.3182C30 26.875 29.4205 28.4716 28.5795 29.8807C27.7386 31.2898 26.6932 32.4886 25.4432 33.4773C24.1932 34.4545 22.7898 35.1989 21.233 35.7102C19.6875 36.2216 18.0341 36.4773 16.2727 36.4773C13.2955 36.4773 10.6477 35.75 8.32955 34.2955C6.01136 32.8409 4.1875 30.7727 2.85795 28.0909C1.52841 25.4091 0.863636 22.2273 0.863636 18.5455C0.863636 14.8636 1.52841 11.6818 2.85795 9C4.1875 6.31818 6.01136 4.25 8.32955 2.79545C10.6477 1.34091 13.2955 0.613635 16.2727 0.613635C18.0341 0.613635 19.6875 0.869317 21.233 1.38068C22.7898 1.89205 24.1932 2.64205 25.4432 3.63068C26.6932 4.60795 27.7386 5.80114 28.5795 7.21023C29.4205 8.60795 30 10.2045 30.3182 12ZM37.1491 36V1.09091H41.3764V16.6364H59.9901V1.09091H64.2173V36H59.9901V20.3864H41.3764V36H37.1491Z" fill="<?if($mainPage):?>white<?else:?>black<?endif;?>"/>
                        </svg>

                    </a>
                        
                    <ul class="maksv-header__user-menu">
                        <?/* <li class="user-menu__item user-menu__item--search">
                            <a id="buttonSearchBarOpen" class="user-menu__link user-menu__link-search" href="javascript:void(0)">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M10 17a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM15 15l6 6"/></svg>                   
                                <span class="screen-reader-text">Поиск</span>                        
                            </a>
                        </li>
                        */?>

                       <?if ($userIsAuth):?>
                       <li class="user-menu__item user-menu__item--cart">
                            <a class="user-menu__link user-menu__link-cart" href="/user/cart/">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M18.279 8H5.72c-.498 0-.924.353-1.006.836l-1.7 10C2.91 19.446 3.39 20 4.02 20h15.958c.63 0 1.11-.555 1.007-1.164l-1.7-10A1.015 1.015 0 0 0 18.278 8zM8 8a4 4 0 1 1 8 0"/></svg>
                                <span class="screen-reader-text">Корзина</span>
                                <i class="user-menu__item-counter">
                                    <?=getBasketCnt();?>
                                </i>
                            </a>
                        </li>
                        <?endif;?>
                        <li class="user-menu__item user-menu__item--profile">
                            <a class="user-menu__link user-menu__link-cart" href="/user/">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12.0001 15C8.83002 15 6.01089 16.5306 4.21609 18.906C3.8298 19.4172 3.63665 19.6728 3.64297 20.0183C3.64785 20.2852 3.81546 20.6219 4.02546 20.7867C4.29728 21 4.67396 21 5.42733 21H18.5729C19.3262 21 19.7029 21 19.9747 20.7867C20.1847 20.6219 20.3523 20.2852 20.3572 20.0183C20.3635 19.6728 20.1704 19.4172 19.7841 18.906C17.9893 16.5306 15.1702 15 12.0001 15Z" stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12.0001 12C14.4854 12 16.5001 9.98528 16.5001 7.5C16.5001 5.01472 14.4854 3 12.0001 3C9.51481 3 7.5001 5.01472 7.5001 7.5C7.5001 9.98528 9.51481 12 12.0001 12Z" stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="screen-reader-text">Профиль</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </header>

            <aside id="sidebarCatalog" class="maksv-sidebar" style="display: none;">
                <div class="maksv-sidebar__backdrop" data-close-sidebar></div>
                <nav class="maksv-sidebar-menu">
                    <div class="maksv-sidebar-menu__wrapper">
                        <header class="maksv-sidebar-menu__header-mobile">
                            <button class="button-close-sidebar__mobile" data-close-sidebar></button>

                            <?//поиск по каталогу (mobile) ?>
                            <?/*<div class="mobile-search">
                                <form class="form-search-mobile js-search-form-mob" action="/search/index.php" method="get">
                                    <div class="form-catalog-search-wrapper">
                                        <div class="form-catalog-search">
                                            <input id="searchInputBigMobile" name="q" name="" type="text" class="form-catalog-search__input" placeholder="Поиск товаров" autocomplete="off">
                                            <input class="form-catalog-search__button" type="submit" value="">
                                            <buttton class="form-catalog-search__clear js-clear-search-mob">x</buttton>
                                            <svg class="search-spinner" viewBox="0 0 48 48"><circle class="path" cx="24" cy="24" r="20" fill="none" stroke-width="3"></circle></svg>
                                        </div>
                                        <button class="form-catalog-search__cancel js-mobile-search__close">Отмена</button>
                                    </div>
                                </form>
                            </div>*/?>

                        </header>

                        <?$APPLICATION->IncludeComponent(
                            "bitrix:menu",
                            "main",
                            array(
                               // "SALE" => $show_sale,
                                "ALLOW_MULTI_SELECT" => "N",
                                "CHILD_MENU_TYPE" => "",
                                "DELAY" => "N",
                                "MAX_LEVEL" => "1",
                                "MENU_CACHE_GET_VARS" => array(),
                                "MENU_CACHE_TIME" => "1900",
                                "MENU_CACHE_TYPE" => "A",
                                "MENU_CACHE_USE_GROUPS" => "N",
                                "CACHE_SELECTED_ITEMS" => "N",
                                "MENU_CACHE_USE_USERS" => "N",
                                "ROOT_MENU_TYPE" => "top",
                                "USE_EXT" => "N",
                                "COMPONENT_TEMPLATE" => "main"
                            ),
                            false
                        );?>

                        <footer class="maksv-sidebar-menu__footer-mobile">
                            <ul class="footer-mobile__menu">
                                <?if(!$userIsAuth){?>
                                    <li>
                                        <a href="/user/">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.0001 15C8.83002 15 6.01089 16.5306 4.21609 18.906C3.8298 19.4172 3.63665 19.6728 3.64297 20.0183C3.64785 20.2852 3.81546 20.6219 4.02546 20.7867C4.29728 21 4.67396 21 5.42733 21H18.5729C19.3262 21 19.7029 21 19.9747 20.7867C20.1847 20.6219 20.3523 20.2852 20.3572 20.0183C20.3635 19.6728 20.1704 19.4172 19.7841 18.906C17.9893 16.5306 15.1702 15 12.0001 15Z" stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round"></path>
                                                <path d="M12.0001 12C14.4854 12 16.5001 9.98528 16.5001 7.5C16.5001 5.01472 14.4854 3 12.0001 3C9.51481 3 7.5001 5.01472 7.5001 7.5C7.5001 9.98528 9.51481 12 12.0001 12Z" stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                            <span>Войти / Зарегистрироваться</span>
                                        </a>
                                    </li>
                                <?}?>
                                <?/*<li>
                                    <a href="/for-customers/shops/">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M5 14.286c-1.851.817-3 1.955-3 3.214C2 19.985 6.477 22 12 22s10-2.015 10-4.5c0-1.259-1.149-2.397-3-3.214M18 8c0 4.064-4.5 6-6 9-1.5-3-6-4.936-6-9a6 6 0 1 1 12 0zm-5 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                                        <span>Магазины</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="tel:+88007072426">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M14.05 6A5 5 0 0 1 18 9.95M14.05 2A9 9 0 0 1 22 9.94m-11.773 3.923a14.604 14.604 0 0 1-2.847-4.01 1.698 1.698 0 0 1-.113-.266 1.046 1.046 0 0 1 .147-.862c.048-.067.105-.124.22-.238.35-.35.524-.524.638-.7a2 2 0 0 0 0-2.18c-.114-.176-.289-.351-.638-.7l-.195-.196c-.532-.531-.797-.797-1.083-.941a2 2 0 0 0-1.805 0c-.285.144-.551.41-1.083.941l-.157.158c-.53.53-.795.794-.997 1.154-.224.4-.386 1.02-.384 1.479 0 .413.081.695.241 1.26a19.038 19.038 0 0 0 4.874 8.283 19.039 19.039 0 0 0 8.283 4.873c.565.16.847.24 1.26.242a3.377 3.377 0 0 0 1.478-.384c.36-.203.625-.468 1.155-.997l.157-.158c.532-.531.797-.797.942-1.082a2 2 0 0 0 0-1.806c-.145-.285-.41-.55-.942-1.082l-.195-.195c-.35-.35-.524-.524-.7-.639a2 2 0 0 0-2.18 0c-.176.114-.35.29-.7.639-.115.114-.172.171-.239.22-.237.17-.581.228-.862.146a1.695 1.695 0 0 1-.266-.113 14.605 14.605 0 0 1-4.01-2.846z"/></svg>
                                        <span>8-800-707-24-26</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://maksv.com">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M3 12h18"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="M12 21c1.657 0 3-4.03 3-9s-1.343-9-3-9-3 4.03-3 9 1.343 9 3 9z"/></svg>
                                        <span>Перейти на maksv.com</span>
                                    </a>
                                </li>*/?>
                            </ul>
                        </footer>
                    </div>
                </nav>
            </aside>

            <?php
            //поиск по каталогу
            // верстка формы для мобильной версии - .mobile-search
            /*$APPLICATION->IncludeComponent(
                "maksv:main.search",
                'bar',
                array(
                    "PAGE" => "#SITE_DIR#search/index.php",
                    "SUGGEST_MAX_COUNT" => 150,
                    "SUGGEST_SHOW_COUNT" => 6,
                    "SUGGEST_SHOW_COUNT_MOBILE" => 6,
                    "CACHE_TIME" => 36000
                )
            );
            */?>

            <main class="maksv-content-main">
                <?if(!$mainPage){?>
                    <?// Крошки
                    $APPLICATION->IncludeComponent("bitrix:breadcrumb", "",
                        [
                            "COMPOSITE_FRAME_MODE" => "A",
                            "COMPOSITE_FRAME_TYPE" => "AUTO",
                            "PATH"                 => "",
                            "SITE_ID"              => "s1",
                            "START_FROM"           => "0",
                            "COMPONENT_TEMPLATE"   => ".default",
                        ],
                        false
                    );?>
                <?}?>
                
