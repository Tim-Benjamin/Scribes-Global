<?php
/**
 * Universal Splash Screen Controller
 * This file should be included at the start of <body> in ALL pages
 * Works for nested pages and pages with custom HTML structure
 */

require_once __DIR__ . '/../config/config.php';

// Determine if splash should be shown
$showSplash = false;

// Check if splash is enabled globally
if (defined('SHOW_SPLASH_SCREEN') && SHOW_SPLASH_SCREEN) {
    // Check if page explicitly disabled splash
    if (!isset($noSplash) || !$noSplash) {
        $showSplash = true;
    }
}

// Only include splash if needed
if ($showSplash) {
    require_once __DIR__ . '/splash.php';
}
?>