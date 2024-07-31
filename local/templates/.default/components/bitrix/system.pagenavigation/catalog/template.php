<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

$this->setFrameMode(true);

if(!$arResult["NavShowAlways"])
{
	if ($arResult["NavRecordCount"] == 0 || ($arResult["NavPageCount"] == 1 && $arResult["NavShowAll"] == false))
		return;
}

$strNavQueryString = ($arResult["NavQueryString"] != "" ? $arResult["NavQueryString"]."&amp;" : "");
$strNavQueryStringFull = ($arResult["NavQueryString"] != "" ? "?".$arResult["NavQueryString"] : "");
?>

<?if ($arResult["NavPageNomer"] > 1):?>
    <?if($arResult["bSavePage"]):?>
        <button class="catalog-pagination__button button-prev" onclick='location.href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>"'></button>
        <ul class="catalog-pagination-list">
            <li class="catalog-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=1">1</a></li>
    <?else:?>
        <?if ($arResult["NavPageNomer"] > 2):?>
            <button class="catalog-pagination__button button-prev" onclick='location.href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>"'></button>        
        <?else:?>
            <button class="catalog-pagination__button button-prev" onclick='location.href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>"'></button>
        <?endif?>
        <ul class="catalog-pagination-list">
            <li class="catalog-pagination-item"><a href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>">1</a></li>
    <?endif?>
<?else:?>
        <button class="catalog-pagination__button button-prev button-disabled"></button>
        <ul class="catalog-pagination-list">
            <li class="catalog-pagination-item _current"><a href="javascript:void(0)">1</a></li>
<?endif?>

<?if ($arResult["nStartPage"] > 1):?>
    <li class="catalog-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=round($arResult["nEndPage"] -$arResult["nStartPage"])?>">...</a></li>
<?endif;?>

<?
$arResult["nStartPage"]++;
while($arResult["nStartPage"] <= $arResult["nEndPage"]-1):
?>
    <?if ($arResult["nStartPage"] == $arResult["NavPageNomer"]):?>
        <li class="catalog-pagination-item _current"><a href="javascript:void(0)"><?=$arResult["nStartPage"]?></a></li>
    <?else:?>
        <li class="catalog-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["nStartPage"]?>"><?=$arResult["nStartPage"]?></a></li>
    <?endif?>
    <?$arResult["nStartPage"]++?>
<?endwhile?>

<?if ($arResult["nEndPage"] < ($arResult["NavPageCount"] - 1)):?>
    <li class="catalog-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=round($arResult["nEndPage"] + ($arResult["NavPageCount"] - $arResult["nEndPage"]) / 2)?>">...</a></li>
<?endif;?>

<?if($arResult["NavPageNomer"] < $arResult["NavPageCount"]):?>
    <?if($arResult["NavPageCount"] > 1):?>
        <li class="catalog-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["NavPageCount"]?>"><?=$arResult["NavPageCount"]?></a></li>
    <?endif?>
    </ul>
    <button class="catalog-pagination__button button-next" onclick='location.href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>"'></button>
<?else:?>
    <?if($arResult["NavPageCount"] > 1):?>
        <li class="catalog-pagination-item _current"><a href="javascript:void(0)"><?=$arResult["NavPageCount"]?></a></li>
    <?endif?>
        </ul>
        <button class="catalog-pagination__button button-next button-disabled"></button>
<?endif?>