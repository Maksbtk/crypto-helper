<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

try {
    okxSummaryVolumeExchange();
} catch (\Throwable $e) {
    $errText = "ERR  | err - : {$e->getMessage()} | timeMark - " . date("d.m.y H:i:s");
    \Maksv\DataOperation::sendErrorInfoMessage($errText, 'okxSummaryVolumeExchange.php', '/crone/okx/');
}