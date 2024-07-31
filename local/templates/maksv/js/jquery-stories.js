$(function(){

    /* открытие слайдера сториз */
    /* для самого слайдера используется скрипт slick.js */

    let storyLink = $('.widget-stories__story-link');
    let storyFrame = $('.widget-stories__fullstory');
    let buttonCloseStory = $('.fullstory__button-close');
    let storyId;
    let currentStory;
    let currentStorySlider;

    storyLink.each(function() {

        $(this).click(function(e){
            e.preventDefault();

            storyId = $(this).data('id');
            currentStory = $('#' + storyId);
            currentStorySlider = $('#' + storyId + ' .fullstory-slider');
            
            //currentStory.show();
            currentStory.fadeIn(500);

            currentStorySlider
                .slick({
                    autoplay: true,
                    infinite: false,
                    autoplaySpeed: 7000, //должна совпадать с @keyframe в css
                    dots: true,
                    fade: true
                })
                .on('beforeChange', function(event, slick, currentSlide, nextSlide){

                    if(currentSlide == 0) {
                        //закрашиваем первый просмотренный слайд в навигации                       
                        $('.slick-dots li:first-child').addClass('slide-seen');
                    }
                })
                .on('afterChange', function(event, slick, currentSlide, nextSlide){
                    //console.log(currentSlide);

                    //закрашиваем просмотренный слайд в навигации
                    $('.slick-dots li:nth-child(' + currentSlide + ')').addClass('slide-seen');

                    //останавливаем слайдер когда все слайды пролистались и скрываем
                    if(currentSlide == ($('.slick-dots ul li').length - 1)) {
                        slickPause();
                        currentStory.hide();
                    }

                    //скрываем слайдер на последнем слайде по нажатию на кнопку next
                    $('.slick-next.slick-disabled').click(function() {
                        currentStory.hide();

                        $('body').removeClass('disable-scroll');
                        $('body').removeClass('scroll-locked');
                    });

                });
        });
    });

    //закрыть сториз
    buttonCloseStory.click(function(){
        storyFrame.hide();

        $('body').removeClass('disable-scroll');
        $('body').removeClass('scroll-locked');
    });

});