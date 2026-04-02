<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $paths = [
        __DIR__ . '/' . INC_DIR . str_replace('\\', '/', $relativeClass) . '.php',
        __DIR__ . '/' . CLASS_DIR . '/' . str_replace('\\', '/', $relativeClass) . '.php',
    ];

    foreach ($paths as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});
