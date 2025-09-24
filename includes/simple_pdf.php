<?php
/**
 * Simple PDF Generator using HTML-to-PDF conversion
 * This creates a proper PDF file that can be opened by browsers
 */

require_once 'config.php';

class SimplePDF {
    
    public static function generateCabinetPDF($cabinetId, $cabinet, $items) {
        // Get QR code path
        $qrFile = null;
        if (!empty($cabinet['qr_path'])) {
            // Check if it's a relative path and make it absolute
            $qrPath = $cabinet['qr_path'];
            if (!file_exists($qrPath)) {
                // Try with qrcodes/ prefix
                $qrPath = 'qrcodes/' . basename($qrPath);
            }
            if (!file_exists($qrPath)) {
                // Try with ../qrcodes/ prefix (from includes directory)
                $qrPath = '../qrcodes/' . basename($cabinet['qr_path']);
            }
            if (file_exists($qrPath)) {
                $qrFile = $qrPath;
            }
        }
        
        // Create HTML content
        $html = self::createHTMLContent($cabinet, $items, $qrFile);
        
        // Set headers for HTML that will be converted to PDF
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('X-Frame-Options: DENY');
        
        echo $html;
    }
    
    private static function createHTMLContent($cabinet, $items, $qrFile) {
        $itemsHtml = '';
        if (!empty($items)) {
            $itemCount = count($items);
            $itemsPerColumn = 8;
            $columnCount = ceil($itemCount / $itemsPerColumn);
            $columnCount = min($columnCount, 4);
            
            $itemsHtml .= '<div style="display: flex; gap: 5px; align-items: flex-start;">';
            
            for ($col = 0; $col < $columnCount; $col++) {
                $startIndex = $col * $itemsPerColumn;
                $endIndex = min(($col + 1) * $itemsPerColumn, $itemCount);
                $columnItems = array_slice($items, $startIndex, $endIndex - $startIndex);
                
                $itemsHtml .= '<div style="flex: 1;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                        <thead>
                            <tr style="background-color: #f0f0f0;">
                                <th style="border: 1px solid #000; padding: 2px; width: 20%;">No.</th>
                                <th style="border: 1px solid #000; padding: 2px; width: 40%;">Item</th>
                                <th style="border: 1px solid #000; padding: 2px; width: 20%;">Qty</th>
                                <th style="border: 1px solid #000; padding: 2px; width: 20%;">Category</th>
                            </tr>
                        </thead>
                        <tbody>';
                
                foreach ($columnItems as $index => $item) {
                    $itemNumber = $startIndex + $index + 1;
                    $itemsHtml .= '<tr>
                        <td style="border: 1px solid #000; padding: 2px;">' . $itemNumber . '</td>
                        <td style="border: 1px solid #000; padding: 2px;">' . htmlspecialchars($item['name']) . '</td>
                        <td style="border: 1px solid #000; padding: 2px;">' . htmlspecialchars($item['quantity']) . '</td>
                        <td style="border: 1px solid #000; padding: 2px;">' . htmlspecialchars($item['category']) . '</td>
                    </tr>';
                }
                
                $itemsHtml .= '</tbody>
                    </table>
                </div>';
            }
            
            $itemsHtml .= '</div>';
        } else {
            $itemsHtml = '<div style="border: 2px solid #000; padding: 20px; text-align: center;">
                <p style="font-style: italic; font-size: 12px;">No items found in this cabinet</p>
            </div>';
        }
        
        $qrHtml = '';
        if ($qrFile) {
            $qrHtml = '<img src="' . htmlspecialchars($qrFile) . '" alt="QR CODE" style="width: 110px; height: 110px;">';
        } else {
            $qrHtml = '<span style="font-size: 10px; font-weight: bold;">QR CODE</span>';
        }
        
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinet Report - ' . htmlspecialchars($cabinet['name']) . '</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5in;
        }
        
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background: white;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 100%;
            margin: 0;
            padding: 0;
        }
        
        .main-layout {
            display: flex;
            gap: 15px;
            border: 2px solid #000;
            padding: 15px;
        }
        
        .qr-section {
            width: 140px;
            text-align: center;
            border-right: 2px solid #000;
            padding-right: 10px;
        }
        
        .details-section {
            flex-grow: 1;
        }
        
        .qr-code {
            border: 2px solid #000;
            width: 120px;
            height: 120px;
            margin: 8px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 15px;
            font-size: 11px;
            color: #666;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-layout">
            <div class="qr-section">
                <h2 style="margin: 0 0 5px 0; font-size: 14px; font-weight: bold;">
                    ' . htmlspecialchars($cabinet['name']) . '
                </h2>
                <p style="margin: 0 0 8px 0; font-size: 11px; font-weight: bold;">
                    (' . htmlspecialchars($cabinet['cabinet_number']) . ')
                </p>
                <div class="qr-code">
                    ' . $qrHtml . '
                </div>
                <p style="margin: 5px 0 0 0; font-size: 9px; line-height: 1.2;">
                    Last Updated:<br>
                    ' . date('M d, Y') . '
                </p>
            </div>
            <div class="details-section">
                <h2 style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold; text-align: center;">Cabinet Details - All Items</h2>
                ' . $itemsHtml . '
            </div>
        </div>
        <div class="footer">
            <p style="margin: 5px 0; font-size: 11px; color: #666;">
                Cabinet Information System - Cabinet Information System | Report Date: ' . date('Y-m-d') . ' | Page: 1
            </p>
        </div>
    </div>
    
    <script>
        // Auto-print when loaded
        window.onload = function() {
            // Add a small delay to ensure content is fully loaded
            setTimeout(function() {
                // Trigger print dialog immediately
                window.print();
                
                // Close window after print dialog is handled
                window.onafterprint = function() {
                    window.close();
                };
            }, 500);
        };
        
        // Handle print events
        window.addEventListener("beforeprint", function() {
            console.log("Print dialog opening...");
        });
        
        window.addEventListener("afterprint", function() {
            console.log("Print dialog closed, closing window...");
            setTimeout(function() {
                window.close();
            }, 100);
        });
    </script>
</body>
</html>';
    }
}
?>
