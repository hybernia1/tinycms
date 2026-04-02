<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\CsrfService;
use App\View\PageView;

final class FrontController
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
            'old' => ['email' => '', 'remember' => 0],
        ]);
    }

    public function loginSubmit(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->pages->loginForm([
                'errors' => [],
                'message' => 'Bezpečnostní token vypršel, odešlete formulář znovu.',
                'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => (int)((int)($_POST['remember'] ?? 0) === 1)],
            ]);
            return;
        }

        $result = $this->authService->login($_POST);

        if (($result['success'] ?? false) === true) {
            $redirect((string)$result['redirect']);
        }

        $this->pages->loginForm([
            'errors' => $result['errors'] ?? [],
            'message' => (string)($result['message'] ?? 'Přihlášení selhalo.'),
            'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => (int)((int)($_POST['remember'] ?? 0) === 1)],
        ]);
    }
}
