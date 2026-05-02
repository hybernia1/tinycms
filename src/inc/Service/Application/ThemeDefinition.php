<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Support\I18n;

final class ThemeDefinition
{
    private static ?self $current = null;
    private static array $cache = [];
    private array $manifest;
    private array $widgetAreas = [];

    private function __construct(private string $rootPath, private string $theme)
    {
        $this->rootPath = rtrim($rootPath, '/\\');
        $this->theme = self::slug($theme) ?: 'default';
        $this->manifest = [
            'slug' => $this->theme,
            'name' => $this->theme,
            'version' => '',
            'author' => '',
            'description' => '',
            'features' => [],
            'settings' => [],
            'customizer_sections' => [],
        ];

        $this->loadFunctions();
    }

    public static function load(string $rootPath, string $theme): self
    {
        $root = str_replace('\\', '/', rtrim($rootPath, '/\\'));
        $slug = self::slug($theme) ?: 'default';
        $key = $root . ':' . $slug;

        return self::$cache[$key] ??= new self($rootPath, $slug);
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    public function manifest(): array
    {
        return $this->manifest;
    }

    public function widgetAreas(): array
    {
        return $this->widgetAreas;
    }

    public function registerTheme(array $data): void
    {
        foreach (['name', 'version', 'author', 'description'] as $key) {
            if (array_key_exists($key, $data)) {
                $this->manifest[$key] = trim((string)$data[$key]);
            }
        }

        if (array_key_exists('features', $data)) {
            $this->manifest['features'] = [];
            foreach ((array)$data['features'] as $feature) {
                $this->registerFeature((string)$feature);
            }
        }
    }

    public function registerOption(string $key, array $field): void
    {
        $key = self::fieldName($key);
        if ($key !== '') {
            $this->manifest['settings'][$key] = $field;
        }
    }

    public function registerCustomizerSection(string $key, string $label = '', array $fields = []): void
    {
        $key = self::fieldName($key);
        if ($key === '') {
            return;
        }

        $this->manifest['customizer_sections'][] = [
            'key' => $key,
            'label' => trim($label),
            'fields' => $fields,
        ];
    }

    public function registerWidgetArea(string $area, string $label = ''): void
    {
        $area = self::slug($area);
        if ($area !== '') {
            $this->widgetAreas[$area] = [
                'name' => $area,
                'label' => trim($label),
            ];
        }
    }

    private function loadFunctions(): void
    {
        $file = $this->themePath() . '/functions.php';
        if (!$this->isInside($file, $this->themePath())) {
            return;
        }

        $previous = self::$current;
        self::$current = $this;
        I18n::pushCataloguePath($this->themePath() . '/lang');

        try {
            require $file;
        } finally {
            I18n::popCataloguePath();
            self::$current = $previous;
        }
    }

    private function themePath(): string
    {
        $themeDir = trim((string)(defined('THEMES_DIR') ? THEMES_DIR : 'themes/'), '/');
        return $this->rootPath . '/' . $themeDir . '/' . $this->theme;
    }

    private function registerFeature(string $feature): void
    {
        $feature = self::name($feature);
        if ($feature !== '' && !in_array($feature, $this->manifest['features'], true)) {
            $this->manifest['features'][] = $feature;
        }
    }

    private function isInside(string $file, string $root): bool
    {
        $real = realpath($file);
        $base = realpath($root);
        $normalizedReal = $real === false ? '' : str_replace('\\', '/', $real);
        $normalizedBase = $base === false ? '' : rtrim(str_replace('\\', '/', $base), '/') . '/';

        return $normalizedReal !== ''
            && $normalizedBase !== ''
            && str_starts_with($normalizedReal, $normalizedBase)
            && is_file($real);
    }

    private static function slug(string $value): string
    {
        $clean = strtolower(trim($value));
        return preg_match('/^[a-z0-9_-]{1,100}$/', $clean) === 1 ? $clean : '';
    }

    private static function name(string $value): string
    {
        $clean = trim($value);
        return preg_match('/^[a-z0-9_-]{1,100}$/i', $clean) === 1 ? $clean : '';
    }

    private static function fieldName(string $value): string
    {
        $clean = trim($value);
        return preg_match('/^[a-z0-9_]{1,100}$/i', $clean) === 1 ? $clean : '';
    }
}
