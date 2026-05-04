<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\SchemaRules;
use App\Service\Support\I18n;

final class ThemeCustomizer
{
    private SchemaRules $schemaRules;

    public function __construct(private Content $content, ?SchemaRules $schemaRules = null)
    {
        $this->schemaRules = $schemaRules ?? new SchemaRules();
    }

    public static function field(string $key): array
    {
        $key = self::fieldName($key);
        $field = self::definitions()[$key] ?? null;
        if ($field === null) {
            return [];
        }

        return self::translated($key, $field);
    }

    public static function normalizeFields(array $fields): array
    {
        $result = [];
        $allowedTypes = ['text', 'textarea', 'select', 'checkbox', 'number', 'file', 'color', 'content_picker', 'widget_area_visibility'];

        foreach ($fields as $key => $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = self::fieldName((string)$key);
            if ($name === '') {
                continue;
            }

            $type = (string)($field['type'] ?? 'text');
            if (!in_array($type, $allowedTypes, true)) {
                continue;
            }

            $result[$name] = [
                'type' => $type,
                'label' => trim((string)($field['label'] ?? '')),
                'default' => (string)($field['default'] ?? ($type === 'checkbox' ? '0' : '')),
                'options' => self::normalizeOptions((array)($field['options'] ?? [])),
                'min' => (int)($field['min'] ?? 0),
                'max' => (int)($field['max'] ?? 1000),
                'empty_label' => trim((string)($field['empty_label'] ?? '')),
                'placeholder' => trim((string)($field['placeholder'] ?? '')),
            ];
        }

        return $result;
    }

