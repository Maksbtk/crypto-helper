<?php
namespace TDauto\Api;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;

class PersonalAccount
{
    private $cacheTime = 1800;
    private $currentUser;
    private $componentResult;

    public function __construct()
    {
        global $USER;
        Loader::includeModule('iblock');
        $this->currentUser = $USER;
    }

    public function getManagerInfo(): array
    {
        $res = [];

        if ($this->currentUser->IsAuthorized()) {
            $userID = $this->currentUser->GetID();

            if (\Bitrix\Main\Loader::includeModule("tdauto.price")) {
                $companyList = \TDAuto\Price\TUser::getCompanyList($userID);
                if (!empty($companyList)) {

                    $cache = Cache::createInstance();
                    if ($cache->initCache($this->cacheTime, 'ManagerInfoForUser-ID-' . $userID)) {
                        $res = $cache->getVars();
                    } elseif ($cache->startDataCache()) {


                        $companyListDef = [];
                        $i = 0;
                        foreach ($companyList as $k => $item) {
                            if ($i == 0) {
                                $companyListDef = getInfoManager($item['MANAGER_ID']);
                                $companyListDef['MANAGER_NAME'] = $item['MANAGER_NAME'];
                            }
                            if ($item['DEFAULT'] == 'Y') {
                                $companyListDef = getInfoManager($item['MANAGER_ID']);
                                $companyListDef['MANAGER_NAME'] = $item['MANAGER_NAME'];
                            }
                            $i++;
                        }

                        $managerImg = \CFile::GetPath($companyListDef['PERSONAL_PHOTO']);
                        if (!file_exists($_SERVER["DOCUMENT_ROOT"] . $managerImg)) {
                            $managerImg = '/images/managerPhotoDefault.png';
                        }

                        $companyListNameAr = [];
                        foreach ($companyList as $company) {
                            $companyListNameAr[] = $company['NAME'];
                        }

                        $rsUser = $this->currentUser->GetByID($userID);
                        $arUser = $rsUser->Fetch();
                        $userUseNDS = $arUser["UF_USE_NDS"];

                        $ofertaFile = '/register/Публичная оферта договора поставки товара без НДС.docx';
                        if ($userUseNDS) {
                            $ofertaFile = '/register/Публичная оферта договора поставки товара с НДС.docx';
                        }

                        $res = [
                            "accountStatus" => true,
                            "photo" => $managerImg,
                            "name" => $companyListDef['MANAGER_NAME'],
                            "phone" => $companyListDef['PERSONAL_PHONE'],
                            "email" => $companyListDef['EMAIL'],
                            'credentials' => [
                                "name" => $this->currentUser->GetFullName(),
                                "login" => $this->currentUser->GetLogin(),
                            ],
                            'contracts' => [
                                "file" => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $ofertaFile,
                                "items" => $companyListNameAr,
                            ],
                        ];

                        $cache->endDataCache($res);
                    }
                } else {
                    $res['accountStatus'] = false;
                }
            }
        } else {
            $res['err'] = 'not authorized';
        }
        
        return $res ?? [];
    }

