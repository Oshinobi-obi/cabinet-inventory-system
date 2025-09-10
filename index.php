<?php
require_once 'includes/config.php';

// Process QR scan or search
$cabinetData = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $searchTerm = sanitizeInput($_POST['search_term']);
    
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
    <style>
        body {
            background-color: #f8f9fa;
        }
        .viewer-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .search-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .cabinet-result {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header i {
            font-size: 40px;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cabinet-filing me-2"></i>Cabinet System
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
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#qrModal">
                    <i class="fas fa-qrcode me-1"></i> Scan QR Code
                </button>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($cabinetData): ?>
            <div class="cabinet-result">
                <div class="row">
                    <div class="col-md-6">
                        <h3><?php echo $cabinetData['name']; ?></h3>
                        <p class="text-muted">Cabinet #: <?php echo $cabinetData['cabinet_number']; ?></p>
                        
                        <?php if ($cabinetData['photo_path']): ?>
                            <img src="<?php echo $cabinetData['photo_path']; ?>" 
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
                                        <td><?php echo $item['name']; ?></td>
                                        <td><?php echo $item['category']; ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
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
        <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
            <div class="alert alert-warning text-center">
                No cabinet found with that number or name.
            </div>
        <?php endif; ?>
    </div>

    <!-- QR Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Scan QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="reader" style="width: 100%;"></div>
                    <div id="result" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script>
        function onScanSuccess(decodedText, decodedResult) {
            // Redirect to the same page with the cabinet number
            window.location.href = index.php?cabinet=${decodedText};
        }

        function onScanFailure(error) {
            // Handle scan failure
        }

        // Initialize QR scanner when modal is shown
        document.getElementById('qrModal').addEventListener('shown.bs.modal', function () {
            var html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", { fps: 10, qrbox: 250 });
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        });
    </script>
</body>
</html>