<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var string $templateFolder
 * @var string $templateName
 * @var CMain $APPLICATION
 * @var CBitrixBasketComponent $component
 */
?>

<?foreach ($arResult['GRID']['ROWS'] as $basketItem){?>
    <div class="popup-product-item">
        <img src="<?=$basketItem['DETAIL_PICTURE_SRC']?>" alt="<?=explode("(",$basketItem['NAME'])[0]?>" class="popup-product-item-img" width="100" height="150">
        <div class="popup-product-item-desc">
            <div class="h4 popup-product-item-name"><?=explode("(",$basketItem['NAME'])[0]?></div>
            <div class="popup-product-item-params">
                <p>Арт.: <?=$basketItem['PROPERTY_CML2_ARTICLE_VALUE']?></p>
                <p>Цвет: <?=$basketItem['PROPS_ALL']['_COLOR']['VALUE']?> </p>
                <p>Размер: <?=$basketItem['PROPS_ALL']['_SIZE']['VALUE']?></p>
            </div>            
            <div class="popup-product-item-price">
                <span class="price"><?=$basketItem['FULL_PRICE_FORMATED']?> x <?=$basketItem['QUANTITY']?></span>
                <!--<span class="old-price">3 989 ₽</span>-->
            </div>
        </div>
    </div>
<?}?>

<style type="text/css">
    .popup-add-to-basket .popup-content-inner {
        margin: 0 -40px;
        width: auto;
    }

    .popup-product-added-items {
        padding: 0 40px 150px; /*отступ снизу для прокрутки*/
    }
    .popup-product-item {
        position: relative;
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;

        padding-top:20px;
        border-top: 1px solid var(--bg-color-main);
        margin-bottom: 20px;

        font-size: 12px;
        line-height: 15.5px;
    }
    .popup-product-item:first-of-type {
        border-top: none;
        padding-top: 0;
    }
    .popup-product-item-img {
        width: 100px;
        height: 150px;
        margin-right: 10px;
    }
    .popup-product-item-desc {
        display: flex;
        flex-direction: column;
    }
    .popup-product-item-name {
        margin: 0 0 5px;
        font-size: 12px;
        line-height: 15.5px;
        text-transform: uppercase;
    }
    .popup-product-item-params p{
        margin: 0;
        opacity: 0.7;
    }
    .popup-product-item-price {
        margin-top: auto;
        font-family: var(--font-family-header);
        letter-spacing: 0.02em;
        font-weight: 500;
    }
    .popup-product-item-price span {
        display: flex;
    }
    .popup-product-item-price .old-price {
        text-decoration: line-through;
        opacity: 0.6;
    }
    .popup-product-item-price .price {
        text-transform: uppercase;
    }
    .popup-product-suggested-items {
        padding: 40px;
        margin: 40px 0 110px;
        background-color: var(--bg-color-main-opacity20);
    }
    .popup-product-suggested-title {
        margin: 0 0 20px;
        font-size: 14px;
        line-height: 18px;
    }
    .popup-product-item .dropdown-size {
        margin-top: 20px;
        width: 115px;
    }
    .popup-product-item .dropdown-size .dropdown-select {
        padding: 10px 25px 10px 10px;
        text-transform: uppercase;
        font-family: var(--font-family-header);
        letter-spacing: 0.02em;
        font-weight: 500;
        font-size: 12px;
        line-height: 15.5px;
    }
    .popup-product-item .dropdown-size .dropdown-select::after {
        top: 10px;
        right: 10px;
    }
    .button-popup-add-to-basket {
        position: absolute;
        right: 0;
        bottom: 0;
        width: 40px;
        height: 40px;

        background-color: var(--bg-color-main);
        background-image: url(../img/icn-plus.svg);
        background-repeat: no-repeat;
        background-position: center;
        background-size: 21px;
        padding: 10px;
        border-radius: 40px;
        border: none;

        text-indent: -9999px;
        outline: none;
        cursor: pointer;
    }
    .button-popup-add-to-basket._added {
        background-image: url(../img/icn-check.svg);
        background-size: 19px;
        pointer-events: none;
    }

    @media only screen and (max-width: 1023px) {
        .popup-product-suggested-items {
            padding-top: 20px;
        }
    }
</style>

<?
//создаем подпись, что получить доступ к битриксовскому компоненту
$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedTemplate = $signer->sign($templateName, 'sale.basket.basket');
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.basket.basket');
?>
<script>
    var cartPopUpJS = {
        arResult: <?=CUtil::PhpToJSObject($arResult, false, false, true)?>,
        template: '<?=CUtil::JSEscape($signedTemplate)?>',
        signedParamsString: '<?=CUtil::JSEscape($signedParams)?>',
    }

    localStorage.setItem('cartPopUpRes', JSON.stringify(<?=CUtil::PhpToJSObject($arResult, false, false, true)?>));

    updateCartCountEl();
    function updateCartCountEl() {
        
        var catrCount = 0;
        $.each(cartPopUpJS.arResult.GRID.ROWS, function (index, element) {
            catrCount += element.QUANTITY;
        });

        $('.user-menu__link-cart .user-menu__item-counter').text(catrCount);
    }
    
    $(document).on('click', '.button-close-popup_', function(){
        $(".popup-add-to-basket").removeClass('_opened');    
    });
</script>