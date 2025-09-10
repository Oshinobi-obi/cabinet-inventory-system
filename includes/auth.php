<?php
require_once 'config.php';

/**
 * Authentication and Authorization Module
 */

// Check if user is authenticated
function authenticate() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to access this page";
        redirect('login.php');
    }
    
    // Check for session hijacking
    if (!isset($_SESSION['user_agent']) || 
        $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_destroy();
        $_SESSION['error'] = "Session invalid. Please login again.";
        redirect('login.php');
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Check authorization based on role
function authorize($allowedRoles) {
    if (!checkRole($allowedRoles)) {
        $_SESSION['error'] = "You are not authorized to access this page";
        
        if (!isLoggedIn()) {
            redirect('login.php');
        }
        redirect('dashboard.php');
    }
}

/**
 * Validate login attempt and prevent brute force attacks
 */
function validateLoginAttempt($username) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    // Clear old attempts
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'],
        function($time) {
            return $time > (time() - LOGIN_TIMEOUT);
        }
    );
    
    // Count attempts for this username
    $attempts = array_count_values($_SESSION['login_attempts'])[$username] ?? 0;
    
    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        return false;
    }
    
    $_SESSION['login_attempts'][] = $username;
    return true;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token']) || 
        empty($_SESSION['csrf_token_time']) || 
        $_SESSION['csrf_token_time'] < (time() - 7200)) {
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return !empty($_SESSION['csrf_token']) && 
           !empty($_SESSION['csrf_token_time']) && 
           $_SESSION['csrf_token_time'] > (time() - 7200) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters long'];
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter'];
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter'];
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number'];
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one special character'];
    }
    
    return ['valid' => true, 'message' => ''];
}
?>