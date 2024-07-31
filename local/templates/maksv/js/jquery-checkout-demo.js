$(function(){

//выбрать способ доставки
    $("#" + $(".delivery-radiogroup .input-radio:checked").val()).show();
    $(".delivery-radiogroup .input-radio").change(function(){
        $(".checkout-delivery-radioblock").hide();
        $("#" + $(this).val()).show();
    });

//детальная информация в мобилке
    $('.delivery-showmore-link').click(function(e) {
        e.preventDefault();
        $(this).next('.checkout-desc-text').toggle();
    });

//выбрать пункт самовывоза
    $('.delivery-choose-selfservice').click(function(e) {
        e.preventDefault();
        $(this).hide();
        $('.delivery-selfservice-selected').show();
    });

    $('.delivery-edit-selfservice').click(function(e) {
        e.preventDefault();
        $('.delivery-selfservice-selected').hide();
        $('.delivery-choose-selfservice').show();
    });

//выбрать магазин
    $('.delivery-choose-shop').click(function(e) {
        e.preventDefault();
        $(this).hide();
        $('.delivery-shop-selected').show();
    });

    $('.delivery-edit-shop').click(function(e) {
        e.preventDefault();
        $('.delivery-shop-selected').hide();
        $('.delivery-choose-shop').show();
    });


//выбрать способ оплаты
    $("#" + $(".payment-radiogroup .input-radio:checked").val()).show();
    $(".payment-radiogroup .input-radio").change(function(){
        $(".checkout-payment-radioblock").hide();
        $("#" + $(this).val()).show();
    });

});