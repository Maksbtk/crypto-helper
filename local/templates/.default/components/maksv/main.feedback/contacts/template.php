<?php
use Bitrix\Main\Page\Asset;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
global $USER;
                                                                             
Asset::getInstance()->addJs( "https://www.google.com/recaptcha/api.js?render=6LfidaUpAAAAAAlS_kLGX4FVOe7S__HcEmzKLIpl");
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery.maskedinput.js");

?>

<div class="page-contacts-feedback">
    <h2 class="page-contacts-subtitle">Форма обратной связи</h2>
    <form class="feedback-form" id="contactsFeedbackForm">
        <p class="contacts-feedback-intro">
            Заполните все поля и нажмите «Отправить», мы обработаем ваше обращение и свяжемся в течение 24 часов.
        </p>
        <fieldset class="form-fieldset" >
            <input type="hidden" value="contacts-form" name="FORM_TYPE">
            <input type="hidden" value="<?=$arParams['EMAIL_SEND']?>" name="EMAIL_SEND">
            <input type="hidden" value="<?=$arParams['EMAIL_TO']?>" name="EMAIL_TO">
            <input type="hidden" value="<?=$arParams['EVENT_NAME']?>" name="EVENT_NAME">
            <?if ($arParams['SUBJECT_FROM'] != 'N'):?>
                <div class="form-row">
                    <div class="dropdown dropdown-theme">
                        <div class="dropdown-select">
                            Тема обращения
                        </div>
                        <ul class="dropdown-box">
                            <?if (is_array($arParams['SUBJECT_FROM'])):?>
                                <? foreach ($arParams['SUBJECT_FROM'] as $key => $item):?>
                                    <li class="dropdown-option" data-label="<?=$key+1?>"><?=$item?></li>
                                <?endforeach;?>
                            <?endif;?>
                        </ul>
                    </div>
                </div>
                <input type="hidden" value="" name="SUBJECT">
            <?endif;?>
            <div class="form-row">
                <input class="form-input" type="text" placeholder="Имя*" value="" name="NAME">
            </div>
            <div class="form-row">
                <input class="form-input" type="text" placeholder="Телефон*" value="" name="NUMBER">
            </div>
            <div class="form-row">
                <input class="form-input" type="text" placeholder="Электронная почта*" value="" name="EMAIL">
            </div>
        </fieldset>
        <div class="form-row ">
            <textarea class="form-textarea" name="" id="message-text" cols="30" rows="10" placeholder="Сообщение"></textarea>
            <input type="hidden" value="" name="MASSAGE">
        </div>
        <div class="button-wrapper">
            <input class="button"value="Отправить" type="submit">
            <button style="display: none;" id="successOpenModal" data-popup="popup-feedback-sent">popUp</button>

            <p class="feedback-form-agree">
                Нажимая кнопку “Отправить”, вы даете согласие на обработку персональных данных.<br>
                <br>
            </p>
        </div>
    </form>
</div>

<div class="popup popup-vertical popup-feedback-sent">
    <div class="popup__backdrop" data-close-popup=""></div>
    <div class="popup-body">
        <header class="popup-header">
            <button class="button-close-popup" data-close-popup=""></button>
            <h2 class="popup-title">Ваша заявка</h2>
        </header>
        <div class="popup-content">
            <div class="popup-content-inner">
                <p>Спасибо за обращение, мы свяжемся с вами по контактам, указанным в заявке. Хорошего дня!</p>
                <p><a href="javascript:location.reload();" class="button" data-close-popup="">Закрыть</a></p>
            </div>
        </div>
    </div>
</div>