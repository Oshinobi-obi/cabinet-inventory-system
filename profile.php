<?php
require_once 'includes/auth.php';
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
    <title>Profile - Cabinet Management System></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link rel="preload" as="video" href="assets/images/Trail-Loading.webm">
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
                    <span class="navbar-brand d-flex align-items-center mb-0">
                        <i class="fas fa-user-shield me-2"></i> <span class="ms-2">My Profile</span>
                    </span>
                </div>
            </div>
        </nav>

        <div class="container p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Account Security</h2>
                <span class="badge bg-<?php echo $_SESSION['user_role'] === 'admin' ? 'danger' : 'primary'; ?>">
                    <?php echo ucfirst($_SESSION['user_role']); ?>
                </span>
            </div>

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
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div id="ajaxErrorContainer" class="alert alert-danger d-none"></div>

            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Profile Overview</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(($user['first_name'] ?? '').' '.($user['last_name'] ?? '')); ?>" disabled>
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

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                </div>
                <div class="card-body">
                    <form id="changePasswordForm" method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="ajax" value="0">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Password (hidden)</label>
                                <input type="text" class="form-control" value="••••••••" disabled>
                                <div class="form-text">For security, your current password isn’t displayed.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleNew"><i class="fas fa-eye"></i></button>
                                </div>
                                <div class="form-text">At least 8 chars, include upper, lower, number, and special char.</div>
                                <div class="mt-2 small" id="passwordCriteria">
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
                    <video src="assets/images/Trail-Loading.webm" style="width: 80px; height: 80px;" autoplay muted loop playsinline></video>
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
    <?php if ($success): ?>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        // Mark password as changed so encoder reminder modal won’t appear again
        try { localStorage.setItem('cis_password_changed', '1'); } catch (e) {}
    </script>
    <?php endif; ?>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
    // Eye icons toggle for password fields
    document.addEventListener('DOMContentLoaded', function() {
        const bindToggle = (btnId, inputId) => {
            const btn = document.getElementById(btnId);
            const input = document.getElementById(inputId);
            if (!btn || !input) return;
            btn.addEventListener('click', function() {
                input.type = input.type === 'password' ? 'text' : 'password';
                const icon = this.querySelector('i');
                if (icon) { icon.classList.toggle('fa-eye'); icon.classList.toggle('fa-eye-slash'); }
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
                const fd = new FormData(form);
                fd.set('ajax', '1');
                // Reset errors UI
                errorBox.classList.add('d-none');
                errorBox.innerHTML = '';

                withLoading(async () => {
                    const resp = await fetch('profile.php', { method: 'POST', body: fd });
                    return await resp.json();
                }, 'Updating Password, please wait...')
                .then(data => {
                    if (data && data.success) {
                        // Success modal
                        const sm = new bootstrap.Modal(document.getElementById('successModal'));
                        sm.show();
                        // Local flag to suppress reminders
                        try { localStorage.setItem('cis_password_changed', '1'); } catch (e) {}
                        // Hide first-time alert if present
                        const banner = document.getElementById('firstTimePasswordAlert');
                        if (banner) banner.classList.add('d-none');
                        // Reset form fields
                        form.reset();
                    } else if (data && data.errors) {
                        errorBox.innerHTML = '<ul class="mb-0">' + data.errors.map(e => '<li>' + e + '</li>').join('') + '</ul>';
                        errorBox.classList.remove('d-none');
                    } else {
                        errorBox.textContent = 'Unexpected error. Please try again.';
                        errorBox.classList.remove('d-none');
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
                const pwd = newPwdEl.value || '';
                updateCriteriaUI({
                    length: pwd.length >= 8,
                    upper: /[A-Z]/.test(pwd),
                    lower: /[a-z]/.test(pwd),
                    number: /[0-9]/.test(pwd),
                    special: /[^A-Za-z0-9]/.test(pwd)
                });
                // Also re-check match when new password changes
                setConfirmValidity(pwd === (confirmPwdEl?.value || ''));
            });
        }
        if (confirmPwdEl) {
            confirmPwdEl.addEventListener('input', function() {
                setConfirmValidity((newPwdEl?.value || '') === confirmPwdEl.value);
            });
        }
    });
    </script>
</body>
</html>