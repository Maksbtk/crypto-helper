$(function(){

let siteHeader = $('.belleyou-header');
let sidebarPanel = $('.belleyou-sidebar-panel');
let linkOpenPanel = $('.belleyou-sidebar-item__link');

function openCatalog() {
    $('#catalogShowButton').hide();
    $('#catalogCloseButton').show();      
    $('body').addClass('disable-scroll');
    $('#sidebarCatalog').toggleClass('_opened');

    siteHeader.removeClass('belleyou-header__transparent');

    if( $('body').hasClass('pwa-mode')) {
        $('.belleyou-header-menu').addClass('pwa-catalog-opened');
    }
}

function closeCatalog() {
    $('#catalogCloseButton').hide();
    $('#catalogShowButton').show();      
    $('body').removeClass('disable-scroll');
    $('#sidebarCatalog').toggleClass('_opened');

    $('.belleyou-sidebar-panel').removeClass('_opened');
    $('.belleyou-sidebar-menu').removeClass('_panel-opened');
    $('.button-submenu-open').removeClass('_active');

    if (siteHeader.hasClass('belleyou-header__mainpage') && $(window).scrollTop() == 0) {

        if( !($('body').hasClass('pwa-mode') && $(window).width() < 1023) ) {
            siteHeader.addClass('belleyou-header__transparent');
        }
    }

    if( $('body').hasClass('pwa-mode')) {
        $('.belleyou-header-menu').removeClass('pwa-catalog-opened');
    }

    $('#catalogSearch').removeClass('_opened');
}
function backToPrewScreen() {
    sidebarPanel.removeClass('_opened'); 
    linkOpenPanel.removeClass('_active');   
}


$('#catalogShowButton').click(function() {  
    openCatalog();
});

$('*[data-close-sidebar]').click(function(e) {
    e.preventDefault();
    closeCatalog();
});

$('*[data-back-panel]').click(function(e) {
    e.preventDefault();
    backToPrewScreen();
});


    $('.button-submenu-open').each(function() {
        $(this).click(function(e) {
            event.preventDefault();

            $('.button-submenu-open').removeClass('_active');
            $('.belleyou-sidebar-panel').removeClass('_opened');

            $(this).addClass('_active');
            $(this).next('.belleyou-sidebar-panel').addClass('_opened');
            $('.belleyou-sidebar-menu').addClass('_panel-opened');

        });        
    });

});