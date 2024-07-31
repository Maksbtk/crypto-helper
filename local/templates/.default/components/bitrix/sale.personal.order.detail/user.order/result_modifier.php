<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Sale;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

/** @var array $arParams */
/** @var array $arResult */

//    $basketItem["IMAGE"]['src'] = SITE_TEMPLATE_PATH.'/img/no_photo.png';

//в случае если заказ выполнен проверяем сколько ему дней с момента выполнения, если больше 7 (7 * 86400), то не выводи кнопку оформить возврат
/*$lastWeekTimfestamp = (new DateTime())->add("-7 days")->getTimestamp();

if ($arResult['STATUS_ID'] === 'F' && (($lastWeekTimestamp - $arResult['DATE_STATUS']->getTimestamp()) > (7 * 86400))) {
    $arResult['SUCCESS_MORE_7DAYS'] = 'Y';
} else {
    $arResult['SUCCESS_MORE_7DAYS'] = 'N';
}*/

foreach ($arResult["BASKET"] as &$product) {
    $product['RESIZE_PICT'] = CFile::ResizeImageGet($product["PREVIEW_PICTURE"], array('width' => 452, 'height' => 678), BX_RESIZE_IMAGE_PROPORTIONAL, true);
}
unset($product);