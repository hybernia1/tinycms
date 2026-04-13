<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Media as MediaService;
use App\Service\Application\Upload as UploadService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\View\AdminView;

final class Media extends BaseAdmin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private MediaService $media,
        private UploadService $upload,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function list(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query] = $this->resolveListQuery();
        $pagination = $this->media->paginate($page, $perPage, $query, $status);
        $statusCounts = $this->media->statusCounts();
        $this->pages->adminMediaList($pagination, $status, $query, $statusCounts);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query] = $this->resolveListQuery();
        $pagination = $this->media->paginate($page, $perPage, $query, $status);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->media->statusCounts();

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'path' => '', 'path_webp' => '', 'author' => (int)($this->authService->auth()->id() ?? 0)];
        $this->pages->adminMediaForm('add', $fallback, [], $this->media->authorOptions(), []);
    }

    public function addApiV1(callable $redirect): void
    {
        if (!$this->guardApiAdminCsrf(I18n::t('common.invalid_csrf'))) {
            return;
        }

        if (!$this->hasUpload('file')) {
            $this->apiError('FILE_REQUIRED', I18n::t('media.file_required'));
            return;
        }

        $upload = $this->upload->uploadImage($_FILES['file'] ?? []);
        if (($upload['success'] ?? false) !== true) {
            $this->apiError('UPLOAD_FAILED', (string)($upload['error'] ?? I18n::t('upload.file_upload_failed')));
            return;
        }

        $uploadData = (array)($upload['data'] ?? []);
        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $input = array_merge($_POST, [
            'name' => trim((string)($_POST['name'] ?? '')) !== '' ? (string)$_POST['name'] : (string)($uploadData['name'] ?? ''),
            'path' => (string)($uploadData['path'] ?? ''),
            'path_webp' => (string)($uploadData['path_webp'] ?? ''),
            'author' => trim((string)($_POST['author'] ?? '')) !== '' ? (string)$_POST['author'] : ($authorId > 0 ? (string)$authorId : ''),
        ]);
        $result = $this->media->save($input);

        if (($result['success'] ?? false) === true) {
            $newId = (int)($result['id'] ?? 0);
            $this->apiOk([
                'redirect' => $newId > 0 ? $this->buildEditPath('admin/media', $newId) : $this->buildPath('admin/media'),
                'message' => I18n::t('media.created'),
            ]);
            return;
        }

        $this->upload->deleteMediaFiles($uploadData);
        $this->apiError('SAVE_FAILED', I18n::t('media.save_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->media->find($id);

        if ($item === null) {
            $this->flash->add('info', I18n::t('media.not_found'));
            $redirect('admin/media');
            return;
        }

        $navigation = $this->media->editNavigation($id);
        $this->pages->adminMediaForm('edit', $item, [], $this->media->authorOptions(), $this->media->thumbnailUsages($id), $navigation);
    }

    public function editApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrf(I18n::t('common.invalid_csrf'))) {
            return;
        }

        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('media.invalid_id'));
            return;
        }

        $item = $this->media->find($id);
        if ($item === null) {
            $this->apiError('NOT_FOUND', I18n::t('media.not_found'), 404);
            return;
        }

        $input = array_merge($_POST, [
            'path' => (string)($item['path'] ?? ''),
            'path_webp' => (string)($item['path_webp'] ?? ''),
        ]);

        $result = $this->media->save($input, $id);

        if (($result['success'] ?? false) === true) {
            $this->apiOk([
                'redirect' => $this->buildEditPath('admin/media', $id),
                'message' => I18n::t('media.updated'),
            ]);
            return;
        }

        $this->apiError('UPDATE_FAILED', I18n::t('media.update_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrf(I18n::t('common.invalid_csrf'))) {
            return;
        }

        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('media.invalid_id'));
            return;
        }

        $item = $this->media->find($id);
        if ($item === null) {
            $this->apiError('NOT_FOUND', I18n::t('media.not_found'), 404);
            return;
        }

        if (!$this->media->delete($id)) {
            $this->apiError('DELETE_FAILED', I18n::t('media.delete_failed'));
            return;
        }

        $this->upload->deleteMediaFiles($item);
        $this->apiOk(['id' => $id]);
    }

    private function resolveListQuery(): array
    {
        [$page, $perPage, $query] = $this->resolvePaginationQuery();
        $status = $this->resolveStatusFilter(['all', 'unassigned']);

        return [$page, $perPage, $status, $query];
    }

    private function mapListItem(array $row): array
    {
        $previewPath = $this->resolvePreviewPath($row);
        if ($previewPath !== '' && !str_starts_with($previewPath, '/')) {
            $previewPath = '/' . ltrim($previewPath, '/');
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'can_edit' => true,
            'can_delete' => true,
            'path' => (string)($row['path'] ?? ''),
            'path_webp' => (string)($row['path_webp'] ?? ''),
            'preview_path' => $previewPath,
            'author_name' => (string)($row['author_name'] ?? '—'),
            'created' => (string)($row['created'] ?? ''),
            'created_label' => $this->formatDateTime((string)($row['created'] ?? '')),
        ];
    }

}
