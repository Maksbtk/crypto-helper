<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<?php if (empty($arResult["ORDERS"])): ?>
    <section class="profile-section profile-section-orders">

        <div class="profile-user-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" fill="none" viewBox="0 0 48 48"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" d="M36.557 16H11.443c-.998 0-1.85.707-2.013 1.671l-3.402 20C5.821 38.891 6.78 40 8.041 40H39.96c1.26 0 2.22-1.11 2.013-2.329l-3.402-20C38.406 16.707 37.555 16 36.557 16zM16 16a8 8 0 1 1 16 0"/></svg>
            <div class="h2">У вас пока нет заказов</div>
            <p>Перейдите в каталог, чтобы совершить свои первые покупки</p>
            <a href="/catalog/" class="button">перейти в каталог</a>
        </div>

    </section>
<?php else: ?>
    <section class="profile-section profile-section-orders">
        <div class="profile-user-orders-list">
            <?foreach ($arResult["ORDERS"] as $keyOrder => $order):?>

                <div class="profile-user-order" id="order<?=$order['ORDER']['ID']?>">
                    <div class="h3 profile-user-order-name">
                        <a href="<?=$arParams['CURRENT_PAGE']?>order.php?ID=<?= $order['ORDER']['ID'] ?>">
                            №<span class="order-number"><?= $order['ORDER']["ACCOUNT_NUMBER"] ?></span> от <span class="order-date"><?= $order['ORDER']["DATE_INSERT_FORMATED"]; ?></span>
                        </a>
                    </div>
                    <?
                    $actualStatusAr = $arResult["INFO"]["STATUS"][$order['ORDER']["STATUS_ID"]];
                    $statusStyle = '1'; //
                    $orderCanceled = $order['ORDER']['CANCELED'] == 'Y' || $actualStatusAr['ID'] == 'CD';// CD - отменен

                    if ($orderCanceled) {
                        $statusStyle = '3'; // cерый
                    } elseif ($actualStatusAr['ID'] == 'N') {
                        $statusStyle = '2'; // желтый
                    }
                    ?>
                    <p class="profile-user-order-status order-status-<?=$statusStyle?>">
                        <?= $orderCanceled ? 'Отменен' : $actualStatusAr["NAME"] ?>
                    </p>
                    <p class="profile-user-order-summary">
                        <?$prodCount = count($order['BASKET_ITEMS']);?>
                        <?=num_word($prodCount, array('товар', 'товара', 'товаров'))?> на <?= $order['ORDER']["FORMATED_PRICE"] ?>
                    </p>

                    <?//if (count($order['BASKET_ITEMS']) <= 4):?>
                        <ul class="order-items-preview">
                            <?php foreach ($order['BASKET_ITEMS'] as $basketItem): ?>
                                <li class="order-item-preview">
                                    <a href="<?=$basketItem['DETAIL_PAGE_URL']?>" title="<?= $basketItem['NAME'] ?>">
                                        <img width="100" alt="<?= $basketItem['NAME'] ?>" src="<?= $arResult['BASKET_PICTURES_FOR_ORDERS'][$order['ORDER']['ID']][$basketItem['PRODUCT_ID']] ?>" height="150">
                                    </a>
                                </li>
                            <?endforeach;?>
                        </ul>
                    <?//endif;?>

                    <?/*<div class="profile-user-order-info">
                        <?php if ($order['ORDER']['STATUS_ID'] === 'F' && $order['ORDER']['SUCCESS_MORE_7DAYS'] != 'Y'): ?>
                            <a href="<?=$arParams['CURRENT_PAGE']?>order.php?ID=<?= $order['ORDER']['ID'] ?>" class="profile-user-order-return-link">Оформить возврат</a>
                        <?php endif ?>
                    </div>*/?>

                    <div class="profile-user-order-options">
                        <?// статусы заказа: N - оформлен, ... ?>
                        <?if ($order['ORDER']['STATUS_ID'] == "N" && $order['ORDER']['PAYED'] == 'N' && !$orderCanceled):?>
                            <button class="button button-secondary js-openCancelPopUp" data-id="<?=$order['ORDER']["ID"]?>" <?/*data-popup="popup-cancel-order"*/?>>отменить</button>
                        <?endif;?>
                        <?// система оплаты: 6 - онлайн ?>
                        <?if ($order['ORDER']['STATUS_ID'] == "N" && $order['ORDER']['PAYED'] == 'N' && $order['ORDER']['PAY_SYSTEM_ID'] == '6' && !$orderCanceled):?>
                            <button data-id="<?=$order['ORDER']['ID']?>" class="button js-buttonPayOrder">
                                оплатить
                            </button>
                        <?endif;?>
                    </div>


                </div>

            <?endforeach;?>

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
                    <p>Ваш заказ № <u id="popUpOrderId"></u> будет отменен.
                        <br>
                        Вы уверены?</p>
                    <div class="form-row">
                        <button class="button form-button button-secondary" data-close-popup="">Ничего не делать</button>
                        <button data-id="" class="button form-button js-buttonCancelOrder">
                            Да, отменить
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
<?php endif ?>
