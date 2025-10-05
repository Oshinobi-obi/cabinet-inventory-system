<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../includes/config.php';
} catch (Exception $e) {
    die("Config error: " . $e->getMessage());
}

// Handle AJAX search requests
if (isset($_GET['ajax']) && $_GET['ajax'] === 'search') {
    header('Content-Type: application/json');
    
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchType = isset($_GET['type']) ? trim($_GET['type']) : 'cabinet';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 9;
    $offset = ($page - 1) * $limit;
    
    if (empty($searchTerm)) {
        echo json_encode(['success' => false, 'message' => 'Search term is required']);
        exit;
    }
    
    try {
        if ($searchType === 'cabinet') {
            // Search cabinets
            $countStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT c.id) as total
                FROM cabinets c
                LEFT JOIN items i ON c.id = i.cabinet_id
                LEFT JOIN categories cat ON i.category_id = cat.id
                WHERE c.cabinet_number LIKE ? OR c.name LIKE ?
            ");
            $searchPattern = '%' . $searchTerm . '%';
            $countStmt->execute([$searchPattern, $searchPattern]);
            $totalResults = $countStmt->fetch()['total'];
            $totalPages = ceil($totalResults / $limit);

            $stmt = $pdo->prepare("
                SELECT c.*, 
                       GROUP_CONCAT(DISTINCT cat.name) as categories,
                       COUNT(i.id) as item_count
                FROM cabinets c
                LEFT JOIN items i ON c.id = i.cabinet_id
                LEFT JOIN categories cat ON i.category_id = cat.id
                WHERE c.cabinet_number LIKE ? OR c.name LIKE ?
                GROUP BY c.id
                ORDER BY c.cabinet_number
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$searchPattern, $searchPattern, $limit, $offset]);
            $results = $stmt->fetchAll();
        } else if ($searchType === 'item') {
            // Search items
            $countStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT c.id) as total
                FROM cabinets c
                INNER JOIN items i ON c.id = i.cabinet_id
                WHERE i.name LIKE ?
            ");
            $searchPattern = '%' . $searchTerm . '%';
            $countStmt->execute([$searchPattern]);
            $totalResults = $countStmt->fetch()['total'];
            $totalPages = ceil($totalResults / $limit);

            $stmt = $pdo->prepare("
                SELECT DISTINCT c.*, 
                       GROUP_CONCAT(DISTINCT cat.name) as categories,
                       COUNT(DISTINCT i.id) as item_count,
                       GROUP_CONCAT(DISTINCT i.name) as matching_items
                FROM cabinets c
                INNER JOIN items i ON c.id = i.cabinet_id
                LEFT JOIN categories cat ON i.category_id = cat.id
                WHERE i.name LIKE ?
                GROUP BY c.id
                ORDER BY c.cabinet_number
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$searchPattern, $limit, $offset]);
            $results = $stmt->fetchAll();
        }
        
        echo json_encode([
            'success' => true,
            'results' => $results,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalResults
            ]
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Search error: ' . $e->getMessage()]);
        exit;
    }
}

$cabinetData = null;
$searchResults = [];
$error = null;
$searchType = 'cabinet';
$searchTerm = '';
$pagination = null;

