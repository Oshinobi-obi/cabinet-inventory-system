<?php
/**
 * Mobile Access Status Checker
 * Verifies that the mobile setup is working correctly
 */

require_once 'includes/functions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Access Status - Cabinet Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .status-card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .check-item { padding: 15px; border-radius: 10px; margin-bottom: 15px; }
        .check-success { background: #d4edda; border-left: 4px solid #28a745; }
        .check-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .check-error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .network-info { background: #e3f2fd; border-radius: 10px; padding: 20px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card status-card">
                    <div class="card-header bg-primary text-white text-center">
                        <h3><i class="fas fa-mobile-alt me-2"></i>Mobile Access Status</h3>
                        <p class="mb-0">Cabinet Information System</p>
                    </div>
                    <div class="card-body">
                        
                        <?php
                        $checks = [];
                        $overallStatus = true;
                        
                        // Check 1: Network config file
                        $networkConfig = null;
                        $networkConfigFile = __DIR__ . '/network_config.json';
                        if (file_exists($networkConfigFile)) {
                            $networkConfig = json_decode(file_get_contents($networkConfigFile), true);
                            $checks[] = [
                                'status' => 'success',
                                'icon' => 'fas fa-check-circle',
                                'title' => 'Network Configuration Found',
                                'message' => 'Mobile server configuration is available'
                            ];
                        } else {
                            $checks[] = [
                                'status' => 'warning',
                                'icon' => 'fas fa-exclamation-triangle',
                                'title' => 'Network Configuration Missing',
                                'message' => 'Start the mobile server using <code>php server.php</code>'
                            ];
                        }
                        
                        // Check 2: Server IP detection
                        $serverIP = getServerIP();
                        if ($serverIP && $serverIP !== '127.0.0.1') {
                            $checks[] = [
                                'status' => 'success',
                                'icon' => 'fas fa-network-wired',
                                'title' => 'Network IP Detected',
                                'message' => "Server IP: <strong>$serverIP</strong>"
                            ];
                        } else {
                            $checks[] = [
                                'status' => 'error',
                                'icon' => 'fas fa-exclamation-circle',
                                'title' => 'Network IP Detection Failed',
                                'message' => 'Could not detect network IP address'
                            ];
                            $overallStatus = false;
                        }
                        
                        // Check 3: Base URL generation
                        $baseURL = getBaseURL();
                        $checks[] = [
                            'status' => 'success',
                            'icon' => 'fas fa-link',
                            'title' => 'Base URL Generated',
                            'message' => "Base URL: <strong>$baseURL</strong>"
                        ];
                        
                        // Check 4: QR Code directory
                        $qrDir = __DIR__ . '/qrcodes/';
                        if (is_dir($qrDir) && is_writable($qrDir)) {
                            $checks[] = [
                                'status' => 'success',
                                'icon' => 'fas fa-qrcode',
                                'title' => 'QR Code Directory Ready',
                                'message' => 'QR codes can be generated and saved'
                            ];
                        } else {
                            $checks[] = [
                                'status' => 'warning',
                                'icon' => 'fas fa-folder-open',
                                'title' => 'QR Code Directory Issue',
                                'message' => 'Directory may need to be created or permissions fixed'
                            ];
                        }
                        
                        // Check 5: Mobile CSS
                        if (file_exists(__DIR__ . '/assets/css/mobile-enhancements.css')) {
                            $checks[] = [
                                'status' => 'success',
                                'icon' => 'fas fa-mobile-alt',
                                'title' => 'Mobile Enhancements Available',
                                'message' => 'Enhanced mobile styles are loaded'
                            ];
                        } else {
                            $checks[] = [
                                'status' => 'warning',
                                'icon' => 'fas fa-exclamation-triangle',
                                'title' => 'Mobile CSS Missing',
                                'message' => 'Mobile enhancement stylesheet not found'
                            ];
                        }
                        
                        // Display checks
                        foreach ($checks as $check) {
                            $statusClass = 'check-' . $check['status'];
                            echo "<div class='check-item $statusClass'>";
                            echo "<div class='d-flex align-items-center'>";
                            echo "<i class='{$check['icon']} fa-lg me-3'></i>";
                            echo "<div>";
                            echo "<h6 class='mb-1'>{$check['title']}</h6>";
                            echo "<p class='mb-0'>{$check['message']}</p>";
                            echo "</div></div></div>";
                        }
                        ?>
                        
                        <!-- Network Information -->
                        <?php if ($networkConfig): ?>
                        <div class="network-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Current Network Setup</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Server IP:</strong> <?php echo $networkConfig['server_ip']; ?><br>
                                    <strong>Port:</strong> <?php echo $networkConfig['server_port']; ?><br>
                                </div>
                                <div class="col-md-6">
                                    <strong>Base URL:</strong> <?php echo $networkConfig['base_url']; ?><br>
                                    <strong>Started:</strong> <?php echo $networkConfig['started_at']; ?><br>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Instructions -->
                        <div class="mt-4">
                            <h5><i class="fas fa-mobile-alt me-2"></i>How to Access on Mobile</h5>
                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item">Make sure your phone and computer are on the same WiFi network</li>
                                <li class="list-group-item">Start the mobile server: <code>php server.php</code></li>
                                <li class="list-group-item">On your phone, open browser and go to: <strong><?php echo $baseURL; ?></strong></li>
                                <li class="list-group-item">Scan QR codes to open cabinet details directly</li>
                            </ol>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-home me-2"></i>Go to Main Site
                            </a>
                            <button onclick="location.reload()" class="btn btn-outline-secondary btn-lg ms-2">
                                <i class="fas fa-sync me-2"></i>Refresh Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>