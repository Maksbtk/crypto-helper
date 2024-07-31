$(function(){

    let siteHeader = $('.belleyou-header');
    let searchBar = $('#catalogSearch');
    let buttonOpenBar = $('#buttonSearchBarOpen');

    let buttonCancelSearch = $('.form-catalog-search__cancel');
    let buttonClearSearch = $('.form-catalog-search__clear');

//открыть панель поиска десктоп
    function openSearchBar() {

        $('body').addClass('disable-scroll');
        searchBar.toggleClass('_opened');
        siteHeader.removeClass('belleyou-header__transparent');
    }

//закрыть панель поиска десктоп
    function closeSearchBar() {
        searchBar.removeClass('_opened');
        $('body').removeClass('disable-scroll');
    }

    buttonOpenBar.click(function() {
        openSearchBar();
    });

    $('*[data-close-searchbar]').click(function(e) {
        e.preventDefault();
        closeSearchBar();
    });


//--- Поиск

//разделяем мобилый и десктоп поиск
    let  searchInput;

    if( $(document).width() > 1023) {
        //console.log('desktop');
        searchInput = $('#searchInputBig');
    } else {
        //console.log('mobile');
        searchInput = $('#searchInputBigMobile');
        //console.log(searchInput);
    }

//показать результаты поиска
    function searchResultShow() {
        //показываем кнопку Отменить
        //buttonCancelSearch.show();

        //показываем спиннер пока работает поиск
        $('.search-spinner').fadeIn().delay(500).fadeOut(1000); //демо показа спиннера в строке поиска

        //крестик в поле поиска показываем когда результаты подргузились
        buttonClearSearch.fadeIn(1000);

        //скрываем стартовый экран
        $('.catalog-search-intro').hide();

        //показываем контент и футер
        $('.catalog-search-result').delay(500).fadeIn(500); //пауза совпадает с временем показа спиннера
        //$('.catalog-search-content').addClass('with-footer');
        //$('.searchbar-sticky-footer').delay(500).fadeIn(500);
    }

//отменить результаты поиска
    function searchResultClear() {
        //скрываем крестик в поле поиска
        buttonClearSearch.fadeOut(500);

        //показываем стартовый экран
        $('.catalog-search-intro').show();

        //скрываем контент и футер
        $('.catalog-search-result').hide();
        //$('.catalog-search-content').removeClass('with-footer');
        //$('.searchbar-sticky-footer').hide();
    }

//проверить инпут на предмет символов
    function searchResultCheck() {
        if ( searchInput.val().length > 0 ) {
            searchResultShow();
        } else
            searchResultClear();
    }

//при вводе символа в поле поиска
    searchInput.on('input', function() {
        searchResultCheck();
    });

//очистить прошлые запросы
    $('.catalog-search-queries__clear').click(function(e) {
        e.preventDefault();
        $('.catalog-search-queries__recent ul').remove();
        $('.catalog-search-queries__recent').remove();
    });


//по клику на запрос подставить его в поле поиска и показать подборки результатов
    $('.catalog-search-query').each(function() {
        $(this).click(function(e) {
            e.preventDefault();
            searchInput.val($(this).text());
            searchResultCheck();
        });
    });

//кнопка очистить поле ввода
    buttonClearSearch.click(function(e) {
        e.preventDefault();
        searchInput.val('');
        $(this).hide();

        //если десктоп, то еще и очистить результаты поиска
        if( $(document).width() > 1023) {
            searchResultClear();
        }
    });

//при прокрутке блока с результатами шапку фиксируем
    $('.catalog-search-content__inner').scroll(function() {
        if( $(this).scrollTop() >  0 ) {
            $('.searchbar-header').addClass('searchbar-sticky-header');
            $('.belleyou-sidebar-menu__header-mobile').addClass('searchbar-sticky-header');
        } else {
            $('.searchbar-header').removeClass('searchbar-sticky-header');
            $('.belleyou-sidebar-menu__header-mobile').removeClass('searchbar-sticky-header');
        }
    });


//--- МОБИЛЬНЫЙ поиск

//кнопка отмена поиска (для мобилки)
    buttonCancelSearch.click(function(e) {
        e.preventDefault();
        searchResultClear();
        searchInput.val('');
    });

//на фокус поля ввода
    $('#searchInputBigMobile').focus(function() {

        openSearchBar();
        buttonCancelSearch.show(300);

    });

    $('.form-catalog-search__cancel').click(function() {

        closeSearchBar();

        $('.belleyou-searchbar-mobile').hide();
        $('.belleyou-sidebar-menu__content').show();
        $('.belleyou-sidebar-menu__footer-mobile').show();

        buttonCancelSearch.hide(300);
    });


});