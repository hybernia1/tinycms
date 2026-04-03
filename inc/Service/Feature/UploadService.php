<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Support\SluggerService;

final class UploadService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const MAX_WEBP_WIDTH = 1024;
    private const LIST_THUMB_SIZE = 100;
    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
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
            return ['success' => false, 'error' => 'Soubor se nepodařilo nahrát.'];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['success' => false, 'error' => 'Neplatný upload souboru.'];
        }

        $originalName = trim((string)($file['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = $this->detectMime($tmpPath);

        if (!isset(self::MIME_TO_EXTENSION[$mime])) {
            return ['success' => false, 'error' => 'Nepodporovaný typ souboru.'];
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = self::MIME_TO_EXTENSION[$mime];
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return ['success' => false, 'error' => 'Nepodporovaná přípona souboru.'];
        }

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $slug = $this->slugger->slug($baseName !== '' ? $baseName : 'soubor', random_int(1000, 999999));
        $subdir = 'uploads/' . date('Y') . '/' . date('m');
        $absDir = $this->rootPath . '/' . $subdir;

        if (!is_dir($absDir) && !mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            return ['success' => false, 'error' => 'Nelze vytvořit cílovou složku.'];
        }

        $fileRel = $subdir . '/' . $slug . '.' . $extension;
        $fileAbs = $this->rootPath . '/' . $fileRel;

        if (!move_uploaded_file($tmpPath, $fileAbs)) {
            return ['success' => false, 'error' => 'Soubor se nepodařilo uložit na disk.'];
        }

        $webpRel = $extension === 'webp' ? $fileRel : $subdir . '/' . $slug . '.webp';
        $webpAbs = $this->rootPath . '/' . $webpRel;

        if (!$this->createWebp($fileAbs, $webpAbs, $mime, self::MAX_WEBP_WIDTH)) {
            if ($fileAbs !== $webpAbs) {
                @unlink($fileAbs);
            }
            return ['success' => false, 'error' => 'Nepodařilo se vytvořit WEBP variantu.'];
        }

        $thumbRel = $this->thumbnailPath($webpRel);
        $thumbAbs = $this->rootPath . '/' . $thumbRel;

        if (!$this->createThumbnailWebp($webpAbs, $thumbAbs, self::LIST_THUMB_SIZE)) {
            if ($fileAbs !== $webpAbs) {
                @unlink($fileAbs);
            }
            @unlink($webpAbs);
            return ['success' => false, 'error' => 'Nepodařilo se vytvořit thumbnail variantu.'];
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
            $paths[] = $this->thumbnailPath($webpPath);
        }

        $paths = array_unique($paths);

        foreach ($paths as $path) {
            $absolute = $this->rootPath . '/' . ltrim($path, '/');
            if (is_file($absolute)) {
                @unlink($absolute);
            }
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

    private function createThumbnailWebp(string $sourceWebp, string $destinationPath, int $size): bool
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

        $srcSize = min($width, $height);
        $srcX = (int)floor(($width - $srcSize) / 2);
        $srcY = (int)floor(($height - $srcSize) / 2);

        $thumb = imagecreatetruecolor($size, $size);
        if ($thumb === false) {
            imagedestroy($image);
            return false;
        }

        imagealphablending($thumb, true);
        imagesavealpha($thumb, true);

        $ok = imagecopyresampled($thumb, $image, 0, 0, $srcX, $srcY, $size, $size, $srcSize, $srcSize);
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

    private function thumbnailPath(string $webpPath): string
    {
        return (string)(preg_replace('/\.webp$/i', '_' . self::LIST_THUMB_SIZE . 'x' . self::LIST_THUMB_SIZE . '.webp', $webpPath) ?? $webpPath);
    }
}
