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
                    'timezone' => ['label' => 'Timezone', 'type' => 'text', 'default' => 'Europe/Prague'],
                    'dateformat' => ['label' => 'Date format', 'type' => 'text', 'default' => 'd.m.Y'],
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
}
