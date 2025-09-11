<?php
// Sidebar component for authenticated pages
?>
<style>
    :root {
        --sidebar-width: 250px;
    }
    
    body {
        overflow-x: hidden;
    }
    
    /* Sidebar - HIDDEN BY DEFAULT */
    #sidebar {
        min-height: 100vh;
        width: var(--sidebar-width);
        position: fixed !important;
        top: 0;
        left: -250px !important; /* Force hidden - move completely off screen */
        z-index: 1050;
        transition: left 0.3s ease-in-out;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        transform: translateX(0); /* Ensure no transform conflicts */
    }
    
    /* Show sidebar when 'show' class is added */
    #sidebar.show {
        left: 0 !important;
    }
    
    /* Content should always take full width */
    #content {
        margin-left: 0 !important;
        width: 100% !important;
        transition: none; /* Remove any margin transitions */
    }
    
    /* Overlay that appears when sidebar is open */
    .sidebar-overlay {
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
    
    .sidebar-overlay.show {
        display: block;
        opacity: 1;
    }
    
    /* Sidebar links styling */
    .sidebar-link {
        color: #adb5bd;
        transition: all 0.3s;
        text-decoration: none;
        display: block;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        margin-bottom: 0.25rem;
    }
    
    .sidebar-link:hover, 
    .sidebar-link.active {
        color: #fff;
        background-color: #0d6efd;
        text-decoration: none;
    }
    
    .sidebar-heading {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.1rem;
        font-weight: 600;
        color: #6c757d !important;
    }
    
    /* Ensure sidebar is hidden on all screen sizes initially */
    @media (max-width: 768px) {
        #sidebar {
            left: -250px !important;
        }
    }
    
    @media (min-width: 769px) {
        #sidebar {
            left: -250px !important; /* Keep hidden on desktop too */
        }
    }
</style>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div id="sidebar" class="bg-dark">
    <div class="p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-center text-white flex-grow-1">
                <img src="assets/images/cabinet-icon.svg" alt="Cabinet Icon" style="width: 40px; height: 40px; filter: brightness(0) invert(1);" class="mb-2">
                <h5>Cabinet Inventory System</h5>
            </div>
            <button class="btn btn-sm btn-outline-light d-lg-none" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <hr class="text-light">
        <ul class="nav flex-column">
            <!-- Core Navigation - Available to ALL roles -->
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="cabinet.php" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'cabinet.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cabinet-filing me-2"></i> Cabinet Management
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php" class="nav-link sidebar-link" target="_blank">
                    <i class="fas fa-search me-2"></i> Search Cabinets
                </a>
            </li>
            
            <!-- Admin-Only Navigation -->
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <i class="fas fa-crown me-2"></i>Administration
                    </h6>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users me-2"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="export.php" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'export.php' ? 'active' : ''; ?>">
                        <i class="fas fa-download me-2"></i> Export Data
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- Encoder Quick Actions -->
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'encoder'): ?>
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <i class="fas fa-edit me-2"></i>Quick Actions
                    </h6>
                </li>
                <li class="nav-item">
                    <a href="cabinet.php" class="nav-link sidebar-link">
                        <i class="fas fa-plus me-2"></i> Add New Cabinet
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- Common Footer Navigation -->
            <li class="nav-item mt-4">
                <a href="logout.php" class="nav-link sidebar-link">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
        
        <hr class="text-light mt-4">
        <div class="text-center text-light small">
            <p class="mb-1">Logged in as:</p>
            <p class="mb-0"><strong><?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'User'; ?></strong></p>
            <p class="mb-0 text-muted"><?php echo isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : ''; ?></p>
        </div>
    </div>
</div>

<script nonce="<?php echo $GLOBALS['csp_nonce']; ?>">
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sidebar script loaded'); // Debug log
    
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');  
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Debug logs
    console.log('Sidebar elements found:', {
        toggle: !!sidebarToggle,
        close: !!sidebarClose,
        sidebar: !!sidebar,
        overlay: !!sidebarOverlay
    });
    
    // Force sidebar to be hidden on page load
    if (sidebar) {
        sidebar.classList.remove('show');
        sidebar.style.left = '-250px';
        console.log('Sidebar forced to hidden position');
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.classList.remove('show');
    }
    
    function showSidebar() {
        console.log('Showing sidebar');
        if (sidebar) {
            sidebar.classList.add('show');
        }
        if (sidebarOverlay) {
            sidebarOverlay.classList.add('show');
        }
        document.body.style.overflow = 'hidden';
    }
    
    function hideSidebar() {
        console.log('Hiding sidebar');
        if (sidebar) {
            sidebar.classList.remove('show');
        }
        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('show');
        }
        document.body.style.overflow = '';
    }
    
    // Toggle sidebar when burger menu is clicked
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Burger menu clicked');
            showSidebar();
        });
    } else {
        console.error('Sidebar toggle button not found!');
    }
    
    // Close sidebar when close button is clicked
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            hideSidebar();
        });
    }
    
    // Close sidebar when overlay is clicked
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            hideSidebar();
        });
    }
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
            hideSidebar();
        }
    });
});
</script>