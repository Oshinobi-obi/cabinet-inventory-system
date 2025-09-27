<?php
// Handle logout POST (AJAX) at the very top before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    require_once '../includes/auth.php';
    $_SESSION = array();
    session_destroy();
    exit;
}

require_once '../includes/auth.php';
authenticate();
authorize(['admin', 'encoder']);

// Handle GET actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action == 'delete' && isset($_GET['id'])) {
        $cabinetId = intval($_GET['id']);

        try {
            // First delete all items in the cabinet
            $stmt = $pdo->prepare("DELETE FROM items WHERE cabinet_id = ?");
            $stmt->execute([$cabinetId]);

            // Get cabinet photo path to delete file
            $stmt = $pdo->prepare("SELECT photo_path FROM cabinets WHERE id = ?");
            $stmt->execute([$cabinetId]);
            $cabinet = $stmt->fetch();

            // Delete the cabinet
            $stmt = $pdo->prepare("DELETE FROM cabinets WHERE id = ?");
            $stmt->execute([$cabinetId]);

            // Delete photo file if exists
            if ($cabinet && $cabinet['photo_path'] && file_exists($cabinet['photo_path'])) {
                unlink($cabinet['photo_path']);
            }

            $_SESSION['success'] = "Cabinet deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting cabinet: " . $e->getMessage();
        }

        redirect('cabinet.php');
    }

    if ($action == 'generate_qr' && isset($_GET['id'])) {
        $cabinetId = intval($_GET['id']);

        // Generate QR code and save to database
        [$success, $qrPathOrError, $cabinet] = generateAndSaveQRCodeToDB($pdo, $cabinetId);

        if ($success) {
            // Don't set regular success message, use modal instead
            $_SESSION['qr_file'] = $qrPathOrError;
            $_SESSION['qr_cabinet_number'] = $cabinet['cabinet_number'];
            $_SESSION['qr_cabinet_name'] = $cabinet['name'];
        } else {
            $_SESSION['error'] = "Failed to generate QR code: " . $qrPathOrError;
        }

        redirect('cabinet.php');
    }
}

// Function to fix auto-increment sequence
function fixAutoIncrement($pdo) {
    try {
        // Get the current auto-increment value
        $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'cabinets'");
        $result = $stmt->fetch();
        $currentAutoIncrement = $result['Auto_increment'] ?? 1;
        
        // Get the actual maximum ID from the table
        $stmt = $pdo->query("SELECT MAX(id) as max_id FROM cabinets");
        $result = $stmt->fetch();
        $maxId = $result['max_id'] ?? 0;
        
        // If auto-increment is way off, reset it
        if ($currentAutoIncrement > $maxId + 100) {
            $nextId = $maxId + 1;
            $pdo->exec("ALTER TABLE cabinets AUTO_INCREMENT = $nextId");
        }
    } catch (Exception $e) {
        // Silently fail if there's an issue
    }
}

// Fix auto-increment sequence on page load
fixAutoIncrement($pdo);


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_cabinet'])) {
        // Add new cabinet
        $cabinetNumber = sanitizeInput($_POST['cabinet_number']);
        $name = sanitizeInput($_POST['name']);

        // Handle file upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . basename($_FILES['photo']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $photoPath = $targetPath;
            }
        }

        try {
            // Insert cabinet
            $stmt = $pdo->prepare("INSERT INTO cabinets (cabinet_number, name, photo_path) VALUES (?, ?, ?)");
            $stmt->execute([$cabinetNumber, $name, $photoPath]);
            $cabinetId = $pdo->lastInsertId();

            // Add items if provided
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['name']) && !empty($item['category'])) {
                        $stmt = $pdo->prepare("
                            INSERT INTO items (cabinet_id, category_id, name, quantity) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $cabinetId,
                            $item['category'],
                            sanitizeInput($item['name']),
                            intval($item['quantity'])
                        ]);
                    }
                }
            }

            // Return JSON response for AJAX
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Cabinet added successfully!']);
                exit;
            }

            $_SESSION['success'] = "Cabinet added successfully!";
            redirect('cabinet.php');
        } catch (PDOException $e) {
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error adding cabinet: ' . $e->getMessage()]);
                exit;
            }
            $error = "Error adding cabinet: " . $e->getMessage();
        }
    } elseif (isset($_POST['edit_cabinet'])) {
        // Edit existing cabinet
        $cabinetId = intval($_POST['cabinet_id']);
        $name = sanitizeInput($_POST['name']);

        // Get current photo path from database first
        $stmt = $pdo->prepare("SELECT photo_path FROM cabinets WHERE id = ?");
        $stmt->execute([$cabinetId]);
        $currentCabinet = $stmt->fetch();
        $photoPath = $currentCabinet['photo_path']; // Keep existing photo by default

        // Handle file upload for edit - only if a new photo is uploaded
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . basename($_FILES['photo']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                // Delete old photo if exists
                if ($photoPath && file_exists($photoPath)) {
                    unlink($photoPath);
                }
                $photoPath = $targetPath;
            }
        }

        try {
            // Update cabinet
            $stmt = $pdo->prepare("UPDATE cabinets SET name = ?, photo_path = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $photoPath, $cabinetId]);

            // Delete existing items
            $stmt = $pdo->prepare("DELETE FROM items WHERE cabinet_id = ?");
            $stmt->execute([$cabinetId]);

            // Add updated items
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['name']) && !empty($item['category'])) {
                        $stmt = $pdo->prepare("
                            INSERT INTO items (cabinet_id, category_id, name, quantity) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $cabinetId,
                            $item['category'],
                            sanitizeInput($item['name']),
                            intval($item['quantity'])
                        ]);
                    }
                }
            }

            // Return JSON response for AJAX
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Cabinet updated successfully!']);
                exit;
            }

            $_SESSION['success'] = "Cabinet updated successfully!";
            redirect('cabinet.php');
        } catch (PDOException $e) {
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error updating cabinet: ' . $e->getMessage()]);
                exit;
            }
            $error = "Error updating cabinet: " . $e->getMessage();
        }
    }
}

