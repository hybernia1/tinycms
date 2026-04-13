<?php
declare(strict_types=1);

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 80100) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'TinyCMS requires PHP 8.1 or newer.';
    exit;
}

require_once __DIR__ . '/autoload.php';

use App\Service\Support\ErrorHandler;

try {
    $app = require __DIR__ . '/' . tinycms_resolve_inc_dir() . 'bootstrap.php';
    $router = $app['router'];

    if (!$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET')) {
        http_response_code(404);
        echo '404';
    }
} catch (\Throwable $e) {
    (new ErrorHandler())->handle($e);
}
