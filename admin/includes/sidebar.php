<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-header-section">
    <div class="admin-logo">
      <div class="admin-logo-icon">
        <i class="fas fa-shield-alt"></i>
      </div>
      <div class="admin-logo-text">
        <h2>Admin Panel</h2>
        <p>Scribes Global</p>
      </div>
    </div>
  </div>
  
  <nav class="admin-nav">
    <div class="admin-nav-section">
      <div class="admin-nav-title">Main</div>
      <a href="<?= SITE_URL ?>/admin" class="admin-nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
        <i class="fas fa-chart-line"></i>
        <span>Dashboard</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/analytics" class="admin-nav-item <?= $currentPage === 'analytics' ? 'active' : '' ?>">
        <i class="fas fa-chart-bar"></i>
        <span>Analytics</span>
      </a>
    </div>
    
    <div class="admin-nav-section">
      <div class="admin-nav-title">Content</div>
      <a href="<?= SITE_URL ?>/admin/events" class="admin-nav-item <?= $currentPage === 'events' ? 'active' : '' ?>">
        <i class="fas fa-calendar-alt"></i>
        <span>Events</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/posts" class="admin-nav-item <?= $currentPage === 'posts' ? 'active' : '' ?>">
        <i class="fas fa-newspaper"></i>
        <span>Blog Posts</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/media" class="admin-nav-item <?= $currentPage === 'media' ? 'active' : '' ?>">
        <i class="fas fa-photo-video"></i>
        <span>Media</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/prayers" class="admin-nav-item <?= $currentPage === 'prayers' ? 'active' : '' ?>">
        <i class="fas fa-praying-hands"></i>
        <span>Prayer Requests</span>
      </a>
    </div>
    
    <div class="admin-nav-section">
      <div class="admin-nav-title">Users & Community</div>
      <a href="<?= SITE_URL ?>/admin/users" class="admin-nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
        <i class="fas fa-users"></i>
        <span>Users</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/badges" class="admin-nav-item <?= $currentPage === 'badges' ? 'active' : '' ?>">
        <i class="fas fa-award"></i>
        <span>Badges</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/chapters" class="admin-nav-item <?= $currentPage === 'chapters' ? 'active' : '' ?>">
        <i class="fas fa-map-marked-alt"></i>
        <span>Chapters</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/ministries" class="admin-nav-item <?= $currentPage === 'ministries' ? 'active' : '' ?>">
        <i class="fas fa-hands-helping"></i>
        <span>Ministries</span>
      </a>
    </div>
    
    <div class="admin-nav-section">
      <div class="admin-nav-title">Financial</div>
      <a href="<?= SITE_URL ?>/admin/donations" class="admin-nav-item <?= $currentPage === 'donations' ? 'active' : '' ?>">
        <i class="fas fa-donate"></i>
        <span>Donations</span>
      </a>
      <a href="<?= SITE_URL ?>/admin/reports" class="admin-nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
        <i class="fas fa-file-invoice-dollar"></i>
        <span>Reports</span>
      </a>
    </div>
    
    <div class="admin-nav-section">
      <div class="admin-nav-title">System</div>
      <a href="<?= SITE_URL ?>/admin/settings" class="admin-nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
        <i class="fas fa-cog"></i>
        <span>Settings</span>
      </a>
      <a href="<?= SITE_URL ?>/pages/dashboard" class="admin-nav-item">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Site</span>
      </a>
    </div>
  </nav>
</aside>