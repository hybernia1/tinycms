<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/' . INC_DIR . $relativeClass . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
