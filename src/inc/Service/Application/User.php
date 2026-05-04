<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\SchemaRules;
use App\Service\Support\I18n;
use InvalidArgumentException;

final class User
{
    private Query $query;
    private SchemaRules $schemaRules;
    private Email $email;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
        $this->schemaRules = new SchemaRules();
        $this->email = new Email();
    }

    public function paginate(int $page = 1, int $perPage = 10, ?int $suspend = null, string $search = ''): array
    {
        $builder = $this->query
            ->from('users')
            ->select(['ID', 'name', 'email', 'role', 'suspend', 'created'])
            ->search(['name', 'email'], $search)
            ->orderBy('ID', 'DESC');

        if ($suspend !== null) {
            $builder->where('suspend', $suspend);
        }

        return $builder->paginate($page, $perPage);
    }

    public function find(int $id): ?array
    {
        $rows = $this->query->select('users', ['ID', 'name', 'email', 'role', 'suspend'], ['ID' => $id]);
        $user = $rows[0] ?? null;
        if (!is_array($user)) {
            return null;
        }

        $user['is_last_admin'] = (string)($user['role'] ?? '') === 'admin' && !$this->hasAnotherActiveAdmin((int)($user['ID'] ?? 0)) ? 1 : 0;
        return $user;
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

        $updated = $this->query->update('users', [
            'suspend' => 1,
        ], ['ID' => $id]) > 0;
        if ($updated) {
            $this->email->send((string)($user['email'] ?? ''), 'emails.user_suspended', [
                'name' => trim((string)($user['name'] ?? '')) !== '' ? (string)$user['name'] : I18n::t('auth.reset_email_generic_user'),
            ]);
        }

        return $updated;
    }

    public function unsuspend(int $id): bool
    {
        $user = $this->find($id);

        if ($user === null || (string)($user['role'] ?? '') === 'admin' || (int)($user['suspend'] ?? 0) === 0) {
            return false;
        }

        $updated = $this->query->update('users', [
            'suspend' => 0,
        ], ['ID' => $id]) > 0;
        if ($updated) {
            $this->email->send((string)($user['email'] ?? ''), 'emails.user_unsuspended', [
                'name' => trim((string)($user['name'] ?? '')) !== '' ? (string)$user['name'] : I18n::t('auth.reset_email_generic_user'),
            ]);
        }

        return $updated;
    }

    public function save(array $input, ?int $id = null): array
    {
        $name = $this->schemaRules->truncate(
            'users',
            'name',
            trim((string)($input['name'] ?? '')),
            255
        );
        $email = mb_strtolower(trim((string)($input['email'] ?? '')));
        $role = trim((string)($input['role'] ?? 'user'));
        $suspend = (int)($input['suspend'] ?? 0) === 1 ? 1 : 0;
        $password = (string)($input['password'] ?? '');
        $errors = [];

        if ($name === '') {
            $errors['name'] = I18n::t('validation.name_required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = I18n::t('validation.email_invalid');
        }

        if ($id === null && $password === '') {
            $errors['password'] = I18n::t('validation.password_required_new_user');
        }

        $lengthErrors = $this->schemaRules->validate('users', [
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
            $errors['email'] = I18n::t('validation.email_already_used');
        }

        if ($id !== null && $role !== 'admin' && !$this->canDemoteAdmin($id)) {
            $errors['role'] = I18n::t('users.last_admin_protected');
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($role === 'admin') {
            $suspend = 0;
        }

        $payload = [
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'suspend' => $suspend,
        ];

        if ($password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        try {
            if ($id === null) {
                $newId = $this->query->insert('users', $payload);
                if ($newId > 0) {
                    $this->email->send($email, 'emails.welcome_user', [
                        'name' => $name !== '' ? $name : I18n::t('auth.reset_email_generic_user'),
                    ]);
                }
                return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
            }

            $updated = $this->query->update('users', $payload, ['ID' => $id]);

            return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'errors' => ['_global' => $e->getMessage()]];
        }
    }

    public function authorLabel(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        $user = $this->find($id);
        if (!is_array($user)) {
            return '';
        }

        $name = trim((string)($user['name'] ?? ''));
        $email = trim((string)($user['email'] ?? ''));
        if ($name !== '' && $email !== '') {
            return $name . ' (' . $email . ')';
        }
        if ($name !== '') {
            return $name;
        }
        if ($email !== '') {
            return $email;
        }

        return '#' . $id;
    }

    public function search(string $query, int $limit = 15): array
    {
        $needle = trim($query);
        $limit = max(1, min(50, $limit));

        $builder = $this->query
            ->from('users')
            ->select(['ID', 'name', 'email'])
            ->limit($limit);

        if ($needle === '') {
            $builder->orderBy('ID', 'DESC');
        } else {
            $builder
                ->search(['name', 'email'], $needle)
                ->orderBy('name', 'ASC')
                ->orderBy('ID', 'ASC');
        }

        $rows = $builder->get();
        return array_map(static fn(array $row): array => [
            'id' => (int)($row['ID'] ?? 0),
            'name' => trim((string)($row['name'] ?? '')),
            'email' => trim((string)($row['email'] ?? '')),
        ], $rows);
    }

    public function statusCounts(): array
    {
        $rows = $this->query->select('users', ['suspend']);
        $counts = [
            'all' => count($rows),
            'active' => 0,
            'suspended' => 0,
        ];

        foreach ($rows as $row) {
            if ((int)($row['suspend'] ?? 0) === 1) {
                $counts['suspended']++;
                continue;
            }

            $counts['active']++;
        }

        return $counts;
    }

    private function canDemoteAdmin(int $id): bool
    {
        $user = $this->find($id);
        if ($user === null || (string)($user['role'] ?? '') !== 'admin') {
            return true;
        }

        return $this->hasAnotherActiveAdmin($id);
    }

    private function hasAnotherActiveAdmin(int $excludedId): bool
    {
        $admins = $this->query->select('users', ['ID'], ['role' => 'admin', 'suspend' => 0]);
        foreach ($admins as $admin) {
            if ((int)($admin['ID'] ?? 0) !== $excludedId) {
                return true;
            }
        }

        return false;
    }

}
