<?php
declare(strict_types=1);

namespace App\Service\Infra\Db;

use App\Service\Feature\SchemaDefinition;

final class SchemaConstraintValidator
{
    private array $rules;

    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? SchemaDefinition::columnRules();
    }

    public function maxLength(string $table, string $column): ?int
    {
        $max = $this->rules[$table][$column]['max'] ?? null;
        return is_int($max) ? $max : null;
    }

    public function validate(string $table, array $input, array $fieldToColumn): array
    {
        $errors = [];

        foreach ($fieldToColumn as $field => $column) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $rule = $this->rules[$table][$column] ?? null;

            if ($rule === null) {
                continue;
            }

            $value = (string)$input[$field];
            $nullable = (bool)($rule['nullable'] ?? true);

            if (!$nullable && trim($value) === '') {
                $errors[$field] = 'Pole je povinné.';
                continue;
            }

            $max = $rule['max'] ?? null;
            if (is_int($max) && mb_strlen($value) > $max) {
                $errors[$field] = 'Pole může mít maximálně ' . $max . ' znaků.';
                continue;
            }

            $allowed = $rule['allowed'] ?? null;
            if (is_array($allowed) && trim($value) !== '' && !in_array($value, $allowed, true)) {
                $errors[$field] = 'Pole obsahuje nepovolenou hodnotu.';
            }
        }

        return $errors;
    }
}
