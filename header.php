<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic System</title>
    <!-- Google Fonts: Inter for Geometric Sans look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Side Menu Design */
        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            transition: background var(--transition-fast);
        }

        .menu-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .side-menu {
            position: fixed;
            top: 0;
            left: -300px;
            width: 300px;
            height: 100%;
            background: var(--color-surface);
            z-index: 2000;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-xl);
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--color-border);
        }

        .side-menu.is-open {
            left: 0;
        }

        .side-menu-header {
            padding: 24px;
            background: var(--color-brand);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .side-menu-header h3 {
            margin: 0;
            font-size: var(--text-lg);
            font-weight: 800;
        }

        .close-menu {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .close-menu:hover {
            opacity: 1;
        }

        .side-menu-content {
            padding: 16px 0;
            flex: 1;
            overflow-y: auto;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 24px;
            text-decoration: none;
            color: var(--color-text-primary);
            font-weight: 600;
            font-size: var(--text-sm);
            transition: all var(--transition-fast);
            border-left: 4px solid transparent;
        }

        .menu-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
            color: var(--color-text-secondary);
        }

        .menu-item:hover {
            background: var(--color-brand-light);
            color: var(--color-brand);
        }

        .menu-item:hover i {
            color: var(--color-brand);
        }

        .menu-item.active {
            background: var(--color-brand-light);
            color: var(--color-brand);
            border-left-color: var(--color-brand);
        }

        .menu-item.active i {
            color: var(--color-brand);
        }

        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            z-index: 1999;
            display: none;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .menu-overlay.is-open {
            display: block;
            opacity: 1;
        }
        .topbar {
            background: var(--color-brand);
            color: white;
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1001;
            box-shadow: var(--shadow-sm);
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }

        .topbar-brand span {
            font-weight: 800;
            font-size: var(--text-lg);
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            gap: 24px;
        }

        .nav-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: var(--text-sm);
            font-weight: 600;
            transition: color var(--transition-fast);
        }

        .nav-links a:hover, .nav-links a.active {
            color: white;
        }
        .theme-toggle {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: var(--radius-full);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            transition: all var(--transition-base);
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }
    </style>
    <script>
        // Apply theme immediately to prevent FOIT/flashing
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>

<div class="topbar">
    <div style="display: flex; align-items: center; gap: 8px;">
        <button class="menu-toggle" id="menuToggleBtn" title="Open Menu">☰</button>
        <a href="employees.php" class="topbar-brand">
            <span>Employee</span>
        </a>
    </div>
    <div style="display: flex; align-items: center; gap: 20px;">
        <div class="nav-links">
        </div>
        <div class="theme-toggle" id="themeToggle" title="Toggle Theme">🌓</div>
    </div>
</div>

<!-- SIDE MENU -->
<div class="menu-overlay" id="menuOverlay"></div>
<div class="side-menu" id="sideMenu">
    <div class="side-menu-header">
        <h3>Clinic Menu</h3>
        <button class="close-menu" id="closeMenuBtn">✕</button>
    </div>
       <hr style="margin: 16px 24px; border: 0; border-top: 1px solid var(--color-border);">
        <a href="employees.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>">
             Dashboard

        </a>

       <a href="health.php?mode=add&id=0" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'health.php' ? 'active' : ''; ?>">
            New Profile
        </a>
        <a href="consultation.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'consultation.php' ? 'active' : ''; ?>">
             Consultation
        </a>
        <a href="prescriptions.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'prescriptions.php' ? 'active' : ''; ?>">
            Prescription
        </a>
        <a href="logout.php" class="menu-item">
             Log out
        </a>
    </div>
</div>

<script>
    const toggleBtn = document.getElementById('themeToggle');
    toggleBtn.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    });

    // Side Menu Logic
    const sideMenu = document.getElementById('sideMenu');
    const menuOverlay = document.getElementById('menuOverlay');
    const menuToggleBtn = document.getElementById('menuToggleBtn');
    const closeMenuBtn = document.getElementById('closeMenuBtn');

    function openMenu() {
        sideMenu.classList.add('is-open');
        menuOverlay.classList.add('is-open');
        document.body.style.overflow = 'hidden'; // Prevent scroll
    }

    function closeMenu() {
        sideMenu.classList.remove('is-open');
        menuOverlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    menuToggleBtn.addEventListener('click', openMenu);
    closeMenuBtn.addEventListener('click', closeMenu);
    menuOverlay.addEventListener('click', closeMenu);

    // Escape key to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenu();
    });
</script>
