$(function() {
    var contactsFeedBackForm = $('#contactsFeedbackForm');

    var blockSubject = $('.dropdown-select');
    var blockMessage = $('#message-text');

    var inputFormType = $('input[name="FORM_TYPE"]');
    var inputEmailSend = $('input[name="EMAIL_SEND"]');
    var inputEmailTo = $('input[name="EMAIL_TO"]');
    var inputEventName = $('input[name="EVENT_NAME"]');
    var inputSubject = $('input[name="SUBJECT"]');
    var inputName = $('input[name="NAME"]');
    var inputNumber = $('input[name="NUMBER"]');
    var inputEmail = $('input[name="EMAIL"]');
    var inputMessage = $('input[name="MASSAGE"]');

    inputNumber.mask("+7 999 999 99 99");

    contactsFeedBackForm.submit(function (event) {
        event.preventDefault();

        inputSubject.val(blockSubject.text())
        inputMessage.val(blockMessage.val());

        //проверяем тему
        if (inputSubject.val().trim() == 'Тема обращения') {
            blockSubject.addClass('error_input');
            return false;
        }

        //проверяем поля
        var inputFields = $('.form-input');
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

        //проверяем сообщение
        if (inputMessage.val() == '') {
            blockMessage.addClass('error_input');
            return false;
        }

        grecaptcha.ready(function() {
            grecaptcha.execute('6LfidaUpAAAAAAlS_kLGX4FVOe7S__HcEmzKLIpl', {action: 'submit'}).then(function(token) {

                var formData = {
                    FORM_TYPE: inputFormType.val(),
                    EMAIL_SEND: inputEmailSend.val(),
                    EMAIL_TO: inputEmailTo.val(),
                    EVENT_NAME: inputEventName.val(),
                    SUBJECT:inputSubject.val(),
                    NAME: inputName.val(),
                    NUMBER: inputNumber.val(),
                    EMAIL: inputEmail.val(),
                    MASSAGE: inputMessage.val(),
                };

                BX.ajax.runComponentAction('belleyou:main.feedback', 'sendAndSave', {
                    mode: 'class',
                    data: formData,
                    timeout: 3000,
                }).then(function(response) {
                    if (response.data.status_iblock) {
                        
                        inputSubject.val('');
                        inputName.val('');
                        inputNumber.val('');
                        inputEmail.val('');
                        inputMessage.val('');

                        blockSubject.text('Тема обращения');
                        blockMessage.val('');

                        //$('.page-contacts-feedback').hide();

                        $('#successOpenModal').click();
                        console.log(response);
                    } else {
                        console.log(response);
                        alert('ошибка!');
                    }
                });
            });
        });

    });

    //если поля помечен красным и мы в них что то пишем, то удаляем красный
    $('body').on('click', '.form-input,.dropdown-select,#message-text', function(e){
        $(this).removeClass('error_input');
    });
    $('body').on('input', '.form-input,.dropdown-select,#message-text', function(e){
        $(this).removeClass('error_input');
    });

});