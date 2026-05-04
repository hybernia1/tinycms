<?php
declare(strict_types=1);

namespace App\Service\Infrastructure\Db;

use InvalidArgumentException;
use PDO;
use App\Service\Support\I18n;

class Query
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function from(string $table, ?string $alias = null): SelectQuery
    {
        $table = Table::name($table);
        return new SelectQuery($this->pdo, $table, $alias);
    }

    public function raw(string $sql): SqlExpression
    {
        return new SqlExpression($sql);
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

    public function insert(string $table, array $data): int
    {
        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');
        if ($data === []) {
            throw new InvalidArgumentException(I18n::t('errors.db.insert_data_empty'));
        }

        $data = $this->normalizeDataKeys($data);
        $this->executeInsert('INSERT INTO', $table, $data);

        return (int)$this->pdo->lastInsertId();
    }

    public function insertIgnore(string $table, array $data): int
    {
        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');
        if ($data === []) {
            throw new InvalidArgumentException(I18n::t('errors.db.insert_data_empty'));
        }

        $data = $this->normalizeDataKeys($data);
        return $this->executeInsert('INSERT IGNORE INTO', $table, $data);
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

    public function deleteWhereIn(string $table, string $column, array $values, array $where = []): int
    {
        return $this->deleteWhereSet($table, $column, $values, $where, false);
    }

    public function deleteWhereNotIn(string $table, string $column, array $values, array $where = []): int
    {
        return $this->deleteWhereSet($table, $column, $values, $where, true);
    }

    public function transaction(callable $callback): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $callback();
        }

        $this->pdo->beginTransaction();
        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function deleteAll(string $table): int
    {
        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');

        return (int)$this->pdo->exec("DELETE FROM $table");
    }

    private function executeInsert(string $verb, string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "$verb $table ($columns) VALUES ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return $stmt->rowCount();
    }

    private function deleteWhereSet(string $table, string $column, array $values, array $where, bool $not): int
    {
        $rawTable = $table;
        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');
        $this->assertIdentifier($column, 'column');

        $values = array_values($values);
        if ($values === []) {
            return $not ? $this->delete($rawTable, $where) : 0;
        }

        [$conditions, $params] = $this->buildWhereConditions($where);
        $placeholders = [];
        foreach ($values as $index => $value) {
            $key = 'set_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $value;
        }

        $operator = $not ? 'NOT IN' : 'IN';
        $conditions[] = "$column $operator (" . implode(', ', $placeholders) . ')';
        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $conditions);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
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

final class SelectQuery
{
    private array $select = [];
    private array $joins = [];
    private array $where = [];
    private array $groupBy = [];
    private array $orderBy = [];
    private array $params = [];
    private array $intParams = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private bool $distinct = false;
    private int $paramIndex = 0;

    public function __construct(
        private PDO $pdo,
        private string $table,
        private ?string $alias = null
    ) {
        $this->assertIdentifier($table, 'table');
        if ($alias !== null) {
            $this->assertIdentifier($alias, 'alias');
        }
    }

    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function select(array|string|SqlExpression $columns): self
    {
        $this->select = [];
        return $this->addSelect($columns);
    }

    public function addSelect(array|string|SqlExpression $columns): self
    {
        foreach (is_array($columns) ? $columns : [$columns] as $column) {
            $this->select[] = $this->columnSql($column);
        }

        return $this;
    }

    public function selectRaw(string $sql): self
    {
        return $this->addSelect(new SqlExpression($sql));
    }

    public function join(string $table, string $alias, string $left, string $operator, string $right, string $type = 'INNER'): self
    {
        $type = strtoupper(trim($type));
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'], true)) {
            throw new InvalidArgumentException('Invalid join type.');
        }

        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');
        $this->assertIdentifier($alias, 'alias');
        $this->assertColumn($left);
        $this->assertOperator($operator);
        $this->assertColumn($right);

        $this->joins[] = "$type JOIN $table $alias ON $left $operator $right";
        return $this;
    }

    public function innerJoin(string $table, string $alias, string $left, string $operator, string $right): self
    {
        return $this->join($table, $alias, $left, $operator, $right, 'INNER');
    }

    public function leftJoin(string $table, string $alias, string $left, string $operator, string $right): self
    {
        return $this->join($table, $alias, $left, $operator, $right, 'LEFT');
    }

    public function where(string $column, mixed $value): self
    {
        return $this->whereOp($column, '=', $value);
    }

    public function whereOp(string $column, string $operator, mixed $value): self
    {
        $this->assertColumn($column);
        $this->assertOperator($operator);
        $param = $this->nextParam($column);
        $this->where[] = "$column $operator :$param";
        $this->params[$param] = $value;
        $this->markIntParam($param, $value);

        return $this;
    }

    public function whereColumn(string $left, string $operator, string $right): self
    {
        $this->assertColumn($left);
        $this->assertOperator($operator);
        $this->assertColumn($right);
        $this->where[] = "$left $operator $right";

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->assertColumn($column);
        $this->where[] = "$column IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->assertColumn($column);
        $this->where[] = "$column IS NOT NULL";
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->assertColumn($column);
        if ($values === []) {
            $this->where[] = '1 = 0';
            return $this;
        }

        $placeholders = [];
        foreach (array_values($values) as $value) {
            $param = $this->nextParam($column);
            $placeholders[] = ':' . $param;
            $this->params[$param] = $value;
            $this->markIntParam($param, $value);
        }

        $this->where[] = "$column IN (" . implode(', ', $placeholders) . ')';
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        $this->assertColumn($column);
        if ($values === []) {
            return $this;
        }

        $placeholders = [];
        foreach (array_values($values) as $value) {
            $param = $this->nextParam($column);
            $placeholders[] = ':' . $param;
            $this->params[$param] = $value;
            $this->markIntParam($param, $value);
        }

        $this->where[] = "$column NOT IN (" . implode(', ', $placeholders) . ')';
        return $this;
    }

    public function whereExists(string $table, string $alias, string $left, string $operator, string $right): self
    {
        return $this->whereExistsSql($table, $alias, $left, $operator, $right, false);
    }

    public function whereNotExists(string $table, string $alias, string $left, string $operator, string $right): self
    {
        return $this->whereExistsSql($table, $alias, $left, $operator, $right, true);
    }

    public function search(array $columns, string $search): self
    {
        $search = trim($search);
        if ($search === '' || $columns === []) {
            return $this;
        }

        $conditions = [];
        foreach ($columns as $column) {
            $column = (string)$column;
            $this->assertColumn($column);
            $param = $this->nextParam($column);
            $conditions[] = "$column LIKE :$param";
            $this->params[$param] = '%' . $search . '%';
        }

        $this->where[] = '(' . implode(' OR ', $conditions) . ')';
        return $this;
    }

    public function whereRaw(string|SqlExpression $sql, array $params = [], array $intParams = []): self
    {
        $sql = trim((string)$sql);
        if ($sql === '') {
            return $this;
        }

        $this->where[] = $sql;
        $this->addParams($params, $intParams);
        return $this;
    }

    public function groupBy(array|string|SqlExpression $columns): self
    {
        foreach (is_array($columns) ? $columns : [$columns] as $column) {
            $this->groupBy[] = $this->columnSql($column);
        }

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->assertColumn($column);
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    public function orderByRaw(string|SqlExpression $sql): self
    {
        $sql = trim((string)$sql);
        if ($sql !== '') {
            $this->orderBy[] = $sql;
        }

        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = max(1, $limit);
        $this->offset = max(0, $offset);
        return $this;
    }

    public function get(): array
    {
        $stmt = $this->pdo->prepare($this->toSql());
        $this->bindValues($stmt, $this->params, $this->intParams);
        $this->bindLimit($stmt);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function first(): ?array
    {
        $clone = clone $this;
        $clone->limit(1);
        $rows = $clone->get();

        return $rows[0] ?? null;
    }

    public function value(string|SqlExpression $column): mixed
    {
        $clone = clone $this;
        $clone->select($column)->limit(1);
        $stmt = $this->pdo->prepare($clone->toSql());
        $clone->bindValues($stmt, $clone->params, $clone->intParams);
        $clone->bindLimit($stmt);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function count(string|SqlExpression|null $expression = null): int
    {
        $sql = $this->countSql($expression);
        $stmt = $this->pdo->prepare($sql);
        $this->bindValues($stmt, $this->params, $this->intParams);
        $stmt->execute();

        return (int)($stmt->fetchColumn() ?: 0);
    }

    public function exists(): bool
    {
        $clone = clone $this;
        $clone->selectRaw('1')->limit(1);

        return $clone->first() !== null;
    }

    public function paginate(int $page = 1, int $perPage = 10, string|SqlExpression|null $countExpression = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $total = $this->count($countExpression);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $clone = clone $this;
        $clone->limit($perPage, $offset);

        return [
            'data' => $clone->get(),
            'total' => $total,
            'total_pages' => $totalPages,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function toSql(): string
    {
        $select = $this->select === [] ? '*' : implode(', ', $this->select);
        $distinct = $this->distinct ? 'DISTINCT ' : '';
        $parts = ["SELECT $distinct$select", $this->baseSql()];

        if ($this->orderBy !== []) {
            $parts[] = 'ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $parts[] = 'LIMIT :_limit OFFSET :_offset';
        }

        return implode("\n", $parts);
    }

    private function countSql(string|SqlExpression|null $expression = null): string
    {
        if ($expression !== null && $this->groupBy === [] && !$this->distinct) {
            return 'SELECT ' . $this->columnSql($expression) . "\n" . $this->baseSql();
        }

        if ($this->groupBy !== [] || $this->distinct) {
            $select = $this->select === [] ? '1' : implode(', ', $this->select);
            $distinct = $this->distinct ? 'DISTINCT ' : '';
            return "SELECT COUNT(*) FROM (\nSELECT $distinct$select\n" . $this->baseSql() . "\n) q";
        }

        return "SELECT COUNT(*)\n" . $this->baseSql();
    }

    private function baseSql(): string
    {
        $from = $this->alias !== null ? "FROM {$this->table} {$this->alias}" : "FROM {$this->table}";
        $parts = array_merge([$from], $this->joins);

        if ($this->where !== []) {
            $parts[] = 'WHERE ' . implode(' AND ', $this->where);
        }

        if ($this->groupBy !== []) {
            $parts[] = 'GROUP BY ' . implode(', ', $this->groupBy);
        }

        return implode("\n", $parts);
    }

    private function columnSql(string|SqlExpression $column): string
    {
        if ($column instanceof SqlExpression) {
            return trim($column->sql());
        }

        $column = trim($column);
        if ($column === '*') {
            return '*';
        }

        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*\.)?[a-zA-Z_][a-zA-Z0-9_]*(\s+AS\s+[a-zA-Z_][a-zA-Z0-9_]*)?$/i', $column) !== 1) {
            throw new InvalidArgumentException('Invalid column identifier: ' . $column);
        }

        return $column;
    }

    private function assertColumn(string $column): void
    {
        $this->columnSql($column);
    }

    private function assertIdentifier(string $value, string $context): void
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value) === 1) {
            return;
        }

        throw new InvalidArgumentException('Invalid ' . $context . ' identifier: ' . $value);
    }

    private function assertOperator(string $operator): void
    {
        if (in_array(strtoupper(trim($operator)), ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE'], true)) {
            return;
        }

        throw new InvalidArgumentException('Invalid query operator.');
    }

    private function whereExistsSql(string $table, string $alias, string $left, string $operator, string $right, bool $not): self
    {
        $table = Table::name($table);
        $this->assertIdentifier($table, 'table');
        $this->assertIdentifier($alias, 'alias');
        $this->assertColumn($left);
        $this->assertOperator($operator);
        $this->assertColumn($right);

        $prefix = $not ? 'NOT ' : '';
        $this->where[] = "{$prefix}EXISTS (SELECT 1 FROM $table $alias WHERE $left $operator $right)";

        return $this;
    }

    private function nextParam(string $column): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_]+/', '_', $column) ?: 'param';
        return trim($base, '_') . '_' . $this->paramIndex++;
    }

    private function addParams(array $params, array $intParams = []): void
    {
        $intParams = array_map(static fn(mixed $param): string => ltrim((string)$param, ':'), $intParams);
        foreach ($params as $key => $value) {
            $name = ltrim((string)$key, ':');
            $this->params[$name] = $value;
            if (in_array($name, $intParams, true)) {
                $this->intParams[] = $name;
            } else {
                $this->markIntParam($name, $value);
            }
        }

        $this->intParams = array_values(array_unique($this->intParams));
    }

    private function markIntParam(string $name, mixed $value): void
    {
        if (is_int($value)) {
            $this->intParams[] = $name;
            $this->intParams = array_values(array_unique($this->intParams));
        }
    }

    private function bindValues(\PDOStatement $stmt, array $params, array $intParams = []): void
    {
        foreach ($params as $key => $value) {
            $name = ltrim((string)$key, ':');
            $type = in_array($name, $intParams, true) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $name, $value, $type);
        }
    }

    private function bindLimit(\PDOStatement $stmt): void
    {
        if ($this->limit === null) {
            return;
        }

        $stmt->bindValue(':_limit', $this->limit, PDO::PARAM_INT);
        $stmt->bindValue(':_offset', $this->offset ?? 0, PDO::PARAM_INT);
    }
}

final class SqlExpression
{
    public function __construct(private string $sql)
    {
    }

    public function sql(): string
    {
        return $this->sql;
    }

    public function __toString(): string
    {
        return $this->sql;
    }
}
