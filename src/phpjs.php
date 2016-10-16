<?php
const PHPJS_BASE_DIR = __DIR__;

spl_autoload_register(function ($className) {
    $path_parts = explode('\\', $className);

    if ($path_parts[0] === 'phpjs') {
        array_shift($path_parts);
        $file = PHPJS_BASE_DIR . DIRECTORY_SEPARATOR .
            implode(DIRECTORY_SEPARATOR, $path_parts) . '.class.php';

        if (file_exists($file)) {
            require $file;
        }
    }
});
