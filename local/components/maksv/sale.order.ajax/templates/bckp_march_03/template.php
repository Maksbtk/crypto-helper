<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main;
use Bitrix\Sale\Location\LocationTable;

/**
 * @var $APPLICATION
 * @var string $templateFolder
 * @var array $arParams
 * @var array $arResult
 * @var CUser $USER
 * @var SaleOrderAjax $component
 */

global $USER;

$this->addExternalCss("/local/templates/belleyou/css/page-checkout.css");
$this->addExternalCss("/local/templates/belleyou/css/page-shops.css");
$this->addExternalJs($templateFolder . '/checkout.js');
CJSCore::Init(['masked_input']);

$context = Main\Application::getInstance()->getContext();
$request = $context->getRequest();
if ((string)$request->get('ORDER_ID') !== '')
{
    include(Main\Application::getDocumentRoot().$templateFolder.'/confirm.php');
    return;
}
elseif ($arParams['DISABLE_BASKET_REDIRECT'] === 'Y' && $arResult['SHOW_EMPTY_BASKET'])
{
    include(Main\Application::getDocumentRoot().$templateFolder.'/empty.php');
    return;
}

$APPLICATION->SetAdditionalCSS('https://cdn.jsdelivr.net/npm/suggestions-jquery@latest/dist/css/suggestions.min.css', true);
//$this->addExternalJs('https://cdn.jsdelivr.net/npm/suggestions-jquery@latest/dist/js/jquery.suggestions.js');
$this->addExternalJs($templateFolder . '/suggestions.js');

$resCountries = LocationTable::getList(array(
    'filter' => array(
        '=TYPE.CODE' => 'COUNTRY',
        '=NAME.LANGUAGE_ID' => 'ru'
    ),
    'select' => array('*', 'NAME', 'TYPE')
));
$countries = [];
$countriesSupportCash = [];
while ($loc = $resCountries->fetch())
{
    if(!($loc['PARENT_ID'] ?? false)){
        $name = trim(mb_strtolower($loc['SALE_LOCATION_LOCATION_NAME_NAME']));
        if($name === 'россия' or $name === 'рф' or $name === 'российская федерация'){
            $countriesSupportCash[] = $loc['CODE'];
        }
    }

    $countries[] = [
        'id' => $loc['ID'],
        'name' => $loc['SALE_LOCATION_LOCATION_NAME_NAME'],
        'value' => $loc['CODE'],
        //'type' => $loc['CODE'] == \kb\service\Settings::RUSSIA_LOCATION_CODE ? 'dadata' : 'default'
    ];
}

$personType = '1'; // Возможно стоит вынести в настройку
$siteId = 's1'; // todo получать site id

$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.order.ajax');
?>

<pre><?php //var_dump($arResult); ?></pre>

<style>
    .radiogroup-fieldset label::before {
        top: 50%;
    }
    #apiship_description, #apiship_logo {
        display: none!important;
    }
    #apiship_closer {
        top: 6px!important;
    }
</style>

<header class="page-checkout-header">
    <a href="<?php echo $arParams['PATH_TO_BASKET']; ?>" class="back-to-basket-link">Вернуться в корзину</a>
    <h1 class="page-checkout-title">Оформление заказа</h1>
</header>

