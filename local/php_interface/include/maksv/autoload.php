<?php
spl_autoload_register(function ($class) {
    // префикс вашего пространства имён
    $prefix = 'Maksv\\';
    // корневая папка, где лежит папка classes
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;

    // если класс не в нашем пространстве имён — пропускаем
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // получаем "хвост" после префикса, например "Openapi\Request"
    $relativeClass = substr($class, $len);

    // преобразуем namespace-разделители в разделители папок
    $file = $baseDir
        . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
        . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
/*spl_autoload_register(function ($class) {
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
});*/