// Pagination setup
$itemsPerPage = 5;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get total count of cabinets
$countStmt = $pdo->query("SELECT COUNT(*) FROM cabinets");
$totalCabinets = $countStmt->fetchColumn();
$totalPages = ceil($totalCabinets / $itemsPerPage);

// Get cabinets for current page
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(i.id) as item_count 
    FROM cabinets c 
    LEFT JOIN items i ON c.id = i.cabinet_id 
    GROUP BY c.id 
    ORDER BY c.updated_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$itemsPerPage, $offset]);
$cabinets = $stmt->fetchAll();

// Get categories for dropdown
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinets</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/DepEd_Logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/navbar.css" rel="stylesheet">
    <link href="../assets/css/cabinet.css" rel="stylesheet">
    <style>
        /* Ensure sidebar is hidden on page load */
        #sidebar {
            left: -250px !important;
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .alert {
            border: none;
            border-radius: 8px;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 10px;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 10px 10px 0 0 !important;
        }

        /* Glassmorphism overlay for logout modal */
        #logoutConfirmModal {
            background: rgba(255, 255, 255, 0.25) !important;
            backdrop-filter: blur(8px) saturate(1.2);
            -webkit-backdrop-filter: blur(8px) saturate(1.2);
            transition: background 0.2s;
            z-index: 2000;
        }

        #logoutConfirmModal .modal-content,
        #logoutConfirmModal .modal-title,
        #logoutConfirmModal .modal-body,
        #logoutConfirmModal .modal-footer,
        #logoutConfirmModal .modal-content p,
        #logoutConfirmModal .modal-content h5 {
            color: #222 !important;
            background: #fff !important;
            user-select: none;
        }

        #logoutConfirmModal .modal-content {
            box-shadow: 0 4px 32px rgba(0, 0, 0, 0.18);
        }

        #logoutConfirmModal .modal-title {
            font-weight: 600;
        }

        #logoutConfirmModal .modal-footer {
            background: #fff !important;
        }

        #logoutConfirmModal .btn-danger,
        #logoutConfirmModal .btn-secondary {
            user-select: none;
        }
        
        /* Mobile-friendly table scrolling */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Ensure table doesn't break on mobile */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .table th,
            .table td {
                white-space: nowrap;
                padding: 0.5rem 0.25rem;
            }
        }
        
        /* Mobile-friendly modal tables */
        .modal .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .modal .table th,
        .modal .table td {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary admin-navbar">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <button id="sidebarToggle" class="btn btn-outline-light me-2">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand d-flex align-items-center">
                        <i class="fa fa-archive me-2"></i>
                        Cabinet Management
                    </span>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4">
            <?php if ($_SESSION['user_role'] === 'encoder'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!
                    </h2>
                    <div class="badge bg-primary fs-6">Encoder Access</div>
                </div>
            <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!
                    </h2>
                    <div class="badge bg-danger fs-6">Admin Access</div>
                </div>
            <?php else: ?>
                <h2 class="mb-4">Cabinets</h2>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>


            <!-- Cabinets List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Existing Cabinets</h5>
                </div>
                <div class="card-body">
                    <?php if ($cabinets): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Cabinet Number</th>
                                        <th>Name</th>
                                        <th>Items</th>
                                        <th>QR Code</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cabinets as $cabinet): ?>
                                        <tr>
                                            <td><?php echo $cabinet['cabinet_number']; ?></td>
                                            <td><?php echo $cabinet['name']; ?></td>
                                            <td><?php echo $cabinet['item_count']; ?></td>
                                            <td>
                                                <?php if (!empty($cabinet['qr_path']) && file_exists(__DIR__ . '/../' . $cabinet['qr_path'])): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Generated
                                                    </span>
                                                    <br>
                                                    <small class="text-muted"><?php echo basename($cabinet['qr_path']); ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-times me-1"></i>Not Generated
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($cabinet['updated_at'])); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-info view-cabinet-btn me-1"
                                                        data-cabinet-id="<?php echo $cabinet['id']; ?>"
                                                        title="View Cabinet Details"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewCabinetModal">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-secondary qr-generate-btn"
                                                        title="Generate QR Code"
                                                        data-bs-toggle="tooltip"
                                                        data-cabinet-id="<?php echo $cabinet['id']; ?>">
                                                        <i class="fas fa-qrcode"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Cabinet pagination" class="mt-4">
                                <div class="d-flex justify-content-center align-items-center">
                                    <!-- Previous button -->
                                    <?php if ($currentPage > 1): ?>
                                        <a class="btn btn-outline-secondary btn-sm me-3" href="?page=<?php echo $currentPage - 1; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm me-3" disabled>
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Current page indicator -->
                                    <span class="fw-bold"><?php echo $currentPage; ?></span>

                                    <!-- Next button -->
                                    <?php if ($currentPage < $totalPages): ?>
                                        <a class="btn btn-outline-secondary btn-sm ms-3" href="?page=<?php echo $currentPage + 1; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm ms-3" disabled>
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        <?php endif; ?>

                        <!-- Pagination info -->
                        <div class="text-center text-muted mt-2">
                            Showing <?php echo (($currentPage - 1) * $itemsPerPage) + 1; ?>-<?php echo min($currentPage * $itemsPerPage, $totalCabinets); ?> of <?php echo $totalCabinets; ?> cabinets
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No cabinets found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- View Cabinet Modal -->
    <div class="modal fade" id="viewCabinetModal" tabindex="-1" aria-labelledby="viewCabinetModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewCabinetModalLabel">Cabinet Details</h5>
                </div>
                <div class="modal-body" id="viewCabinetContent">
                    <!-- Loading State -->
                    <div id="view-loading-state" class="text-center py-5">
                        <video src="../assets/images/Trail-Loading.webm" style="width: 150px; height: 150px; margin: 0 auto; display:block;" autoplay muted loop playsinline></video>
                        <h5 class="mt-3 text-muted">Loading Cabinet Details...</h5>
                    </div>

                    <!-- Content will be loaded here -->
                    <div id="view-content-container" style="display: none;">
                        <!-- Dynamic content goes here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                        <button type="button" class="btn btn-danger" id="deleteCabinetBtn" data-bs-toggle="modal" data-bs-target="#deleteCabinetModal">
                            <i class="fas fa-trash me-1"></i> Delete Cabinet
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Cabinet Confirmation Modal -->
    <div class="modal fade" id="deleteCabinetModal" tabindex="-1" aria-labelledby="deleteCabinetModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteCabinetModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Cabinet
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete this cabinet?</p>
                    <div id="deleteCabinetDetails" class="border rounded p-3 bg-light">
                        <!-- Cabinet details will be populated here -->
                    </div>
                    <p class="text-muted mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        All items inside this cabinet will also be deleted.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i> Delete Cabinet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Data Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">
                        <i class="fas fa-download me-2"></i>Export Cabinet Data
                    </h5>
                </div>
                <div class="modal-body">
                    <!-- Loading Overlay -->
                    <div id="export-loading-overlay" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); z-index: 1000; border-radius: 0.375rem;">
                        <div class="d-flex flex-column justify-content-center align-items-center h-100">
                            <video src="../assets/images/Trail-Loading.webm" style="width: 120px; height: 120px; display:block;" autoplay muted loop playsinline></video>
                            <h6 class="mt-2 text-muted">Preparing Export...</h6>
                        </div>
                    </div>

                    <!-- Export Form -->
                    <form id="exportForm">
                        <div class="mb-3">
                            <label for="export_cabinet" class="form-label">Select Cabinet to Export</label>
                            <select class="form-select" id="export_cabinet" name="cabinet_id" required>
                                <option value="">Choose a cabinet...</option>
                                <option value="all" id="all-cabinets-option">All Cabinets</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT id, cabinet_number, name FROM cabinets ORDER BY cabinet_number");
                                    $exportCabinets = $stmt->fetchAll();
                                    foreach ($exportCabinets as $cabinet): ?>
                                        <option value="<?php echo $cabinet['id']; ?>">
                                            <?php echo $cabinet['cabinet_number'] . ' - ' . $cabinet['name']; ?>
                                        </option>
                                <?php endforeach;
                                } catch (Exception $e) { /* ignore */
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="format" id="format_pdf" value="pdf" checked>
                                <label class="form-check-label" for="format_pdf">
                                    <i class="fas fa-file-pdf text-danger me-1"></i>PDF Document
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="format" id="format_excel" value="excel">
                                <label class="form-check-label" for="format_excel">
                                    <i class="fas fa-file-excel text-success me-1"></i>Excel Spreadsheet
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="downloadExportBtn">
                        <i class="fas fa-download me-1"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Cabinet Modal -->
    <div class="modal fade" id="editCabinetModal" tabindex="-1" aria-labelledby="editCabinetModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editCabinetForm" method="POST" action="cabinet.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCabinetModalLabel">
                            <i class="fas fa-edit me-2"></i>Edit Cabinet
                        </h5>
                    </div>
                    <div class="modal-body">
                        <!-- Loading State -->
                        <div id="edit-loading-state" class="text-center py-5">
                            <video src="../assets/images/Trail-Loading.webm" style="width: 150px; height: 150px; margin: 0 auto; display:block;" autoplay muted loop playsinline></video>
                            <h5 class="mt-3 text-muted">Loading Cabinet Details...</h5>
                        </div>

                        <!-- Form Content -->
                        <div id="edit-form-content" style="display: none;">
                            <input type="hidden" id="edit_cabinet_id" name="cabinet_id">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="edit_cabinet_number" class="form-label">Cabinet Number</label>
                                    <input type="text" class="form-control" id="edit_cabinet_number" name="cabinet_number" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_name" class="form-label">Cabinet Name <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="edit_photo" class="form-label">Cabinet Photo</label>
                                    <input type="file" class="form-control" id="edit_photo" name="photo" accept="image/*">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <span id="current-photo-text">Leave empty to keep current photo</span>
                                    </div>
                                </div>
                                <div class="col-md-4" id="current-photo-preview">
                                    <!-- Current photo preview will be shown here -->
                                </div>
                            </div>

                            <h6 class="mt-4 mb-3">Cabinet Contents</h6>

                            <div id="edit-items-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 15px; background-color: #f8f9fa;">
                                <!-- Items will be loaded dynamically -->
                            </div>

                            <button type="button" id="add-edit-item" class="btn btn-secondary btn-sm mt-2">
                                <i class="fas fa-plus me-1"></i> Add Another Item
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_cabinet" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal (hidden by default) -->
    <div class="modal" id="logoutConfirmModal" tabindex="-1" aria-modal="true" role="dialog" style="display:none;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;">
                <div class="modal-header" style="border-bottom:none;">
                    <h5 class="modal-title">Confirm Logout</h5>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to logout?</p>
                </div>
                <div class="modal-footer" style="border-top:none;">
                    <button type="button" class="btn btn-secondary" id="cancelLogoutBtn">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmLogoutBtn">Logout</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Logout Loading Modal (hidden by default) -->
    <div class="modal" id="logoutLoadingModal" tabindex="-1" aria-hidden="true" style="display:none; background:rgba(255,255,255,0.25); backdrop-filter: blur(8px) saturate(1.2); -webkit-backdrop-filter: blur(8px) saturate(1.2); z-index:2100;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:transparent; border:none; box-shadow:none; align-items:center;">
                <div class="modal-body text-center">
                    <video src="../assets/images/Trail-Loading.webm" autoplay loop muted style="width:120px; border-radius:50%; background:#fff;"></video>
                    <div class="mt-3 text-dark fw-bold" style="font-size:1.2rem; text-shadow:0 1px 4px #fff;">Logging Out! Thank you...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Display Modal -->
    <?php if (isset($_SESSION['qr_file'])): ?>
        <div class="modal fade" id="qrModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-qrcode me-2"></i>QR Code Generated Successfully
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 text-center">
                                <h6>Cabinet: <?php echo htmlspecialchars($_SESSION['qr_cabinet_name'] ?? 'Unknown'); ?></h6>
                                <p class="text-muted">Number: <?php echo htmlspecialchars($_SESSION['qr_cabinet_number'] ?? 'Unknown'); ?></p>
                                <div class="border rounded p-3 bg-light">
                                    <img src="../<?php echo $_SESSION['qr_file']; ?>" alt="QR Code" class="img-fluid" style="max-width: 250px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>How to use this QR Code:</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-mobile-alt text-primary me-2"></i>
                                        Open your phone's camera or QR scanner app
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-camera text-primary me-2"></i>
                                        Point the camera at the QR code
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-external-link-alt text-primary me-2"></i>
                                        Tap the notification to open the cabinet details
                                    </li>
                                </ul>

                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        The QR code links to:
                                        <br>
                                        <code class="small"><?php echo (defined('BASE_URL') ? BASE_URL : 'http://localhost/cabinet-inventory-system/') . "index.php?cabinet=" . urlencode($_SESSION['qr_cabinet_number'] ?? ''); ?></code>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="../includes/export.php?cabinet_id=<?php echo $_GET['id'] ?? ''; ?>" class="btn btn-primary">
                            <i class="fas fa-download me-1"></i>View Full Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php
        unset($_SESSION['qr_file'], $_SESSION['qr_cabinet_number'], $_SESSION['qr_cabinet_name']);
    endif; ?>

    <!-- Success Message Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-check-circle fa-3x text-success"></i>
                    </div>
                    <h5 class="mb-0" id="successMessage">Operation Successful!</h5>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Success Modal -->
    <div class="modal fade" id="qrSuccessModal" tabindex="-1" aria-labelledby="qrSuccessModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-check-circle fa-3x text-success"></i>
                    </div>
                    <h5 id="qrSuccessMessage">Cabinet QR Code Generated Successfully ✓</h5>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        // Categories data for JavaScript
        window.cabinetCategories = <?php echo json_encode($categories); ?>;

        // Add/remove item rows dynamically
        let itemCount = 1;

        document.addEventListener('DOMContentLoaded', function() {
            // Logout modal logic
            var logoutBtn = document.getElementById('logoutSidebarBtn');
            var confirmModal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'), {
                backdrop: 'static',
                keyboard: false
            });
            var loadingModal = new bootstrap.Modal(document.getElementById('logoutLoadingModal'), {
                backdrop: 'static',
                keyboard: false
            });
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('logoutConfirmModal').style.display = 'block';
                    confirmModal.show();
                });
            }
            document.getElementById('confirmLogoutBtn').onclick = function() {
                confirmModal.hide();
                setTimeout(function() {
                    document.getElementById('logoutLoadingModal').style.display = 'block';
                    loadingModal.show();
                    // AJAX POST to logout (destroy session)
                    fetch('cabinet.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'logout=1',
                        cache: 'no-store',
                        credentials: 'same-origin'
                    }).then(function() {
                        setTimeout(function() {
                            window.location.replace('login.php');
                        }, 2000);
                    });
                }, 300);
            };
            document.getElementById('cancelLogoutBtn').onclick = function() {
                confirmModal.hide();
                setTimeout(function() {
                    document.getElementById('logoutConfirmModal').style.display = 'none';
                }, 300);
            };

            // Toggle sidebar
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');

            if (sidebarToggle && sidebar && content) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    content.classList.toggle('expanded');
                });
            }

            // QR Generation button event listeners
            document.querySelectorAll('.qr-generate-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const cabinetId = this.getAttribute('data-cabinet-id');
                    generateQR(cabinetId);
                });
            });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });


            // Show QR success modal if QR code was generated
            <?php if (isset($_SESSION['qr_file'])): ?>
                document.getElementById('qrSuccessMessage').textContent = '<?php echo $_SESSION['qr_cabinet_name']; ?> QR Code Generated Successfully ✓';
                const qrSuccessModal = new bootstrap.Modal(document.getElementById('qrSuccessModal'));
                qrSuccessModal.show();
            <?php endif; ?>
        });

        // Add event listeners to existing remove buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.item-row').remove();
            });
        });

        // Modal event handlers to fix close button issues
        document.addEventListener('DOMContentLoaded', function() {
            // Function to properly close modal and clean up backdrop
            function closeModal(modalElement) {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }

                // Force remove backdrop if it exists
                setTimeout(() => {
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    // Ensure body scroll is restored
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 300);
            }

            // View Cabinet Modal event handlers
            const viewModal = document.getElementById('viewCabinetModal');
            if (viewModal) {
                viewModal.addEventListener('hidden.bs.modal', function() {
                    // Clean up modal content when closed
                    document.getElementById('view-content-container').innerHTML = '';

                    // Reset modal state to loading for next time
                    document.getElementById('view-loading-state').style.display = 'block';
                    document.getElementById('view-content-container').style.display = 'none';

                    // Ensure backdrop cleanup
                    setTimeout(() => {
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 100);
                });
            }

            // Edit Cabinet Modal event handlers
            const editModal = document.getElementById('editCabinetModal');
            if (editModal) {
                editModal.addEventListener('hidden.bs.modal', function() {
                    // Clean up form when modal is closed
                    document.getElementById('editCabinetForm').reset();
                    document.getElementById('edit-items-container').innerHTML = '';
                    const photoPreview = document.getElementById('current-photo-preview');
                    if (photoPreview) photoPreview.innerHTML = '';

                    // Reset modal state to loading for next time
                    document.getElementById('edit-loading-state').style.display = 'block';
                    document.getElementById('edit-form-content').style.display = 'none';

                    // Ensure backdrop cleanup
                    setTimeout(() => {
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 100);
                });
            }

            // Delete Cabinet Modal event handlers
            const deleteModal = document.getElementById('deleteCabinetModal');
            if (deleteModal) {
                deleteModal.addEventListener('hidden.bs.modal', function() {
                    // Ensure backdrop cleanup
                    setTimeout(() => {
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 100);
                });
            }

            // Enhanced close button handlers
            document.addEventListener('click', function(event) {
                if (event.target.matches('[data-bs-dismiss="modal"]') ||
                    event.target.closest('[data-bs-dismiss="modal"]')) {
                    event.preventDefault();
                    event.stopPropagation();

                    const button = event.target.matches('[data-bs-dismiss="modal"]') ?
                        event.target : event.target.closest('[data-bs-dismiss="modal"]');
                    const modal = button.closest('.modal');

                    if (modal) {
                        closeModal(modal);
                    }
                }
            });

            // Global escape key handler
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const openModal = document.querySelector('.modal.show');
                    if (openModal) {
                        const modalInstance = bootstrap.Modal.getInstance(openModal);
                        if (modalInstance) {
                            modalInstance.hide();
                        } else {
                            closeModal(openModal);
                        }
                    }
                }
            });

            // Emergency cleanup - double-click anywhere to force cleanup stuck modals
            let clickCount = 0;
            document.addEventListener('click', function(event) {
                clickCount++;
                setTimeout(() => {
                    clickCount = 0;
                }, 500);

                if (clickCount === 2) { // Double click
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop && !document.querySelector('.modal.show')) {
                        console.log('Emergency cleanup triggered');
                        forceCleanupModals();
                    }
                }
            });

            // Backup cleanup function that runs periodically
            setInterval(() => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop && !document.querySelector('.modal.show')) {
                    backdrop.remove();
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            }, 2000);
        });

        // Dashboard-style modal functionality
        let currentCabinetData = null;

        // View Cabinet Modal functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('view-cabinet-btn') || e.target.closest('.view-cabinet-btn')) {
                const button = e.target.classList.contains('view-cabinet-btn') ? e.target : e.target.closest('.view-cabinet-btn');
                const cabinetId = button.getAttribute('data-cabinet-id');

                if (cabinetId) {
                    // Get cabinet number from the table row to use the same API as dashboard
                    const row = button.closest('tr');
                    const cabinetNumber = row.querySelector('td:first-child').textContent;
                    loadViewCabinetData(cabinetNumber);
                }
            }
        });

        // Export Modal functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('export-cabinet-btn') || e.target.closest('.export-cabinet-btn')) {
                const button = e.target.classList.contains('export-cabinet-btn') ? e.target : e.target.closest('.export-cabinet-btn');
                const cabinetId = button.getAttribute('data-cabinet-id');

                if (cabinetId) {
                    // Pre-select the cabinet in the export modal
                    const exportSelect = document.getElementById('export_cabinet');
                    if (exportSelect) {
                        exportSelect.value = cabinetId;
                    }
                }
            }
        });

        // Load cabinet data for view modal (dashboard-style)
        function loadViewCabinetData(cabinetNumber) {
            // Show loading state
            document.getElementById('view-loading-state').style.display = 'block';
            document.getElementById('view-content-container').style.display = 'none';

            // Fetch cabinet data using the same API as dashboard.php
            fetch(`../includes/cabinet_api.php?action=get_cabinet_by_number&cabinet_number=${cabinetNumber}`)
                .then(response => response.json())
                .then(data => {
                    // Hide loading, show content
                    document.getElementById('view-loading-state').style.display = 'none';
                    document.getElementById('view-content-container').style.display = 'block';

                    if (data.success) {
                        const cabinet = data.cabinet;
                        currentCabinetData = cabinet; // Store for delete functionality

                        document.getElementById('view-content-container').innerHTML = `
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <h6 class="text-primary">Cabinet Information</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td><strong>Cabinet Number:</strong></td>
                                            <td>${cabinet.cabinet_number}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Cabinet Name:</strong></td>
                                            <td>${cabinet.name}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td>${new Date(cabinet.created_at).toLocaleDateString()}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Updated:</strong></td>
                                            <td>${new Date(cabinet.updated_at).toLocaleDateString()}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-4 text-center">
                                    ${cabinet.photo_path ? `<img src="../${cabinet.photo_path}" alt="Cabinet Photo" class="img-fluid rounded" style="max-height: 150px;">` : '<div class="bg-light rounded p-3"><i class="fas fa-image fa-3x text-muted"></i><p class="mt-2 mb-0 text-muted">No photo</p></div>'}
                                </div>
                            </div>
                            
                            <h6 class="text-primary">Cabinet Contents</h6>
                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 10px;">
                                ${cabinet.items && cabinet.items.length > 0 ? `
                                    <div class="table-responsive">
                                        <table class="table table-striped table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Item Name</th>
                                                    <th>Category</th>
                                                    <th>Quantity</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${cabinet.items.map(item => `
                                                    <tr>
                                                        <td>${item.name}</td>
                                                        <td><span class="badge bg-secondary">${item.category_name}</span></td>
                                                        <td>${item.quantity}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                ` : `
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p class="mb-0">No items in this cabinet</p>
                                    </div>
                                `}
                            </div>
                            
                            ${cabinet.qr_path ? `
                                <div class="text-center mt-3">
                                    <h6 class="text-primary">QR Code</h6>
                                    <img src="../${cabinet.qr_path}" alt="QR Code" class="img-fluid" style="max-width: 150px;">
                                </div>
                            ` : ''}
                        `;
                    } else {
                        document.getElementById('view-content-container').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Cabinet not found or error loading data.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    // Hide loading, show content
                    document.getElementById('view-loading-state').style.display = 'none';
                    document.getElementById('view-content-container').style.display = 'block';

                    console.error('Error fetching cabinet data:', error);
                    document.getElementById('view-content-container').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading cabinet data. Please try again.
                        </div>
                    `;
                });
        }

        // Delete Cabinet functionality
        const deleteCabinetBtn = document.getElementById('deleteCabinetBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        if (deleteCabinetBtn) {
            deleteCabinetBtn.addEventListener('click', function() {
                if (!currentCabinetData) {
                    alert('No cabinet data available for deletion.');
                    return;
                }

                // Populate delete confirmation modal
                const cabinet = currentCabinetData;
                const deleteDetails = document.getElementById('deleteCabinetDetails');

                deleteDetails.innerHTML = `
                    <div class="row">
                        <div class="col-12">
                            <strong>Cabinet Number:</strong> ${cabinet.cabinet_number}<br>
                            <strong>Cabinet Name:</strong> ${cabinet.name}<br>
                            <strong>Total Items:</strong> ${cabinet.items ? cabinet.items.length : 0} items<br>
                            <strong>Created:</strong> ${new Date(cabinet.created_at).toLocaleDateString()}
                        </div>
                    </div>
                `;

                // Hide the view modal first
                const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewCabinetModal'));
                if (viewModal) {
                    viewModal.hide();
                }
            });
        }

        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                if (!currentCabinetData) {
                    alert('No cabinet data available for deletion.');
                    return;
                }

                const cabinet = currentCabinetData;

                // Show loading state
                const originalBtnText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Deleting...';
                this.disabled = true;

                // Call delete API
                fetch('../includes/cabinet_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete_cabinet',
                            cabinet_id: cabinet.id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Reset button state
                        this.innerHTML = originalBtnText;
                        this.disabled = false;

                        if (data.success) {
                            // Hide delete confirmation modal
                            const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteCabinetModal'));
                            if (deleteModal) {
                                deleteModal.hide();
                            }

                            // Show success message
                            const successMessage = document.getElementById('successMessage');
                            successMessage.textContent = `Cabinet "${cabinet.name}" has been successfully deleted!`;

                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();

                            // Refresh the page after success
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);

                        } else {
                            alert('Error deleting cabinet: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting cabinet:', error);

                        // Reset button state
                        this.innerHTML = originalBtnText;
                        this.disabled = false;

                        alert('Error deleting cabinet. Please try again.');
                    });
            });
        }

        // Edit Cabinet Modal - Add/Remove items functionality
        let editItemCount = 0;

        // Add new item in edit modal
        document.getElementById('add-edit-item').addEventListener('click', function() {
            editItemCount++;
            const container = document.getElementById('edit-items-container');
            const newRow = document.createElement('div');
            newRow.className = 'item-row';

            let categoriesOptions = '<option value="">Select Category</option>';
            window.cabinetCategories.forEach(category => {
                categoriesOptions += '<option value="' + category.id + '">' + category.name + '</option>';
            });

            newRow.innerHTML =
                '<div class="row g-2 mb-2">' +
                '<div class="col-md-4">' +
                '<label class="form-label">Item Name</label>' +
                '<input type="text" class="form-control" name="items[' + editItemCount + '][name]" required>' +
                '</div>' +
                '<div class="col-md-3">' +
                '<label class="form-label">Category</label>' +
                '<select class="form-select" name="items[' + editItemCount + '][category]" required>' +
                categoriesOptions +
                '</select>' +
                '</div>' +
                '<div class="col-md-3">' +
                '<label class="form-label">Quantity</label>' +
                '<input type="number" class="form-control" name="items[' + editItemCount + '][quantity]" value="1" min="1">' +
                '</div>' +
                '<div class="col-md-2 d-flex align-items-end">' +
                '<button type="button" class="btn btn-danger btn-sm remove-edit-item w-100">' +
                '<i class="fas fa-trash"></i>' +
                '</button>' +
                '</div>' +
                '</div>';
            container.appendChild(newRow);
        });

        // Remove item in edit modal
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-edit-item') || e.target.closest('.remove-edit-item')) {
                const itemRow = e.target.closest('.item-row');
                const container = document.getElementById('edit-items-container');
                if (container.children.length > 1) {
                    itemRow.remove();
                } else {
                    alert('At least one item is required!');
                }
            }
        });

        // Edit Cabinet function (dashboard-style)
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-cabinet-btn') || e.target.closest('.edit-cabinet-btn')) {
                const button = e.target.classList.contains('edit-cabinet-btn') ? e.target : e.target.closest('.edit-cabinet-btn');
                const cabinetId = button.getAttribute('data-cabinet-id');

                if (cabinetId) {
                    editCabinet(cabinetId);
                }
            }
        });

        function editCabinet(cabinetId) {
            // Show modal immediately with loading state
            const modal = new bootstrap.Modal(document.getElementById('editCabinetModal'));
            modal.show();

            // Show loading, hide form content
            document.getElementById('edit-loading-state').style.display = 'block';
            document.getElementById('edit-form-content').style.display = 'none';

            // Clear form
            document.getElementById('editCabinetForm').reset();
            document.getElementById('edit-items-container').innerHTML = '';
            const photoPreview = document.getElementById('current-photo-preview');
            if (photoPreview) photoPreview.innerHTML = '';
            editItemCount = 0;

            // Fetch cabinet data
            fetch('../includes/cabinet_api.php?action=get_cabinet&id=' + cabinetId)
                .then(response => response.json())
                .then(data => {
                    // Hide loading, show form content
                    document.getElementById('edit-loading-state').style.display = 'none';
                    document.getElementById('edit-form-content').style.display = 'block';

                    if (data.success) {
                        const cabinet = data.cabinet;
                        const items = data.items;

                        // Populate form fields
                        document.getElementById('edit_cabinet_id').value = cabinet.id;
                        document.getElementById('edit_cabinet_number').value = cabinet.cabinet_number;
                        document.getElementById('edit_name').value = cabinet.name;

                        // Show current photo if exists
                        const photoPreview = document.getElementById('current-photo-preview');
                        if (photoPreview) {
                            if (cabinet.photo_path) {
                                photoPreview.innerHTML = '<small class="text-muted">Current photo:</small><br><img src="../' + cabinet.photo_path + '" alt="Current Photo" style="max-height: 100px;" class="img-thumbnail">';
                            } else {
                                photoPreview.innerHTML = '';
                            }
                        }

                        // Load items
                        if (items && items.length > 0) {
                            items.forEach(function(item) {
                                editItemCount++;
                                const container = document.getElementById('edit-items-container');
                                const newRow = document.createElement('div');
                                newRow.className = 'item-row';

                                let categoriesOptions = '<option value="">Select Category</option>';
                                window.cabinetCategories.forEach(function(category) {
                                    const selected = category.id == item.category_id ? 'selected' : '';
                                    categoriesOptions += '<option value="' + category.id + '" ' + selected + '>' + category.name + '</option>';
                                });

                                newRow.innerHTML =
                                    '<div class="row g-2 mb-2">' +
                                    '<div class="col-md-4">' +
                                    '<label class="form-label">Item Name</label>' +
                                    '<input type="text" class="form-control" name="items[' + editItemCount + '][name]" value="' + item.name + '" required>' +
                                    '<input type="hidden" name="items[' + editItemCount + '][id]" value="' + item.id + '">' +
                                    '</div>' +
                                    '<div class="col-md-3">' +
                                    '<label class="form-label">Category</label>' +
                                    '<select class="form-select" name="items[' + editItemCount + '][category]" required>' +
                                    categoriesOptions +
                                    '</select>' +
                                    '</div>' +
                                    '<div class="col-md-3">' +
                                    '<label class="form-label">Quantity</label>' +
                                    '<input type="number" class="form-control" name="items[' + editItemCount + '][quantity]" value="' + item.quantity + '" min="1">' +
                                    '</div>' +
                                    '<div class="col-md-2 d-flex align-items-end">' +
                                    '<button type="button" class="btn btn-danger btn-sm remove-edit-item w-100">' +
                                    '<i class="fas fa-trash"></i>' +
                                    '</button>' +
                                    '</div>' +
                                    '</div>';
                                container.appendChild(newRow);
                            });
                        } else {
                            // Add one empty item row
                            document.getElementById('add-edit-item').click();
                        }
                    } else {
                        // Hide loading, show form content even for errors
                        document.getElementById('edit-loading-state').style.display = 'none';
                        document.getElementById('edit-form-content').style.display = 'block';
                        alert('Error loading cabinet data: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    // Hide loading, show form content
                    document.getElementById('edit-loading-state').style.display = 'none';
                    document.getElementById('edit-form-content').style.display = 'block';
                    console.error('Error:', error);
                    alert('Error loading cabinet data. Please try again.');
                });
        }

        // Export functionality
        const downloadExportBtn = document.getElementById('downloadExportBtn');
        if (downloadExportBtn) {
            downloadExportBtn.addEventListener('click', function() {
                const form = document.getElementById('exportForm');
                const formData = new FormData(form);

                const cabinetId = formData.get('cabinet_id');
                const format = formData.get('format');

                if (!cabinetId) {
                    alert('Please select a cabinet to export.');
                    return;
                }

                // Show loading overlay
                document.getElementById('export-loading-overlay').style.display = 'block';

                // Create export URL
                const url = `export.php?cabinet_id=${cabinetId}&format=${format}`;

                // Simulate some processing time, then proceed with export
                setTimeout(() => {
                    // Hide loading overlay
                    document.getElementById('export-loading-overlay').style.display = 'none';

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
                    modal.hide();

                    // Small delay to allow modal to close
                    setTimeout(() => {
                        if (format === 'pdf') {
                            // For PDF, open in new window
                            window.open(url, '_blank', 'width=1024,height=768,scrollbars=yes,resizable=yes');
                        } else {
                            // For other formats, create download link
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = `cabinet_export_${Date.now()}.${format}`;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        }
                    }, 200);
                }, 1000); // 1 second loading time for better UX
            });
        }

        // Toggle All Cabinets option visibility based on format selection
        function toggleAllCabinetsOption() {
            const formatPdf = document.getElementById('format_pdf');
            const formatExcel = document.getElementById('format_excel');
            const allCabinetsOption = document.getElementById('all-cabinets-option');
            const exportCabinetSelect = document.getElementById('export_cabinet');

            if (formatPdf && formatExcel && allCabinetsOption) {
                if (formatExcel.checked) {
                    // Excel format - show "All Cabinets" option
                    allCabinetsOption.style.display = 'block';
                } else {
                    // PDF format - hide "All Cabinets" option
                    allCabinetsOption.style.display = 'none';
                    // If "All Cabinets" was selected, reset to default
                    if (exportCabinetSelect.value === 'all') {
                        exportCabinetSelect.value = '';
                    }
                }
            }
        }

        // Export modal format change handlers
        const formatPdf = document.getElementById('format_pdf');
        const formatExcel = document.getElementById('format_excel');

        if (formatPdf && formatExcel) {
            formatPdf.addEventListener('change', toggleAllCabinetsOption);
            formatExcel.addEventListener('change', toggleAllCabinetsOption);

            // Initialize on page load
            toggleAllCabinetsOption();
        }

        // QR Generation function with loading animation
        function generateQR(cabinetId) {
            // Create a modal-like overlay for QR generation using the same structure as edit modal
            const overlay = document.createElement('div');
            overlay.id = 'qr-loading-overlay';
            overlay.className = 'modal fade show';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            overlay.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-body text-center py-5">
                            <video src="../assets/images/Trail-Loading.webm" style="width: 150px; height: 150px; margin: 0 auto; display:block;" autoplay muted loop playsinline><\/video>
                            <h5 class="mt-3 text-muted">Generating QR Code...</h5>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
            document.body.classList.add('modal-open');

            // Make AJAX request to generate QR code
            fetch('../includes/ajax_qr_generate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cabinet_id: cabinetId
                    })
                })
                .then(response => {
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Response is not JSON');
                    }
                    
                    return response.json();
                })
                .then(data => {
                    // Remove loading overlay
                    document.body.removeChild(overlay);
                    document.body.classList.remove('modal-open');

                    if (data.success) {
                        // Show success modal
                        document.getElementById('qrSuccessMessage').textContent =
                            `${data.cabinet_name} (${data.cabinet_number}) QR Code Generated Successfully ✓`;
                        const qrSuccessModal = new bootstrap.Modal(document.getElementById('qrSuccessModal'));
                        qrSuccessModal.show();

                        // Reload the page after success modal is closed to show updated QR status
                        document.getElementById('qrSuccessModal').addEventListener('hidden.bs.modal', function() {
                            location.reload();
                        }, {
                            once: true
                        });
                    } else {
                        // Show error
                        alert('Error generating QR code: ' + data.error);
                    }
                })
                .catch(error => {
                    // Remove loading overlay
                    if (document.getElementById('qr-loading-overlay')) {
                        document.body.removeChild(overlay);
                        document.body.classList.remove('modal-open');
                    }
                    console.error('QR Generation Error:', error);
                    console.error('Error details:', error.message);
                    
                    // Show more specific error message
                    let errorMessage = 'An error occurred while generating QR code. Please try again.';
                    if (error.message.includes('Response is not JSON')) {
                        errorMessage = 'Server returned invalid response. Please check server logs.';
                    } else if (error.message.includes('HTTP error')) {
                        errorMessage = 'Server error occurred. Please try again.';
                    }
                    
                    alert(errorMessage);
                });
        }
    </script>
</body>

</html>