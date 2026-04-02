<?php
declare(strict_types=1);

use App\Controller\AdminController;
use App\Controller\FrontController;
use App\Service\AuthService;
use App\View\PageView;

$app = require __DIR__ . '/inc/bootstrap.php';
$router = $app['router'];
$view = $app['view'];
$auth = $app['auth'];

$redirect = function (string $path = '') use ($router): void {
    header('Location: ' . $router->url($path));
    exit;
};

$render404 = static function (): void {
    http_response_code(404);
    echo '404';
};

$authService = new AuthService($auth);
$pageView = new PageView($view);
$front = new FrontController($pageView, $authService);
$admin = new AdminController($pageView, $authService);

$router->get('', static function () use ($front): void {
    $front->home();
});

$router->get('login', static function () use ($front, $redirect): void {
    $front->loginForm($redirect);
});

$router->post('login', static function () use ($front, $redirect): void {
    $front->loginSubmit($redirect);
});

$router->get('front/login', static function () use ($redirect): void {
    $redirect('login');
});

$router->get('admin/login', static function () use ($redirect): void {
    $redirect('login');
});

$router->get('admin', static function () use ($admin, $redirect): void {
    $admin->index($redirect);
});

$router->get('admin/dashboard', static function () use ($admin, $redirect): void {
    $admin->dashboard($redirect);
});

$router->get('admin/logout', static function () use ($admin, $redirect): void {
    $admin->logout($redirect);
});

if (!$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET')) {
    $render404();
}
