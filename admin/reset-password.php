<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$message = '';
$error = '';
$token = '';
$validToken = false;
$user = null;

// Get token from URL
if (isset($_GET['token'])) {
    $token = sanitizeInput($_GET['token']);
    
    if (strlen($token) === 64) { // 32 bytes = 64 hex characters
        try {
            // Validate token
            $stmt = $pdo->prepare("
                SELECT prt.*, u.id, u.first_name, u.last_name, u.email 
                FROM password_reset_tokens prt
                JOIN users u ON prt.user_id = u.id
                WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used = 0
            ");
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch();
            
            if ($tokenData) {
                $validToken = true;
                $user = $tokenData;
            } else {
                $error = 'Invalid or expired reset token.';
            }
        } catch (Exception $e) {
            error_log("Token validation error: " . $e->getMessage());
            $error = 'An error occurred while validating the token.';
        }
    } else {
        $error = 'Invalid token format.';
    }
} else {
    $error = 'No reset token provided.';
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $result = ["success" => false, "message" => "An error occurred while resetting the password."];
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $result = ["success" => false, "message" => "All fields are required."];
    } elseif ($newPassword !== $confirmPassword) {
        $result = ["success" => false, "message" => "Passwords do not match."];
    } else {
        // Validate password strength
        $strength = validatePasswordStrength($newPassword);
        if (!$strength['valid']) {
            $result = ["success" => false, "message" => $strength['message']];
        } else {
            try {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?");
                $stmt->execute([$hashedPassword, $user['id']]);
                
                // Mark token as used
                $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
                $stmt->execute([$token]);
                
                $result = ["success" => true, "message" => "Password has been reset successfully. You can now login with your new password."];
                
            } catch (Exception $e) {
                error_log("Password reset error: " . $e->getMessage());
                $result = ["success" => false, "message" => "An error occurred while resetting the password."];
            }
        }
    }
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else {
        if ($result["success"]) {
            $message = $result["message"];
            $validToken = false; // Hide form after successful reset
        } else {
            $error = $result["message"];
        }
    }
}

