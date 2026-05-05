<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;
use App\Service\Application\Auth;
use App\Service\Application\Comment as CommentService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\View\AdminView;

final class Comment extends Admin
{
    public function __construct(
        Auth $authService,
        private CommentService $comments,
        Flash $flash,
        Csrf $csrf,
        private AdminView $adminView
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function listApiV1(): void
    {
        if (!$this->guardApiAdmin()) {
            return;
        }

        $statuses = CommentService::STATUSES;
        [$page, $perPage, $status, $query] = $this->resolveSimpleListQuery(array_merge(['all'], $statuses));
        $pagination = $this->comments->paginate($page, $perPage, $status, $query);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->comments->statusCounts($statuses);

        if ($this->wantsHtmlResponse('list')) {
            $this->adminView->adminCommentListFragment($pagination, $status, $query, $statusCounts);
            return;
        }

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }

    public function deleteApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $item = $this->comments->find($id);
        if (!$this->requireEntity($item, 'NOT_FOUND', I18n::t('comments.not_found'))) {
            return;
        }

        $action = $this->comments->deleteByStatus($id);
        if ($action === null) {
            $this->apiError('DELETE_FAILED', I18n::t('comments.delete_failed'));
            return;
        }

        $this->apiOk([
            'id' => $id,
            'action' => $action,
            'message' => $action === 'soft_deleted' ? I18n::t('comments.moved_to_trash') : I18n::t('comments.deleted'),
            'redirect' => $action === 'soft_deleted' || (int)($item['parent'] ?? 0) > 0
                ? $this->redirectForComment($item)
                : $this->buildPath('admin/comments'),
        ]);
    }

    public function restoreApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        if (!$this->requireEntity($this->comments->find($id), 'NOT_FOUND', I18n::t('comments.not_found'))) {
            return;
        }

        if (!$this->comments->restore($id)) {
            $this->apiError('RESTORE_FAILED', I18n::t('comments.restore_failed'));
            return;
        }

        $this->apiOk([
            'id' => $id,
            'status' => CommentService::STATUS_DRAFT,
            'message' => I18n::t('comments.restored'),
            'redirect' => $this->redirectForComment($this->comments->find($id) ?? ['id' => $id]),
        ]);
    }

    public function editApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $item = $this->comments->find($id);
        if (!$this->requireEntity($item, 'NOT_FOUND', I18n::t('comments.not_found'))) {
            return;
        }

        $result = $this->comments->update($id, $_POST);
        if (($result['success'] ?? false) === true) {
            $this->apiOk([
                'redirect' => $this->redirectForComment($item),
                'message' => I18n::t('comments.updated'),
            ]);
            return;
        }

        $this->apiError('UPDATE_FAILED', I18n::t('comments.update_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function statusApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $mode = (string)($_POST['mode'] ?? CommentService::STATUS_DRAFT);
        $item = $this->comments->find($id);
        if (!$this->requireEntity($item, 'NOT_FOUND', I18n::t('comments.not_found'))) {
            return;
        }

        if ((string)($item['status'] ?? '') === CommentService::STATUS_TRASH) {
            $this->apiError('INVALID_STATUS', I18n::t('comments.status_change_forbidden_in_trash'));
            return;
        }

        if ($mode === 'publish') {
            if (!$this->comments->setStatus($id, CommentService::STATUS_PUBLISHED)) {
                $this->apiError('PUBLISH_FAILED', I18n::t('comments.publish_failed'));
                return;
            }

            $this->apiOk([
                'id' => $id,
                'status' => CommentService::STATUS_PUBLISHED,
                'message' => I18n::t('comments.published'),
                'redirect' => $this->redirectForComment($item),
            ]);
            return;
        }

        if (!$this->comments->setStatus($id, CommentService::STATUS_DRAFT)) {
            $this->apiError('DRAFT_FAILED', I18n::t('comments.draft_failed'));
            return;
        }

        $this->apiOk([
            'id' => $id,
            'status' => CommentService::STATUS_DRAFT,
            'message' => I18n::t('comments.switched_to_draft'),
            'redirect' => $this->redirectForComment($item),
        ]);
    }

    private function mapListItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'content' => (int)($row['content'] ?? 0),
            'content_name' => (string)($row['content_name'] ?? ''),
            'parent' => (int)($row['parent'] ?? 0),
            'author_name' => (string)($row['author_name'] ?? ''),
            'author_email' => (string)($row['author_email'] ?? ''),
            'replies_count' => (int)($row['replies_count'] ?? 0),
            'can_edit' => true,
            'can_delete' => true,
            'can_restore' => (string)($row['status'] ?? '') === CommentService::STATUS_TRASH,
            'status' => (string)($row['status'] ?? CommentService::STATUS_DRAFT),
            'body' => $this->excerpt((string)($row['body'] ?? '')),
            'created' => (string)($row['created'] ?? ''),
            'created_label' => $this->formatDateTime((string)($row['created'] ?? '')),
        ];
    }

    private function excerpt(string $body): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
        return mb_strlen($clean) > 160 ? mb_substr($clean, 0, 157) . '...' : $clean;
    }

    private function redirectForComment(array $item): string
    {
        $targetId = (int)($item['parent'] ?? 0);
        if ($targetId <= 0) {
            $targetId = (int)($item['id'] ?? 0);
        }

        return $targetId > 0 ? $this->buildEditPath('admin/comments', $targetId) : $this->buildPath('admin/comments');
    }
}
