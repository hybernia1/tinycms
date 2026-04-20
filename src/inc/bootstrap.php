<?php
declare(strict_types=1);

if (!defined('BASE_DIR')) {
    exit;
}

use App\Controller\Admin\Admin as AdminController;
use App\Controller\Admin\Content as ContentController;
use App\Controller\Admin\Media as MediaController;
use App\Controller\Admin\Settings as SettingsController;
use App\Controller\Admin\Term as TermController;
use App\Controller\Admin\User as UserController;
use App\Controller\Front\Front as FrontController;
use App\Controller\Api\Content as ApiContentController;
use App\Controller\Api\ContentMedia as ApiContentMediaController;
use App\Controller\Api\Media as ApiMediaController;
use App\Controller\Api\Sessions as SessionsController;
use App\Controller\Api\Settings as ApiSettingsController;
use App\Controller\Api\Term as ApiTermController;
use App\Controller\Api\User as ApiUserController;
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
use App\Service\Support\RequestContext;
use App\Service\Support\RateLimiter;
use App\Service\Support\Slugger;
use App\Service\Application\User as UserService;
use App\Service\Front\AdminBar;
use App\Service\Front\Services as FrontServices;
use App\View\AdminView;
use App\View\FrontView;
use App\View\View;

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = trim(dirname($scriptName), '/.');
$basePath = $baseDir === '' ? '' : '/' . $baseDir;

$router = new Router($basePath);
$flash = new Flash();
$csrf = new Csrf();
$rateLimiter = new RateLimiter();
$dateTimeFormatter = new DateTimeFormatter(APP_DATE_FORMAT, APP_DATETIME_FORMAT);
$view = new View(BASE_DIR, $router, $flash, $csrf, $dateTimeFormatter);

$redirect = static function (string $path = '', bool $permanent = false) use ($router): void {
    header('Location: ' . $router->url($path), true, $permanent ? 301 : 302);
    exit;
};

$requestPath = $router->requestPath((string)($_SERVER['REQUEST_URI'] ?? '/'));

$isInstalled = is_file(BASE_DIR . '/config.php');

require BASE_DIR . '/' . INC_DIR . 'routes/register.php';

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

    require BASE_DIR . '/' . INC_DIR . 'routes/install.php';

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
$uploadService = new UploadService(BASE_DIR, $slugger);
$settingsService = new SettingsService();
$resolvedSettings = $settingsService->resolved();
RequestContext::setWebsiteUrl((string)($resolvedSettings['website_url'] ?? ''));
I18n::setLocale((string)($resolvedSettings['app_lang'] ?? APP_LANG));
$termService = new TermService();
$frontServices = new FrontServices($contentService, $userService, $mediaService, $termService, $settingsService);
$frontAdminBar = new AdminBar($router, $auth);
$frontView = new FrontView(BASE_DIR, $router, $resolvedSettings, $frontAdminBar);
$front = new FrontController($frontView, $frontServices, $auth);
$adminView = new AdminView($view, $settingsService);
$admin = new AdminController($authService, $flash, $csrf, $adminView);
$apiSessions = new SessionsController($authService, $flash, $csrf, $rateLimiter);
$apiUser = new ApiUserController($authService, $userService, $flash, $csrf);
$adminUsers = new UserController($adminView, $authService, $userService, $flash, $csrf);
$adminContent = new ContentController($adminView, $authService, $contentService, $userService, $termService, $flash, $csrf);
$adminMedia = new MediaController($adminView, $authService, $mediaService, $flash, $csrf);
$adminSettings = new SettingsController($adminView, $authService, $settingsService, $flash, $csrf);
$adminTerms = new TermController($adminView, $authService, $termService, $flash, $csrf);
$apiContent = new ApiContentController($authService, $contentService, $termService, $flash, $csrf);
$apiContentMedia = new ApiContentMediaController($authService, $contentService, $mediaService, $uploadService, $flash, $csrf);
$apiMedia = new ApiMediaController($authService, $mediaService, $uploadService, $flash, $csrf);
$apiSettings = new ApiSettingsController($authService, $settingsService, $uploadService, $flash, $csrf);
$apiTerm = new ApiTermController($authService, $termService, $flash, $csrf);

require BASE_DIR . '/' . INC_DIR . 'routes/front.php';
require BASE_DIR . '/' . INC_DIR . 'routes/admin.php';

return [
    'router' => $router,
];
