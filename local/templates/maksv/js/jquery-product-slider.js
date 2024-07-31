$(function(){

    //запуск слайдеров товаров
    $('.products-list-slider').each(function() {

        $(this).slick({
            autoplay: false,
            infinite: false,
            dots: false,
            arrows: true,
            slidesToShow: 4,
            slidesToScroll: 1,
            responsive: [
                {
                  breakpoint: 1023,
                  settings: {
                    variableWidth: true,
                    slidesToShow: 2,
                    slidesToScroll: 2
                  }
                }
              ]
        });

    }); 

});