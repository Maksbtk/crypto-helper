<?php
/** @global CMain $APPLICATION */
/** @global CUser $USER */
use Bitrix\Main,
    Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;
    //Bitrix\Main\Engine\Contract\Controllerable;

CModule::IncludeModule("iblock");

class SectionSlider extends CBitrixComponent// implements Controllerable
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

        /*if(!($arParams["SECTION_COUNT"]))
            $arParams["SECTION_COUNT"] = 3;*/

        if(!($arParams["SECTIONS_CODES"]))
            $arParams["SECTIONS_CODES"] = 'odezhda,topy,trusy';

        if(!($arParams["BLOCK_TITLE"]))
            $arParams["BLOCK_TITLE"] = 'Разделы';

        $arParams["IBLOCK_ID"] = '2';

        if(!($arParams["FILTER"]) && $arParams["SECTIONS_CODES"])
            $arParams["FILTER"] = ["IBLOCK_ID" => "2", "ACTIVE"=>"Y", 'CODE' => explode(',', $arParams["SECTIONS_CODES"])];

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
        if ($this->arParams["CACHE_TIME"] == 'Y'  && $cache->initCache($this->arParams['CACHE_TIME'], 'SectionList|'.$USER->GetUserGroupString().'|'.$cacheId)) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            $sectionsOb = \CIBlockSection::GetList([/*'code'=>'asc'*/], $this->arParams['FILTER'], true);
            while($sectionItem = $sectionsOb -> GetNext()) {
                $res['ITEMS'][] = [
                    'NAME' => $sectionItem['NAME'],
                    'SECTION_PAGE_URL' => $sectionItem['SECTION_PAGE_URL'], 
                    'PICTURE' => \CFile::ResizeImageGet($sectionItem['PICTURE'], ['width' => 473, 'height' => 710], BX_RESIZE_IMAGE_EXACT, true)['src'],
                ];
            }

            $cache->endDataCache($res);
        }

        $this->arResult['ITEMS'] = array_reverse($res['ITEMS']);
    }

    public function executeComponent()
    {
        $this->getResult();
        $this->includeComponentTemplate();
    }
}
