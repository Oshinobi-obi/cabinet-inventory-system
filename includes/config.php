<?php
// Prevent direct file access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__FILE__)) . '/');
}

// Application configuration
define('SITE_NAME', 'Cabinet Information System');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/cabinet-inventory-system/');

// Database configuration
// Smart database host detection for network access
function getDbHost()
{
    // Check if we have network config (from mobile server)
    $networkConfigFile = dirname(dirname(__FILE__)) . '/network_config.json';
    if (file_exists($networkConfigFile)) {
        $networkConfig = json_decode(file_get_contents($networkConfigFile), true);
        if ($networkConfig && isset($networkConfig['server_ip'])) {
            $serverIP = $networkConfig['server_ip'];
            // If accessed via network IP, use that for database connection too
            if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], $serverIP) !== false) {
                return $serverIP;
            }
        }
    }

    // For localhost access, use localhost
    return 'localhost';
}

define('DB_HOST', getDbHost());     // Smart host detection
define('DB_PORT', '3306');          // Replace with your MySQL server port if different
define('DB_NAME', 'cabinet_info_system');
define('DB_USER', 'root'); // Replace with your MySQL username
define('DB_PASS', 'Mico2025!'); // Replace with your MySQL password

// Security configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300);   // 5 minutes in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('CSRF_TOKEN_TIME', 7200); // 2 hours in seconds

// Upload configuration
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png']);
define('UPLOAD_PATH', BASE_PATH . 'uploads/');
define('QRCODE_PATH', BASE_PATH . 'qrcodes/');

// Set error reporting in production
if ($_SERVER['SERVER_NAME'] !== 'localhost') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Configure session security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// Disabled for local development
// ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
session_start();

// Set timezone
date_default_timezone_set('Asia/Manila');

// Generate nonce for inline scripts/styles
$nonce = base64_encode(random_bytes(16));

// Set security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// Build Content Security Policy with nonce
$csp = [
    "default-src 'self'",
    "script-src 'self' 'unsafe-eval' 'nonce-$nonce' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com",
    "script-src-elem 'self' 'nonce-$nonce' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com",
    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
    "style-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
    "img-src 'self' data: blob: https://chart.googleapis.com https://charts.googleapis.com",
    "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com data:",
    "connect-src 'self' https://unpkg.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://chart.googleapis.com https://charts.googleapis.com",
    "media-src 'self' blob:",
    "object-src 'none'"
];

// Send CSP header
header("Content-Security-Policy: " . implode('; ', $csp));

// Make nonce available globally
$GLOBALS['csp_nonce'] = $nonce;

// Create database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

// Include utility functions
require_once 'functions.php';