    public static function normalizeSections(array $sections, array $availableFields): array
    {
        $available = array_fill_keys($availableFields, true);
        $result = [];

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $key = self::fieldName((string)($section['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $fields = [];
            foreach ((array)($section['fields'] ?? []) as $field) {
                $field = self::fieldName((string)$field);
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

    public function normalizeValue(string $value, array $field): string
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

        if ($type === 'content_picker') {
            return $this->normalizePublishedContentId($value);
        }

        if ($type === 'widget_area_visibility') {
            return $this->normalizeSlugList($value, '*');
        }

        if ($type === 'color') {
            return $this->normalizeColor($value, $default);
        }

        $limit = max(1, (int)($field['max'] ?? ($type === 'textarea' ? 10000 : 500)));
        return $this->schemaRules->truncate('settings', 'value', trim($value), $limit);
    }

    public function validateValue(string $value, array $field): string
    {
        $type = (string)($field['type'] ?? 'text');
        $value = trim($value);

        if ($type === 'checkbox') {
            return in_array($value, ['0', '1'], true) ? '' : I18n::t('themes.invalid_value');
        }

        if ($type === 'number') {
            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                return I18n::t('themes.invalid_number');
            }

            $number = (int)$value;
            $min = (int)($field['min'] ?? 0);
            $max = max($min, (int)($field['max'] ?? 1000));
            return $number >= $min && $number <= $max ? '' : sprintf(I18n::t('themes.number_range'), $min, $max);
        }

        if ($type === 'select') {
            return array_key_exists($value, (array)($field['options'] ?? [])) ? '' : I18n::t('themes.invalid_value');
        }

        if ($type === 'content_picker') {
            return $this->validPublishedContentId($value) ? '' : I18n::t('themes.invalid_content');
        }

        if ($type === 'widget_area_visibility') {
            return $this->validSlugList($value, '*') ? '' : I18n::t('themes.invalid_value');
        }

        if ($type === 'color') {
            return $this->validColor($value) ? '' : I18n::t('themes.invalid_value');
        }

        if (str_contains($value, "\0")) {
            return I18n::t('themes.invalid_value');
        }

        $limit = max(1, (int)($field['max'] ?? ($type === 'textarea' ? 10000 : 500)));
        return mb_strlen($value) <= $limit ? '' : sprintf(I18n::t('themes.max_length'), $limit);
    }

    private static function definitions(): array
    {
        return [
            'brand_display' => [
                'type' => 'select',
                'label_key' => 'theme.customizer_fields.branding',
                'default' => 'both',
                'options' => [
                    'both' => 'theme.customizer_options.brand_both',
                    'logo' => 'theme.customizer_options.brand_logo',
                    'title' => 'theme.customizer_options.brand_title',
                    'none' => 'theme.customizer_options.brand_none',
                ],
            ],
            'logo' => ['type' => 'file', 'label_key' => 'theme.customizer_fields.logo'],
            'front_home_content' => [
                'type' => 'content_picker',
                'label_key' => 'theme.customizer_fields.homepage_content',
                'default' => '',
                'empty_label_key' => 'theme.customizer_fields.homepage_loop',
                'placeholder_key' => 'theme.customizer_fields.homepage_search',
            ],
            'layout_width' => [
                'type' => 'select',
                'label_key' => 'theme.customizer_fields.layout_width',
                'default' => 'default',
                'options' => [
                    'narrow' => 'theme.customizer_options.width_narrow',
                    'default' => 'theme.customizer_options.width_default',
                    'wide' => 'theme.customizer_options.width_wide',
                    'full' => 'theme.customizer_options.width_full',
                ],
            ],
            'enabled_widget_areas' => ['type' => 'widget_area_visibility', 'label_key' => 'theme.customizer_fields.enabled_widget_areas', 'default' => '*'],
            'enable_menu' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.enable_menu', 'default' => '1'],
            'enable_search' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.enable_search', 'default' => '1'],
            'enable_footer' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.enable_footer', 'default' => '1'],
            'single_show_thumbnail' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.show_thumbnail', 'default' => '1'],
            'single_meta_date' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.meta_date', 'default' => '1'],
            'single_meta_author' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.meta_author', 'default' => '1'],
            'single_meta_comments' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.meta_comments', 'default' => '0'],
            'single_show_terms' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.show_terms', 'default' => '1'],
            'archive_show_thumbnail' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.show_thumbnail', 'default' => '1'],
            'archive_meta_date' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.meta_date', 'default' => '1'],
            'archive_meta_author' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.meta_author', 'default' => '1'],
            'archive_meta_comments' => ['type' => 'checkbox', 'label_key' => 'theme.customizer_fields.meta_comments', 'default' => '1'],
            'color_bg' => ['type' => 'color', 'label_key' => 'theme.customizer_fields.color_bg', 'default' => '#f7f7f2'],
            'color_surface' => ['type' => 'color', 'label_key' => 'theme.customizer_fields.color_surface', 'default' => '#ffffff'],
            'color_surface_alt' => ['type' => 'color', 'label_key' => 'theme.customizer_fields.color_surface_alt', 'default' => '#efefea'],
            'color_text' => ['type' => 'color', 'label_key' => 'theme.customizer_fields.color_text', 'default' => '#1f2328'],
            'color_muted' => ['type' => 'color', 'label_key' => 'theme.customizer_fields.color_muted', 'default' => '#687076'],
            'color_border' => ['type' => 'color', 'label_key' => 'theme.customizer_fields.color_border', 'default' => '#c8ccc8'],
            'color_accent' => ['type' => 'color', 'label_key' => 'theme.customizer_fields.color_accent', 'default' => '#2457c5'],
            'color_accent_strong' => ['type' => 'color', 'label_key' => 'theme.customizer_fields.color_accent_strong', 'default' => '#163f96'],
            'color_accent_soft' => ['type' => 'color', 'label_key' => 'theme.customizer_fields.color_accent_soft', 'default' => '#edf2ff'],
            'custom_css' => ['type' => 'textarea', 'label_key' => 'theme.customizer_fields.custom_css', 'max' => 20000],
            'footer_text' => ['type' => 'textarea', 'label_key' => 'theme.customizer_fields.footer_text'],
        ];
    }

    private static function translated(string $key, array $field): array
    {
        $result = $field;
        $result['label'] = I18n::t((string)($field['label_key'] ?? ''), $key);
        unset($result['label_key']);

        foreach (['empty_label', 'placeholder'] as $name) {
            $translationKey = (string)($field[$name . '_key'] ?? '');
            if ($translationKey !== '') {
                $result[$name] = I18n::t($translationKey);
            }
            unset($result[$name . '_key']);
        }

        if (isset($field['options']) && is_array($field['options'])) {
            $result['options'] = [];
            foreach ($field['options'] as $value => $translationKey) {
                $result['options'][(string)$value] = I18n::t((string)$translationKey, (string)$value);
            }
        }

        return $result;
    }

    public static function fieldName(string $value): string
    {
        $clean = trim($value);
        return preg_match('/^[a-z0-9_]{1,100}$/i', $clean) === 1 ? $clean : '';
    }

    private static function normalizeOptions(array $options): array
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

    private function normalizeColor(string $value, string $default): string
    {
        $value = strtolower(trim($value));
        if ($value === 'transparent') {
            return $value;
        }

        if (preg_match('/^#[0-9a-f]{6}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $value, $matches) === 1) {
            return '#' . $matches[1] . $matches[1] . $matches[2] . $matches[2] . $matches[3] . $matches[3];
        }

        $default = strtolower(trim($default));
        if ($default === 'transparent') {
            return $default;
        }

        return preg_match('/^#[0-9a-f]{6}$/', $default) === 1 ? $default : '';
    }

    private function normalizePublishedContentId(string $value): string
    {
        $id = (int)$value;
        return $this->content->findPublishedSummary($id) !== null ? (string)$id : '';
    }

    private function normalizeSlugList(string $value, string $allValue = ''): string
    {
        $value = strtolower(trim($value));
        if ($allValue !== '' && $value === $allValue) {
            return $allValue;
        }

        $items = [];
        foreach (explode(',', $value) as $item) {
            $item = $this->slug((string)$item);
            if ($item !== '') {
                $items[$item] = true;
            }
        }

        return implode(',', array_keys($items));
    }

    private function validPublishedContentId(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return true;
        }

        $id = (int)$value;
        return (string)$id === $value && $id > 0 && $this->content->findPublishedSummary($id) !== null;
    }

    private function validSlugList(string $value, string $allValue = ''): bool
    {
        $value = strtolower(trim($value));
        if ($allValue !== '' && $value === $allValue) {
            return true;
        }

        foreach (explode(',', $value) as $item) {
            if (trim($item) !== '' && $this->slug((string)$item) === '') {
                return false;
            }
        }

        return true;
    }

    private function validColor(string $value): bool
    {
        $value = strtolower(trim($value));
        return $value === 'transparent'
            || preg_match('/^#[0-9a-f]{6}$/', $value) === 1
            || preg_match('/^#[0-9a-f]{3}$/', $value) === 1;
    }

    private function slug(string $value): string
    {
        $clean = strtolower(trim($value));
        return preg_match('/^[a-z0-9_-]{1,100}$/', $clean) === 1 ? $clean : '';
    }
}
