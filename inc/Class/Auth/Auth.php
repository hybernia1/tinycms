<?php
declare(strict_types=1);

namespace App\Auth;

class Auth
{
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function check(): bool
    {
        return isset($_SESSION['auth']['id']);
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function id(): ?int
    {
        return $this->check() ? (int)$_SESSION['auth']['id'] : null;
    }

    public function user(): ?array
    {
        return $this->check() ? $_SESSION['auth'] : null;
    }

    public function role(): ?string
    {
        return $this->check() ? (string)$_SESSION['auth']['role'] : null;
    }

    public function hasRole(string $role): bool
    {
        return $this->check() && (string)$_SESSION['auth']['role'] === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool)$params['secure'],
                (bool)$params['httponly']
            );
        }

        session_destroy();
    }

    public function requireLogin(): void
    {
        if (!$this->check()) {
            http_response_code(403);
            exit('Přístup odepřen. Nejste přihlášena.');
        }
    }

    public function requireRole(string $role): void
    {
        $this->requireLogin();

        if (!$this->hasRole($role)) {
            http_response_code(403);
            exit('Přístup odepřen. Nemáte dostatečná oprávnění.');
        }
    }

    public function requireAdmin(): void
    {
        $this->requireRole('admin');
    }
}