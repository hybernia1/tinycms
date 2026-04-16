<?php
declare(strict_types=1);

namespace App\Service\Support;

final class ThumbnailVariants
{
    public const SMALL_NAME = 'small';
    public const FIXED_SUFFIX = '_100x100.webp';
    public const FIXED_WIDTH = 100;

    public static function thumbnailPath(string $webpPath, string $suffix = self::FIXED_SUFFIX): string
    {
        return (string)(preg_replace('/\.webp$/i', $suffix, $webpPath) ?? $webpPath);
    }

    public static function variants(): array
    {
        $variants = [[
            'name' => self::SMALL_NAME,
            'suffix' => self::FIXED_SUFFIX,
            'mode' => 'crop',
            'width' => self::FIXED_WIDTH,
            'height' => self::FIXED_WIDTH,
        ]];

        $raw = defined('MEDIA_THUMB_VARIANTS') && is_array(MEDIA_THUMB_VARIANTS) ? MEDIA_THUMB_VARIANTS : [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string)($item['name'] ?? ''));
            $suffix = trim((string)($item['suffix'] ?? ''));
            $width = (int)($item['width'] ?? 0);
            if ($name === '' || $name === self::SMALL_NAME || $suffix === '' || $width <= 0 || !str_ends_with(strtolower($suffix), '.webp') || $suffix === self::FIXED_SUFFIX) {
                continue;
            }

            $mode = trim((string)($item['mode'] ?? 'crop'));
            if ($mode === 'fit') {
                $variants[] = ['name' => $name, 'suffix' => $suffix, 'mode' => 'fit', 'width' => $width, 'height' => 0];
                continue;
            }

            $height = (int)($item['height'] ?? 0);
            if ($height <= 0) {
                continue;
            }

            $variants[] = ['name' => $name, 'suffix' => $suffix, 'mode' => 'crop', 'width' => $width, 'height' => $height];
        }

        return $variants;
    }

    public static function webpPath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '';
        }

        $replaced = preg_replace('/\.[a-z0-9]+$/i', '.webp', $trimmed);
        return $replaced !== null && $replaced !== '' ? $replaced : $trimmed . '.webp';
    }

    public static function path(string $originPath, string $name = self::SMALL_NAME): string
    {
        $variant = self::variant($name);
        if ($variant === null) {
            return trim($originPath);
        }

        return self::thumbnailPath(self::webpPath($originPath), (string)$variant['suffix']);
    }

    public static function variant(string $name): ?array
    {
        $target = trim($name);
        if ($target === '') {
            return null;
        }

        foreach (self::variants() as $variant) {
            if ((string)($variant['name'] ?? '') === $target) {
                return $variant;
            }
        }

        return null;
    }

    public static function suffixWidthMap(): array
    {
        $map = [];
        foreach (self::variants() as $variant) {
            $map[(string)$variant['suffix']] = (int)$variant['width'];
        }

        return $map;
    }
}
