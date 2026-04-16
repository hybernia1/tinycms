<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Support\Slugger;
use App\Service\Support\I18n;
use App\Service\Support\ThumbnailVariants;

final class Upload
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const SITE_IMAGE_EXTENSIONS = ['png', 'ico', 'svg', 'jpg', 'jpeg', 'webp', 'gif'];
    private const MAX_WEBP_WIDTH = 1024;
    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    private const SITE_IMAGE_MIME_TO_EXTENSION = [
        'image/png' => 'png',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
        'image/svg+xml' => 'svg',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(
        private string $rootPath,
        private Slugger $slugger
    ) {
        $this->rootPath = rtrim($rootPath, '/');
    }

    public function uploadImage(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => I18n::t('upload.file_upload_failed')];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['success' => false, 'error' => I18n::t('upload.invalid_upload')];
        }

        $originalName = trim((string)($file['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = $this->detectMime($tmpPath);

        if (!isset(self::MIME_TO_EXTENSION[$mime])) {
            return ['success' => false, 'error' => I18n::t('upload.unsupported_file_type')];
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = self::MIME_TO_EXTENSION[$mime];
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return ['success' => false, 'error' => I18n::t('upload.unsupported_file_extension')];
        }

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $slug = $this->slugger->slug($baseName !== '' ? $baseName : 'soubor', random_int(1000, 999999));
        $subdir = 'uploads/' . date('Y') . '/' . date('m');
        $absDir = $this->rootPath . '/' . $subdir;

        if (!is_dir($absDir) && !mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            return ['success' => false, 'error' => I18n::t('upload.target_dir_create_failed')];
        }

        $fileRel = $subdir . '/' . $slug . '.' . $extension;
        $fileAbs = $this->rootPath . '/' . $fileRel;

        if (!move_uploaded_file($tmpPath, $fileAbs)) {
            return ['success' => false, 'error' => I18n::t('upload.save_to_disk_failed')];
        }

        $webpRel = ThumbnailVariants::webpPath($fileRel);
        $webpAbs = $this->rootPath . '/' . $webpRel;

        if (!$this->createWebp($fileAbs, $webpAbs, $mime, self::MAX_WEBP_WIDTH)) {
            if ($fileAbs !== $webpAbs) {
                @unlink($fileAbs);
            }
            return ['success' => false, 'error' => I18n::t('upload.webp_create_failed')];
        }

        $createdThumbs = [];
        foreach ($this->thumbVariants() as $variant) {
            $thumbRel = $this->thumbnailPath($webpRel, (string)$variant['suffix']);
            $thumbAbs = $this->rootPath . '/' . $thumbRel;
            if ($this->createThumbnailWebp($webpAbs, $thumbAbs, $variant)) {
                $createdThumbs[] = $thumbAbs;
                continue;
            }

            foreach ($createdThumbs as $createdThumb) {
                @unlink($createdThumb);
            }
            if ($fileAbs !== $webpAbs) {
                @unlink($fileAbs);
            }
            @unlink($webpAbs);
            return ['success' => false, 'error' => I18n::t('upload.thumbnail_create_failed')];
        }

        return [
            'success' => true,
            'data' => [
                'name' => trim($baseName) !== '' ? trim($baseName) : (string)pathinfo($fileRel, PATHINFO_FILENAME),
                'path' => $fileRel,
            ],
        ];
    }

    public function deleteMediaFiles(array $media): void
    {
        $path = trim((string)($media['path'] ?? ''));
        $webpPath = ThumbnailVariants::webpPath($path);
        $paths = array_filter([
            $path,
            $webpPath,
        ]);

        if ($webpPath !== '') {
            foreach ($this->thumbVariants() as $variant) {
                $paths[] = $this->thumbnailPath($webpPath, (string)$variant['suffix']);
            }
        }

        $paths = array_unique($paths);

        foreach ($paths as $path) {
            $absolute = $this->rootPath . '/' . ltrim($path, '/');
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }
    }

    public function uploadFavicon(array $file): array
    {
        return $this->uploadSiteImage($file, 'favicon');
    }

    public function uploadLogo(array $file): array
    {
        return $this->uploadSiteImage($file, 'logo');
    }

    public static function imageAccept(): string
    {
        return self::buildAccept(self::ALLOWED_EXTENSIONS, self::MIME_TO_EXTENSION);
    }

    public static function imageExtensionsLabel(): string
    {
        return self::buildExtensionsLabel(self::ALLOWED_EXTENSIONS);
    }

    public static function siteImageAccept(): string
    {
        return self::buildAccept(self::SITE_IMAGE_EXTENSIONS, self::SITE_IMAGE_MIME_TO_EXTENSION);
    }

    public static function siteImageExtensionsLabel(): string
    {
        return self::buildExtensionsLabel(self::SITE_IMAGE_EXTENSIONS);
    }

    public function deleteRelativeFile(string $path): void
    {
        $trimmedPath = trim($path);
        if ($trimmedPath === '') {
            return;
        }

        $absolute = $this->rootPath . '/' . ltrim($trimmedPath, '/');
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    private function uploadSiteImage(array $file, string $prefix): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => I18n::t('upload.file_upload_failed')];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['success' => false, 'error' => I18n::t('upload.invalid_upload')];
        }

        $mime = $this->detectMime($tmpPath);
        $extension = self::SITE_IMAGE_MIME_TO_EXTENSION[$mime] ?? '';
        if ($extension === '') {
            return ['success' => false, 'error' => I18n::t('upload.unsupported_file_type')];
        }

        $subdir = 'uploads/img';
        $absDir = $this->rootPath . '/' . $subdir;
        if (!is_dir($absDir) && !mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            return ['success' => false, 'error' => I18n::t('upload.target_dir_create_failed')];
        }

        $name = $prefix . '-' . date('YmdHis') . '-' . random_int(1000, 9999) . '.' . $extension;
        $path = $subdir . '/' . $name;
        $absolute = $this->rootPath . '/' . $path;

        if (!move_uploaded_file($tmpPath, $absolute)) {
            return ['success' => false, 'error' => I18n::t('upload.save_to_disk_failed')];
        }

        return ['success' => true, 'data' => ['path' => $path]];
    }

    private function detectMime(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return '';
        }

        $mime = (string)finfo_file($finfo, $path);
        finfo_close($finfo);
        return $mime;
    }

    private static function buildAccept(array $extensions, array $mimeToExtension): string
    {
        $result = [];

        foreach ($extensions as $extension) {
            $normalized = strtolower(trim((string)$extension));
            if ($normalized === '') {
                continue;
            }
            $result[] = '.' . ltrim($normalized, '.');
        }

        foreach (array_keys($mimeToExtension) as $mime) {
            $normalized = strtolower(trim((string)$mime));
            if ($normalized === '') {
                continue;
            }
            $result[] = $normalized;
        }

        return implode(',', array_values(array_unique($result)));
    }

    private static function buildExtensionsLabel(array $extensions): string
    {
        $result = [];
        foreach ($extensions as $extension) {
            $normalized = strtoupper(trim((string)$extension));
            if ($normalized === '') {
                continue;
            }
            $result[] = $normalized;
        }

        return implode(', ', array_values(array_unique($result)));
    }

    private function createWebp(string $sourcePath, string $destinationPath, string $mime, int $maxWidth): bool
    {
        if (!function_exists('imagewebp')) {
            return false;
        }

        $image = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($sourcePath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($sourcePath) : false,
            'image/gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($sourcePath) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($image === false) {
            return false;
        }

        $prepared = $this->resizeToMaxWidth($image, $maxWidth);
        if ($prepared === false) {
            imagedestroy($image);
            return false;
        }

        $result = @imagewebp($prepared, $destinationPath, 85);
        if ($prepared !== $image) {
            imagedestroy($prepared);
        }
        imagedestroy($image);

        return $result;
    }

    private function createThumbnailWebp(string $sourceWebp, string $destinationPath, array $variant): bool
    {
        if (!function_exists('imagecreatefromwebp') || !function_exists('imagewebp')) {
            return false;
        }

        $image = @imagecreatefromwebp($sourceWebp);
        if ($image === false) {
            return false;
        }

        $width = (int)imagesx($image);
        $height = (int)imagesy($image);
        if ($width <= 0 || $height <= 0) {
            imagedestroy($image);
            return false;
        }

        $mode = (string)($variant['mode'] ?? 'crop');
        $targetWidth = max(1, (int)($variant['width'] ?? 100));
        $targetHeight = max(1, (int)($variant['height'] ?? $targetWidth));

        if ($mode === 'fit') {
            $targetWidth = min($targetWidth, $width);
            $targetHeight = (int)max(1, round(($height / $width) * $targetWidth));
            $srcX = 0;
            $srcY = 0;
            $srcWidth = $width;
            $srcHeight = $height;
        } else {
            $srcSize = min($width, $height);
            $srcX = (int)floor(($width - $srcSize) / 2);
            $srcY = (int)floor(($height - $srcSize) / 2);
            $srcWidth = $srcSize;
            $srcHeight = $srcSize;
        }

        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($thumb === false) {
            imagedestroy($image);
            return false;
        }

        imagealphablending($thumb, true);
        imagesavealpha($thumb, true);

        $ok = imagecopyresampled($thumb, $image, 0, 0, $srcX, $srcY, $targetWidth, $targetHeight, $srcWidth, $srcHeight);
        $saved = $ok ? @imagewebp($thumb, $destinationPath, 82) : false;

        imagedestroy($thumb);
        imagedestroy($image);

        return $saved;
    }

    private function resizeToMaxWidth(\GdImage $image, int $maxWidth): \GdImage|false
    {
        $width = (int)imagesx($image);
        $height = (int)imagesy($image);

        if ($width <= 0 || $height <= 0) {
            return false;
        }

        if ($width <= $maxWidth) {
            return $image;
        }

        $newWidth = $maxWidth;
        $newHeight = (int)max(1, round(($height / $width) * $newWidth));
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if ($resized === false) {
            return false;
        }

        imagealphablending($resized, true);
        imagesavealpha($resized, true);

        if (!imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
            imagedestroy($resized);
            return false;
        }

        return $resized;
    }

    private function thumbnailPath(string $webpPath, string $suffix): string
    {
        return ThumbnailVariants::thumbnailPath($webpPath, $suffix);
    }

    private function thumbVariants(): array
    {
        return ThumbnailVariants::variants();
    }
}
