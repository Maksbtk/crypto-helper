<?php
namespace Maksv;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class DataOperation
{
    public function __construct(){}

    public static function sendInfoMessage($actualOpportunities = [], $timeFrame = '30m', $btcInfo = [], $cntInfo = [], $isScreener = false)
    {
        $tgBot = new \Maksv\TelegramBot();
        $message = '';

        if ($isScreener)
            $message .= 'Screener | ';

        $message .= "ℹ info " . $timeFrame . " ⏰" . DataOperation::actualDateFormatted() . "\n\n";

        if ($btcInfo['infoText']) {
            $message .= $btcInfo['infoText'];
        }

        if ($cntInfo['count'] || $cntInfo['analysisCount'] || $cntInfo['analysisSymbols']) {
            $message .= 'cnt info:' . "\n";
            $message .= 'count - ' . $cntInfo['count'] . "\n";
            $message .= 'analysisCount - ' . $cntInfo['analysisCount'] . "\n";
            $message .= 'analysis - ' . $cntInfo['analysisSymbols'] . "\n";
        }

        if ($actualOpportunities['allPump']) {
            $cnt = 1;
            $message .= "🟩\n";
            foreach (array_slice($actualOpportunities['allPump'], 0, 20) as $key => $symbol) {
                $cross = false;
                if($symbol['crossMAVal'] == 1)
                    $cross = '💚';
                else if($symbol['crossMAVal'] == 2)
                    $cross = '❤';

                $macdStrategy = '.';
                foreach ($symbol['actualMacdDivergence']['longDivergenceTypeAr'] as $name => $val) {
                    if ($val) {
                        $macdStrategy = $name;
                    }
                }

                $message .= $cnt . '. ' . $symbol['symbolName'] . ' | ' .  $symbol['strategy'] . ' | MACD ' . $macdStrategy . ' (' . $symbol['actualMacdDivergence']['longDivergenceDistance'] . ') | ' ;

                if ($cross)
                    $message .= $cross . ' | ';

                $message .= ' OI '.$symbol['lastOpenInterest'] . '%. |';

                $message .= ' <a href="https://infocrypto-helper.ru/user/bybitSignals/?analysis='.$symbol['symbolName'].'">🔎</a>' . "\n";

                $cnt++;
            }
        }
        $message .= "\n";

        if ($actualOpportunities['allDump']) {
            $cnt = 1;
            $message .= "🟥\n";
            foreach (array_slice($actualOpportunities['allDump'], 0, 20) as $key => $symbol) {
                $cross = false;
                if($symbol['crossMAVal'] == 1)
                    $cross = '💚';
                else if($symbol['crossMAVal'] == 2)
                    $cross = '❤';

                $macdStrategy = '.';
                foreach ($symbol['actualMacdDivergence']['shortDivergenceTypeAr'] as $name => $val) {
                    if ($val) {
                        $macdStrategy = $name;
                    }
                }

                $message .= $cnt . '. ' . $symbol['symbolName'] . ' | ' . $symbol['strategy']  . ' | MACD ' . $macdStrategy . ' (' . $symbol['actualMacdDivergence']['shortDivergenceDistance'] . ') | ' ;
                if ($cross)
                    $message .= $cross . ' | ';

                $message .= ' OI '.$symbol['lastOpenInterest'] . '%. |';

                $message .= ' <a href="https://infocrypto-helper.ru/user/bybitSignals/?analysis='.$symbol['symbolName'].'">🔎</a>' . "\n";

                $cnt++;
            }
        }

        if (!$actualOpportunities['allDump'] && !$actualOpportunities['allPump']) {
            $message .= "Список пуст\n";
        }

        $message .= "\n";
        $sendRes = $tgBot->messageToTelegram($message, '@cryptoHelperAlerts');

        return $sendRes;
    }

