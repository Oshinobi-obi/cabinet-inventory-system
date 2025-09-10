<?php
require_once 'includes/auth.php';
authenticate();
authorize(['admin', 'encoder']);

header('Content-Type: application/json');
error_log('cabinet_api.php called with params: ' . print_r($_GET, true));

if (!isset($_GET['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_GET['action'];

if ($action === 'get_cabinet' && isset($_GET['id'])) {
    $cabinetId = intval($_GET['id']);
    
    try {
        // Get cabinet details
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(i.id) as item_count 
            FROM cabinets c 
            LEFT JOIN items i ON c.id = i.cabinet_id 
            WHERE c.id = ? 
            GROUP BY c.id
        ");
        $stmt->execute([$cabinetId]);
        $cabinet = $stmt->fetch();
        
        if (!$cabinet) {
            echo json_encode(['success' => false, 'message' => 'Cabinet not found']);
            exit;
        }
        
        // Get cabinet items with category names
        $stmt = $pdo->prepare("
            SELECT i.*, cat.name as category 
            FROM items i 
            JOIN categories cat ON i.category_id = cat.id 
            WHERE i.cabinet_id = ? 
            ORDER BY cat.name, i.name
        ");
        $stmt->execute([$cabinetId]);
        $items = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'cabinet' => $cabinet,
            'items' => $items
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action or missing parameters']);
}
?>
