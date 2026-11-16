<?php

/**
 * Bootstrap – registers the autoloader and returns a configured Application.
 * Used by public/index.php, the CLI scripts and the test runner.
 *
 * Autoloading (PSR-4 style, no Composer needed):
 *   Core\Router                     → core/Router.php
 *   App\Controllers\ItemController  → app/Controllers/ItemController.php
 */

$root = dirname(__DIR__);

spl_autoload_register(function (string $class) use ($root) {
    $map = ['Core\\' => '/core/', 'App\\' => '/app/'];

    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $root . $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

require $root . '/core/helpers.php';

// Application-specific view helpers (icons, stars, badges ...)
if (is_file($root . '/app/helpers.php')) {
    require $root . '/app/helpers.php';
}

return new Core\Application($root);
