<?php
declare(strict_types=1);

register_routes($router, $redirect, [
    ['method' => 'GET', 'path' => 'admin/login', 'handler' => static function () use ($redirect): void {
        $redirect('login');
    }],
    ['method' => 'GET', 'path' => 'admin', 'controller' => $admin, 'action' => 'index'],
    ['method' => 'GET', 'path' => 'admin/dashboard', 'controller' => $admin, 'action' => 'dashboard'],

    ['method' => 'GET', 'path' => 'admin/users', 'controller' => $adminUsers, 'action' => 'list'],
    ['method' => 'GET', 'path' => 'admin/api/v1/users', 'controller' => $adminUsers, 'action' => 'listApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/users/{id}/delete', 'controller' => $adminUsers, 'action' => 'deleteApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/users/{id}/suspend', 'controller' => $adminUsers, 'action' => 'suspendApiV1', 'params' => ['id' => 'int']],
    ['method' => 'GET', 'path' => 'admin/users/add', 'controller' => $adminUsers, 'action' => 'addForm'],
    ['method' => 'POST', 'path' => 'admin/users/add', 'controller' => $adminUsers, 'action' => 'addSubmit'],
    ['method' => 'GET', 'path' => 'admin/users/edit', 'controller' => $adminUsers, 'action' => 'editForm'],
    ['method' => 'POST', 'path' => 'admin/users/edit', 'controller' => $adminUsers, 'action' => 'editSubmit'],

    ['method' => 'GET', 'path' => 'admin/content', 'controller' => $adminContent, 'action' => 'list'],
    ['method' => 'GET', 'path' => 'admin/api/v1/content', 'controller' => $adminContent, 'action' => 'listApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{id}/delete', 'controller' => $adminContent, 'action' => 'deleteApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{id}/status', 'controller' => $adminContent, 'action' => 'statusApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/draft/init', 'controller' => $adminContent, 'action' => 'draftInitApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/autosave', 'controller' => $adminContent, 'action' => 'autosaveApiV1'],
    ['method' => 'GET', 'path' => 'admin/api/v1/link-title', 'controller' => $adminContent, 'action' => 'linkTitleApiV1'],
    ['method' => 'GET', 'path' => 'admin/content/add', 'controller' => $adminContent, 'action' => 'addForm'],
    ['method' => 'POST', 'path' => 'admin/content/add', 'controller' => $adminContent, 'action' => 'addSubmit'],
    ['method' => 'GET', 'path' => 'admin/content/edit', 'controller' => $adminContent, 'action' => 'editForm'],
    ['method' => 'POST', 'path' => 'admin/content/edit', 'controller' => $adminContent, 'action' => 'editSubmit'],
    ['method' => 'GET', 'path' => 'admin/api/v1/content/{contentId}/media', 'controller' => $adminContentMediaApi, 'action' => 'mediaLibraryApiV1', 'params' => ['contentId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/media/upload', 'controller' => $adminContentMediaApi, 'action' => 'mediaLibraryUploadApiV1', 'params' => ['contentId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/media/{mediaId}/delete', 'controller' => $adminContentMediaApi, 'action' => 'mediaLibraryDeleteApiV1', 'params' => ['contentId' => 'int', 'mediaId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/media/{mediaId}/rename', 'controller' => $adminContentMediaApi, 'action' => 'mediaLibraryRenameApiV1', 'params' => ['contentId' => 'int', 'mediaId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/media/{mediaId}/attach', 'controller' => $adminContentMediaApi, 'action' => 'mediaAttachApiV1', 'params' => ['contentId' => 'int', 'mediaId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/thumbnail/detach', 'controller' => $adminContentMediaApi, 'action' => 'thumbnailDetachApiV1', 'params' => ['contentId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/thumbnail/{mediaId}/select', 'controller' => $adminContentMediaApi, 'action' => 'thumbnailSelectApiV1', 'params' => ['contentId' => 'int', 'mediaId' => 'int']],

    ['method' => 'GET', 'path' => 'admin/terms', 'controller' => $adminTerms, 'action' => 'list'],
    ['method' => 'GET', 'path' => 'admin/api/v1/terms', 'controller' => $adminTerms, 'action' => 'listApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/terms/{id}/delete', 'controller' => $adminTerms, 'action' => 'deleteApiV1', 'params' => ['id' => 'int']],
    ['method' => 'GET', 'path' => 'admin/api/v1/terms/suggest', 'controller' => $adminTerms, 'action' => 'suggest'],
    ['method' => 'GET', 'path' => 'admin/terms/add', 'controller' => $adminTerms, 'action' => 'addForm'],
    ['method' => 'POST', 'path' => 'admin/terms/add', 'controller' => $adminTerms, 'action' => 'addSubmit'],
    ['method' => 'GET', 'path' => 'admin/terms/edit', 'controller' => $adminTerms, 'action' => 'editForm'],
    ['method' => 'POST', 'path' => 'admin/terms/edit', 'controller' => $adminTerms, 'action' => 'editSubmit'],

    ['method' => 'GET', 'path' => 'admin/media', 'controller' => $adminMedia, 'action' => 'list'],
    ['method' => 'GET', 'path' => 'admin/api/v1/media', 'controller' => $adminMedia, 'action' => 'listApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/media/{id}/delete', 'controller' => $adminMedia, 'action' => 'deleteApiV1', 'params' => ['id' => 'int']],
    ['method' => 'GET', 'path' => 'admin/media/add', 'controller' => $adminMedia, 'action' => 'addForm'],
    ['method' => 'POST', 'path' => 'admin/media/add', 'controller' => $adminMedia, 'action' => 'addSubmit'],
    ['method' => 'GET', 'path' => 'admin/media/edit', 'controller' => $adminMedia, 'action' => 'editForm'],
    ['method' => 'POST', 'path' => 'admin/media/edit', 'controller' => $adminMedia, 'action' => 'editSubmit'],

    ['method' => 'GET', 'path' => 'admin/settings', 'controller' => $adminSettings, 'action' => 'form'],
    ['method' => 'POST', 'path' => 'admin/settings', 'controller' => $adminSettings, 'action' => 'submit'],
    ['method' => 'GET', 'path' => 'admin/logout', 'controller' => $admin, 'action' => 'logout'],
]);
