<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Support\I18n;

final class Theme
{
    private const OPTIONS_SETTING_KEY = 'theme_options';

    private Settings $settings;
    private ThemeCustomizer $customizer;
    private ?array $themes = null;

    public function __construct(private string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/\\');
        $this->settings = new Settings();
        $this->customizer = new ThemeCustomizer(new Content());
    }

    public function themes(): array
    {
        if ($this->themes !== null) {
            return $this->themes;
        }

        $themes = [];
        $root = $this->themesPath();
        if (!is_dir($root)) {
            return $this->themes = ['default' => $this->fallbackManifest('default')];
        }

        foreach (scandir($root) ?: [] as $item) {
            $slug = $this->slug($item);
            if ($slug === '') {
                continue;
            }

            $path = $root . '/' . $slug;
            if (!is_dir($path)) {
                continue;
            }

            $themes[$slug] = $this->manifest($slug);
        }

        if ($themes === []) {
            $themes['default'] = $this->fallbackManifest('default');
        }

        uasort($themes, static fn(array $a, array $b): int => strnatcasecmp((string)$a['name'], (string)$b['name']));
        return $this->themes = $themes;
    }

    public function active(): string
    {
        $active = $this->slug((string)($this->settings->values()['front_theme'] ?? 'default'));
        return isset($this->themes()[$active]) ? $active : 'default';
    }

    public function resolved(): array
    {
        $active = $this->active();
        $resolved = ['front_theme' => $active];
        $values = $this->themeOptionValues($active);
        $fields = $this->fields($active);

        foreach ($fields as $key => $field) {
            $rawValue = array_key_exists($key, $values) ? (string)$values[$key] : (string)($field['default'] ?? '');
            $resolved[$key] = $this->customizer->normalizeValue($rawValue, $field);
        }

        return $resolved;
    }

    public function fields(?string $theme = null): array
    {
        $theme = $this->slug($theme ?? $this->active());
        $manifest = $this->themes()[$theme] ?? $this->themes()[$this->active()] ?? [];
        return is_array($manifest['settings'] ?? null) ? $manifest['settings'] : [];
    }

    public function customizerSections(?string $theme = null): array
    {
        $theme = $this->slug($theme ?? $this->active());
        $manifest = $this->themes()[$theme] ?? $this->themes()[$this->active()] ?? [];
        return is_array($manifest['customizer_sections'] ?? null) ? $manifest['customizer_sections'] : [];
    }

    public function save(array $input): array
    {
        $themes = $this->themes();
        $active = $this->active();
        $selected = $this->slug((string)($input['front_theme'] ?? $active));

        if ($selected === '' || !isset($themes[$selected])) {
            return ['success' => false, 'errors' => ['front_theme' => I18n::t('themes.invalid_theme')]];
        }

        $themeOptions = $this->themeOptions();
        $themeOptionValues = $this->themeOptionValues($selected, $themeOptions);
        $payload = [];
        $hasThemeInput = false;
        $fields = $this->fields($selected);
        $errors = [];

        foreach ($fields as $key => $field) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $hasThemeInput = true;
            $rawValue = (string)$input[$key];
            $error = $this->customizer->validateValue($rawValue, $field);
            if ($error !== '') {
                $errors[$key] = $error;
                continue;
            }

            $payload[$key] = $this->customizer->normalizeValue($rawValue, $field);
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $values = ['front_theme' => $selected];
        if ($hasThemeInput) {
            $themeOptions[$selected] = $this->filterValues(array_replace($themeOptionValues, $payload), array_keys($fields));
            $values[self::OPTIONS_SETTING_KEY] = $this->encodeThemeOptions($themeOptions);
        }

        $this->settings->saveValues($values);
        return ['success' => true, 'errors' => []];
    }

