<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Content;
use App\Service\Application\Media;
use App\Service\Application\Upload;
use App\Service\Support\Media as MediaPath;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class ContentMedia extends Admin
{
    public function __construct(
        Auth $authService,
        private Content $content,
        private Media $media,
        private Upload $upload,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function thumbnailDetachApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $item = $this->requireExistingContent($id);
        if ($item === null) {
            return;
        }

        if (!$this->content->setThumbnail($id, null)) {
            $this->apiError('DETACH_FAILED', I18n::t('content.thumbnail_detach_failed'));
            return;
        }

        $this->apiOk([
            'id' => $id,
            'message' => I18n::t('media.detached'),
        ]);
    }

    public function thumbnailSelectApiV1(int $contentId, int $mediaId): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $content = $this->requireExistingContent($contentId);
        if ($content === null) {
            return;
        }

        $media = $this->requireExistingMedia($mediaId);
        if ($media === null) {
            return;
        }

        if (!$this->content->setThumbnail($contentId, $mediaId)) {
            $this->apiError('SELECT_FAILED', I18n::t('content.thumbnail_select_failed'));
            return;
        }

        $this->apiOk([
            'content_id' => $contentId,
            'media_id' => $mediaId,
            'media' => $this->mapLibraryItem($media),
            'message' => I18n::t('content.updated'),
        ]);
    }

    public function mediaLibraryApiV1(int $contentId): void
    {
        if (!$this->guardApiAdmin()) {
            return;
        }

        if ($this->requireExistingContent($contentId) === null) {
            return;
        }

        [$page, $perPage, $query] = $this->resolvePaginationQuery();
        $currentMediaId = (int)($_GET['current_media_id'] ?? 0);

        $pagination = $this->media->paginate($page, $perPage, $query);
        $items = array_map(fn(array $item): array => $this->mapLibraryItem($item), (array)($pagination['data'] ?? []));
        if ($currentMediaId > 0) {
            $currentItem = $this->media->find($currentMediaId);
            if ($currentItem !== null && $this->matchesLibraryQuery($currentItem, $query)) {
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

    public function mediaLibraryDeleteApiV1(int $contentId, int $mediaId): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $item = $this->requireExistingContent($contentId);
        if ($item === null) {
            return;
        }

        $media = $this->requireExistingMedia($mediaId);
        if ($media === null || !$this->media->delete($mediaId)) {
            $this->apiError('DELETE_FAILED', I18n::t('media.delete_failed'));
            return;
        }

        if ((int)($item['thumbnail'] ?? 0) === $mediaId) {
            $this->content->setThumbnail($contentId, null);
        }

        $this->upload->deleteMediaFiles($media);
        $this->apiOk([
            'id' => $mediaId,
            'content_id' => $contentId,
            'message' => I18n::t('media.deleted'),
        ]);
    }

    public function mediaLibraryUploadApiV1(int $contentId): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        if ($this->requireExistingContent($contentId) === null) {
            return;
        }

        $upload = $this->upload->uploadImage($_FILES['thumbnail'] ?? []);
        if (($upload['success'] ?? false) !== true) {
            $this->apiError('UPLOAD_FAILED', (string)($upload['error'] ?? I18n::t('upload.file_upload_failed')));
            return;
        }

        $author = (int)($this->authService->auth()->id() ?? 0);
        $data = (array)($upload['data'] ?? []);
        $result = $this->media->save([
            'author' => $author > 0 ? (string)$author : '',
            'name' => (string)($data['name'] ?? ''),
            'path' => (string)($data['path'] ?? ''),
        ]);
        $mediaId = (int)($result['id'] ?? 0);

        if (($result['success'] ?? false) !== true || $mediaId <= 0) {
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
            'webp_path' => MediaPath::bySize((string)($media['path'] ?? ($data['path'] ?? '')), 'webp'),
            'path' => (string)($media['path'] ?? ($data['path'] ?? '')),
            'created' => (string)($media['created'] ?? date('Y-m-d H:i:s')),
            'created_label' => $this->formatDateTime((string)($media['created'] ?? date('Y-m-d H:i:s'))),
            'message' => I18n::t('media.uploaded'),
        ]);
    }

    public function mediaLibraryRenameApiV1(int $contentId, int $mediaId): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $this->apiError('INVALID_DATA', I18n::t('common.invalid_data'));
            return;
        }

        if ($this->requireExistingContent($contentId) === null) {
            return;
        }

        $media = $this->requireExistingMedia($mediaId);
        if ($media === null) {
            return;
        }

        $result = $this->media->save([
            'name' => $name,
            'path' => (string)($media['path'] ?? ''),
            'author' => (string)($media['author'] ?? ''),
        ], $mediaId);

        if (($result['success'] ?? false) !== true) {
            $this->apiError('RENAME_FAILED', (string)($result['errors']['name'] ?? I18n::t('media.rename_failed')));
            return;
        }

        $this->apiOk([
            'id' => $mediaId,
            'name' => $name,
            'message' => I18n::t('media.rename_saved'),
        ]);
    }

    public function mediaAttachApiV1(int $contentId, int $mediaId): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        if ($this->requireExistingContent($contentId) === null) {
            return;
        }

        if ($this->requireExistingMedia($mediaId) === null) {
            return;
        }

        if (!$this->content->attachMedia($contentId, $mediaId)) {
            $this->apiError('ATTACH_FAILED', I18n::t('content.media_attach_failed'));
            return;
        }

        $this->apiOk([
            'content_id' => $contentId,
            'media_id' => $mediaId,
            'message' => I18n::t('content.updated'),
        ]);
    }

    private function requireExistingContent(int $contentId): ?array
    {
        $content = $this->content->find($contentId);
        if (!$this->requireEntity($content, 'NOT_FOUND', I18n::t('content.not_found'))) {
            return null;
        }

        return $content;
    }

    private function requireExistingMedia(int $mediaId): ?array
    {
        $media = $this->media->find($mediaId);
        if (!$this->requireEntity($media, 'NOT_FOUND', I18n::t('media.not_found'))) {
            return null;
        }

        return $media;
    }

    private function mapLibraryItem(array $item): array
    {
        $createdAt = (string)($item['created'] ?? '');
        return [
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'can_edit' => true,
            'can_delete' => true,
            'preview_path' => $this->resolvePreviewPath($item),
            'webp_path' => MediaPath::bySize((string)($item['path'] ?? ''), 'webp'),
            'path' => (string)($item['path'] ?? ''),
            'created' => $createdAt,
            'created_label' => $this->formatDateTime($createdAt),
        ];
    }

    private function matchesLibraryQuery(array $item, string $query): bool
    {
        $needle = mb_strtolower(trim($query), 'UTF-8');
        if ($needle === '') {
            return true;
        }

        $haystacks = [
            (string)($item['name'] ?? ''),
            (string)($item['path'] ?? ''),
        ];

        foreach ($haystacks as $value) {
            if (mb_strpos(mb_strtolower($value, 'UTF-8'), $needle, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }

}
