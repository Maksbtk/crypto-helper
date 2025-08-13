<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

try {
    technicalExhc1d();
} catch (\Throwable $e) {
    $errText = sprintf(
        "ERR  | File: %s | Line: %d | Error: %s | Time: %s",
        $e->getFile(),
        $e->getLine(),
        $e->getMessage(),
        date("d.m.y H:i:s")
    );
    \Maksv\DataOperation::sendErrorInfoMessage($errText, 'technicalExhc1d.php', '/crone/');
}
