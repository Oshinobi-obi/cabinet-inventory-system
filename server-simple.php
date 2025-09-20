<?php

/**
 * Simple PHP Development Server for Network Access (Windows Compatible)
 * No socket dependencies - uses Windows commands for IP detection
 */

// Simple IP detection without socket functions
function getWindowsLocalIP()
{
    echo "🔍 Detecting network IP address...\n";

    // Method 1: Use ipconfig on Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $output = shell_exec('ipconfig');
        if ($output) {
            echo "📋 Network interfaces found:\n";
            // Look for IPv4 addresses
            preg_match_all('/IPv4 Address[.\s]*:\s*(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $output, $matches);

            if (isset($matches[1])) {
                $possibleIPs = [];
                foreach ($matches[1] as $ip) {
                    if ($ip !== '127.0.0.1') {
                        $possibleIPs[] = $ip;
                        echo "   • $ip\n";
                    }
                }

                // Prefer private network IPs
                foreach ($possibleIPs as $ip) {
                    if (
                        strpos($ip, '192.168.') === 0 ||
                        strpos($ip, '10.') === 0 ||
                        strpos($ip, '172.') === 0
                    ) {
                        echo "✅ Selected network IP: $ip\n";
                        return $ip;
                    }
                }

                // If no private IP found, use the first non-localhost IP
                if (!empty($possibleIPs)) {
                    echo "✅ Selected network IP: {$possibleIPs[0]}\n";
                    return $possibleIPs[0];
                }
            }
        }
    }

    // Fallback: Use hostname resolution
    $hostname = gethostname();
    $ip = gethostbyname($hostname);

    if ($ip && $ip !== $hostname && $ip !== '127.0.0.1') {
        echo "✅ Using hostname resolution: $ip\n";
        return $ip;
    }

    // Final fallback
    echo "⚠️  Could not detect network IP automatically.\n";
    echo "   Using localhost. You can manually check your IP with 'ipconfig'\n";
    return '127.0.0.1';
}

// Configuration
$port = 8080;
$host = '0.0.0.0'; // Bind to all interfaces
$localIP = getWindowsLocalIP();

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🚀 Cabinet Information System - Mobile Server\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📱 Access URLs:\n";
echo "   Local:    http://localhost:$port\n";
echo "   Network:  http://$localIP:$port\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📱 For Mobile Access:\n";
echo "   1. Connect your phone to the same WiFi network\n";
echo "   2. Open browser on your phone\n";
echo "   3. Go to: http://$localIP:$port\n";
echo "   4. QR codes will automatically work with this URL\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🛑 Press Ctrl+C to stop the server\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Save network configuration for the application
$config = [
    'server_ip' => $localIP,
    'server_port' => $port,
    'base_url' => "http://$localIP:$port",
    'started_at' => date('Y-m-d H:i:s'),
    'os' => PHP_OS
];

file_put_contents('network_config.json', json_encode($config, JSON_PRETTY_PRINT));
echo "💾 Network configuration saved to network_config.json\n\n";

// Start the PHP development server
$command = "php -S $host:$port";
echo "▶️  Starting server: $command\n";
echo "🌐 Server running...\n\n";

// Execute the server command
passthru($command);
