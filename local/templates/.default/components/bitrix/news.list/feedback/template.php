<?
use Bitrix\Main\Page\Asset;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);

/*Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-feedback.css");
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-review-open.js");*/
?>
<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/page-feedback.css">
<script defer src="<?=SITE_TEMPLATE_PATH?>/js/jquery-review-open.js"></script>

<div class="belleyou-content-layout">
    <div class="page-feedback-wrapper">
        <aside class="page-feedback-sidebar">
            <div class="belleyou-content__header">
                <h1>Отзывы</h1>
            </div>
            <div class="feedback-total-box">
                <h4>Благодаря вам мы становимся лучше!</h4>
                <p>
                    Мы будем признательны, если вы оставите отзыв о коллекции, работе отдела клиентского сервиса и службы доставки или поделитесь мнением о качестве товаров.
                </p>
                <p class="feedback-total">
                    <?=$arResult["GENERAL_RATE"]?> / 5
                </p>
                <p>
                    Оценка на основе <?=num_word($arResult["COUNT_FEEDBACK_ALL"],['отзыва', 'отзывов', 'отзывов'])?>
                </p>
                <button class="button button-send-feedback" data-popup="popup-send-review">оставить отзыв</button>
            </div>
        </aside>
        <? if ($arResult["ITEMS"]):?>
        <section class="page-feedback-section">
            <?if($arParams["DISPLAY_TOP_PAGER"]):?>
                <?=$arResult["NAV_STRING"]?><br />
            <?endif;?>
            <div class="reviews-list js-load-more-list">
                <?foreach($arResult["ITEMS"] as $arItem):?>
                    <?
                    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                    ?>
                    <div class="review-item js-load-more-item" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
                    <div class="review-header">
                        <h3 class="review-user-name"><?=$arItem['PROPERTIES']['USER_NAME']['VALUE']?>, <?=$arItem['PROPERTIES']['USER_CITY']['VALUE']?></h3>
                        <?
                        $dOb = new DateTime($arItem['DATE_CREATE']);
                        $dateCreateFormated = $dOb->format('d.m.y');
                        ?>
                        <span class="review-date"><?=$dateCreateFormated?></span>
                        <h4 class="review-user-rating">Оценка: <?=$arItem['PROPERTIES']['RATING']['VALUE']?></h4>
                    </div>
                    <div class="review-text">
                        <p>
                            <?=$arItem['PROPERTIES']['USER_FEEDBACK']['VALUE']?>
                        </p>
                    </div>
                    <?if ($arItem['PROPERTIES']['STORE_RESPONSE']['VALUE']):?>
                        <h3 class="review-answer-link">Ответ магазина</h3>
                        <div class="review-answer">
                            <p>
                                <?=$arItem['PROPERTIES']['STORE_RESPONSE']['VALUE']?>
                            </p>
                        </div>
                    <?endif;?>
                </div>
                <?endforeach;?>
            </div>
            <?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
                <br /><?=$arResult["NAV_STRING"]?>
            <?endif;?>
            <?/*<button class="button button-secondary button-showmore">показать еще</button> */?>
        </section>
        <?endif;?>
    </div>
</div>

<?php // попап оставить отзыв ?>
<div class="popup popup-send-review">
    <div class="popup__backdrop" data-close-popup="">
    </div>
    <div class="popup-body">
        <?
        $APPLICATION->IncludeComponent(
            "belleyou:main.feedback",
            "feedback",
            array(
                "COMPONENT_TEMPLATE" => "contacts",
                "USE_CAPTCHA" => "Y",
                "SUBJECT_FROM" => array(),
                "EMAIL_SEND" => "N",
                "EMAIL_TO" => "",
                "EVENT_NAME" => ""
            ),
            false
        );
        ?>

    </div>
</div>

<?php // попап сообщение отправлено ?>
<div class="popup popup-review-sent">
    <div class="popup__backdrop" data-close-popup="">
    </div>
    <div class="popup-body">
        <div class="popup-content">
            <div class="popup-content-centered">
                <div class="popup-header">
                    <button class="button-close-popup" data-close-popup=""></button>
                    <h2 class="popup-title">Спасибо!</h2>
                </div>
                <p>
                    Ваш отзыв отправлен на модерацию, служба поддержки проверит и опубликует ваш отзыв.
                </p>
                <p>
                    <button class="button" data-close-popup="">вернуться к списку</button>
                </p>
            </div>
        </div>
    </div>
</div>

