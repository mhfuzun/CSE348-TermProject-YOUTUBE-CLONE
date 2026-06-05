<?php

abstract class Repository {
    private DatabaseAdapterInterface $db;

    public function __construct(
        DatabaseAdapterInterface $db
    ) {
        $this->db = $db;
    }

    public function getDb(): DatabaseAdapterInterface {
        return $this->db;
    }
}