$(function(){

    //показать/скрыть текст возврата
    $('.returns-title').each(function() {
        $(this).click(function() {
            $(this).toggleClass('_opened')
            $(this).next('.returns-body').slideToggle();
        });
    });
});