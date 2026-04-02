<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\FlashService;
use App\Service\UserService;
use App\View\PageView;

final class AdminUserController
{
    private const PER_PAGE_ALLOWED = [10, 20, 50];

    private PageView $pages;
    private AuthService $authService;
    private UserService $users;
    private FlashService $flash;

    public function __construct(PageView $pages, AuthService $authService, UserService $users, FlashService $flash)
    {
        $this->pages = $pages;
        $this->authService = $authService;
        $this->users = $users;
        $this->flash = $flash;
    }

    public function list(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);

        if (!in_array($perPage, self::PER_PAGE_ALLOWED, true)) {
            $perPage = 10;
        }

        $pagination = $this->users->paginate($page, $perPage);
        $this->pages->adminUsersList($pagination, self::PER_PAGE_ALLOWED);
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID uživatele.');
            $redirect('admin/users');
        }

        if ($this->users->delete($id)) {
            $this->flash->add('success', 'Uživatel smazán.');
        } else {
            $this->flash->add('error', 'Uživatele se nepodařilo smazat.');
        }

        $redirect('admin/users');
    }

    public function bulkActionSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $rawIds = (string)($_POST['ids'] ?? '');
        $action = (string)($_POST['action'] ?? '');
        $ids = array_filter(array_map('intval', explode(',', $rawIds)), fn(int $v): bool => $v > 0);

        if ($ids === []) {
            $this->flash->add('info', 'Nebyly vybrány žádné záznamy.');
            $redirect('admin/users');
        }

        if ($action === 'delete') {
            $affected = $this->users->deleteMany($ids);
            $this->flash->add($affected > 0 ? 'success' : 'error', $affected > 0 ? "Smazáno $affected uživatelů." : 'Vybrané uživatele se nepodařilo smazat.');
            $redirect('admin/users');
        }

        if ($action === 'suspend') {
            $affected = $this->users->suspendMany($ids);
            $this->flash->add($affected > 0 ? 'success' : 'error', $affected > 0 ? "Suspendováno $affected uživatelů." : 'Vybrané uživatele se nepodařilo suspendovat.');
            $redirect('admin/users');
        }

        $this->flash->add('info', 'Nebyla vybrána žádná hromadná akce.');
        $redirect('admin/users');
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
            $this->flash->add('success', 'Uživatel vytvořen.');
            $redirect('admin/users');
        }

        $this->flash->add('error', 'Nepodařilo se uložit uživatele.');
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
            $this->flash->add('info', 'Uživatel nenalezen.');
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
            $this->flash->add('error', 'Neplatné ID uživatele.');
            $redirect('admin/users');
        }

        $result = $this->users->save($_POST, $id);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Uživatel upraven.');
            $redirect('admin/users');
        }

        $this->flash->add('error', 'Nepodařilo se upravit uživatele.');
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
            $this->flash->add('info', 'Nemáte přístup do administrace.');
            $redirect('');
            return false;
        }

        return true;
    }
}
