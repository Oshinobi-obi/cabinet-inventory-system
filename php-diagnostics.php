<?php
// PHP Diagnostics - Cabinet Information System
// Check PHP configuration and available extensions

echo "<!DOCTYPE html>\n<html><head><title>PHP Diagnostics</title>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;}</style></head><body>";

echo "<h1>🔧 PHP Diagnostics Report</h1>\n";
echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

// PHP Version
echo "<h2>📊 PHP Information</h2>\n";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>\n";
echo "<p><strong>PHP SAPI:</strong> " . PHP_SAPI . "</p>\n";
echo "<p><strong>OS:</strong> " . PHP_OS . "</p>\n";

// Check MySQL Extensions
echo "<h2>🗄️ Database Extensions</h2>\n";

$extensions = [
    'pdo' => 'PDO (PHP Data Objects)',
    'pdo_mysql' => 'PDO MySQL Driver',
    'mysqli' => 'MySQLi Extension',
    'mysql' => 'Legacy MySQL Extension (deprecated)'
];

foreach ($extensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? '<span class="ok">✓ LOADED</span>' : '<span class="error">✗ MISSING</span>';
    echo "<p><strong>$desc ($ext):</strong> $status</p>\n";
}

// Show all loaded extensions
echo "<h2>📦 All Loaded Extensions</h2>\n";
$loaded_extensions = get_loaded_extensions();
sort($loaded_extensions);
echo "<pre>";
foreach ($loaded_extensions as $ext) {
    echo "• $ext\n";
}
echo "</pre>";

// Try database connection test
echo "<h2>🔌 Database Connection Test</h2>\n";

try {
    // Test PDO MySQL connection
    echo "<h3>PDO MySQL Test:</h3>\n";
    if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
        $pdo = new PDO('mysql:host=localhost;dbname=cabinet_info_system', 'root', 'Mico2025!');
        echo '<p class="ok">✓ PDO MySQL connection successful!</p>';
        $pdo = null;
    } else {
        echo '<p class="error">✗ PDO MySQL extensions not available</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">✗ PDO MySQL connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

try {
    // Test MySQLi connection
    echo "<h3>MySQLi Test:</h3>\n";
    if (extension_loaded('mysqli')) {
        $mysqli = new mysqli('localhost', 'root', 'Mico2025!', 'cabinet_info_system');
        if ($mysqli->connect_error) {
            echo '<p class="error">✗ MySQLi connection failed: ' . htmlspecialchars($mysqli->connect_error) . '</p>';
        } else {
            echo '<p class="ok">✓ MySQLi connection successful!</p>';
            $mysqli->close();
        }
    } else {
        echo '<p class="error">✗ MySQLi extension not available</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">✗ MySQLi connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// PHP Configuration
echo "<h2>⚙️ PHP Configuration</h2>\n";
$config_items = [
    'extension_dir' => ini_get('extension_dir'),
    'include_path' => ini_get('include_path'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

foreach ($config_items as $key => $value) {
    echo "<p><strong>$key:</strong> " . htmlspecialchars($value) . "</p>\n";
}

// Check for php.ini files
echo "<h2>📄 PHP Configuration Files</h2>\n";
echo "<p><strong>Loaded php.ini:</strong> " . php_ini_loaded_file() . "</p>\n";
$scanned = php_ini_scanned_files();
if ($scanned) {
    echo "<p><strong>Additional ini files:</strong></p>\n<pre>" . htmlspecialchars($scanned) . "</pre>\n";
}

echo "</body></html>";
?>