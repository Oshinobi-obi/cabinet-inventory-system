<?php
// Simple QR Code downloader and installer
echo "<h2>QR Code Library Installer</h2>";

$phpqrDir = __DIR__ . '/phpqrcode';
$qrLibUrl = 'https://github.com/t0k4rt/phpqrcode/archive/refs/heads/master.zip';
$zipFile = __DIR__ . '/phpqrcode.zip';

if (!is_dir($phpqrDir)) {
    mkdir($phpqrDir, 0755, true);
}

echo "<h3>Downloading PHP QR Code Library...</h3>";

// Try to download the library
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $qrLibUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$zipContent = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $zipContent) {
    file_put_contents($zipFile, $zipContent);
    echo "✅ Downloaded successfully!<br>";

    // Extract the zip file
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo(__DIR__ . '/temp/');
        $zip->close();

        // Move files to correct location
        $extractedDir = __DIR__ . '/temp/phpqrcode-master';
        if (is_dir($extractedDir)) {
            // Copy all files from extracted directory to phpqrcode directory
            $files = glob($extractedDir . '/*');
            foreach ($files as $file) {
                $fileName = basename($file);
                if (is_file($file)) {
                    copy($file, $phpqrDir . '/' . $fileName);
                }
            }
            echo "✅ Files extracted to phpqrcode directory!<br>";

            // Clean up
            unlink($zipFile);
            array_map('unlink', glob(__DIR__ . '/temp/phpqrcode-master/*'));
            rmdir(__DIR__ . '/temp/phpqrcode-master');
            rmdir(__DIR__ . '/temp');
        } else {
            echo "❌ Extraction failed - directory structure unexpected<br>";
        }
    } else {
        echo "❌ Failed to extract zip file<br>";
    }
} else {
    echo "❌ Failed to download library (HTTP Code: $httpCode)<br>";
    echo "Please download manually from: http://phpqrcode.sourceforge.net/<br>";
}

// Test if installation was successful
if (file_exists(__DIR__ . '/phpqrcode/qrlib.php')) {
    echo "<h3>✅ Installation Successful!</h3>";
    echo "QR Code library is now ready to use.<br>";
    echo "<a href='cabinet.php' class='btn btn-primary'>Go to Cabinet Management</a>";
} else {
    echo "<h3>❌ Installation Failed</h3>";
    echo "Please install manually or use the alternative Google Charts method.<br>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    .btn {
        padding: 10px 20px;
        background: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        display: inline-block;
        margin-top: 10px;
    }
</style>