<div class="checkout-box" id="bx-belleyou-checkout-container">
    <div style="display: none">
        <input type="hidden" data-checkout="sessid" value="<?= bitrix_sessid() ?>">
        <input type="hidden" data-checkout="action-variable" value="<?=$arParams['ACTION_VARIABLE']?>">
        <input type="hidden" data-checkout="location-type" value="code">
        <input type="hidden" data-checkout="buyer-store" value="<?=$arResult['BUYER_STORE']?>">
        <input type="hidden" data-checkout="person-type" value="<?=$personType?>">
        <input type="hidden" data-checkout="site-id" value="<?=$siteId?>">
        <input type="hidden" data-checkout="signature" value="<?=CUtil::JSEscape($signedParams)?>">
    </div>
    <div class="checkout-body" name="ORDER_FORM" id="bx-soa-order-form">

        <section class="checkout-step personal-data">
            <h2 class="checkout-step-title">1. Покупатель</h2>
            <div class="form-fieldset personal-data-fieldset" data-checkout="form.personal">
                <!-- Personal data (wired by js) -->
            </div>
        </section>
        <section class="checkout-step delivery">
            <h2 class="checkout-step-title">2. Способ доставки</h2>
            <select data-checkout="form.delivery-region.countries" style="display: none">
                <?php foreach($countries as $country) : ?>
                    <option value="<?= $country['value'] ?>"><?= $country['name'] ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-fieldset delivery-city-fieldset" data-checkout="form.delivery-region">
                <div class="dropdown dropdown-contry">
                    <div class="dropdown-select">Россия</div>
                    <ul class="dropdown-box">
                        <li class="dropdown-option" data-label="1">Россия</li>
                    </ul>
                </div>
                <div class="form-row">
                    <input class="form-input" type="text" id="input32" placeholder="Населенный пункт, например Москва">
                </div>
            </div>

            <div style="display: none" data-checkout="dadata"></div>

            <div class="selectedCityError" style="text-align: center; display: none;">
                <p style="color: red;">Для данного города нет доступных служб доставки. Выберите, пожалуйста, другой город</p>
            </div>
            
            <ul class="delivery-radiogroup radiogroup-fieldset" data-checkout="form.delivery-methods">
                <!-- Delivery methods (wired by js) -->
            </ul>

            <div class="checkout-delivery-information">
                <a href="#" class="delivery-showmore-link">Дополнительная информация</a>
                <div class="checkout-desc-text">
                    <p>Сроки доставки указаны без учета дня оформления заказа. Для заказов, оформленных после 13:00 МСК, срок доставки будет увеличен на 1 день. Заказы, оформленные в выходные, будут отправлены в первый рабочий день.</p>
                    <p>Сроки доставки в Беларусь, Казахстан, Кыргызстан, Краснодар, Анапу, Геленджик, Симферополь могут быть увеличены на 5 дней.</p>
                    <p>В случае оплаты заказа при получении у транспортной компании «Почта России» взимается комиссия в размере 2% от стоимости всего заказа. Комиссия оплачивается при получении.</p>
                    <p>При выборе курьерской доставки «Почта России» оплата при получении возможна только наличными. При выборе способа доставки «Постамат СДЭК» оплата при получении возможна только картой.</p>
                </div>
            </div>

            <div id="radio-block-1" class="checkout-delivery-radioblock checkout-delivery-address" data-checkout="form.delivery-address">

                <div class="delivery-courier-check" data-checkout="form.delivery-address.services">
                    <h3 class="page-checkout-subtitle">Курьерская служба</h3>
                    <ul class="courier-radiogroup" data-checkout="form.delivery-address.services.fields">
                        <li>
                            <input class="input-radio" type="radio" id="courier1" name="courier-way" value="radio-block-1">
                            <label class="label-radio" for="courier1">
                                Boxberry (<span class="green">БЕСПЛАТНО</span> / 3-4 раб. дня)
                            </label>
                        </li>
                        <li>
                            <input class="input-radio" type="radio" id="courier2" name="courier-way" value="radio-block-2">
                            <label class="label-radio" for="courier2">
                                DPD (299 рублей / 3-4 раб. дня)
                            </label>
                        </li>
                        <li>
                            <input class="input-radio" type="radio" id="courier3" name="courier-way" value="radio-block-3">
                            <label class="label-radio" for="courier3">
                                CDEK (299 рублей / 3-4 раб. дня)
                            </label>
                        </li>
                        <li>
                            <input class="input-radio" type="radio" id="courier4" name="courier-way" value="radio-block-4">
                            <label class="label-radio" for="courier4">
                                Почта РФ (432 рубля / 1-2 раб. дня)
                            </label>
                        </li>
                    </ul>
                </div>

                <div class="delivery-address">
                    <h3 class="page-checkout-subtitle">Адрес доставки</h3>
                    <div class="form-fieldset user-address-fieldset" data-checkout="form.delivery-address.address">
