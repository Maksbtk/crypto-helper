$(function(){

let header = $('.belleyou-header');              
let notifHeight = $('.belleyou-header-notification').outerHeight();
let fixedClass = 'belleyou-header__fixed';

//прозрачная шапка на главной
let mainpageHeader = $('.belleyou-header__mainpage');
let transparentClass = 'belleyou-header__transparent';  

    function stickyHeader() {
                         
        if($(window).scrollTop() > notifHeight) {
            header.addClass(fixedClass);            
            mainpageHeader.removeClass(transparentClass);            
            
        } else {
            header.removeClass(fixedClass);

            //условие для определения pwa-версии
            if( !($('body').hasClass('pwa-mode') && $(window).width() < 1023) ) {
                mainpageHeader.addClass(transparentClass);
            }
        }                

        $(window).scroll(function() {
            if($(this).scrollTop() > notifHeight) {
                header.addClass(fixedClass);
                mainpageHeader.removeClass(transparentClass);
                
            } else {
                header.removeClass(fixedClass);
             
                if( !($('body').hasClass('pwa-mode') && $(window).width() < 1023) ) {
                    mainpageHeader.addClass(transparentClass);
                }
            }
        });
    }

    stickyHeader();

});