<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$strNavQueryString = ($arResult["NavQueryString"] != "" ? $arResult["NavQueryString"]."&amp;" : "");
$strNavQueryStringFull = ($arResult["NavQueryString"] != "" ? "?".$arResult["NavQueryString"] : "");
echo '<pre>'; var_dump($arResult); echo '</pre>';

if($arResult["NavPageCount"] > 1) {?>
    <div class="profile-pagination">
        <ul class="profile-pagination-list">

            <?$bFirst = true;?>

            <?if ($arResult["NavPageNomer"] > 1):?>
                <?if($arResult["bSavePage"]):?>
                    <a class="profile-pagination-button button-prev" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>"></a>
                <?else:?>
                    <?if ($arResult["NavPageNomer"] > 2):?>
                        <a class="profile-pagination-button button-prev" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>"></a>
                    <?else:?>
                        <a class="profile-pagination-button button-prev" href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>"></a>
                    <?endif;?>
                <?endif;?>
                <?if ($arResult["nStartPage"] > 1):?>
                    <?$bFirst = false;?>
                    <?if($arResult["bSavePage"]):?>
                        <li class="profile-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=1">1</a></li>
                    <?else:?>
                        <li class="profile-pagination-item"><a href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>">1</a></li>
                    <?endif;?>
                    <?if ($arResult["nStartPage"] > 2):?>
                        <li class="profile-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=intval($arResult["nStartPage"]/2)?>">...</a></li>
                    <?endif;?>
                <?endif;?>
            <?endif;?>

            <?do{?>
                <?if ($arResult["nStartPage"] == $arResult["NavPageNomer"]):?>
                    <li class="profile-pagination-item _current"><a ><?=$arResult["nStartPage"]?></a></li>
                <?elseif($arResult["nStartPage"] == 1 && $arResult["bSavePage"] == false):?>
                    <li class="profile-pagination-item"><a href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>"><?=$arResult["nStartPage"]?></a></li>
                <?else:?>
                    <li class="profile-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["nStartPage"]?>"><?=$arResult["nStartPage"]?></a></li>
                <?endif;?>
                <?$arResult["nStartPage"]++;?>
                <?$bFirst = false;?>
            <?} while($arResult["nStartPage"] <= $arResult["nEndPage"]);?>

            <?if($arResult["NavPageNomer"] < $arResult["NavPageCount"]):?>
                <?if ($arResult["nEndPage"] < $arResult["NavPageCount"]):?>
                    <?if ($arResult["nEndPage"] < ($arResult["NavPageCount"] - 1)):?>
                        <li class="profile-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=intval(($arResult["nEndPage"]+$arResult["NavPageCount"])/2)?>">...</a></li>
                    <?endif;?>
                    <li class="profile-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["NavPageCount"]?>"><?=$arResult["NavPageCount"]?></a></li>
                <?endif;?>
                <a class="profile-pagination-button button-next" href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>"></a>
            <?endif;?>
            <?if ($arResult["bShowAll"]):?>
                <?if ($arResult["NavShowAll"]):?>
                    <li class="profile-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>SHOWALL_<?=$arResult["NavNum"]?>=0"><?=GetMessage("nav_paged")?></a></li>
                <?else:?>
                    <li class="profile-pagination-item"><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>SHOWALL_<?=$arResult["NavNum"]?>=1"><?=GetMessage("nav_all")?></a></li>
                <?endif;?>
            <?endif?>
        </ul>
    </div>
<?}?>
