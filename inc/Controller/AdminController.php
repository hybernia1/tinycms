<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\View\PageView;

final class AdminController
{
    private PageView $pages;
    private AuthService $authService;

    public function __construct(PageView $pages, AuthService $authService)
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

    public function authCheckApiV1(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->authService->auth()->check()) {
            http_response_code(401);
            echo json_encode([
                'ok' => false,
                'error' => [
                    'code' => 'AUTH_REQUIRED',
                    'message' => 'Byli jste odhlášeni. Přihlaste se znovu.',
                ],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!$this->authService->canAccessAdmin()) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Nemáte dostatečná oprávnění.',
                ],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'ok' => true,
            'data' => ['status' => 'ok'],
        ], JSON_UNESCAPED_UNICODE);
    }
}
