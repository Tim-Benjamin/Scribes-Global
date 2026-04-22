<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>

<style>
/* ═══════════════════════════════════════════════════════════
   ADMIN SIDEBAR - MODERN REDESIGN
   ═══════════════════════════════════════════════════════════ */

:root {
    --primary-purple: #6B46C1;
    --primary-gold: #D4AF37;
    --primary-coral: #EB5757;
    --dark-bg: #1A1A2E;
    --white: #FFFFFF;
    --gray-50: #F9FAFB;
    --gray-100: #F3F4F6;
    --gray-200: #E5E7EB;
    --gray-300: #D1D5DB;
    --gray-600: #4B5563;
    --gray-700: #374151;
    --gray-800: #1F2937;
    --font-heading: 'Fraunces', Georgia, serif;
    --font-body: 'DM Sans', sans-serif;
    --transition: 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* ─── Sidebar Wrapper ──────────────────────────────────────– */
.admin-sidebar {
    width: 260px;
    background: linear-gradient(180deg, #FFFFFF 0%, #F9FAFB 100%);
    border-right: 1px solid var(--gray-200);
    padding: 1.5rem 0;
    overflow-y: auto;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    box-shadow: 4px 0 12px rgba(0, 0, 0, 0.05);
    z-index: 100;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    scrollbar-width: thin;
    scrollbar-color: rgba(107, 70, 193, 0.3) transparent;
}

.admin-sidebar::-webkit-scrollbar {
    width: 6px;
}

.admin-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: rgba(107, 70, 193, 0.3);
    border-radius: 3px;
}

.admin-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(107, 70, 193, 0.5);
}

/* ─── Header Section ────────────────────────────────────────– */
.admin-header-section {
    padding: 0 1.25rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    margin-bottom: 1rem;
}

.admin-logo {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    cursor: pointer;
    transition: all var(--transition);
    text-decoration: none;
}

.admin-logo:hover {
    opacity: 0.9;
    transform: translateX(4px);
}

.admin-logo-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, var(--primary-purple) 0%, #2D9CDB 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.375rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(107, 70, 193, 0.3);
    transition: all var(--transition);
}

.admin-logo:hover .admin-logo-icon {
    transform: rotate(-5deg) scale(1.05);
    box-shadow: 0 6px 16px rgba(107, 70, 193, 0.4);
}

.admin-logo-text {
    flex: 1;
    min-width: 0;
}

.admin-logo-text h2 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
    font-family: var(--font-heading);
    color: var(--dark-bg);
    letter-spacing: -0.3px;
}

.admin-logo-text p {
    margin: 0.125rem 0 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-family: var(--font-body);
}

/* ─── Navigation ────────────────────────────────────────────– */
.admin-nav {
    display: flex;
    flex-direction: column;
}

.admin-nav-section {
    padding: 0.75rem 0;
}

.admin-nav-section:not(:last-child) {
    border-bottom: 1px solid var(--gray-200);
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
}

.admin-nav-title {
    padding: 0 1.25rem;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--gray-600);
    margin-bottom: 0.6rem;
    font-family: var(--font-body);
}

/* ─── Navigation Items ──────────────────────────────────────– */
.admin-nav-item {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.8rem 1rem;
    margin: 0.3rem 0.5rem;
    color: var(--gray-700);
    text-decoration: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 500;
    font-family: var(--font-body);
    transition: all var(--transition);
    position: relative;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-nav-item i {
    font-size: 1.1rem;
    width: 1.375rem;
    text-align: center;
    flex-shrink: 0;
    transition: all var(--transition);
}

.admin-nav-item span {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-nav-item:hover {
    background: linear-gradient(90deg, rgba(107, 70, 193, 0.08) 0%, transparent 100%);
    color: var(--primary-purple);
    transform: translateX(4px);
    box-shadow: inset 3px 0 0 var(--primary-purple);
}

.admin-nav-item:hover i {
    transform: scale(1.15);
}

.admin-nav-item.active {
    background: linear-gradient(90deg, rgba(107, 70, 193, 0.12) 0%, transparent 100%);
    color: var(--primary-purple);
    font-weight: 700;
    box-shadow: inset 3px 0 0 var(--primary-purple);
}

.admin-nav-item.active i {
    color: var(--primary-purple);
}

/* ─── Badge ─────────────────────────────────────────────────– */
.admin-nav-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    background: var(--primary-coral);
    color: white;
    border-radius: 50%;
    font-size: 0.7rem;
    font-weight: 800;
    flex-shrink: 0;
    margin-left: auto;
    animation: badgePulse 2s ease-in-out infinite;
}

@keyframes badgePulse {

    0%,
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(235, 87, 87, 0.7);
    }

    50% {
        box-shadow: 0 0 0 6px rgba(235, 87, 87, 0);
    }
}