<!--                        <div class="form-row">-->
<!--                            <input class="form-input" type="text" placeholder="Улица*" value="">-->
<!--                        </div>-->
<!--                        <div class="form-row short-row">-->
<!--                            <input class="form-input" type="text" placeholder="Дом*" value="">-->
<!--                        </div>-->
<!--                        <div class="form-row short-row">-->
<!--                            <input class="form-input" type="text" placeholder="Кв/офис" value="">-->
<!--                        </div>-->
<!--                        <div class="form-row short-row">-->
<!--                            <input class="form-input" type="text" placeholder="Индекс*" value="">-->
<!--                        </div>-->
                    </div>
                    <div class="save-delivery-address" data-checkout="form.delivery-address.save">
                        <input class="input-checkbox" type="checkbox" value="" id="name104" data-checkout="form.delivery-address.save.field">
                        <label class="label-checkbox" for="name104"><span>Сохранить адрес доставки</span></label>
                    </div>
                    <div class="form-row" data-checkout="form.delivery-address.comment">
                        <textarea class="form-textarea" data-checkout="form.delivery-address.comment.field" rows="5" placeholder="Комментарии для курьера"></textarea>
                    </div>
                </div>
            </div>

            <div id="radio-block-2" class="checkout-delivery-radioblock checkout-delivery-selfservice" data-checkout="form.delivery-pvz">
                <h3 class="page-checkout-subtitle">пункт самовывоза</h3>
                <button data-checkout="form.delivery-pvz.button"
                        class="button button-secondary delivery-choose-selfservice"
                        onclick="IPOLapiship_pvz.selectPVZ();return false;">
                    выбрать пункт самовывоза
                </button>

                <?php //$APPLICATION->IncludeComponent('ipol:ipol.apiship2vPickup', 'order', []) ?>

                <div class="delivery-selfservice-selected" data-checkout="form.delivery-pvz.selected">
                    <button class="delivery-edit-selfservice" data-checkout="form.delivery-pvz.selected.clear"></button>
                    <h4 class="delivery-selfservice-name" data-checkout="form.delivery-pvz.selected.name">
                        <?php /* Пункт выдачи DPD */ ?>
                    </h4>
                    <p class="delivery-selfservice-address" data-checkout="form.delivery-pvz.selected.address">
                        <?php /* Москва, ул. Речников, д. 9 */ ?>
                    </p>
                    <p class="delivery-selfservice-term" data-checkout="form.delivery-pvz.selected.term">
                        <?php /* 4 рабочих дня */ ?>
                    </p>
                </div>
            </div>

            <div id="radio-block-4" class="checkout-delivery-radioblock checkout-delivery-shop" data-checkout="form.delivery-branch">
                <h3 class="page-checkout-subtitle">Магазин</h3>
                <button data-checkout="form.delivery-branch.button"
                        class="button button-secondary delivery-choose-shop">
                    выбрать магазин
                </button>
                <div class="delivery-shop-selected" data-checkout="form.delivery-branch.selected">
                    <button class="delivery-edit-shop" data-checkout="form.delivery-branch.selected.edit"></button>
                    <h4 class="delivery-shop-name" data-checkout="form.delivery-branch.selected.name">
                        <?php /* ТРЦ «ВЕЕР МОЛЛ» */ ?>
                    </h4>
                    <p class="delivery-shop-address" data-checkout="form.delivery-branch.selected.address">
                        <?php /* г. Екатеринбург, ул. Космонавтов, д. 108 */ ?>
                    </p>
                    <p class="delivery-shop-term" data-checkout="form.delivery-branch.selected.term">
                        <?php /* 1 рабочий день */ ?>
                    </p>
                </div>
            </div>

            </fieldset>
        </section>
        <section class="checkout-step payment-data">
            <h2 class="checkout-step-title">3. Способ оплаты</h2>
            <ul class="payment-radiogroup radiogroup-fieldset" data-checkout="form.pay-systems">
                <!-- Payment systems (wired by js) -->
            </ul>

            <div id="pay-3" class="checkout-payment-radioblock payment-podeli" data-checkout="podeli" style="display:none;">
                <div class="podeli-total" data-checkout="podeli.total"></div>
                <div class="podeli-logo"></div>
                <ul class="podeli-payments" data-checkout="podeli.payments">
