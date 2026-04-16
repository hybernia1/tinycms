<?php
declare(strict_types=1);

namespace App\Service\Support;

final class AdminUrl
{
    public static function entityApiBase(string $entity): string
    {
        return 'admin/api/v1/' . trim($entity, '/');
    }

    public static function entityList(string $entity, array $query = []): string
    {
        return self::withQuery('admin/' . trim($entity, '/'), $query);
    }

    public static function entityEditBase(string $entity): string
    {
        return self::withQuery('admin/' . trim($entity, '/') . '/edit', ['id' => '']);
    }

    public static function entityEdit(string $entity, int $id): string
    {
        return self::withQuery('admin/' . trim($entity, '/') . '/edit', ['id' => $id]);
    }

    private static function withQuery(string $path, array $query): string
    {
        if ($query === []) {
            return $path;
        }

        return $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
