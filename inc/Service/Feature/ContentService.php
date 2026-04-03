<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\Connection;
use App\Service\Infra\Db\Query;

final class ContentService
{
    private Query $query;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
    }

    public function paginate(int $page = 1, int $perPage = 10, string $status = 'all', string $search = ''): array
    {
        $where = [];

        if ($status !== 'all') {
            $where['status'] = $status;
        }

        return $this->query->paginate('content', [
            'id',
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
            'orderByAllowed' => ['id', 'name', 'status', 'author', 'created', 'updated'],
            'orderDir' => 'DESC',
            'search' => $search,
            'searchColumns' => ['name', 'excerpt', 'body'],
        ]);
    }

    public function find(int $id): ?array
    {
        $rows = $this->query->select('content', [
            'id',
            'name',
            'status',
            'excerpt',
            'body',
            'author',
            'thumbnail',
            '(SELECT name FROM media WHERE media.id = content.thumbnail LIMIT 1) AS thumbnail_name',
            '(SELECT path FROM media WHERE media.id = content.thumbnail LIMIT 1) AS thumbnail_path',
            '(SELECT path_webp FROM media WHERE media.id = content.thumbnail LIMIT 1) AS thumbnail_path_webp',
            'created',
            'updated',
        ], ['id' => $id]);
        return $rows[0] ?? null;
    }

    public function delete(int $id): bool
    {
        return $this->query->delete('content', ['id' => $id]) > 0;
    }

    public function setStatus(int $id, string $status): bool
    {
        $item = $this->find($id);

        if ($item === null || (string)($item['status'] ?? '') === $status) {
            return false;
        }

        return $this->query->update('content', [
            'status' => $status,
            'updated' => date('Y-m-d H:i:s'),
        ], ['id' => $id]) > 0;
    }

    public function setThumbnail(int $id, ?int $thumbnailId): bool
    {
        $item = $this->find($id);

        if ($item === null) {
            return false;
        }

        if ((int)($item['thumbnail'] ?? 0) === (int)($thumbnailId ?? 0)) {
            return true;
        }

        return $this->query->update('content', [
            'thumbnail' => $thumbnailId,
            'updated' => date('Y-m-d H:i:s'),
        ], ['id' => $id]) >= 0;
    }

    public function save(array $input, int $defaultAuthorId, ?int $id = null): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $status = trim((string)($input['status'] ?? 'draft'));
        $excerpt = trim((string)($input['excerpt'] ?? ''));
        $body = trim((string)($input['body'] ?? ''));
        $author = $this->resolveAuthorId($input, $defaultAuthorId);
        $created = $this->resolveDateTime((string)($input['created'] ?? ''));
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

        $updated = $this->query->update('content', $payload, ['id' => $id]);

        return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
    }

    public function statuses(): array
    {
        $rows = $this->query->select('content', ['status']);
        $statuses = array_values(array_unique(array_filter(array_map(static fn(array $row): string => trim((string)($row['status'] ?? '')), $rows))));
        $statuses = array_values(array_unique(array_merge(['draft', 'published'], $statuses)));

        sort($statuses);
        return $statuses;
    }

    public function listPublished(int $limit = 20): array
    {
        $rows = $this->query->select('content', ['id', 'name', 'excerpt', 'created'], ['status' => 'published']);
        $now = time();
        $items = array_values(array_filter($rows, static fn(array $row): bool => self::isPublishedVisible($row, $now)));

        usort($items, static fn(array $a, array $b): int => strcmp((string)($b['created'] ?? ''), (string)($a['created'] ?? '')));

        if ($limit > 0) {
            return array_slice($items, 0, $limit);
        }

        return $items;
    }

    public function findPublished(int $id): ?array
    {
        $item = $this->find($id);

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

    private function resolveDateTime(string $value): ?string
    {
        $clean = trim($value);

        if ($clean === '') {
            return null;
        }

        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\\TH:i:s', 'Y-m-d\\TH:i'];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $clean);

            if ($date instanceof \DateTimeImmutable) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        $timestamp = strtotime($clean);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
