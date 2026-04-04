<?php
declare(strict_types=1);

namespace App\Service\Infra\Db;

use InvalidArgumentException;
use PDO;
use PDOException;

class Query
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function select(string $table, array $columns = ['*'], array $where = []): array
    {
        $this->assertIdentifier($table, 'table');
        [$whereSql, $params] = $this->buildWhere($where);
        $cols = implode(', ', $columns);
        $sql = "SELECT $cols FROM $table$whereSql";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function paginate(string $table, array $columns = ['*'], array $where = [], array $options = []): array
    {
        $this->assertIdentifier($table, 'table');
        $page = max(1, (int)($options['page'] ?? 1));
        $perPage = max(1, (int)($options['perPage'] ?? 10));
        $orderBy = (string)($options['orderBy'] ?? 'ID');
        $orderByAllowed = (array)($options['orderByAllowed'] ?? []);
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

        $cols = implode(', ', $columns);
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
        $this->assertIdentifier($table, 'table');
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
        } catch (PDOException $e) {
            $this->throwTranslatedDbError($e);
            throw $e;
        }

        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $this->assertIdentifier($table, 'table');
        $set = [];
        foreach ($data as $col => $val) {
            $this->assertIdentifier((string)$col, 'column');
            $set[] = "$col = :$col";
        }

        $conditions = [];
        foreach ($where as $col => $val) {
            $this->assertIdentifier((string)$col, 'column');
            $conditions[] = "$col = :where_$col";
            $data["where_$col"] = $val;
        }

        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $conditions);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->throwTranslatedDbError($e);
            throw $e;
        }
    }

    public function delete(string $table, array $where): int
    {
        $this->assertIdentifier($table, 'table');
        [$whereSql, $params] = $this->buildWhere($where);
        $sql = "DELETE FROM $table$whereSql";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public function deleteIn(string $table, string $column, array $values): int
    {
        $this->assertIdentifier($table, 'table');
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

    private function throwTranslatedDbError(PDOException $e): void
    {
        $sqlState = (string)($e->errorInfo[0] ?? "");
        $driverCode = (int)($e->errorInfo[1] ?? 0);

        if ($sqlState === "22001" || $driverCode === 1406) {
            throw new InvalidArgumentException("Jedna nebo více hodnot je příliš dlouhá pro databázový sloupec.", 0, $e);
        }

        if ($driverCode === 1048 || $driverCode === 1364) {
            throw new InvalidArgumentException("Chybí povinná hodnota (NOT NULL).", 0, $e);
        }

        if ($driverCode === 1062) {
            throw new InvalidArgumentException("Hodnota už existuje a musí být unikátní.", 0, $e);
        }

        if ($driverCode === 1452) {
            throw new InvalidArgumentException("Neplatná vazba na související záznam (foreign key).", 0, $e);
        }
    }
}
