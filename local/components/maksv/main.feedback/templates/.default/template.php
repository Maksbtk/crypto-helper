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


////////////не работает Asset
//Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/section-product-slider.css?v=1");
?>

<?php /*не работает Asset
<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/section-product-slider.css?v=1">*/?>



