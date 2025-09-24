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
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
            margin: auto;
        }

        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo img {
            width: 64px;
            height: 64px;
            margin-bottom: 15px;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .input-group-text {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="bi bi-archive-fill text-primary" style="font-size: 64px;"></i>
                <h3 class="mt-2">Cabinet Management System</h3>
            </div>

            <div id="loginError" class="alert alert-danger d-none"></div>
            <form method="POST" action="" autocomplete="off">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text">👤</span>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">🔒</span>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <div class="text-center mt-3">
                <a href="../public/index.php" class="text-decoration-none">
                    ← Back to Viewer
                </a>
            </div>
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

            // Login modal logic
            const loginForm = document.querySelector('form');
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), {
                backdrop: 'static',
                keyboard: false
            });
            const loadingMessage = document.getElementById('loadingMessage');
            const loadingVideo = document.getElementById('loadingVideo');
            const loadingSpinner = document.getElementById('loadingSpinner');

            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    // Hide previous error
                    document.getElementById('loginError').classList.add('d-none');

                    // Setup video with fallback to spinner
                    if (loadingVideo) {
                        loadingVideo.style.display = 'block';
                        loadingSpinner.style.display = 'none';

                        // Handle video load errors - fallback to spinner
                        loadingVideo.onerror = function() {
                            console.log('Video failed to load, using spinner fallback');
                            loadingVideo.style.display = 'none';
                            loadingSpinner.style.display = 'block';
                        };

                        // Also check if video can play
                        loadingVideo.addEventListener('loadeddata', function() {
                            console.log('Video loaded successfully');
                        }, {
                            once: true
                        });

                        // Fallback timer - if video doesn't load in 500ms, show spinner
                        setTimeout(function() {
                            if (loadingVideo.readyState < 2) {
                                console.log('Video loading timeout, using spinner');
                                loadingVideo.style.display = 'none';
                                loadingSpinner.style.display = 'block';
                            }
                        }, 500);
                    }

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
                                        loadingVideo.src = 'assets/images/Trail-Loading.webm';
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
        });
    </script>
</body>

</html>