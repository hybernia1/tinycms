<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = str_replace('\\', '/', substr($class, strlen($prefix)));

    $paths = [
        __DIR__ . '/' . INC_DIR . $relativeClass . '.php',
    ];

    if (str_starts_with($relativeClass, 'Auth/') || str_starts_with($relativeClass, 'Db/') || str_starts_with($relativeClass, 'Router/')) {
        $paths[] = __DIR__ . '/' . INC_DIR . 'Service/' . $relativeClass . '.php';
    }

    foreach ($paths as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});
