<?php
const PHPJS_BASE_DIR = __DIR__;

spl_autoload_register(function ($className) {
    $path_parts = explode('\\', $className);

    if ($path_parts[0] === 'Rowbot' && $path_parts[1] === 'DOM') {
        array_shift($path_parts);
        array_shift($path_parts);
        $file = PHPJS_BASE_DIR . DIRECTORY_SEPARATOR .
            implode(DIRECTORY_SEPARATOR, $path_parts);

        if (file_exists($file . '.class.php')) {
            require $file . '.class.php';
        } elseif (file_exists($file . '.php')) {
            require $file . '.php';
        }
    }
});
