<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use App\Controller\AdminController;
use App\Controller\AdminContentController;
use App\Controller\AdminContentMediaApiController;
use App\Controller\AdminMediaController;
use App\Controller\AdminSettingsController;
use App\Controller\AdminTermController;
use App\Controller\AdminUserController;
use App\Controller\FrontController;
use App\Controller\InstallController;
use App\Service\Auth\Auth;
use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Feature\InstallService;
use App\Service\Feature\MediaService;
use App\Service\Feature\UploadService;
use App\Service\Feature\TermService;
use App\Service\Feature\ThemeService;
use App\Service\Support\CsrfService;
use App\Service\Support\DateTimeFormatter;
use App\Service\Support\FlashService;
use App\Service\Infra\Router\Router;
use App\Service\Feature\SettingsService;
use App\Service\Support\I18n;
use App\Service\Support\SluggerService;
use App\Service\Feature\UserService;
use App\View\AdminView;
use App\View\FrontView;
use App\View\View;

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = trim(dirname($scriptName), '/.');
$basePath = $baseDir === '' ? '' : '/' . $baseDir;

$router = new Router($basePath);
$flash = new FlashService();
$csrf = new CsrfService();
$dateTimeFormatter = new DateTimeFormatter(APP_DATE_FORMAT, APP_DATETIME_FORMAT);
$view = new View(dirname(__DIR__), $router, $flash, $csrf, $dateTimeFormatter);

$redirect = static function (string $path = '', bool $permanent = false) use ($router): void {
    header('Location: ' . $router->url($path), true, $permanent ? 301 : 302);
    exit;
};

$requestPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
$requestPath = '/' . ltrim($requestPath, '/');
if ($basePath !== '' && ($requestPath === $basePath || str_starts_with($requestPath, $basePath . '/'))) {
    $requestPath = (string)substr($requestPath, strlen($basePath));
}
$requestPath = trim($requestPath, '/');

$isInstalled = is_file(dirname(__DIR__) . '/config.php');

require __DIR__ . '/routes/register.php';

if (!$isInstalled) {
    $install = new InstallController($view, $csrf, new InstallService());

    $allowedWhenMissingConfig = $requestPath === 'install'
        || $requestPath === 'install/db'
        || $requestPath === 'install/admin'
        || $requestPath === 'install/done'
        || str_starts_with($requestPath, 'assets/');

    if (!$allowedWhenMissingConfig) {
        $redirect('install');
    }

    require __DIR__ . '/routes/install.php';

    return [
        'router' => $router,
    ];
}

if (str_starts_with($requestPath, 'install')) {
    $redirect('admin/dashboard');
}

$auth = new Auth();
$authService = new AuthService($auth);
$userService = new UserService();
$contentService = new ContentService();
$mediaService = new MediaService();
$slugger = new SluggerService();
$uploadService = new UploadService(dirname(__DIR__), $slugger);
$themeService = new ThemeService(dirname(__DIR__));
$settingsService = new SettingsService($themeService);
$resolvedSettings = $settingsService->resolved();
I18n::setLocale((string)($resolvedSettings['app_lang'] ?? APP_LANG));
I18n::setTheme($themeService->resolveTheme((string)($resolvedSettings['theme'] ?? 'default')));
$termService = new TermService();
$frontView = new FrontView($view, $themeService);
$adminView = new AdminView($view, $settingsService);
$front = new FrontController($frontView, $authService, $csrf, $settingsService, $contentService, $termService, $slugger);
$admin = new AdminController($adminView, $authService);
$adminUsers = new AdminUserController($adminView, $authService, $userService, $flash, $csrf);
$adminContent = new AdminContentController($adminView, $authService, $contentService, $userService, $termService, $flash, $csrf);
$adminContentMediaApi = new AdminContentMediaApiController($authService, $contentService, $mediaService, $uploadService, $flash, $csrf);
$adminMedia = new AdminMediaController($adminView, $authService, $mediaService, $uploadService, $flash, $csrf);
$adminSettings = new AdminSettingsController($adminView, $authService, $settingsService, $uploadService, $flash, $csrf);
$adminTerms = new AdminTermController($adminView, $authService, $termService, $flash, $csrf);

require __DIR__ . '/routes/front.php';
require __DIR__ . '/routes/admin.php';

return [
    'router' => $router,
    'front' => $front,
];
