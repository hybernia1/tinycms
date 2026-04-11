<?php
declare(strict_types=1);

namespace App\Controller\Admin\Content;

use App\Service\Support\I18n;

final class WorkflowApiController extends BaseContentController
{
    public function statusApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $status = trim((string)($_POST['status'] ?? ''));
        if ($id <= 0 || !in_array($status, $this->content->statuses(), true)) {
            $this->apiError('INVALID_DATA', I18n::t('common.invalid_data'));
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        $data = [
            'name' => (string)($item['name'] ?? ''),
            'excerpt' => (string)($item['excerpt'] ?? ''),
            'body' => (string)($item['body'] ?? ''),
            'status' => $status,
            'author' => (string)($item['author'] ?? ''),
            'created' => (string)($item['created'] ?? ''),
        ];

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($this->applyEditorAuthor($data, $authorId), $authorId, $id);
        if (($result['success'] ?? false) !== true) {
            $this->apiError('UPDATE_FAILED', I18n::t('content.update_failed'));
            return;
        }

        $this->apiOk(['id' => $id, 'status' => $status]);
    }

    public function draftInitApiV1(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $payload = $this->resolveAutosavePayload([
            'name' => $this->resolveAutosaveDraftName(0),
            'status' => 'draft',
            'excerpt' => '',
            'body' => '',
            'created' => date('Y-m-d H:i:s'),
            'author' => $authorId > 0 ? (string)$authorId : '',
        ], $authorId, 0);

        $result = $this->content->save($payload, $authorId);
        if (($result['success'] ?? false) !== true) {
            $this->apiError('CREATE_FAILED', I18n::t('content.save_failed'));
            return;
        }

        $newId = (int)($result['id'] ?? 0);
        if ($newId <= 0) {
            $this->apiError('CREATE_FAILED', I18n::t('content.save_failed'));
            return;
        }

        $this->apiOk([
            'id' => $newId,
            'name' => (string)($payload['name'] ?? ''),
            'status' => (string)($payload['status'] ?? 'draft'),
            'redirect' => $this->buildEditPath('admin/content', $newId),
        ]);
    }

    public function autosaveApiV1(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $authorId = (int)($this->authService->auth()->id() ?? 0);

        if ($id > 0) {
            $existing = $this->content->find($id);
            if ($existing === null) {
                $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
                return;
            }

            if (!$this->canManageByAuthor($existing)) {
                $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
                return;
            }
        }

        $payload = $this->resolveAutosavePayload($_POST, $authorId, $id);
        $result = $this->content->save($payload, $authorId, $id > 0 ? $id : null);
        if (($result['success'] ?? false) !== true) {
            $this->apiError('SAVE_FAILED', I18n::t('content.save_failed'), 422, [
                'details' => (array)($result['errors'] ?? []),
            ]);
            return;
        }

        $savedId = (int)($result['id'] ?? $id);
        if ($savedId <= 0) {
            $this->apiError('SAVE_FAILED', I18n::t('content.save_failed'));
            return;
        }

        $this->terms->syncContentTerms($savedId, (string)($_POST['terms'] ?? ''));
        $saved = $this->content->find($savedId) ?? [];

        $this->apiOk([
            'id' => $savedId,
            'name' => (string)($saved['name'] ?? ($payload['name'] ?? '')),
            'status' => (string)($saved['status'] ?? ($payload['status'] ?? 'draft')),
            'updated' => (string)($saved['updated'] ?? ''),
            'updated_label' => $this->formatDateTime((string)($saved['updated'] ?? '')),
            'terms' => $this->normalizeTermNames((string)($_POST['terms'] ?? '')),
            'redirect' => $this->buildEditPath('admin/content', $savedId),
        ]);
    }

    public function linkTitleApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $url = trim((string)($_GET['url'] ?? ''));
        if ($url === '' || !$this->isValidExternalUrl($url)) {
            $this->apiError('INVALID_URL', I18n::t('content.link_title_invalid_url'));
            return;
        }

        $title = $this->fetchRemoteTitle($url);
        if ($title === '') {
            $this->apiError('TITLE_NOT_FOUND', I18n::t('content.link_title_not_found'));
            return;
        }

        $this->apiOk(['title' => $title]);
    }
}