    public function previewValues(array $input): array
    {
        $themes = $this->themes();
        $selected = $this->slug((string)($input['front_theme'] ?? $this->active()));
        if ($selected === '' || !isset($themes[$selected])) {
            $selected = $this->active();
        }

        $payload = ['front_theme' => $selected];
        $themeOptionValues = $this->themeOptionValues($selected);
        $fields = $this->fields($selected);

        foreach ($fields as $key => $field) {
            if (!array_key_exists($key, $input)) {
                continue;
            }

            $themeOptionValues[$key] = $this->customizer->normalizeValue((string)$input[$key], $field);
        }

        foreach ($fields as $key => $field) {
            $rawValue = array_key_exists($key, $themeOptionValues) ? (string)$themeOptionValues[$key] : (string)($field['default'] ?? '');
            $payload[$key] = $this->customizer->normalizeValue($rawValue, $field);
        }

        return $payload;
    }

    private function manifest(string $slug): array
    {
        $manifest = $this->fallbackManifest($slug);
        $data = ThemeDefinition::load($this->rootPath, $slug)->manifest();

        $manifest['name'] = trim((string)($data['name'] ?? $manifest['name'])) ?: $manifest['name'];
        $manifest['version'] = trim((string)($data['version'] ?? ''));
        $manifest['author'] = trim((string)($data['author'] ?? ''));
        $manifest['description'] = trim((string)($data['description'] ?? ''));
        $manifest['features'] = $this->normalizeFeatures((array)($data['features'] ?? []));
        $manifest['settings'] = array_replace(
            ['enabled_widget_areas' => ThemeCustomizer::field('enabled_widget_areas')],
            ThemeCustomizer::normalizeFields((array)($data['settings'] ?? []))
        );
        $manifest['customizer_sections'] = ThemeCustomizer::normalizeSections(
            (array)($data['customizer_sections'] ?? []),
            array_keys($manifest['settings'])
        );

        return $manifest;
    }

    private function fallbackManifest(string $slug): array
    {
        return [
            'slug' => $slug,
            'name' => $slug,
            'version' => '',
            'author' => '',
            'description' => '',
            'features' => [],
            'settings' => [],
            'customizer_sections' => [],
        ];
    }

    private function normalizeFeatures(array $features): array
    {
        $result = [];
        foreach ($features as $feature) {
            $value = trim((string)$feature);
            if ($value !== '') {
                $result[] = $value;
            }
        }

        return array_values(array_unique($result));
    }

    private function themeOptionValues(string $theme, ?array $themeOptions = null): array
    {
        $theme = $this->slug($theme);
        $themeOptions ??= $this->themeOptions();
        $values = is_array($themeOptions[$theme] ?? null) ? $themeOptions[$theme] : [];

        return $this->filterValues($values);
    }

    private function themeOptions(): array
    {
        $raw = (string)($this->settings->values()[self::OPTIONS_SETTING_KEY] ?? '');
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $result = [];
        foreach ($decoded as $theme => $values) {
            $theme = $this->slug((string)$theme);
            if ($theme !== '' && is_array($values)) {
                $result[$theme] = $this->filterValues($values);
            }
        }

        return $result;
    }

    private function encodeThemeOptions(array $themeOptions): string
    {
        $payload = [];
        foreach ($themeOptions as $theme => $values) {
            $theme = $this->slug((string)$theme);
            if ($theme !== '' && is_array($values)) {
                $fields = array_keys($this->fields($theme));
                if ($fields !== []) {
                    $payload[$theme] = $this->filterValues($values, $fields);
                }
            }
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '{}';
    }

    private function filterValues(array $values, array $allowedKeys = []): array
    {
        $payload = [];
        $allowed = $allowedKeys !== [] ? array_fill_keys($allowedKeys, true) : [];

        foreach ($values as $key => $value) {
            $key = ThemeCustomizer::fieldName((string)$key);
            if ($key === '') {
                continue;
            }
            if ($allowed !== [] && !isset($allowed[$key])) {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $payload[$key] = (string)$value;
        }

        return $payload;
    }

    private function themesPath(): string
    {
        $themeDir = trim((string)(defined('THEMES_DIR') ? THEMES_DIR : 'themes/'), '/');
        return $this->rootPath . '/' . $themeDir;
    }

    private function slug(string $value): string
    {
        $clean = strtolower(trim($value));
        return preg_match('/^[a-z0-9_-]{1,100}$/', $clean) === 1 ? $clean : '';
    }

}
