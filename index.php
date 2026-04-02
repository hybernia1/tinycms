<?php
declare(strict_types=1);

use App\Controller\AdminController;
use App\Controller\FrontController;
use App\Service\AuthService;
use App\View\PageView;

$app = require __DIR__ . '/inc/bootstrap.php';
$router = $app['router'];
$pageView = new PageView($app['view']);
$authService = new AuthService($app['auth']);
$front = new FrontController($pageView, $authService);
$admin = new AdminController($pageView, $authService);

$redirect = function (string $path = '') use ($router): void {
    header('Location: ' . $router->url($path));
    exit;
};

$render404 = static function (): void {
    http_response_code(404);
    echo '404';
};

require __DIR__ . '/inc/routes/front.php';
require __DIR__ . '/inc/routes/auth.php';
require __DIR__ . '/inc/routes/admin.php';

if (!$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET')) {
    $render404();
}
