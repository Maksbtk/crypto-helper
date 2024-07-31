<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->createFrame()->begin("Загрузка навигации");
?>

<?if($arResult["NavPageCount"] > 1):?>

    <?if ($arResult["NavPageNomer"]+1 <= $arResult["nEndPage"]):?>
        <?
        $plus = $arResult["NavPageNomer"]+1;
        $url = $arResult["sUrlPathParams"] . "PAGEN_".$arResult["NavNum"]."=".$plus;
        ?>
        <div class="js-load_more button button-secondary button-showmore" data-url="<?=$url?>">показать еще</div>
    <?/*else:?>
        <div class="js-load_more">
            Загружено все
        </div>*/?>
    <?endif?>
<?endif?>

