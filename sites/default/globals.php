<?php
// Minimal globals.php for sites/default directory
// This file is required by sqlconf.php but doesn't need the full interface globals

// Basic PHP compatibility check
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('OpenEMR requires PHP 7.4.0 or higher. Current version: ' . PHP_VERSION);
}

// Basic OpenSSL check
if (!extension_loaded('openssl')) {
    die('OpenEMR requires the OpenSSL PHP extension to be installed.');
}

// Set basic error reporting
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', '1');

// Load custom session configuration
$session_config_file = __DIR__ . '/../../session_config.ini';
if (file_exists($session_config_file)) {
    $session_config = parse_ini_file($session_config_file);
    foreach ($session_config as $key => $value) {
        ini_set($key, $value);
    }
}

// Basic session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Basic constants that might be needed
if (!defined('IS_DASHBOARD')) {
    define('IS_DASHBOARD', false);
}

if (!defined('IS_PORTAL')) {
    define('IS_PORTAL', false);
}

if (!defined('IS_WINDOW')) {
    define('IS_WINDOW', false);
}

// Basic global variables
$GLOBALS['site_id'] = 'default';
$GLOBALS['site_dir'] = 'default';

// Basic authentication variables
$GLOBALS['authUserID'] = '';
$GLOBALS['authUser'] = '';
$GLOBALS['authProvider'] = '';

// Basic session variables
$GLOBALS['login_screen'] = false;
$GLOBALS['login_screen_via'] = '';

// Basic configuration
$GLOBALS['config'] = 0; // Setup mode