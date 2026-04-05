<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\Connection;
use App\Service\Infra\Db\Query;
use App\Service\Support\I18n;

final class SettingsService
{
    private Query $query;
    private ThemeService $themes;

    public function __construct(ThemeService $themes)
    {
        $this->query = new Query(Connection::get());
        $this->themes = $themes;
    }

    public function fields(): array
    {
        $locales = I18n::availableLocales();
        $localeOptions = [];
        foreach ($locales as $locale) {
            $localeOptions[$locale] = I18n::languageLabel($locale);
        }

        $themeOptions = [];
        foreach ($this->themes->availableThemes() as $theme) {
            $themeOptions[$theme] = ucfirst($theme);
        }

        return [
            'app_lang' => [
                'label_key' => 'settings.fields.app_lang',
                'type' => 'select',
                'default' => (string)APP_LANG,
                'options' => $localeOptions,
            ],
            'theme' => [
                'label_key' => 'settings.fields.theme',
                'type' => 'select',
                'default' => $this->themes->resolveTheme('default'),
                'options' => $themeOptions,
            ],
            'sitename' => ['label_key' => 'settings.fields.sitename', 'type' => 'text', 'default' => 'TinyCMS'],
            'sitefooter' => ['label_key' => 'settings.fields.sitefooter', 'type' => 'text', 'default' => '© TinyCMS'],
            'siteauthor' => ['label_key' => 'settings.fields.siteauthor', 'type' => 'text', 'default' => 'Admin'],
            'meta_title' => ['label_key' => 'settings.fields.meta_title', 'type' => 'text', 'default' => 'TinyCMS'],
            'meta_description' => ['label_key' => 'settings.fields.meta_description', 'type' => 'textarea', 'default' => ''],
            'site_logo' => ['label_key' => 'settings.fields.site_logo', 'type' => 'media', 'default' => ''],
            'site_favicon' => ['label_key' => 'settings.fields.site_favicon', 'type' => 'media', 'default' => ''],
        ];
    }

    public function values(): array
    {
        $rows = $this->query->select('settings', ['key_name', 'value']);
        $values = [];

        foreach ($rows as $row) {
            $key = (string)($row['key_name'] ?? '');

            if ($key === '') {
                continue;
            }

            $decoded = json_decode((string)($row['value'] ?? 'null'), true);
            $values[$key] = is_scalar($decoded) || $decoded === null ? (string)($decoded ?? '') : '';
        }

        return $values;
    }

    public function defaults(): array
    {
        $result = [];

        foreach ($this->fields() as $key => $field) {
            $result[$key] = (string)($field['default'] ?? '');
        }

        return $result;
    }

    public function resolved(): array
    {
        return array_replace($this->defaults(), $this->values());
    }

    public function save(array $input): void
    {
        $fields = $this->fields();
        $existingRows = $this->query->select('settings', ['key_name']);
        $existingKeys = [];

        foreach ($existingRows as $row) {
            $key = (string)($row['key_name'] ?? '');

            if ($key !== '') {
                $existingKeys[$key] = true;
            }
        }

        foreach ($input as $key => $rawValue) {
            if (!isset($fields[$key])) {
                continue;
            }

            $value = trim((string)$rawValue);
            if ($key === 'theme') {
                $value = $this->themes->resolveTheme($value);
            }
            $payload = ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)];

            if (isset($existingKeys[$key])) {
                $this->query->update('settings', $payload, ['key_name' => $key]);
                continue;
            }

            $this->query->insert('settings', ['key_name' => $key, 'value' => $payload['value']]);
            $existingKeys[$key] = true;
        }
    }
}
