<?php
require_once 'config.php';

// Get the current server base URL (network-aware)
function getBaseURL()
{
    // Check if network config exists (from server.php)
    $networkConfigFile = __DIR__ . '/network_config.json';
    if (file_exists($networkConfigFile)) {
        $networkConfig = json_decode(file_get_contents($networkConfigFile), true);
        if ($networkConfig && isset($networkConfig['base_url'])) {
            return $networkConfig['base_url'];
        }
    }

    // Fallback to auto-detection
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // If running on localhost, try to detect local IP for mobile access
    if ($host === 'localhost' || $host === '127.0.0.1') {
        $localIP = getServerIP();
        if ($localIP && $localIP !== '127.0.0.1') {
            // Check if we're running on a custom port
            $port = $_SERVER['SERVER_PORT'] ?? '80';
            if ($port !== '80' && $port !== '443') {
                return "$protocol://$localIP:$port";
            }
            return "$protocol://$localIP";
        }
    }

    return "$protocol://$host";
}

// Get server IP address for mobile access
function getServerIP()
{
    // Method 1: Try to get from network config
    $networkConfigFile = __DIR__ . '/network_config.json';
    if (file_exists($networkConfigFile)) {
        $networkConfig = json_decode(file_get_contents($networkConfigFile), true);
        if ($networkConfig && isset($networkConfig['server_ip'])) {
            return $networkConfig['server_ip'];
        }
    }

    // Method 2: Windows-compatible IP detection
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows method using ipconfig
        $output = shell_exec('ipconfig | findstr /i "IPv4"');
        if ($output) {
            // Extract IP addresses from ipconfig output
            preg_match_all('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $output, $matches);
            if (isset($matches[1])) {
                foreach ($matches[1] as $ip) {
                    // Skip localhost and look for private network IPs
                    if (
                        $ip !== '127.0.0.1' &&
                        (strpos($ip, '192.168.') === 0 ||
                            strpos($ip, '10.') === 0 ||
                            strpos($ip, '172.') === 0)
                    ) {
                        return $ip;
                    }
                }
            }
        }
    } else {
        // Unix/Linux/Mac method
        $output = shell_exec("ifconfig | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | head -1");
        if ($output) {
            $localIP = trim($output);
            if ($localIP && $localIP !== '127.0.0.1') {
                return $localIP;
            }
        }
    }

    // Method 3: Try hostname method
    $hostname = gethostname();
    $localIP = gethostbyname($hostname);
    if ($localIP && $localIP !== $hostname && $localIP !== '127.0.0.1') {
        return $localIP;
    }

    // Method 4: Use server variable if available
    if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
        return $_SERVER['SERVER_ADDR'];
    }

    return '127.0.0.1';
}

// Redirect function with optional message
function redirect($url, $message = null, $type = 'success')
{
    if ($message) {
        $_SESSION[$type] = $message;
    }
    header("Location: $url");
    exit();
}

