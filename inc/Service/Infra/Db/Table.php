<?php
declare(strict_types=1);

namespace App\Service\Infra\Db;

final class Table
{
    public static function name(string $table): string
    {
        return self::prefix() . $table;
    }

    public static function prefix(): string
    {
        $prefix = defined('DB_PREFIX') ? trim((string)DB_PREFIX) : '';

        if ($prefix !== '' && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $prefix) !== 1) {
            throw new \RuntimeException('Invalid DB_PREFIX value.');
        }

        return $prefix;
    }
}
