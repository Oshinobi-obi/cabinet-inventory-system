<?php
/**
 * PHP Development Server for Network Access
 * This script starts a PHP server that can be accessed from mobile devices on the same network
 */

// Get the local IP address (Windows-compatible)
function getLocalIP() {
    $localIP = null;
    
    // Method 1: Try using shell command (Windows)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows method using ipconfig
        $output = shell_exec('ipconfig | findstr /i "IPv4"');
        if ($output) {
            // Extract IP addresses from ipconfig output
            preg_match_all('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $output, $matches);
            if (isset($matches[1])) {
                foreach ($matches[1] as $ip) {
                    // Skip localhost and look for private network IPs
                    if ($ip !== '127.0.0.1' && 
                        (strpos($ip, '192.168.') === 0 || 
                         strpos($ip, '10.') === 0 || 
                         strpos($ip, '172.') === 0)) {
                        $localIP = $ip;
                        break;
                    }
                }
            }
        }
    } else {
        // Unix/Linux/Mac method
        $output = shell_exec("ifconfig | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | head -1");
        if ($output) {
            $localIP = trim($output);
        }
    }
    
    // Method 2: Try using hostname (fallback)
    if (!$localIP) {
        $hostname = gethostname();
        $localIP = gethostbyname($hostname);
        
        // If it returns the same as hostname or localhost, it didn't work
        if ($localIP === $hostname || $localIP === '127.0.0.1') {
            $localIP = null;
        }
    }
    
    // Method 3: Try to get from $_SERVER if available (when running in web context)
    if (!$localIP && isset($_SERVER['SERVER_ADDR'])) {
        $serverAddr = $_SERVER['SERVER_ADDR'];
        if ($serverAddr !== '127.0.0.1' && $serverAddr !== '::1') {
            $localIP = $serverAddr;
        }
    }
    
    // Final fallback
    if (!$localIP || $localIP === '127.0.0.1') {
        $localIP = '127.0.0.1';
        echo "⚠️  Warning: Could not detect network IP. Using localhost only.\n";
        echo "   Make sure you're connected to a network to access from mobile devices.\n";
        echo "   Try running: ipconfig (Windows) or ifconfig (Mac/Linux) to see your IP.\n\n";
    }
    
    return $localIP;
}

// Configuration
$port = 8080;
$host = '0.0.0.0'; // Bind to all interfaces
$localIP = getLocalIP();

// Display startup information
echo "🚀 Starting Cabinet Information System Server...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📱 Mobile Access URLs:\n";
echo "   Local:    http://localhost:$port\n";
echo "   Network:  http://$localIP:$port\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 QR Code Scan URL: http://$localIP:$port\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "💡 Tips:\n";
echo "   • Make sure your phone and computer are on the same WiFi network\n";
echo "   • Use the Network URL on your phone's browser\n";
echo "   • QR codes will automatically use the network URL\n";
echo "   • Press Ctrl+C to stop the server\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Save network configuration for the application
$config = [
    'server_ip' => $localIP,
    'server_port' => $port,
    'base_url' => "http://$localIP:$port",
    'started_at' => date('Y-m-d H:i:s')
];

file_put_contents('network_config.json', json_encode($config, JSON_PRETTY_PRINT));

// Start the PHP development server
$command = "php -S $host:$port";
echo "🔄 Executing: $command\n";
echo "📊 Server Status: Starting...\n\n";

// Execute the server command
passthru($command);
?>