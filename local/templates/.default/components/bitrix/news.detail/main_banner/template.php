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

//не работает
//Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/mainpage-promotion-banner.css");
?>
<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/mainpage-promotion-banner.css">

<?php
$file = CFile::ResizeImageGet($arResult['PREVIEW_PICTURE'], array('width' => 1439, 'height' => 696), BX_RESIZE_IMAGE_EXACT, true);
$fileMobile = CFile::ResizeImageGet($arResult["DETAIL_PICTURE"], array('width' => 768, 'height' => 1152), BX_RESIZE_IMAGE_EXACT, true);
?>

<section class="mainpage-promotion-banner">
    <div class="mainpage-promotion-banner-inner">
        <picture>
            <source media="(max-width: 760px)" srcset="<?=$fileMobile['src']?>">
            <img class="mainpage-promotion-banner-img" src="<?=$file['src']?>"  alt="<?=$arResult['NAME']?>">
        </picture>
        <div class="mainpage-promotion-banner-content">
            <div class="h2 mainpage-promotion-banner-name"><?=$arResult['NAME']?></div>
            <p class="mainpage-promotion-banner-desc"><?=$arResult['PREVIEW_TEXT']?></p>
            <a class="mainpage-promotion-banner-button" href="<?=$arResult['PROPERTIES']['BANNER_LINK']['VALUE']?>">к покупкам</a>
        </div>
    </div>
</section>

