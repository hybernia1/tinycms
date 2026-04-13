<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\View\AdminView;

final class Admin extends BaseAdmin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function index(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect)) {
            return;
        }

        $redirect($this->authService->canAccessAdmin() ? 'admin/dashboard' : '');
    }

    public function dashboard(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect)) {
            return;
        }

        $this->pages->adminDashboard($this->authService->auth()->user());
    }

    public function loginForm(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        $this->pages->adminLoginForm([
            'errors' => [],
            'message' => '',
            'old' => ['email' => '', 'remember' => 0],
        ]);
    }

    public function loginSubmit(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->pages->adminLoginForm([
                'errors' => [],
                'message' => I18n::t('common.csrf_expired'),
                'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => (int)((int)($_POST['remember'] ?? 0) === 1)],
            ]);
            return;
        }

        $result = $this->authService->login($_POST);

        if (($result['success'] ?? false) === true) {
            $redirect((string)$result['redirect']);
        }

        $this->pages->adminLoginForm([
            'errors' => $result['errors'] ?? [],
            'message' => (string)($result['message'] ?? I18n::t('auth.login_failed')),
            'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => (int)((int)($_POST['remember'] ?? 0) === 1)],
        ]);
    }

    public function logout(callable $redirect): void
    {
        $this->authService->auth()->logout();
        $redirect('admin/login');
    }
}
