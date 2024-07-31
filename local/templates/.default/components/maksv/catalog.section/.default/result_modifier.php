<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var CBitrixComponentTemplate $this
 * @var CatalogSectionComponent $component
 */

$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

/*$itemsCount = count($arResult['ITEMS']);
$cp = $this->__component;
if (is_object($cp))
{
    $cp->arResult['ITEMS_COUNT'] = $itemsCount;
    $cp->SetResultCacheKeys(['ITEMS_COUNT']);
}*/