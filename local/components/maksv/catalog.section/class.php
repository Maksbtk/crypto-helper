<?
use \Bitrix\Main;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Error;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Iblock;
use \Bitrix\Iblock\Component\ElementList;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CIntranetToolbar $INTRANET_TOOLBAR
 */

Loc::loadMessages(__FILE__);

if (!\Bitrix\Main\Loader::includeModule('iblock'))
{
	ShowError(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
	return;
}

CBitrixComponent::includeComponentClass("bitrix:catalog.section");

class DecCatalogSectionComponent extends CatalogSectionComponent
{
    protected function getSort()
    {
        $sortFields = [];

        if (($this->isIblockCatalog ||
                ($this->isMultiIblockMode()
                    ||
                (!$this->isMultiIblockMode()
                    &&
                $this->offerIblockExist($this->arParams['IBLOCK_ID']))
                )
            )
            && $this->arParams['HIDE_NOT_AVAILABLE'] === 'L'
        )
        {
            $sortFields['CATALOG_AVAILABLE'] = 'desc,nulls';
        }

        if (is_array($this->arParams['ELEMENT_SORT_ARRAY']) && count($this->arParams['ELEMENT_SORT_ARRAY']) > 0) {

            $sortFields = array_merge($sortFields, $this->arParams['ELEMENT_SORT_ARRAY']);

        } else {

            if (!isset($sortFields[$this->arParams['ELEMENT_SORT_FIELD']]))
            {
                $sortFields[$this->arParams['ELEMENT_SORT_FIELD']] = $this->arParams['ELEMENT_SORT_ORDER'];
            }

            if (!isset($sortFields[$this->arParams['ELEMENT_SORT_FIELD2']]))
            {
                $sortFields[$this->arParams['ELEMENT_SORT_FIELD2']] = $this->arParams['ELEMENT_SORT_ORDER2'];
            }
        }

        return $sortFields;
    }
}