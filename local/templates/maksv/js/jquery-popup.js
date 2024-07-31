$(function(){

    $('.popup').css('display' , '');
    $('#sidebarCatalog').css('display' , '');

    function openPopup(item) {
        $('.popup').removeClass('_opened'); //закрываем открытые попапы
        $('body').removeClass('disable-scroll'); //если попапа не существует на странице

        if( item.length > 0 ) {
            item.addClass('_opened');
            $('body').addClass('disable-scroll');

            lockScroll();
        }
    }
    function closePopup() {
        $('.popup').removeClass('_opened');
        $('body').removeClass('disable-scroll');

        unlockScroll();
    }

    $(document).on("click","*[data-popup]",function(e) {
        e.preventDefault();
        
        let popup = $('.'+$(this).attr('data-popup'));
        openPopup(popup);
    });

    //$(document).on("click","*[data-close-popup]",function() {
    $('*[data-close-popup]').click(function(e) {
        e.preventDefault();
        closePopup();
    });

    //хак для отключения прокрутки контента в iOS

    let body = $('body');
    let bodyScrollTop = 20;
    let locked = false;

    // Заблокировать прокрутку страницы
    function lockScroll(){
        if (!locked) {
            bodyScrollTop = (typeof $(window).pageYOffset !== 'undefined') ? window.pageYOffset : (document.documentElement || document.body.parentNode || document.body).scrollTop;

            body.addClass('scroll-locked');
            body.css('top', '-' + bodyScrollTop + 'px');
            //alert(bodyScrollTop);
            locked = true;
        };
    }

    // Включить прокрутку страницы
    function unlockScroll(){
        if (locked) {
            body.removeClass('scroll-locked');
            body.css('top', null);
            window.scrollTo(0, bodyScrollTop);
            locked = false;
        }
    }

});