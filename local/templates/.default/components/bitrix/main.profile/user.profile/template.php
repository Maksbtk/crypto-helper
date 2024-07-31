<?
use Bitrix\Main\Page\Asset;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery.maskedinput.js");
?>
     
    <section class="profile-section profile-section-user-data">

        <form id="userPersonalDataForm" action="<?=$arResult["FORM_TARGET"]?>" name="form1" method="post" class="form form-user-profile">


        <?=$arResult["BX_SESSION_CHECK"]?>
                <input type="hidden" name="lang" value="<?=LANG?>" />
                <input type="hidden" name="ID" value="<?=$arResult["ID"]?>" />
                <input type="hidden" value="Y" name="save">
            <?
            if ($arResult['DATA_SAVED'] == 'Y') {
                echo '<div class="big-form1__text1 reg-success" style="color: green; margin-bottom: 15px;">Личные данные сохранены</div>';
            }

            if ($arResult["strProfileError"]) {
                echo '<div class="big-form1__text1 reg-errors" style="color: red; margin-bottom: 15px;">' . $arResult["strProfileError"] .'</div>';
            }
            ?>
            <div class="profile-user-data-box">
                <fieldset class="form-fieldset fieldset-user-contact">
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
                        <?/*<input class="form-input" type="text" name="PHONE_NUMBER" placeholder="Номер телефона" value="<?=$arResult['USER_PHONE_NUMBER']?>">*/?>
                        <input class="form-input" type="text" name="PHONE_NUMBER" placeholder="Номер телефона" value="<?= $arResult["arUser"]["PHONE_NUMBER"] ?>">
                    </div>
                    <div class="form-row">
                        <input class="form-input" type="email" name="EMAIL"  placeholder="Эл. почта" value="<?=$arResult["arUser"]["EMAIL"]?>">
                        <input style="display: none;" type="text" name="LOGIN" value="<?=$arResult["arUser"]["LOGIN"]?>">
                    </div>
                    <div class="form-row">
                        <a href="#" class="change-password-link" data-popup="popup-auth_changepass">Изменить пароль</a>
                    </div>
                </fieldset>

                <fieldset class="form-fieldset fieldset-user-name">
                    <div class="form-row">
                        <input class="form-input" type="text" placeholder="Имя" name="NAME" value="<?=$arResult["arUser"]["NAME"]?>">
                    </div>
                    <div class="form-row">
                        <input class="form-input" type="text" placeholder="Фамилия" name="LAST_NAME" value="<?=$arResult["arUser"]["LAST_NAME"]?>">
                    </div>
                    <div class="form-row">
                        <input class="form-input" type="text" name="SECOND_NAME" placeholder="Отчество" value="<?=$arResult["arUser"]["SECOND_NAME"]?>">
                    </div>
                    <div class="form-row">
                        <input id="fakeBirthday" class="form-input" type="date" placeholder="Дата рождения" value="<?=strtolower(FormatDate(/*"d.m.Y"*/"Y-m-d", MakeTimeStamp($arResult["arUser"]["PERSONAL_BIRTHDAY"]))) ?>" placeholder="дд.мм.гггг">
                        <input style="display: none;" type="text" name="PERSONAL_BIRTHDAY" placeholder="Дата рождения" value="<?=strtolower(FormatDate("d.m.Y", MakeTimeStamp($arResult["arUser"]["PERSONAL_BIRTHDAY"]))) ?>">
                    </div>
                </fieldset>

                <!--
                <h3 class="profile-user-address-title">Адрес доставки</h3>
                <fieldset class="form-fieldset fieldset-user-address">
                    <div class="form-row">
                        <input class="form-input" type="text" placeholder="Город" value="Москва">
                    </div>
                    <div class="form-row">
                        <input class="form-input" type="text" placeholder="Улица" value="ул. Стандартная">
                    </div>
                    <div class="form-row short-row">
                        <input class="form-input" type="text" placeholder="Дом" value="84">
                    </div>
                    <div class="form-row short-row">
                        <input class="form-input" type="text" placeholder="Квартира" value="22">
                    </div>
                    <div class="form-row">
                        <input class="form-input" type="text" placeholder="Индекс" value="123456">
                    </div>

                </fieldset>
                -->
                
                <div class="form-row">
                    <button class="button form-button white-color-font" data-popup="popup-save-changes">Сохранить</button>
                    <input style="display: none;" id="save-form-inp" type="submit" value="Сохранить" />
                </div>
        </form>

    </section>

    <!-- попап изменить пароль -->
    <div class="popup popup-vertical popup-auth popup-auth_changepass">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <button class="button-close-popup" data-close-popup></button>
            <div class="popup-content">

                <div class="authbox authbox-changepass">
                    <div class="h2 authbox-title">Изменение пароля</div>
                    <?/*<form>*/?>
                    <form <?/*action="/ajax/changePass.php" method="post"*/?> id="changePassForm">
                    <div class="form-row">
                            <div class="form-input-password-wrapper">
                                <input  id="old_password" name="PASSWORD" type="password" class="form-input" placeholder="Введите текущий пароль" >
                                <a class="password-control">
                                    <svg fill="none" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M3.333 3.333l13.334 13.334M13.75 13.963C12.623 14.57 11.349 15 10 15c-4.603 0-8.333-5-8.333-5s1.74-2.332 4.31-3.811m10.273 6.006C17.546 11.055 18.333 10 18.333 10S14.603 5 10 5c-.281 0-.56.019-.834.054M11.103 11.25A1.667 1.667 0 0 1 8.8 8.843"/></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 12c3.682 0 6.667-4 6.667-4S11.682 4 8 4 1.333 8 1.333 8 4.318 12 8 12z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 9.333a1.333 1.333 0 1 0 0-2.666 1.333 1.333 0 0 0 0 2.666z"/></svg>
                                </a>
                            </div>
                            <?/*<p class="message error-message">Введен неверный пароль</p>*/?>
                            <?/*<p class="message">Забыли текущий пароль? <a href="/auth/?forgot_password=yes">Восстановить</a></p>*/?>
                        </div>
                        <div class="form-row">
                            <div class="form-input-password-wrapper">
                                <input id="new_password" type="password" name="NEW_PASSWORD" class="form-input" placeholder="Введите новый пароль">
                                <a class="password-control">
                                    <svg fill="none" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M3.333 3.333l13.334 13.334M13.75 13.963C12.623 14.57 11.349 15 10 15c-4.603 0-8.333-5-8.333-5s1.74-2.332 4.31-3.811m10.273 6.006C17.546 11.055 18.333 10 18.333 10S14.603 5 10 5c-.281 0-.56.019-.834.054M11.103 11.25A1.667 1.667 0 0 1 8.8 8.843"/></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 12c3.682 0 6.667-4 6.667-4S11.682 4 8 4 1.333 8 1.333 8 4.318 12 8 12z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 9.333a1.333 1.333 0 1 0 0-2.666 1.333 1.333 0 0 0 0 2.666z"/></svg>
                                </a>
                            </div>
                            <p style="display: none;" class="message">Минимум 8 символов</p>
                        </div>
                        <div class="form-row">
                            <div class="form-input-password-wrapper">
                                <input id="new_password_confirm" type="password" name="NEW_PASSWORD_CONFIRM" class="form-input" placeholder="Подтвердите новый пароль">
                                <a class="password-control">
                                    <svg fill="none" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M3.333 3.333l13.334 13.334M13.75 13.963C12.623 14.57 11.349 15 10 15c-4.603 0-8.333-5-8.333-5s1.74-2.332 4.31-3.811m10.273 6.006C17.546 11.055 18.333 10 18.333 10S14.603 5 10 5c-.281 0-.56.019-.834.054M11.103 11.25A1.667 1.667 0 0 1 8.8 8.843"/></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 12c3.682 0 6.667-4 6.667-4S11.682 4 8 4 1.333 8 1.333 8 4.318 12 8 12z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M8 9.333a1.333 1.333 0 1 0 0-2.666 1.333 1.333 0 0 0 0 2.666z"/></svg>
                                </a>
                            </div>
                            <?/*<p class="message error-message">Пароли не совпадают</p>*/?>
                        </div>
                        <div class="message error-message">
                            Пароли не совпадают
                        </div>
                        <div class="form-row">
                            <input type="submit" class="button form-button button-change-password white-color-font" value="Изменить пароль">
                            <?/*<button type="button" class="button form-button button-change-password">Изменить пароль</button>*/?>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- попап Сохранить изменения -->
    <div class="popup popup-vertical popup-auth popup-save-changes">
        <div class="popup__backdrop" data-close-popup></div>
        <div class="popup-body">
            <button class="button-close-popup" data-close-popup></button>
            <div class="popup-content">

                <div class="authbox authbox-save-changes">
                    <div class="h2 authbox-title">Сохранить изменения?</div>
                    <p class="auth-forgetpass-text">Вы ввели неверные электронную почту или телефон, если вы уйдете со страницы, то изменения не вступят в силу, продолжить?</p>
                    <div class="form-row">
                        <button class="button form-button button-secondary">Не сохранять</button>

                        <button id="save-form-btn" class="button form-button white-color-font">Да, сохранить</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