<!--                    <li class="podeli-payment-item">-->
<!--                        <span class="podeli-payment-status"></span>-->
<!--                        <span class="podeli-payment-date">Сегодня</span>-->
<!--                        <span class="podeli-payment-sum">3 564 ₽</span>-->
<!--                    </li>-->
<!--                    <li class="podeli-payment-item">-->
<!--                        <span class="podeli-payment-status"></span>-->
<!--                        <span class="podeli-payment-date">10 сентября</span>-->
<!--                        <span class="podeli-payment-sum">3 564 ₽</span>-->
<!--                    </li>-->
<!--                    <li class="podeli-payment-item">-->
<!--                        <span class="podeli-payment-status"></span>-->
<!--                        <span class="podeli-payment-date">17 сентября</span>-->
<!--                        <span class="podeli-payment-sum">3 564 ₽</span>-->
<!--                    </li>-->
<!--                    <li class="podeli-payment-item">-->
<!--                        <span class="podeli-payment-status"></span>-->
<!--                        <span class="podeli-payment-date">24 сентября</span>-->
<!--                        <span class="podeli-payment-sum">3 564 ₽</span>-->
<!--                    </li>-->
                </ul>
                <p class="podeli-notice">Без комиссий и переплат <span class="podeli-payment-info" data-popup="popup-product-podeli"></span></p>
            </div>

            <div class="checkout-desc-text">
                <p>При оплате картами Visa, Mastercard и МИР, выпущенных российскими банками, рекомендуем использовать ручной ввод данных банковской карты на сайте либо воспользоваться сервисом SberPay.</p>
                <p>Оплата картами Visa и Mastercard зарубежных банков недоступна.</p>
                <p>Обращаем ваше внимание, что при использовании VPN сервисов могут возникнуть сложности с оплатой покупок.</p>
            </div>
        </section>

    </div>

    <aside class="checkout-sidebar">
        <div class="checkout-sidebar-sticky">
            <div class="checkout-items-list">
                <h3 class="checkout-items-title">товары</h3>
                <a href="<?php echo $arParams['PATH_TO_BASKET']; ?>" class="checkput-items-edit">Редактировать</a>
                <ul class="checkout-items-preview" data-checkout="cart.products">
                    <!-- Cart items (wired by js) -->
                </ul>
            </div>
            <?php
            /*
            <div class="checkout-discounts-box">
                <div id="promo1" class="checkout-discounts-item" data-target="promocode">
                    <button class="checkout-discount-button" data-popup="popup-basket-promocode">Промокод</button>
                    <p class="checkout-discount-applied promocode-applied" style="display: none;">Промокод на скидку 500 ₽ применен <button class="button-delete-discount" data-discount-delete="promocode"></button></p>
                </div>
                <div id="cert1" class="checkout-discounts-item" data-target="certificate">
                    <button class="checkout-discount-button" data-popup="popup-basket-certificate">Сертификат</button>
                    <p class="checkout-discount-applied certificate1-applied" style="display: none;">Сертификат SSS000 применен <button class="button-delete-discount" data-discount-delete="certificate"></button></p>
                    <p class="checkout-discount-applied certificate1-applied" style="display: none;">Сертификат SSS000 применен <button class="button-delete-discount" data-discount-delete="certificate"></button></p>
                </div>
                <div id="bal1" class="checkout-discounts-item" data-target="balance">
                    <button class="checkout-discount-button checkout-balance-button" data-popup="popup-basket-balance">Списание баллов</button>
                    <button class="checkout-discount-button checkout-balance-button-na" data-popup="popup-basket-balance-na">Списание баллов</button>
                    <p class="checkout-discount-applied balance-applied" style="display: none;">Будет списано 300 баллов <button class="button-delete-discount"></button></p>
                </div>

                <!-- состояние когда применены другие скидки и баллы не доступны -->
                <!--div class="checkout-discounts-item">
                  <button class="checkout-discount-button _not-available" data-popup="popup-checkout-balance-na">Списание баллов</button>
                </div-->
            </div>
             * */
            ?>

            <h3 class="checkout-summary-title">Ваш заказ</h3>

            <table class="checkout-order-details" data-checkout="totals">
                <tr data-checkout="totals.subtotal">
                    <td>Сумма заказа</td>
                    <td><span class="summary-full" data-checkout="indicator.totals.subtotal"></span></td>
                </tr>
                <tr data-checkout="totals.discount">
                    <td>Скидка</td>
                    <td>– <span class="summary-discount" data-checkout="indicator.totals.discount"></span></td>
                </tr>
                <!--tr>
                  <td>Промокод</td>
                  <td>– <span class="summary-discount">0</span> ₽</td>
                </tr>
                <tr>
                  <td>Сертификат</td>
                  <td>– <span class="summary-discount">0</span> ₽</td>
                </tr>
                <tr>
                  <td>Баллы</td>
                  <td>– <span class="summary-discount">0</span> ₽</td>
                </tr-->
                <tr data-checkout="totals.delivery">
                    <td>Доставка</td>
                    <td data-checkout="indicator.totals.delivery"></td>
                </tr>
                <tr class="sum" data-checkout="totals.total">
                    <td>итого</td>
                    <td data-checkout="indicator.totals.total"></td>
                </tr>
            </table>

            <a class="button button-checkout" data-checkout="checkout-button" href="javascript:void(0);">Оформить заказ</a>
            
            <p style="margin-top: 20px;">Мы запустили обновленный сайт belle you. По техническим причинам в данный момент нельзя оплатить заказ подарочным сертификатом. При возникновении сложностей с оформлением заказа, пожалуйста, свяжитесь со службой поддержки <a href="https://t.me/belleyoubot" target="_blank">https://t.me/belleyoubot</a></p>
        </div>
    </aside>
