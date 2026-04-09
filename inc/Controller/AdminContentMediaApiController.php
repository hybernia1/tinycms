<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Feature\MediaService;
use App\Service\Feature\UploadService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;
use App\Service\Support\PaginationConfig;

final class AdminContentMediaApiController extends BaseAdminController
{
    public function __construct(
        AuthService $authService,
        private ContentService $content,
        private MediaService $media,
        private UploadService $upload,
        FlashService $flash,
        CsrfService $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function thumbnailDetachApiV1(callable $redirect, int $id): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $item = $this->requireManageableContent($id);
        if ($item === null) {
            return;
        }

        if (!$this->content->setThumbnail($id, null)) {
            $this->apiError('DETACH_FAILED', I18n::t('content.thumbnail_detach_failed'));
            return;
        }

        $this->apiOk(['id' => $id]);
    }

    public function thumbnailSelectApiV1(callable $redirect, int $contentId, int $mediaId): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $content = $this->requireManageableContent($contentId);
        if ($content === null) {
            return;
        }

        $media = $this->requireManageableMedia($mediaId);
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
        ]);
    }

    public function mediaLibraryApiV1(callable $redirect, int $contentId): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        if ($this->requireManageableContent($contentId) === null) {
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $defaultPerPage = PaginationConfig::perPage();
        $perPage = (int)($_GET['per_page'] ?? $defaultPerPage);
        $query = trim((string)($_GET['q'] ?? ''));
        $currentMediaId = (int)($_GET['current_media_id'] ?? 0);
        if (!in_array($perPage, PaginationConfig::allowed(), true)) {
            $perPage = $defaultPerPage;
        }

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

    public function mediaLibraryDeleteApiV1(callable $redirect, int $contentId, int $mediaId): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $item = $this->requireDeletableContent($contentId);
        if ($item === null) {
            return;
        }

        if ($mediaId <= 0) {
            $this->apiError('INVALID_MEDIA_ID', I18n::t('media.not_found'));
            return;
        }

        $media = $this->media->find($mediaId);
        if ($media !== null && !$this->canDeleteMedia($media)) {
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
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
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
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
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

    public function attachmentAttachApiV1(callable $redirect, int $contentId, int $mediaId): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        if ($contentId <= 0 || $mediaId <= 0) {
            $this->apiError('INVALID_DATA', I18n::t('common.invalid_data'));
            return;
        }

        if ($this->requireManageableContent($contentId) === null) {
            return;
        }

        if (!$this->content->attachMedia($contentId, $mediaId)) {
            $this->apiError('ATTACH_FAILED', I18n::t('content.attachment_attach_failed'));
            return;
        }

        $this->apiOk(['content_id' => $contentId, 'media_id' => $mediaId]);
    }

    private function requireManageableContent(int $contentId): ?array
    {
        $content = $this->content->find($contentId);
        if ($contentId <= 0 || $content === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return null;
        }

        if (!$this->canManageContent($content)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return null;
        }

        return $content;
    }

    private function requireDeletableContent(int $contentId): ?array
    {
        $content = $this->content->find($contentId);
        if ($contentId <= 0 || $content === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return null;
        }

        if (!$this->canManageContent($content)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return null;
        }

        return $content;
    }

    private function requireManageableMedia(int $mediaId): ?array
    {
        $media = $this->media->find($mediaId);
        if ($mediaId <= 0 || $media === null) {
            $this->apiError('MEDIA_NOT_FOUND', I18n::t('media.not_found'), 404);
            return null;
        }

        if (!$this->canDeleteMedia($media)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return null;
        }

        return $media;
    }

    private function canManageContent(array $item): bool
    {
        if (!$this->isEditor()) {
            return true;
        }

        return (int)($item['author'] ?? 0) === $this->currentUserId();
    }

    private function canDeleteMedia(array $item): bool
    {
        if (!$this->isEditor()) {
            return true;
        }

        return (int)($item['author'] ?? 0) === $this->currentUserId();
    }

    private function mapLibraryItem(array $item): array
    {
        $createdAt = (string)($item['created'] ?? '');
        return [
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'can_edit' => $this->canDeleteMedia($item),
            'can_delete' => $this->canDeleteMedia($item),
            'preview_path' => $this->resolvePreviewPath($item),
            'path' => (string)($item['path'] ?? ''),
            'webp_path' => (string)($item['path_webp'] ?? ''),
            'created' => $createdAt,
            'created_label' => $this->formatDateTime($createdAt),
        ];
    }

    private function resolvePreviewPath(array $item): string
    {
        $previewPath = trim((string)($item['path_webp'] ?? ''));
        if ($previewPath !== '') {
            return (string)(preg_replace('/\.webp$/i', $this->thumbnailSuffix(), $previewPath) ?? $previewPath);
        }

        return trim((string)($item['path'] ?? ''));
    }

    private function thumbnailSuffix(): string
    {
        $suffix = '_100x100.webp';
        if (defined('MEDIA_THUMB_VARIANTS') && is_array(MEDIA_THUMB_VARIANTS)) {
            $firstVariant = MEDIA_THUMB_VARIANTS[0] ?? null;
            if (is_array($firstVariant) && !empty($firstVariant['suffix'])) {
                $suffix = (string)$firstVariant['suffix'];
            }
        }

        return $suffix;
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
            (string)($item['path_webp'] ?? ''),
        ];

        foreach ($haystacks as $value) {
            if (mb_strpos(mb_strtolower($value, 'UTF-8'), $needle, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }

    private function formatDateTime(string $value): string
    {
        $stamp = $value !== '' ? strtotime($value) : false;
        if ($stamp === false) {
            return '';
        }

        return date(APP_DATETIME_FORMAT, $stamp);
    }
}
