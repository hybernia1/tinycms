<?php
declare(strict_types=1);

namespace App\Service\Support;

final class RequestContext
{
    public static function scheme(): string
    {
        $fromConfig = self::configParts();
        if ($fromConfig !== null) {
            return $fromConfig['scheme'];
        }

        return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    }

    public static function authority(): string
    {
        $fromConfig = self::configParts();
        if ($fromConfig !== null) {
            $port = $fromConfig['port'];
            if ($port === null) {
                return $fromConfig['host'];
            }
            return $fromConfig['host'] . ':' . $port;
        }

        [$host, $port] = self::firstTrustedHost();
        if ($host === '') {
            return 'localhost';
        }
        if ($port === null) {
            return $host;
        }
        return $host . ':' . $port;
    }

    public static function domain(): string
    {
        $fromConfig = self::configParts();
        if ($fromConfig !== null) {
            return $fromConfig['host'];
        }

        [$host] = self::firstTrustedHost();
        return $host !== '' ? $host : 'localhost';
    }

    private static function firstTrustedHost(): array
    {
        [$serverName] = self::parseAuthority((string)($_SERVER['SERVER_NAME'] ?? ''));
        if ($serverName !== '') {
            return [$serverName, null];
        }

        return self::parseAuthority((string)($_SERVER['HTTP_HOST'] ?? ''));
    }

    private static function configParts(): ?array
    {
        if (!defined('APP_URL')) {
            return null;
        }

        $raw = trim((string)APP_URL);
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

    private static function parseAuthority(string $value): array
    {
        $raw = trim($value);
        if ($raw === '') {
            return ['', null];
        }

        $host = strtolower((string)parse_url('http://' . $raw, PHP_URL_HOST));
        if (!self::isValidHost($host)) {
            return ['', null];
        }

        $port = parse_url('http://' . $raw, PHP_URL_PORT);
        return [$host, is_int($port) ? $port : null];
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
