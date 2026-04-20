<?php
require_once __DIR__ . '/../config/session.php';
$user = getCurrentUser();
$isLoggedIn = isLoggedIn();
?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap');

  :root {
    --primary-purple: #6B46C1;
    --primary-gold: #D4AF37;
    --secondary-gold-light: #F2D97A;
    --primary-coral: #EB5757;
    --pulse-purple: #a855f7;
    --dark-bg: #1A1A2E;
    --white: #FFFFFF;
    --gray-100: #F7FAFC;
    --gray-200: #EDF2F7;
    --gray-600: #718096;
    --gray-700: #4A5568;
    --font-heading: 'Syne', sans-serif;
    --font-body: 'DM Sans', sans-serif;
    --radius-full: 9999px;
    --transition-base: 300ms ease-in-out;
    --transition-fast: 150ms ease-in-out;
    --transition-slow: 500ms ease-in-out;
  }

  /* =========================================
     FLOATING PILL NAVBAR
     ========================================= */

  .pill-navbar {
    position: fixed;
    top: 0.3rem;
    left: 50%;
    transform: translateX(-50%);
    width: 92%;
    max-width: 1100px;
    z-index: 1000;
    font-family: var(--font-body);
  }

  .pill-navbar-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(15, 12, 26, 0.82);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 9999px;
    padding: 0.55rem 0.65rem 0.55rem 1.4rem;
    box-shadow:
      0 8px 32px rgba(0, 0, 0, 0.45),
      0 2px 8px rgba(0, 0, 0, 0.3),
      inset 0 1px 0 rgba(255, 255, 255, 0.06);
    transition: all var(--transition-base);
  }

  .pill-navbar-inner:hover {
    border-color: rgba(255, 255, 255, 0.14);
    box-shadow:
      0 12px 40px rgba(0, 0, 0, 0.5),
      0 2px 8px rgba(0, 0, 0, 0.3),
      inset 0 1px 0 rgba(255, 255, 255, 0.08);
  }

  /* =========================================
     LOGO
     ========================================= */

  .pill-logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    flex-shrink: 0;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 1.2rem;
    letter-spacing: -0.01em;
    color: #ffffff;
    transition: opacity var(--transition-fast);
  }

  .pill-logo:hover { 
    opacity: 0.85; 
  }

  .pill-logo img {
    height: 50px;
    width: auto;
    object-fit: contain;
    filter: brightness(1.1);
    transition: all 0.3s ease;
  }

  .pill-logo img:hover {
    transform: scale(1.06);
    filter: brightness(1.25);
  }

  /* =========================================
     CENTER NAV LINKS
     ========================================= */

  .pill-nav-links {
    display: none;
    align-items: center;
    gap: 0.1rem;
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .pill-nav-links > li {
    position: relative;
    list-style: none;
  }

  .pill-nav-links > li > a,
  .pill-nav-btn {
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.8rem;
    font-weight: 400;
    font-family: var(--font-body);
    letter-spacing: 0.01em;
    padding: 0.45rem 0.8rem;
    border-radius: 9999px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    transition: color var(--transition-fast), background var(--transition-fast);
    background: none;
    border: none;
    cursor: pointer;
    white-space: nowrap;
  }

  .pill-nav-links > li > a:hover,
  .pill-nav-btn:hover {
    color: rgba(255, 255, 255, 0.95);
    background: rgba(255, 255, 255, 0.07);
  }

  /* =========================================
     DROPDOWN
     ========================================= */

  .pill-dropdown { 
    position: relative; 
  }

  .pill-dropdown-menu {
    position: absolute;
    top: calc(100% + 12px);
    left: 50%;
    transform: translateX(-50%) translateY(-10px);
    background: rgba(15, 12, 26, 0.97);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 0.8rem 0;
    min-width: 300px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 180ms ease, transform 180ms ease, visibility 180ms ease;
    z-index: 1001;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 4px 12px rgba(0, 0, 0, 0.3);
  }

  .pill-dropdown-menu.show,
  .pill-dropdown:hover .pill-dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
  }

  /* ✨ Premium Dropdown Item with Large Image */
  .pill-dropdown-item {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    padding: 0.9rem 1.2rem;
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 400;
    font-family: var(--font-body);
    letter-spacing: 0.01em;
    transition: all var(--transition-fast);
  }

  .pill-dropdown-item:hover {
    color: rgba(255, 255, 255, 0.95);
    background: rgba(255, 255, 255, 0.06);
    padding-left: 1.5rem;
  }

  /* ✨ Large Rounded Image for Desktop Dropdown */
  .dropdown-item-image {
    width: 60px;
    height: 60px;
    min-width: 60px;
    border-radius: 14px;
    object-fit: cover;
    background: linear-gradient(135deg, rgba(107, 70, 193, 0.3), rgba(45, 156, 219, 0.3));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: rgba(212, 175, 55, 0.9);
    flex-shrink: 0;
    border: 2px solid rgba(255, 255, 255, 0.1);
    overflow: hidden;
    transition: all var(--transition-fast);
  }

  .pill-dropdown-item:hover .dropdown-item-image {
    border-color: rgba(212, 175, 55, 0.5);
    transform: scale(1.08);
    box-shadow: 0 4px 16px rgba(212, 175, 55, 0.2);
  }

  .dropdown-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  /* Dropdown item text stack */
  .dropdown-item-content {
    flex: 1;
    min-width: 0;
  }

  .dropdown-item-title {
    font-weight: 600;
    color: rgba(255, 255, 255, 0.95);
    font-size: 0.88rem;
    margin-bottom: 0.3rem;
    display: block;
    font-family: var(--font-heading);
  }

  .dropdown-item-desc {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.4);
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* =========================================
     GIVE BUTTON WITH PULSING GLOW
     ========================================= */

  @keyframes pulseGlow {
    0%, 100% {
      box-shadow: 
        0 0 8px rgba(168, 85, 247, 0.4),
        0 0 16px rgba(168, 85, 247, 0.2),
        0 2px 12px rgba(212, 175, 55, 0.15);
    }
    50% {
      box-shadow: 
        0 0 16px rgba(168, 85, 247, 0.7),
        0 0 32px rgba(168, 85, 247, 0.4),
        0 2px 12px rgba(212, 175, 55, 0.25);
    }
  }

  .give-btn-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    padding: 2px;
    background: rgba(15, 12, 26, 0.82);
    border: 1.5px solid rgba(168, 85, 247, 0.5);
    animation: pulseGlow 2.5s ease-in-out infinite;
    flex-shrink: 0;
    transition: all 0.3s ease;
  }

  .give-btn-wrapper:hover {
    animation: pulseGlow 1.5s ease-in-out infinite;
    border-color: rgba(168, 85, 247, 0.8);
    box-shadow: 
      0 0 20px rgba(168, 85, 247, 0.8),
      0 0 40px rgba(168, 85, 247, 0.5),
      0 2px 12px rgba(212, 175, 55, 0.3);
  }

  .give-btn {
    display: inline-block;
    background: linear-gradient(135deg, rgba(168, 85, 247, 0.15), rgba(168, 85, 247, 0.05));
    color: rgba(255, 255, 255, 0.95) !important;
    padding: 0.5rem 1.4rem;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.85rem;
    font-family: var(--font-heading);
    letter-spacing: 0.04em;
    text-decoration: none;
    transition: all 0.3s ease;
    white-space: nowrap;
    position: relative;
    z-index: 1;
    border: none;
    cursor: pointer;
  }

  .give-btn:hover {
    transform: scale(1.05);
    color: #fff !important;
    background: linear-gradient(135deg, rgba(168, 85, 247, 0.25), rgba(168, 85, 247, 0.12));
  }

  /* =========================================
     RIGHT SECTION
     ========================================= */

  .pill-right {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    flex-shrink: 0;
  }

  /* =========================================
     USER MENU TRIGGER
     ========================================= */

  .user-menu-trigger {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.3rem 0.6rem 0.3rem 0.3rem;
    border-radius: 9999px;
    font-family: var(--font-body);
    transition: background var(--transition-fast);
  }

  .user-menu-trigger:hover {
    background: rgba(255, 255, 255, 0.08);
  }

  .user-trigger-name {
    display: flex;
    align-items: center;
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.8rem;
    font-weight: 500;
    gap: 0.35rem;
  }

  .user-chevron {
    font-size: 0.65rem;
    color: rgba(255, 255, 255, 0.4);
    transition: transform 0.3s ease;
  }

  .user-menu-trigger:hover .user-chevron {
    transform: rotate(-180deg);
  }

  /* =========================================
     AVATARS
     ========================================= */

  .user-avatar-sm {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 1.5px solid rgba(255, 255, 255, 0.2);
  }

  .user-avatar-lg {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary-gold);
    flex-shrink: 0;
  }

  .user-avatar-placeholder-sm {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
    flex-shrink: 0;
    border: 1.5px solid rgba(255, 255, 255, 0.15);
  }

  .user-avatar-placeholder-md {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
  }

  .user-avatar-placeholder-lg {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
    border: 2px solid var(--primary-gold);
    flex-shrink: 0;
  }

  /* =========================================
     USER DROPDOWN (PREMIUM)
     ========================================= */

  .user-dropdown {
    right: 0 !important;
    left: auto !important;
    transform: translateX(0) translateY(-10px) !important;
    min-width: 270px !important;
    border-radius: 16px !important;
  }

  .pill-dropdown:hover .user-dropdown,
  .user-dropdown.show {
    transform: translateX(0) translateY(0) !important;
  }

  .user-dropdown-header {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.9rem 1rem 0.8rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }

  .user-dropdown-name {
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.88rem;
    font-family: var(--font-heading);
    margin-bottom: 0.15rem;
  }

  .user-dropdown-email {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.4);
  }

  .user-dropdown-links { 
    padding: 0.35rem 0; 
  }

  .user-dropdown-link {
    display: flex !important;
    align-items: center;
    gap: 0.65rem;
    padding: 0.6rem 1rem !important;
    color: rgba(255, 255, 255, 0.6) !important;
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 400;
    font-family: var(--font-body);
    transition: all var(--transition-fast);
  }

  .user-dropdown-link:hover {
    background: rgba(255, 255, 255, 0.06) !important;
    color: rgba(255, 255, 255, 0.95) !important;
    padding-left: 1rem !important;
  }

  .user-dropdown-link i {
    width: 16px;
    text-align: center;
    color: rgba(255, 255, 255, 0.35);
    flex-shrink: 0;
    font-size: 0.82rem;
  }

  .user-dropdown-link:hover i { 
    color: rgba(255, 255, 255, 0.7); 
  }

  .user-dropdown-logout { 
    color: rgba(235, 87, 87, 0.8) !important; 
  }
  .user-dropdown-logout i { 
    color: rgba(235, 87, 87, 0.7) !important; 
  }
  .user-dropdown-logout:hover { 
    color: #EB5757 !important; 
  }

  .user-dropdown-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.07);
    margin: 0.3rem 0;
  }

  /* =========================================
     HAMBURGER (MOBILE) - PROPER STYLING
     ========================================= */

  .hamburger {
    display: flex;
    flex-direction: column;
    gap: 5px;
    cursor: pointer;
    padding: 0.45rem;
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 9999px;
    width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
  }

  .hamburger:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.18);
  }

  .hamburger span {
    width: 18px;
    height: 2px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 2px;
    transition: all var(--transition-base);
    display: block;
  }

  .hamburger.active span:nth-child(1) { 
    transform: rotate(45deg) translate(5px, 5px); 
  }
  .hamburger.active span:nth-child(2) { 
    opacity: 0; 
  }
  .hamburger.active span:nth-child(3) { 
    transform: rotate(-45deg) translate(5px, -5px); 
  }

  /* =========================================
     MOBILE OVERLAY (iOS STYLE ANIMATION)
     ========================================= */

  .mobile-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(0, 0, 0, 0.6);
    opacity: 0;
    visibility: hidden;
    transition: opacity 400ms cubic-bezier(0.4, 0, 0.2, 1), 
                visibility 400ms cubic-bezier(0.4, 0, 0.2, 1),
                backdrop-filter 400ms cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 998;
    backdrop-filter: blur(0px);
    -webkit-backdrop-filter: blur(0px);
  }

  .mobile-overlay.active {
    opacity: 1;
    visibility: visible;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
  }

  /* =========================================
     MOBILE MENU PANEL (iOS SPRING ANIMATION)
     ========================================= */

  .mobile-menu {
    position: fixed;
    top: 0;
    right: 0;
    width: 100%;
    height: 100vh;
    background: rgba(10, 8, 20, 0.98);
    backdrop-filter: blur(30px);
    -webkit-backdrop-filter: blur(30px);
    border-left: 1px solid rgba(255, 255, 255, 0.08);
    padding: 2rem 1.5rem;
    overflow-y: auto;
    z-index: 999;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    opacity: 0;
    visibility: hidden;
    transition: transform 400ms cubic-bezier(0.34, 1.56, 0.64, 1),
                opacity 400ms cubic-bezier(0.4, 0, 0.2, 1),
                visibility 400ms cubic-bezier(0.4, 0, 0.2, 1);
  }

  .mobile-menu.active {
    transform: translateX(0);
    opacity: 1;
    visibility: visible;
  }

  /* =========================================
     MOBILE MENU HEADER
     ========================================= */

  .mobile-menu-header {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }

  .mobile-menu-logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 1.2rem;
    color: #ffffff;
  }

  .mobile-menu-logo img {
    height: 60px;
    width: auto;
    object-fit: contain;
  }

  .mobile-close-btn {
    position: absolute;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.25rem;
    cursor: pointer;
    transition: all var(--transition-fast);
  }

  .mobile-close-btn:hover {
    background: rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.95);
  }

  /* =========================================
     MOBILE MENU LIST
     ========================================= */

  .mobile-menu-list {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1;
  }

  .mobile-menu-list li {
    margin-bottom: 0.1rem;
    list-style: none;
  }

  /* ✅ Mobile: TEXT ONLY - no images, no icons */
  .mobile-menu-list a {
    display: block;
    padding: 0.75rem 0.8rem;
    font-size: 0.95rem;
    color: rgba(255, 255, 255, 0.65);
    font-weight: 400;
    font-family: var(--font-body);
    text-decoration: none;
    border-radius: 0.5rem;
    transition: all var(--transition-fast);
  }

  .mobile-menu-list a:hover {
    color: rgba(255, 255, 255, 0.95);
    background: rgba(255, 255, 255, 0.06);
  }

  /* Mobile dropdown */
  .mobile-dropdown-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    width: 100%;
    padding: 0.75rem 0.8rem;
    font-size: 0.95rem;
    font-weight: 400;
    font-family: var(--font-body);
    color: rgba(255, 255, 255, 0.65);
    background: none;
    border: none;
    border-radius: 0.5rem;
    text-align: left;
    transition: all var(--transition-fast);
  }

  .mobile-dropdown-toggle:hover {
    color: rgba(255, 255, 255, 0.95);
    background: rgba(255, 255, 255, 0.06);
  }

  .mobile-dropdown-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 300ms cubic-bezier(0.4, 0, 0.2, 1);
    padding-left: 0.75rem;
  }

  .mobile-dropdown-content.active { 
    max-height: 500px; 
  }

  /* Mobile Give button */
  .mobile-menu .give-btn-wrapper {
    display: block;
    border-radius: 9999px;
    margin: 1.5rem 0;
  }

  .mobile-menu .give-btn {
    display: block;
    text-align: center;
    width: 100%;
  }

  /* =========================================
     RESPONSIVE
     ========================================= */

  @media (min-width: 768px) {
    .pill-nav-links { 
      display: flex; 
    }
    .hamburger { 
      display: none; 
    }
    .mobile-menu, .mobile-overlay { 
      display: none !important; 
    }
    .give-btn-wrapper {
      display: inline-flex !important;
    }
  }

  /* Mobile Hide Give Button from top */
  @media (max-width: 767px) {
    .pill-navbar-inner > .give-btn-wrapper {
      display: none;
    }

    .pill-navbar {
      width: 96%;
      max-width: 100%;
    }

    .pill-navbar-inner {
      padding: 0.55rem 1rem;
    }

    .pill-logo {
      flex: 1;
      justify-content: center;
    }

    .pill-right {
      display: none;
    }

    .hamburger {
      display: flex;
    }
  }

  /* =========================================
     ACCESSIBILITY
     ========================================= */

  .pill-nav-links a:focus-visible,
  .pill-dropdown-menu a:focus-visible,
  .give-btn:focus-visible,
  .pill-login-link:focus-visible,
  .hamburger:focus-visible,
  .mobile-menu-list a:focus-visible,
  .mobile-dropdown-toggle:focus-visible,
  .mobile-close-btn:focus-visible {
    outline: 2px solid rgba(212, 175, 55, 0.7);
    outline-offset: 2px;
  }

  /* =========================================
     REDUCED MOTION
     ========================================= */

  @media (prefers-reduced-motion: reduce) {
    .pill-navbar-inner, 
    .pill-dropdown-menu, 
    .mobile-menu, 
    .mobile-overlay,
    .hamburger span, 
    .give-btn, 
    .give-btn-wrapper, 
    .mobile-dropdown-content,
    .user-chevron,
    .dropdown-item-image {
      transition: none !important;
      animation: none !important;
    }
    .give-btn-wrapper {
      border: 1.5px solid rgba(168, 85, 247, 0.5);
      box-shadow: 0 0 16px rgba(168, 85, 247, 0.3);
    }
  }
