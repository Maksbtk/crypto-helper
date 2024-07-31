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
//Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/jquery-stories.js?v=1");
//Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/belleyou-stories.css?v=1");
?>
<?/*<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/belleyou-stories.css?v=1">
<script defer src="<?=SITE_TEMPLATE_PATH?>/js/jquery-stories.js?v=1"></script>*/?>

<section class="belleyou-stories">
    <div class="widget-stories">
        <ul class="widget-stories__list">
            <?foreach($arResult["ITEMS"] as $arItem):?>
                <?
                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                ?>
                <li class="widget-stories__story" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
                    <a href="#" class="widget-stories__story-link" data-id="story-<?=$arItem['ID']?>" <?/*data-popup="widget-stories__fullstory"*/?>>
                        <figure class="widget-stories__story-preview" style="background-image: url(<?=CFile::ResizeImageGet($arItem['PREVIEW_PICTURE'], array('width' => 105, 'height' => 155), BX_RESIZE_IMAGE_EXACT, true)['src']?>)">
                        </figure>
                        <span class="widget-stories__story-text"><?=htmlspecialchars_decode($arItem['NAME'])?></span>
                    </a>
                </li>
            <?endforeach;?>
        </ul>
    </div>
</section>



