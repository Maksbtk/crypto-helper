<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('CHK_EVENT', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");

echo("start");

$difPeriod = '-12 weeks';
//$difPeriod = '-7 days';

/*$startDate = (new DateTime($difPeriod))->format('d.m.Y');
$endDate = date('Y-m-d');

$date_to = (new DateTime($startDate))->format('d.m.Y');
$date_from = (new DateTime($endDate))->format('d.m.Y');
echo '<pre>';  var_dump('$date_to - ' . $date_to); echo '</pre>';

$delElAr = [];
$countDel= 0;

$itemObj = CIBlockElement::GetList(["ID" => "ASC"], [
    "IBLOCK_ID" => "3",
    "<TIMESTAMP_X" => $date_to . ' 00:00:00',
    //"<TIMESTAMP_X" => $date_from . ' 23:59:59',
], false, false, ["ID"]);
while ($arItem = $itemObj->fetch()) {
    $delElAr[] = $arItem['ID'];
}

echo '<pre>';  var_dump('elements count - '.count($delElAr)); echo '</pre>';

foreach ($delElAr as $item) {
    if(CIBlockElement::Delete($item))
    {
        $countDel++;
    }
}
echo '<pre>';  var_dump('del count - '.$countDel); echo '</pre>';*/
