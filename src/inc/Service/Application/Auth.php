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
    private const RESET_TOKEN_TTL_SECONDS = 86400;

    private SessionAuth $auth;
    private Login $login;
    private SettingsService $settings;
    private UserService $users;
    private Query $query;
    private Email $email;

    public function __construct(SessionAuth $auth)
    {
        $this->auth = $auth;
        $this->query = new Query(Connection::get());
        $this->login = new Login($this->query);
        $this->settings = new SettingsService();
        $this->users = new UserService();
        $this->email = new Email();
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

    public function canUseResetToken(string $token): bool
    {
        return $this->findUserByResetToken($token) !== null;
    }

    public function requestPasswordReset(array $input, string $resetLinkBase): array
    {
        $email = mb_strtolower(trim((string)($input['email'] ?? '')));
        $errors = [];

        if ($email === '') {
            $errors['email'] = I18n::t('auth.email_required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = I18n::t('auth.email_invalid_format');
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
                'message' => I18n::t('auth.form_has_errors'),
            ];
        }

        $users = $this->query->select('users', ['id', 'email', 'name', 'reset_token', 'reset_token_expiry'], ['email' => $email]);
        $user = $users[0] ?? null;
        if (!is_array($user)) {
            return [
                'success' => true,
                'errors' => [],
                'message' => I18n::t('auth.reset_email_sent'),
            ];
        }

        $token = trim((string)($user['reset_token'] ?? ''));
        $expiryRaw = trim((string)($user['reset_token_expiry'] ?? ''));
        if ($this->shouldRegenerateResetToken($token, $expiryRaw)) {
            $token = bin2hex(random_bytes(32));
            $this->query->update('users', [
                'reset_token' => $token,
                'reset_token_expiry' => $this->nextResetTokenExpiry(),
            ], [
                'id' => (int)$user['id'],
            ]);
        }

        $link = $resetLinkBase . '?token=' . urlencode($token);
        $this->email->send(
            (string)$user['email'],
            'emails.password_reset',
            [
                'name' => trim((string)($user['name'] ?? '')) !== '' ? (string)$user['name'] : I18n::t('auth.reset_email_generic_user'),
                'link' => $link,
            ]
        );

        return [
            'success' => true,
            'errors' => [],
            'message' => I18n::t('auth.reset_email_sent'),
        ];
    }

    public function resetPassword(array $input): array
    {
        $token = trim((string)($input['token'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $errors = [];

        if ($token === '') {
            $errors['token'] = I18n::t('auth.reset_token_invalid');
        }
        if ($password === '') {
            $errors['password'] = I18n::t('auth.password_required');
        } elseif (mb_strlen($password) < 6) {
            $errors['password'] = I18n::t('auth.password_min_6');
        } elseif (mb_strlen($password) > 255) {
            $errors['password'] = I18n::t('auth.password_too_long');
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
                'message' => I18n::t('auth.form_has_errors'),
            ];
        }

        $user = $this->findUserByResetToken($token);
        if (!is_array($user)) {
            return [
                'success' => false,
                'errors' => ['token' => I18n::t('auth.reset_token_invalid')],
                'message' => I18n::t('auth.reset_token_invalid'),
            ];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($hash) || $hash === '') {
            return [
                'success' => false,
                'errors' => ['password' => I18n::t('auth.password_hash_failed')],
                'message' => I18n::t('auth.password_hash_failed'),
            ];
        }

        $this->query->update('users', [
            'password' => $hash,
            'reset_token' => null,
            'reset_token_expiry' => null,
        ], [
            'id' => (int)$user['id'],
        ]);

        return [
            'success' => true,
            'errors' => [],
            'message' => I18n::t('auth.password_reset_success'),
            'redirect' => 'auth/login',
        ];
    }

    private function findUserByResetToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $rows = $this->query->select('users', ['id', 'reset_token_expiry'], ['reset_token' => $token]);
        $user = $rows[0] ?? null;
        if (!is_array($user)) {
            return null;
        }

        $expiryTs = strtotime((string)($user['reset_token_expiry'] ?? ''));
        if ($expiryTs === false || $expiryTs < time()) {
            return null;
        }

        return $user;
    }

    private function shouldRegenerateResetToken(string $token, string $expiryRaw): bool
    {
        if (trim($token) === '') {
            return true;
        }

        $expiryTs = $expiryRaw !== '' ? strtotime($expiryRaw) : false;
        return $expiryTs === false || $expiryTs <= time();
    }

    private function nextResetTokenExpiry(): string
    {
        return date('Y-m-d H:i:s', time() + self::RESET_TOKEN_TTL_SECONDS);
    }
}
