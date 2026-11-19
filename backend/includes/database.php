<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    global $config;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbConfig = $config['db'] ?? [];
    foreach (['host', 'name', 'user', 'password'] as $key) {
        if (!array_key_exists($key, $dbConfig) || str_starts_with((string) $dbConfig[$key], 'REPLACE_WITH_')) {
            throw new RuntimeException('Database configuration is incomplete.');
        }
    }

    $charset = (string) ($dbConfig['charset'] ?? 'utf8mb4');
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['name'],
        $charset
    );

    $pdo = new PDO($dsn, (string) $dbConfig['user'], (string) $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ]);

    return $pdo;
}
