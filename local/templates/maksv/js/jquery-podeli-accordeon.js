$(function(){

    //показать/скрыть ответы
    $('.podeli-qa-question').each(function() {
        $(this).click(function() {
            $(this).toggleClass('_opened');
            $(this).next('.podeli-qa-answer').slideToggle();
        });
    });
});