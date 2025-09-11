<?php
require_once 'includes/auth.php';
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
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error deleting cabinet: " . $e->getMessage();
        }
        
        redirect('cabinet.php');
    }
    
    if ($action == 'generate_qr' && isset($_GET['id'])) {
        $cabinetId = intval($_GET['id']);
        
        // Generate QR code and save to database
        [$success, $qrPathOrError, $cabinet] = generateAndSaveQRCodeToDB($pdo, $cabinetId);
        
        if ($success) {
            $_SESSION['success'] = "QR Code generated and saved successfully for " . $cabinet['name'] . "!";
            $_SESSION['qr_file'] = $qrPathOrError;
            $_SESSION['qr_cabinet_number'] = $cabinet['cabinet_number'];
            $_SESSION['qr_cabinet_name'] = $cabinet['name'];
        } else {
            $_SESSION['error'] = "Failed to generate QR code: " . $qrPathOrError;
        }
        
        redirect('cabinet.php');
    }
}

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
            $stmt = $pdo->prepare("INSERT INTO cabinets (cabinet_number, name, photo_path) VALUES (?, ?, ?)");
            $stmt->execute([$cabinetNumber, $name, $photoPath]);
            
            $cabinetId = $pdo->lastInsertId();
            
            // Add items
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
            
            $_SESSION['success'] = "Cabinet added successfully!";
            redirect('cabinet.php');
        } catch(PDOException $e) {
            $error = "Error adding cabinet: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['edit_cabinet'])) {
        // Edit existing cabinet
        $cabinetId = intval($_POST['cabinet_id']);
        $name = sanitizeInput($_POST['name']);
        
        // Handle file upload for edit
        $photoPath = $_POST['existing_photo'] ?? null;
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
            $stmt = $pdo->prepare("UPDATE cabinets SET name = ?, photo_path = ? WHERE id = ?");
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
            
            $_SESSION['success'] = "Cabinet updated successfully!";
            redirect('cabinet.php');
        } catch(PDOException $e) {
            $error = "Error updating cabinet: " . $e->getMessage();
        }
    }
}

