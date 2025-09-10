<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'cabinet_info_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Define base URL
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/cabinet-system/');

// Include functions
require_once 'functions.php';
?>