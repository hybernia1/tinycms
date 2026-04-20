<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\SchemaConstraintValidator;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\I18n;

final class Comment
{
    public const STATUS_PUBLISHED = 'published';

    private \PDO $pdo;
    private SchemaConstraintValidator $schemaConstraintValidator;

    public function __construct()
    {
        $this->pdo = Connection::get();
        $this->schemaConstraintValidator = new SchemaConstraintValidator();
    }

    public function listByContent(int $contentId): array
    {
        if ($contentId <= 0) {
            return [];
        }

        $commentsTable = Table::name('comments');
        $usersTable = Table::name('users');
        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.content, c.author, c.parent, c.reply_to, c.body, c.created, c.updated, c.status,',
            'u.name AS author_name, p.author AS parent_author_id, pu.name AS parent_author_name,',
            'r.author AS reply_author_id, ru.name AS reply_author_name',
            "FROM $commentsTable c",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            "LEFT JOIN $commentsTable p ON p.id = c.parent",
            "LEFT JOIN $usersTable pu ON pu.id = p.author",
            "LEFT JOIN $commentsTable r ON r.id = c.reply_to",
            "LEFT JOIN $usersTable ru ON ru.id = r.author",
            'WHERE c.content = :content AND c.status = :status',
            'ORDER BY c.created ASC, c.id ASC',
        ]));
        $stmt->execute([
            'content' => $contentId,
            'status' => self::STATUS_PUBLISHED,
        ]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!is_array($rows) || $rows === []) {
            return [];
        }

        $parents = [];
        $children = [];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $parentId = (int)($row['parent'] ?? 0);
            $item = $this->mapItem($row);

            if ($id <= 0) {
                continue;
            }

            if ($parentId <= 0) {
                $parents[$id] = $item;
                continue;
            }

            if (!isset($children[$parentId])) {
                $children[$parentId] = [];
            }
            $children[$parentId][] = $item;
        }

        $result = [];
        foreach ($parents as $parentId => $parent) {
            $parent['children'] = $children[$parentId] ?? [];
            $result[] = $parent;
        }

        return $result;
    }

    public function create(array $input, int $authorId): array
    {
        $contentId = (int)($input['content_id'] ?? 0);
        $body = trim((string)($input['body'] ?? ''));
        $replyToId = (int)($input['reply_to'] ?? 0);
        $errors = [];

        if ($contentId <= 0) {
            $errors['content_id'] = I18n::t('comments.invalid_content');
        }

        if ($body === '') {
            $errors['body'] = I18n::t('comments.body_required');
        }

        $lengthErrors = $this->schemaConstraintValidator->validate('comments', [
            'status' => self::STATUS_PUBLISHED,
            'body' => $body,
        ], [
            'status' => 'status',
            'body' => 'body',
        ]);

        foreach ($lengthErrors as $field => $message) {
            if (!isset($errors[$field])) {
                $errors[$field] = $message;
            }
        }

        $content = $this->findPublishedContent($contentId);
        if ($content === null && !isset($errors['content_id'])) {
            $errors['content_id'] = I18n::t('comments.invalid_content');
        }

        [$parentId, $replyTo] = $this->resolveParenting($contentId, $replyToId, $errors);

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $commentsTable = Table::name('comments');
        $stmt = $this->pdo->prepare("INSERT INTO $commentsTable (content, author, parent, reply_to, body, status) VALUES (:content, :author, :parent, :reply_to, :body, :status)");
        $ok = $stmt->execute([
            'content' => $contentId,
            'author' => $authorId,
            'parent' => $parentId > 0 ? $parentId : null,
            'reply_to' => $replyTo > 0 ? $replyTo : null,
            'body' => $body,
            'status' => self::STATUS_PUBLISHED,
        ]);

        return [
            'success' => $ok,
            'errors' => $ok ? [] : ['_global' => I18n::t('comments.save_failed')],
            'id' => $ok ? (int)$this->pdo->lastInsertId() : 0,
        ];
    }

    private function resolveParenting(int $contentId, int $replyToId, array &$errors): array
    {
        if ($replyToId <= 0) {
            return [0, 0];
        }

        $target = $this->findPublishedComment($replyToId, $contentId);
        if ($target === null) {
            $errors['reply_to'] = I18n::t('comments.invalid_parent');
            return [0, 0];
        }

        $targetParent = (int)($target['parent'] ?? 0);
        $parentId = $targetParent > 0 ? $targetParent : (int)($target['id'] ?? 0);

        return [$parentId, (int)($target['id'] ?? 0)];
    }

    private function findPublishedComment(int $commentId, int $contentId): ?array
    {
        if ($commentId <= 0 || $contentId <= 0) {
            return null;
        }

        $commentsTable = Table::name('comments');
        $stmt = $this->pdo->prepare("SELECT id, content, parent, status FROM $commentsTable WHERE id = :id AND content = :content AND status = :status LIMIT 1");
        $stmt->execute([
            'id' => $commentId,
            'content' => $contentId,
            'status' => self::STATUS_PUBLISHED,
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    private function findPublishedContent(int $contentId): ?array
    {
        if ($contentId <= 0) {
            return null;
        }

        $contentTable = Table::name('content');
        $stmt = $this->pdo->prepare("SELECT id FROM $contentTable WHERE id = :id AND status = :status AND created <= :now LIMIT 1");
        $stmt->execute([
            'id' => $contentId,
            'status' => 'published',
            'now' => date('Y-m-d H:i:s'),
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    private function mapItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'content' => (int)($row['content'] ?? 0),
            'author' => (int)($row['author'] ?? 0),
            'author_name' => (string)($row['author_name'] ?? ''),
            'parent' => (int)($row['parent'] ?? 0),
            'reply_to' => (int)($row['reply_to'] ?? 0),
            'reply_author_name' => (string)($row['reply_author_name'] ?? ''),
            'body' => (string)($row['body'] ?? ''),
            'created' => (string)($row['created'] ?? ''),
            'updated' => (string)($row['updated'] ?? ''),
        ];
    }
}
