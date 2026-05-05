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
    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_TRASH];

    private Query $query;
    private \PDO $pdo;
    private SchemaRules $schemaRules;

    public function __construct()
    {
        $this->pdo = Connection::get();
        $this->query = new Query($this->pdo);
        $this->schemaRules = new SchemaRules();
    }

    public function paginateTreeForContent(int $contentId, int $page = 1, int $perPage = 20, array $visibleDraftIds = [], string $sort = 'relevant'): array
    {
        if ($contentId <= 0) {
            return $this->emptyTreePagination($page, $perPage);
        }

        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $visibleDraftIds = $this->normalizeVisibleDraftIds($visibleDraftIds);
        $commentsTable = Table::name('comments');
        $countParams = [
            'content' => $contentId,
            'status' => self::STATUS_PUBLISHED,
        ];
        $sort = $this->normalizeTreeSort($sort);
        $parentWhere = 'p.content = :content AND p.parent IS NULL AND p.status = :status';
        if ($visibleDraftIds !== []) {
            [$draftSql, $draftParams] = $this->visibleDraftCondition($visibleDraftIds, 'p.id', 'p.status');
            $parentWhere = 'p.content = :content AND p.parent IS NULL AND (p.status = :status OR ' . $draftSql . ')';
            $countParams = array_merge($countParams, $draftParams);
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM $commentsTable p WHERE $parentWhere");
        $countStmt->execute($countParams);
        $total = (int)($countStmt->fetchColumn() ?: 0);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        if ($total <= 0) {
            return $this->emptyTreePagination($page, $perPage);
        }

        $parentStmt = $this->pdo->prepare("SELECT p.id FROM $commentsTable p WHERE $parentWhere ORDER BY " . $this->treeSortSql($commentsTable, $sort) . ' LIMIT :limit OFFSET :offset');
        foreach ($countParams as $key => $value) {
            $parentStmt->bindValue(':' . ltrim((string)$key, ':'), $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $parentStmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $parentStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $parentStmt->execute();
        $parentIds = array_map('intval', $parentStmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);

        return [
            'data' => $this->treeForParentIds($contentId, $parentIds, $visibleDraftIds),
            'total' => $total,
            'total_pages' => $totalPages,
            'page' => $page,
            'per_page' => $perPage,
            'sort' => $sort,
        ];
    }

    public function contentForComments(int $contentId): ?array
    {
        return $this->findOpenContent($contentId);
    }

    public function paginateChildrenForParent(int $contentId, int $parentId, int $page = 1, int $perPage = 10, array $visibleDraftIds = []): array
    {
        if ($contentId <= 0 || $parentId <= 0 || !$this->commentBelongsToContent($contentId, $parentId, true, $visibleDraftIds)) {
            return $this->emptyTreePagination($page, $perPage);
        }

        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $visibleDraftIds = $this->normalizeVisibleDraftIds($visibleDraftIds);
        $commentsTable = Table::name('comments');
        $params = [
            'content' => $contentId,
            'parent' => $parentId,
            'status' => self::STATUS_PUBLISHED,
        ];
        $where = 'content = :content AND parent = :parent AND status = :status';
        if ($visibleDraftIds !== []) {
            [$draftSql, $draftParams] = $this->visibleDraftCondition($visibleDraftIds, 'id');
            $where = 'content = :content AND parent = :parent AND (status = :status OR ' . $draftSql . ')';
            $params = array_merge($params, $draftParams);
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM $commentsTable WHERE $where");
        $countStmt->execute($params);
        $total = (int)($countStmt->fetchColumn() ?: 0);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);

        return [
            'data' => $total > 0 ? $this->childrenForParentPage($contentId, $parentId, $page, $perPage, $visibleDraftIds) : [],
            'total' => $total,
            'total_pages' => $totalPages,
            'page' => $page,
            'per_page' => $perPage,
            'parent' => $parentId,
        ];
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
        $where = [];
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

    public function deleteByStatus(int $id): ?string
    {
        return $this->query->deleteByStatus('comments', ['id' => $id], self::STATUS_TRASH);
    }

    public function restore(int $id): bool
    {
        return $this->query->restoreStatus('comments', ['id' => $id], self::STATUS_TRASH, self::STATUS_DRAFT);
    }

    public function setStatus(int $id, string $status): bool
    {
        return $this->query->setStatus('comments', ['id' => $id], $this->normalizeStatus($status));
    }

    public function statusCounts(array $statuses = []): array
    {
        return $this->query->countsBy('comments', 'status', $statuses);
    }

    public function pendingCount(): int
    {
        return $this->query->count('comments', ['status' => self::STATUS_DRAFT]);
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
            "SELECT id, name, comments_enabled FROM $contentTable WHERE id = :id AND " . Content::publicWhere() . " AND comments_enabled = 1 LIMIT 1"
        );
        $stmt->execute(array_merge(['id' => $contentId], Content::publicParams()));

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function treeForParentIds(int $contentId, array $parentIds, array $visibleDraftIds = []): array
    {
        $parentIds = array_values(array_unique(array_filter(
            array_map(static fn(mixed $id): int => (int)$id, $parentIds),
            static fn(int $id): bool => $id > 0
        )));
        if ($parentIds === []) {
            return [];
        }

        $commentsTable = Table::name('comments');
        $usersTable = Table::name('users');
        $params = [
            'content' => $contentId,
            'status' => self::STATUS_PUBLISHED,
        ];
        $parentPlaceholders = [];
        foreach ($parentIds as $index => $id) {
            $key = 'parent_' . $index;
            $parentPlaceholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $statusSql = 'c.status = :status';
        if ($visibleDraftIds !== []) {
            [$draftSql, $draftParams] = $this->visibleDraftCondition($visibleDraftIds, 'c.id', 'c.status');
            $statusSql = '(' . $statusSql . ' OR ' . $draftSql . ')';
            $params = array_merge($params, $draftParams);
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
            'WHERE c.content = :content AND ' . $statusSql,
            'AND c.id IN (' . implode(', ', $parentPlaceholders) . ')',
            'ORDER BY c.id ASC',
        ]));
        $stmt->execute($params);

        $parentsById = [];
        foreach ($this->buildTree($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $parent) {
            $parentsById[(int)($parent['id'] ?? 0)] = $parent;
        }

        $parents = [];
        foreach ($parentIds as $parentId) {
            if (isset($parentsById[$parentId])) {
                $parents[] = $parentsById[$parentId];
            }
        }

        foreach ($parents as &$parent) {
            $parent['children'] = [];
            $parent['children_total'] = $this->countChildrenForParent($contentId, (int)$parent['id'], $visibleDraftIds);
            $parent['children_page'] = 0;
            $parent['children_per_page'] = 10;
            $parent['children_total_pages'] = max(1, (int)ceil((int)$parent['children_total'] / 10));
        }
        unset($parent);

        return $parents;
    }

    private function countChildrenForParent(int $contentId, int $parentId, array $visibleDraftIds = []): int
    {
        $commentsTable = Table::name('comments');
        $params = [
            'content' => $contentId,
            'parent' => $parentId,
            'status' => self::STATUS_PUBLISHED,
        ];
        $where = 'content = :content AND parent = :parent AND status = :status';
        $visibleDraftIds = $this->normalizeVisibleDraftIds($visibleDraftIds);
        if ($visibleDraftIds !== []) {
            [$draftSql, $draftParams] = $this->visibleDraftCondition($visibleDraftIds, 'id');
            $where = 'content = :content AND parent = :parent AND (status = :status OR ' . $draftSql . ')';
            $params = array_merge($params, $draftParams);
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $commentsTable WHERE $where");
        $stmt->execute($params);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    private function childrenForParentPage(int $contentId, int $parentId, int $page, int $perPage, array $visibleDraftIds): array
    {
        $commentsTable = Table::name('comments');
        $usersTable = Table::name('users');
        $params = [
            'content' => $contentId,
            'parent' => $parentId,
            'status' => self::STATUS_PUBLISHED,
        ];
        $statusSql = 'c.status = :status';
        if ($visibleDraftIds !== []) {
            [$draftSql, $draftParams] = $this->visibleDraftCondition($visibleDraftIds, 'c.id', 'c.status');
            $statusSql = '(' . $statusSql . ' OR ' . $draftSql . ')';
            $params = array_merge($params, $draftParams);
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
            'WHERE c.content = :content AND c.parent = :parent AND ' . $statusSql,
            'ORDER BY c.created ASC, c.id ASC',
            'LIMIT :limit OFFSET :offset',
        ]));
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . ltrim((string)$key, ':'), $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->buildItems($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
    }

    private function commentBelongsToContent(int $contentId, int $commentId, bool $topLevelOnly, array $visibleDraftIds = []): bool
    {
        $commentsTable = Table::name('comments');
        $params = [
            'content' => $contentId,
            'id' => $commentId,
            'status' => self::STATUS_PUBLISHED,
        ];
        $where = 'content = :content AND id = :id AND status = :status';
        if ($topLevelOnly) {
            $where .= ' AND parent IS NULL';
        }

        $visibleDraftIds = $this->normalizeVisibleDraftIds($visibleDraftIds);
        if ($visibleDraftIds !== []) {
            [$draftSql, $draftParams] = $this->visibleDraftCondition($visibleDraftIds, 'id');
            $where = 'content = :content AND id = :id AND ' . ($topLevelOnly ? 'parent IS NULL AND ' : '') . '(status = :status OR ' . $draftSql . ')';
            $params = array_merge($params, $draftParams);
        }

        $stmt = $this->pdo->prepare("SELECT 1 FROM $commentsTable WHERE $where LIMIT 1");
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    private function buildTree(array $rows): array
    {
        $parents = [];
        $children = [];

        foreach ($rows as $row) {
            $item = $this->commentItemFromRow($row);

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

    private function buildItems(array $rows): array
    {
        return array_map(fn(array $row): array => $this->commentItemFromRow($row), $rows);
    }

    private function commentItemFromRow(array $row): array
    {
        return [
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
    }

    private function normalizeVisibleDraftIds(array $visibleDraftIds): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn(mixed $id): int => (int)$id, $visibleDraftIds),
            static fn(int $id): bool => $id > 0
        )));
    }

    private function visibleDraftCondition(array $visibleDraftIds, string $column, string $statusColumn = 'status'): array
    {
        $params = ['draft_status' => self::STATUS_DRAFT];
        $placeholders = [];
        foreach ($visibleDraftIds as $index => $id) {
            $key = 'pending_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        return [$column . ' IN (' . implode(', ', $placeholders) . ') AND ' . $statusColumn . ' = :draft_status', $params];
    }

    private function normalizeTreeSort(string $sort): string
    {
        $sort = trim($sort);
        return in_array($sort, ['relevant', 'newest', 'oldest'], true) ? $sort : 'relevant';
    }

    private function treeSortSql(string $commentsTable, string $sort): string
    {
        $repliesCount = "(SELECT COUNT(*) FROM $commentsTable child WHERE child.parent = p.id AND child.status = :status)";
        $lastActivity = "COALESCE((SELECT MAX(child.created) FROM $commentsTable child WHERE child.parent = p.id AND child.status = :status), p.created)";

        return match ($sort) {
            'newest' => "$lastActivity DESC, p.created DESC, p.id DESC",
            'oldest' => 'p.created ASC, p.id ASC',
            default => "$repliesCount DESC, $lastActivity DESC, p.id DESC",
        };
    }

    private function emptyTreePagination(int $page, int $perPage): array
    {
        return [
            'data' => [],
            'total' => 0,
            'total_pages' => 1,
            'page' => max(1, $page),
            'per_page' => max(1, $perPage),
        ];
    }

    private function findCommentForReply(int $commentId): ?array
    {
        return $this->query->first('comments', ['id', 'content', 'parent', 'status'], ['id' => $commentId]);
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, self::STATUSES, true)
            ? $status
            : self::STATUS_DRAFT;
    }

    private function authorExists(int $authorId): bool
    {
        if ($authorId <= 0) {
            return false;
        }

        return $this->query->exists('users', ['ID' => $authorId]);
    }
}
