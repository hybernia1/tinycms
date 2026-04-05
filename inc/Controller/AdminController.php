<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\View\PageView;

final class AdminController
{
    private PageView $pages;
    private AuthService $authService;
    private CsrfService $csrf;

    public function __construct(PageView $pages, AuthService $authService, CsrfService $csrf)
    {
        $this->pages = $pages;
        $this->authService = $authService;
        $this->csrf = $csrf;
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
                'data' => [
                    'csrf' => $this->csrf->token(),
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
                'data' => [
                    'csrf' => $this->csrf->token(),
                ],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'ok' => true,
            'data' => ['status' => 'ok'],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function authLoginApiV1(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            http_response_code(422);
            echo json_encode([
                'ok' => false,
                'error' => [
                    'code' => 'CSRF_INVALID',
                    'message' => 'Bezpečnostní token vypršel, odešlete formulář znovu.',
                ],
                'data' => [
                    'csrf' => $this->csrf->token(),
                ],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = $this->authService->login($_POST);
        if (($result['success'] ?? false) !== true) {
            http_response_code(422);
            echo json_encode([
                'ok' => false,
                'error' => [
                    'code' => 'LOGIN_FAILED',
                    'message' => (string)($result['message'] ?? 'Přihlášení selhalo.'),
                ],
                'data' => [
                    'csrf' => $this->csrf->token(),
                ],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!$this->authService->canAccessAdmin()) {
            $this->authService->auth()->logout();
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Nemáte dostatečná oprávnění do administrace.',
                ],
                'data' => [
                    'csrf' => $this->csrf->token(),
                ],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'ok' => true,
            'data' => [
                'status' => 'ok',
                'csrf' => $this->csrf->token(),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }
}
