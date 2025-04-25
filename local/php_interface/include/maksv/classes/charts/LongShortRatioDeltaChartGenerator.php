<?php
namespace Maksv\Charts;

class LongShortRatioDeltaChartGenerator {
    protected $width;
    protected $height;
    protected $margin;
    protected $totalPoints; // Количество точек для отображения

    /**
     * Конструктор.
     *
     * @param int $totalPoints Количество точек для отображения (по умолчанию 150)
     * @param int $width Ширина изображения (по умолчанию 800)
     * @param int $height Высота изображения (по умолчанию 600)
     * @param int $margin Отступ от краёв (по умолчанию 50)
     */
    public function __construct($totalPoints = 25, $width = 800, $height = 600, $margin = 50) {
        $this->totalPoints = $totalPoints;
        $this->width = $width;
        $this->height = $height;
        $this->margin = $margin;
    }

    /**
     * Генерирует линейный график дельты.
     *
     * @param array  $data      Массив данных, где каждый элемент содержит:
     *                          - 'timestamp' (число, время в мс или секундах)
     *                          - 'timestamp_gmt' (строка, например, "11:00 24.02")
     *                          - 'symbol' (название монеты)
     *                          - 'buyRatio' (например, 0.5538)
     *                          - 'sellRatio' (например, 0.4462)
     * @param string $coinName  Название монеты (для заголовка)
     * @param string $timeFrame Таймфрейм (например, "5m", "15m")
     * @param string|null $filePath Если указан, график сохраняется по этому пути; иначе возвращается PNG-данные.
     *
     * @return string|bool Содержимое PNG или true при успешном сохранении.
     */
    public function generateChart(array $data, $coinName, $timeFrame = '5m', $filePath = null) {
        // Если данных больше, чем нужно, берём последние totalPoints
        $total = count($data);
        $numPoints = ($total >= $this->totalPoints) ? $this->totalPoints : $total;
        $selectedData = array_slice($data, $total - $numPoints, $numPoints);

        // Вычисляем для каждой точки дельту: delta = 2*buyRatio - 1
        $deltas = [];
        foreach ($selectedData as $point) {
            $buyRatio = floatval($point['buyRatio']);
            $delta = 2 * $buyRatio - 1;
            $deltas[] = $delta;
        }

        // Определяем симметричный диапазон для оси Y вокруг 0
        $minDelta = min($deltas) - 0.5;
        $maxDelta = max($deltas) + 0.5;
        $range = max(abs($minDelta), abs($maxDelta));
        if ($range < 0.1) {
            $range = 0.1;
        }
        $minY = -$range;
        $maxY = $range;

        // Создаем изображение через GD
        $image = imagecreatetruecolor($this->width, $this->height);

        // Основные цвета
        $background = imagecolorallocate($image, 0, 0, 0);          // Черный фон
        $gridColor  = imagecolorallocate($image, 50, 50, 50);         // Темно-серый для сетки
        $textColor  = imagecolorallocate($image, 255, 255, 255);       // Белый для текста
        $lineColor  = imagecolorallocate($image, 5, 112, 172);         // Синий для линии графика
        $dottedColor = imagecolorallocate($image, 120, 120, 120);      // Серый для пунктирной линии

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

        // Определяем область графика
        $plotWidth = $this->width - 2 * $this->margin;
        $plotHeight = $this->height - 2 * $this->margin;
        $xStep = $plotWidth / ($numPoints - 1);
        $yScale = $plotHeight / ($maxY - $minY);

        // Рисуем пунктирную горизонтальную линию на уровне 0
        $yZero = $this->height - $this->margin - ((0 - $minY) * $yScale);
        $dashLength = 5;
        $gapLength = 5;
        for ($x = $this->margin; $x < $this->width - $this->margin; $x += ($dashLength + $gapLength)) {
            imageline($image, $x, $yZero, min($x + $dashLength, $this->width - $this->margin), $yZero, $dottedColor);
        }
        // Подпись "0" слева от линии
        imagestring($image, 2, 2, $yZero - 7, "0", $textColor);

        // Рисуем линейный график дельты
        $prevX = null;
        $prevY = null;
        for ($i = 0; $i < $numPoints; $i++) {
            $delta = $deltas[$i];
            $x = $this->margin + $i * $xStep;
            $y = $this->height - $this->margin - (($delta - $minY) * $yScale);
            imagefilledellipse($image, $x, $y, 4, 4, $lineColor);
            if ($prevX !== null && $prevY !== null) {
                imageline($image, $prevX, $prevY, $x, $y, $lineColor);
            }
            $prevX = $x;
            $prevY = $y;
        }

        // Заголовок: формируем строку: <текущее время> | <coinName> | Long Short Delta <timeFrame>
        $headerLine = date("H:i") . " | " . $coinName . " | Long Short Delta " . $timeFrame;
        imagestring($image, 3, $this->margin, 5, $headerLine, $textColor);

        // Вывод изображения
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
