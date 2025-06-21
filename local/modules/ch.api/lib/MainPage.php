<?php
namespace TDauto\Api;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;

class MainPage
{
    private $cacheTime = 36000;

    public function __construct()
    {
        Loader::includeModule('iblock');
    }

    public function getBottomLinks(): array
    {
        $res = [];

        $cache = Cache::createInstance();
        if ($cache->initCache($this->cacheTime, 'MainPage|Bottom|Links')) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            $dbLinks = \CIBlockElement::GetList(
                ["SORT"=>"ASC"],
                ["IBLOCK_CODE" => 'main_page_bottom_links', "ACTIVE"=>"Y"],
                false,
                false,
                ['NAME', "ID", 'PROPERTY_DESCRIPTION', 'PROPERTY_LINK']
            );

            while ($link = $dbLinks->fetch()) {
                $res[] = [
                    "id" => $link['ID'],
                    "href" => $link['PROPERTY_LINK_VALUE'],
                    "title" => $link['NAME'],
                    "desc" => $link['PROPERTY_DESCRIPTION_VALUE'],
                ];
            }

            $cache->endDataCache($res);
        }

        return $res ?? [];
    }

    public function getCatalogSectionLinks(): array
    {
        $res = [];

        $cache = Cache::createInstance();
        if ($cache->initCache($this->cacheTime, 'MainPage|Catalog|Section|Links')) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            global $APPLICATION;
            $menuObj = $APPLICATION->GetMenu("left");
            foreach ($menuObj->arMenu as $menuKey => $menuItem) {

                $res[] = [
                    'id' => $menuKey,
                    'title' => $menuItem[0],
                    'icon' => $menuItem[3]['src'],
                    'href' => $menuItem[1],
                ];

            }

            $cache->endDataCache($res);
        }

        return $res ?? [];
    }

    public function getMainSlides(): array
    {
        $res = [];

        $cache = Cache::createInstance();
        if ($cache->initCache($this->cacheTime, 'MainPage|Main|Slides|')) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            $dbSlides = \CIBlockElement::GetList(
                ["SORT"=>"ASC"],
                ["IBLOCK_CODE" => 'main_page_slides', "ACTIVE"=>"Y"],
                false,
                false,
                ['NAME', 'SORT', "ID", "PREVIEW_PICTURE", "PREVIEW_TEXT", 'PROPERTY_SUBTITLE', 'PROPERTY_URL']
            );

            while ($slide = $dbSlides->fetch()) {
                $res[] = [
                    "id" => $slide['ID'],
                    'sort' => $slide['SORT'],
                    'url' => $slide['PROPERTY_URL_VALUE'],
                    "src" => \CFile::GetPath($slide['PREVIEW_PICTURE']),
                    "title" => $slide['NAME'],
                    "subtitle" => $slide['PROPERTY_SUBTITLE_VALUE'] ?? null,
                    "desc" => $slide['PREVIEW_TEXT'] ?? null,
                ];
            }

            $cache->endDataCache($res);
        }

        return $res ?? [];
    }

    public function getCards(): array
    {
        $res = [];

        $cache = Cache::createInstance();
        if ($cache->initCache($this->cacheTime, 'MainPage|Cards|')) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            $dbSlides = \CIBlockElement::GetList(
                ["SORT"=>"ASC"],
                ["IBLOCK_CODE" => 'main_page_cards', "ACTIVE"=>"Y"],
                false,
                false,
                ['NAME', "ID", "PREVIEW_PICTURE", "PROPERTY_LINK", 'PROPERTY_LINK_TEXT']
            );

            while ($slide = $dbSlides->fetch()) {
                $res[] = [
                    "id" => $slide['ID'],
                    "src" => \CFile::GetPath($slide['PREVIEW_PICTURE']),
                    "title" => $slide['NAME'],
                    "href" => $slide['PROPERTY_LINK_VALUE'],
                    "linkText" => $slide['PROPERTY_LINK_TEXT_VALUE'],
                ];
            }

            $cache->endDataCache($res);
        }

        return $res ?? [];
    }

    public function getNews($allNews = 'n'): array
    {
        $res = [];
        $resultData = [];


        $arFilter = ["IBLOCK_CODE" => 'news', "ACTIVE"=>"Y" , ">DATE_CREATE" => '01.01.2022'];
        if ($allNews == 'y')
        {
            unset($arFilter[">DATE_CREATE"]);
        }

        $cacheId = md5(implode($arFilter));
        $cache = Cache::createInstance();
        if ($cache->initCache($this->cacheTime, 'MainPage|News|'.$cacheId))
        {
            $res = $cache->getVars();
        }
        elseif($cache->startDataCache())
        {

            $dbNews = \CIBlockElement::GetList(
                ["created_date"=>"desc"],
                $arFilter,
                false,
                [],//['nTopCount' => 5],
                ['NAME', "ID", "CODE", "IBLOCK_SECTION_ID", "PREVIEW_TEXT", "DATE_CREATE", 'PROPERTY_CML2_STATUS', 'DETAIL_TEXT', 'DETAIL_PICTURE']
            );

            $resultData['categories'] = ["id" => 0, 'title' => 'Все', 'items' => [],];
            while ($news = $dbNews->fetch()) {

                $previewDesc = $this->formatText($news['PREVIEW_TEXT']);
                $detailDesc = $this->formatText($news['DETAIL_TEXT']);

                $maxStrLength = 96;
                $shortPreviewDesc = $previewDesc;
                if (strlen($previewDesc) >= $maxStrLength) {
                    $shortPreviewDesc = mb_substr($previewDesc, 0, $maxStrLength).'...';
                }

                $resultData['categories']['items'][] = [
                    "id" => intval($news['ID']),
                    "code" => $news['CODE'],
                    "datetime" => $news['DATE_CREATE'],
                    "title" => $news['NAME'],
                    "shortDesc" => $shortPreviewDesc,
                    "previewDesc" => $previewDesc   ,
                    "detailDesc" => $detailDesc,
                    "detailPict" => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . \CFile::GetPath($news['DETAIL_PICTURE']),
                    "category" => $news['PROPERTY_CML2_STATUS_VALUE'] ,
                ];

            }

            $categories = $resultData['categories']['items'];

            $groupedCategories = [];
            $groupedCategories[0] = $resultData['categories'];

            $idCount = 1;
            foreach ($categories as $category) {
                $categoryId = $idCount;
                $categoryName = $category['category'];
                if (!isset($groupedCategories[$categoryName])) {
                    $groupedCategories[$categoryName] = [
                        'id' => $categoryId,
                        'title' => $categoryName,
                        'items' => []
                    ];
                    $idCount++;
                }
                $groupedCategories[$categoryName]['items'][] = $category;
            }

            $res = [
                "categories" => array_values($groupedCategories)
            ];

            $cache->endDataCache($res);
        }

        return $res ?? [];
    }
    
    public function formatText($str) 
    {
        $newStr = '';
        $newStr = strip_tags($str);
        $newStr = str_replace(["\r\n", "\r", "\n", "\t"], '', $newStr);
        $newStr = trim($newStr);

        return $newStr;
    }

}