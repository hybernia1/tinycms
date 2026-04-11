<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Support\PaginationConfig;

final class AdminListState
{
    public static function basic(array $pagination, ?string $status, ?string $query): array
    {
        return [
            'listItems' => (array)($pagination['data'] ?? []),
            'listPage' => (int)($pagination['page'] ?? 1),
            'listPerPage' => (int)($pagination['per_page'] ?? PaginationConfig::perPage()),
            'listTotalPages' => (int)($pagination['total_pages'] ?? 1),
            'statusCurrent' => (string)($status ?? 'all'),
            'listQuery' => (string)($query ?? ''),
        ];
    }
}