    public function getOrderList($pagen, $limit, $orderFilters = []): array
    {
        $res = [];

        if ($this->currentUser->IsAuthorized()) {

            if (\Bitrix\Main\Loader::includeModule("tdauto.price")) {

                global $APPLICATION;
                $componentResult = $APPLICATION->IncludeComponent(
                    "tdauto:sale.personal.order.list",
                    "td_auto",
                    Array(
                        "STATUS_COLOR_N" => "green",
                        "STATUS_COLOR_P" => "yellow",
                        "STATUS_COLOR_F" => "gray",
                        "STATUS_COLOR_PSEUDO_CANCELLED" => "red",
                        "SEF_MODE" => "Y",
                        "ITEMS_PER_PAGE" => 20,
                        "PAGER_TEMPLATE" => "paginator",
                        "PATH_TO_PAYMENT" => "payment.php",
                        "PATH_TO_BASKET" => "basket.php",
                        "PAGER_TITLE" => "Заказы",
                        "SET_TITLE" => "Y",
                        "SAVE_IN_SESSION" => "Y",
                        "ACTIVE_DATE_FORMAT" => "d.m.Y",
                        "PROP_1" => Array(),
                        "PROP_2" => Array(),
                        "CACHE_TYPE" => "A",
                        "CACHE_TIME" => "3600",
                        "CACHE_GROUPS" => "Y",
                        "CUSTOM_SELECT_PROPS" => "",
                        "HISTORIC_STATUSES" => ["F","N"],
                        "SEF_FOLDER" => "/",
                        "DISPLAY_TOP_PAGER" => "N",
                        "DISPLAY_BOTTOM_PAGER" => "Y",
                        "SEF_URL_TEMPLATES" => Array(
                            "list" => "index.php",
                            "detail" => "order_detail.php?ID=#ID#",
                            "cancel" => "order_cancel.php?ID=#ID#"
                        ),
                        "VARIABLE_ALIASES" => Array(
                            "list" => Array(),
                            "detail" => Array(
                                "ID" => "ID"
                            ),
                            "cancel" => Array(
                                "ID" => "ID"
                            ),
                        ),
                        "REACT_API" => "Y",
                        "PAGEN" => intval($pagen),
                        "LIMIT" => intval($limit),
                        "API_ORDER_FILTERS" => $orderFilters,
                    )
                );

                // -- код из /var/www/stage.td-auto.ru/bitrix/templates/.default/components/tdauto/sale.personal.order.list/td_auto/result_modifier.php
                $arBasketItemID = [];

                foreach ($componentResult['ORDERS'] as &$order) {
                    foreach ($order["BASKET_ITEMS"] as &$arItem) {
                        $arBasketItemID[] = $arItem["ID"];
                    }
                    unset($arItem);
                }
                unset($order);

                $arBasketItemStatus = \TDAuto\Price\TBasketStatus::get($arBasketItemID);

                //Список всех статутов для товаров
                $basketItamStatusList = \TDAuto\Price\TBasketStatus::getAirusStatusList();
            }

            foreach ($componentResult['ORDERS'] as &$order) {
                /**
                 * Новая позиция на заказе
                 */
                \Bitrix\Main\Loader::includeModule("dvizhcom.airus");
                $dbAirusItems = \Dvizhcom\Airus\Order\AdditionalProductTable::getList([
                    'filter' => [
                        'order_id' => $order["ORDER"]["ID"]
                    ],
                    'order' => [
                        'date_create' => "DESC"
                    ]
                ]);
                while ($arItem = $dbAirusItems->fetch()) {
                    if (!isset($order["BASKET_ITEMS"]["ADDITIONAL_ITEM_{$arItem["airus_id"]}"])) {
                        $order["BASKET_ITEMS"]["ADDITIONAL_ITEM_{$arItem["airus_id"]}"] = [
                            "ID" => $arItem["id"],
                            "AIRUS" => $arItem["airus_id"],
                            "ARTICLE" => $arItem["article"],
                            "BRAND" => $arItem["brand"],
                            "QUANTITY" => $arItem["quant_zak"],
                            "PRICE" => $arItem["price"],
                            "CURRENCY" => "RUB",
                            "NEW_AIRUS_POSITION" => "Y"
                        ];
                    }
                    $dbElement = \Bitrix\Iblock\ElementTable::getRow([
                        'filter' => [
                            'IBLOCK_ID' => \AirusCatalog::getIblocks(array_keys(\AirusCatalog::$arExtIblock)),
                            'ACTIVE' => "Y",
                            'CODE' => $arItem["airus_id"]
                        ]
                    ]);
                    $order["BASKET_ITEMS"]["ADDITIONAL_ITEM_{$arItem["airus_id"]}"]["NAME"] = $dbElement["NAME"];
                    $order["BASKET_ITEMS"]["ADDITIONAL_ITEM_{$arItem["airus_id"]}"]["STATUS"][] = array_merge(
                        $basketItamStatusList[$arItem["status_id"]],
                        ["DATE_CREATE" => $arItem["date_create"]]
                    );
                }
            }
            unset($order);

            foreach ($componentResult['ORDERS'] as &$order) {

                //$order["ORDER"]["STATUS_NAME"] = $arResult["INFO"]["STATUS"][$order["ORDER"]["STATUS_ID"]]["NAME"];

                foreach ($order["BASKET_ITEMS"] as &$arItem) {
                    if (!isset($arItem["STATUS"])) {
                        $arItem["STATUS"] = [];
                        if ($arBasketItemStatus[$arItem["ID"]])
                            $arItem["STATUS"] = $arBasketItemStatus[$arItem["ID"]];
                    }


                    //Заказ отменен и статус товара "Ожидает утверждения"
                    if (
                        ($order["ORDER"]["STATUS_ID"] == "N" || $order["ORDER"]["CANCELED"] == "Y") &&
                        (count($arItem["STATUS"]) == 1 && $arItem["STATUS"][0]["ID"] == 1)
                    ) {
                        $arItem["STATUS"][0] = $basketItamStatusList[6];
                    }


                    //Заказ выдан и статус товара "Ожидает утверждения"
                    if (
                        $order["ORDER"]["STATUS_ID"] == "F" &&
                        (count($arItem["STATUS"]) == 1 && $arItem["STATUS"][0]["ID"] == 1)
                    ) {
                        $arItem["STATUS"][0] = $basketItamStatusList[12];
                    }


                    $startDate = null;
                    $endDate = null; //
                    $deliveryTime = 0;

                    //Заказ обработан
                    foreach ($arItem["STATUS"] as $status)
                        if ($status["ID"] == 3 && $status["DATE_CREATE"]) {
                            $startDate = new \DateTime($status["DATE_CREATE"]->format("Y-m-d"));
                            break;
                        }

                    //Выдан или В Пункте получения
                    foreach (array_reverse($arItem["STATUS"]) as $status)
                        if (in_array($status["ID"], [11, 12]) && $status["DATE_CREATE"]) {
                            $endDate = new \DateTime($status["DATE_CREATE"]->format("Y-m-d"));
                            break;
                        }


                    if ($arItem["MODULE"] == "catalog") {

                        $arAttributes = \AirusCatalog::getArticleBrand($arItem["PRODUCT_ID"]);

                        $brand = $arAttributes["BRAND"];
                        $article = $arAttributes["ARTICLE"];
                        $model = $arAttributes["MODEL"];

                        $arItem["ARTICLE"] = $article;
                        $arItem["BRAND"] = $brand;

                    } elseif ($arItem["MODULE"] == "linemedia.auto") {

                        $arItem["PROPS"] = $this->TDGetBasketItemProps($arItem["ID"]);

                        $arItem["ARTICLE"] = $arItem["PROPS"]["article"];
                        $arItem["BRAND"] = $arItem["PROPS"]["brand_title"];
                        $arItem["AIRUS"] = $arItem["PROPS"]["AIRUS"];

                        $deliveryTime = ceil(((int)$arItem["PROPS"]["remote_delivery_time"] + (int)$arItem["PROPS"]["delivery_time"]) / 24);

                    }

                    if ($startDate) {
                        //Есть В работе
                        //Ожидает доставку в
                        $deliveryStartDate = \TDAuto\Price\TDate::addDayWithHolidays($startDate, $deliveryTime);
                        $arItem["DELIVERY_DATE"] = $deliveryStartDate;

                        if (!in_array($arItem["STATUS"][0]["ID"], [4, 5, 6, 7, 8])) {

                            //Пришел или должно прийти
                            $deliveryEndDate = \TDAuto\Price\TDate::addDayWithHolidays(new \DateTime("today"), 0);
                            if ($endDate) {

                                $deliveryEndDate = $endDate;
                                $arItem["ARRIVAL_DATE"] = $deliveryEndDate; //в складе или выдан

                            }

                            $diff = $deliveryEndDate->diff($deliveryStartDate);
                            if ($diff->invert && $deliveryEndDate->diff($deliveryStartDate)->days > 0)
                                $arItem["DELAY_DAY"] = $diff->days; //Задержка в день

                        }
                    }

                    $airusCode = false;
                    if ($arItem['PROPS']['airus_id'])
                        $airusCode = $arItem['PROPS']['airus_id'] ?? false;

                    if (!$airusCode && $arItem["ARTICLE"] && $arItem["BRAND"])
                        $airusCode = \AirusCatalog::getAirusCodeByArticleBrand($arItem["ARTICLE"], $arItem["BRAND"]) ?? false;

                    if (!$airusCode && $arItem["PRODUCT_ID"])
                        $airusCode = \AirusCatalog::getAirusCodeById($arItem["PRODUCT_ID"]) ?? false;

                    if ($airusCode)
                        $arItem["AIRUS"] = $airusCode;

                    $arItem["FORMATED_PRICE"] = \CCurrencyLang::CurrencyFormat(
                        roundEx(
                            $arItem["PRICE"] * $arItem["QUANTITY"],
                            SALE_VALUE_PRECISION
                        ),
                        $arItem["CURRENCY"],
                        true
                    );

                    $hasStatusCreated = false;
                    foreach ($arItem["STATUS"] as &$status) {
                        if ($status["DATE_CREATE"]) {
                            $status["DATE_FORMATTED"] = $status["DATE_CREATE"]->format("d.m.Y H:i:s");
                        } elseif ($status["ID"] == 0) {
                            $hasStatusCreated = true;
                            $status["DATE_FORMATTED"] = $order["ORDER"]["DATE_INSERT"]->format('d.m.Y H:i:s');
                        } else {
                            $status["DATE_FORMATTED"] = $order["ORDER"]["DATE_STATUS"]->format('d.m.Y H:i:s');
                        }
                    }
                    unset($status);

                    if (!$hasStatusCreated) {
                        $arItem["STATUS"][] = [
                            "ID" => 0,
                            "NAME" => "Создан",
                            "DATE_FORMATTED" => $order["ORDER"]["DATE_INSERT"]->format('d.m.Y H:i:s')
                        ];
                    }

                }
                unset($arItem);

            }
            unset($order);
            // -- код из /var/www/stage.td-auto.ru/bitrix/templates/.default/components/tdauto/sale.personal.order.list/td_auto/result_modifier.php

            foreach ($componentResult['ORDERS'] as $orderKey => $order) {

                // адрес доставки
                $resultDelivery = '';
                $obOrder = \Bitrix\Sale\Order::load($order["ORDER"]["ID"]);
                $propertyCollection = $obOrder->getPropertyCollection();
                $deliveryAddress = "";
                foreach ($propertyCollection as $property) {
                    if ($property->getField("CODE") == "ADDRESS") {
                        $deliveryAddress = $property->getField("VALUE");
                    }
                }

                if ($order["ORDER"]["DELIVERY_ID"] == 1 && $deliveryAddress) {
                    $resultDelivery = $componentResult['INFO']['DELIVERY'][$order["ORDER"]["DELIVERY_ID"]]['NAME'] . ' : ' . $deliveryAddress;
                } else {
                    $resultDelivery = $componentResult['INFO']['DELIVERY'][$order["ORDER"]["DELIVERY_ID"]]['NAME'];
                }
                // адрес доставки

                //корзина заказа
                $maxDeliveryTime = 0;
                $basketItems = [];
                foreach ($order["BASKET_ITEMS"] as $basketItem) {
                    $basketItemDeliveryDate = '-';
                    $basketItemDeliveryDelay = '';
                    if ($basketItem['DELIVERY_DATE']) {
                        $basketItemDeliveryDate = $basketItem['DELIVERY_DATE']->format('d.m.Y');
                        if ($basketItem["ARRIVAL_DATE"]) {
                            $basketItemDeliveryDate = $basketItemDeliveryDate . "/" . $basketItem["ARRIVAL_DATE"]->format('d.m.Y');
                        }
                        if ($basketItem["DELAY_DAY"]) {
                            $basketItemDeliveryDelay = 'задержка ' . $basketItem['DELAY_DAY'] . ' ' . plural_form($arItem["DELAY_DAY"], ["день", "дня", "дней"]);
                        }
                    } else if ($basketItem["NEW_AIRUS_POSITION"] == "Y") {
                        $basketItemDeliveryDate = 'Добавлено менеджером, дата не известна';
                    }

                    if ($basketItem['PROPS']['delivery_time_in_hours'] && intval($basketItem['PROPS']['delivery_time_in_hours']) > $maxDeliveryTime)
                        $maxDeliveryTime = intval($basketItem['PROPS']['delivery_time_in_hours']);

                    $basketItems[] = [
                        'id' => $basketItem['ID'],
                        'title' => $basketItem['NAME'] . ', ' . $basketItem['QUANTITY'] . 'шт',
                        'brand' => $basketItem['BRAND'],
                        'productCode' => $basketItem['ARTICLE'],
                        'code' => $basketItem['AIRUS'] ?? '-',
                        'quantity' => $basketItem['QUANTITY'],
                        'amount' => $basketItem["FORMATED_PRICE"],
                        'status' => $basketItem["STATUS"][0]["NAME"],
                        'date' => $basketItemDeliveryDate,
                        'delay' => $basketItemDeliveryDelay,
                        'deliveryTerm' => parseDeliveryTime($basketItem['PROPS']['delivery_time_in_hours'], 'Сегодня'),
                        //'dev' => $basketItem,
                    ];
                }

                $res['orders'][] = [
                    'status' => $componentResult['INFO']['STATUS'][$order["ORDER"]["STATUS_ID"]]['NAME'],
                    'title' => $order["ORDER"]["ACCOUNT_NUMBER"] . ' от ' . $order["ORDER"]["DATE_INSERT_FORMATED"],
                    'amount' => CurrencyFormat($order["ORDER"]["PRICE"], 'RUB'),
                    'date' => $order["ORDER"]["DATE_M"],
                    'address' => $resultDelivery,
                    'items' => $basketItems,
                    'deliveryTime' => $maxDeliveryTime ?? 0,
                    'deliveryTerm' => parseDeliveryTime($maxDeliveryTime, 'Сегодня'),
                    //'debv' => $componentResult,
                ];
            }

            $res['nav'] = $componentResult['NAV_PARAMS'];
            $res['orderFilters'] = $orderFilters;
        } else {
            $res['err'] = 'not authorized';
        }

        return $res ?? [];
    }

