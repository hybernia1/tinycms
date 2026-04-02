<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Service\Db\Connection;
use App\Service\Db\Query;

class Auth
{
    private bool $synced = false;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function check(): bool
    {
        $this->sync();
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
        $this->synced = false;
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

    private function sync(): void
    {
        if ($this->synced || !isset($_SESSION['auth']['id'])) {
            return;
        }

        $userId = (int)$_SESSION['auth']['id'];
        $rows = (new Query(Connection::get()))->select('users', ['ID', 'name', 'email', 'role', 'suspend'], ['ID' => $userId]);

        if (empty($rows) || (int)($rows[0]['suspend'] ?? 0) === 1) {
            unset($_SESSION['auth']);
            $this->synced = true;
            return;
        }

        $user = $rows[0];
        $_SESSION['auth'] = [
            'id' => (int)$user['ID'],
            'name' => (string)$user['name'],
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
        ];

        $this->synced = true;
    }
}
