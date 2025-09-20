<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'includes/config.php';
} catch (Exception $e) {
    die("Config error: " . $e->getMessage());
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
    <title>Cabinet Viewer - Cabinet Management System</title>
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
                <i class="fas fa-cabinet-filing me-2"></i>Cabinet Management System
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
            <form method="POST" action="" id="searchForm">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-center gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="search_type" id="search_cabinet" value="cabinet" <?php echo $searchType === 'cabinet' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="search_cabinet">
                                    <i class="fas fa-cabinet-filing me-1"></i> Search Cabinet
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="search_type" id="search_item" value="item" <?php echo $searchType === 'item' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="search_item">
                                    <i class="fas fa-box me-1"></i> Search Item
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
                    <button class="btn btn-primary" type="submit" id="searchButton">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
            </form>

            <div class="text-center mt-3">
                <button id="qrCodeBtn" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#qrDisplayModal" disabled>
                    <i class="fas fa-qrcode me-1"></i> <span id="qrBtnText">Select a Cabinet First</span>
                </button>
                <button id="qrScanBtn" class="btn btn-outline-success">
                    <i class="fas fa-camera me-1"></i> Scan QR Code
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

    <div class="modal fade" id="viewCabinetModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-cabinet-filing me-2"></i>Cabinet Details
                    </h5>
                </div>
                <div class="modal-body">
                    <div id="viewCabinetContent">
                        <div class="text-center py-4">
                            <video src="assets/images/Trail-Loading.webm" style="width: 150px; height: 150px; margin: 0 auto; display:block;" autoplay muted loop playsinline></video>
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

    <div class="modal fade" id="qrDisplayModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-qrcode me-2"></i><span id="qrModalTitle">QR Code for Cabinet</span>
                    </h5>
                </div>
                <div class="modal-body text-center" id="qrModalBody">
                    <div class="text-center py-4">
                        <video src="assets/images/Trail-Loading.webm" style="width: 150px; height: 150px; margin: 0 auto; display:block;" autoplay muted loop playsinline></video>
                        <h5 class="mt-3 text-muted">Loading QR Code...</h5>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="assets/js/index.js"></script>
    <script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
        document.addEventListener('DOMContentLoaded', function() {
            updateQRButtonState();

            setTimeout(() => {
                if (typeof Html5Qrcode !== 'undefined') {
                    console.log('Html5Qrcode library loaded successfully');
                } else {
                    console.error('Html5Qrcode library failed to load');
                }
            }, 1000);

            let html5QrCode = null;
            let isScanning = false;

            document.getElementById('qrScanBtn').addEventListener('click', function() {
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

                if (!isHTTPS && !isLocalhost && isLocalNetwork) {
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
                } else if (!isHTTPS && !isLocalhost) {
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
                    updateQRButtonState();
                    updateSelectedCabinetVisual();
                }
            });

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

            const viewCabinetModal = document.getElementById('viewCabinetModal');
            if (viewCabinetModal) {
                viewCabinetModal.addEventListener('hidden.bs.modal', function() {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                    });

                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';

                    const content = document.getElementById('viewCabinetContent');
                    if (content) {
                        content.innerHTML = `
                            <div class="text-center py-4">
                                <video src="assets/images/Trail-Loading.webm" style="width: 150px; height: 150px; margin: 0 auto; display:block;" autoplay muted loop playsinline><\/video>
                                <h5 class="mt-3 text-muted">Loading Cabinet Details...<\/h5>
                            <\/div>
                        `;
                    }
                });
            }

            const searchForm = document.getElementById('searchForm');
            const searchButton = document.getElementById('searchButton');

            if (searchForm && searchButton) {
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const searchInput = document.querySelector('input[name="search_term"]');
                    const searchTerm = searchInput ? searchInput.value.trim() : '';

                    searchButton.innerHTML = `
                        <div class="d-inline-block me-2" style="width: 20px; height: 20px;">
                            <video src="assets/images/Trail-Loading.webm" style="width: 100%; height: 100%; display:block;" autoplay muted loop playsinline><\/video>
                        <\/div>
                        Searching...
                    `;
                    searchButton.disabled = true;

                    const overlay = document.createElement('div');
                    overlay.id = 'search-loading-overlay';
                    overlay.className = 'modal fade show';
                    overlay.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.5);
                        z-index: 9999;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    `;

                    const searchMessage = searchTerm ? `Searching "${searchTerm}"...` : 'Searching...';

                    overlay.innerHTML = `
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-body text-center py-5">
                                    <video src="assets/images/Trail-Loading.webm" style="width: 150px; height: 150px; margin: 0 auto; display:block;" autoplay muted loop playsinline><\/video>
                                    <h5 class="mt-3 text-muted">${searchMessage}<\/h5>
                                <\/div>
                            <\/div>
                        <\/div>
                    `;

                    document.body.appendChild(overlay);
                    document.body.classList.add('modal-open');

                    setTimeout(() => {
                        searchForm.submit();
                    }, 3000);
                });
            }
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

            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <video src="assets/images/Trail-Loading.webm" style="width: 150px; height: 150px; margin: 0 auto; display:block;" autoplay muted loop playsinline><\/video>
                    <h5 class="mt-3 text-muted">Loading QR Code...</h5>
                </div>
            `;

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
            }, 3000);
        }

        function loadCabinetDetails(cabinetId, searchType = '', searchTerm = '') {
            const content = document.getElementById('viewCabinetContent');
            const modal = new bootstrap.Modal(document.getElementById('viewCabinetModal'));

            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="loading-container position-relative">
                        <!-- Primary loading (Bootstrap spinner) - always visible -->
                        <div class="spinner-border text-primary primary-spinner" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <!-- Secondary loading (Video) - overlay if available -->
                        <video
                            class="video-loader position-absolute top-0 start-50 translate-middle-x"
                            src="assets/images/Trail-Loading.webm"
                            style="width: 150px; height: 150px; z-index: 10; opacity: 0; display:block;"
                            autoplay muted loop playsinline>
                        </video>
                    </div>
                    <h5 class="mt-3 text-muted">Loading Cabinet Details...</h5>
                </div>
            `;
            modal.show();
            console.log('Cabinet modal opened with dual loading system');

            let videoLoaded = false;
            let dataLoaded = false;
            const videoPlayer = content.querySelector('.video-loader');
            const primarySpinner = content.querySelector('.primary-spinner');

            const hideLoadingAnimations = () => {
                if (dataLoaded && videoPlayer && primarySpinner) {
                    videoPlayer.style.display = 'none';
                    primarySpinner.style.display = 'none';
                }
            };

            const onVideoReady = () => {
                if (videoPlayer && primarySpinner && !videoLoaded) {
                    videoLoaded = true;
                    videoPlayer.style.opacity = '1';
                    primarySpinner.style.display = 'none';
                    console.log('Video animation loaded successfully');
                }
            };

            if (videoPlayer) {
                videoPlayer.addEventListener('loadeddata', onVideoReady, {
                    once: true
                });
                videoPlayer.addEventListener('canplaythrough', onVideoReady, {
                    once: true
                });
            }

            setTimeout(() => {
                if (!videoLoaded && videoPlayer && primarySpinner) {
                    videoPlayer.style.display = 'none';
                    primarySpinner.style.display = 'inline-block';
                    console.log('Using fallback spinner for loading animation');
                }
            }, 2000);

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
                                <div class="loading-container position-relative">
                                    <!-- Primary loading (Bootstrap spinner) - always visible -->
                                    <div class="spinner-border text-primary primary-spinner" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <!-- Secondary loading (Video) - overlay if available -->
                                    <video
                                        class="video-loader position-absolute top-0 start-50 translate-middle-x"
                                        src="assets/images/Trail-Loading.webm"
                                        style="width: 150px; height: 150px; z-index: 10; opacity: 0; display:block;"
                                        autoplay muted loop playsinline>
                                    </video>
                                </div>
                                <h5 class="mt-3 text-muted">${loadingMessage}</h5>
                            </div>
                        `;

                        const secondVideoPlayer = content.querySelector('.video-loader');
                        const secondSpinner = content.querySelector('.primary-spinner');
                        let secondVideoLoaded = false;

                        console.log('Starting second phase loading with personalized message:', loadingMessage);

                        const onSecondVideoReady = () => {
                            if (secondVideoPlayer && secondSpinner && !secondVideoLoaded) {
                                secondVideoLoaded = true;
                                secondVideoPlayer.style.opacity = '1';
                                secondSpinner.style.display = 'none';
                                console.log('Second phase video animation loaded successfully');
                            }
                        };

                        if (secondVideoPlayer) {
                            secondVideoPlayer.addEventListener('loadeddata', onSecondVideoReady, {
                                once: true
                            });
                            secondVideoPlayer.addEventListener('canplaythrough', onSecondVideoReady, {
                                once: true
                            });
                        }
                        setTimeout(() => {
                            if (!secondVideoLoaded && secondVideoPlayer && secondSpinner) {
                                secondVideoPlayer.style.display = 'none';
                                secondSpinner.style.display = 'inline-block';
                                console.log('Using fallback spinner for second phase loading');
                            }
                        }, 2000);

                        setTimeout(() => {
                            dataLoaded = true;

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
    </script>
    <button id="whatsNewBtn" type="button" class="btn btn-primary rounded-circle shadow-lg" style="position:fixed;bottom:24px;right:24px;z-index:1055;width:56px;height:56px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;">
        <i class="fas fa-question"></i>
    </button>
    <div class="modal fade" id="whatsNewModal" tabindex="-1" aria-labelledby="whatsNewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px; width:90vw;">
            <div class="modal-content" style="border-radius:16px;">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="whatsNewModalLabel"><i class="fas fa-bolt text-warning me-2"></i>What's New?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-2 px-2">
                    <ul class="list-group list-group-flush whats-new-list" style="max-height:180px;overflow-y:auto;" id="whatsNewAccordion">
                        <li class="list-group-item p-2 whats-new-toggle" data-version="wn-v14" style="cursor:pointer;user-select:none;">
                            <span class="toggle-arrow" style="display:inline-block;width:18px;vertical-align:middle;">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.4</strong> - Added QR code scanning for cabinets</span>
                            <div class="collapse mt-1" id="wn-v14" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">You can now scan QR codes to quickly access cabinet details using your device camera.</div>
                            </div>
                        </li>
                        <li class="list-group-item p-2 whats-new-toggle" data-version="wn-v13" style="cursor:pointer;user-select:none;">
                            <span class="toggle-arrow" style="display:inline-block;width:18px;vertical-align:middle;">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.3</strong> - Improved mobile responsiveness</span>
                            <div class="collapse mt-1" id="wn-v13" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">The interface now adapts better to phones and tablets for easier use on the go.</div>
                            </div>
                        </li>
                        <li class="list-group-item p-2 whats-new-toggle" data-version="wn-v12" style="cursor:pointer;user-select:none;">
                            <span class="toggle-arrow" style="display:inline-block;width:18px;vertical-align:middle;">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.2</strong> - User-friendly error messages</span>
                            <div class="collapse mt-1" id="wn-v12" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">Clearer error messages help you understand and fix issues faster.</div>
                            </div>
                        </li>
                        <li class="list-group-item p-2 whats-new-toggle" data-version="wn-v11" style="cursor:pointer;user-select:none;">
                            <span class="toggle-arrow" style="display:inline-block;width:18px;vertical-align:middle;">
                                <svg width="16" height="16" viewBox="0 0 16 16">
                                    <polygon points="5,3 13,8 5,13" fill="#555" />
                                </svg>
                            </span>
                            <span class="toggle-label"><strong>v1.1</strong> - Initial public viewer release</span>
                            <div class="collapse mt-1" id="wn-v11" data-parent="#whatsNewAccordion">
                                <div class="text-secondary small ms-4">First release of the public cabinet viewer for easy access to cabinet information.</div>
                            </div>
                        </li>
                        <li class="list-group-item p-2 whats-new-toggle" data-version="wn-v10" style="cursor:pointer;user-select:none;">
                            <span class="toggle-arrow" style="display:inline-block;width:18px;vertical-align:middle;">
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
    <style>
        #whatsNewBtn {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
            transition: background 0.2s;
        }

        #whatsNewBtn:active {
            background: #0b5ed7;
        }

        .whats-new-list {
            min-width: 200px;
        }

        @media (max-width: 600px) {
            #whatsNewBtn {
                right: 12px;
                bottom: 12px;
                width: 48px;
                height: 48px;
                font-size: 1.2rem;
            }

            .modal-dialog {
                margin: 0 auto;
            }

            .whats-new-list {
                max-height: 120px;
                font-size: 0.98rem;
            }
        }
    </style>
    <style>
        .toggle-arrow svg {
            transition: transform 0.2s;
        }

        .toggle-arrow[aria-expanded="true"] svg {
            transform: rotate(90deg);
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo isset($GLOBALS['csp_nonce']) ? $GLOBALS['csp_nonce'] : ''; ?>">
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
                    } else {
                        target.classList.add('show');
                        row.setAttribute('aria-expanded', 'true');
                    }
                });
            }
        });
    </script>
</body>

</html>