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

if (empty($arResult["ITEMS"]))
    return;
?>
<?
//не работает Asset
//Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-home-slider.js");
//$this->addExternalJS(SITE_TEMPLATE_PATH . "/js/jquery-home-slider.js");

//Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/mainpage-slider.css?v=1");
//$APPLICATION->SetAdditionalCss(SITE_TEMPLATE_PATH . "/css/mainpage-slider.css?v=1");
?>

<?php /*не работает Asset*/?>
<script defer src="<?=SITE_TEMPLATE_PATH?>/js/jquery-home-slider.js?v=1"></script>
<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/mainpage-slider.css?v=1">


<section class="mainpage-slider" style="overflow: hidden;">
    <div class="home-slider">
        <?foreach($arResult["ITEMS"] as $arItem):?>
            <?
            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
            ?>

            <div class="home-slider-slide home-slider-slide_<?=$arItem["ID"]?>" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
                <div class="home-slider-content">
                    <div class="h3 home-slider-title"><?=htmlspecialchars_decode($arItem['NAME'])?></div>
                    <p class="home-slider-subtitle"><?=$arItem['PROPERTIES']['SUBTITLE']['VALUE']?></p>
                    <?if ($arItem['PROPERTIES']['LINK']['VALUE']):?>
                        <a href="<?=$arItem['PROPERTIES']['LINK']['VALUE']?>" class="home-slider-button"><?if ($arItem['PROPERTIES']['BUTTON_TEXT']['VALUE']):?><?=$arItem['PROPERTIES']['BUTTON_TEXT']['VALUE']?><?else:?>Посмотреть<?endif;?></a>
                    <?endif;?>
                </div>
            </div>

       <?endforeach;?>
    </div>
</section>

<style>
<?foreach($arResult["ITEMS"] as $arItem):?>
    <?php
     $file = CFile::ResizeImageGet($arItem['PREVIEW_PICTURE'], array('width' => 1920, 'height' => 910), BX_RESIZE_IMAGE_EXACT, true);
     $fileMobile = CFile::ResizeImageGet($arItem["DETAIL_PICTURE"], array('width' => 600, 'height' => 900), BX_RESIZE_IMAGE_EXACT, true);
     ?>
    .home-slider-slide_<?=$arItem["ID"]?> {
        background-image: url(<?=$file['src']?>    );
    }
    @media only screen and (max-width: 1023px) {
        .home-slider-slide_<?=$arItem["ID"]?> {
            background-image: url(<?=$fileMobile['src']?>);
        }
    }
<?endforeach;?>
</style>
