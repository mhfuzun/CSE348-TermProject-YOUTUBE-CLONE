<?php

abstract class Service {
    private PdoDatabaseAdapter $db;

    public function __construct(
        PdoDatabaseAdapter $db
    ) {
        $this->db = $db;
    }

    public function getDb(): PdoDatabaseAdapter {
        return $this->db;
    }
}
