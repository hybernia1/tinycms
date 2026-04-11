<?php
declare(strict_types=1);

namespace App\Controller\Admin\ContentMedia;

use App\Service\Support\I18n;

final class ThumbnailApiController extends BaseContentMediaController
{
    public function thumbnailDetachApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardContentApiCsrf($redirect)) {
            return;
        }

        if ($this->requireManageableContent($id) === null) {
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
        if (!$this->guardContentApiCsrf($redirect)) {
            return;
        }

        if ($this->requireManageableContent($contentId) === null) {
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
}