</style>

<nav class="pill-navbar">
  <div class="pill-navbar-inner">

    <!-- Logo -->
    <a href="<?= SITE_URL ?>" class="pill-logo">
      <img src="<?= ASSETS_PATH ?>images/logo/logo.png" alt="Scribes Global">
    </a>

    <!-- Desktop Center Links -->
    <ul class="pill-nav-links">

      <!-- About Us Dropdown -->
      <li class="pill-dropdown" onmouseenter="showDropdown(this)" onmouseleave="hideDropdown(this)">
        <button class="pill-nav-btn" onclick="return false;">About Us <span style="font-size:0.6rem; opacity:0.6;">▾</span></button>
        <div class="pill-dropdown-menu">
          <a href="<?= SITE_URL ?>/pages/about/scribes-global" class="pill-dropdown-item">
            <div class="dropdown-item-image">
              <img src="<?= ASSETS_PATH ?>images/about.jpeg" alt="Scribes Global" onerror="this.parentElement.textContent='🌍'">
            </div>
            <div class="dropdown-item-content">
              <span class="dropdown-item-title">Scribes Global</span>
              <span class="dropdown-item-desc">Our mission & vision</span>
            </div>
          </a>
          <a href="<?= SITE_URL ?>/pages/about/ministries" class="pill-dropdown-item">
            <div class="dropdown-item-image">
              <img src="<?= ASSETS_PATH ?>images/about.jpeg" alt="Ministries" onerror="this.parentElement.textContent='🎨'">
            </div>
            <div class="dropdown-item-content">
              <span class="dropdown-item-title">Ministries</span>
              <span class="dropdown-item-desc">Creative expressions</span>
            </div>
          </a>
          <a href="<?= SITE_URL ?>/pages/about/chapters" class="pill-dropdown-item">
            <div class="dropdown-item-image">
              <img src="<?= ASSETS_PATH ?>images/about.jpeg" alt="Chapters" onerror="this.parentElement.textContent='🏢'">
            </div>
            <div class="dropdown-item-content">
              <span class="dropdown-item-title">Chapters</span>
              <span class="dropdown-item-desc">Local communities</span>
            </div>
          </a>
        </div>
      </li>

      <!-- Projects Dropdown -->
      <li class="pill-dropdown" onmouseenter="showDropdown(this)" onmouseleave="hideDropdown(this)">
        <button class="pill-nav-btn" onclick="return false;">Projects <span style="font-size:0.6rem; opacity:0.6;">▾</span></button>
        <div class="pill-dropdown-menu">
          <a href="<?= SITE_URL ?>/pages/projects/heal" class="pill-dropdown-item">
            <div class="dropdown-item-image">
              <img src="<?= ASSETS_PATH ?>images/heal.jpg" alt="H.E.A.L" onerror="this.parentElement.textContent='❤️'">
            </div>
            <div class="dropdown-item-content">
              <span class="dropdown-item-title">H.E.A.L</span>
              <span class="dropdown-item-desc">Help, Educate & Love</span>
            </div>
          </a>
          <a href="<?= SITE_URL ?>/pages/projects/move" class="pill-dropdown-item">
            <div class="dropdown-item-image">
              <img src="<?= ASSETS_PATH ?>images/heal.jpg" alt="M.O.V.E" onerror="this.parentElement.textContent='🎭'">
            </div>
            <div class="dropdown-item-content">
              <span class="dropdown-item-title">M.O.V.E</span>
              <span class="dropdown-item-desc">Movement & outreach</span>
            </div>
          </a>
        </div>
      </li>

      <!-- Events -->
      <li><a href="<?= SITE_URL ?>/pages/events">Events</a></li>

      <!-- Media -->
      <li><a href="<?= SITE_URL ?>/pages/media">Media</a></li>

      <!-- Connect Dropdown -->
      <li class="pill-dropdown" onmouseenter="showDropdown(this)" onmouseleave="hideDropdown(this)">
        <button class="pill-nav-btn" onclick="return false;">Connect <span style="font-size:0.6rem; opacity:0.6;">▾</span></button>
        <div class="pill-dropdown-menu">
          <a href="<?= SITE_URL ?>/pages/connect/invite" class="pill-dropdown-item">
            <div class="dropdown-item-image">
              <img src="<?= ASSETS_PATH ?>images/about.jpeg" alt="Invite Us" onerror="this.parentElement.textContent='📢'">
            </div>
            <div class="dropdown-item-content">
              <span class="dropdown-item-title">Invite Us</span>
              <span class="dropdown-item-desc">Bring us to your event</span>
            </div>
          </a>
          <a href="<?= SITE_URL ?>/pages/connect/volunteer" class="pill-dropdown-item">
            <div class="dropdown-item-image">
              <img src="<?= ASSETS_PATH ?>images/about.jpeg" alt="Join / Volunteer" onerror="this.parentElement.textContent='🤝'">
            </div>
            <div class="dropdown-item-content">
              <span class="dropdown-item-title">Join / Volunteer</span>
              <span class="dropdown-item-desc">Be part of the team</span>
            </div>
          </a>
        </div>
      </li>

    </ul>

    <!-- Right Section - Give Button (Desktop Only) -->
    <div class="pill-right">
      <div class="give-btn-wrapper">
        <a href="<?= SITE_URL ?>/pages/give" class="give-btn">Give</a>
      </div>
    </div>

    <!-- Mobile Hamburger -->
    <button class="hamburger" id="mobileToggle" onclick="toggleMobileMenu()">
      <span></span>
      <span></span>
      <span></span>
    </button>

  </div>
