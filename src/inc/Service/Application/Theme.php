<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\SchemaConstraintValidator;
use App\Service\Support\I18n;

final class Theme
{
    private const SETTINGS_KEY = 'theme_settings';

    private SchemaConstraintValidator $schemaConstraintValidator;
    private Settings $settings;
    private ?array $themes = null;

    public function __construct(private string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/\\');
        $this->settings = new Settings();
        $this->schemaConstraintValidator = new SchemaConstraintValidator();
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

            $themes[$slug] = $this->manifest($slug, $path);
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
        $values = $this->themeValues($active);
        $fields = $this->fields($active);

        foreach ($fields as $key => $field) {
            $resolved[$key] = $this->normalizeValue($key, (string)($values[$key] ?? ($field['default'] ?? '')), $field);
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
        $sections = is_array($manifest['customizer_sections'] ?? null) ? $manifest['customizer_sections'] : [];
        if ($sections !== []) {
            return $sections;
        }

        $fields = array_keys($this->fields($theme));
        return $fields === [] ? [] : [
            'settings' => [
                'label' => I18n::t('themes.sections.settings'),
                'fields' => $fields,
            ],
        ];
    }

    public function save(array $input): array
    {
        $themes = $this->themes();
        $active = $this->active();
        $selected = $this->slug((string)($input['front_theme'] ?? $active));

        if ($selected === '' || !isset($themes[$selected])) {
            return ['success' => false, 'errors' => ['front_theme' => I18n::t('themes.invalid_theme')]];
        }

        $themeSettings = $this->themeSettings();
        $themeValues = $this->themeValues($selected, $themeSettings);
        $payload = [];
        $hasThemeInput = false;
        $fields = $this->fields($selected);

        foreach ($fields as $key => $field) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $hasThemeInput = true;
            $payload[$key] = $this->normalizeValue($key, (string)$input[$key], $field);
        }

        $values = ['front_theme' => $selected];
        if ($hasThemeInput) {
            $themeSettings[$selected] = $this->filterValues(array_replace($themeValues, $payload), array_keys($fields));
            $values[self::SETTINGS_KEY] = $this->encodeThemeSettings($themeSettings);
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
        $themeValues = $this->themeValues($selected);
        $fields = $this->fields($selected);

        foreach ($fields as $key => $field) {
            if (!array_key_exists($key, $input)) {
                continue;
            }

            $themeValues[$key] = $this->normalizeValue($key, (string)$input[$key], $field);
        }

        foreach ($fields as $key => $field) {
            $payload[$key] = $this->normalizeValue($key, (string)($themeValues[$key] ?? ($field['default'] ?? '')), $field);
        }

        return $payload;
    }

    private function manifest(string $slug, string $path): array
    {
        $manifest = $this->fallbackManifest($slug);
        $file = $path . '/theme.json';
        if (!is_file($file)) {
            return $manifest;
        }

        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data)) {
            return $manifest;
        }

        $manifest['name'] = trim((string)($data['name'] ?? $manifest['name'])) ?: $manifest['name'];
        $manifest['version'] = trim((string)($data['version'] ?? ''));
        $manifest['author'] = trim((string)($data['author'] ?? ''));
        $manifest['description'] = trim((string)($data['description'] ?? ''));
        $manifest['features'] = $this->normalizeFeatures((array)($data['features'] ?? []));
        $manifest['settings'] = $this->normalizeFields((array)($data['settings'] ?? []));
        $manifest['customizer_sections'] = $this->normalizeCustomizerSections(
            (array)($data['customizer']['sections'] ?? $data['customizer_sections'] ?? []),
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

    private function normalizeFields(array $fields): array
    {
        $result = [];
        $allowedTypes = ['text', 'textarea', 'select', 'checkbox', 'number', 'file', 'color'];

        foreach ($fields as $key => $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = $this->fieldName((string)$key);
            if ($name === '') {
                continue;
            }

            $type = (string)($field['type'] ?? 'text');
            if (!in_array($type, $allowedTypes, true)) {
                $type = 'text';
            }

            $result[$name] = [
                'type' => $type,
                'label' => trim((string)($field['label'] ?? '')),
                'default' => (string)($field['default'] ?? ($type === 'checkbox' ? '0' : '')),
                'options' => $this->normalizeOptions((array)($field['options'] ?? [])),
                'min' => (int)($field['min'] ?? 0),
                'max' => (int)($field['max'] ?? 1000),
            ];
        }

        return $result;
    }

    private function normalizeOptions(array $options): array
    {
        $result = [];
        foreach ($options as $value => $label) {
            $value = trim((string)$value);
            if ($value !== '') {
                $result[$value] = (string)$label;
            }
        }

        return $result;
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

    private function normalizeValue(string $key, string $value, array $field): string
    {
        $type = (string)($field['type'] ?? 'text');
        $default = (string)($field['default'] ?? '');

        if ($type === 'checkbox') {
            return $value === '1' ? '1' : '0';
        }

        if ($type === 'number') {
            $min = (int)($field['min'] ?? 0);
            $max = max($min, (int)($field['max'] ?? 1000));
            return (string)max($min, min($max, (int)$value));
        }

        if ($type === 'select') {
            $options = (array)($field['options'] ?? []);
            return array_key_exists($value, $options) ? $value : $default;
        }

        if ($type === 'color') {
            return $this->normalizeColor($value, $default);
        }

        $limit = max(1, (int)($field['max'] ?? ($type === 'textarea' ? 10000 : 500)));
        return $this->schemaConstraintValidator->truncate('settings', 'value', trim($value), $limit);
    }

    private function normalizeColor(string $value, string $default): string
    {
        $value = strtolower(trim($value));
        if (preg_match('/^#[0-9a-f]{6}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $value, $matches) === 1) {
            return '#' . $matches[1] . $matches[1] . $matches[2] . $matches[2] . $matches[3] . $matches[3];
        }

        $default = strtolower(trim($default));
        return preg_match('/^#[0-9a-f]{6}$/', $default) === 1 ? $default : '';
    }

    private function themeValues(string $theme, ?array $themeSettings = null): array
    {
        $theme = $this->slug($theme);
        $themeSettings ??= $this->themeSettings();
        $values = is_array($themeSettings[$theme] ?? null) ? $themeSettings[$theme] : [];

        return $this->filterValues($values);
    }

    private function themeSettings(): array
    {
        $raw = (string)($this->settings->values()[self::SETTINGS_KEY] ?? '');
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

    private function normalizeCustomizerSections(array $sections, array $availableFields): array
    {
        $available = array_fill_keys($availableFields, true);
        $result = [];

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $key = $this->fieldName((string)($section['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $fields = [];
            foreach ((array)($section['fields'] ?? []) as $field) {
                $field = $this->fieldName((string)$field);
                if ($field !== '' && isset($available[$field])) {
                    $fields[] = $field;
                }
            }

            $fields = array_values(array_unique($fields));
            if ($fields === []) {
                continue;
            }

            $result[$key] = [
                'label' => trim((string)($section['label'] ?? '')),
                'fields' => $fields,
            ];
        }

        return $result;
    }

    private function encodeThemeSettings(array $themeSettings): string
    {
        $payload = [];
        foreach ($themeSettings as $theme => $values) {
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
            $key = $this->fieldName((string)$key);
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

    private function fieldName(string $value): string
    {
        $clean = trim($value);
        return preg_match('/^[a-z0-9_]{1,100}$/i', $clean) === 1 ? $clean : '';
    }
}
