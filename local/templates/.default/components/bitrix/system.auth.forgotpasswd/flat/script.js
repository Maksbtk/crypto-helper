$(function() {

    $('input[name="USER_LOGIN"]').focus();

    // делаем замену перед отправкой формы и проверяем поля
    $('body').on('submit', '#auth-forgetpass-form', function (e) {

        //проверяем поля
        var inputFields = $('.form-input');
        var flagFieldsErr = false;
        inputFields.each(function (index) {
            if ($(this).val() == '') {
                $(this).addClass('error_input');
                flagFieldsErr = true;
            }
        });
        if (flagFieldsErr) {
            return false;
        }

        //приравниваем email к логину
        $('input[USER_EMAIL]').val($('input[name="USER_LOGIN"]').val());

        //дописываем код страны к номеру
        $('input[name="REGISTER[PHONE_NUMBER]"]').val($('.dropdown-phone-code span').text() + $('input[name="REGISTER[PHONE_NUMBER]"]').val());

        //return false;
    })

    //если поля помечен красным и мы в них что то пишем, то удаляем красный
    $('body').on('click', '.form-input', function(e){
        $(this).removeClass('error_input');
    });

    //если чекбокс помечен красным и мы на него нажимаем, то удаляем красный
    $('body').on('click', '#personalDataCB', function(e){
        $(this).parent().removeClass('checkbox-error');
    });

});