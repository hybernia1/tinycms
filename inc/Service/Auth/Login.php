<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Service\Db\Query;

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

        $errors = [];

        if ($email === '') {
            $errors['email'] = 'E-mail je povinný.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail není ve správném formátu.';
        } else {
            $email = mb_strtolower($email);
        }

        if ($password === '') {
            $errors['password'] = 'Heslo je povinné.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Formulář obsahuje chyby.',
                'errors' => $errors,
            ];
        }

        $users = $this->query->select('users', ['ID', 'name', 'email', 'password', 'role', 'suspend'], [
            'email' => $email,
        ]);

        if (empty($users)) {
            return [
                'success' => false,
                'message' => 'Neplatné přihlašovací údaje.',
                'errors' => [
                    'email' => 'Uživatel nebyl nalezen.',
                ],
            ];
        }

        $user = $users[0];

        if ((int)($user['suspend'] ?? 0) === 1) {
            return [
                'success' => false,
                'message' => 'Tento účet je blokovaný.',
            ];
        }

        if (!isset($user['password']) || !password_verify($password, (string)$user['password'])) {
            return [
                'success' => false,
                'message' => 'Neplatné přihlašovací údaje.',
                'errors' => [
                    'password' => 'Nesprávné heslo.',
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
            'role' => (string)$user['role'],
        ];

        unset($user['password']);

        return [
            'success' => true,
            'message' => 'Přihlášení proběhlo úspěšně.',
            'user' => [
                'id' => (int)$user['ID'],
                'name' => (string)$user['name'],
                'email' => (string)$user['email'],
                'role' => (string)$user['role'],
            ],
        ];
    }
}