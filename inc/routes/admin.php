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

$router->get('admin/api/v1/auth/check', static function () use ($admin): void {
    $admin->authCheckApiV1();
});

$router->post('admin/api/v1/auth/login', static function () use ($admin): void {
    $admin->authLoginApiV1();
});

$router->get('admin/users', static function () use ($adminUsers, $redirect): void {
    $adminUsers->list($redirect);
});

$router->get('admin/api/v1/users', static function () use ($adminUsers, $redirect): void {
    $adminUsers->listApiV1($redirect);
});

$router->post('admin/api/v1/users/{id}/delete', static function (array $params) use ($adminUsers, $redirect): void {
    $adminUsers->deleteApiV1($redirect, (int)($params['id'] ?? 0));
});

$router->post('admin/api/v1/users/{id}/suspend', static function (array $params) use ($adminUsers, $redirect): void {
    $adminUsers->suspendApiV1($redirect, (int)($params['id'] ?? 0));
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

$router->get('admin/api/v1/content', static function () use ($adminContent, $redirect): void {
    $adminContent->listApiV1($redirect);
});

$router->post('admin/api/v1/content/{id}/delete', static function (array $params) use ($adminContent, $redirect): void {
    $adminContent->deleteApiV1($redirect, (int)($params['id'] ?? 0));
});

$router->post('admin/api/v1/content/{id}/status', static function (array $params) use ($adminContent, $redirect): void {
    $adminContent->statusApiV1($redirect, (int)($params['id'] ?? 0));
});

$router->post('admin/api/v1/content/draft/init', static function () use ($adminContent, $redirect): void {
    $adminContent->draftInitApiV1($redirect);
});

$router->post('admin/api/v1/content/autosave', static function () use ($adminContent, $redirect): void {
    $adminContent->autosaveApiV1($redirect);
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

$router->post('admin/content/thumbnail/delete', static function () use ($adminContent, $redirect): void {
    $adminContent->thumbnailDeleteSubmit($redirect);
});

$router->get('admin/api/v1/content/{id}/media', static function (array $params) use ($adminContent, $redirect): void {
    $adminContent->mediaLibraryApiV1($redirect, (int)($params['id'] ?? 0));
});

$router->post('admin/api/v1/content/{id}/media/upload', static function (array $params) use ($adminContent, $redirect): void {
    $adminContent->mediaLibraryUploadApiV1($redirect, (int)($params['id'] ?? 0));
});

$router->post('admin/api/v1/content/{id}/media/{mediaId}/delete', static function (array $params) use ($adminContent, $redirect): void {
    $adminContent->mediaLibraryDeleteApiV1($redirect, (int)($params['id'] ?? 0), (int)($params['mediaId'] ?? 0));
});

$router->post('admin/api/v1/content/{id}/media/{mediaId}/rename', static function (array $params) use ($adminContent, $redirect): void {
    $adminContent->mediaLibraryRenameApiV1($redirect, (int)($params['id'] ?? 0), (int)($params['mediaId'] ?? 0));
});

$router->post('admin/api/v1/content/{id}/media/{mediaId}/attach', static function (array $params) use ($adminContent, $redirect): void {
    $adminContent->attachmentAttachApiV1($redirect, (int)($params['id'] ?? 0), (int)($params['mediaId'] ?? 0));
});

$router->post('admin/api/v1/content/{id}/thumbnail/detach', static function (array $params) use ($adminContent, $redirect): void {
    $adminContent->thumbnailDetachApiV1($redirect, (int)($params['id'] ?? 0));
});

$router->post('admin/api/v1/content/{id}/thumbnail/{mediaId}/select', static function (array $params) use ($adminContent, $redirect): void {
    $adminContent->thumbnailSelectApiV1($redirect, (int)($params['id'] ?? 0), (int)($params['mediaId'] ?? 0));
});

$router->get('admin/terms', static function () use ($adminTerms, $redirect): void {
    $adminTerms->list($redirect);
});

$router->get('admin/api/v1/terms', static function () use ($adminTerms, $redirect): void {
    $adminTerms->listApiV1($redirect);
});

$router->post('admin/api/v1/terms/{id}/delete', static function (array $params) use ($adminTerms, $redirect): void {
    $adminTerms->deleteApiV1($redirect, (int)($params['id'] ?? 0));
});

$router->get('admin/api/v1/terms/suggest', static function () use ($adminTerms, $redirect): void {
    $adminTerms->suggest($redirect);
});

$router->get('admin/terms/add', static function () use ($adminTerms, $redirect): void {
    $adminTerms->addForm($redirect);
});

$router->post('admin/terms/add', static function () use ($adminTerms, $redirect): void {
    $adminTerms->addSubmit($redirect);
});

$router->get('admin/terms/edit', static function () use ($adminTerms, $redirect): void {
    $adminTerms->editForm($redirect);
});

$router->post('admin/terms/edit', static function () use ($adminTerms, $redirect): void {
    $adminTerms->editSubmit($redirect);
});
$router->get('admin/media', static function () use ($adminMedia, $redirect): void {
    $adminMedia->list($redirect);
});

$router->get('admin/api/v1/media', static function () use ($adminMedia, $redirect): void {
    $adminMedia->listApiV1($redirect);
});

$router->post('admin/api/v1/media/{id}/delete', static function (array $params) use ($adminMedia, $redirect): void {
    $adminMedia->deleteApiV1($redirect, (int)($params['id'] ?? 0));
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
