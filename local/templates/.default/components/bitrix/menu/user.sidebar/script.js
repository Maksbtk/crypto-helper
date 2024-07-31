$(function() {

    //прокрутка списка до текущего пункта меню (только мобилка)
    $(".profile-user-nav").animate({
        scrollLeft:$(".profile-user-menu .active").offset().left-20
    }, 1000);

    $('body').on('submit', '#update-user-info', function (e) {
        e.preventDefault();

        window.siteShowPrelouder();
        var formData = new FormData(this);

        $.ajax({
            url: $(this).data('action'),
            type: 'POST',
            cache:false,
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            enctype: 'multipart/form-data',
            success: function(result) {
                if(result.status == 'success'){
                    window.location.reload();
                }
            },
            error: function (jqXHR, exception) {
                console.log('error photo change');
                window.siteHidePrelouder();
            }
        });
        setTimeout(function() {window.siteHidePrelouder()}, 5000);
    });

    $("#uploadProfilePic").on("change", function() {
        $("#update-user-info").submit();
    });

    $('body').on('click', '.upload-userpic-button, .button-upload-picture', function (e) {
        $("#uploadProfilePic").click();
    });

    $('body').on('click', '.button-remove-avatar', function (e) {
        $('#delete-user-photo').val('y');
        $('#update-user-info').submit();
    });

});

