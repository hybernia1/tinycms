<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\TermService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;
use App\View\PageView;

final class AdminTermController extends BaseAdminController
{
    private const PER_PAGE_ALLOWED = [10, 20, 50];
    private const FORM_STATE_KEY = 'admin_term_form_state';

    public function __construct(
        private PageView $pages,
        AuthService $authService,
        private TermService $terms,
        FlashService $flash,
        CsrfService $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function list(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);
        $query = trim((string)($_GET['q'] ?? ''));

        if (!in_array($perPage, self::PER_PAGE_ALLOWED, true)) {
            $perPage = 10;
        }

        $pagination = $this->terms->paginate($page, $perPage, $query);
        $this->pages->adminTermList($pagination, self::PER_PAGE_ALLOWED, $query);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);
        $query = trim((string)($_GET['q'] ?? ''));
        if (!in_array($perPage, self::PER_PAGE_ALLOWED, true)) {
            $perPage = 10;
        }

        $pagination = $this->terms->paginate($page, $perPage, $query);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));

        $this->respondJson([
            'ok' => true,
            'data' => $items,
            'meta' => [
                'page' => (int)($pagination['page'] ?? 1),
                'per_page' => (int)($pagination['per_page'] ?? $perPage),
                'total_pages' => (int)($pagination['total_pages'] ?? 1),
                'query' => $query,
            ],
        ]);
    }

    public function suggest(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $query = trim((string)($_GET['q'] ?? ''));
        $this->respondJson([
            'ok' => true,
            'data' => $this->terms->search($query, 15),
            'meta' => [
                'query' => $query,
            ],
        ]);
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'body' => '', 'created' => date('Y-m-d H:i:s'), 'updated' => null];
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add', null);
        $this->pages->adminTermForm('add', $state['data'] ?? $fallback, $state['errors'] ?? []);
    }

    public function addSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/terms', I18n::t('common.invalid_csrf', 'Invalid CSRF token.'))
        ) {
            return;
        }

        $result = $this->terms->save($_POST);
        if (($result['success'] ?? false) === true) {
            $newId = (int)($result['id'] ?? 0);
            $this->flash->add('success', I18n::t('terms.created', 'Tag created.'));
            $redirect($newId > 0 ? $this->editPath($newId) : 'admin/terms');
            return;
        }

        $this->flash->add('error', I18n::t('terms.save_failed', 'Could not save tag.'));
        $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/terms/add');
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->terms->find($id);

        if ($item === null) {
            $this->flash->add('info', I18n::t('terms.not_found', 'Tag not found.'));
            $redirect('admin/terms');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $this->pages->adminTermForm('edit', $state['data'] ?? $item, $state['errors'] ?? []);
    }

    public function editSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/terms', I18n::t('common.invalid_csrf', 'Invalid CSRF token.'))
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', I18n::t('terms.invalid_id', 'Invalid tag ID.'));
            $redirect('admin/terms');
            return;
        }

        $result = $this->terms->save($_POST, $id);
        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', I18n::t('terms.updated', 'Tag updated.'));
            $redirect($this->editPath($id));
            return;
        }

        $this->flash->add('error', I18n::t('terms.update_failed', 'Could not update tag.'));
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect($this->editPath($id));
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/terms', I18n::t('common.invalid_csrf', 'Invalid CSRF token.'))
        ) {
            return;
        }

        if ($id <= 0) {
            $this->respondJson(['ok' => false, 'error' => ['code' => 'INVALID_ID', 'message' => I18n::t('terms.invalid_id', 'Invalid tag ID.')]], 422);
            return;
        }

        if (!$this->terms->delete($id)) {
            $this->respondJson(['ok' => false, 'error' => ['code' => 'DELETE_FAILED', 'message' => I18n::t('terms.delete_failed', 'Could not delete tag.')]], 422);
            return;
        }

        $this->respondJson(['ok' => true, 'data' => ['id' => $id]]);
    }

    private function editPath(int $id): string
    {
        return 'admin/terms/edit?id=' . $id;
    }

    private function mapListItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'body' => (string)($row['body'] ?? ''),
            'created' => (string)($row['created'] ?? ''),
            'created_label' => $this->formatDateTime((string)($row['created'] ?? '')),
        ];
    }

    private function formatDateTime(string $value): string
    {
        $stamp = $value !== '' ? strtotime($value) : false;
        if ($stamp === false) {
            return '';
        }

        return date(APP_DATETIME_FORMAT, $stamp);
    }
}
