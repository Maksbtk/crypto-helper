$(function(){

   /* let counter = $('.basket-item').length;

    $('*[data-basket-counter]').text(counter);   

    //удалить из корзины
    $('.basket-item-delete').click(function() {
        let item = $(this).closest('.basket-item');
        let count = item.find('.js-item-quantity').val();

        counter = counter - count;
        if(counter < 0){counter = 0;}        
        $('*[data-basket-counter]').text(counter);

        function basketReload() {
            location.reload();
        }

        if(counter == 0){
            setTimeout(basketReload, 1000);
        }
        
        item.remove();
    });
    */

    // форма с - и +
    /*$('.button-plus').click(function() {
        let $input = $(this).closest('.js-quantity').find('input');

        $input.val(parseInt($input.val())+1);

        $('*[data-basket-counter]').text(++counter);
    });

    $('.button-minus').click(function() {
        let $input = $(this).closest('.js-quantity').find('input');
        if(parseInt($input.val())>1) {
            $input.val(parseInt($input.val())-1);

            $('*[data-basket-counter]').text(--counter);
        }
    });*/

    $('.js-item-quantity').keydown(function(e) {
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
            (e.keyCode == 65 && e.ctrlKey === true) ||
            (e.keyCode >= 35 && e.keyCode <= 39)) {
            return;
        }
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    }); 


    //ПРОМОКОДЫ

    let promocode = 'hellobelle';
    let certificate = 'SSS000';
    let balance = '300';

    let a = 500; //сумма промокода  
    let b = 1000; //сумма сертификата  
    let c = 300; //сумма за баллы 


    //если в поле символов больше 1 то показывать крестик очистки поля
    let p = $('[data-promocode-input]');
    
    p.each(function() {
        if ($(this).val().length >= 1) {
            $(this).closest('.form-row').addClass('_active');   
        } else
            $(this).closest('.form-row').removeClass('_error _success _active _balance');        
    });

    $(document).on('keyup', '*[data-promocode-input]', function(){
        if ($(this).val().length >= 1) {
            $(this).closest('.form-row').addClass('_active');             
        }        
        else {
            $(this).closest('.form-row').removeClass('_error _success _active _balance');   
        }
    });

    //применить промокод
    function applyPromo(i,j,k) {
        
        if ( (k == 'promocode') && (j.val() == promocode) ) {
            i.removeClass('_error').addClass('_success');
            
            j.attr('data-promocode-input', a);
            $('.basket-discount-applied.promocode-applied').show();            
            $('#promo1').addClass('_applied');
        }            
        else if ( (k == 'certificate') && (j.val() == certificate) ) {
            i.removeClass('_error').addClass('_success');  
            
            j.attr('data-promocode-input', b);  
            $('.basket-discount-applied.certificate1-applied').show();
            $('#cert1').addClass('_applied');
        }  
        else if ( (k == 'balance') && (j.val() == balance) ) {
            i.removeClass('_error').addClass('_success'); 
            
            j.attr('data-promocode-input', c);
            $('.basket-discount-applied.balance-applied').show();
            $('#bal1').addClass('_applied');
        }                   
        else
            i.removeClass('_success').addClass('_error');               
    }


    //удалить примененную скидку из попапа
    let clear1 = $('.button-promocode-delete');

    clear1.each(function() {
        $(this).click(function() {
            $(this).closest('.form-row').find('input').val('').attr('data-promocode-input', '0');
            $(this).closest('.form-row').removeClass('_active _error _success');  

            let v = $(this).closest('.form-row').attr('id');
            
            $('[data-target="' + v + '"]').find('.basket-discount-applied').hide();

            $('.balance-message').hide();

            //calcTotal();
        });
    });

    //удалить примененную скидку со страницы и попапа
    let clear2 = $('[data-discount-delete]');

    clear2.each(function() {
        $(this).click(function() {
            
            let k = $(this).attr('data-discount-delete');
            $('#' + k).find('input').val('').attr('data-promocode-input', '0');
            $('#' + k).removeClass('_active _error _success');  

            $(this).parents('.basket-discount-applied').hide();
        });
    });


    //применить промокоды
    $(document).on('click', '*[data-entity-button]', function(e){
        e.preventDefault();

        let inputBox = $(this).prev('.form-row');
        let inputCode = $(this).prev('.form-row').find('input');
        let type = $(this).attr('data-entity-button');  

        $('.balance-message').hide();        
                
        //применяем промокод
        applyPromo(inputBox,inputCode,type);

        //пересчитываем сумму после применения скидок
        //calcTotal();

        //если сертификат то еще действия 
        if ($(this).attr('data-entity-button') == 'certificate' && inputBox.hasClass('_success')) {
            $(this).parents('.basket-discount-box').removeClass('certificate-screen-1').addClass('certificate-screen-2');
        }

        //если баллы, то еще действия
        if ($('#promo1').hasClass('_applied') || $('#cert1').hasClass('_applied')) {
            $('.basket-balance-button').hide();
            $('.basket-balance-button-na').show();
        }
    });


    //проверить баланс сертификата 
    $(document).on('click', '.button-check-certificate', function(){
        let ival = $(this).parents('.basket-certitficate-box').find('input').val();
        if (ival.length >= 1 && ival == certificate) {
            $(this).parents('.basket-certitficate-box').find('.form-row').removeClass('_error _success').find('.balance-message').show();
        } else
            $(this).parents('.basket-certitficate-box').find('.form-row').addClass('_error');
                
    });

    //добавить еще поле для сертификата
    $('.button-add-certificate').click(function(){
        let newCertLine = $('#bCert1').clone();
        newCertLine.attr('id', 'bCert2');      
        newCertLine.find('.form-row').attr('id', 'certificate2').removeClass('_success _active _error _balance');
        newCertLine.find('input').attr('data-promocode-input', '0').val('');
        $('#bCert1').addClass('_filled').after(newCertLine);
        $(this).parents('.basket-discount-box').removeClass('certificate-screen-2').addClass('certificate-screen-3');            

    });

/*
    //функция подсчёта общeй скидки

    function calcDiscount() {
        let sum = 0;        

        $('[data-promocode-input]').each(function() {
            sum = sum + parseInt($(this).attr('data-promocode-input'));
            //alert(sum);
        });


        //выводим скидку
        $('.summary-discount').text(sum);
        return sum;
    }

    //функция подсчёта стоимости после скидки

    let sumFull = $('.summary-full').text();

    $('.summary-final').text(sumFull);

    function calcTotal() {
        let sumDiscount = calcDiscount();
        let sumFinal = 0;
        
        sumFinal = parseInt(sumFull.replace(/[^0-9.]/gim, "")) - sumDiscount;            

        //выводим итоговую цену после скидок
        $('.summary-final').text(("" + sumFinal).replace(/.(?=(\d{3})+$)/g, "$& "));
    }
*/
    

});