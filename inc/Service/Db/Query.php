<?php
declare(strict_types=1);

namespace App\Service\Db;

use PDO;
use PDOException;

class Query
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ----------------------------
    // SELECT
    // ----------------------------
    public function select(string $table, array $columns = ['*'], array $where = []): array
    {
        $cols = implode(', ', $columns);

        $sql = "SELECT $cols FROM $table";

        $params = [];
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $column => $value) {
                $conditions[] = "$column = :$column";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ----------------------------
    // INSERT
    // ----------------------------
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    // ----------------------------
    // UPDATE
    // ----------------------------
    public function update(string $table, array $data, array $where): int
    {
        $set = [];
        foreach ($data as $col => $val) {
            $set[] = "$col = :$col";
        }

        $conditions = [];
        foreach ($where as $col => $val) {
            $conditions[] = "$col = :where_$col";
            $data["where_$col"] = $val; // prefix pro WHERE parametry
        }

        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $conditions);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return $stmt->rowCount();
    }

    // ----------------------------
    // DELETE
    // ----------------------------
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
}