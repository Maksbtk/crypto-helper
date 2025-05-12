<?php
namespace Maksv;

class PatternDetector
{
    /**
     * Detects the most recent Head & Shoulders (regular or inverse)
     * and looks for a breakout within a small window after the right shoulder.
     *
     * Uses a **horizontal** neckline drawn through the higher (regular) or lower
     * (inverse) of the two troughs D/E for more intuitive support/resistance.
     *
     * @param array $candles            Array of candles ['h','l','c','v','t'], oldest → newest
     * @param int   $lookback           Bars to use when finding local extremes
     * @param float $shoulderTolerance  Max % difference between left & right shoulder heights
     * @param float $entryTolerance     % above/below neckline defining the entry zone
     * @param int   $maxBarsAfter       How many bars after right shoulder to scan for breakout
     * @return array|null               Pattern info with isLong/isShort, or null if none
     */
    public static function detectHeadAndShoulders(
        array $candles,
        int   $lookback          = 4,
        float $shoulderTolerance = 2.0,
        float $entryTolerance    = 0.5,
        int   $maxBarsAfter      = 30
    ): ?array
    {
        $n = count($candles);
        if ($n < $lookback * 2 + 5) {
            return null;
        }

        // Extract arrays
        $highs      = array_column($candles, 'h');
        $lows       = array_column($candles, 'l');
        $closes     = array_column($candles, 'c');
        $timestamps = array_column($candles, 't');

        // Time formatter
        $fmtTime = fn(int $ms): string => date("H:i d.m", (int)($ms / 1000));

        // Find local highs/lows
        $peaks = TechnicalAnalysis::findLocalExtremes($highs, 'high', $lookback);
        $pits  = TechnicalAnalysis::findLocalExtremes($lows,  'low',  $lookback);

        // Internal tester for one triplet
        $test = function(array $A, array $B, array $C, bool $regular) use (
            $closes, $timestamps, $n, $lookback,
            $shoulderTolerance, $entryTolerance, $maxBarsAfter, $fmtTime
        ) {
            $valA = $A['value']; $valB = $B['value']; $valC = $C['value'];

            // Head vs shoulders
            if ($regular) {
                if (!($valB > $valA && $valB > $valC)) {
                    return null;
                }
            } else {
                if (!($valB < $valA && $valB < $valC)) {
                    return null;
                }
            }

            // Shoulders equality
            if (abs($valA - $valC) / max($valA, $valC) * 100 > $shoulderTolerance) {
                return null;
            }

            // Identify D/E troughs or peaks for neckline
            $extremes = $regular
                ? TechnicalAnalysis::findLocalExtremes(array_column($closes, 'l'), 'low', $lookback)
                : TechnicalAnalysis::findLocalExtremes(array_column($closes, 'h'), 'high', $lookback);

            $D = $E = null;
            foreach ($extremes as $pt) {
                if (!$D && $pt['index'] > $A['index'] && $pt['index'] < $B['index']) {
                    $D = $pt;
                }
                if (!$E && $pt['index'] > $B['index'] && $pt['index'] < $C['index']) {
                    $E = $pt;
                }
                if ($D && $E) {
                    break;
                }
            }
            if (!$D || !$E) {
                return null;
            }

            // Horizontal neckline price
            if ($regular) {
                $neckPrice = max($D['value'], $E['value']);
            } else {
                $neckPrice = min($D['value'], $E['value']);
            }

            // Look for breakout in window after C
            $start = $C['index'] + 1;
            $end   = min($n - 1, $C['index'] + $maxBarsAfter);
            for ($j = $start; $j <= $end; $j++) {
                $close = $closes[$j];
                $broken = $regular
                    ? ($close < $neckPrice)
                    : ($close > $neckPrice);

                if (!$broken) {
                    continue;
                }

                // Entry zone around neckline
                if ($regular) {
                    $entryMin = $neckPrice * (1 - $entryTolerance / 100);
                    $entryMax = $neckPrice;
                } else {
                    $entryMin = $neckPrice;
                    $entryMax = $neckPrice * (1 + $entryTolerance / 100);
                }

                // % distance from neckline (at head) to head
                $neckAtHead = $neckPrice;
                $distancePc = $regular
                    ? ($valB - $neckAtHead) / $neckAtHead * 100
                    : ($neckAtHead - $valB) / $neckAtHead * 100;

                return [
                    'patternType'     => $regular ? 'head_and_shoulders' : 'inverse_head_and_shoulders',
                    'isShort'         => $regular,
                    'isLong'          => !$regular,
                    'leftShoulder'    => [
                        'idx'   => $A['index'],
                        'price' => $valA,
                        'time'  => $fmtTime($timestamps[$A['index']])
                    ],
                    'head'            => [
                        'idx'   => $B['index'],
                        'price' => $valB,
                        'time'  => $fmtTime($timestamps[$B['index']])
                    ],
                    'rightShoulder'   => [
                        'idx'   => $C['index'],
                        'price' => $valC,
                        'time'  => $fmtTime($timestamps[$C['index']])
                    ],
                    'neckline'        => [
                        'horizontal' => true,
                        'price'      => $neckPrice
                    ],
                    'breakIdx'        => $j,
                    'breakPrice'      => $close,
                    'entryZone'       => ['min' => $entryMin, 'max' => $entryMax],
                    'headToNeckPc'    => round($distancePc, 2),
                ];
            }

            return null;
        };

        // 1) Regular H&S → short
        if (count($peaks) >= 3) {
            $last3 = array_slice($peaks, -3);
            if ($res = $test($last3[0], $last3[1], $last3[2], true)) {
                return $res;
            }
        }

        // 2) Inverse H&S → long
        if (count($pits) >= 3) {
            $last3 = array_slice($pits, -3);
            if ($res = $test($last3[0], $last3[1], $last3[2], false)) {
                return $res;
            }
        }

        return null;
    }
}
