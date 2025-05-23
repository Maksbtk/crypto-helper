<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
    die();

use Bitrix\Main\Page\Asset;

//стили страниц авторизации
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-auth.css");

//стили попапов авторизации
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/forms-auth.css");

Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery.maskedinput.js");
?>

<script src="<?=SITE_TEMPLATE_PATH?>/js/jquery-auth.js"></script>

<?if($arResult["SHOW_SMS_FIELD"] == true)
{
    CJSCore::Init('phone_auth');
}

$fieldsOrder = [
    'NAME',
    'LAST_NAME',
    'PHONE_NUMBER',
    'EMAIL',        
    'PASSWORD',
    'CONFIRM_PASSWORD'
];?>

<div class="page-auth-container">
    <?if(!$USER->IsAuthorized()) {
        if (count($arResult["ERRORS"]) > 0) {
            $arResult["GENERAL_ERRORS"] = [];
            foreach ($arResult["ERRORS"] as $key => $error) {
                if (intval($key) == 0 && $key !== 0) {
                    $arResult["ERRORS"][$key] = str_replace("#FIELD_NAME#", "&quot;" . GetMessage("REGISTER_FIELD_" . $key) . "&quot;", $error);
                } else {
                    $arResult["GENERAL_ERRORS"][] = $error;
                }
            }
        } elseif($arResult["USE_EMAIL_CONFIRMATION"] === "Y") {?>
            <div class="big-form1__text1 reg-success"><?echo GetMessage("REGISTER_EMAIL_WILL_BE_SENT")?></div>
        <?}?>

        <?/*if($arResult["SHOW_SMS_FIELD"] == true) {?>
            <?$_SESSION["USER_ID"] = $arResult["VALUES"]["USER_ID"];
            $APPLICATION->IncludeComponent(
                "mindbox:phone.confirm",
                "register",
                Array()
            );?>        
        <?} else {*/?>
            <div class="authbox authbox-register">
                <h2 class="authbox-title">Регистрация</h2>
                <div class="form-row">
                    <?
                    if (!empty($arParams["~AUTH_RESULT"]) && $arParams["~AUTH_RESULT"]["ERROR_TYPE"] !== "LOGIN") {
                        ShowMessage($arParams["~AUTH_RESULT"]);
                    }
                    ?>
                </div>

                <form id="registration-form" method="post" action="<?=POST_FORM_ACTION_URI?>" name="regform" enctype="multipart/form-data">
                    <?if ($arResult["BACKURL"] <> '') {?>
                        <input type="hidden" name="backurl" value="<?= $arResult["BACKURL"] ?>"/>
                    <?}?>
                    
                    <?if (count($arResult["ERRORS"]) > 0) {
                        echo '<div class="big-form1__text1 reg-errors">' . implode("<br />", $arResult["GENERAL_ERRORS"]) . '</div>';
                    }?>                
                
                    <input type="hidden" name="AUTH_FORM" value="Y" />
                    <input type="hidden" name="TYPE" value="REGISTRATION" />
                    <div class="form-row">
                        <input class="form-input" type="text" placeholder="Имя*" name="REGISTER[NAME]" maxlength="50" value="<?=$arResult["VALUES"]["NAME"]?>">
                    </div>
                    <div class="form-row">
                        <input class="form-input" type="text" placeholder="Фамилия*" name="REGISTER[LAST_NAME]" maxlength="50" value="<?=$arResult["VALUES"]["LAST_NAME"]?>">

                    </div>
                    <div class="form-row form-row-phone">
                        <?/*<div class="dropdown-phone-code">
                            <div class="dropdown-select"><i class="flag" data-flag="1"></i> <span>+7</span></div>
                            <ul class="dropdown-box">
                                <li class="dropdown-option" data-label="1" data-code="+7">Россия +7</li>
                                <li class="dropdown-option" data-label="2" data-code="+7">Казахстан +7</li>
                                <li class="dropdown-option" data-label="3" data-code="+375">Беларусь +375</li>
                                <li class="dropdown-option" data-label="4" data-code="+996">Кыргызстан +996</li>
                            </ul>
                        </div>*/?>

                        <input class="form-input" type="tel" placeholder="Номер телефона*" name="REGISTER[PHONE_NUMBER]" maxlength="255" value="">
                    </div>
                    <div class="form-row">
                        <input type="hidden" name="REGISTER[LOGIN]" maxlength="50" value="">
                        <input class="form-input" type="email" placeholder="Эл. почта*" id="reg-email-field" name="REGISTER[EMAIL]" maxlength="255" value="<?=$arResult["VALUES"]["EMAIL"]?>">
                    </div>
                    <div class="form-row">
                        <div class="form-input-password-wrapper">
                            <input type="password" class="form-input" placeholder="Пароль*" name="REGISTER[PASSWORD]" maxlength="255" value="<?=$arResult["VALUES"]["PASSWORD"]?>">
                            <a class="password-control">
                                <svg fill="none" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M3.333 3.333l13.334 13.334M13.75 13.963C12.623 14.57 11.349 15 10 15c-4.603 0-8.333-5-8.333-5s1.74-2.332 4.31-3.811m10.273 6.006C17.546 11.055 18.333 10 18.333 10S14.603 5 10 5c-.281 0-.56.019-.834.054M11.103 11.25A1.667 1.667 0 0 1 8.8 8.843"/></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 12c3.682 0 6.667-4 6.667-4S11.682 4 8 4 1.333 8 1.333 8 4.318 12 8 12z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 9.333a1.333 1.333 0 1 0 0-2.666 1.333 1.333 0 0 0 0 2.666z"/></svg>
                            </a>
                        </div>
                        <p class="message">Минимум 8 символов</p>
                    </div>
                    <div class="form-row">
                        <div class="form-input-password-wrapper">
                            <input type="password" class="form-input" placeholder="Подтвердите пароль*" name="REGISTER[CONFIRM_PASSWORD]" maxlength="255" value="<?=$arResult["VALUES"]["CONFIRM_PASSWORD"]?>">
                            <a class="password-control">
                                <svg fill="none" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M3.333 3.333l13.334 13.334M13.75 13.963C12.623 14.57 11.349 15 10 15c-4.603 0-8.333-5-8.333-5s1.74-2.332 4.31-3.811m10.273 6.006C17.546 11.055 18.333 10 18.333 10S14.603 5 10 5c-.281 0-.56.019-.834.054M11.103 11.25A1.667 1.667 0 0 1 8.8 8.843"/></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 12c3.682 0 6.667-4 6.667-4S11.682 4 8 4 1.333 8 1.333 8 4.318 12 8 12z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 9.333a1.333 1.333 0 1 0 0-2.666 1.333 1.333 0 0 0 0 2.666z"/></svg>
                            </a>
                        </div>
                    </div>
                    <div class="registration-agreements">
                        <div class="form-row form-row-checkbox">
                            <input class="input-checkbox check_news" type="checkbox" name="UF_NEWS" value="1" id="subscritCB" checked="checked">
                            <label class="label-checkbox" for="subscritCB">Подтверждаю свое согласие на получение информации о новинках и выгодных предложениях</label>
                        </div>
                        <div class="form-row form-row-checkbox">
                            <input class="input-checkbox" type="checkbox" name="UF_AGREEMENT" value="" id="personalDataCB">
                            <label class="label-checkbox" for="personalDataCB">Подтверждаю свое согласие на обработку и хранение моих персональных данных в соответствии с пользовательским соглашением</label>
                        </div>
                        <div class="form-row form-row-checkbox">
                            <input class="input-checkbox check_pl" type="checkbox" name="UF_PL_MEMBER" value="1" id="loyaltyCB" checked="checked">
                            <label class="label-checkbox" for="loyaltyCB">Подтверждаю согласие <a href="/oferta/">с публичной офертой сайта Crypto Helper</a>. Несу полную ответственность за использование информационных продуктов, расположенных на сайте <a href="/">https://infocrypto-helper.ru/</a></label>
                        </div>
                    </div>
                    <div class="form-row">
                        <input class="button form-button js-reg-button button-register white-color-font" type="submit" name="register_submit_button" value="зарегистрироваться" />
                    </div>
                    <div class="form-row" style="display: flex;align-items: center;justify-content: center;">
                        <a style="" href="/user/profile/" rel="nofollow">Авторизация</a>
                    </div>

                </form>

            </div>
        <? /* } */?>
    <?}?>
</div>

<style type="text/css">
.reg-errors {
    color: #E53737;
    margin-bottom: 15px;
    text-align: center;
}
.checkbox-error .input-checkbox + label::before {
    border-color: #E53737 !important;
}
</style>
