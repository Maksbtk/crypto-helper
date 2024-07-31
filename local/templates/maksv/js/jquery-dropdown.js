$(function(){

//дропдаун с любыми данными, короткий список
    /*
    $('.dropdown-select').each(function() {
        $(this).on("click", function() {
            $(this).parents('.dropdown').toggleClass('_opened');    
        });        
    });

    $('.dropdown-option').each(function() {
        $(this).on("click", function() {
            let i = $(this).text();
            $(this).parents('.dropdown-box').prev('.dropdown-select').text(i);

            $('.dropdown-option').removeClass('selected');
            $(this).addClass('selected');

            $('.dropdown').removeClass('_opened');
        });        
    });
    */
    
    $(document).on('click', '.dropdown-select', function () {
        $(this).parents('.dropdown').toggleClass('_opened');    
    });    
    
    $(document).on('click', '.dropdown-option', function () {
        let i = $(this).text();
        $(this).parents('.dropdown-box').prev('.dropdown-select').text(i);

        $('.dropdown-option').removeClass('selected');
        $(this).addClass('selected');

        $('.dropdown').removeClass('_opened');    
    });

});