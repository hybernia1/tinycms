<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Db\Connection;
use App\Service\Db\Query;

final class ContentService
{
    private Query $query;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
    }

    public function paginate(string $type, int $page = 1, int $perPage = 10, string $status = 'all', string $search = ''): array
    {
        $where = ['type' => $type];

        if ($status !== 'all') {
            $where['status'] = $status;
        }

        return $this->query->paginate('content', ['id', 'type', 'name', 'status', 'author', 'created', 'updated'], $where, [
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => 'id',
            'orderDir' => 'DESC',
            'search' => $search,
            'searchColumns' => ['name', 'excerpt', 'body'],
        ]);
    }

    public function find(int $id, string $type): ?array
    {
        $rows = $this->query->select('content', ['id', 'type', 'name', 'status', 'excerpt', 'body', 'author'], ['id' => $id, 'type' => $type]);
        return $rows[0] ?? null;
    }

    public function delete(int $id, string $type): bool
    {
        return $this->query->delete('content', ['id' => $id, 'type' => $type]) > 0;
    }

    public function deleteMany(array $ids, string $type): int
    {
        $clean = $this->sanitizeIds($ids);

        if ($clean === []) {
            return 0;
        }

        $deleted = 0;

        foreach ($clean as $id) {
            $deleted += $this->delete($id, $type) ? 1 : 0;
        }

        return $deleted;
    }

    public function save(array $input, int $authorId, string $type, ?int $id = null): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $status = trim((string)($input['status'] ?? 'draft'));
        $excerpt = trim((string)($input['excerpt'] ?? ''));
        $body = trim((string)($input['body'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Název je povinný.';
        }

        if ($status === '') {
            $errors['status'] = 'Status je povinný.';
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'type' => $type,
            'name' => $name,
            'status' => $status,
            'excerpt' => $excerpt === '' ? null : mb_substr($excerpt, 0, 500),
            'body' => $body,
            'author' => $authorId > 0 ? $authorId : null,
            'updated' => $now,
        ];

        if ($id === null) {
            $payload['created'] = $now;
            $newId = $this->query->insert('content', $payload);
            return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
        }

        $updated = $this->query->update('content', $payload, ['id' => $id, 'type' => $type]);

        return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
    }

    public function statusesForType(string $type): array
    {
        $rows = $this->query->select('content', ['status'], ['type' => $type]);
        $statuses = array_values(array_unique(array_filter(array_map(static fn(array $row): string => (string)($row['status'] ?? ''), $rows))));

        if ($statuses === []) {
            return ['draft', 'published'];
        }

        sort($statuses);
        return $statuses;
    }

    private function sanitizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
    }
}
