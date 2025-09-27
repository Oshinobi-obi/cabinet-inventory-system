<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

try {
    require_once 'config.php';
    require_once 'auth.php';
    require_once 'functions.php';

    // Ensure user is authenticated
    if (!isLoggedIn()) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }

    // Clear any output that might have been generated
    ob_clean();

    // Set JSON content type
    header('Content-Type: application/json');
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Initialization error: ' . $e->getMessage()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get cabinet ID from POST data
$input = json_decode(file_get_contents('php://input'), true);
$cabinetId = $input['cabinet_id'] ?? null;

if (!$cabinetId) {
    echo json_encode(['success' => false, 'error' => 'Cabinet ID is required']);
    exit;
}

try {
    // Log the attempt
    error_log("QR Generation attempt for cabinet ID: " . $cabinetId);
    
    // Check if PDO is available
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    // Check if qrcodes directory exists and is writable
    $qrDir = __DIR__ . '/../qrcodes/';
    if (!is_dir($qrDir)) {
        if (!mkdir($qrDir, 0755, true)) {
            throw new Exception("Cannot create qrcodes directory");
        }
    }
    
    if (!is_writable($qrDir)) {
        throw new Exception("qrcodes directory is not writable");
    }
    
    // Generate QR code and save to database
    [$success, $qrPathOrError, $cabinet] = generateAndSaveQRCodeToDB($pdo, $cabinetId);

    if ($success) {
        error_log("QR Generation successful for cabinet: " . $cabinet['cabinet_number']);
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'QR Code generated successfully',
            'cabinet_name' => $cabinet['name'],
            'cabinet_number' => $cabinet['cabinet_number'],
            'qr_path' => $qrPathOrError
        ]);
        exit;
    } else {
        error_log("QR Generation failed: " . $qrPathOrError);
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $qrPathOrError
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("QR Generation exception: " . $e->getMessage());
    error_log("QR Generation stack trace: " . $e->getTraceAsString());
    
    // Clean any output and ensure JSON response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while generating QR code: ' . $e->getMessage()
    ]);
    exit;
}
