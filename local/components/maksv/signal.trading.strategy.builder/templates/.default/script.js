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

    var lvlsBlock = $('.js-lvls');

    var upperLevelsTable = $('.js-symbol_levels-tbody .js-upperlvls');
    var lowerLevelsTable = $('.js-symbol_levels-tbody .js-lowerlvls');

    $('html, body').animate({ scrollTop: $('.page-startegy_table').offset().top + 150 }, 1000);

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

   /* oiTableActuality.click(function() {
        symbolAnalysis();
    });*/

    var symbolInput = $('input[name="oi-symbol"]');

    function symbolAnalysis (symbol = false) {

        if (!symbol)
            symbol = symbolInput.val() ?? null;

        symbolInput.val(symbol)

        console.log('oiTableClick', symbol)
        if (symbol) {
            updateOITable(symbol.trim().toUpperCase());
        }
    }

    function updateOITable(symbol) {

        oiTableErrorTextBlock.fadeOut();
        oiTableexternalLoader.fadeIn();
        symbolInput.attr('disabled','disabled');
        $('table').addClass('disabled-el');

        var res = BX.ajax.runComponentAction('maksv:signal.trading.strategy.builder', 'bybitOI', {
            mode: 'class',
            data: {symbols:symbol},
            timeout: 30000,
        }).then(function(response) {

            console.log('findLevelsRes', response.data.identifyZonesResDev)
            console.log('updateOITableResp', response)

            if (response.data.success == true) {
                oiTableTbodyEl.html('');

                upperLevelsTable.html('');
                lowerLevelsTable.html('');

                $('.js-open_interests_table .js_contract_name').text(response.data.symbol);

                /*var OI = response.data.OI;
                var crossMA = response.data.crossMA;
                var sarData = response.data.sarData;
                var supertrendData = response.data.supertrendData;
                var priceChange = response.data.priceChange;
                var timestapOI = response.data.timestapOI;
                oiTableTbodyEl.append(
                    '<tr>\n' +
                    '                            <td>Price / OI</td>\n' +
                    '                            <td>'+priceChange.m15 + ' / ' + OI.m15 + '</td>\n' +
                    '                            <td>'+priceChange.m30+ ' / ' + OI.m30 + '</td>\n' +
                    '                            <td>'+priceChange.h1+ ' / ' + OI.h1 + '</td>\n' +
                    '                            <td>'+priceChange.h4+ ' / ' + OI.h4 + '</td>\n' +
                    '                            <td>'+priceChange.d1+ ' / ' + OI.d1 + '</td>\n' +
                    '                        </tr>' +
                    '<tr>\n' +
                    '                            <td>MA x EMA</td>\n' +
                    '                            <td>'+crossMA.m15+'</td>\n' +
                    '                            <td>'+crossMA.m30+'</td>\n' +
                    '                            <td>'+crossMA.h1+'</td>\n' +
                    '                            <td>'+crossMA.h4+'</td>\n' +
                    '                            <td>'+crossMA.d1+'</td>\n' +
                    '                        </tr>' +
                    '<tr>\n' +
                    '                            <td>SAR</td>\n' +
                    '                            <td>'+sarData.m15.trend+'<br>'+sarData.m15.sar_value.toFixed(5)+'</td>\n' +
                    '                            <td>'+sarData.m30.trend+'<br>'+sarData.m30.sar_value.toFixed(5)+'</td>\n' +
                    '                            <td>'+sarData.h1.trend+'<br>'+sarData.h1.sar_value.toFixed(5)+'</td>\n' +
                    '                            <td>'+sarData.h4.trend+'<br>'+sarData.h4.sar_value.toFixed(5)+'</td>\n' +
                    '                            <td>'+sarData.d1.trend+'<br>'+sarData.d1.sar_value.toFixed(5)+'</td>\n' +
                    '                        </tr>' +
                    '<tr>\n' +
                    '                            <td>SUPER<br>TREND</td>\n' +
                    '                            <td>'+supertrendData.m15.trend+'<br>'+supertrendData.m15.value.toFixed(5)+'</td>\n' +
                    '                            <td>'+supertrendData.m30.trend+'<br>'+supertrendData.m30.value.toFixed(5)+'</td>\n' +
                    '                            <td>'+supertrendData.h1.trend+'<br>'+supertrendData.h1.value.toFixed(5)+'</td>\n' +
                    '                            <td>'+supertrendData.h4.trend+'<br>'+supertrendData.h4.value.toFixed(5)+'</td>\n' +
                    '                            <td>'+supertrendData.d1.trend+'<br>'+supertrendData.d1.value.toFixed(5)+'</td>\n' +
                    '                        </tr>' +
                    '<tr>\n' +
                    '                            <td>timestap</td>\n' +
                    '                            <td>'+timestapOI.m15+'</td>\n' +
                    '                            <td>'+timestapOI.m30+'</td>\n' +
                    '                            <td>'+timestapOI.h1+'</td>\n' +
                    '                            <td>'+timestapOI.h4+'</td>\n' +
                    '                            <td>'+timestapOI.d1+'</td>\n' +
                    '                        </tr>'
                );*/

                var OI = response.data.OI;
                var crossMA = response.data.crossMA;
                var sarData = response.data.sarData;
                var supertrendData = response.data.supertrendData;
                var priceChange = response.data.priceChange;
                var timestapOI = response.data.timestapOI;

                var intervals = ["m15", "m30", "h1", "h4", "d1"];

                // Функция для безопасного получения значения или "-"
                function formatValue(value, fixed = 6) {
                    return value !== undefined && value !== null ? (typeof value === 'number' ? value.toFixed(fixed) : value) : "-";
                }

                oiTableTbodyEl.append(
                    '<tr><td>Price / OI</td>' +
                    intervals.map(interval => '<td>' + formatValue(priceChange[interval], 2) + ' / ' + formatValue(OI[interval], 2) + '</td>').join('') +
                    '</tr>' +

                    '<tr><td>MA x EMA</td>' +
                    intervals.map(interval => '<td>' + formatValue(crossMA[interval]) + '</td>').join('') +
                    '</tr>' +

                    '<tr><td>SAR</td>' +
                    intervals.map(interval => '<td>' + formatValue(sarData[interval]?.trend) + '<br>' + formatValue(sarData[interval]?.sar_value) + '</td>').join('') +
                    '</tr>' +

                    '<tr><td>SUPER<br>TREND</td>' +
                    intervals.map(interval => '<td>' + formatValue(supertrendData[interval]?.trend) + '<br>' + formatValue(supertrendData[interval]?.value) + '</td>').join('') +
                    '</tr>' +

                    '<tr><td>timestap</td>' +
                    intervals.map(interval => '<td>' + formatValue(timestapOI[interval]) + '</td>').join('') +
                    '</tr>'
                );

                var levels = response.data.levels;

                $.each(levels.lower, function(index, element) {
                    lowerLevelsTable.append('<td class="red-bg">' + element.price + '<br>' + element.volume + '<br>' + element.percent_from_last_close + '%' + '</td>');
                });

                $.each(levels.upper, function(index, element) {
                    upperLevelsTable.append('<td class="green-bg">' + element.price + '<br>' + element.volume + '<br>' + element.percent_from_last_close + '%' + '</td>');
                });

                lvlsBlock.fadeIn();
                lvlsBlock.css('display', 'block');
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
    

});