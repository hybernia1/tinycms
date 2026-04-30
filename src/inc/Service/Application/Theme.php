<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\SchemaConstraintValidator;
use App\Service\Support\I18n;

final class Theme
{
    private Query $query;
    private SchemaConstraintValidator $schemaConstraintValidator;
    private ?array $themes = null;

    public function __construct(private string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/\\');
        $this->query = new Query(Connection::get());
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
        $active = $this->slug((string)($this->values()['front_theme'] ?? 'default'));
        return isset($this->themes()[$active]) ? $active : 'default';
    }

    public function resolved(): array
    {
        $active = $this->active();
        $values = $this->values();
        $resolved = ['front_theme' => $active];

        foreach ($this->fields($active) as $key => $field) {
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

    public function save(array $input): array
    {
        $themes = $this->themes();
        $active = $this->active();
        $selected = $this->slug((string)($input['front_theme'] ?? $active));

        if ($selected === '' || !isset($themes[$selected])) {
            return ['success' => false, 'errors' => ['front_theme' => I18n::t('themes.invalid_theme')]];
        }

        $payload = ['front_theme' => $selected];
        foreach ($this->fields($selected) as $key => $field) {
            if (!array_key_exists($key, $input)) {
                continue;
            }

            $payload[$key] = $this->normalizeValue($key, (string)$input[$key], $field);
        }

        $this->saveValues($payload);
        return ['success' => true, 'errors' => []];
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
        ];
    }

    private function normalizeFields(array $fields): array
    {
        $result = [];
        $allowedTypes = ['text', 'textarea', 'select', 'checkbox', 'number', 'file'];

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
                'label_key' => trim((string)($field['label_key'] ?? '')),
                'label' => trim((string)($field['label'] ?? $name)),
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

        $limit = $type === 'textarea' ? 1000 : 500;
        return $this->schemaConstraintValidator->truncate('settings', 'value', trim($value), $limit);
    }

    private function values(): array
    {
        $rows = $this->query->select('settings', ['key_name', 'value']);
        $values = [];

        foreach ($rows as $row) {
            $key = (string)($row['key_name'] ?? '');
            if ($key !== '') {
                $values[$key] = trim((string)($row['value'] ?? ''));
            }
        }

        return $values;
    }

    private function saveValues(array $values): void
    {
        $existingRows = $this->query->select('settings', ['key_name']);
        $existing = [];
        foreach ($existingRows as $row) {
            $key = (string)($row['key_name'] ?? '');
            if ($key !== '') {
                $existing[$key] = true;
            }
        }

        foreach ($values as $key => $value) {
            $key = $this->fieldName((string)$key);
            if ($key === '') {
                continue;
            }

            $payload = ['value' => (string)$value];
            if (isset($existing[$key])) {
                $this->query->update('settings', $payload, ['key_name' => $key]);
                continue;
            }

            $this->query->insert('settings', ['key_name' => $key, 'value' => $payload['value']]);
            $existing[$key] = true;
        }
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
