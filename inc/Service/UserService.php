<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Db\Connection;
use App\Service\Db\Query;

final class UserService
{
    private Query $query;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
    }

    public function paginate(int $page = 1, int $perPage = 10): array
    {
        return $this->query->paginate('users', ['ID', 'name', 'email', 'role', 'suspend', 'created'], [], [
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => 'ID',
            'orderDir' => 'DESC',
        ]);
    }

    public function find(int $id): ?array
    {
        $rows = $this->query->select('users', ['ID', 'name', 'email', 'role', 'suspend'], ['ID' => $id]);
        return $rows[0] ?? null;
    }

    public function delete(int $id): bool
    {
        $user = $this->find($id);

        if ($user === null || (string)($user['role'] ?? '') === 'admin') {
            return false;
        }

        return $this->query->delete('users', ['ID' => $id]) > 0;
    }

    public function deleteMany(array $ids): int
    {
        $clean = $this->sanitizeIds($ids);

        if ($clean === []) {
            return 0;
        }

        $deleted = 0;

        foreach ($clean as $id) {
            if ($this->delete($id)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public function suspendMany(array $ids): int
    {
        $clean = $this->sanitizeIds($ids);

        if ($clean === []) {
            return 0;
        }

        $updated = 0;

        foreach ($clean as $id) {
            $user = $this->find($id);

            if ($user === null || (string)($user['role'] ?? '') === 'admin') {
                continue;
            }

            $updated += $this->query->update('users', [
                'suspend' => 1,
                'updated' => date('Y-m-d H:i:s'),
            ], ['ID' => $id]) > 0 ? 1 : 0;
        }

        return $updated;
    }

    public function save(array $input, ?int $id = null): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $email = mb_strtolower(trim((string)($input['email'] ?? '')));
        $role = trim((string)($input['role'] ?? 'user'));
        $suspend = (int)($input['suspend'] ?? 0) === 1 ? 1 : 0;
        $password = (string)($input['password'] ?? '');
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Jméno je povinné.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail není validní.';
        }

        if (!in_array($role, ['admin', 'user'], true)) {
            $errors['role'] = 'Role musí být admin nebo user.';
        }

        if ($id === null && $password === '') {
            $errors['password'] = 'Heslo je povinné pro nového uživatele.';
        }

        $existing = $this->query->select('users', ['ID'], ['email' => $email]);

        if (!empty($existing) && (int)$existing[0]['ID'] !== ($id ?? 0)) {
            $errors['email'] = 'E-mail je už použit.';
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($role === 'admin') {
            $suspend = 0;
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'suspend' => $suspend,
            'updated' => $now,
        ];

        if ($password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($id === null) {
            $payload['created'] = $now;
            $newId = $this->query->insert('users', $payload);
            return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
        }

        $updated = $this->query->update('users', $payload, ['ID' => $id]);

        return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
    }

    private function sanitizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), fn(int $v): bool => $v > 0)));
    }
}
