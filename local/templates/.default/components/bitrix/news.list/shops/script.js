$(function(){
    
    let shopLink = $('.shop-link');
    let backLink = $('.link-back-to-shopslist');

    $(document).on("click",".dropdown-shops-city ul li",function() {
        var directionLink = $(this).data('direction');
        window.location.href = directionLink;
    });

    shopLink.each(function() {
        $(this).click(function() {
            $('.shops-list').hide();
            $('.shop-open-body').append($(this).children().clone());
            $('body,html').animate({scrollTop: 100}, 50).addClass('ontop');
            $('.shop-open').show();

            window.GLOBAL_arMapObjects.SHOPS_MAP.setCenter([$(this).data('latitude'),$(this).data('longitude')],$(this).data('size'));
        });
    });

    backLink.click(function() {
        $('.shops-list').show();
        $('.shop-open-body').empty();
        $('.shop-open').hide();

        window.GLOBAL_arMapObjects.SHOPS_MAP.setCenter([$(this).data('latitude'),$(this).data('longitude')],$(this).data('size'))
    });

    //мобилка переключение список/карта
    $('.shops-menu-mobile-item').each(function() {
        $(this).click(function() {

            $('.shops-content').fadeOut();
            $('.shops-menu-mobile-item').removeClass('active');
            $(this).addClass('active');

            if ($(this).hasClass('item-list')) {
                $('.shops-list').fadeIn();
            } else {
                $('.shops-map-bar').fadeIn();
                $('.shop-open').hide();
            }

        });
    });
    
});