    protected function TDGetBasketItemProps($id)
    {
        $basketProperties = array();

        $filter = array(
            "filter" => array("BASKET_ID" => $id),
            "order"  => array()
        );

        $basketList = \Bitrix\Sale\Internals\BasketPropertyTable::getList($filter);

        while ($basket = $basketList->Fetch()) {
            $basketProperties[$basket["CODE"]] = $basket["VALUE"];
        }

        return $basketProperties;
    }


    /**
    [
        'status' : 'Y', // Y - включен, N - выключен
        'showType' : 'B', // B - обе цены, D - только с наценкой, P - только без наценки
        'discounts' : [
          [
            'id': "BT_0_100",
            'price': "До 100 ₽",
            'initialMargin': 50,
            'initialMode': 'R', // R - рублей, P - процентов
          ],
          ...
        ]
    ]
    */
    public function getDiscount(): array
    {
        $res = [];

        if ($this->currentUser->IsAuthorized() && \Bitrix\Main\Loader::includeModule("tdauto.price")) {

            $priceSetting = \TDAuto\Price\TDiscount::getSettings();

            if (is_array($priceSetting) && ($priceSetting['ACTIVE'] == 'Y')) {
                $res['status'] = $priceSetting['ACTIVE'];
                $res['showType'] = $priceSetting['SHOW_TYPE'];

                $priceRanges = [
                    'BT_0_100' => 'До 100 ₽',
                    'BT_100_500' => 'От 100₽ до 500 ₽',
                    'BT_500_1000' => 'От 500₽ до 1000 ₽',
                    'BT_1000_3000' => 'От 1000₽ до 3000 ₽',
                    'BT_3000_5000' => 'От 3000₽ до 5000 ₽',
                    'BT_5000_10000' => 'От 5000₽ до 10000 ₽',
                    'BT_10000_15000' => 'От 10000₽ до 15000 ₽',
                    'BT_15000_50000' => 'От 15000₽ до 50000 ₽',
                    'BT_50000' => 'Свыше 50000 ₽',
                ];

                foreach ($priceRanges as $key => $label) {
                    $discount = [
                        'id' => $key,
                        'price' => $label,
                        'initialMargin' => (int)$priceSetting[$key],
                        'initialMode' => $priceSetting[$key . '_TYPE']
                    ];
                    $res['discounts'][] = $discount;
                }
            } else {
                $res['status'] = $priceSetting['ACTIVE'];
                $res['discounts'] = [];
            }
            
        } else {
            $res['err'] = 'not authorized';
        }

        return $res ?? [];
    }

