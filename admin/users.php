<?php
// Handle logout POST (AJAX) at the very top before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    require_once '../includes/auth.php';
    $_SESSION = array();
    session_destroy();
    exit;
}

require_once '../includes/auth.php';
require_once '../includes/email_service.php';
authenticate();
authorize(['admin']);

// Additional security check - redirect encoders to dashboard
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. Only administrators can manage users.";
    redirect('dashboard.php');
}

// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $userId = intval($_GET['id']);
        if ($userId != $_SESSION['user_id']) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $_SESSION['success'] = "User deleted successfully!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "You cannot delete your own account!";
        }
        redirect('users.php');
    }
}

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
                $_SESSION['success'] = "User added successfully! Welcome email sent to " . $email;
            } else {
                $_SESSION['success'] = "User added successfully, but email failed to send: " . $emailResult['message'];
                // Still consider this a success since user was created
            }

            redirect('users.php');
        } catch (PDOException $e) {
            $error = "Error adding user: " . $e->getMessage();
        }
    } elseif (isset($_POST['edit_user'])) {
        $userId = intval($_POST['user_id']);
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $office = sanitizeInput($_POST['office']);
        $division = sanitizeInput($_POST['division']);
        $email = sanitizeInput($_POST['email']);
        $mobile = sanitizeInput($_POST['mobile']);
        $username = sanitizeInput($_POST['username']);
        $role = sanitizeInput($_POST['role']);

        try {
            if (!empty($_POST['password'])) {
                $hashedPassword = password_hash(sanitizeInput($_POST['password']), PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users SET first_name=?, last_name=?, office=?, division=?, email=?, mobile=?, username=?, password=?, role=? 
                    WHERE id=?
                ");
                $stmt->execute([$firstName, $lastName, $office, $division, $email, $mobile, $username, $hashedPassword, $role, $userId]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users SET first_name=?, last_name=?, office=?, division=?, email=?, mobile=?, username=?, role=? 
                    WHERE id=?
                ");
                $stmt->execute([$firstName, $lastName, $office, $division, $email, $mobile, $username, $role, $userId]);
            }

            $_SESSION['success'] = "User updated successfully!";
            redirect('users.php');
        } catch (PDOException $e) {
            $error = "Error updating user: " . $e->getMessage();
        }
    }
}

// Get user for editing
$editUser = null;
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $editUserId = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editUserId]);
    $editUser = $stmt->fetch();
}

// Pagination for users
$usersPerPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $usersPerPage;

// Hide current logged-in admin from the user list
// This ensures the admin only sees other users, not themselves
$currentUserId = $_SESSION['user_id'];

// Check if user_id is set
if (!isset($currentUserId) || empty($currentUserId)) {
    error_log("User ID not found in session");
    die("Session error: User not properly authenticated.");
}

