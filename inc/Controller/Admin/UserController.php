<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;
use App\Service\Support\PaginationConfig;
use App\Service\Feature\UserService;
use App\View\PageView;

final class UserController extends BaseController
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

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardSuperAdminCsrf($redirect, 'admin/users', I18n::t('common.csrf_expired'))) {
            return;
        }

        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('users.invalid_id'));
            return;
        }

        if (!$this->users->delete($id)) {
            $this->apiError('DELETE_FAILED', I18n::t('users.delete_failed'));
            return;
        }

        $this->apiOk(['id' => $id]);
    }

    public function suspendApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardSuperAdminCsrf($redirect, 'admin/users', I18n::t('common.csrf_expired'))) {
            return;
        }

        $mode = (string)($_POST['mode'] ?? 'suspend');
        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('users.invalid_id'));
            return;
        }

        if ($mode === 'unsuspend') {
            if (!$this->users->unsuspend($id)) {
                $this->apiError('UNSUSPEND_FAILED', I18n::t('users.unsuspend_failed'));
                return;
            }

            $this->apiOk(['id' => $id, 'suspend' => 0]);
            return;
        }

        if (!$this->users->suspend($id)) {
            $this->apiError('SUSPEND_FAILED', I18n::t('users.suspend_failed'));
            return;
        }

        $this->apiOk(['id' => $id, 'suspend' => 1]);
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
        if (!$this->guardSuperAdminCsrf($redirect, 'admin/users', I18n::t('common.csrf_expired'))) {
            return;
        }

        $result = $this->users->save($_POST);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', I18n::t('users.created'));
            $redirect('admin/users');
            return;
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
        if (!$this->guardSuperAdminCsrf($redirect, 'admin/users', I18n::t('common.csrf_expired'))) {
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
            return;
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
        [$page, $perPage, $query] = $this->resolvePaginationQuery();
        $status = $this->resolveStatusFilter(['all', 'active', 'suspended']);
        $suspend = $status === 'active' ? 0 : ($status === 'suspended' ? 1 : null);

        return [$page, $perPage, $status, $suspend, $query];
    }

}
