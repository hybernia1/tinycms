<?php
declare(strict_types=1);

namespace App\Service\Infrastructure\Db;

use InvalidArgumentException;
use PDO;
use App\Service\Support\I18n;
use App\Service\Infrastructure\Db\Table;

class Query
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function select(string $table, array $columns = ['*'], array $where = []): array
    {
        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');
        $cols = $this->buildColumns($columns);
        [$whereSql, $params] = $this->buildWhere($where);
        $sql = "SELECT $cols FROM $table$whereSql";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function paginate(string $table, array $columns = ['*'], array $where = [], array $options = []): array
    {
        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');
        $cols = $this->buildColumns($columns);
        $page = max(1, (int)($options['page'] ?? 1));
        $perPage = max(1, (int)($options['perPage'] ?? 10));
        $orderBy = (string)($options['orderBy'] ?? 'ID');
        $orderByAllowed = $this->filterIdentifiers((array)($options['orderByAllowed'] ?? []));
        $orderDir = strtoupper((string)($options['orderDir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $search = trim((string)($options['search'] ?? ''));
        $searchColumns = (array)($options['searchColumns'] ?? []);

        if ($orderByAllowed !== []) {
            $orderBy = in_array($orderBy, $orderByAllowed, true) ? $orderBy : (string)($orderByAllowed[0] ?? 'ID');
        } elseif (!$this->isIdentifier($orderBy)) {
            $orderBy = 'ID';
        }

        [$conditions, $params] = $this->buildWhereConditions($where);
        [$searchSql, $searchParams] = $this->buildSearch($search, $searchColumns);
        $params = array_merge($params, $searchParams);

        if ($searchSql !== '') {
            $conditions[] = $searchSql;
        }

        return $this->paginateQuery([
            'select' => $cols,
            'from' => "FROM $table",
            'where' => $conditions,
            'params' => $params,
            'orderBy' => "$orderBy $orderDir",
        ], [
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    public function paginateQuery(array $query, array $options = []): array
    {
        $select = trim((string)($query['select'] ?? '*'));
        $from = trim((string)($query['from'] ?? ''));
        $joins = $this->cleanSqlParts((array)($query['joins'] ?? []));
        $countJoins = $this->cleanSqlParts(array_key_exists('countJoins', $query) ? (array)$query['countJoins'] : $joins);
        $where = $this->cleanSqlParts((array)($query['where'] ?? []));
        $params = (array)($query['params'] ?? []);
        $intParams = array_values(array_map('strval', (array)($query['intParams'] ?? [])));
        $orderBy = trim((string)($query['orderBy'] ?? ''));
        $count = trim((string)($query['count'] ?? 'COUNT(*)'));
        $page = max(1, (int)($options['page'] ?? 1));
        $perPage = max(1, (int)($options['perPage'] ?? 10));

        if ($select === '' || $from === '' || $orderBy === '' || $count === '') {
            throw new InvalidArgumentException('Invalid pagination query.');
        }

        $baseSql = implode("\n", array_merge([$from], $joins));
        $countBaseSql = implode("\n", array_merge([$from], $countJoins));
        if ($where !== []) {
            $baseSql .= "\nWHERE " . implode(' AND ', $where);
            $countBaseSql .= "\nWHERE " . implode(' AND ', $where);
        }

        $countStmt = $this->pdo->prepare("SELECT $count\n$countBaseSql");
        $this->bindValues($countStmt, $params, $intParams);
        $countStmt->execute();
        $total = (int)($countStmt->fetchColumn() ?: 0);

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(implode("\n", [
            "SELECT $select",
            $baseSql,
            "ORDER BY $orderBy",
            'LIMIT :limit OFFSET :offset',
        ]));
        $this->bindValues($stmt, $params, $intParams);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'total_pages' => $totalPages,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function insert(string $table, array $data): int
    {
        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');
        if ($data === []) {
            throw new InvalidArgumentException(I18n::t('errors.db.insert_data_empty'));
        }

        $data = $this->normalizeDataKeys($data);
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');
        if ($data === []) {
            throw new InvalidArgumentException(I18n::t('errors.db.update_data_empty'));
        }

        if ($where === []) {
            throw new InvalidArgumentException(I18n::t('errors.db.update_conditions_empty'));
        }

        $data = $this->normalizeDataKeys($data);
        $where = $this->normalizeDataKeys($where);
        $set = [];
        foreach ($data as $col => $val) {
            $set[] = "$col = :$col";
        }

        $conditions = [];
        foreach ($where as $col => $val) {
            $conditions[] = "$col = :where_$col";
            $data["where_$col"] = $val;
        }

        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $conditions);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');
        if ($where === []) {
            throw new InvalidArgumentException(I18n::t('errors.db.delete_conditions_empty'));
        }

        [$whereSql, $params] = $this->buildWhere($where);
        $sql = "DELETE FROM $table$whereSql";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public function value(string $table, string $column, array $where): mixed
    {
        $this->assertIdentifier($column, 'column');
        $rows = $this->select($table, [$column], $where);
        return $rows[0][$column] ?? null;
    }

    public function deleteByStatus(string $table, array $where, string $trashStatus, string $statusColumn = 'status'): ?string
    {
        $status = $this->value($table, $statusColumn, $where);
        if ($status === null) {
            return null;
        }

        if ((string)$status === $trashStatus) {
            return $this->delete($table, $where) > 0 ? 'hard_deleted' : null;
        }

        return $this->update($table, [$statusColumn => $trashStatus], $where) > 0 ? 'soft_deleted' : null;
    }

    public function restoreStatus(string $table, array $where, string $fromStatus, string $toStatus, string $statusColumn = 'status'): bool
    {
        return $this->update($table, [$statusColumn => $toStatus], array_merge($where, [$statusColumn => $fromStatus])) > 0;
    }

    public function setStatus(string $table, array $where, string $status, string $statusColumn = 'status'): bool
    {
        return $this->update($table, [$statusColumn => $status], $where) > 0;
    }

    private function buildWhere(array $where): array
    {
        [$conditions, $params] = $this->buildWhereConditions($where);

        return $conditions === [] ? ['', []] : [' WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function buildWhereConditions(array $where): array
    {
        if ($where === []) {
            return [[], []];
        }

        $conditions = [];
        $params = [];

        foreach ($where as $column => $value) {
            $this->assertIdentifier((string)$column, 'column');
            $conditions[] = "$column = :$column";
            $params[$column] = $value;
        }

        return [$conditions, $params];
    }

    private function buildSearch(string $search, array $columns): array
    {
        if ($search === '' || $columns === []) {
            return ['', []];
        }

        $conditions = [];
        $params = [];

        foreach ($columns as $index => $column) {
            $this->assertIdentifier((string)$column, 'column');
            $key = 'search_' . $index;
            $conditions[] = "$column LIKE :$key";
            $params[$key] = '%' . $search . '%';
        }

        return ['(' . implode(' OR ', $conditions) . ')', $params];
    }

    private function buildColumns(array $columns): string
    {
        if ($columns === ['*']) {
            return '*';
        }

        $filtered = [];
        foreach ($columns as $column) {
            $value = trim((string)$column);
            if ($value !== '') {
                $filtered[] = $value;
            }
        }
        $filtered = array_values(array_unique($filtered));

        if ($filtered === []) {
            throw new InvalidArgumentException(I18n::t('errors.db.columns_empty'));
        }

        return implode(', ', $filtered);
    }

    private function cleanSqlParts(array $parts): array
    {
        return array_values(array_filter(array_map(
            static fn(mixed $part): string => trim((string)$part),
            $parts
        ), static fn(string $part): bool => $part !== ''));
    }

    private function bindValues(\PDOStatement $stmt, array $params, array $intParams = []): void
    {
        foreach ($params as $key => $value) {
            $name = ltrim((string)$key, ':');
            $type = in_array($name, $intParams, true) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $name, $value, $type);
        }
    }

    private function filterIdentifiers(array $identifiers): array
    {
        $filtered = [];
        foreach ($identifiers as $identifier) {
            $identifier = (string)$identifier;
            if (!$this->isIdentifier($identifier)) {
                continue;
            }

            $filtered[] = $identifier;
        }

        return array_values(array_unique($filtered));
    }

    private function normalizeDataKeys(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $key = (string)$key;
            $this->assertIdentifier($key, 'column');
            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private function assertIdentifier(string $value, string $context): void
    {
        if ($this->isIdentifier($value)) {
            return;
        }

        throw new InvalidArgumentException('Invalid ' . $context . ' identifier: ' . $value);
    }

    private function isIdentifier(string $value): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value) === 1;
    }

}
