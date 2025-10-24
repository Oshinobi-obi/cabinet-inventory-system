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

// Set CSP nonce and a permissive CSP header (align with dashboard)
if (!isset($GLOBALS['csp_nonce'])) {
    $GLOBALS['csp_nonce'] = base64_encode(random_bytes(16));
}
header("Content-Security-Policy: script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; object-src 'none';");

// Allow both roles to access profile; but page is mainly for encoders
$errors = [];
$success = null;

// Load current user info
$userId = $_SESSION['user_id'];
// Fetch user info for display (try to include password_changed_at)
try {
    $stmt = $pdo->prepare('SELECT username, email, first_name, last_name, password_changed_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    // Fallback if column doesn't exist yet
    $stmt = $pdo->prepare('SELECT username, email, first_name, last_name FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $user['password_changed_at'] = null;
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine if this is an AJAX submission
    $isAjax = (isset($_POST['ajax']) && $_POST['ajax'] === '1') ||
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    // Validate CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid session token. Please refresh and try again.';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
    } else {
        $new = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $strength = validatePasswordStrength($new);
            if (!$strength['valid']) {
                $errors[] = $strength['message'];
            }
        }

        if (!$errors) {
            // Update password and set password_changed_at timestamp
            $newHash = password_hash($new, PASSWORD_BCRYPT);
            try {
                $upd = $pdo->prepare('UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?');
                $upd->execute([$newHash, $userId]);
            } catch (Exception $e) {
                // If column doesn't exist yet, fallback to updating only password
                $upd = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $upd->execute([$newHash, $userId]);
            }
            $success = 'Your password has been updated successfully.';
            // Mark as changed in local copy for immediate UI updates
            $user['password_changed_at'] = date('Y-m-d H:i:s');
        }
        if ($isAjax) {
            header('Content-Type: application/json');
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
            } else {
                echo json_encode(['success' => true, 'message' => $success]);
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
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

        .form-control:disabled {
            background-color: #f8f9fa;
            border-color: #e9ecef;
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

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
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

        /* Password criteria styling */
        #passwordCriteria div {
            transition: all 0.3s ease;
        }
        
        /* Profile specific styles */
        .profile-card {
            margin-bottom: 25px;
        }
        
        .profile-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .profile-info-row {
            margin-bottom: 15px;
        }
        
        .password-criteria {
            margin-top: 10px;
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
                        <i class="fa fa-user me-2 text-dark"></i>
                        <span class="ms-2 text-dark">Profile Management</span>
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
                <h2 class="mb-4"><i class="bi bi-person-fill me-2"></i>Profile</h2>
            <?php endif; ?>

            <?php
            // Show a professional first-time password alert to encoders whose password hasn't been changed yet
            $showFirstTimeBanner = (($_SESSION['user_role'] ?? '') === 'encoder') && empty($user['password_changed_at']);
            if ($showFirstTimeBanner): ?>
                <div id="firstTimePasswordAlert" class="alert alert-warning d-flex align-items-start" role="alert">
                    <i class="fas fa-shield-alt me-2 mt-1"></i>
                    <div>
                        <strong>Security reminder:</strong> Please update your initial password to keep your account protected. You can change it below. This notice will disappear after you update your password.
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div id="ajaxErrorContainer" class="alert alert-danger d-none"></div>

            <!-- Profile Information Card -->
            <div class="card profile-card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-person-fill me-2"></i>Profile Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                </div>
                <div class="card-body">
                    <form id="changePasswordForm" method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="ajax" value="0">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Password (hidden)</label>
                                <input type="text" class="form-control" value="••••••••" disabled>
                                <div class="form-text">For security, your current password isn't displayed.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleNew"><i class="fas fa-eye"></i></button>
                                </div>
                                <div class="form-text">At least 8 chars, include upper, lower, number, and special char.</div>
                                <div class="password-criteria small" id="passwordCriteria">
                                    <div id="crit-length" class="text-muted"><i class="fas fa-circle me-1"></i> At least 8 characters</div>
                                    <div id="crit-upper" class="text-muted"><i class="fas fa-circle me-1"></i> Uppercase letter (A-Z)</div>
                                    <div id="crit-lower" class="text-muted"><i class="fas fa-circle me-1"></i> Lowercase letter (a-z)</div>
                                    <div id="crit-number" class="text-muted"><i class="fas fa-circle me-1"></i> Number (0-9)</div>
                                    <div id="crit-special" class="text-muted"><i class="fas fa-circle me-1"></i> Special character (!@#$%^&*)</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirm"><i class="fas fa-eye"></i></button>
                                </div>
                                <div class="invalid-feedback" id="confirmFeedback">Passwords do not match.</div>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-primary" id="updatePasswordBtn">
                                <i class="fas fa-save me-1"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <video id="loadingVideo" src="../assets/images/Trail-Loading.webm" style="width: 80px; height: 80px;" autoplay muted loop playsinline></video>
                    <h6 id="loadingMessage" class="mt-3 text-muted">Updating Password, please wait...</h6>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-check-circle fa-3x text-success"></i>
                    </div>
                    <h5 class="mb-0" id="successMessage">Password Updated Successfully!</h5>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
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

    <?php if ($success): ?>
        <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
            // Mark password as changed so encoder reminder modal won't appear again
            try {
                localStorage.setItem('cis_password_changed', '1');
            } catch (e) {}
        </script>
    <?php endif; ?>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        // Logout modal logic
        document.addEventListener('DOMContentLoaded', function() {
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
                    confirmModal.show();
                });
            }
            document.getElementById('confirmLogoutBtn').onclick = function() {
                confirmModal.hide();
                setTimeout(function() {
                    document.getElementById('logoutLoadingModal').style.display = 'block';
                    loadingModal.show();
                    // AJAX POST to logout (destroy session)
                    fetch('profile.php', {
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

            // Eye icons toggle for password fields
            const bindToggle = (btnId, inputId) => {
                const btn = document.getElementById(btnId);
                const input = document.getElementById(inputId);
                if (!btn || !input) return;
                btn.addEventListener('click', function() {
                    input.type = input.type === 'password' ? 'text' : 'password';
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye');
                        icon.classList.toggle('fa-eye-slash');
                    }
                });
            };
            bindToggle('toggleNew', 'new_password');
            bindToggle('toggleConfirm', 'confirm_password');

            // AJAX submission with 5-second minimum loading animation
            const form = document.getElementById('changePasswordForm');
            const errorBox = document.getElementById('ajaxErrorContainer');
            const LOADING_MIN_MS = 5000;

            function showLoading(message) {
                const msg = document.getElementById('loadingMessage');
                if (msg && message) msg.textContent = message;
                const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
                modal.show();
                return modal;
            }

            async function withLoading(action, message) {
                const modal = showLoading(message || 'Processing...');
                const start = Date.now();
                try {
                    const res = await action();
                    const elapsed = Date.now() - start;
                    if (elapsed < LOADING_MIN_MS) {
                        await new Promise(r => setTimeout(r, LOADING_MIN_MS - elapsed));
                    }
                    return res;
                } finally {
                    modal.hide();
                }
            }

            if (form) {
                form.addEventListener('submit', function(e) {
                    // Client-side validation before AJAX
                    const newEl = document.getElementById('new_password');
                    const confirmEl = document.getElementById('confirm_password');
                    const pwd = newEl.value || '';
                    const confirmPwd = confirmEl.value || '';
                    const meets = {
                        length: pwd.length >= 8,
                        upper: /[A-Z]/.test(pwd),
                        lower: /[a-z]/.test(pwd),
                        number: /[0-9]/.test(pwd),
                        special: /[^A-Za-z0-9]/.test(pwd)
                    };
                    const allOk = meets.length && meets.upper && meets.lower && meets.number && meets.special;
                    const matchOk = pwd === confirmPwd;
                    updateCriteriaUI(meets);
                    setConfirmValidity(matchOk);
                    if (!allOk || !matchOk) {
                        e.preventDefault();
                        errorBox.classList.remove('d-none');
                        errorBox.textContent = !allOk ? 'New password does not meet the required criteria.' : 'Passwords do not match.';
                        return;
                    }
                    e.preventDefault();
                    
                    // Show loading modal with custom message
                    document.getElementById('loadingMessage').textContent = 'Updating Password...';
                    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), {
                        backdrop: 'static',
                        keyboard: false
                    });
                    loadingModal.show();
                    
                    const fd = new FormData(form);
                    fd.set('ajax', '1');
                    // Reset errors UI
                    errorBox.classList.add('d-none');
                    errorBox.innerHTML = '';

                    withLoading(async () => {
                            const resp = await fetch('profile.php', {
                                method: 'POST',
                                body: fd
                            });
                            return await resp.json();
                        }, 'Updating Password, please wait...')
                        .then(data => {
                            if (data && data.success) {
                                // Show Success_Check.webm animation
                                const loadingVideo = document.getElementById('loadingVideo');
                                if (loadingVideo) {
                                    loadingVideo.src = '../assets/images/Success_Check.webm';
                                    loadingVideo.load();
                                    document.getElementById('loadingMessage').textContent = 'Password Updated Successfully!';
                                    
                                    // Show the success animation for 2 seconds
                                    setTimeout(() => {
                                        loadingModal.hide();
                                        
                                        // Success modal
                                        const sm = new bootstrap.Modal(document.getElementById('successModal'));
                                        sm.show();
                                        // Local flag to suppress reminders
                                        try {
                                            localStorage.setItem('cis_password_changed', '1');
                                        } catch (e) {}
                                        // Hide first-time alert if present
                                        const banner = document.getElementById('firstTimePasswordAlert');
                                        if (banner) banner.classList.add('d-none');
                                        // Reset form fields
                                        form.reset();
                                    }, 2000);
                                } else {
                                    // Fallback if video not available
                                    loadingModal.hide();
                                    
                                    // Success modal
                                    const sm = new bootstrap.Modal(document.getElementById('successModal'));
                                    sm.show();
                                    // Local flag to suppress reminders
                                    try {
                                        localStorage.setItem('cis_password_changed', '1');
                                    } catch (e) {}
                                    // Hide first-time alert if present
                                    const banner = document.getElementById('firstTimePasswordAlert');
                                    if (banner) banner.classList.add('d-none');
                                    // Reset form fields
                                    form.reset();
                                }
                            } else if (data && data.errors) {
                                // Show Cross.webm animation for error
                                const loadingVideo = document.getElementById('loadingVideo');
                                if (loadingVideo) {
                                    loadingVideo.src = '../assets/images/Cross.webm';
                                    loadingVideo.load();
                                    document.getElementById('loadingMessage').textContent = 'Failed to Update Password!';
                                    
                                    // Show the error animation for 2 seconds
                                    setTimeout(() => {
                                        loadingModal.hide();
                                        errorBox.innerHTML = '<ul class="mb-0">' + data.errors.map(e => '<li>' + e + '</li>').join('') + '</ul>';
                                        errorBox.classList.remove('d-none');
                                    }, 2000);
                                } else {
                                    // Fallback if video not available
                                    loadingModal.hide();
                                    errorBox.innerHTML = '<ul class="mb-0">' + data.errors.map(e => '<li>' + e + '</li>').join('') + '</ul>';
                                    errorBox.classList.remove('d-none');
                                }
                            } else {
                                // Show Cross.webm animation for error
                                const loadingVideo = document.getElementById('loadingVideo');
                                if (loadingVideo) {
                                    loadingVideo.src = '../assets/images/Cross.webm';
                                    loadingVideo.load();
                                    document.getElementById('loadingMessage').textContent = 'Unexpected Error!';
                                    
                                    // Show the error animation for 2 seconds
                                    setTimeout(() => {
                                        loadingModal.hide();
                                        errorBox.textContent = 'Unexpected error. Please try again.';
                                        errorBox.classList.remove('d-none');
                                    }, 2000);
                                } else {
                                    // Fallback if video not available
                                    loadingModal.hide();
                                    errorBox.textContent = 'Unexpected error. Please try again.';
                                    errorBox.classList.remove('d-none');
                                }
                            }
                        })
                        .catch(err => {
                            errorBox.textContent = 'Network error. Please try again.';
                            errorBox.classList.remove('d-none');
                        });
                });
            }
            // Real-time validation listeners
            const newPwdEl = document.getElementById('new_password');
            const confirmPwdEl = document.getElementById('confirm_password');

            function updateCriteriaUI(meets) {
                const set = (id, ok) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.classList.toggle('text-success', ok);
                    el.classList.toggle('text-muted', !ok);
                    const icon = el.querySelector('i');
                    if (icon) {
                        icon.className = ok ? 'fas fa-check-circle me-1' : 'fas fa-circle me-1';
                    }
                };
                set('crit-length', meets.length);
                set('crit-upper', meets.upper);
                set('crit-lower', meets.lower);
                set('crit-number', meets.number);
                set('crit-special', meets.special);
            }

            function setConfirmValidity(ok) {
                if (!confirmPwdEl) return;
                if (!ok && confirmPwdEl.value) {
                    confirmPwdEl.classList.add('is-invalid');
                } else {
                    confirmPwdEl.classList.remove('is-invalid');
                }
            }
            if (newPwdEl) {
                newPwdEl.addEventListener('input', function() {
                    const pwd = this.value || '';
                    updateCriteriaUI({
                        length: pwd.length >= 8,
                        upper: /[A-Z]/.test(pwd),
                        lower: /[a-z]/.test(pwd),
                        number: /[0-9]/.test(pwd),
                        special: /[^A-Za-z0-9]/.test(pwd)
                    });
                    setConfirmValidity(pwd === confirmPwdEl.value);
                });
            }
            if (confirmPwdEl) {
                confirmPwdEl.addEventListener('input', function() {
                    setConfirmValidity(this.value === newPwdEl.value);
                });
            }
        });
    </script>
</body>

</html>