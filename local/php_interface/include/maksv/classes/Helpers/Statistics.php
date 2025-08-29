<?php
namespace Maksv\Helpers;

class Statistics
{

    public static function findLinRegForResultDate(string $resultDate, array $indexByTimestamp, array $indexByMs, string $timeframe = '1h'): mixed {
        // пытаемся распарсить ожидаемый формат "d.m.Y H:i:s"
        $dt = \DateTime::createFromFormat('d.m.Y H:i:s', $resultDate);

        // если не распарсилось — пробуем общий конструктор DateTime
        if (!$dt) {
            try {
                $dt = new \DateTime($resultDate);
            } catch (\Exception $e) {
                return false;
            }
        }

        // Опционально установить таймзону, если нужно:
        // $dt->setTimezone(new \DateTimeZone('Europe/Amsterdam'));

        $rounded = null;

        if ($timeframe === '1h') {
            $minute = (int)$dt->format('i');
            if ($minute >= 30) {
                $rounded = (clone $dt)->setTime((int)$dt->format('H'), 0, 0)->modify('+1 hour');
            } else {
                $rounded = (clone $dt)->setTime((int)$dt->format('H'), 0, 0);
            }
        } elseif ($timeframe === '15m') {
            $minute = (int)$dt->format('i');

            // вычисляем "квартал" (0..3) и округляем по середине четверти (>=8 -> вверх)
            $quarter = (int) floor($minute / 15);
            $minuteInQuarter = $minute % 15;
            if ($minuteInQuarter >= 8) {
                $quarter++;
            }

            if ($quarter >= 4) {
                // перенос на следующий час
                $rounded = (clone $dt)->setTime((int)$dt->format('H'), 0, 0)->modify('+1 hour');
            } else {
                $rounded = (clone $dt)->setTime((int)$dt->format('H'), $quarter * 15, 0);
            }
        } elseif ($timeframe === '4h') {
            // определяем 4-часовой блок: 0,4,8,12,16,20
            $hour = (int)$dt->format('H');
            $minute = (int)$dt->format('i');
            $second = (int)$dt->format('s');

            $blockStartHour = (int) floor($hour / 4) * 4;

            // минуты (и доли) от начала блока
            $minutesFromBlockStart = ($hour - $blockStartHour) * 60 + $minute + $second / 60.0;

            // если прошло >= половины блока (2 часа = 120 минут) — округляем вверх в следующий 4h блок
            if ($minutesFromBlockStart >= 120) {
                // начало блока +4 часа (может перейти на следующий день — DateTime корректно обработает)
                $rounded = (clone $dt)->setTime($blockStartHour, 0, 0)->modify('+4 hour');
            } else {
                // округление вниз — начало текущего 4h блока
                $rounded = (clone $dt)->setTime($blockStartHour, 0, 0);
            }
        } else {
            // неизвестный таймфрейм
            return false;
        }

        $roundedStr = $rounded->format('Y-m-d H:i:s'); // формат совпадает с others timestamp

        // 1) пробуем найти по строковому timestamp
        if (isset($indexByTimestamp[$roundedStr])) {
            return $indexByTimestamp[$roundedStr];
        }

        // 2) пробуем по миллисекундам
        $roundedMs = (int) ($rounded->getTimestamp() * 1000);
        if (isset($indexByMs[$roundedMs])) {
            return $indexByMs[$roundedMs];
        }

        // Не найдено — можно опционально искать ближайший бар в пределах одного периода (раскомментируйте по необходимости)
        /*
        $closest = null;
        $bestDiff = PHP_INT_MAX;
        foreach ($indexByMs as $ms => $el) {
            $diff = abs($ms - $roundedMs);
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $closest = $el;
            }
        }
        // если ближайшая разница <= период_in_ms (например для 4h = 4*3600*1000), вернуть closest
        if ($closest) {
            // определите threshold_ms в зависимости от timeframe
            // $thresholdMs = ($timeframe === '4h') ? 4*3600*1000 : (($timeframe === '1h') ? 3600*1000 : 15*60*1000);
            // if ($bestDiff <= $thresholdMs) return $closest;
        }
        */

        return false;
    }

}
