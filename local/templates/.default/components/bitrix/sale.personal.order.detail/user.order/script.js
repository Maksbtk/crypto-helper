
$(document).ready(function () {
  /*  $("#copyInBasketBtn").on("click", function () {
        var names = [];
        $(".pastorder-contains__item-name").each(function () {
            names.push({name: $(this).data('prod'), quantity: 1});
        });
        $(".pastorder-contains__item-amount").each(function (i) {
            names[i].quantity = $(this).data('count');
        });
        $.ajax({
            type: 'POST',
            url: '/ajax/copyInBasket.php',
            data: {products: names},
            success: function (data) {
                if (data) {
                    $("#basket-header span").text(JSON.parse(data).number);
                    window.location.href = "/cart/";
                }
            }
        });
    });*/

    $(".js-buttonCancelOrder").on("click", function () {
        var orderId = $(this).data('id');
        $.ajax({
            type: 'GET',
            url: '/ajax/orderActions.php?action=cancel_order&order_id='+orderId,
            success: function (data) {
                if (data) {
                    if (data.status == 'success') {
                        window.location.href = "/user/orders/order.php?ID="+orderId;
                    } else if ( data.status == "error" && data.errors && data.errors[0] && data.errors[0].message ){
                        alert(data.errors[0].message);
                    }
                }
            }
        });
    });

    $(".js-buttonPayOrder").on("click", function () {
        var orderId = $(this).data('id');
        $.ajax({
            type: 'GET',
            url: '/ajax/orderActions.php?action=pay_order&order_id='+orderId,
            success: function (data) {
                if (data) {
                    if (data.status == 'success') {
                        window.location.href = data.data.link;
                    } else if ( data.status == "error" && data.errors && data.errors[0] && data.errors[0].message ){
                        alert(data.errors[0].message);
                    }
                }
            }
        });
    });
});
