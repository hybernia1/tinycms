<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/autoload.php';

use App\Auth\Auth;
use App\Router\Router;
use App\View\View;

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = trim(dirname($scriptName), '/.');
$basePath = $baseDir === '' ? '' : '/' . $baseDir;

$router = new Router($basePath);
$view = new View(dirname(__DIR__), $router);
$auth = new Auth();

return [
    'router' => $router,
    'view' => $view,
    'auth' => $auth,
];
