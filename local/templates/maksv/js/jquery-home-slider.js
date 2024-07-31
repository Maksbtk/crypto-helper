$(function(){

    let homeSlider = $('.home-slider');

    //добавляем svg для анимации кружков слайдера
    homeSlider.on('init', function(event, slick){
        $('.home-slider .slick-dots li').each(function() {
            $(this).append('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" class="loader"><circle class="progress" fill="none" stroke-linecap="round" cx="20" cy="20" r="15.915494309" /></svg>')
        });
    });

    //запускаем слайдер
    homeSlider.slick({
        autoplay: true,
        infinite: true,
        dots: true,
        arrows: true,
        autoplaySpeed: 3000
    });


});