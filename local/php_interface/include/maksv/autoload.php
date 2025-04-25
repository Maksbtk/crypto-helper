<?php
//require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/*spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
    $classNameAr = explode('\\', $class);
    $className = $classNameAr[array_key_last($classNameAr)];
    $file = $baseDir . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});*/

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
    $classNameAr = explode('\\', $class);
    $className = $classNameAr[array_key_last($classNameAr)];

    // Функция для рекурсивного поиска файла
    $findFile = function ($dir, $className) use (&$findFile) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $found = $findFile($path, $className);
                if ($found) {
                    return $found;
                }
            } elseif ($file === $className . '.php') {
                return $path;
            }
        }
        return null;
    };

    $file = $findFile($baseDir, $className);

    if ($file) {
        require_once $file;
    }
});