<?php
require_once 'config.php';

// Redirect function with optional message
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION[$type] = $message;
    }
    header("Location: $url");
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        return false;
    }
    
    // Check session timeout
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

// Check user role
function checkRole($allowedRoles) {
    if (!isLoggedIn() || !isset($_SESSION['user_role']) || 
        !in_array($_SESSION['user_role'], $allowedRoles)) {
        return false;
    }
    return true;
}

// Generate secure random password
function generatePassword($length = 12) {
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $special = '!@#$%^&*()_-=+;:,.?';
    
    $password = '';
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    $all = $lowercase . $uppercase . $numbers . $special;
    for ($i = strlen($password); $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    return str_shuffle($password);
}

// Sanitize input data with advanced filtering
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = strip_tags($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Generate unique cabinet number with year
function generateCabinetNumber($pdo) {
    $prefix = "CAB";
    $year = date('Y');
    
    $stmt = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING(cabinet_number, 9) AS UNSIGNED)) as last_num
        FROM cabinets
        WHERE cabinet_number LIKE :prefix
    ");
    $stmt->execute(['prefix' => "{$prefix}{$year}%"]);
    $result = $stmt->fetch();
    
    $nextNum = ($result['last_num'] ?? 0) + 1;
    return sprintf("%s%s%04d", $prefix, $year, $nextNum);
}

// Validate file upload
function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        return [false, $errors[$file['error']] ?? 'Unknown upload error'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return [false, 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
    }
    
    if ($file['size'] > $maxSize) {
        return [false, 'File too large. Maximum size: ' . ($maxSize / 1024 / 1024) . 'MB'];
    }
    
    return [true, ''];
}

// Handle file upload
function handleFileUpload($file, $uploadDir, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = 5242880) {
    // Validate file
    [$isValid, $message] = validateFileUpload($file, $allowedTypes, $maxSize);
    if (!$isValid) {
        return [false, $message];
    }
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return [false, 'Could not create upload directory'];
        }
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('', true) . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $targetPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [false, 'Could not save uploaded file'];
    }
    
    return [true, $targetPath];
}

// Log system activity
function logActivity($pdo, $userId, $action, $details = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, 
            $action, 
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Validate and sanitize email
function validateEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate QR Code for cabinet
function generateQRCode($cabinetNumber) {
    $qrLibPath = BASE_PATH . 'phpqrcode/qrlib.php';
    if (!file_exists($qrLibPath)) {
        error_log('PHP QR Code library not found. Please download from http://phpqrcode.sourceforge.net/ and extract to phpqrcode directory.');
        return false;
    }
    
    require_once $qrLibPath;
    
    $qrDir = BASE_PATH . 'qrcodes/';
    if (!is_dir($qrDir)) {
        if (!mkdir($qrDir, 0755, true)) {
            error_log('Failed to create QR codes directory');
            return false;
        }
    }
    
    $qrContent = BASE_URL . "index.php?cabinet=" . urlencode($cabinetNumber);
    $qrFile = $qrDir . 'cabinet_' . preg_replace('/[^a-zA-Z0-9]/', '_', $cabinetNumber) . '.png';
    
    if (!class_exists('QRcode')) {
        error_log('QRcode class not found. Please check PHP QR Code library installation.');
        return false;
    }
    
    try {
        \QRcode::png($qrContent, $qrFile, constant('QR_ECLEVEL_L'), 10);
        return str_replace(BASE_PATH, '', $qrFile); // Return relative path
    } catch (Exception $e) {
        error_log('Failed to generate QR code: ' . $e->getMessage());
        return false;
    }
}

// Get user display name
function getUserDisplayName($user) {
    return trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['username'];
}