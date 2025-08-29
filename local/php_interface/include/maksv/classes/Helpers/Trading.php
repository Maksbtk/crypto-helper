<?php
namespace Maksv\Helpers;

class Trading
{
    /**
     * #рынок #others
     * Анализирует рыночную импульсную информацию на основе множества технических индикаторов.
     *
     * Выполняет комплексный анализ рынка на различных таймфреймах (5m, 15m, 1h, 4h) с использованием
     * импульсных индикаторов MACD, ADX, Supertrend, Stochastic, LinReg и других. Формирует торговые
     * сигналы, рассчитывает параметры риска и тейк-профитов на основе рыночных условий.
     *
     * @return array
     *   Ассоциативный массив с результатами анализа:
     *   - 'isLong' (bool) Сигнал на покупку
     *   - 'isShort' (bool) Сигнал на продажу
     *   - 'risk' (float) Уровень риска (3.6 по умолчанию)
     *   - 'atrMultipliers' (array) Множители ATR для тейк-профитов
     *   - 'shortTpCount' (int) Количество тейк-профитов для шорта (1 по умолчанию)
     *   - 'longTpCount' (int) Количество тейк-профитов для лонга (1 по умолчанию)
     *   - 'marketImpulsInfo' (array) Информация о рыночном импульсе
     *   - 'btcImpulsInfo' (array) Информация о импульсе BTC
     *   - 'infoText' (string) Детальное текстовое описание анализа
     *   - 'longMl' (array) Результаты ML модели для лонга
     *   - 'shortMl' (array) Результаты ML модели для шорта
     *
     *   Анализирует дивергенции, тренды, импульсы и другие технические показатели
     *   для принятия торговых решений с учетом множества условий и правил риска.
     */
    public static function checkMarketImpulsInfo()
    {
        $infoText = '';
        $res['isLong'] = $res['isShort'] = false;
        $res['atrMultipliers'] = false;
        $res['shortTpCount'] = $res['longTpCount'] = 1;
        $res['risk'] = 3.6;
        $maDistance = 1;

        $res['marketImpulsInfo'] = $marketImpulsInfo = self::getMarketInfo();
        $res['btcImpulsInfo'] = $btcImpulsInfo = self::checkBtcImpulsInfo();

        $infoText .= "\nmarket info:\n\n";

        $marketImpulsMacdVal = 200000000;
        $marketImpulseMacdTrendBoardVal = 100000000;
        //$marketStrongImpulsMacdVal = 650000000;
        $marketStrongImpulsMacdVal = 450000000;

        $marketMidImpulsBoard = 2390000000;
        $marketImpulsBoard = 3000000000;

        //market impuls macd 4h text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd4h']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd4h']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd4h']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd4h']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd4h']['trend']['trendVal'] . '), 4h' . "\n";

        //market impuls macd 1h text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd1h']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd1h']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd1h']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd1h']['trend']['trendVal'] . '), 1h' . "\n";

        //market impuls macd 15m text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd15m']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd15m']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd15m']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd15m']['trend']['trendVal'] . '), 15m' . "\n";
        //market impuls macd 5m text
        $infoText .= 'impuls macd hist ' . formatBigNumber($marketImpulsInfo['actualImpulsMacd5m']['histogram']) . ' trend ' . ($marketImpulsInfo['actualImpulsMacd5m']['trend']['trendText'])
            . ' (' . formatBigNumber($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd']) . ', '
            . formatBigNumber($marketImpulsInfo['actualImpulsMacd5m']['signal_line']) . '), (' . $marketImpulsInfo['actualImpulsMacd5m']['trend']['trendVal'] . '), 5m' . "\n\n";

        //adx 1h text
        $infoText .= 'adx trend ' . $marketImpulsInfo['actualAdx1h']['trendDirection']['trendDir'] . ', dir ' . $marketImpulsInfo['actualAdx1h']['adxDirection']['adxDir'] . ' (' . round($marketImpulsInfo['actualAdx1h']['adx'], 2) . '), 1h' . "\n";
        //adx 15m text
        $infoText .= 'adx trend ' . $marketImpulsInfo['actualAdx15m']['trendDirection']['trendDir'] . ', dir ' . $marketImpulsInfo['actualAdx15m']['adxDirection']['adxDir'] . ' (' . round($marketImpulsInfo['actualAdx15m']['adx'], 2) . '), 15m' . "\n";
        //adx 5m text
        $infoText .= 'adx trend ' . $marketImpulsInfo['actualAdx5m']['trendDirection']['trendDir'] . ', dir ' . $marketImpulsInfo['actualAdx5m']['adxDirection']['adxDir'] . ' (' . round($marketImpulsInfo['actualAdx5m']['adx'], 2) . '), 5m' . "\n\n";

        //mfi 15m text
        $infoText .= 'mfi ' . $marketImpulsInfo['mfi15m']['mfi'] . ' (' . $marketImpulsInfo['mfi15m']['fast_mfi'] . ', ' . $marketImpulsInfo['mfi15m']['slow_mfi'] . '), 15m' . "\n\n";

        //LinReg 4h text
        $infoText .= 'LinReg ' . $marketImpulsInfo['linRegChannel4h']['percent'] . '% 4h' . "\n";
        //LinReg 1h text
        $infoText .= 'LinReg ' . $marketImpulsInfo['linRegChannel1h']['percent'] . '% 1h' . "\n";
        //LinReg 15m text
        $infoText .= 'LinReg ' . $marketImpulsInfo['linRegChannel15m']['percent'] . '% 15m' . "\n";

        //market
        if ($marketImpulsInfo['longDivergenceVal1h'] && $marketImpulsInfo['longDivergenceText1h'])
            $infoText .= $marketImpulsInfo['longDivergenceText1h'] . "\n";

        if ($marketImpulsInfo['shortDivergenceVal1h'] && $marketImpulsInfo['shortDivergenceText1h'])
            $infoText .= $marketImpulsInfo['shortDivergenceText1h'] . "\n";

        if ($marketImpulsInfo['longDivergenceVal15m'] && $marketImpulsInfo['longDivergenceText15m'])
            $infoText .= $marketImpulsInfo['longDivergenceText15m'] . "\n";

        if ($marketImpulsInfo['shortDivergenceVal15m'] && $marketImpulsInfo['shortDivergenceText15m'])
            $infoText .= $marketImpulsInfo['shortDivergenceText15m'] . "\n";

        if ($marketImpulsInfo['longDivergenceVal5m'] && $marketImpulsInfo['longDivergenceText5m'])
            $infoText .= $marketImpulsInfo['longDivergenceText5m'] . "\n";

        if ($marketImpulsInfo['shortDivergenceVal5m'] && $marketImpulsInfo['shortDivergenceText5m'])
            $infoText .= $marketImpulsInfo['shortDivergenceText5m'] . "\n";

        //btc
        if ($btcImpulsInfo['longDivergenceVal15m'] && $btcImpulsInfo['longDivergenceText15m'])
            $infoText .= $btcImpulsInfo['longDivergenceText15m'] . "\n";

        if ($btcImpulsInfo['shortDivergenceVal15m'] && $btcImpulsInfo['shortDivergenceText15m'])
            $infoText .= $btcImpulsInfo['shortDivergenceText15m'] . "\n";

        if ($btcImpulsInfo['longDivergenceVal5m'] && $btcImpulsInfo['longDivergenceText5m'])
            $infoText .= $btcImpulsInfo['longDivergenceText5m'] . "\n";

        if ($btcImpulsInfo['shortDivergenceVal5m'] && $btcImpulsInfo['shortDivergenceText5m'])
            $infoText .= $btcImpulsInfo['shortDivergenceText5m'] . "\n";


        if (
            (
                $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketImpulseMacdTrendBoardVal
                || $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > $marketImpulseMacdTrendBoardVal
            )
            && (
                $marketImpulsInfo['actualImpulsMacd15m']['trend']['longDirection']
                //$marketImpulsInfo['actualImpulsMacd15m']['trend']['trendVal'] != -2
                && ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= $marketImpulsInfo['actualImpulsMacd15m']['signal_line'])
                && $marketImpulsInfo['actualImpulsMacd15m']['histogram'] > ($marketImpulsMacdVal / 5)
            )

        ) {
            $res['isLong'] = true;
            $res['atrMultipliers'] = [2.6, 3.2, 3.7];
            $res['risk'] = 3.6;
        } else if (
            (
                $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulseMacdTrendBoardVal
                || $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < -$marketImpulseMacdTrendBoardVal
            )
            && (
                $marketImpulsInfo['actualImpulsMacd15m']['trend']['shortDirection']
                //$marketImpulsInfo['actualImpulsMacd15m']['trend']['trendVal'] != 2
                && ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= $marketImpulsInfo['actualImpulsMacd15m']['signal_line'])
                && $marketImpulsInfo['actualImpulsMacd15m']['histogram'] < -($marketImpulsMacdVal / 5)
            )
        ) {
            $res['isShort'] = true;
            $res['atrMultipliers'] = [2.6, 3.2, 3.7];
            $res['risk'] = 3.6;
        }

        // risk/profit rules long
        if ($res['isLong']) {

            if ($marketImpulsInfo['linRegChannel15m']['percent'] > 92) {
                $res['risk'] = 3.6;
                $res['atrMultipliers'] = [2.6, 3.2, 3.7];
                $infoText .= 'risk ' . $res['risk'] . " high LinReg 15m\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= 0 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 400000000) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 15m\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " down adx 1h\n";
            }

            if ($marketImpulsInfo['linRegChannel4h']['percent'] > 98) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " high LinReg 4h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= 4000000000) {
                $res['risk'] = 3.4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 15m\n";
            }

            if (
                $marketImpulsInfo['actualStochastic15m']['%K'] <= 54
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 3.4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m stoch trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > 0 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 400000000) {
                $res['risk'] = 3.4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 5m\n";
            }

            if (
                ($marketImpulsInfo['actualStochastic1h']['%K'] <= 53 && $marketImpulsInfo['actualStochastic1h']['hist'] < 4)
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 3.4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 1h stoch trend\n";
            }

