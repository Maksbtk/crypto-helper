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

if (empty($arResult["ITEMS"]))
    return;
?>

<?foreach ($arResult['ITEMS'] as $item):?>
<?$image = CFile::ResizeImageGet($item['PREVIEW_PICTURE']['ID'], ["width" => 40, "height" => 40], BX_RESIZE_IMAGE_EXACT, true);?>
<div class="widget-stories__fullstory" id="story-<?=$item['ID']?>">
    <div class="fullstory__wrapper">
        <header class="fullstory-header">
            <div class="fullstory-header__info">
                <span class="fullstory-info__thumb" style="background-image: url(<?=$image['src']?>)"></span>
                <div class="h5 fullstory-info__title"><?= $item['NAME'] ?></div>
            </div>
            <button class="fullstory__button-close">x</button>
        </header>

        <div class="fullstory-slider">
            <?foreach ($item['PROPERTIES']['PHOTOS']['VALUE'] as $key => $photoId): ?>
                <div class="fullstory-slider__slide">
                    <picture style="background-image: url(<?= CFile::ResizeImageGet(CFile::GetFileArray($photoId), ['width' => 375, 'height' => 753], BX_RESIZE_IMAGE_EXACT)['src'] ?>);">

                    </picture>
                    <div class="fullstory-content__wrapper">
                       <?/* <div class="h3 fullstory-content-title"><?= $item['PROPERTIES']['PHOTOS']['DESCRIPTION'][$key] ?></div>*/?>
                        <?php if (/*$index !== false &&*/ $item['PROPERTIES']['SLIDER_TEXT']['~VALUE'][$key]['TYPE'] === 'HTML'): ?>
                            <?= $item['PROPERTIES']['SLIDER_TEXT']['~VALUE'][$key]['TEXT'] ?>
                        <?php endif?>
                    </div>
                </div>

            <?endforeach;?>

        </div>
    </div>
</div>
<?endforeach;?>