</div>

<div style="display: none">
    <?php
    // we need to have all styles for sale.location.selector.steps, but RestartBuffer() cuts off document head with styles in it
//    $APPLICATION->IncludeComponent(
//        'bitrix:sale.location.selector.steps',
//        '.default',
//        array(),
//        false
//    );
    $APPLICATION->IncludeComponent(
        'bitrix:sale.location.selector.search',
        '.default',
        array(),
        false
    );
    ?>
</div>

<script>
    'use strict';

    (function(){
        BX.ready(() => {
            BX.Sale.Checkout
                .instance('bx-belleyou-checkout-container', {
                    storage: 'local',
                    storageKey: 'BX_SALE_ORDER_AJAX_CHECKOUT_DATA',
                    userHash: '<?= hash('ripemd128', intval($USER ? $USER->GetID() : 0)) ?>',

                    preloaderElementSelector: '.preloader-body',

                    dadataToken: '4b38696fcbf74680a2e881705d34cfd4a4cffb54',

                    podeliPaymentsCount: 4,
                    podeliPaymentsInterval: 14, // 14 days

                    addressFields: {
                        '25': BX.Sale.Checkout.ADDRESS_MODE_DELIVERY,
                        '27': BX.Sale.Checkout.ADDRESS_MODE_DELIVERY,
                        '26': BX.Sale.Checkout.ADDRESS_MODE_PVZ,
                        '28': BX.Sale.Checkout.ADDRESS_MODE_PVZ,
                        '5': BX.Sale.Checkout.ADDRESS_MODE_NO_DELIVERY,
                        '3': BX.Sale.Checkout.ADDRESS_MODE_BRANCH,
                    },
                    cashForCountries: <?= CUtil::PhpToJSObject($countriesSupportCash) ?>,
                    branchPropCode: 'pickup',

                    phoneMasks: { <?php /* https://gist.github.com/mikemunsie/d58d88cad0281e4b187b0effced769b2 */ ?>
                        '0000028023': {
                            mask: '+7 (999) 999-99-99',
                            prefix: '7',
                        },
                        '0000000001': {
                            mask: '+375 (99) 999-99-99',
                            prefix: '375',
                        },
                        '0000000276': {
                            mask: '+7 (999) 999-99-99',
                            prefix: '7',
                        },
                        '0000028022': {
                            mask: '+996 (999) 999-999',
                            prefix: '996',
                        },
                    },

                    certificate_ids: [42162, 42159, 42156, 42153],
                })
                .firstRender(<?=CUtil::PhpToJSObject($arResult['JS_DATA'])?>, true)
        })
    })();
</script>

<?$prods = [];
foreach($arResult['BASKET_ITEMS'] as $bitm){
    $sum += $bitm['SUM_NUM'];
    $prods[$bitm['PRODUCT_ID']] = array(
        'id' => $bitm['PRODUCT_ID'],
        'price' => $bitm['SUM_NUM']
    );    
}

$vk_list = [
    'products' => array_values($prods),
    'total_price' => $sum
];   
?> 

