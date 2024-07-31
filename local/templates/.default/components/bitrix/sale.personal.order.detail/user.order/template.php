<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


/** @var array $arParams */
/** @var array $arResult */

$db_props = CSaleOrderPropsValue::GetOrderProps($arResult['ID']);
while ($arProps = $db_props->Fetch()) {
    if ($arProps["NAME"] == "Доставщик Apiship") {
        if (stripos(strtolower($arProps["VALUE"]), 'dpd') !== false) {
            $deliveryType = "Курьер DPD";   
        }if (stripos(strtolower($arProps["VALUE"]), 'cdek') !== false) {
            $deliveryType = "Курьер CDEK";   
        }if (stripos(strtolower($arProps["VALUE"]), 'boxberry') !== false) {
            $deliveryType = "Курьер Boxberry";   
        }if (stripos(strtolower($arProps["VALUE"]), 'rupost') !== false) {
            $deliveryType = "Курьер Почта РФ";   
        }        
    }    
    if ($arProps["NAME"] == "Промокод") {
        $promo = $arProps["VALUE"];
    }
    if ($arProps["NAME"] == "Скидка по купону") {
        $promo_sale = $arProps["VALUE"];
    }
}

foreach($arResult["ORDER_PROPS"] as $property){
    if ($property["NAME"] == "Пункт самовывоза") {
        $delivery_pickpoint = $property["VALUE"];
    }
}
?>

<?php // ? ?>
<input type="hidden" value="<?= $arResult['ID'] ?>" class="ret_order_id">
<?php // ? ?>

