<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\View\PageView;

final class FrontController
{
    private PageView $pages;
    private AuthService $authService;

    public function __construct(PageView $pages, AuthService $authService)
    {
        $this->pages = $pages;
        $this->authService = $authService;
    }

    public function home(): void
    {
        $this->pages->home($this->authService->auth()->user());
    }

    public function loginForm(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        $this->pages->loginForm([
            'errors' => [],
            'message' => '',
            'old' => ['email' => ''],
        ]);
    }

    public function loginSubmit(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        $result = $this->authService->login($_POST);

        if (($result['success'] ?? false) === true) {
            $redirect((string)$result['redirect']);
        }

        $this->pages->loginForm([
            'errors' => $result['errors'] ?? [],
            'message' => (string)($result['message'] ?? 'Přihlášení selhalo.'),
            'old' => ['email' => trim((string)($_POST['email'] ?? ''))],
        ]);
    }
}