    /**
        [
        'ACTIVE' : 'Y', // Y - включен, N - выключен
        'SHOW_TYPE' : 'B', // B - обе цены, D - только с наценкой, P - только без наценки
        'BT_0_100' : '50',
        'BT_0_100_TYPE' : 'R',
        'BT_100_500' : '100',
        'BT_100_500_TYPE' : 'R',
        'BT_500_1000' : '20',
        'BT_500_1000_TYPE' : 'R',
        'BT_1000_3000' : '20',
        'BT_1000_3000_TYPE' : 'R',
        'BT_3000_5000' : '20',
        'BT_3000_5000_TYPE' : 'R',
        'BT_5000_10000' : '20',
        'BT_5000_10000_TYPE' : 'R',
        'BT_10000_15000' : '20',
        'BT_10000_15000_TYPE' : 'R',
        'BT_15000_50000' : '20',
        'BT_15000_50000_TYPE' : 'R',
        'BT_50000' : '20',
        'BT_50000_TYPE' : 'R',
        ]
     */
    public function setDiscount($request): array {
        $res = [];

        if ($this->currentUser->IsAuthorized() && \Bitrix\Main\Loader::includeModule("tdauto.price")) {

            if ($request['ACTIVE'] == 'N') {
                \TDAuto\Price\TDiscount::deactive();
                $res['active'] = "N";
            } elseif ($request['ACTIVE'] == 'Y') {
                \TDAuto\Price\TDiscount::active();
                //unset($request["update"], $request["ACTIVE"]);
                \TDAuto\Price\TDiscount::updateSettings($request);
                $res['active'] = "Y";
            }
            $res['success'] = true;

        } else {
            $res['err'] = 'not authorized';
        }

        return $res ?? [];
    }

    public function userCompanies($companyId = 0) {

        $res = [];
        if ($this->currentUser->IsAuthorized() && \Bitrix\Main\Loader::includeModule("tdauto.price")) {
            global $APPLICATION;
            $componentRes = $APPLICATION->IncludeComponent(
                "tdauto:company.list",
                "",
                [
                    'REACT_API' => 'Y',
                    'COMPANY_CODE' => $companyId
                ]
            );

            //$res['dev'] = $componentRes;
            if (!$companyId && $componentRes['ITEMS'])
                $res['list'] = $componentRes['ITEMS'];
            else if (!$companyId)
                $res['err'][] = 'no component items';

            if ($componentRes['DEFAULT_ITEM'])
                $res['currentCompany'] = $componentRes['DEFAULT_ITEM'];
            else
                $res['err'][] = 'no current item';

        } else {
            $res['err'][] = 'not authorized';
        }
        return $res ?? [];
    }

    public function getSellInvoiceList($pagen, $limit ) {

        $res = [];
        global $USER;

        $arConsignmentFileId = [];
        $consignmentItems = \CIBlockElement::GetList(
            [],
            [
                //'IBLOCK_CODE' => 'consignment_doc',
                'IBLOCK_ID' => '157',
                '=PROPERTY_NAKL_USER'=>$USER->GetID()
            ],
            false,
            [
                'nTopCount' => false,
                'nPageSize' => $limit,
                'iNumPage' => $pagen,
                'checkOutOfRange' => true
            ],
            ['PROPERTY_CONSIGNMENT_DOC', 'IBLOCK_ID', 'ID']
        );

        $res['INVOICES'] = [];
        //$count = 0;
        while ($arItem = $consignmentItems->Fetch()) {
            $invoices = $arActIds = $retInvoices = $dataProdRefundQuantityAr = [];
            //$count++ ;

            //достаем возвратные акты привязанные к продажной накладной
            $resActProp = \CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], false, ["CODE" => "REFUND_ACT"]);
            while ($obActProp = $resActProp->fetch()) {
                if ($obActProp['CODE'] == 'REFUND_ACT' && $obActProp['VALUE']) {
                    $arActIds[] = $obActProp['VALUE'];
                }
            }
            $invoices['RET_ACT'] = $arActIds;

            $retInvoicesIds = [];
            if ($arActIds && is_array($arActIds) && count($arActIds) >= 1) {

                //достаем возвратные ид возвратных накладных привязанных к возвратным актам
                $retActDb = \CIBlockElement::GetList([], [ 'IBLOCK_CODE' => 'refunds_act', 'ID' => $arActIds, '!PROPERTY_JSON_DATA' => false], false, [], ['ID', 'IBLOCK_ID',]);
                while ($retActItem = $retActDb->Fetch()) {
                    $resRetInvociesProp = \CIBlockElement::GetProperty($retActItem['IBLOCK_ID'], $retActItem['ID'], false, ["CODE" => "RETURN_INVOICES"]);
                    while ($retInvociesPropItem = $resRetInvociesProp->fetch()) {
                        if ($retInvociesPropItem['CODE'] == 'RETURN_INVOICES' && $retInvociesPropItem['VALUE']) {
                            $retInvoicesIds[] = $retInvociesPropItem['VALUE'];
                        }
                    }
                }

                if ($retInvoicesIds && is_array($retInvoicesIds) && count($retInvoicesIds) >= 1) {
                    //достаем возвратные накладные привязанные к возвратным актам
                    $retInvoicesDb = \CIBlockElement::GetList([], ['IBLOCK_ID' => '167', 'ID' => $retInvoicesIds], false, [], ['ID', 'NAME', 'PROPERTY_CONSIGNMENT_DOC', 'PROPERTY_RET_ACT_ID', 'PROPERTY_SELL_INVOICE_ID']);
                    while ($retInvoicesItem = $retInvoicesDb->Fetch()) {

                        $filePath = $_SERVER['DOCUMENT_ROOT'] . \CFile::GetPath($retInvoicesItem['PROPERTY_CONSIGNMENT_DOC_VALUE']);
                        $fileContent = json_decode(file_get_contents($filePath, FILE_USE_INCLUDE_PATH), true);//['Nak_Name'][0]['t1']

                        foreach ($fileContent['Nak_Name'][0]['t1'] as $dataProdRefundItem) {
                            if ($fileContent['Nak_Name'][0]['isDeleted'] == '0') {
                                $dataProdRefundQuantityAr[$dataProdRefundItem['C_Tov']] = floatval($dataProdRefundItem['Kol_vo']);
                            } else if ($fileContent['Nak_Name'][0]['isDeleted'] == '1') {
                                $dataProdRefundQuantityAr[$dataProdRefundItem['C_Tov']] = floatval($dataProdRefundItem['Kol_vo']) * (-1);
                            }
                        }

                        $retInvoices[] = [
                            'ID' => $retInvoicesItem['ID'],
                            'EXT_ID' => $retInvoicesItem['NAME'],
                            'RETURN_ACT_ID' => $retInvoicesItem['PROPERTY_RET_ACT_ID_VALUE'],
                            'FILE_ID' => $retInvoicesItem['PROPERTY_CONSIGNMENT_DOC_VALUE'],
                            //'FILE_CONTENT' => $fileContent,
                            'FILE_PATH' => $filePath,
                            'SELL_INVOICE_ID' => $retInvoicesItem['PROPERTY_SELL_INVOICE_ID_VALUE'],
                        ];

                    }
                }
            }
            $invoices['RET_INVOICES_IDS'] = $retInvoicesIds;
            $invoices['RET_INVOICES'] = $retInvoices;
            $invoices['REFUND_PROD'] = $dataProdRefundQuantityAr;

