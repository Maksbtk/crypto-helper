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

/*\Bitrix\Main\Page\Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/mainpage-section-shops.css?v=4",true);
\Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-shop-slider.js?v=1");*/
?>
<?/*<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/mainpage-section-shops.css">
<script defer src="<?=SITE_TEMPLATE_PATH?>/js/jquery-shop-slider.js"></script>*/?>

<section class="mainpage-section-shops">
    <div class="h2"><?=$arParams['PAGER_TITLE']?></div>
    <a class="section-shops__find-shop-link" href="/for-customers/shops/">Найти адрес</a>
    <div class="our-shops-list our-shops-slider">
        <?foreach($arResult["ITEMS"] as $arItem):?>
            <div class="shop-frame"><img src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>" alt="<?=htmlspecialchars_decode($arItem['NAME'])?>"></div>
        <?endforeach;?>
    </div>
</section>