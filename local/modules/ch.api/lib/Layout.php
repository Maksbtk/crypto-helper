<?php
namespace TDauto\Api;

use Bitrix\Main\Loader,
Bitrix\Main\Data\Cache;
\Bitrix\Main\Loader::includeModule("tdauto.price");

class Layout
{
    private $cacheTime = 18000;

    public function __construct()
    {
        Loader::includeModule('iblock');
    }

    public function getHeaderMenu(): array
    {
        $res = [];

        $cache = Cache::createInstance();
        if ($cache->initCache($this->cacheTime, 'Header|Catalog|MainMenu|')) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            global $APPLICATION;
            $menuObj = $APPLICATION->GetMenu("main");
            foreach ($menuObj->arMenu as $menuItem) {

                $secondLvlMenuAr = $APPLICATION->GetMenu(
                    "top.child",
                    true,
                    false,
                    $menuItem[1],
                )->arMenu;

                if ($secondLvlMenuAr) {
                    $innerMenuAr = [];
                    foreach ($secondLvlMenuAr as $menuItemSecond) {
                        $innerMenuAr[] = [
                            'title' => $menuItemSecond[0],
                            'href' => $menuItemSecond[1],
                        ];
                    }

                    $res[] = [
                        'title' => $menuItem[0],
                        'href' => $menuItem[1],
                        'items' => $innerMenuAr
                    ];
                } else {
                    $res[] = [
                        'title' => $menuItem[0],
                        'href' => $menuItem[1],
                    ];
                }

            }

            $cache->endDataCache($res);
        }

