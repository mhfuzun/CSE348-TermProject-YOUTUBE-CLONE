<?php

class PdoDatabaseAdapter implements DatabaseAdapterInterface
{
    private ?PDO $pdo = null;

    public function __construct(?array $config = null)
    {
        if ($config !== null) {
            $this->connect($config);
        }
    }

    public function connect(array $config): void
    {
        $host = $config['host'] ?? 'localhost';
        $database = $config['database'] ?? throw new InvalidArgumentException('Database name is required.');
        $username = $config['username'] ?? throw new InvalidArgumentException('Username is required.');
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";

        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    public function getPDO(): PDO
    {
        if ($this->pdo === null) {
            return null;
        }

        return $this->pdo;
    }

    public function insert(string $tableName, array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Insert data cannot be empty.');
        }

        $table = $this->quoteIdentifier($tableName);

        $columns = [];
        $placeholders = [];
        $params = [];

        $i = 0;

        foreach ($data as $column => $value) {
            $columns[] = $this->quoteIdentifier($column);

            $placeholder = ':insert_' . $i;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $value;

            $i++;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $statement = $this->pdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->execute();

        return (int) $this->pdo()->lastInsertId();
    }

    public function select(
        string $tableName,
        array $conditions = [],
        array $columns = ['*'],
        ?int $limit = null
    ): array {
        $table = $this->quoteIdentifier($tableName);
        $columnSql = $this->buildColumnList($columns);

        [$whereSql, $params] = $this->buildWhereClause($conditions);

        $sql = "SELECT {$columnSql} FROM {$table}{$whereSql}";

        if ($limit !== null) {
            if ($limit <= 0) {
                throw new InvalidArgumentException('Limit must be greater than zero.');
            }

            $sql .= ' LIMIT ' . $limit;
        }

        $statement = $this->pdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function update(
        string $tableName,
        array $conditions,
        array $data,
        ?int $limit = null
    ): int {
        if (empty($data)) {
            throw new InvalidArgumentException('Update data cannot be empty.');
        }

        if (empty($conditions)) {
            throw new InvalidArgumentException('Update conditions cannot be empty.');
        }

        $table = $this->quoteIdentifier($tableName);

        $setParts = [];
        $params = [];

        $i = 0;

        foreach ($data as $column => $value) {
            $placeholder = ':set_' . $i;

            $setParts[] = $this->quoteIdentifier($column) . " = {$placeholder}";
            $params[$placeholder] = $value;

            $i++;
        }

        [$whereSql, $whereParams] = $this->buildWhereClause($conditions, 'where');

        $params = array_merge($params, $whereParams);

        $sql = sprintf(
            'UPDATE %s SET %s%s',
            $table,
            implode(', ', $setParts),
            $whereSql
        );

        if ($limit !== null) {
            if ($limit <= 0) {
                throw new InvalidArgumentException('Limit must be greater than zero.');
            }

            $sql .= ' LIMIT ' . $limit;
        }

        $statement = $this->pdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->execute();

        return $statement->rowCount();
    }

    public function delete(
        string $tableName,
        array $conditions,
        ?int $limit = null
    ): int {
        if (empty($conditions)) {
            throw new InvalidArgumentException('Delete conditions cannot be empty.');
        }

        $table = $this->quoteIdentifier($tableName);

        [$whereSql, $params] = $this->buildWhereClause($conditions, 'where');

        $sql = sprintf(
            'DELETE FROM %s%s',
            $table,
            $whereSql
        );

        if ($limit !== null) {
            if ($limit <= 0) {
                throw new InvalidArgumentException('');
            }

            $sql .= ' LIMIT ' . $limit;
        }

        $statement = $this->pdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->execute();

        return $statement->rowCount();
    }

    public function count(
        string $tableName,
        array $conditions
    ) : int {
        if (empty($conditions)) {
            throw new InvalidArgumentException('Update conditions cannot be empty.');
        }

        $params = [];

        $table = $this->quoteIdentifier($tableName);
        [$whereSql, $whereParams] = $this->buildWhereClause($conditions, 'where');
        $params = array_merge($params, $whereParams);

        $sql = sprintf(
            'SELECT COUNT(*) AS RowCount FROM %s%s',
            $table,
            $whereSql
        );

        $statement = $this->pdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $statement->execute();

        $row = $statement->fetchAll();
        return (int) $row[0]['RowCount'];
    }

    public function close(): void
    {
        $this->pdo = null;
    }

    private function pdo(): PDO
    {
        if ($this->pdo === null) {
            throw new RuntimeException('Database is not connected.');
        }

        return $this->pdo;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException("Invalid SQL identifier: {$identifier}");
        }

        return "`{$identifier}`";
    }

    private function buildColumnList(array $columns): string
    {
        if (empty($columns) || $columns === ['*']) {
            return '*';
        }

        $safeColumns = [];

        foreach ($columns as $column) {
            $safeColumns[] = $this->quoteIdentifier($column);
        }

        return implode(', ', $safeColumns);
    }

    private function buildWhereClause(array $conditions, string $prefix = 'cond'): array
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $whereParts = [];
        $params = [];

        $i = 0;

        foreach ($conditions as $column => $value) {
            $placeholder = ':' . $prefix . '_' . $i;

            $whereParts[] = $this->quoteIdentifier($column) . " = {$placeholder}";
            $params[$placeholder] = $value;

            $i++;
        }

        return [
            ' WHERE ' . implode(' AND ', $whereParts),
            $params
        ];
    }

    private function buildOrder(string $orderRule, string $columnName): string
    {
        if ($orderRule !== 'ASC' || $orderRule !== 'DESC') {
            throw new InvalidArgumentException('Invalid order rule: {$orderRule}');
        }

        return 'ORDER BY(' . $columnName . ') ' . $orderRule;
    }

    private function bindValues(PDOStatement $statement, array $params): void
    {
        foreach ($params as $placeholder => $value) {
            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif ($value === null) {
                $type = PDO::PARAM_NULL;
            } else {
                $type = PDO::PARAM_STR;
            }

            $statement->bindValue($placeholder, $value, $type);
        }
    }
}