</nav>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileMenu()"></div>

<!-- Mobile Menu (TEXT ONLY with iOS ANIMATION) -->
<div class="mobile-menu" id="mobileMenu">
  
  <!-- Mobile Menu Header - Logo Centered -->
  <div class="mobile-menu-header">
    <a href="<?= SITE_URL ?>" class="mobile-menu-logo" onclick="closeMobileMenu()">
      <img src="<?= ASSETS_PATH ?>images/logo/logo.png" alt="Scribes Global">
    </a>
    <button class="mobile-close-btn" onclick="closeMobileMenu()">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <!-- Mobile Menu Content (TEXT ONLY - NO IMAGES) -->
  <ul class="mobile-menu-list">

    <!-- About Us -->
    <li>
      <div class="mobile-dropdown-toggle" onclick="toggleMobileDropdown(this)">
        <span>About Us</span>
        <span>+</span>
      </div>
      <div class="mobile-dropdown-content">
        <a href="<?= SITE_URL ?>/pages/about/scribes-global" onclick="closeMobileMenu()">Scribes Global</a>
        <a href="<?= SITE_URL ?>/pages/about/ministries" onclick="closeMobileMenu()">Ministries</a>
        <a href="<?= SITE_URL ?>/pages/about/chapters" onclick="closeMobileMenu()">Chapters</a>
      </div>
    </li>

    <!-- Projects -->
    <li>
      <div class="mobile-dropdown-toggle" onclick="toggleMobileDropdown(this)">
        <span>Projects</span>
        <span>+</span>
      </div>
      <div class="mobile-dropdown-content">
        <a href="<?= SITE_URL ?>/pages/projects/heal" onclick="closeMobileMenu()">H.E.A.L</a>
        <a href="<?= SITE_URL ?>/pages/projects/move" onclick="closeMobileMenu()">M.O.V.E</a>
      </div>
    </li>

    <li><a href="<?= SITE_URL ?>/pages/events" onclick="closeMobileMenu()">Events</a></li>
    <li><a href="<?= SITE_URL ?>/pages/media" onclick="closeMobileMenu()">Media</a></li>

    <!-- Connect -->
    <li>
      <div class="mobile-dropdown-toggle" onclick="toggleMobileDropdown(this)">
        <span>Connect</span>
        <span>+</span>
      </div>
      <div class="mobile-dropdown-content">
        <a href="<?= SITE_URL ?>/pages/connect/invite" onclick="closeMobileMenu()">Invite Us</a>
        <a href="<?= SITE_URL ?>/pages/connect/volunteer" onclick="closeMobileMenu()">Join / Volunteer</a>
      </div>
    </li>

    <!-- Give Button in Mobile Menu -->
    <li>
      <div class="give-btn-wrapper" style="display:block; margin: 1.5rem 0;">
        <a href="<?= SITE_URL ?>/pages/give" class="give-btn" style="display:block; text-align:center;" onclick="closeMobileMenu()">Give</a>
      </div>
    </li>

  </ul>
