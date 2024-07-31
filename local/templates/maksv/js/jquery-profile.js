$(function(){

    //показать/скрыть историю операций
    $('#pointsHistoryToggle').click(function() {
        $(this).toggleClass('_opened')
        $('.points-history').slideToggle();
    });

    //подставляем запросы из предложенных в поле поиска
    $(document).on("click",".js-enable_loyalty",function(e) {
        e.preventDefault();
        window.siteShowPrelouder();
        var errBlock = $('.profile-user-empty .error-message');
        errBlock.fadeOut();

        BX.ajax({
            method: 'GET',
            dataType: 'json',
            url: '/ajax/enableLoyaltyProgram.php',
            onsuccess:  function(result){
                console.log(result);
                if (result.status == true) {
                    window.location.reload();
                } else {
                    errBlock.html(result.message);
                    errBlock.fadeIn();
                    window.siteHidePrelouder();
                }
            },
            onfailure: function() {
                errBlock.html('error ajax loyality');
                errBlock.fadeIn();
                window.siteHidePrelouder();
            },
        });


    });

    /*    //прокрутка списка до текущего пункта меню (только мобилка)
        $(".profile-user-nav").animate({
            scrollLeft:$(".profile-user-menu .active").offset().left-20
        }, 1000);


        //добавить превью аватара
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onloadend = function(e) {
                    $('#userPicture').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        $("#uploadProfilePic").change(function() {
            readURL(this);
            $('.profile-user-picture').removeClass('_empty');
            $('.button-upload-picture span').text('Выбрать другое фото');
            $('.button-cancel-avatar').hide();
            $('.button-remove-avatar').show();

            //закрыть попап
                $('.popup').removeClass('_opened');
                $('body').removeClass('disable-scroll');
        });

        //удалить превью аватара
        $('.button-remove-avatar').click(function() {
            $('.profile-user-picture').addClass('_empty');
            $('.button-upload-picture span').text('Выбрать фото');
            $('.button-cancel-avatar').show();
            $('.button-remove-avatar').hide();
        });*/


});