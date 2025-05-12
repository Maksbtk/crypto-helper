<?php
namespace Maksv\Charts;

class PriceChartGenerator {
    protected $width;
    protected $height;
    protected $margin;
    protected $totalCandle; // количество свечей для отображения

    /**
     * Конструктор. Задаёт размеры изображения, отступы и количество свечей.
     *
     * @param int $totalCandle Количество свечей для отображения (по умолчанию 100)
     * @param int $width Ширина изображения (по умолчанию 800)
     * @param int $height Высота изображения (по умолчанию 600)
     * @param int $margin Отступ от краёв (по умолчанию 50)
     */
    public function __construct($totalCandle = 100, $width = 800, $height = 600, $margin = 50) {
        $this->totalCandle = $totalCandle;
        $this->width = $width;
        $this->height = $height;
        $this->margin = $margin;
    }

    /**
     * Генерирует график цены (candlestick chart) с опциональными линиями MA.
     *
     * @param array  $candles   Массив свечей, каждый элемент содержит ключи: 't', 'o', 'h', 'l', 'c'
     * @param string $coinName  Название монеты (для заголовка)
     * @param string $timeFrame Таймфрейм (например, "5m")
     * @param string|null $filePath Если указан, график сохраняется по данному пути; иначе возвращается PNG-данные.
     * @param array|null $ma  (Опционально) Ассоциативный массив MA, например: ["ma26"=> [...], "ma100"=> [...], "ma400"=> [...]]
     *
     * @return string|bool Содержимое PNG или true при успешном сохранении.
     */
    public function generateChart(array $candles, $coinName, $timeFrame = '5m', $filePath = null, array $ma = null) {
        // Выбираем последние свечи для отображения
        $total = count($candles);
        $numPoints = ($total >= $this->totalCandle) ? $this->totalCandle : $total;
        $selectedCandles = array_slice($candles, $total - $numPoints, $numPoints);

        // Подготовка массива цен для расчёта диапазона оси Y:
        $prices = [];
        foreach ($selectedCandles as $candle) {
            $prices[] = floatval($candle['l']);
            $prices[] = floatval($candle['h']);
        }
        // Если передан MA, добавляем их значения 'sma' в диапазон (берем последние $numPoints значений)
        if (!empty($ma) && is_array($ma)) {
            foreach (['ma26', 'ma100', 'ma400'] as $key) {
                if (isset($ma[$key]) && is_array($ma[$key]) && count($ma[$key]) >= $numPoints) {
                    $selectedMA = array_slice($ma[$key], count($ma[$key]) - $numPoints, $numPoints);
                    for ($i = 0; $i < $numPoints; $i++) {
                        $prices[] = floatval($selectedMA[$i]['sma']);
                    }
                }
            }
        }
        $minVal = min($prices);
        $maxVal = max($prices);
        if ($maxVal == $minVal) {
            $maxVal += 1;
        }

        // Создаем изображение через GD
        $image = imagecreatetruecolor($this->width, $this->height);

        // Основные цвета
        $background = imagecolorallocate($image, 0, 0, 0);            // Черный фон
        $gridColor  = imagecolorallocate($image, 50, 50, 50);           // Темно-серый для сетки
        $textColor  = imagecolorallocate($image, 255, 255, 255);         // Белый для текста
        $green      = imagecolorallocate($image, 0, 148, 79);             // Зеленый для бычьих свечей (close >= open)
        $red        = imagecolorallocate($image, 204, 57, 57);            // Красный для медвежьих свечей (close < open)

        // Цвета для MA:
        $ma26Color  = imagecolorallocate($image, 255, 165, 0);           // Оранжевый
        $ma100Color = imagecolorallocate($image, 255, 105, 180);          // Розовый
        $ma400Color = imagecolorallocate($image, 128, 0, 0);            // красный
        //$ma200Color = imagecolorallocate($image, 128, 0, 128);            // Фиолетовый

        // Заполняем фон
        imagefill($image, 0, 0, $background);

        // Рисуем сетку: горизонтальные и вертикальные линии каждые 50 пикселей
        for ($y = $this->margin; $y <= $this->height - $this->margin; $y += 50) {
            imageline($image, $this->margin, $y, $this->width - $this->margin, $y, $gridColor);
        }
        for ($x = $this->margin; $x <= $this->width - $this->margin; $x += 50) {
            imageline($image, $x, $this->margin, $x, $this->height - $this->margin, $gridColor);
        }

        // Рисуем оси
        imageline($image, $this->margin, $this->margin, $this->margin, $this->height - $this->margin, $textColor);
        imageline($image, $this->margin, $this->height - $this->margin, $this->width - $this->margin, $this->height - $this->margin, $textColor);

        // Область построения графика
        $plotWidth = $this->width - 2 * $this->margin;
        $plotHeight = $this->height - 2 * $this->margin;
        $xStep = $plotWidth / ($numPoints - 1);
        $yScale = $plotHeight / ($maxVal - $minVal);

        // Отрисовка свечей
        $bodyWidth = max(2, round($xStep * 0.6));
        for ($i = 0; $i < $numPoints; $i++) {
            $candle = $selectedCandles[$i];
            $x = $this->margin + $i * $xStep;
            $open = floatval($candle['o']);
            $high = floatval($candle['h']);
            $low  = floatval($candle['l']);
            $close = floatval($candle['c']);

            // Определяем цвет тела свечи
            $bodyColor = ($close >= $open) ? $green : $red;
            $yHigh = $this->height - $this->margin - (($high - $minVal) * $yScale);
            $yLow = $this->height - $this->margin - (($low - $minVal) * $yScale);
            $yOpen = $this->height - $this->margin - (($open - $minVal) * $yScale);
            $yClose = $this->height - $this->margin - (($close - $minVal) * $yScale);

            // Рисуем тень (wick)
            imageline($image, $x, $yHigh, $x, $yLow, $bodyColor);

            // Определяем координаты тела свечи
            if ($close >= $open) {
                $topBody = $yClose;
                $bottomBody = $yOpen;
            } else {
                $topBody = $yOpen;
                $bottomBody = $yClose;
            }
            $bodyX1 = $x - $bodyWidth / 2;
            $bodyX2 = $x + $bodyWidth / 2;
            imagefilledrectangle($image, $bodyX1, $topBody, $bodyX2, $bottomBody, $bodyColor);
        }

        // Отрисовка MA (если передан массив $ma)
        if (!empty($ma) && is_array($ma)) {
            $maKeys = ['ma26', 'ma100', 'ma400'];
            $maColors = [
                'ma26' => $ma26Color,
                'ma100' => $ma100Color,
                'ma400' => $ma400Color
            ];
            foreach ($maKeys as $key) {
                if (isset($ma[$key]) && is_array($ma[$key]) && count($ma[$key]) >= $numPoints) {
                    $selectedMA = array_slice($ma[$key], count($ma[$key]) - $numPoints, $numPoints);
                    $prevX = null;
                    $prevY = null;
                    for ($i = 0; $i < $numPoints; $i++) {
                        $sma = floatval($selectedMA[$i]['sma']);
                        $x = $this->margin + $i * $xStep;
                        $y = $this->height - $this->margin - (($sma - $minVal) * $yScale);
                        imagefilledellipse($image, $x, $y, 3, 3, $maColors[$key]);
                        if ($prevX !== null && $prevY !== null) {
                            imageline($image, $prevX, $prevY, $x, $y, $maColors[$key]);
                        }
                        $prevX = $x;
                        $prevY = $y;
                    }
                }
            }
        }

        // Отрисовка подписей по оси X: вертикальные подписи времени начала интервала (формат H:i)
        // Подписываем каждые 5 интервалов и обязательно последний интервал.
        $labelInterval = 5;
        for ($i = 0; $i < $numPoints; $i += $labelInterval) {
            $candle = $selectedCandles[$i];
            $timestamp = $candle['t'];
            // Если timestamp больше 1e10, предполагаем, что он в мс, и делим на 1000.
            if ($timestamp > 10000000000) {
                $timestamp = $timestamp / 1000;
            }
            $timeLabel = date("H:i", $timestamp);
            $x = $this->margin + $i * $xStep;
            $yPos = $this->height - $this->margin + 20;
            imagestringup($image, 2, $x, $yPos, $timeLabel, $textColor);
        }
        if ((($numPoints - 1) % $labelInterval) != 0) {
            $i = $numPoints - 1;
            $candle = $selectedCandles[$i];
            $timestamp = $candle['t'];
            if ($timestamp > 10000000000) {
                $timestamp = $timestamp / 1000;
            }
            $timeLabel = date("H:i", $timestamp);
            $x = $this->margin + $i * $xStep;
            $yPos = $this->height - $this->margin + 20;
            imagestringup($image, 2, $x, $yPos, $timeLabel, $textColor);
        }

        // Добавляем легенду для MA под осью X (горизонтально)
        if (!empty($ma) && is_array($ma)) {
            $legendX = $this->margin + 10;
            $legendY = $this->height - $this->margin + 40; // фиксированная Y координата
            $legendSize = 8;
            $legendSpacing = 10; // отступ между легендами

            foreach (['ma26', 'ma100', 'ma400'] as $key) {
                if (isset($ma[$key]) && is_array($ma[$key]) && count($ma[$key]) >= $numPoints) {
                    $color = $maColors[$key];
                    // Рисуем квадрат
                    imagefilledrectangle($image, $legendX, $legendY, $legendX + $legendSize, $legendY + $legendSize, $color);
                    // Выводим текст справа от квадрата
                    $text = strtoupper($key);
                    if ($key != 'ma26')
                        $text = $text . ' 15m';

                    imagestring($image, 2, $legendX + $legendSize + 5, $legendY, $text, $textColor);
                    // Вычисляем ширину текста
                    $textWidth = imagefontwidth(2) * strlen($text);
                    // Смещаем X для следующей легенды
                    $legendX += $legendSize + 5 + $textWidth + $legendSpacing;
                }
            }
        }

        // Заголовок: формируем строку: <текущее время> | <coinName> | price <timeFrame>
        $headerLine = date("H:i") . " | " . $coinName . " | price " . $timeFrame;
        imagestring($image, 3, $this->margin, 5, $headerLine, $textColor);

        if ($filePath) {
            imagepng($image, $filePath);
            imagedestroy($image);
            return true;
        } else {
            ob_start();
            imagepng($image);
            $imgData = ob_get_contents();
            ob_end_clean();
            imagedestroy($image);
            return $imgData;
        }
    }
}
?>
