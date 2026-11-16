<?php

namespace Core;

/**
 * Migrator – runs the .sql files in database/migrations in order and records
 * which ones have run in a `migrations` table, so each runs only once.
 *
 * Migrations use placeholders so the same file works on MySQL and SQLite:
 *   {PK}      auto-increment primary key
 *   {FK}      integer column type matching {PK} (for foreign keys)
 *   {ENGINE}  table options (InnoDB + utf8mb4 on MySQL)
 */
class Migrator
{
    public function __construct(private Database $db, private string $path)
    {
    }

    public static function translate(string $sql, string $driver): string
    {
        $map = $driver === 'sqlite'
            ? ['{PK}' => 'INTEGER PRIMARY KEY AUTOINCREMENT', '{FK}' => 'INTEGER', '{ENGINE}' => '']
            : ['{PK}' => 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY', '{FK}' => 'INT UNSIGNED', '{ENGINE}' => ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'];

        return strtr($sql, $map);
    }

    private function ensureTable(): void
    {
        $this->db->pdo()->exec(self::translate(
            'CREATE TABLE IF NOT EXISTS migrations (
                id {PK},
                migration VARCHAR(191) NOT NULL,
                ran_at DATETIME NOT NULL
            ){ENGINE}',
            $this->db->driver()
        ));
    }

    /** @return string[] names of migrations that were run */
    public function migrate(): array
    {
        $this->ensureTable();
        $done = array_column($this->db->fetchAll('SELECT migration FROM migrations'), 'migration');
        $ran = [];

        foreach ($this->files() as $file) {
            $name = basename($file, '.sql');
            if (in_array($name, $done, true)) {
                continue;
            }

            $sql = self::translate(file_get_contents($file), $this->db->driver());
            foreach ($this->splitStatements($sql) as $statement) {
                $this->db->pdo()->exec($statement);
            }

            $this->db->insert('migrations', ['migration' => $name, 'ran_at' => date('Y-m-d H:i:s')]);
            $ran[] = $name;
        }

        return $ran;
    }

    /** Drops every table (in reverse order) – used by "migrate --fresh". */
    public function dropAll(): void
    {
        $tables = ['reviews', 'order_items', 'orders', 'cart_items', 'items', 'games', 'categories', 'users', 'migrations'];

        if ($this->db->driver() === 'mysql') {
            $this->db->pdo()->exec('SET FOREIGN_KEY_CHECKS = 0');
        }
        foreach ($tables as $table) {
            $this->db->pdo()->exec("DROP TABLE IF EXISTS $table");
        }
        if ($this->db->driver() === 'mysql') {
            $this->db->pdo()->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function files(): array
    {
        $files = glob(rtrim($this->path, '/') . '/*.sql');
        sort($files);
        return $files;
    }

    private function splitStatements(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        return array_filter(array_map('trim', explode(';', $sql)));
    }
}
