<?php

require_once rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/local/components/bitrix/sale.order.ajax/class.php';

use Bitrix\Crm\Service\Sale\Order\BuyerService;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Controller\PhoneAuth;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Session\Session;
use Bitrix\Main\Web\Json;
use Bitrix\Sale;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Location\GeoIp;
use Bitrix\Sale\Location\LocationTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\PersonType;
use Bitrix\Sale\Result;
use Bitrix\Sale\Services\Company;
use Bitrix\Sale\Shipment;
use Bitrix\Main\UserTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/**
 * @var $APPLICATION CMain
 * @var $USER CUser
 */

Loc::loadMessages(__FILE__);

if (!Loader::includeModule("sale"))
{
	ShowError(Loc::getMessage("SOA_MODULE_NOT_INSTALL"));

	return;
}

class BelleyouCheckout extends SaleOrderAjax
{

    protected function getJsDataResult()
    {
        parent::getJsDataResult();
        
        global $USER;
        $arResult =& $this->arResult;
        $result =& $this->arResult['JS_DATA'];

        $arr = $this->order->getPropertyCollection()->getArray();
        foreach ($arr['properties'] as $key => $property)
        {
            if (in_array($property['CODE'], ['DADATA_LOCATION', 'DADATA_FIAS', 'DADATA_ZIP']))
            {
                $result["DADATA_PROPS"][$property['CODE']] = $property;
            }
        }
    }

}
