<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Db\Connection;
use App\Service\Db\Query;

final class ContentService
{
    private Query $query;
    private DateTimeService $dateTime;

    public function __construct(DateTimeService $dateTime)
    {
        $this->query = new Query(Connection::get());
        $this->dateTime = $dateTime;
    }

    public function paginate(string $type, int $page = 1, int $perPage = 10, string $status = 'all', string $search = ''): array
    {
        $where = ['type' => $type];

        if ($status !== 'all') {
            $where['status'] = $status;
        }

        return $this->query->paginate('content', [
            'id',
            'type',
            'name',
            'status',
            'author',
            '(SELECT name FROM users WHERE users.ID = content.author LIMIT 1) AS author_name',
            'created',
            'updated',
        ], $where, [
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
        $rows = $this->query->select('content', ['id', 'type', 'name', 'status', 'excerpt', 'body', 'author', 'created', 'updated'], ['id' => $id, 'type' => $type]);
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

    public function setStatus(int $id, string $type, string $status): bool
    {
        $item = $this->find($id, $type);

        if ($item === null || (string)($item['status'] ?? '') === $status) {
            return false;
        }

        return $this->query->update('content', [
            'status' => $status,
            'updated' => date('Y-m-d H:i:s'),
        ], ['id' => $id, 'type' => $type]) > 0;
    }

    public function setStatusMany(array $ids, string $type, string $status): int
    {
        $clean = $this->sanitizeIds($ids);

        if ($clean === []) {
            return 0;
        }

        $updated = 0;

        foreach ($clean as $id) {
            $updated += $this->setStatus($id, $type, $status) ? 1 : 0;
        }

        return $updated;
    }

    public function save(array $input, int $defaultAuthorId, string $type, ?int $id = null): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $status = trim((string)($input['status'] ?? 'draft'));
        $excerpt = trim((string)($input['excerpt'] ?? ''));
        $body = trim((string)($input['body'] ?? ''));
        $author = $this->resolveAuthorId($input, $defaultAuthorId);
        $created = $this->dateTime->toStorage((string)($input['created'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Název je povinný.';
        }

        if ($status === '') {
            $errors['status'] = 'Status je povinný.';
        }

        if (($input['author'] ?? '') !== '' && $author === null) {
            $errors['author'] = 'Autor není validní.';
        }

        if (($input['created'] ?? '') !== '' && $created === null) {
            $errors['created'] = 'Datum publikace není validní.';
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
            'author' => $author,
            'updated' => $now,
        ];

        if ($id === null) {
            $payload['created'] = $created ?? $now;
            $newId = $this->query->insert('content', $payload);
            return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
        }

        if ($created !== null) {
            $payload['created'] = $created;
        }

        $updated = $this->query->update('content', $payload, ['id' => $id, 'type' => $type]);

        return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
    }

    public function statusesForType(string $type): array
    {
        $rows = $this->query->select('content', ['status'], ['type' => $type]);
        $statuses = array_values(array_unique(array_filter(array_map(static fn(array $row): string => trim((string)($row['status'] ?? '')), $rows))));
        $statuses = array_values(array_unique(array_merge(['draft', 'published'], $statuses)));

        sort($statuses);
        return $statuses;
    }

    public function listPublished(string $type = '', int $limit = 20): array
    {
        $rows = $this->query->select('content', ['id', 'type', 'name', 'excerpt', 'created'], ['status' => 'published']);
        $now = time();
        $typeKey = trim(mb_strtolower($type));
        $items = array_values(array_filter($rows, static function (array $row) use ($now, $typeKey): bool {
            if ($typeKey !== '' && trim(mb_strtolower((string)($row['type'] ?? ''))) !== $typeKey) {
                return false;
            }

            return self::isPublishedVisible($row, $now);
        }));

        usort($items, static fn(array $a, array $b): int => strcmp((string)($b['created'] ?? ''), (string)($a['created'] ?? '')));

        if ($limit > 0) {
            return array_slice($items, 0, $limit);
        }

        return $items;
    }

    public function findPublished(int $id, string $type): ?array
    {
        $item = $this->find($id, $type);

        if ($item === null || !self::isPublishedVisible($item)) {
            return null;
        }

        return $item;
    }

    private static function isPublishedVisible(array $item, ?int $now = null): bool
    {
        if (trim((string)($item['status'] ?? 'published')) !== 'published') {
            return false;
        }

        $created = strtotime((string)($item['created'] ?? ''));
        if ($created === false) {
            return false;
        }

        return $created <= ($now ?? time());
    }

    private function sanitizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
    }

    private function resolveAuthorId(array $input, int $defaultAuthorId): ?int
    {
        if (array_key_exists('author', $input) && trim((string)$input['author']) === '') {
            return null;
        }

        $raw = $input['author'] ?? null;
        $authorId = $raw === null ? $defaultAuthorId : (int)$raw;

        if ($authorId <= 0) {
            return null;
        }

        $rows = $this->query->select('users', ['ID'], ['ID' => $authorId]);
        return $rows === [] ? null : $authorId;
    }
}
