<?php

$orderId = $_GET['ORDER_ID'];
$order = \Bitrix\Sale\Order::load($orderId);

if (!$order)
    LocalRedirect('/cart/', false, '301 Moved permanently');

/** @var Payment $payment */
$payment = $order->getPaymentCollection()->current();
$paymentFields = $payment->getFields();

?>
<div class="checkout-box-success">
    <?if ($paymentFields['PAID'] == 'N'):?>
        <?php
        $service = \Bitrix\Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
        if ($service) {
            $context = \Bitrix\Main\Application::getInstance()->getContext();

            $result = $service->initiatePay(
                $payment,
                $context->getRequest(),
                \Bitrix\Sale\PaySystem\BaseServiceHandler::STRING
            );

            if ($result->isSuccess()) {
                //echo '<pre>'; var_dump($result->getPaymentUrl()); echo '</pre>';
                //$result->getErrors()
                $payLink = $result->getPaymentUrl() ?? false;
                if ($payLink) {
                header('Location: ' . $payLink);
                } else {
                    echo('
                    <div class="checkout-box-error">
        <svg width="40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 40 40"><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M35.877 30.833l-14.433-25a1.667 1.667 0 0 0-2.887 0l-14.434 25a1.667 1.667 0 0 0 1.444 2.5h28.867a1.667 1.667 0 0 0 1.444-2.5z"/><path stroke="#1F2020" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.917 26.667h.167v.166h-.167v-.166zM20 15v6.667"/></svg>
        <h1 class="checkout-error-title">При обработке платежа возникла ошибка</h1>
        <p class="checkout-error-notice">Попробуйте позже или выберете другой способ оплаты</p>
        <div class="checkout-error-buttons">
            
        </div>
    </div>'
                    );
                }
            }
        }
        ?>

    <?elseif ($paymentFields['PAID'] == 'Y'):?>
        <h1 class="checkout-success-title">Благодарим вас за покупку!</h1>
        <div class="checkout-success-text">
            <p>Заказ №<?=$orderId?> успешно оформлен!</p>
            <p>После отправки заказа вы получите подтверждение по электронной почте информацию о подписке</p>
            <p>Если у вас остались вопросы, свяжитесь с нами <a href="/contacts/">через станицу обратной связи</a></p>
            <p>Спасибо, что выбрали Crypto Helper!</p>
        </div>
        <div class="checkout-success-buttons">
            <a href="/user/bybitSignals/" class="button">перейти к сигналам</a>
        </div>
    <?endif;?>
</div>


