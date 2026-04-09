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
        return defined('DB_PREFIX') ? trim((string)DB_PREFIX) : '';
    }
}
