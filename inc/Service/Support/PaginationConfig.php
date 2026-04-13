<?php
declare(strict_types=1);

namespace App\Service\Support;

final class PaginationConfig
{
    private const MAX_PER_PAGE = 50;

    public static function perPage(): int
    {
        $value = defined('APP_POSTS_PER_PAGE') ? (int)APP_POSTS_PER_PAGE : 10;
        if ($value < 1) {
            return 10;
        }

        return min($value, self::MAX_PER_PAGE);
    }
}
