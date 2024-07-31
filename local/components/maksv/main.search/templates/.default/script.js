$(function() {

    let searchPanel = $('#catalogSearch');
    let queries = $('.catalog-search-intro');
    let resultsPreview = $('.catalog-search-result');
    let inputField = $('._inputSearch');
    let countSuggest = $('.js-countSuggest');
    let countShowResultEl = $('.js-showResult');
    let suggestMaxCount = $('#suggest-max-count').val();
    let suggestShowCount = $('#suggest-show-count').val();
    let searchForm = $('.js-search-form');
    let collection = $('#searchCollection');
    let suggestList = $('#js-search-queries__list');
    let collectionsList = $('#js-collections__list');
    let searchCancel = $('.js-search__close');
    let clearCockie = $('.catalog-search-queries__clear');
    let buttonClearSearch = $('.js-clear-search');
    let searchSpinner = $('.search-spinner');

    let searchCancelMobile = $('.js-mobile-search__close');
    let searchBlockMobile = $('.mobile-search');
    let suggestShowCountMobile = $('#suggest-show-count-mobile').val();
    let inputFieldMobile = $('#searchInputBigMobile');
    let searchFormMobile = $('.js-search-form-mob');
    let buttonClearSearchMobile = $('.js-clear-search-mob');

    let transitionalSize = 1023;

    countShowResultEl.click(function() {
        event.preventDefault();
        if( $(document).width() < transitionalSize ) {
            searchFormMobile.trigger("submit");
        } else {
            searchForm.trigger("submit");
        }
    });

    //подставляем запросы из предложенных в поле поиска
    $(document).on("click",".catalog-search-query",function() {
        if( $(document).width() < transitionalSize ) {
            searchCancelMobile.hide();
            buttonClearSearchMobile.hide();

            $(inputFieldMobile).val($(this).text());
            searchFormMobile.trigger("submit");
        } else {
            searchCancel.hide();
            buttonClearSearch.hide();
            $(inputField).val($(this).text());
            searchForm.trigger("submit");
        }
    });

    //удалить историю поиска
    clearCockie.click(function() {
        setCookie('LAST_MAIN_SEARCH','', 0);
        $('.js-search-history').fadeOut(500);
    });

    // логика по истории поиска
    function createCookie (newLastSearch) {
        setCookie('cookie_agrmnt','Y', 30);//TODO: настроить принятие куков, затем удалить строку

        var cookieArg = getCookie('cookie_agrmnt');
        var lastSearchСookieAr = [];
        if (cookieArg == 'Y') {
            var cookieSearch = getCookie('LAST_MAIN_SEARCH');
            if (cookieSearch != 'undefined' &&  cookieSearch != null) {
                cookieSearch = cookieSearch.split(',');
                if ($.isArray(cookieSearch)) {
                    if (cookieSearch.includes(newLastSearch)) {
                        cookieSearch.splice(cookieSearch.indexOf(newLastSearch), 1);
                    }
                    cookieSearch.push(newLastSearch);
                    if (cookieSearch.length > 4) {
                        cookieSearch.shift();
                    }
                    setCookie('LAST_MAIN_SEARCH',cookieSearch, 30);
                }
            } else {
                var cookieSearch = [];
                cookieSearch.push(newLastSearch);
                setCookie('LAST_MAIN_SEARCH',cookieSearch, 30);
            }
        }
    }

    //просим у сервера предложени по поиску
    function getSearchSuggestion(fieldValue, suggestShowCount) {
        //const fieldValue = $(inputField).val();

        suggestList.html('');
        collectionsList.html('');

        suggestList.hide();
        collection.hide();
        countShowResultEl.hide();

        searchSpinner.fadeIn();

        var res = BX.ajax.runComponentAction('maksv:main.search', 'searchSuggestion', {
            mode: 'class',
            data: {query: fieldValue, suggestMaxCount: suggestMaxCount, suggestShowCount: suggestShowCount},
            timeout: 3000,
        }).then(function(response) {

            if (response.status === 'success') {
                suggestList.html('');
                collectionsList.html('');

                //если есть предложения по поиску, то вставляем эти предложения
                if (response.data.search.suggestion.length > 0) {

                    $.each(response.data.search.suggestion, function (index, element) {
                        suggestList.append('<li class="catalog-search-queries__item"><a class="catalog-search-query"><span class="query-current">'+element+'</span></a></li>');
                    });
                    suggestList.fadeIn(200);

                    if (response.data.collection.length > 0) {
                        $.each(response.data.collection, function (index, element) {

                            //создаем вертску цветов для товара
                            /*var colorHtml = ''
                            if (element.PROPERTY__COLOR_VALUE.length > 0) {
                                var countCollor = element.PROPERTY__COLOR_VALUE.length;
                                if (countCollor > 3) {
                                    countCollor = 3;
                                }
                                colorHtml += '                            <ul class="colors-slider__wrapper">\n';
                                for (let i = 0; i < countCollor; i++) {
                                    colorHtml += '                           <li class="colors-slider__item colors-slider__item--current">\n' +
                                        '                                        <span class="product-list1__color" style="background-image: url(' + element.PROPERTY__COLOR_VALUE[i].DETAIL_PICTURE + ');"></a>\n' +//.DETAIL_PICTURE.SRC
                                        '                                    </li>\n';
                                }
                                if (element.PROPERTY__COLOR_VALUE.length > 3) {
                                    colorHtml += '                            <li class="colors-slider__item more-colors">+' + (element.PROPERTY__COLOR_VALUE.length - 3) + ' цветов</li>\n';
                                }
                                colorHtml += '                             </ul>\n';
                            }*/

                            var wishlistClass = '';
                            var wishNotAuth = '';
                            if (response.data.userIsAuthorized == 'Y') {
                                wishlistClass = 'js-check-wishlist-button';
                                if (element.WISHLIST == 'Y') {
                                    wishlistClass = wishlistClass + ' _added';
                                }
                            } else if (response.data.userIsAuthorized == 'N'){
                                //wishNotAuth = 'data-popup="popup-go-to-auth"';
                                wishNotAuth = 'style="display:none;"';
                            }

                            collectionsList.append(
                                ' <li class="product-item">\n' +
                                '                                <div class="product-media-wrapper">\n' +
                                '                                    <a class="product-link" href="'+element.DETAIL_PAGE_URL+'">\n' +
                                '                                        <div class="product-picture-wrapper">\n' +
                                '                                            <img class="product-picture" src="'+element.PREVIEW_PICTURE+'" alt="'+element.NAME+'">\n' +
                                '                                        </div>\n' +
                                '                                    </a>\n' +
                                '                                    <a class="button-add-to-favorite '+ wishlistClass +'" data-id="'+element.ID+'" ' + wishNotAuth + '><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16"><path stroke="#A0BCD2" stroke-linecap="round" stroke-linejoin="round" d="M15.023.53c-2.047-.23-4.25.912-5.024 2.618C9.225 1.442 7 .301 4.976.53 2.28.823.553 3.57 1.099 6.22c.744 3.604 4.801 5.214 8.9 9.281 4.081-4.067 8.156-5.683 8.9-9.282.547-2.65-1.182-5.395-3.876-5.689z"></path></svg></a>\n' +
                                /* '                                    <div class="product-labels">\n' +
                                 '                                        <span class="product-label">New</span>\n' +
                                 '                                    </div>\n' +*/
                                '                                </div>\n' +
                                '                                <div class="product-info-wrapper">\n' +
                                '                                    <a class="product-link" href="'+element.DETAIL_PAGE_URL+'"><h4 class="product-name">'+element.NAME+'</h4></a>\n' +
                                '                                    <div class="product-pricebox">\n' +
                                /*  '                                        <span class="proudct-old-price">989 ₽</span>\n' +*/
                                '                                        <span class="proudct-current-price">'+element.PRICE+' ₽</span>\n' +
                                '                                    </div>\n' +
                                /*'                                    <div class="product-colors-sheme">\n' +
                                '                                        <ul class="product-colors-list">\n' +
                                '                                            <li class="product-color product-color__with-border">\n' +
                                '                                                <a style="background-image: url(https://belleyou.ru/upload/iblock/79e/79e96c531741ee2c701afdd9957d19d4.jpeg);"></a>\n' +
                                '                                            </li>\n' +
                                '                                            <li class="product-color">\n' +
                                '                                                <a style="background-image: url(https://belleyou.ru/upload/iblock/1f0/1f0aeea049dfac0a3d9d8f464e4381ab.jpeg);"></a>\n' +
                                '                                            </li>\n' +
                                '                                            <li class="product-color">\n' +
                                '                                                <a style="background-color: #000000;"></a>\n' +
                                '                                            </li>\n' +
                                '                                        </ul>\n' +
                                '                                        <span class="product-more-colors-label">+2 цвета</span>\n' +
                                '                                    </div>\n' +*/
                                '                                </div>\n' +
                                '                            </li>'
                            );

                        });

                        collection.fadeIn(500);
                    }

                } else {
                    suggestList.append(' <li class="">Ничего не нашли по запросу "'+response.data.secondNotFound+'"</li>');
                }

                if (parseInt(response.data.search.countSuggestion) > 0) {
                    countSuggest.text(response.data.search.countSuggestion);
                    countShowResultEl.fadeIn(500);
                }

                searchSpinner.fadeOut();
            }
        });
    }

    //решаем проблему со множественными ajax-запросами на сервер
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this,
                args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };

    function setCookie(name, value, daysToLive) {
        // Encode value in order to escape semicolons, commas, and whitespace
        var cookie = name + "=" + encodeURIComponent(value);
        if(typeof daysToLive === "number") {
            // Sets the max-age attribute so that the cookie expires after the specified number of days
            cookie += "; max-age=" + (daysToLive*24*60*60) +';path=/';
            document.cookie = cookie;
        }
    }

    function getCookie(name) {
        // Split cookie string and get all individual name=value pairs in an array
        var cookieArr = document.cookie.split(";");
        // Loop through the array elements
        for(var i = 0; i < cookieArr.length; i++) {
            var cookiePair = cookieArr[i].split("=");
            // Removing whitespace at the beginning of the cookie name and compare it with the given string
            if(name == cookiePair[0].trim()) {
                // Decode the cookie value and return
                return decodeURIComponent(cookiePair[1]);
            }
        }
        // Return null if not found
        return null;
    }

    if( $(document).width() >= transitionalSize ) {

        inputFieldHasText();

        //отправка ajax-запроса по вводу инпут в случае если 500мс ничего не вводим
        var debounceOnInput = debounce(inputFieldHasText, 500);
        inputField.on('input', debounceOnInput);

        //закрыть окно поиска
        $('*[data-close-searchbar]').click(function (e) {
            e.preventDefault();
            $('body').removeClass('disable-scroll');
            stopSearchAction();
            searchPanel.removeClass('_opened');
        });

        //клик на кнопку поиска в шапке открывает панель с поиском ДЕСКТОП
        $('#buttonSearchBarOpen').click(function () {
            $('body').addClass('disable-scroll');
            searchPanel.addClass('_opened');
            $('.belleyou-header').removeClass('belleyou-header__transparent');
            inputFieldHasText();
        });

        //отчистить инпут поиска
        searchCancel.click(function(event) {
            event.preventDefault();
            $('body').removeClass('disable-scroll');
            stopSearchAction();
            searchPanel.removeClass('_opened');
        });

        //отчистить инпут поиска
        buttonClearSearch.click(function(event) {
            event.preventDefault();
            stopSearchAction();
        });

        function stopSearchAction() {
            inputField.val('');
            inputField.trigger( "input" );
        }

        function inputFieldHasText() {
            const fieldValue = $(inputField).val();

            if (fieldValue.length >= 1) {
                //кнопки и блок результотов показать
                searchCancel.fadeIn(1000);
                buttonClearSearch.fadeIn(1000);
                resultsPreview.delay(500).fadeIn(500);
                //блок запросов скрыть
                queries.hide();

                getSearchSuggestion(fieldValue, suggestShowCount);
            } else {
                //кнопки и блок результатов скрыть
                searchCancel.fadeOut(500);
                buttonClearSearch.fadeOut(500);
                resultsPreview.hide();
                //блок запросов показать
                queries.show();
            }
        }

    } else {

        inputFieldHasTextMobile();

        var debounceOnInputMobile = debounce(inputFieldHasTextMobile, 500);
        inputFieldMobile.on('input', debounceOnInputMobile);

        //клик в поле ввода открывает панель с поиском
        inputFieldMobile.on('focus', function () {
            $('body').addClass('disable-scroll');
            searchPanel.addClass('_opened');
            resultsPreview.hide();
            queries.show();
            searchCancelMobile.fadeIn(500);
            return;
        });

        searchFormMobile.submit(function( event ) {
            //event.preventDefault();
            var newLastSearch = inputFieldMobile.val();
            if (newLastSearch !== '') {
                createCookie(newLastSearch);
            }
            return true;
        });

        //при закрытии сайдбара на мобилки чистим поле и скрываем блоки
        $('*[data-close-sidebar]').click(function (e) {
            e.preventDefault();
            $('body').removeClass('disable-scroll');
            stopSearchMobileAction();
            searchCancelMobile.fadeOut(500);
            searchPanel.removeClass('_opened');
        });

        //отменить поиск
        searchCancelMobile.click(function(event) {
            event.preventDefault();
            $('body').removeClass('disable-scroll');
            stopSearchMobileAction();
            searchCancelMobile.fadeOut(500);
            searchPanel.removeClass('_opened');
        });

        //отчистить инпут поиска
        buttonClearSearchMobile.click(function(event) {
            event.preventDefault();
            stopSearchMobileAction();
        });

        function stopSearchMobileAction() {
            inputFieldMobile.val('');
            inputFieldMobile.trigger( "input" );
        }

        function inputFieldHasTextMobile() {
            const fieldValue = $(inputFieldMobile).val();
            if (fieldValue.length >= 1) {
                //кнопки и блок результотов показать
                buttonClearSearchMobile.fadeIn(1000);
                resultsPreview.delay(500).fadeIn(500);
                //блок запросов скрыть
                queries.hide();

                getSearchSuggestion(fieldValue, suggestShowCount);
            } else {
                //кнопки и блок результатов скрыть
                buttonClearSearchMobile.fadeOut(500);
                resultsPreview.hide();
                //блок запросов показать
                queries.show();
            }
        }
    }

});