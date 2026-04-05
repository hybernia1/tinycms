<?php
declare(strict_types=1);

namespace App\Service\Support;

final class I18n
{
    private const DEFAULT_LOCALE = 'en';
    private static ?string $locale = null;
    private static ?string $theme = null;
    private static array $catalogues = [];

    public static function setLocale(string $locale): void
    {
        $normalized = self::normalizeLocale($locale);
        self::$locale = in_array($normalized, self::availableLocales(), true) ? $normalized : self::DEFAULT_LOCALE;
    }

    public static function locale(): string
    {
        if (self::$locale !== null) {
            return self::$locale;
        }

        self::setLocale((string)APP_LANG);
        return (string)self::$locale;
    }

    public static function htmlLang(): string
    {
        return self::locale();
    }

    public static function setTheme(?string $theme): void
    {
        $normalized = self::normalizeLocale((string)$theme);
        self::$theme = $normalized === '' ? null : $normalized;
        self::$catalogues = [];
    }

    public static function t(string $key, ?string $fallback = null): string
    {
        $locale = self::locale();
        $catalogue = self::loadCatalogue($locale);
        $value = self::getByPath($catalogue, $key);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if ($locale !== self::DEFAULT_LOCALE) {
            $defaultValue = self::getByPath(self::loadCatalogue(self::DEFAULT_LOCALE), $key);
            if (is_string($defaultValue) && $defaultValue !== '') {
                return $defaultValue;
            }
        }

        return $fallback ?? $key;
    }

    public static function availableLocales(): array
    {
        $dir = dirname(__DIR__, 2) . '/lang';
        if (!is_dir($dir)) {
            return [self::DEFAULT_LOCALE];
        }

        $files = glob($dir . '/*.php');
        if (!is_array($files)) {
            return [self::DEFAULT_LOCALE];
        }

        $locales = [];
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (is_string($name) && $name !== '') {
                $locales[] = self::normalizeLocale($name);
            }
        }

        $locales = array_values(array_unique($locales));
        sort($locales);
        return $locales !== [] ? $locales : [self::DEFAULT_LOCALE];
    }

    public static function languageLabel(string $locale): string
    {
        $normalized = self::normalizeLocale($locale);
        $value = self::getByPath(self::loadCatalogue($normalized), 'meta.language_name');
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return strtoupper($normalized);
    }

    private static function loadCatalogue(string $locale): array
    {
        $cacheKey = $locale . '|' . (self::$theme ?? '');
        if (isset(self::$catalogues[$cacheKey])) {
            return self::$catalogues[$cacheKey];
        }

        $catalogue = [];
        $baseFile = dirname(__DIR__, 2) . '/lang/' . $locale . '.php';
        if (is_file($baseFile)) {
            $base = require $baseFile;
            if (is_array($base)) {
                $catalogue = $base;
            }
        }

        if (self::$theme !== null) {
            $themeFile = dirname(__DIR__, 3) . '/themes/' . self::$theme . '/lang/' . $locale . '.php';
            if (is_file($themeFile)) {
                $themeCatalogue = require $themeFile;
                if (is_array($themeCatalogue)) {
                    $catalogue = array_replace_recursive($catalogue, $themeCatalogue);
                }
            }
        }

        self::$catalogues[$cacheKey] = $catalogue;
        return $catalogue;
    }

    private static function getByPath(array $catalogue, string $key): mixed
    {
        $segments = explode('.', $key);
        $current = $catalogue;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    private static function normalizeLocale(string $locale): string
    {
        return strtolower(trim($locale));
    }
}
