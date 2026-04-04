<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\Connection;
use App\Service\Infra\Db\Query;

final class MenuService
{
    private Query $query;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
        $this->query = new Query($this->pdo);
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT m.id, m.parent_id, m.content_id, m.name, m.url, m.position,
                p.name AS parent_name,
                c.name AS content_name
            FROM menu m
            LEFT JOIN menu p ON p.id = m.parent_id
            LEFT JOIN content c ON c.id = m.content_id
            ORDER BY COALESCE(m.parent_id, 0) ASC, m.position ASC, m.id ASC'
        );

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $rows = $this->query->select('menu', ['id', 'parent_id', 'content_id', 'name', 'url', 'position'], ['id' => $id]);
        return $rows[0] ?? null;
    }

    public function save(array $input, ?int $id = null): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $url = trim((string)($input['url'] ?? ''));
        $position = (int)($input['position'] ?? 0);
        $parentId = $this->resolveMenuId($input['parent_id'] ?? null);
        $contentId = $this->resolveContentId($input['content_id'] ?? null);
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Název je povinný.';
        }

        if ($id !== null && $parentId === $id) {
            $errors['parent_id'] = 'Položka nemůže být sama sobě rodičem.';
        }

        if (($input['parent_id'] ?? '') !== '' && $parentId === null) {
            $errors['parent_id'] = 'Nadřazená položka není validní.';
        }

        if (($input['content_id'] ?? '') !== '' && $contentId === null) {
            $errors['content_id'] = 'Obsah není validní.';
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $payload = [
            'name' => mb_substr($name, 0, 255),
            'url' => $url === '' ? null : mb_substr($url, 0, 2048),
            'position' => $position,
            'parent_id' => $parentId,
            'content_id' => $contentId,
        ];

        if ($id === null) {
            $newId = $this->query->insert('menu', $payload);
            return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
        }

        $updated = $this->query->update('menu', $payload, ['id' => $id]);
        return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
    }

    public function delete(int $id): bool
    {
        return $this->query->delete('menu', ['id' => $id]) > 0;
    }

    public function options(?int $excludeId = null): array
    {
        $rows = $this->query->select('menu', ['id', 'name', 'parent_id', 'position']);
        usort($rows, static function (array $a, array $b): int {
            $parentA = (int)($a['parent_id'] ?? 0);
            $parentB = (int)($b['parent_id'] ?? 0);

            return [$parentA, (int)($a['position'] ?? 0), (int)($a['id'] ?? 0)] <=> [$parentB, (int)($b['position'] ?? 0), (int)($b['id'] ?? 0)];
        });

        return array_values(array_filter(array_map(static function (array $item) use ($excludeId): ?array {
            $id = (int)($item['id'] ?? 0);
            if ($id <= 0 || ($excludeId !== null && $id === $excludeId)) {
                return null;
            }

            return [
                'id' => $id,
                'name' => (string)($item['name'] ?? ''),
            ];
        }, $rows)));
    }

    public function contentOptions(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM content ORDER BY name ASC');

        return array_map(static fn(array $row): array => [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
        ], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function resolveMenuId(mixed $raw): ?int
    {
        if ($raw === null || trim((string)$raw) === '') {
            return null;
        }

        $id = (int)$raw;
        if ($id <= 0) {
            return null;
        }

        $rows = $this->query->select('menu', ['id'], ['id' => $id]);
        return $rows === [] ? null : $id;
    }

    private function resolveContentId(mixed $raw): ?int
    {
        if ($raw === null || trim((string)$raw) === '') {
            return null;
        }

        $id = (int)$raw;
        if ($id <= 0) {
            return null;
        }

        $rows = $this->query->select('content', ['id'], ['id' => $id]);
        return $rows === [] ? null : $id;
    }
}
