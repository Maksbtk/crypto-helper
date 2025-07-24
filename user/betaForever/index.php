<?
use Bitrix\Main\Application;
//use Bitrix\Main\Web\Cookie;
use Bitrix\Main\Page\Asset;

define('NEED_AUTH', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "beta forever");
$APPLICATION->SetTitle("beta forever");

global $USER;
$application = Application::getInstance();
$context = $application->getContext();

//стили форм авторизации
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/forms-auth.css");
//стили попапов авторизации
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/popup-auth.css");
//стили страниц личного кабинета
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-profile.css");

//if (!$USER->IsAdmin())
//  LocalRedirect('/', false, '301 Moved permanently');
?>

    <script defer src="<?=SITE_TEMPLATE_PATH?>/js/jquery-profile.js?v=12"></script>

    <div class="profile-wrapper">
        <?
        //меню (тянется из /user/) и аватарка с функционалом ее замены
        $APPLICATION->IncludeComponent("bitrix:menu", "user.sidebar", Array(
            "ALLOW_MULTI_SELECT" => "N",    // Разрешить несколько активных пунктов одновременно
            "CHILD_MENU_TYPE" => "left",    // Тип меню для остальных уровней
            "DELAY" => "N",    // Откладывать выполнение шаблона меню
            "MAX_LEVEL" => "1",    // Уровень вложенности меню
            "MENU_CACHE_GET_VARS" => array(    // Значимые переменные запроса
                0 => "",
            ),
            "MENU_CACHE_TIME" => "1800",    // Время кеширования (сек.)
            "MENU_CACHE_TYPE" => "N",    // Тип кеширования
            "MENU_CACHE_USE_GROUPS" => "Y",    // Учитывать права доступа
            "ROOT_MENU_TYPE" => "left",    // Тип меню для первого уровня
            "USE_EXT" => "N",    // Подключать файлы с именами вида .тип_меню.menu_ext.php
        ), false);
        ?>

        <?if(in_array(6, $USER->GetUserGroupArray())){?>
            <section class="profile-section profile-section-loyalty">
                <?
                $APPLICATION->IncludeComponent(
                    "maksv:signal.trading.strategy.builder",
                    'screener',
                    array(
                        "MAIN_CODE" => 'screener',
                        "CACHE_TIME" => 36000,
                        "MARKET_CODE" => 'betaForever',
                        "BETA_SECTION_CODE" => 'normal_ml',
                        "PROFIT_FILTER" => 'N',
                        "PAGE_COUNT" => '10',
                    )
                );
                ?>
            </section>
        <?}else{?>
            <section class="profile-section profile-section-loyalty">
                <div class="profile-user-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" height="48" fill="none" viewBox="0 0 144 48"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M24 44c11.046 0 20-8.954 20-20S35.046 4 24 4 4 12.954 4 24s8.954 20 20 20z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M23.993 18.136c-2-2.338-5.333-2.966-7.838-.826s-2.858 5.719-.89 8.25c1.26 1.622 4.486 4.629 6.643 6.58.717.65 1.076.974 1.505 1.104.37.112.791.112 1.16 0 .43-.13.788-.455 1.505-1.103 2.157-1.952 5.384-4.96 6.644-6.58 1.967-2.532 1.658-6.133-.89-8.251-2.549-2.118-5.84-1.512-7.839.826z" clip-rule="evenodd"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M72 44c11.046 0 20-8.954 20-20S83.046 4 72 4s-20 8.954-20 20 8.954 20 20 20z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M71.993 18.136c-2-2.338-5.333-2.966-7.838-.826s-2.858 5.719-.89 8.25c1.26 1.622 4.486 4.629 6.644 6.58.716.65 1.075.974 1.504 1.104.37.112.791.112 1.16 0 .43-.13.788-.455 1.505-1.103 2.157-1.952 5.384-4.96 6.644-6.58 1.967-2.532 1.658-6.133-.89-8.251-2.548-2.118-5.84-1.512-7.839.826z" clip-rule="evenodd"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M120 44c11.046 0 20-8.954 20-20s-8.954-20-20-20-20 8.954-20 20 8.954 20 20 20z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M119.993 18.136c-1.999-2.338-5.333-2.966-7.838-.826s-2.858 5.719-.891 8.25c1.26 1.622 4.487 4.629 6.644 6.58.717.65 1.076.974 1.505 1.104.369.112.791.112 1.16 0 .429-.13.788-.455 1.505-1.103 2.157-1.952 5.384-4.96 6.644-6.58 1.967-2.532 1.658-6.133-.89-8.251-2.549-2.118-5.839-1.512-7.839.826z" clip-rule="evenodd"/></svg>
                    <h2>Вы еще купили (или не продлили) доступ к сигналам для биржи Bybit</h2>
                    <a href="/user/subscriptions/" class="button white-color-font">Купить</a>
                    <br>
                    <div class="error-message">
                    </div>
                </div>
            </section>
        <?}?>

    </div><!-- end profile-wrapper -->

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>