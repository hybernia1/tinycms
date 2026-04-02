<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\UserService;
use App\View\PageView;

final class AdminUserController
{
    private PageView $pages;
    private AuthService $authService;
    private UserService $users;

    public function __construct(PageView $pages, AuthService $authService, UserService $users)
    {
        $this->pages = $pages;
        $this->authService = $authService;
        $this->users = $users;
    }

    public function list(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $status = (string)($_GET['status'] ?? '');
        $this->pages->adminUsersList($this->users->all(), $status);
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $this->pages->adminUsersForm('add', [
            'ID' => null,
            'name' => '',
            'email' => '',
            'role' => 'user',
            'suspend' => 0,
        ], [], '');
    }

    public function addSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $result = $this->users->save($_POST);

        if (($result['success'] ?? false) === true) {
            $redirect('admin/users?status=created');
        }

        $this->pages->adminUsersForm('add', $_POST, $result['errors'] ?? [], 'Nepodařilo se uložit uživatele.');
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->users->find($id);

        if ($user === null) {
            $redirect('admin/users');
        }

        $this->pages->adminUsersForm('edit', $user, [], '');
    }

    public function editSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $redirect('admin/users');
        }

        $result = $this->users->save($_POST, $id);

        if (($result['success'] ?? false) === true) {
            $redirect('admin/users?status=updated');
        }

        $data = array_merge($_POST, ['ID' => $id]);
        $this->pages->adminUsersForm('edit', $data, $result['errors'] ?? [], 'Nepodařilo se upravit uživatele.');
    }

    private function guard(callable $redirect): bool
    {
        if (!$this->authService->auth()->check()) {
            $redirect('login');
            return false;
        }

        if (!$this->authService->canAccessAdmin()) {
            $redirect('');
            return false;
        }

        return true;
    }
}
