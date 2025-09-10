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
        :root {
            --sidebar-width: 250px;
        }
        body {
            overflow-x: hidden;
        }
        #sidebar {
            min-height: 100vh;
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: all 0.3s;
        }
        #content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
            width: calc(100% - var(--sidebar-width));
        }
        #sidebar.collapsed {
            margin-left: -var(--sidebar-width);
        }
        #content.expanded {
            margin-left: 0;
            width: 100%;
        }
        .sidebar-link {
            color: #adb5bd;
            transition: all 0.3s;
        }
        .sidebar-link:hover, .sidebar-link.active {
            color: #fff;
            background-color: #0d6efd;
        }
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -var(--sidebar-width);
            }
            #sidebar.collapsed {
                margin-left: 0;
            }
            #content {
                margin-left: 0;
                width: 100%;
            }
            #content.expanded {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar" class="bg-dark">
        <div class="p-3">
            <div class="text-center text-white mb-4">
                <i class="fas fa-cabinet-filing fa-2x mb-2"></i>
                <h5>Cabinet System</h5>
            </div>
            <hr class="text-light">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link sidebar-link active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="cabinet.php" class="nav-link sidebar-link">
                        <i class="fas fa-cabinet-filing me-2"></i> Cabinets
                    </a>
                </li>
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <li class="nav-item">
                    <a href="users.php" class="nav-link sidebar-link">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link sidebar-link">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <button id="sidebarToggle" class="btn btn-primary">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand ms-2">Welcome, <?php echo $_SESSION['first_name']; ?>!</span>
                <div class="ms-auto">
                    <a href="index.php" class="btn btn-outline-light">
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
                                    <i class="fas fa-cabinet-filing fa-3x"></i>
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
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('content').classList.toggle('expanded');
        });
    </script>
</body>
</html>