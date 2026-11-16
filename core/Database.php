<?php

namespace Core;

use PDO;
use PDOStatement;

/**
 * Database – a thin wrapper around PDO.
 * One shared connection (singleton), prepared statements everywhere (prevents SQL injection).
 * Supports MySQL (XAMPP) and SQLite (zero-setup testing).
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private string $driver;

    public function __construct(array $config)
    {
        $this->driver = $config['driver'] ?? 'mysql';

        if ($this->driver === 'sqlite') {
            $dsn = 'sqlite:' . $config['database'];
            $this->pdo = new PDO($dsn);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? '3306',
                $config['database']
            );
            $this->pdo = new PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? '');
        }

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public static function connect(array $config): self
    {
        return self::$instance = new self($config);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Database not connected. Call Database::connect() first.');
        }
        return self::$instance;
    }

    public static function setInstance(?Database $db): void
    {
        self::$instance = $db;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchValue(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /** insert('users', ['name' => 'Ana']) → new id */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->query("INSERT INTO $table ($columns) VALUES ($placeholders)", array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn ($col) => "$col = ?", array_keys($data)));
        return $this->query("UPDATE $table SET $set WHERE $where", [...array_values($data), ...$whereParams])->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        return $this->query("DELETE FROM $table WHERE $where", $params)->rowCount();
    }

    /** Runs $callback inside a transaction; rolls back if it throws. */
    public function transaction(callable $callback): mixed
    {
        // Already inside a transaction? Just join it (the outer one commits or rolls back).
        if ($this->pdo->inTransaction()) {
            return $callback($this);
        }

        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
