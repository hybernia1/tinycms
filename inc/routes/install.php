<?php
declare(strict_types=1);

$router->get('install', static function () use ($install): void {
    $install->formLanguage();
});

$router->post('install', static function () use ($install, $redirect): void {
    $install->submitLanguage($redirect);
});

$router->get('install/db', static function () use ($install): void {
    $install->formDb();
});

$router->post('install/db', static function () use ($install, $redirect): void {
    $install->submitDb($redirect);
});

$router->get('install/admin', static function () use ($install, $redirect): void {
    $install->formAdmin($redirect);
});

$router->post('install/admin', static function () use ($install, $redirect): void {
    $install->submitAdmin($redirect);
});

$router->get('install/done', static function () use ($install, $redirect): void {
    $install->done($redirect);
});
