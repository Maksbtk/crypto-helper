<?
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
?>
<noindex>
    <div class="belleyou-header-notification">
        <a class="belleyou-header-notification__link notification__link_pc" href="<?=$arResult['PROPERTIES']['LINK']['VALUE']?>" target="_blank"><?=$arResult['NAME']?></a>
        <a class="belleyou-header-notification__link notification__link_mob" href="<?=$arResult['PROPERTIES']['LINK']['VALUE']?>" target="_blank"><?=$arResult['PROPERTIES']['MOBILE_TEXT']['VALUE']?></a>
    </div>
</noindex>
