<?php
/**
 * Database Connection Test Script
 * This script helps debug database connection issues
 */

echo "<h2>Database Connection Test</h2>";

// Test 1: Check if we can detect the network IP
echo "<h3>1. Network Detection Test</h3>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "<br>";
echo "SERVER_ADDR: " . ($_SERVER['SERVER_ADDR'] ?? 'not set') . "<br>";
echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'not set') . "<br>";

// Test 2: Check network config file
echo "<h3>2. Network Config Test</h3>";
$networkConfigFile = 'includes/network_config.json';
if (file_exists($networkConfigFile)) {
    $networkConfig = json_decode(file_get_contents($networkConfigFile), true);
    echo "Network config found:<br>";
    echo "<pre>" . json_encode($networkConfig, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "Network config file not found at: $networkConfigFile<br>";
}

// Test 3: Test database connection with different hosts
echo "<h3>3. Database Connection Tests</h3>";

$hosts = ['localhost', '127.0.0.1', '192.168.102.201'];
$username = 'root';
$password = 'Mico123!';
$dbname = 'cabinet_info_system';
$port = '3306';

foreach ($hosts as $host) {
    echo "<strong>Testing connection to: $host</strong><br>";
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ <span style='color: green;'>SUCCESS: Connected to $host</span><br>";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "✅ Database query successful. Users table has {$result['count']} records.<br>";
        
    } catch (PDOException $e) {
        echo "❌ <span style='color: red;'>FAILED: " . $e->getMessage() . "</span><br>";
    }
    echo "<br>";
}

// Test 4: Show what the config.php would use
echo "<h3>4. Config.php Host Detection Test</h3>";
include_once 'includes/config.php';
echo "DB_HOST that would be used: " . DB_HOST . "<br>";
echo "DB_PORT: " . DB_PORT . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";

echo "<h3>5. MySQL Service Status</h3>";
echo "Checking if MySQL is running on port 3306...<br>";

// Check if MySQL is listening on port 3306
$connection = @fsockopen('localhost', 3306, $errno, $errstr, 5);
if ($connection) {
    echo "✅ MySQL is listening on localhost:3306<br>";
    fclose($connection);
} else {
    echo "❌ MySQL is not listening on localhost:3306<br>";
}

$connection = @fsockopen('192.168.102.201', 3306, $errno, $errstr, 5);
if ($connection) {
    echo "✅ MySQL is listening on 192.168.102.201:3306<br>";
    fclose($connection);
} else {
    echo "❌ MySQL is not listening on 192.168.102.201:3306<br>";
}

echo "<br><strong>Recommendations:</strong><br>";
echo "1. Make sure MySQL is configured to accept connections from network IPs<br>";
echo "2. Check MySQL bind-address in my.ini (should be 0.0.0.0)<br>";
echo "3. Ensure MySQL user has network access permissions<br>";
echo "4. Check Windows Firewall settings<br>";
?>
