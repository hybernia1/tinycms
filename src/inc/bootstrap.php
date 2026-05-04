<?php
declare(strict_types=1);

if (!defined('BASE_DIR')) {
    exit;
}

use App\Controller\Admin\Admin as AdminController;
use App\Controller\Admin\Comment as CommentController;
use App\Controller\Admin\Content as ContentController;
use App\Controller\Admin\Media as MediaController;
use App\Controller\Admin\Menu as MenuController;
use App\Controller\Admin\Settings as SettingsController;
use App\Controller\Admin\Term as TermController;
use App\Controller\Admin\Theme as ThemeController;
use App\Controller\Admin\User as UserController;
use App\Controller\Admin\Widget as WidgetController;
use App\Controller\Front\Avatar as AvatarController;
use App\Controller\Front\Comment as FrontCommentController;
use App\Controller\Front\Front as FrontController;
use App\Controller\Api\Content as ApiContentController;
use App\Controller\Api\ContentMedia as ApiContentMediaController;
use App\Controller\Api\Comment as ApiCommentController;
use App\Controller\Api\Media as ApiMediaController;
use App\Controller\Api\Menu as ApiMenuController;
use App\Controller\Api\Sessions as SessionsController;
use App\Controller\Api\Settings as ApiSettingsController;
use App\Controller\Api\Term as ApiTermController;
use App\Controller\Api\Theme as ApiThemeController;
use App\Controller\Api\User as ApiUserController;
use App\Controller\Api\Widget as ApiWidgetController;
use App\Controller\Install\Install as InstallController;
use App\Service\Auth\Auth as SessionAuth;
use App\Service\Application\Auth as AppAuth;
use App\Service\Application\Content as ContentService;
use App\Service\Application\ContentStats as ContentStatsService;
use App\Service\Application\Comment as CommentService;
use App\Service\Application\Dashboard as DashboardService;
use App\Service\Application\Install as InstallService;
use App\Service\Application\Media as MediaService;
use App\Service\Application\Menu as MenuService;
use App\Service\Application\Widget as WidgetService;
use App\Service\Application\Upload as UploadService;
use App\Service\Application\Term as TermService;
use App\Service\Support\Csrf;
use App\Service\Support\Date;
use App\Service\Support\Flash;
use App\Service\Support\Media as MediaSupport;
use App\Service\Infrastructure\Router\Router;
use App\Service\Application\Settings as SettingsService;
use App\Service\Application\Theme as ThemeService;
use App\Service\Support\I18n;
use App\Service\Support\RequestContext;
use App\Service\Support\RateLimiter;
use App\Service\Support\Shortcode;
use App\Service\Support\Slugger;
use App\Service\Support\Avatar as AvatarService;
use App\Service\Application\User as UserService;
use App\Service\Front\AdminBar;
use App\View\AdminView;
use App\View\FrontView;
use App\View\View;

$isInstalled = is_file(BASE_DIR . '/config.php');
$resolvedSettings = [];
$auth = null;

if ($isInstalled) {
    $auth = new SessionAuth();
    $settingsService = new SettingsService();
    $themeService = new ThemeService(BASE_DIR);
    $resolvedSettings = array_replace($settingsService->resolved(), $themeService->resolved());
    $previewInput = is_array($_GET['theme'] ?? null) ? (array)$_GET['theme'] : [];
    if (trim((string)($_GET['theme_preview'] ?? '')) !== '' && $auth->isAdmin() && $previewInput !== []) {
        $resolvedSettings = array_replace($resolvedSettings, $themeService->previewValues($previewInput));
    }
    MediaSupport::configure($resolvedSettings);
    RequestContext::setWebsiteUrl((string)($resolvedSettings['website_url'] ?? ''));
    I18n::setLocale((string)($resolvedSettings['app_lang'] ?? APP_LANG));
}

$basePath = RequestContext::basePath();
$router = new Router($basePath, RequestContext::queryMode($basePath));
$flash = new Flash();
$csrf = new Csrf();
$rateLimiter = new RateLimiter();
$dateFormat = (string)($resolvedSettings['app_date_format'] ?? APP_DATE_FORMAT);
$dateTimeFormat = (string)($resolvedSettings['app_datetime_format'] ?? APP_DATETIME_FORMAT);
Date::configure($dateTimeFormat);
$dateTimeFormatter = new Date($dateFormat, $dateTimeFormat);
$view = new View(BASE_DIR, $router, $flash, $csrf, $dateTimeFormatter);

