<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\Connection;
use App\Service\Infra\Db\Query;
use App\Service\Infra\Db\SchemaConstraintValidator;
use App\Service\Infra\Db\Table;
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

    public function paginate(int $page = 1, int $perPage = 10, string $search = '', string $status = 'all'): array
    {
        if ($status === 'unassigned') {
            return $this->paginateUnassigned($page, $perPage, $search);
        }

        $mediaTable = Table::name('media');
        $usersTable = Table::name('users');
        return $this->query->paginate('media', [
            'id',
            'name',
            'path',
            'path_webp',
            'author',
            'created',
            'updated',
            "(SELECT name FROM $usersTable WHERE $usersTable.ID = $mediaTable.author LIMIT 1) AS author_name",
        ], [], [
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => 'id',
            'orderDir' => 'DESC',
            'search' => $search,
            'searchColumns' => ['name', 'path', 'path_webp'],
        ]);
    }

    public function statusCounts(): array
    {
        $mediaTable = Table::name('media');
        $contentTable = Table::name('content');
        $attachmentsTable = Table::name('attachments');

        $all = (int)(Connection::get()->query("SELECT COUNT(*) FROM $mediaTable")->fetchColumn() ?: 0);
        $unassignedSql = implode("\n", [
            "SELECT COUNT(*) FROM $mediaTable m",
            "WHERE NOT EXISTS (SELECT 1 FROM $contentTable c WHERE c.thumbnail = m.id)",
            "AND NOT EXISTS (SELECT 1 FROM $attachmentsTable a WHERE a.media = m.id)",
        ]);
        $unassigned = (int)(Connection::get()->query($unassignedSql)->fetchColumn() ?: 0);

        return [
            'all' => $all,
            'unassigned' => $unassigned,
        ];
    }

    public function find(int $id): ?array
    {
        return $this->query->first('media', ['id', 'author', 'name', 'path', 'path_webp', 'created', 'updated'], ['id' => $id]);
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
            $errors['name'] = I18n::t('validation.name_required');
        }

        if ($path === '') {
            $errors['path'] = I18n::t('media.path_required');
        }

        if (($input['author'] ?? '') !== '' && $author === null) {
            $errors['author'] = I18n::t('validation.author_invalid');
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

        $contentTable = Table::name('content');
        $attachmentsTable = Table::name('attachments');
        $sql = implode("\n", [
            'SELECT c.id, c.name, c.created,',
            'MAX(CASE WHEN c.thumbnail = :media THEN 1 ELSE 0 END) AS used_as_thumbnail,',
            'MAX(CASE WHEN a.media = :media THEN 1 ELSE 0 END) AS used_in_body',
            "FROM $contentTable c",
            "LEFT JOIN $attachmentsTable a ON a.content = c.id",
            'WHERE c.thumbnail = :media OR a.media = :media',
            'GROUP BY c.id, c.name, c.created',
            'ORDER BY COALESCE(MAX(c.updated), c.created) DESC',
        ]);

        $stmt = Connection::get()->prepare($sql);
        $stmt->bindValue(':media', $mediaId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
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

        return $this->query->exists('users', ['ID' => $authorId]) ? $authorId : null;
    }

    private function paginateUnassigned(int $page, int $perPage, string $search): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $mediaTable = Table::name('media');
        $usersTable = Table::name('users');
        $contentTable = Table::name('content');
        $attachmentsTable = Table::name('attachments');

        $params = [];
        $searchSql = '';
        if ($search !== '') {
            $searchSql = ' AND (m.name LIKE :search OR m.path LIKE :search OR m.path_webp LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $baseSql = implode("\n", [
            "FROM $mediaTable m",
            "WHERE NOT EXISTS (SELECT 1 FROM $contentTable c WHERE c.thumbnail = m.id)",
            "AND NOT EXISTS (SELECT 1 FROM $attachmentsTable a WHERE a.media = m.id)",
        ]) . $searchSql;

        $countStmt = Connection::get()->prepare("SELECT COUNT(*) $baseSql");
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetchColumn() ?: 0);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = implode("\n", [
            'SELECT',
            'm.id, m.name, m.path, m.path_webp, m.author, m.created, m.updated,',
            "(SELECT name FROM $usersTable u WHERE u.ID = m.author LIMIT 1) AS author_name",
            $baseSql,
            'ORDER BY m.id DESC',
            'LIMIT :limit OFFSET :offset',
        ]);
        $stmt = Connection::get()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'total' => $total,
            'total_pages' => $totalPages,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

}