</div>

<!-- ✅ VANILLA JAVASCRIPT (NO FRAMEWORKS NEEDED) -->
<script>
  // Mobile Menu Toggle
  function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileOverlay');
    const toggle = document.getElementById('mobileToggle');
    
    const isOpen = menu.classList.contains('active');
    
    if (isOpen) {
      closeMobileMenu();
    } else {
      menu.classList.add('active');
      overlay.classList.add('active');
      toggle.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }

  // Close Mobile Menu
  function closeMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileOverlay');
    const toggle = document.getElementById('mobileToggle');
    
    menu.classList.remove('active');
    overlay.classList.remove('active');
    toggle.classList.remove('active');
    document.body.style.overflow = 'auto';
  }

  // Toggle Mobile Dropdown
  function toggleMobileDropdown(element) {
    const content = element.nextElementSibling;
    const toggle = element.querySelector('span:last-child');
    const isOpen = content.classList.contains('active');
    
    if (isOpen) {
      content.classList.remove('active');
      toggle.textContent = '+';
    } else {
      content.classList.add('active');
      toggle.textContent = '−';
    }
  }

  // Desktop Dropdown
  function showDropdown(element) {
    const menu = element.querySelector('.pill-dropdown-menu');
    if (menu) {
      menu.classList.add('show');
    }
  }

  function hideDropdown(element) {
    const menu = element.querySelector('.pill-dropdown-menu');
    if (menu) {
      menu.classList.remove('show');
    }
  }

  // Close menu on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeMobileMenu();
    }
  });

  // Close menu when clicking outside
  document.addEventListener('click', function(e) {
    const menu = document.getElementById('mobileMenu');
    const toggle = document.getElementById('mobileToggle');
    
    if (menu.classList.contains('active') && 
        !menu.contains(e.target) && 
        !toggle.contains(e.target)) {
      closeMobileMenu();
    }
  });
</script>