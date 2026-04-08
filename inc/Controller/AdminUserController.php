<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;
use App\Service\Support\PaginationConfig;
use App\Service\Feature\UserService;
use App\View\PageView;

final class AdminUserController extends BaseAdminController
{
    private const FORM_STATE_KEY = 'admin_users_form_state';

    private PageView $pages;
    private UserService $users;

    public function __construct(PageView $pages, AuthService $authService, UserService $users, FlashService $flash, CsrfService $csrf)
    {
        parent::__construct($authService, $flash, $csrf);
        $this->pages = $pages;
        $this->users = $users;
    }

    public function list(callable $redirect): void
    {
        if (!$this->guardSuperAdmin($redirect)) {
            return;
        }

        [$page, $perPage, $status, $suspend, $query] = $this->resolveListQuery();

        $pagination = $this->users->paginate($page, $perPage, $suspend, $query);
        $statusCounts = $this->users->statusCounts();
        $this->pages->adminUsersList($pagination, PaginationConfig::allowed(), $status, $query, $statusCounts);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardSuperAdmin($redirect)) {
            return;
        }

        [$page, $perPage, $status, $suspend, $query] = $this->resolveListQuery();
        $pagination = $this->users->paginate($page, $perPage, $suspend, $query);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->users->statusCounts();

        $this->respondJson([
            'ok' => true,
            'data' => $items,
            'meta' => [
                'page' => (int)($pagination['page'] ?? 1),
                'per_page' => (int)($pagination['per_page'] ?? $perPage),
                'total_pages' => (int)($pagination['total_pages'] ?? 1),
                'status' => $status,
                'query' => $query,
                'status_counts' => $statusCounts,
            ],
        ]);
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (
            !$this->guardSuperAdmin($redirect)
            || !$this->guardCsrf($redirect, 'admin/users', I18n::t('common.csrf_expired'))
        ) {
            return;
        }

        if ($id <= 0) {
            $this->respondJson(['ok' => false, 'error' => ['code' => 'INVALID_ID', 'message' => I18n::t('users.invalid_id')]], 422);
            return;
        }

        if (!$this->users->delete($id)) {
            $this->respondJson(['ok' => false, 'error' => ['code' => 'DELETE_FAILED', 'message' => I18n::t('users.delete_failed')]], 422);
            return;
        }

        $this->respondJson(['ok' => true, 'data' => ['id' => $id]]);
    }

    public function suspendApiV1(callable $redirect, int $id): void
    {
        if (
            !$this->guardSuperAdmin($redirect)
            || !$this->guardCsrf($redirect, 'admin/users', I18n::t('common.csrf_expired'))
        ) {
            return;
        }

        $mode = (string)($_POST['mode'] ?? 'suspend');
        if ($id <= 0) {
            $this->respondJson(['ok' => false, 'error' => ['code' => 'INVALID_ID', 'message' => I18n::t('users.invalid_id')]], 422);
            return;
        }

        if ($mode === 'unsuspend') {
            if (!$this->users->unsuspend($id)) {
                $this->respondJson(['ok' => false, 'error' => ['code' => 'UNSUSPEND_FAILED', 'message' => I18n::t('users.unsuspend_failed')]], 422);
                return;
            }

            $this->respondJson(['ok' => true, 'data' => ['id' => $id, 'suspend' => 0]]);
            return;
        }

        if (!$this->users->suspend($id)) {
            $this->respondJson(['ok' => false, 'error' => ['code' => 'SUSPEND_FAILED', 'message' => I18n::t('users.suspend_failed')]], 422);
            return;
        }

        $this->respondJson(['ok' => true, 'data' => ['id' => $id, 'suspend' => 1]]);
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardSuperAdmin($redirect)) {
            return;
        }

        $fallback = [
            'ID' => null,
            'name' => '',
            'email' => '',
            'role' => 'editor',
            'suspend' => 0,
        ];
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add');
        $this->pages->adminUsersForm('add', $state['data'] ?? $fallback, $state['errors'] ?? []);
    }

    public function addSubmit(callable $redirect): void
    {
        if (
            !$this->guardSuperAdmin($redirect)
            || !$this->guardCsrf($redirect, 'admin/users', I18n::t('common.csrf_expired'))
        ) {
            return;
        }

        $result = $this->users->save($_POST);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', I18n::t('users.created'));
            $redirect('admin/users');
        }

        $this->flash->add('error', I18n::t('users.save_failed'));
        $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/users/add');
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardSuperAdmin($redirect)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->users->find($id);

        if ($user === null) {
            $this->flash->add('info', I18n::t('users.not_found'));
            $redirect('admin/users');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $this->pages->adminUsersForm('edit', $state['data'] ?? $user, $state['errors'] ?? []);
    }

    public function editSubmit(callable $redirect): void
    {
        if (
            !$this->guardSuperAdmin($redirect)
            || !$this->guardCsrf($redirect, 'admin/users', I18n::t('common.csrf_expired'))
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', I18n::t('users.invalid_id'));
            $redirect('admin/users');
            return;
        }

        $result = $this->users->save($_POST, $id);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', I18n::t('users.updated'));
            $redirect('admin/users');
        }

        $this->flash->add('error', I18n::t('users.update_failed'));
        $data = array_merge($_POST, ['ID' => $id]);
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, $data, $result['errors'] ?? []);
        $redirect('admin/users/edit?id=' . $id);
    }

    private function mapListItem(array $row): array
    {
        $isSuspended = (int)($row['suspend'] ?? 0) === 1;
        return [
            'id' => (int)($row['ID'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'role' => (string)($row['role'] ?? 'editor'),
            'is_admin' => (string)($row['role'] ?? '') === 'admin',
            'is_suspended' => $isSuspended,
        ];
    }

    private function resolveListQuery(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $defaultPerPage = PaginationConfig::perPage();
        $perPage = (int)($_GET['per_page'] ?? $defaultPerPage);
        $status = (string)($_GET['status'] ?? 'all');
        $status = in_array($status, ['all', 'active', 'suspended'], true) ? $status : 'all';
        $suspend = $status === 'active' ? 0 : ($status === 'suspended' ? 1 : null);
        $query = trim((string)($_GET['q'] ?? ''));

        if (!in_array($perPage, PaginationConfig::allowed(), true)) {
            $perPage = $defaultPerPage;
        }

        return [$page, $perPage, $status, $suspend, $query];
    }

}
