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

if (empty($arResult["ITEMS"]))
    return;

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/section-product-slider.css?v=1");
?>
<?php /*
<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/mainpage-product-banners.css">*/?>

<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/mainpage-product-banners.css">
<section class="mainpage-product-banners">
    <ul class="product-banners-list">
        <?foreach ($arResult["ITEMS"] as $item):?>

        <li class="product-banner-item">
            <div class="product-banner-item__inner">
                <img class="product-banner-img" src="<?=$item['PICTURE']?>" alt="<?=$item['NAME']?>">
                <div class="product-banner-content">
                    <h2 class="product-banner-name"><?=$item['NAME']?></h2>
                    <a href="<?=$item['SECTION_PAGE_URL']?>" class="product-banner-link">Выбрать</a>
                </div>
            </div>
        </li>
        <?endforeach;?>
    </ul>
</section>