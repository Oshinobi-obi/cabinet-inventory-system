<?php
// Simple setup checker for Cabinet Information System

echo "<h2>Cabinet Information System - Setup Checker</h2>";

// Check PHP version
echo "<h3>PHP Version</h3>";
$phpVersion = phpversion();
echo "Current PHP Version: <strong>$phpVersion</strong><br>";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "✅ PHP version is compatible<br>";
} else {
    echo "❌ PHP 7.4+ is required<br>";
}

// Check extensions
echo "<h3>Required Extensions</h3>";
$extensions = ['pdo', 'pdo_mysql', 'gd'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension is loaded<br>";
    } else {
        echo "❌ $ext extension is missing<br>";
    }
}

// Check directories
echo "<h3>Directory Structure</h3>";
$dirs = ['uploads', 'qrcodes', 'phpqrcode', 'includes', 'assets'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ Directory '$dir' exists<br>";
        if (is_writable($dir)) {
            echo "  ✅ Directory '$dir' is writable<br>";
        } else {
            echo "  ❌ Directory '$dir' is not writable<br>";
        }
    } else {
        echo "❌ Directory '$dir' missing<br>";
        if (in_array($dir, ['uploads', 'qrcodes'])) {
            echo "  ℹ️ This directory will be created automatically<br>";
        }
    }
}

// Check QR Code library
echo "<h3>QR Code Library & Generation</h3>";
$qrLibPath = __DIR__ . '/phpqrcode/qrlib.php';
if (file_exists($qrLibPath)) {
    echo "✅ PHP QR Code library is installed<br>";
    
    // Test if it can be included
    try {
        require_once $qrLibPath;
        if (class_exists('QRcode')) {
            echo "✅ QRcode class is available<br>";
            
            // Test QR generation
            try {
                require_once 'includes/functions.php';
                $testQR = generateQRCode('TEST123');
                if ($testQR) {
                    echo "✅ QR code generation test successful<br>";
                    if (file_exists($testQR)) {
                        echo "✅ QR code file was created<br>";
                    } else {
                        echo "ℹ️ QR code uses external URL (Google Charts)<br>";
                    }
                } else {
                    echo "❌ QR code generation test failed<br>";
                }
            } catch (Exception $e) {
                echo "❌ QR generation error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ QRcode class not found in library<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error loading QR library: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ PHP QR Code library not found<br>";
    echo "ℹ️ System will use Google Charts API as fallback<br>";
    
    // Test Google Charts fallback
    try {
        require_once 'includes/functions.php';
        $testQR = generateQRCode('TEST123');
        if ($testQR) {
            echo "✅ Google Charts QR generation available<br>";
        } else {
            echo "❌ QR generation completely failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ QR generation error: " . $e->getMessage() . "<br>";
    }
    
    echo "📥 <strong>To install PHP QR Code library:</strong><br>";
    echo "1. Visit: <a href='http://phpqrcode.sourceforge.net/' target='_blank'>http://phpqrcode.sourceforge.net/</a><br>";
    echo "2. Download the latest version<br>";
    echo "3. Extract to the 'phpqrcode/' directory<br>";
    echo "4. Ensure 'phpqrcode/qrlib.php' exists<br>";
    echo "5. Or run: <a href='install_qr.php' target='_blank'>install_qr.php</a><br>";
}

// Check database connection
echo "<h3>Database Connection</h3>";
if (file_exists('includes/config.php')) {
    echo "✅ Config file exists<br>";
    
    try {
        require_once 'includes/config.php';
        if (isset($pdo)) {
            echo "✅ Database connection successful<br>";
            
            // Check tables
            $tables = ['cabinets', 'categories', 'items', 'users'];
            $stmt = $pdo->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                if (in_array($table, $existingTables)) {
                    echo "✅ Table '$table' exists<br>";
                } else {
                    echo "❌ Table '$table' missing<br>";
                }
            }
        } else {
            echo "❌ Database connection failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Config file missing<br>";
}

echo "<h3>Summary</h3>";
echo "If all items show ✅, your system is ready to use!<br>";
echo "If you see ❌ items, please address them before using the system.<br>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #34495e; margin-top: 20px; }
</style>
