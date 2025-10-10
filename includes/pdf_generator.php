<?php
/**
 * PDF Generator for Cabinet Inventory System
 * Generates PDF reports using HTML-to-PDF conversion
 */

require_once 'config.php';

class PDFGenerator {
    
    public static function generateCabinetReport($cabinetId, $cabinet, $items) {
        // Generate HTML content optimized for PDF conversion
        $html = self::generateHTMLContent($cabinet, $items);
        
        // Set headers for HTML that will be converted to PDF by browser
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="cabinet_report_' . $cabinet['cabinet_number'] . '_' . date('Y-m-d') . '.html"');
        
        echo $html;
    }
    
    private static function generateHTMLContent($cabinet, $items) {
        $qrFile = null;
        if (!empty($cabinet['qr_path']) && file_exists($cabinet['qr_path'])) {
            $qrFile = $cabinet['qr_path'];
        }
        
        // Generate items HTML
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
                
                $itemsHtml .= '<div style="flex: 1; height: auto;">
                    <table style="width: 100%; border-collapse: collapse; margin: 0; table-layout: fixed; height: auto;">
                        <thead>
                            <tr style="background-color: #f0f0f0;">
                                <th style="width: 20%; font-size: 8px;">No.</th>
                                <th style="width: 40%; font-size: 8px;">Item</th>
                                <th style="width: 20%; font-size: 8px;">Qty</th>
                                <th style="width: 20%; font-size: 8px;">Category</th>
                            </tr>
                        </thead>
                        <tbody>';
                
                foreach ($columnItems as $index => $item) {
                    $itemNumber = $startIndex + $index + 1;
                    $itemsHtml .= '<tr>
                        <td style="font-size: 8px;">' . $itemNumber . '</td>
                        <td style="font-size: 8px;">' . htmlspecialchars($item['name']) . '</td>
                        <td style="font-size: 8px;">' . htmlspecialchars($item['quantity']) . '</td>
                        <td style="font-size: 8px;">' . htmlspecialchars($item['category']) . '</td>
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
        
        // Generate QR code HTML
        $qrHtml = '';
        if ($qrFile) {
            $qrHtml = '<img src="' . htmlspecialchars($qrFile) . '" alt="QR CODE" style="width: 110px; height: 110px;">';
        } else {
            $qrHtml = '<span style="font-size: 10px; font-weight: bold;">QR CODE</span>';
        }
        
        // Generate the complete HTML
        $html = '<!DOCTYPE html>
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
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background: white;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
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
        
        .pdf-export {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .printing {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
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
                Policy Planning and Research Division - Cabinet Management System | Report Date: ' . date('Y-m-d') . ' | Page: 1
            </p>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            setTimeout(function() {
                document.body.classList.add("pdf-export");
                window.print();
                window.onafterprint = function() {
                    window.close();
                };
            }, 1500);
        };
        
        window.addEventListener("beforeprint", function() {
            document.body.classList.add("printing");
        });
        
        window.addEventListener("afterprint", function() {
            document.body.classList.remove("printing");
        });
    </script>
</body>
</html>';
        
        return $html;
    }
}
?>