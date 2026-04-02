<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Service\Db\Query;

class Register
{
    private Query $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Registrace nového uživatele
     *
     * @param array{
     *     name:string,
     *     email:string,
     *     password:string
     * } $data
     * @return array{
     *     success:bool,
     *     message:string,
     *     user_id?:int,
     *     errors?:array<string,string>
     * }
     */
    public function create(array $data): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');

        $errors = [];

        // ----------------------------
        // Validace jména
        // ----------------------------
        if ($name === '') {
            $errors['name'] = 'Jméno je povinné.';
        } elseif (mb_strlen($name) < 3) {
            $errors['name'] = 'Jméno musí mít alespoň 3 znaky.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Jméno je příliš dlouhé.';
        }

        // ----------------------------
        // Validace emailu
        // ----------------------------
        if ($email === '') {
            $errors['email'] = 'E-mail je povinný.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail není ve správném formátu.';
        } else {
            $email = mb_strtolower($email);
        }

        // ----------------------------
        // Validace hesla
        // ----------------------------
        if ($password === '') {
            $errors['password'] = 'Heslo je povinné.';
        } elseif (mb_strlen($password) < 6) {
            $errors['password'] = 'Heslo musí mít alespoň 6 znaků.';
        } elseif (mb_strlen($password) > 255) {
            $errors['password'] = 'Heslo je příliš dlouhé.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Formulář obsahuje chyby.',
                'errors'  => $errors,
            ];
        }

        // ----------------------------
        // Kontrola duplicity emailu
        // ----------------------------
        $existingUser = $this->query->select('users', ['ID'], [
            'email' => $email,
        ]);

        if (!empty($existingUser)) {
            return [
                'success' => false,
                'message' => 'Tento e-mail je již registrován.',
                'errors'  => [
                    'email' => 'Tento e-mail je již používán.',
                ],
            ];
        }

        // ----------------------------
        // Hash hesla
        // ----------------------------
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            return [
                'success' => false,
                'message' => 'Nepodařilo se vytvořit hash hesla.',
            ];
        }

        $now = date('Y-m-d H:i:s');

        // ----------------------------
        // Uložení do DB
        // ----------------------------
        $userId = $this->query->insert('users', [
            'name'     => $name,
            'email'    => $email,
            'password' => $passwordHash,
            'created'  => $now,
            'updated'  => $now,
            'role'     => 'user',
            'suspend'  => 0,
        ]);

        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'Registraci se nepodařilo uložit.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Registrace proběhla úspěšně.',
            'user_id' => $userId,
        ];
    }
}