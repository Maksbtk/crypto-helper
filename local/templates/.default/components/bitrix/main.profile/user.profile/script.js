$(function() {
    
    $('input[name="PHONE_NUMBER"]').mask("+7 (999) 999-99 99");

    $(document).on('click', '#save-form-btn', function() {
        $('#save-form-inp').click();
    });

    //показать/скрыть историю операций
    $('#pointsHistoryToggle').click(function() {
        $(this).toggleClass('_opened')
        $('.points-history').slideToggle();
    });

    //удалить превью аватара
    $('.button-remove-avatar').click(function() {
        $('.profile-user-picture').addClass('_empty');
        $('.button-upload-picture span').text('Выбрать фото');
        $('.button-cancel-avatar').show();
        $('.button-remove-avatar').hide();
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
        return false;
    });

    //приравниваем email к логину перед сохранением данных
    $('body').on('submit', '#userPersonalDataForm', function (e) {
        $('input[name="LOGIN"]').val($('input[name="EMAIL"]').val());
    })

    //
    $('body').on('submit', '#changePassForm', function (e) {
        e.preventDefault();

         var errBlock = $(this).find('.error-message');
         errBlock.hide();

        //проверяем поля
        var inputFields = $(this).find('.form-input');
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

        var data = {
            old_password: $('#old_password').val(),
            new_password: $('#new_password').val(),
            new_password_confirm: $('#new_password_confirm').val(),
        };

        BX.ajax({
            method: 'POST',
            dataType: 'json',
            url: '/ajax/changePass.php',
            data: data,
            onsuccess:  function(result){
                console.log(result);
                if (result.status == true) {
                    $('.popup__backdrop').click();
                    inputFields.val('');
                    window.location.reload();
                } else {
                    errBlock.html(result.message);
                    errBlock.show();
                }
            },
            onfailure: function() {
                console.log('error ajax change password');
            },
        });

    });

    //если поля помечен красным и мы в них что то пишем, то удаляем красный
    $('body').on('click', '.form-input', function(e){
        $(this).removeClass('error_input');
    });

    $('body').on('change', '#fakeBirthday', function(e){
        var selectedDate = $(this).val();
        var formattedDate = formatDate(selectedDate);
        $('input[name="PERSONAL_BIRTHDAY"]').val(formattedDate);
    });

    function formatDate(inputDate) {
        var dateObject = new Date(inputDate);
        if (isNaN(dateObject.getTime())) {
            console.error('Invalid date');
            return inputDate;
        }

        // Форматирование даты вручную
        var day = String(dateObject.getDate()).padStart(2, '0');
        var month = String(dateObject.getMonth() + 1).padStart(2, '0'); // Добавляем 1, так как месяцы в JavaScript начинаются с 0
        var year = dateObject.getFullYear();

        var formattedDate = day + '.' + month + '.' + year;
        return formattedDate;
    }

});

