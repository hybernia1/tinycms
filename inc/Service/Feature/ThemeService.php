<?php
declare(strict_types=1);

namespace App\Service\Feature;

final class ThemeService
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/');
    }

    public function availableThemes(): array
    {
        $themesDir = $this->rootPath . '/themes';
        if (!is_dir($themesDir)) {
            return ['default'];
        }

        $entries = glob($themesDir . '/*', GLOB_ONLYDIR);
        if (!is_array($entries)) {
            return ['default'];
        }

        $themes = [];
        foreach ($entries as $entry) {
            $name = basename($entry);
            if ($this->isValidTheme($name)) {
                $themes[] = $name;
            }
        }

        sort($themes);
        return $themes !== [] ? $themes : ['default'];
    }

    public function resolveTheme(?string $theme): string
    {
        $normalized = $this->normalizeTheme((string)$theme);
        $available = $this->availableThemes();
        if (in_array($normalized, $available, true)) {
            return $normalized;
        }

        return in_array('default', $available, true) ? 'default' : (string)($available[0] ?? 'default');
    }

    public function hasTemplate(string $theme, string $template): bool
    {
        $resolvedTheme = $this->resolveTheme($theme);
        $name = trim($template);
        if ($name === '' || preg_match('/^[a-z0-9\-_\/]+$/i', $name) !== 1) {
            return false;
        }

        return is_file($this->rootPath . '/themes/' . $resolvedTheme . '/' . ltrim($name, '/') . '.php');
    }

    private function isValidTheme(string $name): bool
    {
        if ($name === '' || $name !== $this->normalizeTheme($name)) {
            return false;
        }

        return is_file($this->rootPath . '/themes/' . $name . '/layout.php');
    }

    private function normalizeTheme(string $theme): string
    {
        return strtolower(trim($theme));
    }
}
