$(function(){

//выбор размера десктоп
   /* $('.product-size').each(function() {
        $(this).click(function() {
            $('.product-size').removeClass('_selected');
            $(this).addClass('_selected'); 
            if ($(this).hasClass('unavailable')) {
                $('.product-buy-button').hide();
                $('.product-alert-in-stock').show();
            } else {
                $('.product-buy-button').show();
                $('.product-alert-in-stock').hide();
            }
        });        
    });*/


//попап подписаться на отсутствующий товар
    $('.button-product-size-subscribe').click(function() {
        $(this).parents('.in-popup-product').hide();
        $('.back-in-stock_subscribe-success').show();
    });

//кнопка добавить рекомендацию в корзину
    $('.button-popup-add-to-basket').each(function() {
        $(this).click(function() {
            $(this).addClass('_added');
        });
    })

//добавить товар в избранное
    $('.product-addtofavorite-button, .product-add-to-favorite__mobile').click(function() {
        $(this).toggleClass('_added');
    });

//слайдер картинок товара включается в мобилке
    /*function productImgSlider() {
        $('.product-images-slider').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: false,
            dots: true,
            infinite: false,
            autoplay: false
        });
    }*/

//слайдер "идеально подходит"
  /*  function productSuggestSlider() {
        $('.product-suggestions-slider').slick({
            autoplay: false,
            infinite: false,
            dots: false,
            arrows: true,
            variableWidth: true,
            slidesToShow: 2,
            slidesToScroll: 2            
        });

    }*/


   /* if ( $(window).width() < 1024 ) {
        productImgSlider();

        $('.product-suggestions-slider').addClass('products-list-slider');
        productSuggestSlider();
    } //else
        //$('.product-images-slider').slick('unslick');
        
    $(window).on('resize', function(){
        if ( $(window).width() < 1024 ) {
            productImgSlider();
            
            $('.product-suggestions-slider').addClass('products-list-slider');
            productSuggestSlider();
        } //else
            //$('.product-images-slider').slick('unslick');
    }); */

});