<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\Connection;
use App\Service\Infra\Db\Query;
use App\Service\Infra\Db\SchemaConstraintValidator;
use App\Service\Support\I18n;
use InvalidArgumentException;

final class MediaService
{
    private Query $query;
    private SchemaConstraintValidator $schemaConstraintValidator;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
        $this->schemaConstraintValidator = new SchemaConstraintValidator();
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
            'created',
            'updated',
            '(SELECT name FROM users WHERE users.ID = media.author LIMIT 1) AS author_name',
        ], [], [
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => 'id',
            'orderByAllowed' => ['id', 'name', 'path', 'path_webp', 'author', 'created', 'updated'],
            'orderDir' => 'DESC',
            'search' => $search,
            'searchColumns' => ['name', 'path', 'path_webp'],
        ]);
    }

    public function find(int $id): ?array
    {
        $rows = $this->query->select('media', ['id', 'author', 'name', 'path', 'path_webp', 'created', 'updated'], ['id' => $id]);
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
            $errors['name'] = I18n::t('validation.name_required', 'Name is required.');
        }

        if ($path === '') {
            $errors['path'] = I18n::t('media.path_required', 'Path is required.');
        }

        if (($input['author'] ?? '') !== '' && $author === null) {
            $errors['author'] = I18n::t('validation.author_invalid', 'Author is not valid.');
        }

        $lengthErrors = $this->schemaConstraintValidator->validate('media', [
            'name' => $name,
            'path' => $path,
            'path_webp' => $pathWebp,
        ], [
            'name' => 'name',
            'path' => 'path',
            'path_webp' => 'path_webp',
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
            'path_webp' => $pathWebp === '' ? null : $pathWebp,
            'author' => $author,
        ];

        try {
            if ($id === null) {
                $payload['created'] = date('Y-m-d H:i:s');
                $newId = $this->query->insert('media', $payload);
                return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
            }

            $payload['updated'] = date('Y-m-d H:i:s');
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

        $rows = $this->query->select('content', ['id', 'name', 'status', 'created', 'updated'], ['thumbnail' => $mediaId]);
        usort($rows, static fn(array $a, array $b): int => strcmp((string)($b['updated'] ?? $b['created'] ?? ''), (string)($a['updated'] ?? $a['created'] ?? '')));
        return $rows;
    }

    public function authorOptions(): array
    {
        $rows = $this->query->select('users', ['ID', 'name', 'email']);
        usort($rows, static fn(array $a, array $b): int => strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? '')));
        return $rows;
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

    public function nextIdAfterDelete(int $id, ?int $authorId = null): ?int
    {
        $ids = array_values(array_filter($this->accessibleIds($authorId), static fn(int $itemId): bool => $itemId !== $id));
        if ($ids === []) {
            return null;
        }

        foreach ($ids as $itemId) {
            if ($itemId > $id) {
                return $itemId;
            }
        }

        return $ids[count($ids) - 1] ?? null;
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
}
