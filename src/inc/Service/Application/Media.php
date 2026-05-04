<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\SchemaRules;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\I18n;
use InvalidArgumentException;

final class Media
{
    private Query $query;
    private SchemaRules $schemaRules;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
        $this->schemaRules = new SchemaRules();
    }

    public function paginate(int $page = 1, int $perPage = 10, string $search = '', string $status = 'all'): array
    {
        if ($status === 'unassigned') {
            return $this->paginateUnassigned($page, $perPage, $search);
        }

        return $this->query
            ->from('media', 'm')
            ->select(['m.id', 'm.name', 'm.path', 'm.author', 'm.created', 'm.updated', 'u.name AS author_name'])
            ->leftJoin('users', 'u', 'u.id', '=', 'm.author')
            ->search(['m.name', 'm.path'], $search)
            ->orderBy('m.id', 'DESC')
            ->paginate($page, $perPage);
    }

    public function statusCounts(): array
    {
        $all = $this->query->from('media', 'm')->count();
        $unassigned = $this->query
            ->from('media', 'm')
            ->whereNotExists('content', 'c', 'c.thumbnail', '=', 'm.id')
            ->whereNotExists('content_media', 'a', 'a.media', '=', 'm.id')
            ->count();

        return [
            'all' => $all,
            'unassigned' => $unassigned,
        ];
    }

    public function find(int $id): ?array
    {
        $rows = $this->query->select('media', ['id', 'author', 'name', 'path', 'created', 'updated'], ['id' => $id]);
        return $rows[0] ?? null;
    }

    public function findByPath(string $path): ?array
    {
        $normalized = trim($path);
        if ($normalized === '') {
            return null;
        }

        $rows = $this->query->select('media', ['id', 'author', 'name', 'path', 'created', 'updated'], ['path' => $normalized]);
        return $rows[0] ?? null;
    }

    public function delete(int $id): bool
    {
        return $this->query->delete('media', ['id' => $id]) > 0;
    }

    public function save(array $input, ?int $id = null): array
    {
        $name = $this->schemaRules->truncate(
            'media',
            'name',
            trim((string)($input['name'] ?? '')),
            255
        );
        $path = trim((string)($input['path'] ?? ''));
        $author = $this->resolveAuthor($input);
        $errors = [];

        if ($name === '') {
            $errors['name'] = I18n::t('validation.name_required');
        }

        if ($path === '') {
            $errors['path'] = I18n::t('media.path_required');
        }

        if (($input['author'] ?? '') !== '' && $author === null) {
            $errors['author'] = I18n::t('validation.author_invalid');
        }

        $lengthErrors = $this->schemaRules->validate('media', [
            'name' => $name,
            'path' => $path,
        ], [
            'name' => 'name',
            'path' => 'path',
        ]);

        foreach ($lengthErrors as $field => $message) {
            if (!isset($errors[$field])) {
                $errors[$field] = $message;
            }
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $payload = [
            'name' => $name,
            'path' => $path,
            'author' => $author,
        ];

        try {
            if ($id === null) {
                $newId = $this->query->insert('media', $payload);
                return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
            }

            $updated = $this->query->update('media', $payload, ['id' => $id]);
            return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'errors' => ['_global' => $e->getMessage()]];
        }
    }

    public function thumbnailUsages(int $mediaId): array
    {
        if ($mediaId <= 0) {
            return [];
        }

        return $this->query
            ->from('content', 'c')
            ->select(['c.id', 'c.name', 'c.created'])
            ->selectRaw('MAX(CASE WHEN c.thumbnail = :media THEN 1 ELSE 0 END) AS used_as_thumbnail')
            ->selectRaw('MAX(CASE WHEN a.media = :media THEN 1 ELSE 0 END) AS used_in_body')
            ->leftJoin('content_media', 'a', 'a.content', '=', 'c.id')
            ->whereRaw('c.thumbnail = :media OR a.media = :media', ['media' => $mediaId], ['media'])
            ->groupBy(['c.id', 'c.name', 'c.created'])
            ->orderByRaw('COALESCE(MAX(c.updated), c.created) DESC')
            ->get();
    }

    public function editNavigation(int $id, ?int $authorId = null): array
    {
        $ids = $this->accessibleIds($authorId);
        if ($ids === []) {
            return ['prev' => null, 'next' => null];
        }

        $index = array_search($id, $ids, true);
        if ($index === false) {
            return ['prev' => null, 'next' => null];
        }

        return [
            'prev' => $ids[$index - 1] ?? null,
            'next' => $ids[$index + 1] ?? null,
        ];
    }

    private function accessibleIds(?int $authorId): array
    {
        $where = $authorId !== null ? ['author' => $authorId] : [];
        $rows = $this->query->select('media', ['id'], $where);
        $ids = array_values(array_filter(array_map(static fn(array $row): int => (int)($row['id'] ?? 0), $rows), static fn(int $itemId): bool => $itemId > 0));
        sort($ids);
        return $ids;
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

    private function paginateUnassigned(int $page, int $perPage, string $search): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $usersTable = Table::name('users');
        return $this->query
            ->from('media', 'm')
            ->select(['m.id', 'm.name', 'm.path', 'm.author', 'm.created', 'm.updated'])
            ->selectRaw("(SELECT name FROM $usersTable u WHERE u.ID = m.author LIMIT 1) AS author_name")
            ->whereNotExists('content', 'c', 'c.thumbnail', '=', 'm.id')
            ->whereNotExists('content_media', 'a', 'a.media', '=', 'm.id')
            ->search(['m.name', 'm.path'], $search)
            ->orderBy('m.id', 'DESC')
            ->paginate($page, $perPage);
    }
}
