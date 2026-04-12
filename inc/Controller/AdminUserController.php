<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;
use App\Service\Feature\UserService;
use App\View\AdminView;

final class AdminUserController extends BaseAdminController
{
    private AdminView $pages;
    private UserService $users;

    public function __construct(AdminView $pages, AuthService $authService, UserService $users, FlashService $flash, CsrfService $csrf)
    {
        parent::__construct($authService, $flash, $csrf);
        $this->pages = $pages;
        $this->users = $users;
    }

    public function list(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect)) {
            return;
        }

        [$page, $perPage, $status, $suspend, $query] = $this->resolveListQuery();

        $pagination = $this->users->paginate($page, $perPage, $suspend, $query);
        $statusCounts = $this->users->statusCounts();
        $this->pages->adminUsersList($pagination, $status, $query, $statusCounts);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect)) {
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
        if (!$this->guardAdminCsrf($redirect, 'admin/users', I18n::t('common.csrf_expired'))) {
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
        if (!$this->guardAdminCsrf($redirect, 'admin/users', I18n::t('common.csrf_expired'))) {
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
        if (!$this->guardAdmin($redirect)) {
            return;
        }

        $fallback = [
            'ID' => null,
            'name' => '',
            'email' => '',
            'role' => 'user',
            'suspend' => 0,
        ];
        $this->pages->adminUsersForm('add', $fallback, []);
    }

    public function addApiV1(callable $redirect): void
    {
        if (!$this->guardApiAdminCsrf(I18n::t('common.csrf_expired'))) {
            return;
        }

        $result = $this->users->save($_POST);

        if (($result['success'] ?? false) === true) {
            $this->apiOk([
                'redirect' => 'admin/users',
            ]);
            return;
        }

        $this->apiError('SAVE_FAILED', I18n::t('users.save_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->users->find($id);

        if ($user === null) {
            $this->flash->add('info', I18n::t('users.not_found'));
            $redirect('admin/users');
            return;
        }

        $this->pages->adminUsersForm('edit', $user, []);
    }

    public function editApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrf(I18n::t('common.csrf_expired'))) {
            return;
        }

        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('users.invalid_id'));
            return;
        }

        $result = $this->users->save($_POST, $id);

        if (($result['success'] ?? false) === true) {
            $this->apiOk([
                'redirect' => 'admin/users',
            ]);
            return;
        }

        $this->apiError('UPDATE_FAILED', I18n::t('users.update_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }

    private function mapListItem(array $row): array
    {
        $isSuspended = (int)($row['suspend'] ?? 0) === 1;
        return [
            'id' => (int)($row['ID'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'role' => (string)($row['role'] ?? 'user'),
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
