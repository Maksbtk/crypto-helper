$(function() {

    //var arRes = window.jsArResultStrategyComponent;
    var tableTbodyEl = $('.js-strategy-tbody');
    var timeMarkEl = $('.js-timeMark-actuality');
    var errorTextBlock = $('.table_loading_block.error_text');


    var oiTableTimeMarkEl = $('.js-timeMark-oi_actuality');
    var oiTableActuality = $('.js-oi_actuality');
    var oiTableErrorTextBlock = $('.open_interests_loading_block.error_text');
    var oiTableexternalLoader = $('.js-loader_oi_external');
    var oiTableTbodyEl = $('.js-open_interests-tbody');

    var orderBlockEl = $('.js-orderBlock');
    //var lvlsBlock = $('.js-lvls');
    var zonesBlock = $('.js-zones');

    var upperOrderBlockTable = $('.js-symbol_orderBlock-tbody .js-upperOrderBlock');
    var lowerOrderBlockTable = $('.js-symbol_orderBlock-tbody .js-lowerOrderBlock');

    //var upperLevelsTable = $('.js-symbol_levels-tbody .js-upperlvls');
    //var lowerLevelsTable = $('.js-symbol_levels-tbody .js-lowerlvls');

    var upperZonesTable = $('.js-symbol_zones-tbody .js-upperZones');
    var lowerZonesTable = $('.js-symbol_zones-tbody .js-lowerZones');

    var symbolInput = $('input[name="oi-symbol"]');
    var clearIcon = $('.clear-icon');

    var urlParams = {};
    $.each(decodeURIComponent(location.search.substr(1)).split('&'), function (index, element) {
        var splitElAr = element.split('=');
        if (splitElAr[0] && splitElAr[1])
            urlParams[splitElAr[0]] = splitElAr[1];
    });

    if (urlParams.analysis) {
        symbolInput.val(urlParams.analysis);
        symbolAnalysis(urlParams.analysis);
    } else {
        $('html, body').animate({ scrollTop: $('.page-startegy_table').offset().top + 150 }, 1000);
    }

    inOutClearInput();

    // Показываем крестик при вводе текста
    symbolInput.on('input', function () {
        inOutClearInput();
    });

    // Клик по крестику очищает поле
    clearIcon.on('click', function () {
        symbolInput.val('');
        clearIcon.fadeOut();
        symbolInput.focus(); // Возвращаем фокус на инпут
    });

    $(document).on("click",".js-go_up-page",function() {
        //console.log($(this))
        $('html, body').animate({ scrollTop: oiTableexternalLoader.offset().top - 150 }, 1000);
    });

    $('.js-strategy-tbody tr').on('dblclick', function () {
        var symbol = $(this).data('val') ?? null;
        symbolAnalysis(symbol);
        $('html, body').animate({ scrollTop: oiTableexternalLoader.offset().top - 150 }, 1000);
    })

    $('.js-oi-symbol_form').submit(function( event ) {
        event.preventDefault();
        symbolAnalysis();
    });

    /*oiTableActuality.click(function() {
        symbolAnalysis();
    });*/

    $('.timeframe-button').on('click', function () {
        if ($(this).hasClass('active')) {
            event.preventDefault();
        } else {

            $('.timeframe-button').removeClass('active'); // Убираем выделение с остальных кнопок
            $(this).addClass('active'); // Выделяем выбранную кнопку

            const selectedTimeframe = $(this).data('timeframe');

            var symbol = symbolInput.val() ?? null;
            if (symbol)
                updateOITable(symbol.trim().toUpperCase(), true);
        }
    });

    function symbolAnalysis(symbol = false) {
        if (!symbol)
            symbol = symbolInput.val() ?? null;

        setUrlParameter('analysis', symbol);
        symbolInput.val(symbol);
        inOutClearInput();

        console.log('oiTableClick', symbol)
        if (symbol) {
            updateOITable(symbol.trim().toUpperCase());
        }
    }

    function updateOITable(symbol, onlyZones = false) {

        oiTableErrorTextBlock.fadeOut();
        oiTableexternalLoader.fadeIn();
        symbolInput.attr('disabled','disabled');
        $('table').addClass('disabled-el');
        var selectedZoneTf = $('.timeframe-button.active').data('timeframe');

        var res = BX.ajax.runComponentAction('maksv:signal.trading.strategy.builder', 'bybitOI', {
            mode: 'class',
            data: {symbols:symbol, zoneTf: selectedZoneTf, onlyZones: onlyZones},
            timeout: 30000,
        }).then(function(response) {

            console.log('updateOITableResp', response)
            console.log('detectHeadAndShouldersRes', response.data.detectHeadAndShouldersRes)
            //console.log('accountRatioList', response.data.accountRatioList)
            //console.log('calculateSideVolumesRes', response.data.calculateSideVolumesRes)
            //console.log('crossMA', response.data.crossMA)
            //console.log('crossHistoryMA', response.data.crossHistoryMA)
            //console.log('detectFlat', response.data.detectFlat)
            //console.log('volumeMA', response.data.volumeMA)
            //console.log('ATRData', response.data.ATRData)

            //console.log('macdData', response.data.macdData)
            //console.log('lastMacd', response.data.lastMacd)
            //console.log('summaryOpenInterestOb', response.data.summaryOpenInterestOb)
            //console.log('detectOrderBlocksRes', response.data.detectOrderBlocksRes)

            if (response.data.success == true) {

                $('.js_open_interests-section').css('margin-bottom', '140px');

                if (!onlyZones) {

                    oiTableTbodyEl.html('');
                    $('.js-open_interests_table .js_contract_name').text(response.data.symbol);

                    var OI = response.data.OI;
                    var crossMA = response.data.crossMA ?? {"m5": [], "m15": [], "m30": [], "h1": [], "h4": [], "d1": []};
                    var sarData = response.data.sarData ?? {"m5": [], "m15": [], "m30": [], "h1": [], "h4": [], "d1": []};
                    var supertrendData = response.data.supertrendData ?? {"m5": [], "m15": [], "m30": [], "h1": [], "h4": [], "d1": []};
                    var priceChange = response.data.priceChange ?? {"m5": [], "m15": [], "m30": [], "h1": [], "h4": [], "d1": []};
                    var macdData = response.data.lastMacd ?? {"m5": [], "m15": [], "m30": [], "h1": [], "h4": [], "d1": []};
                    var ATRData = response.data.lastATR ?? {"m5": [], "m15": [], "m30": [], "h1": [], "h4": [], "d1": []};
                    //var timestapOI = response.data.timestapOI;

                    var intervals = ["m5", "m15", "m30", "h1", "h4", "d1"];

                    // Функция для безопасного получения значения или "-"
                    function formatValue(value, fixed = 6) {
                        return value !== undefined && value !== null ? (typeof value === 'number' ? value.toFixed(fixed) : value) : "-";
                    }

                    oiTableTbodyEl.append(
                        '<tr><td>Price / OI</td>' +
                        intervals.map(interval => '<td>' + formatValue(priceChange[interval], 2) + ' / ' + formatValue(OI[interval], 2) + '</td>').join('') +
                        '</tr>' +

                        '<tr><td>MA26 x EMA9</td>' +
                        intervals.map(interval => '<td>' + formatValue(crossMA[interval]) + '</td>').join('') +
                        '</tr>'  +

                        '<tr><td>MACD</td>' +
                        intervals.map(interval => {
                            const macd = macdData[interval];
                            //console.log(macd);

                            let divergenceText = '';
                            let extremesText = '';
                            let resMacdText = '';
                            let macdInputParams = '';
                            let distance = '';

                            if (macd) {
                                const { shortDivergenceTypeAr, longDivergenceTypeAr, extremes } = macd;
                                
                                // Проверка на шорт-дивергенцию
                                if (shortDivergenceTypeAr?.regular || shortDivergenceTypeAr?.hidden) {
                                    divergenceText = (shortDivergenceTypeAr.regular ? 'short <br> regular divergence' : '') +
                                        (shortDivergenceTypeAr.hidden ? 'short <br> hidden divergence' : '');
                                    extremesText = `High1: ${formatValue(extremes?.selected?.high?.priceHigh1.value)}<br>` +
                                        `High2: ${formatValue(extremes?.selected?.high?.priceHigh2.value)}`;

                                    macdInputParams = macd.inputParams;
                                    distance = macd.shortDivergenceDistance;
                                }

                                if (divergenceText.length >= 1)
                                    resMacdText += divergenceText + ' (' + distance + ')' + '<br>' + extremesText+ '<br>' + macdInputParams;

                                extremesText = '';
                                divergenceText = '';
                                macdInputParams = '';
                                distance = '';

                                // Проверка на лонг-дивергенцию
                                if (longDivergenceTypeAr?.regular || longDivergenceTypeAr?.hidden) {
                                    divergenceText = (longDivergenceTypeAr.regular ? 'long <br> regular divergence' : '') +
                                        (longDivergenceTypeAr.hidden ? 'long <br> hidden divergence' : '');
                                    extremesText = `low1 ${formatValue(extremes?.selected?.low?.priceLow1.value)}<br>` +
                                        `low2 ${formatValue(extremes?.selected?.low?.priceLow2.value)}`;

                                    macdInputParams = macd.inputParams;
                                    distance = macd.longDivergenceDistance;
                                }

                                if (divergenceText.length >= 1)
                                    resMacdText += '<br><br>' +  divergenceText + ' (' + distance + ')' + '<br>' + extremesText+ '<br>' + macdInputParams;

                                if (resMacdText.length == 0)
                                    resMacdText = 'no divergence'
                            }

                            //return '<td>' + divergenceText + '<br>' + extremesText+ '<br>' + macdInputParams + '</td>';
                            return '<td>' + resMacdText + '</td>';
                        }).join('') +
                        '</tr>' +

                        '<tr><td>SUPER<br>TREND</td>' +
                        intervals.map(interval => '<td>' + formatValue(supertrendData[interval]?.trend) + '<br>' + formatValue(supertrendData[interval]?.value) + '</td>').join('') +
                        '</tr>' +

                        '<tr><td>ATR</td>' +
                        intervals.map(interval => '<td>' + formatValue(ATRData[interval])?.atr + '<br>' + formatValue(ATRData[interval])?.longTP + '<br>' + formatValue(ATRData[interval])?.shortTP + '</td>').join('') +
                        '</tr>' /*+

                        '<tr><td>timestap</td>' +
                        intervals.map(interval => '<td>' + formatValue(timestapOI[interval]) + '</td>').join('') +
                        '</tr>'*/
                    );
                }

                upperZonesTable.html('');
                lowerZonesTable.html('');
                var zones = response.data.supportResistanceZonesRes;

                if (zones.resistance.length > 0) {
                    upperZonesTable.append('<td class="red-bg">hits<br>volume<br>distance<br>upper<br>lower</td>');
                    $.each(zones.resistance, function (index, element) {
                        upperZonesTable.append('<td class="red-bg">' + element.hits + '<br>' + element.volume.toFixed(0) + '<br>' + element.distance.toFixed(1) + ' %' + '<br>' + element.upper.toFixed(5) + '<br>' + element.lower.toFixed(5) + '</td>');
                    });
                }

                if (zones.support.length > 0) {
                    lowerZonesTable.append('<td class="green-bg">hits<br>volume<br>distance<br>upper<br>lower</td>');
                    $.each(zones.support, function (index, element) {
                        lowerZonesTable.append('<td class="green-bg">' + element.hits + '<br>' + element.volume.toFixed(0) + '<br>' + element.distance.toFixed(1) + ' %' + '<br>' + element.upper.toFixed(5) + '<br>' + element.lower.toFixed(5) + '</td>');
                    });
                }

                zonesBlock.fadeIn();
                zonesBlock.css('display', 'block');
                
                upperOrderBlockTable.html('');
                lowerOrderBlockTable.html('');
                var orderBlock = response.data.detectOrderBlocksRes;

                if (orderBlock.bullish.length > 0) {
                    lowerOrderBlockTable.append('<td class="green-bg">strength<br>distance<br>upper<br>lower</td>');
                    $.each(orderBlock.bullish, function (index, element) {
                        lowerOrderBlockTable.append('<td class="green-bg">' + element.strength.toFixed(1) + ' %<br>' + element.distance.toFixed(1) + ' %<br>' + element.upper.toFixed(5) + '<br>' + element.lower.toFixed(5) +'</td>');
                    });
                }

                if (orderBlock.bearish.length > 0) {
                    upperOrderBlockTable.append('<td class="red-bg">average<br>strength<br>distance<br>upper<br>lower</td>');
                    $.each(orderBlock.bearish, function (index, element) {
                        upperOrderBlockTable.append('<td class="red-bg">' + element.strength.toFixed(1) + ' %<br>' + element.distance.toFixed(1) + ' %<br>' + element.upper.toFixed(5) + '<br>' + element.lower.toFixed(5) +'</td>');
                    });
                } 

                orderBlockEl.fadeIn();
                orderBlockEl.css('display', 'block');

                /*upperLevelsTable.html('');
                lowerLevelsTable.html('');
                var levels = response.data.levels;

                if (levels.upper) {
                    upperLevelsTable.append('<td class="green-bg">volume<br>distance<br>price</td>');
                    $.each(levels.upper, function (index, element) {
                        upperLevelsTable.append('<td class="green-bg">' + element.volume_percent.toFixed(2) + ' %<br>' + element.distance.toFixed(1) + ' %' + '<br>' + element.price.toFixed(5) + '</td>');
                    });
                }

                if (levels.lower) {
                    lowerLevelsTable.append('<td class="red-bg">volume<br>distance<br>price<br>');
                    $.each(levels.lower, function (index, element) {
                        lowerLevelsTable.append('<td class="red-bg">' + element.volume_percent.toFixed(2) + ' %<br>' + element.distance.toFixed(1) + ' %' + '<br>' + element.price.toFixed(5) + '</td>');
                    });
                }

                lvlsBlock.fadeIn();
                lvlsBlock.css('display', 'block');*/

                oiTableexternalLoader.fadeOut();
                oiTableTimeMarkEl.text(response.data.timeMark)
                //symbolInput.removeClass('disabled-input');
                symbolInput.removeAttr('disabled');
                $('table').removeClass('disabled-el');

            } else {
                if (!response.data.message)
                    response.data.message = 'Неизветная ошибка';

                oiTableErrorTextBlock.text('Внимание! - ' + response.data.message);
                oiTableErrorTextBlock.fadeIn();
                oiTableexternalLoader.fadeOut();

                symbolInput.removeAttr('disabled');
                $('table').removeClass('disabled-el');
            }
        }, function (response) {
            //сюда будут приходить все ответы, у которых status !== 'success'
            console.log('err');
            console.log(response);
            oiTableErrorTextBlock.text('Произошла ошибка');
            oiTableErrorTextBlock.fadeIn();

            oiTableexternalLoader.fadeOut();
        });
    }

    function inOutClearInput() {
        if (symbolInput.val().length > 0) {
            clearIcon.fadeIn();
        } else {
            clearIcon.fadeOut();
        }
    }

    function setUrlParameter(key, value) {
        var url = new URL(window.location.href); // Получаем текущий URL
        url.searchParams.set(key, value); // Устанавливаем/обновляем параметр
        window.history.pushState({}, '', url); // Обновляем URL без перезагрузки страницы
    }


    // btcinfo
    // Функция закрытия везде
    function closeAll() {
        $('.btc-info-overlay').remove();
        $('.btc-info-wrapper').removeClass('active');
    }

    // Открытие: добавляем overlay и active
    function openWrapper($w) {
        $w.addClass('active');
        const $ov = $('<div class="btc-info-overlay"></div>');
        $('body').append($ov);
        $ov.on('click', closeAll);
    }

    // Нажатие
    $(document).on('click', '.info-icon', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (window.matchMedia('(max-width: 768px)').matches) {
            const $w = $(this).closest('.btc-info-wrapper');
            if ($w.hasClass('active')) {
                closeAll();
            } else {
                closeAll();
                openWrapper($w);
            }
        }
    });

    // Закрытие кликом вне wrapper
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btc-info-wrapper').length) {
            closeAll();
        }
    });

    // Закрытие на скролле или тач-движении
    $(window).on('scroll touchmove', closeAll);
});