$itemsPerPage = 9;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['cabinet']) || isset($_GET['search_term'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $searchTerm = sanitizeInput($_POST['search_term']);
        $searchType = isset($_POST['search_type']) ? sanitizeInput($_POST['search_type']) : 'cabinet';

        $redirectUrl = $_SERVER['PHP_SELF'] . '?search_term=' . urlencode($searchTerm) . '&search_type=' . urlencode($searchType);
        header("Location: " . $redirectUrl);
        exit();
    } else if (isset($_GET['search_term'])) {
        $searchTerm = sanitizeInput($_GET['search_term']);
        $searchType = isset($_GET['search_type']) ? sanitizeInput($_GET['search_type']) : 'cabinet';
    } else {
        $searchTerm = sanitizeInput($_GET['cabinet']);
        $searchType = 'cabinet';
    }

    try {
        if ($searchType === 'cabinet') {
            $countStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT c.id) as total
                FROM cabinets c
                LEFT JOIN items i ON c.id = i.cabinet_id
                LEFT JOIN categories cat ON i.category_id = cat.id
                WHERE c.cabinet_number = ? OR c.name LIKE ?
            ");
            $countStmt->execute([$searchTerm, "%$searchTerm%"]);
            $totalResults = $countStmt->fetch()['total'];
            $totalPages = ceil($totalResults / $itemsPerPage);

            $stmt = $pdo->prepare("
                SELECT c.*, 
                       GROUP_CONCAT(DISTINCT cat.name) as categories,
                       COUNT(i.id) as item_count
                FROM cabinets c
                LEFT JOIN items i ON c.id = i.cabinet_id
                LEFT JOIN categories cat ON i.category_id = cat.id
                WHERE c.cabinet_number = ? OR c.name LIKE ?
                GROUP BY c.id
                ORDER BY c.cabinet_number
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$searchTerm, "%$searchTerm%", $itemsPerPage, $offset]);
            $searchResults = $stmt->fetchAll();

            if (count($searchResults) === 1 && $totalResults === 1) {
                $cabinetData = $searchResults[0];
            }
        } else if ($searchType === 'item') {
            $countStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT c.id) as total
                FROM cabinets c
                INNER JOIN items i ON c.id = i.cabinet_id
                WHERE i.name LIKE ?
            ");
            $countStmt->execute(["%$searchTerm%"]);
            $totalResults = $countStmt->fetch()['total'];
            $totalPages = ceil($totalResults / $itemsPerPage);

            $stmt = $pdo->prepare("
                SELECT DISTINCT c.*, 
                       GROUP_CONCAT(DISTINCT cat.name) as categories,
                       COUNT(DISTINCT i.id) as item_count,
                       GROUP_CONCAT(DISTINCT i.name) as matching_items
                FROM cabinets c
                INNER JOIN items i ON c.id = i.cabinet_id
                LEFT JOIN categories cat ON i.category_id = cat.id
                WHERE i.name LIKE ?
                GROUP BY c.id
                ORDER BY c.cabinet_number
                LIMIT ? OFFSET ?
            ");
            $stmt->execute(["%$searchTerm%", $itemsPerPage, $offset]);
            $searchResults = $stmt->fetchAll();
        }

        if (isset($totalResults) && $totalResults > 0) {
            $pagination = [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_records' => $totalResults,
                'items_per_page' => $itemsPerPage
            ];
        }
    } catch (PDOException $e) {
        $error = "Error searching: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinet Viewer</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/DepEd_Logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style nonce="<?php echo isset($GLOBALS['csp_nonce']) ? $GLOBALS['csp_nonce'] : ''; ?>">
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar Styles */
        .public-navbar {
            background: rgba(13, 27, 62, 0.95) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 600;
            color: white !important;
            font-size: 1.3rem;
        }

        .btn-outline-light {
            border-width: 2px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: white;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        }

        .viewer-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }

        .header h1 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .header p {
            color: #6c757d;
            font-size: 1.1rem;
            margin: 0;
        }

        .search-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .search-results {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .cabinet-result {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Form Styling */
        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }

        .input-group-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 2px solid #667eea;
            color: white;
            border-radius: 12px 0 0 12px;
            font-weight: 500;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        /* Button Styling */
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 12px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }

        .btn-outline-success {
            border: 2px solid #28a745;
            color: #28a745;
            border-radius: 12px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-success:hover {
            background: #28a745;
            border-color: #28a745;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(40, 167, 69, 0.3);
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .card-img-top {
            border-radius: 15px 15px 0 0;
        }

        .cabinet-card.selected {
            border: 3px solid #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
        }

        /* Form Check Styling */
        .form-check-label {
            font-weight: 500;
            cursor: pointer;
            color: #333;
        }

        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        /* Alert Styling */
        .alert {
            border-radius: 15px;
            border: none;
            padding: 20px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }

        /* Badge Styling */
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border-bottom: none;
        }

        .modal-footer {
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 20px 20px;
        }

        /* What's New Button */
        /* Fix What's New button overlapping with mobile sidebar */
        #whatsNewBtn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1035; /* Lower than mobile sidebar (1050) but higher than other content */
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        #whatsNewBtn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        /* Hide What's New button when mobile sidebar is open */
        #mobileSidebar.show ~ #whatsNewBtn {
            opacity: 0;
            visibility: hidden;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        /* Ensure proper z-index layering */
        #mobileSidebar {
            z-index: 1050;
        }

        .mobile-sidebar-overlay {
            z-index: 1040;
        }

        .public-navbar {
            z-index: 1030;
        }

        /* Alternative: Move What's New button when sidebar is open */
        @media (max-width: 991.98px) {
            #mobileSidebar.show ~ #whatsNewBtn {
                right: 300px; /* Move button left when sidebar is open */
                opacity: 0.7;
                visibility: visible;
            }
        }

        /* For very small screens, hide the button completely when sidebar is open */
        @media (max-width: 576px) {
            #mobileSidebar.show ~ #whatsNewBtn {
                opacity: 0;
                visibility: hidden;
            }
        }

        /* Pagination Styling */
        .pagination .page-link {
            border: 2px solid #e9ecef;
            color: #667eea;
            border-radius: 10px;
            margin: 0 2px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            transform: translateY(-1px);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }

        /* Loading Animation Styles */
        .search-loading {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .search-loading-animation {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px auto;
        }

        .search-loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            color: #667eea;
            width: 3rem;
            height: 3rem;
        }

        .search-loading-video {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: transparent;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            object-fit: cover;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .viewer-container {
                margin: 15px auto;
                padding: 15px;
            }

            .search-box,
            .search-results,
            .header,
            .cabinet-result {
                padding: 25px;
                margin-bottom: 20px;
            }

            .header i {
                font-size: 2.5rem;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .btn {
                display: block;
                width: 100%;
                margin: 5px 0;
            }

            .btn.me-2 {
                margin-right: 0 !important;
            }

            #whatsNewBtn {
                right: 12px;
                bottom: 12px;
                width: 48px;
                height: 48px;
                font-size: 1.2rem;
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(241, 241, 241, 0.5);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }

        /* What's New Modal Fixes */
        #whatsNewModal .modal-dialog {
            margin: 1.75rem auto;
            max-width: 400px;
            width: 90vw;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #whatsNewModal .modal-content {
            border-radius: 16px;
            overflow: hidden;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        #whatsNewModal .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 16px 16px 0 0;
        }
        
        #whatsNewModal .modal-body {
            padding: 0;
            background: white;
        }
        
        #whatsNewModal .modal-footer {
            border: none;
            padding: 0;
            background: white;
        }
        
        #whatsNewModal .list-group-item {
            border: none;
            border-bottom: 1px solid #f0f0f0;
            padding: 12px 20px;
        }
        
        #whatsNewModal .list-group-item:last-child {
            border-bottom: none;
        }
        
        #whatsNewModal .whats-new-list {
            max-height: 180px;
            overflow-y: auto;
        }
        
        #whatsNewModal .whats-new-toggle {
            cursor: pointer;
            user-select: none;
        }
        
        #whatsNewModal .toggle-arrow {
            display: inline-block;
            width: 18px;
            vertical-align: middle;
            margin-right: 8px;
        }
        
        #whatsNewModal .toggle-label {
            vertical-align: middle;
        }

        /* Additional Card Enhancements */
        .cabinet-card {
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .cabinet-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
        }

        .cabinet-card:hover::before {
            opacity: 1;
        }

        .cabinet-card .card-body {
            position: relative;
            z-index: 2;
        }

        /* Enhanced Button Interactions */
        .btn:active {
            transform: translateY(0) !important;
        }

        /* Improved form styling */
        .form-check {
            padding: 10px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .form-check:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Transparent modal background for loading animations - only when loading */
        #viewCabinetModal .modal-content.loading {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        #viewCabinetModal .modal-header.loading {
            background: transparent !important;
            border: none !important;
        }

        #viewCabinetModal .modal-body.loading {
            background: transparent !important;
        }

        #viewCabinetModal .modal-footer.loading {
            background: transparent !important;
            border: none !important;
        }

        /* Normal modal styling when not loading */
        #viewCabinetModal .modal-content:not(.loading) {
            background: white !important;
            border: 1px solid #dee2e6 !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        #viewCabinetModal .modal-header:not(.loading) {
            background: #0d6efd !important;
            color: white !important;
            border-bottom: 1px solid #dee2e6 !important;
        }

        #viewCabinetModal .modal-body:not(.loading) {
            background: white !important;
        }

        #viewCabinetModal .modal-footer:not(.loading) {
            background: #f8f9fa !important;
            border-top: 1px solid #dee2e6 !important;
        }

        /* Remove any background from loading video */
        #viewCabinetModal video {
            background: transparent !important;
        }

        /* Make loading container transparent */
        #viewCabinetModal .loading-container {
            background: transparent !important;
        }

        /* QR display modal - normal styling */
        #qrDisplayModal .modal-content {
            background: white !important;
            border: 1px solid #dee2e6 !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        #qrDisplayModal .modal-header {
            background: #0d6efd !important;
            color: white !important;
            border-bottom: 1px solid #dee2e6 !important;
        }

        #qrDisplayModal .modal-body {
            background: white !important;
        }

        #qrDisplayModal .modal-footer {
            background: #f8f9fa !important;
            border-top: 1px solid #dee2e6 !important;
        }

        #qrDisplayModal video {
            background: transparent !important;
        }

        /* Ensure loading videos are visible */
        .video-loader {
            display: block !important;
            opacity: 1 !important;
        }

        /* Loading container styling */
        .loading-container {
            position: relative;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark public-navbar">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cabinet-filing me-2"></i>Cabinet Management System
            </a>
            
            <!-- Mobile burger menu button -->
            <button class="navbar-toggler d-lg-none" type="button" id="mobileMenuToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Desktop login button -->
            <div class="d-none d-lg-block ms-auto">
                <a href="../admin/pin-verify.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <style>
        /* Mobile Sidebar Styles */
        #mobileSidebar {
            min-height: 100vh;
            width: 280px;
            position: fixed;
            top: 0;
            right: -280px;
            z-index: 1050;
            transition: right 0.3s ease-in-out;
            box-shadow: -4px 0 15px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border-left: 3px solid white;
        }

        #mobileSidebar.show {
            right: 0;
        }

        .mobile-sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .mobile-sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        .mobile-sidebar-link {
            color: #adb5bd;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
        }

        .mobile-sidebar-link:hover,
        .mobile-sidebar-link.active {
            color: #fff;
            background-color: #0d6efd;
            text-decoration: none;
        }

        /* Normal mobile sidebar close button */
        #mobileSidebarClose {
            width: 32px !important;
            height: 32px !important;
            padding: 0.25rem !important;
            font-size: 0.875rem !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            transition: all 0.3s ease;
        }
        
        #mobileSidebarClose:hover {
            background-color: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.5) !important;
            transform: scale(1.05);
        }
        
        #mobileSidebarClose i {
            font-size: 0.875rem !important;
        }
        
        /* Mobile-specific sidebar adjustments */
        @media (max-width: 768px) {
            #mobileSidebar {
                /* Remove footer space on mobile */
                padding-bottom: 0;
            }
            
            /* Ensure sidebar content fits without footer */
            #mobileSidebar .p-3 {
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
            }
        }
    </style>

    <div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"></div>

    <div id="mobileSidebar" class="bg-dark">
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="text-center text-white flex-grow-1">
                    <i class="fa fa-archive text-white" style="font-size: 40px;"></i>
                    <h5 class="mt-2">Cabinet Inventory System</h5>
                </div>
                <button class="btn btn-sm btn-outline-light" id="mobileSidebarClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <hr class="text-light">
            
            <!-- Login Button in Sidebar -->
            <div class="mb-4">
                <a href="../admin/login.php" class="btn btn-outline-light w-100 mobile-sidebar-link text-center">
                    <i class="fas fa-sign-in-alt me-2"></i> Login to Admin Panel
                </a>
            </div>

        </div>
    </div>

    <div class="viewer-container">
        <div class="header">
            <i class="fas fa-archive"></i>
            <h1 class="mt-2">Cabinet Contents Viewer</h1>
            <p class="text-muted">Search by cabinet number or name, or scan QR code</p>
        </div>

        <div class="search-box">
            <form method="POST" action="" id="searchForm">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-center gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="search_type" id="search_cabinet" value="cabinet" <?php echo $searchType === 'cabinet' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="search_cabinet">
                                    <i class="fas fa-cabinet-filing me-2"></i> Search Cabinet
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="search_type" id="search_item" value="item" <?php echo $searchType === 'item' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="search_item">
                                    <i class="fas fa-box me-2"></i> Search Item
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="input-group mb-4">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control form-control-lg" id="searchInput"
                        placeholder="Cabinet Number or Name..."
                        name="search_term"
                        value="<?php echo htmlspecialchars($searchTerm); ?>"
                        autocomplete="off">
                </div>

                <div class="text-center">
                    <button type="button" id="qrCodeBtn" class="btn btn-outline-primary me-3" data-bs-toggle="modal" data-bs-target="#qrDisplayModal" disabled>
                        <i class="fas fa-qrcode me-2"></i> <span id="qrBtnText">Select a Cabinet First</span>
                </button>
                    <button type="button" id="qrScanBtn" class="btn btn-outline-success">
                        <i class="fas fa-camera me-2"></i> Scan QR Code
                </button>
            </div>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($searchResults)): ?>
            <div class="search-results">
                <h4 class="mb-4">
                    <i class="fas fa-list me-2 text-primary"></i>Search Results
                    <span class="badge bg-primary ms-2"><?php echo count($searchResults); ?> cabinet(s) found</span>
                </h4>

                <div class="row">
                    <?php foreach ($searchResults as $cabinet): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm cabinet-card" data-cabinet-id="<?php echo $cabinet['id']; ?>" data-cabinet-number="<?php echo htmlspecialchars($cabinet['cabinet_number']); ?>" data-cabinet-name="<?php echo htmlspecialchars($cabinet['name']); ?>" data-qr-path="<?php echo htmlspecialchars($cabinet['qr_path'] ?? ''); ?>">
                                <div class="position-absolute top-0 end-0 p-2" style="z-index: 10;">
                                    <input class="form-check-input cabinet-selector" type="radio" name="selected_cabinet" value="<?php echo $cabinet['id']; ?>" id="cabinet_<?php echo $cabinet['id']; ?>">
                                </div>

                                <?php if ($cabinet['photo_path']): ?>
                                    <img src="../<?php echo htmlspecialchars($cabinet['photo_path']); ?>"
                                        class="card-img-top"
                                        alt="Cabinet Photo"
                                        style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                        <i class="fas fa-cabinet-filing fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0 text-primary">Cabinet <?php echo htmlspecialchars($cabinet['cabinet_number']); ?></h5>
                                        <i class="fas fa-eye text-primary" style="cursor: pointer;" title="View Details"></i>
                                    </div>

                                    <h6 class="text-muted mb-3"><?php echo htmlspecialchars($cabinet['name']); ?></h6>

                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-box me-1"></i><?php echo $cabinet['item_count']; ?> items
                                            <?php if ($cabinet['categories']): ?>
                                                <br><i class="fas fa-tags me-1"></i><?php echo htmlspecialchars($cabinet['categories']); ?>
                                            <?php endif; ?>
                                            <?php if ($searchType === 'item' && isset($cabinet['matching_items'])): ?>
                                                <br><i class="fas fa-search me-1"></i>Contains: <?php echo htmlspecialchars($cabinet['matching_items']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>

                                    <div class="mt-auto">
                                        <button class="btn btn-primary btn-sm w-100 view-cabinet-btn"
                                            data-cabinet-id="<?php echo $cabinet['id']; ?>"
                                            data-search-type="<?php echo $searchType; ?>"
                                            data-search-term="<?php echo htmlspecialchars($searchTerm); ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewCabinetModal">
                                            <i class="fas fa-eye me-1"></i> View Cabinet
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($pagination && $pagination['total_pages'] > 1): ?>
                    <nav aria-label="Search results pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagination['current_page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link pagination-nav" href="#"
                                        data-page="<?php echo $pagination['current_page'] - 1; ?>"
                                        data-search-type="<?php echo htmlspecialchars($searchType); ?>"
                                        data-search-term="<?php echo htmlspecialchars($searchTerm); ?>">&lt;</a>
                                </li>
                            <?php endif; ?>

                            <?php if ($pagination['current_page'] > 5 && $pagination['total_pages'] >= 5): ?>
                                <?php $skipBackPage = max(1, $pagination['current_page'] - 5); ?>
                                <li class="page-item">
                                    <a class="page-link pagination-nav" href="#"
                                        data-page="<?php echo $skipBackPage; ?>"
                                        data-search-type="<?php echo htmlspecialchars($searchType); ?>"
                                        data-search-term="<?php echo htmlspecialchars($searchTerm); ?>">&lt;&lt;</a>
                                </li>
                            <?php endif; ?>

                            <li class="page-item active">
                                <input type="number" class="form-control form-control-sm text-center guest-page-input"
                                    value="<?php echo $pagination['current_page']; ?>"
                                    min="1" max="<?php echo $pagination['total_pages']; ?>"
                                    style="width: 40px; height: 30px; border: none; font-size: 0.8rem; padding: 2px 4px;"
                                    data-max-pages="<?php echo $pagination['total_pages']; ?>"
                                    data-search-type="<?php echo htmlspecialchars($searchType); ?>"
                                    data-search-term="<?php echo htmlspecialchars($searchTerm); ?>">
                            </li>

                            <?php if ($pagination['current_page'] + 5 <= $pagination['total_pages'] && $pagination['total_pages'] >= 5): ?>
                                <?php $skipForwardPage = min($pagination['total_pages'], $pagination['current_page'] + 5); ?>
                                <li class="page-item">
                                    <a class="page-link pagination-nav" href="#"
                                        data-page="<?php echo $skipForwardPage; ?>"
                                        data-search-type="<?php echo htmlspecialchars($searchType); ?>"
                                        data-search-term="<?php echo htmlspecialchars($searchTerm); ?>">&gt;&gt;</a>
                                </li>
                            <?php endif; ?>

                            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link pagination-nav" href="#"
                                        data-page="<?php echo $pagination['current_page'] + 1; ?>"
                                        data-search-type="<?php echo htmlspecialchars($searchType); ?>"
                                        data-search-term="<?php echo htmlspecialchars($searchTerm); ?>">&gt;</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Showing <?php echo (($pagination['current_page'] - 1) * $pagination['items_per_page'] + 1); ?> -
                            <?php echo min($pagination['current_page'] * $pagination['items_per_page'], $pagination['total_records']); ?>
                            of <?php echo $pagination['total_records']; ?> results
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['cabinet'])): ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No <?php echo $searchType === 'item' ? 'items' : 'cabinets'; ?> found matching "<?php echo htmlspecialchars($searchTerm); ?>".
            </div>
        <?php endif; ?>
    </div>

    <!-- View Cabinet Modal -->
    <div class="modal fade" id="viewCabinetModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cabinet-filing me-2"></i>Cabinet Details
                    </h5>
                </div>
                <div class="modal-body">
                    <div id="viewCabinetContent">
                        <div class="text-center py-4">
                            <div class="search-loading-animation">
                                <div class="search-loading-spinner spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <video class="search-loading-video" autoplay loop muted playsinline style="display:none;">
                                    <source src="../assets/images/Trail-Loading.webm" type="video/webm">
                                </video>
                            </div>
                            <h5 class="mt-3 text-muted" id="viewLoadingMessage">Loading Cabinet Details...</h5>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Display Modal -->
    <div class="modal fade" id="qrDisplayModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-qrcode me-2"></i><span id="qrModalTitle">QR Code for Cabinet</span>
                    </h5>
                </div>
                <div class="modal-body text-center" id="qrModalBody">
                    <div class="text-center py-4">
                        <div class="search-loading-animation">
                            <div class="search-loading-spinner spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <video class="search-loading-video" autoplay loop muted playsinline style="display:none;">
                                <source src="../assets/images/Trail-Loading.webm" type="video/webm">
                            </video>
                        </div>
                        <h5 class="mt-3 text-muted">Loading QR Code...</h5>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Scanner Modal -->
    <div class="modal fade" id="qrScannerModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-camera me-2"></i>Scan QR Code
                    </h5>
                </div>
                <div class="modal-body p-0" id="qrScannerBody">
                    <div class="text-center py-4">
                        <div id="qr-reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                        <div id="qr-reader-results" class="mt-3"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close Scanner</button>
                </div>
            </div>
        </div>
    </div>

    <!-- What's New Button -->
    <button id="whatsNewBtn" type="button" class="btn btn-primary rounded-circle shadow-lg" style="position:fixed;bottom:24px;right:24px;z-index:1055;width:56px;height:56px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;">
        <i class="fas fa-question"></i>
    </button>

    <!-- What's New Modal -->
    <div class="modal fade" id="whatsNewModal" tabindex="-1" aria-labelledby="whatsNewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="whatsNewModalLabel"><i class="fas fa-bolt text-warning me-2"></i>What's New?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group list-group-flush whats-new-list" id="whatsNewAccordion">
                        <li class="list-group-item whats-new-toggle" data-version="wn-v20">
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v2.0</strong> - Real-time search & mobile optimization</span>
                            <div class="collapse mt-1" id="wn-v20" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">Added instant search across all cabinets, mobile-friendly tables with horizontal scrolling, and beautiful loading animations.</div>
                            </div>
                        </li>
                        <li class="list-group-item whats-new-toggle" data-version="wn-v19">
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.9</strong> - Enhanced search with pagination</span>
                            <div class="collapse mt-1" id="wn-v19" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">Search now maintains pagination (9 items per page) and includes loading animations for better user experience.</div>
                            </div>
                        </li>
                        <li class="list-group-item whats-new-toggle" data-version="wn-v18">
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.8</strong> - Improved search interface</span>
                            <div class="collapse mt-1" id="wn-v18" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">Removed search button highlight and added dynamic placeholder text for better usability.</div>
                            </div>
                        </li>
                        <li class="list-group-item whats-new-toggle" data-version="wn-v17">
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.7</strong> - Loading animations</span>
                            <div class="collapse mt-1" id="wn-v17" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">Added beautiful loading animations using Trail-Loading.webm for search operations.</div>
                            </div>
                        </li>
                        <li class="list-group-item whats-new-toggle" data-version="wn-v16">
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.6</strong> - Global search functionality</span>
                            <div class="collapse mt-1" id="wn-v16" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">Search now works across all cabinets regardless of current page, making it easier to find specific cabinets.</div>
                            </div>
                        </li>
                        <li class="list-group-item whats-new-toggle" data-version="wn-v15" >
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.5</strong> - Real-time search</span>
                            <div class="collapse mt-1" id="wn-v15" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">Added type-as-you-search functionality for instant results without clicking search button.</div>
                            </div>
                        </li>
                        <li class="list-group-item whats-new-toggle" data-version="wn-v14" >
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.4</strong> - Added QR code scanning for cabinets</span>
                            <div class="collapse mt-1" id="wn-v14" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">You can now scan QR codes to quickly access cabinet details using your device camera.</div>
                            </div>
                        </li>
                        <li class="list-group-item whats-new-toggle" data-version="wn-v13" >
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.3</strong> - Improved mobile responsiveness</span>
                            <div class="collapse mt-1" id="wn-v13" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">The interface now adapts better to phones and tablets for easier use on the go.</div>
                            </div>
                        </li>
                        <li class="list-group-item whats-new-toggle" data-version="wn-v12" >
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.2</strong> - User-friendly error messages</span>
                            <div class="collapse mt-1" id="wn-v12" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">Clearer error messages help you understand and fix issues faster.</div>
                            </div>
                        </li>
                        <li class="list-group-item whats-new-toggle" data-version="wn-v11" >
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.1</strong> - Initial public viewer release</span>
                            <div class="collapse mt-1" id="wn-v11" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">First release of the public cabinet viewer for easy access to cabinet information.</div>
                            </div>
                        </li>
                        <li class="list-group-item whats-new-toggle" data-version="wn-v10" >
                            <span class="toggle-arrow">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.0</strong> - Project launched 🚀</span>
                            <div class="collapse mt-1" id="wn-v10" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">The Cabinet Inventory System project is live!</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script nonce="<?php echo isset($GLOBALS['csp_nonce']) ? $GLOBALS['csp_nonce'] : ''; ?>">
        // Mobile sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mobileSidebarClose = document.getElementById('mobileSidebarClose');
            const mobileSidebar = document.getElementById('mobileSidebar');
            const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');

            function showMobileSidebar() {
                if (mobileSidebar) {
                    mobileSidebar.classList.add('show');
                }
                if (mobileSidebarOverlay) {
                    mobileSidebarOverlay.classList.add('show');
                }
                document.body.style.overflow = 'hidden';
            }

            function hideMobileSidebar() {
                if (mobileSidebar) {
                    mobileSidebar.classList.remove('show');
                }
                if (mobileSidebarOverlay) {
                    mobileSidebarOverlay.classList.remove('show');
                }
                document.body.style.overflow = '';
            }

            // Toggle mobile sidebar when burger menu is clicked
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    showMobileSidebar();
                });
            }

            // Close mobile sidebar when close button is clicked
            if (mobileSidebarClose) {
                mobileSidebarClose.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    hideMobileSidebar();
                });
            }

            // Close mobile sidebar when overlay is clicked
            if (mobileSidebarOverlay) {
                mobileSidebarOverlay.addEventListener('click', function() {
                    hideMobileSidebar();
                });
            }

            // Close mobile sidebar on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && mobileSidebar && mobileSidebar.classList.contains('show')) {
                    hideMobileSidebar();
                }
            });
        });
    </script>
    <script nonce="<?php echo isset($GLOBALS['csp_nonce']) ? $GLOBALS['csp_nonce'] : ''; ?>">
        document.addEventListener('DOMContentLoaded', function() {
            updateQRButtonState();
            
            // Ensure modal is properly initialized and not auto-opened
            const viewCabinetModal = document.getElementById('viewCabinetModal');
            if (viewCabinetModal) {
                // Hide modal if it's somehow shown
                const modalInstance = bootstrap.Modal.getInstance(viewCabinetModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                
                // Remove any existing modal backdrops
                const existingBackdrops = document.querySelectorAll('.modal-backdrop');
                existingBackdrops.forEach(backdrop => backdrop.remove());
                
                // Reset body classes
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
            
            // Real-time search functionality
            let searchTimeout;
            let currentSearchType = 'cabinet';
            let currentPage = 1;
            
            // Get search elements
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');
            const searchResultsContainer = document.querySelector('.search-results');
            const viewerContainer = document.querySelector('.viewer-container');
            
            // Radio button change handlers
            document.getElementById('search_cabinet').addEventListener('change', function() {
                if (this.checked) {
                    currentSearchType = 'cabinet';
                    searchInput.placeholder = 'Cabinet Number or Name...';
                    performSearch();
                }
            });
            
            document.getElementById('search_item').addEventListener('change', function() {
                if (this.checked) {
                    currentSearchType = 'item';
                    searchInput.placeholder = 'Item Name...';
                    performSearch();
                }
            });
            
            // Real-time search input handler
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                
                // Clear previous timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                if (searchTerm === '') {
                    // Clear results if search is empty
                    clearSearchResults();
                    return;
                }
                
                // Debounce search - wait 300ms after user stops typing
                searchTimeout = setTimeout(() => {
                    performSearch(searchTerm);
                }, 300);
            });
            
            function performSearch(searchTerm = null) {
                const term = searchTerm || searchInput.value.trim();
                
                if (!term) {
                    clearSearchResults();
                    return;
                }
                
                // Show loading animation
                showSearchLoading(term);
                
                // Perform AJAX search
                fetch(`index.php?ajax=search&search=${encodeURIComponent(term)}&type=${currentSearchType}&page=1`)
                    .then(response => response.json())
                    .then(data => {
                        hideSearchLoading();
                        
                        if (data.success) {
                            displaySearchResults(data.results, data.pagination, term);
                        } else {
                            showSearchError(data.message || 'Search failed');
                        }
                    })
                    .catch(error => {
                        hideSearchLoading();
                        showSearchError('Network error. Please try again.');
                        console.error('Search error:', error);
                    });
            }
            
            function showSearchLoading(searchTerm) {
                // Remove any existing search results container first
                const existingContainer = document.querySelector('.search-results');
                if (existingContainer) {
                    existingContainer.remove();
                }
                
                // Create new search results container
                const container = document.createElement('div');
                container.className = 'search-results';
                viewerContainer.appendChild(container);
                
                // Show enhanced loading animation with proper styling and centering
                container.innerHTML = `
                    <div class="search-loading">
                        <div class="search-loading-animation">
                            <div class="spinner-border text-primary search-loading-spinner" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <video class="search-loading-video" autoplay loop muted playsinline>
                                <source src="../assets/images/Trail-Loading.webm" type="video/webm">
                            </video>
                        </div>
                        <div class="text-center">
                            <h5 class="mb-2">Searching ${currentSearchType === 'cabinet' ? 'cabinets' : 'items'}...</h5>
                            <p class="text-muted mb-0">Looking for "${searchTerm}"</p>
                        </div>
                    </div>
                `;
                
                // Handle video loading with simplified approach (like admin pages)
                const video = container.querySelector('.search-loading-video');
                const spinner = container.querySelector('.search-loading-spinner');
                
                if (video && spinner) {
                    // Simple approach like admin pages
                    video.style.display = 'none';
                    spinner.style.display = 'block';
                    
                    // Clear any existing event listeners
                    video.onloadeddata = null;
                    video.oncanplaythrough = null;
                    video.onerror = null;
                    video.onended = null;
                    video.onloadstart = null;
                    
                    // Set video properties
                    video.src = '../assets/images/Trail-Loading.webm';
                    video.loop = true;
                    video.muted = true;
                    video.autoplay = true;
                    video.preload = 'auto';
                    video.playsInline = true;

                    const onVideoReady = () => {
                        video.style.display = 'block';
                        spinner.style.display = 'none';
                        video.play().catch(() => {
                            video.style.display = 'none';
                            spinner.style.display = 'block';
                        });
                    };

                    const onVideoError = () => {
                        video.style.display = 'none';
                        spinner.style.display = 'block';
                    };

                    // Simple event listeners
                    video.addEventListener('loadeddata', onVideoReady, { once: true });
                    video.addEventListener('canplaythrough', onVideoReady, { once: true });
                    video.addEventListener('error', onVideoError, { once: true });

                    // Load the video
                    video.load();

                    // Simple timeout (2 seconds like admin pages)
                    setTimeout(() => {
                        if (video.readyState < 2) {
                            video.style.display = 'none';
                            spinner.style.display = 'block';
                        }
                    }, 2000);
                }
            }
            
            function hideSearchLoading() {
                // No need to hide anything - results will replace the loading content
            }
            
            function displaySearchResults(results, pagination, searchTerm) {
                // Remove any existing search results container first
                const existingContainer = document.querySelector('.search-results');
                if (existingContainer) {
                    existingContainer.remove();
                }
                
                // Create new search results container
                const container = document.createElement('div');
                container.className = 'search-results';
                viewerContainer.appendChild(container);
                
                if (results.length === 0) {
                    container.innerHTML = `
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No ${currentSearchType === 'item' ? 'items' : 'cabinets'} found matching "${searchTerm}".
                        </div>
                    `;
                    return;
                }
                
                // Generate results HTML
                const resultsHTML = `
                    <h4 class="mb-4">
                        <i class="fas fa-list me-2 text-primary"></i>Search Results
                        <span class="badge bg-primary ms-2">${results.length} ${currentSearchType}(s) found</span>
                    </h4>
                    <div class="row">
                        ${results.map(cabinet => `
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 shadow-sm cabinet-card" data-cabinet-id="${cabinet.id}" data-cabinet-number="${cabinet.cabinet_number}" data-cabinet-name="${cabinet.name}" data-qr-path="${cabinet.qr_path || ''}">
                                    <div class="position-absolute top-0 end-0 p-2" style="z-index: 10;">
                                        <input class="form-check-input cabinet-selector" type="radio" name="selected_cabinet" value="${cabinet.id}" id="cabinet_${cabinet.id}">
                                    </div>
                                    ${cabinet.photo_path ? 
                                        `<img src="../${cabinet.photo_path}" class="card-img-top" alt="Cabinet Photo" style="height: 200px; object-fit: cover;">` :
                                        `<div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                            <i class="fas fa-cabinet-filing fa-3x text-muted"></i>
                                        </div>`
                                    }
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0 text-primary">Cabinet ${cabinet.cabinet_number}</h5>
                                            <i class="fas fa-eye text-primary" style="cursor: pointer;" title="View Details"></i>
                                        </div>
                                        <h6 class="text-muted mb-3">${cabinet.name}</h6>
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="fas fa-box me-1"></i>${cabinet.item_count} items
                                                ${cabinet.categories ? `<br><i class="fas fa-tags me-1"></i>${cabinet.categories}` : ''}
                                                ${currentSearchType === 'item' && cabinet.matching_items ? `<br><i class="fas fa-search me-1"></i>Contains: ${cabinet.matching_items}` : ''}
                                            </small>
                                        </div>
                                        <div class="mt-auto">
                                            <button class="btn btn-primary btn-sm w-100 view-cabinet-btn"
                                                data-cabinet-id="${cabinet.id}"
                                                data-search-type="${currentSearchType}"
                                                data-search-term="${searchTerm}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewCabinetModal">
                                                <i class="fas fa-eye me-1"></i> View Cabinet
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    ${pagination.total_pages > 1 ? generatePaginationHTML(pagination, searchTerm) : ''}
                `;
                
                container.innerHTML = resultsHTML;
                updateQRButtonState();
            }
            
            function generatePaginationHTML(pagination, searchTerm) {
                return `
                    <nav aria-label="Search results pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            ${pagination.current_page > 1 ? `
                                <li class="page-item">
                                    <a class="page-link pagination-link" href="#" data-page="${pagination.current_page - 1}">&lt;</a>
                                </li>
                            ` : ''}
                            <li class="page-item active">
                                <input type="number" class="form-control form-control-sm text-center page-input"
                                    value="${pagination.current_page}"
                                    min="1" max="${pagination.total_pages}"
                                    style="width: 40px; height: 30px; border: none; font-size: 0.8rem; padding: 2px 4px;"
                                    data-max-pages="${pagination.total_pages}">
                            </li>
                            ${pagination.current_page < pagination.total_pages ? `
                                <li class="page-item">
                                    <a class="page-link pagination-link" href="#" data-page="${pagination.current_page + 1}">&gt;</a>
                                </li>
                            ` : ''}
                        </ul>
                    </nav>
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Showing ${((pagination.current_page - 1) * 9) + 1} - ${Math.min(pagination.current_page * 9, pagination.total_records)}
                            of ${pagination.total_records} results
                        </small>
                    </div>
                `;
            }
            
            function clearSearchResults() {
                const container = document.querySelector('.search-results');
                if (container) {
                    container.remove();
                }
                
                // Clear QR button state when search is cleared
                updateQRButtonState();
            }
            
            function showSearchError(message) {
                // Remove any existing search results container first
                const existingContainer = document.querySelector('.search-results');
                if (existingContainer) {
                    existingContainer.remove();
                }
                
                // Create new search results container
                const container = document.createElement('div');
                container.className = 'search-results';
                container.innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${message}
                    </div>
                `;
                viewerContainer.appendChild(container);
            }
            
            // Pagination handlers
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('pagination-link')) {
                    e.preventDefault();
                    const page = parseInt(e.target.getAttribute('data-page'));
                    if (page) {
                        loadSearchPage(page);
                    }
                }
            });
            
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('page-input')) {
                    const page = parseInt(e.target.value);
                    const maxPages = parseInt(e.target.getAttribute('data-max-pages'));
                    if (page >= 1 && page <= maxPages) {
                        loadSearchPage(page);
                    }
                }
            });
            
            function loadSearchPage(page) {
                const searchTerm = searchInput.value.trim();
                if (!searchTerm) return;
                
                showSearchLoading(searchTerm);
                
                fetch(`index.php?ajax=search&search=${encodeURIComponent(searchTerm)}&type=${currentSearchType}&page=${page}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displaySearchResults(data.results, data.pagination, searchTerm);
                        } else {
                            showSearchError(data.message || 'Search failed');
                        }
                    })
                    .catch(error => {
                        showSearchError('Network error. Please try again.');
                        console.error('Search error:', error);
                    });
            }

            setTimeout(() => {
                if (typeof Html5Qrcode !== 'undefined') {
                    console.log('Html5Qrcode library loaded successfully');
                } else {
                    console.error('Html5Qrcode library failed to load');
                }
            }, 1000);

            let html5QrCode = null;
            let isScanning = false;

            document.getElementById('qrScanBtn').addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('QR Scan button clicked');
                const modal = new bootstrap.Modal(document.getElementById('qrScannerModal'));
                modal.show();

                document.getElementById('qrScannerModal').addEventListener('shown.bs.modal', function() {
                    setTimeout(() => {
                        startQRScanner();
                    }, 100);
                }, {
                    once: true
                });

                document.getElementById('qrScannerModal').addEventListener('hidden.bs.modal', function() {
                    stopQRScanner();
                });
            });

            function startQRScanner() {
                if (isScanning) return;

                const qrReaderResults = document.getElementById('qr-reader-results');

                if (typeof Html5Qrcode === 'undefined') {
                    console.error('Html5Qrcode library not loaded');
                    qrReaderResults.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            QR Scanner library failed to load. Please refresh the page and try again.
                        </div>
                    `;
                    return;
                }

                const isHTTPS = window.location.protocol === 'https:';
                const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
                const isLocalNetwork = window.location.hostname.match(/^192\.168\./) ||
                    window.location.hostname.match(/^10\./) ||
                    window.location.hostname.match(/^172\.(1[6-9]|2[0-9]|3[0-1])\./);

                if (!isHTTPS && !isLocalhost && !isLocalNetwork) {
                    qrReaderResults.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>HTTPS Required</strong><br>
                            Camera access requires HTTPS for security.<br>
                            <div class="bg-light p-2 mt-2 rounded">
                                <strong>Quick Solutions:</strong><br>
                                1. <strong>ngrok (Easiest):</strong> Creates HTTPS tunnel<br>
                                   • Download from ngrok.com<br>
                                   • Run: <code>ngrok http 8080</code><br>
                                   • Use the HTTPS URL<br><br>
                                2. <strong>Self-signed HTTPS:</strong> Add to server.php
                            </div>
                        </div>
                    `;
                    return;
                } else if (!isHTTPS && !isLocalhost && isLocalNetwork) {
                    qrReaderResults.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>HTTP Detected (Local Network)</strong><br>
                            Camera may not work on some browsers via HTTP. 
                            If scanner fails, try one of these solutions:<br>
                            <small>
                                • Use ngrok for HTTPS: <a href="#" onclick="alert('1. Download ngrok\\n2. Run: ngrok http 8080\\n3. Use the HTTPS URL')">Quick Setup</a><br>
                                • Some browsers allow camera on local networks<br>
                                • Trying to start scanner anyway...
                            </small>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-camera me-2"></i>
                            Attempting to start camera... Please allow access if prompted.
                        </div>
                    `;
                } else {
                    qrReaderResults.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-camera me-2"></i>
                            Starting camera... Please allow camera access when prompted.
                        </div>
                    `;
                }

                html5QrCode = new Html5Qrcode("qr-reader");

                Html5Qrcode.getCameras().then(devices => {
                    console.log('Available cameras:', devices);

                    if (devices && devices.length) {
                        const realCameras = devices.filter(device =>
                            !device.label.toLowerCase().includes('obs') &&
                            !device.label.toLowerCase().includes('virtual') &&
                            !device.label.toLowerCase().includes('snap') &&
                            device.label.trim() !== ''
                        );

                        console.log('Real cameras found:', realCameras);

                        const camerasToUse = realCameras.length > 0 ? realCameras : devices;
                        let cameraId = camerasToUse[0].id;

                        const backCamera = camerasToUse.find(device =>
                            device.label.toLowerCase().includes('back') ||
                            device.label.toLowerCase().includes('rear') ||
                            device.label.toLowerCase().includes('environment')
                        );

                        if (backCamera) {
                            cameraId = backCamera.id;
                            console.log('Using back camera:', backCamera.label);
                        } else {
                            console.log('Using camera:', camerasToUse[0].label);
                        }

                        html5QrCode.start(
                            cameraId, {
                                fps: 10,
                                qrbox: {
                                    width: 250,
                                    height: 250
                                },
                                aspectRatio: 1.0
                            },
                            (decodedText, decodedResult) => {
                                console.log('QR Code detected:', decodedText);
                                processQRCode(decodedText);
                            },
                            (errorMessage) => {
                                if (!errorMessage.includes('No MultiFormat Readers')) {
                                    console.log('QR scan error (can be ignored):', errorMessage);
                                }
                            }
                        ).then(() => {
                            isScanning = true;
                            console.log('QR Scanner started successfully');
                            qrReaderResults.innerHTML = `
                                <div class="alert alert-success">
                                    <i class="fas fa-qrcode me-2"></i>
                                    Scanner active! Point your camera at a QR code.
                                </div>
                            `;
                        }).catch(err => {
                            console.error('Camera start error:', err);
                            isScanning = false;

                            let errorMessage = 'Camera access failed.';
                            let errorDetails = err.message || 'Unknown error';

                            const isHTTPS = window.location.protocol === 'https:';
                            const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

                            if (!isHTTPS && !isLocalhost) {
                                errorMessage = 'Camera requires HTTPS connection.';
                                errorDetails = `You're accessing via ${window.location.protocol}// but cameras require HTTPS for security. Try accessing via HTTPS or localhost.`;
                            } else if (err.name === 'NotAllowedError') {
                                errorMessage = 'Camera permission denied.';
                                errorDetails = 'Please allow camera access and try again. Check your browser permissions.';
                            } else if (err.name === 'NotFoundError') {
                                errorMessage = 'No camera found.';
                                errorDetails = 'No cameras detected on this device.';
                            } else if (err.name === 'NotReadableError') {
                                errorMessage = 'Camera is busy or unavailable.';
                                errorDetails = 'Camera may be in use by another application (like OBS, Skype, etc.). Close other camera apps and try again.';
                            } else if (err.name === 'OverconstrainedError') {
                                errorMessage = 'Camera configuration not supported.';
                                errorDetails = 'The camera doesn\'t support the required configuration.';
                            }

                            qrReaderResults.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>${errorMessage}</strong>
                                    <br><small>${errorDetails}</small>
                                    ${!isHTTPS && !isLocalhost ? `
                                        <hr>
                                        <div class="text-center">
                                            <strong>Quick Fixes:</strong><br>
                                            1. Use HTTPS: <code>https://${window.location.host}${window.location.pathname}</code><br>
                                            2. Or access via localhost if server is local
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        });
                    } else {
                        console.error('No cameras found');
                        qrReaderResults.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-camera me-2"></i>
                                No cameras found on this device.
                            </div>
                        `;
                    }
                }).catch(err => {
                    console.error('Camera detection error:', err);

                    const isHTTPS = window.location.protocol === 'https:';
                    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

                    let errorMessage = 'Error accessing camera system.';
                    let errorDetails = err.message || 'Unknown error';

                    if (!isHTTPS && !isLocalhost) {
                        errorMessage = 'HTTPS Required for Camera Access';
                        errorDetails = `Modern browsers require HTTPS to access cameras for security. You're currently using ${window.location.protocol}//`;
                    }

                    qrReaderResults.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>${errorMessage}</strong>
                            <br><small>${errorDetails}</small>
                            ${!isHTTPS && !isLocalhost ? `
                                <hr>
                                <div class="bg-light p-2 mt-2 rounded">
                                    <strong>Solutions:</strong><br>
                                    • Enable HTTPS on your server<br>
                                    • Access via <code>https://${window.location.host}${window.location.pathname}</code><br>
                                    • Or use localhost if testing locally
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
            }

            function stopQRScanner() {
                if (html5QrCode && isScanning) {
                    html5QrCode.stop().then(() => {
                        html5QrCode = null;
                        isScanning = false;
                        document.getElementById('qr-reader-results').innerHTML = '';
                    }).catch(err => {
                        console.error('Error stopping scanner:', err);
                    });
                }
            }

            function processQRCode(decodedText) {
                stopQRScanner();
                const modal = bootstrap.Modal.getInstance(document.getElementById('qrScannerModal'));
                if (modal) {
                    modal.hide();
                }

                let cabinetInfo = extractCabinetFromQR(decodedText);

                if (cabinetInfo) {
                    const searchInput = document.querySelector('input[name="search_term"]');
                    const cabinetRadio = document.getElementById('search_cabinet');

                    if (searchInput && cabinetRadio) {
                        searchInput.value = cabinetInfo;
                        cabinetRadio.checked = true;

                        showQRScanSuccess(cabinetInfo);

                        setTimeout(() => {
                            document.getElementById('searchForm').dispatchEvent(new Event('submit', {
                                bubbles: true
                            }));
                        }, 1500);
                    }
                } else {
                    showQRScanError(decodedText);
                }
            }

            function extractCabinetFromQR(qrText) {
                const urlMatch = qrText.match(/[?&]cabinet=([^&]+)/);
                if (urlMatch) {
                    return decodeURIComponent(urlMatch[1]);
                }

                const cabinetMatch = qrText.match(/CAB\d+|Cabinet\s+\d+|Cabinet\s+[A-Za-z0-9]+/i);
                if (cabinetMatch) {
                    return cabinetMatch[0];
                }

                if (/^[A-Za-z0-9\s]+$/.test(qrText) && qrText.length < 50) {
                    return qrText;
                }

                return null;
            }

            function showQRScanSuccess(cabinetInfo) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
                alert.style.cssText = 'top: 20px; right: 20px; z-index: 10000; max-width: 400px;';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>QR Code Scanned!</strong><br>
                    Found cabinet: ${cabinetInfo}<br>
                    <small>Searching automatically...</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alert);

                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 5000);
            }

            function showQRScanError(qrText) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-warning alert-dismissible fade show position-fixed';
                alert.style.cssText = 'top: 20px; right: 20px; z-index: 10000; max-width: 400px;';
                alert.innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>QR Code Not Recognized</strong><br>
                    This doesn't appear to be a cabinet QR code.<br>
                    <small>Scanned: ${qrText.substring(0, 50)}${qrText.length > 50 ? '...' : ''}</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alert);

                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 7000);
            }

            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('cabinet-selector')) {
                    console.log('Cabinet selector changed:', e.target.value);
                    updateQRButtonState();
                    updateSelectedCabinetVisual();
                }
            });

            document.addEventListener('click', function(e) {
                if (e.target.closest('.cabinet-card') && !e.target.closest('.view-cabinet-btn') && !e.target.classList.contains('cabinet-selector')) {
                    const card = e.target.closest('.cabinet-card');
                    const radio = card.querySelector('.cabinet-selector');
                    if (radio) {
                        console.log('Cabinet card clicked, selecting:', radio.value);
                        radio.checked = true;
                        updateQRButtonState();
                        updateSelectedCabinetVisual();
                    }
                }
            });

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('pagination-nav')) {
                    e.preventDefault();
                    const page = parseInt(e.target.getAttribute('data-page'));
                    const searchType = e.target.getAttribute('data-search-type');
                    const searchTerm = e.target.getAttribute('data-search-term');
                    if (page && searchType && searchTerm) {
                        navigateToPage(page, searchType, searchTerm);
                    }
                    return false;
                }
            });

            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('guest-page-input')) {
                    const page = parseInt(e.target.value);
                    const maxPages = parseInt(e.target.getAttribute('data-max-pages'));
                    const searchType = e.target.getAttribute('data-search-type');
                    const searchTerm = e.target.getAttribute('data-search-term');
                    if (page >= 1 && page <= maxPages && searchType && searchTerm) {
                        navigateToPage(page, searchType, searchTerm);
                    }
                }
            });

            document.addEventListener('keypress', function(e) {
                if (e.target.classList.contains('guest-page-input') && e.key === 'Enter') {
                    const page = parseInt(e.target.value);
                    const maxPages = parseInt(e.target.getAttribute('data-max-pages'));
                    const searchType = e.target.getAttribute('data-search-type');
                    const searchTerm = e.target.getAttribute('data-search-term');
                    if (page >= 1 && page <= maxPages && searchType && searchTerm) {
                        navigateToPage(page, searchType, searchTerm);
                    }
                }
            });

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('view-cabinet-btn') || e.target.closest('.view-cabinet-btn')) {
                    const button = e.target.classList.contains('view-cabinet-btn') ? e.target : e.target.closest('.view-cabinet-btn');
                    const cabinetId = button.getAttribute('data-cabinet-id');
                    const searchType = button.getAttribute('data-search-type');
                    const searchTerm = button.getAttribute('data-search-term');
                    if (cabinetId) {
                        loadCabinetDetails(cabinetId, searchType, searchTerm);
                    }
                }
            });

            const cabinetModal = document.getElementById('viewCabinetModal');
            if (cabinetModal) {
                // Ensure modal is not auto-opened
                const modalInstance = bootstrap.Modal.getInstance(cabinetModal);
                if (modalInstance && modalInstance._isShown) {
                    modalInstance.hide();
                }
                
                cabinetModal.addEventListener('hidden.bs.modal', function() {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                    });

                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    
                    // Remove loading class when modal is closed
                    const modalContent = document.querySelector('#viewCabinetModal .modal-content');
                    const modalHeader = document.querySelector('#viewCabinetModal .modal-header');
                    const modalBody = document.querySelector('#viewCabinetModal .modal-body');
                    const modalFooter = document.querySelector('#viewCabinetModal .modal-footer');
                    
                    if (modalContent) modalContent.classList.remove('loading');
                    if (modalHeader) modalHeader.classList.remove('loading');
                    if (modalBody) modalBody.classList.remove('loading');
                    if (modalFooter) modalFooter.classList.remove('loading');

                    const content = document.getElementById('viewCabinetContent');
                    if (content) {
                        content.innerHTML = `
                            <div class="text-center py-4">
                                <video src="../assets/images/Trail-Loading.webm" style="width: 150px; height: 150px; margin: 0 auto; display:block;" autoplay muted loop playsinline></video>
                                <h5 class="mt-3 text-muted">Loading Cabinet Details...</h5>
                            </div>
                        `;
                    }
                });
            }

            // Remove old form submission logic - now using real-time search
        });

        function updateQRButtonState() {
            const selectedCabinet = document.querySelector('input[name="selected_cabinet"]:checked');
            const qrBtn = document.getElementById('qrCodeBtn');
            const qrBtnText = document.getElementById('qrBtnText');

            console.log('updateQRButtonState called', selectedCabinet);

            if (selectedCabinet) {
                const card = selectedCabinet.closest('.cabinet-card');
                const cabinetNumber = card.getAttribute('data-cabinet-number');
                const cabinetName = card.getAttribute('data-cabinet-name');

                qrBtn.disabled = false;
                qrBtn.className = 'btn btn-primary me-3';
                qrBtnText.textContent = `Show QR Code for Cabinet ${cabinetNumber}`;

                window.selectedCabinetData = {
                    id: selectedCabinet.value,
                    cabinet_number: cabinetNumber,
                    name: cabinetName,
                    qr_path: card.getAttribute('data-qr-path')
                };
                
                console.log('Cabinet selected:', window.selectedCabinetData);
            } else {
                qrBtn.disabled = true;
                qrBtn.className = 'btn btn-outline-primary me-3';
                qrBtnText.textContent = 'Select a Cabinet First';
                window.selectedCabinetData = null;
                
                console.log('No cabinet selected');
            }
        }

        function updateSelectedCabinetVisual() {
            document.querySelectorAll('.cabinet-card').forEach(card => {
                card.classList.remove('selected');
            });

            const selectedCabinet = document.querySelector('input[name="selected_cabinet"]:checked');
            if (selectedCabinet) {
                selectedCabinet.closest('.cabinet-card').classList.add('selected');
            }
        }

        function navigateToPage(page, searchType, searchTerm) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            url.searchParams.set('search_type', searchType);
            url.searchParams.set('search_term', searchTerm);

            window.location.href = url.toString();
        }

        // Add click handler for QR Code button to prevent form submission
        document.getElementById('qrCodeBtn').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('QR Code button clicked');
        });

        document.addEventListener('show.bs.modal', function(e) {
            if (e.target.id === 'qrDisplayModal') {
                console.log('QR Display Modal opening, selected cabinet data:', window.selectedCabinetData);
                loadQRModalContent();
            }
        });

        function loadQRModalContent() {
            const modalTitle = document.getElementById('qrModalTitle');
            const modalBody = document.getElementById('qrModalBody');

            if (!window.selectedCabinetData) {
                modalTitle.textContent = 'No Cabinet Selected';
                modalBody.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Please select a cabinet first.
                    </div>
                `;
                return;
            }

            const cabinet = window.selectedCabinetData;
            modalTitle.textContent = `QR Code for ${cabinet.name}`;

            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="search-loading-animation">
                        <div class="search-loading-spinner spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <video class="search-loading-video" autoplay loop muted playsinline style="display:none;">
                            <source src="../assets/images/Trail-Loading.webm" type="video/webm">
                        </video>
                    </div>
                    <h5 class="mt-3 text-muted">Loading QR Code...</h5>
                </div>
            `;

            // Handle video loading for QR modal (simplified approach)
            const video = modalBody.querySelector('.search-loading-video');
            const spinner = modalBody.querySelector('.search-loading-spinner');
            if (video && spinner) {
                // Simple approach like admin pages
                video.style.display = 'none';
                spinner.style.display = 'block';
                
                // Clear any existing event listeners
                video.onloadeddata = null;
                video.oncanplaythrough = null;
                video.onerror = null;
                video.onended = null;
                video.onloadstart = null;
                
                // Set video properties
                video.src = '../assets/images/Trail-Loading.webm';
                video.loop = true;
                video.muted = true;
                video.autoplay = true;
                video.preload = 'auto';
                video.playsInline = true;

                const onVideoReady = () => {
                    video.style.display = 'block';
                    spinner.style.display = 'none';
                    video.play().catch(() => {
                        video.style.display = 'none';
                        spinner.style.display = 'block';
                    });
                };

                const onVideoError = () => {
                    video.style.display = 'none';
                    spinner.style.display = 'block';
                };

                // Simple event listeners
                video.addEventListener('loadeddata', onVideoReady, { once: true });
                video.addEventListener('canplaythrough', onVideoReady, { once: true });
                video.addEventListener('error', onVideoError, { once: true });

                // Load the video
                video.load();

                // Simple timeout (2 seconds like admin pages)
                setTimeout(() => {
                    if (video.readyState < 2) {
                        video.style.display = 'none';
                        spinner.style.display = 'block';
                    }
                }, 2000);
            }

            setTimeout(() => {
                modalBody.innerHTML = `
                    <h6 class="mb-3">Cabinet: ${cabinet.cabinet_number}</h6>
                    
                    ${cabinet.qr_path ? `
                        <div class="qr-code-container mb-3">
                            <img src="../${cabinet.qr_path}" 
                                 alt="QR Code for ${cabinet.cabinet_number}"
                                 class="img-fluid"
                                 style="max-width: 250px; border: 1px solid #dee2e6; border-radius: 8px; background: white; padding: 10px;">
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-mobile-alt me-2"></i>How to use this QR Code:</h6>
                            <ul class="list-unstyled mb-0">
                                <li><i class="fas fa-camera text-primary me-2"></i>Open your phone's camera</li>
                                <li><i class="fas fa-qrcode text-primary me-2"></i>Point at the QR code above</li>
                                <li><i class="fas fa-external-link-alt text-primary me-2"></i>Tap the notification to view cabinet</li>
                            </ul>
                        </div>
                    ` : `
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>QR Code Not Generated</h6>
                            <p class="mb-3">No QR code has been generated for this cabinet yet.</p>
                            <p class="text-muted">QR codes are generated by administrators in the admin panel.</p>
                        </div>
                    `}
                    
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        This QR code links to: ${window.location.origin}/cabinet-inventory-system/index.php?cabinet=${encodeURIComponent(cabinet.cabinet_number)}
                    </small>
                `;
            }, 2000); // Shorter delay for QR modal
        }

        function loadCabinetDetails(cabinetId, searchType = '', searchTerm = '') {
            // Prevent automatic modal opening
            if (!cabinetId || cabinetId === 'undefined' || cabinetId === 'null') {
                console.log('Invalid cabinet ID, preventing modal opening');
                return;
            }
            
            const content = document.getElementById('viewCabinetContent');
            const modal = new bootstrap.Modal(document.getElementById('viewCabinetModal'));

            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="search-loading-animation">
                        <div class="search-loading-spinner spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <video class="search-loading-video" autoplay loop muted playsinline style="display:none;">
                            <source src="../assets/images/Trail-Loading.webm" type="video/webm">
                        </video>
                    </div>
                    <h5 class="mt-3 text-muted">Loading Cabinet Details...</h5>
                </div>
            `;
            modal.show();

            // Handle initial loading video/spinner (simplified approach)
            const video = content.querySelector('.search-loading-video');
            const spinner = content.querySelector('.search-loading-spinner');
            if (video && spinner) {
                // Simple approach like admin pages
                video.style.display = 'none';
                spinner.style.display = 'block';
                
                // Clear any existing event listeners
                video.onloadeddata = null;
                video.oncanplaythrough = null;
                video.onerror = null;
                video.onended = null;
                video.onloadstart = null;
                
                // Set video properties
                video.src = '../assets/images/Trail-Loading.webm';
                video.loop = true;
                video.muted = true;
                video.autoplay = true;
                video.preload = 'auto';
                video.playsInline = true;

                const onVideoReady = () => {
                    video.style.display = 'block';
                    spinner.style.display = 'none';
                    video.play().catch(() => {
                        video.style.display = 'none';
                        spinner.style.display = 'block';
                    });
                };

                const onVideoError = () => {
                    video.style.display = 'none';
                    spinner.style.display = 'block';
                };

                // Simple event listeners
                video.addEventListener('loadeddata', onVideoReady, { once: true });
                video.addEventListener('canplaythrough', onVideoReady, { once: true });
                video.addEventListener('error', onVideoError, { once: true });

                // Load the video
                video.load();

                // Simple timeout (2 seconds like admin pages)
                setTimeout(() => {
                    if (video.readyState < 2) {
                        video.style.display = 'none';
                        spinner.style.display = 'block';
                    }
                }, 2000);
            }

            fetch(`public_api.php?action=get_cabinet&cabinet_id=${cabinetId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cabinet = data.cabinet;
                        const cabinetName = cabinet.name || `Cabinet ${cabinet.cabinet_number}`;

                        let loadingMessage;
                        if (searchType === 'item' && searchTerm) {
                            loadingMessage = `Finding "${searchTerm}" in ${cabinetName}...`;
                        } else {
                            loadingMessage = `Viewing ${cabinetName}...`;
                        }

                        content.innerHTML = `
                            <div class="text-center py-4">
                                <div class="search-loading-animation">
                                    <div class="search-loading-spinner spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <video class="search-loading-video" autoplay loop muted playsinline style="display:none;">
                                        <source src="../assets/images/Trail-Loading.webm" type="video/webm">
                                    </video>
                                </div>
                                <h5 class="mt-3 text-muted" id="viewLoadingMessage">${loadingMessage}</h5>
                            </div>
                        `;

                        // Handle second-phase loading video/spinner (simplified approach)
                        const phase2Video = content.querySelector('.search-loading-video');
                        const phase2Spinner = content.querySelector('.search-loading-spinner');
                        if (phase2Video && phase2Spinner) {
                            // Simple approach like admin pages
                            phase2Video.style.display = 'none';
                            phase2Spinner.style.display = 'block';
                            
                            // Clear any existing event listeners
                            phase2Video.onloadeddata = null;
                            phase2Video.oncanplaythrough = null;
                            phase2Video.onerror = null;
                            phase2Video.onended = null;
                            phase2Video.onloadstart = null;
                            
                            // Set video properties
                            phase2Video.src = '../assets/images/Trail-Loading.webm';
                            phase2Video.loop = true;
                            phase2Video.muted = true;
                            phase2Video.autoplay = true;
                            phase2Video.preload = 'auto';
                            phase2Video.playsInline = true;

                            const onPhase2Ready = () => {
                                phase2Video.style.display = 'block';
                                phase2Spinner.style.display = 'none';
                                phase2Video.play().catch(() => {
                                    phase2Video.style.display = 'none';
                                    phase2Spinner.style.display = 'block';
                                });
                            };

                            const onPhase2Error = () => {
                                phase2Video.style.display = 'none';
                                phase2Spinner.style.display = 'block';
                            };

                            // Simple event listeners
                            phase2Video.addEventListener('loadeddata', onPhase2Ready, { once: true });
                            phase2Video.addEventListener('canplaythrough', onPhase2Ready, { once: true });
                            phase2Video.addEventListener('error', onPhase2Error, { once: true });

                            // Load the video
                            phase2Video.load();

                            // Simple timeout (2 seconds like admin pages)
                            setTimeout(() => {
                                if (phase2Video.readyState < 2) {
                                    phase2Video.style.display = 'none';
                                    phase2Spinner.style.display = 'block';
                                }
                            }, 2000);
                        }

                        setTimeout(() => {
                            const shouldHighlight = searchType === 'item' && searchTerm;
                            
                            content.innerHTML = `
                                <div class="row mb-4">
                                    <div class="col-md-8">
                                        <h6 class="text-primary">Cabinet Information</h6>
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <td><strong>Cabinet Number:</strong></td>
                                                <td>${cabinet.cabinet_number}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Cabinet Name:</strong></td>
                                                <td>${cabinet.name}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Items:</strong></td>
                                                <td>${cabinet.items ? cabinet.items.length : 0}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Last Updated:</strong></td>
                                                <td>${new Date(cabinet.updated_at).toLocaleDateString()}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-4">
                                        ${cabinet.photo_path ? 
                                            `<img src="../${cabinet.photo_path}" alt="Cabinet Photo" class="img-fluid rounded shadow">` :
                                            `<div class="bg-light rounded p-4 text-center">
                                                <i class="fas fa-cabinet-filing fa-3x text-muted"></i>
                                                <p class="mt-2 mb-0 small text-muted">No photo available</p>
                                            </div>`
                                        }
                                    </div>
                                </div>

                                <h6 class="text-primary">Cabinet Contents</h6>
                                ${shouldHighlight ? 
                                    `<div class="alert alert-info mb-3">
                                        <i class="fas fa-search me-2"></i>
                                        Items matching "<strong>${searchTerm}</strong>" are highlighted below.
                                    </div>` : ''
                                }
                                ${cabinet.items && cabinet.items.length > 0 ? 
                                    `<div class="cabinet-contents-container ${cabinet.items.length > 7 ? 'cabinet-contents-scrollable' : ''}">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Item Name</th>
                                                        <th>Category</th>
                                                        <th>Quantity</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${cabinet.items.map(item => {
                                                        const isHighlighted = shouldHighlight && item.name.toLowerCase().includes(searchTerm.toLowerCase());
                                                        const rowClass = isHighlighted ? 'table-warning highlight-item' : '';
                                                        const highlightIcon = isHighlighted ? '<i class="fas fa-star text-warning me-1"></i>' : '';
                                                        
                                                        return `
                                                            <tr class="${rowClass}">
                                                                <td>${highlightIcon}${item.name}</td>
                                                                <td><span class="badge bg-secondary">${item.category_name}</span></td>
                                                                <td><span class="badge bg-primary">${item.quantity}</span></td>
                                                            </tr>
                                                        `;
                                                    }).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>` :
                                    `<div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        This cabinet contains no items.
                                    </div>`
                                }
                            `;
                        }, 3000);
                    } else {
                        content.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Cabinet not found or error loading data.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching cabinet data:', error);
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading cabinet data. Please try again.
                        </div>
                    `;
                });
        }

        // What's New Modal functionality
        document.getElementById('whatsNewBtn').addEventListener('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('whatsNewModal'));
            modal.show();
        });

        document.querySelectorAll('.whats-new-toggle').forEach(function(row) {
            var versionId = row.getAttribute('data-version');
            var target = document.getElementById(versionId);
            var arrow = row.querySelector('.toggle-arrow');
            row.setAttribute('aria-expanded', 'false');
            if (target) {
                row.addEventListener('click', function(e) {
                    if (e.target.closest('.collapse')) return;
                    var isOpen = target.classList.contains('show');
                    document.querySelectorAll('.collapse[id^="wn-v"]').forEach(function(el) {
                        if (el !== target) {
                            el.classList.remove('show');
                            var otherRow = document.querySelector('.whats-new-toggle[data-version="' + el.id + '"]');
                            if (otherRow) otherRow.setAttribute('aria-expanded', 'false');
                        }
                    });
                    if (isOpen) {
                        target.classList.remove('show');
                        row.setAttribute('aria-expanded', 'false');
                        arrow.querySelector('svg').style.transform = 'rotate(0deg)';
                    } else {
                        target.classList.add('show');
                        row.setAttribute('aria-expanded', 'true');
                        arrow.querySelector('svg').style.transform = 'rotate(90deg)';
                    }
                });
            }
        });

        // Toggle arrow rotation for What's New
        document.querySelectorAll('.toggle-arrow').forEach(arrow => {
            arrow.style.transition = 'transform 0.2s ease';
        });
    </script>
</body>

</html>