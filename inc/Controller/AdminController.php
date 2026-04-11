<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\View\AdminView;

final class AdminController
{
    private AdminView $pages;
    private AuthService $authService;

    public function __construct(AdminView $pages, AuthService $authService)
    {
        $this->pages = $pages;
        $this->authService = $authService;
    }

    public function index(callable $redirect): void
    {
        if (!$this->authService->auth()->check()) {
            $redirect('login');
            return;
        }

        $redirect($this->authService->canAccessAdmin() ? 'admin/dashboard' : '');
    }

    public function dashboard(callable $redirect): void
    {
        if (!$this->authService->auth()->check()) {
            $redirect('login');
            return;
        }

        if (!$this->authService->canAccessAdmin()) {
            $redirect('');
            return;
        }

        $this->pages->adminDashboard($this->authService->auth()->user());
    }

    public function logout(callable $redirect): void
    {
        $this->authService->auth()->logout();
        $redirect('login');
    }
}
