<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Support\SluggerService;

final class UploadService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
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

        if ($extension !== 'webp' && !$this->createWebp($fileAbs, $webpAbs, $mime)) {
            @unlink($fileAbs);
            return ['success' => false, 'error' => 'Nepodařilo se vytvořit WEBP variantu.'];
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
        $paths = array_unique(array_filter([
            trim((string)($media['path'] ?? '')),
            trim((string)($media['path_webp'] ?? '')),
        ]));

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

    private function createWebp(string $sourcePath, string $destinationPath, string $mime): bool
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

        $result = @imagewebp($image, $destinationPath, 85);
        imagedestroy($image);

        return $result;
    }
}
