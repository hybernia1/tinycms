<?php
declare(strict_types=1);

namespace App\Service\Support;

final class MediaPath
{
    public static function bySize(string $path, string $size = 'origin'): string
    {
        $normalized = trim($path);
        if ($normalized === '') {
            return '';
        }

        return match (strtolower(trim($size))) {
            'small' => ThumbnailVariants::smallPath($normalized),
            'medium' => ThumbnailVariants::thumbnailPath(ThumbnailVariants::webpPath($normalized), ThumbnailVariants::MEDIUM_SUFFIX),
            'webp' => ThumbnailVariants::webpPath($normalized),
            default => $normalized,
        };
    }
}