    public static function sendSignalMessage($pump = [], $dump = [], $btcInfo = false, $chatName = '@cryptoHelperAlerts', $timeFrame = '', $infoAr = [])
    {
        $tgBot = new \Maksv\TelegramBot();
        $message = '';
        $message .= "ℹ " . $timeFrame . " ⏰" .  DataOperation::actualDateFormatted() . "\n\n";

        if ($pump) {
            $message .= "🟩⬆ long:\n";

            $cnt = 1;
            foreach (array_slice($pump, 0, 12) as $key => $symbol) {

                $message .= $cnt . '. ' . $symbol['symbolName'] . ' | ';

                if ($symbol['actualClosePrice'])
                    $message .= '✅EP: ' . $symbol['actualClosePrice'] . " | ";

                if ($symbol['actualClosePrice'])
                    $message .= '‼Sl: ' . $symbol['SL'] . ' | ';

                if ($symbol['TP']) {
                    $message .= '🎯TP: ';
                    foreach ($symbol['TP'] as $tp) {
                        $message .= $tp . ' ';
                    }
                    $message .= '| ';
                }

                if ($symbol['strategy'])
                    $message .= $symbol['strategy'] . ' | ';

                $message .= ' <a href="https://infocrypto-helper.ru/user/bybitSignals/?analysis='.$symbol['symbolName'].'">🔎</a>' . "\n";
                
                $cnt++;
            }
        } else {
            $message .= "long список пуст\n";
        }
        $message .= "\n";

        if ($dump) {
            $message .= "🟥⬇ short:\n";

            $cnt = 1;
            foreach (array_slice($dump, 0, 12) as $key => $symbol) {

                $message .= $cnt . '. ' . $symbol['symbolName'] . ' | ';

                if ($symbol['actualClosePrice'])
                    $message .= '✅EP: ' . $symbol['actualClosePrice'] . " | ";

                if ($symbol['actualClosePrice'])
                    $message .= '‼Sl: ' . $symbol['SL'] . ' | ';

                if ($symbol['TP']) {
                    $message .= '🎯TP: ';
                    foreach ($symbol['TP'] as $tp) {
                        $message .= $tp . ' ';
                    }
                    $message .= '| ';
                }

                if ($symbol['strategy'])
                    $message .= $symbol['strategy'] . ' | ';

                $message .= ' <a href="https://infocrypto-helper.ru/user/bybitSignals/?analysis='.$symbol['symbolName'].'">🔎</a>' . "\n";

                $cnt++;
            }
        } else {
            $message .= "short список пуст\n";
        }

        /*if ($infoAr['REPEAT_SYMBOLS'] && is_array($infoAr['REPEAT_SYMBOLS'])) {
            $message .= "\nWARN: ";

            foreach ($infoAr['REPEAT_SYMBOLS'] as $symbol)
                $message .= $symbol . ' ';
        }*/

        $message .= "\n";
        $sendRes = $tgBot->messageToTelegram($message, $chatName);
        return $sendRes;
    }

    public static function sendScreener($res, $chatName = '@cryptoHelperAlerts')
    {
        $tgBot = new \Maksv\TelegramBot();
        $islong = '🔴';
        if ($res['isLong']) {
            $islong = '🟢';
            $directionText = 'long';
        } else {
            $directionText = 'short';
        }

        $quote = "USDT";
        $base = str_replace($quote, "", $res['symbolName']);
        $symbolFormatted = "#" . $base . "/" . $quote;

        $message = $islong . ' ' . $symbolFormatted . ' ' . $directionText . "\n";

        /*if ($res['cnt'])
            $message .= '‼6h cnt ' .  $res['cnt'] .  "\n";*/

        $intervalsMap = [
            '5m' => 'M5',
            '15m' => 'M15',
            '30m' => 'M30',
            '1h' => 'H1',
        ];

        $message .= '⏰ Timeframe: ' . $intervalsMap[$res['interval']] . "\n";

        if ($res['leverage'])
            $message .= '💰 leverage: cross ' . $res['leverage'] . "\n\n";
        else
            $message .= '💰 leverage: cross 5x'. "\n\n";

        $message .= '✅ Entry Target: ' . $res['actualClosePrice'] . ' (Entry as market)' . "\n\n";

        if ($res['TP'] && is_array($res['TP']))  {
            $message .= "🎯 Profit Targets:\n";
            foreach ($res['TP'] as $key => $tpVal) {
                $message .= $key+1 . ') ' . $tpVal . "\n";
            }
            $message .= "\n";
        }
        //$message = '🎯 Profit Targets: ' . $res['$actualClosePrice'] . "\n\n";

        if ($res['SL'])
            $message .= 'Stop Loss: ' . $res['SL'] . "\n";

        $message .= '_______________________' . "\n";

       /* if ($res['recommendedEntry'])
            $message .= 'Recommended entry: ' . $res['recommendedEntry'] .  "\n";*/

        if ($res['summaryOI'])
            $message .= 'OI ' .  $res['summaryOI'] .  " | ";

        if ($res['summaryOI'])
            $message .= 'bybit ' .  $res['summaryOIBybit'] .  " | ";

        if ($res['summaryOI'])
            $message .= 'binance ' .  $res['summaryOIBinance'] .  "\n";

        // Если задан путь к графику, отправляем фото с подписью,
        // иначе отправляем обычное текстовое сообщение
        if ($res['tempChartPath'] && is_array($res['tempChartPath'])) {
            $sendRes = $tgBot->messageToTelegram($message, $chatName, $res['tempChartPath']);
        } else {
            $sendRes = $tgBot->messageToTelegram($message, $chatName);
        }

        return $sendRes;
    }

    public static function sendBtcCharts($res, $chatName = '@cryptoHelperAlerts')
    {
        $tgBot = new \Maksv\TelegramBot();

        $message = "ℹ " . $res['interval'] .  "\n\n";
        // Если задан путь к графику, отправляем фото с подписью,
        // иначе отправляем обычное текстовое сообщение
        if ($res['tempChartPath'] && is_array($res['tempChartPath'])) {
            $sendRes = $tgBot->messageToTelegram($message, $chatName, $res['tempChartPath']);
        } else {
            $sendRes = $tgBot->messageToTelegram($message, $chatName);
        }
        return $sendRes;
    }

