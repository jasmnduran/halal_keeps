<?php
// Get current page for active sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$role_id = $_SESSION['role_id'] ?? 0;
$role_name = $_SESSION['role_name'] ?? 'User';
$notif_count = isLoggedIn() ? getUnreadNotificationCount($conn, $_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Halal Institute of Development Philippines - Halal Certification Body System">
    <title><?= $page_title ?? 'Halal Institute of Development Philippines' ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<?php if (isset($is_dashboard) && $is_dashboard): ?>
<!-- Dashboard Layout -->
<div class="dashboard-layout">
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>" class="sidebar-brand">
                <div class="brand-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <span>Halal Keeps</span>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            <?php 
            $menu_items = getSidebarMenu($role_id);
            foreach ($menu_items as $item): 
                $is_active = (strpos($_SERVER['REQUEST_URI'], basename($item['url'])) !== false) || 
                             ($item['label'] === 'Dashboard' && ($current_page === 'index.php' && isset($is_dashboard)));
            ?>
            <div class="nav-item">
                <a href="<?= $item['url'] ?>" class="nav-link <?= $is_active ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
            </div>
            <?php endforeach; ?>
            
            <div class="nav-label" style="margin-top: 16px;">Account</div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>dashboard/profile.php" class="nav-link">
                    <i class="fas fa-user-cog"></i>
                    <span>My Profile</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= BASE_URL ?>auth/logout.php" class="nav-link" style="color: rgba(239,68,68,0.7);">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <?php if (!empty($_SESSION['avatar'])): ?>
                    <img src="<?= $_SESSION['avatar'] ?>" alt="Avatar" class="user-avatar">
                <?php else: ?>
                    <div class="user-avatar-placeholder">
                        <?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></div>
                    <div class="user-role"><?= htmlspecialchars($role_name) ?></div>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="dashboard-main">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <button class="btn-icon sidebar-toggle" style="display:none; background:none; border:none; font-size:1.2rem; color:var(--neutral-600); cursor:pointer;">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h2><?= $page_heading ?? 'Dashboard' ?></h2>
                    <?php if (isset($breadcrumbs)): ?>
                    <div class="breadcrumb">
                        <a href="<?= BASE_URL ?>">Home</a>
                        <?php foreach ($breadcrumbs as $crumb): ?>
                            <span>/</span>
                            <?php if (isset($crumb['url'])): ?>
                                <a href="<?= $crumb['url'] ?>"><?= $crumb['label'] ?></a>
                            <?php else: ?>
                                <span><?= $crumb['label'] ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="header-right">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search..." id="globalSearch">
                </div>
                
                <button class="header-notification" id="notifToggle">
                    <i class="fas fa-bell"></i>
                    <span class="notif-count" style="<?= $notif_count > 0 ? '' : 'display:none' ?>"><?= $notif_count ?></span>
                </button>
                
                <div class="dropdown">
                    <?php if (!empty($_SESSION['avatar'])): ?>
                        <img src="<?= $_SESSION['avatar'] ?>" alt="Avatar" class="header-avatar dropdown-trigger">
                    <?php else: ?>
                        <div class="header-avatar dropdown-trigger" style="background:var(--primary-100);color:var(--primary-700);display:flex;align-items:center;justify-content:center;font-weight:700;">
                            <?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="dropdown-menu">
                        <a href="<?= BASE_URL ?>dashboard/profile.php">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="<?= getDashboardUrl($role_id) ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <div class="divider"></div>
                        <a href="<?= BASE_URL ?>auth/logout.php" style="color: var(--danger);">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Notifications Panel -->
        <div class="notification-panel" id="notifPanel">
            <div class="panel-header">
                <h3>Notifications</h3>
                <button class="btn-icon close-notif-panel" style="background:var(--neutral-100);border:none;cursor:pointer;width:32px;height:32px;border-radius:8px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="panel-body" id="notifList">
                <!-- Notifications loaded dynamically -->
                <div class="empty-state" style="padding:30px 10px">
                    <div class="empty-icon" style="width:50px;height:50px;font-size:1.2rem">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <p>No new notifications</p>
                </div>
            </div>
        </div>
        
        <!-- Page Content -->
        <div class="dashboard-content">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
                    <i class="fas fa-<?= $_SESSION['flash_type'] === 'success' ? 'check-circle' : ($_SESSION['flash_type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                    <span><?= $_SESSION['flash_message'] ?></span>
                    <button class="close-alert"><i class="fas fa-times"></i></button>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>
<?php else: ?>
<!-- Public Page Layout -->
<?php endif; ?>
