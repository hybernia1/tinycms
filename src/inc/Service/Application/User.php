<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\SchemaRules;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\I18n;
use InvalidArgumentException;

final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const ROLES = [self::ROLE_ADMIN, self::ROLE_USER];

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
        $user = $rows[0] ?? null;
        if (!is_array($user)) {
            return null;
        }

        $user['is_last_admin'] = (string)($user['role'] ?? '') === self::ROLE_ADMIN && !$this->hasAnotherActiveAdmin((int)($user['ID'] ?? 0)) ? 1 : 0;
        return $user;
    }

    public function delete(int $id): bool
    {
        $user = $this->find($id);

        if ($user === null || (string)($user['role'] ?? '') === self::ROLE_ADMIN) {
            return false;
        }

        return $this->query->delete('users', ['ID' => $id]) > 0;
    }

    public function suspend(int $id): bool
    {
        $user = $this->find($id);

        if ($user === null || (string)($user['role'] ?? '') === self::ROLE_ADMIN || (int)($user['suspend'] ?? 0) === 1) {
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

        if ($user === null || (string)($user['role'] ?? '') === self::ROLE_ADMIN || (int)($user['suspend'] ?? 0) === 0) {
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
        $role = trim((string)($input['role'] ?? self::ROLE_USER));
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

        if ($id !== null && $role !== self::ROLE_ADMIN && !$this->canDemoteAdmin($id)) {
            $errors['role'] = I18n::t('users.last_admin_protected');
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($role === self::ROLE_ADMIN) {
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
        $usersTable = Table::name('users');

        if ($needle === '') {
            $stmt = Connection::get()->prepare("SELECT ID, name, email FROM $usersTable ORDER BY ID DESC LIMIT :limit");
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = Connection::get()->prepare("SELECT ID, name, email FROM $usersTable WHERE name LIKE :search OR email LIKE :search ORDER BY name ASC, ID ASC LIMIT :limit");
            $stmt->bindValue(':search', '%' . $needle . '%');
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
        }

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
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
        if ($user === null || (string)($user['role'] ?? '') !== self::ROLE_ADMIN) {
            return true;
        }

        return $this->hasAnotherActiveAdmin($id);
    }

    private function hasAnotherActiveAdmin(int $excludedId): bool
    {
        $admins = $this->query->select('users', ['ID'], ['role' => self::ROLE_ADMIN, 'suspend' => 0]);
        foreach ($admins as $admin) {
            if ((int)($admin['ID'] ?? 0) !== $excludedId) {
                return true;
            }
        }

        return false;
    }

}
