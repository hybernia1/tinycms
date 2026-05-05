<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;

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

    private function sync(): void
    {
        if ($this->synced || !isset($_SESSION['auth']['id'])) {
            return;
        }

        $userId = (int)$_SESSION['auth']['id'];
        $user = (new Query(Connection::get()))->first('users', ['ID', 'name', 'email', 'role', 'suspend'], ['ID' => $userId]);

        if (!is_array($user) || (int)($user['suspend'] ?? 0) === 1) {
            unset($_SESSION['auth']);
            $this->synced = true;
            return;
        }

        $_SESSION['auth'] = [
            'id' => (int)$user['ID'],
            'name' => (string)$user['name'],
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
        ];

        $this->synced = true;
    }
}
