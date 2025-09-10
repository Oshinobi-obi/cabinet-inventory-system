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
    $qrFile = null; // Don't redirect, just show without QR code
    error_log("QR code generation failed for cabinet: " . $cabinet['cabinet_number']);
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
                display: none !important;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .card {
                border: 1px solid #000 !important;
                box-shadow: none !important;
            }
            .card-header {
                background-color: #f8f9fa !important;
                border-bottom: 1px solid #000 !important;
            }
            .table {
                border: 1px solid #000 !important;
            }
            .table th, .table td {
                border: 1px solid #000 !important;
            }
        }
        
        .cabinet-photo {
            max-height: 200px;
        }
        
        .qr-code-section {
            text-align: center;
            border: 2px dashed #dee2e6;
            padding: 20px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        
        .export-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        
        .items-summary {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 12px;
            color: #666;
            display: none;
        }
        
        @media print {
            .print-footer {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <a href="cabinet.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Cabinets
                </a>
            </div>
            <div>
                <button id="printButton" class="btn btn-primary me-2">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <button id="downloadPdfButton" class="btn btn-success me-2">
                    <i class="fas fa-file-pdf me-1"></i> Download PDF
                </button>
                <button id="shareButton" class="btn btn-info">
                    <i class="fas fa-share-alt me-1"></i> Share
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header export-header">
                <div class="text-center">
                    <h3 class="mb-0">Cabinet Information Report</h3>
                    <p class="mb-0 mt-2">Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
                </div>
            </div>
            <div class="card-body">
                <!-- Cabinet Summary -->
                <div class="items-summary">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="text-primary"><?php echo htmlspecialchars($cabinet['name']); ?></h4>
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Cabinet Number:</strong><br><?php echo $cabinet['cabinet_number']; ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Total Items:</strong><br><?php echo $cabinet['item_count']; ?></p>
                                </div>
                            </div>
                            <p><strong>Categories:</strong><br><?php echo $cabinet['categories'] ?: 'No categories'; ?></p>
                            <p><strong>Last Updated:</strong><br><?php echo date('M j, Y g:i A', strtotime($cabinet['updated_at'])); ?></p>
                        </div>
                        <div class="col-md-4">
                            <?php if ($cabinet['photo_path'] && file_exists($cabinet['photo_path'])): ?>
                                <img src="<?php echo $cabinet['photo_path']; ?>" 
                                     class="img-fluid rounded cabinet-photo" 
                                     alt="Cabinet Photo">
                            <?php else: ?>
                                <div class="text-center text-muted p-4 border rounded">
                                    <i class="fas fa-image fa-3x mb-2"></i>
                                    <p>No photo available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- QR Code Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="qr-code-section">
                            <h5><i class="fas fa-qrcode me-2"></i>Scan QR Code for Online Access</h5>
                            <?php if ($qrFile): ?>
                                <div class="row align-items-center">
                                    <div class="col-md-6 text-center">
                                        <img src="<?php echo $qrFile; ?>" class="img-fluid" alt="QR Code" style="max-width: 200px;">
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Quick Access URL:</strong></p>
                                        <p class="small text-muted bg-white p-2 rounded border">
                                            <?php echo (defined('BASE_URL') ? BASE_URL : 'http://localhost/cabinet-inventory-system/') . "index.php?cabinet=" . urlencode($cabinet['cabinet_number']); ?>
                                        </p>
                                        <p class="small text-muted">
                                            <i class="fas fa-mobile-alt me-1"></i>
                                            Scan with your mobile device to view cabinet details online
                                        </p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    QR code could not be generated. Please contact system administrator.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h5 class="mb-3"><i class="fas fa-list me-2"></i>Inventory Details</h5>
                <?php if ($items): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 45%;">Item Name</th>
                                    <th style="width: 30%;">Category</th>
                                    <th style="width: 20%;">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                $totalQuantity = 0;
                                foreach ($items as $item): 
                                    $totalQuantity += $item['quantity'];
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($item['category']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <strong><?php echo $item['quantity']; ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th colspan="3" class="text-end">Total Items:</th>
                                    <th class="text-center"><?php echo $totalQuantity; ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No items are currently stored in this cabinet.
                    </div>
                <?php endif; ?>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Report generated on: <?php echo date('F j, Y \a\t g:i A'); ?>
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <i class="fas fa-user me-1"></i>
                            Generated by: <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Print Footer (only visible when printing) -->
        <div class="print-footer">
            <hr>
            <p>Cabinet Information System - <?php echo SITE_NAME; ?> | Report Date: <?php echo date('Y-m-d'); ?> | Page: <span id="pageNumber"></span></p>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Cabinet Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Share this cabinet information with others:</p>
                    <div class="mb-3">
                        <label class="form-label">Public View URL:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shareUrl" 
                                   value="<?php echo (defined('BASE_URL') ? BASE_URL : 'http://localhost/cabinet-inventory-system/') . "index.php?cabinet=" . urlencode($cabinet['cabinet_number']); ?>" 
                                   readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copyUrlBtn">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <?php if ($qrFile): ?>
                    <div class="text-center">
                        <p>Or scan this QR code:</p>
                        <img src="<?php echo $qrFile; ?>" alt="QR Code" style="max-width: 200px;">
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        document.addEventListener('DOMContentLoaded', function() {
            // Print functionality
            const printButton = document.getElementById('printButton');
            if (printButton) {
                printButton.addEventListener('click', function() {
                    window.print();
                });
            }
            
            // PDF Download (using browser's print to PDF)
            const downloadPdfButton = document.getElementById('downloadPdfButton');
            if (downloadPdfButton) {
                downloadPdfButton.addEventListener('click', function() {
                    // Add a class to optimize for PDF
                    document.body.classList.add('pdf-mode');
                    
                    // Trigger print dialog (user can choose "Save as PDF")
                    setTimeout(() => {
                        window.print();
                        document.body.classList.remove('pdf-mode');
                    }, 100);
                });
            }
            
            // Share functionality
            const shareButton = document.getElementById('shareButton');
            if (shareButton) {
                shareButton.addEventListener('click', function() {
                    const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
                    shareModal.show();
                });
            }
            
            // Copy URL functionality
            const copyUrlBtn = document.getElementById('copyUrlBtn');
            if (copyUrlBtn) {
                copyUrlBtn.addEventListener('click', function() {
                    const shareUrl = document.getElementById('shareUrl');
                    shareUrl.select();
                    shareUrl.setSelectionRange(0, 99999); // For mobile devices
                    
                    try {
                        document.execCommand('copy');
                        this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-success');
                        
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-copy"></i> Copy';
                            this.classList.remove('btn-success');
                            this.classList.add('btn-outline-secondary');
                        }, 2000);
                    } catch (err) {
                        console.error('Failed to copy URL:', err);
                        alert('Failed to copy URL. Please copy manually.');
                    }
                });
            }
            
            // Enhanced print styles
            window.addEventListener('beforeprint', function() {
                document.body.classList.add('printing');
            });
            
            window.addEventListener('afterprint', function() {
                document.body.classList.remove('printing');
            });
            
            // Auto-focus on print dialog for better UX
            if (window.location.hash === '#print') {
                setTimeout(() => {
                    window.print();
                }, 500);
            }
        });
    </script>
</body>
</html>