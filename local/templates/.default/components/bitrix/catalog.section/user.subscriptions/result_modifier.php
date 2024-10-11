<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var CBitrixComponentTemplate $this
 * @var CatalogSectionComponent $component
 */
$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

$arResult['ITEMS'] = array_map(
    function($item) use($arParams) {
        array_walk(
            $item['OFFERS'],
            function(&$offer) use($arParams) {
                $offer['OFFER_PROPERTIES'] = [];
                array_walk(
                    $arParams['OFFERS_PROPERTY_CODE'],
                    function($code) use(&$offer) {
                        $offer['OFFER_PROPERTIES'][$code] = CIBlockFormatProperties::GetDisplayValue($offer, $offer['PROPERTIES'][$code], '');
                    }
                );
            }
        );

        return $item;
    },
    $arResult['ITEMS']
);
