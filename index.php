<?php
declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use App\Service\Support\ErrorHandler;

try {
    $app = require __DIR__ . '/inc/bootstrap.php';
    $router = $app['router'];

    if (!$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET')) {
        http_response_code(404);
        echo '404';
    }
} catch (\Throwable $e) {
    (new ErrorHandler())->handle($e);
}
