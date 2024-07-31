<?
use Bitrix\Main\Page\Asset;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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

        <div class="authbox authbox-password-recovery">
            <h2 class="authbox-title">восстановление пароля</h2>
            <?if($arResult["SHOW_FORM"]):?>

            <form method="post" action="<?=$arResult["AUTH_URL"]?>" name="bform" id="auth-password-recovery-form" >
                <?if ($arResult["BACKURL"] <> ''): ?>
                    <input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
                <? endif ?>
                <input type="hidden" name="AUTH_FORM" value="Y">
                <input type="hidden" name="TYPE" value="CHANGE_PWD">


                <input type="hidden" name="USER_LOGIN" maxlength="50" value="<?=$arResult["LAST_LOGIN"]?>" />
                <?if($arResult["USE_PASSWORD"]):?>
                    <input type="hidden" name="USER_CURRENT_PASSWORD" maxlength="255" value="<?=$arResult["USER_CURRENT_PASSWORD"]?>"  autocomplete="new-password" />
                <?else:?>
                    <input type="hidden" name="USER_CHECKWORD" maxlength="50" value="<?=$arResult["USER_CHECKWORD"]?>" autocomplete="off" />
                <?endif;?>
                <div class="form-row">
                    <div class="form-input-password-wrapper">
                        <input class="form-input" type="password" name="USER_PASSWORD" maxlength="255" value="<?=$arResult["USER_PASSWORD"]?>" placeholder="Новый пароль" autocomplete="new-password" />
                        <a class="password-control">
                            <svg fill="none" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M3.333 3.333l13.334 13.334M13.75 13.963C12.623 14.57 11.349 15 10 15c-4.603 0-8.333-5-8.333-5s1.74-2.332 4.31-3.811m10.273 6.006C17.546 11.055 18.333 10 18.333 10S14.603 5 10 5c-.281 0-.56.019-.834.054M11.103 11.25A1.667 1.667 0 0 1 8.8 8.843"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 12c3.682 0 6.667-4 6.667-4S11.682 4 8 4 1.333 8 1.333 8 4.318 12 8 12z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 9.333a1.333 1.333 0 1 0 0-2.666 1.333 1.333 0 0 0 0 2.666z"/></svg>
                        </a>
                    </div>
                    <p class="message"><?=$arResult["GROUP_POLICY"]["PASSWORD_REQUIREMENTS"]?></p>
                </div>
                <div class="form-row">
                    <div class="form-input-password-wrapper">
                        <input type="password" name="USER_CONFIRM_PASSWORD" maxlength="255" value="<?=$arResult["USER_CONFIRM_PASSWORD"]?>" class="form-input" placeholder="Повторите пароль"" placeholder="Повторите пароль" autocomplete="new-password" />

                        <a class="password-control">
                            <svg fill="none" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M3.333 3.333l13.334 13.334M13.75 13.963C12.623 14.57 11.349 15 10 15c-4.603 0-8.333-5-8.333-5s1.74-2.332 4.31-3.811m10.273 6.006C17.546 11.055 18.333 10 18.333 10S14.603 5 10 5c-.281 0-.56.019-.834.054M11.103 11.25A1.667 1.667 0 0 1 8.8 8.843"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 12c3.682 0 6.667-4 6.667-4S11.682 4 8 4 1.333 8 1.333 8 4.318 12 8 12z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 9.333a1.333 1.333 0 1 0 0-2.666 1.333 1.333 0 0 0 0 2.666z"/></svg>
                        </a>
                    </div>
                    <p class="message error-message">Пароли не соотвествуют</p>
                </div>
                <div class="form-row">
                    <input type="submit" name="change_pwd" class="button form-button button-change-password white-color-font white-color-font" value="сменить пароль">
                </div>
                <div class="form-row" style="display: flex;align-items: center;justify-content: center;">
                    <a style="" href="<?=$arResult["AUTH_AUTH_URL"]?>" rel="nofollow">Авторизация</a>
                </div>
            </form>
            <?endif;?>

            <? if (!empty($arParams["~AUTH_RESULT"])):?>
            <div class="auth-password-recovery-success" >
                <p class="auth-success-text"><?ShowMessage($arParams["~AUTH_RESULT"]);?></p>
                <a href="<?=$arResult["AUTH_AUTH_URL"]?>" class="button white-color-font">войти в аккаунт</a>
            </div>
            <?endif;?>

            
        </div>
    </div>

<script>
    document.bform.USER_PASSWORD.focus();
</script>
