<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;
use App\Service\Application\Auth;
use App\Service\Application\Comment as CommentService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class Comment extends Admin
{
    public function __construct(
        Auth $authService,
        private CommentService $comments,
        Flash $flash,
        Csrf $csrf,
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function addApiV1(): void
    {
        if (!$this->guardAuthenticatedApi()) {
            return;
        }

        if (!$this->verifyApiCsrf()) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        if ($authorId <= 0) {
            $this->apiError('UNAUTHENTICATED', I18n::t('comments.login_required'), 401, [
                'errors' => [],
                'csrf' => $this->csrf->token(),
            ]);
            return;
        }

        $result = $this->comments->create($_POST, $authorId);
        if (($result['success'] ?? false) !== true) {
            $this->apiError('SAVE_FAILED', I18n::t('comments.save_failed'), 422, [
                'errors' => $result['errors'] ?? [],
                'csrf' => $this->csrf->token(),
            ]);
            return;
        }

        $contentId = (int)($_POST['content_id'] ?? 0);
        $slug = trim((string)($_POST['content_slug'] ?? ''));
        $redirect = $slug !== '' ? $slug . '/comments' : 'content/' . $contentId . '/comments';

        $this->apiOk([
            'message' => I18n::t('comments.created'),
            'csrf' => $this->csrf->token(),
            'redirect' => $this->buildPath($redirect),
            'id' => (int)($result['id'] ?? 0),
        ]);
    }

    public function listApiV1(callable $_redirect): void
    {
        if (!$this->guardApiAdmin()) {
            return;
        }

        [$page, $perPage, $status, $query] = $this->resolveSimpleListQuery(['all', CommentService::STATUS_DRAFT, CommentService::STATUS_PUBLISHED]);
        $pagination = $this->comments->paginate($page, $perPage, $status, $query);
        $statusCounts = $this->comments->statusCounts();
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }

    public function deleteApiV1(callable $_redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        if (!$this->requireEntity($this->comments->find($id), 'NOT_FOUND', I18n::t('comments.not_found'))) {
            return;
        }

        if (!$this->comments->delete($id)) {
            $this->apiError('DELETE_FAILED', I18n::t('comments.delete_failed'));
            return;
        }

        $this->apiOk([
            'id' => $id,
            'message' => I18n::t('comments.deleted'),
            'redirect' => $this->buildPath('admin/comments'),
        ]);
    }

    public function editApiV1(callable $_redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        if (!$this->requireEntity($this->comments->find($id), 'NOT_FOUND', I18n::t('comments.not_found'))) {
            return;
        }

        $result = $this->comments->save($_POST, $id);
        if (($result['success'] ?? false) !== true) {
            $this->apiError('UPDATE_FAILED', I18n::t('comments.update_failed'), 422, [
                'errors' => $result['errors'] ?? [],
            ]);
            return;
        }

        $this->apiOk([
            'message' => I18n::t('comments.updated'),
        ]);
    }

    public function statusApiV1(callable $_redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $item = $this->comments->find($id);
        if (!$this->requireEntity($item, 'NOT_FOUND', I18n::t('comments.not_found'))) {
            return;
        }

        $mode = trim((string)($_POST['mode'] ?? 'draft'));
        if ($mode === 'publish') {
            if (!$this->comments->setStatus($id, CommentService::STATUS_PUBLISHED)) {
                $this->apiError('PUBLISH_FAILED', I18n::t('comments.publish_failed'));
                return;
            }
            $this->apiOk([
                'id' => $id,
                'status' => CommentService::STATUS_PUBLISHED,
                'message' => I18n::t('comments.published'),
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
        ]);
    }

    private function guardAuthenticatedApi(): bool
    {
        if ($this->authService->auth()->check()) {
            return true;
        }

        $this->apiError('UNAUTHENTICATED', I18n::t('comments.login_required'), 401, [
            'errors' => [],
            'csrf' => $this->csrf->token(),
        ]);
        return false;
    }

    private function verifyApiCsrf(): bool
    {
        if ($this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            return true;
        }

        $this->apiError('INVALID_CSRF', I18n::t('common.invalid_csrf'), 419, [
            'errors' => [],
            'csrf' => $this->csrf->token(),
        ]);
        return false;
    }

    private function mapListItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'comment_edit_path' => (int)($row['id'] ?? 0) > 0 ? $this->buildPath('admin/comments/edit?id=' . (int)($row['id'] ?? 0)) : '',
            'content' => (int)($row['content'] ?? 0),
            'content_name' => (string)($row['content_name'] ?? ''),
            'content_edit_path' => (int)($row['content'] ?? 0) > 0 ? $this->buildEditPath('admin/content', (int)($row['content'] ?? 0)) : '',
            'author' => (int)($row['author'] ?? 0),
            'author_name' => (string)($row['author_name'] ?? ''),
            'status' => (string)($row['status'] ?? CommentService::STATUS_PUBLISHED),
            'body' => (string)($row['body'] ?? ''),
            'parent' => (int)($row['parent'] ?? 0),
            'reply_to' => (int)($row['reply_to'] ?? 0),
            'created' => (string)($row['created'] ?? ''),
            'created_label' => $this->formatDateTime((string)($row['created'] ?? '')),
        ];
    }
}
