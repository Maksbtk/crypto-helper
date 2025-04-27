<?php
use Bitrix\Main\Loader;
use \Bitrix\Main,
    \Bitrix\Sale\Internals;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Sale\Payment;
use Bitrix\Sale;
use \Bitrix\Main\Context;

\CModule::IncludeModule('highloadblock');
\CModule::IncludeModule('iblock');

#Логирование
function devlogs($data, $type)
{
    $root = Bitrix\Main\Application::getDocumentRoot();

    if (!is_dir("$root/devlogs/$type/"))
        mkdir("$root/devlogs/$type/");

    file_put_contents("$root/devlogs/$type/" . date("Ym") . ".txt", print_r($data, true)."\n", FILE_APPEND);
}

function agentBybitRespDev()
{
    //\Maksv\Bybit\Exchange::bybitExchange('15m', 0.1, true);
    //bybitExch15m();
    //\Maksv\Bybit\Exchange::screener('15m', 1.49, -1.49, true);
    //\Maksv\Bybit\Exchange::bybitSummaryVolumeExchange(true);
    //\Maksv\Bybit\Exchange::bybitExchange('1d', 0.99, -0.99, true);
    //\Maksv\Bybit\Exchange::sendBtcCharts();
    //\Maksv\Bybit\Exchange::btcDOthersExchange();
    \Maksv\Bybit\Exchange::oiBorderExchange('15m', 720, 1, 16, 2,5, -2.5, true);

    return "agentBybitRespDev();";
}

function bybitExch15m()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if (in_array($minute,  [0, 1, 5, 6, 10, 11, 15, 16, 20, 21, 25, 26, 30, 31, 35, 36, 40, 41, 45, 46, 50, 51, 55, 56]))
        \Maksv\Bybit\Exchange::bybitExchange('15m', 0.99, -0.99);

    return "bybitExch15m();";
}

function bybitExch5m()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    //if (in_array($minute,  [0, 1, 5, 6, 10, 11, 15, 16, 20, 21, 25, 26, 30, 31, 35, 36, 40, 41, 45, 46, 50, 51, 55, 56]))
        //\Maksv\Bybit\Exchange::bybitExchange('5m',0.99, -0.99);

    return "bybitExch5m();";
}

function bybitExch30m()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if (in_array($minute,  [0, 1, 5, 6, 10, 11, 15, 16, 20, 21, 25, 26, 30, 31, 35, 36, 40, 41, 45, 46, 50, 51, 55, 56]))
        \Maksv\Bybit\Exchange::bybitExchange('30m', 0.99, -0.99);

    return "bybitExch30m();";
}

function bybitExhc1d()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if (in_array($hour, [3]) && in_array($minute, [3, 4])) {
        //собираем инфу о монетках
        \Maksv\Bybit\Exchange::bybitExchange('1d', 33, -33);
        //собираем инфу об oi
        \Maksv\Bybit\Exchange::oiBorderExchange('15m', 240, 1, 16, 2,5, -2.5, true);
        //\Maksv\Bybit\Exchange::oiBorderExchange('5m', 720, 3, 48, 2.5, -2.5);
    }

    return "bybitExhc1d();";
}

function bybitScreenerExch15m()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if (in_array($minute,  [0, 1, 5, 6, 10, 11, 15, 16, 20, 21, 25, 26, 30, 31, 35, 36, 40, 41, 45, 46, 50, 51, 55, 56]))
        \Maksv\Bybit\Exchange::screener('15m', 1.49, -1.49);

    return "bybitScreenerExch15m();";
}

function bybitScreenerExch5m()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    //if (in_array($minute,  [0, 1, 5, 6, 10, 11, 15, 16, 20, 21, 25, 26, 30, 31, 35, 36, 40, 41, 45, 46, 50, 51, 55, 56]))
      //  \Maksv\Bybit\Exchange::screener('5m', 1.49, -1.49);

    return "bybitScreenerExch5m();";
}

function bybitScreenerExch30m()
{
    $minute = (int)date('i');
    $hour = (int)date('H');

    if (in_array($minute,  [0, 1, 10, 11, 20, 21, 30, 31, 40, 41, 50, 51]))
        \Maksv\Bybit\Exchange::screener('30m', 1.49, -1.49);

    return "bybitScreenerExch30m();";
}

function bybitSummaryVolumeExchange()
{
    $hour = (int)date('H');
    $minute = (int)date('i');
    //if (in_array($minute,  [1, 2, 6, 7, 11, 12, 16, 17, 21, 22, 26, 27, 31, 32, 36, 37, 41, 42, 46, 47, 51, 52, 56, 57]))
    if (in_array($minute,  [2, 3, 7, 8, 12, 13, 17, 18, 22, 23, 27, 28, 32, 33, 37, 38, 42, 43, 47, 48, 52, 53, 57, 58]))
        \Maksv\Bybit\Exchange::bybitSummaryVolumeExchange();

    return "bybitSummaryVolumeExchange();";
}

