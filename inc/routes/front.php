<?php
declare(strict_types=1);

$router->get('', static function () use ($front): void {
    $front->home();
});

$router->get('front/login', static function () use ($redirect): void {
    $redirect('login');
});

$router->get('login', static function () use ($front, $redirect): void {
    $front->loginForm($redirect);
});

$router->post('login', static function () use ($front, $redirect): void {
    $front->loginSubmit($redirect);
});

$router->get('term/{slug}', static function (array $params) use ($front, $redirect): void {
    $front->termArchive($params, $redirect);
});

$router->get('{slug}', static function (array $params) use ($front, $redirect): void {
    $front->entry($params, $redirect);
});
