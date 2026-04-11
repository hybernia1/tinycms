<?php
declare(strict_types=1);

namespace App\Controller\Admin\ContentMedia;

use App\Service\Support\I18n;

final class LibraryApiController extends BaseContentMediaController
{
    public function mediaLibraryApiV1(callable $redirect, int $contentId): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        if ($this->requireManageableContent($contentId) === null) {
            return;
        }

        [$page, $perPage, $query] = $this->resolvePaginationQuery();
        $currentMediaId = (int)($_GET['current_media_id'] ?? 0);

        $pagination = $this->media->paginate($page, $perPage, $query);
        $items = array_map(fn(array $item): array => $this->mapLibraryItem($item), (array)($pagination['data'] ?? []));
        if ($currentMediaId > 0) {
            $currentItem = $this->media->find($currentMediaId);
            if ($currentItem !== null && $this->canManageByAuthor($currentItem) && $this->matchesLibraryQuery($currentItem, $query)) {
                $items = array_values(array_filter($items, static fn(array $row): bool => (int)($row['id'] ?? 0) !== $currentMediaId));
                array_unshift($items, $this->mapLibraryItem($currentItem));
            }
        }

        $this->apiOk($items, [
            'page' => (int)($pagination['page'] ?? 1),
            'per_page' => (int)($pagination['per_page'] ?? $perPage),
            'total_pages' => (int)($pagination['total_pages'] ?? 1),
            'query' => $query,
        ]);
    }

    public function mediaLibraryDeleteApiV1(callable $redirect, int $contentId, int $mediaId): void
    {
        if (!$this->guardContentApiCsrf($redirect)) {
            return;
        }

        $item = $this->requireManageableContent($contentId);
        if ($item === null) {
            return;
        }

        if ($mediaId <= 0) {
            $this->apiError('INVALID_MEDIA_ID', I18n::t('media.not_found'));
            return;
        }

        $media = $this->media->find($mediaId);
        if ($media !== null && !$this->canManageByAuthor($media)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if ($media === null || !$this->media->delete($mediaId)) {
            $this->apiError('DELETE_FAILED', I18n::t('media.delete_failed'));
            return;
        }

        if ((int)($item['thumbnail'] ?? 0) === $mediaId) {
            $this->content->setThumbnail($contentId, null);
        }

        $this->upload->deleteMediaFiles($media);
        $this->apiOk(['id' => $mediaId, 'content_id' => $contentId]);
    }

    public function mediaLibraryUploadApiV1(callable $redirect, int $contentId): void
    {
        if (!$this->guardContentApiCsrf($redirect)) {
            return;
        }

        if ($this->requireManageableContent($contentId) === null) {
            return;
        }

        $upload = $this->upload->uploadImage($_FILES['thumbnail'] ?? []);
        if (($upload['success'] ?? false) !== true) {
            $this->apiError('UPLOAD_FAILED', (string)($upload['error'] ?? I18n::t('upload.file_upload_failed')));
            return;
        }

        $author = (int)($this->authService->auth()->id() ?? 0);
        $data = (array)($upload['data'] ?? []);
        $mediaId = $this->media->create(
            $author > 0 ? $author : null,
            (string)($data['name'] ?? ''),
            (string)($data['path'] ?? ''),
            (string)($data['path_webp'] ?? '')
        );

        if ($mediaId <= 0) {
            $this->upload->deleteMediaFiles($data);
            $this->apiError('SAVE_FAILED', I18n::t('media.save_failed'));
            return;
        }

        $media = $this->media->find($mediaId);
        $previewPath = $media !== null ? $this->resolvePreviewPath($media) : (string)($data['path'] ?? '');
        $this->apiOk([
            'id' => $mediaId,
            'name' => (string)($media['name'] ?? ($data['name'] ?? '')),
            'preview_path' => $previewPath,
            'path' => (string)($media['path'] ?? ($data['path'] ?? '')),
            'webp_path' => (string)($media['path_webp'] ?? ($data['path_webp'] ?? '')),
            'created' => (string)($media['created'] ?? date('Y-m-d H:i:s')),
            'created_label' => $this->formatDateTime((string)($media['created'] ?? date('Y-m-d H:i:s'))),
        ]);
    }

    public function mediaLibraryRenameApiV1(callable $redirect, int $contentId, int $mediaId): void
    {
        if (!$this->guardContentApiCsrf($redirect)) {
            return;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        if ($contentId <= 0 || $mediaId <= 0 || $name === '') {
            $this->apiError('INVALID_DATA', I18n::t('common.invalid_data'));
            return;
        }

        if ($this->requireManageableContent($contentId) === null) {
            return;
        }

        $media = $this->requireManageableMedia($mediaId);
        if ($media === null) {
            return;
        }

        $result = $this->media->save([
            'name' => $name,
            'path' => (string)($media['path'] ?? ''),
            'path_webp' => (string)($media['path_webp'] ?? ''),
            'author' => (string)($media['author'] ?? ''),
        ], $mediaId);

        if (($result['success'] ?? false) !== true) {
            $this->apiError('RENAME_FAILED', (string)($result['errors']['name'] ?? I18n::t('media.rename_failed')));
            return;
        }

        $this->apiOk(['id' => $mediaId, 'name' => $name]);
    }
}
