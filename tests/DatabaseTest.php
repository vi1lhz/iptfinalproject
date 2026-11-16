<?php

namespace Tests;

use App\Models\Category;
use App\Models\User;
use Core\Database;
use Core\Migrator;

class DatabaseTest extends TestCase
{
    private Database $db;

    public function setUp(): void
    {
        $this->db = $this->freshDatabase();
    }

    public function testMigrationsCreateAllTables(): void
    {
        $tables = array_column($this->db->fetchAll("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"), 'name');
        foreach (['users', 'categories', 'games', 'items', 'cart_items', 'orders', 'order_items', 'reviews', 'migrations'] as $table) {
            $this->assertTrue(in_array($table, $tables, true), "Missing table $table");
        }
    }

    public function testMigrationsRunOnlyOnce(): void
    {
        $again = (new Migrator($this->db, dirname(__DIR__) . '/database/migrations'))->migrate();
        $this->assertSame([], $again);
    }

    public function testPlaceholdersTranslatePerDriver(): void
    {
        $this->assertSame('id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY', Migrator::translate('id {PK}', 'mysql'));
        $this->assertSame('id INTEGER PRIMARY KEY AUTOINCREMENT', Migrator::translate('id {PK}', 'sqlite'));
        $this->assertContains('ENGINE=InnoDB', Migrator::translate('){ENGINE}', 'mysql'));
    }

    public function testModelCrud(): void
    {
        $id = Category::create(['name' => 'Test', 'slug' => 'test', 'icon' => 'box']);
        $this->assertSame('Test', Category::find($id)['name']);

        Category::update($id, ['name' => 'Renamed']);
        $this->assertSame('Renamed', Category::findBy('slug', 'test')['name']);
        $this->assertSame(1, Category::count());

        Category::delete($id);
        $this->assertNull(Category::find($id));
    }

    public function testFillableBlocksMassAssignment(): void
    {
        // "id" and unknown keys are ignored, so a form can't overwrite them.
        $id = Category::create(['name' => 'A', 'slug' => 'a', 'icon' => 'box', 'id' => 999, 'hacker' => 'x']);
        $this->assertTrue($id !== 999);
    }

    public function testPreparedStatementsStopSqlInjection(): void
    {
        User::register(['username' => 'ana', 'email' => 'ana@mail.test', 'password' => 'secret123']);
        $evil = "' OR '1'='1";
        $this->assertNull(User::findBy('email', $evil));
        $this->assertSame(1, User::count());
    }

    public function testRejectsInvalidColumnNames(): void
    {
        $this->assertThrows(\InvalidArgumentException::class, fn () => User::findBy('email; DROP TABLE users', 'x'));
    }

    public function testTransactionRollsBackOnError(): void
    {
        try {
            $this->db->transaction(function (Database $db) {
                $db->insert('categories', ['name' => 'X', 'slug' => 'x', 'icon' => 'box', 'created_at' => date('Y-m-d H:i:s')]);
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
        }
        $this->assertSame(0, Category::count());
    }

    public function testPasswordsAreHashed(): void
    {
        $id = User::register(['username' => 'ben', 'email' => 'BEN@mail.test', 'password' => 'secret123']);
        $user = User::find($id);
        $this->assertTrue($user['password'] !== 'secret123');
        $this->assertTrue(password_verify('secret123', $user['password']));
        $this->assertSame('ben@mail.test', $user['email'], 'Email should be lower-cased.');
    }
}
