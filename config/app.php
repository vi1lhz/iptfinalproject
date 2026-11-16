<?php

use Core\Env;

return [
    'name'             => Env::get('APP_NAME', 'LootVault'),
    'env'              => Env::get('APP_ENV', 'production'),
    'debug'            => Env::get('APP_DEBUG', false),
    'url'              => Env::get('APP_URL', 'http://localhost/lootvault'),
    'timezone'         => Env::get('APP_TIMEZONE', 'Asia/Manila'),

    // All prices are in Philippine pesos.
    'currency_symbol'  => '₱',

    // Marketplace fee kept by the platform on each sale (10%).
    'platform_fee'     => 0.10,

    // Upload limits
    'max_upload_mb'    => 20,
];
