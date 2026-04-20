<?php
declare(strict_types=1);

namespace App\Service\Support;

final class RequestContext
{
    private static ?array $websiteUrlParts = null;

    public static function setWebsiteUrl(?string $url): void
    {
        self::$websiteUrlParts = self::parseWebsiteUrl($url);
    }

    public static function isValidWebsiteUrl(string $value): bool
    {
        $raw = trim($value);
        if ($raw === '') {
            return true;
        }

        return self::parseWebsiteUrl($raw) !== null;
    }

    public static function scheme(): string
    {
        if (self::$websiteUrlParts !== null) {
            return self::$websiteUrlParts['scheme'];
        }

        return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    }

    public static function authority(): string
    {
        if (self::$websiteUrlParts !== null) {
            $port = self::$websiteUrlParts['port'];
            if ($port === null) {
                return self::$websiteUrlParts['host'];
            }
            return self::$websiteUrlParts['host'] . ':' . $port;
        }
        return 'localhost';
    }

    public static function hasAuthority(): bool
    {
        return self::$websiteUrlParts !== null;
    }

    public static function domain(): string
    {
        if (self::$websiteUrlParts !== null) {
            return self::$websiteUrlParts['host'];
        }
        return 'localhost';
    }

    private static function parseWebsiteUrl(?string $url): ?array
    {
        $raw = trim((string)$url);
        if ($raw === '') {
            return null;
        }

        $parts = parse_url($raw);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        $host = strtolower(trim((string)($parts['host'] ?? '')));
        if (!self::isValidHost($host)) {
            return null;
        }

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => isset($parts['port']) ? (int)$parts['port'] : null,
        ];
    }

    private static function isValidHost(string $host): bool
    {
        if ($host === 'localhost') {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        return filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
