$(function() {
    
    $('input[name="REGISTER[PHONE_NUMBER]"]').mask("+7 (999) 999 99 99");

    // меняем дату перед отправкой формы и проверяем поля
    $('body').on('submit', '#registration-form', function (e) {

        //проверяем поля
        var inputFields = $('#registration-form .form-input');
        var flagFieldsErr = false;
        inputFields.each(function( index ) {
            if ($(this).val() == '') {
                $(this).addClass('error_input');
                flagFieldsErr = true;
            }
        });
        if (flagFieldsErr) {
            return false;
        }

        //проверяем чекбоксы
        var personalDataCB = $('#personalDataCB');
        //var loyaltyCB = $('#loyaltyCB');
        if (!personalDataCB.is(':checked')/* || !loyaltyCB.is(':checked')*/) {

            if (!personalDataCB.is(':checked')) {
                personalDataCB.parent().addClass('checkbox-error');
            }
            /*if (!loyaltyCB.is(':checked')) {
                loyaltyCB.addClass.parent().('checkbox-error');
            }*/

            return false;
        }

        //приравниваем email к логину
        $('input[name="REGISTER[LOGIN]"]').val($('input[name="REGISTER[EMAIL]"]').val());

        //дописываем код страны к номеру
        //$('input[name="REGISTER[PHONE_NUMBER]"]').val( $('.dropdown-phone-code span').text() + $('input[name="REGISTER[PHONE_NUMBER]"]').val());

        //retail rocket
       /* var emailReg = $('#reg-email-field').val();
        var nameReg = $('input[name="USER_NAME"]').val();
        var surnameReg = $('input[name="USER_LAST_NAME"]').val();
        var numReg = $('input[name="USER_PHONE_NUMBER"]').val();

        (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
            try { rrApi.setEmail(emailReg, {"name": nameReg, "surname": surnameReg, "number": numReg }); } catch(e) {}
        })*/

        //return false;
    });

});