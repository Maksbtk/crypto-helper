$(function(){

    let siteHeader = $('.belleyou-header');
    let filterBar = $('#catalogFilter');
    let buttonOpenBar = $('#buttonOpenFilter');

    function openFilterBar() {
        $('body').addClass('disable-scroll');
        filterBar.toggleClass('_opened');

        siteHeader.removeClass('belleyou-header__transparent');
    }

    function closeFilterBar() {
        $('body').removeClass('disable-scroll');
        filterBar.removeClass('_opened');
    }    

    buttonOpenBar.click(function() {  
        openFilterBar();
    });

    $('*[data-close-filter]').click(function(e) {
        e.preventDefault();
        closeFilterBar();
    });


    //ФИЛЬТР
    //открыть-закрыть раздел фильтра
    $('.smartfilter-section-header').each(function(){
        $(this).on("click", function(){
            if ($(this).parents('.smartfilter-section').hasClass("_opened")) {
                $(this).parents('.smartfilter-section').removeClass('_opened');
            }else{
                $('.smartfilter-section').removeClass("_opened");
                
                $(this).parents('.smartfilter-section').addClass('_opened');    
            }
        });
    });

    //добавить выбранные фильтры в список
    function addFilter(i) {
        let filterText = i.text();
        let t = i.prev('.input-checkbox').attr('data-type');
        //console.log(filterText);
        //console.log(t);
        $('.smartfilter-section-' + t + ' .smartfilter-selected-items').append('<span class="smartfilter-selected-item">' + filterText + '</span>');
        
    }

    //применить фильтры
    function applyFilters() {
        $('.filter-checked').each(function(){               
            $('.smartfilter-section').removeClass('_opened');
        });
    }

    //очистить фильтры
    function clearAllFilters() {        
        $('.input-checkbox').prop('checked', false);
        $('.smartfilter-list-item').removeClass('filter-checked');
        $('.smartfilter-selected-item').remove();
    }

    //состояние по умолчанию
    //clearAllFilters(); //сняты все галочки
    //$('.buttons-filter-checked').hide(); //кнопки спрятаны
    //$('.button-close-filter').show();    

    //кликаем на чекбокс 
    $('.label-checkbox').each(function() {
        $(this).click(function(){

            if( ! $(this).prev().prop('checked') ) {
                $(this).parents('.smartfilter-list-item').addClass('filter-checked');
                addFilter($(this));
            } 
            else {
                $(this).parents('.smartfilter-list-item').removeClass('filter-checked');
                //removeFilter($(this));
            }


            //если отмечен хоть один фильтр меняем кнопки в футере
            if( $('.filter-checked') ) {
                $('.buttons-filter-checked').show();
                $('.button-close-filter').hide();
            } else {
                $('.buttons-filter-checked').hide();
                $('.button-close-filter').show();
            }

        });
    });

    //кнопка "Очистить"
    $('.button-clear-filter').click(function() {
        clearAllFilters();
    });

    //кнопка "Показать"
    $('.button-show-results').click(function() {
        applyFilters();
    });
});