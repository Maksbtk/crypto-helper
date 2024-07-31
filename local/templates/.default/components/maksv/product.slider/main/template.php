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

$userIsAuth = $USER->IsAuthorized()
?>

<section class="mainpage-section-product section-product-slider">
    <div class="h2"><?=$arParams['BLOCK_TITLE']?></div>

    <div class="product-list-wrapper">
        <div class="products-list products-list-slider">

            <?foreach ($arResult["ITEMS"] as $item):?>
                <?
                $this->AddEditAction($item['ID'], $item['EDIT_LINK'], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
                $this->AddDeleteAction($item['ID'], $item['DELETE_LINK'], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                ?>
                <div class="product-item" id="<?=$this->GetEditAreaId($item['ID']);?>">
                <div class="product-media-wrapper">
                    <a class="product-link" title="<?=$item['NAME']?>" href="<?=$arParams["SECTION_URL"].$item['CODE'].'/'?>">
                        <div class="product-picture-wrapper">
                            <img class="product-picture" src="<?=$item['PREVIEW_PICTURE']?>" alt="<?=$item['NAME']?>">
                        </div>
                    </a>

                    <a class="button-add-to-favorite <?if($userIsAuth):?>  js-check-wishlist-button<?endif;?><?if (in_array($item['ID'], $arResult['UF_FAVORITES'])):?> _added<?endif;?>"
                       data-id="<?=$item['ID']?>"
                        <?if(!$userIsAuth):?> data-popup="popup-go-to-auth"<?endif;?>
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16"><path stroke="#A0BCD2" stroke-linecap="round" stroke-linejoin="round" d="M15.023.53c-2.047-.23-4.25.912-5.024 2.618C9.225 1.442 7 .301 4.976.53 2.28.823.553 3.57 1.099 6.22c.744 3.604 4.801 5.214 8.9 9.281 4.081-4.067 8.156-5.683 8.9-9.282.547-2.65-1.182-5.395-3.876-5.689z"/></svg>
                    </a>

                    <?/*<div class="product-labels">
                        <span class="product-label">New</span>
                        <span class="product-label">–50% на 2–е боди</span>
                        <span class="product-label">Gift idea</span>
                        <span class="product-label">Только online</span>
                    </div>*/?>
                </div>
                <div class="product-info-wrapper">
                    <a class="product-link" href="<?=$arParams["SECTION_URL"].$item['CODE'].'/'?>" title="<?=$item['NAME']?>"><div class="h4 product-name"><?=$item['NAME']?></div></a>
                    <div class="product-pricebox">
                        <?/*<span class="proudct-old-price"><?=$item['PRICE']?></span>*/?>
                        <span class="proudct-current-price"><?=$item['PRICE']?></span>
                    </div>

                    <div class="product-colors-sheme">
                        <ul class="product-colors-list">
                            <?$colorCnt = 1;
                            $colors = $arResult['SORTED_COLOR'][$item['ARTICLE']];
                            foreach($colors as $colorCode => $color){
                                if($colorCnt == 4){break;}?>
                                <li class="product-color product-color__with-border">
                                    <a href="<?=$color['DETAIL_PAGE_URL']?>" target="_blank" style="background: url('/upload/colors/<?=$color['COLOR_CODE']?>.jpg') no-repeat;" title="<?=$color['NAME']?>"></a>
                                </li>
                                <?$colorCnt++;
                            }?>
                        </ul>
                        <?if(count($colors) > 3){
                            $totalColorCnt = count($colors)-3;?>
                            <span class="product-more-colors-label">+<?=num_word($totalColorCnt, array('цвет', 'цвета', 'цветов'))?></span>
                        <?}?>
                    </div>
                </div>
            </div>
            <?endforeach;?>

            <div class="product-item product-item-last">
                <a href="<?=$arParams["SECTION_URL"]?>" class="product-item-see-all" style="background-image: url(<?=$arParams['SHOW_MORE_PICTURE']?>);">
                    <span>Смотреть все</span>
                </a>
            </div>
        </div>
    </div>
</section>

