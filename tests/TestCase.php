<?php

namespace Tests;

use Core\Database;
use Core\Migrator;

/**
 * Minimal unit-testing base class (PHPUnit-style API, no Composer needed).
 * Every public method whose name starts with "test" is run by tests/run.php.
 */
abstract class TestCase
{
    public int $assertions = 0;

    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    /** Fresh in-memory SQLite database with all migrations applied. */
    protected function freshDatabase(): Database
    {
        $db = new Database(['driver' => 'sqlite', 'database' => ':memory:']);
        Database::setInstance($db);
        (new Migrator($db, dirname(__DIR__) . '/database/migrations'))->migrate();
        return $db;
    }

    protected function assertTrue(mixed $value, string $message = 'Expected true'): void
    {
        $this->assertions++;
        if ($value !== true) {
            throw new AssertionFailed($message . ' (got ' . var_export($value, true) . ')');
        }
    }

    protected function assertFalse(mixed $value, string $message = 'Expected false'): void
    {
        $this->assertions++;
        if ($value !== false) {
            throw new AssertionFailed($message . ' (got ' . var_export($value, true) . ')');
        }
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new AssertionFailed(trim($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)));
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertions++;
        if ($expected != $actual) {
            throw new AssertionFailed(trim($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)));
        }
    }

    protected function assertNull(mixed $value, string $message = 'Expected null'): void
    {
        $this->assertSame(null, $value, $message);
    }

    protected function assertCount(int $expected, array $array, string $message = ''): void
    {
        $this->assertSame($expected, count($array), $message ?: 'Wrong count.');
    }

    protected function assertContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new AssertionFailed($message ?: "Expected to find \"$needle\".");
        }
    }

    /** Asserts that $fn throws an exception of class $class. */
    protected function assertThrows(string $class, callable $fn, string $message = ''): \Throwable
    {
        $this->assertions++;
        try {
            $fn();
        } catch (\Throwable $e) {
            if ($e instanceof $class) {
                return $e;
            }
            throw new AssertionFailed("Expected $class, got " . get_class($e) . ': ' . $e->getMessage());
        }
        throw new AssertionFailed($message ?: "Expected $class to be thrown.");
    }
}

class AssertionFailed extends \Exception
{
}
