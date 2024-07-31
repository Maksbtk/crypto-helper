<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
* @var CBitrixComponentTemplate $this
* @var CatalogElementComponent $component
*/
$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

if(!empty($arResult['PROPERTIES']['CML2_ARTICLE']['VALUE'])){
    $otherColors = Belleyou\ColorAssistant::getСolorListbyArticle($arResult['PROPERTIES']['CML2_ARTICLE']['VALUE']);
    if ($otherColors) {
        $arResult["OTHER_COLORS"] = $otherColors;
    }
}
 
$this->__component->SetResultCacheKeys(array(
    "DETAIL_PAGE_URL",
    "PREVIEW_PICTURE"
));