/* ─── Mobile Responsive ────────────────────────────────────– */
@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.2);
    }

    .admin-sidebar.mobile-visible {
        transform: translateX(0);
    }

    .admin-header-section {
        padding: 0 1rem 1.25rem;
    }

    .admin-nav-item {
        margin: 0.25rem 0.35rem;
        padding: 0.7rem 0.9rem;
    }

    .admin-nav-title {
        padding: 0 1rem;
        font-size: 0.7rem;
    }
}

@media (max-width: 480px) {
    .admin-sidebar {
        width: 100%;
        max-width: 280px;
    }

    .admin-logo-text h2 {
        font-size: 1rem;
    }

    .admin-nav-item span {
        display: none;
    }

    .admin-nav-item {
        justify-content: center;
        padding: 0.75rem;
        margin: 0.25rem 0.25rem;
        border-radius: 8px;
    }

    .admin-nav-item:hover,
    .admin-nav-item.active {
        box-shadow: none;
    }

    .admin-nav-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        min-width: 20px;
        height: 20px;
        font-size: 0.65rem;
    }
}
</style>

<aside class="admin-sidebar" id="adminSidebar">
    <!-- Logo Section -->
    <div class="admin-header-section">
        <a href="<?= SITE_URL ?>/admin" class="admin-logo">
            <div class="admin-logo-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="admin-logo-text">
                <h2>Admin</h2>
                <p>Dashboard</p>
            </div>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="admin-nav">

        <!-- Main Section -->
        <div class="admin-nav-section">
            <div class="admin-nav-title">Main</div>
            <a href="<?= SITE_URL ?>/admin" class="admin-nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/analytics"
                class="admin-nav-item <?= $currentPage === 'analytics' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </a>
        </div>

        <!-- Content Section -->
        <div class="admin-nav-section">
            <div class="admin-nav-title">Content Management</div>
            <a href="<?= SITE_URL ?>/admin/posts.php"
                class="admin-nav-item <?= $currentPage === 'posts' ? 'active' : '' ?>">
                <i class="fas fa-newspaper"></i>
                <span>Blog Posts</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/events"
                class="admin-nav-item <?= $currentPage === 'events' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Events</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/videos"
                class="admin-nav-item <?= $currentPage === 'videos' ? 'active' : '' ?>">
                <i class="fas fa-video"></i>
                <span>Videos</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/media"
                class="admin-nav-item <?= $currentPage === 'media' ? 'active' : '' ?>">
                <i class="fas fa-images"></i>
                <span>Media Library</span>
            </a>
        </div>

        <!-- Bookings Section -->
        <div class="admin-nav-section">
            <div class="admin-nav-title">Bookings & Requests</div>
            <a href="<?= SITE_URL ?>/admin/bookings"
                class="admin-nav-item <?= $currentPage === 'bookings' ? 'active' : '' ?>">
                <i class="fas fa-book"></i>
                <span>Bookings</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/prayers"
                class="admin-nav-item <?= $currentPage === 'prayers' ? 'active' : '' ?>">
                <i class="fas fa-praying-hands"></i>
                <span>Prayer Requests</span>
            </a>
        </div>

        <!-- Community Section -->
        <div class="admin-nav-section">
            <div class="admin-nav-title">Community</div>
            <a href="<?= SITE_URL ?>/admin/users"
                class="admin-nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/chapters"
                class="admin-nav-item <?= $currentPage === 'chapters' ? 'active' : '' ?>">
                <i class="fas fa-map-marked-alt"></i>
                <span>Chapters</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/ministries"
                class="admin-nav-item <?= $currentPage === 'ministries' ? 'active' : '' ?>">
                <i class="fas fa-hands-helping"></i>
                <span>Ministries</span>
            </a>
            <!-- <a href="<?= SITE_URL ?>/admin/badges" class="admin-nav-item <?= $currentPage === 'badges' ? 'active' : '' ?>">
        <i class="fas fa-award"></i>
        <span>Badges</span>
      </a> -->
            <a href="<?= SITE_URL ?>/admin/newsletter"
                class="sidebar-item <?= strpos($currentPage, '/admin/newsletter') !== false ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i>
                <span>Newsletter</span>
                <span class="badge">Beta</span>
            </a>
        </div>

        <!-- Financial Section -->
        <div class="admin-nav-section">
            <div class="admin-nav-title">Financial</div>
            <a href="<?= SITE_URL ?>/admin/donations"
                class="admin-nav-item <?= $currentPage === 'donations' ? 'active' : '' ?>">
                <i class="fas fa-donate"></i>
                <span>Donations</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/reports"
                class="admin-nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Reports</span>
            </a>
        </div>

        <!-- System Section -->
        <div class="admin-nav-section">
            <div class="admin-nav-title">System</div>
            <a href="<?= SITE_URL ?>/admin/settings"
                class="admin-nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="<?= SITE_URL ?>/pages/dashboard" class="admin-nav-item">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Site</span>
            </a>
            <a href="<?= SITE_URL ?>/auth/logout" class="admin-nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>

    </nav>
</aside>