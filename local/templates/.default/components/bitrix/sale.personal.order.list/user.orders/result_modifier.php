<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
//use Bitrix\Main\Type\DateTime;

/** @var array $arParams */
/** @var array $arResult */

/*$yesterdayTimestamp = (new DateTime())->add("-1 day")->getTimestamp();
$lastWeekTimestamp = (new DateTime())->add("-7 days")->getTimestamp();*/

$ordersBasketPictures = [];
$defPicture = SITE_TEMPLATE_PATH.'/img/img-no-photo.svg';
foreach ($arResult['ORDERS'] as $key => &$order) {

    //в случае если заказ выполнен проверяем сколько ему дней с момента выполнения, если больше 7 то не выводи кнопку оформить возврат
    /*if ($order['ORDER']['STATUS_ID'] === 'F' && (($lastWeekTimestamp - $order['ORDER']['DATE_STATUS']->getTimestamp()) > (7 * 86400))) {
        $order['ORDER']['SUCCESS_MORE_7DAYS'] = 'Y';
    } else {
        $order['ORDER']['SUCCESS_MORE_7DAYS'] = 'N';
    }*/
    $orderId = $order['ORDER']['ID'];
    if(!empty($order['BASKET_ITEMS'])){
        foreach ($order['BASKET_ITEMS'] as &$basketItem) {
            $ordersBasketPictures[$orderId][$basketItem['PRODUCT_ID']] = $defPicture;
        }
        unset($basketItem);
    }

    if ($ordersBasketPictures[$orderId]) {
        $ordersBasketPictures[$orderId] = fillBasketPictures($ordersBasketPictures[$orderId], $orderId);
    }

}
unset($order);
$arResult['BASKET_PICTURES_FOR_ORDERS'] = $ordersBasketPictures ?? [];


function fillBasketPictures ($picturesAr, $orderId) {

    $res = [];
    $defPicture = SITE_TEMPLATE_PATH.'/img/img-no-photo.svg';
    $cache = Cache::createInstance();
    if ($cache->initCache(2592000, 'ClientOrderPicturesForBasket_'.$orderId)) {// 30 дней
        $res = $cache->getVars();
    } elseif ($cache->startDataCache()) {

        foreach ($picturesAr as $skuID => $picturePath) {
            $skuResult = CCatalogSku::GetProductInfo($skuID);

            $res[$skuID] = $defPicture;
            if ($skuResult) {
                $prodOb = CIBlockElement::GetList(
                    ['sort' => 'asc'],
                    ['IBLOCK_ID' => '2', 'ID' => $skuResult['ID']], //2 - id каталога
                    false,
                    false,
                    ['PREVIEW_PICTURE']
                );
                if ($prodEl = $prodOb->fetch()) {
                    //$picture = CFile::GetPath($prodEl["PREVIEW_PICTURE"]);
                    $picture = CFile::ResizeImageGet($prodEl["PREVIEW_PICTURE"], array('width'=>150, 'height'=>225), BX_RESIZE_IMAGE_EXACT, true)['src'];
                    $picture = str_replace('http:', 'https:', $picture);
                    if ($picture) {
                        $res[$skuID] = $picture;
                    }
                }
            }

        }

        if (!$res) {
            $res = $picturesAr;
        }

        $cache->endDataCache($res);
    }

    return $res;
}

