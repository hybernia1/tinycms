<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\CsrfService;
use App\Service\FlashService;

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
                $this->flash->add('info', 'Nemáte přístup do administrace.');
            }

            $redirect('');
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

    private function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
