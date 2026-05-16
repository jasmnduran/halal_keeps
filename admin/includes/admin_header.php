<?php
/**
 * Admin panel header / layout shell
 * Included at the top of every admin page.
 */
$admin_current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($admin_page_title ?? 'Admin Panel') ?> — Halal Keeps Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        /* ── Admin-specific overrides ── */
        :root { --admin-sidebar: #0f172a; --admin-accent: #6366f1; }

        .admin-layout { display:flex; min-height:100vh; }

        /* Sidebar */
        .admin-sidebar {
            width: 260px; background: var(--admin-sidebar);
            position: fixed; top:0; left:0; bottom:0;
            z-index:100; overflow-y:auto; display:flex; flex-direction:column;
            transition: width .3s ease;
        }
        .admin-sidebar-header {
            padding:20px 18px; border-bottom:1px solid rgba(255,255,255,.07);
            display:flex; align-items:center; gap:10px;
        }
        .admin-brand-icon {
            width:38px; height:38px; border-radius:10px;
            background:linear-gradient(135deg,#6366f1,#4f46e5);
            display:flex; align-items:center; justify-content:center;
            font-size:1rem; color:#fff; flex-shrink:0;
        }
        .admin-brand-text { font-family:var(--font-display); font-weight:700; font-size:.95rem; color:#fff; }
        .admin-brand-sub  { font-size:.7rem; color:rgba(255,255,255,.4); }

        .admin-nav { padding:12px 10px; flex:1; }
        .admin-nav-label {
            font-size:.65rem; text-transform:uppercase; letter-spacing:.1em;
            color:rgba(255,255,255,.3); padding:10px 12px 6px; font-weight:600;
        }
        .admin-nav-link {
            display:flex; align-items:center; gap:10px; padding:10px 14px;
            color:rgba(255,255,255,.55); border-radius:8px; font-size:.875rem;
            font-weight:500; transition:all .15s ease; margin-bottom:2px;
            text-decoration:none;
        }
        .admin-nav-link:hover { color:#fff; background:rgba(255,255,255,.07); }
        .admin-nav-link.active { color:#fff; background:linear-gradient(135deg,#6366f1,#4f46e5); }
        .admin-nav-link i { width:18px; text-align:center; font-size:.9rem; }
        .admin-nav-badge {
            margin-left:auto; background:#ef4444; color:#fff;
            font-size:.65rem; padding:2px 7px; border-radius:999px; font-weight:700;
        }

        .admin-sidebar-footer {
            padding:14px 10px; border-top:1px solid rgba(255,255,255,.07);
        }
        .admin-user-card {
            display:flex; align-items:center; gap:10px; padding:10px 12px;
            background:rgba(255,255,255,.05); border-radius:10px; color:#fff;
        }
        .admin-user-avatar {
            width:34px; height:34px; border-radius:8px;
            background:linear-gradient(135deg,#6366f1,#4f46e5);
            display:flex; align-items:center; justify-content:center;
            font-weight:700; font-size:.85rem; flex-shrink:0;
        }
        .admin-user-name  { font-size:.82rem; font-weight:600; }
        .admin-user-role  { font-size:.7rem; color:rgba(255,255,255,.4); }

        /* Main */
        .admin-main { margin-left:260px; flex:1; min-height:100vh; background:#f8fafc; }

        .admin-topbar {
            height:64px; background:#fff; border-bottom:1px solid #e2e8f0;
            display:flex; align-items:center; justify-content:space-between;
            padding:0 28px; position:sticky; top:0; z-index:50;
        }
        .admin-topbar-title { font-family:var(--font-display); font-weight:700; font-size:1.15rem; color:#1e293b; }
        .admin-topbar-right { display:flex; align-items:center; gap:10px; }

        .admin-content { padding:28px; }

        /* Security badge in topbar */
        .security-badge {
            display:inline-flex; align-items:center; gap:6px;
            background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;
            padding:5px 12px; border-radius:999px; font-size:.75rem; font-weight:600;
        }

        /* DLP banner */
        .dlp-banner {
            background:linear-gradient(135deg,#1e293b,#0f172a);
            color:rgba(255,255,255,.7); font-size:.75rem;
            padding:6px 28px; display:flex; align-items:center; gap:8px;
            border-bottom:1px solid rgba(255,255,255,.05);
        }
        .dlp-banner i { color:#6366f1; }

        /* Stat cards */
        .admin-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:18px; margin-bottom:24px; }
        .admin-stat-card {
            background:#fff; border-radius:14px; padding:22px 20px;
            border:1px solid #e2e8f0; position:relative; overflow:hidden;
        }
        .admin-stat-card::before {
            content:''; position:absolute; top:0; left:0; right:0; height:3px;
        }
        .admin-stat-card.indigo::before  { background:linear-gradient(90deg,#6366f1,#4f46e5); }
        .admin-stat-card.green::before   { background:linear-gradient(90deg,#22c55e,#16a34a); }
        .admin-stat-card.red::before     { background:linear-gradient(90deg,#ef4444,#dc2626); }
        .admin-stat-card.amber::before   { background:linear-gradient(90deg,#f59e0b,#d97706); }
        .admin-stat-card.blue::before    { background:linear-gradient(90deg,#3b82f6,#2563eb); }
        .admin-stat-card.purple::before  { background:linear-gradient(90deg,#a855f7,#9333ea); }

        .admin-stat-icon {
            width:44px; height:44px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.1rem; margin-bottom:14px;
        }
        .admin-stat-icon.indigo { background:#eef2ff; color:#6366f1; }
        .admin-stat-icon.green  { background:#dcfce7; color:#16a34a; }
        .admin-stat-icon.red    { background:#fee2e2; color:#dc2626; }
        .admin-stat-icon.amber  { background:#fef9c3; color:#d97706; }
        .admin-stat-icon.blue   { background:#dbeafe; color:#2563eb; }
        .admin-stat-icon.purple { background:#f3e8ff; color:#9333ea; }

        .admin-stat-value { font-family:var(--font-display); font-size:1.9rem; font-weight:800; color:#0f172a; line-height:1; margin-bottom:4px; }
        .admin-stat-label { font-size:.8rem; color:#64748b; font-weight:500; }

        /* Tables */
        .admin-table thead th { background:#f8fafc; font-size:.72rem; }

        /* Section card */
        .admin-card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; margin-bottom:22px; }
        .admin-card-header {
            padding:18px 22px; border-bottom:1px solid #f1f5f9;
            display:flex; align-items:center; justify-content:space-between;
        }
        .admin-card-header h3 { font-family:var(--font-display); font-weight:700; font-size:1rem; color:#1e293b; }
        .admin-card-body { padding:22px; }

        /* Tabs */
        .admin-tabs { display:flex; gap:4px; border-bottom:2px solid #e2e8f0; margin-bottom:22px; }
        .admin-tab {
            padding:9px 18px; font-size:.85rem; font-weight:600; color:#64748b;
            border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer;
            transition:all .15s; text-decoration:none;
        }
        .admin-tab:hover { color:#6366f1; }
        .admin-tab.active { color:#6366f1; border-bottom-color:#6366f1; }

        /* Toggle switch */
        .toggle-switch { position:relative; display:inline-block; width:42px; height:22px; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .toggle-slider {
            position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0;
            background:#cbd5e1; border-radius:22px; transition:.3s;
        }
        .toggle-slider::before {
            content:''; position:absolute; height:16px; width:16px;
            left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s;
        }
        .toggle-switch input:checked + .toggle-slider { background:#6366f1; }
        .toggle-switch input:checked + .toggle-slider::before { transform:translateX(20px); }

        /* Session timeout warning */
        #sessionWarning {
            position:fixed; bottom:24px; right:24px; z-index:9999;
            background:#1e293b; color:#fff; border-radius:14px; padding:18px 22px;
            box-shadow:0 20px 40px rgba(0,0,0,.3); display:none; max-width:320px;
        }
        #sessionWarning h4 { font-size:.9rem; font-weight:700; margin-bottom:6px; color:#fbbf24; }
        #sessionWarning p  { font-size:.8rem; color:rgba(255,255,255,.7); margin-bottom:12px; }

        /* Screenshot / print block */
        @media print {
            .admin-sidebar, .admin-topbar, .no-print { display:none !important; }
            .admin-main { margin-left:0; }
            body::before {
                content:'CONFIDENTIAL — HALAL KEEPS ADMIN';
                display:block; text-align:center; font-size:1.5rem;
                color:rgba(0,0,0,.15); padding:20px; letter-spacing:.2em;
            }
        }
    </style>
</head>
<body>

<!-- DLP Banner -->
<div class="dlp-banner no-print">
    <i class="fas fa-shield-alt"></i>
    <span>ADMIN PANEL — Restricted Access. All actions are logged. Unauthorized use is prohibited.</span>
    <span style="margin-left:auto;color:#6366f1">Session: <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
</div>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="admin-brand-icon"><i class="fas fa-shield-alt"></i></div>
            <div>
                <div class="admin-brand-text">Admin Panel</div>
                <div class="admin-brand-sub">Halal Keeps Security</div>
            </div>
        </div>

        <nav class="admin-nav">
            <div class="admin-nav-label">Overview</div>
            <a href="<?= BASE_URL ?>admin/index.php" class="admin-nav-link <?= $admin_current === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>

            <div class="admin-nav-label" style="margin-top:10px">Authorization</div>
            <a href="<?= BASE_URL ?>admin/users.php" class="admin-nav-link <?= $admin_current === 'users.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> User Management
            </a>
            <a href="<?= BASE_URL ?>admin/roles.php" class="admin-nav-link <?= $admin_current === 'roles.php' ? 'active' : '' ?>">
                <i class="fas fa-user-tag"></i> Roles & Permissions
            </a>
            <a href="<?= BASE_URL ?>admin/api_permissions.php" class="admin-nav-link <?= $admin_current === 'api_permissions.php' ? 'active' : '' ?>">
                <i class="fas fa-key"></i> API Permissions
            </a>

            <div class="admin-nav-label" style="margin-top:10px">Security & Logs</div>
            <a href="<?= BASE_URL ?>admin/login_logs.php" class="admin-nav-link <?= $admin_current === 'login_logs.php' ? 'active' : '' ?>">
                <i class="fas fa-sign-in-alt"></i> Login Attempt Logs
            </a>
            <a href="<?= BASE_URL ?>admin/activity_logs.php" class="admin-nav-link <?= $admin_current === 'activity_logs.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i> Admin Activity Logs
            </a>
            <a href="<?= BASE_URL ?>admin/system_logs.php" class="admin-nav-link <?= $admin_current === 'system_logs.php' ? 'active' : '' ?>">
                <i class="fas fa-list-alt"></i> System Activity Logs
            </a>

            <div class="admin-nav-label" style="margin-top:10px">Data Protection</div>
            <a href="<?= BASE_URL ?>admin/data_classification.php" class="admin-nav-link <?= $admin_current === 'data_classification.php' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> Data Classification
            </a>
            <a href="<?= BASE_URL ?>admin/dlp_settings.php" class="admin-nav-link <?= $admin_current === 'dlp_settings.php' ? 'active' : '' ?>">
                <i class="fas fa-lock"></i> DLP Settings
            </a>
            <a href="<?= BASE_URL ?>admin/encrypted_fields.php" class="admin-nav-link <?= $admin_current === 'encrypted_fields.php' ? 'active' : '' ?>">
                <i class="fas fa-database"></i> Encrypted Fields
            </a>

            <div class="admin-nav-label" style="margin-top:10px">System</div>
            <a href="<?= BASE_URL ?>admin/settings.php" class="admin-nav-link <?= $admin_current === 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> System Settings
            </a>
            <a href="<?= BASE_URL ?>dashboard/president/" class="admin-nav-link">
                <i class="fas fa-arrow-left"></i> Back to Portal
            </a>
            <a href="<?= BASE_URL ?>auth/logout.php" class="admin-nav-link" style="color:rgba(239,68,68,.7)">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <div class="admin-user-card">
                <div class="admin-user-avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?></div>
                <div>
                    <div class="admin-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></div>
                    <div class="admin-user-role">System Administrator</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="admin-main">
        <div class="admin-topbar">
            <div class="admin-topbar-title"><?= htmlspecialchars($admin_page_title ?? 'Admin Panel') ?></div>
            <div class="admin-topbar-right">
                <span class="security-badge no-print">
                    <i class="fas fa-shield-alt"></i> Secure Session
                </span>
                <span style="font-size:.8rem;color:#64748b" id="sessionTimer"></span>
                <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-sm btn-danger no-print">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="admin-content">
