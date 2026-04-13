<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\SchemaConstraintValidator;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\I18n;
use InvalidArgumentException;

final class Term
{
    private Query $query;
    private SchemaConstraintValidator $schemaConstraintValidator;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
        $this->schemaConstraintValidator = new SchemaConstraintValidator();
    }

    public function paginate(int $page = 1, int $perPage = 10, string $search = '', string $status = 'all'): array
    {
        if ($status === 'unassigned') {
            return $this->paginateUnassigned($page, $perPage, $search);
        }

        return $this->query->paginate('terms', [
            'id',
            'name',
            'created',
            'updated',
        ], [], [
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => 'id',
            'orderByAllowed' => ['id', 'name', 'created', 'updated'],
            'orderDir' => 'DESC',
            'search' => $search,
            'searchColumns' => ['name'],
        ]);
    }

    public function statusCounts(): array
    {
        $termsTable = Table::name('terms');
        $contentTermsTable = Table::name('content_terms');

        $all = (int)($this->query->fetchColumn("SELECT COUNT(*) FROM $termsTable") ?: 0);
        $unassignedSql = implode("\n", [
            "SELECT COUNT(*) FROM $termsTable t",
            "WHERE NOT EXISTS (SELECT 1 FROM $contentTermsTable ct WHERE ct.term = t.id)",
        ]);
        $unassigned = (int)($this->query->fetchColumn($unassignedSql) ?: 0);

        return [
            'all' => $all,
            'unassigned' => $unassigned,
        ];
    }

    public function find(int $id): ?array
    {
        $rows = $this->query->select('terms', ['id', 'name', 'created', 'updated'], ['id' => $id]);
        return $rows[0] ?? null;
    }

    public function save(array $input, ?int $id = null): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors['name'] = I18n::t('validation.name_required');
        }

        if ($name !== '' && $this->existsByName($name, $id)) {
            $errors['name'] = I18n::t('terms.name_exists');
        }

        $lengthErrors = $this->schemaConstraintValidator->validate('terms', [
            'name' => $name,
        ], [
            'name' => 'name',
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
        ];

        try {
            if ($id === null) {
                $newId = $this->query->insert('terms', $payload);
                return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
            }

            $updated = $this->query->update('terms', $payload, ['id' => $id]);
            return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'errors' => ['_global' => $e->getMessage()]];
        }
    }

    public function delete(int $id): bool
    {
        return $this->query->delete('terms', ['id' => $id]) > 0;
    }

    public function search(string $query, int $limit = 15): array
    {
        $needle = trim($query);
        if ($limit <= 0) {
            return [];
        }

        if ($needle === '') {
            $rows = $this->query->paginate('terms', ['id', 'name'], [], [
                'page' => 1,
                'perPage' => min($limit, 50),
                'orderBy' => 'name',
                'orderByAllowed' => ['name'],
                'orderDir' => 'ASC',
            ]);
            return array_map(static fn(array $item): array => [
                'id' => (int)($item['id'] ?? 0),
                'name' => (string)($item['name'] ?? ''),
            ], (array)($rows['data'] ?? []));
        }

        $termsTable = Table::name('terms');
        $rows = $this->query->fetchAll(
            "SELECT id, name FROM $termsTable WHERE name LIKE :search ORDER BY name ASC LIMIT :limit",
            ['search' => '%' . $needle . '%', 'limit' => min($limit, 50)]
        );

        return array_map(static fn(array $item): array => [
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
        ], $rows);
    }


    public function listByContent(int $contentId): array
    {
        if ($contentId <= 0) {
            return [];
        }

        $termsTable = Table::name('terms');
        $contentTermsTable = Table::name('content_terms');
        $rows = $this->query->fetchAll(
            "SELECT DISTINCT t.id, t.name FROM $termsTable t INNER JOIN $contentTermsTable ct ON ct.term = t.id WHERE ct.content = :content ORDER BY t.name ASC",
            ['content' => $contentId]
        );

        return array_map(static fn(array $row): array => [
            'id' => (int)($row['id'] ?? 0),
            'name' => trim((string)($row['name'] ?? '')),
        ], $rows);
    }

    public function namesByContent(int $contentId): array
    {
        if ($contentId <= 0) {
            return [];
        }

        $termsTable = Table::name('terms');
        $contentTermsTable = Table::name('content_terms');
        $rows = $this->query->fetchAll(
            "SELECT DISTINCT t.name FROM $termsTable t INNER JOIN $contentTermsTable ct ON ct.term = t.id WHERE ct.content = :content ORDER BY t.name ASC",
            ['content' => $contentId]
        );

        return array_values(array_filter(array_map(static fn(array $row): string => trim((string)($row['name'] ?? '')), $rows)));
    }

    public function totalCount(): int
    {
        $termsTable = Table::name('terms');
        return (int)$this->query->fetchColumn("SELECT COUNT(*) FROM $termsTable");
    }

    public function syncContentTerms(int $contentId, string $rawTerms): void
    {
        if ($contentId <= 0) {
            return;
        }

        $names = $this->normalizeTerms($rawTerms);
        if ($names === []) {
            $contentTermsTable = Table::name('content_terms');
            $this->query->execute("DELETE FROM $contentTermsTable WHERE content = :content", ['content' => $contentId]);
            return;
        }

        try {
            $this->query->transaction(function () use ($names, $contentId): void {
                $termIds = [];
                $termsTable = Table::name('terms');
                $contentTermsTable = Table::name('content_terms');

                foreach ($names as $name) {
                    $id = (int)$this->query->fetchColumn("SELECT id FROM $termsTable WHERE name = :name LIMIT 1", ['name' => $name]);
                    if ($id <= 0) {
                        $this->query->execute("INSERT IGNORE INTO $termsTable (name) VALUES (:name)", ['name' => $name]);
                        $id = (int)$this->query->fetchColumn("SELECT id FROM $termsTable WHERE name = :name LIMIT 1", ['name' => $name]);
                    }
                    if ($id > 0) {
                        $termIds[] = $id;
                    }
                }

                $termIds = array_values(array_unique($termIds));
                if ($termIds === []) {
                    $this->query->execute("DELETE FROM $contentTermsTable WHERE content = :content", ['content' => $contentId]);
                    return;
                }

                $this->query->execute("DELETE FROM $contentTermsTable WHERE content = :content", ['content' => $contentId]);
                foreach ($termIds as $termId) {
                    $this->query->execute(
                        "INSERT IGNORE INTO $contentTermsTable (content, term) VALUES (:content, :term)",
                        ['content' => $contentId, 'term' => $termId]
                    );
                }
            });
        } catch (\Throwable $e) {
        }
    }

    public function contentUsages(int $termId): array
    {
        if ($termId <= 0) {
            return [];
        }

        $contentTable = Table::name('content');
        $contentTermsTable = Table::name('content_terms');
        return $this->query->fetchAll(implode("\n", [
            'SELECT c.id, c.name, c.created',
            "FROM $contentTable c",
            "INNER JOIN $contentTermsTable ct ON ct.content = c.id",
            'WHERE ct.term = :term',
            'ORDER BY COALESCE(c.updated, c.created) DESC',
        ]), ['term' => $termId]);
    }

    private function normalizeTerms(string $rawTerms): array
    {
        $parts = preg_split('/[\n,]+/', $rawTerms) ?: [];
        $terms = [];

        foreach ($parts as $part) {
            $value = trim((string)$part);
            if ($value === '') {
                continue;
            }
            $value = mb_substr($value, 0, 255);
            $key = mb_strtolower($value);
            $terms[$key] = $value;
        }

        return array_values($terms);
    }

    private function existsByName(string $name, ?int $excludeId = null): bool
    {
        $termsTable = Table::name('terms');
        $foundId = (int)$this->query->fetchColumn("SELECT id FROM $termsTable WHERE name = :name LIMIT 1", ['name' => $name]);

        if ($foundId <= 0) {
            return false;
        }

        return $excludeId === null || $foundId !== $excludeId;
    }

    private function paginateUnassigned(int $page, int $perPage, string $search): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $termsTable = Table::name('terms');
        $contentTermsTable = Table::name('content_terms');

        $params = [];
        $searchSql = '';
        if ($search !== '') {
            $searchSql = ' AND t.name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $baseSql = implode("\n", [
            "FROM $termsTable t",
            "WHERE NOT EXISTS (SELECT 1 FROM $contentTermsTable ct WHERE ct.term = t.id)",
        ]) . $searchSql;

        $total = (int)($this->query->fetchColumn("SELECT COUNT(*) $baseSql", $params) ?: 0);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = implode("\n", [
            'SELECT t.id, t.name, t.created, t.updated',
            $baseSql,
            'ORDER BY t.id DESC',
            'LIMIT :limit OFFSET :offset',
        ]);

        return [
            'data' => $this->query->fetchAll($sql, array_merge($params, [
                'limit' => $perPage,
                'offset' => $offset,
            ])),
            'total' => $total,
            'total_pages' => $totalPages,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }
}
