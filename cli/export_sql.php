<?php

/**
 * Writes database/lootvault.sql – the full MySQL schema plus the current data –
 * so the database can be imported with phpMyAdmin (Import tab) without using the command line.
 * Included by: php cli/migrate.php --export
 *
 * @var Core\Database $db
 * @var Core\Migrator $migrator
 */

use Core\Application;
use Core\Migrator;

$out = "-- LootVault database dump (MySQL / MariaDB)\n";
$out .= '-- Generated ' . date('Y-m-d H:i:s') . " by cli/migrate.php --export\n";
$out .= "-- Import in phpMyAdmin: Import tab → choose this file → Go\n\n";
$out .= "CREATE DATABASE IF NOT EXISTS `lootvault` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE `lootvault`;\n\n";
$out .= "SET FOREIGN_KEY_CHECKS = 0;\n";

$tables = ['reviews', 'order_items', 'orders', 'cart_items', 'items', 'games', 'categories', 'users', 'migrations'];
foreach ($tables as $t) {
    $out .= "DROP TABLE IF EXISTS `$t`;\n";
}
$out .= "\n" . Migrator::translate(
    "CREATE TABLE migrations (\n    id {PK},\n    migration VARCHAR(191) NOT NULL,\n    ran_at DATETIME NOT NULL\n){ENGINE};\n",
    'mysql'
) . "\n";

foreach ($migrator->files() as $file) {
    $out .= '-- ' . basename($file) . "\n" . trim(Migrator::translate(file_get_contents($file), 'mysql')) . "\n\n";
}

$pdo = $db->pdo();
foreach (array_reverse($tables) as $table) {
    $rows = $db->fetchAll("SELECT * FROM $table");
    if (!$rows) {
        continue;
    }
    $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
    $values = array_map(function ($row) use ($pdo) {
        return '(' . implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : str_replace('\\', '\\\\', $pdo->quote((string) $v)), $row)) . ')';
    }, $rows);
    $out .= "INSERT INTO `$table` ($columns) VALUES\n" . implode(",\n", $values) . ";\n\n";
}

$out .= "SET FOREIGN_KEY_CHECKS = 1;\n";

// SQLite's quote() doubles single quotes, which MySQL also accepts. Newlines stay literal inside quotes (valid in MySQL).
file_put_contents(Application::$root . '/database/lootvault.sql', $out);
echo 'Exported database/lootvault.sql (' . number_format(strlen($out)) . " bytes)\n";
