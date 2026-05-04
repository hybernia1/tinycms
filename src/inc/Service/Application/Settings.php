<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\SchemaRules;
use App\Service\Support\Date;
use App\Service\Support\I18n;
use App\Service\Support\RequestContext;

final class Settings
{
    private Query $query;
    private SchemaRules $schemaRules;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
        $this->schemaRules = new SchemaRules();
    }

    public function fields(): array
    {
        $locales = I18n::availableLocales();
        $localeOptions = [];
        foreach ($locales as $locale) {
            $localeOptions[$locale] = I18n::languageLabel($locale);
        }
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
                'default' => Date::normalizeDateFormat((string)(defined('APP_DATE_FORMAT') ? APP_DATE_FORMAT : '')),
                'options' => Date::dateFormatOptions(),
            ],
            'app_datetime_format' => [
                'label_key' => 'settings.fields.app_datetime_format',
                'section' => 'localization',
                'type' => 'select',
                'default' => Date::normalizeDateTimeFormat((string)(defined('APP_DATETIME_FORMAT') ? APP_DATETIME_FORMAT : '')),
                'options' => Date::dateTimeFormatOptions(),
            ],
            'sitename' => ['label_key' => 'settings.fields.sitename', 'section' => 'general', 'type' => 'text', 'default' => 'TinyCMS'],
            'siteauthor' => ['label_key' => 'settings.fields.siteauthor', 'section' => 'general', 'type' => 'text', 'default' => 'Admin'],
            'meta_description' => ['label_key' => 'settings.fields.meta_description', 'section' => 'general', 'type' => 'textarea', 'default' => ''],
            'website_url' => ['label_key' => 'settings.fields.website_url', 'section' => 'general', 'type' => 'text', 'default' => ''],
            'website_email' => ['label_key' => 'settings.fields.website_email', 'section' => 'general', 'type' => 'text', 'default' => ''],
            'favicon' => ['label_key' => 'settings.fields.favicon', 'section' => 'general', 'type' => 'file', 'default' => ''],
            'front_posts_per_page' => [
                'label_key' => 'settings.fields.front_posts_per_page',
                'section' => 'content',
                'type' => 'number',
                'default' => (string)APP_POSTS_PER_PAGE,
                'min' => 1,
                'max' => 100,
            ],
            'media_small_width' => [
                'label_key' => 'settings.fields.media_small_width',
                'section' => 'media',
                'type' => 'number',
                'default' => '300',
                'min' => 1,
                'max' => 3000,
            ],
            'media_small_height' => [
                'label_key' => 'settings.fields.media_small_height',
                'section' => 'media',
                'type' => 'number',
                'default' => '300',
                'min' => 1,
                'max' => 3000,
            ],
            'media_medium_width' => [
                'label_key' => 'settings.fields.media_medium_width',
                'section' => 'media',
                'type' => 'number',
                'default' => '768',
                'min' => 1,
                'max' => 3000,
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
            'comments_allow_anonymous' => [
                'label_key' => 'settings.fields.comments_allow_anonymous',
                'section' => 'content',
                'type' => 'select',
                'default' => '0',
                'options' => [
                    '0' => I18n::t('settings.options.comments_allow_anonymous.disabled'),
                    '1' => I18n::t('settings.options.comments_allow_anonymous.enabled'),
                ],
            ],
        ];
    }

    public function values(): array
    {
        $rows = $this->query->select('settings', ['key_name', 'value']);
        $values = [];

        foreach ($rows as $row) {
            $key = trim((string)($row['key_name'] ?? ''));
            if ($key !== '') {
                $values[$key] = trim((string)($row['value'] ?? ''));
            }
        }

        return $values;
    }

    public function hasSection(string $section): bool
    {
        $section = strtolower(trim($section));
        foreach ($this->fields() as $field) {
            if ((string)($field['section'] ?? 'general') === $section) {
                return true;
            }
        }

        return false;
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
            $type = (string)($field['type'] ?? '');
            if ($type === 'select') {
                $options = (array)($field['options'] ?? []);
                if (!array_key_exists((string)($result[$key] ?? ''), $options)) {
                    $result[$key] = (string)($field['default'] ?? '');
                }
            }
            if ($type === 'number') {
                $result[$key] = $this->normalizeNumber((string)($result[$key] ?? ''), $field);
            }
        }

        return $result;
    }

    public function save(array $input): void
    {
        $fields = $this->fields();
        $currentValues = $this->values();
        $values = [];

        foreach ($input as $key => $rawValue) {
            if (!isset($fields[$key])) {
                continue;
            }

            $value = trim((string)$rawValue);
            $type = (string)($fields[$key]['type'] ?? '');
            if ($type === 'password' && $value === '' && isset($currentValues[$key])) {
                $value = (string)$currentValues[$key];
            }
            if ($type === 'number') {
                $value = $this->normalizeNumber($value, $fields[$key]);
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
            if ($type === 'select') {
                $options = (array)($fields[$key]['options'] ?? []);
                if ($value !== '' && !array_key_exists($value, $options)) {
                    $value = (string)($fields[$key]['default'] ?? '');
                }
            }
            if (in_array($key, ['sitename', 'siteauthor', 'meta_description'], true)) {
                $value = $this->schemaRules->truncate(
                    'settings',
                    'value',
                    $value,
                    1000
                );
            }
            $values[$key] = $value;
        }

        $this->saveValues($values);
    }

    public function saveValues(array $values): void
    {
        if ($values === []) {
            return;
        }

        $existing = array_fill_keys(array_keys($this->values()), true);

        foreach ($values as $key => $value) {
            $key = trim((string)$key);
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

    private function normalizeNumber(string $value, array $field): string
    {
        $min = (int)($field['min'] ?? 1);
        $max = max($min, (int)($field['max'] ?? $min));
        $numeric = (int)$value;

        return (string)max($min, min($max, $numeric > 0 ? $numeric : $min));
    }

}
