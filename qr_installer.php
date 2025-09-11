<?php
/**
 * QR Code Library Installer and Real QR Generator
 */

// Include functions for generateRealQRCode
require_once 'includes/functions.php';

// Function to download and install PHP QR Code library
function installQRCodeLibrary() {
    $messages = [];
    
    // Check if already installed
    if (is_dir('phpqrcode') && file_exists('phpqrcode/qrlib.php')) {
        $messages[] = "✅ PHP QR Code library already installed";
        return [true, $messages];
    }
    
    // Method 1: Download from GitHub
    $libraryUrl = 'https://github.com/t0k4rt/phpqrcode/archive/refs/heads/master.zip';
    $zipFile = 'phpqrcode-master.zip';
    
    $messages[] = "Downloading PHP QR Code library...";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'timeout' => 30
        ]
    ]);
    
    $zipData = @file_get_contents($libraryUrl, false, $context);
    
    if ($zipData === false) {
        $messages[] = "❌ Failed to download from GitHub";
        return [false, $messages];
    }
    
    $messages[] = "✅ Downloaded " . strlen($zipData) . " bytes";
    
    // Save zip file
    if (file_put_contents($zipFile, $zipData) === false) {
        $messages[] = "❌ Failed to save zip file";
        return [false, $messages];
    }
    
    $messages[] = "✅ Zip file saved";
    
    // Check if ZipArchive is available
    if (!class_exists('ZipArchive')) {
        $messages[] = "❌ ZipArchive class not available - manual extraction needed";
        $messages[] = "Download and extract manually: $libraryUrl";
        return [false, $messages];
    }
    
    // Extract zip
    $zip = new ZipArchive;
    $result = $zip->open($zipFile);
    
    if ($result === TRUE) {
        $zip->extractTo('./');
        $zip->close();
        $messages[] = "✅ Zip extracted";
        
        // Rename directory
        if (is_dir('phpqrcode-master')) {
            if (rename('phpqrcode-master', 'phpqrcode')) {
                $messages[] = "✅ Directory renamed to phpqrcode";
            } else {
                $messages[] = "❌ Failed to rename directory";
            }
        }
        
        // Clean up zip file
        unlink($zipFile);
        $messages[] = "✅ Cleanup completed";
        
        // Verify installation
        if (file_exists('phpqrcode/qrlib.php')) {
            $messages[] = "✅ PHP QR Code library successfully installed!";
            return [true, $messages];
        } else {
            $messages[] = "❌ Installation verification failed";
            return [false, $messages];
        }
        
    } else {
        $messages[] = "❌ Failed to extract zip file (Error: $result)";
        return [false, $messages];
    }
}

// Function to generate QR using installed library
function generateWithLibrary($data, $filename) {
    if (!file_exists('phpqrcode/qrlib.php')) {
        return false;
    }
    
    require_once 'phpqrcode/qrlib.php';
    
    if (!class_exists('QRcode')) {
        return false;
    }
    
    try {
        // Use QRcode class to generate PNG
        call_user_func_array(['QRcode', 'png'], [$data, $filename, 'M', 10, 2]);
        return true;
    } catch (Exception $e) {
        error_log('QR library error: ' . $e->getMessage());
        return false;
    }
}

// Handle installation request
if (isset($_POST['install_library'])) {
    [$success, $messages] = installQRCodeLibrary();
    $installResult = ['success' => $success, 'messages' => $messages];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Code Library Installer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .message { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .test-result { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>QR Code Library Installer & Tester</h1>
    
    <?php if (isset($installResult)): ?>
        <div class="test-result">
            <h3>Installation Result</h3>
            <?php foreach ($installResult['messages'] as $msg): ?>
                <div class="message <?php echo strpos($msg, '✅') !== false ? 'success' : (strpos($msg, '❌') !== false ? 'error' : 'info'); ?>">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <h2>Current Status</h2>
    <div class="test-result">
        <?php
        $phpqrExists = file_exists('phpqrcode/qrlib.php');
        $zipArchiveExists = class_exists('ZipArchive');
        $gdExists = extension_loaded('gd');
        ?>
        
        <p>PHP QR Code Library: <?php echo $phpqrExists ? '✅ Installed' : '❌ Not installed'; ?></p>
        <p>ZipArchive Extension: <?php echo $zipArchiveExists ? '✅ Available' : '❌ Not available'; ?></p>
        <p>GD Extension: <?php echo $gdExists ? '✅ Available' : '❌ Not available'; ?></p>
        <p>QRCodes Directory: <?php echo is_dir('qrcodes') ? '✅ Exists' : '❌ Missing'; ?></p>
        
        <?php if (!is_dir('qrcodes')): ?>
            <?php mkdir('qrcodes', 0755, true); ?>
            <p>✅ QRCodes directory created</p>
        <?php endif; ?>
    </div>
    
    <?php if (!$phpqrExists): ?>
        <h2>Install PHP QR Code Library</h2>
        <form method="post">
            <button type="submit" name="install_library" style="padding: 10px 20px; font-size: 16px;">
                Install PHP QR Code Library
            </button>
        </form>
        
        <div class="message info">
            <strong>Note:</strong> This will download the PHP QR Code library from GitHub and install it locally.
            If automatic installation fails, you can manually download from: 
            <a href="https://github.com/t0k4rt/phpqrcode" target="_blank">https://github.com/t0k4rt/phpqrcode</a>
        </div>
    <?php endif; ?>
    
    <h2>Test QR Generation</h2>
    <div class="test-result">
        <?php
        $testData = "http://localhost/cabinet-inventory-system/index.php?cabinet=TEST123";
        $testFile = "qrcodes/library_test.png";
        
        echo "<p>Testing QR generation for: <code>$testData</code></p>";
        
        if ($phpqrExists) {
            if (generateWithLibrary($testData, $testFile)) {
                echo "<p>✅ QR Code generated with library!</p>";
                if (file_exists($testFile)) {
                    $fileSize = filesize($testFile);
                    echo "<p>File size: $fileSize bytes</p>";
                    echo "<img src='$testFile' alt='Library QR Code' style='max-width: 200px; border: 1px solid #ccc;'>";
                }
            } else {
                echo "<p>❌ Library QR generation failed</p>";
            }
        } else {
            echo "<p>⚠️ PHP QR Code library not installed - install it first</p>";
        }
        
        // Test external API fallback
        echo "<h3>Testing External API Fallback</h3>";
        $apiFile = "qrcodes/api_test.png";
        
        if (generateRealQRCode($testData, $apiFile)) {
            echo "<p>✅ External API QR Code generated!</p>";
            if (file_exists($apiFile)) {
                $fileSize = filesize($apiFile);
                echo "<p>File size: $fileSize bytes</p>";
                echo "<img src='$apiFile' alt='API QR Code' style='max-width: 200px; border: 1px solid #ccc;'>";
            }
        } else {
            echo "<p>❌ External API QR generation failed</p>";
        }
        ?>
    </div>
    
    <h2>Quick Actions</h2>
    <p>
        <a href="test_real_qr.php">🧪 Test Real QR Generation</a> |
        <a href="cabinet.php">📦 Cabinet Management</a> |
        <a href="qr_database_test.php">💾 Database Test</a>
    </p>
    
</body>
</html>
