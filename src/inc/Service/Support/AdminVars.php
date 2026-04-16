<?php
declare(strict_types=1);

namespace App\Service\Support;

final class AdminVars
{
    public static function make(callable $url): array
    {
        return [
            'entityApiBase' => static fn(string $entity): string => $url(self::entityApiBasePath($entity)),
            'entityList' => static fn(string $entity, array $query = []): string => $url(self::entityListPath($entity, $query)),
            'entityEditBase' => static fn(string $entity): string => $url(self::entityEditBasePath($entity)),
            'entityEdit' => static fn(string $entity, int $id): string => $url(self::entityEditPath($entity, $id)),
        ];
    }

    public static function entityApiBasePath(string $entity): string
    {
        return 'admin/api/v1/' . trim($entity, '/');
    }

    public static function entityListPath(string $entity, array $query = []): string
    {
        return self::withQuery('admin/' . trim($entity, '/'), $query);
    }

    public static function entityEditBasePath(string $entity): string
    {
        return self::withQuery('admin/' . trim($entity, '/') . '/edit', ['id' => '']);
    }

    public static function entityEditPath(string $entity, int $id): string
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
