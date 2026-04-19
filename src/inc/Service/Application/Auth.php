<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Auth\Auth as SessionAuth;
use App\Service\Auth\Login;
use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Support\I18n;
use App\Service\Application\Settings as SettingsService;
use App\Service\Application\User as UserService;

final class Auth
{
    private SessionAuth $auth;
    private Login $login;
    private SettingsService $settings;
    private UserService $users;

    public function __construct(SessionAuth $auth)
    {
        $this->auth = $auth;
        $this->login = new Login(new Query(Connection::get()));
        $this->settings = new SettingsService();
        $this->users = new UserService();
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
        return $this->canAccessAdmin() ? 'admin' : 'account';
    }

    public function isRegistrationAllowed(): bool
    {
        return (int)($this->settings->resolved()['allow_registration'] ?? 0) === 1;
    }

    public function register(array $input): array
    {
        if (!$this->isRegistrationAllowed()) {
            return [
                'success' => false,
                'errors' => [],
                'message' => I18n::t('auth.registration_disabled'),
            ];
        }

        $result = $this->users->save([
            'name' => trim((string)($input['name'] ?? '')),
            'email' => trim((string)($input['email'] ?? '')),
            'password' => (string)($input['password'] ?? ''),
            'role' => 'user',
            'suspend' => 0,
        ]);

        if (($result['success'] ?? false) !== true) {
            return [
                'success' => false,
                'errors' => $result['errors'] ?? [],
                'message' => I18n::t('auth.registration_save_failed'),
            ];
        }

        return [
            'success' => true,
            'errors' => [],
            'message' => I18n::t('auth.registration_success'),
            'redirect' => 'auth/login',
        ];
    }

    public function canAccessAdmin(): bool
    {
        return $this->auth->check() && (string)$this->auth->role() === 'admin';
    }
}
