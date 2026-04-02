<?php
declare(strict_types=1);

namespace App\Auth;

final class LoginLayer
{
    private Login $login;
    private Auth $auth;

    public function __construct(Login $login, Auth $auth)
    {
        $this->login = $login;
        $this->auth = $auth;
    }

    public function attempt(array $data): array
    {
        $result = $this->login->attempt($data);

        if (($result['success'] ?? false) !== true) {
            return [
                'success' => false,
                'errors' => $result['errors'] ?? [],
                'message' => (string)($result['message'] ?? 'Přihlášení selhalo.'),
            ];
        }

        return [
            'success' => true,
            'redirect' => $this->auth->isAdmin() ? 'admin/dashboard' : '',
            'errors' => [],
            'message' => '',
        ];
    }
}
