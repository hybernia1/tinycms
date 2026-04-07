<?php
declare(strict_types=1);

namespace App\Service\Support;

final class PaginationConfig
{
    public static function perPage(): int
    {
        $value = defined('APP_POSTS_PER_PAGE') ? (int)APP_POSTS_PER_PAGE : 10;
        return $value > 0 ? $value : 10;
    }

    public static function allowed(): array
    {
        $base = self::perPage();
        return array_values(array_unique([$base, $base * 2, $base * 5]));
    }
}
