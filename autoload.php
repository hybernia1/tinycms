<?php
declare(strict_types=1);

require_once "config.php";

spl_autoload_register(function ($class) {

    // prefix namespace
    $prefix = 'App\\';

    // base directory
    $base_dir = CLASS_DIR . '/';

    // kontrola prefixu
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    // odstranění prefixu
    $relative_class = substr($class, strlen($prefix));

    // namespace -> cesta
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});