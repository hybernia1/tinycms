<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\CsrfService;
use App\Service\FlashService;
use App\Service\UserService;
use App\View\PageView;

final class AdminUserController
{
    private const PER_PAGE_ALLOWED = [10, 20, 50];
    private const FORM_STATE_KEY = 'admin_users_form_state';

    private PageView $pages;
    private AuthService $authService;
    private UserService $users;
    private FlashService $flash;
    private CsrfService $csrf;

    public function __construct(PageView $pages, AuthService $authService, UserService $users, FlashService $flash, CsrfService $csrf)
    {
        $this->pages = $pages;
        $this->authService = $authService;
        $this->users = $users;
        $this->flash = $flash;
        $this->csrf = $csrf;
    }

    public function list(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);
        $status = (string)($_GET['status'] ?? 'all');
        $status = in_array($status, ['all', 'active', 'suspended'], true) ? $status : 'all';
        $suspend = $status === 'active' ? 0 : ($status === 'suspended' ? 1 : null);
        $query = trim((string)($_GET['q'] ?? ''));

        if (!in_array($perPage, self::PER_PAGE_ALLOWED, true)) {
            $perPage = 10;
        }

        $pagination = $this->users->paginate($page, $perPage, $suspend, $query);
        $this->pages->adminUsersList($pagination, self::PER_PAGE_ALLOWED, $status, $query);
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
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

    public function suspendToggleSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $mode = (string)($_POST['mode'] ?? 'suspend');

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID uživatele.');
            $redirect('admin/users');
        }

        if ($mode === 'unsuspend') {
            $ok = $this->users->unsuspend($id);
            $this->flash->add($ok ? 'success' : 'error', $ok ? 'Uživatel odsuspendován.' : 'Uživatele se nepodařilo odsuspendovat.');
            $redirect('admin/users');
        }

        $ok = $this->users->suspend($id);
        $this->flash->add($ok ? 'success' : 'error', $ok ? 'Uživatel suspendován.' : 'Uživatele se nepodařilo suspendovat.');
        $redirect('admin/users');
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $fallback = [
            'ID' => null,
            'name' => '',
            'email' => '',
            'role' => 'user',
            'suspend' => 0,
        ];
        $state = $this->consumeFormState('add');
        $this->pages->adminUsersForm('add', $state['data'] ?? $fallback, $state['errors'] ?? []);
    }

    public function addSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $result = $this->users->save($_POST);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Uživatel vytvořen.');
            $redirect('admin/users');
        }

        $this->flash->add('error', 'Nepodařilo se uložit uživatele.');
        $this->storeFormState('add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/users/add');
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

        $state = $this->consumeFormState('edit', $id);
        $this->pages->adminUsersForm('edit', $state['data'] ?? $user, $state['errors'] ?? []);
    }

    public function editSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
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
        $this->storeFormState('edit', $id, $data, $result['errors'] ?? []);
        $redirect('admin/users/edit?id=' . $id);
    }

    private function storeFormState(string $mode, ?int $id, array $data, array $errors): void
    {
        $this->ensureSession();
        $_SESSION[self::FORM_STATE_KEY] = [
            'mode' => $mode,
            'id' => $id,
            'data' => $data,
            'errors' => $errors,
        ];
    }

    private function consumeFormState(string $mode, ?int $id = null): ?array
    {
        $this->ensureSession();
        $state = $_SESSION[self::FORM_STATE_KEY] ?? null;
        unset($_SESSION[self::FORM_STATE_KEY]);

        if (!is_array($state)) {
            return null;
        }

        if (($state['mode'] ?? null) !== $mode || ($state['id'] ?? null) !== $id) {
            return null;
        }

        return $state;
    }

    private function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    private function guardCsrf(callable $redirect): bool
    {
        if ($this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            return true;
        }

        $this->flash->add('error', 'Bezpečnostní token vypršel, odešlete formulář znovu.');
        $redirect('admin/users');
        return false;
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