            if (!$arItem['PROPERTY_CONSIGNMENT_DOC_VALUE']) {
                $res['INVOICES'][] = ['ERR' => 'err. file id. iblock el id - ' . $arItem['ID']];
                continue;
            }

            $resFile = \CFile::GetList(false, ["ID"=>$arItem['PROPERTY_CONSIGNMENT_DOC_VALUE']]);
            $arConsignmentFilePath = '';
            if ($res_arr = $resFile->Fetch())
                $arConsignmentFilePath = $_SERVER['DOCUMENT_ROOT'] . "/upload/" . $res_arr["SUBDIR"] . "/" . $res_arr["FILE_NAME"];

            $jsonFileData = json_decode(file_get_contents($arConsignmentFilePath, FILE_USE_INCLUDE_PATH), true);
            if (!$jsonFileData) {
                $res['INVOICES'][] = ['ERR' => 'err. file read. iblock el id - ' . $arItem['ID']];
                continue;
            }// $_SERVER['DOCUMENT_ROOT']

            $orderShipmenDate = new \DateTimeImmutable($jsonFileData['Nak_Name'][0]['D_Reg']);
            $arConsignmentAirusId = [];

            foreach ($jsonFileData['Nak_Name'][0]['t1'] as $consignmentProductItem) {
                $arConsignmentAirusId[] = $consignmentProductItem['C_Tov'];
            }

            // достаем по AirusID данные из прайс-листа linemedia
            $rs = \LMProductsTable::getList(array(
                'select' => array(
                    'article',
                    'brand_title',
                    'title',
                    'AirusID',
                    'MinZakaz'
                ),
                'runtime' => [],
                'filter' => ['AirusID' => $arConsignmentAirusId , '@supplier_id' =>['4','35']],
                'limit' => count($arConsignmentAirusId),
                'group' => []
            ));

            $arLmData = [];
            while ($part = $rs->fetch()) {
                $arLmData[intval($part['AirusID'])] = $part;
            }

            $priceSum = 0;
            $arConsignmentProducts = [];
            //$filterIndicator = false;
            foreach ($jsonFileData['Nak_Name'][0]['t1'] as $consignmentProductItem) {
                $intAirusCode = intval($consignmentProductItem['C_Tov']);

                if (floatval($consignmentProductItem['Kol_vo']) < floatval($arLmData[$intAirusCode]['MinZakaz']))
                    $arLmData[$intAirusCode]['MinZakaz'] = $consignmentProductItem['Kol_vo'];

                $prodCount = $consignmentProductItem['Kol_vo'];
                if ($dataProdRefundQuantityAr[$consignmentProductItem['C_Tov']])
                    $prodCount = strval(floatval($consignmentProductItem['Kol_vo']) - floatval($dataProdRefundQuantityAr[$consignmentProductItem['C_Tov']]));

                $arConsignmentProducts[] = [
                    'AIRUS' => $consignmentProductItem['C_Tov'],
                    'ARTICLE' => $arLmData[$intAirusCode]['article'],
                    'BRAND' => $arLmData[$intAirusCode]['brand_title'],
                    'NAME' => $arLmData[$intAirusCode]['title'],
                    'MIN_ZAKAZ' => $arLmData[$intAirusCode]['MinZakaz'],
                    //'QUANTITY' => $consignmentProductItem['Kol_vo'],
                    'QUANTITY' => $prodCount,
                    'PRICE' => $consignmentProductItem['C2'],
                ];

                $priceSum += floatval($consignmentProductItem['C2']) * $consignmentProductItem['Kol_vo'];
            }

            $companyDataAr = [];
            $resCompanyData  = \CIBlockElement::GetList(
                [],
                ["IBLOCK_ID"=>140,'CODE'=> $jsonFileData['Nak_Name'][0]['C_PP']],//140-это компании
                false,
                false,
                ["NAME", "PROPERTY_MANAGER_NAME", "PROPERTY_ADDRESS"]
            );

            while($resData = $resCompanyData->fetch())
                $companyDataAr = $resData;

            $invoices['DATA'] = [
                'NAK_ID' => $jsonFileData['Nak_Name'][0]['NakID'],
                'D_REG' => $orderShipmenDate->format('d.m.Y'),
                'FULL_PRICE' => $priceSum,
                'BUYER_CODE' => $jsonFileData['Nak_Name'][0]['C_PP'],
                'USER_ID' => $USER->GetID(),
                //'BARCODE' => '10'.str_replace([':',' ','.'],'',$jsonFileData['Nak_Name'][0]['D_Reg']),
                'BARCODE' => substr('10'.$orderShipmenDate->format('ymdHisu'),0,-3),
                'COMPANY_INFO' => $companyDataAr,
            ];

            $invoices['PRODUCTS'] = $arConsignmentProducts;

