<?php
require_once 'includes/auth.php';
authenticate();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cabinet Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Ensure sidebar is hidden on page load */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        #sidebar {
            left: -250px !important;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .progress {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <button id="sidebarToggle" class="btn btn-outline-light me-2">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand">Welcome, <?php echo $_SESSION['first_name']; ?>!</span>
                <div class="ms-auto">
                    <a href="index.php" class="btn btn-outline-light" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i> View Public Site
                    </a>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4">
            <h2 class="mb-4">Dashboard</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Statistics Cards -->
                <div class="col-md-4 mb-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Cabinets</h5>
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM cabinets");
                                    $cabinetCount = $stmt->fetch()['count'];
                                    ?>
                                    <h2 class="mb-0"><?php echo $cabinetCount; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <img src="assets/images/cabinet-icon.svg" alt="Cabinet" style="width: 48px; height: 48px; filter: brightness(0) invert(1);">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Items</h5>
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM items");
                                    $itemCount = $stmt->fetch()['count'];
                                    ?>
                                    <h2 class="mb-0"><?php echo $itemCount; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-boxes fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Categories</h5>
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
                                    $categoryCount = $stmt->fetch()['count'];
                                    ?>
                                    <h2 class="mb-0"><?php echo $categoryCount; ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-tags fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Recent Cabinets -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Recent Cabinets</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $stmt = $pdo->query("
                                SELECT c.*, COUNT(i.id) as item_count 
                                FROM cabinets c 
                                LEFT JOIN items i ON c.id = i.cabinet_id 
                                GROUP BY c.id 
                                ORDER BY c.created_at DESC 
                                LIMIT 5
                            ");
                            $recentCabinets = $stmt->fetchAll();
                            
                            if ($recentCabinets):
                            ?>
                            <div class="list-group">
                                <?php foreach ($recentCabinets as $cabinet): ?>
                                <a href="cabinet.php?action=view&id=<?php echo $cabinet['id']; ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo $cabinet['name']; ?></h6>
                                        <small><?php echo $cabinet['item_count']; ?> items</small>
                                    </div>
                                    <p class="mb-1 text-muted">#<?php echo $cabinet['cabinet_number']; ?></p>
                                    <small>Created: <?php echo date('M j, Y', strtotime($cabinet['created_at'])); ?></small>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                                <p class="text-muted">No cabinets found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Category Distribution -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Items by Category</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $stmt = $pdo->query("
                                SELECT cat.name, COUNT(i.id) as item_count 
                                FROM categories cat 
                                LEFT JOIN items i ON cat.id = i.category_id 
                                GROUP BY cat.id 
                                ORDER BY item_count DESC
                            ");
                            $categoryItems = $stmt->fetchAll();
                            
                            if ($categoryItems):
                            ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Items</th>
                                            <th width="60%">Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $totalItems = array_sum(array_column($categoryItems, 'item_count'));
                                        foreach ($categoryItems as $category): 
                                            $percentage = $totalItems > 0 ? ($category['item_count'] / $totalItems) * 100 : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo $category['name']; ?></td>
                                            <td><?php echo $category['item_count']; ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%;"
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?php echo round($percentage, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <p class="text-muted">No items found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
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
        });
    </script>
</body>
</html>