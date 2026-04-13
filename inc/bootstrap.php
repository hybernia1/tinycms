<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use App\Controller\Admin\AdminController;
use App\Controller\Admin\ContentController;
use App\Controller\Admin\Api\ContentMediaController;
use App\Controller\Admin\MediaController;
use App\Controller\Admin\SettingsController;
use App\Controller\Admin\TermController;
use App\Controller\Admin\UserController;
use App\Controller\Install\InstallController;
use App\Service\Auth\Auth;
use App\Service\Application\AuthService;
use App\Service\Application\ContentService;
use App\Service\Application\InstallService;
use App\Service\Application\MediaService;
use App\Service\Application\UploadService;
use App\Service\Application\TermService;
use App\Service\Support\CsrfService;
use App\Service\Support\DateTimeFormatter;
use App\Service\Support\FlashService;
use App\Service\Infrastructure\Router\Router;
use App\Service\Application\SettingsService;
use App\Service\Support\I18n;
use App\Service\Support\SluggerService;
use App\Service\Application\UserService;
use App\View\AdminView;
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
$settingsService = new SettingsService();
I18n::setLocale((string)($settingsService->resolved()['app_lang'] ?? APP_LANG));
$termService = new TermService();
$adminView = new AdminView($view, $settingsService);
$admin = new AdminController($adminView, $authService, $flash, $csrf);
$adminUsers = new UserController($adminView, $authService, $userService, $flash, $csrf);
$adminContent = new ContentController($adminView, $authService, $contentService, $userService, $termService, $flash, $csrf);
$adminContentMediaApi = new ContentMediaController($authService, $contentService, $mediaService, $uploadService, $flash, $csrf);
$adminMedia = new MediaController($adminView, $authService, $mediaService, $uploadService, $flash, $csrf);
$adminSettings = new SettingsController($adminView, $authService, $settingsService, $uploadService, $flash, $csrf);
$adminTerms = new TermController($adminView, $authService, $termService, $flash, $csrf);

require __DIR__ . '/routes/admin.php';

return [
    'router' => $router,
];
