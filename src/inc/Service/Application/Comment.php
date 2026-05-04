<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\SchemaRules;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\I18n;
use InvalidArgumentException;

final class Comment
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_TRASH = 'trash';

    private Query $query;
    private \PDO $pdo;
    private SchemaRules $schemaRules;

    public function __construct()
    {
        $this->pdo = Connection::get();
        $this->query = new Query($this->pdo);
        $this->schemaRules = new SchemaRules();
    }

    public function treeForContent(int $contentId, array $visibleDraftIds = []): array
    {
        if ($contentId <= 0) {
            return [];
        }

        $visibleDraftIds = array_values(array_unique(array_filter(
            array_map(static fn(mixed $id): int => (int)$id, $visibleDraftIds),
            static fn(int $id): bool => $id > 0
        )));
        $commentsTable = Table::name('comments');
        $usersTable = Table::name('users');
        $draftCondition = '';
        $params = [
            'content' => $contentId,
            'status' => self::STATUS_PUBLISHED,
        ];
        if ($visibleDraftIds !== []) {
            $pendingPlaceholders = [];
            foreach ($visibleDraftIds as $index => $id) {
                $key = 'pending_' . $index;
                $pendingPlaceholders[] = ':' . $key;
                $params[$key] = $id;
            }
            $draftCondition = ' OR (c.status = :draft_status AND c.id IN (' . implode(', ', $pendingPlaceholders) . '))';
            $params['draft_status'] = self::STATUS_DRAFT;
        }

        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.parent, c.reply_to, c.author, c.status, c.body, c.created, c.updated,',
            'COALESCE(NULLIF(u.name, \'\'), c.author_name) AS author_name,',
            'COALESCE(NULLIF(u.email, \'\'), c.author_email) AS author_email,',
            'COALESCE(NULLIF(reply_user.name, \'\'), reply_target.author_name) AS reply_to_author_name',
            "FROM $commentsTable c",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            "LEFT JOIN $commentsTable reply_target ON reply_target.id = c.reply_to",
            "LEFT JOIN $usersTable reply_user ON reply_user.id = reply_target.author",
            'WHERE c.content = :content AND (c.status = :status' . $draftCondition . ')',
            'ORDER BY COALESCE(c.parent, c.id) ASC, c.parent IS NOT NULL ASC, c.created ASC, c.id ASC',
        ]));
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $parents = [];
        $children = [];

        foreach ($rows as $row) {
            $item = [
                'id' => (int)($row['id'] ?? 0),
                'parent' => (int)($row['parent'] ?? 0),
                'reply_to' => (int)($row['reply_to'] ?? 0),
                'reply_to_author_name' => trim((string)($row['reply_to_author_name'] ?? '')),
                'author' => (int)($row['author'] ?? 0),
                'author_name' => trim((string)($row['author_name'] ?? '')),
                'author_email' => trim((string)($row['author_email'] ?? '')),
                'status' => (string)($row['status'] ?? self::STATUS_PUBLISHED),
                'is_pending' => (string)($row['status'] ?? '') === self::STATUS_DRAFT,
                'body' => (string)($row['body'] ?? ''),
                'created' => (string)($row['created'] ?? ''),
                'updated' => (string)($row['updated'] ?? ''),
                'children' => [],
            ];

            if ($item['parent'] > 0) {
                $children[$item['parent']][] = $item;
                continue;
            }

            $parents[$item['id']] = $item;
        }

        foreach ($children as $parentId => $items) {
            if (isset($parents[$parentId])) {
                $parents[$parentId]['children'] = $items;
            }
        }

        return array_values($parents);
    }

    public function countForContent(int $contentId): int
    {
        if ($contentId <= 0) {
            return 0;
        }

        $commentsTable = Table::name('comments');
        $contentTable = Table::name('content');
        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT COUNT(*)',
            "FROM $commentsTable c",
            "INNER JOIN $contentTable content ON content.id = c.content",
            "LEFT JOIN $commentsTable parent_comment ON parent_comment.id = c.parent",
            'WHERE c.content = :content AND c.status = :status AND content.comments_enabled = 1',
            'AND (c.parent IS NULL OR parent_comment.status = :status)',
        ]));
        $stmt->execute([
            'content' => $contentId,
            'status' => self::STATUS_PUBLISHED,
        ]);

        return (int)($stmt->fetchColumn() ?: 0);
    }

    public function paginate(int $page = 1, int $perPage = 10, string $status = 'all', string $search = ''): array
    {
        $commentsTable = Table::name('comments');
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $search = mb_substr(trim($search), 0, 100);
        $where = ['c.parent IS NULL'];
        $params = [];

        if ($status !== 'all') {
            $where[] = 'c.status = :status';
            $params['status'] = $status;
        }

        if ($search !== '') {
            $where[] = implode(' ', [
                '(c.body LIKE :search OR content.name LIKE :search OR u.name LIKE :search OR u.email LIKE :search OR c.author_name LIKE :search OR c.author_email LIKE :search',
                "OR EXISTS (SELECT 1 FROM $commentsTable child LEFT JOIN $usersTable child_user ON child_user.id = child.author",
                'WHERE child.parent = c.id AND (child.body LIKE :search OR child_user.name LIKE :search OR child_user.email LIKE :search OR child.author_name LIKE :search OR child.author_email LIKE :search)))',
            ]);
            $params['search'] = '%' . $search . '%';
        }

        return $this->query->paginateQuery([
            'select' => implode("\n", [
                'c.id, c.content, c.parent, c.reply_to, c.author, c.status, c.body, c.ip_address, c.created, c.updated,',
                'content.name AS content_name,',
                'COALESCE(NULLIF(u.name, \'\'), c.author_name) AS author_name,',
                'COALESCE(NULLIF(u.email, \'\'), c.author_email) AS author_email,',
                "(SELECT COUNT(*) FROM $commentsTable child WHERE child.parent = c.id) AS replies_count",
            ]),
            'from' => "FROM $commentsTable c",
            'joins' => [
                "INNER JOIN $contentTable content ON content.id = c.content",
                "LEFT JOIN $usersTable u ON u.id = c.author",
            ],
            'where' => $where,
            'params' => $params,
            'orderBy' => 'c.created DESC, c.id DESC',
        ], [
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $commentsTable = Table::name('comments');
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.content, c.parent, c.reply_to, c.author, c.status, c.body, c.ip_address, c.created, c.updated,',
            'content.name AS content_name,',
            'COALESCE(NULLIF(u.name, \'\'), c.author_name) AS author_name,',
            'COALESCE(NULLIF(u.email, \'\'), c.author_email) AS author_email',
            "FROM $commentsTable c",
            "INNER JOIN $contentTable content ON content.id = c.content",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            'WHERE c.id = :id',
            'LIMIT 1',
        ]));
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, array $input): array
    {
        $item = $this->find($id);
        if ($item === null) {
            return ['success' => false, 'errors' => ['_global' => I18n::t('comments.not_found')]];
        }

        $body = trim((string)($input['body'] ?? ''));
        if ($body === '') {
            return ['success' => false, 'errors' => ['body' => I18n::t('comments.body_required')]];
        }

        $status = $this->normalizeStatus((string)($input['status'] ?? ($item['status'] ?? self::STATUS_PUBLISHED)));

        try {
            $updated = $this->query->update('comments', [
                'body' => $body,
                'status' => $status,
            ], ['id' => $id]);
            return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'errors' => ['_global' => $e->getMessage()]];
        }
    }

    public function childrenForParent(int $parentId): array
    {
        if ($parentId <= 0) {
            return [];
        }

        $commentsTable = Table::name('comments');
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.content, c.parent, c.reply_to, c.author, c.status, c.body, c.ip_address, c.created, c.updated,',
            'content.name AS content_name,',
            'COALESCE(NULLIF(u.name, \'\'), c.author_name) AS author_name,',
            'COALESCE(NULLIF(u.email, \'\'), c.author_email) AS author_email',
            "FROM $commentsTable c",
            "INNER JOIN $contentTable content ON content.id = c.content",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            'WHERE c.parent = :parent',
            'ORDER BY c.created ASC, c.id ASC',
        ]));
        $stmt->execute(['parent' => $parentId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private function delete(int $id): bool
    {
        return $id > 0 && $this->query->delete('comments', ['id' => $id]) > 0;
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
        $status = $this->normalizeStatus($status);

        if ($item === null || (string)($item['status'] ?? '') === $status) {
            return false;
        }

        return $this->query->update('comments', ['status' => $status], ['id' => $id]) > 0;
    }

    public function statusCounts(array $statuses = []): array
    {
        $commentsTable = Table::name('comments');
        $stmt = $this->pdo->query("SELECT status FROM $commentsTable WHERE parent IS NULL");
        $rows = $stmt ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
        $counts = [
            'all' => count($rows),
        ];

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

    public function pendingCount(): int
    {
        $commentsTable = Table::name('comments');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $commentsTable WHERE status = :status");
        $stmt->execute(['status' => self::STATUS_DRAFT]);

        return (int)($stmt->fetchColumn() ?: 0);
    }

    public function save(int $contentId, int $authorId, array $input, string $ipAddress, bool $allowAnonymous = false): array
    {
        if ($this->findOpenContent($contentId) === null) {
            return ['success' => false, 'errors' => ['_global' => I18n::t('comments.closed')]];
        }

        $hasAuthor = $this->authorExists($authorId);
        if (!$hasAuthor && !$allowAnonymous) {
            return ['success' => false, 'errors' => ['_global' => I18n::t('comments.login_required')]];
        }

        $body = trim((string)($input['body'] ?? ''));
        $authorName = $hasAuthor ? '' : $this->schemaRules->truncate(
            'comments',
            'author_name',
            trim((string)($input['author_name'] ?? '')),
            255
        );
        $authorEmail = $hasAuthor ? '' : mb_strtolower($this->schemaRules->truncate(
            'comments',
            'author_email',
            trim((string)($input['author_email'] ?? '')),
            255
        ));
        $parentId = (int)($input['parent'] ?? 0);
        $replyToId = (int)($input['reply_to'] ?? 0);
        $replyTarget = $replyToId > 0 ? $this->findCommentForReply($replyToId) : null;
        if ($replyTarget !== null) {
            $parentId = (int)($replyTarget['parent'] ?? 0) > 0 ? (int)$replyTarget['parent'] : (int)$replyTarget['id'];
        }
        $parent = $parentId > 0 ? $this->findCommentForReply($parentId) : null;
        $errors = [];

        if ($body === '') {
            $errors['body'] = I18n::t('comments.body_required');
        }

        if (!$hasAuthor && $authorName === '') {
            $errors['author_name'] = I18n::t('comments.author_name_required');
        }

        if (!$hasAuthor && $authorEmail !== '' && !filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['author_email'] = I18n::t('comments.author_email_invalid');
        }

        if (
            $parentId > 0
            && (!$parent
                || (int)$parent['content'] !== $contentId
                || (int)($parent['parent'] ?? 0) > 0
                || (string)($parent['status'] ?? '') !== self::STATUS_PUBLISHED)
        ) {
            $errors['parent'] = I18n::t('comments.parent_invalid');
        }

        if (
            $replyToId > 0
            && (!$replyTarget
                || (int)$replyTarget['content'] !== $contentId
                || (string)($replyTarget['status'] ?? '') !== self::STATUS_PUBLISHED)
        ) {
            $errors['reply_to'] = I18n::t('comments.parent_invalid');
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $ipAddress = $this->schemaRules->truncate('comments', 'ip_address', trim($ipAddress), 45);

        try {
            $newId = $this->query->insert('comments', [
                'content' => $contentId,
                'parent' => $parentId > 0 ? $parentId : null,
                'reply_to' => $replyToId > 0 ? $replyToId : null,
                'author' => $hasAuthor ? $authorId : null,
                'author_name' => $authorName !== '' ? $authorName : null,
                'author_email' => $authorEmail !== '' ? $authorEmail : null,
                'ip_address' => $ipAddress !== '' ? $ipAddress : null,
                'status' => $hasAuthor ? self::STATUS_PUBLISHED : self::STATUS_DRAFT,
                'body' => $body,
            ]);

            return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'errors' => ['_global' => $e->getMessage()]];
        }
    }

    private function findOpenContent(int $contentId): ?array
    {
        if ($contentId <= 0) {
            return null;
        }

        $contentTable = Table::name('content');
        $stmt = $this->pdo->prepare(
            "SELECT id FROM $contentTable WHERE id = :id AND " . Content::publicWhere() . " AND comments_enabled = 1 LIMIT 1"
        );
        $stmt->execute(array_merge(['id' => $contentId], Content::publicParams()));

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function findCommentForReply(int $commentId): ?array
    {
        $rows = $this->query->select('comments', ['id', 'content', 'parent', 'status'], ['id' => $commentId]);
        return $rows[0] ?? null;
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_TRASH], true)
            ? $status
            : self::STATUS_DRAFT;
    }

    private function authorExists(int $authorId): bool
    {
        if ($authorId <= 0) {
            return false;
        }

        return $this->query->select('users', ['ID'], ['ID' => $authorId]) !== [];
    }
}
