<?php
declare(strict_types=1);

namespace App\View\Admin;

use App\Service\Support\PaginationConfig;

final class ListConfigFactory
{
    public static function build(array $input): array
    {
        $statusCurrent = (string)($input['statusCurrent'] ?? 'all');
        $perPage = (int)($input['perPage'] ?? PaginationConfig::perPage());
        $query = (string)($input['query'] ?? '');

        return [
            'meta' => [
                'name' => (string)($input['name'] ?? 'list'),
                'endpoint' => (string)($input['endpoint'] ?? ''),
                'editBase' => (string)($input['editBase'] ?? ''),
                'rootAttrs' => is_array($input['rootAttrs'] ?? null) ? $input['rootAttrs'] : [],
                'csrfMarkup' => (string)($input['csrfMarkup'] ?? ''),
            ],
            'filters' => [
                'statusEnabled' => true,
                'statusLinks' => is_array($input['statusLinks'] ?? null) ? $input['statusLinks'] : [],
                'statusCurrent' => $statusCurrent,
                'statusUrl' => is_callable($input['statusUrl'] ?? null) ? $input['statusUrl'] : null,
                'searchPlaceholder' => (string)($input['searchPlaceholder'] ?? ''),
                'searchHidden' => ['status' => $statusCurrent, 'per_page' => (string)$perPage, 'page' => '1'],
                'query' => $query,
            ],
            'table' => [
                'columns' => is_array($input['columns'] ?? null) ? $input['columns'] : [],
                'rowRenderer' => is_callable($input['rowRenderer'] ?? null) ? $input['rowRenderer'] : null,
            ],
            'pagination' => [
                'page' => (int)($input['page'] ?? 1),
                'perPage' => $perPage,
                'totalPages' => (int)($input['totalPages'] ?? 1),
                'allowedPerPage' => is_array($input['allowedPerPage'] ?? null) ? $input['allowedPerPage'] : [],
                'perPageHidden' => ['status' => $statusCurrent, 'q' => $query, 'page' => '1'],
                'url' => is_callable($input['paginationUrl'] ?? null) ? $input['paginationUrl'] : null,
            ],
            'actions' => [
                'deleteConfirmText' => (string)($input['deleteConfirmText'] ?? ''),
            ],
        ];
    }
}
