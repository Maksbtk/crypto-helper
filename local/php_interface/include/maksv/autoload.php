<?php
//require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
    $classNameAr = explode('\\', $class);
    $className = $classNameAr[array_key_last($classNameAr)];
    $file = $baseDir . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});