<?php
/**
 * PHP Development Server for Network Access
 * This script starts a PHP server that can be accessed from mobile devices on the same network
 */

// Get the local IP address
function getLocalIP() {
    // Try to get the local IP address
    $localIP = null;
    
    // Method 1: Use socket connection (works on most systems)
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if ($sock) {
        socket_connect($sock, "8.8.8.8", 53);
        socket_getsockname($sock, $localIP);
        socket_close($sock);
    }
    
    // Fallback method if socket doesn't work
    if (!$localIP) {
        // Try to get from hostname
        $localIP = gethostbyname(trim(`hostname`));
    }
    
    // Final fallback
    if (!$localIP || $localIP === '127.0.0.1') {
        $localIP = '127.0.0.1';
        echo "⚠️  Warning: Could not detect network IP. Using localhost only.\n";
        echo "   Make sure you're connected to a network to access from mobile devices.\n\n";
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