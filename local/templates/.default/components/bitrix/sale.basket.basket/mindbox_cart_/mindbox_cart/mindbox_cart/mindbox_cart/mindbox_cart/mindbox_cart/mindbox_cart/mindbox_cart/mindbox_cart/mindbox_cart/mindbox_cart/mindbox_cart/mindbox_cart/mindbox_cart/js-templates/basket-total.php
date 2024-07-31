<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 */
 
global $USER;
?>
<script id="basket-total-template" type="text/html">
    <?
    if ($arParams['HIDE_COUPON'] !== 'Y')
    {?>
        <div class="basket-discounts-box">
            <!--
            <div id="cert1" class="basket-discounts-item" data-target="certificate">
                <button class="basket-discount-button" data-popup="popup-basket-certificate">Сертификат</button>  
                <p class="basket-discount-applied certificate1-applied" style="display: none;">Сертификат SSS000 применен <button class="button-delete-discount" data-discount-delete="certificate"></button></p>  
                <p class="basket-discount-applied certificate1-applied" style="display: none;">Сертификат SSS000 применен <button class="button-delete-discount" data-discount-delete="certificate"></button></p>              
            </div>-->
            <?if($_SESSION["UF_PL_MEMBER"] == "Y" && $USER->isAuthorized()){?>
                <div id="promo1" class="basket-discounts-item" data-target="promocode">
                    <button class="basket-discount-button" data-popup="popup-basket-promocode">Промокод</button>
                    
                    <p class="basket-discount-applied promocode-applied" style="display: none;">Промокод на скидку 500 ₽ применен <button class="button-delete-discount" data-discount-delete="promocode"></button></p>
                </div>
                <?if($_SESSION['ORDER_AVAILABLE_BONUSES']){?>            
                    <div id="bal1" class="basket-discounts-item" data-target="balance">
                        <button class="basket-discount-button basket-balance-button" data-popup="popup-basket-balance">Списание баллов</button>
                        <button class="basket-discount-button basket-balance-button-na" data-popup="popup-basket-balance-na">Списание баллов</button>
                        
                        <?if($_SESSION['PAY_BONUSES'] > 0){?>
                            <p class="basket-discount-applied balance-applied">Будет списано <?=$_SESSION['PAY_BONUSES'] > 0 ? $_SESSION['PAY_BONUSES'] : ''?> баллов <button class="button-delete-discount" style="display: none"></button></p>
                        <?}?>
                    </div>
                <?}?>                
            <?}else{?>
                <p><a href="/user/profile/">Войдите</a> или <a href="/user/profile/?register=yes">зарегистрируйтесь</a>, чтобы применить промокод или получить баллы за покупку.</p>
            <?}?>
        </div>
        
        {{#GET_BONUSES}}
            <p class="basket-checkout-points-info">За данный заказ на ваш бонусный счет начислится {{{GET_BONUSES}}} баллов</p>
        {{/GET_BONUSES}}        
    <?}?>
    
    <h2 class="basket-summary-title">Ваш заказ</h2>
    <!--<div class="free-delivery-progress-bar">
        <span class="progress" style="width: {{{FREE_DELIVERY_PERCENTS}}}%;"></span>
        <span class="progress-label">{{{FREE_DELIVERY_TEXT}}}</span>
    </div>-->
    <table class="basket-order-details">
        {{#PRICE_WITHOUT_DISCOUNT_FORMATED}}
        <tr>
            <td>Сумма заказа</td>
            <td><span class="summary-full">{{{PRICE_WITHOUT_DISCOUNT_FORMATED}}}</span></td>
        </tr>
        {{/PRICE_WITHOUT_DISCOUNT_FORMATED}}
        <!--<tr>
            <td>Сертификат</td>
            <td>– <span class="summary-discount">0</span> ₽</td>
        </tr>-->
        {{#PAY_BONUSES}}
        <tr>
            <td>Баллы</td>
            <td>– <span class="summary-discount">{{{PAY_BONUSES}}}</span> ₽</td>
        </tr>
        {{/PAY_BONUSES}}
        {{#DISCOUNT_PRICE_FORMATED}}
        <tr>
            <td>Скидка всего</td>
            <td>– <span class="summary-discount">{{{DISCOUNT_PRICE_FORMATED}}}</span></td>
        </tr>
        {{/DISCOUNT_PRICE_FORMATED}}
        {{#PRICE_FORMATED}}
        <tr class="sum">
            <td>итого</td>
            <td><span class="summary-final" data-entity="basket-total-price">{{{PRICE_FORMATED}}}</span></td>
        </tr>
        {{/PRICE_FORMATED}}
    </table>

    <a class="button button-checkout{{#DISABLE_CHECKOUT}} disabled{{/DISABLE_CHECKOUT}}" href="/checkout/">перейти к оформлению</a>

    <p style="margin-top: 20px;">Мы запустили обновленный сайт belle you. По техническим причинам в данный момент нельзя оплатить заказ подарочным сертификатом. При возникновении сложностей с оформлением заказа, пожалуйста, свяжитесь со службой поддержки <a href="https://t.me/belleyoubot" target="_blank">https://t.me/belleyoubot</a></p>

    <?//beauty box notification?>
    <p style="margin-top: 20px; display: none" id="beautyBoxBlock">
        Вам доступен ПОДАРОЧНЫЙ НАБОР «BEAUTY BOX». Вы можете добавить его в корзину.
        <a class="basket-order-details addBoxButton" href="javascript:void();">Добавить</a>
    </p>    
    
    <!--<p style="margin-top: 20px; display: none;" id="beautyBoxBlock">
        Вам доступен ПОДАРОЧНЫЙ НАБОР «BEAUTY BOX». Вы можете добавить его в корзину.
        <a class="basket-order-details" id="addBeautyBox" href="javascript:void();">Добавить</a>
    </p>-->
    
	<!--<div class="basket-checkout-container" data-entity="basket-checkout-aligner">
		<?
		if ($arParams['HIDE_COUPON'] !== 'Y')
		{
			?>
			<div class="basket-coupon-section">
				<div class="basket-coupon-block-field">
					<div class="basket-coupon-block-field-description">
						<?=Loc::getMessage('SBB_COUPON_ENTER')?>:
					</div>
					<div class="form">
						<div class="form-group" style="position: relative;">
							<input type="text" class="form-control" id="" placeholder="" data-entity="basket-coupon-input">
							<span class="basket-coupon-block-coupon-btn"></span>
						</div>
					</div>
				</div>
			</div>
			<?
		}

		if ($arParams['HIDE_COUPON'] !== 'Y')
		{
		?>
			<div class="basket-coupon-alert-section">
				<div class="basket-coupon-alert-inner">
					{{#COUPON_LIST}}
					<div class="basket-coupon-alert text-{{CLASS}}">
						<span class="basket-coupon-text">
							<strong>{{COUPON}}</strong> - <?=Loc::getMessage('SBB_COUPON')?> {{JS_CHECK_CODE}}
							{{#DISCOUNT_NAME}}({{DISCOUNT_NAME}}){{/DISCOUNT_NAME}}
						</span>
						<span class="close-link" data-entity="basket-coupon-delete" data-coupon="{{COUPON}}">
							<?=Loc::getMessage('SBB_DELETE')?>
						</span>
					</div>
					{{/COUPON_LIST}}
				</div>
			</div>
			<?
		}
		?>
	</div>-->
</script>