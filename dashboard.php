<?php
require_once 'includes/auth.php';
authenticate();

// Handle AJAX requests for activity data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'recent_activity') {
    header('Content-Type: application/json');
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'updated_at';
    $sortOrder = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
    
    // Validate sort column
    $allowedSorts = ['cabinet_number', 'name', 'updated_at'];
    if (!in_array($sortBy, $allowedSorts)) {
        $sortBy = 'updated_at';
    }
    
    try {
        // Get total count
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM cabinets");
        $totalRecords = $countStmt->fetch()['total'];
        $totalPages = ceil($totalRecords / $limit);
        
        // Get paginated and sorted data
        $stmt = $pdo->prepare("SELECT cabinet_number, name, created_at, updated_at FROM cabinets ORDER BY {$sortBy} {$sortOrder} LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        $activities = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'activities' => $activities,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords
            ]
        ]);
        exit;
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Get dashboard statistics
try {
    // Count cabinets
    $stmt = $pdo->query("SELECT COUNT(*) as total_cabinets FROM cabinets");
    $totalCabinets = $stmt->fetch()['total_cabinets'];
    
    // Count items
    $stmt = $pdo->query("SELECT COUNT(*) as total_items FROM items");
    $totalItems = $stmt->fetch()['total_items'];
    
    // Count categories
    $stmt = $pdo->query("SELECT COUNT(*) as total_categories FROM categories");
    $totalCategories = $stmt->fetch()['total_categories'];
    
    // Count QR codes generated
    $stmt = $pdo->query("SELECT COUNT(*) as qr_generated FROM cabinets WHERE qr_path IS NOT NULL AND qr_path != ''");
    $qrGenerated = $stmt->fetch()['qr_generated'];
    
    // Admin-only statistics
    if ($_SESSION['user_role'] === 'admin') {
        // Count users
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
        $totalUsers = $stmt->fetch()['total_users'];
    }
    
    // Recent activity (last 10 cabinets updated)
    $stmt = $pdo->query("SELECT cabinet_number, name, updated_at FROM cabinets ORDER BY updated_at DESC LIMIT 5");
    $recentActivity = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($_SESSION['user_role']); ?> Dashboard - Cabinet Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <style nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        /* Hide number input spinners/arrows */
        .page-input::-webkit-outer-spin-button,
        .page-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        /* Firefox */
        .page-input[type=number] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
        
        /* Make page input smaller and improve spacing */
        .page-input {
            width: 40px !important;
            height: 30px !important;
            font-size: 0.8rem !important;
            padding: 2px 4px !important;
            border-radius: 4px !important;
        }
        
        /* Adjust pagination spacing */
        .pagination .page-item:not(:first-child) .page-link {
            margin-left: 3px;
        }
        
        .pagination .page-item.active {
            margin: 0 5px;
        }
        
        /* Ensure consistent height for all pagination elements */
        .pagination .page-link {
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary admin-navbar">
            <div class="container-fluid">
                <button id="sidebarToggle" class="btn btn-outline-light">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand">
                    <i class="fas fa-<?php echo $_SESSION['user_role'] === 'admin' ? 'crown' : 'edit'; ?>"></i>
                    <?php echo ucfirst($_SESSION['user_role']); ?> Dashboard
                </span>
                <div class="ms-auto">
                    <a href="index.php" class="btn btn-outline-light" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Public View
                    </a>
                </div>
            </div>
        </nav>
        
        <div class="container-fluid p-4">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Role-based Dashboard Content -->
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <!-- ADMIN DASHBOARD -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!
                    </h2>
                    <div class="badge bg-danger fs-6">Admin Access</div>
                </div>
                
                <!-- Admin Statistics -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $totalCabinets; ?></h4>
                                        <p class="mb-0">Total Cabinets</p>
                                    </div>
                                    <div class="align-self-center">
                                        <img src="assets/images/cabinet-icon.svg" alt="Cabinet" style="width: 48px; height: 48px; filter: brightness(0) invert(1);">
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="cabinet.php" class="text-white text-decoration-none">
                                    <small>Manage Cabinets <i class="fas fa-arrow-right"></i></small>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $totalItems; ?></h4>
                                        <p class="mb-0">Total Items</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-boxes fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="cabinet.php" class="text-white text-decoration-none">
                                    <small>View Items <i class="fas fa-arrow-right"></i></small>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $qrGenerated; ?></h4>
                                        <p class="mb-0">QR Codes</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-qrcode fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="cabinet.php" class="text-white text-decoration-none">
                                    <small>Generate More <i class="fas fa-arrow-right"></i></small>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $totalUsers; ?></h4>
                                        <p class="mb-0">System Users</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="users.php" class="text-white text-decoration-none">
                                    <small>Manage Users <i class="fas fa-arrow-right"></i></small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>Admin Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addCabinetModal">
                                            <i class="fas fa-plus me-2"></i>Add Cabinet
                                        </button>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                            <i class="fas fa-user-plus me-2"></i>Add User
                                        </button>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="index.php" class="btn btn-info w-100" target="_blank">
                                            <i class="fas fa-search me-2"></i>Public Search
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <button type="button" class="btn btn-secondary w-100" data-bs-toggle="modal" data-bs-target="#exportModal">
                                            <i class="fas fa-download me-2"></i>Export Data
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- ENCODER DASHBOARD -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!
                    </h2>
                    <div class="badge bg-primary fs-6">Encoder Access</div>
                </div>
                
                <!-- Encoder Statistics -->
                <div class="row mb-4">
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $totalCabinets; ?></h4>
                                        <p class="mb-0">Cabinets to Manage</p>
                                    </div>
                                    <div class="align-self-center">
                                        <img src="assets/images/cabinet-icon.svg" alt="Cabinet" style="width: 48px; height: 48px; filter: brightness(0) invert(1);">
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="cabinet.php" class="text-white text-decoration-none">
                                    <small>Manage Cabinets <i class="fas fa-arrow-right"></i></small>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $totalItems; ?></h4>
                                        <p class="mb-0">Items to Catalog</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-boxes fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="cabinet.php" class="text-white text-decoration-none">
                                    <small>Add Items <i class="fas fa-arrow-right"></i></small>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $qrGenerated; ?></h4>
                                        <p class="mb-0">QR Codes Ready</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-qrcode fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="cabinet.php" class="text-white text-decoration-none">
                                    <small>Generate QR <i class="fas fa-arrow-right"></i></small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Encoder Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Your Daily Tasks</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <a href="cabinet.php" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Add New Cabinet
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <a href="cabinet.php" class="btn btn-success w-100">
                                            <i class="fas fa-edit me-2"></i>Edit Cabinets
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <a href="index.php" class="btn btn-info w-100" target="_blank">
                                            <i class="fas fa-search me-2"></i>Search & View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Recent Activity Section -->
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                <?php echo $_SESSION['user_role'] === 'admin' ? 'Recent System Activity' : 'Recently Updated Cabinets'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="recent-activity-container">
                                <!-- Activity data will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Categories Overview -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-tags me-2"></i>Categories Overview
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $stmt = $pdo->query("
                                SELECT cat.name, COUNT(i.id) as item_count 
                                FROM categories cat 
                                LEFT JOIN items i ON cat.id = i.category_id 
                                GROUP BY cat.id 
                                ORDER BY item_count DESC
                                LIMIT 5
                            ");
                            $categoryItems = $stmt->fetchAll();
                            
                            if ($categoryItems):
                            ?>
                            <div class="category-list">
                                <?php 
                                $totalCategoryItems = array_sum(array_column($categoryItems, 'item_count'));
                                foreach ($categoryItems as $category): 
                                    $percentage = $totalCategoryItems > 0 ? ($category['item_count'] / $totalCategoryItems) * 100 : 0;
                                ?>
                                <div class="category-item mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold"><?php echo $category['name']; ?></span>
                                        <span class="badge bg-secondary"><?php echo $category['item_count']; ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-primary" 
                                             style="width: <?php echo $percentage; ?>%"
                                             title="<?php echo round($percentage, 1); ?>%">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    Total: <?php echo $totalCategories; ?> categories, <?php echo $totalCategoryItems; ?> items
                                </small>
                            </div>
                            
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No categories found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Cabinet Modal -->
    <div class="modal fade" id="addCabinetModal" tabindex="-1" aria-labelledby="addCabinetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="cabinet.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCabinetModalLabel">
                            <i class="fas fa-plus me-2"></i>Add New Cabinet
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="modal_cabinet_number" class="form-label">Cabinet Number <span class="text-muted">(Auto Generated)</span></label>
                                <input type="text" class="form-control" id="modal_cabinet_number" name="cabinet_number" value="<?php 
                                    // Generate cabinet number
                                    try {
                                        $currentYear = date('Y');
                                        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(cabinet_number, 8) AS UNSIGNED)) as max_number FROM cabinets WHERE cabinet_number LIKE ?");
                                        $stmt->execute(['CAB' . $currentYear . '%']);
                                        $result = $stmt->fetch();
                                        $nextNumber = ($result['max_number'] ?? 0) + 1;
                                        echo 'CAB' . $currentYear . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                                    } catch(Exception $e) {
                                        echo 'CAB' . date('Y') . '0001';
                                    }
                                ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="modal_name" class="form-label">Cabinet Name <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <input type="text" class="form-control" id="modal_name" name="name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modal_photo" class="form-label">Cabinet Photo</label>
                            <input type="file" class="form-control" id="modal_photo" name="photo" accept="image/*">
                        </div>
                        
                        <h6 class="mt-4 mb-3">Cabinet Contents</h6>
                        
                        <div id="modal-items-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 15px; background-color: #f8f9fa;">
                            <div class="item-row">
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Item Name</label>
                                        <input type="text" class="form-control" name="items[0][name]" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="items[0][category]" required>
                                            <option value="">Select Category</option>
                                            <?php 
                                            try {
                                                $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
                                                $categories = $stmt->fetchAll();
                                                foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                                <?php endforeach; 
                                            } catch(Exception $e) { /* ignore */ } 
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control" name="items[0][quantity]" value="1" min="1">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-sm remove-modal-item w-100">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" id="add-modal-item" class="btn btn-secondary btn-sm mt-2">
                            <i class="fas fa-plus me-1"></i> Add Another Item
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_cabinet" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Cabinet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="users.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">
                            <i class="fas fa-user-plus me-2"></i>Add New User
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="modal_first_name" class="form-label">First Name <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <input type="text" class="form-control" id="modal_first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="modal_last_name" class="form-label">Last Name <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <input type="text" class="form-control" id="modal_last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="modal_office" class="form-label">Office <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <input type="text" class="form-control" id="modal_office" name="office" required>
                            </div>
                            <div class="col-md-6">
                                <label for="modal_division" class="form-label">Division <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <input type="text" class="form-control" id="modal_division" name="division" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="modal_email" class="form-label">Email Address <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <input type="email" class="form-control" id="modal_email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="modal_mobile" class="form-label">Mobile Number <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <input type="tel" class="form-control" id="modal_mobile" name="mobile" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="modal_username" class="form-label">Username <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <input type="text" class="form-control" id="modal_username" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label for="modal_role" class="form-label">Role <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <select class="form-select" id="modal_role" name="role" required>
                                    <option value="encoder">Encoder</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="modal_password" class="form-label">Password <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                                <div class="row g-2">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="modal_password" name="password" required>
                                            <button type="button" class="btn btn-outline-secondary" id="modalTogglePassword">Show</button>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" id="modalGeneratePassword" class="btn btn-secondary w-100">
                                            <i class="fas fa-key me-1"></i> Generate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-success">
                            <i class="fas fa-user-plus me-1"></i> Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Export Data Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">
                        <i class="fas fa-download me-2"></i>Export Cabinet Data
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="exportForm">
                        <div class="mb-3">
                            <label for="export_cabinet" class="form-label">Select Cabinet to Export</label>
                            <select class="form-select" id="export_cabinet" name="cabinet_id" required>
                                <option value="">Choose a cabinet...</option>
                                <option value="all" id="all-cabinets-option">All Cabinets</option>
                                <?php 
                                try {
                                    $stmt = $pdo->query("SELECT id, cabinet_number, name FROM cabinets ORDER BY cabinet_number");
                                    $cabinets = $stmt->fetchAll();
                                    foreach ($cabinets as $cabinet): ?>
                                        <option value="<?php echo $cabinet['id']; ?>">
                                            <?php echo $cabinet['cabinet_number'] . ' - ' . $cabinet['name']; ?>
                                        </option>
                                    <?php endforeach; 
                                } catch(Exception $e) { /* ignore */ } 
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
                    <button type="button" class="btn btn-primary" onclick="downloadExport()">
                        <i class="fas fa-download me-1"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Cabinet Modal -->
    <div class="modal fade" id="viewCabinetModal" tabindex="-1" aria-labelledby="viewCabinetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewCabinetModalLabel">
                        <i class="fas fa-eye me-2"></i>Cabinet Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="viewCabinetContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editCabinetBtn" data-bs-toggle="modal" data-bs-target="#editCabinetModal">
                        <i class="fas fa-edit me-1"></i> Edit Cabinet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Cabinet Modal -->
    <div class="modal fade" id="editCabinetModal" tabindex="-1" aria-labelledby="editCabinetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editCabinetForm" method="POST" action="cabinet.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCabinetModalLabel">
                            <i class="fas fa-edit me-2"></i>Edit Cabinet
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
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

    <!-- Success Message Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
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

    <!-- Error Message Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                    </div>
                    <h5 class="mb-0">Update Failed!</h5>
                    <p class="text-muted mb-0">Please try again.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        // Toggle sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            
            if (sidebarToggle && sidebar && content) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    content.classList.toggle('expanded');
                });
            }

            // Add Cabinet Modal - Add/Remove items functionality
            let modalItemCount = 0;

            // Add new item in modal
            document.getElementById('add-modal-item').addEventListener('click', function() {
                modalItemCount++;
                const container = document.getElementById('modal-items-container');
                const newRow = document.createElement('div');
                newRow.className = 'item-row';
                newRow.innerHTML = `
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="items[${modalItemCount}][name]" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="items[${modalItemCount}][category]" required>
                                <option value="">Select Category</option>
                                <?php 
                                try {
                                    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
                                    $modalCategories = $stmt->fetchAll();
                                    foreach ($modalCategories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                    <?php endforeach; 
                                } catch(Exception $e) { /* ignore */ } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="items[${modalItemCount}][quantity]" value="1" min="1">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm remove-modal-item w-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(newRow);
            });

            // Remove item in modal
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-modal-item') || e.target.closest('.remove-modal-item')) {
                    const itemRow = e.target.closest('.item-row');
                    const container = document.getElementById('modal-items-container');
                    if (container.children.length > 1) {
                        itemRow.remove();
                    } else {
                        alert('At least one item is required!');
                    }
                }
            });

            // Add User Modal - Password functionality
            const modalTogglePassword = document.getElementById('modalTogglePassword');
            const modalPasswordField = document.getElementById('modal_password');
            
            if (modalTogglePassword && modalPasswordField) {
                modalTogglePassword.addEventListener('click', function() {
                    const type = modalPasswordField.type === 'password' ? 'text' : 'password';
                    modalPasswordField.type = type;
                    this.textContent = type === 'password' ? 'Show' : 'Hide';
                });
            }

            const modalGeneratePassword = document.getElementById('modalGeneratePassword');
            if (modalGeneratePassword) {
                modalGeneratePassword.addEventListener('click', function() {
                    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
                    let password = '';
                    for (let i = 0; i < 12; i++) {
                        password += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    modalPasswordField.value = password;
                    // Keep password field as password type (hidden)
                    modalPasswordField.type = 'password';
                    modalTogglePassword.textContent = 'Show';
                });
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

            // Initialize Recent Activity table
            loadRecentActivity();
        });

        // Recent Activity table functionality
        let currentSort = 'updated_at';
        let currentOrder = 'desc';
        let currentPage = 1;

        function loadRecentActivity(page = 1, sort = 'updated_at', order = 'desc') {
            currentPage = page;
            currentSort = sort;
            currentOrder = order;

            fetch(`dashboard.php?ajax=recent_activity&page=${page}&sort=${sort}&order=${order}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderRecentActivityTable(data.activities, sort, order);
                        renderPaginationControls(data.pagination);
                    } else {
                        console.error('Failed to load recent activity:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading recent activity:', error);
                });
        }

        function renderRecentActivityTable(activities, currentSort, currentOrder) {
            const container = document.getElementById('recent-activity-container');
            if (!container) return;

            let tableHtml = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="sortable-header" data-sort="cabinet_number" style="cursor: pointer;">
                                    Cabinet Number ${getSortArrow('cabinet_number', currentSort, currentOrder)}
                                </th>
                                <th scope="col" class="sortable-header" data-sort="name" style="cursor: pointer;">
                                    Name ${getSortArrow('name', currentSort, currentOrder)}
                                </th>
                                <th scope="col">Activity</th>
                                <th scope="col" class="sortable-header" data-sort="updated_at" style="cursor: pointer;">
                                    Date Updated ${getSortArrow('updated_at', currentSort, currentOrder)}
                                </th>
                            </tr>
                        </thead>
                        <tbody>`;

            if (activities.length > 0) {
                activities.forEach(activity => {
                    const activityText = getActivityText(activity);
                    tableHtml += `
                        <tr>
                            <td class="fw-bold text-primary">
                                <button type="button" class="btn btn-link fw-bold text-primary p-0 view-cabinet-btn" 
                                        data-cabinet-number="${activity.cabinet_number}" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewCabinetModal">
                                    ${activity.cabinet_number}
                                </button>
                            </td>
                            <td>${activity.name}</td>
                            <td>${activityText}</td>
                            <td>${new Date(activity.updated_at).toLocaleDateString()}</td>
                        </tr>`;
                });
            } else {
                tableHtml += `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            No recent activity found
                        </td>
                    </tr>`;
            }

            tableHtml += `
                        </tbody>
                    </table>
                </div>`;

            container.innerHTML = tableHtml;
        }

        function getSortArrow(column, currentSort, currentOrder) {
            if (currentSort !== column) {
                return '<i class="bi bi-arrow-up-down text-muted"></i>';
            }
            return currentOrder === 'asc' 
                ? '<i class="bi bi-arrow-up text-primary"></i>' 
                : '<i class="bi bi-arrow-down text-primary"></i>';
        }

        function toggleSort(column) {
            let newOrder = 'desc';
            if (currentSort === column && currentOrder === 'desc') {
                newOrder = 'asc';
            }
            loadRecentActivity(1, column, newOrder); // Reset to page 1 when sorting
        }

        function getActivityText(activity) {
            if (activity.created_at === activity.updated_at) {
                return '<span class="badge bg-success">Created</span>';
            } else {
                return '<span class="badge bg-info">Updated</span>';
            }
        }

        function renderPaginationControls(pagination) {
            const container = document.getElementById('recent-activity-container');
            if (!container) return;

            if (pagination.total_pages <= 1) return; // No pagination needed

            let paginationHtml = `
                <nav aria-label="Recent Activity pagination" class="mt-3">
                    <ul class="pagination pagination-sm justify-content-center mb-0">`;

            // Previous page
            if (pagination.current_page > 1) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link pagination-link" href="#" data-page="${pagination.current_page - 1}" data-sort="${currentSort}" data-order="${currentOrder}">&lt;</a>
                    </li>`;
            }

            // Skip 5 pages backward (only show if current page > 5 AND total pages >= 5)
            if (pagination.current_page > 5 && pagination.total_pages >= 5) {
                const skipBackPage = Math.max(1, pagination.current_page - 5);
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link pagination-link" href="#" data-page="${skipBackPage}" data-sort="${currentSort}" data-order="${currentOrder}">&lt;&lt;</a>
                    </li>`;
            }

            // Current page input
            paginationHtml += `
                <li class="page-item active">
                    <input type="number" class="form-control form-control-sm text-center page-input" 
                           value="${pagination.current_page}" 
                           min="1" max="${pagination.total_pages}" 
                           style="border: none;"
                           data-max-pages="${pagination.total_pages}">
                </li>`;

            // Skip 5 pages forward (only show if we can skip 5 pages AND total pages >= 5)
            if (pagination.current_page + 5 <= pagination.total_pages && pagination.total_pages >= 5) {
                const skipForwardPage = Math.min(pagination.total_pages, pagination.current_page + 5);
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link pagination-link" href="#" data-page="${skipForwardPage}" data-sort="${currentSort}" data-order="${currentOrder}">&gt;&gt;</a>
                    </li>`;
            }

            // Next page
            if (pagination.current_page < pagination.total_pages) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link pagination-link" href="#" data-page="${pagination.current_page + 1}" data-sort="${currentSort}" data-order="${currentOrder}">&gt;</a>
                    </li>`;
            }

            paginationHtml += `
                    </ul>
                </nav>`;

            container.innerHTML += paginationHtml;
        }

        // Handle view cabinet button clicks using event delegation
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('view-cabinet-btn') || e.target.closest('.view-cabinet-btn')) {
                const button = e.target.classList.contains('view-cabinet-btn') ? e.target : e.target.closest('.view-cabinet-btn');
                const cabinetNumber = button.getAttribute('data-cabinet-number');
                if (cabinetNumber) {
                    viewCabinet(cabinetNumber);
                }
            }
        });

        // Handle sorting header clicks using event delegation
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('sortable-header') || e.target.closest('.sortable-header')) {
                const header = e.target.classList.contains('sortable-header') ? e.target : e.target.closest('.sortable-header');
                const sortColumn = header.getAttribute('data-sort');
                if (sortColumn) {
                    toggleSort(sortColumn);
                }
            }
        });

        // Handle pagination clicks using event delegation
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('pagination-link')) {
                e.preventDefault();
                const page = parseInt(e.target.getAttribute('data-page'));
                const sort = e.target.getAttribute('data-sort');
                const order = e.target.getAttribute('data-order');
                if (page && sort && order) {
                    loadRecentActivity(page, sort, order);
                }
                return false;
            }
        });

        // Handle page input changes using event delegation
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('page-input')) {
                const page = parseInt(e.target.value);
                const maxPages = parseInt(e.target.getAttribute('data-max-pages'));
                if (page >= 1 && page <= maxPages) {
                    loadRecentActivity(page, currentSort, currentOrder);
                }
            }
        });

        // Handle page input Enter key using event delegation
        document.addEventListener('keypress', function(e) {
            if (e.target.classList.contains('page-input') && e.key === 'Enter') {
                const page = parseInt(e.target.value);
                const maxPages = parseInt(e.target.getAttribute('data-max-pages'));
                if (page >= 1 && page <= maxPages) {
                    loadRecentActivity(page, currentSort, currentOrder);
                }
            }
        });

        // Handle edit cabinet button click
        document.getElementById('editCabinetBtn').addEventListener('click', function() {
            loadEditCabinet();
        });

        // Handle photo file selection in edit modal
        document.getElementById('edit_photo').addEventListener('change', function(e) {
                const file = e.target.files[0];
                const photoPreview = document.getElementById('current-photo-preview');
                const currentPhotoText = document.getElementById('current-photo-text');
                
                if (file) {
                    // Show preview of new selected file
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        photoPreview.innerHTML = `
                            <div class="text-center">
                                <small class="text-info d-block mb-2">New photo selected:</small>
                                <img src="${e.target.result}" alt="New Photo Preview" style="max-height: 100px;" class="img-thumbnail mb-2">
                                <div class="small text-primary">
                                    <i class="fas fa-file-image me-1"></i>
                                    ${file.name}
                                </div>
                            </div>
                        `;
                    };
                    reader.readAsDataURL(file);
                    
                    currentPhotoText.innerHTML = `
                        <i class="fas fa-check-circle text-success me-1"></i>
                        New photo selected: <strong>${file.name}</strong> - This will replace the current photo
                    `;
                } else {
                    // Reset to show current photo if file selection is cleared
                    if (currentCabinetData && currentCabinetData.photo_path) {
                        const photoPath = currentCabinetData.photo_path;
                        const photoFileName = photoPath.split('/').pop();
                        photoPreview.innerHTML = `
                            <div class="text-center">
                                <small class="text-muted d-block mb-2">Current photo:</small>
                                <img src="${photoPath}" alt="Current Photo" style="max-height: 100px;" class="img-thumbnail mb-2">
                                <div class="small text-success">
                                    <i class="fas fa-file-image me-1"></i>
                                    ${photoFileName}
                                </div>
                            </div>
                        `;
                        currentPhotoText.innerHTML = `
                            <i class="fas fa-info-circle me-1"></i>
                            Current: <strong>${photoFileName}</strong> - Leave empty to keep current photo
                        `;
                    }
                }
            });

        // Handle edit cabinet form submission
        document.getElementById('editCabinetForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('edit_cabinet', '1'); // Ensure the edit_cabinet flag is set
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
                submitBtn.disabled = true;
                
                fetch('cabinet.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Reset button first
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                    
                    if (response.ok) {
                        // Close edit modal
                        const editModal = bootstrap.Modal.getInstance(document.getElementById('editCabinetModal'));
                        editModal.hide();
                        
                        // Show success modal with edit message
                        const successMessage = document.getElementById('successMessage');
                        successMessage.textContent = 'Cabinet Updated Successfully ✔';
                        
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        
                        // Refresh the page after success modal is closed
                        const successModalElement = document.getElementById('successModal');
                        const refreshHandler = function() {
                            window.location.reload();
                            successModalElement.removeEventListener('hidden.bs.modal', refreshHandler);
                        };
                        successModalElement.addEventListener('hidden.bs.modal', refreshHandler);
                        
                    } else {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                })
                .catch(error => {
                    console.error('Error updating cabinet:', error);
                    
                    // Reset button
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                    
                    // Show error modal
                    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    errorModal.show();
                });
            });

        // Handle add cabinet form submission
        document.getElementById('addCabinetModal').querySelector('form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('add_cabinet', '1'); // Ensure the add_cabinet flag is set
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
                submitBtn.disabled = true;
                
                fetch('cabinet.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Reset button first
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                    
                    if (response.ok) {
                        // Close add modal
                        const addModal = bootstrap.Modal.getInstance(document.getElementById('addCabinetModal'));
                        addModal.hide();
                        
                        // Show success modal with add message
                        const successMessage = document.getElementById('successMessage');
                        successMessage.textContent = 'Cabinet Added Successfully ✔';
                        
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        
                        // Reset the form
                        this.reset();
                        // Reset items container to one item
                        const container = document.getElementById('modal-items-container');
                        const firstItem = container.querySelector('.item-row');
                        container.innerHTML = '';
                        container.appendChild(firstItem);
                        firstItem.querySelector('input[name*="[name]"]').value = '';
                        firstItem.querySelector('select[name*="[category]"]').value = '';
                        firstItem.querySelector('input[name*="[quantity]"]').value = '1';
                        modalItemCount = 0;
                        
                        // Refresh the page after success modal is closed
                        const successModalElement = document.getElementById('successModal');
                        const refreshHandler = function() {
                            window.location.reload();
                            successModalElement.removeEventListener('hidden.bs.modal', refreshHandler);
                        };
                        successModalElement.addEventListener('hidden.bs.modal', refreshHandler);
                        
                    } else {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                })
                .catch(error => {
                    console.error('Error adding cabinet:', error);
                    
                    // Reset button
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                    
                    // Show error modal
                    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    errorModal.show();
                });
            });

        // Edit Cabinet Modal - Add/Remove items functionality
        let editItemCount = 0;

        // Add new item in edit modal
        document.getElementById('add-edit-item').addEventListener('click', function() {
                editItemCount++;
                const container = document.getElementById('edit-items-container');
                const newRow = document.createElement('div');
                newRow.className = 'item-row';
                newRow.innerHTML = `
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="items[${editItemCount}][name]" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="items[${editItemCount}][category]" required>
                                <option value="">Select Category</option>
                                <?php 
                                try {
                                    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
                                    $editCategories = $stmt->fetchAll();
                                    foreach ($editCategories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                    <?php endforeach; 
                                } catch(Exception $e) { /* ignore */ } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="items[${editItemCount}][quantity]" value="1" min="1">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm remove-edit-item w-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
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

        // View Cabinet function
        let currentCabinetData = null; // Store current cabinet data for editing

        function viewCabinet(cabinetNumber) {
            const content = document.getElementById('viewCabinetContent');
            
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            // Fetch cabinet data
            fetch(`cabinet_api.php?action=get_cabinet_by_number&cabinet_number=${cabinetNumber}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cabinet = data.cabinet;
                        currentCabinetData = cabinet; // Store for editing
                        
                        content.innerHTML = `
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
                                            <td><strong>Location:</strong></td>
                                            <td>${cabinet.location || 'Not specified'}</td>
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
                                    ${cabinet.photo_path ? `<img src="${cabinet.photo_path}" alt="Cabinet Photo" class="img-fluid rounded" style="max-height: 150px;">` : '<div class="bg-light rounded p-3"><i class="fas fa-image fa-3x text-muted"></i><p class="mt-2 mb-0 text-muted">No photo</p></div>'}
                                </div>
                            </div>
                            
                            <h6 class="text-primary">Cabinet Contents</h6>
                            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 10px;">
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
                            </div>
                            
                            ${cabinet.qr_path ? `
                                <div class="text-center mt-3">
                                    <h6 class="text-primary">QR Code</h6>
                                    <img src="${cabinet.qr_path}" alt="QR Code" class="img-fluid" style="max-width: 150px;">
                                </div>
                            ` : ''}
                        `;
                    } else {
                        content.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Cabinet not found or error loading data.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching cabinet data:', error);
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading cabinet data. Please try again.
                        </div>
                    `;
                });
        }

        // Load Edit Cabinet function
        function loadEditCabinet() {
            if (!currentCabinetData) {
                alert('No cabinet data available for editing.');
                return;
            }

            const cabinet = currentCabinetData;
            
            // Populate edit form
            document.getElementById('edit_cabinet_id').value = cabinet.id;
            document.getElementById('edit_cabinet_number').value = cabinet.cabinet_number;
            document.getElementById('edit_name').value = cabinet.name;
            
            // Handle photo preview
            const photoPreview = document.getElementById('current-photo-preview');
            const currentPhotoText = document.getElementById('current-photo-text');
            
            if (cabinet.photo_path) {
                // Extract filename from path
                const photoPath = cabinet.photo_path;
                const photoFileName = photoPath.split('/').pop();
                photoPreview.innerHTML = `
                    <div class="text-center">
                        <small class="text-muted d-block mb-2">Current photo:</small>
                        <img src="${photoPath}" alt="Current Photo" style="max-height: 100px;" class="img-thumbnail mb-2">
                        <div class="small text-success">
                            <i class="fas fa-file-image me-1"></i>
                            ${photoFileName}
                        </div>
                    </div>
                `;
                currentPhotoText.innerHTML = `
                    <i class="fas fa-info-circle me-1"></i>
                    Current: <strong>${photoFileName}</strong> - Leave empty to keep current photo
                `;
            } else {
                photoPreview.innerHTML = `
                    <div class="text-center">
                        <div class="bg-light rounded p-3">
                            <i class="fas fa-image fa-2x text-muted"></i>
                            <p class="mt-2 mb-0 small text-muted">No current photo</p>
                        </div>
                    </div>
                `;
                currentPhotoText.innerHTML = `
                    <i class="fas fa-info-circle me-1"></i>
                    No current photo - Select a file to add one
                `;
            }
            
            // Clear and populate items
            const container = document.getElementById('edit-items-container');
            container.innerHTML = '';
            editItemCount = 0;
            
            cabinet.items.forEach((item, index) => {
                const newRow = document.createElement('div');
                newRow.className = 'item-row';
                newRow.innerHTML = `
                    <div class="row g-2 mb-2">
                        <input type="hidden" name="items[${index}][id]" value="${item.id}">
                        <div class="col-md-4">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="items[${index}][name]" value="${item.name}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="items[${index}][category]" required>
                                <option value="">Select Category</option>
                                <?php 
                                try {
                                    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
                                    $allCategories = $stmt->fetchAll();
                                    foreach ($allCategories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">${item.category_id == <?php echo $category['id']; ?> ? 'selected' : ''}><?php echo $category['name']; ?></option>
                                    <?php endforeach; 
                                } catch(Exception $e) { /* ignore */ } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="items[${index}][quantity]" value="${item.quantity}" min="1">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm remove-edit-item w-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(newRow);
                
                // Set the selected category
                const categorySelect = newRow.querySelector('select[name*="[category]"]');
                categorySelect.value = item.category_id;
                
                editItemCount = index + 1;
            });
        }

        // Export function
        function downloadExport() {
            const form = document.getElementById('exportForm');
            const formData = new FormData(form);
            
            const cabinetId = formData.get('cabinet_id');
            const format = formData.get('format');
            
            if (!cabinetId) {
                alert('Please select a cabinet to export.');
                return;
            }
            
            // Create download URL
            const url = `export.php?cabinet_id=${cabinetId}&format=${format}`;
            
            // Create temporary link and trigger download
            const link = document.createElement('a');
            link.href = url;
            link.download = `cabinet_export_${Date.now()}.${format}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
            modal.hide();
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
    </script>
    });
</body>
</html>