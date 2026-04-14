<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\ContentMedia as AdminContentMedia;

final class ContentMedia
{
    public function __construct(private AdminContentMedia $controller)
    {
    }

    public function thumbnailDetachApiV1(callable $redirect, int $id): void { $this->controller->thumbnailDetachApiV1($redirect, $id); }
    public function thumbnailSelectApiV1(callable $redirect, int $contentId, int $mediaId): void { $this->controller->thumbnailSelectApiV1($redirect, $contentId, $mediaId); }
    public function mediaLibraryApiV1(callable $redirect, int $contentId): void { $this->controller->mediaLibraryApiV1($redirect, $contentId); }
    public function mediaLibraryDeleteApiV1(callable $redirect, int $contentId, int $mediaId): void { $this->controller->mediaLibraryDeleteApiV1($redirect, $contentId, $mediaId); }
    public function mediaLibraryUploadApiV1(callable $redirect, int $contentId): void { $this->controller->mediaLibraryUploadApiV1($redirect, $contentId); }
    public function mediaLibraryRenameApiV1(callable $redirect, int $contentId, int $mediaId): void { $this->controller->mediaLibraryRenameApiV1($redirect, $contentId, $mediaId); }
    public function mediaAttachApiV1(callable $redirect, int $contentId, int $mediaId): void { $this->controller->mediaAttachApiV1($redirect, $contentId, $mediaId); }
}
