<?php
require_once 'includes/auth.php';
authenticate();

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
    <link href="assets/css/navbar.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
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
                                        <a href="cabinet.php" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Add Cabinet
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="users.php" class="btn btn-success w-100">
                                            <i class="fas fa-user-plus me-2"></i>Add User
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="index.php" class="btn btn-info w-100" target="_blank">
                                            <i class="fas fa-search me-2"></i>Public Search
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="export.php" class="btn btn-secondary w-100">
                                            <i class="fas fa-download me-2"></i>Export Data
                                        </a>
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
                            <?php if (!empty($recentActivity)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm">
                                        <thead>
                                            <tr>
                                                <th>Cabinet Number</th>
                                                <th>Name</th>
                                                <th>Last Updated</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentActivity as $activity): ?>
                                            <tr>
                                                <td>
                                                    <strong class="text-primary"><?php echo $activity['cabinet_number']; ?></strong>
                                                </td>
                                                <td><?php echo $activity['name']; ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y g:i A', strtotime($activity['updated_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <a href="cabinet.php" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No cabinets found. Start by adding your first cabinet!</p>
                                    <a href="cabinet.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Add First Cabinet
                                    </a>
                                </div>
                            <?php endif; ?>
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
        });
    </script>
</body>
</html>