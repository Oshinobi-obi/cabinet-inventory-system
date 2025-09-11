<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <style>
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
        #qr-reader {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
        }
        #qr-reader img {
            width: 100%;
        }
        .scanner-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .scanner-overlay.active {
            display: block;
        }
        .error-message {
            color: #dc3545;
            margin-top: 10px;
            text-align: center;
        }
        #stopButton {
            display: none !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary public-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cabinet-filing me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-center mb-0">
                            <i class="fas fa-qrcode me-2"></i>
                            Scan QR Code
                        </h4>
                    </div>
                    <div class="card-body">
                        <div id="qr-reader"></div>
                        <div id="qr-reader-results"></div>
                        
                        <div class="text-center mt-3">
                            <button id="startButton" class="btn btn-primary">
                                <i class="fas fa-camera me-2"></i>
                                Start Scanner
                            </button>
                            <button id="stopButton" class="btn btn-danger">
                                <i class="fas fa-stop me-2"></i>
                                Stop Scanner
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Point your camera at a cabinet's QR code to view its contents
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="scanner-overlay" id="scannerOverlay">
        <div class="position-absolute top-50 start-50 translate-middle text-white">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                <p>Loading cabinet information...</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let html5QrcodeScanner = null;

        function onScanSuccess(decodedText, decodedResult) {
            // Stop scanner
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }

            // Show loading overlay
            document.getElementById('scannerOverlay').classList.add('active');
            
            // Extract cabinet number from QR code URL
            try {
                const url = new URL(decodedText);
                const cabinetNumber = url.searchParams.get('cabinet');
                if (cabinetNumber) {
                    // Redirect to cabinet view page
                    window.location.href = 'index.php?cabinet=' + encodeURIComponent(cabinetNumber);
                } else {
                    throw new Error('Invalid QR code format');
                }
            } catch (error) {
                document.getElementById('qr-reader-results').innerHTML = 
                    '<div class="error-message">Invalid QR code. Please try again.</div>';
                document.getElementById('scannerOverlay').classList.remove('active');
                startScanner(); // Restart scanner
            }
        }

        function onScanFailure(error) {
            // Handle scan failure if needed
            console.warn(`QR code scan error: ${error}`);
        }

        function startScanner() {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader",
                { 
                    fps: 10,
                    qrbox: {width: 250, height: 250},
                    aspectRatio: 1.0
                }
            );
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            
            document.getElementById('startButton').style.display = 'none';
            document.getElementById('stopButton').style.display = 'inline-block';
        }

        function stopScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
            }
            document.getElementById('startButton').style.display = 'inline-block';
            document.getElementById('stopButton').style.display = 'none';
        }

        // Event listeners
        document.getElementById('startButton').addEventListener('click', startScanner);
        document.getElementById('stopButton').addEventListener('click', stopScanner);
    </script>
</body>
</html>