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
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480" viewBox="0 0 640 480" role="img" aria-label="404 image not found"><rect width="640" height="480" fill="#f3f4f6"/><g fill="#9ca3af"><rect x="120" y="130" width="400" height="220" rx="14"/><circle cx="230" cy="220" r="28"/><path d="M168 314l88-88 64 64 56-56 96 80z"/></g><text x="320" y="418" text-anchor="middle" font-family="Arial,sans-serif" font-size="28" fill="#6b7280">404 · Image not found</text></svg>';
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
