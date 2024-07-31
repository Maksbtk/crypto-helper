<?use Bitrix\Main\Page\Asset;
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

//стили страниц авторизации
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-auth.css");
//стили попапов авторизации
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/forms-auth.css");?>

<script src="<?=SITE_TEMPLATE_PATH?>/js/jquery-auth.js"></script>

<div class="page-auth-container">
    <?if($_REQUEST["sms_auth"] == "yes"){?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.10/jquery.mask.js"></script>
        
        <?php $APPLICATION->IncludeComponent(
            "mindbox:auth.sms",
            "custom",
            [
                "FILLUP_FORM_FIELDS" => ["MOBILE_PHONE"],
                "PERSONAL_PAGE_URL" => "/user/profile/"
            ]
        ); ?>
    <?}else{?>
        <div class="authbox authbox-email">
            <h2 class="authbox-title">мой CH</h2>
            <div class="form-row">
                <?if (!empty($arParams["~AUTH_RESULT"]) && $_REQUEST["login"] == "yes") {
                    ShowMessage($arParams["~AUTH_RESULT"]);
                }?>
            </div>
            <form id="auth-email-form" action="<?=$arResult["AUTH_URL"]?>" method="post" target="_top">
                <input type="hidden" name="AUTH_FORM" value="Y" />
                <input type="hidden" name="TYPE" value="AUTH" />
                <?if ($arResult["BACKURL"] <> ''):?>
                    <input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
                <?endif?>
                <?foreach ($arResult["POST"] as $key => $value):?>
                    <input type="hidden" name="<?=$key?>" value="<?=$value?>" />
                <?endforeach?>

                <div class="form-row">
                    <input type="text" class="form-input" placeholder="Эл. почта*" name="USER_LOGIN">
                </div>
                <div class="form-row">
                    <div class="form-input-password-wrapper">
                        <input type="password" class="form-input" placeholder="Пароль*" name="USER_PASSWORD"  maxlength="255">
                            <a class="password-control">
                                <svg fill="none" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M3.333 3.333l13.334 13.334M13.75 13.963C12.623 14.57 11.349 15 10 15c-4.603 0-8.333-5-8.333-5s1.74-2.332 4.31-3.811m10.273 6.006C17.546 11.055 18.333 10 18.333 10S14.603 5 10 5c-.281 0-.56.019-.834.054M11.103 11.25A1.667 1.667 0 0 1 8.8 8.843"/></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 12c3.682 0 6.667-4 6.667-4S11.682 4 8 4 1.333 8 1.333 8 4.318 12 8 12z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 9.333a1.333 1.333 0 1 0 0-2.666 1.333 1.333 0 0 0 0 2.666z"/></svg>
                            </a>
                    </div>
                    <?if (!empty($arResult['ERROR_MESSAGE'])) {
                        ShowMessage($arResult['ERROR_MESSAGE']);
                    }?>
                </div>
                
                <?if($arResult["CAPTCHA_CODE"]):?>
                    <tr>
                        <td></td>
                        <td><input type="hidden" name="captcha_sid" value="<?echo $arResult["CAPTCHA_CODE"]?>" />
                            <img src="/bitrix/tools/captcha.php?captcha_sid=<?echo $arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" /></td>
                    </tr>
                    <tr>
                        <td class="bx-auth-label"><?echo GetMessage("AUTH_CAPTCHA_PROMT")?>:</td>
                        <td><input class="bx-auth-input form-control" type="text" name="captcha_word" maxlength="50" value="" size="15" autocomplete="off" /></td>
                    </tr>
                <?endif;?>
                
                <input type="checkbox" id="USER_REMEMBER" name="USER_REMEMBER" value="Y" checked="checked" style="display: block; position: absolute; left: -99999px" />
                
                <a href="<?=$arResult["AUTH_FORGOT_PASSWORD_URL"]?>" class="auth-email-forgetpass-link" rel="nofollow">Забыли пароль?</a>
                <div class="form-row">
                    <input type="submit" class="button form-button white-color-font white-color-font" name="Login" value="Войти"> <?//Войти</button>?>
                </div>
                <?/*<a class="auth-email-link" href="/user/profile/?sms_auth=yes">Войти по номеру телефона</a>*/?>
            </form>

            <div class="authbox-go-register">
                <p>Нет аккаунта? Пройдите регистрацию</p>
                <a href="<?=$arResult["AUTH_REGISTER_URL"]?>" rel="nofollow" class="button button-secondary button-go-register">зарегистрироваться</a>
            </div>
        </div>
    <?}?>
</div>

<script type="text/javascript">
    <?if ($arResult["LAST_LOGIN"] <> ''):?>
        try{document.form_auth.USER_PASSWORD.focus();}catch(e){}
    <?else:?>
        try{document.form_auth.USER_LOGIN.focus();}catch(e){}
    <?endif?>
</script>
