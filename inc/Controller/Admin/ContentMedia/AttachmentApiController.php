<?php
declare(strict_types=1);

namespace App\Controller\Admin\ContentMedia;

use App\Service\Support\I18n;

final class AttachmentApiController extends BaseContentMediaController
{
    public function attachmentAttachApiV1(callable $redirect, int $contentId, int $mediaId): void
    {
        if (!$this->guardContentApiCsrf($redirect)) {
            return;
        }

        if ($contentId <= 0 || $mediaId <= 0) {
            $this->apiError('INVALID_DATA', I18n::t('common.invalid_data'));
            return;
        }

        if ($this->requireManageableContent($contentId) === null) {
            return;
        }

        if ($this->requireManageableMedia($mediaId) === null) {
            return;
        }

        if (!$this->content->attachMedia($contentId, $mediaId)) {
            $this->apiError('ATTACH_FAILED', I18n::t('content.attachment_attach_failed'));
            return;
        }

        $this->apiOk(['content_id' => $contentId, 'media_id' => $mediaId]);
    }
}
