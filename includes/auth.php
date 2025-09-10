<?php
require_once 'config.php';

// Check if user is authenticated
function authenticate() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to access this page";
        redirect('login.php');
    }
}

// Check authorization based on role
function authorize($allowedRoles) {
    if (!checkRole($allowedRoles)) {
        $_SESSION['error'] = "You are not authorized to access this page";
        redirect('dashboard.php');
    }
}
?>