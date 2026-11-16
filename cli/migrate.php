<?php

/**
 * Command-line database tool.
 *
 *   php cli/migrate.php                 run new migrations
 *   php cli/migrate.php --fresh         drop all tables, then migrate
 *   php cli/migrate.php --fresh --seed  ... and fill with demo data
 *   php cli/migrate.php --export        write database/lootvault.sql (MySQL dump for phpMyAdmin)
 *
 * On XAMPP (Windows):  C:\xampp\php\php.exe cli\migrate.php --fresh --seed
 */

if (PHP_SAPI !== 'cli') {
    exit('Run this script from the command line.');
}

/** @var Core\Application $app */
$app = require __DIR__ . '/../core/bootstrap.php';
require __DIR__ . '/../database/seeds/DatabaseSeeder.php';

use Core\Application;
use Core\Database;
use Core\Migrator;

$args = array_slice($argv, 1);
$config = Application::config('database');

// Create the MySQL database automatically if it doesn't exist yet.
if ($config['driver'] === 'mysql') {
    try {
        $pdo = new PDO("mysql:host={$config['host']};port={$config['port']}", $config['username'], $config['password']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        fwrite(STDERR, "Could not connect to MySQL: {$e->getMessage()}\nIs MySQL running in the XAMPP Control Panel?\n");
        exit(1);
    }
} elseif (!is_file($config['database'])) {
    touch($config['database']);
}

$app->connectDatabase();
$db = Database::getInstance();
$migrator = new Migrator($db, Application::$root . '/database/migrations');

if (in_array('--export', $args, true)) {
    require __DIR__ . '/export_sql.php';
    exit(0);
}

if (in_array('--fresh', $args, true)) {
    $migrator->dropAll();
    // Remove generated demo files from previous seeds
    foreach (glob(Application::$root . '/storage/files/seed-*.bin') as $file) {
        unlink($file);
    }
    echo "Dropped all tables.\n";
}

$ran = $migrator->migrate();
echo $ran ? 'Migrated: ' . implode(', ', $ran) . "\n" : "Nothing to migrate.\n";

if (in_array('--seed', $args, true)) {
    $result = (new \Database\Seeds\DatabaseSeeder($db))->run();
    echo "Seeded: {$result['users']} users, {$result['categories']} categories, {$result['items']} items, {$result['orders']} orders.\n";
    echo "Games: {$result['games']} (source: {$result['games_source']}" . ($result['games_source'] === 'api' ? ' – live FreeToGame data' : ' – offline sample; will sync from FreeToGame when online') . ")\n";
    echo "Log in with admin@lootvault.test / password\n";
}
