<?php

declare(strict_types=1);

/**
 * Copy this file to config.local.php on the PHP host and replace the placeholders.
 * Never commit config.local.php or real credentials.
 */
return [
    'db' => [
        'host' => 'REPLACE_WITH_TINKERHOST_DB_HOST',
        'name' => 'REPLACE_WITH_DATABASE_NAME',
        'user' => 'REPLACE_WITH_DATABASE_USER',
        'password' => 'REPLACE_WITH_DATABASE_PASSWORD',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'site_origin' => 'https://jeankareza-beep.github.io',
        'base_url' => 'https://REPLACE_WITH_TINKERHOST_HOST/',
        'setup_key' => 'REPLACE_WITH_A_RANDOM_ONE_TIME_SETUP_KEY',
        'csrf_secret' => 'REPLACE_WITH_A_RANDOM_SECRET_OF_AT_LEAST_32_CHARACTERS',
        'upload_max_bytes' => 3145728,
        'timezone' => 'Asia/Manila',
    ],
];
