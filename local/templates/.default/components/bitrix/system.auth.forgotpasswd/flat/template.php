<?
use Bitrix\Main\Page\Asset;
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?
global $APPLICATION;
?>
<?
//скрипт для демонстрации работы форм //////////не работает Asset
//Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-auth-demo.js");

//стили страниц авторизации
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-auth.css");

//стили попапов авторизации
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/forms-auth.css");
?>
<script src="<?=SITE_TEMPLATE_PATH?>/js/jquery-auth.js"></script>

<div class="page-auth-container">

    <div class="authbox authbox-forgetpass">
        <h2 class="authbox-title">Забыли пароль?</h2>
        <form id="auth-forgetpass-form" name="bform" method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>">
            <?if ($arResult["BACKURL"] <> ''):?>
                <input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
            <?endif;?>
            <input type="hidden" name="AUTH_FORM" value="Y">
            <input type="hidden" name="TYPE" value="SEND_PWD">

            <p><?echo GetMessage("sys_forgot_pass_label")?></p>

            <p class="auth-forgetpass-text">Введите ваш адрес электронной почты, и мы отправим на него ссылку для смены пароля.</p>
            <div class="form-row">
                <input type="text" class="form-input" placeholder="Эл. почта" name="USER_LOGIN" value="<?=$arResult["USER_LOGIN"]?>">
                <input type="hidden" name="USER_EMAIL" />
                <?/*<p class="error-message">Пользователь с такой эл. почтой не найден</p>*/?>
            </div>
            <div class="form-row">
                <input class="button form-button button-send-forgetpass white-color-font" type="submit" name="send_account_info" value="Выслать" />

                <a href="/user/profile/" class="button button-secondary form-button">Отмена</a>
            </div>
        </form>

        <? if (!empty($arParams["~AUTH_RESULT"]) && $arParams["~AUTH_RESULT"]["ERROR_TYPE"] !== "LOGIN"):?>
        <div class="auth-forgetpass-success" >
            <p class="auth-success-text"><?ShowMessage($arParams["~AUTH_RESULT"]);?></p>
            <a href="/user/profile/" class="button white-color-font">Войти в аккаунт</a>
        </div>
        <?endif;?>


    </div>

</div>



