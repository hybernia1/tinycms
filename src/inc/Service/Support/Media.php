<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Media
{
    public const SMALL_SUFFIX = '_small.webp';
    public const MEDIUM_SUFFIX = '_medium.webp';
    private const DEFAULT_SMALL_WIDTH = 300;
    private const DEFAULT_SMALL_HEIGHT = 300;
    private const DEFAULT_MEDIUM_WIDTH = 768;
    private static int $smallWidth = self::DEFAULT_SMALL_WIDTH;
    private static int $smallHeight = self::DEFAULT_SMALL_HEIGHT;
    private static int $mediumWidth = self::DEFAULT_MEDIUM_WIDTH;

    public static function configure(array $settings): void
    {
        self::$smallWidth = self::settingInt($settings, 'media_small_width', self::DEFAULT_SMALL_WIDTH);
        self::$smallHeight = self::settingInt($settings, 'media_small_height', self::DEFAULT_SMALL_HEIGHT);
        self::$mediumWidth = self::settingInt($settings, 'media_medium_width', self::DEFAULT_MEDIUM_WIDTH);
    }

    public static function bySize(string $path, string $size = 'origin'): string
    {
        $normalized = trim($path);
        if ($normalized === '') {
            return '';
        }

        return match (strtolower(trim($size))) {
            'small' => self::smallPath($normalized),
            'medium' => self::thumbnailPath(self::webpPath($normalized), self::MEDIUM_SUFFIX),
            'webp' => self::webpPath($normalized),
            default => $normalized,
        };
    }

    public static function webpPath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '';
        }

        if (preg_match('/\.webp$/i', $trimmed) === 1) {
            return $trimmed;
        }

        $converted = preg_replace('/\.[^.\/]+$/', '.webp', $trimmed);
        if (is_string($converted) && $converted !== '') {
            return $converted;
        }

        return $trimmed . '.webp';
    }

    public static function thumbnailPath(string $webpPath, string $suffix): string
    {
        return (string)(preg_replace('/\.webp$/i', $suffix, $webpPath) ?? $webpPath);
    }

    public static function smallPath(string $path): string
    {
        return self::variantPath($path, self::SMALL_SUFFIX);
    }

    public static function variants(): array
    {
        return [[
            'name' => 'small',
            'suffix' => self::SMALL_SUFFIX,
            'mode' => 'crop',
            'width' => self::smallWidth(),
            'height' => self::smallHeight(),
        ], [
            'name' => 'medium',
            'suffix' => self::MEDIUM_SUFFIX,
            'mode' => 'fit',
            'width' => self::mediumWidth(),
            'height' => 0,
        ]];
    }

    private static function variantPath(string $path, string $suffix): string
    {
        $webpPath = self::webpPath($path);
        if ($webpPath === '') {
            return '';
        }

        return self::thumbnailPath($webpPath, $suffix);
    }

    private static function smallWidth(): int
    {
        return self::$smallWidth;
    }

    private static function smallHeight(): int
    {
        return self::$smallHeight;
    }

    private static function mediumWidth(): int
    {
        return self::$mediumWidth;
    }

    private static function settingInt(array $settings, string $key, int $default): int
    {
        $value = (int)($settings[$key] ?? $default);
        return max(1, $value > 0 ? $value : $default);
    }
}
