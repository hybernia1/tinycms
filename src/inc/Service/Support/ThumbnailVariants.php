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

            $name = strtolower(trim((string)($item['name'] ?? '')));
            $suffix = trim((string)($item['suffix'] ?? ''));
            $width = (int)($item['width'] ?? 0);
            if ($suffix === '' || $width <= 0 || !str_ends_with(strtolower($suffix), '.webp') || $suffix === self::FIXED_SUFFIX) {
                continue;
            }

            $mode = trim((string)($item['mode'] ?? 'crop'));
            if ($mode === 'fit') {
                $variant = ['suffix' => $suffix, 'mode' => 'fit', 'width' => $width, 'height' => 0];
                if ($name !== '') {
                    $variant['name'] = $name;
                }
                $variants[] = $variant;
                continue;
            }

            $height = (int)($item['height'] ?? 0);
            if ($height <= 0) {
                continue;
            }

            $variant = ['suffix' => $suffix, 'mode' => 'crop', 'width' => $width, 'height' => $height];
            if ($name !== '') {
                $variant['name'] = $name;
            }
            $variants[] = $variant;
        }

        return $variants;
    }

    public static function contentMediaPath(string $originalPath, string $name = self::SMALL_NAME): string
    {
        $normalized = trim($originalPath);
        if ($normalized === '') {
            return '';
        }

        $basePath = (string)(preg_replace('/\.[^.\/]+$/', '', $normalized) ?? $normalized);
        if ($basePath === $normalized) {
            return $normalized;
        }

        $variant = self::variantByName($name);
        if ($variant === null) {
            return $basePath . '.webp';
        }

        return $basePath . (string)$variant['suffix'];
    }

    private static function variantByName(string $name): ?array
    {
        $needle = strtolower(trim($name));
        if ($needle === '') {
            return null;
        }

        foreach (self::variants() as $variant) {
            if (strtolower((string)($variant['name'] ?? '')) === $needle) {
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
