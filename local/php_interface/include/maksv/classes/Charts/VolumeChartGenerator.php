<?php
namespace Maksv\Charts;

class VolumeChartGenerator {
    protected $width;
    protected $height;
    protected $margin;
    protected $totalCandle; // количество свечей, которые будут отображаться

    /**
     * Конструктор. Задаёт размеры изображения, отступы и количество свечей.
     *
     * @param int $totalCandle Количество свечей для отображения (по умолчанию 80)
     * @param int $width Ширина изображения (по умолчанию 800)
     * @param int $height Высота изображения (по умолчанию 600)
     * @param int $margin Отступ от краёв (по умолчанию 50)
     * @param int $maWindow Окно для расчёта скользящей средней (по умолчанию 10)
     */
    public function __construct($totalCandle = 100, $width = 800, $height = 600, $margin = 50) {
        $this->totalCandle = $totalCandle;
        $this->width = $width;
        $this->height = $height;
        $this->margin = $margin;
    }

    /**
     * Генерирует график объёмов и скользящей средней.
     *
     * @param array  $candles   Массив свечей (каждый элемент с ключами: 't', 'o', 'h', 'l', 'c', 'v')
     * @param array  $volumeMA  Массив данных, рассчитанных по объёмам, где каждый элемент содержит (среди прочего) ключ 'smoothed_ma' и 'isFlat'
     * @param string $coinName  Название монеты (будет выведено в заголовке)
     * @param string $timeFrame Таймфрейм (будет выведен в заголовке)
     * @param string|null $filePath Если указан, график сохраняется по данному пути; иначе возвращается содержимое PNG.
     *
     * @return string|bool Содержимое PNG или true при успешном сохранении.
     */
    public function generateChart(array $candles, array $volumeMA, $coinName, $timeFrame = '5m',  $filePath = null) {
        // Убираем последнюю свечу (если нужно, как в вашем предыдущем варианте)
        //$candles = array_slice($candles, 0, -1);
        //$volumeMA = array_slice($volumeMA, 0, -1);

        $total = count($candles);
        $numPoints = ($total >= $this->totalCandle) ? $this->totalCandle : $total;
        // Берем последние $numPoints свечей
        $selectedCandles = array_slice($candles, $total - $numPoints, $numPoints);
        // Берем соответствующие данные из volumeMA
        $selectedVolumeMA = array_slice($volumeMA, count($volumeMA) - $numPoints, $numPoints);

        // Формируем массив объёмов (используем ключ 'v' из свечей)
        $volumes = [];
        foreach ($selectedCandles as $candle) {
            $volumes[] = floatval($candle['v']);
        }

        // Формируем массив для скользящей средней (из ключа 'smoothed_ma')
        $movingAvg = [];
        foreach ($selectedVolumeMA as $data) {
            $movingAvg[] = floatval($data['smoothed_ma']);
        }

        // Создаем изображение через GD
        $image = imagecreatetruecolor($this->width, $this->height);

        // Определяем основные цвета
        $background = imagecolorallocate($image, 0, 0, 0);            // Чёрный фон
        $gridColor  = imagecolorallocate($image, 50, 50, 50);           // Тёмно-серый для сетки
        $textColor  = imagecolorallocate($image, 255, 255, 255);         // Белый для текста
        $green      = imagecolorallocate($image, 0, 148, 79);             // Зеленый для бычьих свечей
        $red        = imagecolorallocate($image, 204, 57, 57);            // Красный для медвежьих свечей
        $blue       = imagecolorallocate($image, 5, 112, 172);            // Синий для линии скользящей средней
        $purple     = imagecolorallocate($image, 128, 0, 128);            // Фиолетовый для маркера flat

        // Заполняем фон чёрным цветом
        imagefill($image, 0, 0, $background);

        // Рисуем сетку: горизонтальные и вертикальные линии каждые 50 пикселей
        for ($y = $this->margin; $y <= $this->height - $this->margin; $y += 50) {
            imageline($image, $this->margin, $y, $this->width - $this->margin, $y, $gridColor);
        }
        for ($x = $this->margin; $x <= $this->width - $this->margin; $x += 50) {
            imageline($image, $x, $this->margin, $x, $this->height - $this->margin, $gridColor);
        }

        // Рисуем оси поверх сетки
        imageline($image, $this->margin, $this->margin, $this->margin, $this->height - $this->margin, $textColor);
        imageline($image, $this->margin, $this->height - $this->margin, $this->width - $this->margin, $this->height - $this->margin, $textColor);

        // Определяем диапазон значений для оси Y (учитываем и объемы, и скользящую среднюю)
        $allValues = array_merge($volumes, array_filter($movingAvg, function($v) { return $v !== null; }));
        $minVal = min($allValues);
        $maxVal = max($allValues);
        if ($maxVal == $minVal) {
            $maxVal += 1;
        }

        // Область построения графика
        $plotWidth = $this->width - 2 * $this->margin;
        $plotHeight = $this->height - 2 * $this->margin;
        $xStep = $plotWidth / ($numPoints - 1);
        $yScale = $plotHeight / ($maxVal - $minVal);

        // Определяем ширину баров для объёмов
        $barWidth = max(2, round($xStep * 0.6));

        $prevX = null;
        $prevY_ma = null;

        // Рисуем для каждой свечи:
        // 1. Объём как бар (свечу), окрашенный в зеленый, если цена закрытия >= цены открытия, иначе в красный.
        // 2. Точку и линию скользящей средней (если значение имеется).
        // 3. Если в данных volumeMA для свечи isFlat = true, рисуем фиолетовый кружок над баром.
        for ($i = 0; $i < $numPoints; $i++) {
            $x = $this->margin + $i * $xStep;
            // Y для объёма: чем выше объём, тем выше график (бар тянется вверх от оси X)
            $y_vol = $this->height - $this->margin - (($volumes[$i] - $minVal) * $yScale);
            // Определяем цвет для свечи по данным свечи (из массива $selectedCandles)
            $candle = $selectedCandles[$i];
            $candleColor = ($candle['c'] >= $candle['o']) ? $green : $red;

            // Рисуем бар (объёмную свечу)
            $barX1 = $x - $barWidth / 2;
            $barY1 = $y_vol;
            $barX2 = $x + $barWidth / 2;
            $barY2 = $this->height - $this->margin; // основание оси X
            imagefilledrectangle($image, $barX1, $barY1, $barX2, $barY2, $candleColor);

            // Если для данной свечи isFlat = true (из массива volumeMA), рисуем фиолетовый кружок над баром
            if (isset($selectedVolumeMA[$i]['isFlat']) && $selectedVolumeMA[$i]['isFlat'] === true) {
                // Рисуем кружок (диаметр 8 пикселей) немного выше вершины бара (например, на 10 пикселей)
                imagefilledellipse($image, $x, $barY1 - 10, 8, 8, $purple);
            }

            // Скользящая средняя
            if ($movingAvg[$i] !== null) {
                $y_ma = $this->height - $this->margin - (($movingAvg[$i] - $minVal) * $yScale);
                imagefilledellipse($image, $x, $y_ma, 4, 4, $blue);
                if ($prevX !== null && $prevY_ma !== null) {
                    imageline($image, $prevX, $prevY_ma, $x, $y_ma, $blue);
                }
                $prevY_ma = $y_ma;
            }
            $prevX = $x;
        }

        // Рисуем подписи по оси Y для минимального и максимального значений
        imagestring($image, 3, 5, $this->height - $this->margin - 10, number_format($minVal), $textColor);
        imagestring($image, 3, 5, $this->margin, number_format($maxVal), $textColor);

        // Заголовок:
        // Формируем строку: В начале время (формат H:i), затем |, затем название монеты, затем |, затем "volume" и таймфрейм.
        $font = 3; // Используем встроенный шрифт
        $headerLine = date("H:i") . " | " . $coinName . " | volume " . $timeFrame;
        imagestring($image, $font, $this->margin, 5, $headerLine, $textColor);

        // Легенда в нижнем левом углу: маленький фиолетовый кружок и подпись "flat"
        $legendX = $this->margin;
        $legendY = $this->height - $this->margin + 10;
        $legendCircleSize = 8;
        imagefilledellipse($image, $legendX + $legendCircleSize/2, $legendY + $legendCircleSize/2, $legendCircleSize, $legendCircleSize, $purple);
        imagestring($image, $font, $legendX + $legendCircleSize + 5, $legendY, "flat", $textColor);

        // Если указан путь для сохранения, сохраняем изображение, иначе возвращаем его как строку
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
