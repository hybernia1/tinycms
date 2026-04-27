<?php
declare(strict_types=1);

namespace App\Service\Support;

final class ExtensionPaths
{
    public static function themesPath(string $rootPath): string
    {
        return self::join($rootPath, self::themesDir());
    }

    public static function widgetsPath(string $rootPath): string
    {
        return self::join($rootPath, self::extensionsDir(), 'widgets');
    }

    public static function themePath(string $rootPath, string $theme): string
    {
        return self::join($rootPath, self::themesDir(), trim($theme, '/\\'));
    }

    public static function themeUrlPath(string $theme, string $path = ''): string
    {
        return trim(self::themesDir() . '/' . trim($theme, '/\\') . '/' . ltrim($path, '/\\'), '/\\');
    }

    public static function safeFile(string $path, string $root): string
    {
        $real = realpath($path);
        $rootReal = realpath($root);
        $file = $real === false ? '' : str_replace('\\', '/', $real);
        $base = $rootReal === false ? '' : rtrim(str_replace('\\', '/', $rootReal), '/');

        if ($file === '' || $base === '' || !is_file($file)) {
            return '';
        }

        return ($file === $base || str_starts_with($file, $base . '/')) ? $file : '';
    }

    private static function extensionsDir(): string
    {
        return trim(str_replace('\\', '/', (string)(defined('EXTENSIONS_DIR') ? EXTENSIONS_DIR : 'extensions/')), '/');
    }

    private static function themesDir(): string
    {
        return trim(str_replace('\\', '/', (string)(defined('THEMES_DIR') ? THEMES_DIR : self::extensionsDir() . '/themes/')), '/');
    }

    private static function join(string ...$parts): string
    {
        $clean = [];
        foreach ($parts as $index => $part) {
            $value = $index === 0 ? rtrim($part, '/\\') : trim($part, '/\\');
            if ($value !== '') {
                $clean[] = $value;
            }
        }

        return implode('/', $clean);
    }
}
