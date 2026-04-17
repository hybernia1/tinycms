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
        $rows = (new Query(Connection::get()))->select('users', ['ID', 'name', 'email'], ['ID' => $userId]);

        if (empty($rows)) {
            unset($_SESSION['auth']);
            $this->synced = true;
            return;
        }

        $user = $rows[0];
        $_SESSION['auth'] = [
            'id' => (int)$user['ID'],
            'name' => (string)$user['name'],
            'email' => (string)$user['email'],
        ];

        $this->synced = true;
    }
}
