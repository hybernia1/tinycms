<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use App\Controller\AdminController;
use App\Controller\AdminContentController;
use App\Controller\AdminSettingsController;
use App\Controller\AdminUserController;
use App\Controller\FrontController;
use App\Service\Auth\Auth;
use App\Service\AuthService;
use App\Service\ContentService;
use App\Service\ContentTypeService;
use App\Service\CsrfService;
use App\Service\FlashService;
use App\Service\Router\Router;
use App\Service\SettingsService;
use App\Service\SluggerService;
use App\Service\UserService;
use App\View\PageView;
use App\View\View;

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = trim(dirname($scriptName), '/.');
$basePath = $baseDir === '' ? '' : '/' . $baseDir;

$router = new Router($basePath);
$auth = new Auth();
$flash = new FlashService();
$csrf = new CsrfService();
$view = new View(dirname(__DIR__), $router, $flash, $csrf);

$authService = new AuthService($auth);
$userService = new UserService();
$contentService = new ContentService();
$slugger = new SluggerService();
$contentTypes = new ContentTypeService();
$settingsService = new SettingsService();
$pageView = new PageView($view, $settingsService, $contentTypes);
$front = new FrontController($pageView, $authService, $csrf, $settingsService, $contentService, $contentTypes, $slugger);
$admin = new AdminController($pageView, $authService);
$adminUsers = new AdminUserController($pageView, $authService, $userService, $flash, $csrf);
$adminContent = new AdminContentController($pageView, $authService, $contentService, $contentTypes, $userService, $flash, $csrf);
$adminSettings = new AdminSettingsController($pageView, $authService, $settingsService, $flash, $csrf);

$redirect = static function (string $path = '') use ($router): void {
    header('Location: ' . $router->url($path));
    exit;
};

$router->get('', static function () use ($front): void {
    $front->home();
});

$router->get('{typeSlug}/{slug}', static function (array $params) use ($front): void {
    $front->contentDetail($params);
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

$router->post('admin/users/suspend-toggle', static function () use ($adminUsers, $redirect): void {
    $adminUsers->suspendToggleSubmit($redirect);
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

$router->get('admin/content', static function () use ($adminContent, $redirect): void {
    $adminContent->list($redirect);
});

$router->post('admin/content/delete', static function () use ($adminContent, $redirect): void {
    $adminContent->deleteSubmit($redirect);
});

$router->post('admin/content/bulk-action', static function () use ($adminContent, $redirect): void {
    $adminContent->bulkActionSubmit($redirect);
});

$router->post('admin/content/status-toggle', static function () use ($adminContent, $redirect): void {
    $adminContent->statusToggleSubmit($redirect);
});

$router->get('admin/content/add', static function () use ($adminContent, $redirect): void {
    $adminContent->addForm($redirect);
});

$router->post('admin/content/add', static function () use ($adminContent, $redirect): void {
    $adminContent->addSubmit($redirect);
});

$router->get('admin/content/edit', static function () use ($adminContent, $redirect): void {
    $adminContent->editForm($redirect);
});

$router->post('admin/content/edit', static function () use ($adminContent, $redirect): void {
    $adminContent->editSubmit($redirect);
});


$router->get('admin/settings', static function () use ($adminSettings, $redirect): void {
    $adminSettings->form($redirect);
});

$router->post('admin/settings', static function () use ($adminSettings, $redirect): void {
    $adminSettings->submit($redirect);
});

$router->get('admin/logout', static function () use ($admin, $redirect): void {
    $admin->logout($redirect);
});

return [
    'router' => $router,
];
