<?php
// Handle logout POST (AJAX) at the very top before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    require_once '../includes/auth.php';
    $_SESSION = array();
    session_destroy();
    exit;
}

require_once '../includes/auth.php';
require_once '../includes/pin-auth.php';
authenticate();
authorize(['admin']); // Only admins can manage PINs

// Handle PIN update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pin'])) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    $role = sanitizeInput($_POST['role']);
    $newPin = $_POST['new_pin'];
    $confirmPin = $_POST['confirm_pin'];
    $reason = sanitizeInput($_POST['reason'] ?? '');
    
    if ($newPin !== $confirmPin) {
        $result = ['success' => false, 'message' => 'PINs do not match'];
    } else if (strlen($newPin) != 4 || !is_numeric($newPin)) {
        $result = ['success' => false, 'message' => 'PIN must be exactly 4 digits'];
    } else {
        $result = updateRolePIN($role, $newPin, $_SESSION['user_id'], $reason);
    }
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else {
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        redirect('pin-management.php');
    }
}

// Get PIN change history with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 5; // Show 5 records per page
$offset = ($page - 1) * $limit;

// Get total count for pagination
$totalCount = getPINChangeHistoryCount();
$totalPages = ceil($totalCount / $limit);

// Handle AJAX pagination request
if (isset($_GET['ajax']) && $_GET['ajax'] === 'pin_history') {
    header('Content-Type: application/json');
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;
    
    try {
        $history = getPINChangeHistory($limit, $offset);
        $totalCount = getPINChangeHistoryCount();
        $totalPages = ceil($totalCount / $limit);
        
        echo json_encode([
            'success' => true,
            'history' => $history,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalCount,
                'limit' => $limit
            ]
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading PIN history: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Get paginated history
$history = getPINChangeHistory($limit, $offset);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIN Management - Cabinet Management System</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/PPRD_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/navbar.css" rel="stylesheet">
    <link rel="preload" as="video" href="../assets/images/Trail-Loading.webm">
    <style nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container-fluid {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            padding: 30px;
            max-width: calc(100% - 40px);
        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .admin-navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border-radius: 15px;
            margin-bottom: 25px;
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
        .table tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.1);
            transition: background-color 0.3s ease;
        }
        
        /* Pagination styling for better visibility */
        .pagination .page-link {
            color: #667eea;
            border-color: #667eea;
        }
        
        .pagination .page-link:hover {
            color: white;
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .pagination .page-item.active .page-link {
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #fff;
            border-color: #dee2e6;
        }
        
        /* Fix border radius for Previous and Next buttons */
        .pagination .page-item:first-child .page-link {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .pagination .page-item:last-child .page-link {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
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
                        <i class="fas fa-key me-2 text-dark"></i>
                        <span class="ms-2 text-dark">PIN Management</span>
                    </span>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-user-shield me-2"></i>Admin PIN
                        </div>
                        <div class="card-body">
                            <form id="adminPinForm" class="pin-update-form" data-role="admin">
                                <input type="hidden" name="role" value="admin">
                                <div class="mb-3">
                                    <label class="form-label">New Admin PIN (4 digits)</label>
                                    <input type="password" class="form-control" name="new_pin" maxlength="4" pattern="[0-9]{4}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Admin PIN</label>
                                    <input type="password" class="form-control" name="confirm_pin" maxlength="4" pattern="[0-9]{4}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason for Change (optional)</label>
                                    <textarea class="form-control" name="reason" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Update Admin PIN
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-user-edit me-2"></i>Encoder PIN
                        </div>
                        <div class="card-body">
                            <form id="encoderPinForm" class="pin-update-form" data-role="encoder">
                                <input type="hidden" name="role" value="encoder">
                                <div class="mb-3">
                                    <label class="form-label">New Encoder PIN (4 digits)</label>
                                    <input type="password" class="form-control" name="new_pin" maxlength="4" pattern="[0-9]{4}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Encoder PIN</label>
                                    <input type="password" class="form-control" name="confirm_pin" maxlength="4" pattern="[0-9]{4}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason for Change (optional)</label>
                                    <textarea class="form-control" name="reason" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Update Encoder PIN
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history me-2"></i>PIN Change History
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Changed By</th>
                                    <th>Reason</th>
                                    <th>IP Address</th>
                                    <th>Date Changed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No PIN change history yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($history as $record): ?>
                                        <tr>
                                            <td><span class="badge <?php echo $record['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                <?php echo ucfirst($record['role']); ?>
                                            </span></td>
                                            <td><?php echo $record['first_name'] . ' ' . $record['last_name']; ?></td>
                                            <td><?php echo htmlspecialchars($record['change_reason'] ?: 'No reason provided'); ?></td>
                                            <td><?php echo htmlspecialchars($record['ip_address']); ?></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($record['changed_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Showing <?php echo (($page - 1) * $limit) + 1; ?> to <?php echo min($page * $limit, $totalCount); ?> of <?php echo $totalCount; ?> entries
                            </div>
                            <nav aria-label="PIN History Pagination">
                                <ul class="pagination pagination-sm mb-0">
                                    <!-- Previous Button -->
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="#" data-page="<?php echo $page - 1; ?>">&lt;</a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">&lt;</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Page Numbers - Sliding Window of 5 -->
                                    <?php
                                    // Calculate sliding window
                                    $windowSize = 5;
                                    $halfWindow = floor($windowSize / 2);
                                    
                                    if ($totalPages <= $windowSize) {
                                        // Show all pages if total is less than window size
                                        $startPage = 1;
                                        $endPage = $totalPages;
                                    } else {
                                        // Calculate sliding window
                                        $startPage = max(1, $page - $halfWindow);
                                        $endPage = min($totalPages, $startPage + $windowSize - 1);
                                        
                                        // Adjust start if we're near the end
                                        if ($endPage - $startPage + 1 < $windowSize) {
                                            $startPage = max(1, $endPage - $windowSize + 1);
                                        }
                                    }
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Next Button -->
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="#" data-page="<?php echo $page + 1; ?>">&gt;</a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">&gt;</span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <video id="loadingVideo" src="../assets/images/Trail-Loading.webm" style="width: 80px; height: 80px; display: block; margin: 0 auto;" autoplay muted loop playsinline></video>
                    <h5 id="loadingMessage" class="mt-3 mb-0">Processing...</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
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

    <!-- Logout Loading Modal -->
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
        // Reusable function for PIN loading animations (matching dashboard.php)
        function showPINLoadingAnimation(loadingMessage, successMessage, duration = 3000) {
            // Set loading message first
            document.getElementById('loadingMessage').textContent = loadingMessage;

            // Ensure video is set to Trail-Loading.webm and preload
            const loadingVideo = document.getElementById('loadingVideo');
            if (loadingVideo) {
                loadingVideo.src = '../assets/images/Trail-Loading.webm';
                loadingVideo.load();
                loadingVideo.currentTime = 0; // Reset to beginning
            }

            // Show loading modal with Trail-Loading.webm
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), {
                backdrop: 'static',
                keyboard: false
            });

            loadingModal.show();

            // Store the modal reference globally so it can be controlled by AJAX responses
            window.currentLoadingModal = loadingModal;
            window.currentLoadingVideo = loadingVideo;
            window.loadingAnimationStartTime = Date.now();

            // After specified duration, show success animation
            setTimeout(() => {
                if (loadingVideo) {
                    // Switch to Success_Check.webm and play once
                    loadingVideo.src = '../assets/images/Success_Check.webm';
                    loadingVideo.load();
                    loadingVideo.loop = false; // Play only once
                    document.getElementById('loadingMessage').textContent = successMessage;

                    // Wait exactly 3 seconds after success animation starts, then refresh
                    setTimeout(() => {
                        loadingModal.hide();
                        // Reset video for next use
                        loadingVideo.src = '../assets/images/Trail-Loading.webm';
                        loadingVideo.loop = true; // Reset loop for next use
                        loadingVideo.load();
                        // Clear global references
                        window.currentLoadingModal = null;
                        window.currentLoadingVideo = null;
                        // Refresh page immediately
                        window.location.reload();
                    }, 3000); // Exactly 3 seconds for success animation
                } else {
                    // Fallback if video not available
                    loadingModal.hide();
                    // Refresh page immediately
                    window.location.reload();
                }
            }, duration);

            return loadingModal;
        }

        document.querySelectorAll('.pin-update-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const role = this.getAttribute('data-role');
                const formData = new FormData(this);
                formData.append('update_pin', '1');
                
                // Show loading animation first
                showPINLoadingAnimation(
                    `Updating ${role.charAt(0).toUpperCase() + role.slice(1)} PIN...`,
                    `${role.charAt(0).toUpperCase() + role.slice(1)} PIN Updated Successfully!`,
                    3000
                );
                
                fetch('pin-management.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // Handle error case
                        setTimeout(() => {
                            if (window.currentLoadingModal) {
                                window.currentLoadingModal.hide();
                            }
                            alert(`Update Failed! ${data.message}`);
                        }, 3000);
                    }
                    // Success case is handled by the loading animation
                })
                .catch(error => {
                    console.error('Error:', error);
                    setTimeout(() => {
                        if (window.currentLoadingModal) {
                            window.currentLoadingModal.hide();
                        }
                        alert('Network Error! Please try again.');
                    }, 3000);
                });
            });
        });

        // AJAX Pagination for PIN Change History
        document.addEventListener('DOMContentLoaded', function() {
            const paginationLinks = document.querySelectorAll('.pagination a[data-page]');
            
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const page = this.getAttribute('data-page');
                    loadPINHistory(page);
                });
            });
        });

        function loadPINHistory(page) {
            // Show loading state
            const tableBody = document.querySelector('.table tbody');
            const originalContent = tableBody.innerHTML;
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>';
            
            // Fetch new page data
            fetch(`pin-management.php?ajax=pin_history&page=${page}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update table content
                    updateTableContent(data.history);
                    // Update pagination
                    updatePagination(data.pagination);
                } else {
                    // Restore original content on error
                    tableBody.innerHTML = originalContent;
                    alert('Error loading PIN history: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tableBody.innerHTML = originalContent;
                alert('Network error loading PIN history');
            });
        }

        function updateTableContent(history) {
            const tableBody = document.querySelector('.table tbody');
            
            if (history.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No PIN change history yet</td></tr>';
                return;
            }
            
            let html = '';
            history.forEach(record => {
                const roleClass = record.role === 'admin' ? 'bg-danger' : 'bg-primary';
                const roleText = record.role.charAt(0).toUpperCase() + record.role.slice(1);
                const userName = record.first_name + ' ' + record.last_name;
                const reason = record.change_reason || 'No reason provided';
                const date = new Date(record.changed_at).toLocaleDateString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                
                html += `
                    <tr>
                        <td><span class="badge ${roleClass}">${roleText}</span></td>
                        <td>${userName}</td>
                        <td>${reason}</td>
                        <td>${record.ip_address}</td>
                        <td>${date}</td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }

        function updatePagination(pagination) {
            const paginationContainer = document.querySelector('.pagination');
            if (!paginationContainer) return;
            
            // Update pagination HTML (this would be generated server-side in a real implementation)
            // For now, we'll just update the current page indicator
            const currentPageLinks = document.querySelectorAll('.pagination .page-item.active');
            currentPageLinks.forEach(link => link.classList.remove('active'));
            
            const newActiveLink = document.querySelector(`.pagination a[data-page="${pagination.current_page}"]`);
            if (newActiveLink) {
                newActiveLink.parentElement.classList.add('active');
            }
        }

        // Logout functionality
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
                    fetch('pin-management.php', {
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
        });
    </script>
</body>
</html>