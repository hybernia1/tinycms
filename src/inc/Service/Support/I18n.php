<?php
declare(strict_types=1);

namespace App\Service\Support;

final class I18n
{
    private const DEFAULT_LOCALE = 'en';
    private static ?string $locale = null;
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
        $dir = BASE_DIR . '/' . INC_DIR . 'lang';
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

    public static function subset(array $paths): array
    {
        $result = [];
        foreach ($paths as $path) {
            $key = trim((string)$path);
            if ($key === '') {
                continue;
            }

            $value = self::value($key);
            if ($value === null) {
                continue;
            }

            self::setByPath($result, $key, $value);
        }

        return $result;
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
        $cacheKey = $locale;
        if (isset(self::$catalogues[$cacheKey])) {
            return self::$catalogues[$cacheKey];
        }

        $catalogue = [];
        $baseFile = BASE_DIR . '/' . INC_DIR . 'lang/' . $locale . '.php';
        if (is_file($baseFile)) {
            $base = require $baseFile;
            if (is_array($base)) {
                $catalogue = $base;
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

    private static function value(string $key): mixed
    {
        $locale = self::locale();
        $catalogue = self::loadCatalogue($locale);
        $value = self::getByPath($catalogue, $key);
        if ($value !== null) {
            return $value;
        }

        if ($locale === self::DEFAULT_LOCALE) {
            return null;
        }

        return self::getByPath(self::loadCatalogue(self::DEFAULT_LOCALE), $key);
    }

    private static function setByPath(array &$target, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $last = array_pop($segments);
        if ($last === null || $last === '') {
            return;
        }

        $current = &$target;
        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current[$last] = $value;
    }

    private static function normalizeLocale(string $locale): string
    {
        return strtolower(trim($locale));
    }
}
