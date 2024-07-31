$(document).ready(function() {

    // заполнение попапа отмены
    $(".js-openCancelPopUp").on("click", function () {
        $(".js-buttonCancelOrder").data("id", $(this).data('id'));
        $("#popUpOrderId").html($(this).data('id'));
        $('.popup-cancel-order').addClass('_opened')
    });

    $(".js-buttonCancelOrder").on("click", function () {
        var orderId = $(this).data('id');
        $.ajax({
            type: 'GET',
            url: '/ajax/orderActions.php?action=cancel_order&order_id='+orderId,
            success: function (data) {
                if (data) {
                    if (data.status == 'success') {
                        window.location. reload();
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