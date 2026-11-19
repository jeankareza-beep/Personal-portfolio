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

$setupKey = (string) ($config['app']['setup_key'] ?? '');
$csrfSecret = (string) ($config['app']['csrf_secret'] ?? '');
$siteOrigin = (string) ($config['app']['site_origin'] ?? '');
$baseUrl = (string) ($config['app']['base_url'] ?? '');
$originParts = parse_url($siteOrigin);
$baseParts = parse_url($baseUrl);
if (strlen($setupKey) < 32 || str_starts_with($setupKey, 'REPLACE_WITH_')
    || strlen($csrfSecret) < 32 || str_starts_with($csrfSecret, 'REPLACE_WITH_')
    || filter_var($siteOrigin, FILTER_VALIDATE_URL) === false
    || !is_array($originParts) || ($originParts['scheme'] ?? '') !== 'https'
    || !empty($originParts['path']) || !empty($originParts['query']) || !empty($originParts['fragment'])
    || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
    || !is_array($baseParts) || ($baseParts['scheme'] ?? '') !== 'https') {
    http_response_code(503);
    exit('Security settings are incomplete. Configure HTTPS URLs and strong private setup and CSRF secrets.');
}

$timezone = (string) ($config['app']['timezone'] ?? 'UTC');
if (!in_array($timezone, timezone_identifiers_list(), true)) {
    $timezone = 'UTC';
}
date_default_timezone_set($timezone);

require_once APP_ROOT . '/includes/database.php';
require_once APP_ROOT . '/includes/security.php';
