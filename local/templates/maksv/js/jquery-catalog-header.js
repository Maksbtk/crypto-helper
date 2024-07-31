$(function(){

let header = $('.belleyou-header');
let catalogHeader = $('.page-catalog-header');
let offset = catalogHeader.offset();

let fixedClass = 'page-catalog-header__fixed';
let fixedClassWithHeader = '_with-header'

    function stickyCatalogHeader() {

        if ( !catalogHeader.hasClass('no-scroll') ) {
        
            if($(window).scrollTop() > (offset.top - 20)) {
                catalogHeader.addClass(fixedClass);
                
            } else {
                catalogHeader.removeClass(fixedClass);
            }                        

            $(window).scroll(function() {
                if($(this).scrollTop() > (offset.top - 20)) {
                    catalogHeader.addClass(fixedClass);
                    
                } else {
                    catalogHeader.removeClass(fixedClass);
                }
            });


            //на прокрутку вверх показываем хедер
            $(window).on('wheel', function(e){
                if (e.originalEvent.wheelDelta >= 0) {
                    catalogHeader.addClass(fixedClassWithHeader);
                    //console.log('Вверх');

                } else {
                    catalogHeader.removeClass(fixedClassWithHeader);
                }
            });
        }
    }

    stickyCatalogHeader();

});