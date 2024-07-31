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
?>

<div class="popup-header">
    <button class="button-close-popup" data-close-popup=""></button>
    <h3 class="popup-title">Оставить отзыв</h3>
</div>
<div class="popup-content">
    <div class="popup-content-inner">
        <div class="review-form">
            <p class="review-form-intro">
                Мы будем признательны, если вы оставите мнение о магазине и поделитесь своим опытом покупки. Это очень важно для нас и поможет другим покупателям.
            </p>
            <form action="" id="feedbackForm">
                <input type="hidden" value="feedback-form" name="FORM_TYPE">
                <input type="hidden" value="<?=$arParams['EMAIL_SEND']?>" name="EMAIL_SEND">
                <input type="hidden" value="<?=$arParams['EMAIL_TO']?>" name="EMAIL_TO">
                <input type="hidden" value="<?=$arParams['EVENT_NAME']?>" name="EVENT_NAME">
                <fieldset class="form-fieldset">
                    <h2 class="review-form-subtitle">Ваша оценка</h2>
                    <ul class="scores-list">
                        <li class="score-item">1</li>
                        <li class="score-item">2</li>
                        <li class="score-item">3</li>
                        <li class="score-item">4</li>
                        <li class="score-item _selected">5</li>
                    </ul>
                </fieldset>
                <input type="hidden" value="" name="RATE">
                <fieldset class="form-fieldset">
                    <input class="form-input" type="text" name="NAME" placeholder="Имя*"> <input class="form-input" name="CITY" placeholder="Город*">
                </fieldset>
                <fieldset class="form-fieldset fieldset-review-text">
                    <h2 class="review-form-subtitle">ваш отзыв</h2>
                    <?$maxlength='1400'?>
                    <span class="review-text-counter"><span id="countMessageSimbol">0</span>/<?=$maxlength?></span>
                    <textarea class="form-textarea" name="" id="message-text" cols="30" rows="10" maxlength="<?=$maxlength?>"></textarea>
                    <input type="hidden" value="" name="MASSAGE">
                </fieldset>
                <footer class="form-fieldset form-fieldset-footer">
                    <input class="button" value="Отправить" type="submit">
                    <button style="display: none;" id="successOpenModal" data-popup="popup-review-sent">popUp</button>
                    <p class="review-user-agreement">
                        Нажимая кнопку “Отправить”, вы даете согласие на обработку <a href="/about/privacy-policy/" target="_blank">персональных данных</a>.
                    </p>
                </footer>
            </form>
        </div>
    </div>

</div>



