<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Db\Connection;
use App\Service\Db\Query;

final class SettingsService
{
    private Query $query;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
    }

    public function groups(): array
    {
        return [
            'main' => [
                'label' => 'Main settings',
                'fields' => [
                    'sitename' => ['label' => 'Site name', 'type' => 'text', 'default' => 'TinyCMS'],
                    'sitefooter' => ['label' => 'Site footer', 'type' => 'text', 'default' => '© TinyCMS'],
                    'siteauthor' => ['label' => 'Site author', 'type' => 'text', 'default' => 'Admin'],
                ],
            ],
            'custom' => [
                'label' => 'Custom settings',
                'fields' => [
                    'timezone' => ['label' => 'Timezone', 'type' => 'select', 'default' => 'UTC', 'options' => $this->utcTimezoneOptions()],
                    'dateformat_mode' => ['label' => 'Date format', 'type' => 'select', 'default' => 'cs', 'options' => [
                        'cs' => '3. 4. 2026 - j. n. Y',
                        'db' => 'Výchozí z databáze',
                        'custom' => 'Vlastní',
                    ]],
                    'dateformat_custom' => ['label' => 'Date format custom', 'type' => 'text', 'default' => 'j. n. Y'],
                    'timeformat_mode' => ['label' => 'Time format', 'type' => 'select', 'default' => 'short', 'options' => [
                        'short' => '10:50',
                        'custom' => 'Vlastní',
                    ]],
                    'timeformat_custom' => ['label' => 'Time format custom', 'type' => 'text', 'default' => 'H:i'],
                    'currency' => ['label' => 'Currency', 'type' => 'text', 'default' => 'CZK'],
                    'theme' => ['label' => 'Default theme', 'type' => 'select', 'default' => 'light', 'options' => ['light' => 'Light', 'dark' => 'Dark']],
                ],
            ],
            'seo' => [
                'label' => 'SEO settings',
                'fields' => [
                    'meta_title' => ['label' => 'Meta title', 'type' => 'text', 'default' => 'TinyCMS'],
                    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => ''],
                ],
            ],
        ];
    }

    public function values(): array
    {
        $rows = $this->query->select('settings', ['group_name', 'key_name', 'value']);
        $values = [];

        foreach ($rows as $row) {
            $group = (string)($row['group_name'] ?? '');
            $key = (string)($row['key_name'] ?? '');
            $decoded = json_decode((string)($row['value'] ?? 'null'), true);
            $values[$group][$key] = is_scalar($decoded) || $decoded === null ? (string)($decoded ?? '') : '';
        }

        return $values;
    }

    public function defaults(): array
    {
        $result = [];

        foreach ($this->groups() as $groupKey => $group) {
            foreach ($group['fields'] as $key => $field) {
                $result[$groupKey][$key] = (string)($field['default'] ?? '');
            }
        }

        return $result;
    }


    public function resolved(): array
    {
        return array_replace_recursive($this->defaults(), $this->values());
    }

    public function save(array $input): void
    {
        $groups = $this->groups();
        $pdo = Connection::get();
        $stmt = $pdo->prepare('INSERT INTO settings (group_name, key_name, value, value_type, is_public) VALUES (:group_name, :key_name, :value, :value_type, :is_public) ON DUPLICATE KEY UPDATE value = VALUES(value), value_type = VALUES(value_type), is_public = VALUES(is_public)');

        foreach ($input as $groupName => $groupInput) {
            if (!isset($groups[$groupName]) || !is_array($groupInput)) {
                continue;
            }

            foreach ($groupInput as $keyName => $rawValue) {
                if (!isset($groups[$groupName]['fields'][$keyName])) {
                    continue;
                }

                $value = trim((string)$rawValue);
                $field = $groups[$groupName]['fields'][$keyName];

                if (($field['type'] ?? '') === 'select') {
                    $options = array_map('strval', array_keys((array)($field['options'] ?? [])));

                    if (!in_array($value, $options, true)) {
                        $value = (string)($field['default'] ?? '');
                    }
                }

                $stmt->execute([
                    'group_name' => $groupName,
                    'key_name' => $keyName,
                    'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                    'value_type' => 'string',
                    'is_public' => 1,
                ]);
            }
        }
    }

    private function utcTimezoneOptions(): array
    {
        $options = [];

        for ($offset = -12 * 60; $offset <= 14 * 60; $offset += 30) {
            $sign = $offset < 0 ? '-' : '+';
            $abs = abs($offset);
            $hours = str_pad((string)intdiv($abs, 60), 2, '0', STR_PAD_LEFT);
            $minutes = str_pad((string)($abs % 60), 2, '0', STR_PAD_LEFT);
            $value = $offset === 0 ? 'UTC' : 'UTC' . $sign . $hours . ':' . $minutes;
            $options[$value] = $value;
        }

        return $options;
    }
}
