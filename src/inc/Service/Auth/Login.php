<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Service\Infrastructure\Db\Query;
use App\Service\Support\I18n;

class Login
{
    private Query $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Přihlášení uživatele
     *
     * @param array{
     *     email:string,
     *     password:string
     * } $data
     * @return array{
     *     success:bool,
     *     message:string,
     *     user?:array<string,mixed>,
     *     errors?:array<string,string>
     * }
     */
    public function attempt(array $data): array
    {
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $remember = (int)($data['remember'] ?? 0) === 1;

        $errors = [];

        if ($email === '') {
            $errors['email'] = I18n::t('auth.email_required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = I18n::t('auth.email_invalid_format');
        } else {
            $email = mb_strtolower($email);
        }

        if ($password === '') {
            $errors['password'] = I18n::t('auth.password_required');
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => I18n::t('auth.form_has_errors'),
                'errors' => $errors,
            ];
        }

        $users = $this->query->select('users', ['ID', 'name', 'email', 'password'], [
            'email' => $email,
        ]);

        if (empty($users)) {
            return [
                'success' => false,
                'message' => I18n::t('auth.invalid_credentials'),
                'errors' => [
                    'email' => I18n::t('auth.user_not_found'),
                ],
            ];
        }

        $user = $users[0];


        if (!isset($user['password']) || !password_verify($password, (string)$user['password'])) {
            return [
                'success' => false,
                'message' => I18n::t('auth.invalid_credentials'),
                'errors' => [
                    'password' => I18n::t('auth.wrong_password'),
                ],
            ];
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['auth'] = [
            'id' => (int)$user['ID'],
            'name' => (string)$user['name'],
            'email' => (string)$user['email'],
        ];

        if ($remember) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                time() + (60 * 60 * 24 * 30),
                $params['path'] ?: '/',
                $params['domain'] ?: '',
                (bool)$params['secure'],
                (bool)$params['httponly']
            );
        }

        unset($user['password']);

        return [
            'success' => true,
            'message' => I18n::t('auth.login_success'),
            'user' => [
                'id' => (int)$user['ID'],
                'name' => (string)$user['name'],
                'email' => (string)$user['email'],
            ],
        ];
    }
}
