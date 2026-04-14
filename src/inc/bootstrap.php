<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/autoload.php';

use App\Controller\Admin\Admin as AdminController;
use App\Controller\Admin\Content as ContentController;
use App\Controller\Admin\ContentMedia as ContentMediaController;
use App\Controller\Admin\Media as MediaController;
use App\Controller\Admin\Settings as SettingsController;
use App\Controller\Admin\Term as TermController;
use App\Controller\Admin\User as UserController;
use App\Controller\Api\Heartbeat as HeartbeatController;
use App\Controller\Install\Install as InstallController;
use App\Service\Auth\Auth as SessionAuth;
use App\Service\Application\Auth as AppAuth;
use App\Service\Application\Content as ContentService;
use App\Service\Application\Install as InstallService;
use App\Service\Application\Media as MediaService;
use App\Service\Application\Upload as UploadService;
use App\Service\Application\Term as TermService;
use App\Service\Support\Csrf;
use App\Service\Support\DateTimeFormatter;
use App\Service\Support\Flash;
use App\Service\Infrastructure\Router\Router;
use App\Service\Application\Settings as SettingsService;
use App\Service\Support\I18n;
use App\Service\Support\Slugger;
use App\Service\Application\User as UserService;
use App\View\AdminView;
use App\View\View;

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = trim(dirname($scriptName), '/.');
$basePath = $baseDir === '' ? '' : '/' . $baseDir;

$router = new Router($basePath);
$flash = new Flash();
$csrf = new Csrf();
$dateTimeFormatter = new DateTimeFormatter(APP_DATE_FORMAT, APP_DATETIME_FORMAT);
$view = new View(dirname(__DIR__, 2), $router, $flash, $csrf, $dateTimeFormatter);

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

$isInstalled = is_file(dirname(__DIR__, 2) . '/config.php');

require __DIR__ . '/routes/register.php';

if (!$isInstalled) {
    $install = new InstallController($view, $csrf, new InstallService());

    $allowedWhenMissingConfig = $requestPath === 'install'
        || $requestPath === 'install/db'
        || $requestPath === 'install/admin'
        || $requestPath === 'install/done'
        || str_starts_with($requestPath, trim(ASSETS_DIR, '/'));

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

$auth = new SessionAuth();
$authService = new AppAuth($auth);
$userService = new UserService();
$contentService = new ContentService();
$mediaService = new MediaService();
$slugger = new Slugger();
$uploadService = new UploadService(dirname(__DIR__, 2), $slugger);
$settingsService = new SettingsService();
I18n::setLocale((string)($settingsService->resolved()['app_lang'] ?? APP_LANG));
$termService = new TermService();
$adminView = new AdminView($view, $settingsService);
$admin = new AdminController($adminView, $authService, $flash, $csrf);
$heartbeatApi = new HeartbeatController($authService, $csrf);
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
