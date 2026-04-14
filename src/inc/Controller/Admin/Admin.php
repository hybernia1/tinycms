<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
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
            'old' => ['email' => '', 'remember' => 0],
        ]);
    }

    public function logout(callable $redirect): void
    {
        $this->authService->auth()->logout();
        $redirect('admin/login');
    }

}
