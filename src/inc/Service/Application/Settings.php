<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Support\DateTimeFormatter;
use App\Service\Support\I18n;
use App\Service\Support\RequestContext;

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
                'section' => 'localization',
                'type' => 'select',
                'default' => (string)APP_LANG,
                'options' => $localeOptions,
            ],
            'app_date_format' => [
                'label_key' => 'settings.fields.app_date_format',
                'section' => 'localization',
                'type' => 'select',
                'default' => DateTimeFormatter::normalizeDateFormat((string)(defined('APP_DATE_FORMAT') ? APP_DATE_FORMAT : '')),
                'options' => DateTimeFormatter::dateFormatOptions(),
            ],
            'app_datetime_format' => [
                'label_key' => 'settings.fields.app_datetime_format',
                'section' => 'localization',
                'type' => 'select',
                'default' => DateTimeFormatter::normalizeDateTimeFormat((string)(defined('APP_DATETIME_FORMAT') ? APP_DATETIME_FORMAT : '')),
                'options' => DateTimeFormatter::dateTimeFormatOptions(),
            ],
            'sitename' => ['label_key' => 'settings.fields.sitename', 'section' => 'general', 'type' => 'text', 'default' => 'TinyCMS'],
            'siteauthor' => ['label_key' => 'settings.fields.siteauthor', 'section' => 'general', 'type' => 'text', 'default' => 'Admin'],
            'meta_description' => ['label_key' => 'settings.fields.meta_description', 'section' => 'general', 'type' => 'textarea', 'default' => ''],
            'website_url' => ['label_key' => 'settings.fields.website_url', 'section' => 'general', 'type' => 'text', 'default' => ''],
            'website_email' => ['label_key' => 'settings.fields.website_email', 'section' => 'general', 'type' => 'text', 'default' => ''],
            'front_home_content' => [
                'label_key' => 'settings.fields.front_home_content',
                'section' => 'content',
                'type' => 'select',
                'default' => '',
                'options' => $homePageOptions,
            ],
            'front_posts_per_page' => [
                'label_key' => 'settings.fields.front_posts_per_page',
                'section' => 'content',
                'type' => 'number',
                'default' => (string)APP_POSTS_PER_PAGE,
                'min' => 1,
                'max' => 100,
            ],
            'front_theme' => [
                'label_key' => 'settings.fields.front_theme',
                'section' => 'appearance',
                'type' => 'select',
                'default' => 'default',
                'options' => $themeOptions,
            ],
            'allow_registration' => [
                'label_key' => 'settings.fields.allow_registration',
                'section' => 'content',
                'type' => 'select',
                'default' => '0',
                'options' => [
                    '0' => I18n::t('settings.options.allow_registration.disabled'),
                    '1' => I18n::t('settings.options.allow_registration.enabled'),
                ],
            ],
            'logo' => ['label_key' => 'settings.fields.logo', 'section' => 'appearance', 'type' => 'file', 'default' => ''],
            'favicon' => ['label_key' => 'settings.fields.favicon', 'section' => 'appearance', 'type' => 'file', 'default' => ''],
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
        $fields = $this->fields();
        $result = [];

        foreach ($fields as $key => $field) {
            $result[$key] = (string)($field['default'] ?? '');
        }

        $result = array_replace($result, $this->values());
        foreach ($fields as $key => $field) {
            if (($field['type'] ?? '') !== 'select') {
                continue;
            }

            $options = (array)($field['options'] ?? []);
            if (!array_key_exists((string)($result[$key] ?? ''), $options)) {
                $result[$key] = (string)($field['default'] ?? '');
            }
        }

        return $result;
    }

    public function save(array $input): void
    {
        $fields = $this->fields();
        $currentValues = $this->values();
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
            if ($key === 'website_email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $value = '';
            }
            if ($key === 'website_url' && !RequestContext::isValidWebsiteUrl($value)) {
                $value = '';
            }
            if ($key === 'website_url' && $value === '') {
                $currentUrl = trim((string)($currentValues['website_url'] ?? ''));
                if ($currentUrl !== '') {
                    $value = $currentUrl;
                }
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
