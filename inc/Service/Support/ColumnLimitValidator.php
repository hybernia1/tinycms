<?php
declare(strict_types=1);

namespace App\Service\Support;

use App\Service\Feature\SchemaDefinition;

final class ColumnLimitValidator
{
    private array $limits;

    public function __construct(?array $limits = null)
    {
        $this->limits = $limits ?? SchemaDefinition::stringLimits();
    }

    public function maxLength(string $table, string $column): ?int
    {
        return $this->limits[$table][$column] ?? null;
    }

    public function validate(string $table, array $input, array $fieldToColumn): array
    {
        $errors = [];

        foreach ($fieldToColumn as $field => $column) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $max = $this->maxLength($table, $column);

            if ($max === null) {
                continue;
            }

            $value = (string)$input[$field];

            if (mb_strlen($value) > $max) {
                $errors[$field] = 'Pole může mít maximálně ' . $max . ' znaků.';
            }
        }

        return $errors;
    }
}