// Get all cabinets
$stmt = $pdo->query("
    SELECT c.*, COUNT(i.id) as item_count 
    FROM cabinets c 
    LEFT JOIN items i ON c.id = i.cabinet_id 
    GROUP BY c.id 
    ORDER BY c.updated_at DESC
");
$cabinets = $stmt->fetchAll();

// Get categories for dropdown
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Generate cabinet number
$cabinetNumber = generateCabinetNumber($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinets - Cabinet Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <link href="assets/css/cabinet.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary admin-navbar">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <button id="sidebarToggle" class="btn btn-outline-light me-2">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand">
                        <i class="fas fa-archive me-2"></i>Cabinet Management
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
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Add Cabinet Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Add New Cabinet</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cabinet_number" class="form-label">Cabinet Number <span class="text-muted">(Auto Generated)</span></label>
                                <input type="text" class="form-control" id
                                ="cabinet_number" 
                                       name="cabinet_number" value="<?php echo $cabinetNumber; ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">Cabinet Name <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="photo" class="form-label">Cabinet Photo</label>
                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        </div>
                        
                        <h5 class="mt-4 mb-3">Cabinet Contents</h5>
                        
                        <div id="items-container">
                            <div class="item-row">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <div class="row g-2">
                                            <div class="col-md-4 col-sm-6">
                                                <label class="form-label">Item Name</label>
                                                <input type="text" class="form-control" name="items[0][name]" required>
                                            </div>
                                            <div class="col-md-3 col-sm-6">
                                                <label class="form-label">Category</label>
                                                <select class="form-select" name="items[0][category]" required>
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 col-sm-8">
                                                <label class="form-label">Quantity</label>
                                                <input type="number" class="form-control" name="items[0][quantity]" value="1" min="1">
                                            </div>
                                            <div class="col-md-2 col-sm-4 d-flex align-items-end">
                                                <button type="button" class="btn btn-danger remove-item w-100">
                                                    <i class="fas fa-trash me-1"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" id="add-item" class="btn btn-secondary mt-3 w-100">
                            <i class="fas fa-plus me-1"></i> Add Another Item</button>
                        
                        <div class="mt-4">
                            <button type="submit" name="add_cabinet" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Save Cabinet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
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
                                            <?php if (!empty($cabinet['qr_path']) && file_exists($cabinet['qr_path'])): ?>
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
                                                <button type="button" class="btn btn-sm btn-info view-cabinet-btn" 
                                                        data-cabinet-id="<?php echo $cabinet['id']; ?>"
                                                        title="View Cabinet Details"
                                                        data-bs-toggle="tooltip">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success edit-cabinet-btn" 
                                                        data-cabinet-id="<?php echo $cabinet['id']; ?>"
                                                        title="Edit Cabinet"
                                                        data-bs-toggle="tooltip">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="export.php?cabinet_id=<?php echo $cabinet['id']; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="Export & Print"
                                                   data-bs-toggle="tooltip">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="cabinet.php?action=generate_qr&id=<?php echo $cabinet['id']; ?>" 
                                                   class="btn btn-sm btn-secondary" 
                                                   title="Generate QR Code"
                                                   data-bs-toggle="tooltip">
                                                    <i class="fas fa-qrcode"></i>
                                                </a>
                                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                                <button type="button" class="btn btn-sm btn-danger delete-cabinet-btn" 
                                                        data-cabinet-id="<?php echo $cabinet['id']; ?>"
                                                        data-cabinet-name="<?php echo htmlspecialchars($cabinet['name']); ?>"
                                                        title="Delete Cabinet"
                                                        data-bs-toggle="tooltip">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No cabinets found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- View Cabinet Modal -->
    <div class="modal fade" id="viewCabinetModal" tabindex="-1" aria-labelledby="viewCabinetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewCabinetModalLabel">Cabinet Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewCabinetContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Cabinet Modal -->
    <div class="modal fade" id="editCabinetModal" tabindex="-1" aria-labelledby="editCabinetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCabinetModalLabel">Edit Cabinet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCabinetForm" method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="cabinet_id" id="edit_cabinet_id">
                        <input type="hidden" name="existing_photo" id="edit_existing_photo">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_cabinet_number" class="form-label">Cabinet Number</label>
                                <input type="text" class="form-control" id="edit_cabinet_number" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_name" class="form-label">Cabinet Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_photo" class="form-label">Cabinet Photo</label>
                            <input type="file" class="form-control" id="edit_photo" name="photo" accept="image/*">
                            <div id="current_photo_preview" class="mt-2"></div>
                        </div>
                        
                        <h5 class="mt-4 mb-3">Cabinet Contents</h5>
                        
                        <div id="edit-items-container">
                            <!-- Items will be loaded here -->
                        </div>
                        
                        <button type="button" id="edit-add-item" class="btn btn-secondary mt-3">
                            <i class="fas fa-plus me-1"></i> Add Another Item
                        </button>
                        
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" name="edit_cabinet" form="editCabinetForm" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Cabinet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCabinetModal" tabindex="-1" aria-labelledby="deleteCabinetModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCabinetModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the cabinet "<strong id="deleteCabinetName"></strong>"?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone and will delete all items in this cabinet.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete Cabinet
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Display Modal -->
    <?php if (isset($_SESSION['qr_file'])): ?>
    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-qrcode me-2"></i>QR Code Generated Successfully
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 text-center">
                            <h6>Cabinet: <?php echo htmlspecialchars($_SESSION['qr_cabinet_name'] ?? 'Unknown'); ?></h6>
                            <p class="text-muted">Number: <?php echo htmlspecialchars($_SESSION['qr_cabinet_number'] ?? 'Unknown'); ?></p>
                            <div class="border rounded p-3 bg-light">
                                <img src="<?php echo $_SESSION['qr_file']; ?>" alt="QR Code" class="img-fluid" style="max-width: 250px;">
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
                    <a href="export.php?cabinet_id=<?php echo $_GET['id'] ?? ''; ?>" class="btn btn-primary">
                        <i class="fas fa-download me-1"></i>View Full Report
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php 
    unset($_SESSION['qr_file'], $_SESSION['qr_cabinet_number'], $_SESSION['qr_cabinet_name']); 
    endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        // Categories data for JavaScript
        window.cabinetCategories = <?php echo json_encode($categories); ?>;
        
        // Add/remove item rows dynamically
        let itemCount = 1;
        
        // View cabinet function
        function viewCabinet(cabinetId) {
            console.log('viewCabinet called with ID:', cabinetId);
            const url = `cabinet_api.php?action=get_cabinet&id=${cabinetId}`;
            console.log('Fetching URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('API Response:', data);
                    if (data.success) {
                        displayCabinetView(data.cabinet, data.items);
                        const modalElement = document.getElementById('viewCabinetModal');
                        
                        // Ensure any existing backdrop is removed
                        const existingBackdrop = document.querySelector('.modal-backdrop');
                        if (existingBackdrop) {
                            existingBackdrop.remove();
                        }
                        
                        // Reset body state
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                        
                        // Create fresh modal instance
                        const modal = new bootstrap.Modal(modalElement, {
                            backdrop: true,
                            keyboard: true,
                            focus: true
                        });
                        modal.show();
                    } else {
                        alert('Error loading cabinet details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading cabinet details: ' + error.message);
                });
        }
        
        // Edit cabinet function
        function editCabinet(cabinetId) {
            console.log('editCabinet called with ID:', cabinetId);
            const url = `cabinet_api.php?action=get_cabinet&id=${cabinetId}`;
            console.log('Fetching URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('API Response:', data);
                    if (data.success) {
                        populateEditForm(data.cabinet, data.items);
                        const modalElement = document.getElementById('editCabinetModal');
                        
                        // Ensure any existing backdrop is removed
                        const existingBackdrop = document.querySelector('.modal-backdrop');
                        if (existingBackdrop) {
                            existingBackdrop.remove();
                        }
                        
                        // Reset body state
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                        
                        // Create fresh modal instance
                        const modal = new bootstrap.Modal(modalElement, {
                            backdrop: true,
                            keyboard: true,
                            focus: true
                        });
                        modal.show();
                    } else {
                        alert('Error loading cabinet details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading cabinet details: ' + error.message);
                });
        }
        
        // Delete cabinet function
        function deleteCabinet(cabinetId, cabinetName) {
            console.log('deleteCabinet called with ID:', cabinetId, 'Name:', cabinetName);
            document.getElementById('deleteCabinetName').textContent = cabinetName;
            document.getElementById('confirmDeleteBtn').href = `cabinet.php?action=delete&id=${cabinetId}`;
            
            const modalElement = document.getElementById('deleteCabinetModal');
            
            // Ensure any existing backdrop is removed
            const existingBackdrop = document.querySelector('.modal-backdrop');
            if (existingBackdrop) {
                existingBackdrop.remove();
            }
            
            // Reset body state
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();
        }
        
        // Force cleanup function for stuck modals
        function forceCleanupModals() {
            // Remove any lingering backdrops
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.remove();
            });
            
            // Reset body state
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            // Hide any visible modals
            document.querySelectorAll('.modal.show').forEach(modal => {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                modal.style.display = 'none';
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Add event listeners for cabinet actions
            setupCabinetActionListeners();
            
            // Debug: Log if functions are properly defined
            if (typeof viewCabinet === 'function') {
                console.log('viewCabinet function is defined');
            } else {
                console.error('viewCabinet function is not defined');
            }
            
            if (typeof editCabinet === 'function') {
                console.log('editCabinet function is defined');
            } else {
                console.error('editCabinet function is not defined');
            }
            
            // Show QR modal if QR code was generated
            <?php if (isset($_SESSION['qr_file'])): ?>
            const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
            qrModal.show();
            <?php endif; ?>
        });
        
        document.getElementById('add-item').addEventListener('click', function() {
            addItemRow('items-container', itemCount, 'items');
            itemCount++;
        });
        
        document.getElementById('edit-add-item').addEventListener('click', function() {
            addItemRow('edit-items-container', editItemCount, 'items');
            editItemCount++;
        });
        
        function addItemRow(containerId, index, namePrefix) {
            const container = document.getElementById(containerId);
            const newRow = document.createElement('div');
            newRow.className = 'item-row';
            
            let categoriesOptions = '<option value="">Select Category</option>';
            categories.forEach(category => {
                categoriesOptions += `<option value="${category.id}">${category.name}</option>`;
            });
            
            newRow.innerHTML = `
                <div class="row g-2">
                    <div class="col-12">
                        <div class="row g-2">
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label">Item Name</label>
                                <input type="text" class="form-control" name="${namePrefix}[${index}][name]" required>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="${namePrefix}[${index}][category]" required>
                                    ${categoriesOptions}
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-8">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="${namePrefix}[${index}][quantity]" value="1" min="1">
                            </div>
                            <div class="col-md-2 col-sm-4 d-flex align-items-end">
                                <button type="button" class="btn btn-danger remove-item w-100">
                                    <i class="fas fa-trash me-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
            
            // Add event listener to the new remove button
            newRow.querySelector('.remove-item').addEventListener('click', function() {
                newRow.remove();
            });
        }
        
        // Add event listeners to existing remove buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.item-row').remove();
            });
        });
        
        // Setup cabinet action listeners
        function setupCabinetActionListeners() {
            console.log('Setting up cabinet action listeners...');
            
            // View cabinet buttons
            const viewButtons = document.querySelectorAll('.view-cabinet-btn');
            console.log('Found', viewButtons.length, 'view buttons');
            viewButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const cabinetId = this.getAttribute('data-cabinet-id');
                    console.log('View button clicked for cabinet ID:', cabinetId);
                    viewCabinet(cabinetId);
                });
            });
            
            // Edit cabinet buttons
            const editButtons = document.querySelectorAll('.edit-cabinet-btn');
            console.log('Found', editButtons.length, 'edit buttons');
            editButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const cabinetId = this.getAttribute('data-cabinet-id');
                    console.log('Edit button clicked for cabinet ID:', cabinetId);
                    editCabinet(cabinetId);
                });
            });
            
            // Delete cabinet buttons
            const deleteButtons = document.querySelectorAll('.delete-cabinet-btn');
            console.log('Found', deleteButtons.length, 'delete buttons');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const cabinetId = this.getAttribute('data-cabinet-id');
                    const cabinetName = this.getAttribute('data-cabinet-name');
                    console.log('Delete button clicked for cabinet ID:', cabinetId, 'Name:', cabinetName);
                    deleteCabinet(cabinetId, cabinetName);
                });
            });
            
            console.log('Cabinet action listeners setup complete');
        }
        
        function displayCabinetView(cabinet, items) {
            let itemsHtml = '';
            if (items && items.length > 0) {
                itemsHtml = '<h6 class="mt-3">Items:</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Item Name</th><th>Category</th><th>Quantity</th></tr></thead><tbody>';
                items.forEach(item => {
                    itemsHtml += `<tr><td>${item.name}</td><td>${item.category}</td><td>${item.quantity}</td></tr>`;
                });
                itemsHtml += '</tbody></table></div>';
            } else {
                itemsHtml = '<p class="text-muted mt-3">No items in this cabinet.</p>';
            }
            
            let photoHtml = '';
            if (cabinet.photo_path) {
                photoHtml = `<img src="${cabinet.photo_path}" alt="Cabinet Photo" class="img-fluid mb-3" style="max-height: 200px;">`;
            }
            
            let qrHtml = '';
            if (cabinet.qr_path) {
                qrHtml = `
                    <div class="mt-3">
                        <h6><i class="fas fa-qrcode me-2"></i>QR Code</h6>
                        <div class="border rounded p-2 bg-light text-center" style="max-width: 150px;">
                            <img src="${cabinet.qr_path}" alt="QR Code" class="img-fluid" style="max-width: 120px;">
                        </div>
                        <small class="text-muted">Scan to view cabinet details</small>
                    </div>
                `;
            } else {
                qrHtml = `
                    <div class="mt-3">
                        <h6><i class="fas fa-qrcode me-2"></i>QR Code</h6>
                        <span class="badge bg-secondary">Not Generated</span>
                        <br><small class="text-muted">Click "Generate QR Code" to create</small>
                    </div>
                `;
            }
            
            document.getElementById('viewCabinetContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h5>${cabinet.name}</h5>
                        <p><strong>Cabinet Number:</strong> ${cabinet.cabinet_number}</p>
                        <p><strong>Total Items:</strong> ${cabinet.item_count}</p>
                        <p><strong>Last Updated:</strong> ${new Date(cabinet.updated_at).toLocaleDateString()}</p>
                        ${photoHtml}
                        ${qrHtml}
                    </div>
                    <div class="col-md-6">
                        ${itemsHtml}
                    </div>
                </div>
            `;
        }
        
        function populateEditForm(cabinet, items) {
            document.getElementById('edit_cabinet_id').value = cabinet.id;
            document.getElementById('edit_cabinet_number').value = cabinet.cabinet_number;
            document.getElementById('edit_name').value = cabinet.name;
            document.getElementById('edit_existing_photo').value = cabinet.photo_path || '';
            
            // Show current photo if exists
            const photoPreview = document.getElementById('current_photo_preview');
            if (cabinet.photo_path) {
                photoPreview.innerHTML = `<small class="text-muted">Current photo:</small><br><img src="${cabinet.photo_path}" alt="Current Photo" style="max-height: 100px;" class="img-thumbnail">`;
            } else {
                photoPreview.innerHTML = '';
            }
            
            // Clear and populate items
            const container = document.getElementById('edit-items-container');
            container.innerHTML = '';
            editItemCount = 0;
            
            if (items && items.length > 0) {
                items.forEach(item => {
                    const newRow = document.createElement('div');
                    newRow.className = 'item-row';
                    
                    let categoriesOptions = '<option value="">Select Category</option>';
                    categories.forEach(category => {
                        const selected = category.id == item.category_id ? 'selected' : '';
                        categoriesOptions += `<option value="${category.id}" ${selected}>${category.name}</option>`;
                    });
                    
                    newRow.innerHTML = `
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Item Name</label>
                                <input type="text" class="form-control" name="items[${editItemCount}][name]" value="${item.name}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="items[${editItemCount}][category]" required>
                                    ${categoriesOptions}
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="items[${editItemCount}][quantity]" value="${item.quantity}" min="1">
                            </div>
                            <div class="col-md-3 align-self-end">
                                <button type="button" class="btn btn-danger remove-item">
                                    <i class="fas fa-trash me-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    `;
                    container.appendChild(newRow);
                    
                    // Add event listener to the remove button
                    newRow.querySelector('.remove-item').addEventListener('click', function() {
                        newRow.remove();
                    });
                    
                    editItemCount++;
                });
            } else {
                // Add one empty row if no items
                addItemRow('edit-items-container', editItemCount, 'items');
                editItemCount++;
            }
        }

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
                viewModal.addEventListener('hidden.bs.modal', function () {
                    // Clean up modal content when closed
                    document.getElementById('viewCabinetContent').innerHTML = '';
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
                editModal.addEventListener('hidden.bs.modal', function () {
                    // Clean up form when modal is closed
                    document.getElementById('editCabinetForm').reset();
                    document.getElementById('edit-items-container').innerHTML = '';
                    document.getElementById('current_photo_preview').innerHTML = '';
                    editItemCount = 0;
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
                deleteModal.addEventListener('hidden.bs.modal', function () {
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
                setTimeout(() => { clickCount = 0; }, 500);
                
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

    </script>
    <script src="assets/js/cabinet.js"></script>
</body>
</html>