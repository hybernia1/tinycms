<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;
use App\Service\Application\Auth;
use App\Service\Application\User as UserService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class User extends Admin
{
    public function __construct(
        Auth $authService,
        private UserService $users,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function listApiV1(callable $_redirect): void
    {
        if (!$this->guardApiAdmin()) {
            return;
        }

        [$page, $perPage, $status, $suspend, $query] = $this->resolveUserListQuery();
        $pagination = $this->users->paginate($page, $perPage, $suspend, $query);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->users->statusCounts();

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }

    public function searchApiV1(callable $_redirect): void
    {
        if (!$this->guardApiAdmin()) {
            return;
        }

        $query = trim((string)($_GET['q'] ?? ''));
        $limit = (int)($_GET['limit'] ?? 15);
        $this->apiOk($this->users->search($query, $limit));
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        if (!$this->users->delete($id)) {
            $this->apiError('DELETE_FAILED', I18n::t('users.delete_failed'));
            return;
        }

        $this->apiOk([
            'id' => $id,
            'message' => I18n::t('users.deleted'),
            'redirect' => $this->buildPath('admin/users'),
        ]);
    }

    public function suspendApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $mode = (string)($_POST['mode'] ?? 'suspend');

        if ($mode === 'unsuspend') {
            if (!$this->users->unsuspend($id)) {
                $this->apiError('UNSUSPEND_FAILED', I18n::t('users.unsuspend_failed'));
                return;
            }

            $this->apiOk([
                'id' => $id,
                'suspend' => 0,
                'message' => I18n::t('users.unsuspended'),
            ]);
            return;
        }

        if (!$this->users->suspend($id)) {
            $this->apiError('SUSPEND_FAILED', I18n::t('users.suspend_failed'));
            return;
        }

        $this->apiOk([
            'id' => $id,
            'suspend' => 1,
            'message' => I18n::t('users.suspended'),
        ]);
    }

    public function addApiV1(callable $redirect): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $result = $this->users->save($_POST);

        if (($result['success'] ?? false) === true) {
            $this->apiOk([
                'redirect' => $this->buildPath('admin/users'),
                'message' => I18n::t('users.created'),
            ]);
            return;
        }

        $this->apiError('SAVE_FAILED', I18n::t('users.save_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function editApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $result = $this->users->save($_POST, $id);

        if (($result['success'] ?? false) === true) {
            $this->apiOk([
                'message' => I18n::t('users.updated'),
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
}
