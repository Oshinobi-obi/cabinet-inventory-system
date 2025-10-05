<?php
require_once '../includes/config.php';
require_once '../includes/pin-auth.php';

// Check PIN authentication first (but not if coming from successful login)
if (!isPINAuthenticated() || isPINSessionExpired()) {
    clearPINAuthentication();
    redirect('pin-verify.php');
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $result = ["success" => false, "message" => "Invalid username or password"];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Validate user role matches PIN role
            $pinRole = getPINRole();
            $userRole = $user['role'];
            
            if (!validateUserRoleWithPIN($userRole)) {
                // Role mismatch
                if (strtolower($pinRole) === 'admin' && strtolower($userRole) === 'encoder') {
                    $result = [
                        "success" => false, 
                        "message" => "Encoder Credentials Detected! Only Admin Credentials Are Allowed!",
                        "role_mismatch" => true
                    ];
                } else if (strtolower($pinRole) === 'encoder' && strtolower($userRole) === 'admin') {
                    $result = [
                        "success" => false, 
                        "message" => "Admin Credentials Detected! Only Encoder Credentials Are Allowed!",
                        "role_mismatch" => true
                    ];
                } else {
                    $result = [
                        "success" => false, 
                        "message" => "Role mismatch. Please use the correct PIN.",
                        "role_mismatch" => true
                    ];
                }
            } else {
                // Valid credentials and role match
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['last_activity'] = time();
                $result = ["success" => true, "username" => $user['username']];
            }
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

$pinRole = getPINRole();
$roleDisplay = ucfirst($pinRole ?? 'Unknown');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cabinet Management System</title>
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
            padding: 20px;
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
            margin: auto;
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
            margin-bottom: 8px;
        }

        .logo-section p {
            color: #6c757d;
            margin-top: 0;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .role-text {
            font-weight: 600;
            font-size: 1rem;
            margin-top: 5px;
            margin-bottom: 0;
        }

        .role-text.admin {
            color: #ff6b6b;
        }

        .role-text.encoder {
            color: #4ecdc4;
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

        .spinner-border {
            border-color: #667eea;
            border-right-color: transparent;
        }

        .video-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading-video {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            object-fit: cover;
            z-index: 2;
            position: absolute;
        }

        .loading-spinner {
            position: absolute;
            z-index: 1;
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .logo-section h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo-section">
            <i class="fas fa-archive"></i>
            <h2>Cabinet Management System</h2>
            <p>Welcome back! Please sign in to continue</p>
            <p class="role-text <?php echo strtolower($pinRole); ?>">
                <?php echo $roleDisplay; ?> Login
            </p>
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
            <a href="#" id="changePinLink">
                <i class="fas fa-shield-alt me-1"></i>Change PIN
            </a>
            <br>
            <a href="../public/index.php">
                <i class="fas fa-arrow-left me-1"></i>Back to Viewer
            </a>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:transparent; border:none; box-shadow:none;">
                <div class="modal-body text-center">
                    <div class="video-container">
                        <div id="loadingSpinner" class="spinner-border text-primary loading-spinner" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <video id="loadingVideo" class="loading-video" autoplay loop muted playsinline preload="auto" style="display:none;">
                            <source src="../assets/images/Trail-Loading.webm" type="video/webm">
                        </video>
                    </div>
                    <div id="loadingMessage" class="mt-3 text-white fw-bold" style="font-size:1.2rem; text-shadow:0 2px 4px rgba(0,0,0,0.8);">
                        Verifying Credentials. This won't take long...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        document.addEventListener('DOMContentLoaded', function() {
            const loadingModalElement = document.getElementById('loadingModal');
            const loadingMessage = document.getElementById('loadingMessage');
            const loadingVideo = document.getElementById('loadingVideo');
            const loadingSpinner = document.getElementById('loadingSpinner');
            let loadingModal = null;
            
            if (loadingModalElement && typeof bootstrap !== 'undefined') {
                loadingModal = new bootstrap.Modal(loadingModalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
            }

            function preloadVideos() {
                const videos = [
                    '../assets/images/Trail-Loading.webm',
                    '../assets/images/Success_Check.webm',
                    '../assets/images/Cross.webm'
                ];
                
                console.log('Browser video support:', {
                    webm: document.createElement('video').canPlayType('video/webm'),
                    mp4: document.createElement('video').canPlayType('video/mp4'),
                    ogg: document.createElement('video').canPlayType('video/ogg')
                });
                
                videos.forEach(src => {
                    const video = document.createElement('video');
                    video.preload = 'auto';
                    video.src = src;
                    video.muted = true;
                    video.playsInline = true;
                    video.load();
                    
                    // Test if Trail-Loading.webm can be loaded
                    if (src.includes('Trail-Loading')) {
                        video.addEventListener('error', (e) => {
                            console.warn('Trail-Loading.webm failed to preload:', e);
                        });
                        video.addEventListener('loadeddata', () => {
                            console.log('✅ Trail-Loading.webm preloaded successfully');
                        });
                        video.addEventListener('loadstart', () => {
                            console.log('🔄 Trail-Loading.webm preload started');
                        });
                    }
                });
            }

            function setupLoadingVideo(videoSrc = '../assets/images/Trail-Loading.webm', loop = true) {
                if (!loadingVideo) return;

                // Simple approach like forgot password button
                loadingVideo.style.display = 'none';
                loadingSpinner.style.display = 'block';
                
                // Clear any existing event listeners
                loadingVideo.onloadeddata = null;
                loadingVideo.oncanplaythrough = null;
                loadingVideo.onerror = null;
                loadingVideo.onended = null;
                loadingVideo.onloadstart = null;
                
                // Set video properties
                loadingVideo.src = videoSrc;
                loadingVideo.loop = loop;
                loadingVideo.muted = true;
                loadingVideo.autoplay = true;
                loadingVideo.preload = 'auto';
                loadingVideo.playsInline = true;

                const onVideoReady = () => {
                    loadingVideo.style.display = 'block';
                    loadingSpinner.style.display = 'none';
                    loadingVideo.play().catch(() => {
                        loadingVideo.style.display = 'none';
                        loadingSpinner.style.display = 'block';
                    });
                };

                const onVideoError = () => {
                    loadingVideo.style.display = 'none';
                    loadingSpinner.style.display = 'block';
                };

                // Simple event listeners
                loadingVideo.addEventListener('loadeddata', onVideoReady, { once: true });
                loadingVideo.addEventListener('canplaythrough', onVideoReady, { once: true });
                loadingVideo.addEventListener('error', onVideoError, { once: true });

                // Load the video
                loadingVideo.load();

                // Simple timeout like forgot password (2 seconds)
                setTimeout(() => {
                    if (loadingVideo.readyState < 2) {
                        loadingVideo.style.display = 'none';
                        loadingSpinner.style.display = 'block';
                    }
                }, 2000);
            }

            preloadVideos();

            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (!loadingModal) return;
                    
                    document.getElementById('loginError').classList.add('d-none');

                    setupLoadingVideo('../assets/images/Trail-Loading.webm', true);
                    loadingMessage.textContent = "Verifying Credentials. This won't take long...";
                    loadingModal.show();

                    const formData = new FormData(loginForm);
                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(response => response.json())
                        .then(data => {
                            const username = formData.get('username') || '';
                            
                            if (data.success) {
                                loadingMessage.textContent = `Verification Successful! Welcome back ${data.username || username}!`;
                                setupLoadingVideo('../assets/images/Success_Check.webm', false);
                                
                                if (loadingVideo) {
                                    loadingVideo.addEventListener('ended', function() {
                                        window.location.href = 'dashboard.php';
                                    }, { once: true });
                                }
                                
                                setTimeout(() => {
                                    window.location.href = 'dashboard.php';
                                }, 4000);
                                
                            } else {
                                if (data.role_mismatch) {
                                    loadingMessage.textContent = data.message;
                                    setupLoadingVideo('../assets/images/Cross.webm', false);
                                    
                                    setTimeout(() => {
                                        loadingModal.hide();
                                        document.getElementById('loginError').textContent = data.message;
                                        document.getElementById('loginError').classList.remove('d-none');
                                        document.getElementById('password').value = '';
                                        setupLoadingVideo('../assets/images/Trail-Loading.webm', true);
                                    }, 3000);
                                } else {
                                    loadingMessage.textContent = 'Failed to verify credentials! Please try again...';
                                    setupLoadingVideo('../assets/images/Cross.webm', false);
                                    
                                    setTimeout(() => {
                                        loadingModal.hide();
                                        document.getElementById('loginError').textContent = 'Wrong Username or Password! Please try again!';
                                        document.getElementById('loginError').classList.remove('d-none');
                                        document.getElementById('username').value = username;
                                        document.getElementById('password').value = '';
                                        setupLoadingVideo('../assets/images/Trail-Loading.webm', true);
                                    }, 3000);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Login error:', error);
                            loadingModal.hide();
                            document.getElementById('loginError').textContent = 'An error occurred. Please try again.';
                            document.getElementById('loginError').classList.remove('d-none');
                        });
                });
            }

            const forgotPasswordLink = document.getElementById('forgotPasswordLink');
            if (forgotPasswordLink) {
                forgotPasswordLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (!loadingModal) {
                        window.location.href = 'forgot-password.php';
                        return;
                    }
                    
                    setupLoadingVideo('../assets/images/Trail-Loading.webm', true);
                    loadingMessage.textContent = "Redirecting! Please Wait...";
                    loadingModal.show();

                    setTimeout(function() {
                        window.location.href = 'forgot-password.php';
                    }, 2000);
                });
            }

            // Change PIN link handler
            const changePinLink = document.getElementById('changePinLink');
            if (changePinLink) {
                changePinLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (!loadingModal) {
                        window.location.href = 'pin-verify.php';
                        return;
                    }
                    
                    setupLoadingVideo('../assets/images/Trail-Loading.webm', true);
                    loadingMessage.textContent = "Redirecting to PIN verification...";
                    loadingModal.show();

                    // Clear PIN session before redirecting
                    fetch('pin-verify.php?clear_session=1')
                        .then(() => {
                            setTimeout(function() {
                                window.location.href = 'pin-verify.php';
                            }, 2000);
                        })
                        .catch(() => {
                            setTimeout(function() {
                                window.location.href = 'pin-verify.php';
                            }, 2000);
                        });
                });
            }
        });
    </script>
</body>
</html>