$redirect = static function (string $path = '', bool $permanent = false) use ($router): void {
    header('Location: ' . $router->url($path), true, $permanent ? 301 : 302);
    exit;
};

$requestPath = $router->requestPath((string)($_SERVER['REQUEST_URI'] ?? '/'));

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

if ($requestPath === 'install/done') {
    $install = new InstallController($view, $csrf, new InstallService());
    require BASE_DIR . '/' . INC_DIR . 'routes/install.php';

    return [
        'router' => $router,
    ];
}

if (str_starts_with($requestPath, 'install')) {
    $redirect('admin/dashboard');
}

$auth ??= new SessionAuth();
Shortcode::configure($router, $auth, $resolvedSettings);
$authService = new AppAuth($auth);
$userService = new UserService();
$contentService = new ContentService();
$contentStatsService = new ContentStatsService();
$commentService = new CommentService();
$mediaService = new MediaService();
$menuService = new MenuService();
$widgetService = new WidgetService(BASE_DIR, (string)($resolvedSettings['front_theme'] ?? 'default'));
$slugger = new Slugger();
$uploadService = new UploadService(BASE_DIR, $slugger);
$termService = new TermService();
$dashboardService = new DashboardService($contentService, $commentService, $mediaService, $userService);
$frontAdminBar = new AdminBar($router, $auth, $commentService);
$frontView = new FrontView(BASE_DIR, $router, $resolvedSettings, $frontAdminBar, $menuService, $widgetService, $commentService, $contentStatsService, $auth, $csrf);
$front = new FrontController($frontView, $settingsService, $termService, $userService, $auth, $contentStatsService, $resolvedSettings);
$frontAvatar = new AvatarController(new AvatarService());
$frontComments = new FrontCommentController($auth, $commentService, $csrf, $rateLimiter, $resolvedSettings);
$adminView = new AdminView($view, $settingsService, $contentService, $themeService->resolved());
$admin = new AdminController($authService, $flash, $csrf, $adminView, $dashboardService);
$apiSessions = new SessionsController($authService, $flash, $csrf, $rateLimiter);
$apiUser = new ApiUserController($authService, $userService, $flash, $csrf);
$adminUsers = new UserController($adminView, $authService, $userService, $flash, $csrf);
$adminContent = new ContentController($adminView, $authService, $contentService, $userService, $termService, $flash, $csrf);
$adminComments = new CommentController($adminView, $authService, $commentService, $flash, $csrf);
$adminMedia = new MediaController($adminView, $authService, $mediaService, $userService, $flash, $csrf);
$adminMenu = new MenuController($adminView, $authService, $menuService, $flash, $csrf);
$adminWidgets = new WidgetController($adminView, $authService, $widgetService, $flash, $csrf);
$adminSettings = new SettingsController($adminView, $authService, $settingsService, $flash, $csrf);
$adminThemes = new ThemeController($adminView, $authService, $themeService, $menuService, $widgetService, $flash, $csrf);
$adminTerms = new TermController($adminView, $authService, $termService, $flash, $csrf);
$apiContent = new ApiContentController($authService, $contentService, $termService, $flash, $csrf);
$apiComment = new ApiCommentController($authService, $commentService, $flash, $csrf);
$apiContentMedia = new ApiContentMediaController($authService, $contentService, $mediaService, $uploadService, $flash, $csrf);
$apiMedia = new ApiMediaController($authService, $mediaService, $uploadService, $flash, $csrf);
$apiMenu = new ApiMenuController($authService, $menuService, $flash, $csrf);
$apiWidget = new ApiWidgetController($authService, $widgetService, $themeService, $flash, $csrf);
$apiSettings = new ApiSettingsController($authService, $settingsService, $flash, $csrf);
$apiTheme = new ApiThemeController($authService, $themeService, $flash, $csrf);
$apiTerm = new ApiTermController($authService, $termService, $flash, $csrf);

require BASE_DIR . '/' . INC_DIR . 'routes/front.php';
require BASE_DIR . '/' . INC_DIR . 'routes/admin.php';

return [
    'router' => $router,
    'front' => $front,
];
