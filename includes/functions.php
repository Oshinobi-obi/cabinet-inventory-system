<?php
// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function checkRole($allowedRoles) {
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
        return false;
    }
    return true;
}

// Generate random password
function generatePassword($length = 8) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    return substr(str_shuffle($chars), 0, $length);
}

// Sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate cabinet number
function generateCabinetNumber($pdo) {
    $prefix = "CAB";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM cabinets");
    $count = $stmt->fetch()['count'] + 1;
    return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
}
?>