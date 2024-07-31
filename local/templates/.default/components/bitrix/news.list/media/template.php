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

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/page-press.css");
?>

<?if ($arResult["ITEMS"] ):?>
<div class="belleyou-content-layout">
    <div class="page-press-wrapper">
        <aside class="page-press-sidebar">
            <div class="belleyou-content__header">
                <h1>СМИ о нас</h1>
            </div>
            <ul class="page-press-archive">
                <?foreach($arParams["YEARS"] as $arItem):?>
                    <li class="page-press-archive-item <?if ($arItem == $arParams['CURRENT_YEAR']):?>current<?endif;?>">
                        <a href="/about/media/?year=<?=$arItem?>"><?=$arItem?></a>
                    </li>
                <?endforeach;?>
            </ul>
        </aside>
        <section class="page-press-section">
            <ul class="press-list js-load-more-list">
                <?if($arParams["DISPLAY_TOP_PAGER"]):?>
                    <?=$arResult["NAV_STRING"]?><br />
                <?endif;?>
                <?foreach($arResult["ITEMS"] as $arItem):?>
                <?
                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                ?>
                    <li class="press-item js-load-more-item" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
                        <a href="<?=$arItem['PROPERTIES']['NEWS_LINK']['VALUE']?>" class="press-item-link" rel="nofollow" target="_blank">
                            <img width="152" alt="<?=$arItem['NAME']?>" src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" srcset="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?> 1x, <?=$arItem["PREVIEW_PICTURE"]["SRC"]?>">
                            <div class="press-item-body">
                                <?
                                //$dateOb = new DateTime($arItem['PROPERTIES']['NEWS_DATE']['VALUE']);
                                //$date = $date->format('Y-m-d H:i:s');
                                ?>
                                <span class="press-item-date"><?=$arItem['PROPERTIES']['NEWS_DATE']['VALUE']?></span>
                                <h3><?=$arItem['NAME']?></h3>
                            </div>
                            <span class="press-item-pseudolink">Читать</span>
                        </a>
                    </li>
                <?endforeach;?>
                <?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
                    <br /><?=$arResult["NAV_STRING"]?>
                <?endif;?>
            </ul>
            <?/*<button class="button button-secondary">показать еще<br>
                <br>
            </button>*/?>
        </section>
    </div>
</div>
<?endif;?>

