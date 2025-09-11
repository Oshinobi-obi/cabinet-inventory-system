<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'includes/config.php';
} catch (Exception $e) {
    die("Config error: " . $e->getMessage());
}

// Process QR scan or search
$cabinetData = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['cabinet'])) {
    // If POST, use search term, else use GET param
    $searchTerm = $_SERVER['REQUEST_METHOD'] == 'POST'
        ? sanitizeInput($_POST['search_term'])
        : sanitizeInput($_GET['cabinet']);

    try {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   GROUP_CONCAT(DISTINCT cat.name) as categories,
                   COUNT(i.id) as item_count
            FROM cabinets c
            LEFT JOIN items i ON c.id = i.cabinet_id
            LEFT JOIN categories cat ON i.category_id = cat.id
            WHERE c.cabinet_number = ? OR c.name LIKE ?
            GROUP BY c.id
        ");
        $stmt->execute([$searchTerm, "%$searchTerm%"]);
        $cabinetData = $stmt->fetch();
    } catch(PDOException $e) {
        $error = "Error searching cabinet: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinet Viewer - Cabinet Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/index.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cabinet-filing me-2"></i>Cabinet Inventory System
            </a>
            <div class="ms-auto">
                <a href="login.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
            </div>
        </div>
    </nav>
    <div class="viewer-container">
        <div class="header">
            <i class="fas fa-cabinet-filing"></i>
            <h1 class="mt-2">Cabinet Contents Viewer</h1>
            <p class="text-muted">Search by cabinet number or name, or scan QR code</p>
        </div>
        
        <div class="search-box">
            <form method="POST" action="">
                <div class="input-group">
                    <input type="text" class="form-control form-control-lg" 
                           placeholder="Enter cabinet number or name" 
                           name="search_term" required>
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <?php if ($cabinetData): ?>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#qrDisplayModal">
                        <i class="fas fa-qrcode me-1"></i> Show QR Code
                    </button>
                <?php else: ?>
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="fas fa-qrcode me-1"></i> Search Cabinet First
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($cabinetData): ?>
            <div class="cabinet-result">
                <div class="row">
                    <div class="col-md-6">
                        <h3><?php echo htmlspecialchars($cabinetData['name']); ?></h3>
                        <p class="text-muted">Cabinet #: <?php echo htmlspecialchars($cabinetData['cabinet_number']); ?></p>
                        
                        <?php if ($cabinetData['photo_path']): ?>
                            <img src="<?php echo htmlspecialchars($cabinetData['photo_path']); ?>" 
                                 class="img-fluid rounded mb-3" 
                                 alt="Cabinet Photo">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5>Contents Summary</h5>
                        <p>Categories: <?php echo $cabinetData['categories'] ?: 'None'; ?></p>
                        <p>Total Items: <?php echo $cabinetData['item_count']; ?></p>
                        
                        <h5 class="mt-4">Items List</h5>
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT i.name, i.quantity, cat.name as category
                            FROM items i
                            JOIN categories cat ON i.category_id = cat.id
                            WHERE i.cabinet_id = ?
                            ORDER BY cat.name, i.name
                        ");
                        $stmt->execute([$cabinetData['id']]);
                        $items = $stmt->fetchAll();
                        
                        if ($items):
                        ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Category</th>
                                        <th>Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p>No items found in this cabinet.</p>
                        <?php endif; ?>
                        </div>
                </div>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['cabinet'])): ?>
            <div class="alert alert-warning text-center">
                No cabinet found with that number or name.
            </div>
        <?php endif; ?>
    </div>

    <!-- QR Display Modal -->
    <div class="modal fade" id="qrDisplayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-qrcode me-2"></i>QR Code for <?php echo htmlspecialchars($cabinetData['name'] ?? 'Cabinet'); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <?php if ($cabinetData): ?>
                        <h6 class="mb-3">Cabinet: <?php echo htmlspecialchars($cabinetData['cabinet_number']); ?></h6>
                        
                        <?php if (!empty($cabinetData['qr_path']) && file_exists($cabinetData['qr_path'])): ?>
                            <div class="qr-code-container mb-3">
                                <img src="<?php echo htmlspecialchars($cabinetData['qr_path']); ?>" 
                                     alt="QR Code for <?php echo htmlspecialchars($cabinetData['cabinet_number']); ?>"
                                     class="img-fluid"
                                     style="max-width: 250px; border: 1px solid #dee2e6; border-radius: 8px; background: white; padding: 10px;">
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-mobile-alt me-2"></i>How to use this QR Code:</h6>
                                <ul class="list-unstyled mb-0">
                                    <li><i class="fas fa-camera text-primary me-2"></i>Open your phone's camera</li>
                                    <li><i class="fas fa-qrcode text-primary me-2"></i>Point at the QR code above</li>
                                    <li><i class="fas fa-external-link-alt text-primary me-2"></i>Tap the notification to view cabinet</li>
                                </ul>
                            </div>
                            
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>QR Code Not Generated</h6>
                                <p class="mb-3">No QR code has been generated for this cabinet yet.</p>
                                <button type="button" 
                                        class="btn btn-primary"
                                        onclick="generateQRForCabinet(<?php echo $cabinetData['id']; ?>, '<?php echo htmlspecialchars($cabinetData['cabinet_number']); ?>', '<?php echo htmlspecialchars($cabinetData['name']); ?>')">
                                    <i class="fas fa-qrcode me-1"></i>Generate QR Code Now
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            This QR code links to: <?php echo (defined('BASE_URL') ? BASE_URL : 'http://localhost/cabinet-inventory-system/') . "index.php?cabinet=" . urlencode($cabinetData['cabinet_number']); ?>
                        </small>
                        
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/index.js"></script>
</body>
</html>