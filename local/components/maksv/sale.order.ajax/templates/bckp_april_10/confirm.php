<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

global $USER;

/**
 * @var array $arParams
 * @var array $arResult
 * @var $APPLICATION CMain
 */

if ($arParams["SET_TITLE"] == "Y")
{
    $APPLICATION->SetTitle(Loc::getMessage("SOA_ORDER_COMPLETE"));
}
$error = false;
?>

<?php if(!empty($order = $arResult["ORDER"])) : ?>
    <?php
    $orderId = $order['ID'];
    $isPaid = $order['PAYED'] === 'Y';
    $price = $order['PRICE'];
    ?>
    
    <?if(!empty($_SESSION['PAY_BONUSES']) || !empty($_SESSION['PROMO_CODE'])){
        $hlbl = 91;
        $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getById($hlbl)->fetch(); 

        $entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock); 
        $entity_data_class = $entity->getDataClass(); 

        $rsData = $entity_data_class::getList(array(
           "select" => array("ID", "UF_ORDER_ID"),
           "order" => array("ID" => "ASC"),
           "filter" => array()
        ));

        while($arData = $rsData->Fetch()){
            if(!empty($arData["UF_ORDER_ID"]))
                $orders[(int)$arData["UF_ORDER_ID"]] = 1;
        }

        if(!array_key_exists((int)$arResult["ORDER"]["ID"], $orders)){
            $data = [
                "UF_ORDER_ID" => (int)$arResult["ORDER"]["ID"],
                "UF_POINTS" => $_SESSION['PAY_BONUSES'],
                "UF_PROMO" => $_SESSION['PROMO_CODE']
            ];
            $entity_data_class::add($data);  
        }
        
        sleep(1);
    }?>

    <?php
    //собираем статистику pwa
    if($_SESSION['ispwa'] == 'Y'){
        CModule::IncludeModule("iblock");
        $el = new CIBlockElement;

        $already_exist = false;

        $arSelect = Array("ID", "IBLOCK_ID", "NAME", "PROPERTY_ORDER", "PROPERTY_UUID");
        $arFilter = Array("IBLOCK_ID" => 45, "PROPERTY_UUID" => $_COOKIE['mindboxDeviceUUID'], "PROPERTY_ORDER" => $arResult["ORDER"]["ACCOUNT_NUMBER"]);
        $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        while($ob = $res->GetNext())
        {
            $already_exist = $ob["ID"];
        }

        if(!$already_exist){
            $props = array();
            $arLoadProductArray = array(
                "IBLOCK_SECTION_ID" => false,
                "IBLOCK_ID"         => 45,
                "NAME"              => $_COOKIE['mindboxDeviceUUID'],
                "ACTIVE"            => "Y"
            );

            $PID = $el->Add($arLoadProductArray, false, false, false);

            $props['ORDER'] = $arResult["ORDER"]["ACCOUNT_NUMBER"];
            $props['SESSION'] = $_SESSION['fixed_session_id'];
            $props['UUID'] = $_COOKIE['mindboxDeviceUUID'];

            CIblockElement::SetPropertyValuesEx($PID, 45, $props);
        }
    }
    ?>

    <?php if ($arResult["ORDER"]["IS_ALLOW_PAY"] === 'Y')
        {
            if (!empty($arResult["PAYMENT"]))
            {
                foreach ($arResult["PAYMENT"] as $payment)
                {
                    if ($payment["PAID"] != 'Y')
                    {
                        if (!empty($arResult['PAY_SYSTEM_LIST'])
                            && array_key_exists($payment["PAY_SYSTEM_ID"], $arResult['PAY_SYSTEM_LIST'])
                        )
                        {
                            $arPaySystem = $arResult['PAY_SYSTEM_LIST_BY_PAYMENT_ID'][$payment["ID"]];

                            $isPodeli = strpos(strtolower($arPaySystem['NAME'] ?? ''), 'подели') !== false
                                        || strpos(strtolower($arPaySystem['NAME'] ?? ''), 'podeli') !== false;


                            // FIXME. На проде надо убрать эти 2 временных костыля, они нужны только для тестового сайта
                            $isPodeli = $payment['PAY_SYSTEM_ID'] == 11; // костыль 1
                            if($isPodeli){
                                unset($arPaySystem['ERROR']); // костыль 2
                            }


                            if (empty($arPaySystem["ERROR"]))
                            {
                                if($arPaySystem["IS_CASH"] !== 'Y'){
                                    $order = \Bitrix\Sale\Order::load($arResult["ORDER"]["ID"]);
                                    
                                    $userEmail = "";
                                    if($propertyCollection = $order->getPropertyCollection()) {
                                        if($propUserEmail = $propertyCollection->getUserEmail()) {
                                            $userEmail = $propUserEmail->getValue();
                                        }else{
                                            foreach($propertyCollection as $orderProperty) {
                                                if($orderProperty->getField('CODE') == 'EMAIL') {
                                                    $userEmail = $orderProperty->getValue();
                                                    break;
                                                }
                                            }
                                        }
                                        if($propUserName = $propertyCollection->getPayerName()){
                                            $userName = $propUserName->getValue();     
                                        }        
                                    }
                                    if(empty($userEmail) && $USER->isAuthorized()){
                                        $userEmail = $USER->GetEmail();
                                    }elseif(empty($userEmail) && !$USER->isAuthorized()){
                                        $userEmail = $_COOKIE['mindboxDeviceUUID']."@unknown.email";
                                        $userEmail_banner = "xname@clocktory.com";
                                    }
                                    
                                    if(empty($userName)){
                                        $userName = $USER->GetFullName();
                                    }         
                                    
                                    $gtag = \kb\service\Helper::getPurchaseGtag($order);

                                    $gPurchase = json_encode([        
                                        "event" => "purchase",
                                        "event_category" => "ecommerce",
                                        "event_label" => "purchase",
                                        "ecommerce" => $gtag
                                    ]);                                    
                                    
                                    foreach($gtag['items'] as $flock_p){
                                        $sum += $flock_p['price'];
                                        $floctory_prods[] = array(
                                            'id' => $flock_p['id'],
                                            'name' => $flock_p['name'],
                                            'price' => $flock_p['price'],
                                            'count' => $flock_p['quantity']
                                        );    
                                    }
                                    $sum += $gtag['shipping'];?>

                                    <script>
                                        $(document).ready(function(){
                                            window.dataLayer = window.dataLayer || [];
                                            dataLayer.push(<?=$gPurchase?>);                                            
                                            
                                            window.flocktory = window.flocktory || [];
                                            window.flocktory.push(['postcheckout', {
                                                user: {
                                                    email: '<?=$userEmail?>',
                                                    name: '<?=$userName?>'
                                                },
                                                order: {
                                                    id: '<?=$arResult["ORDER"]["ID"]?>',
                                                    price: <?=$sum?>,
                                                    items: <?=json_encode($floctory_prods, JSON_UNESCAPED_UNICODE)?>}
                                            }]);       
                                        });
                                    </script>

                                    <!--<div class="i-flocktory" data-fl-action="exchange" data-fl-user-name="<?=$USER->GetFullName()?>" data-fl-user-email="<?=$userEmail?>"></div>-->
                                    
                                    <?php
                                    $orderAccountNumber = urlencode(urlencode($arResult["ORDER"]["ID"]));
                                    $paymentAccountNumber = $payment["ID"];
                                    
                                    if($isPodeli && !empty($_SESSION['PODELI_URL'])){
                                        $sPaymentLink = $_SESSION['PODELI_URL'];    
                                    }else{
                                        $sPaymentLink = $arParams['PATH_TO_PAYMENT'] . '?ORDER_ID=' . $orderAccountNumber . '&PAYMENT_ID=' . $paymentAccountNumber;    
                                    }
                                    
                                    $sPaymentAction = ($arPaySystem['NEW_WINDOW'] === 'Y')
                                        ? 'window.open(\'' . htmlentities($sPaymentLink) . '\')'
                                        : 'location.href = \'' . htmlentities($sPaymentLink) . '\'';
                                    ?>
                                    
                                    <div class="checkout-box-success">
                                        <h1 class="checkout-success-title">заказ №<?=$orderId?> успешно оформлен!</h1>
                                        <p class="checkout-success-notice">
                                            сумма к оплате:
                                            <span><?php echo number_format($price, ceil($price) === floor($price) ? 0 : 2, '.', ' '); ?> ₽</span>
                                        </p>
                                        <!--                                        <p class="checkout-success-subnotice">Пожалуйста, выберите удобный способ оплаты.</p>-->
                                        <div class="checkout-success-buttons">
                                            <button class="button" onclick="<?=$sPaymentAction?>">
                                                <?php if($isPodeli) : ?>
                                                    Оплатить частями
                                                <?php else : ?>
                                                    Оплатить картой
                                                <?php endif; ?>
                                            </button>
                                            <!--                                        <button class="button">Оплатить по QR-коду</button>-->
                                            <!--                                        <button class="button button-sber">Оплатить <span></span></button>-->
                                            <!--                                        <button class="button button-sbp">Оплатить <span></span></button>-->
                                        </div>
                                    </div>
                                    <?php
                                }
                                else
                                {
                                    #ANALYTICS
                                    $order = \Bitrix\Sale\Order::load($arResult["ORDER"]["ID"]);
                                    
                                    $gtag = \kb\service\Helper::getPurchaseGtag($order);
                                    $vk = \kb\service\Helper::getPurchaseVK($order);
                                
                                    $userEmail = "";
                                    $userEmail_banner = "";
                                    if($propertyCollection = $order->getPropertyCollection()) {
                                        if($propUserEmail = $propertyCollection->getUserEmail()) {
                                            $userEmail = $propUserEmail->getValue();
                                        }else{
                                            foreach($propertyCollection as $orderProperty) {
                                                if($orderProperty->getField('CODE') == 'EMAIL') {
                                                    $userEmail = $orderProperty->getValue();
                                                    break;
                                                }
                                            }
                                        }
                                        if($propUserName = $propertyCollection->getPayerName()){
                                            $userName = $propUserName->getValue();     
                                        }            
                                    }
                                    $userEmail_banner = $userEmail;
                                    
                                    #IF !EMAIL
                                    if(empty($userEmail) && $USER->isAuthorized()){
                                        $userEmail = $USER->GetEmail();
                                        $userEmail_banner = $userEmail;
                                    }elseif(empty($userEmail) && !$USER->isAuthorized()){
                                        $userEmail = $_COOKIE['mindboxDeviceUUID']."@unknown.email";
                                        $userEmail_banner = "xname@clocktory.com";
                                    }
                                            
                                    if(empty($userName)){
                                        $userName = $USER->GetFullName();
                                    }?>
                                    
                                    <!--<div class="i-flocktory" data-fl-action="exchange" data-fl-user-name="<?=$USER->GetFullName()?>" data-fl-user-email="<?=$userEmail_banner?>"></div>-->
                                    
                                    <?php if ((int)$_COOKIE["order_complete"] !== $arResult["ORDER"]["ID"]) { ?>
                                        <?
                                        $gPurchase = json_encode([        
                                            "event" => "purchase",
                                            "event_category" => "ecommerce",
                                            "event_label" => "purchase",
                                            "ecommerce" => $gtag
                                        ]);                                       
                                        ?>
                                        
                                        <script>
                                            $(document).ready(function() {
                                                window.dataLayer = window.dataLayer || [];
                                                dataLayer.push(<?=$gPurchase?>);
                                                
                                                ym(24428327,'reachGoal','ecommerce__purchase');
                                                
                                                gtag('event', 'checkout', {
                                                    event_category: 'ecommerce',
                                                    event_label: '6-step_complete-off'
                                                });
                                                   
                                                ym(24428327,'reachGoal','ecommerce__checkout__6-step_complete-off');
                                                
                                                document.cookie = "order_complete=<?=$arResult["ORDER"]["ID"]?>; max-age=31536000; path=/";
                                            });
                                        </script>
                                        
                                        <?            
                                        $prods = [];
                                        $floctory_prods = [];
                                        foreach($vk['items'] as $bitm){
                                            $sum += $bitm['price'];
                                            $prods[$bitm['id']] = array(
                                                'id' => $bitm['id'],
                                                'price' => $bitm['price']
                                            );   
                                        }            
                                        foreach($gtag['items'] as $flock_p){
                                            $floctory_prods[] = array(
                                                'id' => $flock_p['id'],
                                                'name' => $flock_p['name'],
                                                'price' => $flock_p['price'],
                                                'count' => $flock_p['quantity']
                                            );    
                                        }
                                        
                                        $sum += $vk['shipping'];
                                        
                                        $vk_list = [
                                            'products' => array_values($prods),
                                            "currency_code" => "RUR",
                                            'total_price' => $sum
                                        ];
                                        
                                        $cnt = 0;
                                        $ids_ = "";
                                        foreach($prods as $pid => $prod){
                                            if($cnt == 0){
                                                $ids_ .= "'".$pid."'";    
                                            }else{
                                                $ids_ .= ","."'".$pid."'";
                                            }
                                            $cnt++;         
                                        }                
                                        ?>

                                        <script>
                                            $(document).ready(function(){
                                                var _tmr = window._tmr || (window._tmr = []);
                                                _tmr.push({"type":"reachGoal","id":3251356,"goal":"purchase"});

                                                _tmr.push({
                                                    type: 'itemView',
                                                    productid: [<?=$ids_?>],
                                                    pagetype: 'purchase',
                                                    list: 1, 
                                                    totalvalue: '<?=$sum?>'
                                                });
                                            
                                                window.flocktory = window.flocktory || [];
                                                window.flocktory.push(['postcheckout', {
                                                    user: {
                                                        email: '<?=$userEmail?>',
                                                        name: '<?=$userName?>'
                                                    },
                                                    order: {
                                                        id: '<?=$arResult["ORDER"]["ID"]?>',
                                                        price: <?=$sum?>,
                                                        items: <?=json_encode($floctory_prods, JSON_UNESCAPED_UNICODE)?>
                                                    }
                                                }]);       
                                            }); 
                                                
                                            setTimeout(function() {            
                                                VK.Retargeting.ProductEvent(132039, "purchase", <?=json_encode($vk_list)?>);
                                                VK.Goal('purchase');
                                            }, 1500);
                                        </script>            
                                    <?}
                                    #!ANALYTICS?>                                    

                                    <div class="checkout-box-success">
                                        <h1 class="checkout-success-title">заказ №<?=$orderId?> успешно оформлен!</h1>
                                        <div class="checkout-success-text">
                                            <p>Благодарим вас за покупку!</p>
                                            <p>После отправки заказа вы получите подтверждение по электронной почте и смс с трек-номером для отслеживания статуса заказа.</p>
                                            <p>Если у вас остались вопросы, свяжитесь с нами по бесплатному номеру телефона 8–800–333–87–22</p>
                                            <p>Время работы с 07:00 до 20:00 по московскому времени.</p>
                                            <p>Спасибо, что выбрали belle you!</p>
                                        </div>
                                        <div class="checkout-success-buttons">
                                            <a href="/" class="button">вернуться на главную</a>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            else
                            {
                                $error = Loc::getMessage("SOA_ORDER_PS_ERROR");
                            }
                        }
                        else
                        {
                            $error = Loc::getMessage("SOA_ORDER_PS_ERROR");
                        }
                    }
                }
            }
        }
        else
        {
            $error = $arParams['MESS_PAY_SYSTEM_PAYABLE_ERROR'];
        }
        ?>

<?php endif; ?>

<?php if($error) : ?>
    <div class="checkout-box-error">
        <svg width="40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 40 40"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M35.877 30.833l-14.433-25a1.667 1.667 0 0 0-2.887 0l-14.434 25a1.667 1.667 0 0 0 1.444 2.5h28.867a1.667 1.667 0 0 0 1.444-2.5z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.917 26.667h.167v.166h-.167v-.166zM20 15v6.667"/></svg>
        <h1 class="checkout-error-title">При обработке платежа возникла ошибка</h1>
        <p class="checkout-error-notice">Попробуйте позже или выберете другой способ оплаты</p>
        <div class="checkout-error-buttons">
            <button class="button">повторить</button>
            <button class="button button-secondary">другой способ оплаты</button>
        </div>
    </div>
<?php endif; ?>

<?
if(!empty($arResult["ORDER"]["ID"])){
    $order = \Bitrix\Sale\Order::load($arResult["ORDER"]["ID"]);

    $rr = \kb\service\Helper::getPurchaseGtag($order);
    foreach($rr['items'] as $rr_p){
        $rr_prods[] = array(
            'id' => $rr_p['id'],
            'price' => $rr_p['price'],
            'qnt' => $rr_p['quantity']
        );    
    }
    
    $userEmail = "";
    if($propertyCollection = $order->getPropertyCollection()) {
        if($propUserEmail = $propertyCollection->getUserEmail()) {
            $userEmail = $propUserEmail->getValue();
        }else{
            foreach($propertyCollection as $orderProperty) {
                if($orderProperty->getField('CODE') == 'EMAIL') {
                    $userEmail = $orderProperty->getValue();
                    break;
                }
            }
        }
    }
    if(empty($userEmail) && $USER->isAuthorized()){
        $userEmail = $USER->GetEmail();
    }elseif(empty($userEmail) && !$USER->isAuthorized()){
        $userEmail = $_COOKIE['mindboxDeviceUUID']."@unknown.email";
        $userEmail_banner = "xname@clocktory.com";
    }    
}
?>

<script>
    $(document).ready(function(){
        <?php if ((int)$_COOKIE["order_complete_all"] !== (int)$arResult["ORDER"]["ID"]) {?>
            gtag('event', 'sendForm', {
                event_category: 'interaction',
                event_label: 'order-complete'
            });
            ym(24428327,'reachGoal','interaction__sendForm__order-complete');         
            
            document.cookie = "order_complete_all=<?=$arResult["ORDER"]["ID"]?>; max-age=31536000; path=/";
            
            (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
                try {
                    <?if($USER->isAuthorized()):?>
                        rrApi.setCustomer({customerId: "<?=$USER->GetID();?>", email: "<?=$userEmail;?>"});
                    <?else:?>
                        rrApi.setProfile({email: "<?=$userEmail;?>"});
                    <?endif;?>
                    rrApi.order({"transaction": "<?=$arResult["ORDER"]["ID"]?>","items": <?=json_encode($rr_prods, JSON_UNESCAPED_UNICODE)?>});
                } catch(e) {}
            })
        <?}?>
        
        $(".sberbank__payment-link").on("click", function(){
            gtag('event', 'checkout', {
                event_category: 'ecommerce',
                event_label: '6-step_complete-on'
            });
            
            ym(24428327,'reachGoal','ecommerce__checkout__6-step_complete-on');
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        $('#payButton').length > 0 && $('#payButton').click();
    });
</script>

<style>
    .belleyou-breadcrumbs {
        display: none;
    }
</style>
