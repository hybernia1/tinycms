<?php
declare(strict_types=1);

$router->get('admin/login', static function () use ($redirect): void {
    $redirect('login');
});

$router->get('admin', static function () use ($admin, $redirect): void {
    $admin->index($redirect);
});

$router->get('admin/dashboard', static function () use ($admin, $redirect): void {
    $admin->dashboard($redirect);
});

$router->get('admin/users', static function () use ($adminUsers, $redirect): void {
    $adminUsers->list($redirect);
});

$router->post('admin/users/delete', static function () use ($adminUsers, $redirect): void {
    $adminUsers->deleteSubmit($redirect);
});

$router->post('admin/users/suspend-toggle', static function () use ($adminUsers, $redirect): void {
    $adminUsers->suspendToggleSubmit($redirect);
});

$router->get('admin/users/add', static function () use ($adminUsers, $redirect): void {
    $adminUsers->addForm($redirect);
});

$router->post('admin/users/add', static function () use ($adminUsers, $redirect): void {
    $adminUsers->addSubmit($redirect);
});

$router->get('admin/users/edit', static function () use ($adminUsers, $redirect): void {
    $adminUsers->editForm($redirect);
});

$router->post('admin/users/edit', static function () use ($adminUsers, $redirect): void {
    $adminUsers->editSubmit($redirect);
});

$router->get('admin/content', static function () use ($adminContent, $redirect): void {
    $adminContent->list($redirect);
});

$router->post('admin/content/delete', static function () use ($adminContent, $redirect): void {
    $adminContent->deleteSubmit($redirect);
});

$router->post('admin/content/status-toggle', static function () use ($adminContent, $redirect): void {
    $adminContent->statusToggleSubmit($redirect);
});

$router->get('admin/content/add', static function () use ($adminContent, $redirect): void {
    $adminContent->addForm($redirect);
});

$router->post('admin/content/add', static function () use ($adminContent, $redirect): void {
    $adminContent->addSubmit($redirect);
});

$router->get('admin/content/edit', static function () use ($adminContent, $redirect): void {
    $adminContent->editForm($redirect);
});

$router->post('admin/content/edit', static function () use ($adminContent, $redirect): void {
    $adminContent->editSubmit($redirect);
});

$router->post('admin/content/thumbnail/upload', static function () use ($adminContent, $redirect): void {
    $adminContent->thumbnailUploadSubmit($redirect);
});

$router->post('admin/content/thumbnail/detach', static function () use ($adminContent, $redirect): void {
    $adminContent->thumbnailDetachSubmit($redirect);
});

$router->post('admin/content/thumbnail/delete', static function () use ($adminContent, $redirect): void {
    $adminContent->thumbnailDeleteSubmit($redirect);
});

$router->get('admin/media', static function () use ($adminMedia, $redirect): void {
    $adminMedia->list($redirect);
});

$router->post('admin/media/delete', static function () use ($adminMedia, $redirect): void {
    $adminMedia->deleteSubmit($redirect);
});

$router->get('admin/media/add', static function () use ($adminMedia, $redirect): void {
    $adminMedia->addForm($redirect);
});

$router->post('admin/media/add', static function () use ($adminMedia, $redirect): void {
    $adminMedia->addSubmit($redirect);
});

$router->get('admin/media/edit', static function () use ($adminMedia, $redirect): void {
    $adminMedia->editForm($redirect);
});

$router->post('admin/media/edit', static function () use ($adminMedia, $redirect): void {
    $adminMedia->editSubmit($redirect);
});

$router->get('admin/settings', static function () use ($adminSettings, $redirect): void {
    $adminSettings->form($redirect);
});

$router->post('admin/settings', static function () use ($adminSettings, $redirect): void {
    $adminSettings->submit($redirect);
});

$router->get('admin/logout', static function () use ($admin, $redirect): void {
    $admin->logout($redirect);
});
