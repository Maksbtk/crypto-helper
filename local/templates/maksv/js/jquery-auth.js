$(function() {
    //$('input[name="USER_PHONE_NUMBER"]').mask("(999)999-99-99");
    /*$('#button-get-code').click(function (e) {
        event.preventDefault();
        $('#mindbox-input-phone').hide();
        $('.authbox-go-register').hide();
        $('#mindbox-input-code').show();
    });
    $('#button-phone-enter').click(function (e) {
        event.preventDefault();
        $('.form-row-sms-code').addClass('_error');
    });*/

   /* $('.button-send-forgetpass').click(function (e) {
        event.preventDefault();
        $('#auth-forgetpass-form').hide();
        $('.auth-forgetpass-success').show();
    });*/

  /*  $('.button-change-password').click(function (e) {
        event.preventDefault();
        $('#auth-password-recovery-form').hide();
        $('.auth-password-recovery-success').show();
    });*/


    //дропдаун с кодом
    $('.dropdown-select').each(function () {
        $(this).click(function () {
            $('.dropdown-box').toggle();
        });
    });
    $('.dropdown-option').each(function () {
        $(this).click(function () {
            let i = $(this).data('label');
            $('.dropdown-select i').attr('data-flag', i);
            let code = $(this).data('code');
            $('.dropdown-select span').text(code);

            $('.dropdown-box').hide();
        });
    });

    //глаз в поле ввода пароля
    $('body').on('click', '.password-control', function () {
        let password = $(this).prev('.form-input');

        if (password.attr('type') == 'password') {
            $(this).addClass('view');
            password.attr('type', 'text');
        } else {
            $(this).removeClass('view');
            password.attr('type', 'password');
        }
        return false;
    });

    
    //если поля помечен красным и мы в них что то пишем, то удаляем красный
    $('body').on('click', '.form-input', function(e){
        $(this).removeClass('error_input');
    });

    //если чекбокс помечен красным и мы на него нажимаем, то удаляем красный
    $('body').on('click', '#personalDataCB', function(e){
        $(this).parent().removeClass('checkbox-error');
    });

    
});