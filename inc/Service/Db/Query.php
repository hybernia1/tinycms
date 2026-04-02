<?php
declare(strict_types=1);

namespace App\Service\Db;

use PDO;

class Query
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function select(string $table, array $columns = ['*'], array $where = []): array
    {
        [$whereSql, $params] = $this->buildWhere($where);
        $cols = implode(', ', $columns);
        $sql = "SELECT $cols FROM $table$whereSql";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function paginate(string $table, array $columns = ['*'], array $where = [], array $options = []): array
    {
        $page = max(1, (int)($options['page'] ?? 1));
        $perPage = max(1, (int)($options['perPage'] ?? 10));
        $orderBy = (string)($options['orderBy'] ?? 'ID');
        $orderDir = strtoupper((string)($options['orderDir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        [$whereSql, $params] = $this->buildWhere($where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table$whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $cols = implode(', ', $columns);
        $sql = "SELECT $cols FROM $table$whereSql ORDER BY $orderBy $orderDir LIMIT :limit OFFSET :offset";

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
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
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
        $conditions = [];
        $params = [];
        foreach ($where as $col => $val) {
            $conditions[] = "$col = :$col";
            $params[$col] = $val;
        }

        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $conditions);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

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
            $conditions[] = "$column = :$column";
            $params[$column] = $value;
        }

        return [' WHERE ' . implode(' AND ', $conditions), $params];
    }
}
