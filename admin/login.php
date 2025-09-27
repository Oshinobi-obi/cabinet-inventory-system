<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password before verification
    $result = ["success" => false, "message" => "Invalid username or password"];
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['last_activity'] = time();
            $result = ["success" => true, "username" => $user['username']];
        }
    } catch (PDOException $e) {
        $result = ["success" => false, "message" => "Login error: " . $e->getMessage()];
    }
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else if ($result["success"]) {
        redirect('dashboard.php');
    } else {
        $error = $result["message"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/DepEd_Logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
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

        .logo-section h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 0;
        }

        .logo-section p {
            color: #6c757d;
            margin-top: 8px;
            font-size: 0.95rem;
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

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
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

        .footer-links {
            text-align: center;
            margin-top: 25px;
        }

        .footer-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            margin: 8px 15px;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            text-decoration: underline;
            color: #5a67d8;
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        /* Loading animation improvements */
        .spinner-border {
            border-color: #667eea;
            border-right-color: transparent;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo-section">
            <i class="fas fa-archive"></i>
            <h2>Cabinet Management System</h2>
            <p>Welcome back! Please sign in to continue</p>
        </div>

        <div id="loginError" class="alert alert-danger d-none"></div>
        
        <form method="POST" action="" autocomplete="off" id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user me-2"></i>Username
                </label>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Enter your username" required autocomplete="username">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required autocomplete="current-password">
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </div>
        </form>

        <div class="footer-links">
            <a href="#" id="forgotPasswordLink">
                <i class="fas fa-key me-1"></i>Forgot Password?
            </a>
            <br>
            <a href="../public/index.php">
                <i class="fas fa-arrow-left me-1"></i>Back to Viewer
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
                    <div id="loadingMessage" class="mt-3 text-white fw-bold" style="font-size:1.2rem; text-shadow:0 1px 4px #000;">Verifying Credentials. This won't take long...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        // Handle image load error (existing)
        document.addEventListener('DOMContentLoaded', function() {
            const cabinetIcon = document.getElementById('cabinetIcon');
            if (cabinetIcon) {
                cabinetIcon.addEventListener('error', function() {
                    const svgFallback = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 24 24\'%3E%3Crect x=\'3\' y=\'2\' width=\'18\' height=\'20\' rx=\'1\' fill=\'%230d6efd\'/%3E%3Crect x=\'4\' y=\'3\' width=\'16\' height=\'6\' fill=\'%23ffffff\' fill-opacity=\'0.2\'/%3E%3Ccircle cx=\'18\' cy=\'6\' r=\'0.8\' fill=\'%23ffffff\'/%3E%3Crect x=\'4\' y=\'10\' width=\'16\' height=\'6\' fill=\'%23ffffff\' fill-opacity=\'0.2\'/%3E%3Ccircle cx=\'18\' cy=\'13\' r=\'0.8\' fill=\'%23ffffff\'/%3E%3Crect x=\'4\' y=\'17\' width=\'16\' height=\'4\' fill=\'%23ffffff\' fill-opacity=\'0.2\'/%3E%3Ccircle cx=\'18\' cy=\'19\' r=\'0.8\' fill=\'%23ffffff\'/%3E%3C/svg%3E';
                    this.src = svgFallback;
                });
            }

            // Initialize loading modal and elements GLOBALLY (accessible to all functions)
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
            function setupLoadingVideo() {
                if (loadingVideo) {
                    loadingVideo.style.display = 'block';
                    loadingSpinner.style.display = 'none';
                    loadingVideo.src = '../assets/images/Trail-Loading.webm';
                    loadingVideo.loop = true;
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

            // Login form logic
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Check if modal is available
                    if (!loadingModal) {
                        console.error('Loading modal not initialized');
                        return;
                    }
                    
                    // Hide previous error
                    document.getElementById('loginError').classList.add('d-none');

                    // Setup video with fallback to spinner
                    setupLoadingVideo();
                    loadingMessage.textContent = "Verifying Credentials. This won't take long...";
                    loadingModal.show();

                    // Gather form data
                    const formData = new FormData(loginForm);
                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            const username = formData.get('username') || '';
                            if (data.success) {
                                // Success case - show Success_Check.webm
                                loadingMessage.textContent = `Verification Successful! Welcome back ${data.username || username}!`;
                                
                                if (loadingVideo && loadingVideo.style.display !== 'none') {
                                    // Switch to Success_Check.webm
                                    loadingVideo.src = '../assets/images/Success_Check.webm';
                                    loadingVideo.loop = false; // Play only once
                                    loadingVideo.load();
                                    
                                    // Handle video load errors
                                    loadingVideo.onerror = function() {
                                        loadingVideo.style.display = 'none';
                                        loadingSpinner.style.display = 'block';
                                    };
                                    
                                    // Wait for video to end or 3 seconds, then redirect
                                    loadingVideo.addEventListener('ended', function() {
                                        window.location.href = 'dashboard.php';
                                    }, { once: true });
                                    
                                    // Fallback timeout - redirect after 3 seconds regardless
                                    setTimeout(() => {
                                        window.location.href = 'dashboard.php';
                                    }, 3000);
                                } else {
                                    // Fallback if video not available
                                    setTimeout(() => {
                                        window.location.href = 'dashboard.php';
                                    }, 3000);
                                }
                            } else {
                                // Failure case - show Cross.webm
                                loadingMessage.textContent = 'Failed to verify credentials! Please try again...';
                                
                                if (loadingVideo && loadingVideo.style.display !== 'none') {
                                    // Switch to Cross.webm
                                    loadingVideo.src = '../assets/images/Cross.webm';
                                    loadingVideo.loop = false; // Play only once
                                    loadingVideo.load();
                                    
                                    // Handle video load errors
                                    loadingVideo.onerror = function() {
                                        loadingVideo.style.display = 'none';
                                        loadingSpinner.style.display = 'block';
                                    };
                                }
                                
                                // Close modal after exactly 3 seconds
                                setTimeout(() => {
                                    loadingModal.hide();
                                    document.getElementById('loginError').textContent = 'Wrong Username or Password! Please try again!';
                                    document.getElementById('loginError').classList.remove('d-none');
                                    document.getElementById('username').value = username;
                                    document.getElementById('password').value = '';
                                    
                                    // Reset video for next attempt
                                    if (loadingVideo) {
                                        loadingVideo.src = '../assets/images/Trail-Loading.webm';
                                        loadingVideo.loop = true;
                                        loadingVideo.load();
                                    }
                                }, 3000);
                            }
                        })
                        .catch(() => {
                            loadingModal.hide();
                            document.getElementById('loginError').textContent = 'An error occurred. Please try again.';
                            document.getElementById('loginError').classList.remove('d-none');
                        });
                });
            }

            // Forgot Password Link functionality
            const forgotPasswordLink = document.getElementById('forgotPasswordLink');
            if (forgotPasswordLink) {
                forgotPasswordLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Check if modal is available
                    if (!loadingModal) {
                        console.error('Loading modal not initialized');
                        // Fallback - direct redirect
                        window.location.href = 'forgot-password.php';
                        return;
                    }
                    
                    // Setup video with fallback to spinner
                    setupLoadingVideo();
                    loadingMessage.textContent = "Redirecting! Please Wait...";
                    loadingModal.show();

                    // Redirect after 3 seconds
                    setTimeout(function() {
                        window.location.href = 'forgot-password.php';
                    }, 3000);
                });
            }
        });
    </script>
</body>

</html>