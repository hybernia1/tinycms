<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Auth\Auth as SessionAuth;
use App\Service\Auth\Login;
use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Support\I18n;

final class Auth
{
    private SessionAuth $auth;
    private Login $login;

    public function __construct(SessionAuth $auth)
    {
        $this->auth = $auth;
        $this->login = new Login(new Query(Connection::get()));
    }

    public function auth(): SessionAuth
    {
        return $this->auth;
    }

    public function login(array $input): array
    {
        $result = $this->login->attempt([
            'email' => trim((string)($input['email'] ?? '')),
            'password' => (string)($input['password'] ?? ''),
            'remember' => (int)((int)($input['remember'] ?? 0) === 1),
        ]);

        if (($result['success'] ?? false) !== true) {
            return [
                'success' => false,
                'errors' => $result['errors'] ?? [],
                'message' => (string)($result['message'] ?? I18n::t('auth.login_failed')),
            ];
        }

        return [
            'success' => true,
            'redirect' => $this->redirectAfterLogin(),
            'errors' => [],
            'message' => '',
        ];
    }

    public function redirectAfterLogin(): string
    {
        return $this->canAccessAdmin() ? 'admin/dashboard' : '';
    }

    public function canAccessAdmin(): bool
    {
        return $this->auth->check();
    }
}
