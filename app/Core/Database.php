<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    // Single instance — only one connection allowed
    private static ?Database $instance = null;
    private PDO $pdo;

    // ─── Called after login with cashier email ────────────────────────
    // Example: Database::connect('ahmed@gmail.com')
    private function __construct(string $email)
    {
        // ahmed@gmail.com → ahmed
        $name   = explode('@', $email)[0];
        $dir    = Config::get('db_storage');
        $dbPath = $dir . $name . '.sqlite';

        // Create storage/database folder if it does not exist
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, 60); // 60 seconds timeout
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
        } catch (PDOException $e) {
            die(json_encode([
                'status'  => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ]));
        }
    }

    // Prevent clone and unserialize
    private function __clone() {}
    public function __wakeup() {}

    // ─── Connect — called once after login ───────────────────────────
    public static function connect(string $email): void
    {
        self::$instance = new self($email);
    }

    // ─── Get existing instance ────────────────────────────────────────
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            // Not connected yet — use session email
            $email = Auth::email() ?? 'guest';
            self::$instance = new self($email);
        }
        return self::$instance;
    }

    // ─── Get PDO connection directly ─────────────────────────────────
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // ─── SELECT — returns array of rows ──────────────────────────────
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ─── SELECT — returns one row only ───────────────────────────────
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ─── INSERT / UPDATE / DELETE — returns affected rows count ──────
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // ─── Returns last inserted ID ────────────────────────────────────
    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    // ─── Transaction helpers ──────────────────────────────────────────
    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void           { $this->pdo->commit(); }
    public function rollback(): void         { $this->pdo->rollBack(); }
}


// login ناجح
//      ↓
// Database::connect('ahmed@gmail.com')
//      ↓
// ahmed.sqlite اتنشأ
//      ↓
// CreateTables->run()
//      ↓
// pullData() → حفظ في ahmed.sqlite