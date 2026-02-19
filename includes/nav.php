<?php
require_once __DIR__ . '/../config/session.php';
$user = getCurrentUser();
$isLoggedIn = isLoggedIn();
?>

<nav class="navbar" x-data="{ mobileMenuOpen: false }">
  <div class="navbar-container">
    <a href="<?= SITE_URL ?>" class="navbar-logo">
      <img src="<?= ASSETS_PATH ?>images/logo/logo.svg" alt="Scribes Global" onerror="this.style.display='none'">
      <span>Scribes Global</span>
    </a>
    
    <!-- Desktop Menu -->
    <ul class="navbar-menu">
      <!-- About Us Dropdown -->
      <li class="dropdown" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
        <a href="#" @click.prevent>About Us ▾</a>
        <div class="dropdown-menu" x-show="open" x-transition>
          <a href="<?= SITE_URL ?>/pages/about/scribes-global">Scribes Global</a>
          <a href="<?= SITE_URL ?>/pages/about/ministries">Ministries</a>
          <a href="<?= SITE_URL ?>/pages/about/chapters">Chapters</a>
        </div>
      </li>
      
      <!-- Projects Dropdown -->
      <li class="dropdown" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
        <a href="#" @click.prevent>Projects ▾</a>
        <div class="dropdown-menu" x-show="open" x-transition>
          <a href="<?= SITE_URL ?>/pages/projects/heal">Project H.E.A.L</a>
          <a href="<?= SITE_URL ?>/pages/projects/move">Project M.O.V.E</a>
        </div>
      </li>
      
      <!-- Events -->
      <li><a href="<?= SITE_URL ?>/pages/events">Events</a></li>
      
      <!-- Media -->
      <li><a href="<?= SITE_URL ?>/pages/media">Media</a></li>
      
      <!-- Connect Dropdown -->
      <li class="dropdown" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
        <a href="#" @click.prevent>Connect ▾</a>
        <div class="dropdown-menu" x-show="open" x-transition>
          <a href="<?= SITE_URL ?>/pages/connect/invite">Invite Scribes Global</a>
          <a href="<?= SITE_URL ?>/pages/connect/volunteer">Join/Volunteer</a>
        </div>
      </li>
      
      <!-- Give (CTA) -->
      <li><a href="<?= SITE_URL ?>/pages/give" class="navbar-cta">Give</a></li>
      
      <!-- Auth Items -->
      <?php if ($isLoggedIn && $user): ?>
        <li class="dropdown" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
          <a href="#" @click.prevent style="display: flex; align-items: center; gap: 0.5rem;">
            <?php if (!empty($user['profile_photo'])): ?>
              <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
            <?php else: ?>
              <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-purple); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.875rem;">
                <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
              </div>
            <?php endif; ?>
            <span><?= htmlspecialchars($user['first_name'] ?? 'User') ?> ▾</span>
          </a>
          <div class="dropdown-menu" x-show="open" x-transition>
            <a href="<?= SITE_URL ?>/pages/dashboard">Dashboard</a>
            <a href="<?= SITE_URL ?>/pages/dashboard/profile">Profile</a>
            <?php if (isAdmin()): ?>
              <a href="<?= SITE_URL ?>/admin">Admin Panel</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/auth/logout">Logout</a>
          </div>
        </li>
      <?php else: ?>
        <li><a href="<?= SITE_URL ?>/auth/login">Login</a></li>
      <?php endif; ?>
    </ul>
    
    <!-- Mobile Hamburger -->
    <div class="hamburger" @click="mobileMenuOpen = !mobileMenuOpen" :class="{ 'active': mobileMenuOpen }">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>
</nav>

<!-- Mobile Menu Overlay -->
<div class="mobile-overlay" :class="{ 'active': mobileMenuOpen }" @click="mobileMenuOpen = false"></div>

<!-- Mobile Menu -->
<div class="mobile-menu" :class="{ 'active': mobileMenuOpen }">
  <ul class="mobile-menu-list">
    <!-- About Us -->
    <li x-data="{ open: false }">
      <div class="mobile-dropdown-toggle" @click="open = !open">
        <span>About Us</span>
        <span x-text="open ? '−' : '+'"></span>
      </div>
      <div class="mobile-dropdown-content" :class="{ 'active': open }">
        <a href="<?= SITE_URL ?>/pages/about/scribes-global">Scribes Global</a>
        <a href="<?= SITE_URL ?>/pages/about/ministries">Ministries</a>
        <a href="<?= SITE_URL ?>/pages/about/chapters">Chapters</a>
      </div>
    </li>
    
    <!-- Projects -->
    <li x-data="{ open: false }">
      <div class="mobile-dropdown-toggle" @click="open = !open">
        <span>Projects</span>
        <span x-text="open ? '−' : '+'"></span>
      </div>
      <div class="mobile-dropdown-content" :class="{ 'active': open }">
        <a href="<?= SITE_URL ?>/pages/projects/heal">Project H.E.A.L</a>
        <a href="<?= SITE_URL ?>/pages/projects/move">Project M.O.V.E</a>
      </div>
    </li>
    
    <li><a href="<?= SITE_URL ?>/pages/events">Events</a></li>
    <li><a href="<?= SITE_URL ?>/pages/media">Media</a></li>
    
    <!-- Connect -->
    <li x-data="{ open: false }">
      <div class="mobile-dropdown-toggle" @click="open = !open">
        <span>Connect</span>
        <span x-text="open ? '−' : '+'"></span>
      </div>
      <div class="mobile-dropdown-content" :class="{ 'active': open }">
        <a href="<?= SITE_URL ?>/pages/connect/invite">Invite Scribes Global</a>
        <a href="<?= SITE_URL ?>/pages/connect/volunteer">Join/Volunteer</a>
      </div>
    </li>
    
    <li><a href="<?= SITE_URL ?>/pages/give" class="navbar-cta" style="display: block; text-align: center; margin: 1rem 0;">Give</a></li>
    
    <?php if ($isLoggedIn && $user): ?>
      <li style="padding: 1rem 0; border-top: 1px solid var(--gray-200);">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
          <?php if (!empty($user['profile_photo'])): ?>
            <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
          <?php else: ?>
            <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--primary-purple); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.25rem;">
              <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
            </div>
          <?php endif; ?>
          <div>
            <div style="font-weight: 700; color: var(--dark-bg);"><?= htmlspecialchars($user['first_name'] ?? 'User') ?> <?= htmlspecialchars($user['last_name'] ?? '') ?></div>
            <div style="font-size: 0.875rem; color: var(--gray-600);"><?= htmlspecialchars($user['email'] ?? '') ?></div>
          </div>
        </div>
      </li>
      <li><a href="<?= SITE_URL ?>/pages/dashboard">Dashboard</a></li>
      <li><a href="<?= SITE_URL ?>/pages/dashboard/profile">Profile</a></li>
      <?php if (isAdmin()): ?>
        <li><a href="<?= SITE_URL ?>/admin">Admin Panel</a></li>
      <?php endif; ?>
      <li><a href="<?= SITE_URL ?>/auth/logout" style="color: var(--primary-coral);">Logout</a></li>
    <?php else: ?>
      <li><a href="<?= SITE_URL ?>/auth/login">Login</a></li>
      <li><a href="<?= SITE_URL ?>/auth/register">Create Account</a></li>
    <?php endif; ?>
  </ul>
</div>