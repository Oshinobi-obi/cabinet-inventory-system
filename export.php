<?php
require_once 'includes/auth.php';
authenticate();
authorize(['admin', 'encoder']);

if (!isset($_GET['cabinet_id'])) {
    $_SESSION['error'] = "No cabinet specified";
    redirect('cabinet.php');
}

$cabinetId = intval($_GET['cabinet_id']);

// Get cabinet details
$stmt = $pdo->prepare("
    SELECT c.*, 
           GROUP_CONCAT(DISTINCT cat.name) as categories,
           COUNT(i.id) as item_count
    FROM cabinets c
    LEFT JOIN items i ON c.id = i.cabinet_id
    LEFT JOIN categories cat ON i.category_id = cat.id
    WHERE c.id = ?
    GROUP BY c.id
");
$stmt->execute([$cabinetId]);
$cabinet = $stmt->fetch();

if (!$cabinet) {
    $_SESSION['error'] = "Cabinet not found";
    redirect('cabinet.php');
}

// Get items
$stmt = $pdo->prepare("
    SELECT i.name, i.quantity, cat.name as category
    FROM items i
    JOIN categories cat ON i.category_id = cat.id
    WHERE i.cabinet_id = ?
    ORDER BY cat.name, i.name
");
$stmt->execute([$cabinetId]);
$items = $stmt->fetchAll();

// Generate QR code using the function from functions.php
$qrFile = generateQRCode($cabinet['cabinet_number']);

if ($qrFile === false) {
    $_SESSION['error'] = "Failed to generate QR code. Please check the system logs.";
    redirect('cabinet.php');
}

// For PDF generation, we would use a library like TCPDF or Dompdf
// This is a simplified version that outputs the data with the QR code

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export - <?php echo $cabinet['name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between mb-4 no-print">
            <a href="cabinet.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Cabinets
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> Print
            </button>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Cabinet Contents</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4><?php echo $cabinet['name']; ?></h4>
                        <p><strong>Cabinet #:</strong> <?php echo $cabinet['cabinet_number']; ?></p>
                        <p><strong>Categories:</strong> <?php echo $cabinet['categories'] ?: 'None'; ?></p>
                        <p><strong>Total Items:</strong> <?php echo $cabinet['item_count']; ?></p>
                        
                        <?php if ($cabinet['photo_path']): ?>
                            <img src="<?php echo $cabinet['photo_path']; ?>" 
                                 class="img-fluid rounded mb-3" 
                                 alt="Cabinet Photo" style="max-height: 200px;">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-center">
                        <h5>Scan QR Code to View Online</h5>
                        <?php if ($qrFile): ?>
                            <img src="<?php echo $qrFile; ?>" class="img-fluid" alt="QR Code">
                            <p class="mt-2 text-muted">Cabinet #: <?php echo $cabinet['cabinet_number']; ?></p>
                            <p class="small text-muted">
                                Scan with your mobile device to view cabinet details
                            </p>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                QR code generation failed. Please contact system administrator.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <hr>
                
                <h5 class="mb-3">Items List</h5>
                <?php if ($items): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
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
                    <p class="text-muted">No items found in this cabinet.</p>
                <?php endif; ?>
                
                <div class="mt-4 text-muted">
                    <small>Generated on: <?php echo date('Y-m-d H:i:s'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>