<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/Class/Router/Router.php';

use App\Router\Router;

$router = new Router();
$router->dispatch($_SERVER['REQUEST_URI'] ?? '/');
