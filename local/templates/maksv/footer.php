<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
        </main>

        <footer class="maksv-footer white-color-font">
            <div class="footer-userbox">
                <?$APPLICATION->IncludeComponent(
                    "bitrix:menu",
                    "bottom",
                    array(
                        "ALLOW_MULTI_SELECT" => "N",
                        "CHILD_MENU_TYPE" => "",
                        "DELAY" => "N",
                        "MAX_LEVEL" => "1",
                        "MENU_CACHE_GET_VARS" => array(
                        ),
                        "MENU_CACHE_TIME" => "1800",
                        "MENU_CACHE_TYPE" => "A",
                        "MENU_CACHE_USE_GROUPS" => "Y",
                        "ROOT_MENU_TYPE" => "bottom",
                        "USE_EXT" => "N",
                        "COMPONENT_TEMPLATE" => "bottom"
                    ),
                    false
                );?>

            </div>

            <?/*
            $APPLICATION->IncludeComponent(
                "bitrix:news.detail",
                "footer.brandbox",
                array(
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
                    "ELEMENT_ID" => "151684",
                    "FIELD_CODE" => array(
                        0 => "",
                        1 => "",
                    ),
                    "IBLOCK_ID" => "47",
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
                    "PROPERTY_CODE" => array(
                        0 => "SLOGAN",
                        1 => "COMPANY_PHONE",
                        2 => "COMPANY_PHONE_TEXT",
                        3 => "LINK_VK",
                        4 => "LINK_TG",
                        5 => "MOBILE_TEXT",
                        6 => "LINK",
                        7 => "",
                    ),
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
                    "USE_SHARE" => "N",
                    "COMPONENT_TEMPLATE" => "footer.brandbox"
                ),
                false
            );*/
            ?>
            <div class="footer-brandbox__inner">
                <div class="footer-brandbox__slogan">Crypto Helper</div>

                <div class="footer-brandbox__contacts">
                    <?/*<a href="tel:+79502675091" class="footer-contacts__phone white-color-font">+79502675091</a>*/?>
                    <a href="/about/" class="footer-contacts__phone white-color-font">Crypto Helper</a>
                    <p class="footer-contacts__text"><a class="white-color-font" href="/oferta/">ИП Максимов Василий Андреевич</a></p>
                </div>

                <div class="footer-brandbox__socials">
                    <noindex class="footer-brandbox__socials-inner">
                        <a href="https://t.me/CryptoHelperSupport" target="_blank" class="footer-social__icn footer-social__icn-tg" rel="nofollow"><svg width="40" height="40" viewBox="0 0 40 40" fill="none" style="stroke: white;" xmlns="http://www.w3.org/2000/svg"><path d="M28 12.6022L24.9946 28.2923C24.9946 28.2923 24.5741 29.3801 23.4189 28.8584L16.4846 23.3526L16.4524 23.3364C17.3891 22.4654 24.6524 15.7027 24.9698 15.3961C25.4613 14.9214 25.1562 14.6387 24.5856 14.9974L13.8568 22.053L9.71764 20.6108C9.71764 20.6108 9.06626 20.3708 9.00359 19.8491C8.9401 19.3265 9.73908 19.0439 9.73908 19.0439L26.6131 12.1889C26.6131 12.1889 28 11.5579 28 12.6022Z" fill="#1F2020"></path></svg></a>
                    </noindex>
                </div>
            </div>

        </footer>
        </div>

        <style type="text/css">
            .authbox-title {
                margin: 10px 0;
                font-size: 14px;
                line-height: 18px;
                
                font-family: var(--font-family-header);
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.02em;    
            }        
        </style>
        <?// попап ведущий на авторизацию ?>
        <?/*<div class="popup popup-vertical popup-auth popup-go-to-auth" style="display: none;">
            <div class="popup__backdrop" data-close-popup></div>
            <div class="popup-body">
                <button class="button-close-popup" data-close-popup></button>
                <div class="popup-content">

                    <div class="authbox authbox-save-changes">
                        <div class="authbox-title">Необходимо авторизоваться</div>
                        <p class="auth-forgetpass-text">Для того чтобы добавить товар в избранное, необходимо авторизоваться</p>
                        <div class="form-row">
                            <a href="/auth/" class="button form-button">Авторизоваться</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>*/?>

        <?//прелоудер?>
        <div class="preloader-body">
            <div class="preloader-inner"><img src="<?= SITE_TEMPLATE_PATH ?>/img/preloader/loader__new.svg" alt="" class="spinner"></div>
        </div>

    </body>
</html>