            if ($marketImpulsInfo['shortDivergenceVal5m'] || $marketImpulsInfo['shortDivergenceVal15m']
                || $btcImpulsInfo['shortDivergenceVal5m'] || $btcImpulsInfo['shortDivergenceVal15m']
                || $marketImpulsInfo['shortDivergenceVal1h']
            ) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " diver\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] >= 5000000000) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd 1h\n";
            }

            if (
                $marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] == 0
                || $marketImpulsInfo['actualImpulsMacd4h']['impulse_macd'] == 0
            ) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 4h 1h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > 0 && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] <= ($marketImpulsMacdVal / 2)) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 5m\n";
            }

            if (!\Maksv\Helpers\Trading::checkMaCondition($marketImpulsInfo['ma100_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 100 close\n";
            }

            if (!\Maksv\Helpers\Trading::checkMaCondition($marketImpulsInfo['ma200_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 200 close\n";
            }

            if (
                $marketImpulsInfo['actualAdx5m']['adx'] < 22
                || ($marketImpulsInfo['actualAdx5m']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx5m']['adx'] < 27)
            ) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 5m (22)\n";
            }


            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx1h']['adx'] < 25) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " down + low adx 1h (25)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 20) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (20)\n";
            }

            if (
                $marketImpulsInfo['actualAdx15m']['adx'] < 25
                && ($marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir'])
            ) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 15m\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 19 && $marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 1h (19)\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 22) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (22)\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 0) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " down trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] >= 4900000000) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 2 15m\n";
            }

            if (!\Maksv\Helpers\Trading::checkMaCondition($marketImpulsInfo['ma100_1h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'long')) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " 1h ma 100 close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['histogram'] > -100000000 && $marketImpulsInfo['actualImpulsMacd1h']['histogram'] < 50000000) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 1h\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 22 && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 15m (22)\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketMidImpulsBoard) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 5m\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > -500000000 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 0) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " trend close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > -750000000 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 0) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " 15m trend close\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 15) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (15)\n";
            }

            if (abs($marketImpulsInfo['actualImpulsMacd15m']['histogram']) < 150000000) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 15m \n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] > 6000000000) {
                $res['risk'] = 2.2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " high impuls macd 2 1h\n";
            }

            if (
                $marketImpulsInfo['actualAdx5m']['adx'] < 18
            ) {
                $res['risk'] = 2.2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " low adx 5m (18)\n";
            }

            if (!\Maksv\Helpers\Trading::checkMaCondition($marketImpulsInfo['ma100_4h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance / 1.5, 'long')) {
                $res['risk'] = 2.2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " 4h ma 100 close\n";
            }

            if (!\Maksv\Helpers\Trading::checkMaCondition($marketImpulsInfo['ma150_4h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance / 1.5, 'long')) {
                $res['risk'] = 2.2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " 4h ma 150 close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > $marketImpulsBoard && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] < ($marketStrongImpulsMacdVal)) {
                $res['risk'] = 2.2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 5m 2\n";
            }

            if ($marketImpulsInfo['linRegChannel1h']['percent'] > 93) {
                $res['risk'] = 2.1;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " high LinReg 1h\n";
            }

            if (
                $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] == 0
            ) {
                $res['risk'] = 2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 15m\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 17 && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']) {
                $res['risk'] = 2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (17)\n";
            }

            if ($marketImpulsInfo['mfi15m']['isUpDir'] === false && $marketImpulsInfo['mfi15m']['mfi'] <= 50) {
                $res['risk'] = 1.7;
                $res['atrMultipliers'] = [1.1, 1.9, 2.6];
                $infoText .= 'risk ' . $res['risk'] . " down mfi 15m\n";
            }

        }

        // risk/profit rules short
        if ($res['isShort']) {

            if ($marketImpulsInfo['linRegChannel15m']['percent'] < 8) {
                $res['risk'] = 3.6;
                $res['atrMultipliers'] = [2.6, 3.2, 3.7];
                $infoText .= 'risk ' . $res['risk'] . " low LinReg 15m\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= 0 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > -400000000) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 15m\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " down adx 1h\n";
            }

            if ($marketImpulsInfo['linRegChannel4h']['percent'] < 4) {
                $res['risk'] = 3.5;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low LinReg 4h\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= -4000000000) {
                $res['risk'] = 3.4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " high impuls macd line 15m\n";
            }

            if ($marketImpulsInfo['actualStochastic15m']['%K'] >= 47
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 3.4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m stoch trend\n";
            }


            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 0 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > -400000000) {
                $res['risk'] = 3.4;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls macd line 5m\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] <= -5000000000) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd 1h\n";
            }

            if ($marketImpulsInfo['longDivergenceVal5m'] || $marketImpulsInfo['longDivergenceVal15m']
                || $btcImpulsInfo['longDivergenceVal5m'] || $btcImpulsInfo['longDivergenceVal15m']
                || $marketImpulsInfo['longDivergenceVal1h']
            ) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " diver\n";
            }

            if (
                $marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] == 0
                || $marketImpulsInfo['actualImpulsMacd4h']['impulse_macd'] == 0
            ) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 4h 1h\n";
            }

            if (!\Maksv\Helpers\Trading::checkMaCondition($marketImpulsInfo['ma100_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 100 close\n";
            }

            if (!\Maksv\Helpers\Trading::checkMaCondition($marketImpulsInfo['ma200_15m'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 15m ma 200 close\n";
            }

            if (
                ($marketImpulsInfo['actualStochastic1h']['%K'] >= 47 && $marketImpulsInfo['actualStochastic1h']['hist'] > -4)
                && !($marketImpulsInfo['actualImpulsMacd5m']['histogram'] > $marketStrongImpulsMacdVal && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard)
            ) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " 1h stoch trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['histogram'] < 0 && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] >= -($marketImpulsMacdVal / 2)) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 5m\n";
            }

            if (
                $marketImpulsInfo['actualAdx5m']['adx'] < 22
                || ($marketImpulsInfo['actualAdx5m']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx5m']['adx'] < 27)
            ) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 5m (22)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir'] && $marketImpulsInfo['actualAdx1h']['adx'] < 25) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " down + low adx 1h (25)\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 20) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (20)\n";
            }

            if (
                $marketImpulsInfo['actualAdx15m']['adx'] < 25
                && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']
            ) {
                $res['risk'] = 3.2;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 15m\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 19 && $marketImpulsInfo['actualAdx1h']['adxDirection']['isDownDir']) {
                $res['risk'] = 3;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 1h (19)\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 22) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (22)\n";
            }


            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > 0) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " up trend\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] <= -4900000000) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . "  high impuls macd line 2 15m\n";
            }

            if (!\Maksv\Helpers\Trading::checkMaCondition($marketImpulsInfo['ma100_1h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance, 'short')) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " 1h ma 100 close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['histogram'] < 100000000 && $marketImpulsInfo['actualImpulsMacd1h']['histogram'] > -50000000) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 1h\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 22 && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low + down adx 15m (22)\n";
            }


            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketMidImpulsBoard * 1.05) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 5m\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < 500000000 && $marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] > 0) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " trend close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 750000000 && $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > 0) {
                $res['risk'] = 2.5;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " 15m trend close\n";
            }

            if ($marketImpulsInfo['actualAdx1h']['adx'] < 15) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " low adx 1h (15)\n";
            }

            if (abs($marketImpulsInfo['actualImpulsMacd15m']['histogram']) < 150000000) {
                $res['risk'] = 2.4;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " low impuls hist 15m\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] < -6000000000) {
                $res['risk'] = 2.2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " high impuls macd 2 1h\n";
            }

            if (
                $marketImpulsInfo['actualAdx5m']['adx'] < 18
            ) {
                $res['risk'] = 2.2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " low adx 5m (18)\n";
            }

            if (!\Maksv\Helpers\Trading::checkMaCondition($marketImpulsInfo['ma100_4h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance / 1.5, 'short')) {
                $res['risk'] = 2.2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " 4h ma 100 close\n";
            }

            if (!\Maksv\Helpers\Trading::checkMaCondition($marketImpulsInfo['ma150_4h'], $marketImpulsInfo['actualClosePrice15m'], $maDistance / 1.5, 'short')) {
                $res['risk'] = 2.2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " 4h ma 150 close\n";
            }

            if ($marketImpulsInfo['actualImpulsMacd5m']['impulse_macd'] < -$marketImpulsBoard && $marketImpulsInfo['actualImpulsMacd5m']['histogram'] > -($marketStrongImpulsMacdVal)) {
                $res['risk'] = 2.2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " high impuls 5m 2\n";
            }

            if ($marketImpulsInfo['linRegChannel1h']['percent'] < 7) {
                $res['risk'] = 2.1;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " low LinReg 1h\n";
            }

            if (
                $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] == 0
            ) {
                $res['risk'] = 2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " neutral trend 15m\n";
            }

            if ($marketImpulsInfo['actualAdx15m']['adx'] < 17 && $marketImpulsInfo['actualAdx15m']['adxDirection']['isDownDir']) {
                $res['risk'] = 2;
                $res['atrMultipliers'] = [1.6, 2.4, 3.2];
                $infoText .= 'risk ' . $res['risk'] . " low adx 15m (17)\n";
            }

            if ($marketImpulsInfo['mfi15m']['isDownDir'] === false && $marketImpulsInfo['mfi15m']['mfi'] >= 50) {
                $res['risk'] = 1.7;
                $res['atrMultipliers'] = [1.1, 1.9, 2.6];
                $infoText .= 'risk ' . $res['risk'] . " up mfi 15m\n";
            }
        }

        // risk/profit rules all
        /*if ($res['isLong'] || $res['isShort']) {
            if (
                $marketImpulsInfo['actualAdx1h']['adx'] < 20
                || $marketImpulsInfo['actualAdx5m']['adx'] < 22
            ) {
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
            }
        }*/

        //tpRules
        if (
            $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] < 0
            || $marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] <= $marketImpulsInfo['actualImpulsMacd1h']['signal_line']
        ) {
            $res['shortTpCount'] = 2;
            $res['longTpCount'] = 1;
        } else if (
            $marketImpulsInfo['actualImpulsMacd15m']['impulse_macd'] > 0
            || $marketImpulsInfo['actualImpulsMacd1h']['impulse_macd'] >= $marketImpulsInfo['actualImpulsMacd1h']['signal_line']
        ) {
            $res['shortTpCount'] = 1;
            $res['longTpCount'] = 2;
        }

        $backtraceStr = '';
        $backtraceAr = debug_backtrace();
        foreach ($backtraceAr as $key => $backtrace) {
            if ($backtrace['function'])
                $backtraceStr .= ($key + 1) . '. func ' . $backtrace['function'] . ' (' . $backtrace['line'] . ')' . "\n";
            else
                $backtraceStr .= ($key + 1) . '. file ' . $backtrace['file'] . ' (' . $backtrace['line'] . ')' . "\n";
        }

        $directionAr = ['long', 'short'];
        foreach ($directionAr as $directionItem) {
            if (!$res['atrMultipliers']) $res['atrMultipliers'] = [1.9, 2.6, 3.4];
            $atrMultipliersIncreased = array_map(fn($n) => $n * 1.1, $res['atrMultipliers']);
            //$atrMultipliersIncreased =  [1.4, 2.6, 3.4];

            try {
                $processedMarket = \Maksv\Helpers\Trading::processSignal(
                    $directionItem,
                    floatval($marketImpulsInfo['actualATR']['atr']),
                    floatval($marketImpulsInfo['actualClosePrice15m']),
                    $marketImpulsInfo['last30Candles15m'],
                    $marketImpulsInfo['actualSupertrend5m'],
                    $marketImpulsInfo['actualSupertrend15m'],
                    $marketImpulsInfo['actualMacdDivergence15m'],
                    1,
                    $atrMultipliersIncreased,
                    ['risk' => 10],
                    'others',
                    "getMarketInfo",
                    true,
                    false
                );
            } catch (\Exception $e) {
                \Maksv\DataOperation::sendErrorInfoMessage('catch processSignal market err - ' . $e->getMessage(), $backtraceStr, 'checkMarketImpulsInfo');
            }

            $res[$directionItem . 'Ml'] = $processedMarket['actualMlModel'];
        }

        $mainText = "Market:\n";
        $mainText .= 'direction ' . ($res['isLong'] ? 'Y' : 'N') . ' | ' . ($res['isShort'] ? 'Y' : 'N') . "\n";
        $mainText .= 'TP ' . $res['longTpCount'] . ' | ' . $res['shortTpCount'] . "\n";
        if ($res['longMl']['probabilities'][1] && $res['shortMl']['probabilities'][1]) $mainText .= 'ML predict ' . $res['longMl']['probabilities'][1] . '% | ' . $res['shortMl']['probabilities'][1] . "%\n";

        $mainText .= "\nSignals:\n";
        $mainText .= 'Risk ' . ($res['risk'] ? $res['risk'] : '-') . "\n";
        $infoText = $mainText . "\n " . $infoText . "\n";

        $res['infoText'] = $infoText;
        return $res;
    }

    /**
     * Получает и анализирует рыночную информацию с различных таймфреймов.
     *
     * Использует кэширование и механизм блокировок для предотвращения параллельного выполнения.
     * Выполняет комплексный технический анализ на таймфреймах 5m, 15m, 1h и 4h с использованием
     * множества индикаторов: MACD, Supertrend, Stochastic RSI, ADX, ATR, MFI, LinReg, Fibonacci и других.
     *
     * @param string $symbol Символ для анализа ('others' по умолчанию, 'total3' - альтернативный вариант)
     *
     * @return array
     *   Ассоциативный массив с результатами анализа, содержащий:
     *   - Данные индикаторов для всех таймфреймов (5m, 15m, 1h, 4h)
     *   - Информацию о скользящих средних (MA100, MA200, MA150)
     *   - Данные о дивергенциях
     *   - Уровни Фибоначчи
     *   - ATR и волатильность
     *   - Статусы ошибок (если есть)
     *   - Временные метки и информацию о свежасти данных
     *
     * @throws \Exception В случае ошибок при расчете индикаторов или получении данных
     */
    public static function getMarketInfo($symbol = 'others')
    {
        $devlogsCode = 'getMarketInfo/' . $symbol;
        $cacheID = md5($devlogsCode . 'Cache|' . $symbol);

        $backtraceStr = '';
        $backtraceAr = debug_backtrace();
        foreach ($backtraceAr as $key => $backtrace) {
            if ($backtrace['function'])
                $backtraceStr .= ($key + 1) . '. func ' . $backtrace['function'] . ' (' . $backtrace['line'] . ')' . "\n";
            else
                $backtraceStr .= ($key + 1) . '. file ' . $backtrace['file'] . ' (' . $backtrace['line'] . ')' . "\n";
        }

        // --- файловый лок, предотвращающий параллельный exec ---
        $lockFile = $_SERVER['DOCUMENT_ROOT'] . '/upload/traydingviewExchange/getMarketInfo.lock';
        $fp = fopen($lockFile, 'c');
        if (!$fp) {
            \Maksv\DataOperation::sendErrorInfoMessage(
                "Cannot open lock file: {$lockFile}",
                '',
                $devlogsCode
            );
            return ['err' => ['Lock open error']];
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            // Другой процесс обновляет: ждём появления кеша до 10 секунд
            $cache = \Bitrix\Main\Data\Cache::createInstance();
            $waitStart = time();
            $res = null;
            $tryGetCacheTime = 0;
            while (time() - $waitStart < 15) {
                if ($cache->initCache(30, $cacheID)) {
                    $res = $cache->getVars();
                    $tryGetCacheTime = time() - $waitStart;
                    break;
                }
                usleep(250000);                       // пауза n сек перед повтором
            }
            if ($res !== null) {
                //$err = "Получили cache - {$symbol}, попытка длилась - {$tryGetCacheTime} сек";
                //\Maksv\DataOperation::sendErrorInfoMessage($err, $backtraceStr, $devlogsCode);
                return $res;
            }
            // кеш так и не появился — возвращаем ошибку
            //$err = "Не получили cache, идем в api tw - {$symbol}";
            //\Maksv\DataOperation::sendErrorInfoMessage($err, $backtraceStr, $devlogsCode);
            //return ['err' => [$err]];
        }

        try {
            $cache = \Bitrix\Main\Data\Cache::createInstance();
            if ($cache->initCache(30, $cacheID)) {
                $res = $cache->getVars();
            } elseif ($cache->startDataCache()) {
                devlogs('start no cache.  timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);

                $exec = new \Maksv\Traydingview\RequestExecutor();
                if (!$exec->execute($symbol)) {
                    $errText = 'execute not ok, watch py script';
                    $res['err'][] = 'get others, watch py script';
                    \Maksv\DataOperation::sendErrorInfoMessage($errText, $backtraceStr, 'getMarketInfo');
                    $cache->abortDataCache();
                    return $res;
                }

                $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/traydingviewExchange/total_ex_top10.json';
                switch ($symbol) {
                    case 'others':
                        $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/traydingviewExchange/total_ex_top10.json';
                        break;
                    case 'total3':
                        $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/traydingviewExchange/total3.json';
                        break;
                }

                $marketData = json_decode(file_get_contents($path), true) ?? [];
                $timestamp = $marketData['timestamp'] ?? 0;

                if (time() - $timestamp > 180) { // 3 минут = 300 секунд
                    $errText = 'Data is older than 3 minutes';
                    $res['err'][] = $errText;
                    \Maksv\DataOperation::sendErrorInfoMessage($errText, $backtraceStr, 'getMarketInfo');
                    $cache->abortDataCache();
                    return $res;
                }

                $res['marketReadDif'] = time() - $timestamp;
                $res['marketReadDifRule'] = time() - $timestamp > 100;
                $marketKlines = $marketData['data'];

                $klineList5m = $marketKlines['5m'] ?? [];
                if (!$klineList5m) {
                    $errText = 'не удалось получить candles 5m';
                    $res['err'][] = $errText;
                    \Maksv\DataOperation::sendErrorInfoMessage($errText, $backtraceStr, 'getMarketInfo');
                    $cache->abortDataCache();
                }

                if ($klineList5m && is_array($klineList5m) && count($klineList5m) > 80) {
                    $candles5m = array_map(function ($k) {
                        // создаём объект DateTime из строки
                        $dt = new \DateTime($k['datetime']);
                        // получаем секунды с эпохи и умножаем на 1000 — получаем миллисекунды
                        $ms = $dt->getTimestamp() * 1000;

                        return [
                            't' => $ms, // timestamp
                            'o' => floatval($k['open']), // Open price
                            'h' => floatval($k['high']), // High price
                            'l' => floatval($k['low']), // Low price
                            'c' => floatval($k['close']), // Close price
                            'v' => floatval($k['volume'])  // Volume
                        ];
                    }, $klineList5m);

                    try {
                        $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($candles5m, 10, 3) ?? false; // длина 10, фактор 3
                        $res['actualSupertrend5m'] = $actualSupertrend5m = $supertrendData[array_key_last($supertrendData) - 1] ?? false;
                    } catch (\Exception $e) {
                        devlogs('ERR | err - Supertrend 5m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $macdData5m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles5m, 12, 'EMA', 26, 'EMA', 9, 'EMA', 8, 'histogram') ?? false;
                        if ($macdData5m && is_array($macdData5m))
                            $res['actualMacd5m'] = $actualMacd5m = $macdData5m[array_key_last($macdData5m)];
                        unset($res['actualMacd5m']['extremes']);
                    } catch (\Exception $e) {
                        devlogs('ERR | err - macd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $stochasticOscillatorData5m = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles5m) ?? false;
                        if ($stochasticOscillatorData5m && is_array($stochasticOscillatorData5m))
                            /* $res['actualStochastic5m'] = */ $actualStochastic5m = $stochasticOscillatorData5m[array_key_last($stochasticOscillatorData5m)];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - stoch 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $actualMacdDivergence5m = self::checkMultiMACD(
                            $candles5m,
                            '5m',
                            ['5m' => 16, '15m' => 16, '30m' => 5, '1h' => 5, '4h' => 8, '1d' => 8],
                        );

                        $res['longDivergenceVal5m'] = $res['shortDivergenceVal5m'] = false;
                        if ($actualMacdDivergence5m['longDivergenceTypeAr']['regular']) {
                            $res['longDivergenceVal5m'] = true;
                            $res['longDivergenceText5m'] = 'oth bullish dever ' . $actualMacdDivergence5m['inputParams'] . ' (' . $actualMacdDivergence5m['longDivergenceDistance'] . '), 5m';
                        }

                        if ($actualMacdDivergence5m['shortDivergenceTypeAr']['regular']) {
                            $res['shortDivergenceVal5m'] = true;
                            $res['shortDivergenceText5m'] = 'oth bearish dever ' . $actualMacdDivergence5m['inputParams'] . ' (' . $actualMacdDivergence5m['shortDivergenceDistance'] . '), 5m';
                        }

                        //unset( $res['actualMacdDivergence5m']['extremes']);
                    } catch (\Exception $e) {
                        devlogs('ERR | err - checkMultiMACD 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $adxData5m = \Maksv\TechnicalAnalysis::calculateADX($candles5m);
                        $res['actualAdx5m'] = $actualAdx5m = $adxData5m[array_key_last($adxData5m)];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - actualAdx5m 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $impulseMACD5m = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles5m) ?? false;
                        if ($impulseMACD5m && is_array($impulseMACD5m))
                            $res['actualImpulsMacd5m'] = $actualImpulsMacd5m = $impulseMACD5m[array_key_last($impulseMACD5m)];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - actualImpulsMacd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $linRegChannelData5m = \Maksv\TechnicalAnalysis::calculateLinRegChannel($candles5m, 100, 2.0);
                        $res['linRegChannel5m'] = $linRegChannel5m = end($linRegChannelData5m);
                    } catch (\Exception $e) {
                        devlogs('ERR | err - linRegChanne 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }
                }

                $klineList15m = $marketKlines['15m'] ?? [];
                if (!$klineList15m) {
                    $errText = 'не удалось получить candles 15m';
                    $res['err'][] = $errText;
                    \Maksv\DataOperation::sendErrorInfoMessage($errText, $backtraceStr, 'getMarketInfo');
                    $cache->abortDataCache();
                }

                $res['ma100_15m'] = $res['ma200_15m'] = [];
                if ($klineList15m && is_array($klineList15m) && count($klineList15m) > 80) {

                    $actualKline15m = $klineList15m[array_key_last($klineList15m)] ?? false;
                    if ($actualKline15m)
                        $res['actualClosePrice15m'] = floatval($actualKline15m['close']);

                    $candles15m = array_map(function ($k) {
                        // создаём объект DateTime из строки
                        $dt = new \DateTime($k['datetime']);
                        // получаем секунды с эпохи и умножаем на 1000 — получаем миллисекунды
                        $ms = $dt->getTimestamp() * 1000;

                        return [
                            't' => $ms, // timestamp
                            'o' => floatval($k['open']), // Open price
                            'h' => floatval($k['high']), // High price
                            'l' => floatval($k['low']), // Low price
                            'c' => floatval($k['close']), // Close price
                            'v' => floatval($k['volume'])  // Volume
                        ];
                    }, $klineList15m);

                    $res['last30Candles15m'] = array_slice($candles15m, -30);
                    if (!$res['last30Candles15m'] || !is_array($res['last30Candles15m'])) {
                        $res['last30Candles15m'] = [];
                        \Maksv\DataOperation::sendErrorInfoMessage('not found data last30Candles15m', $backtraceStr, 'getMarketInfo');
                    }

                    try {
                        $impulseMACD15m = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles15m) ?? false;
                        if ($impulseMACD15m && is_array($impulseMACD15m))
                            $res['actualImpulsMacd15m'] = $actualImpulsMacd15m = $impulseMACD15m[array_key_last($impulseMACD15m)];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - actualImpulsMacd 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    $screenerData['actualMacd'] = $actualMacd = [];
                    try {
                        $macdSimpleData15m = \Maksv\TechnicalAnalysis::analyzeMACD($candles15m) ?? false;
                        $res['actualSimpleMacd15m'] = $actualSimpleMacd15m = $macdSimpleData15m[array_key_last($macdSimpleData15m)] ?? false;
                    } catch (\Exception $e) {
                        devlogs('ERR | err - actualSimpleMacd15m 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $res['actualMacdDivergence15m'] = $actualMacdDivergence15m = self::checkMultiMACD(
                            $candles15m,
                            '15m',
                            ['5m' => 16, '15m' => 13, '30m' => 11, '1h' => 5, '4h' => 8, '1d' => 8],
                        );

                        $res['longDivergenceVal15m'] = $res['shortDivergenceVal15m'] = false;
                        if ($actualMacdDivergence15m['longDivergenceTypeAr']['regular']) {
                            $res['longDivergenceVal15m'] = true;
                            $res['longDivergenceText15m'] = 'oth bullish dever ' . $actualMacdDivergence15m['inputParams'] . ' (' . $actualMacdDivergence15m['longDivergenceDistance'] . '), 15m';

                        }

                        if ($actualMacdDivergence15m['shortDivergenceTypeAr']['regular']) {
                            $res['shortDivergenceVal15m'] = true;
                            $res['shortDivergenceText15m'] = 'oth bearish dever ' . $actualMacdDivergence15m['inputParams'] . ' (' . $actualMacdDivergence15m['shortDivergenceDistance'] . '), 15m';
                        }

                        //unset( $res['actualMacdDivergence15m']['extremes']);
                    } catch (\Exception $e) {
                        devlogs('ERR | err - checkMultiMACD ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($candles15m, 10, 3) ?? false; // длина 10, фактор 3
                        $res['actualSupertrend15m'] = $actualSupertrend15m = $supertrendData[array_key_last($supertrendData) - 1] ?? false;
                    } catch (\Exception $e) {
                        devlogs('ERR | err - Supertrend' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        //$macdData15m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles15m, 5, 'SMA', 35, 'SMA', 5,'SMA', 8, 'macdLine') ?? false;
                        $macdData15m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles15m, 12, 'EMA', 26, 'EMA', 9, 'EMA', 8, 'histogram') ?? false;
                        if ($macdData15m && is_array($macdData15m)) {
                            $res['actualMacd15m'] = $actualMacd15m = $macdData15m[array_key_last($macdData15m)];
                            unset($res['actualMacd15m']['extremes']);
                        }
                    } catch (\Exception $e) {
                        devlogs('ERR | err - macd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $stochasticOscillatorData15m = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles15m) ?? false;
                        if ($stochasticOscillatorData15m && is_array($stochasticOscillatorData15m))
                            $res['actualStochastic15m'] = $actualStochastic15m = $stochasticOscillatorData15m[array_key_last($stochasticOscillatorData15m)];

                    } catch (\Exception $e) {
                        devlogs('ERR | err - stoch 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $adxData15m = \Maksv\TechnicalAnalysis::calculateADX($candles15m);
                        $res['actualAdx15m'] = $actualAdx15m = $adxData15m[array_key_last($adxData15m)];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - actualAdx5m 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $atrData15m = \Maksv\TechnicalAnalysis::calculateATR($candles15m);
                        $res['actualAtr15m'] = $actualAtr15m = $atrData15m[array_key_last($atrData15m)];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - actualAtr15m 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $res['ma100_15m'] = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 9, 100, 20, 2) ?? [];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - ma100 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $res['ma200_15m'] = \Maksv\TechnicalAnalysis::checkMACross($candles15m, 9, 200, 20, 2) ?? [];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - ma200 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $ATRData = \Maksv\TechnicalAnalysis::calculateATR($candles15m);
                        $res['actualATR'] = $ATRData[array_key_last($ATRData)] ?? null;
                    } catch (\Exception $e) {
                        devlogs('ERR | err - atr ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $mfiData15m = \Maksv\TechnicalAnalysis::calculateMFI($candles15m);
                        //$res['data'] = $mfiData15m[array_key_last($mfiData15m)] ?? [];
                        $res['mfi15m'] = $mfiData15m[array_key_last($mfiData15m)] ?? [];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - mfi 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $linRegChannelData15m = \Maksv\TechnicalAnalysis::calculateLinRegChannel($candles15m, 100, 2.0);
                        $res['linRegChannel15m'] = $linRegChannel15m = end($linRegChannelData15m);
                    } catch (\Exception $e) {
                        devlogs('ERR | err - linRegChanne 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $res['fibonacciLevels15m'] = \Maksv\TechnicalAnalysis::buildFibonacciLevels($candles15m, 8);

                    } catch (\Exception $e) {
                        devlogs('ERR | err - fiba 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                }

                $klineList1h = $marketKlines['1h'] ?? [];
                if (!$klineList1h) {
                    $errText = 'не удалось получить candles 1h';
                    $res['err'][] = $errText;
                    \Maksv\DataOperation::sendErrorInfoMessage($errText, $backtraceStr, 'getMarketInfo');
                    $cache->abortDataCache();
                }

                $res['ma100_1h'] = [];
                if ($klineList1h && is_array($klineList1h) && count($klineList1h) > 80) {
                    $candles1h = array_map(function ($k) {
                        // создаём объект DateTime из строки
                        $dt = new \DateTime($k['datetime']);
                        // получаем секунды с эпохи и умножаем на 1000 — получаем миллисекунды
                        $ms = $dt->getTimestamp() * 1000;

                        return [
                            't' => $ms, // timestamp
                            'o' => floatval($k['open']), // Open price
                            'h' => floatval($k['high']), // High price
                            'l' => floatval($k['low']), // Low price
                            'c' => floatval($k['close']), // Close price
                            'v' => floatval($k['volume'])  // Volume
                        ];
                    }, $klineList1h);


                    $res['last30Candles1h'] = array_slice($candles1h, -30);
                    if (!$res['last30Candles1h'] || !is_array($res['last30Candles1h'])) {
                        $res['last30Candles1h'] = [];
                        \Maksv\DataOperation::sendErrorInfoMessage('not found data last30Candles1h', $backtraceStr, 'getMarketInfo');
                    }

                    try {

                        /*$res['actualMacdDivergence1h'] =*/
                        $actualMacdDivergence1h = self::checkMultiMACD(
                            $candles1h,
                            '1h',
                            ['5m' => 16, '15m' => 16, '30m' => 11, '1h' => 6, '4h' => 8, '1d' => 8],
                        );

                        $res['longDivergenceVal1h'] = $res['shortDivergenceVal1h'] = false;
                        if ($actualMacdDivergence1h['longDivergenceTypeAr']['regular']) {
                            $res['longDivergenceVal1h'] = true;
                            $res['longDivergenceText1h'] = 'oth bullish dever ' . $actualMacdDivergence1h['inputParams'] . ' (' . $actualMacdDivergence1h['longDivergenceDistance'] . '), 1h';

                        }

                        if ($actualMacdDivergence1h['shortDivergenceTypeAr']['regular']) {
                            $res['shortDivergenceVal1h'] = true;
                            $res['shortDivergenceText1h'] = 'oth bearish dever ' . $actualMacdDivergence1h['inputParams'] . ' (' . $actualMacdDivergence1h['shortDivergenceDistance'] . '), 1h';
                        }

                        //unset( $res['actualMacdDivergence1h']['extremes']);
                    } catch (\Exception $e) {
                        devlogs('ERR | err - checkMultiMACD 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $stochasticOscillatorData1h = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles1h) ?? false;
                        if ($stochasticOscillatorData1h && is_array($stochasticOscillatorData1h))
                            $res['actualStochastic1h'] = $actualStochastic1h = $stochasticOscillatorData1h[array_key_last($stochasticOscillatorData1h)];

                    } catch (\Exception $e) {
                        devlogs('ERR | err - stoch 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $adxData1h = \Maksv\TechnicalAnalysis::calculateADX($candles1h);
                        $res['actualAdx1h'] = $actualAdx1h = $adxData1h[array_key_last($adxData1h)];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - actualAdx5m 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $res['ma100_1h'] = \Maksv\TechnicalAnalysis::checkMACross($candles1h, 4, 100, 20, 2) ?? [];
                        $res['ma100_1h']['actualClosePrice15m'] = $res['actualClosePrice15m'];

                        if ($res['ma100_1h']['ema'] < $res['ma100_1h']['sma']) {
                            $res['ma100_1h']['isUptrend'] = false;
                        } else if ($res['ma100_1h']['ema'] > $res['ma100_1h']['sma']) {
                            $res['ma100_1h']['isUptrend'] = true;
                        } else {
                            if ($res['actualClosePrice15m'] < $res['ma100_1h']['sma'])
                                $res['ma100_1h']['isUptrend'] = false;
                            else if ($res['actualClosePrice15m'] < $res['ma100_1h']['sma'])
                                $res['ma100_1h']['isUptrend'] = true;
                        }

                    } catch (\Exception $e) {
                        devlogs('ERR | err - ma100 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $impulseMACD1h = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles1h) ?? false;
                        if ($impulseMACD1h && is_array($impulseMACD1h))
                            $res['actualImpulsMacd1h'] = $actualImpulsMacd1h = $impulseMACD1h[array_key_last($impulseMACD1h)];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - actualImpulsMacd 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $linRegChannelData1h = \Maksv\TechnicalAnalysis::calculateLinRegChannel($candles1h, 100, 2.0);
                        $res['linRegChannel1h'] = $linRegChannel1h = end($linRegChannelData1h);
                    } catch (\Exception $e) {
                        devlogs('ERR | err - linRegChanne 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $mfiData1h = \Maksv\TechnicalAnalysis::calculateMFI($candles1h);
                        $res['mfi1h'] = $mfiData1h[array_key_last($mfiData1h)] ?? [];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - mfi 1h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }
                }

                $klineList4h = $marketKlines['4h'] ?? [];
                if (!$klineList4h) {
                    $errText = 'не удалось получить candles 4h';
                    $res['err'][] = $errText;
                    \Maksv\DataOperation::sendErrorInfoMessage($errText, $backtraceStr, 'getMarketInfo');
                    $cache->abortDataCache();
                }

                $res['ma100_4h'] = $res['ma150_4h'] = [];
                if ($klineList4h && is_array($klineList4h) && count($klineList4h) > 80) {
                    $candles4h = array_map(function ($k) {
                        // создаём объект DateTime из строки
                        $dt = new \DateTime($k['datetime']);
                        // получаем секунды с эпохи и умножаем на 1000 — получаем миллисекунды
                        $ms = $dt->getTimestamp() * 1000;

                        return [
                            't' => $ms, // timestamp
                            'o' => floatval($k['open']), // Open price
                            'h' => floatval($k['high']), // High price
                            'l' => floatval($k['low']), // Low price
                            'c' => floatval($k['close']), // Close price
                            'v' => floatval($k['volume'])  // Volume
                        ];
                    }, $klineList4h);

                    try {
                        $impulseMACD4h = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles4h) ?? false;
                        if ($impulseMACD4h && is_array($impulseMACD4h))
                            $res['actualImpulsMacd4h'] = $actualImpulsMacd4h = $impulseMACD4h[array_key_last($impulseMACD4h)];
                    } catch (\Exception $e) {
                        devlogs('ERR | err - actualImpulsMacd  4h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $res['ma100_4h'] = \Maksv\TechnicalAnalysis::checkMACross($candles4h, 2, 100, 20, 2) ?? [];
                        $res['ma100_4h']['actualClosePrice15m'] = $res['actualClosePrice15m'];
                        if ($res['ma100_4h']['ema'] < $res['ma100_4h']['sma']) {
                            $res['ma100_4h']['isUptrend'] = false;
                        } else if ($res['ma100_4h']['ema'] > $res['ma100_4h']['sma']) {
                            $res['ma100_4h']['isUptrend'] = true;
                        } else {
                            if ($res['actualClosePrice15m'] < $res['ma100_4h']['sma']) {
                                $res['ma100_4h']['isUptrend'] = false;
                            } else if ($res['actualClosePrice15m'] < $res['ma100_4h']['sma']) {
                                $res['ma100_4h']['isUptrend'] = true;
                            }
                        }

                    } catch (\Exception $e) {
                        devlogs('ERR | err - ma100 4h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $res['ma150_4h'] = \Maksv\TechnicalAnalysis::checkMACross($candles4h, 3, 150, 20, 2) ?? [];

                        $res['ma150_4h']['actualClosePrice15m'] = $res['actualClosePrice15m'];
                        if ($res['ma150_4h']['ema'] < $res['ma150_4h']['sma']) {
                            $res['ma150_4h']['isUptrend'] = false;
                        } else if ($res['ma150_4h']['ema'] > $res['ma150_4h']['sma']) {
                            $res['ma150_4h']['isUptrend'] = true;
                        }
                    } catch (\Exception $e) {
                        devlogs('ERR | err - ma150 4h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        $linRegChannelData4h = \Maksv\TechnicalAnalysis::calculateLinRegChannel($candles4h, 100, 2.0);
                        $res['linRegChannel4h'] = $linRegChannel4h = end($linRegChannelData4h);
                    } catch (\Exception $e) {
                        devlogs('ERR | err - linRegChanne 4h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }

                    try {
                        //$firstNCandles =  array_slice($candles4h, 0, 250);
                        $res['fibonacciLevels4h'] = \Maksv\TechnicalAnalysis::buildFibonacciLevels($candles4h, 8);

                    } catch (\Exception $e) {
                        devlogs('ERR | err - fiba 4h ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), $devlogsCode);
                    }
                }

                devlogs('end no cache.  timeMark - ' . date("d.m.y H:i:s") . '_________________________', $devlogsCode);
                $cache->endDataCache($res);
            }

            return $res;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * Анализирует импульсную информацию по BTCUSDT на основе множества технических индикаторов.
     *
     * Выполняет комплексный технический анализ на 5-минутном и 15-минутном таймфреймах,
     * используя MACD, Stochastic RSI, ADX, Supertrend и пользовательские импульсные индикаторы.
     * Формирует торговые сигналы и рассчитывает параметры риска и тейк-профитов.
     *
     * @param int $trendBoard Пороговое значение для определения тренда по MACD (по умолчанию 10)
     *
     * @return array
     *   Ассоциативный массив с результатами анализа:
     *   - 'isLong' (bool) Сигнал на покупку
     *   - 'isShort' (bool) Сигнал на продажу
     *   - 'risk' (float|false) Уровень риска
     *   - 'atrMultipliers' (array|false) Множители ATR для тейк-профитов
     *   - 'shortTpCount' (int|false) Количество тейк-профитов для шорта
     *   - 'longTpCount' (int|false) Количество тейк-профитов для лонга
     *   - 'infoText' (string) Текстовое описание анализа
     *   - Данные по дивергенциям и индикаторам для обоих таймфреймов
     *
     * @throws \Exception В случае ошибок при расчете индикаторов
     */
    public static function checkBtcImpulsInfo($trendBoard = 10)
    {
        $infoText = $actualAdx5m = $actualAdx15m = $actualImpulsMacd5m = $actualImpulsMacd15m = $actualSupertrend15m = $actualStochastic5m = $actualMacd15m = $actualStochastic15m = $actualMacdDivergence5m = $actualMacd5m = $actualMacdDivergence15m = false;
        $res['isLong'] = $res['isShort'] = false;
        $res['risk'] = $res['atrMultipliers'] = $res['shortTpCount'] = $res['longTpCount'] = false;

        $bybitApiOb = new \Maksv\Bybit\Bybit();
        $bybitApiOb->openConnection();

        $kline5m = $bybitApiOb->klineV5("linear", "BTCUSDT", '5m', 402, true, 120);
        if ($kline5m['result'] && $kline5m['result']['list']) {
            $klineList5m = array_reverse($kline5m['result']['list']);
            $candles5m = array_map(function ($k) {
                return [
                    't' => floatval($k[0]), // timestap
                    'o' => floatval($k[1]), // Open price
                    'h' => floatval($k[2]), // High price
                    'l' => floatval($k[3]), // Low price
                    'c' => floatval($k[4]), // Close price
                    'v' => floatval($k[5])  // Volume
                ];
            }, $klineList5m);

            try {
                //$macdData5m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles5m, 5, 'SMA', 35, 'SMA', 5,'SMA', 8, 'macdLine') ?? false;
                $macdData5m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles5m, 12, 'EMA', 26, 'EMA', 9, 'EMA', 8, 'histogram') ?? false;
                if ($macdData5m && is_array($macdData5m))
                    $actualMacd5m = $macdData5m[array_key_last($macdData5m)];

            } catch (\Exception $e) {
                devlogs('ERR | err - macd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $stochasticOscillatorData5m = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles5m) ?? false;
                if ($stochasticOscillatorData5m && is_array($stochasticOscillatorData5m))
                    $actualStochastic5m = $stochasticOscillatorData5m[array_key_last($stochasticOscillatorData5m)];

            } catch (\Exception $e) {
                devlogs('ERR | err - stoch 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $actualMacdDivergence5m = self::checkMultiMACD(
                    $candles5m,
                    '5m',
                    ['5m' => 10, '15m' => 10, '30m' => 10, '1h' => 7, '4h' => 8, '1d' => 8],
                );

                $res['longDivergenceVal5m'] = $res['shortDivergenceVal5m'] = false;
                if ($actualMacdDivergence5m['longDivergenceTypeAr']['regular']) {
                    $res['longDivergenceVal5m'] = true;
                    $res['longDivergenceText5m'] = 'btc bullish dever ' . $actualMacdDivergence5m['inputParams'] . ' (' . $actualMacdDivergence5m['longDivergenceDistance'] . '), 5m';
                }

                if ($actualMacdDivergence5m['shortDivergenceTypeAr']['regular']) {
                    $res['shortDivergenceVal5m'] = true;
                    $res['shortDivergenceText5m'] = 'btc bearish dever ' . $actualMacdDivergence5m['inputParams'] . ' (' . $actualMacdDivergence5m['shortDivergenceDistance'] . '), 5m';
                }
            } catch (\Exception $e) {
                devlogs('ERR | err - checkMultiMACD 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $adxData5m = \Maksv\TechnicalAnalysis::calculateADX($candles5m);
                $actualAdx5m = $adxData5m[array_key_last($adxData5m)];
            } catch (\Exception $e) {
                devlogs('ERR | err - actualAdx5m 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $impulseMACD5m = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles5m) ?? false;
                if ($impulseMACD5m && is_array($impulseMACD5m))
                    $res['actualImpulsMacd5m'] = $actualImpulsMacd5m = $impulseMACD5m[array_key_last($impulseMACD5m)];
            } catch (\Exception $e) {
                devlogs('ERR | err - actualImpulsMacd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }
        }

        $kline15m = $bybitApiOb->klineV5("linear", "BTCUSDT", '15m', 402, true, 120);
        if ($kline15m['result'] && $kline15m['result']['list']) {
            $klineList15m = array_reverse($kline15m['result']['list']);
            $candles15m = array_map(function ($k) {
                return [
                    't' => floatval($k[0]), // timestap
                    'o' => floatval($k[1]), // Open price
                    'h' => floatval($k[2]), // High price
                    'l' => floatval($k[3]), // Low price
                    'c' => floatval($k[4]), // Close price
                    'v' => floatval($k[5])  // Volume
                ];
            }, $klineList15m);

            try {
                $impulseMACD15m = \Maksv\TechnicalAnalysis::analyzeImpulseMACD($candles15m) ?? false;
                if ($impulseMACD15m && is_array($impulseMACD15m))
                    $res['actualImpulsMacd15m'] = $actualImpulsMacd15m = $impulseMACD15m[array_key_last($impulseMACD15m)];
            } catch (\Exception $e) {
                devlogs('ERR | err - actualImpulsMacd 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $actualMacdDivergence15m = self::checkMultiMACD(
                    $candles15m,
                    '15m',
                    ['5m' => 11, '15m' => 11, '30m' => 11, '1h' => 8, '4h' => 8, '1d' => 8],
                );

                $res['longDivergenceVal15m'] = $res['shortDivergenceVal15m'] = false;
                if ($actualMacdDivergence15m['longDivergenceTypeAr']['regular']) {
                    $res['longDivergenceVal15m'] = true;
                    $res['longDivergenceText15m'] = 'btc bullish dever ' . $actualMacdDivergence15m['inputParams'] . ' (' . $actualMacdDivergence15m['longDivergenceDistance'] . '), 15m';

                }

                if ($actualMacdDivergence15m['shortDivergenceTypeAr']['regular']) {
                    $res['shortDivergenceVal15m'] = true;
                    $res['shortDivergenceText15m'] = 'btc bearish dever ' . $actualMacdDivergence15m['inputParams'] . ' (' . $actualMacdDivergence15m['shortDivergenceDistance'] . '), 15m';
                }
            } catch (\Exception $e) {
                devlogs('ERR | err - checkMultiMACD ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $supertrendData = \Maksv\TechnicalAnalysis::calculateSupertrend($candles15m, 10, 3) ?? false; // длина 10, фактор 3
                $actualSupertrend15m = $supertrendData[array_key_last($supertrendData) - 1] ?? false;
            } catch (\Exception $e) {
                devlogs('ERR | err - Supertrend' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                //$macdData15m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles15m, 5, 'SMA', 35, 'SMA', 5,'SMA', 8, 'macdLine') ?? false;
                $macdData15m = \Maksv\TechnicalAnalysis::calculateMacdExt($candles15m, 12, 'EMA', 26, 'EMA', 9, 'EMA', 8, 'histogram') ?? false;
                if ($macdData15m && is_array($macdData15m))
                    $actualMacd15m = $macdData15m[array_key_last($macdData15m)];

            } catch (\Exception $e) {
                devlogs('ERR | err - macd 5m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $adxData15m = \Maksv\TechnicalAnalysis::calculateADX($candles15m);
                $actualAdx15m = $adxData15m[array_key_last($adxData15m)];
            } catch (\Exception $e) {
                devlogs('ERR | err - actualAdx 15m' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }

            try {
                $stochasticOscillatorData15m = \Maksv\TechnicalAnalysis::calculateStochasticRSI($candles15m) ?? false;
                if ($stochasticOscillatorData15m && is_array($stochasticOscillatorData15m))
                    $actualStochastic15m = $stochasticOscillatorData15m[array_key_last($stochasticOscillatorData15m)];

            } catch (\Exception $e) {
                devlogs('ERR | err - stoch 15m ' . $e . ' | timeMark - ' . date("d.m.y H:i:s"), 'checkBtcInfo');
            }
        }

        //macd trend 15m text
        /*if ($actualMacd15m && $actualMacd15m['main_values']['macd_line'] > $trendBoard)
            $infoText .= 'local trend - up, (' . round($actualMacd15m['main_values']['macd_line'], 1) . ') 15m' . "\n";
        else if ($actualMacd15m && $actualMacd15m['main_values']['macd_line'] < -$trendBoard)
            $infoText .= 'local trend - down, (' . round($actualMacd15m['main_values']['macd_line'], 1) . ') 15m' . "\n";
        else
            $infoText .= 'local trend - neutral, (' . round($actualMacd15m['main_values']['macd_line'], 1) . ') 15m' . "\n";*/

        $infoText .= "\n" . 'btc info:' . "\n\n";

        //stoch 15m text
        $infoText .= 'stoch hist ' . round($actualStochastic15m['hist'], 2) . ' (' . round($actualStochastic15m['%K'], 2) . ', ' . round($actualStochastic15m['%D'], 2) . '), 15m' . "\n";
        //stoch 5m text
        $infoText .= 'stoch hist ' . round($actualStochastic5m['hist'], 2) . ' (' . round($actualStochastic5m['%K'], 2) . ', ' . round($actualStochastic5m['%D'], 2) . '), 5m' . "\n\n";

        //impuls macd 15m text
        $infoText .= 'impuls macd hist ' . round($actualImpulsMacd15m['histogram'], 1) . ' trend ' . ($actualImpulsMacd15m['trend']['trendText'])
            . ' (' . round($actualImpulsMacd15m['impulse_macd'], 2) . ', '
            . round($actualImpulsMacd15m['signal_line'], 2) . '), (' . $actualImpulsMacd5m['trend']['trendVal'] . '), 15m' . "\n";

        //impuls 5m text
        $infoText .= 'impuls macd hist ' . round($actualImpulsMacd5m['histogram'], 1) . ' trend ' . ($actualImpulsMacd5m['trend']['trendText'])
            . ' (' . round($actualImpulsMacd5m['impulse_macd'], 2) . ', '
            . round($actualImpulsMacd5m['signal_line'], 2) . '), (' . $actualImpulsMacd5m['trend']['trendVal'] . '), 5m' . "\n\n";

        //adx 15m text
        $infoText .= 'adx trend ' . $actualAdx15m['trendDirection']['trendDir'] . ', dir ' . $actualAdx15m['adxDirection']['adxDir'] . ' (' . round($actualAdx15m['adx'], 2) . '), 15m' . "\n";
        //adx 5m text
        $infoText .= 'adx trend ' . $actualAdx5m['trendDirection']['trendDir'] . ', dir ' . $actualAdx5m['adxDirection']['adxDir'] . ' (' . round($actualAdx5m['adx'], 2) . '), 5m' . "\n\n";

        //macd trend 5m text
        if ($actualMacd5m && $actualMacd5m['main_values']['macd_line'] > $trendBoard)
            $infoText .= 'local trend - up, (' . round($actualMacd5m['main_values']['macd_line'], 1) . ') 5m' . "\n\n";
        else if ($actualMacd5m && $actualMacd5m['main_values']['macd_line'] < -$trendBoard)
            $infoText .= 'local trend - down, (' . round($actualMacd5m['main_values']['macd_line'], 1) . ') 5m' . "\n\n";
        else
            $infoText .= 'local trend - neutral, (' . round($actualMacd5m['main_values']['macd_line'], 1) . ') 5m' . "\n\n";


        //divergence 5m text and val
        $shortDivergenceVal5m = $shortDivergenceVal15m = $longDivergenceVal5m = $longDivergenceVal15m = false;
        if ($res['longDivergenceVal5m']) {
            $longDivergenceVal5m = true;
            $infoText .= $res['longDivergenceText5m'] . "\n";
        }

        if ($res['shortDivergenceVal5m']) {
            $shortDivergenceVal5m = true;
            $infoText .= $res['shortDivergenceText5m'] . "\n";
        }

        //divergence 15m text and val
        if ($res['longDivergenceVal15m']) {
            $longDivergenceVal15m = true;
            $infoText .= $res['longDivergenceText15m'] . "\n";
        }

        if ($res['shortDivergenceVal15m']) {
            $shortDivergenceVal15m = true;
            $infoText .= $res['shortDivergenceText15m'] . "\n";
        }

        //main rules
        $impulseMacdTrendBoardVal = 3;

        $impulsMacdVal = 30;
        $strongImpulsMacdVal = 175;
        $impulsRSIVal = 8;
        if (
            ($actualImpulsMacd15m['impulse_macd'] > $impulseMacdTrendBoardVal || $actualImpulsMacd15m['impulse_macd'] < -$impulseMacdTrendBoardVal)
            && ($actualImpulsMacd5m['impulse_macd'] > $impulseMacdTrendBoardVal || $actualImpulsMacd5m['impulse_macd'] < -$impulseMacdTrendBoardVal)
        ) {
            if (
                (
                    $actualMacd5m['main_values']['macd_line'] > $trendBoard
                    || $actualImpulsMacd5m['impulse_macd'] > $impulseMacdTrendBoardVal
                    || $actualImpulsMacd5m['histogram'] > $strongImpulsMacdVal
                )
                && $actualImpulsMacd5m['trend']['longDirection']
                && (
                    ($actualMacd5m['main_values']['histogram_value'] > ($impulsMacdVal / 3))
                    || ($actualStochastic15m['hist'] > $impulsRSIVal)
                    || ($actualImpulsMacd5m['histogram'] > ($impulsMacdVal / 2))
                )
                && (
                    $actualAdx5m['adxDirection']['isUpDir'] && $actualAdx5m['trendDirection']['isUpTrend']
                    || $actualAdx5m['adxDirection']['isDownDir'] && $actualAdx5m['trendDirection']['isDownTrend']
                )

            ) {
                $res['isLong'] = true;
                $res['atrMultipliers'] = [2.3, 2.9, 3.3];
                $res['risk'] = 3.5;
            } else if (
                (
                    $actualMacd5m['main_values']['macd_line'] < -$trendBoard
                    || $actualImpulsMacd5m['impulse_macd'] < -$impulseMacdTrendBoardVal
                    || $actualImpulsMacd5m['histogram'] < -$strongImpulsMacdVal
                )
                && $actualImpulsMacd5m['trend']['shortDirection']
                && (
                    ($actualMacd5m['main_values']['histogram_value'] < -($impulsMacdVal / 3))
                    || ($actualStochastic15m['hist'] < -$impulsRSIVal)
                    || ($actualImpulsMacd5m['histogram'] < -($impulsMacdVal / 2))
                )
                && (
                    $actualAdx5m['adxDirection']['isUpDir'] && $actualAdx5m['trendDirection']['isDownTrend']
                    || $actualAdx5m['adxDirection']['isDownDir'] && $actualAdx5m['trendDirection']['isUpTrend']
                )
            ) {
                $res['isShort'] = true;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $res['risk'] = 3.5;
            }
        }

        // risk/profit rules long
        if ($res['isLong']) {
            if ($actualImpulsMacd5m['impulse_macd'] > 250) {
                $res['risk'] = 2.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C1.1 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] > 0 && $actualImpulsMacd5m['impulse_macd'] < 75) {
                $res['risk'] = 2.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C2.1 \n";
            }

            if (
                $actualAdx15m['adx'] < 22
                || ($actualAdx15m['adx'] < 27 && $actualAdx15m['adxDirection']['isDownDir'])
            ) {
                $res['risk'] = 2.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C3.1\n";
            }

            if ($shortDivergenceVal5m || $shortDivergenceVal15m) {//
                $res['risk'] = 1.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C4.1 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] > -100 && $actualImpulsMacd5m['impulse_macd'] < 0) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C5.1 \n";
            }

            if ($actualImpulsMacd15m['impulse_macd'] > -100 && $actualImpulsMacd15m['impulse_macd'] < 0) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C6.1 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] > 400 && $actualImpulsMacd5m['histogram'] < $strongImpulsMacdVal) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C7.1 \n";
            }

            if ($actualImpulsMacd5m['histogram'] > 0 && $actualImpulsMacd5m['histogram'] <= ($impulsMacdVal / 2)) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C8.1\n";
            }
        }

        // risk/profit rules short
        if ($res['isShort']) {
            if ($actualImpulsMacd5m['impulse_macd'] < -250) {
                $res['risk'] = 2.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C1.2 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] < 0 && $actualImpulsMacd5m['impulse_macd'] > -75) {
                $res['risk'] = 2.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C2.2 \n";
            }

            if (
                $actualAdx15m['adx'] < 22
                || ($actualAdx15m['adx'] < 27 && $actualAdx15m['adxDirection']['isDownDir'])
            ) {
                $res['risk'] = 2.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C3.2\n";
            }

            if ($longDivergenceVal5m || $longDivergenceVal15m) {//
                $res['risk'] = 1.95;
                $res['atrMultipliers'] = [1.9, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C4.2 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] < 100 && $actualImpulsMacd5m['impulse_macd'] > 0) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C5.2 \n";
            }

            if ($actualImpulsMacd15m['impulse_macd'] < 100 && $actualImpulsMacd15m['impulse_macd'] > 0) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C6.2 \n";
            }

            if ($actualImpulsMacd5m['impulse_macd'] < -400 && $actualImpulsMacd5m['histogram'] > -$strongImpulsMacdVal) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C7.2 \n";
            }

            if ($actualImpulsMacd5m['histogram'] < 0 && $actualImpulsMacd5m['histogram'] >= -($impulsMacdVal / 2)) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C8.2\n";
            }
        }

        // risk/profit rules all
        if ($res['isLong'] || $res['isShort']) {
            if ($actualImpulsMacd15m['histogram'] <= ($impulsMacdVal / 3) && $actualImpulsMacd15m['histogram'] >= -($impulsMacdVal / 3)) {
                $res['risk'] = 1.45;
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
                $infoText .= 'risk ' . $res['risk'] . " C9\n";
            }

            if (
                $actualAdx15m['adx'] < 22
                || ($actualAdx15m['adx'] < 27 && $actualAdx15m['adxDirection']['isDownDir'])
            ) {
                $res['atrMultipliers'] = [1.4, 2.6, 3.4];
            }
        }

        if ($actualImpulsMacd15m['impulse_macd'] > 0) {
            $res['shortTpCount'] = 1;
            $res['longTpCount'] = 2;
        } else if ($actualImpulsMacd15m['impulse_macd'] < 0) {
            $res['shortTpCount'] = 2;
            $res['longTpCount'] = 1;
        }

        $infoText .= ($res['isLong'] ? 'Y' : 'N') . ' | ' . ($res['isShort'] ? 'Y' : 'N');
        $infoText .= "\n\n";

        /*$res['isLong'] = false;
        $res['isShort'] = false;*/
        $res['infoText'] = $infoText;
        $bybitApiOb->closeConnection();
        return $res;
    }

    /**
     * Функция для обработки одной “сигнальной” записи (будь то pump, dump или screenerData).
     *
     * @param string $direction Направление сигнала: 'long' или 'short'.
     * @param float $atr Значение ATR (например, floatval($item['actualATR']['atr'])).
     * @param float $closePrice Текущая цена закрытия (floatval(...)).
     * @param array $candles15m Массив свечей 15m (например, $item['candles15m'] или $candles15m из screener).
     * @param array $supertrend5m Данные Supertrend на 5m (массив с ключами 'isUptrend', 'value').
     * @param array $supertrend15m Данные Supertrend на 15m (массив с ключами 'isUptrend', 'value').
     * @param array $macdDivergence Данные MACD-дивергенции (массив с ключами
     *                                   ['extremes']['selected']['low']['priceLow2']['value']
     *                                   и (для short) ['extremes']['selected']['high']['priceHigh2']['value'],
     *                                   а также ['longDivergenceTypeAr'], ['shortDivergenceTypeAr'] для стратегий).
     * @param int $symbolScale Количество десятичных знаков (scale) у инструмента.
     * @param array $atrMultipliers Массив множителей ATR (например, [1.9, 2.6, 3.4] или [$item['atrMultipliers']]).
     * @param array $marketInfo Ассоциативный массив с текущими настройками риска, напр. ['risk' => 4, 'isLong'=>true, 'isShort'=>false].
     * @param string $symbolName Имя символа (например, BTCUSDT).
     * @param string $logContext Любая строка для логирования (например, "$marketCode/bybitExchange$timeFrame" или "$marketMode/screener$interval").
     *
     * @return array|false
     *   Если сигнал “проходит” все проверки риска, возвращает массив вида:
     *     [
     *       'determineEntryPoint'        => [...],        // результат TechnicalAnalysis::determineEntryPoint
     *       'recommendedEntry'           => float|false,  // рекомендуемая точка входа либо false
     *       'calculateRiskTargetsWithATR'=> [...],        // результат TechnicalAnalysis::calculateRiskTargetsWithATR
     *       'SL'                         => float,        // рассчитанный стоп-лосс
     *       'TP'                         => array,        // рассчитанные тейк-профиты (array of floats)
     *       'riskBoard'                  => int,          // значение $marketInfo['risk'] (или 4 по умолчанию)
     *       'actualMlModel'              => array,        // результат ML-прогноза (массив)
     *     ]
     *
     *   Если риск (riskPercent) выше threshold ($marketInfo['risk']), функция сразу вернёт false.
     */
    public static function processSignal(
        string $direction,
        float  $atr,
        float  $closePrice,
               $candles15m,
               $supertrend5m,
               $supertrend15m,
               $macdDivergence,
        int    $symbolScale,
        array  $atrMultipliers,
        array  $marketInfo,
        string $symbolName,
        string $logContext,
        bool   $mlFlag = true,
        bool   $mlFilter = true,
        bool   $mlDevMode = false,
    )
    {
        // Инициализируем “пустой” результат
        $result = [
            'determineEntryPoint' => null,
            'recommendedEntry' => false,
            'calculateRiskTargetsWithATR' => null,
            'SL' => false,
            'TP' => false,
            'riskBoard' => null,
            'actualMlModel' => [],
        ];

        if (!is_array($candles15m)) {
            throw new \InvalidArgumentException('candles15m must be an array');
        }

        if (!is_array($supertrend5m)) {
            throw new \InvalidArgumentException('supertrend5m must be an array');
        }

        if (!is_array($supertrend15m)) {
            throw new \InvalidArgumentException('supertrend5m must be an array');
        }

        if (!is_array($macdDivergence)) {
            throw new \InvalidArgumentException('macdDivergence must be an array');
        }

        //
        // 1) Определяем точку входа через TechnicalAnalysis::determineEntryPoint
        //
        try {
            $determineEntryPoint = \Maksv\TechnicalAnalysis::determineEntryPoint($atr, $candles15m, $direction);
            $result['determineEntryPoint'] = $determineEntryPoint;

            if (!$determineEntryPoint['isEntryPointGood']) {
                // если точка входа “не годится”, сохраняем рекомендуемую (округлённую) или false
                $result['recommendedEntry'] = round($determineEntryPoint['recommendedEntry'], $symbolScale);
            }
        } catch (\Exception $e) {
            devlogs(
                "ERR $symbolName | err - determineEntryPoint: {$e->getMessage()} | timeMark - " .
                date("d.m.y H:i:s"),
                $logContext
            );
            // Если determineEntryPoint кинуло исключение, можно либо считать, что точка входа не найдена, либо возвращать false:
            // здесь просто оставляем recommendedEntry == false и продолжаем дальше.
        }

        //
        // 2) Считаем “родительский” стоп-лосс (slParent) и смещение slOffset
        //
        //    Для long: по приоритету берём Supertrend5m→Supertrend15m→экстремум дивергенции→ATR*2
        //    Для short: аналогично, но проверяем isUptrend == false и value
        //
        $offsetPercent = 1.8; //%
        $offsetPercentSecond = 1.8; //%

        $defAtrMtplr = 5; //%
        $slOffset = 0.05; //%
        $closePriceRuleBord = 10; //$

        if ($direction === 'long') {
            $slParent = ($closePrice - ($atr * $defAtrMtplr));

            if ($closePrice < $closePriceRuleBord) {
                // default: берем экстремум по дивергенции “low”
                $slParent = isset($macdDivergence['extremes']['selected']['low']['priceLow2']['value'])
                    ? floatval($macdDivergence['extremes']['selected']['low']['priceLow2']['value'])
                    : ($closePrice - ($atr * $defAtrMtplr));
                $slOffset = $offsetPercentSecond;

                if (!empty($supertrend5m['isUptrend']) && $supertrend5m['value']) {
                    $slParent = floatval($supertrend5m['value']);
                    $slOffset = $offsetPercent;
                } elseif (!empty($supertrend15m['isUptrend']) && $supertrend15m['value']) {
                    $slParent = floatval($supertrend15m['value']);
                }
            }

        } else { // short
            $slParent = ($closePrice + ($atr * $defAtrMtplr));

            if ($closePrice < $closePriceRuleBord) {
                // default: берем экстремум по дивергенции “high”
                $slParent = isset($macdDivergence['extremes']['selected']['high']['priceHigh2']['value'])
                    ? floatval($macdDivergence['extremes']['selected']['high']['priceHigh2']['value'])
                    : ($closePrice + ($atr * $defAtrMtplr));
                $slOffset = $offsetPercentSecond;

                if (isset($supertrend5m['isUptrend']) && !$supertrend5m['isUptrend'] && $supertrend5m['value']) {
                    $slParent = floatval($supertrend5m['value']);
                    $slOffset = $offsetPercent;
                } elseif (isset($supertrend15m['isUptrend']) && !$supertrend15m['isUptrend'] && $supertrend15m['value']) {
                    $slParent = floatval($supertrend15m['value']);
                }
            }
        }

        // 2.1) Если recommendedEntry задана, но slParent “мешает” (закладывается за цену входа), обнуляем recommendedEntry
        if ($result['recommendedEntry'] !== false) {
            if (
                ($direction === 'long' && $slParent >= $result['recommendedEntry'])
                || ($direction === 'short' && $slParent <= $result['recommendedEntry'])
            ) {
                $result['recommendedEntry'] = false;
            }
        }

        //
        // 3) Считаем стоп-лосс и тейк-профиты через TechnicalAnalysis::calculateRiskTargetsWithATR
        //
        try {
            $calculateRiskTargets = \Maksv\TechnicalAnalysis::calculateRiskTargetsWithATR(
                $atr,
                $closePrice,
                $slParent,
                $direction,
                $symbolScale,
                $slOffset,
                $atrMultipliers
            );

            // если риск (riskPercent) >= допустимого ($marketInfo['risk'] или по умолчанию) → сразу false
            $riskBoard = floatval($marketInfo['risk'] ?? 3.5);
            if (floatval($calculateRiskTargets['riskPercent']) >= $riskBoard) {
                devlogs(
                    "ERR RISK $symbolName | RISK {$calculateRiskTargets['riskPercent']} >= $riskBoard | timeMark - " .
                    date("d.m.y H:i:s"),
                    $logContext
                );
                return false;
            }

            $result['calculateRiskTargetsWithATR'] = $calculateRiskTargets;
            $result['SL'] = floatval($calculateRiskTargets['stopLoss']);
            $result['TP'] = $calculateRiskTargets['takeProfits'];
            $result['riskBoard'] = $riskBoard;

        } catch (\Exception $e) {
            devlogs(
                "ERR 2 RISK $symbolName | err - calculateRiskTargetsWithATR: {$e->getMessage()} | timeMark - " .
                date("d.m.y H:i:s"),
                $logContext
            );
            return false;
        }

        //
        // 4) Прогноз ML (если нужно)
        //
        if ($mlFlag) {
            try {
                if (!empty($candles15m) && is_array($candles15m)) {
                    $mlCandles = $candles15m;
                    if (count($candles15m) > 30) {
                        // берём последние 30 свечей
                        $mlCandles = array_slice($candles15m, -30);
                    }

                    $entryPrice = $closePrice;
                    $tpsRaw = (array)$result['TP'];
                    $tps = array_map(fn($x) => floatval($x), $tpsRaw);
                    $slPrice = floatval($result['SL']);

                    $mlPayload = [
                        'candles' => $mlCandles,
                        'entry' => $entryPrice,
                        'tps' => $tps,
                        'sl' => $slPrice,
                        'direction' => $direction,
                    ];

                    $ml = new \Maksv\MachineLearning\Request('http://127.0.0.1:8000');
                    $result['actualMlModel'] = $ml->predict($mlPayload);

                    if ($mlDevMode) {
                        $mlDev = new \Maksv\MachineLearning\Request('http://127.0.0.1:8001', 'mlDev');
                        $result['actualMlModel'] = $mlDev->predict($mlPayload);
                    }

                    if ($mlFilter) {
                        $minMlBoard = 0.61;
                        if ($result['actualMlModel']['probabilities'][1] < $minMlBoard) {
                            devlogs(
                                "ERR $symbolName | ML {$result['actualMlModel']['probabilities'][1]} >= $minMlBoard | timeMark - " .
                                date("d.m.y H:i:s"),
                                $logContext
                            );
                            return false;
                        }
                    }

                } else {
                    $errText = "ERR $symbolName | err - ML candles absent or not array | timeMark - " . date("d.m.y H:i:s");
                    \Maksv\DataOperation::sendErrorInfoMessage($errText, 'processSignal', $logContext);
                    devlogs($errText, $logContext);
                }
            } catch (\Exception $e) {
                $errText = "ERR $symbolName | err - {$errText}: {$e->getMessage()} | timeMark - " . date("d.m.y H:i:s");
                \Maksv\DataOperation::sendErrorInfoMessage($errText, 'processSignal', $logContext);
                devlogs($errText, $logContext);

                // Пытаемся автоматически рестартануть ML‑сервис
                $resRestartMlService = \Maksv\MachineLearning\Assistant::restartMlService();

                // Формируем сообщение по шагам
                $lines = [];
                foreach ($resRestartMlService['report'] as $step) {
                    $lines[] = "> CMD: {$step['cmd']}";
                    $lines[] = "  ↳ Code: {$step['return']}";
                    foreach ($step['output'] as $ln) {
                        $lines[] = "    • $ln";
                    }
                    if ($step['return'] !== 0) {
                        break;
                    }
                }
                $status = $resRestartMlService['success']
                    ? "✅ ML service перезапущен"
                    : "❌ Не удалось перезапустить ML service";

                $message = $status . "\n\n" . implode("\n", $lines);
                \Maksv\DataOperation::sendErrorInfoMessage($message, 'processSignal', $logContext);

            }
        } else {
            //devlogs('WARN | skip ML', $logContext);
        }

        return $result;
    }

    /**
     * Упрощённая версия processSignal — считает только SL, TP и ML-прогноз по MFI-сигналу.
     *
     * @param string $direction 'long' или 'short'
     * @param float $atr значение ATR
     * @param float $closePrice текущая цена закрытия
     * @param array $candles15m массив 15m-свечей для ML (каждая с 't','h','l','c','v')
     * @param int $symbolScale число знаков после запятой в цене инструмента
     * @param array $tpAtrMultipliers массив множителей для тейк-профитов, например [1.9,2.9,...]
     * @param float $slAtrMultiplier множитель для стоп-лосса, например 3.5
     * @return array|false               либо массив с ключами ['SL','TP','MLModel'], либо false при ошибке
     */
    public static function processSignalMfi(
        string $direction,
        float  $atr,
        float  $closePrice,
        array  $candles15m,
        int    $symbolScale,
        array  $tpAtrMultipliers,
        float  $slAtrMultiplier
    )
    {
        // 1) Проверки
        if (!in_array($direction, ['long', 'short'], true)) {
            return false;
            //throw new \InvalidArgumentException("direction must be 'long' or 'short'");
        }
        if ($atr <= 0 || $closePrice <= 0) {
            return false;
        }
        if (empty($candles15m) || !is_array($candles15m)) {
            return false;
        }
        if (empty($tpAtrMultipliers)) {
            return false;
        }

        // 2) Вычисляем стоп-лосс
        if ($direction === 'long') {
            $sl = round($closePrice - $slAtrMultiplier * $atr, $symbolScale);
        } else {
            $sl = round($closePrice + $slAtrMultiplier * $atr, $symbolScale);
        }

        // 3) Вычисляем массив тейк-профитов
        $tp = [];
        foreach ($tpAtrMultipliers as $mult) {
            if (!is_numeric($mult)) {
                continue;
            }
            if ($direction === 'long') {
                $tp[] = round($closePrice + floatval($mult) * $atr, $symbolScale);
            } else {
                $tp[] = round($closePrice - floatval($mult) * $atr, $symbolScale);
            }
        }

        // 4) ML-прогноз (базовый, без dev-режима)
        $mlModel = [];
        try {
            // берём последние 30 свечей или меньше
            $slice = count($candles15m) > 30 ? array_slice($candles15m, -30) : $candles15m;
            $payload = [
                'candles' => $slice,
                'entry' => $closePrice,
                'tps' => $tp,
                'sl' => $sl,
                'direction' => $direction,
            ];
            $ml = new \Maksv\MachineLearning\Request('http://127.0.0.1:8000');
            $mlModel = $ml->predict($payload);
        } catch (\Exception $e) {
            // при ошибке ML просто оставляем пустой массив
            $mlModel = [];
        }

        return [
            'SL' => $sl,
            'TP' => $tp,
            'actualMlModel' => $mlModel,
        ];
    }

    /**
     * Проверяет наличие дивергенции MACD на нескольких наборах параметров.
     *
     * Последовательно проверяет различные конфигурации MACD до обнаружения первой дивергенции.
     * Если ни в одной конфигурации дивергенция не найдена, возвращает результат основной конфигурации.
     *
     * @param array $candles Массив свечных данных
     * @param string $tf Таймфрейм для определения допуска по индексу цены
     * @param array $priceIndexToleranceMap Карта допусков индекса цены по таймфреймам
     *
     * @return array|false
     *   Массив с данными дивергенции последней проверенной конфигурации или false при ошибке.
     *   Возвращаемый массив содержит:
     *   - Данные дивергенции MACD
     *   - 'inputParams' с типом использованной конфигурации
     *   - Информацию о типах дивергенции (regular/hidden для long/short)
     *
     *   Приоритет конфигураций:
     *   1. 5.35.5.SMA (основная)
     *   2. 12.26.9.EMA
     *   3. 3.10.16.SMA
     */
    public static function checkMultiMACD(
        $candles = [],
        $tf = '15m',
        $priceIndexToleranceMap = ['5m' => 11, '15m' => 11, '30m' => 11, '1h' => 8, '4h' => 8, '1d' => 6],
    )
    {
        if (!is_array($candles) || count($candles) < 30)
            return false;

        $mainType = '5.35.5.SMA';
        $macdParamsMap = [
            $mainType => ['fastPeriod' => 5, 'fastMAType' => 'SMA', 'slowPeriod' => 35, 'slowMAType' => 'SMA', 'signalPeriod' => 5, 'signalMAType' => 'SMA', 'extremesType' => 'macdLine'],
            '12.26.9.EMA' => ['fastPeriod' => 12, 'fastMAType' => 'EMA', 'slowPeriod' => 26, 'slowMAType' => 'EMA', 'signalPeriod' => 9, 'signalMAType' => 'EMA', 'extremesType' => 'histogram'],
            '3.10.16.SMA' => ['fastPeriod' => 3, 'fastMAType' => 'SMA', 'slowPeriod' => 10, 'slowMAType' => 'SMA', 'signalPeriod' => 16, 'signalMAType' => 'SMA', 'extremesType' => 'macdLine'],
        ];

        $actualMacdDivergence = false;
        $actualMacdDivergenceAr = false;
        $notFoundFlag = true;
        foreach ($macdParamsMap as $type => $param) {
            $macdDivergenceData = \Maksv\TechnicalAnalysis::calculateMacdExt($candles, $param['fastPeriod'], $param['fastMAType'], $param['slowPeriod'], $param['slowMAType'], $param['signalPeriod'], $param['signalMAType'], $priceIndexToleranceMap[$tf], $param['extremesType']) ?? false;

            if ($macdDivergenceData && is_array($macdDivergenceData))
                $actualMacdDivergenceAr[$type] = $actualMacdDivergence = $macdDivergenceData[array_key_last($macdDivergenceData)];

            $actualMacdDivergenceAr[$type]['inputParams'] = $actualMacdDivergence['inputParams'] = $type;
            if (
                $actualMacdDivergence
                && (
                    $actualMacdDivergence['longDivergenceTypeAr']['regular']
                    || $actualMacdDivergence['longDivergenceTypeAr']['hidden']
                    || $actualMacdDivergence['shortDivergenceTypeAr']['regular']
                    || $actualMacdDivergence['shortDivergenceTypeAr']['hidden']
                )
            ) {
                $notFoundFlag = false;
                break;
            }
        }

        if ($notFoundFlag)
            $actualMacdDivergence = $actualMacdDivergenceAr[$mainType];

        return $actualMacdDivergence;
    }

    /**
     * Агрегирует 5-минутные интервалы объемов торгов в 15-минутные интервалы.
     *
     * Группирует 5-минутные интервалы в 15-минутные блоки, суммируя объемы покупок, продаж и общий объем.
     * Для каждого 15-минутного интервала устанавливает фиксированные временные границы и вычисляет дополнительные показатели.
     *
     * @param array $fiveMinIntervals Массив 5-минутных интервалов с ключами:
     *                               - 'startTime' (int) Время начала в секундах
     *                               - 'buyVolume' (float) Объем покупок
     *                               - 'sellVolume' (float) Объем продаж
     *                               - 'sumVolume' (float) Суммарный объем
     *
     * @return array
     *   Массив 15-минутных интервалов с ключами:
     *   - 'buyVolume' (float) Суммарный объем покупок
     *   - 'sellVolume' (float) Суммарный объем продаж
     *   - 'sumVolume' (float) Суммарный объем торгов
     *   - 'startTime_gmt' (string) Время начала в GMT формате
     *   - 'startTime' (int) Время начала в секундах
     *   - 'endTime' (int) Время окончания в секундах (startTime + 900)
     *   - 'endTime_gmt' (string) Время окончания в GMT формате
     *   - 'last_edit' (string) Время последнего редактирования
     *   - Результаты расчета дельты (добавляются через calculateDelta)
     *
     *   Массив отсортирован по времени в порядке убывания (от новых к старым).
     */
    public static function aggregateSumVolume5mTo15m(array $fiveMinIntervals): array
    {
        $result = [];

        foreach ($fiveMinIntervals as $interval) {
            $start = $interval['startTime'];  // предполагается, что время в секундах
            $minute = (int)date('i', $start);
            // Определяем начало 15-минутного блока: округляем вниз до ближайшего кратного 15
            $groupMinute = floor($minute / 15) * 15;
            // Определяем начало группы (текущая дата, час и округленная минута)
            $groupStart = strtotime(date(sprintf('Y-m-d H:%02d:00', $groupMinute), $start));
            // Ключ группы – строка, например: "2023-03-15 14:00"
            $groupKey = date('Y-m-d H', $groupStart) . ':' . sprintf('%02d', $groupMinute);

            // Если группы еще нет, создаем ее с фиксированными границами 15 минут
            if (!isset($result[$groupKey])) {
                $result[$groupKey] = [
                    'buyVolume' => 0,
                    'sellVolume' => 0,
                    'sumVolume' => 0,
                    'startTime_gmt' => \Maksv\Bybit\Bybit::gmtTimeByTimestamp($groupStart * 1000),
                    'startTime' => $groupStart,
                    'endTime' => $groupStart + 900,  // фиксированно 15 минут (900 секунд)
                    'endTime_gmt' => \Maksv\Bybit\Bybit::gmtTimeByTimestamp(($groupStart + 900) * 1000),
                    'last_edit' => date("d.m.y H:i:s")
                ];
            }

            // Добавляем объемы из 5-минутного интервала в группу
            $result[$groupKey]['buyVolume'] += $interval['buyVolume'];
            $result[$groupKey]['sellVolume'] += $interval['sellVolume'];
            $result[$groupKey]['sumVolume'] += $interval['sumVolume'];
            // Обновляем время редактирования
            $result[$groupKey]['last_edit'] = date("d.m.y H:i:s");
        }

        // Приводим результат к индексированному массиву и сортируем по времени (от свежих к старым)
        $aggregated = array_values($result);
        usort($aggregated, function ($a, $b) {
            return $b['startTime'] - $a['startTime'];
        });

        $aggregated = \Maksv\TechnicalAnalysis::calculateDelta($aggregated);

        return $aggregated;
    }


    /**
     * Вспомогательный метод: возвращает набор порогов в зависимости от цены.
     *
     * @param float $closePrice
     * @return array{maDistance:float, pump:float, dump:float}
     */
    private static function getThresholdsByPrice($closePrice)
    {
        // дефолт
        $maDistance = 2.5;
        $pump = 2.5;
        $dump = -2.5;

        if ($closePrice > 2000) {
            $maDistance = 0.4;
            $pump = 0.4;
            $dump = -0.4;
        } else if ($closePrice > 800) {
            $maDistance = 0.6;
            $pump = 0.6;
            $dump = -0.6;
        } else if ($closePrice > 300) {
            $maDistance = 1.6;
            $pump = 1.6;
            $dump = -1.6;
        } else if ($closePrice > 40) {
            $maDistance = 2.0;
            $pump = 2.0;
            $dump = -2.0;
        } else if ($closePrice > 10) {
            $maDistance = 2.3;
            $pump = 2.3;
            $dump = -2.3;
        }

        return [
            'maDistance' => (float)$maDistance,
            'pump'       => (float)$pump,
            'dump'       => (float)$dump,
        ];
    }

    /**
     * Определяет пороговое расстояние до скользящей средней (MA).
     */
    public static function getMaDistance($closePrice) {
        $thr = self::getThresholdsByPrice($closePrice);
        return $thr['maDistance'];
    }

    /**
     * Определяет пороговые значения для pump/dump.
     */
    public static function getPumpDumpThresholds($closePrice) {
        $thr = self::getThresholdsByPrice($closePrice);
        return [
            'pumpThreshold' => $thr['pump'],
            'dumpThreshold' => $thr['dump'],
        ];
    }

    /**
     * Хелпер для проверки условий по скользящим средним (MA) с учётом направления сигнала.
     *
     * @param array $ma Ассоциативный массив MA с ключами:
     *                                - 'isUptrend' => bool
     *                                - 'sma'       => float|string
     * @param float $actualClosePrice Текущая цена закрытия
     * @param int $maDistance Порог в процентах (например, 3)
     * @param string $direction Направление сигнала: 'long' или 'short'
     *
     * @return bool
     *   Для 'long':
     *     - если 'sma' не задана или равна нулю → true (пропускаем проверку);
     *     - если isUptrend == true → true;
     *     - иначе рассчитываем diffPercent = ((actualClosePrice - sma) / sma) * 100
     *       и возвращаем true, если diffPercent <= -$maDistance, иначе false.
     *
     *   Для 'short':
     *     - если 'sma' не задана или равна нулю → true;
     *     - если isUptrend == false → true;
     *     - иначе рассчитываем diffPercent = ((actualClosePrice - sma) / sma) * 100
     *       и возвращаем true, если diffPercent >= $maDistance, иначе false.
     */
    public static function checkMaCondition($ma, float $actualClosePrice, float $maDistance, string $direction): bool
    {
        // 1) Если 'sma' не указан или равен нулю → считаем условие выполненным (true)
        if (empty($ma['sma']) || floatval($ma['sma']) === 0.0) {
            return true;
        }

        $sma = floatval($ma['sma']);
        $diffPercent = (($actualClosePrice - $sma) / $sma) * 100.0;

        if ($direction === 'long') {
            // Для long: если уже в восходящем тренде → true
            if ($ma['isUptrend']) {
                return true;
            }
            // Иначе: проверяем, что цена упала относительно SMA на maDistance процентов либо больше
            return ($diffPercent <= -$maDistance);

        } elseif ($direction === 'short') {
            // Для short: если уже не в восходящем тренде → true
            if (!$ma['isUptrend']) {
                return true;
            }
            // Иначе: проверяем, что цена поднялась относительно SMA на maDistance процентов либо больше
            return ($diffPercent >= $maDistance);

        } else {
            // Неподдерживаемое направление — пропускаем проверку
            return true;
        }
    }
}