function btcDOthersExchange()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if (in_array($hour, [3, 7, 11, 15, 19, 23]) && in_array($minute, [0, 1]))
       \Maksv\Bybit\Exchange::btcDOthersExchange();

    return "btcDOthersExchange();";
}

function btcDivergenceExchange()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    if (in_array($minute, [0, 15, 30, 45]))
        \Maksv\Bybit\Exchange::sendBtcCharts();

    if (in_array($minute, [0, 1, 30, 31]))
        \Maksv\Bybit\Exchange::btcDivergenceCheck('15m');

    //if (in_array($minute, [0, 1]))
       // \Maksv\Bybit\Exchange::btcDivergenceCheck('30m');

    if (in_array($minute, [0, 1]))
        \Maksv\Bybit\Exchange::btcDivergenceCheck('1h');

    if (in_array($hour, [3, 7, 11, 15, 19, 23]) && in_array($minute, [0, 1]))
        \Maksv\Bybit\Exchange::btcDivergenceCheck('4h');

   /* if (in_array($hour, [10, 19]) && in_array($minute, [0, 1]))
        \Maksv\Bybit\Exchange::btcDivergenceCheck('1d');*/
    
    return "btcDivergenceExchange();";
}

function fearGreedIndex()
{
    $hour = (int)date('H');
    $minute = (int)date('i');

    //if (in_array($hour, [3, 7, 11, 15, 19, 23]) && in_array($minute, [1, 2]))
        //\Maksv\Bybit\Exchange::fearGreedExchange();

    return "btcDOthersExchange();";
}

function encryptMessage($message, $shift) {
    $result = '';
    for ($i = 0; $i < strlen($message); $i++) {
        $charCode = ord($message[$i]);
        $result .= chr($charCode + $shift);
    }
    return $result;
}

function decryptMessage($encryptedMessage, $shift = 3) {
    $result = '';
    for ($i = 0; $i < strlen($encryptedMessage); $i++) {
        $charCode = ord($encryptedMessage[$i]);
        $result .= chr($charCode - $shift);
    }
    return $result;
}

function getBasketCnt(): int
{
    \CModule::IncludeModule('sale');

    $dbBasketItems = \CSaleBasket::GetList(
        array(
            "NAME" => "ASC",
            "ID" => "ASC"
        ),
        array(
            "FUSER_ID" => \CSaleBasket::GetBasketUserID(),
            "LID" => SITE_ID,
            "ORDER_ID" => "NULL"
        ),
        false,
        false,
        array("ID","QUANTITY")
    );
    $cnt = 0;
    while($bItem = $dbBasketItems->Fetch()) {
        $cnt += $bItem['QUANTITY'];
    }

    return $cnt;
}

function clean_expire_cache($path = "") {
    //devlogs("start" . ' - ' .  date("d.m.y H:i:s"), 'cleanCache');

    if (!class_exists("CFileCacheCleaner"))
        require_once ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/cache_files_cleaner.php");

    //$curentTime = mktime();
    $curentTime = time();
    /*if (defined("BX_CRONTAB") && BX_CRONTAB === true) $endTime = time() + 5; //Если на кроне, то работаем 5 секунд
    else $endTime = time() + 1; //Если на хитах, то не более секунды*/
    $endTime = time() + 5;
    //Работаем со всем кешем
    $obCacheCleaner = new CFileCacheCleaner("all");

    if (!$obCacheCleaner->InitPath($path))
        return "clean_expire_cache();";

    $obCacheCleaner->Start();
    while ($file = $obCacheCleaner->GetNextFile()) {
        if (is_string($file)) {
            $date_expire = $obCacheCleaner->GetFileExpiration($file);
            if ($date_expire) {
                if ($date_expire < $curentTime) {
                    unlink($file);
                }
            }
            if (time() >= $endTime) break;
        }
    }
    //devlogs("end" . ' - ' .  date("d.m.y H:i:s"), 'cleanCache');

    if (is_string($file))
        return "clean_expire_cache(\"" . $file . "\");";
    else
        return "clean_expire_cache();";
}

function convertExponentialToDecimal($number) {
    // Преобразуем число в строку с фиксированной точкой (достаточно знаков после запятой)
    $decimal = sprintf('%.20f', $number);
    // Удаляем лишние нули в конце и точку, если она осталась
    $decimal = rtrim($decimal, '0');
    $decimal = rtrim($decimal, '.');
    return $decimal;
}
