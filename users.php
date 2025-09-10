<?php
require_once 'includes/auth.php';
authenticate();
authorize(['admin']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $office = sanitizeInput($_POST['office']);
        $division = sanitizeInput($_POST['division']);
        $email = sanitizeInput($_POST['email']);
        $mobile = sanitizeInput($_POST['mobile']);
        $username = sanitizeInput($_POST['username']);
        $password = sanitizeInput($_POST['password']);
        $role = sanitizeInput($_POST['role']);
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, office, division, email, mobile, username, password, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$firstName, $lastName, $office, $division, $email, $mobile, $username, $hashedPassword, $role]);
            
            $_SESSION['success'] = "User added successfully!";
            redirect('users.php');
        } catch(PDOException $e) {
            $error = "Error adding user: " . $e->getMessage();
        }
    }
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Cabinet Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Ensure sidebar is hidden on page load */
        #sidebar {
            left: -250px !important;
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
                <span class="navbar-brand">User Management</span>
            </div>
        </nav>
        <div class="container-fluid p-4">
            <h2 class="mb-4">Users</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <!-- Add User Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Add New User</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name <span class="text-danger" style="font-size: 0.9em;">*Required</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger" style="font-size: 0.9em;">*Required</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="office" class="form-label">Office <span class="text-danger" style="font-size: 0.9em;">*Required</span></label>
                                <input type="text" class="form-control" id="office" name="office">
                            </div>
                            <div class="col-md-6">
                                <label for="division" class="form-label">Division <span class="text-danger" style="font-size: 0.9em;">*Required</span></label>
                                <input type="text" class="form-control" id="division" name="division">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger" style="font-size: 0.9em;">*Required</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="mobile" class="form-label">Mobile Number <span class="text-danger" style="font-size: 0.9em;">*Required</span></label>
                                <input type="tel" class="form-control" id="mobile" name="mobile">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username <span class="text-danger" style="font-size: 0.9em;">*Required</span></label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role <span class="text-danger" style="font-size: 0.9em;">*Required</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="encoder">Encoder</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="password" class="form-label">Password <span class="text-danger" style="font-size: 0.9em;">*Required</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">Show</button>
                                </div>
                            </div>
                            <div class="col-md-4 align-self-end">
                                <button type="button" id="generatePassword" class="btn btn-secondary">
                                    <i class="fas fa-key me-1"></i> Generate Password
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i> Add User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Users List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Existing Users</h5>
                </div>
                <div class="card-body">
                    <?php if ($users): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                                        <td><?php echo $user['username']; ?></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this user?')">
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
                        <p class="text-muted">No users found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
document.addEventListener('DOMContentLoaded', function() {
    // Generate random password
    document.getElementById('generatePassword').addEventListener('click', function() {
        const password = generateRandomPassword(12);
        document.getElementById('password').value = password;
    });

    function generateRandomPassword(length) {
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?";
        let password = "";
        for (let i = 0; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        return password;
    }

    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            this.textContent = "Hide";
        } else {
            passwordInput.type = "password";
            this.textContent = "Show";
        }
    });
});
</script>
</body>
</html>