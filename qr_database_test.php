<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h1>QR Code Generation and Database Save Test</h1>";

// Test 1: Check if qrcodes directory exists and is writable
$qrDir = 'qrcodes/';
if (!is_dir($qrDir)) {
    if (mkdir($qrDir, 0755, true)) {
        echo "<p>✅ Created qrcodes directory</p>";
    } else {
        echo "<p>❌ Failed to create qrcodes directory</p>";
    }
} else {
    echo "<p>✅ qrcodes directory exists</p>";
}

if (is_writable($qrDir)) {
    echo "<p>✅ qrcodes directory is writable</p>";
} else {
    echo "<p>❌ qrcodes directory is not writable</p>";
}

// Test 2: Get a cabinet to test with
try {
    $stmt = $pdo->query("SELECT id, cabinet_number, name, qr_path FROM cabinets LIMIT 1");
    $testCabinet = $stmt->fetch();
    
    if ($testCabinet) {
        echo "<h2>Testing with Cabinet: {$testCabinet['name']} ({$testCabinet['cabinet_number']})</h2>";
        
        // Test 3: Generate QR code and save to database
        echo "<h3>Current QR Path: " . ($testCabinet['qr_path'] ?? 'NULL') . "</h3>";
        
        echo "<h3>Testing generateAndSaveQRCodeToDB function...</h3>";
        [$success, $result, $cabinet] = generateAndSaveQRCodeToDB($pdo, $testCabinet['id']);
        
        if ($success) {
            echo "<p>✅ QR generation successful!</p>";
            echo "<p>QR Path: {$result}</p>";
            echo "<p>File exists: " . (file_exists($result) ? "✅ Yes" : "❌ No") . "</p>";
            
            if (file_exists($result)) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; display: inline-block;'>";
                echo "<img src='{$result}' alt='Generated QR Code' style='max-width: 200px;'>";
                echo "</div>";
            }
            
            // Check database was updated
            $stmt = $pdo->prepare("SELECT qr_path FROM cabinets WHERE id = ?");
            $stmt->execute([$testCabinet['id']]);
            $updatedCabinet = $stmt->fetch();
            echo "<p>Database QR Path: {$updatedCabinet['qr_path']}</p>";
            
        } else {
            echo "<p>❌ QR generation failed: {$result}</p>";
        }
        
        // Test 4: Test the old generateQRCode function with saveToFile = true
        echo "<h3>Testing generateQRCode function with saveToFile = true...</h3>";
        $qrPath = generateQRCode($testCabinet['cabinet_number'], true);
        
        if ($qrPath) {
            echo "<p>✅ generateQRCode returned: {$qrPath}</p>";
            echo "<p>File exists: " . (file_exists($qrPath) ? "✅ Yes" : "❌ No") . "</p>";
            
            if (file_exists($qrPath)) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; display: inline-block; margin-left: 20px;'>";
                echo "<img src='{$qrPath}' alt='Direct QR Code' style='max-width: 200px;'>";
                echo "</div>";
            }
        } else {
            echo "<p>❌ generateQRCode failed to save file</p>";
        }
        
        // Test 5: Test the old generateQRCode function without saveToFile (URL mode)
        echo "<h3>Testing generateQRCode function for URL display...</h3>";
        $qrUrl = generateQRCode($testCabinet['cabinet_number'], false);
        
        if ($qrUrl) {
            echo "<p>✅ generateQRCode URL returned: {$qrUrl}</p>";
            echo "<div style='border: 1px solid #ccc; padding: 10px; display: inline-block; margin-left: 40px;'>";
            echo "<img src='{$qrUrl}' alt='URL QR Code' style='max-width: 200px;'>";
            echo "</div>";
        } else {
            echo "<p>❌ generateQRCode failed to return URL</p>";
        }
        
    } else {
        echo "<p>❌ No cabinets found in database for testing</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>QRCodes Directory Contents:</h2>";
$files = glob('qrcodes/*');
if ($files) {
    foreach ($files as $file) {
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "<p>{$file} - {$size} bytes - Modified: {$modified}</p>";
    }
} else {
    echo "<p>No files in qrcodes directory</p>";
}
?>

<hr>
<p><a href="cabinet.php">← Back to Cabinet Management</a></p>
<p><a href="qr_test.php">QR Generation Methods Test</a></p>
