<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Support\SluggerService;
use App\Service\Support\I18n;

final class UploadService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const MAX_WEBP_WIDTH = 1024;
    private const DEFAULT_THUMB_VARIANTS = [
        ['suffix' => '_100x100.webp', 'mode' => 'crop', 'width' => 100, 'height' => 100],
        ['suffix' => '_w768.webp', 'mode' => 'fit', 'width' => 768],
    ];
    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    private const FAVICON_MIME_TO_EXTENSION = [
        'image/png' => 'png',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
        'image/svg+xml' => 'svg',
    ];

    public function __construct(
        private string $rootPath,
        private SluggerService $slugger
    ) {
        $this->rootPath = rtrim($rootPath, '/');
    }

    public function uploadImage(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => I18n::t('upload.file_upload_failed', 'File upload failed.')];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['success' => false, 'error' => I18n::t('upload.invalid_upload', 'Invalid file upload.')];
        }

        $originalName = trim((string)($file['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = $this->detectMime($tmpPath);

        if (!isset(self::MIME_TO_EXTENSION[$mime])) {
            return ['success' => false, 'error' => I18n::t('upload.unsupported_file_type', 'Unsupported file type.')];
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = self::MIME_TO_EXTENSION[$mime];
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return ['success' => false, 'error' => I18n::t('upload.unsupported_file_extension', 'Unsupported file extension.')];
        }

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $slug = $this->slugger->slug($baseName !== '' ? $baseName : 'soubor', random_int(1000, 999999));
        $subdir = 'uploads/' . date('Y') . '/' . date('m');
        $absDir = $this->rootPath . '/' . $subdir;

        if (!is_dir($absDir) && !mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            return ['success' => false, 'error' => I18n::t('upload.target_dir_create_failed', 'Cannot create target directory.')];
        }

        $fileRel = $subdir . '/' . $slug . '.' . $extension;
        $fileAbs = $this->rootPath . '/' . $fileRel;

        if (!move_uploaded_file($tmpPath, $fileAbs)) {
            return ['success' => false, 'error' => I18n::t('upload.save_to_disk_failed', 'Failed to save file to disk.')];
        }

        $webpRel = $extension === 'webp' ? $fileRel : $subdir . '/' . $slug . '.webp';
        $webpAbs = $this->rootPath . '/' . $webpRel;

        if (!$this->createWebp($fileAbs, $webpAbs, $mime, self::MAX_WEBP_WIDTH)) {
            if ($fileAbs !== $webpAbs) {
                @unlink($fileAbs);
            }
            return ['success' => false, 'error' => I18n::t('upload.webp_create_failed', 'Failed to create WEBP variant.')];
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
            return ['success' => false, 'error' => I18n::t('upload.thumbnail_create_failed', 'Failed to create thumbnail variant.')];
        }

        return [
            'success' => true,
            'data' => [
                'name' => $originalName !== '' ? $originalName : basename($fileRel),
                'path' => $fileRel,
                'path_webp' => $webpRel,
            ],
        ];
    }

    public function deleteMediaFiles(array $media): void
    {
        $paths = array_filter([
            trim((string)($media['path'] ?? '')),
            trim((string)($media['path_webp'] ?? '')),
        ]);

        $webpPath = trim((string)($media['path_webp'] ?? ''));
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
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => I18n::t('upload.file_upload_failed', 'File upload failed.')];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['success' => false, 'error' => I18n::t('upload.invalid_upload', 'Invalid file upload.')];
        }

        $mime = $this->detectMime($tmpPath);
        $extension = self::FAVICON_MIME_TO_EXTENSION[$mime] ?? '';
        if ($extension === '') {
            return ['success' => false, 'error' => I18n::t('upload.unsupported_file_type', 'Unsupported file type.')];
        }

        $subdir = 'uploads/favicons';
        $absDir = $this->rootPath . '/' . $subdir;
        if (!is_dir($absDir) && !mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            return ['success' => false, 'error' => I18n::t('upload.target_dir_create_failed', 'Cannot create target directory.')];
        }

        $name = 'favicon-' . date('YmdHis') . '-' . random_int(1000, 9999) . '.' . $extension;
        $path = $subdir . '/' . $name;
        $absolute = $this->rootPath . '/' . $path;

        if (!move_uploaded_file($tmpPath, $absolute)) {
            return ['success' => false, 'error' => I18n::t('upload.save_to_disk_failed', 'Failed to save file to disk.')];
        }

        return ['success' => true, 'data' => ['path' => $path]];
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
        return (string)(preg_replace('/\.webp$/i', $suffix, $webpPath) ?? $webpPath);
    }

    private function thumbVariants(): array
    {
        $raw = defined('MEDIA_THUMB_VARIANTS') && is_array(MEDIA_THUMB_VARIANTS) ? MEDIA_THUMB_VARIANTS : self::DEFAULT_THUMB_VARIANTS;
        $variants = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $suffix = trim((string)($item['suffix'] ?? ''));
            $mode = trim((string)($item['mode'] ?? 'crop'));
            $width = (int)($item['width'] ?? 0);
            $height = (int)($item['height'] ?? 0);

            if ($suffix === '' || !str_ends_with(strtolower($suffix), '.webp') || $width <= 0) {
                continue;
            }

            if ($mode === 'fit') {
                $variants[] = ['suffix' => $suffix, 'mode' => 'fit', 'width' => $width, 'height' => 0];
                continue;
            }

            $variants[] = ['suffix' => $suffix, 'mode' => 'crop', 'width' => $width, 'height' => $height > 0 ? $height : $width];
        }

        return $variants === [] ? self::DEFAULT_THUMB_VARIANTS : $variants;
    }
}
