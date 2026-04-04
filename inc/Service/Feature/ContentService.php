<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\Connection;
use App\Service\Infra\Db\Query;
use App\Service\Infra\Db\SchemaConstraintValidator;
use InvalidArgumentException;

final class ContentService
{
    private Query $query;
    private \PDO $pdo;
    private SchemaConstraintValidator $schemaConstraintValidator;

    public function __construct()
    {
        $this->pdo = Connection::get();
        $this->query = new Query($this->pdo);
        $this->schemaConstraintValidator = new SchemaConstraintValidator();
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

        $lengthErrors = $this->schemaConstraintValidator->validate('content', [
            'name' => $name,
            'status' => $status,
            'excerpt' => $excerpt,
        ], [
            'name' => 'name',
            'status' => 'status',
            'excerpt' => 'excerpt',
        ]);

        foreach ($lengthErrors as $field => $message) {
            if (!isset($errors[$field])) {
                $errors[$field] = $message;
            }
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

        try {
            if ($id === null) {
                $payload['created'] = $created ?? $now;
                $newId = $this->query->insert('content', $payload);
                if ($newId > 0) {
                    $this->syncAttachments($newId, $body);
                }
                return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
            }

            if ($created !== null) {
                $payload['created'] = $created;
            }

            $updated = $this->query->update('content', $payload, ['id' => $id]);
            if ($updated >= 0) {
                $this->syncAttachments($id, $body);
            }

            return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'errors' => ['_global' => $e->getMessage()]];
        }
    }

    public function statuses(): array
    {
        $rows = $this->query->select('content', ['status']);
        $statuses = array_values(array_unique(array_filter(array_map(static fn(array $row): string => trim((string)($row['status'] ?? '')), $rows))));
        $statuses = array_values(array_unique(array_merge(['draft', 'published'], $statuses)));

        sort($statuses);
        return $statuses;
    }

    public function attachMedia(int $contentId, int $mediaId): bool
    {
        if ($contentId <= 0 || $mediaId <= 0 || $this->find($contentId) === null) {
            return false;
        }

        $mediaExists = $this->query->select('media', ['id'], ['id' => $mediaId]) !== [];
        if (!$mediaExists) {
            return false;
        }

        $stmt = $this->pdo->prepare('INSERT IGNORE INTO attachments (content, media) VALUES (:content, :media)');
        return $stmt->execute([
            'content' => $contentId,
            'media' => $mediaId,
        ]);
    }

    private function syncAttachments(int $contentId, string $body): void
    {
        if ($contentId <= 0) {
            return;
        }

        $mediaIds = $this->extractMediaIdsFromBody($body);
        if ($mediaIds === []) {
            $stmt = $this->pdo->prepare('DELETE FROM attachments WHERE content = :content');
            $stmt->execute(['content' => $contentId]);
            return;
        }

        $placeholders = [];
        $params = ['content' => $contentId];
        foreach ($mediaIds as $index => $mediaId) {
            $key = 'media_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $mediaId;
        }

        $deleteSql = sprintf(
            'DELETE FROM attachments WHERE content = :content AND media NOT IN (%s)',
            implode(', ', $placeholders),
        );
        $deleteStmt = $this->pdo->prepare($deleteSql);
        $deleteStmt->execute($params);

        $insertStmt = $this->pdo->prepare('INSERT IGNORE INTO attachments (content, media) VALUES (:content, :media)');
        foreach ($mediaIds as $mediaId) {
            $insertStmt->execute([
                'content' => $contentId,
                'media' => $mediaId,
            ]);
        }
    }

    private function extractMediaIdsFromBody(string $body): array
    {
        if (trim($body) === '') {
            return [];
        }

        preg_match_all('/data-media-id=["\'](\d+)["\']/i', $body, $matches);
        $ids = array_map('intval', $matches[1] ?? []);
        $ids = array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare('SELECT id FROM media WHERE id IN (' . $placeholders . ')');
        $stmt->execute($ids);
        $existingIds = array_map(static fn(array $row): int => (int)($row['id'] ?? 0), $stmt->fetchAll(\PDO::FETCH_ASSOC));
        $allowed = array_fill_keys($existingIds, true);

        return array_values(array_filter($ids, static fn(int $id): bool => isset($allowed[$id])));
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