<section class="profile-section profile-section-order">
    <header class="profile-order-header">
        <a href="<?= htmlspecialcharsbx($arResult["URL_TO_LIST"]) ?>#order<?=$arResult["ID"]?>" class="back-to-list-link">Вернуться к списку</a>
        <h1 class="profile-order-title">заказ №<?=htmlspecialcharsbx($arResult["ID"])?></h1>
        <span class="profile-order-date">от <?= strtolower(FormatDate("d F Y", MakeTimeStamp($arResult["DATE_INSERT_FORMATED"]))); ?></span>
        <?
        $actualStatusAr = $arResult["STATUS"];
        $statusStyle = '1'; //
        $orderCanceled = $arResult['CANCELED'] == 'Y' || $actualStatusAr['ID'] == 'CD';// CD - отменен

        if ($orderCanceled) {
            $statusStyle = '3'; // cерый
        } elseif ($actualStatusAr['ID'] == 'N') {
            $statusStyle = '2'; // желтый
        }
        ?>
        <span class="profile-user-order-status order-status-<?=$statusStyle?>"> <?= $orderCanceled ? 'Отменен' : $arResult["STATUS"]["NAME"] ?></span>
    </header>

    <div class="profile-order-details">
        <div class="profile-order-details-box">
            <div class="h3 profile-order-details-title">Способ оплаты</fiv>
            <p class="profile-order-details-text"> <?= $arResult['PAY_SYSTEM']['NAME'] ?> </p>
        </div>
        <div class="profile-order-details-box">
            <div class="h3 profile-order-details-title">Информация о доставке</div>
            <p class="profile-order-details-text"><?=$deliveryType?></p>
        </div>
        <?foreach ($arResult['ORDER_PROPS'] as $prop):?>
            <?if($prop['CODE'] != 'TED' && $prop['VALUE'] !== '-'): // TODO: что то сделать?>
                <div class="profile-order-details-box">
                        <div class="h3 profile-order-details-title"><?=$prop['NAME']?></div>
                        <p class="profile-order-details-text"><?=$prop['VALUE']?></p>
                </div>
            <?endif;?>
        <?endforeach;?>

    </div>

    <div class="profile-order-contents">
        <ul class="profile-order-items-list">
            <?php foreach ($arResult["BASKET"] as $product): ?>

                <li>
                    <div class="profile-order-item">
                        <a  href="<?= $product["DETAIL_PAGE_URL"] ?>" target="_blank" title="<?=$product["NAME"]?>">
                            <img src="<?= $product["RESIZE_PICT"]["src"]; ?>" width="100" height="150" alt="<?=$product["NAME"]?>" class="profile-order-item-img">
                        </a>
                        <div class="profile-order-desc">
                            <a  href="<?= $product["DETAIL_PAGE_URL"] ?>" target="_blank">
                                <div class="h4 profile-order-item-name"><?=$product["NAME"]?></div>
                            </a>
                            <div class="profile-order-item-params">
                                <?php if ($product["PROPS"]["CML2_ARTICLE"]): ?>
                                    <p>Арт.: <?=$product["PROPS"]["CML2_ARTICLE"]?></p>
                                <?php endif; ?>

                                <?php if ($product["PROPERTY_RUSSKIY_TSVET_VALUE"]): ?>
                                    <p>Цвет: <?=$product["PROPERTY_RUSSKIY_TSVET_VALUE"]?> </p>
                                <?php endif; ?>

                                <?php if ($product["PROPERTY_RAZMER_VALUE"]): ?>
                                    <p>Размер: <?=$product["PROPERTY_RAZMER_VALUE"]?></p>
                                <?php endif; ?>
                                <?php if ($product["QUANTITY"]): ?>
                                    <p>Количество: <?=$product["QUANTITY"]?></p>
                                <?php endif; ?>


                            </div>
                        </div>
                        <div class="profile-order-item-price">
                            <?/*<span class="old-price">3 989 ₽</span>*/?>
                            <span class="price"><?=$product['FULL_PRICE_FORMATED']?></span>
                        </div>
                    </div>
                    <?/*<div class="profile-order-return-label">
                        <p class="return-label-status">Заявка одобрена. Накладная №1902838923</p>
                    </div>*/?>
                </li>

            <?php endforeach; ?>

        </ul>
    </div>

    <table class="profile-order-details-table">
        <tr>
            <td>Сумма заказа</td>
            <td><?=$arResult['PRODUCT_SUM_FORMATED']?></td>
        </tr>
        <?/*<tr>
            <td>Скидка</td>
            <td>–1 000 ₽</td>
        </tr>*/?>
        <tr>
            <td>Доставка</td>
            <td><?= $arResult["PRICE_DELIVERY_FORMATED"] ?></td>
        </tr>
        <tr class="sum">
            <td>итого</td>
            <td><?=$arResult["PRICE_FORMATED"]?></td>
        </tr>
    </table>
    <div class="profile-order-actions">

        <?/*<a href="#" class="profile-user-order-return-link" style="display: none;">Оформить возврат</a>
        <button class="button" style="display: none;">повторить</button>*/?>
        <?//echo '<pre>'; var_dump($arResult['PAY_SYSTEM'] ); echo '</pre>';?>
        <div class="button-row">

            <?// статусы заказа: N - оформлен, ... ?>
            <?if ($arResult["STATUS"]['ID'] == "N" && $arResult['PAYED'] == 'N' && !$orderCanceled):?>

                <button class="button button-secondary" data-popup="popup-cancel-order">отменить</button>
            
            <?endif;?>
            <?// система оплаты: 6 - онлайн ?>
            <?if ($arResult["STATUS"]['ID'] == "N" && $arResult['PAYED'] == 'N' && $arResult['PAY_SYSTEM']['ID'] == '6' && !$orderCanceled):?>
                <button data-id="<?=$arResult['ID']?>" class="button js-buttonPayOrder">
                    оплатить
                </button>
            <?endif;?>
        </div>

    </div>

</section>
<?// попап Отмена ?>
<div class="popup popup-vertical popup-cancel-order">
    <div class="popup__backdrop" data-close-popup=""></div>
    <div class="popup-body">
        <button class="button-close-popup" data-close-popup=""></button>
        <div class="popup-content">

            <div class="popup-content-inner">
                <div class="h2 popup-title">Отменить заказ?</div>
                <p>Ваш заказ <u>№<?=htmlspecialcharsbx($arResult["ID"])?> от <?= strtolower(FormatDate("d F Y", MakeTimeStamp($arResult["DATE_INSERT_FORMATED"]))); ?></u> будет отменен.
                    <br>
                    Вы уверены?</p>
                <div class="form-row">
                    <button class="button form-button button-secondary" data-close-popup="">Ничего не делать</button>
                    <button data-id="<?=$arResult['ID']?>" class="button form-button js-buttonCancelOrder">
                        Да, отменить
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
