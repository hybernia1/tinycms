<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\View\AdminView;

final class AdminController extends BaseAdminController
{
    public function __construct(
        private AdminView $pages,
        AuthService $authService,
        FlashService $flash,
        CsrfService $csrf
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

    public function logout(callable $redirect): void
    {
        $this->authService->auth()->logout();
        $redirect('login');
    }
}
