<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Ensure user is authenticated
authenticate();

// Set JSON content type
header('Content-Type: application/json');

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
    // Generate QR code and save to database
    [$success, $qrPathOrError, $cabinet] = generateAndSaveQRCodeToDB($pdo, $cabinetId);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'QR Code generated successfully',
            'cabinet_name' => $cabinet['name'],
            'cabinet_number' => $cabinet['cabinet_number'],
            'qr_path' => $qrPathOrError
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => $qrPathOrError
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'An error occurred while generating QR code: ' . $e->getMessage()
    ]);
}
?>