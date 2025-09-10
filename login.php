<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password before verification
    
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
            
            redirect('dashboard.php');
        } else {
            $error = "Invalid username or password";
        }
    } catch(PDOException $e) {
        $error = "Login error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cabinet Information System</title>
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
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
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
                <img id="cabinetIcon" src="assets/images/cabinet-icon.svg" alt="Cabinet Icon">
                <h3 class="mt-2">Cabinet Information System</h3>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text">👤</span>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">🔒</span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            
            <div class="text-center mt-3">
                <a href="index.php" class="text-decoration-none">
                    ← Back to Viewer
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        // Handle image load error
        document.addEventListener('DOMContentLoaded', function() {
            const cabinetIcon = document.getElementById('cabinetIcon');
            if (cabinetIcon) {
                cabinetIcon.addEventListener('error', function() {
                    // Replace with inline SVG fallback if image fails to load
                    const svgFallback = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 24 24\'%3E%3Crect x=\'3\' y=\'2\' width=\'18\' height=\'20\' rx=\'1\' fill=\'%230d6efd\'/%3E%3Crect x=\'4\' y=\'3\' width=\'16\' height=\'6\' fill=\'%23ffffff\' fill-opacity=\'0.2\'/%3E%3Ccircle cx=\'18\' cy=\'6\' r=\'0.8\' fill=\'%23ffffff\'/%3E%3Crect x=\'4\' y=\'10\' width=\'16\' height=\'6\' fill=\'%23ffffff\' fill-opacity=\'0.2\'/%3E%3Ccircle cx=\'18\' cy=\'13\' r=\'0.8\' fill=\'%23ffffff\'/%3E%3Crect x=\'4\' y=\'17\' width=\'16\' height=\'4\' fill=\'%23ffffff\' fill-opacity=\'0.2\'/%3E%3Ccircle cx=\'18\' cy=\'19\' r=\'0.8\' fill=\'%23ffffff\'/%3E%3C/svg%3E';
                    this.src = svgFallback;
                });
            }
        });
    </script>
</body>
</html>