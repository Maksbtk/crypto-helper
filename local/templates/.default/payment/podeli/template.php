<?php
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$sum = round($params['amount'], 2);
?>

<div class="mb-4" >
    <p><?php echo Loc::getMessage('PODELI.PAYMENT_TEMPLATE_DESCRIPTION') . " " . SaleFormatCurrency($sum, 'RUB'); ?></p>
    <div class="d-flex align-items-center mb-3">
        <div class="col-auto pl-0">
            <a class="btn btn-lg btn-success" style="border-radius: 32px;" href="<?php echo $params['link']; ?>"><?php echo Loc::getMessage('PODELI.PAYMENT_TEMPLATE_BUTTON_PAID'); ?></a>
        </div>
        <div class="col pr-0"><?php echo Loc::getMessage('PODELI.PAYMENT_TEMPLATE_REDIRECT'); ?></div>
    </div>

    <p><?php echo Loc::getMessage('PODELI.PAYMENT_TEMPLATE_WARNING_RETURN'); ?></p>
</div>
