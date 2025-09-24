<?php
// Public API endpoint for cabinet information (no authentication required)
header('Content-Type: application/json');

try {
    require_once '../includes/config.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Config error: ' . $e->getMessage()]);
    exit;
}

if (!isset($_GET['action'])) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

$action = $_GET['action'];

try {
    switch ($action) {
        case 'get_cabinet':
            if (!isset($_GET['cabinet_id'])) {
                echo json_encode(['success' => false, 'error' => 'Cabinet ID required']);
                exit;
            }

            $cabinetId = intval($_GET['cabinet_id']);

            // Get cabinet details
            $stmt = $pdo->prepare("SELECT * FROM cabinets WHERE id = ?");
            $stmt->execute([$cabinetId]);
            $cabinet = $stmt->fetch();

            if (!$cabinet) {
                echo json_encode(['success' => false, 'error' => 'Cabinet not found']);
                exit;
            }

            // Get cabinet items
            $stmt = $pdo->prepare("
                SELECT i.*, c.name as category_name 
                FROM items i 
                JOIN categories c ON i.category_id = c.id 
                WHERE i.cabinet_id = ? 
                ORDER BY c.name, i.name
            ");
            $stmt->execute([$cabinetId]);
            $items = $stmt->fetchAll();

            $cabinet['items'] = $items;

            echo json_encode(['success' => true, 'cabinet' => $cabinet]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
