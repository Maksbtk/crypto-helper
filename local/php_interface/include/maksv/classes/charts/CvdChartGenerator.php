<?php
namespace Maksv\Charts;

class CvdChartGenerator {
    protected $width;
    protected $height;
    protected $margin;
    protected $totalPoints; // Количество интервалов для отображения

    /**
     * Конструктор.
     *
     * @param int $totalPoints Количество интервалов для отображения (по умолчанию 150)
     * @param int $width Ширина изображения (по умолчанию 800)
     * @param int $height Высота изображения (по умолчанию 600)
     * @param int $margin Отступ от краёв (по умолчанию 50)
     */
    public function __construct($totalPoints = 100, $width = 800, $height = 600, $margin = 50) {
        $this->totalPoints = $totalPoints;
        $this->width = $width;
        $this->height = $height;
        $this->margin = $margin;
    }

    /**
     * Генерирует график CVD (кумулятивная дельта объёмов).
     *
     * @param array  $data      Массив интервалов, где каждый элемент содержит:
     *                          - 'buyVolume'
     *                          - 'sellVolume'
     *                          - 'delta'   (buyVolume - sellVolume)
     *                          - 'cvd'     (кумулятивная сумма дельт)
     *                          - 'startTime' (unix timestamp начала интервала)
     *                          - 'startTime_gmt' (опционально, например, "17:30 26.02")
     * @param string $coinName  Название монеты (для заголовка)
     * @param string $timeFrame Таймфрейм (например, "15m")
     * @param string|null $filePath Если указан, график сохраняется по этому пути; иначе возвращается PNG-данные.
     *
     * @return string|bool Содержимое PNG или true при успешном сохранении.
     */
    public function generateChart(array $data, $coinName, $timeFrame = '15m', $filePath = null) {
        // Если данных больше, чем нужно, берём последние totalPoints
        $total = count($data);
        $numPoints = ($total >= $this->totalPoints) ? $this->totalPoints : $total;
        $selectedData = array_slice($data, $total - $numPoints, $numPoints);

        // Из выбранных данных извлекаем массивы для построения графика
        $buyVolumes = array_map(function($d) {
            return floatval($d['buyVolume']);
        }, $selectedData);
        $sellVolumes = array_map(function($d) {
            return floatval($d['sellVolume']);
        }, $selectedData);
        $cvdValues = array_map(function($d) {
            return floatval($d['cvd']);
        }, $selectedData);

        // Определяем границы для шкалы:
        // Для столбиков: верхняя граница – максимум buyVolume, нижняя – -максимум sellVolume.
        $maxBuy = max($buyVolumes);
        $maxSell = max($sellVolumes);
        $volumeUpperBound = $maxBuy;    // положительное значение
        $volumeLowerBound = -$maxSell;   // отрицательное значение

        // Для кумулятивной дельты:
        $cvdMin = min($cvdValues);
        $cvdMax = max($cvdValues);

        // Общий диапазон: берем минимум и максимум из обоих наборов, обязательно включая 0.
        $minValue = min($volumeLowerBound, $cvdMin, 0);
        $maxValue = max($volumeUpperBound, $cvdMax, 0);

        // Добавляем padding
        $range = $maxValue - $minValue;
        if ($range < 1) {
            $range = 1;
        }
        $padding = 0.1 * $range;
        $minY = $minValue - $padding;
        $maxY = $maxValue + $padding;

        // Создаем изображение через GD
        $image = imagecreatetruecolor($this->width, $this->height);

        // Основные цвета
        $background = imagecolorallocate($image, 0, 0, 0);            // Черный фон
        $gridColor  = imagecolorallocate($image, 50, 50, 50);           // Темно-серый для сетки
        $textColor  = imagecolorallocate($image, 255, 255, 255);         // Белый для текста
        $buyColor   = imagecolorallocate($image, 0, 148, 79);             // Зеленый для объёма покупок (Buy)
        $sellColor  = imagecolorallocate($image, 204, 57, 57);            // Красный для объёма продаж (Sell)
        $cvdLineColor = imagecolorallocate($image, 255, 140, 0);           // Темно-оранжевый для линии CVD
        $dottedColor = imagecolorallocate($image, 120, 120, 120);         // Серый для пунктирной линии (baseline)

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
        $yScale = $plotHeight / ($maxY - $minY);

        // Определяем baseline для объемов: уровень 0
        $yZero = $this->height - $this->margin - ((0 - $minY) * $yScale);

        // Рисуем горизонтальную пунктирную линию на уровне 0 (baseline)
        $dashLength = 5;
        $gapLength = 5;
        for ($x = $this->margin; $x < $this->width - $this->margin; $x += ($dashLength + $gapLength)) {
            imageline($image, $x, $yZero, min($x + $dashLength, $this->width - $this->margin), $yZero, $dottedColor);
        }
        imagestring($image, 2, 2, $yZero - 7, "0", $textColor);

        // Рисуем столбцы для объемов в один столбец по интервалу:
        // В каждом интервале рисуем один столбец, который делится на две части:
        // - Верхняя часть (от baseline вверх) для Buy.
        // - Нижняя часть (от baseline вниз) для Sell.
        $barWidth = max(2, round($xStep * 0.6)); // Общая ширина столбца
        $halfBar = round($barWidth / 2);
        for ($i = 0; $i < $numPoints; $i++) {
            $xCenter = $this->margin + $i * $xStep;
            // Buy: рисуем от baseline вверх
            $buyVol = $buyVolumes[$i];
            $yBuy = $yZero - ($buyVol * $yScale);
            imagefilledrectangle($image,
                $xCenter - $halfBar,
                min($yZero, $yBuy),
                $xCenter + $halfBar,
                $yZero,
                $buyColor
            );
            // Sell: рисуем от baseline вниз
            $sellVol = $sellVolumes[$i];
            $ySell = $yZero + ($sellVol * $yScale);
            imagefilledrectangle($image,
                $xCenter - $halfBar,
                $yZero,
                $xCenter + $halfBar,
                max($yZero, $ySell),
                $sellColor
            );
        }

        // Рисуем линию CVD (кумулятивная дельта)
        $prevX = null;
        $prevY = null;
        foreach ($cvdValues as $i => $value) {
            $x = $this->margin + $i * $xStep;
            $y = $this->height - $this->margin - (( $value - $minY ) * $yScale);
            imagefilledellipse($image, $x, $y, 4, 4, $cvdLineColor);
            if ($prevX !== null && $prevY !== null) {
                imageline($image, $prevX, $prevY, $x, $y, $cvdLineColor);
            }
            $prevX = $x;
            $prevY = $y;
        }

        // Добавляем подписи по оси Y для ключевых меток: минимум, 25%, 50%, 75%, максимум
        $numLabels = 5;
        $stepVal = ($maxY - $minY) / ($numLabels - 1);
        for ($i = 0; $i < $numLabels; $i++) {
            $value = $minY + $i * $stepVal;
            $yPos = $this->height - $this->margin - (($value - $minY) * $yScale);
            imagestring($image, 2, 2, $yPos - 7, number_format($value, 0), $textColor);
        }

        // Добавляем подписи по оси X: вертикально написанные, только для некоторых интервалов (начало интервала)
        $labelInterval = ($numPoints >= 5) ? 5 : 1; // например, каждые 5 интервалов
        for ($i = 0; $i < $numPoints; $i += $labelInterval) {
            // Форматируем время начала интервала из 'startTime'
            $timestamp = isset($selectedData[$i]['startTime']) ? $selectedData[$i]['startTime'] : time();
            $timestamp = $timestamp;
            $timeLabel = date("H:i", $timestamp);
            $x = $this->margin + $i * $xStep;
            // Устанавливаем позицию ниже оси X
            $yPos = $this->height - $this->margin + 20;
            // Выводим вертикальный текст (с использованием imagestringup)
            imagestringup($image, 2, $x, $yPos, $timeLabel, $textColor);
        }
        // Если последний интервал не подписан, подписываем его
        if ((($numPoints - 1) % $labelInterval) != 0) {
            $i = $numPoints - 1;
            $timestamp = isset($selectedData[$i]['startTime']) ? $selectedData[$i]['startTime'] : time();
            $timestamp = $timestamp;
            $timeLabel = date("H:i", $timestamp);
            $x = $this->margin + $i * $xStep;
            $yPos = $this->height - $this->margin + 20;
            imagestringup($image, 2, $x, $yPos, $timeLabel, $textColor);
        }

        // Заголовок: формируем строку: <текущее время> | <coinName> | CVD <timeFrame>
        $headerLine = date("H:i") . " | " . $coinName . " | CVD " . $timeFrame;
        imagestring($image, 3, $this->margin, 5, $headerLine, $textColor);

        // Легенда: оставляем только подпись "CVD" с квадратом темно-оранжевого цвета
        $legendX = $this->margin;
        $legendY = $this->height - $this->margin + 10;
        $legendSize = 8;
        imagefilledrectangle($image, $legendX, $legendY, $legendX + $legendSize, $legendY + $legendSize, $cvdLineColor);
        imagestring($image, 2, $legendX + $legendSize + 5, $legendY, "CVD", $textColor);

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
