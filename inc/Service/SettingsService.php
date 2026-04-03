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

    public function fields(): array
    {
        return [
            'sitename' => ['label' => 'Site name', 'type' => 'text', 'default' => 'TinyCMS'],
            'sitefooter' => ['label' => 'Site footer', 'type' => 'text', 'default' => '© TinyCMS'],
            'siteauthor' => ['label' => 'Site author', 'type' => 'text', 'default' => 'Admin'],
            'meta_title' => ['label' => 'Meta title', 'type' => 'text', 'default' => 'TinyCMS'],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'default' => ''],
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

        foreach ($input as $key => $rawValue) {
            if (!isset($fields[$key])) {
                continue;
            }

            $value = trim((string)$rawValue);
            $payload = ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)];
            $exists = $this->query->select('settings', ['key_name'], ['key_name' => $key]) !== [];

            if ($exists) {
                $this->query->update('settings', $payload, ['key_name' => $key]);
                continue;
            }

            $this->query->insert('settings', ['key_name' => $key, 'value' => $payload['value']]);
        }
    }
}
