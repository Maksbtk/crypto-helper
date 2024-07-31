$(function(){

    $('#button-get-code').click(function(e) {
        event.preventDefault();
        $('#mindbox-input-phone').hide();
        $('.authbox-go-register').hide();
        $('#mindbox-input-code').show();
    });
    $('#button-phone-enter').click(function(e) {
        event.preventDefault();
        $('.form-row-sms-code').addClass('_error');
    });
    $('.button-send-forgetpass').click(function(e) {
        event.preventDefault();
        $('#auth-forgetpass-form').hide();
        $('.auth-forgetpass-success').show();
    });

    $('.button-change-password').click(function(e) {
        event.preventDefault();
        $('#auth-password-recovery-form').hide();
        $('.auth-password-recovery-success').show();
    });


    //дропдаун с кодом
    $('.dropdown-select').each(function() {
        $(this).click(function() {
            $('.dropdown-box').toggle();            
        });        
    });
    $('.dropdown-option').each(function() {
        $(this).click(function() {
            let i = $(this).data('label');
            $('.dropdown-select i').attr('data-flag', i);
            let code = $(this).data('code');
            $('.dropdown-select span').text(code);

            $('.dropdown-box').hide();
        });        
    });

    //глаз в поле ввода пароля
    $('body').on('click', '.password-control', function(){
        let password = $(this).prev('.form-input');

        if (password.attr('type') == 'password'){
            $(this).addClass('view');
            password.attr('type', 'text');
        } else {
            $(this).removeClass('view');
            password.attr('type', 'password');
        }
        //alert('lol');
        return false;
    });

});