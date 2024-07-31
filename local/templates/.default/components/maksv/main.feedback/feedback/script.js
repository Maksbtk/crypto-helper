$(function() {
    var feedBackForm = $('#feedbackForm');

    var inputFormType = $('input[name="FORM_TYPE"]');
    var inputEmailSend = $('input[name="EMAIL_SEND"]');
    var inputEmailTo = $('input[name="EMAIL_TO"]');
    var inputEventName = $('input[name="EVENT_NAME"]');

    var blockSubject = $('.dropdown-select');
    var blockMessage = $('#message-text');

    var inputRate = $('input[name="RATE"]');
    var inputName = $('input[name="NAME"]');
    var inputCity = $('input[name="CITY"]');
    var inputMessage = $('input[name="MASSAGE"]');

    feedBackForm.submit(function (event) {
        event.preventDefault();

        inputRate.val($('.score-item._selected').text());
        inputMessage.val(blockMessage.val());

        //проверяем поля
        var inputFields = $('#contactsFeedbackForm .form-input');
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
                console.log('grecaptcha_token');
                console.log(token);

                var formData = {
                    FORM_TYPE: inputFormType.val(),
                    EMAIL_SEND: inputEmailSend.val(),
                    EMAIL_TO: inputEmailTo.val(),
                    EVENT_NAME: inputEventName.val(),
                    RATE:inputRate.val(),
                    NAME: inputName.val(),
                    CITY: inputCity.val(),
                    MASSAGE: inputMessage.val(),
                };

                BX.ajax.runComponentAction('belleyou:main.feedback', 'sendAndSave', {
                    mode: 'class',
                    data: formData,
                    timeout: 3000,
                }).then(function(response) {
                    if (response.data.status_iblock) {

                        inputRate.val('');
                        inputName.val('');
                        inputCity.val('');
                        inputMessage.val('');

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

    //если поля помечены красным и мы в них что то пишем, то удаляем красный
    $('body').on('click', '.form-input,.dropdown-select,#message-text', function(e){
        $(this).removeClass('error_input');
    });
    $('body').on('input', '.form-input,.dropdown-select,#message-text', function(e){
        $(this).removeClass('error_input');
    });

    $('body').on('input', '#message-text', function(e){
        var str = $(this).val();
        $('#countMessageSimbol').text(str.length);
        console.log(str.length);
    });

    $('body').on('click', '.score-item', function(e){
        $('.score-item').removeClass('_selected');
        $(this).addClass('_selected')
    });
});




