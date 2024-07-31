$(function(){

    //демонстрация состояний формы

    $('.button-submit__form-subscribtion').removeClass('_active _done');
    $('.form-subscription-input').val('');

    //адрес введен
    $('.form-subscription-input').on('input', function() {
       $(this).next('.button-submit__form-subscribtion').addClass('_active'); 
    });

    //нажата кнопка отправить
    $('.button-submit__form-subscribtion').click(function(event) {
        event.preventDefault();
        $(this).removeClass('_active').addClass('_done');
        $(this).prev('.form-subscription-input').val('Спасибо за подписку!');
        $('.subscription-text1').hide();
        $('.subscription-text2').show();
    })

});