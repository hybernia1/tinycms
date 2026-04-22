<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Escape
{
    public static function escHtml(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function escAttr(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function escUrl(mixed $value): string
    {
        $url = trim((string)$value);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return self::escAttr($url);
        }

        if (preg_match('#^[a-z][a-z0-9+\-.]*:#i', $url) === 1) {
            $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
            if (!in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
                return '';
            }
        }

        if (str_starts_with($url, '//')) {
            return '';
        }

        return self::escAttr($url);
    }

    public static function escJs(mixed $value): string
    {
        return (string)json_encode((string)$value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