try {
    // Check if PDO connection exists
    if (!isset($pdo) || !$pdo) {
        error_log("PDO connection not available");
        die("Database connection error. Please try again later.");
    }
    
    // Get total user count (excluding current logged-in admin)
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE id != ?");
    $countStmt->execute([$currentUserId]);
    $totalUsers = $countStmt->fetch()['total'];
    $totalPages = ceil($totalUsers / $usersPerPage);

    // Get paginated users (excluding current logged-in admin)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id != ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$currentUserId, $usersPerPage, $offset]);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database error in users.php: " . $e->getMessage());
    $error = "Database error occurred. Please try again.";
    $users = [];
    $totalPages = 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/PPRD_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/navbar.css" rel="stylesheet">
    <link href="../assets/css/cabinet.css" rel="stylesheet">
    <style nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        /* Modern Dashboard Design - Matching Login Style */
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

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        #content {
            background: transparent;
        }

        .container-fluid {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            padding: 30px !important;
            max-width: calc(100% - 40px);
        }

        /* Navbar styling to match gradient theme */
        .admin-navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        /* Card improvements */
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
            padding: 15px 20px;
        }

        /* Button improvements */
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(17, 153, 142, 0.3);
        }

        .btn-secondary {
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(108, 117, 125, 0.3);
        }

        .btn-info {
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-info:hover {
            transform: translateY(-2px);
        }

        .btn-danger {
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
        }

        /* Form controls */
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Table improvements */
        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .table thead th {
            background: transparent;
            color: white;
            border: none;
            font-weight: 600;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
            transition: background-color 0.3s ease;
        }

        /* Badge improvements */
        .badge {
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: 500;
        }

        /* Modal improvements */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-bottom: none;
            padding: 25px 25px 15px;
            border-radius: 20px 20px 0 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .modal-footer {
            border-top: none;
            padding: 15px 25px 25px;
        }

        /* Alert improvements */
        .alert {
            border-radius: 15px;
            border: none;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        }

        /* Pagination improvements */
        .btn-outline-secondary {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
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

        /* Add User Button */
        .add-user-btn {
            margin-bottom: 20px;
        }

        /* Action buttons group */
        .btn-group .btn {
            margin-right: 5px;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }
    </style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary admin-navbar">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <button id="sidebarToggle" class="btn btn-outline-light me-2" style="background-color: rgba(255,255,255,0.1); border: 2px solid #000; color: white;">
                        <i class="fas fa-bars text-dark" style="color: #000 !important; text-shadow: none !important;"></i>
                    </button>
                    <span class="navbar-brand d-flex align-items-center mb-0 text-dark">
                        <i class="fa fa-users me-2 text-dark"></i>
                        <span class="ms-2 text-dark">User Management</span>
                    </span>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!
                </h2>
                <div class="badge bg-danger fs-6">Admin Access</div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>


            <!-- Users List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Existing Users</h5>
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
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-info edit-user-btn" 
                                                            data-user='<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>'
                                                            title="Edit User">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <button class="btn btn-sm btn-danger delete-user-btn" 
                                                                data-user-id="<?php echo $user['id']; ?>" 
                                                                data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                title="Delete User">
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

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Users pagination" class="mt-4">
                                <div class="d-flex justify-content-center align-items-center">
                                    <!-- Previous button -->
                                    <?php if ($page > 1): ?>
                                        <a class="btn btn-outline-secondary btn-sm me-3" href="?page=<?php echo $page - 1; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm me-3" disabled>
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Current page indicator -->
                                    <span class="fw-bold"><?php echo $page; ?></span>

                                    <!-- Next button -->
                                    <?php if ($page < $totalPages): ?>
                                        <a class="btn btn-outline-secondary btn-sm ms-3" href="?page=<?php echo $page + 1; ?>">
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
                            Showing <?php echo (($page - 1) * $usersPerPage) + 1; ?>-<?php echo min($page * $usersPerPage, $totalUsers); ?> of <?php echo $totalUsers; ?> users
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No users found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit User
                    </h5>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Office</label>
                                <input type="text" class="form-control" id="edit_office" name="office">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Division</label>
                                <input type="text" class="form-control" id="edit_division" name="division">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile</label>
                                <input type="tel" class="form-control" id="edit_mobile" name="mobile">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" id="edit_username" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select class="form-select" id="edit_role" name="role" required>
                                    <option value="encoder">Encoder</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" name="password">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Leave empty to keep the current password unchanged
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteUserModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
                    <p class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        All data associated with this user will be permanently removed.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <a id="confirmDeleteBtn" href="#" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete User
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <video id="loadingVideo" src="../assets/images/Trail-Loading.webm" style="width: 80px; height: 80px;" autoplay muted loop playsinline></video>
                    <h6 id="loadingMessage" class="mt-3 text-muted">Loading User Details...</h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal (hidden by default) -->
    <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-modal="true" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px 15px 0 0; border-bottom: none;">
                    <h5 class="modal-title text-white fw-bold">
                        <i class="fas fa-sign-out-alt me-2"></i>Confirm Logout
                    </h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-question-circle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <p class="mb-0 text-dark fw-semibold" style="font-size: 1.1rem;">Are you sure you want to logout?</p>
                </div>
                <div class="modal-footer" style="border-top: none; justify-content: center; padding: 1rem 2rem 2rem;">
                    <button type="button" class="btn btn-outline-secondary me-2" id="cancelLogoutBtn" style="border-radius: 8px; padding: 8px 20px;">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmLogoutBtn" style="border-radius: 8px; padding: 8px 20px;">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
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
                    fetch('users.php', {
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

            // Generate random password
            var genBtn = document.getElementById('generatePassword');
            if (genBtn) {
                genBtn.addEventListener('click', function() {
                    const password = generateRandomPassword(12);
                    var pwInput = document.getElementById('password');
                    if (pwInput) pwInput.value = password;
                });
            }

            function generateRandomPassword(length) {
                const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?";
                let password = "";
                for (let i = 0; i < length; i++) {
                    password += charset.charAt(Math.floor(Math.random() * charset.length));
                }
                return password;
            }

            // Toggle password visibility
            var toggleBtn = document.getElementById('togglePassword');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    var passwordInput = document.getElementById('password');
                    if (!passwordInput) return;
                    if (passwordInput.type === "password") {
                        passwordInput.type = "text";
                        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        passwordInput.type = "password";
                        this.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                });
            }

            // Edit and Delete button event delegation for CSP compliance
            document.querySelector('tbody').addEventListener('click', function(e) {
                // Edit
                if (e.target.closest('.edit-user-btn')) {
                    var btn = e.target.closest('.edit-user-btn');
                    var user = JSON.parse(btn.getAttribute('data-user'));
                    editUser(user);
                }
                // Delete
                if (e.target.closest('.delete-user-btn')) {
                    var btn = e.target.closest('.delete-user-btn');
                    var userId = btn.getAttribute('data-user-id');
                    var userName = btn.getAttribute('data-user-name');
                    deleteUser(userId, userName);
                }
            });
        });

        function editUser(user) {
            // Show loading modal
            var loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), {
                backdrop: 'static',
                keyboard: false
            });
            var loadingMessage = document.getElementById('loadingMessage');
            var roleLabel = user.role === 'admin' ? 'Admin' : 'Encoder';
            loadingMessage.textContent = `Loading ${roleLabel} Details...`;
            loadingModal.show();

            setTimeout(function() {
                loadingModal.hide();
                // Fill form fields
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_first_name').value = user.first_name;
                document.getElementById('edit_last_name').value = user.last_name;
                document.getElementById('edit_office').value = user.office || '';
                document.getElementById('edit_division').value = user.division || '';
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_mobile').value = user.mobile || '';
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_role').value = user.role;
                new bootstrap.Modal(document.getElementById('editUserModal')).show();
            }, 1000);
        }

        function deleteUser(userId, userName) {
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('confirmDeleteBtn').href = 'users.php?action=delete&id=' + userId;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }
    </script>
</body>

</html>