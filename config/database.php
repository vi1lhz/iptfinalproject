<?php

use Core\Application;
use Core\Env;

$driver = Env::get('DB_DRIVER', 'mysql');

return [
    'driver'   => $driver,
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => Env::get('DB_PORT', '3306'),
    'database' => $driver === 'sqlite'
        ? Application::$root . '/' . Env::get('DB_DATABASE', 'storage/database.sqlite')
        : Env::get('DB_DATABASE', 'lootvault'),
    'username' => Env::get('DB_USERNAME', 'root'),
    'password' => Env::get('DB_PASSWORD', ''),
];
