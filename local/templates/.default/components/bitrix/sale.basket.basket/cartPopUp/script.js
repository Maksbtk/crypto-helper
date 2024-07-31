$(function() {



    $(document).on('click', '.open-cart-popup', function () {
        $('.modal-in-basket').addClass('slideToggle__content');
    });

    $(document).on('click', '.in-basket-button__continue, .modal-up__background-close,.modal-up__close', function () {
        $('.modal-in-basket').removeClass('slideToggle__content').delay(1000).fadeOut(300);
        $('.modal-in-basket').remove();
    });
    
    $(document).on('click','#x-btn-checkout', function(e) {
        href_data = $(this).attr('href');
       
        e.preventDefault();
        
        $(".preloader-body").css('display','block');
        
        setTimeout(function() {window.location = href_data}, 500);    
    });    

    //считаем количество товаров для отображения в мобилках
    var parentPopUpCart = $('.modal-in-basket .modal-up__content');
    var itemsPopUpCart = $('.in-basket-item').length;

    //регулирование высоты попапа
    function boxHeight() {
        if ($(window).width() <= 760) {
            if(itemsPopUpCart > 3) {
                parentPopUpCart.addClass('full-height');
            } else
                parentPopUpCart.removeClass('full-height');
        } else {
            if(itemsPopUpCart > 4) {
                parentPopUpCart.addClass('full-height');
            } else
                parentPopUpCart.removeClass('full-height');
        }
    }

    $(window).on("load resize",function(e){
        boxHeight();
    });

    //удаление товара из списка
    $(document).on('click', '.in-basket-item__remove', function () {
        var basketItemId = 'DELETE_'+$(this).data('basket-id');

        var basketParams = {};
        basketParams[basketItemId] = 'Y';

        updateBasket(basketParams, 'remove');
    });

    //вешаем событие на изменение количества товара
    $(document).on('change', '.js-basket-item-quantity', function() {
        var basketItemId = 'QUANTITY_'+$(this).data('basket-id');
        var skuPropValue = $(this).val();

        if (parseInt(skuPropValue) == 0) {
            skuPropValue = 1;
            $(this).val(skuPropValue);
        }

        var basketParams = {};
        basketParams[basketItemId] = skuPropValue;

        updateBasket(basketParams, 'changeQuantity');
    });

    //вешаем событие на клики +- количество
    $(document).on('click', '[data-entity="basket-item-quantity-minus"], [data-entity="basket-item-quantity-plus"]', function() {
        var input = $(this).closest('.js-quantity').find('.js-basket-item-quantity');
        var oldValue = parseFloat(input.val());

        if($(this).attr('data-entity') == 'basket-item-quantity-minus') {
            if(oldValue == 1) {
                return;
            }
            var newVal = oldValue - 1;
        } else {
            var newVal = oldValue + 1;
        }

        input.val(newVal);
        input.trigger('change');
    });

    $(document).on('change', '.in-basket-item__size select', function() {
        var selectedOption = $(this).find('option:selected');
        var basketId = selectedOption.data('basket-id');
        var basketPropId = 'OFFER_' + basketId;
        var skuPropName = '_SIZE';
        var skuPropValue = selectedOption.data('value-id');

        var basketParams = {};
        basketParams[basketPropId] = {};
        basketParams[basketPropId][skuPropName] = skuPropValue;

        updateBasket(basketParams, 'changeSize')
    });

    // ajax запрос в битрикс корзину на изменение полей
    function updateBasket(basketParams, action) {
        //$('.in-basket-container__inner').addClass('disabled');

        var data = {
            basket: basketParams,
            basketAction: 'recalculateAjax',
            //lastAppliedDiscounts : ,
            sessid : BX.bitrix_sessid(),
            signedParamsString : cartPopUpJS.signedParamsString,
            site_id : BX.message('SITE_ID'),
            site_template_id : "belleyou",
            template : cartPopUpJS.template,
            via_ajax : 'Y',
        };

        BX.ajax({
            method: 'POST',
            dataType: 'json',
            url: '/bitrix/components/bitrix/sale.basket.basket/ajax.php',
            data: data,
            onsuccess:  function(result){
                clearlyUpdatedFields(result, action);
            },
            onfailure: function() {
                console.log('error');
            },
        });
    }

    // тут решаем что делать с публичной частью модалки
    function clearlyUpdatedFields (basket, action) {
        switch (action) {
            case 'remove':
                var basketItem = $('.in-basket-item[data-basket-id="' + basket.DELETED_BASKET_ITEMS[0] + '"]');
                basketItem.remove();

                //если товаров стало меньше 3х, меняем высоту попапа для мобилки
                items = $('.in-basket-item').length;
                if(items < 3) {
                    parentPopUpCart.removeClass('full-height');
                }

                //счетчик уже добавленных товаров
                $('.items-counter').text(items - 1);
                if((items - 1) == 0) {
                    $('.in-basket-more-items__header').hide();
                }

                //если удален последний товар
                if(items == 0) {
                    $('.in-basket-no-items').show();
                    $('.modal-in-basket footer').hide();
                }

                break;
            case 'changeQuantity':
                if (basket.BASKET_DATA.WARNING_MESSAGE.length != 0) {
                    $('.js-basket-item-quantity[data-basket-id="' + basket.CHANGED_BASKET_ITEMS[0] + '"]').val(basket.BASKET_DATA.GRID.ROWS[basket.CHANGED_BASKET_ITEMS].QUANTITY);
                }
                break;
            case 'changeSize':
                $('.js-basket-item-quantity[data-basket-id="' + basket.CHANGED_BASKET_ITEMS[0] + '"]').val(basket.BASKET_DATA.GRID.ROWS[basket.CHANGED_BASKET_ITEMS].QUANTITY);
                break;
        }
        generalUpdatedFields(basket.BASKET_DATA);
    }

    // тут обновляем поля корзины
    function generalUpdatedFields(basket) {
        //обновляем поля у каждого элемента
        $.each(basket.GRID.ROWS, function(basketId, elementFields) {
            $('.in-basket-item[data-basket-id="' + basketId + '"] .in-basket-item__name').html(elementFields.NAME);
            $('.in-basket-item[data-basket-id="' + basketId + '"] .in-basket-item__price').html(elementFields.SUM_FULL_PRICE_FORMATED);
        });

        //обновляем сумму корзины
        $('.in-basket-summary strong').html(basket.allSum_FORMATED);

        //$('.in-basket-container__inner').removeClass('disabled');
    }


});
