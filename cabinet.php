<?php
require_once 'includes/auth.php';
authenticate();
authorize(['admin', 'encoder']);

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
}

// Get all cabinets
$stmt = $pdo->query("
    SELECT c.*, COUNT(i.id) as item_count 
    FROM cabinets c 
    LEFT JOIN items i ON c.id = i.cabinet_id 
    GROUP BY c.id 
    ORDER BY c.created_at DESC
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
    <style>
        /* Ensure sidebar is hidden on page load */
        #sidebar {
            left: -250px !important;
        }
        
        .item-row {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <button id="sidebarToggle" class="btn btn-outline-light me-2">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand">Cabinets Management</span>
            </div>
        </nav>
        <div class="container-fluid p-4">
            <h2 class="mb-4">Cabinets</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
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
                                <label for="name" class="form-label">Cabinet Name <span class="text-danger" style="font-size: 0.9em;">*Required</span></label>
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
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Item Name</label>
                                        <input type="text" class="form-control" name="items[0][name]" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="items[0][category]" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control" name="items[0][quantity]" value="1" min="1">
                                    </div>
                                    <div class="col-md-3 align-self-end">
                                        <button type="button" class="btn btn-danger remove-item">
                                            <i class="fas fa-trash me-1"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" id="add-item" class="btn btn-secondary mt-3">
                            <i class="fas fa-plus me-1"></i> Add Another Item</button>
                        
                        <div class="mt-4">
                            <button type="submit" name="add_cabinet" class="btn btn-primary">
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
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cabinets as $cabinet): ?>
                                    <tr>
                                        <td><?php echo $cabinet['cabinet_number']; ?></td>
                                        <td><?php echo $cabinet['name']; ?></td>
                                        <td><?php echo $cabinet['item_count']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($cabinet['created_at'])); ?></td>
                                        <td>
                                            <a href="cabinet.php?action=view&id=<?php echo $cabinet['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="export.php?cabinet_id=<?php echo $cabinet['id']; ?>" 
                                               class="btn btn-sm btn-warning">
                                                <i class="fas fa-qrcode"></i>
                                            </a>
                                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                            <a href="cabinet.php?action=delete&id=<?php echo $cabinet['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this cabinet?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        // Add/remove item rows dynamically
        let itemCount = 1;
        
        document.getElementById('add-item').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const newRow = document.createElement('div');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-control" name="items[${itemCount}][name]" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="items[${itemCount}][category]" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="items[${itemCount}][quantity]" value="1" min="1">
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button type="button" class="btn btn-danger remove-item">
                            <i class="fas fa-trash me-1"></i> Remove
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
            itemCount++;
            
            // Add event listener to the new remove button
            newRow.querySelector('.remove-item').addEventListener('click', function() {
                newRow.remove();
            });
        });
        
        // Add event listeners to existing remove buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.item-row').remove();
            });
        });
    </script>
</body>
</html>