    public static function sendTrendWarning($cmcExchangeRes, $btcDVal = false, $btcVal = false, $chatName = '@infoCryptoHelperTrend')
    {
        $tgBot = new \Maksv\TelegramBot();
        $message = "ℹ BTC.D coinmarketcap" . "\n\n";

        if ($btcDVal && $btcVal)
            $message .= 'BTC.D ' .  $btcDVal . '% | ' . 'BTC ' .  $btcVal .  "\n\n";

        foreach ($cmcExchangeRes as $th => $resItem) {
            $message .= $th . ' | ' . 'BTC.D ' .  $resItem['btcD'] . ' | ' . 'BTC ' .  $resItem['btc'] . ' | ' . 'OTHERS ' .  $resItem['others'] . "\n";
        }

        $sendRes = $tgBot->messageToTelegram($message, $chatName);
        return $sendRes;
    }

    public static function sendFearGreedWarning($cmcExchangeRes, $chatName = '@infoCryptoHelperTrend')
    {
        $tgBot = new \Maksv\TelegramBot();
        $message = "ℹ fear and greed index coinmarketcap" . "\n\n";

        $message .=  '👻 ' . $cmcExchangeRes . "\n";

        $sendRes = $tgBot->messageToTelegram($message, $chatName);

        return $sendRes;
    }

    public static function sendBtdDivergenceWarning($text, $chatName = '@infoCryptoHelperTrend')
    {
        $tgBot = new \Maksv\TelegramBot();
        $message = "ℹ BTC MACD Divergence alert" . "\n\n";

        $message .=  $text . "\n";

        $sendRes = $tgBot->messageToTelegram($message, $chatName);

        return $sendRes;
    }

    public static function saveSignalToIblock($timeframe = '30m', $iblockCode = 'bybit', $sectionCode = 'master')
    {
        $opportunitiesPathMap[$iblockCode] = [
            'alerts' => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $iblockCode . 'Exchange/'.$timeframe.'/actualMarketVolumes.json',
            'master' => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $iblockCode . 'Exchange/'.$timeframe.'/actualMarketVolumes.json',
            'screener' => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $iblockCode . 'Exchange/screener/'.$timeframe.'/actualStrategy.json',
            //'screener' => $_SERVER['DOCUMENT_ROOT'] . '/upload/screener/actualStrategy.json',/upload/bybitExchange/screener/15m
        ];

        $opportunitiesFileAr = \CFile::MakeFileArray($opportunitiesPathMap[$iblockCode][$sectionCode]);
        $opportunitiesFileId = \CFile::SaveFile($opportunitiesFileAr, 'opportunities');
        $res = ['status' => false];

        if ($opportunitiesFileId && \CModule::IncludeModule("iblock")) {

            $iblockMap = ['bybit' => 3];

            $iblockSectionsMap['bybit'] = [
                'master' => 5,
                'alerts' => 6,
                'screener' => 7,
            ];

            $elementProperty = [
                'STRATEGIES_FILE' => $opportunitiesFileId,
                'TIMEFRAME' => $timeframe,
            ];

            $el = new \CIBlockElement;
            $arLoadElementArray = [
                //"MODIFIED_BY"    => $modifiedBy,
                "IBLOCK_SECTION_ID" => $iblockSectionsMap[$iblockCode][$sectionCode],
                "IBLOCK_ID" => $iblockMap[$iblockCode],
                "NAME" => DataOperation::actualDateFormatted() . ' / ' . $timeframe,
                "ACTIVE" => 'Y',
                "PROPERTY_VALUES" => $elementProperty,
            ];

            if ($elementId = $el->Add($arLoadElementArray))
                return ['status' => true, 'data' => $elementId];
            else
                return ['status' => false, 'data' => $el->LAST_ERROR];
        }
        return $res;
    }

    public static function actualDateFormatted($inputTime = null)
    {
        // Получаем текущее время
        $date = new \DateTime();

        // Проверяем, передано ли время в формате 'H:i'
        if ($inputTime) {
            // Преобразуем входное время в объект DateTime (дата остаётся текущей)
            $timeParts = explode(':', $inputTime);
            if (count($timeParts) == 2 && is_numeric($timeParts[0]) && is_numeric($timeParts[1])) {
                // Устанавливаем переданное время (с текущей датой)
                $date->setTime((int)$timeParts[0], (int)$timeParts[1]);
            } else {
                throw new \Exception("Неверный формат времени: $inputTime");
            }
        }

        // Вычитаем 3 часа
        $date->modify('-3 hours');

        // Округляем время до ближайших 0, 15, 30 или 45 минут
        $minutes = (int)$date->format('i');
        if ($minutes < 8) {
            $date->setTime((int)$date->format('H'), 0);
        } elseif ($minutes < 23) {
            $date->setTime((int)$date->format('H'), 15);
        } elseif ($minutes < 38) {
            $date->setTime((int)$date->format('H'), 30);
        } elseif ($minutes < 53) {
            $date->setTime((int)$date->format('H'), 45);
        } else {
            $date->setTime((int)$date->format('H') + 1, 0);
        }

        // Форматируем дату и время
        $formattedTime = $date->format('H:i d.m');

        return $formattedTime;
    }
}
