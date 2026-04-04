<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use App\Controller\AdminController;
use App\Controller\AdminContentController;
use App\Controller\AdminMediaController;
use App\Controller\AdminMenuController;
use App\Controller\AdminSettingsController;
use App\Controller\AdminTermController;
use App\Controller\AdminUserController;
use App\Controller\FrontController;
use App\Service\Auth\Auth;
use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Feature\MediaService;
use App\Service\Feature\MenuService;
use App\Service\Feature\UploadService;
use App\Service\Feature\TermService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Infra\Router\Router;
use App\Service\Feature\SettingsService;
use App\Service\Support\SluggerService;
use App\Service\Feature\UserService;
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
$mediaService = new MediaService();
$slugger = new SluggerService();
$uploadService = new UploadService(dirname(__DIR__), $slugger);
$settingsService = new SettingsService();
$termService = new TermService();
$menuService = new MenuService();
$pageView = new PageView($view);
$front = new FrontController($pageView, $authService, $csrf, $settingsService, $contentService, $slugger);
$admin = new AdminController($pageView, $authService);
$adminUsers = new AdminUserController($pageView, $authService, $userService, $flash, $csrf);
$adminContent = new AdminContentController($pageView, $authService, $contentService, $mediaService, $uploadService, $userService, $termService, $flash, $csrf);
$adminMedia = new AdminMediaController($pageView, $authService, $mediaService, $uploadService, $flash, $csrf);
$adminSettings = new AdminSettingsController($pageView, $authService, $settingsService, $flash, $csrf);
$adminTerms = new AdminTermController($pageView, $authService, $termService, $flash, $csrf);
$adminMenu = new AdminMenuController($pageView, $authService, $menuService, $flash, $csrf);

$redirect = static function (string $path = '', bool $permanent = false) use ($router): void {
    header('Location: ' . $router->url($path), true, $permanent ? 301 : 302);
    exit;
};

require __DIR__ . '/routes/front.php';
require __DIR__ . '/routes/admin.php';

return [
    'router' => $router,
];
