<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\Connection;
use App\Service\Infra\Db\Query;
use App\Service\Infra\Db\SchemaConstraintValidator;
use InvalidArgumentException;

final class UserService
{
    private Query $query;
    private SchemaConstraintValidator $columnLimitValidator;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
        $this->columnLimitValidator = new SchemaConstraintValidator();
    }

    public function paginate(int $page = 1, int $perPage = 10, ?int $suspend = null, string $search = ''): array
    {
        $where = $suspend === null ? [] : ['suspend' => $suspend];

        return $this->query->paginate('users', ['ID', 'name', 'email', 'role', 'suspend', 'created'], $where, [
            'page' => $page,
            'perPage' => $perPage,
            'orderBy' => 'ID',
            'orderByAllowed' => ['ID', 'name', 'email', 'role', 'suspend', 'created'],
            'orderDir' => 'DESC',
            'search' => $search,
            'searchColumns' => ['name', 'email'],
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

    public function suspend(int $id): bool
    {
        $user = $this->find($id);

        if ($user === null || (string)($user['role'] ?? '') === 'admin' || (int)($user['suspend'] ?? 0) === 1) {
            return false;
        }

        return $this->query->update('users', [
            'suspend' => 1,
            'updated' => date('Y-m-d H:i:s'),
        ], ['ID' => $id]) > 0;
    }

    public function unsuspend(int $id): bool
    {
        $user = $this->find($id);

        if ($user === null || (string)($user['role'] ?? '') === 'admin' || (int)($user['suspend'] ?? 0) === 0) {
            return false;
        }

        return $this->query->update('users', [
            'suspend' => 0,
            'updated' => date('Y-m-d H:i:s'),
        ], ['ID' => $id]) > 0;
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

        $lengthErrors = $this->columnLimitValidator->validate('users', [
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'password' => $password,
        ], [
            'name' => 'name',
            'email' => 'email',
            'role' => 'role',
            'password' => 'password',
        ]);

        if ($password === '') {
            unset($lengthErrors['password']);
        }

        foreach ($lengthErrors as $field => $message) {
            if (!isset($errors[$field])) {
                $errors[$field] = $message;
            }
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

        try {
            if ($id === null) {
                $payload['created'] = $now;
                $newId = $this->query->insert('users', $payload);
                return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
            }

            $updated = $this->query->update('users', $payload, ['ID' => $id]);

            return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'errors' => ['_global' => $e->getMessage()]];
        }
    }

    public function authorOptions(): array
    {
        $rows = $this->query->select('users', ['ID', 'name', 'email']);
        usort($rows, static fn(array $a, array $b): int => strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? '')));
        return $rows;
    }

}
