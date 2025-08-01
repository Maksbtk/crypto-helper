$(document).ready(function () {

    //cursor
    // общий плагин для рисования crosshair
    const crosshairPlugin = {
        id: 'crosshair',
        afterInit(chart) {
            // создаём crosshair объект, если ещё нет
            //chart.crosshair = {x: null};
            chart.crosshair = {x: null, y: null, active: false};
        },
        afterEvent(chart, args) {
            const e = args.event;

            if (!chart.crosshair) return; // защита

            if (e.type === 'mouseout') {
                chart.crosshair.x = null;
                chart.draw();
                return;
            }

            const xScale = chart.scales.x;
            if (!xScale) return;

            const canvasPosition = Chart.helpers.getRelativePosition(e, chart);
            if (canvasPosition.x >= xScale.left && canvasPosition.x <= xScale.right) {
                chart.crosshair.x = canvasPosition.x;
                chart.draw();
            }
        },
        afterDraw(chart) {
            const ch = chart.crosshair;
            if (!ch || ch.x == null) return;

            const ctx = chart.ctx;
            // Раскрываем chartArea
            const {top, bottom, left, right} = chart.chartArea;
            // Берём координаты из объекта crosshair
            const x = ch.x;
            const y = ch.y;

            ctx.save();
            ctx.strokeStyle = 'rgba(14, 14, 42, 0.7)';
            ctx.setLineDash([4, 4]);

            // 1) Горизонтальная линия — только для активного графика
            if (ch.active && y != null) {
                ctx.beginPath();
                ctx.moveTo(left, y);
                ctx.lineTo(right, y);
                ctx.stroke();
            }

            // 2) Вертикальная линия — для всех
            ctx.beginPath();
            ctx.moveTo(x, top);
            ctx.lineTo(x, bottom);
            ctx.stroke();
            ctx.restore();
        }
    };

    // регистрируем плагин
    Chart.register(crosshairPlugin);
    var defaultChartInterval = $('#tfInterval').val();

    function aggregate(data, tf) {
        var grouped = {};
        data.forEach(pt => {
            var m = moment(pt.t, ['DD.MM.YYYY HH:mm:ss', 'YYYY-MM-DD HH:mm:ss']);
            switch (tf) {
                case '3day':
                    // приведём дату к началу блока 3‑дневки:
                    // сначала к дню, а потом задаём номер блока
                    m.startOf('day');
                    // сколько дней от начала эпохи
                    var days = Math.floor(m.diff(moment(0), 'days'));
                    // номер блока
                    var block = Math.floor(days / 3);
                    // начало блока
                    m = moment(0).add(block * 3, 'days');
                    break;
                case 'month':
                    m.startOf('month');
                    break;
                case 'week':
                    m.startOf('week');
                    break;
                case '4hour':
                    m.hour(Math.floor(m.hour() / 4) * 4).minute(0).second(0);
                    break;
                case 'hour':
                    m.minute(0).second(0);
                    break;
                case 'day': // day
                    m.hour(0).minute(0).second(0);
                    break
            }
            grouped[m.valueOf()] = {t: m.format('DD.MM.YYYY HH:mm:ss'), y: pt.y};
        });
        return Object.values(grouped)
            .sort((a, b) => moment(a.t, 'DD.MM.YYYY HH:mm:ss') - moment(b.t, 'DD.MM.YYYY HH:mm:ss'));
    }

    //кривая доходности
    var sliceEquityData = aggEquityPoints.slice(); // ваши точки
    var ctx = document.getElementById('equityChart');
    var equityChart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [{
                label: 'Кривая баланса',
                data: aggregate(sliceEquityData, defaultChartInterval),
                clip: false,
                //borderColor: 'rgba(14, 14, 42, 0.8)',
                //backgroundColor: 'rgba(0, 123, 255, 0.2)',
                /* borderColor: 'rgba(0, 102, 51, 0.7)',
                backgroundColor: 'rgba(0, 102, 51, 0.1)',*/
                pointRadius: 10,
                borderWidth: 2,
                fill: true,
                tension: 0.2,
                segment: {
                    // линия красная, если хотя бы одна из точек сегмента ниже нуля
                    borderColor: ctx =>
                        (ctx.p0.parsed.y < 0 || ctx.p1.parsed.y < 0)
                            ? 'rgba(255, 3, 3, 0.7)'
                            : 'rgba(0, 102, 51, 0.7)',
                    // заливка тоже в том же цветовом ключе
                    backgroundColor: ctx =>
                        (ctx.p0.parsed.y < 0 || ctx.p1.parsed.y < 0)
                            ? 'rgba(255, 3, 3, 0.1)'
                            : 'rgba(0, 102, 51, 0.1)'
                },
                pointBackgroundColor: ctx =>
                    (ctx.parsed.y < 0)
                        ? 'rgba(255, 3, 3, 0.1)'
                        : 'rgba(0, 102, 51, 0.1)',
                pointBorderColor: ctx =>
                    (ctx.parsed.y < 0)
                        ? 'rgba(255, 3, 3, 0.7)'
                        : 'rgba(0, 102, 51, 0.7)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            parsing: {xAxisKey: 't', yAxisKey: 'y'},
            scales: {
                x: {
                    type: 'time',
                    time: {
                        parser: 'DD.MM.YYYY HH:mm:ss',
                        tooltipFormat: 'DD.MM.YYYY HH:mm',
                        displayFormats: {hour: 'DD.MM HH:mm', day: 'DD.MM.YYYY'}
                    },
                    grid: {color: '#f0f0f0'}
                },
                y: {
                    label: 'Профит ($)',
                    grid: {color: '#f0f0f0'}
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {label: ctx => ' ' + ctx.parsed.y.toFixed(2) + ' $'}
                },
                legend: {
                    display: false
                }
            }
        }
    });

    // просадка
    var sliceDrawdownData = drawdownData.slice(); // ваши точки
    var ddCtx = document.getElementById('drawdownChart');
    var drawdownChart = new Chart(ddCtx, {
        type: 'line',
        data: {
            datasets: [{
                label: 'Просадка (%)',
                data: aggregate(sliceDrawdownData, defaultChartInterval),//drawdownData,
                clip: false,
                borderColor: 'rgba(255, 3, 3, 0.7)',
                backgroundColor: 'rgba(255, 3, 3, 0.1)',
                pointRadius: 10,
                borderWidth: 2,
                fill: true,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            parsing: {xAxisKey: 't', yAxisKey: 'y'},
            scales: {
                x: {
                    type: 'time',
                    time: {
                        parser: 'DD.MM.YYYY HH:mm:ss',
                        tooltipFormat: 'DD.MM.YYYY HH:mm',
                        displayFormats: {day: 'DD.MM.YYYY'}
                    },
                    grid: {color: '#f0f0f0'}
                },
                y: {
                    title: {display: true, text: 'Просадка (%)'},
                    grid: {color: '#f0f0f0'},
                    // обычно drawdown — отрицательные значения
                    min: Math.min(...drawdownData.map(p => p.y)),
                    max: 0
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {label: ctx => ctx.parsed.y.toFixed(2) + ' %'}
                },
                legend: {
                    display: false
                }
            }
        }
    });

    //распределение доодности
    var values = returnBuckets.slice().sort((a, b) => a - b);
    var min = values[0], max = values[values.length - 1];
    var binCount = 20;
    var binSize = (max - min) / binCount;
    var bins = Array(binCount).fill(0);
    values.forEach(v => {
        var idx = Math.min(binCount - 1, Math.floor((v - min) / binSize));
        bins[idx]++;
    });
    var labels = bins.map((_, i) => {
        var start = (min + i * binSize).toFixed(1);
        var end = (min + (i + 1) * binSize).toFixed(1);
        return start + '…' + end;
    });
    var ctxH = document.getElementById('histProfitChart');
    new Chart(ctxH, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Count',
                data: bins,
                //backgroundColor: 'rgba(0, 123, 255, 0.6)',
                //borderColor:     'rgba(0, 123, 255, 1)',
                borderColor: 'rgba(14, 14, 42, 0.7)',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {title: {display: true, text: 'Профит %'}, grid: {display: false}},
                y: {title: {display: true, text: 'Количество сделок'}, beginAtZero: true}
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.parsed.y + ' сделок'
                    }
                },
                legend: {display: false}
            }
        }
    });

    //winrate chate
    function aggregateWinRate(data, tf) {
        const grouped = {};
        data.forEach(pt => {
            let m = moment(pt.t, ['DD.MM.YYYY HH:mm:ss', 'YYYY-MM-DD HH:mm:ss']);
            if (!m.isValid()) return;

            switch (tf) {
                case '3day':
                    // приводим к началу дня…
                    m.startOf('day');
                    // считаем, сколько дней от эпохи
                    const days = Math.floor(m.diff(moment(0), 'days'));
                    // какой это 3-дневный блок
                    const block = Math.floor(days / 3);
                    // начало блока = эпоха + block*3 дней
                    m = moment(0).add(block * 3, 'days');
                    break;
                case 'month':
                    m.startOf('month');
                    break;
                case 'week':
                    m.startOf('week');
                    break;
                case '4hour':
                    m.hour(Math.floor(m.hour() / 4) * 4).minute(0).second(0);
                    break;
                case 'hour':
                    m.minute(0).second(0);
                    break;
                case 'day': // day
                    m.hour(0).minute(0).second(0);
                    break
            }

            const key = m.valueOf();
            if (!grouped[key]) grouped[key] = {t: m.format('DD.MM.YYYY HH:mm:ss'), wins: 0, cnt: 0};
            grouped[key].wins += pt.win;
            grouped[key].cnt += 1;
        });

        return Object.values(grouped)
            .map(o => ({
                t: o.t,
                y: +(o.wins / o.cnt * 100).toFixed(2),
                cnt: o.cnt,
                wins: o.wins
            }))
            .sort((a, b) =>
                moment(a.t, 'DD.MM.YYYY HH:mm:ss') - moment(b.t, 'DD.MM.YYYY HH:mm:ss')
            );
    }

    var vinRateBoard = 60;
    let winData = aggregateWinRate(winRateChartArr, defaultChartInterval);
    const ctxW = document.getElementById('winrateChart');
    var winrateChart = new Chart(ctxW, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: `Винрейт`,
                    data: winData,
                    clip: false,
                    borderColor: 'rgba(14, 14, 42, 0.7)',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    pointRadius: 10,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2
                },
                {
                    label: vinRateBoard + ' %',
                    data: winData.map(pt => ({t: pt.t, y: vinRateBoard})), // y=65 на всех t
                    borderColor: 'rgba(255, 3, 3, 0.5)',
                    backgroundColor: 'rgba(255, 3, 3, 0.1)',
                    borderWidth: 2,
                    borderDash: [4, 4],
                    pointRadius: 0,
                    fill: false,
                    tension: 0,
                    tooltip: {enabled: false}
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            parsing: {xAxisKey: 't', yAxisKey: 'y'},
            scales: {
                x: {
                    type: 'time',
                    time: {
                        parser: 'DD.MM.YYYY HH:mm:ss',
                        tooltipFormat: 'DD.MM.YYYY HH:mm',
                        displayFormats: {
                            day: 'DD.MM.YYYY',
                            hour: 'DD.MM HH:mm'
                        }
                    },
                    grid: {color: '#f0f0f0'}
                },
                y: {
                    title: {display: true, text: 'Винрейт (%)'},
                    min: 0,
                    max: 100,
                    grid: {color: '#f0f0f0'}
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: context => {
                            const {y, cnt} = context.raw;   // raw – это {t, y, cnt}
                            return `винрейт: ${y.toFixed(1)} % | сделок: ${cnt} шт`;
                        }
                    }
                },
                legend: {display: false}
            }
        }
    });

    $('#tfInterval').on('change', function () {
        var tf = $(this).val();
        $('input#chartIntervalFilter').val(tf);

        var aggEquity = aggregate(sliceEquityData, tf);
        equityChart.data.datasets[0].data = aggEquity;
        equityChart.update();

        var aggDrawdown = aggregate(sliceDrawdownData, tf);
        drawdownChart.data.datasets[0].data = aggDrawdown;
        drawdownChart.update();

        const aggWin = aggregateWinRate(winRateChartArr, tf);
        winrateChart.data.datasets[0].data = aggWin;
        winrateChart.data.datasets[1].data = aggWin.map(pt => ({t: pt.t, y: vinRateBoard}));
        winrateChart.update();
    });
    //!-chart

    //cursor
    const charts = [equityChart, drawdownChart, winrateChart];
    const canvases = charts.map(c => c.canvas);

    // где‑то в начале вашего скрипта
    let lastActiveKey = null;

    // общий обработчик движения
    function handlePointerMove(e) {

        // 1) на каком графике мы?
        const source = charts.find(c => c.canvas === e.target);
        if (!source) return;

        // 2) локальный X внутри этого canvas
        const rect = source.canvas.getBoundingClientRect();
        //const x = e.clientX - rect.left;
        const x = Math.round(e.clientX - rect.left);

        // 3) синхронизируем crosshair на всех:
        charts.forEach(ch => {
            //ch.crosshair.x = x;
            // активируем горизонталь только на source
            ch.crosshair.x = x;
            ch.crosshair.active = (ch === source);
            if (ch.crosshair.active) {
                // запомним Y для горизонтали
                const pos = Chart.helpers.getRelativePosition(e, ch);
                ch.crosshair.y = Math.round(pos.y);
            }
            ch.draw();                // здесь мы НЕ трогаем тултипы
        });

        // 4) на исходном графике ищем ближайшую точку строго по X
        //const pts = source.getElementsAtEventForMode(
        let pts = source.getElementsAtEventForMode(
            e,
            'index',//'nearest',
            { intersect: false },//{intersect: false, axis: 'x'},
            false
        );
        pts = pts.filter(p => p.datasetIndex === 0);

        if (pts.length) {
            // составляем ключ, чтобы понять, сменилась ли точка
            const p = pts[0];
            const key = `${source.canvas.id}|${p.datasetIndex}|${p.index}`;

            if (key !== lastActiveKey) {
                lastActiveKey = key;
                // показываем тултип
                source.tooltip.setActiveElements(pts, {
                    x: e.clientX,
                    y: e.clientY
                });
                source.draw(); // и перерисовываем уже с тултипом
            }
        }
        // **если pts.length === 0**, ничего не делаем — тултип остаётся на последней
        
    }

    // отдельный handler на выход из canvas
    function handlePointerLeave(e) {
        // сбросим все тултипы + crosshair
        charts.forEach(ch => {
            ch.tooltip.setActiveElements([], {});
            //ch.crosshair.x = null;
            ch.crosshair.x = ch.crosshair.y = null;
            ch.crosshair.active = false;
            ch.draw();
        });
        lastActiveKey = null;
    }

    // подписываемся
    canvases.forEach(canvas => {
        canvas.addEventListener('pointermove', handlePointerMove);
        canvas.addEventListener('pointerleave', handlePointerLeave);
    });

    //!-cursor


    function sendAIAnalysis(filterFunc, promptIntro) {
        var trades = finalResults.res.filter(filterFunc);
        if (!trades.length) {
            alert('Нет сделок для анализа');
            return;
        }
        var payload = {
            filters: {
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                riskFilter: $('#riskFilter').val(),
                tpCountGeneral: $('#tpCountGeneral').val(),
                tpFilter: finalResults.selectedTpStrategy,
                direction: $('#directionFilter').val(),
                amountInTrade: $('#deposit').val(),
                tf: $('#tfFilter').val(),
                entry: $('#entryFilter').val(),
                strategy: $('#strategyFilter').val() || null,
                moveeSLafterReachingTP: $('#shiftSL').val() || 'не сдвигать SL',
            },
            trades: trades,
            aiModel: 'deepseek',//'gpt',
            promptIntro: promptIntro,
        };

        $('#aiAnalyzeResult').html('<em>Идёт запрос к ИИ…</em>');
        $.ajax({
            url: '/ajax/aiStatAnalyze.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function (resp) {
                console.log('trades:', trades);
                console.log('Prompt:', resp.messages[1].content);

                if (resp.error) {
                    $('#aiAnalyzeResult').html('<span style="color:red;">Ошибка: ' + resp.error + '</span>');
                } else {
                    $('#aiAnalyzeResult').html(
                        '<textarea readonly style="width:100%;height:300px;padding:10px;border:1px solid #ccc;border-radius:4px;">'
                        + "\n" + resp.analysis
                        + '</textarea>'
                    );
                }
            },
            error: function () {
                $('#aiAnalyzeResult').html('<span style="color:red;">Серверная ошибка</span>');
            }
        });
    }

    $('#btnAiAnalyze').on('click', function () {
        sendAIAnalysis(
            function (trade) {
                return trade.profit !== 0;
            },
            'Анализ всех сделок:'
        );
    });

    $('#btnAiAnalyzeLosses').on('click', function () {
        sendAIAnalysis(
            function (trade) {
                /*console.log('trade.profit', trade.profit < 0)*/
                return trade.profit < 0;
            },
            'Анализ убыточных сделок на основе технического анализа allInfo:'
        );
    });

    // Если потребуется ajax-подгрузка формы, можно сделать так:
    $("#statsFilterForm").on("submit", function (e) {
        window.siteShowPrelouder();

    });

    $('#exchange').change(function () {
        $("#statsFilterForm").submit();
    });

    $('#statsFilterForm').on('submit', function (e) {
        window.siteShowPrelouder();
        setTimeout(() => {
            window.siteHidePrelouder();
        }, "15000");

    });

});
