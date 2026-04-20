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

function sendImageNotFoundFallback(string $uri): bool
{
    $path = (string)(parse_url($uri, PHP_URL_PATH) ?? '');
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return false;
    }

    $extension = strtolower((string)pathinfo($cleanPath, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif', 'bmp', 'ico'], true)) {
        return false;
    }

    if (is_file(BASE_DIR . '/' . $cleanPath)) {
        return false;
    }

    http_response_code(404);
    header('Content-Type: image/svg+xml; charset=utf-8');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480" viewBox="0 0 640 480" role="img" aria-label="Missing image placeholder"><rect width="640" height="480" fill="#f3f4f6"/><rect x="120" y="120" width="400" height="240" rx="14" fill="none" stroke="#cbd5e1" stroke-width="20"/><circle cx="235" cy="220" r="34" fill="#cbd5e1"/><path d="M160 320l96-96 82 82 62-62 80 76H160z" fill="#cbd5e1"/></svg>';
    return true;
}

try {
    $app = require __DIR__ . '/' . INC_DIR . 'bootstrap.php';
    $router = $app['router'];

    if (!$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET')) {
        if (sendImageNotFoundFallback($_SERVER['REQUEST_URI'] ?? '/')) {
            exit;
        }

        if (isset($app['front']) && $app['front'] instanceof \App\Controller\Front\Front) {
            $app['front']->notFound();
        } else {
            http_response_code(404);
            echo '404';
        }
    }
} catch (\Throwable $e) {
    (new ErrorHandler())->handle($e);
}
