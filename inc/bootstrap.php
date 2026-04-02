<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use App\Controller\AdminController;
use App\Controller\FrontController;
use App\Service\Auth\Auth;
use App\Service\AuthService;
use App\Service\Router\Router;
use App\View\PageView;
use App\View\View;

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = trim(dirname($scriptName), '/.');
$basePath = $baseDir === '' ? '' : '/' . $baseDir;

$router = new Router($basePath);
$view = new View(dirname(__DIR__), $router);
$auth = new Auth();

$authService = new AuthService($auth);
$pageView = new PageView($view);
$front = new FrontController($pageView, $authService);
$admin = new AdminController($pageView, $authService);

$redirect = static function (string $path = '') use ($router): void {
    header('Location: ' . $router->url($path));
    exit;
};

$router->get('', static function () use ($front): void {
    $front->home();
});

$router->get('front/login', static function () use ($redirect): void {
    $redirect('login');
});

$router->get('login', static function () use ($front, $redirect): void {
    $front->loginForm($redirect);
});

$router->post('login', static function () use ($front, $redirect): void {
    $front->loginSubmit($redirect);
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

return [
    'router' => $router,
];
