<?php
// Sidebar component for authenticated pages
?>
<style>
    :root {
        --sidebar-width: 280px;
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
        left: -280px !important;
        /* Force hidden - move completely off screen */
        z-index: 1050;
        transition: left 0.3s ease-in-out;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2), 0 0 20px rgba(0, 0, 0, 0.1);
        transform: translateX(0);
        border-right: 3px solid white;
        /* Ensure no transform conflicts */
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Show sidebar when 'show' class is added */
    #sidebar.show {
        left: 0 !important;
    }

    /* Content should always take full width */
    #content {
        margin-left: 0 !important;
        width: 100% !important;
        transition: none;
        /* Remove any margin transitions */
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
        color: white !important;
    }

    /* Tablet-specific improvements */
    @media (max-width: 1024px) and (min-width: 769px) {
        #sidebarClose {
            width: 28px !important;
            height: 28px !important;
            padding: 0.2rem !important;
            font-size: 0.75rem !important;
        }
        
        #sidebarClose i {
            font-size: 0.75rem !important;
        }
    }

    /* Mobile-specific sidebar improvements */
    @media (max-width: 768px) {
        #sidebar {
            left: -280px !important;
            width: 280px;
            max-height: 100vh;
            /* Account for mobile browser UI */
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }
        
        /* Fix close button size on mobile - much smaller */
        #sidebarClose {
            width: 24px !important;
            height: 24px !important;
            padding: 0 !important;
            font-size: 0.625rem !important;
            border-radius: 4px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 24px !important;
            min-height: 24px !important;
        }
        
        #sidebarClose i {
            font-size: 0.625rem !important;
            line-height: 1 !important;
            margin: 0 !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* Ensure footer is visible on mobile */
        #sidebar .sidebar-footer {
            margin-top: auto;
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
        }
        
        /* Mobile-specific content padding */
        #sidebar .p-3 {
            padding: 1rem !important;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0; /* Allow flex shrinking */
        }
        
        /* Ensure navigation takes available space */
        #sidebar .nav {
            flex: 1;
            margin-bottom: 1rem;
            overflow-y: auto;
        }
        
        /* Mobile viewport height handling */
        #sidebar {
            height: 100vh;
            height: 100dvh; /* Dynamic viewport height for mobile */
            /* Account for mobile navigation bars */
            padding-bottom: 60px; /* Space for mobile navigation bar */
            box-sizing: border-box;
        }
        
        /* Ensure footer is always visible above mobile nav bar */
        #sidebar .sidebar-footer {
            position: sticky;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            z-index: 10;
            margin-top: auto;
            padding: 0.75rem 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            /* Force footer above mobile navigation */
            margin-bottom: 0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.3);
        }
        
        /* Additional mobile navigation bar handling */
        @supports (padding: max(0px)) {
            #sidebar {
                padding-bottom: max(60px, env(safe-area-inset-bottom, 0px));
            }
        }
        
        /* Prevent body scroll when sidebar is open */
        body.sidebar-open {
            overflow: hidden;
            position: fixed;
            width: 100%;
        }
    }
    
    /* Extra small mobile devices */
    @media (max-width: 480px) {
        #sidebarClose {
            width: 20px !important;
            height: 20px !important;
            padding: 0.1rem !important;
            font-size: 0.5rem !important;
        }
        
        #sidebarClose i {
            font-size: 0.5rem !important;
        }
        
        #sidebar {
            padding-bottom: 80px; /* More space for smaller devices */
        }
        
        #sidebar .sidebar-footer {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }
    }

    @media (min-width: 769px) {
        #sidebar {
            left: -280px !important;
            /* Keep hidden on desktop too */
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
                <i class="fa fa-archive text-white" style="font-size: 40px;"></i>
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
                <a href="cabinet.php" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'cabinet.php' ? 'active' : ''; ?>" style="display: flex; align-items: center;">
                    <i class="fa fa-archive text-white me-2"></i> Cabinet Management
                </a>
            </li>
            <!-- Search Cabinets button removed -->
            <li class="nav-item">
                <a href="profile.php" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog me-2"></i> Profile
                </a>
            </li>

            <!-- Admin-Only Navigation -->
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                <li class="nav-item mt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-white" style="color: white !important;">
                        <i class="fas fa-crown me-2 text-white"></i>Administration
                    </h6>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" style="display: flex; align-items: center;">
                        <i class="fas fa-users me-2"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pin-management.php" class="nav-link sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'pin-management.php' ? 'active' : ''; ?>" style="display: flex; align-items: center;">
                        <i class="fas fa-key me-2"></i> PIN Management
                    </a>
                </li>
            <?php endif; ?>


            <!-- Common Footer Navigation -->
            <li class="nav-item mt-4">
                <a href="#" id="logoutSidebarBtn" class="nav-link sidebar-link">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Mobile-optimized footer -->
    <div class="sidebar-footer">
        <hr class="text-light">
        <div class="text-center text-light small">
            <p class="mb-1">Logged in as:</p>
            <p class="mb-0"><strong><?php echo isset($_SESSION['first_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'User'; ?></strong></p>
            <p class="mb-0 text-white"><?php echo isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : ''; ?></p>
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
            sidebar.style.left = '-280px';
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
            // Mobile-specific body scroll prevention
            document.body.classList.add('sidebar-open');
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
            // Restore body scroll
            document.body.classList.remove('sidebar-open');
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
        
        // Mobile navigation bar detection and handling
        function handleMobileNavigationBar() {
            if (window.innerWidth <= 768) {
                // Detect if mobile navigation bar is present
                const viewportHeight = window.innerHeight;
                const screenHeight = window.screen.height;
                const navigationBarHeight = screenHeight - viewportHeight;
                
                if (navigationBarHeight > 0) {
                    // Mobile navigation bar detected
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) {
                        sidebar.style.paddingBottom = (navigationBarHeight + 20) + 'px';
                    }
                }
            }
        }
        
        // Run on load and resize
        handleMobileNavigationBar();
        window.addEventListener('resize', handleMobileNavigationBar);
        window.addEventListener('orientationchange', function() {
            setTimeout(handleMobileNavigationBar, 100);
        });
    });
</script>