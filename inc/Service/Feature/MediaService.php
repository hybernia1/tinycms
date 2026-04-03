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

    public function paginate(int $page = 1, int $perPage = 10, string $search = ''): array
    {
        return $this->query->paginate('media', [
            'id',
            'name',
            'path',
            'path_webp',
            'author',
            '(SELECT name FROM users WHERE users.ID = media.author LIMIT 1) AS author_name',
        ], [], [
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => 'id',
            'orderByAllowed' => ['id', 'name', 'path', 'path_webp', 'author'],
            'orderDir' => 'DESC',
            'search' => $search,
            'searchColumns' => ['name', 'path', 'path_webp'],
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

    public function save(array $input, ?int $id = null): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $path = trim((string)($input['path'] ?? ''));
        $pathWebp = trim((string)($input['path_webp'] ?? ''));
        $author = $this->resolveAuthor($input);
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Název je povinný.';
        }

        if ($path === '') {
            $errors['path'] = 'Path je povinná.';
        }

        if (($input['author'] ?? '') !== '' && $author === null) {
            $errors['author'] = 'Autor není validní.';
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $payload = [
            'name' => $name,
            'path' => $path,
            'path_webp' => $pathWebp === '' ? null : $pathWebp,
            'author' => $author,
        ];

        if ($id === null) {
            $newId = $this->query->insert('media', $payload);
            return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
        }

        $updated = $this->query->update('media', $payload, ['id' => $id]);
        return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
    }

    public function authorOptions(): array
    {
        $rows = $this->query->select('users', ['ID', 'name', 'email']);
        usort($rows, static fn(array $a, array $b): int => strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? '')));
        return $rows;
    }

    private function resolveAuthor(array $input): ?int
    {
        if (!array_key_exists('author', $input) || trim((string)$input['author']) === '') {
            return null;
        }

        $authorId = (int)$input['author'];
        if ($authorId <= 0) {
            return null;
        }

        $rows = $this->query->select('users', ['ID'], ['ID' => $authorId]);
        return $rows === [] ? null : $authorId;
    }
}