        return $res ?? [];
    }


    public function getFooterMenu(): array
    {
        $res = [];

        $cache = Cache::createInstance();
        if ($cache->initCache($this->cacheTime, 'Footer|Menu|')) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            global $APPLICATION;
            $menuObj = $APPLICATION->GetMenu("bottom");
            foreach ($menuObj->arMenu as $menuItem) {

                $secondLvlMenuAr = $APPLICATION->GetMenu(
                    "top.child",
                    true,
                    false,
                    $menuItem[1],
                )->arMenu;

                if ($secondLvlMenuAr) {
                    $innerMenuAr = [];
                    foreach ($secondLvlMenuAr as $menuItemSecond) {
                        $innerMenuAr[] = [
                            'title' => $menuItemSecond[0],
                            'href' => $menuItemSecond[1],
                        ];
                    }

                    $res[] = [
                        'title' => $menuItem[0],
                        'href' => $menuItem[1],
                        'items' => $innerMenuAr
                    ];
                } else {
                    $res[] = [
                        'title' => $menuItem[0],
                        'href' => $menuItem[1],
                    ];
                }

            }

            $cache->endDataCache($res);
        }

        return $res ?? [];
    }

    public function getFooterOffices(): array
    {
        $res = [];
        
        $cache = Cache::createInstance();
        if ($cache->initCache($this->cacheTime, 'Footer|Offices|')) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            $dbOficess = \CIBlockElement::GetList(
                ["SORT"=>"ASC"],
                ["IBLOCK_CODE" => 'footer_offices', "ACTIVE"=>"Y"],
                false,
                false,
                ['ID', 'NAME', 'PROPERTY_ADRESS', 'PROPERTY_PHONE']);


            while ($ofice = $dbOficess->fetch()) {
                $res[] = [
                    "id" => $ofice['ID'],
                    "phone" => $ofice['PROPERTY_PHONE_VALUE'],
                    "address" => $ofice['PROPERTY_ADRESS_VALUE'],
                ];
            }

            $cache->endDataCache($res);
        }

        return $res ?? [];
    }

    public function getFooterAdvantages(): array
    {
        $res = [];

        $cache = Cache::createInstance();
        if ($cache->initCache($this->cacheTime, 'Footer|Advantages|')) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            $dbOficess = \CIBlockElement::GetList(
                ["SORT"=>"ASC"],
                ["IBLOCK_CODE" => 'footer_advantages', "ACTIVE"=>"Y"],
                false,
                false,
                ['NAME', 'PROPERTY_DESCRIPTION', 'PROPERTY_PATH_TO_SVG']);


            while ($ofice = $dbOficess->fetch()) {
                $res[] = [
                    "src" => $ofice['PROPERTY_PATH_TO_SVG_VALUE'],
                    "title" => $ofice['NAME'],
                    "desc" => $ofice['PROPERTY_DESCRIPTION_VALUE'],
                ];
            }

            $cache->endDataCache($res);
        }

        return $res ?? [];
    }

    protected function getStoresByCity($id)
    {
        $res = [];
        $getCityListStore = getCityListStore();
        $res['defaultStore'] = \TDAuto\Price\TUser::getDefaultStore();
        $res['storesByCity'] = $getCityListStore[$id];
        return $res;
    }

    public function getCurCity(): array
    {
        $res = [];
        $curGeoBase = \Geo::getCity();

        if ($curGeoBase)
        {
            $res = [
                'id' => $curGeoBase['ID'],
                'name' => $curGeoBase['NAME'],
                'storesByCity' => $this->getStoresByCity($curGeoBase['ID'])['storesByCity'],
            ];
        }
        //$res = $curGeoBase;
        return $res ?? [];
    }

    public function getSiteCities(): array
    {
        $curGeoBase = \Geo::getCity();
        // getCityList описана в init.php
        $cityList = getCityList();
        foreach ($cityList as $cityAr)
        {
            foreach ($cityAr as $city)
            {
                if ($city['NAME'] && $city['PROPERTY_LOCATION_VALUE'])
                {
                    $res[] = [
                        'id' => strval($city['PROPERTY_LOCATION_VALUE']),
                        'name' => $city['NAME'],
                        'sort' => $city['SORT'],
                        'current'=> strval($curGeoBase['ID']) == strval($city['PROPERTY_LOCATION_VALUE']),
                        //'dev' => $city,
                    ];
                }
            }
        }

        return $res ?? [];
    }

    public function setSiteCity($cityId = '30'): array // 30 - Питер
    {
        $res['message'] = 'fail';
        if (!empty($cityId))
        {
            global $APPLICATION;

            $APPLICATION->set_cookie("DEFAULT_STORE", '', time()-300);
            //$APPLICATION->set_cookie("DEFAULT_STORE", $cityId, time() + 60 * 60 * 24);
            //$APPLICATION->set_cookie("cityAccepted", $cityId, time()+60*60*24*30*12);

            \Geo::setCookieByID($cityId);
            $id = $cityId;

            $getCityListStore = getCityListStore();
            $defaultStore = \TDAuto\Price\TUser::getDefaultStore();
            $storesByCity = $getCityListStore[$id];

            $res['message'] = [];
            if(is_array($defaultStore["SUPPLIER_ID"]) && count($defaultStore["SUPPLIER_ID"])>1){
                //делаем пересчет и выводим товары, которые уменьшатся
                $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
                $basketItems = $basket->getBasketItems();
                $to = getIdStoresByXml($storesByCity);

                foreach ($basketItems as $item){
                    $productID = $item->getProductId();
                    $CCatalogStoreProduct = \CCatalogStoreProduct::GetList(['ID'=>'DESC'],
                        ['PRODUCT_ID'=>$productID,'STORE_ID'=>$to[$storesByCity[0]]],
                        false,
                        false);
                    if($arStore = $CCatalogStoreProduct->Fetch()){
                        if($arStore['AMOUNT']<$item->getQuantity()){
                            $item->setField('QUANTITY', $arStore['AMOUNT']);
                            $res['message'][] = 'cart has changed';
                        }
                    }
                }
                $res['message'][] = 'few default stores';

                $basket->save();
            } else {
                $res['message'][] = '1 default store';
            }

           /* $res['currentCity'] = $this->getCurCity();
            $res['message']['coockie'] = $_COOKIE;*/

        }
        return $res ?? [];
    }

    public function getTegsByUrl($url = '/'): array
    {
        $res = [];

        $cache = Cache::createInstance();
        $cacheId = md5('PAGE|SEO|' . $url);
        if ($cache->initCache($this->cacheTime, $cacheId)) {
            $res = $cache->getVars();
        } elseif($cache->startDataCache()) {

            $dbSeo = \CIBlockElement::GetList(
                ["SORT"=>"ASC"],
                ["IBLOCK_CODE" => 'page_seo', "ACTIVE"=>"Y", 'NAME' => $url],
                false,
                false,
                ['NAME', 'PROPERTY_TITLE', 'PROPERTY_DESCRIPTION', 'PROPERTY_H1']);


            if ($seo = $dbSeo->fetch())
            {
                $res = [
                    "url" => $seo['NAME'],
                    "title" => $seo['PROPERTY_TITLE_VALUE'],
                    "description" => $seo['PROPERTY_DESCRIPTION_VALUE'],
                    "h1" => $seo['PROPERTY_H1_VALUE'],
                ];
            }
            else
            {
                $res['err'] = 'no tags found for ' . $url;
            }

            $cache->endDataCache($res);
        }

        return $res ?? [];
    }
}