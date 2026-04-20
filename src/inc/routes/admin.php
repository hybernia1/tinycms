<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}


register_routes($router, $redirect, [
    ['method' => 'GET', 'path' => 'auth/login', 'controller' => $admin, 'action' => 'loginForm'],
    ['method' => 'GET', 'path' => 'auth/register', 'controller' => $admin, 'action' => 'registerForm'],
    ['method' => 'GET', 'path' => 'auth/lost', 'controller' => $admin, 'action' => 'lostForm'],
    ['method' => 'GET', 'path' => 'admin/api/v1/heartbeat', 'controller' => $apiSessions, 'action' => 'heartbeatApiV1', 'with_redirect' => false],
    ['method' => 'POST', 'path' => 'admin/api/v1/auth/login', 'controller' => $apiSessions, 'action' => 'loginApiV1', 'with_redirect' => false],
    ['method' => 'POST', 'path' => 'admin/api/v1/auth/register', 'controller' => $apiSessions, 'action' => 'registerApiV1', 'with_redirect' => false],
    ['method' => 'POST', 'path' => 'admin/api/v1/auth/lost', 'controller' => $apiSessions, 'action' => 'lostRequestApiV1', 'with_redirect' => false],
    ['method' => 'POST', 'path' => 'admin/api/v1/auth/lost/reset', 'controller' => $apiSessions, 'action' => 'lostResetApiV1', 'with_redirect' => false],
    ['method' => 'POST', 'path' => 'admin/api/v1/comments/add', 'controller' => $apiComment, 'action' => 'addApiV1', 'with_redirect' => false],
    ['method' => 'GET', 'path' => 'admin', 'controller' => $admin, 'action' => 'index'],
    ['method' => 'GET', 'path' => 'admin/dashboard', 'controller' => $admin, 'action' => 'dashboard'],

    ['method' => 'GET', 'path' => 'admin/users', 'controller' => $adminUsers, 'action' => 'list'],
    ['method' => 'GET', 'path' => 'admin/api/v1/users', 'controller' => $apiUser, 'action' => 'listApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/users/add', 'controller' => $apiUser, 'action' => 'addApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/users/{id}/edit', 'controller' => $apiUser, 'action' => 'editApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/users/{id}/delete', 'controller' => $apiUser, 'action' => 'deleteApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/users/{id}/suspend', 'controller' => $apiUser, 'action' => 'suspendApiV1', 'params' => ['id' => 'int']],
    ['method' => 'GET', 'path' => 'admin/users/add', 'controller' => $adminUsers, 'action' => 'addForm'],
    ['method' => 'GET', 'path' => 'admin/users/edit', 'controller' => $adminUsers, 'action' => 'editForm'],

    ['method' => 'GET', 'path' => 'admin/content', 'controller' => $adminContent, 'action' => 'list'],
    ['method' => 'GET', 'path' => 'admin/api/v1/content', 'controller' => $apiContent, 'action' => 'listApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{id}/delete', 'controller' => $apiContent, 'action' => 'deleteApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{id}/restore', 'controller' => $apiContent, 'action' => 'restoreApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{id}/status', 'controller' => $apiContent, 'action' => 'statusApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/add', 'controller' => $apiContent, 'action' => 'addApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{id}/edit', 'controller' => $apiContent, 'action' => 'editApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/draft/init', 'controller' => $apiContent, 'action' => 'draftInitApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/autosave', 'controller' => $apiContent, 'action' => 'autosaveApiV1'],
    ['method' => 'GET', 'path' => 'admin/api/v1/content/link-title', 'controller' => $apiContent, 'action' => 'linkTitleApiV1'],
    ['method' => 'GET', 'path' => 'admin/content/add', 'controller' => $adminContent, 'action' => 'addForm'],
    ['method' => 'GET', 'path' => 'admin/content/edit', 'controller' => $adminContent, 'action' => 'editForm'],
    ['method' => 'GET', 'path' => 'admin/api/v1/content/{contentId}/media', 'controller' => $apiContentMedia, 'action' => 'mediaLibraryApiV1', 'params' => ['contentId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/media/upload', 'controller' => $apiContentMedia, 'action' => 'mediaLibraryUploadApiV1', 'params' => ['contentId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/media/{mediaId}/delete', 'controller' => $apiContentMedia, 'action' => 'mediaLibraryDeleteApiV1', 'params' => ['contentId' => 'int', 'mediaId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/media/{mediaId}/rename', 'controller' => $apiContentMedia, 'action' => 'mediaLibraryRenameApiV1', 'params' => ['contentId' => 'int', 'mediaId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/media/{mediaId}/attach', 'controller' => $apiContentMedia, 'action' => 'mediaAttachApiV1', 'params' => ['contentId' => 'int', 'mediaId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/thumbnail/detach', 'controller' => $apiContentMedia, 'action' => 'thumbnailDetachApiV1', 'params' => ['contentId' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/content/{contentId}/thumbnail/{mediaId}/select', 'controller' => $apiContentMedia, 'action' => 'thumbnailSelectApiV1', 'params' => ['contentId' => 'int', 'mediaId' => 'int']],

    ['method' => 'GET', 'path' => 'admin/comments', 'controller' => $adminComments, 'action' => 'list'],
    ['method' => 'GET', 'path' => 'admin/comments/edit', 'controller' => $adminComments, 'action' => 'editForm'],
    ['method' => 'GET', 'path' => 'admin/api/v1/comments', 'controller' => $apiComment, 'action' => 'listApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/comments/{id}/edit', 'controller' => $apiComment, 'action' => 'editApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/comments/{id}/status', 'controller' => $apiComment, 'action' => 'statusApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/comments/{id}/delete', 'controller' => $apiComment, 'action' => 'deleteApiV1', 'params' => ['id' => 'int']],

    ['method' => 'GET', 'path' => 'admin/terms', 'controller' => $adminTerms, 'action' => 'list'],
    ['method' => 'GET', 'path' => 'admin/api/v1/terms', 'controller' => $apiTerm, 'action' => 'listApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/terms/add', 'controller' => $apiTerm, 'action' => 'addApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/terms/{id}/edit', 'controller' => $apiTerm, 'action' => 'editApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/terms/{id}/delete', 'controller' => $apiTerm, 'action' => 'deleteApiV1', 'params' => ['id' => 'int']],
    ['method' => 'GET', 'path' => 'admin/api/v1/terms/search', 'controller' => $apiTerm, 'action' => 'searchApiV1'],
    ['method' => 'GET', 'path' => 'admin/terms/add', 'controller' => $adminTerms, 'action' => 'addForm'],
    ['method' => 'GET', 'path' => 'admin/terms/edit', 'controller' => $adminTerms, 'action' => 'editForm'],

    ['method' => 'GET', 'path' => 'admin/media', 'controller' => $adminMedia, 'action' => 'list'],
    ['method' => 'GET', 'path' => 'admin/api/v1/media', 'controller' => $apiMedia, 'action' => 'listApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/media/add', 'controller' => $apiMedia, 'action' => 'addApiV1'],
    ['method' => 'POST', 'path' => 'admin/api/v1/media/{id}/edit', 'controller' => $apiMedia, 'action' => 'editApiV1', 'params' => ['id' => 'int']],
    ['method' => 'POST', 'path' => 'admin/api/v1/media/{id}/delete', 'controller' => $apiMedia, 'action' => 'deleteApiV1', 'params' => ['id' => 'int']],
    ['method' => 'GET', 'path' => 'admin/media/add', 'controller' => $adminMedia, 'action' => 'addForm'],
    ['method' => 'GET', 'path' => 'admin/media/edit', 'controller' => $adminMedia, 'action' => 'editForm'],

    ['method' => 'GET', 'path' => 'admin/settings', 'controller' => $adminSettings, 'action' => 'form'],
    ['method' => 'POST', 'path' => 'admin/api/v1/settings', 'controller' => $apiSettings, 'action' => 'submitApiV1'],
    ['method' => 'GET', 'path' => 'admin/logout', 'controller' => $admin, 'action' => 'logout'],
]);