// Function to validate password strength
function validatePasswordStrength($password) {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters long.'];
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter.'];
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter.'];
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number.'];
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one special character.'];
    }
    
    return ['valid' => true, 'message' => 'Password is strong.'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/PPRD_Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .reset-password-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-section i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-login a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.875rem;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <div class="logo-section">
            <i class="fas fa-key"></i>
            <h2 class="mb-0">Reset Password</h2>
            <?php if ($validToken && $user): ?>
                <p class="text-muted mt-2">Hello <?php echo htmlspecialchars($user['first_name']); ?>, enter your new password</p>
            <?php else: ?>
                <p class="text-muted mt-2">Set a new password for your account</p>
            <?php endif; ?>
        </div>
        
        <div id="resetError" class="alert alert-danger d-none"></div>
        <div id="resetSuccess" class="alert alert-success d-none"></div>
        
        <?php if ($message): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($validToken && $user): ?>
            <form method="POST" action="" id="resetPasswordForm">
                <div class="mb-3">
                    <label for="new_password" class="form-label">
                        <i class="fas fa-lock me-2"></i>New Password
                    </label>
                    <input type="password" class="form-control" id="new_password" name="new_password" 
                           placeholder="Enter new password" required>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Confirm Password
                    </label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm new password" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Reset Password
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="back-to-login">
            <a href="login.php">
                <i class="fas fa-arrow-left me-2"></i>Back to Login
            </a>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" style="background:rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:transparent; border:none; box-shadow:none; align-items:center;">
                <div class="modal-body text-center">
                    <!-- Video with fallback spinner -->
                    <div class="position-relative" style="width:120px; height:120px; margin:0 auto;">
                        <video id="loadingVideo" style="width:120px; height:120px; border-radius:50%; background:#fff; display:none;" autoplay loop muted playsinline>
                            <source src="../assets/images/Trail-Loading.webm" type="video/webm">
                        </video>
                        <!-- Fallback spinner -->
                        <div id="loadingSpinner" class="spinner-border text-primary" style="width:120px; height:120px;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="loadingMessage" class="mt-3 text-white fw-bold" style="font-size:1.2rem; text-shadow:0 1px 4px #000;">Resetting Password! Please Wait...</div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize loading modal and elements
            const loadingModalElement = document.getElementById('loadingModal');
            const loadingMessage = document.getElementById('loadingMessage');
            const loadingVideo = document.getElementById('loadingVideo');
            const loadingSpinner = document.getElementById('loadingSpinner');
            let loadingModal = null;
            
            // Ensure Bootstrap is loaded before creating modal
            if (loadingModalElement && typeof bootstrap !== 'undefined') {
                try {
                    loadingModal = new bootstrap.Modal(loadingModalElement, {
                        backdrop: 'static',
                        keyboard: false
                    });
                } catch (error) {
                    console.log('Bootstrap Modal creation failed:', error);
                }
            }

            // Function to setup video with spinner fallback
            function setupLoadingVideo(videoSrc = '../assets/images/Trail-Loading.webm', loop = true) {
                if (loadingVideo) {
                    loadingVideo.style.display = 'block';
                    loadingSpinner.style.display = 'none';
                    loadingVideo.src = videoSrc;
                    loadingVideo.loop = loop;
                    loadingVideo.load();

                    // Handle video load errors - fallback to spinner
                    loadingVideo.onerror = function() {
                        console.log('Video failed to load, using spinner fallback');
                        loadingVideo.style.display = 'none';
                        loadingSpinner.style.display = 'block';
                    };

                    // Also check if video can play
                    loadingVideo.addEventListener('loadeddata', function() {
                        console.log('Video loaded successfully');
                    }, { once: true });

                    // Fallback timer - if video doesn't load in 500ms, show spinner
                    setTimeout(function() {
                        if (loadingVideo.readyState < 2) {
                            console.log('Video loading timeout, using spinner');
                            loadingVideo.style.display = 'none';
                            loadingSpinner.style.display = 'block';
                        }
                    }, 500);
                }
            }

            // Reset Password form logic
            const resetPasswordForm = document.getElementById('resetPasswordForm');
            if (resetPasswordForm) {
                resetPasswordForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Check if modal is available
                    if (!loadingModal) {
                        console.error('Loading modal not initialized');
                        // Fallback to regular form submission
                        resetPasswordForm.submit();
                        return;
                    }
                    
                    // Hide previous messages
                    document.getElementById('resetError').classList.add('d-none');
                    document.getElementById('resetSuccess').classList.add('d-none');

                    // Setup first loading phase
                    setupLoadingVideo('../assets/images/Trail-Loading.webm', true);
                    loadingMessage.textContent = "Resetting Password! Please Wait...";
                    loadingModal.show();

                    // Gather form data
                    const formData = new FormData(resetPasswordForm);
                    
                    // Wait 3 seconds before making the request (for loading animation)
                    setTimeout(() => {
                        fetch(window.location.href, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Success case - show Success_Check.webm
                                    loadingMessage.textContent = 'Password Reset Successful! Redirecting...';
                                    
                                    if (loadingVideo && loadingVideo.style.display !== 'none') {
                                        // Switch to Success_Check.webm
                                        setupLoadingVideo('../assets/images/Success_Check.webm', false);
                                        
                                        // Wait for video to end or 3 seconds, then redirect to login
                                        loadingVideo.addEventListener('ended', function() {
                                            window.location.href = 'login.php';
                                        }, { once: true });
                                        
                                        // Fallback timeout - redirect after 3 seconds regardless
                                        setTimeout(() => {
                                            window.location.href = 'login.php';
                                        }, 3000);
                                    } else {
                                        // Fallback if video not available
                                        setTimeout(() => {
                                            window.location.href = 'login.php';
                                        }, 3000);
                                    }
                                } else {
                                    // Failure case - show Cross.webm if available, or just close modal
                                    loadingMessage.textContent = 'Failed to reset password! Please try again...';
                                    
                                    if (loadingVideo && loadingVideo.style.display !== 'none') {
                                        // Switch to Cross.webm if available
                                        setupLoadingVideo('../assets/images/Cross.webm', false);
                                    }
                                    
                                    // Close modal after 3 seconds and show error
                                    setTimeout(() => {
                                        loadingModal.hide();
                                        document.getElementById('resetError').innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + data.message;
                                        document.getElementById('resetError').classList.remove('d-none');
                                        
                                        // Reset form fields
                                        document.getElementById('new_password').value = '';
                                        document.getElementById('confirm_password').value = '';
                                        document.getElementById('passwordStrength').innerHTML = '';
                                        
                                        // Reset video for next attempt
                                        if (loadingVideo) {
                                            setupLoadingVideo('../assets/images/Trail-Loading.webm', true);
                                        }
                                    }, 3000);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                loadingModal.hide();
                                document.getElementById('resetError').innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>An error occurred. Please try again.';
                                document.getElementById('resetError').classList.remove('d-none');
                            });
                    }, 3000); // 3-second delay for loading animation
                });
            }

            // Password strength indicator
            const newPasswordField = document.getElementById('new_password');
            if (newPasswordField) {
                newPasswordField.addEventListener('input', function() {
                    const password = this.value;
                    const strengthDiv = document.getElementById('passwordStrength');
                    
                    if (password.length === 0) {
                        strengthDiv.innerHTML = '';
                        return;
                    }
                    
                    let strength = 0;
                    let feedback = [];
                    
                    if (password.length >= 8) strength++;
                    else feedback.push('At least 8 characters');
                    
                    if (/[A-Z]/.test(password)) strength++;
                    else feedback.push('One uppercase letter');
                    
                    if (/[a-z]/.test(password)) strength++;
                    else feedback.push('One lowercase letter');
                    
                    if (/[0-9]/.test(password)) strength++;
                    else feedback.push('One number');
                    
                    if (/[^A-Za-z0-9]/.test(password)) strength++;
                    else feedback.push('One special character');
                    
                    let strengthText = '';
                    let strengthClass = '';
                    
                    if (strength < 3) {
                        strengthText = 'Weak';
                        strengthClass = 'strength-weak';
                    } else if (strength < 5) {
                        strengthText = 'Medium';
                        strengthClass = 'strength-medium';
                    } else {
                        strengthText = 'Strong';
                        strengthClass = 'strength-strong';
                    }
                    
                    strengthDiv.innerHTML = `<span class="${strengthClass}">Password Strength: ${strengthText}</span>`;
                });
            }
            
            // Confirm password validation
            const confirmPasswordField = document.getElementById('confirm_password');
            if (confirmPasswordField) {
                confirmPasswordField.addEventListener('input', function() {
                    const password = document.getElementById('new_password').value;
                    const confirmPassword = this.value;
                    
                    if (confirmPassword && password !== confirmPassword) {
                        this.setCustomValidity('Passwords do not match');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        });
    </script>
</body>
</html>