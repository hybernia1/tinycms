<?php
declare(strict_types=1);

if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (is_file(__DIR__ . '/config.example.php')) {
    require_once __DIR__ . '/config.example.php';
}

if (!defined('SRC_DIR')) {
    define('SRC_DIR', 'src/');
}

if (!defined('INC_DIR')) {
    define('INC_DIR', SRC_DIR . 'inc/');
}

if (!defined('VIEW_DIR')) {
    define('VIEW_DIR', SRC_DIR . 'view/');
}

if (!defined('ASSETS_DIR')) {
    define('ASSETS_DIR', SRC_DIR . 'assets/');
}

if (!defined('MEDIA_THUMB_VARIANTS')) {
    define('MEDIA_THUMB_VARIANTS', []);
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

if (!defined('APP_LANG')) {
    define('APP_LANG', 'en');
}

if (!defined('APP_DATE_FORMAT')) {
    define('APP_DATE_FORMAT', 'Y-m-d');
}

if (!defined('APP_DATETIME_FORMAT')) {
    define('APP_DATETIME_FORMAT', 'Y-m-d H:i:s');
}

if (!defined('APP_POSTS_PER_PAGE')) {
    define('APP_POSTS_PER_PAGE', 10);
}

if (!defined('DB_PREFIX')) {
    define('DB_PREFIX', '');
}

if (!function_exists('tinycms_resolve_inc_dir')) {
    function tinycms_resolve_inc_dir(): string
    {
        $candidates = [];

        if (defined('INC_DIR')) {
            $candidates[] = trim((string)INC_DIR, '/') . '/';
        }

        $candidates[] = 'src/inc/';
        $candidates[] = 'inc/';

        foreach (array_values(array_unique($candidates)) as $candidate) {
            if (is_dir(__DIR__ . '/' . $candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }
}

$resolvedIncDir = tinycms_resolve_inc_dir();

spl_autoload_register(function (string $class) use ($resolvedIncDir): void {
    $prefix = 'App\\';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/' . $resolvedIncDir . $relativeClass . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
