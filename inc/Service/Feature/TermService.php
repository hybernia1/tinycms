<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\Connection;
use App\Service\Infra\Db\Query;

final class TermService
{
    private Query $query;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
        $this->query = new Query($this->pdo);
    }

    public function paginate(int $page = 1, int $perPage = 10, string $search = ''): array
    {
        return $this->query->paginate('terms', [
            'id',
            'name',
            'body',
            'created',
            'updated',
        ], [], [
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => 'id',
            'orderByAllowed' => ['id', 'name', 'created', 'updated'],
            'orderDir' => 'DESC',
            'search' => $search,
            'searchColumns' => ['name', 'body'],
        ]);
    }

    public function find(int $id): ?array
    {
        $rows = $this->query->select('terms', ['id', 'name', 'body', 'created', 'updated'], ['id' => $id]);
        return $rows[0] ?? null;
    }

    public function save(array $input, ?int $id = null): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $body = trim((string)($input['body'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Název je povinný.';
        }

        if ($name !== '' && $this->existsByName($name, $id)) {
            $errors['name'] = 'Štítek s tímto názvem už existuje.';
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $payload = [
            'name' => $name,
            'body' => $body === '' ? null : $body,
            'updated' => date('Y-m-d H:i:s'),
        ];

        if ($id === null) {
            $payload['created'] = date('Y-m-d H:i:s');
            $newId = $this->query->insert('terms', $payload);
            return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
        }

        $updated = $this->query->update('terms', $payload, ['id' => $id]);
        return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
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

        $stmt = $this->pdo->prepare('SELECT id, name FROM terms WHERE name LIKE :search ORDER BY name ASC LIMIT :limit');
        $stmt->bindValue(':search', '%' . $needle . '%');
        $stmt->bindValue(':limit', min($limit, 50), \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn(array $item): array => [
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
        ], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function namesByContent(int $contentId): array
    {
        if ($contentId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare('SELECT t.name FROM terms t INNER JOIN content_terms ct ON ct.term = t.id WHERE ct.content = :content ORDER BY t.name ASC');
        $stmt->execute(['content' => $contentId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_values(array_filter(array_map(static fn(array $row): string => trim((string)($row['name'] ?? '')), $rows)));
    }

    public function syncContentTerms(int $contentId, string $rawTerms): void
    {
        if ($contentId <= 0) {
            return;
        }

        $names = $this->normalizeTerms($rawTerms);
        if ($names === []) {
            $stmt = $this->pdo->prepare('DELETE FROM content_terms WHERE content = :content');
            $stmt->execute(['content' => $contentId]);
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $termIds = [];
            $selectStmt = $this->pdo->prepare('SELECT id FROM terms WHERE name = :name LIMIT 1');
            $insertStmt = $this->pdo->prepare('INSERT INTO terms (name, created, updated) VALUES (:name, NOW(), NOW())');

            foreach ($names as $name) {
                $selectStmt->execute(['name' => $name]);
                $id = (int)$selectStmt->fetchColumn();
                if ($id <= 0) {
                    $insertStmt->execute(['name' => $name]);
                    $id = (int)$this->pdo->lastInsertId();
                }
                if ($id > 0) {
                    $termIds[] = $id;
                }
            }

            $termIds = array_values(array_unique($termIds));
            if ($termIds === []) {
                $deleteStmt = $this->pdo->prepare('DELETE FROM content_terms WHERE content = :content');
                $deleteStmt->execute(['content' => $contentId]);
                $this->pdo->commit();
                return;
            }

            $params = ['content' => $contentId];
            $placeholders = [];
            foreach ($termIds as $index => $termId) {
                $key = 'term_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $termId;
            }

            $deleteSql = sprintf('DELETE FROM content_terms WHERE content = :content AND term NOT IN (%s)', implode(', ', $placeholders));
            $deleteStmt = $this->pdo->prepare($deleteSql);
            $deleteStmt->execute($params);

            $attachStmt = $this->pdo->prepare('INSERT IGNORE INTO content_terms (content, term) VALUES (:content, :term)');
            foreach ($termIds as $termId) {
                $attachStmt->execute(['content' => $contentId, 'term' => $termId]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        }
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
        $stmt = $this->pdo->prepare('SELECT id FROM terms WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $name]);
        $foundId = (int)$stmt->fetchColumn();

        if ($foundId <= 0) {
            return false;
        }

        return $excludeId === null || $foundId !== $excludeId;
    }
}
