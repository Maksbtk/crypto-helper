$(function(){

    $('.catalog-pagination-item').each(function(){ 
        $(this).click(function(e) {
        	event.preventDefault();
        	$('.catalog-pagination-item').removeClass('_current');
            $(this).addClass('_current');
        });
    });

});
	