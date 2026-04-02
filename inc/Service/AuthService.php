<?php
declare(strict_types=1);

namespace App\Service;

use App\Auth\Auth;
use App\Auth\Login;
use App\Auth\LoginLayer;
use App\Db\Connection;
use App\Db\Query;

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