            $res['INVOICES'][/*$arItem['ID']*/] = $invoices;
        }

        //$consignmentItems->NavStart();
        $allCnt = $consignmentItems->NavRecordCount;
        $res['NAV'] = [
            "currentPage" => $pagen,
            "pageSize" => $limit,
            "recordCount" => $allCnt,
            //"dev_count" => $count
        ];

        return $res ?? [];
    }

    public function getReturnActs($pagen, $limit)
    {
        global $USER;

        $res = [];
        $refundActItems = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_CODE' => 'refunds_act',
                '=PROPERTY_ACT_USER' => $USER->GetID(),
                '!PROPERTY_JSON_DATA' => false
            ],
            false,
            [
                'nTopCount' => false,
                'nPageSize' => $limit,
                'iNumPage' => $pagen,
                'checkOutOfRange' => true
            ],
            ['PROPERTY_JSON_ALLDATA_STR', 'PROPERTY_REFUND_ACT', 'PROPERTY_JSON_DATA', 'IBLOCK_ID', 'ID']
        );

        $res = $err = [];
        while ($arItem = $refundActItems->fetch())
        {
            //if ($arItem['PROPERTY_JSON_DATA_VALUE']) {

            $retActFilePath = $_SERVER['DOCUMENT_ROOT'] . \CFile::GetPath($arItem['PROPERTY_JSON_DATA_VALUE']);
            $jsonFileData = json_decode(file_get_contents($retActFilePath, FILE_USE_INCLUDE_PATH), true);

            if (!$jsonFileData) {
                $err[] = ['ERR' => 'err. file read. iblock el id - ' . $arItem['ID']];
                continue;
            }

            $jsonFileData['dataRefund'][0]['PDF_LINK'] = false;
            if ($arItem['PROPERTY_REFUND_ACT_VALUE'])
                $jsonFileData['dataRefund'][0]['PDF_LINK'] = \CFile::GetPath($arItem['PROPERTY_REFUND_ACT_VALUE']) ?? false;
            else
                $err[] = ['ERR' => 'err. pdf file read. - ' . $arItem['ID']];

            //достаем возвратные акты привязанные к продажной накладной
            $arRetInvoicesIds = $retInvoices = $dataProdRefundQuantityAr = [];
            $resActProp = \CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], false, ["CODE" => "RETURN_INVOICES"]);
            while ($obActProp = $resActProp->fetch()) {
                if ($obActProp['CODE'] == 'RETURN_INVOICES' && $obActProp['VALUE']) {
                    $arRetInvoicesIds[] = $obActProp['VALUE'];
                }
            }
            $jsonFileData['dataRefund'][0]['RET_INVOICES_IDS'] = $arRetInvoicesIds;

            if ($arRetInvoicesIds && is_array($arRetInvoicesIds) && count($arRetInvoicesIds) >= 1) {
                //достаем возвратные накладные привязанные к возвратным актам
                $retInvoicesDb = \CIBlockElement::GetList([], ['IBLOCK_CODE' => 'return_consignment_doc', 'ID' => $arRetInvoicesIds], false, [], ['ID', 'NAME', 'PROPERTY_CONSIGNMENT_DOC', 'PROPERTY_RET_ACT_ID', 'PROPERTY_SELL_INVOICE_ID']);
                while ($retInvoicesItem = $retInvoicesDb->fetch()) {

                    $filePath = $_SERVER['DOCUMENT_ROOT'] . \CFile::GetPath($retInvoicesItem['PROPERTY_CONSIGNMENT_DOC_VALUE']);
                    $fileContent = json_decode(file_get_contents($filePath, FILE_USE_INCLUDE_PATH), true);//['Nak_Name'][0]['t1']

                    foreach ($fileContent['Nak_Name'][0]['t1'] as $dataProdRefundItem) {
                        if ($fileContent['Nak_Name'][0]['isDeleted'] == '0') {
                            $dataProdRefundQuantityAr[$dataProdRefundItem['C_Tov']] = floatval($dataProdRefundItem['Kol_vo']);
                        } else  if ($fileContent['Nak_Name'][0]['isDeleted'] == '1') {
                            $dataProdRefundQuantityAr[$dataProdRefundItem['C_Tov']] = floatval($dataProdRefundItem['Kol_vo']) * (-1);
                        }
                    }

                    $retInvoices[] = [
                        'ID' => $retInvoicesItem['ID'],
                        'EXT_ID' => $retInvoicesItem['NAME'],
                        'RETURN_ACT_ID' => $retInvoicesItem['PROPERTY_RET_ACT_ID_VALUE'],
                        //'FILE_ID' => $retInvoicesItem['PROPERTY_CONSIGNMENT_DOC_VALUE'],
                        'CONTENT' => $fileContent,
                        //'FILE_PATH' => $filePath,
                        'SELL_INVOICE_ID' => $retInvoicesItem['PROPERTY_SELL_INVOICE_ID_VALUE'],
                    ];

                }
            }

            $jsonFileData['dataRefund'][0]['RET_INVOICES'] = $retInvoices;
            $jsonFileData['dataRefund'][0]['REFUND_PROD'] = $dataProdRefundQuantityAr;

            $resCompanyData = \CIBlockElement::GetList(
                [],
                ["IBLOCK_ID" => 140, 'CODE' => $jsonFileData['dataRefund'][0]['buyerCode']],//140-это компании
                false,
                false,
                ["NAME", "PROPERTY_MANAGER_NAME", "PROPERTY_ADDRESS"]
            );

            if ($resData = $resCompanyData->fetch()) {
                $jsonFileData['dataRefund'][0]['COMPANY_INFO'][] = $resData;
            }

            $res['INVOICES'][/*$arItem['ID']*/] = [
                //"DEV" => $jsonFileData,
                "PROD" => $jsonFileData['dataProdRefund'],
                "INFO" => $jsonFileData['dataRefund'][0],
                "ERR" => $err,
            ];

        }

        $refundActItems->NavStart();
        $allCnt = $refundActItems->NavRecordCount;
        $res['NAV'] = [
            "currentPage" => $pagen,
            "pageSize" => $limit,
            "recordCount" => $allCnt,
        ];

        return $res;
    }

    public function createReturnAct($params) {
        $res['success'] = false;

        require_once ($_SERVER["DOCUMENT_ROOT"].'/bitrix/php_interface/include/dompdf/vendor/autoload.php');
        require_once ($_SERVER["DOCUMENT_ROOT"].'/bitrix/php_interface/include/barcode/vendor/autoload.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

        \CModule::IncludeModule("iblock");
        global $USER;

        $companyDataAr = [];
        $resCompanyData  = \CIBlockElement::GetList(
            [],
            ["IBLOCK_ID"=>140,'CODE'=> $params['dataRefund'][0]['buyerCode']], //140-это компании
            false,
            false,
            ["NAME","PROPERTY_MANAGER_NAME", 'ID']
        );
        while($resData = $resCompanyData->fetch()){
            $companyDataAr = $resData;
        }

        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        //$barcodeStr = $params['dataRefund'][0]['barcode'];

        // Получаем код покупателя, проверяем его наличие и преобразуем в строку
        if (!isset($params['dataRefund'][0]['buyerCode']))
            $buyerCode = '';
        else
            $buyerCode = (string)$params['dataRefund'][0]['buyerCode'];

        $buyerCode = substr($buyerCode, 0, 7);
        $length = strlen($buyerCode);
        $zeroCount = max(7 - $length, 0);
        $zeros = str_repeat('0', $zeroCount);
        $middle = $zeros . $buyerCode;
        $datePart = date('dHi');
        $barcodeStr = '1661' . $middle . $datePart;
        $barcodeStr = substr($barcodeStr, 0, 17);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'dompdf_arial');
        $dompdf = new \Dompdf\Dompdf($options);

        $htmlContent = '
        <html>
            <meta http-equiv="content-type" content="text/html; charset=utf-8" />
            <body style="font-family: dompdf_arial; color: #414141;">
                <div style="min-height: 970px;max-width: 670px;">
                <div class="header" style="min-height: 170px;width: 100%; font-size: 14px; line-height:2.5;">
                    <div style="width: 100%; font-size: 13px; line-height:1.2!important;">
                        
                        <span style="width: 40%; height: 30%;">ООО "КОМПАНИЯ АВТО-КОМПОНЕНТ"</span>ㅤㅤㅤㅤㅤ
                        <span style="width: 60%; ">
                        <img style="" src="data:image/png;base64,'.base64_encode($generator->getBarcode($barcodeStr, $generator::TYPE_CODE_128)).'">ㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤㅤ
                        '.$barcodeStr.'
                        </span>	
                        
                    </div>
                    <br>
                    <div style="width: 80%; margin-bottom: 5px;">г. Санкт-Петербург, п. Шушары, Московское шоссе, 177 лит. А <br>тел./факс (812) 718-75-75, 704-52-95</div>
                    <div>Акт возврата <span style="text-decoration:underline;"> '. $companyDataAr['PROPERTY_MANAGER_NAME_VALUE'] .' </span>(ответственный менеджер)</div>
                    <br>
                    <div>От кого: <span style="text-decoration:underline;">'. $companyDataAr['NAME'] .'</span></div>
                </div>
                    
                    <div class="main" style=" width: 100%; line-height:2;">
                        <table style=" width: 100%;border-collapse: collapse;border: 2px solid white;  font-size: 12.5px;">
                            <tr>
                                <th style="padding: 3px;border: 1px solid #000000;text-align: center; width: 15%;">№ накладной</th>
                                <th style="padding: 3px;border: 1px solid #000000;text-align: center; width: 15%;">Код товара</th>
                                <th style="padding: 3px;border: 1px solid #000000;text-align: center; width: 30%;">Наименование<br>(марка, производитель)</th>
                                <th style="padding: 3px;border: 1px solid #000000;text-align: center; width: 5%;">Кол-во</th>
                                <th style="padding: 3px;border: 1px solid #000000;text-align: center; width: 15%;">Причина<br>возврата</th>
                                <th style="padding: 3px;border: 1px solid #000000;text-align: center; width: 20%;">Комментарий</th>
                            </tr>';

        foreach ($params['dataProdRefund'] as $dataProdRefundItem) {
            $htmlContent .= '
					<tr>
						<td style="padding: 3px;border: 1px solid #000000;text-align: center;">'.$params['dataRefund'][0]['nakId'].'</td>
						<td style="padding: 3px;border: 1px solid #000000;text-align: center;">'.$dataProdRefundItem['airusId'].'</td>
						<td style="padding: 3px;border: 1px solid #000000;text-align: center;">'.$dataProdRefundItem['prodName'].'</td>
						<td style="padding: 3px;border: 1px solid #000000;text-align: center;">'.$dataProdRefundItem['prodQuantity'].'</td>
						<td style="padding: 3px;border: 1px solid #000000;text-align: center;">'.$dataProdRefundItem['prodReason'].'</td>
						<td style="padding: 3px;border: 1px solid #000000;text-align: center;">'.$dataProdRefundItem['prodComment'].'</td>
					</tr>';
        }
        $htmlContent.='</table>
                    </div>
                    <div class="footer" style=" min-height: 200px;width: 100%; font-size: 14px; display: flex;flex-direction: column;align-content: center;align-items: flex-start; line-height:3;">
                        <div style="line-height:4;">Дата:ㅤ<span style="text-decoration:underline;">'.date("d.m.y").'</span></div>
                        <div style="line-height:4;">!Подпись водителя ООО "КОМПАНИЯ АВТО-КОМПОНЕНТ":ㅤ<span style="text-decoration:underline;">ㅤㅤㅤㅤㅤㅤ</span></div>
                        <div style="line-height:4;">Дата передачи водителю:ㅤ<span style="text-decoration:underline;">ㅤㅤㅤㅤㅤㅤ</span></div>
                        <div style="line-height:4;">Покупатель:ㅤ<span style="text-decoration:underline;">ㅤㅤㅤㅤㅤㅤ</span></div>
                        <div style="font-weight: 700!important; font-size: 13px; line-height:4; color: #000000;">Обращаем ваше внимание:</div> 		
                        <div>
                        При сдаче в брак сложных узлов и агрегатов, необходим сертификат со станции на право проведения
                        работ и акт выбраковки товара с указанием причины брака.<br>
                        На весь остальной товар - обязательное четкое указание причины брака.<br>
                        *Без указания причины брака - брак будет возвращен клиенту.
                        </div> 		
                    </div>
                </div>
            </body>
        </html>';

        $htmlContent = mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8');
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        if (!is_dir($_SERVER["DOCUMENT_ROOT"] . '/upload/tdauto/ClientSalesRefundAct/'))
            mkdir($_SERVER["DOCUMENT_ROOT"] . '/upload/tdauto/ClientSalesRefundAct/');

        $resultRefundActFilePath = $_SERVER["DOCUMENT_ROOT"].'/upload/tdauto/ClientSalesRefundAct/refundAct_'.$params['dataRefund'][0]['nakId'].'.pdf';
        file_put_contents($resultRefundActFilePath, $dompdf->output());

        $resultRefundActJsonPath = $_SERVER["DOCUMENT_ROOT"].'/upload/tdauto/ClientSalesRefundAct/refundAct_'.$params['dataRefund'][0]['nakId'].'.json';
        file_put_contents($resultRefundActJsonPath, json_encode($params));

        $refundPdf = \CFile::MakeFileArray($resultRefundActFilePath);
        $elRefundAct = new \CIBlockElement;
        $propElRefundAct = [
            'ACT_USER' => $USER->GetID(),//$params['dataRefund'][0]['userId'],
            'REFUND_ACT' => $refundPdf,
            'JSON_DATA' => \CFile::MakeFileArray($resultRefundActJsonPath),
            'SELL_INVOICE_ID' => $params['dataRefund'][0]['nakId'],
            'NEW_ACT_FROM_WEBSITE' => 'Y',
        ];

        $arElRefundAct = [
            "ACTIVE_FROM" => date('d.m.Y H:i:s'),
            "MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => 158,
            "NAME" => $params['dataRefund'][0]['nakId'].'_'.date('Hi'),
            "ACTIVE" => "Y",
            "PROPERTY_VALUES" => $propElRefundAct,
        ];

        if ($elRefundActId = $elRefundAct->Add($arElRefundAct)) {

            $actFromSellInvoiceRes = $this->getRetActFromSellInvoice($params['dataRefund'][0]['nakId']) ?? [];
            $actFromSellInvoice = $actFromSellInvoiceRes['actFromSellInvoice'];
            $actFromSellInvoice[] = $elRefundActId;
            if ($actFromSellInvoice)
                \CIblockElement::SetPropertyValuesEx($actFromSellInvoiceRes['sellInvoiceId'], 157, ["REFUND_ACT" => $actFromSellInvoice]);

            $getRetActRes = $this->getRetAct($elRefundActId);
            $res['pdfLink'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . \CFile::GetPath($getRetActRes['PROPERTY_REFUND_ACT_VALUE']) ?? false;

            if ($res['pdfLink'])
                $res['success'] = true;
            else
                $res['err'][] = 'не удалось получить ссылку на созданный акт возврата (pdf)';

            unlink($resultRefundActFilePath);
            unlink($resultRefundActJsonPath);
        } else {
            $res['err'][] = 'не создался элемент инфоблока Возвраты (акты)';
        }

        //$dompdf->stream('refund',array('Attachment' => 0));
        //$res = [$refundPdf, $actFromSellInvoiceRes, $elRefundActId];
        return $res;
    }

    private function getRetActFromSellInvoice($actId = false, $sellInvoiceId = false) {
        $sellInvoicesFromAct = [];
        $filter['IBLOCK_CODE'] = 'consignment_doc';

        if ($actId)
            $filter['NAME'] = $actId;

        $existingRetActob = \CIBlockElement::GetList([], $filter, false, false, ['IBLOCK_ID', 'NAME', 'ID']);
        if ($arItem = $existingRetActob->fetch()) {
            $sellInvoiceId = $arItem['ID'];
            $res = \CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], false, ["CODE" => "REFUND_ACT"]);
            while ($ob = $res->GetNext())
            {
                if ($ob['CODE'] == 'REFUND_ACT')
                    $sellInvoicesFromAct[] = $ob['VALUE'];
            }
        }

        return ['sellInvoiceId' => $sellInvoiceId, 'actFromSellInvoice'=> $sellInvoicesFromAct];
    }

    private function getRetAct($actId = false) {
        $existingRetActInfoAr = [];
        $filter['IBLOCK_CODE'] = 'refunds_act';
        if ($actId) {
            $filter['ID'] = $actId;

            $existingReActItems = \CIBlockElement::GetList([], $filter, false, false, ['NAME', 'ID', 'PROPERTY_REFUND_ACT', 'PROPERTY_SELL_INVOICE_ID', 'PROPERTY_RETURN_INVOICES']);
            while ($arItem = $existingReActItems->fetch()) {
                $existingRetActInfoAr = $arItem;
            }
        }

        return $existingRetActInfoAr;
    }

    public function getRefundReasons() {

        \CModule::IncludeModule("iblock");
        $reasonRefund = [];

        $cache = Cache::createInstance();
        if ($cache->initCache($this->cacheTime * 10, 'getRefundReasons')) {
            $reasonRefund = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $resReasonRefund  = \CIBlockElement::GetList(
                [],
                ["IBLOCK_ID" => 159],//159- инфоблок причины возвратов
                false,
                false,
                ["NAME", "PROPERTY_REQUIRED_COMMENT", "PROPERTY_ACTIVITY_PERIOD", "XML_ID"]
            );
            while($resData = $resReasonRefund->GetNextElement()){
                $rsProp = $resData->GetFields();
                $reasonRefund[] = [
                    'NAME' => $rsProp['NAME'],
                    'ID' => $rsProp['XML_ID'],
                    'REQUIRED_COMMENT' => $rsProp['PROPERTY_REQUIRED_COMMENT_VALUE'] == 'Y' ? true : false,
                    'ACTIVITY_PERIOD' => $rsProp['PROPERTY_ACTIVITY_PERIOD_VALUE'] ?? false,
                ];
            }
            $cache->endDataCache($reasonRefund);
        }

        return $reasonRefund;
    }

    public function startExchange() {
        $res[] = include_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/crone_import_consignment.php");
        return $res;
    }

    public function changePass($oldPass, $pass, $confirmPass) {
        global  $USER;
        $res['status'] = false;
        $res['message'] = '';

        if ($oldPass != $pass) {
            $arAuthResult = $USER->Login($USER->GetLogin(), $oldPass, "Y");
            if ($arAuthResult == 1) {
                if ($pass == $confirmPass) {
                    $user = new \CUser;
                    $fields = array(
                        "PASSWORD" => $pass,
                        "CONFIRM_PASSWORD" => $confirmPass,
                    );
                    $user->Update($USER->GetID(), $fields);
                    $strError = $user->LAST_ERROR;
                    if (empty($strError)) {
                        $res['status'] = true;
                        $res['message'] = 'success';
                    } else {
                        $res['message'] = $strError;
                    }
                } else {
                    $res['message'] = 'Новые пароли не совпадают';
                }
            } else {
                $res['message'] = 'Не верный старый пароль';
            }
        } else {
            $res['message'] = 'Старый пароль не отличается от нового';
        }

        return $res;
    }

    public function cancelOrder($orderId) {
        global $USER;
        $res = [
            'status' => false,
            'message' => ''
        ];

        // Проверяем, что модуль sale доступен
        if (!\Bitrix\Main\Loader::includeModule("sale")) {
            $res['message'] = 'Модуль "sale" не подключен.';
            return $res;
        }

        // Загружаем заказ через D7 ORM
        $order = \Bitrix\Sale\Order::load($orderId);
        if (!$order) {
            $res['message'] = 'Заказ с ID ' . $orderId . ' не найден.';
            return $res;
        }

        // Проверяем принадлежность заказа текущему пользователю
        if ($order->getField("USER_ID") != $USER->GetID()) {
            $res['message'] = 'Заказ не принадлежит текущему пользователю.';
            return $res;
        }

        // Отменяем заказ:
        // Устанавливаем флаг отмены и задаём причину отмены
        $order->setField("CANCELED", "Y");
        $order->setField("REASON_CANCELED", "отменен пользователем");
        $saveResult = $order->save();
        if (!$saveResult->isSuccess()) {
            $errorMsg = implode(', ', $saveResult->getErrorMessages());
            $res['message'] = 'Ошибка при сохранении изменений заказа: ' . $errorMsg;
            return $res;
        }
        
        // Обновляем статус заказа на "отменён" (код статуса "N")
        $order->setField("STATUS_ID", "N");

        //$order->setField("UPDATED_1C", "N");
        //$order->setField("CANCELED_BY", $USER->GetID());
        $id = $order->getId();
        if (is_array($id) && count($id)) {
            $q = 'UPDATE b_sale_order SET UPDATED_1C="Y" WHERE ID="'.$id.'"';
            $DB->Query($q);
        }

        // Сохраняем изменения заказа
        $saveResult = $order->save();
        if (!$saveResult->isSuccess()) {
            $errorMsg = implode(', ', $saveResult->getErrorMessages());
            $res['message'] = 'Ошибка при сохранении изменений заказа: ' . $errorMsg;
            return $res;
        }

        $res['status'] = true;
        return $res;
    }
}
