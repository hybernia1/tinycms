<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Feature\UserService;
use App\View\PageView;

final class AdminUserController extends BaseAdminController
{
    private const PER_PAGE_ALLOWED = [10, 20, 50];
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
        if (!$this->guardAdmin($redirect)) {
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
        if (
            !$this->guardAdmin($redirect)
            || !$this->guardCsrf($redirect, 'admin/users', 'Bezpečnostní token vypršel, odešlete formulář znovu.')
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID uživatele.');
            $redirect('admin/users');
            return;
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
        if (
            !$this->guardAdmin($redirect)
            || !$this->guardCsrf($redirect, 'admin/users', 'Bezpečnostní token vypršel, odešlete formulář znovu.')
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $mode = (string)($_POST['mode'] ?? 'suspend');

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID uživatele.');
            $redirect('admin/users');
            return;
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
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add');
        $this->pages->adminUsersForm('add', $state['data'] ?? $fallback, $state['errors'] ?? []);
    }

    public function addSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect)
            || !$this->guardCsrf($redirect, 'admin/users', 'Bezpečnostní token vypršel, odešlete formulář znovu.')
        ) {
            return;
        }

        $result = $this->users->save($_POST);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Uživatel vytvořen.');
            $redirect('admin/users');
        }

        $this->flash->add('error', 'Nepodařilo se uložit uživatele.');
        $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/users/add');
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->users->find($id);

        if ($user === null) {
            $this->flash->add('info', 'Uživatel nenalezen.');
            $redirect('admin/users');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $this->pages->adminUsersForm('edit', $state['data'] ?? $user, $state['errors'] ?? []);
    }

    public function editSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect)
            || !$this->guardCsrf($redirect, 'admin/users', 'Bezpečnostní token vypršel, odešlete formulář znovu.')
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID uživatele.');
            $redirect('admin/users');
            return;
        }

        $result = $this->users->save($_POST, $id);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Uživatel upraven.');
            $redirect('admin/users');
        }

        $this->flash->add('error', 'Nepodařilo se upravit uživatele.');
        $data = array_merge($_POST, ['ID' => $id]);
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, $data, $result['errors'] ?? []);
        $redirect('admin/users/edit?id=' . $id);
    }
}
