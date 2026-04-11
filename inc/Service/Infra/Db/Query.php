<?php
declare(strict_types=1);

namespace App\Service\Infra\Db;

use InvalidArgumentException;
use PDO;
use PDOException;
use App\Service\Support\I18n;
use App\Service\Infra\Db\Table;

class Query
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function select(string $table, array $columns = ['*'], array $where = []): array
    {
        $table = $this->resolveTable($table);
        $cols = $this->buildColumns($columns);
        [$whereSql, $params] = $this->buildWhere($where);
        $sql = "SELECT $cols FROM $table$whereSql";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(string $table, array $columns = ['*'], array $where = []): ?array
    {
        return $this->select($table, $columns, $where)[0] ?? null;
    }

    public function exists(string $table, array $where = []): bool
    {
        return $this->first($table, ['*'], $where) !== null;
    }

    public function count(string $table, array $where = []): int
    {
        $table = $this->resolveTable($table);
        [$whereSql, $params] = $this->buildWhere($where);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table$whereSql");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function countBy(string $table, string $column, array $where = []): array
    {
        $table = $this->resolveTable($table);
        $this->assertIdentifier($column, 'column');
        [$whereSql, $params] = $this->buildWhere($where);
        $stmt = $this->pdo->prepare("SELECT $column AS value, COUNT(*) AS total FROM $table$whereSql GROUP BY $column");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = [];

        foreach ($rows as $row) {
            $counts[(string)($row['value'] ?? '')] = (int)($row['total'] ?? 0);
        }

        return $counts;
    }

    public function paginate(string $table, array $columns = ['*'], array $where = [], array $options = []): array
    {
        $table = $this->resolveTable($table);
        $cols = $this->buildColumns($columns);
        $page = max(1, (int)($options['page'] ?? 1));
        $perPage = max(1, (int)($options['perPage'] ?? 10));
        $orderBy = (string)($options['orderBy'] ?? 'ID');
        $orderByAllowed = $this->filterIdentifiers((array)($options['orderByAllowed'] ?? []));
        if ($orderByAllowed === []) {
            $orderByAllowed = $this->filterIdentifiers($columns);
        }
        $orderDir = strtoupper((string)($options['orderDir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $search = trim((string)($options['search'] ?? ''));
        $searchColumns = (array)($options['searchColumns'] ?? []);

        if ($orderByAllowed !== []) {
            $orderBy = in_array($orderBy, $orderByAllowed, true) ? $orderBy : (string)($orderByAllowed[0] ?? 'ID');
        } elseif (!$this->isIdentifier($orderBy)) {
            $orderBy = 'ID';
        }

        [$whereSql, $params] = $this->buildWhere($where);
        [$searchSql, $searchParams] = $this->buildSearch($search, $searchColumns);
        $params = array_merge($params, $searchParams);
        $conditionsSql = $whereSql;

        if ($searchSql !== '') {
            $conditionsSql .= ($whereSql === '' ? ' WHERE ' : ' AND ') . $searchSql;
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table$conditionsSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT $cols FROM $table$conditionsSql ORDER BY $orderBy $orderDir LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

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
        $table = $this->resolveTable($table);
        if ($data === []) {
            throw new InvalidArgumentException(I18n::t('errors.db.insert_data_empty'));
        }

        $data = $this->normalizeDataKeys($data);
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
        } catch (PDOException $e) {
            throw $this->translatedDbError($e);
        }

        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $table = $this->resolveTable($table);
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

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw $this->translatedDbError($e);
        }
    }

    public function delete(string $table, array $where): int
    {
        $table = $this->resolveTable($table);
        if ($where === []) {
            throw new InvalidArgumentException(I18n::t('errors.db.delete_conditions_empty'));
        }

        [$whereSql, $params] = $this->buildWhere($where);
        $sql = "DELETE FROM $table$whereSql";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public function deleteIn(string $table, string $column, array $values): int
    {
        $table = $this->resolveTable($table);
        $this->assertIdentifier($column, 'column');
        $ids = array_values(array_unique(array_filter(array_map('intval', $values), fn(int $v): bool => $v > 0)));

        if ($ids === []) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM $table WHERE $column IN ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);

        return $stmt->rowCount();
    }

    private function buildWhere(array $where): array
    {
        if ($where === []) {
            return ['', []];
        }

        $conditions = [];
        $params = [];

        foreach ($where as $column => $value) {
            $this->assertIdentifier((string)$column, 'column');
            $conditions[] = "$column = :$column";
            $params[$column] = $value;
        }

        return [' WHERE ' . implode(' AND ', $conditions), $params];
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

    private function resolveTable(string $table): string
    {
        $resolved = Table::name($table);
        $this->assertIdentifier($resolved, 'table');
        return $resolved;
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

    private function translatedDbError(PDOException $e): InvalidArgumentException
    {
        $sqlState = (string)($e->errorInfo[0] ?? "");
        $driverCode = (int)($e->errorInfo[1] ?? 0);

        if ($sqlState === "22001" || $driverCode === 1406) {
            return new InvalidArgumentException(I18n::t('errors.db.value_too_long'), 0, $e);
        }

        if ($driverCode === 1048 || $driverCode === 1364) {
            return new InvalidArgumentException(I18n::t('errors.db.required_value_missing'), 0, $e);
        }

        if ($driverCode === 1062) {
            return new InvalidArgumentException(I18n::t('errors.db.unique_violation'), 0, $e);
        }

        if ($driverCode === 1452) {
            return new InvalidArgumentException(I18n::t('errors.db.invalid_foreign_key'), 0, $e);
        }

        return new InvalidArgumentException(I18n::t('errors.db.operation_failed'), 0, $e);
    }
}
