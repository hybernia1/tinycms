<?php
declare(strict_types=1);

namespace App\Controller\Admin\ContentMedia;

use App\Controller\Admin\BaseController;
use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Feature\MediaService;
use App\Service\Feature\UploadService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;

abstract class BaseContentMediaController extends BaseController
{
    public function __construct(
        AuthService $authService,
        protected ContentService $content,
        protected MediaService $media,
        protected UploadService $upload,
        FlashService $flash,
        CsrfService $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    protected function guardContentApiCsrf(callable $redirect): bool
    {
        return $this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false);
    }

    protected function requireManageableContent(int $contentId): ?array
    {
        $content = $this->content->find($contentId);
        if ($contentId <= 0 || $content === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return null;
        }

        if (!$this->canManageByAuthor($content)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return null;
        }

        return $content;
    }

    protected function requireManageableMedia(int $mediaId): ?array
    {
        $media = $this->media->find($mediaId);
        if ($mediaId <= 0 || $media === null) {
            $this->apiError('MEDIA_NOT_FOUND', I18n::t('media.not_found'), 404);
            return null;
        }

        if (!$this->canManageByAuthor($media)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return null;
        }

        return $media;
    }

    protected function mapLibraryItem(array $item): array
    {
        $createdAt = (string)($item['created'] ?? '');

        return [
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'can_edit' => $this->canManageByAuthor($item),
            'can_delete' => $this->canManageByAuthor($item),
            'preview_path' => $this->resolvePreviewPath($item),
            'path' => (string)($item['path'] ?? ''),
            'webp_path' => (string)($item['path_webp'] ?? ''),
            'created' => $createdAt,
            'created_label' => $this->formatDateTime($createdAt),
        ];
    }

    protected function matchesLibraryQuery(array $item, string $query): bool
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
}
