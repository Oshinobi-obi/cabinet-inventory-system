<?php
require_once '../includes/config.php';
require_once '../includes/pin-auth.php';

// Handle session clear request from "Change PIN" button
if (isset($_GET['clear_session']) && $_GET['clear_session'] == '1') {
    clearPINAuthentication();
    // Just clear and return - JavaScript will handle redirect
    exit;
}

// If already authenticated and session not expired, allow re-verification (Change PIN scenario)
// Don't auto-redirect - let user enter PIN again
if (isPINSessionExpired()) {
    clearPINAuthentication();
}

// Handle PIN verification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    $pin = $_POST['pin'] ?? '';
    $role = verifyPIN($pin);
    
    $result = [
        'success' => false,
        'message' => 'Invalid PIN',
        'role' => null
    ];
    
    if ($role) {
        setPINAuthentication($role);
        $result = [
            'success' => true,
            'message' => 'PIN verified successfully',
            'role' => ucfirst($role)
        ];
    }
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else if ($result['success']) {
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIN Authentication - Cabinet Management System</title>
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

        .pin-container {
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

        .pin-inputs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 30px 0;
        }

        .pin-input {
            width: 60px;
            height: 60px;
            font-size: 2rem;
            text-align: center;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .pin-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
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

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .footer-links {
            text-align: center;
            margin-top: 25px;
        }

        .footer-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            text-decoration: underline;
            color: #5a67d8;
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
            width: 120px;
            height: 120px;
        }
    </style>
</head>
<body>
    <div class="pin-container">
        <div class="logo-section">
            <i class="fas fa-shield-alt"></i>
            <h2>Authentication Required</h2>
            <p>Enter your PIN to access the system</p>
        </div>

        <div id="pinError" class="alert alert-danger d-none"></div>

        <form id="pinForm" method="POST">
            <div class="pin-inputs">
                <input type="password" maxlength="1" class="pin-input" id="pin1" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                <input type="password" maxlength="1" class="pin-input" id="pin2" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                <input type="password" maxlength="1" class="pin-input" id="pin3" pattern="[0-9]" inputmode="numeric" autocomplete="off">
                <input type="password" maxlength="1" class="pin-input" id="pin4" pattern="[0-9]" inputmode="numeric" autocomplete="off">
            </div>
            <input type="hidden" name="pin" id="pinValue">

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check me-2"></i>Verify PIN
                </button>
            </div>
        </form>

        <div class="footer-links">
            <a href="../public/index.php">
                <i class="fas fa-arrow-left me-1"></i>Back to Public Viewer
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
                        Verifying Authentication Pin! Please Wait...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        document.addEventListener('DOMContentLoaded', function() {
            const pinInputs = document.querySelectorAll('.pin-input');
            const pinForm = document.getElementById('pinForm');
            const pinValue = document.getElementById('pinValue');
            const pinError = document.getElementById('pinError');
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

            // Auto-focus first input
            pinInputs[0].focus();

            // Handle PIN input navigation
            pinInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    const value = this.value;
                    
                    // Only allow numbers
                    if (value && !/^\d$/.test(value)) {
                        this.value = '';
                        return;
                    }
                    
                    // Move to next input
                    if (value && index < pinInputs.length - 1) {
                        pinInputs[index + 1].focus();
                    }
                    
                    // Auto-submit when all filled
                    if (index === pinInputs.length - 1 && value) {
                        setTimeout(() => pinForm.dispatchEvent(new Event('submit')), 100);
                    }
                });

                input.addEventListener('keydown', function(e) {
                    // Handle backspace
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        pinInputs[index - 1].focus();
                        pinInputs[index - 1].value = '';
                    }
                    
                    // Handle arrow keys
                    if (e.key === 'ArrowLeft' && index > 0) {
                        pinInputs[index - 1].focus();
                    }
                    if (e.key === 'ArrowRight' && index < pinInputs.length - 1) {
                        pinInputs[index + 1].focus();
                    }
                });

                // Handle paste
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text');
                    const digits = pastedData.replace(/\D/g, '').slice(0, 4);
                    
                    digits.split('').forEach((digit, i) => {
                        if (pinInputs[i]) {
                            pinInputs[i].value = digit;
                        }
                    });
                    
                    if (digits.length === 4) {
                        setTimeout(() => pinForm.dispatchEvent(new Event('submit')), 100);
                    }
                });
            });

            function setupVideo(videoSrc, loop = true) {
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


            pinForm.addEventListener('submit', function(e) {
                e.preventDefault();
                pinError.classList.add('d-none');
                
                // Collect PIN
                const pin = Array.from(pinInputs).map(input => input.value).join('');
                
                if (pin.length !== 4) {
                    pinError.textContent = 'Please enter a complete 4-digit PIN';
                    pinError.classList.remove('d-none');
                    return;
                }
                
                pinValue.value = pin;
                
                if (!loadingModal) return;
                
                setupVideo('../assets/images/Trail-Loading.webm', true);
                loadingMessage.textContent = 'Verifying Authentication Pin! Please Wait...';
                loadingModal.show();
                
                const formData = new FormData(pinForm);
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadingMessage.textContent = `${data.role} Pin Detected! Redirecting...`;
                        setupVideo('../assets/images/Success_Check.webm', false);
                        
                        loadingVideo.addEventListener('ended', () => {
                            window.location.href = 'login.php';
                        }, { once: true });
                        
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 3000);
                    } else {
                        loadingMessage.textContent = 'Invalid PIN! Please try again...';
                        setupVideo('../assets/images/Cross.webm', false);
                        
                        setTimeout(() => {
                            loadingModal.hide();
                            pinError.textContent = 'Invalid PIN. Please try again.';
                            pinError.classList.remove('d-none');
                            pinInputs.forEach(input => input.value = '');
                            pinInputs[0].focus();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('PIN verification error:', error);
                    loadingModal.hide();
                    pinError.textContent = 'An error occurred. Please try again.';
                    pinError.classList.remove('d-none');
                });
            });
        });
    </script>
</body>
</html>