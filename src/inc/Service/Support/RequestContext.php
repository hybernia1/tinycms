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

    public static function basePath(?string $scriptName = null, ?string $requestUri = null): string
    {
        $requestBasePath = self::requestBasePath($scriptName, $requestUri);

        if (self::$websiteUrlParts !== null) {
            $configuredPath = self::$websiteUrlParts['path'];
            if (str_ends_with($requestBasePath, '/index.php') && !str_ends_with($configuredPath, '/index.php')) {
                return $requestBasePath;
            }

            return $configuredPath;
        }

        return $requestBasePath;
    }

    private static function requestBasePath(?string $scriptName = null, ?string $requestUri = null): string
    {
        $script = str_replace('\\', '/', (string)($scriptName ?? ($_SERVER['SCRIPT_NAME'] ?? '')));
        $directory = str_replace('\\', '/', dirname($script));
        $clean = trim($directory, '/.');

        $basePath = $clean === '' ? '' : '/' . $clean;
        $scriptPath = $basePath . '/' . basename($script);
        $requestPath = '/' . ltrim(str_replace('\\', '/', (string)(parse_url((string)($requestUri ?? ($_SERVER['REQUEST_URI'] ?? '')), PHP_URL_PATH) ?? '')), '/');

        if (basename($script) === 'index.php' && ($requestPath === $scriptPath || str_starts_with($requestPath, $scriptPath . '/'))) {
            return $scriptPath;
        }

        return $basePath;
    }

    public static function queryMode(?string $basePath = null): bool
    {
        return str_ends_with($basePath ?? self::basePath(), '/index.php');
    }

    public static function path(string $path = '', ?string $basePath = null, ?bool $queryMode = null): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $basePath ??= self::basePath();
        $queryMode ??= self::queryMode($basePath);
        $cleanPath = trim($path, '/');

        if ($cleanPath === '') {
            return $basePath === '' ? '/' : $basePath;
        }

        if ($queryMode) {
            $parts = parse_url($cleanPath);
            $routePath = (string)(is_array($parts) ? ($parts['path'] ?? '') : '');
            $query = (string)(is_array($parts) ? ($parts['query'] ?? '') : '');
            $fragment = (string)(is_array($parts) ? ($parts['fragment'] ?? '') : '');

            if (defined('BASE_DIR') && is_file(BASE_DIR . '/' . ltrim($routePath, '/'))) {
                $staticBase = str_ends_with($basePath, '/index.php') ? substr($basePath, 0, -10) : $basePath;
                return ($staticBase === '' ? '' : $staticBase) . '/' . $cleanPath;
            }

            return $basePath . '?route=' . rawurlencode($routePath) . ($query === '' ? '' : '&' . $query) . ($fragment === '' ? '' : '#' . $fragment);
        }

        return $basePath . '/' . $cleanPath;
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
            'path' => self::normalizeBasePath((string)($parts['path'] ?? '')),
        ];
    }

    private static function normalizeBasePath(string $path): string
    {
        $clean = trim(str_replace('\\', '/', $path), '/.');
        return $clean === '' ? '' : '/' . $clean;
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
