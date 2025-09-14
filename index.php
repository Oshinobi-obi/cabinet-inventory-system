<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'includes/config.php';
} catch (Exception $e) {
    die("Config error: " . $e->getMessage());
}

// Process QR scan or search
$cabinetData = null;
$searchResults = [];
$error = null;
$searchType = 'cabinet'; // default search type
$searchTerm = '';
$pagination = null;

// Pagination settings
$itemsPerPage = 9;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['cabinet']) || isset($_GET['search_term'])) {
    // Handle different search types
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $searchTerm = sanitizeInput($_POST['search_term']);
        $searchType = isset($_POST['search_type']) ? sanitizeInput($_POST['search_type']) : 'cabinet';
    } else if (isset($_GET['search_term'])) {
        // Handle pagination with existing search
        $searchTerm = sanitizeInput($_GET['search_term']);
        $searchType = isset($_GET['search_type']) ? sanitizeInput($_GET['search_type']) : 'cabinet';
    } else {
        // QR scan always searches by cabinet
        $searchTerm = sanitizeInput($_GET['cabinet']);
        $searchType = 'cabinet';
    }

    try {
        if ($searchType === 'cabinet') {
            // Search by cabinet number or name with pagination
            // First get total count
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
            
            // Get paginated results
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
            
            // For backward compatibility with QR codes, set cabinetData for single result
            if (count($searchResults) === 1 && $totalResults === 1) {
                $cabinetData = $searchResults[0];
            }
            
        } else if ($searchType === 'item') {
            // Search by item name with pagination
            // First get total count
            $countStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT c.id) as total
                FROM cabinets c
                INNER JOIN items i ON c.id = i.cabinet_id
                WHERE i.name LIKE ?
            ");
            $countStmt->execute(["%$searchTerm%"]);
            $totalResults = $countStmt->fetch()['total'];
            $totalPages = ceil($totalResults / $itemsPerPage);
            
            // Get paginated results
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
        
        // Create pagination object
        if (isset($totalResults) && $totalResults > 0) {
            $pagination = [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_records' => $totalResults,
                'items_per_page' => $itemsPerPage
            ];
        }
        
    } catch(PDOException $e) {
        $error = "Error searching: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinet Viewer - Cabinet Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <link href="assets/css/index.css" rel="stylesheet">
    <link href="assets/css/mobile-enhancements.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark public-navbar">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cabinet-filing me-2"></i>Cabinet Inventory System
            </a>
            <div class="ms-auto">
                <a href="login.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
            </div>
        </div>
    </nav>
    <div class="viewer-container">
        <div class="header">
            <i class="fas fa-cabinet-filing"></i>
            <h1 class="mt-2">Cabinet Contents Viewer</h1>
            <p class="text-muted">Search by cabinet number or name, or scan QR code</p>
        </div>
        
        <div class="search-box">
            <form method="POST" action="">
                <!-- Search Type Radio Buttons -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-center gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="search_type" id="search_cabinet" value="cabinet" <?php echo $searchType === 'cabinet' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="search_cabinet">
                                    <i class="fas fa-cabinet-filing me-1"></i> Search by Name / Number
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="search_type" id="search_item" value="item" <?php echo $searchType === 'item' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="search_item">
                                    <i class="fas fa-box me-1"></i> Search by Item
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="input-group">
                    <input type="text" class="form-control form-control-lg" 
                           placeholder="Enter search term..." 
                           name="search_term" 
                           value="<?php echo htmlspecialchars($searchTerm); ?>" 
                           required>
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <button id="qrCodeBtn" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#qrDisplayModal" disabled>
                    <i class="fas fa-qrcode me-1"></i> <span id="qrBtnText">Select a Cabinet First</span>
                </button>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($searchResults)): ?>
            <div class="search-results">
                <h4 class="mb-4">
                    <i class="fas fa-list me-2"></i>Search Results 
                    <span class="badge bg-primary"><?php echo count($searchResults); ?> cabinet(s) found</span>
                </h4>
                
                <div class="row">
                    <?php foreach ($searchResults as $cabinet): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm cabinet-card" data-cabinet-id="<?php echo $cabinet['id']; ?>" data-cabinet-number="<?php echo htmlspecialchars($cabinet['cabinet_number']); ?>" data-cabinet-name="<?php echo htmlspecialchars($cabinet['name']); ?>" data-qr-path="<?php echo htmlspecialchars($cabinet['qr_path'] ?? ''); ?>">
                                <!-- Selection checkbox -->
                                <div class="position-absolute top-0 end-0 p-2">
                                    <input class="form-check-input cabinet-selector" type="radio" name="selected_cabinet" value="<?php echo $cabinet['id']; ?>" id="cabinet_<?php echo $cabinet['id']; ?>">
                                </div>
                                
                                <?php if ($cabinet['photo_path']): ?>
                                    <img src="<?php echo htmlspecialchars($cabinet['photo_path']); ?>" 
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
                                        <h5 class="card-title mb-0">Cabinet <?php echo htmlspecialchars($cabinet['cabinet_number']); ?></h5>
                                        <i class="fas fa-eye text-primary" style="cursor: pointer;" title="View Details"></i>
                                    </div>
                                    
                                    <h6 class="text-muted mb-2"><?php echo htmlspecialchars($cabinet['name']); ?></h6>
                                    
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
                
                <!-- Pagination Controls -->
                <?php if ($pagination && $pagination['total_pages'] > 1): ?>
                    <nav aria-label="Search results pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <!-- Previous page -->
                            <?php if ($pagination['current_page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link pagination-nav" href="#" 
                                       data-page="<?php echo $pagination['current_page'] - 1; ?>" 
                                       data-search-type="<?php echo htmlspecialchars($searchType); ?>" 
                                       data-search-term="<?php echo htmlspecialchars($searchTerm); ?>">&lt;</a>
                                </li>
                            <?php endif; ?>

                            <!-- Skip 5 pages backward -->
                            <?php if ($pagination['current_page'] > 5 && $pagination['total_pages'] >= 5): ?>
                                <?php $skipBackPage = max(1, $pagination['current_page'] - 5); ?>
                                <li class="page-item">
                                    <a class="page-link pagination-nav" href="#" 
                                       data-page="<?php echo $skipBackPage; ?>" 
                                       data-search-type="<?php echo htmlspecialchars($searchType); ?>" 
                                       data-search-term="<?php echo htmlspecialchars($searchTerm); ?>">&lt;&lt;</a>
                                </li>
                            <?php endif; ?>

                            <!-- Current page input -->
                            <li class="page-item active">
                                <input type="number" class="form-control form-control-sm text-center guest-page-input" 
                                       value="<?php echo $pagination['current_page']; ?>" 
                                       min="1" max="<?php echo $pagination['total_pages']; ?>" 
                                       style="width: 40px; height: 30px; border: none; font-size: 0.8rem; padding: 2px 4px;"
                                       data-max-pages="<?php echo $pagination['total_pages']; ?>"
                                       data-search-type="<?php echo htmlspecialchars($searchType); ?>" 
                                       data-search-term="<?php echo htmlspecialchars($searchTerm); ?>">
                            </li>

                            <!-- Skip 5 pages forward -->
                            <?php if ($pagination['current_page'] + 5 <= $pagination['total_pages'] && $pagination['total_pages'] >= 5): ?>
                                <?php $skipForwardPage = min($pagination['total_pages'], $pagination['current_page'] + 5); ?>
                                <li class="page-item">
                                    <a class="page-link pagination-nav" href="#" 
                                       data-page="<?php echo $skipForwardPage; ?>" 
                                       data-search-type="<?php echo htmlspecialchars($searchType); ?>" 
                                       data-search-term="<?php echo htmlspecialchars($searchTerm); ?>">&gt;&gt;</a>
                                </li>
                            <?php endif; ?>

                            <!-- Next page -->
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
                    
                    <!-- Pagination info -->
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
    <div class="modal fade" id="viewCabinetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-cabinet-filing me-2"></i>Cabinet Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="viewCabinetContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
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
    <div class="modal fade" id="qrDisplayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-qrcode me-2"></i><span id="qrModalTitle">QR Code for Cabinet</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="qrModalBody">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/index.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        // Handle view cabinet button clicks
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize QR button state
            updateQRButtonState();
            
            // Handle cabinet selection
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('cabinet-selector')) {
                    updateQRButtonState();
                    updateSelectedCabinetVisual();
                }
            });
            
            // Handle cabinet card clicks to select them
            document.addEventListener('click', function(e) {
                if (e.target.closest('.cabinet-card') && !e.target.closest('.view-cabinet-btn') && !e.target.classList.contains('cabinet-selector')) {
                    const card = e.target.closest('.cabinet-card');
                    const radio = card.querySelector('.cabinet-selector');
                    if (radio) {
                        radio.checked = true;
                        updateQRButtonState();
                        updateSelectedCabinetVisual();
                    }
                }
            });
            
            // Handle pagination clicks
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
            
            // Handle page input changes
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

            // Handle page input Enter key
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
            
            // View cabinet modal functionality
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
        });

        function updateQRButtonState() {
            const selectedCabinet = document.querySelector('input[name="selected_cabinet"]:checked');
            const qrBtn = document.getElementById('qrCodeBtn');
            const qrBtnText = document.getElementById('qrBtnText');
            
            if (selectedCabinet) {
                const card = selectedCabinet.closest('.cabinet-card');
                const cabinetNumber = card.getAttribute('data-cabinet-number');
                const cabinetName = card.getAttribute('data-cabinet-name');
                
                qrBtn.disabled = false;
                qrBtn.className = 'btn btn-primary';
                qrBtnText.textContent = `Show QR Code for Cabinet ${cabinetNumber}`;
                
                // Store selected cabinet data for the modal
                window.selectedCabinetData = {
                    id: selectedCabinet.value,
                    cabinet_number: cabinetNumber,
                    name: cabinetName,
                    qr_path: card.getAttribute('data-qr-path')
                };
            } else {
                qrBtn.disabled = true;
                qrBtn.className = 'btn btn-outline-primary';
                qrBtnText.textContent = 'Select a Cabinet First';
                window.selectedCabinetData = null;
            }
        }

        function updateSelectedCabinetVisual() {
            // Remove previous selections
            document.querySelectorAll('.cabinet-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to currently selected cabinet
            const selectedCabinet = document.querySelector('input[name="selected_cabinet"]:checked');
            if (selectedCabinet) {
                selectedCabinet.closest('.cabinet-card').classList.add('selected');
            }
        }

        function navigateToPage(page, searchType, searchTerm) {
            // Create URL with pagination parameters
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            url.searchParams.set('search_type', searchType);
            url.searchParams.set('search_term', searchTerm);
            
            // Navigate to the new page
            window.location.href = url.toString();
        }

        // Handle QR modal opening
        document.addEventListener('show.bs.modal', function(e) {
            if (e.target.id === 'qrDisplayModal') {
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
            
            // Show loading initially
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Load cabinet QR data
            setTimeout(() => {
                modalBody.innerHTML = `
                    <h6 class="mb-3">Cabinet: ${cabinet.cabinet_number}</h6>
                    
                    ${cabinet.qr_path ? `
                        <div class="qr-code-container mb-3">
                            <img src="${cabinet.qr_path}" 
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
            }, 500);
        }

        function loadCabinetDetails(cabinetId, searchType = '', searchTerm = '') {
            const content = document.getElementById('viewCabinetContent');
            
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            // Fetch cabinet data
            fetch(`public_api.php?action=get_cabinet&cabinet_id=${cabinetId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cabinet = data.cabinet;
                        
                        // Check if we need to highlight items based on search
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
                                        `<img src="${cabinet.photo_path}" alt="Cabinet Photo" class="img-fluid rounded shadow">` :
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
                                `<div class="table-responsive">
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
                                </div>` :
                                `<div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    This cabinet contains no items.
                                </div>`
                            }
                        `;
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
    </script>
</body>
</html>