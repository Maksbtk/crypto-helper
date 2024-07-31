<?php
//require_once ($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/belleyou/autoload.php');

/** @global CMain $APPLICATION */
/** @global CUser $USER */
use Bitrix\Main,
    Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;
    //Bitrix\Main\Engine\Contract\Controllerable;

CModule::IncludeModule("iblock");
//CModule::IncludeModule("catalog");

class ProductSlider extends CBitrixComponent// implements Controllerable
{

    public function __construct($component = null)
    {
        parent::__construct($component);
    }

    public function onPrepareComponentParams($arParams)
    {

        if(!($arParams["CACHE_TYPE"]))
            $arParams["CACHE_TYPE"] = 'N';

        if(!($arParams["CACHE_TIME"]))
            $arParams["CACHE_TIME"] = 36000;

        if(!($arParams["PROD_COUNT"]))
            $arParams["PROD_COUNT"] = 10;

        if(!($arParams["BLOCK_TITLE"]))
            $arParams["BLOCK_TITLE"] = 'Товары';

        if(!($arParams["SECTION_URL"]))
            $arParams["SECTION_URL"] = "/catalog/kollektsii/";

        if($arParams['COLLECTION_CODE'])
            $arParams["SECTION_URL"] = "/catalog/kollektsii/".$arParams['COLLECTION_CODE']."/";

        if(!($arParams["SHOW_MORE_PICTURE"]) || !file_exists($_SERVER['DOCUMENT_ROOT'].$arParams["SHOW_MORE_PICTURE"]))
            $arParams["SHOW_MORE_PICTURE"] = SITE_TEMPLATE_PATH."/demo-pics/pi-see-all_mobile.jpg";

        $arParams["IBLOCK_ID"] = '2';//каталог

        if(!($arParams["FILTER"]) && $arParams['COLLECTION_CODE'])
            $arParams["FILTER"] = ["IBLOCK_ID" => "2", "ACTIVE"=>"Y", "SECTION_CODE" => $arParams['COLLECTION_CODE'], /*"!PREVIEW_PICTURE" => false*/];

        if(!($arParams["ELEMENT_SORT"]))
            $arParams["ELEMENT_SORT"] = ["SORT" => "ASC"];


        return $arParams;
    }

    /*public function configureActions(): array
    {
        return [
            '...' => [
                'prefilters' => []
            ],
        ];
    }*/
    /*public function ...Action()
    {
        $res = [];
        return $res;
    }*/

    protected function getResult()
    {
        global $USER;
        $res = [];
        $cacheId = md5(serialize($this->arParams['FILTER']));

        $cache = Cache::createInstance();
        if ($this->arParams["CACHE_TIME"] == 'Y' && $cache->initCache($this->arParams['CACHE_TIME'], 'ProductSlider|'.$USER->GetUserGroupString().'|'.$cacheId)) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            $resultObj = \CIBlockElement::GetList(
                $this->arParams["ELEMENT_SORT"],
                $this->arParams['FILTER'],
                false,
                ['nTopCount' => $this->arParams['PROD_COUNT']],
                ['ID', 'IBLOCK_ID', 'CODE', 'NAME', 'PREVIEW_PICTURE', "PROPERTY_NEW", 'CATALOG_PRICE_1', 'DETAIL_PAGE_URL', 'PROPERTY_CML2_ARTICLE', 'PROPERTY_SAYT_NAIMENOVANIE_DLYA_SAYTA']
            );

            while ($arItem = $resultObj->Fetch()) {

                $arButtons = $this->getPanelButtons($arItem);
                $res['ITEMS'][] = [
                    'ID' => $arItem['ID'],
                    'IBLOCK_ID' => $arItem['IBLOCK_ID'],
                    'EDIT_LINK' => $arButtons["edit"]["edit_element"]["ACTION_URL"],
                    'CODE' => $arItem['CODE'],
                    'NAME_ORIGINAL' => $arItem['NAME'],
                    'ARTICLE' => $arItem['PROPERTY_CML2_ARTICLE_VALUE'],
                    'NAME' => $arItem['PROPERTY_SAYT_NAIMENOVANIE_DLYA_SAYTA_VALUE'],
                    'DETAIL_PAGE_URL' => \CIBlock::ReplaceDetailUrl($arItem['DETAIL_PAGE_URL'], $arItem, true, "E"),
                    'PREVIEW_PICTURE' => \CFile::ResizeImageGet($arItem['PREVIEW_PICTURE'], ['width' => 352, 'height' => 530], BX_RESIZE_IMAGE_EXACT, true)['src'],
                    'PRICE' => number_format($arItem['CATALOG_PRICE_1'], 0, '.', ' ') . ' ₽',
                 ];
                $arProdIds[] = $arItem['ID'];
            }

            $colorAssistOb = new Belleyou\ColorAssistant();
            $res['SORTED_COLOR'] = $colorAssistOb->getСolorList($arProdIds);

            if ($this->arParams['SHOW_BANNER'] == 'Y' && $this->arParams['COLLECTION_CODE']) {
                $res['BANNER'] = $this->getBanner();
            }

            $cache->endDataCache($res);
        }

        $ufFavorites = getUfFavorites() ?? [];
        $this->arResult['UF_FAVORITES'] = $ufFavorites;
        
        $this->arResult['ITEMS'] = $res['ITEMS'];
        $this->arResult['BANNER'] = $res['BANNER'];
        $this->arResult['SORTED_COLOR'] = $res['SORTED_COLOR'];
    }

    protected function getPanelButtons($arItem) {

        $arButtons = CIBlock::GetPanelButtons(
            $arItem["IBLOCK_ID"],
            $arItem["ID"],
            0,
            array("SECTION_BUTTONS"=>false, "SESSID"=>false)
        );

        return $arButtons;
    }

    protected function getBanner() {

        $res = [];
        $resultSection = CIBlockSection::GetList([], ['IBLOCK_ID' => $this->arParams["IBLOCK_ID"], 'CODE' => $this->arParams['COLLECTION_CODE']], false, ['NAME', 'DESCRIPTION', 'PICTURE', 'DETAIL_PICTURE', 'SECTION_PAGE_URL']);
        if ($arSection = $resultSection->Fetch()) {
            $res = [
                'NAME' => $arSection['NAME'],
                'DESCRIPTION_HTML' => $arSection['DESCRIPTION'],
                'PICTURE' => \CFile::ResizeImageGet($arSection['PICTURE'], ['width' => 1439, 'height' => 696], BX_RESIZE_IMAGE_EXACT, true)['src'],
                'MOBILE_PICTURE' => \CFile::ResizeImageGet($arSection['DETAIL_PICTURE'], ['width' => 360, 'height' => 541], BX_RESIZE_IMAGE_EXACT, true)['src'],
                #'SECTION_PAGE_URL' => $arSection['SECTION_PAGE_URL']  Переделать на нормальный вывод цепочки разделов
            ];
        }

        return $res;
    }

    public function executeComponent()
    {
        $this->getResult();
        $this->includeComponentTemplate();
    }
}
