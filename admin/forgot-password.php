<?php
require_once '../includes/config.php';
require_once '../includes/email_service.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$message = '';
$error = '';

// Process forgot password request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $email = sanitizeInput($_POST['email']);
    $result = ["success" => false, "message" => "An error occurred. Please try again later."];
    
    // Debug logging
    error_log("Forgot password request - Email: " . $email . ", AJAX: " . ($isAjax ? 'true' : 'false'));
    
    if (empty($email)) {
        $result = ["success" => false, "message" => "Email address is required."];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result = ["success" => false, "message" => "Please enter a valid email address."];
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now
                
                // Store token in database
                $stmt = $pdo->prepare("
                    INSERT INTO password_reset_tokens (user_id, token, expires_at) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user['id'], $token, $expiresAt]);
                
                // Generate reset URL
                $resetUrl = getBaseURL() . "/admin/reset-password.php?token=" . $token;
                
                // Send email
                $emailService = new EmailService();
                $emailResult = $emailService->sendPasswordResetEmail(
                    $user['email'],
                    $user['first_name'] . ' ' . $user['last_name'],
                    $resetUrl,
                    $token
                );
                
                if ($emailResult['success']) {
                    $result = ["success" => true, "message" => "Password reset instructions have been sent to your email address."];
                } else {
                    $result = ["success" => false, "message" => "Failed to send email. Please try again later."];
                }
            } else {
                // Don't reveal if email exists or not for security
                $result = ["success" => true, "message" => "If an account with that email exists, password reset instructions have been sent."];
            }
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $result = ["success" => false, "message" => "An error occurred. Please try again later."];
        }
    }
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else {
        if ($result["success"]) {
            $message = $result["message"];
        } else {
            $error = $result["message"];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        
        .forgot-password-container {
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
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="logo-section">
            <i class="fas fa-lock"></i>
            <h2 class="mb-0">Forgot Password</h2>
            <p class="text-muted mt-2">Enter your email to receive reset instructions</p>
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
        
        <form method="POST" action="" id="forgotPasswordForm">
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope me-2"></i>Email Address
                </label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                       placeholder="Enter your email address" required>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Instructions
                </button>
            </div>
        </form>
        
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
                    <div id="loadingMessage" class="mt-3 text-white fw-bold" style="font-size:1.2rem; text-shadow:0 1px 4px #000;">Sending Reset Instruction! Please Wait...</div>
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

            // Forgot Password form logic
            const forgotPasswordForm = document.getElementById('forgotPasswordForm');
            if (forgotPasswordForm) {
                forgotPasswordForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Check if modal is available
                    if (!loadingModal) {
                        console.error('Loading modal not initialized');
                        // Fallback to regular form submission
                        forgotPasswordForm.submit();
                        return;
                    }
                    
                    // Hide previous messages
                    document.getElementById('resetError').classList.add('d-none');
                    document.getElementById('resetSuccess').classList.add('d-none');

                    // Setup first loading phase
                    setupLoadingVideo('../assets/images/Trail-Loading.webm', true);
                    loadingMessage.textContent = "Sending Reset Instruction! Please Wait...";
                    loadingModal.show();

                    // Gather form data
                    const formData = new FormData(forgotPasswordForm);
                    
                    // Wait 3 seconds before making the request (for loading animation)
                    setTimeout(() => {
                        fetch(window.location.href, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    // Success case - show Success_Check.webm
                                    loadingMessage.textContent = 'Password Reset Instruction Sent! Returning...';
                                    
                                    if (loadingVideo && loadingVideo.style.display !== 'none') {
                                        // Switch to Success_Check.webm
                                        setupLoadingVideo('../assets/images/Success_Check.webm', false);
                                        
                                        // Wait for video to end or 3 seconds, then close modal and show success
                                        loadingVideo.addEventListener('ended', function() {
                                            loadingModal.hide();
                                            document.getElementById('resetSuccess').innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;
                                            document.getElementById('resetSuccess').classList.remove('d-none');
                                            document.getElementById('email').value = ''; // Clear email field
                                        }, { once: true });
                                        
                                        // Fallback timeout - close modal after 3 seconds regardless
                                        setTimeout(() => {
                                            loadingModal.hide();
                                            document.getElementById('resetSuccess').innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;
                                            document.getElementById('resetSuccess').classList.remove('d-none');
                                            document.getElementById('email').value = ''; // Clear email field
                                        }, 3000);
                                    } else {
                                        // Fallback if video not available
                                        setTimeout(() => {
                                            loadingModal.hide();
                                            document.getElementById('resetSuccess').innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;
                                            document.getElementById('resetSuccess').classList.remove('d-none');
                                            document.getElementById('email').value = ''; // Clear email field
                                        }, 3000);
                                    }
                                } else {
                                    // Failure case - show Cross.webm if available, or just close modal
                                    loadingMessage.textContent = 'Failed to send reset instruction! Please try again...';
                                    
                                    if (loadingVideo && loadingVideo.style.display !== 'none') {
                                        // Switch to Cross.webm if available
                                        setupLoadingVideo('../assets/images/Cross.webm', false);
                                    }
                                    
                                    // Close modal after 3 seconds and show error
                                    setTimeout(() => {
                                        loadingModal.hide();
                                        document.getElementById('resetError').innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + data.message;
                                        document.getElementById('resetError').classList.remove('d-none');
                                        
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
        });
    </script>
</body>
</html>