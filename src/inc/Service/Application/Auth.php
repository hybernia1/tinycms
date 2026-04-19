<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Auth\Auth as SessionAuth;
use App\Service\Auth\Login;
use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\Mailer;
use App\Service\Support\I18n;
use App\Service\Application\Settings as SettingsService;
use App\Service\Application\User as UserService;
use PDO;
use Throwable;

final class Auth
{
    private SessionAuth $auth;
    private Login $login;
    private SettingsService $settings;
    private UserService $users;
    private Query $query;
    private Mailer $mailer;
    private static bool $resetColumnsChecked = false;

    public function __construct(SessionAuth $auth)
    {
        $this->auth = $auth;
        $this->query = new Query(Connection::get());
        $this->login = new Login($this->query);
        $this->settings = new SettingsService();
        $this->users = new UserService();
        $this->mailer = new Mailer();
        $this->ensureResetColumns();
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

    public function requestPasswordReset(array $input, string $resetLinkBase, string $locale): array
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
        $expiryTs = $expiryRaw !== '' ? strtotime($expiryRaw) : false;
        $now = time();

        if ($token === '' || $expiryTs === false || $expiryTs <= $now) {
            $token = bin2hex(random_bytes(32));
            $this->query->update('users', [
                'reset_token' => $token,
                'reset_token_expiry' => date('Y-m-d H:i:s', $now + 86400),
            ], [
                'id' => (int)$user['id'],
            ]);
        }

        $link = $resetLinkBase . '?token=' . urlencode($token);
        $this->mailer->send(
            (string)$user['email'],
            I18n::t('auth.reset_email_subject'),
            $this->resetEmailBody((string)($user['name'] ?? ''), $link, $locale),
            'TinyCMS@domena.tld'
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

    private function resetEmailBody(string $name, string $link, string $locale): string
    {
        $greeting = trim($name) !== '' ? trim($name) : I18n::t('auth.reset_email_generic_user');
        $isCs = strtolower($locale) === 'cs';
        if ($isCs) {
            return "Dobrý den {$greeting},\n\nobdrželi jsme žádost o reset hesla v TinyCMS.\nPro změnu hesla otevřete tento odkaz:\n{$link}\n\nPlatnost odkazu je 24 hodin.\nPokud jste o reset nežádali, tento e-mail ignorujte.\n\nTinyCMS";
        }

        return "Hello {$greeting},\n\nwe received a TinyCMS password reset request.\nUse this link to set a new password:\n{$link}\n\nThis link is valid for 24 hours.\nIf you did not request this reset, you can ignore this email.\n\nTinyCMS";
    }

    private function ensureResetColumns(): void
    {
        if (self::$resetColumnsChecked) {
            return;
        }
        self::$resetColumnsChecked = true;

        try {
            $pdo = Connection::get();
            $table = Table::name('users');
            $dbName = (string)DB_NAME;
            $check = $pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table AND column_name IN (\'reset_token\', \'reset_token_expiry\')');
            $check->execute([
                'schema' => $dbName,
                'table' => $table,
            ]);
            $existing = $check->fetchAll(PDO::FETCH_COLUMN);
            $columns = is_array($existing) ? $existing : [];
            $missing = [];
            if (!in_array('reset_token', $columns, true)) {
                $missing[] = 'ADD COLUMN reset_token VARCHAR(100) DEFAULT NULL';
            }
            if (!in_array('reset_token_expiry', $columns, true)) {
                $missing[] = 'ADD COLUMN reset_token_expiry DATETIME DEFAULT NULL';
            }
            if ($missing !== []) {
                $pdo->exec('ALTER TABLE ' . $table . ' ' . implode(', ', $missing));
            }
        } catch (Throwable) {
        }
    }
}
