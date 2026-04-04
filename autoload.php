<?php
declare(strict_types=1);

if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (is_file(__DIR__ . '/config.example.php')) {
    require_once __DIR__ . '/config.example.php';
}

if (!defined('INC_DIR')) {
    define('INC_DIR', 'inc/');
}

if (!defined('MEDIA_THUMB_VARIANTS')) {
    define('MEDIA_THUMB_VARIANTS', []);
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

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
