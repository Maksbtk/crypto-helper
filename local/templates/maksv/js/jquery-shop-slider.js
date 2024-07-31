$(function(){

    $('.our-shops-slider').slick({
        autoplay: true,
        infinite: true,
        dots: false,
        arrows: false,
        slidesToShow: 4,
        slidesToScroll: 1,
        centerMode: true,
        responsive: [
            {
              breakpoint: 1023,
              settings: {
                slidesToShow: 1
              }
            }
        ]
    });

});