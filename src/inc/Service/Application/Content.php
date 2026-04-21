<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\SchemaConstraintValidator;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\I18n;
use InvalidArgumentException;

final class Content
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_TRASH = 'trash';
    public const TYPE_ARTICLE = 'article';
    public const TYPE_PAGE = 'page';
    public const TYPE_ABOUT_PAGE = 'about_page';
    public const TYPE_NEWS_ARTICLE = 'news_article';
    public const TYPE_BLOG_POSTING = 'blog_posting';
    public const TYPE_FAQ_PAGE = 'faq_page';

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
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $where = [];

        if ($status !== 'all') {
            $where['status'] = $status;
        }

        return $this->query->paginate('content', [
            'id',
            'name',
            'status',
            'type',
            'author',
            "(SELECT name FROM $usersTable WHERE $usersTable.ID = $contentTable.author LIMIT 1) AS author_name",
            'created',
            'updated',
        ], $where, [
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => 'id',
            'orderByAllowed' => ['id', 'name', 'status', 'type', 'author', 'created', 'updated'],
            'orderDir' => 'DESC',
            'search' => $search,
            'searchColumns' => ['name', 'excerpt', 'body'],
        ]);
    }

    public function find(int $id): ?array
    {
        $contentTable = Table::name('content');
        $mediaTable = Table::name('media');
        $rows = $this->query->select('content', [
            'id',
            'name',
            'status',
            'type',
            'excerpt',
            'body',
            'author',
            'thumbnail',
            "(SELECT name FROM $mediaTable WHERE $mediaTable.id = $contentTable.thumbnail LIMIT 1) AS thumbnail_name",
            "(SELECT path FROM $mediaTable WHERE $mediaTable.id = $contentTable.thumbnail LIMIT 1) AS thumbnail_path",
            'created',
            'updated',
        ], ['id' => $id]);
        return $rows[0] ?? null;
    }

    public function delete(int $id): bool
    {
        return $this->query->delete('content', ['id' => $id]) > 0;
    }

    public function deleteByStatus(int $id): ?string
    {
        $item = $this->find($id);
        if ($item === null) {
            return null;
        }

        if ((string)($item['status'] ?? '') === self::STATUS_TRASH) {
            return $this->delete($id) ? 'hard_deleted' : null;
        }

        return $this->setStatus($id, self::STATUS_TRASH) ? 'soft_deleted' : null;
    }

    public function restore(int $id): bool
    {
        $item = $this->find($id);
        if ($item === null || (string)($item['status'] ?? '') !== self::STATUS_TRASH) {
            return false;
        }

        return $this->setStatus($id, self::STATUS_DRAFT);
    }

    public function setStatus(int $id, string $status): bool
    {
        $item = $this->find($id);

        if ($item === null || (string)($item['status'] ?? '') === $status) {
            return false;
        }

        return $this->query->update('content', [
            'status' => $status,
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
        ], ['id' => $id]) >= 0;
    }

    public function save(array $input, int $defaultAuthorId, ?int $id = null): array
    {
        $name = $this->schemaConstraintValidator->truncate(
            'content',
            'name',
            trim((string)($input['name'] ?? '')),
            255
        );
        $status = trim((string)($input['status'] ?? 'draft'));
        $type = trim((string)($input['type'] ?? self::TYPE_ARTICLE));
        $excerpt = $this->schemaConstraintValidator->truncate(
            'content',
            'excerpt',
            $this->sanitizeExcerpt((string)($input['excerpt'] ?? '')),
            500
        );
        $body = trim((string)($input['body'] ?? ''));
        $author = $this->resolveAuthorId($input, $defaultAuthorId);
        $created = $this->resolveDateTime((string)($input['created'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors['name'] = I18n::t('validation.name_required');
        }

        if ($status === '') {
            $errors['status'] = I18n::t('validation.status_required');
        }

        if ($type === '') {
            $errors['type'] = I18n::t('errors.validation.required');
        } elseif (!in_array($type, $this->types(), true)) {
            $errors['type'] = I18n::t('errors.validation.invalid_value');
        }

        if (($input['author'] ?? '') !== '' && $author === null) {
            $errors['author'] = I18n::t('validation.author_invalid');
        }

        if (($input['created'] ?? '') !== '' && $created === null) {
            $errors['created'] = I18n::t('validation.publish_date_invalid');
        }

        $lengthErrors = $this->schemaConstraintValidator->validate('content', [
            'name' => $name,
            'status' => $status,
            'type' => $type,
            'excerpt' => $excerpt,
        ], [
            'name' => 'name',
            'status' => 'status',
            'type' => 'type',
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
            'type' => $type,
            'excerpt' => $excerpt === '' ? null : $excerpt,
            'body' => $body,
            'author' => $author,
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
        $statuses = array_values(array_unique(array_merge([self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_TRASH], $statuses)));

        sort($statuses);
        return $statuses;
    }

    public function types(): array
    {
        return [
            self::TYPE_ARTICLE,
            self::TYPE_PAGE,
            self::TYPE_ABOUT_PAGE,
            self::TYPE_NEWS_ARTICLE,
            self::TYPE_BLOG_POSTING,
            self::TYPE_FAQ_PAGE,
        ];
    }

    public function statusCounts(array $statuses = []): array
    {
        $rows = $this->query->select('content', ['status']);
        $counts = ['all' => count($rows)];

        foreach ($rows as $row) {
            $value = trim((string)($row['status'] ?? ''));
            if ($value === '') {
                continue;
            }

            if (!isset($counts[$value])) {
                $counts[$value] = 0;
            }
            $counts[$value]++;
        }

        foreach ($statuses as $status) {
            $key = trim((string)$status);
            if ($key !== '' && !isset($counts[$key])) {
                $counts[$key] = 0;
            }
        }

        return $counts;
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

        $contentMediaTable = Table::name('content_media');
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO $contentMediaTable (content, media) VALUES (:content, :media)");
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
            $contentMediaTable = Table::name('content_media');
            $stmt = $this->pdo->prepare("DELETE FROM $contentMediaTable WHERE content = :content");
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

        $contentMediaTable = Table::name('content_media');
        $deleteSql = sprintf(
            "DELETE FROM $contentMediaTable WHERE content = :content AND media NOT IN (%s)",
            implode(', ', $placeholders),
        );
        $deleteStmt = $this->pdo->prepare($deleteSql);
        $deleteStmt->execute($params);

        $insertStmt = $this->pdo->prepare("INSERT IGNORE INTO $contentMediaTable (content, media) VALUES (:content, :media)");
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
        $mediaTable = Table::name('media');
        $stmt = $this->pdo->prepare("SELECT id FROM $mediaTable WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $existingIds = array_map(static fn(array $row): int => (int)($row['id'] ?? 0), $stmt->fetchAll(\PDO::FETCH_ASSOC));
        $allowed = array_fill_keys($existingIds, true);

        return array_values(array_filter($ids, static fn(int $id): bool => isset($allowed[$id])));
    }

    private function sanitizeExcerpt(string $excerpt): string
    {
        $clean = trim(html_entity_decode(strip_tags($excerpt), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return preg_replace('/\s+/', ' ', $clean) ?? '';
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
