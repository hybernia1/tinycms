<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\Service\Application\User as UserService;
use App\View\AdminView;

final class User extends Admin
{
    private AdminView $pages;
    private UserService $users;

    public function __construct(AdminView $pages, Auth $authService, UserService $users, Flash $flash, Csrf $csrf)
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

    private function resolveListQuery(): array
    {
        [$page, $perPage, $query] = $this->resolvePaginationQuery();
        $status = $this->resolveStatusFilter(['all', 'active', 'suspended']);
        $suspend = $status === 'active' ? 0 : ($status === 'suspended' ? 1 : null);

        return [$page, $perPage, $status, $suspend, $query];
    }

}
