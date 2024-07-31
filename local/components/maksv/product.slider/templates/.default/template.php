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

////////////не работает Asset
//Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/css/section-product-slider.css?v=1");
?>

<?php /*не работает Asset*/?>
<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/section-product-slider.css?v=1">

<?php //echo '<pre>'; var_dump($arResult); echo '</pre>'; ?>
<section class="mainpage-section-product section-product-slider">
    <h2>Товары</h2>

    <div class="product-list-wrapper">
        <ul class="products-list products-list__one-line">

            <?foreach ($arResult["ITEMS"] as $item):?>
                <?
                $this->AddEditAction($item['ID'], $item['EDIT_LINK'], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
                $this->AddDeleteAction($item['ID'], $item['DELETE_LINK'], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                ?>
                <li class="product-item" id="<?=$this->GetEditAreaId($item['ID']);?>">
                <div class="product-media-wrapper">
                    <a class="product-link" href="<?=$item['DETAIL_PAGE_URL']?>">
                        <div class="product-picture-wrapper">
                            <img class="product-picture" src="<?=$item['PREVIEW_PICTURE']?>" alt="<?=$item['NAME']?>">
                        </div>
                    </a>

                    <?/*<a class="button-add-to-favorite">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16"><path stroke="#A0BCD2" stroke-linecap="round" stroke-linejoin="round" d="M15.023.53c-2.047-.23-4.25.912-5.024 2.618C9.225 1.442 7 .301 4.976.53 2.28.823.553 3.57 1.099 6.22c.744 3.604 4.801 5.214 8.9 9.281 4.081-4.067 8.156-5.683 8.9-9.282.547-2.65-1.182-5.395-3.876-5.689z"/></svg>
                    </a>*/?>

                    <?/*<div class="product-labels">
                        <span class="product-label">New</span>
                        <span class="product-label">–50% на 2–е боди</span>
                        <span class="product-label">Gift idea</span>
                        <span class="product-label">Только online</span>
                    </div>*/?>
                </div>
                <div class="product-info-wrapper">
                    <a class="product-link" href="<?=$item['DETAIL_PAGE_URL']?>"><h4 class="product-name"><?=$item['NAME']?></h4></a>
                    <div class="product-pricebox">
                        <?/*<span class="proudct-old-price"><?=$item['PRICE']?></span>*/?>
                        <span class="proudct-current-price"><?=$item['PRICE']?></span>
                    </div>

                    <?/*<div class="product-colors-sheme">
                        <ul class="product-colors-list">
                            <li class="product-color product-color__with-border">
                                <a style="background-image: url(https://belleyou.ru/upload/iblock/79e/79e96c531741ee2c701afdd9957d19d4.jpeg);"></a>
                            </li>
                            <li class="product-color">
                                <a style="background-image: url(https://belleyou.ru/upload/iblock/1f0/1f0aeea049dfac0a3d9d8f464e4381ab.jpeg);"></a>
                            </li>
                            <li class="product-color">
                                <a style="background-color: #000000;"></a>
                            </li>
                        </ul>
                        <span class="product-more-colors-label">+2 цвета</span>
                    </div>*/?>
                </div>
            </li>
            <?endforeach;?>

            <?/*/DEL?>
            <?for ($i = 1; $i <= 3; $i++):?>
                <li class="product-item">
                    <div class="product-media-wrapper">
                        <a class="product-link" href="product.html">
                            <div class="product-picture-wrapper">
                                <img class="product-picture" src="<?=SITE_TEMPLATE_PATH?>/demo-pics/product5.jpg" alt="Топ с лазерным кроем с чашками-вкладышами Invisible">
                            </div>
                        </a>
                        <a class="button-add-to-favorite"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16"><path stroke="#A0BCD2" stroke-linecap="round" stroke-linejoin="round" d="M15.023.53c-2.047-.23-4.25.912-5.024 2.618C9.225 1.442 7 .301 4.976.53 2.28.823.553 3.57 1.099 6.22c.744 3.604 4.801 5.214 8.9 9.281 4.081-4.067 8.156-5.683 8.9-9.282.547-2.65-1.182-5.395-3.876-5.689z"/></svg></a>
                        <div class="product-labels">
                            <span class="product-label">–999%</span>
                        </div>
                    </div>
                    <div class="product-info-wrapper">
                        <a class="product-link" href="product.html"><h4 class="product-name">TEST PRODUCT</h4></a>
                        <div class="product-pricebox">
                            <span class="proudct-old-price" style="display: none;">9 999 ₽</span>
                            <span class="proudct-current-price">2 189 ₽</span>
                        </div>
                        <div class="product-colors-sheme">
                            <ul class="product-colors-list">
                                <li class="product-color product-color__with-border">
                                    <a style="background-image: url(https://belleyou.ru/upload/iblock/79e/79e96c531741ee2c701afdd9957d19d4.jpeg);"></a>
                                </li>
                                <li class="product-color">
                                    <a style="background-image: url(https://belleyou.ru/upload/iblock/1f0/1f0aeea049dfac0a3d9d8f464e4381ab.jpeg);"></a>
                                </li>
                                <li class="product-color">
                                    <a style="background-color: #000000;"></a>
                                </li>
                            </ul>
                            <span class="product-more-colors-label">+99 цвета</span>
                        </div>
                    </div>
                </li>
            <?endfor;?>
            <?//DEL*/?>

            <li class="product-item product-item-last">
                <a href="<?=$arParams["NEW_SECTION_URL"]?>" class="product-item-see-all" style="background-image: url(<?=SITE_TEMPLATE_PATH?>/demo-pics/pi-see-all_mobile.jpg);">
                    <span>Смотреть все</span>
                </a>
            </li>
        </ul>
        <button class="button-product-list-button"></button>
    </div>
</section>

