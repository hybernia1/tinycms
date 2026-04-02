<?php
declare(strict_types=1);

$router->get('login', static function () use ($front, $redirect): void {
    $front->loginForm($redirect);
});

$router->post('login', static function () use ($front, $redirect): void {
    $front->loginSubmit($redirect);
});
