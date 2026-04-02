<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Auth\Auth;
use App\Service\Auth\Login;
use App\Service\Auth\LoginLayer;
use App\Service\Db\Connection;
use App\Service\Db\Query;

final class AuthService
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function auth(): Auth
    {
        return $this->auth;
    }

    public function login(array $input): array
    {
        $layer = new LoginLayer(new Login(new Query(Connection::get())), $this->auth);

        return $layer->attempt([
            'email' => trim((string)($input['email'] ?? '')),
            'password' => (string)($input['password'] ?? ''),
            'remember' => (int)((int)($input['remember'] ?? 0) === 1),
        ]);
    }

    public function redirectAfterLogin(): string
    {
        return $this->auth->isAdmin() ? 'admin/dashboard' : '';
    }

    public function canAccessAdmin(): bool
    {
        return $this->auth->check() && $this->auth->isAdmin();
    }
}
