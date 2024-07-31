$(function(){

  if ( $(window).width() < 1024 ) {
    $('.statuses-slider').slick({
      autoplay: false,
      infinite: false,
      dots: true,
      arrows: false,
      slidesToShow: 1,
      slidesToScroll: 1
    });
  } else {
    $('.statuses-slider').slick('unslick');
  }

  $(window).on('resize', function(){
    if ( $(window).width() < 1024 ) {
      $('.statuses-slider').slick({
        autoplay: false,
        infinite: false,
        dots: true,
        arrows: false,
        slidesToShow: 1,
        slidesToScroll: 1
      });
    } else {
      $('.statuses-slider').slick('unslick');
    }
  });
});