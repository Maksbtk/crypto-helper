<?php
namespace Maksv;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class DataOperation
{
    public function __construct(){}

    public static function sendInfoMessage($actualOpportunities = [], $timeFrame = '30m')
    {
        $tgBot = new \Maksv\TelegramBot();
        $message = '';
        $message .= "ℹ info " . $timeFrame . " ⏰" . DataOperation::actualDateFormatted() . "\n\n";

        if ($actualOpportunities['allPump']) {
            $cnt = 1;
            $message .= "🟩\n";
            foreach (array_slice($actualOpportunities['allPump'], 0, 20) as $key => $symbol) {
                $cross = 'no cross. ';
                if($symbol['crossMAVal'] == 1)
                    $cross = 'cross - 💚. ';
                else if($symbol['crossMAVal'] == 2)
                    $cross = 'cross - ❤. ';

                $sTrend = ' sTrend - ' .$symbol['lastSupertrend']['trend'] . '. ';
                if ($symbol['supertrendVal'] == 1)
                    $sTrend = ' sTrand-🟢. ';
                else if ($symbol['supertrendVal'] == 2)
                    $sTrend = ' sTrand-🔴. ';

                /*$approve = '';
                if ($symbol['filter']) {
                    $approve = ' Approve: ';
                    $cntApprove = 0;
                    foreach ($symbol['filter'] as $tf => $flag) {
                        if ($flag) {
                            $approve .= ' ' . $tf . ',';
                            $cntApprove++;
                        }
                    };
                    if ($cntApprove == 0) {
                        $approve = '';
                    } else {
                        $approve = substr($approve, 0, -1);
                        $approve .= '.';
                    }
                }*/

                //$message .= $cnt . '.' . $sTrend . $cross .' ' . $symbol['symbolName']  . '. P '.$symbol['lastPriceChange'].'%.' . ' OI '.$symbol['lastOpenInterest'].'%.' . $approve . "\n";
                $message .= $cnt . '. ' . $symbol['symbolName'] . ' ' . $symbol['strategy'] . ' ' . $sTrend . $cross .' ' . ' OI '.$symbol['lastOpenInterest'].'%.' . "\n";
                $cnt++;
            }
        }
        $message .= "\n";

        if ($actualOpportunities['allDump']) {
            $cnt = 1;
            $message .= "🟥\n";
            foreach (array_slice($actualOpportunities['allDump'], 0, 20) as $key => $symbol) {
                $cross = 'no cross. ';
                if($symbol['crossMAVal'] == 1)
                    $cross = 'cross - 💚. ';
                else if($symbol['crossMAVal'] == 2)
                    $cross = 'cross - ❤. ';

                $sTrend = ' sTrend - ' .$symbol['lastSupertrend']['trend'] . '. ';
                if ($symbol['supertrendVal'] == 1)
                    $sTrend = ' sTrand-🟢. ';
                else if ($symbol['supertrendVal'] == 2)
                    $sTrend = ' sTrand-🔴. ';

               /* $approve = '';
                if ($symbol['filter']) {
                    $approve = ' Approve: ';
                    $cntApprove = 0;
                    foreach ($symbol['filter'] as $tf => $flag) {
                        if ($flag) {
                            $approve .= ' ' . $tf . ',';
                            $cntApprove++;
                        }
                    };
                    if ($cntApprove == 0) {
                        $approve = '';
                    } else {
                        $approve = substr($approve, 0, -1);
                        $approve .= '.';
                    }
                }*/

                //$message .= $cnt . '.' . $sTrend . $cross .' ' . $symbol['symbolName'] . '. P '.$symbol['lastPriceChange'].'%.' . ' OI '.$symbol['lastOpenInterest'].'%.' . $approve . "\n";
                $message .= $cnt . '. ' . $symbol['symbolName'] . ' ' . $symbol['strategy'] . ' ' . $sTrend . $cross .' ' . ' OI '.$symbol['lastOpenInterest'].'%.' . "\n";

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

    public static function sendSignalMessage($pump = [], $dump = [], $chatName = '@cryptoHelperAlerts', $timeFrame = '')
    {
        $tgBot = new \Maksv\TelegramBot();
        $message = '';
        $message .= "ℹ info " . $timeFrame . " ⏰" .  DataOperation::actualDateFormatted() . "\n\n";

        if ($pump) {
            $message .= "🟩⬆ long:\n";

            $cnt = 1;
            foreach (array_slice($pump, 0, 12) as $key => $symbol) {

                /* $cross = 'no cross. ';
                 if($symbol['crossMAVal'] == 1)
                     $cross = 'cross - 💚. ';
                 else if($symbol['crossMAVal'] == 2)
                     $cross = 'cross - ❤. ';

                 $sTrend = ' sTrend - ' .$symbol['lastSupertrend']['trend'] . '. ';
                 if ($symbol['supertrendVal'] == 1)
                     $sTrend = ' sTrand-🟢. ';
                 else if ($symbol['supertrendVal'] == 2)
                     $sTrend = ' sTrand-🔴. ';

                 $approve = '';
                 if ($symbol['filter']) {
                     $approve = ' Approve: ';
                     $cntApprove = 0;
                     foreach ($symbol['filter'] as $tf => $flag) {
                         if ($flag) {
                             $approve .= ' ' . $tf . ',';
                             $cntApprove++;
                         }
                     };
                     if ($cntApprove == 0) {
                         $approve = '';
                     } else {
                         $approve = substr($approve, 0, -1);
                         $approve .= '.';
                     }
                 }*/

                //$message .= $cnt . $sTrend . $cross .' ' . $symbol['symbolName'] . '. P '.$symbol['lastPriceChange'].'%.' . ' OI '.$symbol['lastOpenInterest'].'%.' . $approve . "\n";
                $message .= $cnt . '. ' . $symbol['symbolName'] . ' OI '.$symbol['lastOpenInterest'].'%.' . "\n";

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

                /*$cross = 'no cross. ';
                if($symbol['crossMAVal'] == 1)
                    $cross = 'cross - 💚. ';
                else if($symbol['crossMAVal'] == 2)
                    $cross = 'cross - ❤. ';


                $sTrend = ' sTrend - ' .$symbol['lastSupertrend']['trend'] . '. ';
                if ($symbol['supertrendVal'] == 1)
                    $sTrend = ' sTrand-🟢. ';
                else if ($symbol['supertrendVal'] == 2)
                    $sTrend = ' sTrand-🔴. ';

                $approve = '';
                if ($symbol['filter']) {
                    $approve = ' Approve: ';
                    $cntApprove = 0;
                    foreach ($symbol['filter'] as $tf => $flag) {
                        if ($flag) {
                            $approve .= ' ' . $tf . ',';
                            $cntApprove++;
                        }
                    };
                    if ($cntApprove == 0) {
                        $approve = '';
                    } else {
                        $approve = substr($approve, 0, -1);
                        $approve .= '.';
                    }
                }*/

                //$message .= $cnt . $sTrend . $cross .' ' . $symbol['symbolName'] . '. P '.$symbol['lastPriceChange'].'%.' . ' OI '.$symbol['lastOpenInterest'].'%. ' . $approve . "\n";
                $message .= $cnt . '. ' . $symbol['symbolName'] . ' OI '.$symbol['lastOpenInterest'].'%.' . "\n";

                $cnt++;
            }
        } else {
            $message .= "short список пуст\n";
        }
        $message .= "\n";
        $sendRes = $tgBot->messageToTelegram($message, $chatName);

        return $sendRes;
    }

    public static function sendTrendWarning($symbol, $indicator, $trend, $chatName = '@infoCryptoHelperTrend', $timeFrame = '')
    {
        $tgBot = new \Maksv\TelegramBot();
        $message = "ℹ " . $symbol['symbolName']  . '. Смена тренда ('.$indicator.'). ' . $timeFrame . ' ⏰' .  DataOperation::actualDateFormatted() . "\n\n";

        $indicatorText = '';
        if ($trend)
            $indicatorText = 'trend - UP🟢. ';
        else
            $indicatorText = 'trend - DOWN🔴. ';

        $message .= $indicatorText . "\n";
        $sendRes = $tgBot->messageToTelegram($message, $chatName);

        return $sendRes;
    }

    public static function saveSignalToIblock($timeframe = '30m', $iblockCode = 'bybit', $isMaster = false)
    {
        /*if (!$actualOpportunities)
            $actualSymbolsAr = (json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $iblockCode . 'Exchange/'.$timeframe.'/actualMarketVolumes.json'), true))['STRATEGIES'] ?? [];*/

        $opportunitiesPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $iblockCode . 'V5' . 'Exchange/'.$timeframe.'/actualMarketVolumes.json';
        $opportunitiesFileAr = \CFile::MakeFileArray($opportunitiesPath);
        $opportunitiesFileId = \CFile::SaveFile($opportunitiesFileAr, 'opportunities');
        $res = ['status' => false];

        if ($opportunitiesFileId && \CModule::IncludeModule("iblock")) {

            $iblockMap = [
                'bybit' => 3
            ];

            $nameTimeFrame = $timeframe;
            if ($isMaster)
                $timeframe = 'master';

            $iblockSectionsMap = [
                '30m' => 2,
                '1h' => 3,
                '4h' => 4,
                '1d' => 1,
                'master' => 5
            ];

            $elementProperty = [
                'STRATEGIES_FILE' => $opportunitiesFileId,
            ];

            $el = new \CIBlockElement;
            $arLoadElementArray = [
                //"MODIFIED_BY"    => $modifiedBy,
                "IBLOCK_SECTION_ID" => $iblockSectionsMap[$timeframe],
                "IBLOCK_ID" => $iblockMap[$iblockCode],
                //"NAME" => date('H:i', /*strtotime('+1 minute', */ strtotime('-3 hour'))/*)*/,
                "NAME" => DataOperation::actualDateFormatted() . ' / ' . $nameTimeFrame,
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

    /*public static function actualDateFormatted() {
        // Получаем текущее время
        $date = new \DateTime();

        // Вычитаем 3 часа
        $date->modify('-3 hours');

        // Округляем время до ближайших 30 минут
        $minutes = (int)$date->format('i');
        if ($minutes < 15) {
            $date->setTime($date->format('H'), 0);
        } elseif ($minutes >= 15 && $minutes < 45) {
            $date->setTime($date->format('H'), 30);
        } else {
            $date->setTime($date->format('H') + 1, 0);
        }

        // Форматируем дату и время
        $formattedTime = $date->format('H:i d.m');

        return $formattedTime;
    }*/
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

        // Округляем время до ближайших 30 минут
        $minutes = (int)$date->format('i');
        if ($minutes < 15) {
            $date->setTime($date->format('H'), 0);
        } elseif ($minutes >= 15 && $minutes < 45) {
            $date->setTime($date->format('H'), 30);
        } else {
            $date->setTime($date->format('H') + 1, 0);
        }

        // Форматируем дату и время
        $formattedTime = $date->format('H:i d.m');

        return $formattedTime;
    }
}
