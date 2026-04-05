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


$router->get('register', static function () use ($front, $redirect): void {
    $front->registerForm($redirect);
});

$router->post('register', static function () use ($front, $redirect): void {
    $front->registerSubmit($redirect);
});

$router->get('activate', static function () use ($front): void {
    $front->activateForm();
});

$router->get('lost', static function () use ($front): void {
    $front->lostForm();
});

$router->post('lost', static function () use ($front): void {
    $front->lostSubmit();
});

$router->get('search', static function () use ($front): void {
    $front->search();
});

$router->get('feed', static function () use ($front): void {
    $front->feed();
});

$router->get('term/{slug}/feed', static function (array $params) use ($front, $redirect): void {
    $front->termFeed($params, $redirect);
});

$router->get('term/{slug}', static function (array $params) use ($front, $redirect): void {
    $front->termArchive($params, $redirect);
});

$router->get('{slug}', static function (array $params) use ($front, $redirect): void {
    $front->entry($params, $redirect);
});
