<?php

namespace App\Core;

class Model
{
    protected string $table = '';
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Get all rows ─────────────────────────────────────────────────
    public function all(): array
    {
        return $this->db->query("SELECT * FROM {$this->table}");
    }

    // ─── Find by local id ─────────────────────────────────────────────
    public function find(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    // ─── Find by api_id (id from server) ─────────────────────────────
    public function findByApiId(int $apiId): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM {$this->table} WHERE api_id = ?",
            [$apiId]
        );
    }

    // ─── Find by any column ───────────────────────────────────────────
    public function findBy(string $column, mixed $value): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM {$this->table} WHERE {$column} = ?",
            [$value]
        );
    }

    // ─── Get rows by condition ────────────────────────────────────────
    public function where(string $column, mixed $value): array
    {
        return $this->db->query(
            "SELECT * FROM {$this->table} WHERE {$column} = ?",
            [$value]
        );
    }

    // ─── Insert new row — returns new id ─────────────────────────────
    public function insert(array $data): int
    {
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->db->execute(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        return $this->db->lastInsertId();
    }

    // ─── Update row by local id ───────────────────────────────────────
    public function update(int $id, array $data): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));

        return $this->db->execute(
            "UPDATE {$this->table} SET {$set} WHERE id = ?",
            [...array_values($data), $id]
        );
    }

    // ─── Update row by api_id ─────────────────────────────────────────
    public function updateByApiId(int $apiId, array $data): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));

        return $this->db->execute(
            "UPDATE {$this->table} SET {$set} WHERE api_id = ?",
            [...array_values($data), $apiId]
        );
    }

    // ─── Delete row by local id ───────────────────────────────────────
    public function delete(int $id): int
    {
        return $this->db->execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    // ─── Count all rows ───────────────────────────────────────────────
    public function count(): int
    {
        $row = $this->db->queryOne(
            "SELECT COUNT(*) as total FROM {$this->table}"
        );
        return (int) ($row['total'] ?? 0);
    }

    // ─── Custom query — returns array of rows ─────────────────────────
    public function raw(string $sql, array $params = []): array
    {
        return $this->db->query($sql, $params);
    }

    // ─── Custom query — returns one row ──────────────────────────────
    public function rawOne(string $sql, array $params = []): ?array
    {
        return $this->db->queryOne($sql, $params);
    }
}