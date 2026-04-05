<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Service\Infra\Db\Query;
use App\Service\Support\I18n;

class Register
{
    private Query $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function create(array $data): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');

        $errors = [];

        if ($name === '') {
            $errors['name'] = I18n::t('auth.name_required', 'Name is required.');
        } elseif (mb_strlen($name) < 3) {
            $errors['name'] = I18n::t('auth.name_min_3', 'Name must be at least 3 characters.');
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = I18n::t('auth.name_too_long', 'Name is too long.');
        }

        if ($email === '') {
            $errors['email'] = I18n::t('auth.email_required', 'Email is required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = I18n::t('auth.email_invalid_format', 'Email format is invalid.');
        } else {
            $email = mb_strtolower($email);
        }

        if ($password === '') {
            $errors['password'] = I18n::t('auth.password_required', 'Password is required.');
        } elseif (mb_strlen($password) < 6) {
            $errors['password'] = I18n::t('auth.password_min_6', 'Password must be at least 6 characters.');
        } elseif (mb_strlen($password) > 255) {
            $errors['password'] = I18n::t('auth.password_too_long', 'Password is too long.');
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => I18n::t('auth.form_has_errors', 'The form contains errors.'),
                'errors'  => $errors,
            ];
        }

        $existingUser = $this->query->select('users', ['ID'], [
            'email' => $email,
        ]);

        if (!empty($existingUser)) {
            return [
                'success' => false,
                'message' => I18n::t('auth.email_already_registered', 'This email is already registered.'),
                'errors'  => [
                    'email' => I18n::t('auth.email_already_used', 'This email is already in use.'),
                ],
            ];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            return [
                'success' => false,
                'message' => I18n::t('auth.password_hash_failed', 'Failed to create password hash.'),
            ];
        }

        $now = date('Y-m-d H:i:s');

        $userId = $this->query->insert('users', [
            'name'     => $name,
            'email'    => $email,
            'password' => $passwordHash,
            'created'  => $now,
            'updated'  => $now,
            'role'     => 'user',
            'suspend'  => 1,
        ]);

        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => I18n::t('auth.registration_save_failed', 'Registration could not be saved.'),
            ];
        }

        return [
            'success' => true,
            'message' => I18n::t('auth.registration_success', 'Registration successful.'),
            'user_id' => $userId,
        ];
    }
}
