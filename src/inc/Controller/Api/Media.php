<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;
use App\Service\Application\Auth;
use App\Service\Application\Media as MediaService;
use App\Service\Application\Upload as UploadService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class Media extends Admin
{
    public function __construct(
        Auth $authService,
        private MediaService $media,
        private UploadService $upload,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function listApiV1(): void
    {
        if (!$this->guardApiAdmin()) {
            return;
        }

        [$page, $perPage, $status, $query] = $this->resolveSimpleListQuery(['all', 'unassigned']);
        $pagination = $this->media->paginate($page, $perPage, $query, $status);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $currentPath = trim((string)($_GET['current_media_path'] ?? ''));
        if ($currentPath !== '') {
            $current = $this->media->findByPath($currentPath);
            if ($current !== null) {
                $currentId = (int)($current['id'] ?? 0);
                $items = array_values(array_filter($items, static fn(array $row): bool => (int)($row['id'] ?? 0) !== $currentId));
                array_unshift($items, $this->mapListItem($current));
            }
        }
        $statusCounts = $this->media->statusCounts();

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }

    public function addApiV1(): void
    {
        if (!$this->guardApiAdminCsrf()) {
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

    public function editApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $item = $this->media->find($id);
        if (!$this->requireEntity($item, 'NOT_FOUND', I18n::t('media.not_found'))) {
            return;
        }

        $input = array_merge($_POST, [
            'path' => (string)($item['path'] ?? ''),
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

    public function deleteApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $item = $this->media->find($id);
        if (!$this->requireEntity($item, 'NOT_FOUND', I18n::t('media.not_found'))) {
            return;
        }

        if (!$this->media->delete($id)) {
            $this->apiError('DELETE_FAILED', I18n::t('media.delete_failed'));
            return;
        }

        $this->upload->deleteMediaFiles($item);
        $this->apiOk([
            'id' => $id,
            'message' => I18n::t('media.deleted'),
            'redirect' => $this->buildPath('admin/media'),
        ]);
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
            'preview_path' => $previewPath,
            'author_name' => (string)($row['author_name'] ?? '—'),
            'created' => (string)($row['created'] ?? ''),
            'created_label' => $this->formatDateTime((string)($row['created'] ?? '')),
        ];
    }
}
