<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Support\I18n;

final class Settings
{
    private Query $query;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
    }

    public function fields(): array
    {
        $locales = I18n::availableLocales();
        $localeOptions = [];
        foreach ($locales as $locale) {
            $localeOptions[$locale] = I18n::languageLabel($locale);
        }
        $themeOptions = $this->themeOptions();
        $homePageOptions = $this->homePageOptions();

        return [
            'app_lang' => [
                'label_key' => 'settings.fields.app_lang',
                'type' => 'select',
                'default' => (string)APP_LANG,
                'options' => $localeOptions,
            ],
            'sitename' => ['label_key' => 'settings.fields.sitename', 'type' => 'text', 'default' => 'TinyCMS'],
            'siteauthor' => ['label_key' => 'settings.fields.siteauthor', 'type' => 'text', 'default' => 'Admin'],
            'meta_title' => ['label_key' => 'settings.fields.meta_title', 'type' => 'text', 'default' => 'TinyCMS'],
            'meta_description' => ['label_key' => 'settings.fields.meta_description', 'type' => 'textarea', 'default' => ''],
            'front_home_mode' => [
                'label_key' => 'settings.fields.front_home_mode',
                'type' => 'select',
                'default' => 'latest',
                'options' => [
                    'latest' => I18n::t('settings.options.front_home_mode.latest'),
                    'content' => I18n::t('settings.options.front_home_mode.content'),
                ],
            ],
            'front_home_content' => [
                'label_key' => 'settings.fields.front_home_content',
                'type' => 'select',
                'default' => '',
                'options' => $homePageOptions,
            ],
            'front_posts_per_page' => [
                'label_key' => 'settings.fields.front_posts_per_page',
                'type' => 'number',
                'default' => (string)APP_POSTS_PER_PAGE,
                'min' => 1,
                'max' => 100,
            ],
            'front_theme' => [
                'label_key' => 'settings.fields.front_theme',
                'type' => 'select',
                'default' => 'default',
                'options' => $themeOptions,
            ],
            'allow_registration' => [
                'label_key' => 'settings.fields.allow_registration',
                'type' => 'select',
                'default' => '0',
                'options' => [
                    '0' => I18n::t('settings.options.allow_registration.disabled'),
                    '1' => I18n::t('settings.options.allow_registration.enabled'),
                ],
            ],
            'favicon' => ['label_key' => 'settings.fields.favicon', 'type' => 'file', 'default' => ''],
            'logo' => ['label_key' => 'settings.fields.logo', 'type' => 'file', 'default' => ''],
            'website_email' => ['label_key' => 'settings.fields.website_email', 'type' => 'text', 'default' => ''],
        ];
    }

    private function homePageOptions(): array
    {
        $rows = $this->query->select('content', ['id', 'name'], ['status' => 'published']);
        $options = ['' => I18n::t('settings.options.front_home_content.none')];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $name = trim((string)($row['name'] ?? ''));
            $options[(string)$id] = $name !== '' ? $name : sprintf('#%d', $id);
        }

        return $options;
    }

    private function themeOptions(): array
    {
        $themesDir = defined('THEMES_DIR') ? (string)THEMES_DIR : 'themes/';
        $path = rtrim(BASE_DIR . '/' . trim($themesDir, '/'), '/');
        if (!is_dir($path)) {
            return ['default' => 'default'];
        }

        $items = scandir($path);
        if (!is_array($items)) {
            return ['default' => 'default'];
        }

        $options = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (!is_dir($fullPath)) {
                continue;
            }

            $key = trim($item);
            if ($key === '') {
                continue;
            }
            $options[$key] = $key;
        }

        if ($options === []) {
            return ['default' => 'default'];
        }

        ksort($options);
        return $options;
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

            $values[$key] = trim((string)($row['value'] ?? ''));
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
            if ($key === 'front_posts_per_page') {
                $numeric = (int)$value;
                $min = (int)($fields[$key]['min'] ?? 1);
                $max = (int)($fields[$key]['max'] ?? 100);
                $value = (string)max($min, min($max, $numeric > 0 ? $numeric : $min));
            }
            if (($fields[$key]['type'] ?? '') === 'select') {
                $options = (array)($fields[$key]['options'] ?? []);
                if ($value !== '' && !array_key_exists($value, $options)) {
                    $value = (string)($fields[$key]['default'] ?? '');
                }
            }
            $payload = ['value' => $value];

            if (isset($existingKeys[$key])) {
                $this->query->update('settings', $payload, ['key_name' => $key]);
                continue;
            }

            $this->query->insert('settings', ['key_name' => $key, 'value' => $payload['value']]);
            $existingKeys[$key] = true;
        }
    }
}
