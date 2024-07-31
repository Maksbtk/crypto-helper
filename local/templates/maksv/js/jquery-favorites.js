$(function(){

    $(document).on("click",".js-check-wishlist-button",function()
    {
        var favorID = $(this).data('id');

        if($(this).hasClass('_added'))
            var doAction = 'delete';
        else
            var doAction = 'add';

        addFavorite(favorID, doAction);
    });

    function addFavorite(id, action)
    {
        var param = 'id='+id+"&action="+action;
        $.ajax({
            url:     '/ajax/favoritesActions.php',
            type:     "GET",
            dataType: "html",
            data: param,
            success: function(response) { // Если Данные отправлены успешно
                console.log(response);

                var result = $.parseJSON(response);
                var currentAddToFav = $('.js-check-wishlist-button[data-id="'+id+'"]');
                var wishEl = $('.user-menu__link-favorites .user-menu__item-counter');

                if(result == 1){ // Если всё ок, то выполняем действия, которые показывают, что данные отправлены :)
                    currentAddToFav.addClass('_added');
                    var wishCount = parseInt(wishEl.html()) + 1;
                    wishEl.html(wishCount); // Визуально меняем количество у иконки
                }
                if(result == 2){

                    currentAddToFav.removeClass('_added');
                    var wishCount = parseInt(wishEl.html()) - 1;
                    wishEl.html(wishCount); // Визуально меняем количество у иконки
                }
            },
            error: function(jqXHR, textStatus, errorThrown){ // Если ошибка, то выкладываем печаль в консоль
                console.log('Error: '+ errorThrown);
            }
        });
    }

});