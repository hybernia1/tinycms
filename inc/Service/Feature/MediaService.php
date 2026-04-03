<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\Connection;
use App\Service\Infra\Db\Query;

final class MediaService
{
    private Query $query;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
    }

    public function create(?int $author, string $name, string $path, ?string $pathWebp): int
    {
        return $this->query->insert('media', [
            'author' => $author,
            'name' => $name,
            'path' => $path,
            'path_webp' => $pathWebp,
        ]);
    }

    public function find(int $id): ?array
    {
        $rows = $this->query->select('media', ['id', 'author', 'name', 'path', 'path_webp'], ['id' => $id]);
        return $rows[0] ?? null;
    }

    public function delete(int $id): bool
    {
        return $this->query->delete('media', ['id' => $id]) > 0;
    }
}
