<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;

abstract class BaseAdminController
{
    public function __construct(
        protected AuthService $authService,
        protected FlashService $flash,
        protected CsrfService $csrf
    ) {
    }

    protected function guardAdmin(callable $redirect, bool $flashDenied = true): bool
    {
        if (!$this->authService->auth()->check()) {
            $redirect('login');
            return false;
        }

        if (!$this->authService->canAccessAdmin()) {
            if ($flashDenied) {
                $this->flash->add('info', I18n::t('admin.access_denied'));
            }

            $redirect('');
            return false;
        }

        return true;
    }

    protected function guardSuperAdmin(callable $redirect, bool $flashDenied = true): bool
    {
        if (!$this->authService->auth()->check()) {
            $redirect('login');
            return false;
        }

        if (!$this->authService->auth()->isAdmin()) {
            if ($flashDenied) {
                $this->flash->add('info', I18n::t('admin.access_denied'));
            }

            $redirect('admin/dashboard');
            return false;
        }

        return true;
    }

    protected function guardCsrf(callable $redirect, string $redirectPath, string $message): bool
    {
        if ($this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            return true;
        }

        $this->flash->add('error', $message);
        $redirect($redirectPath);
        return false;
    }

    protected function storeFormState(string $sessionKey, string $mode, ?int $id, array $data, array $errors): void
    {
        $this->ensureSession();
        $_SESSION[$sessionKey] = [
            'mode' => $mode,
            'id' => $id,
            'data' => $data,
            'errors' => $errors,
        ];
    }

    protected function consumeFormState(string $sessionKey, string $mode, ?int $id = null): ?array
    {
        $this->ensureSession();
        $state = $_SESSION[$sessionKey] ?? null;
        unset($_SESSION[$sessionKey]);

        if (!is_array($state)) {
            return null;
        }

        if (($state['mode'] ?? null) !== $mode || ($state['id'] ?? null) !== $id) {
            return null;
        }

        return $state;
    }

    protected function wantsJson(): bool
    {
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        return str_contains($accept, 'application/json');
    }

    protected function jsonError(string $message, int $statusCode = 422): void
    {
        $this->respondJson([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }

    protected function jsonSuccess(array $payload = [], int $statusCode = 200): void
    {
        $this->respondJson(array_merge(['success' => true], $payload), $statusCode);
    }

    protected function respondJson(array $payload, int $statusCode = 200): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    protected function currentUserId(): int
    {
        return (int)($this->authService->auth()->id() ?? 0);
    }

    protected function isEditor(): bool
    {
        return (string)($this->authService->auth()->role() ?? '') === 'editor';
    }

    private function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
