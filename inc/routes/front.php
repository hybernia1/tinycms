<?php
declare(strict_types=1);

$router->get('', static function () use ($front): void {
    $front->home();
});

$router->get('front/login', static function () use ($redirect): void {
    $redirect('login');
});
