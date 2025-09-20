<?php
// Simple QR Code Generator (basic implementation)
// This creates a minimal QR code using basic PHP functions

function generateSimpleQR($text, $size = 300)
{
    // This is a very basic QR-like pattern generator
    // For production use, install the proper PHP QR Code library

    $gridSize = 25; // 25x25 grid
    $cellSize = floor($size / $gridSize);
    $actualSize = $gridSize * $cellSize;

    // Create image
    $img = imagecreate($actualSize, $actualSize);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);

    // Fill with white background
    imagefill($img, 0, 0, $white);

    // Create a pattern based on the text hash
    $hash = md5($text);
    $pattern = [];

    // Generate position markers (corners)
    $markers = [
        [0, 0],
        [0, $gridSize - 7],
        [$gridSize - 7, 0]
    ];

    foreach ($markers as $marker) {
        list($mx, $my) = $marker;
        // Draw 7x7 position marker
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                $x = $mx + $i;
                $y = $my + $j;
                if ($x < $gridSize && $y < $gridSize) {
                    $fill = ($i == 0 || $i == 6 || $j == 0 || $j == 6 ||
                        ($i >= 2 && $i <= 4 && $j >= 2 && $j <= 4)) ? $black : $white;
                    imagefilledrectangle(
                        $img,
                        $x * $cellSize,
                        $y * $cellSize,
                        ($x + 1) * $cellSize - 1,
                        ($y + 1) * $cellSize - 1,
                        $fill
                    );
                }
            }
        }
    }

    // Generate data pattern from hash
    for ($i = 0; $i < strlen($hash); $i++) {
        $hex = hexdec($hash[$i]);
        for ($bit = 0; $bit < 4; $bit++) {
            $x = ($i * 4 + $bit) % $gridSize;
            $y = floor(($i * 4 + $bit) / $gridSize) % $gridSize;

            // Skip position markers
            if (($x < 9 && $y < 9) ||
                ($x < 9 && $y > $gridSize - 9) ||
                ($x > $gridSize - 9 && $y < 9)
            ) {
                continue;
            }

            if (($hex >> $bit) & 1) {
                imagefilledrectangle(
                    $img,
                    $x * $cellSize,
                    $y * $cellSize,
                    ($x + 1) * $cellSize - 1,
                    ($y + 1) * $cellSize - 1,
                    $black
                );
            }
        }
    }

    return $img;
}

// Usage example:
if (isset($_GET['text'])) {
    $text = $_GET['text'];
    $size = isset($_GET['size']) ? intval($_GET['size']) : 300;

    $img = generateSimpleQR($text, $size);

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600');
    imagepng($img);
    imagedestroy($img);
} else {
    echo "Usage: simple_qr.php?text=your_content&size=300";
}
