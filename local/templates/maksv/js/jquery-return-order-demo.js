$(function(){

    let step = $('.section-step');
    let editStepButton = $('.edit-section-button');
    let saveStepButton = $('.save-step-button');
    let nextStepButton = $('.next-step-button');
    let finishButton = $('.finish-button');

    //переход на следующий шаг
    function nextStep() {
        if ($('.section__opened').hasClass('step-4')) {
            lastStep();
        }
        else {
            $('.section__opened').addClass('section__edited');
            $('.section__edited').next(step).addClass('section__opened');
            $('.section__edited').removeClass('section__opened');

            markStep();
        }        
    }

    //последний шаг
    function lastStep() {        
        nextStepButton.hide();
        $('.step-4').removeClass('section__opened').addClass('section__edited');
        $('.return-order-finish').show();
        checkboxCheck();
    } 

    //галочки заполненных шагов
    function markStep() {                
        $('.section__edited').each(function() {            
            $('#' + $(this).attr('data-marker')).addClass('checked');
        });
    }

    nextStepButton.click(function() {        
        nextStep();
    });    

    //редактировать шаг
    editStepButton.each(function() {        
        $(this).click(function() {
            step.removeClass('section__opened');
            $(this).parents('.section-step').addClass('section__opened');
        });
    });

    //сохранить отредактированный шаг
    saveStepButton.each(function() {        
        $(this).click(function() {
            step.removeClass('section__opened');
        });
    });


    //чекбокс соглашение 
    function checkboxCheck() {
        if ($('input[type=checkbox][name=agreement]').is(':checked')) {
            finishButton.removeClass('disabled');
        }
        else {
            finishButton.addClass('disabled');
        }
    }

    $('input[type=checkbox][name=agreement]').change(function() {
        checkboxCheck();        
    });

/* попап Выбор причины возврата */    

    $('.return-item-checkbox input[type="checkbox"]').prop('checked', false);
    $('.reason-ways-radiogroup input[type="radio"]').prop('checked', false);
    $('.return-item-reason-link').text();
    $('.dropdown-option').removeClass('selected');

    //подставляем нужную форму под соотв причину в попапе
    $('.dropdown-option').each(function(){
        $(this).click(function(){
            $('.reason-details-box').hide();

            let i = $(this).attr('data-label');
            
            $('#reasonBox' + i).show();
            if( !(i == '4') ) {
                $('#returnItemPhotos').show();
            } else
                $('#returnItemPhotos').hide();
            
            //разблокирывание кнопки должно происходить когда заполнены все поля, для демо - когда выбираем причину
            $('.button-send-reason').removeAttr('disabled');
        });
    });


    //при нажатии на кнопку "Подтвердить" 
    $('.button-send-reason').click(function() {
        let popup = $(this).parents('.popup');
        let id = popup.attr('data-id');        
        let reasonName = popup.find('.dropdown-option.selected').text();

        //отправляем причину в лейбл
        $('#' + id +' .return-item-reason a').text(reasonName);

        //отмечаем чекбокс в лейбле
        $('#' + id +' .return-item-checkbox input[type="checkbox"]').prop('checked', true);


        //если была выбрана доставка недоставленного товара открываем другой попап
        let v = $('.reason-ways-radiogroup input[type="radio"]:checked').val();
        if( v == '2' && $('.dropdown-option.selected').attr('data-label')  == '4') {
            $('.popup-choose-reason-sent').addClass('_opened');
            $('body').addClass('disable-scroll');
        }

    });

    

/* загрузка фотографий */

     //var maxFileSize = 2 * 1024 * 1024; // (байт) Максимальный размер файла (2мб)
     var queue = {};
     var imagesList = $('#uploadImagesList');

     var itemPreviewTemplate = imagesList.find('.item.template').clone();
     itemPreviewTemplate.removeClass('template');
     imagesList.find('.item.template').remove();

    let photoCounter = 0;
    let photoCounterBox = $('._photo-counter'); 


     $('#addImages').on('change', function () {

        $('.upload-photo-text').hide(); 

        photoCounterBox.text(++photoCounter);


         var files = this.files;

         for (var i = 0; i < files.length; i++) {
             var file = files[i];

             if ( !file.type.match(/image\/(jpeg|jpg|png|gif)/) ) {
                 alert( 'Фотография должна быть в формате jpg, png или gif' );
                 continue;
             }

             //if ( file.size > maxFileSize ) {
             //    alert( 'Размер фотографии не должен превышать 2 Мб' );
             //    continue;
             //}

             preview(files[i]);
         }

         this.value = '';
     });

     // Создание превью
     function preview(file) {
         var reader = new FileReader();
         reader.addEventListener('load', function(event) {
             var img = document.createElement('img');

             var itemPreview = itemPreviewTemplate.clone();

             itemPreview.find('.img-wrap img').attr('src', event.target.result);
             itemPreview.data('id', file.name);

             imagesList.append(itemPreview);

             queue[file.name] = file;

         });
         reader.readAsDataURL(file);
     }

     // Удаление фотографий
     imagesList.on('click', '.delete-link', function () {
         var item = $(this).closest('.item'),
             id = item.data('id');

         delete queue[id];

         item.remove();

         photoCounterBox.text(photoCounter--);
     });

    
});