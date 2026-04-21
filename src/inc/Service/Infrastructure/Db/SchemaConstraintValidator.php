<?php
declare(strict_types=1);

namespace App\Service\Infrastructure\Db;

use App\Service\Application\SchemaDefinition;
use App\Service\Support\I18n;

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

    public function truncate(string $table, string $column, string $value, ?int $fallback = null): string
    {
        $max = $this->maxLength($table, $column) ?? $fallback;
        return $max === null ? $value : mb_substr($value, 0, $max);
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
                $errors[$field] = I18n::t('errors.validation.required');
                continue;
            }

            $max = $rule['max'] ?? null;
            if (is_int($max) && mb_strlen($value) > $max) {
                $errors[$field] = sprintf(I18n::t('errors.validation.max_length'), $max);
                continue;
            }

            $allowed = $rule['allowed'] ?? null;
            if (is_array($allowed) && trim($value) !== '' && !in_array($value, $allowed, true)) {
                $errors[$field] = I18n::t('errors.validation.invalid_value');
            }
        }

        return $errors;
    }
}
