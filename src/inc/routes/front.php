<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}

register_routes($router, $redirect, [
    ['method' => 'GET', 'path' => '', 'controller' => $front, 'action' => 'home', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'content/{id}', 'controller' => $front, 'action' => 'content', 'with_redirect' => false, 'raw_params' => true],
    ['method' => 'GET', 'path' => 'term/{id}', 'controller' => $front, 'action' => 'termArchive', 'with_redirect' => false, 'raw_params' => true],
]);
