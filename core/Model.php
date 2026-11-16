<?php

namespace Core;

/**
 * Model – a tiny "active record" base class. Each subclass sets $table
 * and gets find(), where(), create(), update(), delete() for free.
 * Rows are returned as plain associative arrays to keep things simple.
 */
abstract class Model
{
    protected static string $table;
    protected static array $fillable = [];

    protected static function db(): Database
    {
        return Database::getInstance();
    }

    public static function find(int|string $id): ?array
    {
        return static::db()->fetch('SELECT * FROM ' . static::$table . ' WHERE id = ?', [$id]);
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        self::assertColumn($column);
        return static::db()->fetch('SELECT * FROM ' . static::$table . " WHERE $column = ? LIMIT 1", [$value]);
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        return static::db()->fetchAll('SELECT * FROM ' . static::$table . " ORDER BY $orderBy");
    }

    /** where(['seller_id' => 3, 'status' => 'active']) */
    public static function where(array $conditions, string $orderBy = 'id DESC'): array
    {
        [$sql, $params] = self::buildWhere($conditions);
        return static::db()->fetchAll('SELECT * FROM ' . static::$table . " WHERE $sql ORDER BY $orderBy", $params);
    }

    public static function count(array $conditions = []): int
    {
        if (!$conditions) {
            return (int) static::db()->fetchValue('SELECT COUNT(*) FROM ' . static::$table);
        }
        [$sql, $params] = self::buildWhere($conditions);
        return (int) static::db()->fetchValue('SELECT COUNT(*) FROM ' . static::$table . " WHERE $sql", $params);
    }

    /** Only columns listed in $fillable are saved (mass-assignment protection). */
    public static function create(array $data): int
    {
        $data = static::filterFillable($data);
        $data['created_at'] = date('Y-m-d H:i:s');
        return static::db()->insert(static::$table, $data);
    }

    public static function update(int $id, array $data): int
    {
        $data = static::filterFillable($data);
        return static::db()->update(static::$table, $data, 'id = ?', [$id]);
    }

    public static function delete(int $id): int
    {
        return static::db()->delete(static::$table, 'id = ?', [$id]);
    }

    protected static function filterFillable(array $data): array
    {
        return static::$fillable ? array_intersect_key($data, array_flip(static::$fillable)) : $data;
    }

    private static function buildWhere(array $conditions): array
    {
        $clauses = [];
        foreach (array_keys($conditions) as $column) {
            self::assertColumn($column);
            $clauses[] = "$column = ?";
        }
        return [implode(' AND ', $clauses), array_values($conditions)];
    }

    /** Column names cannot be bound as parameters, so we whitelist their format instead. */
    private static function assertColumn(string $column): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new \InvalidArgumentException("Invalid column name: $column");
        }
    }
}
