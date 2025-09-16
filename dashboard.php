<?php
require_once 'includes/auth.php';
require_once 'includes/email_service.php';
authenticate();

// Generate CSP nonce for inline scripts
if (!isset($GLOBALS['csp_nonce'])) {
    $GLOBALS['csp_nonce'] = base64_encode(random_bytes(16));
}

// Set Content Security Policy header - Permissive for development
header("Content-Security-Policy: script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; object-src 'none';");

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

// Handle AJAX requests for categories data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'categories_overview') {
    header('Content-Type: application/json');
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;
    
    try {
        // Get total count
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
        $totalRecords = $countStmt->fetch()['total'];
        $totalPages = ceil($totalRecords / $limit);
        
        // Get paginated categories data
        $stmt = $pdo->prepare("
            SELECT cat.name, COUNT(i.id) as item_count 
            FROM categories cat 
            LEFT JOIN items i ON cat.id = i.category_id 
            GROUP BY cat.id, cat.name 
            ORDER BY item_count DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $categories = $stmt->fetchAll();
        
        // Get total items for percentage calculation
        $totalItemsStmt = $pdo->query("SELECT COUNT(*) as total_items FROM items");
        $totalItems = $totalItemsStmt->fetch()['total_items'];
        
        echo json_encode([
            'success' => true,
            'categories' => $categories,
            'total_items' => $totalItems,
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

// Handle AJAX request for adding category
if (isset($_POST['add_category']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    
    $categoryName = sanitizeInput($_POST['category_name']);
    
    if (empty($categoryName)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit;
    }
    
    try {
        // Check if category already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE name = ?");
        $stmt->execute([$categoryName]);
        $exists = $stmt->fetch()['count'] > 0;
        
        if ($exists) {
            echo json_encode(['success' => false, 'message' => 'Category already exists']);
            exit;
        }
        
        // Insert new category
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$categoryName]);
        
        echo json_encode(['success' => true, 'message' => 'Category Added Successfully ✓']);
        exit;
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle AJAX request for adding user
if (isset($_POST['add_user']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    
    // Get and sanitize input data
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $office = sanitizeInput($_POST['office']);
    $division = sanitizeInput($_POST['division']);
    $email = sanitizeInput($_POST['email']);
    $mobile = sanitizeInput($_POST['mobile']);
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    $role = sanitizeInput($_POST['role']);
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($office) || empty($division) || 
        empty($email) || empty($mobile) || empty($username) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $usernameExists = $stmt->fetch()['count'] > 0;
        
        if ($usernameExists) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $emailExists = $stmt->fetch()['count'] > 0;
        
        if ($emailExists) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, office, division, email, mobile, username, password, role) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$firstName, $lastName, $office, $division, $email, $mobile, $username, $hashedPassword, $role]);
        
        // Prepare user data for email
        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'username' => $username,
            'password' => $password, // Send original password, not hashed
            'role' => $role,
            'office' => $office,
            'division' => $division
        ];
        
        // Send welcome email with credentials
        $emailResult = EmailService::sendNewUserEmail($userData);
        
        // Log email activity
        EmailService::logEmailActivity($userData, $emailResult['success'], $emailResult['message']);
        
        if ($emailResult['success']) {
            echo json_encode(['success' => true, 'message' => 'User Added Successfully ✓', 'email_sent' => true]);
        } else {
            echo json_encode(['success' => true, 'message' => 'User Added Successfully ✓ (Email failed to send)', 'email_sent' => false]);
        }
        exit;
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/mobile-enhancements.css" rel="stylesheet">
    <style nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        /* Loading Modal Styling */
        #loadingModal .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        #loadingModal .modal-body {
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        #loadingModal lottie-player {
            margin: 0 auto;
            display: block;
        }
        
        #loadingMessage {
            color: #6c757d;
            font-weight: 500;
            text-align: center;
            margin-top: 15px;
        }
        
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
                    <i class="bi bi-archive-fill me-2"></i>
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
                                        <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#emailSettingsModal">
                                            <i class="fas fa-envelope-open-text me-2"></i>Email Settings
                                        </button>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="index.php" class="btn btn-info w-100" target="_blank">
                                            <i class="fas fa-search me-2"></i>Public Search
                                        </a>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
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
                            <div id="categories-overview-container">
                                <!-- Categories data will be loaded here -->
                            </div>
                            
                            <!-- Add Categories Button -->
                            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'encoder'): ?>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="fas fa-plus me-1"></i>Add Categories
                                </button>
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
                <form id="addUserForm" method="POST" action="dashboard.php">
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
                    <button type="button" class="btn btn-primary" id="downloadExportBtn">
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
                    <button type="button" class="btn btn-danger" id="deleteCabinetBtn" data-bs-toggle="modal" data-bs-target="#deleteCabinetModal">
                        <i class="fas fa-trash me-1"></i> Delete Cabinet
                    </button>
                    <button type="button" class="btn btn-primary" id="editCabinetBtn" data-bs-toggle="modal" data-bs-target="#editCabinetModal">
                        <i class="fas fa-edit me-1"></i> Edit Cabinet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Cabinet Confirmation Modal -->
    <div class="modal fade" id="deleteCabinetModal" tabindex="-1" aria-labelledby="deleteCabinetModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteCabinetModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Cabinet
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body">
                    <lottie-player src="assets/images/Trail loading.json" background="transparent" speed="1" style="width: 80px; height: 80px;" loop autoplay></lottie-player>
                    <h5 id="loadingMessage">Processing...</h5>
                </div>
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

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addCategoryForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCategoryModalLabel">
                            <i class="fas fa-tags me-2"></i>Add New Category
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name <span class="text-danger" style="font-size: 0.85em;">*Required</span></label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required placeholder="Enter category name">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Email Settings Modal -->
    <div class="modal fade" id="emailSettingsModal" tabindex="-1" aria-labelledby="emailSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="emailSettingsModalLabel">
                        <i class="fas fa-envelope-open-text me-2"></i>Email Configuration Settings
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Email Setup Instructions:</strong><br>
                                Configure these settings to automatically send welcome emails to new users with their login credentials.
                            </div>
                            
                            <form id="emailConfigForm">
                                <h6 class="text-primary mb-3">📧 Basic Email Settings</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="from_email" class="form-label">From Email Address</label>
                                        <input type="email" class="form-control" id="from_email" placeholder="admin@yourcompany.com" required>
                                        <small class="text-muted">The email address that sends welcome emails</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="from_name" class="form-label">From Name</label>
                                        <input type="text" class="form-control" id="from_name" placeholder="Cabinet Inventory System" required>
                                        <small class="text-muted">Display name for outgoing emails</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reply_to" class="form-label">Reply-To Email</label>
                                    <input type="email" class="form-control" id="reply_to" placeholder="support@yourcompany.com">
                                    <small class="text-muted">Where replies will go (optional)</small>
                                </div>
                                
                                <h6 class="text-primary mb-3 mt-4">🔧 SMTP Configuration (for reliable delivery)</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_host" class="form-label">SMTP Server</label>
                                        <select class="form-select" id="smtp_host">
                                            <option value="">Select Email Provider</option>
                                            <option value="smtp.gmail.com">Gmail (smtp.gmail.com)</option>
                                            <option value="smtp-mail.outlook.com">Outlook (smtp-mail.outlook.com)</option>
                                            <option value="smtp.yahoo.com">Yahoo (smtp.yahoo.com)</option>
                                            <option value="custom">Custom SMTP Server</option>
                                        </select>
                                        <input type="text" class="form-control mt-2 d-none" id="custom_smtp" placeholder="Enter custom SMTP server">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_port" class="form-label">SMTP Port</label>
                                        <select class="form-select" id="smtp_port">
                                            <option value="587">587 (TLS - Recommended)</option>
                                            <option value="465">465 (SSL)</option>
                                            <option value="25">25 (No Encryption)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_username" class="form-label">SMTP Username</label>
                                        <input type="email" class="form-control" id="smtp_username" placeholder="your-email@gmail.com">
                                        <small class="text-muted">Usually same as your email address</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_password" class="form-label">SMTP Password/App Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="smtp_password" placeholder="Your app password">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                                <i class="fas fa-eye" id="passwordToggle"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Use App Password for Gmail, not your regular password</small>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-question-circle me-1"></i>Quick Setup Guides</h6>
                                </div>
                                <div class="card-body">
                                    <div class="accordion" id="emailGuideAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#gmailGuide">
                                                    📧 Gmail Setup
                                                </button>
                                            </h2>
                                            <div id="gmailGuide" class="accordion-collapse collapse" data-bs-parent="#emailGuideAccordion">
                                                <div class="accordion-body">
                                                    <small>
                                                        <strong>Steps:</strong><br>
                                                        1. Enable 2-factor authentication<br>
                                                        2. Generate App Password<br>
                                                        3. Use App Password here<br>
                                                        4. Server: smtp.gmail.com<br>
                                                        5. Port: 587
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#outlookGuide">
                                                    📧 Outlook Setup
                                                </button>
                                            </h2>
                                            <div id="outlookGuide" class="accordion-collapse collapse" data-bs-parent="#emailGuideAccordion">
                                                <div class="accordion-body">
                                                    <small>
                                                        <strong>Steps:</strong><br>
                                                        1. Server: smtp-mail.outlook.com<br>
                                                        2. Port: 587<br>
                                                        3. Use your email & password<br>
                                                        4. Enable SMTP authentication
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="previewEmail()">
                                            <i class="fas fa-eye me-1"></i>Preview Email Template
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="saveEmailConfig()">
                        <i class="fas fa-save me-1"></i>Save Configuration
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        // Email Configuration Functions
        function loadEmailConfig() {
            // Load current email configuration when modal opens
            $('#emailSettingsModal').on('shown.bs.modal', function () {
                fetch('includes/email_service.php?action=get_config')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.config) {
                            document.getElementById('from_email').value = data.config.from_email || '';
                            document.getElementById('from_name').value = data.config.from_name || '';
                            document.getElementById('reply_to').value = data.config.reply_to || '';
                            document.getElementById('smtp_host').value = data.config.smtp_host || '';
                            document.getElementById('smtp_port').value = data.config.smtp_port || '587';
                            document.getElementById('smtp_username').value = data.config.smtp_username || '';
                            
                            // Handle custom SMTP
                            if (data.config.smtp_host && !['smtp.gmail.com', 'smtp-mail.outlook.com', 'smtp.yahoo.com'].includes(data.config.smtp_host)) {
                                document.getElementById('smtp_host').value = 'custom';
                                document.getElementById('custom_smtp').value = data.config.smtp_host;
                                document.getElementById('custom_smtp').classList.remove('d-none');
                            }
                        }
                    })
                    .catch(error => console.error('Error loading config:', error));
            });
        }
        
        function saveEmailConfig() {
            const form = document.getElementById('emailConfigForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const config = {
                from_email: document.getElementById('from_email').value,
                from_name: document.getElementById('from_name').value,
                reply_to: document.getElementById('reply_to').value,
                smtp_host: document.getElementById('smtp_host').value === 'custom' 
                    ? document.getElementById('custom_smtp').value 
                    : document.getElementById('smtp_host').value,
                smtp_port: document.getElementById('smtp_port').value,
                smtp_username: document.getElementById('smtp_username').value,
                smtp_password: document.getElementById('smtp_password').value
            };
            
            const saveBtn = event.target;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            
            fetch('includes/email_service.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save_config',
                    config: config
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', '✅ Email Configuration Saved Successfully!<br><small>New users will now automatically receive welcome emails with their credentials.</small>');
                    $('#emailSettingsModal').modal('hide');
                } else {
                    showAlert('error', 'Error saving configuration: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Network error occurred while saving configuration.');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Configuration';
            });
        }
        
        function previewEmail() {
            window.open('includes/email_service.php?action=preview', '_blank', 'width=800,height=600');
        }
        
        function togglePassword() {
            const passwordField = document.getElementById('smtp_password');
            const toggleIcon = document.getElementById('passwordToggle');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
        
        // Handle SMTP host selection
        document.getElementById('smtp_host').addEventListener('change', function() {
            const customInput = document.getElementById('custom_smtp');
            if (this.value === 'custom') {
                customInput.classList.remove('d-none');
                customInput.required = true;
            } else {
                customInput.classList.add('d-none');
                customInput.required = false;
                customInput.value = '';
            }
        });
        
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Add to top of modal body or page
            const modalBody = document.querySelector('#emailSettingsModal .modal-body');
            if (modalBody) {
                modalBody.insertAdjacentHTML('afterbegin', alertHTML);
            } else {
                document.body.insertAdjacentHTML('afterbegin', alertHTML);
            }
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
        
        // Initialize email configuration loading
        document.addEventListener('DOMContentLoaded', function() {
            loadEmailConfig();
        });
    </script>
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

            // Export download button handler
            const downloadExportBtn = document.getElementById('downloadExportBtn');
            if (downloadExportBtn) {
                downloadExportBtn.addEventListener('click', downloadExport);
            }

            // Initialize Recent Activity table
            loadRecentActivity();
            
            // Initialize Categories Overview
            loadCategoriesOverview();
            
            // Setup delete cabinet functionality
            setupDeleteCabinet();
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
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-hover" style="min-width: 600px;">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="sortable-header" data-sort="cabinet_number" style="cursor: pointer; white-space: nowrap;">
                                    Cabinet Number ${getSortArrow('cabinet_number', currentSort, currentOrder)}
                                </th>
                                <th scope="col" class="sortable-header" data-sort="name" style="cursor: pointer; white-space: nowrap;">
                                    Name ${getSortArrow('name', currentSort, currentOrder)}
                                </th>
                                <th scope="col" style="white-space: nowrap;">Activity</th>
                                <th scope="col" class="sortable-header" data-sort="updated_at" style="cursor: pointer; white-space: nowrap;">
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

        // Categories Overview functionality
        let currentCategoriesPage = 1;

        function loadCategoriesOverview(page = 1) {
            const container = document.getElementById('categories-overview-container');
            if (!container) return;

            currentCategoriesPage = page;

            // Show loading state
            container.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            fetch(`dashboard.php?ajax=categories_overview&page=${page}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderCategoriesOverview(data.categories, data.total_items);
                        renderCategoriesPagination(data.pagination);
                    } else {
                        console.error('Failed to load categories overview:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading categories overview:', error);
                });
        }

        function renderCategoriesOverview(categories, totalItems) {
            const container = document.getElementById('categories-overview-container');
            
            if (categories.length > 0) {
                let html = '<div class="category-list">';
                
                categories.forEach(category => {
                    const percentage = totalItems > 0 ? (category.item_count / totalItems) * 100 : 0;
                    html += `
                        <div class="category-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold">${category.name}</span>
                                <span class="badge bg-secondary">${category.item_count}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" 
                                     style="width: ${percentage}%"
                                     title="${Math.round(percentage * 10) / 10}%">
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Total: ${totalItems} items across all categories
                        </small>
                    </div>`;
                
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No categories found</p>
                    </div>
                `;
            }
        }

        function renderCategoriesPagination(pagination) {
            const container = document.getElementById('categories-overview-container');
            if (!container || pagination.total_pages <= 1) return;
            
            let paginationHtml = `
                <nav aria-label="Categories pagination" class="mt-3">
                    <div class="d-flex justify-content-center align-items-center">
                        <!-- Previous button -->`;
            
            if (pagination.current_page > 1) {
                paginationHtml += `
                    <a class="btn btn-outline-secondary btn-sm me-3 categories-pagination-link" 
                       href="#" data-page="${pagination.current_page - 1}">
                        <i class="fas fa-chevron-left"></i>
                    </a>`;
            } else {
                paginationHtml += `
                    <button class="btn btn-outline-secondary btn-sm me-3" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>`;
            }
            
            paginationHtml += `
                        <!-- Current page indicator -->
                        <span class="fw-bold">${pagination.current_page}</span>
                        
                        <!-- Next button -->`;
            
            if (pagination.current_page < pagination.total_pages) {
                paginationHtml += `
                    <a class="btn btn-outline-secondary btn-sm ms-3 categories-pagination-link" 
                       href="#" data-page="${pagination.current_page + 1}">
                        <i class="fas fa-chevron-right"></i>
                    </a>`;
            } else {
                paginationHtml += `
                    <button class="btn btn-outline-secondary btn-sm ms-3" disabled>
                        <i class="fas fa-chevron-right"></i>
                    </button>`;
            }
            
            paginationHtml += `
                    </div>
                </nav>`;
            
            container.innerHTML += paginationHtml;
        }

        // Handle categories pagination clicks using event delegation
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('categories-pagination-link')) {
                e.preventDefault();
                const page = parseInt(e.target.getAttribute('data-page'));
                if (page) {
                    loadCategoriesOverview(page);
                }
                return false;
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

        // Delete Cabinet functionality
        function setupDeleteCabinet() {
            const deleteCabinetBtn = document.getElementById('deleteCabinetBtn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            // Handle delete button click from view modal
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
                                <strong>Total Items:</strong> ${cabinet.items.length} items<br>
                                <strong>Created:</strong> ${new Date(cabinet.created_at).toLocaleDateString()}
                            </div>
                        </div>
                    `;                    // Hide the view modal first
                    const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewCabinetModal'));
                    if (viewModal) {
                        viewModal.hide();
                    }
                });
            }
            
            // Handle confirm delete button click
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
                    fetch('cabinet_api.php', {
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
                            
                            // Refresh the activity list
                            setTimeout(() => {
                                loadRecentActivity(currentPage);
                                // Reset current cabinet data
                                currentCabinetData = null;
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
        }

        // Export function
        function downloadExport() {
            console.log('Export function called'); // Debug logging
            const form = document.getElementById('exportForm');
            const formData = new FormData(form);
            
            const cabinetId = formData.get('cabinet_id');
            const format = formData.get('format');
            
            console.log('Cabinet ID:', cabinetId, 'Format:', format); // Debug logging
            
            if (!cabinetId) {
                alert('Please select a cabinet to export.');
                return;
            }
            
            // Create export URL
            const url = `export.php?cabinet_id=${cabinetId}&format=${format}`;
            console.log('Export URL:', url); // Debug logging
            
            // Close modal first to prevent aria-hidden focus issues
            const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
            modal.hide();
            
            // Small delay to allow modal to close properly before opening new window
            setTimeout(() => {
                if (format === 'pdf') {
                    // For PDF, open in new window which will trigger print dialog
                    window.open(url, '_blank', 'width=1024,height=768,scrollbars=yes,resizable=yes');
                    console.log('PDF export opened in new window'); // Debug logging
                } else {
                    // For other formats, create download link
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `cabinet_export_${Date.now()}.${format}`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    console.log('Export download triggered'); // Debug logging
                }
            }, 200);
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

        // Add User Form Handling
        const addUserForm = document.getElementById('addUserForm');
        if (addUserForm) {
            addUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Hide the Add User modal first
                const addUserModal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                addUserModal.hide();
                
                // Show loading modal with custom message
                document.getElementById('loadingMessage').textContent = 'Adding User...';
                const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
                loadingModal.show();
                
                const formData = new FormData();
                formData.append('add_user', 'true');
                formData.append('first_name', document.getElementById('modal_first_name').value);
                formData.append('last_name', document.getElementById('modal_last_name').value);
                formData.append('office', document.getElementById('modal_office').value);
                formData.append('division', document.getElementById('modal_division').value);
                formData.append('email', document.getElementById('modal_email').value);
                formData.append('mobile', document.getElementById('modal_mobile').value);
                formData.append('username', document.getElementById('modal_username').value);
                formData.append('password', document.getElementById('modal_password').value);
                formData.append('role', document.getElementById('modal_role').value);
                
                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Hide loading modal
                    loadingModal.hide();
                    
                    if (data.success) {
                        // Reset form
                        document.getElementById('addUserForm').reset();
                        
                        // Show success modal with custom message
                        document.getElementById('successMessage').textContent = data.message;
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
                        // Show error
                        alert('Error: ' + (data.message || 'Failed to add user'));
                    }
                })
                .catch(error => {
                    console.error('Error adding user:', error);
                    
                    // Hide loading modal
                    loadingModal.hide();
                    
                    alert('Error adding user. Please try again.');
                });
            });
        }

        // Add Category Form Handling
        const addCategoryForm = document.getElementById('addCategoryForm');
        if (addCategoryForm) {
            addCategoryForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Adding...';
                submitBtn.disabled = true;
                
                const formData = new FormData();
                formData.append('add_category', 'true');
                formData.append('category_name', document.getElementById('category_name').value);
                
                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                    
                    if (data.success) {
                        // Close the add category modal
                        const addModal = bootstrap.Modal.getInstance(document.getElementById('addCategoryModal'));
                        addModal.hide();
                        
                        // Reset form
                        document.getElementById('category_name').value = '';
                        
                        // Show success modal with custom message
                        document.getElementById('successMessage').textContent = data.message;
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
                        // Show error
                        alert('Error: ' + (data.message || 'Failed to add category'));
                    }
                })
                .catch(error => {
                    console.error('Error adding category:', error);
                    
                    // Reset button
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                    
                    alert('Error adding category. Please try again.');
                });
            });
        }
        
    </script>
</body>
</html>