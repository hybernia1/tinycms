<?php
declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use App\Router\Router;

$router = new Router();
$router->dispatch($_SERVER['REQUEST_URI'] ?? '/');