<?php if ($vk_list): ?>
    <script>
        BX.ready(function() {
            /*$('._step-forward-1').on('click', function() {
                gtag('event', 'checkout', {
                    event_category: 'ecommerce',
                    event_label: '2-step_item-order'
                });
                ym(251472467,'reachGoal','ecommerce__checkout__2-step_item-order');
            });
            $('._step-forward-2').on('click', function() {
                gtag('event', 'checkout', {
                    event_category: 'ecommerce',
                    event_label: '3-step_geo'
                });
                ym(251472469,'reachGoal','ecommerce__checkout__3-step_geo');
            });
            $('._step-forward-4').on('click', function() {
                gtag('event', 'checkout', {
                    event_category: 'ecommerce',
                    event_label: '3-step_deliv'
                });
                ym(251472473,'reachGoal','ecommerce__checkout__3-step_deliv');
            });
            
            $('._step-forward-3').on('click', function() {
                if($("#ID_PAY_SYSTEM_ID_2").is(':checked')){
                    gtag('event', 'checkout', {
                        event_category: 'ecommerce',
                        event_label: '4-step_pay-meth-on'
                    });
                    ym(251472567,'reachGoal','ecommerce__checkout__4-step_pay-meth-on');                        
                }
                if($("#ID_PAY_SYSTEM_ID_6").is(':checked')){
                    gtag('event', 'checkout', {
                        event_category: 'ecommerce',
                        event_label: '4-step_pay-meth-off'
                    });
                    ym(251472617,'reachGoal','ecommerce__checkout__4-step_pay-meth-off');                        
                }
            });

            $('._step-save').on('click', function() {
                gtag('event', 'checkout', {
                    event_category: 'ecommerce',
                    event_label: '5-step_buyer'
                });
                ym(251472654,'reachGoal','ecommerce__checkout__5-step_buyer');
            });*/
        });
        
        setTimeout(function() {
            VK.Retargeting.ProductEvent(132039, "init_checkout", <?=json_encode($vk_list)?>);

            VK.Retargeting.Event('initiate_checkout');
            VK.Retargeting.Add(49531488);      
            VK.Goal('initiate_checkout');
            
            var _tmr = window._tmr || (window._tmr = []);
            _tmr.push({"type":"reachGoal","id":3251356,"goal":"initiate_checkout"});

        }, 2000);          
    </script> 
<?php endif ?>

<?#GTAG
foreach($arResult['BASKET_ITEMS'] as $cartProd){
    $needed_ID[] = $cartProd['PRODUCT_ID'];
    
    $tpData[$cartProd['PRODUCT_ID']]['QUANTITY'] = $cartProd['QUANTITY'];    
    $tpData[$cartProd['PRODUCT_ID']]['PRICE'] = $cartProd['PRICE'];    
}

if(!empty($needed_ID)){
    $requiredTP = [];
    $dbItemsXmlFoReq = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 3, "ID" => $needed_ID), false, false, array('ID','SECTION_ID','PROPERTY_CML2_LINK','PROPERTY_SAYT_NAIMENOVANIE_DLYA_SAYTA'));
    while ($item = $dbItemsXmlFoReq->Fetch()) {
        $requiredTP[] = $item['PROPERTY_CML2_LINK_VALUE'];
        
        $requiredTPData[$item['PROPERTY_CML2_LINK_VALUE']]['QUANTITY'] = $tpData[$item['ID']]['QUANTITY'];
        $requiredTPData[$item['PROPERTY_CML2_LINK_VALUE']]['PRICE'] = $tpData[$item['ID']]['PRICE'];
    }

    $requiredProds = [];
    $dbItemsXmlFoReq = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 2, "ID" => $requiredTP), false, false, array('ID','IBLOCK_SECTION_ID','NAME','PROPERTY_SAYT_NAIMENOVANIE_DLYA_SAYTA'));
    while ($item = $dbItemsXmlFoReq->Fetch()) {
        $requiredProds[] = $item;
        $sections[] = $item['IBLOCK_SECTION_ID'];
    }

    $SectList = CIBlockSection::GetList(array(), array("IBLOCK_ID" => 2,"ID" => $sections) ,false, array("ID","NAME"));
    while ($SectListGet = $SectList->GetNext())
    {
        $sectionsList[$SectListGet['ID']] = $SectListGet['NAME'];
    }
}  

if(!empty($requiredProds)){    
    foreach($requiredProds as $prod){
        $google_content[] = [
            'item_id' => $prod['ID'],
            'item_name' => $prod['PROPERTY_SAYT_NAIMENOVANIE_DLYA_SAYTA_VALUE'],
            'item_brand' => 'belle you',
            'price' => $requiredTPData[$prod['ID']]['PRICE'],
            'item_category' => $sectionsList[$prod['IBLOCK_SECTION_ID']], 
            'item_variant' => $prod['NAME'],
            'currency' => 'RUB',
            'quantity' => $requiredTPData[$prod['ID']]['QUANTITY'],
        ];
    }

    $gPurchaseBegin = json_encode([
        "event" => "begin_checkout",
        "event_category" => "ecommerce",
        "event_label" => "begin_checkout",
        "ecommerce" => ["items" =>  $google_content ]
    ]);
}                                     
?>

<script>
    BX.ready(function() {
        gtag(<?=$gPurchaseBegin?>);
    });
</script>        