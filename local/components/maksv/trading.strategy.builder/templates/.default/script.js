$(function() {

    //var arRes = window.jsArResultStrategyComponent;
    var externalLoader = $('.js-loader_external');
    var workTableexternalLoader = $('.js-loader_work_external');
    var errorTextBlock = $('.table_loading_block.error_text');
    var workTableErrorTextBlock = $('.work-table_loading_block.error_text');

    var tableTbodyEl = $('.js-strategy-tbody');
    var workTableTbodyEl = $('.js-work-strategy-tbody');

    var workTableActuality = $('.js-work_actuality');
    var workTableTimeMarkEl = $('.js-timeMark-work_actuality');

    var timeMarkEl = $('.js-timeMark-actuality');

    updateMainTable('updateJsonData', window.jsArResultStrategyComponent.arParams.MARKET_CODE, '', window.jsArResultStrategyComponent.arParams.PROFIT_FILTER);

    setInterval(function() {
        //updateMainTableLines('updateJsonData', window.jsArResultStrategyComponent.arParams.MARKET_CODE, window.jsArResultStrategyComponent.arParams.PROFIT_FILTER);
        updateMainTable('updateJsonData', window.jsArResultStrategyComponent.arParams.MARKET_CODE, '', window.jsArResultStrategyComponent.arParams.PROFIT_FILTER);
    }, 5000);

    /*   setInterval(function() {
        updateMainTableLines('updateJsonData', window.jsArResultStrategyComponent.arParams.MARKET_CODE, window.jsArResultStrategyComponent.arParams.PROFIT_FILTER);
    }, 2000);*/

    $('.js-update_button').click(function() {
        var lines = $('.js-strategy-tbody tr');

        // Собираем все символы
        var symbols = [];
        $.each(lines, function(index, element) {
            var symbol = $(element).data('val') ?? null;
            if (symbol) {
                symbols.push(symbol);
            }

            //if (index > 4)
                //return false;
        });

        // Переменная для отслеживания текущего индекса
        var currentIndex = 0;

        // Функция для вызова updateMainTableLine с интервалом
        function processNextSymbol() {
            if (currentIndex < symbols.length) {
                updateMainTableLine(symbols[currentIndex]);
                currentIndex++;
            } else {
                clearInterval(intervalId); // Останавливаем интервал после обработки всех символов
            }
        }

        var intervalId = setInterval(processNextSymbol, 500);
    });

    workTableActuality.click(function() {
        event.preventDefault();
        var symbols = $('.js-work-strategy-tbody tr').data('val') ?? null;

        if (symbols) {
            updateWorkTable(symbols, 'update' + window.jsArResultStrategyComponent.arParams.MARKET_CODE + 'Data');
        }
    });

    $(document).on("click",".js-strategy-tbody tr",function() {
        //console.log($(this))
        var symbols = $(this).data('val') ?? null;

        if (symbols) {
            workTableTbodyEl.html('');
            workTableTbodyEl.append($(this).clone());
            updateWorkTable(symbols, 'update' + window.jsArResultStrategyComponent.arParams.MARKET_CODE + 'Data');

            $('html, body').animate({ scrollTop: workTableTbodyEl.offset().top }, 1000);

        }
    });

    $(document).on("click",".js-go_up-page",function() {
        //console.log($(this))
        $('html, body').animate({ scrollTop: externalLoader.offset().top }, 1000);
    });

    function updateWorkTable(symbols, method = '') {

        workTableErrorTextBlock.fadeOut();
        workTableexternalLoader.fadeIn();

        var res = BX.ajax.runComponentAction('maksv:trading.strategy.builder', method, {
            mode: 'class',
            data: {symbols:symbols},
            timeout: 30000,
        }).then(function(response) {

            if (response.data.success == true) {
                workTableTbodyEl.html('');

                $.each(response.data.strategies, function (index, element) {

                    var profitClass = '';
                    var profitVal = parseFloat(element.profit);

                    if (profitVal >= 0.6)
                        profitClass = 'green-bg';
                    else if (profitVal >= 0.3 && profitVal < 0.6)
                        profitClass = 'yellow-bg';
                    else if (profitVal <= 0.3)
                        profitClass = 'red-bg';

                    workTableTbodyEl.append('<tr data-val="'+element.pair1+','+element.pair2+','+element.pair3+'">\n' +
                        '                            <td data-name="pair1">'+element.pair1+'-'+element.price1+'</td>\n' +
                        '                            <td data-name="pair2">'+element.pair2+'-'+element.price2+'</td>\n' +
                        '                            <td data-name="pair3">'+element.pair3+'-'+element.price3+'</td>\n' +
                        '                            <td data-name="profit" class="'+profitClass+'">'+element.profit+'</td>\n' +
                        '                            <td data-name="profitPercent" class="'+profitClass+'">'+element.profitPercent+'</td>\n' +
                        '                        </tr>');
                });

                workTableexternalLoader.fadeOut();
                workTableTimeMarkEl.text(response.data.timeMark)

            } else {
                if (!response.data.message)
                    response.data.message = 'Неизветная ошибка';

                workTableErrorTextBlock.text('Внимание! - ' + response.data.message);
                workTableErrorTextBlock.fadeIn();
                workTableexternalLoader.fadeOut();

            }
        }, function (response) {
            //сюда будут приходить все ответы, у которых status !== 'success'
            console.log('err');
            console.log(response);
            workTableErrorTextBlock.text('Произошла ошибка');
            workTableErrorTextBlock.fadeIn();

            workTableexternalLoader.fadeOut();
        });

    }

    //function updateMainTable(method = 'updateJsonData', symbols = '') {
    function updateMainTable(method = 'updateJsonData', code = '', symbols = '', profitFilter = false) {
        errorTextBlock.fadeOut();
        externalLoader.fadeIn();
        //updateTebleButton.addClass('disabled');

        var res = BX.ajax.runComponentAction('maksv:trading.strategy.builder', method, {
            mode: 'class',
            data: {symbols:symbols,code:code,profitFilter:profitFilter},
            timeout: 30000,
        }).then(function(response) {
            /*console.log('respons');
            console.log(response);*/

            if (response.data.success == true) {
                tableTbodyEl.html('');

                $.each(response.data.strategies, function (index, element) {

                    var profitClass = '';
                    var profitVal = parseFloat(element.profit);

                    if (profitVal >= 0.6)
                        profitClass = 'green-bg';
                    else if (profitVal >= 0.3 && profitVal < 0.6)
                        profitClass = 'yellow-bg';
                    else if (profitVal <= 0.3)
                        profitClass = 'red-bg';

                    tableTbodyEl.append('<tr data-val="'+element.pair1+','+element.pair2+','+element.pair3+'">\n' +
                        '                            <td data-name="pair1">'+element.pair1+'</td>\n' +
                        '                            <td data-name="pair2">'+element.pair2+'</td>\n' +
                        '                            <td data-name="pair3">'+element.pair3+'</td>\n' +
                        '                            <td data-name="profit" class="'+profitClass+'">'+element.profit+'</td>\n' +
                        '                            <td data-name="profitPercent" class="'+profitClass+'">'+element.profitPercent+'</td>\n' +
                        '                        </tr>');

                });

                timeMarkEl.text(response.data.timeMark)
            } else {
                if (!response.data.message)
                    response.data.message = 'Неизветная ошибка';

                errorTextBlock.text('Ошибка - ' + response.data.message);
                errorTextBlock.fadeIn();
            }
            externalLoader.fadeOut();
            //updateTebleButton.removeClass('disabled');

        }, function (response) {
            //сюда будут приходить все ответы, у которых status !== 'success'
            console.log('err');
            console.log(response);
            errorTextBlock.text('Произошла ошибка');
            errorTextBlock.fadeIn();

            externalLoader.fadeOut();
        });
    }

    function updateMainTableLines(method = 'updateJsonData', code = '', symbols = '', profitFilter = false) {

        //errorTextBlock.fadeOut();
        externalLoader.fadeIn();
        //updateTebleButton.addClass('disabled');

        var res = BX.ajax.runComponentAction('maksv:trading.strategy.builder', method, {
            mode: 'class',
            data: {symbols:symbols,code:code,profitFilter:profitFilter},
            timeout: 100000,
        }).then(function(response) {
            /*console.log('respons');
            console.log(response);*/

            if (response.data.success == true) {

                $.each(response.data.strategies, function (index, element) {

                    var line = $('.js-strategy-tbody tr[data-val="'+element.pair1+','+element.pair2+','+element.pair3+'"]');
                    var profitVal = parseFloat(element.profit);

                    if (profitVal >= 0.6)
                        profitClass = 'green-bg';
                    else if (profitVal >= 0.3 && profitVal < 0.6)
                        profitClass = 'yellow-bg';
                    else if (profitVal <= 0.3)
                        profitClass = 'red-bg';

                    line.children('[data-name="profit"]').text(element.profit).removeClass().addClass(profitClass);
                    line.children('[data-name="profitPercent"]').text(element.profitPercent).removeClass().addClass(profitClass);
                });

                timeMarkEl.text(response.data.timeMark)
            } else {
                console.log('9298 err - ' + symbols)
                console.log('9298 err response - ' + response)

                if (!response.data.message)
                    response.data.message = 'Неизветная ошибка';
                
                errorTextBlock.text('Ошибка - ' + response.data.message);
                errorTextBlock.fadeIn();
            }
            externalLoader.fadeOut();
            //updateTebleButton.removeClass('disabled');

        }, function (response) {
            //сюда будут приходить все ответы, у которых status !== 'success'
            console.log('9293 err - ' + symbols)
            /*errorTextBlock.text('Произошла ошибка');
            errorTextBlock.fadeIn();*/

            externalLoader.fadeOut();
        });
    }

});