// Check if user is logged in
function isLoggedIn()
{
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
function checkRole($allowedRoles)
{
    if (
        !isLoggedIn() || !isset($_SESSION['user_role']) ||
        !in_array($_SESSION['user_role'], $allowedRoles)
    ) {
        return false;
    }
    return true;
}

// Generate secure random password
function generatePassword($length = 12)
{
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
function sanitizeInput($data)
{
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }

    $data = trim($data);
    $data = strip_tags($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Generate unique cabinet number with year
function generateCabinetNumber($pdo)
{
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
function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = 5242880)
{
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
function handleFileUpload($file, $uploadDir, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = 5242880)
{
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
function logActivity($pdo, $userId, $action, $details = '')
{
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
function validateEmail($email)
{
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate a real QR Code using reliable external services
function generateRealQRCode($data, $filename, $size = 300)
{
    // First, try to create a simple QR code using a local method
    if (createSimpleQRCode($data, $filename, $size)) {
        return true;
    }

    // If local method fails, try external APIs
    // Create context with proper headers to avoid blocking
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'timeout' => 5  // Reduced timeout
        ]
    ]);

    // Method 1: QR Server API (most reliable)
    $qrServerUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&format=png&data=" . urlencode($data);

    $imageData = @file_get_contents($qrServerUrl, false, $context);

    if ($imageData !== false && strlen($imageData) > 100) {
        // Verify it's actually a PNG image by checking the header
        if (substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n") {
            if (file_put_contents($filename, $imageData)) {
                return true;
            }
        }
    }

    // Method 2: Google Charts API
    $googleUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($data) . "&choe=UTF-8";

    $imageData = @file_get_contents($googleUrl, false, $context);

    if ($imageData !== false && strlen($imageData) > 100) {
        // Verify it's actually a PNG image
        if (substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n") {
            if (file_put_contents($filename, $imageData)) {
                return true;
            }
        }
    }

    // Method 3: QuickChart API
    $quickChartUrl = "https://quickchart.io/qr?text=" . urlencode($data) . "&size={$size}";

    $imageData = @file_get_contents($quickChartUrl, false, $context);

    if ($imageData !== false && strlen($imageData) > 100) {
        if (substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n") {
            if (file_put_contents($filename, $imageData)) {
                return true;
            }
        }
    }

    return false;
}

// Create a simple QR code using external APIs (PNG only)
function createSimpleQRCode($data, $filename, $size = 300)
{
    try {
        // Create directory if it doesn't exist
        $qrDir = dirname($filename);
        if (!is_dir($qrDir)) {
            if (!mkdir($qrDir, 0755, true)) {
                return false;
            }
        }

        // Use external APIs to generate PNG QR codes directly
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ],
                'timeout' => 10
            ]
        ]);

        // Try QR Server API first (most reliable for PNG)
        $qrServerUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&format=png&data=" . urlencode($data);
        $imageData = @file_get_contents($qrServerUrl, false, $context);

        if ($imageData !== false && strlen($imageData) > 100) {
            // Verify it's actually a PNG image by checking the header
            if (substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n") {
                if (file_put_contents($filename, $imageData)) {
                    return true;
                }
            }
        }

        // Try Google Charts API as fallback
        $googleUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($data) . "&choe=UTF-8";
        $imageData = @file_get_contents($googleUrl, false, $context);

        if ($imageData !== false && strlen($imageData) > 100) {
            // Verify it's actually a PNG image
            if (substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n") {
                if (file_put_contents($filename, $imageData)) {
                    return true;
                }
            }
        }

        // Try QuickChart API as final fallback
        $quickChartUrl = "https://quickchart.io/qr?text=" . urlencode($data) . "&size={$size}";
        $imageData = @file_get_contents($quickChartUrl, false, $context);

        if ($imageData !== false && strlen($imageData) > 100) {
            if (substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n") {
                if (file_put_contents($filename, $imageData)) {
                    return true;
                }
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Simple QR code creation failed: ' . $e->getMessage());
        return false;
    }
}

// Generate QR Code for cabinet and save to file
function generateQRCode($cabinetNumber, $saveToFile = false)
{
    $qrDir = __DIR__ . '/../qrcodes/';
    if (!is_dir($qrDir)) {
        if (!mkdir($qrDir, 0755, true)) {
            error_log('Failed to create QR codes directory');
            return false;
        }
    }

    // Use network-aware base URL for mobile access
    $baseUrl = getBaseURL();
    if (defined('BASE_URL')) {
        // Remove the /cabinet-inventory-system/ part and use the base URL
        $baseUrl = rtrim($baseUrl, '/');
    }

    // QR codes should point to the public interface
    $qrContent = $baseUrl . "/public/index.php?cabinet=" . urlencode($cabinetNumber);
    $qrFileName = 'cabinet_' . preg_replace('/[^a-zA-Z0-9]/', '_', $cabinetNumber) . '.png';
    $qrFile = $qrDir . $qrFileName;
    $qrRelativePath = 'qrcodes/' . $qrFileName;

    // If saving to file, use the real QR code generator
    if ($saveToFile) {
        if (generateRealQRCode($qrContent, $qrFile, 300)) {
            return $qrRelativePath;
        }
        return false;
    }

    // If not saving to file, return URLs for display (old behavior)
    // Check if file already exists
    if (file_exists($qrFile)) {
        return $qrRelativePath;
    }

    // Method 1: Use QR proxy to bypass CSP restrictions
    try {
        $proxyUrl = $baseUrl . 'qr_proxy.php?data=' . urlencode($qrContent) . '&size=300';

        // Return the proxy URL - it will handle the generation and serve locally
        return $proxyUrl;
    } catch (Exception $e) {
        error_log('Failed to generate QR code with proxy: ' . $e->getMessage());
    }

    // Method 2: Use simple QR generator
    try {
        $simpleQrUrl = $baseUrl . 'simple_qr.php?text=' . urlencode($qrContent) . '&size=300';

        return $simpleQrUrl;
    } catch (Exception $e) {
        error_log('Failed to generate simple QR code: ' . $e->getMessage());
    }

    // Final fallback: return direct external URL
    return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&data=' . urlencode($qrContent);
}

// Generate and save QR code to database
function generateAndSaveQRCodeToDB($pdo, $cabinetId)
{
    try {
        // Get cabinet details
        $stmt = $pdo->prepare("SELECT cabinet_number, name FROM cabinets WHERE id = ?");
        $stmt->execute([$cabinetId]);
        $cabinet = $stmt->fetch();

        if (!$cabinet) {
            return [false, "Cabinet not found"];
        }

        // Generate QR code and save to file
        $qrPath = generateQRCode($cabinet['cabinet_number'], true);

        if (!$qrPath) {
            return [false, "Failed to generate QR code file"];
        }

        // Update database with QR path
        $stmt = $pdo->prepare("UPDATE cabinets SET qr_path = ? WHERE id = ?");
        $success = $stmt->execute([$qrPath, $cabinetId]);

        if ($success) {
            return [true, $qrPath, $cabinet];
        } else {
            return [false, "Failed to update database"];
        }
    } catch (Exception $e) {
        error_log('QR generation and save failed: ' . $e->getMessage());
        return [false, "Database error: " . $e->getMessage()];
    }
}

// Generate QR code as data URL (inline SVG)
function generateQRCodeDataURL($content)
{
    // Simple QR code using Google Charts API as data URL
    $googleUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($content) . '&choe=UTF-8';
    return $googleUrl; // Return the URL directly for display
}

// Create a simple QR code alternative when all methods fail
function createSimpleQRAlternative($qrContent, $qrFile)
{
    // Create a simple HTML/CSS based QR placeholder
    $qrDir = dirname($qrFile);
    $htmlFile = $qrDir . '/qr_' . preg_replace('/[^a-zA-Z0-9]/', '_', basename($qrFile, '.png')) . '.html';

    $htmlContent = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { margin: 0; padding: 20px; text-align: center; font-family: Arial, sans-serif; background: white; }
        .qr-placeholder { 
            width: 250px; height: 250px; margin: 0 auto; 
            border: 3px solid #000; display: flex; align-items: center; justify-content: center;
            flex-direction: column; background: #f0f0f0;
        }
        .qr-text { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
        .qr-url { font-size: 10px; word-break: break-all; padding: 10px; }
    </style>
</head>
<body>
    <div class="qr-placeholder">
        <div class="qr-text">QR CODE</div>
        <div>Scan with phone to visit:</div>
        <div class="qr-url">' . htmlspecialchars($qrContent) . '</div>
    </div>
    <p><a href="' . htmlspecialchars($qrContent) . '" target="_blank">Click here to open link</a></p>
</body>
</html>';

    if (file_put_contents($htmlFile, $htmlContent) !== false) {
        return basename($htmlFile);
    }

    // Final fallback - return the Google Charts URL directly
    return 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrContent) . '&choe=UTF-8';
}

// Get user display name
function getUserDisplayName($user)
{
    return trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['username'];
}