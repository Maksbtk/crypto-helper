$(function(){

$('.smartphone-type-box button').each(function() {

    $(this).click(function() {
      $('.smartphone-type-box button').addClass('button-secondary');
      $(this).removeClass('button-secondary');

      $('.instruction-section').hide();
      $('#' + $(this).attr('data-link')).show();
    });
});

});