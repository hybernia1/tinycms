<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Auth\Auth;
use App\Service\Auth\Login;
use App\Service\Auth\Register;
use App\Service\Infra\Db\Connection;
use App\Service\Infra\Db\Query;
use App\Service\Support\I18n;
use App\Service\Support\MailService;

final class AuthService
{
    private Auth $auth;
    private Query $query;
    private Login $login;
    private Register $register;
    private MailService $mail;
    private SettingsService $settings;

    public function __construct(Auth $auth, SettingsService $settings)
    {
        $this->auth = $auth;
        $this->settings = $settings;
        $this->query = new Query(Connection::get());
        $this->login = new Login($this->query);
        $this->register = new Register($this->query);
        $this->mail = new MailService();
    }

    public function auth(): Auth
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
                'message' => (string)($result['message'] ?? I18n::t('auth.login_failed', 'Login failed.')),
            ];
        }

        return [
            'success' => true,
            'redirect' => $this->redirectAfterLogin(),
            'errors' => [],
            'message' => '',
        ];
    }

    public function register(array $input, string $baseUrl): array
    {
        if (!$this->isRegistrationAllowed()) {
            return ['success' => false, 'message' => I18n::t('auth.registration_disabled', 'Registration is disabled.'), 'errors' => []];
        }

        $result = $this->register->create([
            'name' => trim((string)($input['name'] ?? '')),
            'email' => trim((string)($input['email'] ?? '')),
            'password' => (string)($input['password'] ?? ''),
        ]);

        if (($result['success'] ?? false) !== true) {
            return $result;
        }

        $userId = (int)($result['user_id'] ?? 0);
        if ($userId <= 0) {
            return ['success' => false, 'message' => I18n::t('auth.registration_save_failed', 'Registration could not be saved.')];
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 86400);
        $this->query->update('users', ['suspend' => 1, 'token' => $token, 'token_expired' => $expires], ['id' => $userId]);

        $settings = $this->settings->resolved();
        $siteName = (string)($settings['sitename'] ?? 'TinyCMS');
        $link = rtrim($baseUrl, '/') . '/activate?token=' . rawurlencode($token);

        $this->mail->send(
            $settings,
            trim((string)($input['email'] ?? '')),
            I18n::t('email.registration.subject', 'Confirm your registration'),
            str_replace(['{site}', '{link}'], [$siteName, $link], I18n::t('email.registration.body', "Welcome to {site}.\n\nActivate account: {link}"))
        );

        return ['success' => true, 'message' => I18n::t('auth.registration_pending_activation', 'Registration successful. Check your email to activate account.')];
    }

    public function activate(string $token): array
    {
        if ($token === '') {
            return ['success' => false, 'message' => I18n::t('auth.token_expired', 'token je již neplatný')];
        }

        $rows = $this->query->select('users', ['id', 'suspend', 'token_expired'], ['token' => $token]);
        if ($rows === []) {
            return ['success' => false, 'message' => I18n::t('auth.token_expired', 'token je již neplatný')];
        }

        $user = $rows[0];
        $expired = strtotime((string)($user['token_expired'] ?? ''));
        if ($expired === false || $expired < time()) {
            return ['success' => false, 'message' => I18n::t('auth.token_expired', 'token je již neplatný')];
        }

        $this->query->update('users', ['suspend' => 0, 'token' => null, 'token_expired' => null], ['id' => (int)$user['id']]);
        return ['success' => true, 'message' => I18n::t('auth.account_activated', 'Account was activated.')];
    }

    public function lost(array $input, string $baseUrl): array
    {
        $email = mb_strtolower(trim((string)($input['email'] ?? '')));
        $mode = trim((string)($input['mode'] ?? 'password'));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => I18n::t('auth.email_invalid_format', 'Email format is invalid.')];
        }

        $rows = $this->query->select('users', ['id', 'name', 'email', 'suspend'], ['email' => $email]);
        if ($rows === []) {
            return ['success' => true, 'message' => I18n::t('auth.lost_sent', 'If account exists, email was sent.')];
        }

        $user = $rows[0];
        $settings = $this->settings->resolved();
        $siteName = (string)($settings['sitename'] ?? 'TinyCMS');

        if ($mode === 'activation') {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 86400);
            $this->query->update('users', ['token' => $token, 'token_expired' => $expires, 'suspend' => 1], ['id' => (int)$user['id']]);
            $link = rtrim($baseUrl, '/') . '/activate?token=' . rawurlencode($token);
            $this->mail->send(
                $settings,
                (string)$user['email'],
                I18n::t('email.activation.subject', 'Activation token'),
                str_replace(['{site}', '{link}'], [$siteName, $link], I18n::t('email.activation.body', "New activation link for {site}:\n{link}"))
            );
            return ['success' => true, 'message' => I18n::t('auth.lost_sent', 'If account exists, email was sent.')];
        }

        $newPassword = substr(bin2hex(random_bytes(8)), 0, 12);
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hash !== false) {
            $this->query->update('users', ['password' => $hash], ['id' => (int)$user['id']]);
        }

        $this->mail->send(
            $settings,
            (string)$user['email'],
            I18n::t('email.reset.subject', 'New password'),
            str_replace(['{site}', '{password}', '{lost_link}'], [$siteName, $newPassword, rtrim($baseUrl, '/') . '/lost'], I18n::t('email.reset.body', "New password for {site}: {password}\n\nIf you need activation token: {lost_link}"))
        );

        return ['success' => true, 'message' => I18n::t('auth.lost_sent', 'If account exists, email was sent.')];
    }

    public function isRegistrationAllowed(): bool
    {
        return (int)($this->settings->resolved()['allow_registration'] ?? '1') === 1;
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
