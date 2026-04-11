<?php
declare(strict_types=1);

namespace App\Controller\Admin\Content;

use App\Service\Support\I18n;

final class FormController extends BaseContentController
{
    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('content.invalid_id'));
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

        if (!$this->content->delete($id)) {
            $this->apiError('DELETE_FAILED', I18n::t('content.delete_failed'));
            return;
        }

        $this->apiOk(['id' => $id]);
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', I18n::t('content.invalid_id'));
            $redirect('admin/content');
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->flash->add('error', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        if (!$this->content->delete($id)) {
            $this->flash->add('error', I18n::t('content.delete_failed'));
            $redirect($this->buildEditPath('admin/content', $id));
            return;
        }

        $this->flash->add('success', I18n::t('content.deleted'));
        $redirect('admin/content');
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'status' => 'draft', 'excerpt' => '', 'body' => '', 'created' => date('Y-m-d H:i:s'), 'updated' => null];
        $fallback['author'] = (int)($this->authService->auth()->id() ?? 0);
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add', null);
        $statuses = $this->content->statuses();
        $item = $state['data'] ?? $fallback;
        $selectedTerms = $this->resolveSelectedTerms($item, null);
        $this->pages->adminContentForm('add', $item, $state['errors'] ?? [], $statuses, $this->users->authorOptions(), $selectedTerms);
    }

    public function addSubmit(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($this->applyEditorAuthor($_POST, $authorId), $authorId);

        if (($result['success'] ?? false) === true) {
            $newId = (int)($result['id'] ?? 0);
            if ($newId > 0) {
                $this->terms->syncContentTerms($newId, (string)($_POST['terms'] ?? ''));
            }
            $this->flash->add('success', I18n::t('content.created'));
            $redirect($newId > 0 ? $this->buildEditPath('admin/content', $newId) : 'admin/content');
            return;
        }

        $this->flash->add('error', I18n::t('content.save_failed'));
        $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/content/add');
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->content->find($id);

        if ($item === null) {
            $this->flash->add('info', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $statuses = $this->content->statuses();
        $formItem = $state['data'] ?? $item;
        $selectedTerms = $this->resolveSelectedTerms($formItem, $id);
        $this->pages->adminContentForm('edit', $formItem, $state['errors'] ?? [], $statuses, $this->users->authorOptions(), $selectedTerms);
    }

    public function editSubmit(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', I18n::t('content.invalid_id'));
            $redirect('admin/content');
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->flash->add('error', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($this->applyEditorAuthor($_POST, $authorId), $authorId, $id);

        if (($result['success'] ?? false) === true) {
            $this->terms->syncContentTerms($id, (string)($_POST['terms'] ?? ''));
            $this->flash->add('success', I18n::t('content.updated'));
            $redirect($this->buildEditPath('admin/content', $id));
            return;
        }

        $this->flash->add('error', I18n::t('content.update_failed'));
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect('admin/content/edit?id=' . $id);
    }
}
