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