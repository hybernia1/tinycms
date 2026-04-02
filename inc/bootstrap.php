<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use App\Controller\AdminController;
use App\Controller\AdminUserController;
use App\Controller\FrontController;
use App\Service\Auth\Auth;
use App\Service\AuthService;
use App\Service\FlashService;
use App\Service\Router\Router;
use App\Service\UserService;
use App\View\PageView;
use App\View\View;

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = trim(dirname($scriptName), '/.');
$basePath = $baseDir === '' ? '' : '/' . $baseDir;

$router = new Router($basePath);
$auth = new Auth();
$flash = new FlashService();
$view = new View(dirname(__DIR__), $router, $flash);

$authService = new AuthService($auth);
$pageView = new PageView($view);
$userService = new UserService();
$front = new FrontController($pageView, $authService);
$admin = new AdminController($pageView, $authService);
$adminUsers = new AdminUserController($pageView, $authService, $userService, $flash);

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

$router->get('admin/users', static function () use ($adminUsers, $redirect): void {
    $adminUsers->list($redirect);
});

$router->post('admin/users/delete', static function () use ($adminUsers, $redirect): void {
    $adminUsers->deleteSubmit($redirect);
});

$router->post('admin/users/suspend', static function () use ($adminUsers, $redirect): void {
    $adminUsers->suspendSubmit($redirect);
});

$router->post('admin/users/bulk-action', static function () use ($adminUsers, $redirect): void {
    $adminUsers->bulkActionSubmit($redirect);
});

$router->get('admin/users/add', static function () use ($adminUsers, $redirect): void {
    $adminUsers->addForm($redirect);
});

$router->post('admin/users/add', static function () use ($adminUsers, $redirect): void {
    $adminUsers->addSubmit($redirect);
});

$router->get('admin/users/edit', static function () use ($adminUsers, $redirect): void {
    $adminUsers->editForm($redirect);
});

$router->post('admin/users/edit', static function () use ($adminUsers, $redirect): void {
    $adminUsers->editSubmit($redirect);
});

$router->get('admin/logout', static function () use ($admin, $redirect): void {
    $admin->logout($redirect);
});

return [
    'router' => $router,
];
