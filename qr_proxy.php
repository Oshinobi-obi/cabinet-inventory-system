<?php
// QR Code Proxy - fetches QR codes from Google Charts and serves them locally
// This bypasses CSP restrictions by serving images from the same domain
// Note: No authentication required for QR generation

if (!isset($_GET['data'])) {
    http_response_code(400);
    die('Missing data parameter');
}

$qrContent = $_GET['data'];
$size = isset($_GET['size']) ? intval($_GET['size']) : 300;
$size = max(100, min(1000, $size)); // Limit size between 100-1000px

$cacheKey = md5($qrContent . $size);
$cacheDir = __DIR__ . '/qrcodes/';
$cacheFile = $cacheDir . 'proxy_' . $cacheKey . '.png';

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
    // Serve cached version
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400'); // Cache for 24 hours
    readfile($cacheFile);
    exit;
}

$googleUrl = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($qrContent) . '&choe=UTF-8';

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'Cabinet Information System QR Proxy',
        'method' => 'GET'
    ]
]);

$qrData = @file_get_contents($googleUrl, false, $context);

if ($qrData !== false && strlen($qrData) > 0) {
    file_put_contents($cacheFile, $qrData);

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    echo $qrData;
} else {
    $img = imagecreate($size, $size);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $gray = imagecolorallocate($img, 128, 128, 128);
    
    imagefill($img, 0, 0, $white);
    imagerectangle($img, 10, 10, $size-10, $size-10, $black);
    
    // Add text
    $text = "QR CODE";
    if (function_exists('imagettftext')) {
        imagestring($img, 4, $size/2 - 30, $size/2 - 10, $text, $black);
        imagestring($img, 3, $size/2 - 50, $size/2 + 10, "Generation Failed", $gray);
    } else {
        imagestring($img, 3, $size/2 - 30, $size/2 - 10, $text, $black);
        imagestring($img, 2, $size/2 - 50, $size/2 + 10, "Generation Failed", $gray);
    }
    
    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
}
?>
