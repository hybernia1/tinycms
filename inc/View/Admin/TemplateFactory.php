<?php
declare(strict_types=1);

namespace App\View\Admin;

use App\Service\Support\PaginationConfig;

final class TemplateFactory
{
    private const ADMIN_ONLY_PATHS = ['admin/users', 'admin/settings'];

    public static function adminPageType(string $currentPath): string
    {
        return match (true) {
            str_ends_with($currentPath, 'admin/users/add') => 'users_add',
            str_ends_with($currentPath, 'admin/users/edit') => 'users_edit',
            str_ends_with($currentPath, 'admin/users') => 'users_list',
            str_ends_with($currentPath, 'admin/content/add') => 'content_add',
            str_ends_with($currentPath, 'admin/content/edit') => 'content_edit',
            str_ends_with($currentPath, 'admin/content') => 'content_list',
            str_ends_with($currentPath, 'admin/media/add') => 'media_add',
            str_ends_with($currentPath, 'admin/media/edit') => 'media_edit',
            str_ends_with($currentPath, 'admin/media') => 'media_list',
            str_ends_with($currentPath, 'admin/terms/add') => 'terms_add',
            str_ends_with($currentPath, 'admin/terms/edit') => 'terms_edit',
            str_ends_with($currentPath, 'admin/terms') => 'terms_list',
            str_ends_with($currentPath, 'admin/settings') => 'settings',
            default => 'other',
        };
    }


    public static function listState(array $pagination, mixed $status, mixed $query, mixed $statusCounts): array
    {
        return [
            'items' => is_array($pagination['data'] ?? null) ? $pagination['data'] : [],
            'page' => (int)($pagination['page'] ?? 1),
            'perPage' => (int)($pagination['per_page'] ?? PaginationConfig::perPage()),
            'totalPages' => (int)($pagination['total_pages'] ?? 1),
            'statusCurrent' => (string)($status ?? 'all'),
            'query' => (string)($query ?? ''),
            'statusCounts' => is_array($statusCounts) ? $statusCounts : [],
        ];
    }

    public static function adminNavItems(array $items, mixed $authUser, string $currentPath): array
    {
        $resolved = [];
        $role = is_array($authUser) ? (string)($authUser['role'] ?? '') : '';

        foreach ($items as $item) {
            $entry = is_array($item) ? $item : [];
            $itemUrl = (string)($entry['url'] ?? '');
            $itemPath = trim(parse_url($itemUrl, PHP_URL_PATH) ?? '', '/');

            if ($role !== 'admin' && in_array($itemPath, self::ADMIN_ONLY_PATHS, true)) {
                continue;
            }

            $entry['active'] = $itemPath !== '' && str_starts_with($currentPath, $itemPath);
            $resolved[] = $entry;
        }

        return $resolved;
    }


    public static function fieldError(array $errors, string $key): string
    {
        $message = trim((string)($errors[$key] ?? ''));
        if ($message == '') {
            return '';
        }

        return '<small class="text-danger">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</small>';
    }

    public static function confirmModal(array $config): string
    {
        $modalAttrs = ['class' => 'modal-overlay'];
        if (($config['withDataModal'] ?? true) === true) {
            $modalAttrs['data-modal'] = null;
        }
        if (!empty($config['id'])) {
            $modalAttrs['id'] = (string)$config['id'];
        }
        foreach ((array)($config['modalAttrs'] ?? []) as $key => $value) {
            $modalAttrs[(string)$key] = $value;
        }

        $paragraphAttrs = ['data-modal-text' => null];
        foreach ((array)($config['textAttrs'] ?? []) as $key => $value) {
            $paragraphAttrs[(string)$key] = $value;
        }

        $cancelLabel = (string)($config['cancelLabel'] ?? '');
        $confirmLabel = (string)($config['confirmLabel'] ?? '');
        $text = (string)($config['text'] ?? '');

        return '<div' . self::attrs($modalAttrs) . '><div class="modal"><p' . self::attrs($paragraphAttrs) . '>'
            . htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
            . '</p><div class="modal-actions"><button class="btn btn-light" type="button"'
            . self::attrs((array)($config['cancelAttrs'] ?? []))
            . '>' . htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8')
            . '</button><button class="btn btn-primary" type="button"'
            . self::attrs((array)($config['confirmAttrs'] ?? []))
            . '>' . htmlspecialchars($confirmLabel, ENT_QUOTES, 'UTF-8')
            . '</button></div></div></div>';
    }

    public static function listConfig(array $input): array
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

    private static function attrs(array $attrs): string
    {
        $compiled = '';
        foreach ($attrs as $key => $value) {
            $compiled .= ' ' . htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
            if ($value !== null) {
                $compiled .= '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return $compiled;
    }
}
