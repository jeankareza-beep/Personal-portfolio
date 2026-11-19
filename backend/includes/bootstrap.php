<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);
}

define('APP_ROOT', dirname(__DIR__));

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$configFile = APP_ROOT . '/config.local.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Application is not configured. Copy config.example.php to config.local.php and add your private hosting settings.');
}

$config = require $configFile;
if (!is_array($config) || !isset($config['db'], $config['app'])) {
    http_response_code(503);
    exit('Application configuration is invalid.');
}

$timezone = (string) ($config['app']['timezone'] ?? 'UTC');
if (!in_array($timezone, timezone_identifiers_list(), true)) {
    $timezone = 'UTC';
}
date_default_timezone_set($timezone);

require_once APP_ROOT . '/includes/database.php';
require_once APP_ROOT . '/includes/security.php';
