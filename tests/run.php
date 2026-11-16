<?php

/**
 * Test runner:  php tests/run.php            (all tests)
 *               php tests/run.php Router     (only classes containing "Router")
 */

$app = require __DIR__ . '/../core/bootstrap.php';
require __DIR__ . '/TestCase.php';

$filter = $argv[1] ?? '';
$files = glob(__DIR__ . '/*Test.php');
sort($files);

$passed = $failed = $assertions = 0;
$failures = [];
$start = microtime(true);

foreach ($files as $file) {
    require_once $file;
    $class = 'Tests\\' . basename($file, '.php');
    if ($filter && !str_contains($class, $filter)) {
        continue;
    }

    echo "\n" . basename($file, '.php') . "\n";
    foreach (get_class_methods($class) as $method) {
        if (!str_starts_with($method, 'test')) {
            continue;
        }
        $test = new $class();
        $label = trim(strtolower(preg_replace('/([A-Z])/', ' $1', substr($method, 4))));
        try {
            $test->setUp();
            $test->$method();
            $test->tearDown();
            $passed++;
            echo "  \033[32m✔\033[0m $label\n";
        } catch (\Throwable $e) {
            $failed++;
            $failures[] = "$class::$method – " . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
            echo "  \033[31m✘ $label\033[0m\n";
        }
        $assertions += $test->assertions;
    }
}

printf("\n%d tests, %d assertions, %d failed (%.2f s)\n", $passed + $failed, $assertions, $failed, microtime(true) - $start);
foreach ($failures as $f) {
    echo "  FAIL: $f\n";
}
exit($failed ? 1 : 0);
