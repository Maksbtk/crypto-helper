//отключение лоадера по событию и принудительно по таймауту
$(window).on('load', function() {
    siteHidePrelouder();
});
setTimeout(function() {siteHidePrelouder();}, 1500);

$("a").on('click', function(e) {

    var href_data = false;
    var href_target = false;
    href_target = $(this).attr('target');
    href_data = $(this).attr('href');

    if ($(this).hasClass('content-information1__add-cart')) {

    } else if(href_data && href_target !== "_blank" && href_data !== "#" && href_data !== "#androidSection" && href_data !== "#iphoneSection" && href_data !== "" && href_data !== "javascript:void(0)" && href_data !== "javascript:void(0);" && !href_data.includes('javascript:') && !href_data.includes('tel:')){
        e.preventDefault();
        siteShowPrelouder();
        setTimeout(function() {window.location = href_data}, 500);
    }

});

function siteHidePrelouder() {
    //$(".preloader-body").css('display','none');
    $(".preloader-body").fadeOut(300);
}

function siteShowPrelouder() {
    $(".preloader-body").fadeIn(300);
    //$(".preloader-body").css('display','block');
}