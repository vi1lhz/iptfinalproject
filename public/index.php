<?php

/**
 * Front controller – the ONLY public PHP file. Every request enters here.
 */

// When using PHP's built-in server (php -S), let it serve real files directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

/** @var Core\Application $app */
$app = require __DIR__ . '/../core/bootstrap.php';

$app->connectDatabase();
$app->loadRoutes(__DIR__ . '/../routes/web.php');
$app->run();
