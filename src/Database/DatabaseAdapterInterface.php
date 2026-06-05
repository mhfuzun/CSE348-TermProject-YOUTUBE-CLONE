<?php

interface DatabaseAdapterInterface
{
    public function connect(array $config): void;

    public function isConnected(): bool;

    public function insert(string $tableName, array $data): int;

    public function select(
        string $tableName,
        array $conditions = [],
        array $columns = ['*'],
        ?int $limit = null
    ): array;

    public function update(
        string $tableName,
        array $conditions,
        array $data,
        ?int $limit = null
    ): int;

    public function close(): void;
}
