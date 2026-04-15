<?php
require_once __DIR__ . '/../config/session.php';
$user = getCurrentUser();
$isLoggedIn = isLoggedIn();
?>
<link rel="stylesheet" href="<?= ASSETS_PATH ?>css/navbar.css">

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

      <!-- Give (CTA) — animated gradient border -->
      <li class="give-btn-li">
        <div class="give-btn-wrapper">
          <a href="<?= SITE_URL ?>/pages/give" class="give-btn">Give</a>
        </div>
      </li>

      <!-- Auth Items -->
      <?php if (isLoggedIn()): ?>
        <?php
        $user = getCurrentUser();
        $userBadges = getUserBadges($user['id']);
        ?>
        <!-- User Dropdown -->
        <li class="dropdown" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
          <button @click="open = !open" class="user-menu-trigger">
            <?php if (!empty($user['profile_photo'])): ?>
              <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" class="user-avatar-sm">
            <?php else: ?>
              <div class="user-avatar-placeholder-sm">
                <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
              </div>
            <?php endif; ?>
            <div class="user-trigger-name">
              <?= renderUserNameWithBadges($user['first_name'], $user['last_name'], $userBadges, 16) ?>
              <i class="fas fa-chevron-down user-chevron"></i>
            </div>
          </button>

          <div class="dropdown-menu user-dropdown" x-show="open" x-transition>
            <!-- User Info Header -->
            <div class="user-dropdown-header">
              <?php if (!empty($user['profile_photo'])): ?>
                <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" class="user-avatar-lg">
              <?php else: ?>
                <div class="user-avatar-placeholder-lg">
                  <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                </div>
              <?php endif; ?>
              <div>
                <div class="user-dropdown-name">
                  <?= renderUserNameWithBadges($user['first_name'], $user['last_name'], $userBadges, 18) ?>
                </div>
                <div class="user-dropdown-email">
                  <?= htmlspecialchars($user['email']) ?>
                </div>
              </div>
            </div>

            <!-- Menu Links -->
            <div class="user-dropdown-links">
              <a href="<?= SITE_URL ?>/pages/dashboard" class="user-dropdown-link">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
              </a>
              <a href="<?= SITE_URL ?>/pages/dashboard/profile" class="user-dropdown-link">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
              </a>
              <a href="<?= SITE_URL ?>/pages/dashboard/settings" class="user-dropdown-link">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
              </a>

              <?php if (isAdmin()): ?>
                <div class="user-dropdown-divider"></div>
                <a href="<?= SITE_URL ?>/admin" class="user-dropdown-link">
                  <i class="fas fa-shield-alt"></i>
                  <span>Admin Panel</span>
                </a>
              <?php endif; ?>

              <div class="user-dropdown-divider"></div>
              <a href="<?= SITE_URL ?>/auth/logout" class="user-dropdown-link user-dropdown-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
              </a>
            </div>
          </div>
        </li>

      <?php else: ?>
        <li><a href="<?= SITE_URL ?>/auth/login" class="btn-outline">Login</a></li>
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

    <!-- Give Button Mobile -->
    <li>
      <div class="give-btn-wrapper" style="display:block; margin: 1rem 0;">
        <a href="<?= SITE_URL ?>/pages/give" class="give-btn" style="display:block; text-align:center;">Give</a>
      </div>
    </li>

    <?php if ($isLoggedIn && $user): ?>
      <li class="mobile-user-section">
        <div class="mobile-user-info">
          <?php if (!empty($user['profile_photo'])): ?>
            <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" class="user-avatar-sm">
          <?php else: ?>
            <div class="user-avatar-placeholder-md">
              <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
            </div>
          <?php endif; ?>
          <div>
            <div class="mobile-user-name"><?= htmlspecialchars($user['first_name'] ?? 'User') ?> <?= htmlspecialchars($user['last_name'] ?? '') ?></div>
            <div class="mobile-user-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
          </div>
        </div>
      </li>
      <li><a href="<?= SITE_URL ?>/pages/dashboard">Dashboard</a></li>
      <li><a href="<?= SITE_URL ?>/pages/dashboard/profile">Profile</a></li>
      <?php if (isAdmin()): ?>
        <li><a href="<?= SITE_URL ?>/admin">Admin Panel</a></li>
      <?php endif; ?>
      <li><a href="<?= SITE_URL ?>/auth/logout" class="mobile-logout">Logout</a></li>
    <?php else: ?>
      <li><a href="<?= SITE_URL ?>/auth/login">Login</a></li>
      <li><a href="<?= SITE_URL ?>/auth/register">Create Account</a></li>
    <?php endif; ?>

  </ul>
</div>