<?php
$pageTitle = 'Events Management - Admin - Scribes Global';
$pageDescription = 'Manage events';
$pageCSS = 'admin';
$noSplash = true;
$noNav = true;        // Don't show navigation
$noFooter = true;     // Don't show footer content

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

if (!isAdmin()) {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: ' . SITE_URL . '/pages/dashboard');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get filters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$chapter = $_GET['chapter'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT e.*, c.name as chapter_name, 
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
          FROM events e
          LEFT JOIN chapters c ON e.chapter_id = c.id
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($type)) {
    $query .= " AND e.event_type = ?";
    $params[] = $type;
}

if (!empty($status)) {
    $query .= " AND e.status = ?";
    $params[] = $status;
}

if (!empty($chapter)) {
    $query .= " AND e.chapter_id = ?";
    $params[] = $chapter;
}

// Add ORDER BY and LIMIT directly
$query .= " ORDER BY e.start_date DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM events e WHERE 1=1";
$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (e.title LIKE ? OR e.description LIKE ?)";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
}
if (!empty($type)) {
    $countQuery .= " AND e.event_type = ?";
    $countParams[] = $type;
}
if (!empty($status)) {
    $countQuery .= " AND e.status = ?";
    $countParams[] = $status;
}
if (!empty($chapter)) {
    $countQuery .= " AND e.chapter_id = ?";
    $countParams[] = $chapter;
}

$countStmt = $conn->prepare($countQuery);
$countStmt->execute($countParams);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Get all chapters for filter
$chaptersStmt = $conn->query("SELECT * FROM chapters WHERE status = 'active' ORDER BY name ASC");
$chapters = $chaptersStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════
   ADMIN EVENTS PAGE
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
  --radius-full: 9999px;
  --radius-2xl: 24px;
  --radius-xl: 16px;
  --radius-lg: 12px;
  --radius-md: 8px;
  --transition-base: 300ms ease-in-out;
}

body {
  margin: 0;
  padding: 0;
  background: var(--gray-50);
  font-family: var(--font-body);
}

.admin-layout {
  display: flex;
  background: var(--gray-50);
  min-height: 100vh;
}

.admin-main {
  flex: 1;
  margin-left: 260px;
  padding: 2rem;
  overflow-y: auto;
  transition: margin-left var(--transition);
}

.admin-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.admin-page-title {
  margin: 0;
  font-size: clamp(1.75rem, 4vw, 2.25rem);
  font-family: var(--font-heading);
  font-weight: 700;
  color: var(--dark-bg);
  letter-spacing: -0.5px;
}

.admin-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.mobile-admin-toggle {
  display: none;
  background: var(--white);
  border: 1px solid var(--gray-200);
  width: 40px;
  height: 40px;
  border-radius: 8px;
  cursor: pointer;
  color: var(--dark-bg);
  font-size: 1.25rem;
  transition: all var(--transition);
}

.mobile-admin-toggle:hover {
  background: var(--gray-100);
  border-color: var(--gray-300);
}

/* ─── Stats Grid ────────────────────────────────────────────– */
.admin-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.admin-stat-card {
  background: white;
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  border: 1px solid var(--gray-200);
  transition: all var(--transition);
  position: relative;
  overflow: hidden;
}

.admin-stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, currentColor, transparent);
  opacity: 0;
  transition: opacity var(--transition);
}

.admin-stat-card:hover {
  border-color: currentColor;
  box-shadow: 0 8px 24px rgba(107, 70, 193, 0.12);
  transform: translateY(-4px);
}

.admin-stat-card:hover::before {
  opacity: 1;
}

.admin-stat-card.purple { color: var(--primary-purple); }
.admin-stat-card.gold { color: var(--primary-gold); }
.admin-stat-card.teal { color: #2D9CDB; }
.admin-stat-card.coral { color: var(--primary-coral); }

.admin-stat-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.admin-stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  background: rgba(107, 70, 193, 0.1);
  color: var(--primary-purple);
}

.admin-stat-card.gold .admin-stat-icon {
  background: rgba(212, 175, 55, 0.1);
  color: var(--primary-gold);
}

.admin-stat-card.teal .admin-stat-icon {
  background: rgba(45, 156, 219, 0.1);
  color: #2D9CDB;
}

.admin-stat-card.coral .admin-stat-icon {
  background: rgba(235, 87, 87, 0.1);
  color: var(--primary-coral);
}

.admin-stat-value {
  font-size: 2rem;
  font-weight: 800;
  font-family: var(--font-heading);
  color: var(--dark-bg);
  line-height: 1;
  margin-bottom: 0.5rem;
}

.admin-stat-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--gray-600);
}

/* ─── Filters Bar ────────────────────────────────────────────– */
.filters-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--gray-200);
}

.search-input,
.filter-select {
  padding: 0.75rem 1rem;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  font-size: 0.9rem;
  font-family: var(--font-body);
  transition: all var(--transition);
  background: white;
  color: var(--dark-bg);
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: var(--primary-purple);
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

/* ─── Card Styles ────────────────────────────────────────────– */
.admin-card {
  background: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--gray-200);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all var(--transition);
}

.admin-card:hover {
  border-color: var(--gray-300);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.admin-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-100);
}

.admin-card-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  font-family: var(--font-heading);
  color: var(--dark-bg);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.admin-card-body {
  padding: 1.5rem;
}

/* ─── Event Grid ────────────────────────────────────────────– */
.event-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.event-admin-card {
  background: white;
  border-radius: var(--radius-xl);
  overflow: hidden;
  border: 1px solid var(--gray-200);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all var(--transition);
  cursor: pointer;
  position: relative;
}

.event-admin-card:hover {
  transform: translateY(-5px);
  border-color: var(--primary-purple);
  box-shadow: 0 12px 24px rgba(107, 70, 193, 0.15);
}

.event-admin-image {
  width: 100%;
  height: 180px;
  object-fit: cover;
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2.5rem;
}

.event-admin-body {
  padding: 1.5rem;
}

.event-admin-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  gap: 1rem;
}

.event-admin-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}

.status-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

.status-badge.upcoming {
  background: rgba(45, 156, 219, 0.15);
  color: #2D9CDB;
}

.status-badge.ongoing {
  background: rgba(81, 207, 102, 0.15);
  color: #51CF66;
}

.status-badge.completed {
  background: rgba(107, 70, 193, 0.15);
  color: var(--primary-purple);
}

.status-badge.cancelled {
  background: rgba(235, 87, 87, 0.15);
  color: var(--primary-coral);
}

.featured-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: linear-gradient(135deg, #D4AF37 0%, #F2D97A 100%);
  color: #1A1A2E;
  padding: 0.5rem 1rem;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
  box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  z-index: 1;
}

.event-admin-meta {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.event-admin-meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--gray-600);
}

.event-admin-meta-item i {
  width: 16px;
  color: var(--primary-purple);
  flex-shrink: 0;
}

.event-admin-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid var(--gray-200);
  margin-bottom: 1rem;
}

.event-admin-stat {
  text-align: center;
}

.event-admin-stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-purple);
  display: block;
}

.event-admin-stat-label {
  font-size: 0.7rem;
  color: var(--gray-600);
  text-transform: uppercase;
  font-weight: 600;
}

.event-admin-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  border: 1px solid var(--gray-200);
  background: white;
  color: var(--gray-700);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  transition: all var(--transition);
}

.btn-icon:hover {
  background: var(--gray-100);
  border-color: var(--primary-purple);
  color: var(--primary-purple);
}

.btn-icon.btn-delete:hover {
  background: rgba(235, 87, 87, 0.1);
  border-color: var(--primary-coral);
  color: var(--primary-coral);
}

/* ─── Table Styles ──────────────────────────────────────────– */
.table-wrapper {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-family: var(--font-body);
}

.data-table thead {
  background: var(--gray-50);
  border-bottom: 2px solid var(--gray-200);
}

.data-table th {
  padding: 1rem;
  text-align: left;
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--gray-700);
}

.data-table td {
  padding: 1rem;
  border-bottom: 1px solid var(--gray-100);
  font-size: 0.95rem;
  color: var(--gray-800);
}

.data-table tbody tr:hover {
  background: var(--gray-50);
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  object-fit: cover;
  border: 1px solid var(--gray-200);
  flex-shrink: 0;
}

.user-info h4 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--dark-bg);
}

.user-info p {
  margin: 0.25rem 0 0;
  font-size: 0.8rem;
  color: var(--gray-600);
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

/* ─── Pagination ────────────���───────────────────────────────– */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--gray-200);
}

.pagination button,
.pagination span {
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  border: 1px solid var(--gray-200);
  background: white;
  color: var(--gray-700);
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all var(--transition);
}

.pagination button:hover {
  background: var(--primary-purple);
  color: white;
  border-color: var(--primary-purple);
}

.pagination button.active {
  background: var(--primary-purple);
  color: white;
  border-color: var(--primary-purple);
}

.pagination span {
  cursor: default;
  border: none;
  background: transparent;
}

/* ─── Empty State ────────────────────────────────────────────– */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
}

.empty-state-icon {
  font-size: 3rem;
  color: var(--gray-400);
  margin-bottom: 1rem;
}

.empty-state-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--gray-700);
  margin: 0 0 0.5rem 0;
  font-family: var(--font-heading);
}

.empty-state-text {
  font-size: 1rem;
  color: var(--gray-600);
  margin: 0 0 1.5rem 0;
}

/* ─── Modal Styles ──────────────────────────────────────────– */
.admin-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  padding: 2rem;
  overflow-y: auto;
  backdrop-filter: blur(4px);
}

.admin-modal.active {
  display: flex;
  align-items: center;
  justify-content: center;
}

.admin-modal-content {
  background: white;
  border-radius: var(--radius-xl);
  max-width: 800px;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.admin-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.admin-modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  font-family: var(--font-heading);
  color: var(--dark-bg);
}

.admin-modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--gray-600);
  transition: all var(--transition);
}

.admin-modal-close:hover {
  color: var(--primary-purple);
}

.admin-modal-body {
  padding: 1.5rem;
  max-height: calc(80vh - 70px);
  overflow-y: auto;
}

/* ─── Button Styles ─────────────────────────────────────────– */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  border: none;
  cursor: pointer;
  transition: all var(--transition);
  font-family: var(--font-body);
  text-decoration: none;
}

.btn-primary {
  background: var(--primary-purple);
  color: white;
  box-shadow: 0 4px 12px rgba(107, 70, 193, 0.3);
}

.btn-primary:hover {
  background: #5a3aa5;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(107, 70, 193, 0.4);
}

.btn-secondary {
  background: var(--gray-100);
  color: var(--dark-bg);
  border: 1px solid var(--gray-200);
}

.btn-secondary:hover {
  background: var(--gray-200);
}

.btn-outline {
  background: white;
  color: var(--dark-bg);
  border: 1px solid var(--gray-200);
}

.btn-outline:hover {
  background: var(--gray-100);
  border-color: var(--primary-purple);
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

/* ─── Responsive Design ────────────────────────────────────– */
@media (max-width: 768px) {
  .admin-main {
    margin-left: 0;
    padding: 1.25rem;
  }

  .mobile-admin-toggle {
    display: flex;
  }

  .admin-stats-grid {
    grid-template-columns: 1fr;
  }

  .event-card-grid {
    grid-template-columns: 1fr;
  }

  .filters-bar {
    grid-template-columns: 1fr;
  }

  .admin-page-title {
    font-size: 1.5rem;
  }

  .admin-card-header {
    flex-direction: column;
    gap: 1rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
    font-size: 0.85rem;
  }
}

@media (max-width: 480px) {
  .admin-main {
    padding: 1rem;
  }

  .event-admin-stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .event-admin-actions {
    flex-wrap: wrap;
  }

  .btn-icon {
    width: 32px;
    height: 32px;
    font-size: 0.85rem;
  }
}
</style>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Events Management</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem; font-family: var(--font-body);">Manage all events, registrations and schedules</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <a href="<?= SITE_URL ?>/admin/events/create" class="btn btn-primary">
          <i class="fas fa-plus"></i> Create Event
        </a>
      </div>
    </div>
    
    <!-- Stats Overview -->
    <div class="admin-stats-grid">
      <?php
      $statsQuery = "
        SELECT 
          (SELECT COUNT(*) FROM events WHERE status = 'upcoming') as upcoming,
          (SELECT COUNT(*) FROM events WHERE status = 'ongoing') as ongoing,
          (SELECT COUNT(*) FROM events WHERE status = 'completed') as completed,
          (SELECT COUNT(*) FROM event_registrations WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as month_registrations
      ";
      $statsStmt = $conn->query($statsQuery);
      $eventStats = $statsStmt->fetch();
      ?>
      
      <div class="admin-stat-card purple" data-aos="fade-up">
        <div class="admin-stat-header">
          <div class="admin-stat-icon purple">
            <i class="fas fa-calendar-alt"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= $eventStats['upcoming'] ?></div>
        <div class="admin-stat-label">Upcoming Events</div>
      </div>
      
      <div class="admin-stat-card gold" data-aos="fade-up" data-aos-delay="100">
        <div class="admin-stat-header">
          <div class="admin-stat-icon gold">
            <i class="fas fa-clock"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= $eventStats['ongoing'] ?></div>
        <div class="admin-stat-label">Ongoing Events</div>
      </div>
      
      <div class="admin-stat-card teal" data-aos="fade-up" data-aos-delay="200">
        <div class="admin-stat-header">
          <div class="admin-stat-icon teal">
            <i class="fas fa-check-circle"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= $eventStats['completed'] ?></div>
        <div class="admin-stat-label">Completed Events</div>
      </div>
      
      <div class="admin-stat-card coral" data-aos="fade-up" data-aos-delay="300">
        <div class="admin-stat-header">
          <div class="admin-stat-icon coral">
            <i class="fas fa-user-check"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= $eventStats['month_registrations'] ?></div>
        <div class="admin-stat-label">Registrations (30d)</div>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
      <input 
        type="text" 
        class="search-input" 
        placeholder="Search events by title or description..." 
        value="<?= htmlspecialchars($search) ?>"
        id="searchInput"
      >
      
      <select class="filter-select" id="typeFilter">
        <option value="">All Types</option>
        <option value="physical" <?= $type === 'physical' ? 'selected' : '' ?>>Physical</option>
        <option value="virtual" <?= $type === 'virtual' ? 'selected' : '' ?>>Virtual</option>
        <option value="hybrid" <?= $type === 'hybrid' ? 'selected' : '' ?>>Hybrid</option>
      </select>
      
      <select class="filter-select" id="statusFilter">
        <option value="">All Statuses</option>
        <option value="upcoming" <?= $status === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
        <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
      
      <select class="filter-select" id="chapterFilter">
        <option value="">All Chapters</option>
        <?php foreach ($chapters as $chap): ?>
          <option value="<?= $chap['id'] ?>" <?= $chapter == $chap['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($chap['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      
      <button class="btn btn-outline" onclick="resetFilters()">
        <i class="fas fa-redo"></i> Reset
      </button>
    </div>
    
    <!-- Events Card -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-calendar"></i>
          All Events (<?= number_format($total) ?>)
        </h3>
        <div style="display: flex; gap: 0.5rem;">
          <button class="btn btn-secondary btn-sm" onclick="toggleView('grid')" id="gridViewBtn">
            <i class="fas fa-th"></i> Grid
          </button>
          <button class="btn btn-outline btn-sm" onclick="toggleView('list')" id="listViewBtn">
            <i class="fas fa-list"></i> List
          </button>
        </div>
      </div>
      <div class="admin-card-body">
        <?php if (count($events) > 0): ?>
          <!-- Grid View -->
          <div class="event-card-grid" id="gridView">
            <?php foreach ($events as $event): ?>
              <div class="event-admin-card" data-aos="fade-up">
                <?php if ($event['featured']): ?>
                  <div class="featured-badge">
                    <i class="fas fa-star"></i> Featured
                  </div>
                <?php endif; ?>
                
                <?php if ($event['hero_image']): ?>
                  <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($event['hero_image']) ?>" alt="Event" class="event-admin-image">
                <?php else: ?>
                  <div class="event-admin-image">
                    <i class="fas fa-calendar-alt"></i>
                  </div>
                <?php endif; ?>
                
                <div class="event-admin-body">
                  <div class="event-admin-header">
                    <h4 class="event-admin-title"><?= htmlspecialchars($event['title']) ?></h4>
                    <span class="status-badge <?= $event['status'] ?>"><?= ucfirst($event['status']) ?></span>
                  </div>
                  
                  <div class="event-admin-meta">
                    <div class="event-admin-meta-item">
                      <i class="fas fa-calendar"></i>
                      <?= date('M d, Y', strtotime($event['start_date'])) ?>
                    </div>
                    <div class="event-admin-meta-item">
                      <i class="fas fa-clock"></i>
                      <?= date('g:i A', strtotime($event['start_date'])) ?>
                    </div>
                    <div class="event-admin-meta-item">
                      <i class="fas fa-map-marker-alt"></i>
                      <?= htmlspecialchars($event['location']) ?>
                    </div>
                    <?php if ($event['chapter_name']): ?>
                      <div class="event-admin-meta-item">
                        <i class="fas fa-users"></i>
                        <?= htmlspecialchars($event['chapter_name']) ?>
                      </div>
                    <?php endif; ?>
                    <div class="event-admin-meta-item">
                      <i class="fas fa-<?= $event['event_type'] === 'physical' ? 'building' : ($event['event_type'] === 'virtual' ? 'video' : 'globe') ?>"></i>
                      <?= ucfirst($event['event_type']) ?>
                    </div>
                  </div>
                  
                  <div class="event-admin-stats">
                    <div class="event-admin-stat">
                      <span class="event-admin-stat-value"><?= $event['registration_count'] ?></span>
                      <span class="event-admin-stat-label">Registered</span>
                    </div>
                    <div class="event-admin-stat">
                      <span class="event-admin-stat-value"><?= $event['registration_limit'] ?? '∞' ?></span>
                      <span class="event-admin-stat-label">Limit</span>
                    </div>
                    <div class="event-admin-stat">
                      <span class="event-admin-stat-value"><?= $event['views'] ?? 0 ?></span>
                      <span class="event-admin-stat-label">Views</span>
                    </div>
                  </div>
                  
                  <div class="event-admin-actions">
                    <a href="<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>" class="btn-icon btn-view" title="View Event" target="_blank">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?= SITE_URL ?>/admin/events/edit?id=<?= $event['id'] ?>" class="btn-icon btn-edit" title="Edit Event">
                      <i class="fas fa-edit"></i>
                    </a>
                    <button class="btn-icon btn-view" onclick="viewRegistrations(<?= $event['id'] ?>)" title="View Registrations" style="background: rgba(212, 175, 55, 0.1); color: #D4AF37;">
                      <i class="fas fa-users"></i>
                    </button>
                    <button class="btn-icon btn-delete" onclick="deleteEvent(<?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['title'])) ?>')" title="Delete Event">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          
          <!-- List View (Hidden by default) -->
          <div class="table-wrapper" id="listView" style="display: none;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>Type</th>
                  <th>Date & Time</th>
                  <th>Location</th>
                  <th>Registrations</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($events as $event): ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <?php if ($event['hero_image']): ?>
                          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($event['hero_image']) ?>" alt="Event" class="user-avatar" style="border-radius: 8px;">
                        <?php else: ?>
                          <div class="user-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: white; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calendar"></i>
                          </div>
                        <?php endif; ?>
                        <div class="user-info">
                          <h4><?= htmlspecialchars($event['title']) ?></h4>
                          <p><?= $event['chapter_name'] ?? 'No Chapter' ?></p>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span style="padding: 0.35rem 0.85rem; background: rgba(45, 156, 219, 0.1); color: #2D9CDB; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">
                        <i class="fas fa-<?= $event['event_type'] === 'physical' ? 'building' : ($event['event_type'] === 'virtual' ? 'video' : 'globe') ?>"></i>
                        <?= ucfirst($event['event_type']) ?>
                      </span>
                    </td>
                    <td>
                      <div style="font-size: 0.875rem;">
                        <div style="font-weight: 600; color: var(--dark-bg); margin-bottom: 0.25rem;">
                          <?= date('M d, Y', strtotime($event['start_date'])) ?>
                        </div>
                        <div style="color: var(--gray-600);">
                          <?= date('g:i A', strtotime($event['start_date'])) ?>
                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($event['location']) ?></td>
                    <td>
                      <div style="font-weight: 700; color: var(--primary-purple);">
                        <?= $event['registration_count'] ?><?= $event['registration_limit'] ? '/' . $event['registration_limit'] : '' ?>
                      </div>
                    </td>
                    <td><span class="status-badge <?= $event['status'] ?>"><?= ucfirst($event['status']) ?></span></td>
                    <td>
                      <div class="action-buttons">
                        <a href="<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>" class="btn-icon btn-view" title="View Event" target="_blank">
                          <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?= SITE_URL ?>/admin/events/edit?id=<?= $event['id'] ?>" class="btn-icon btn-edit" title="Edit Event">
                          <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn-icon btn-delete" onclick="deleteEvent(<?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['title'])) ?>')" title="Delete Event">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div class="pagination">
              <?php if ($page > 1): ?>
                <button onclick="goToPage(<?= $page - 1 ?>)">
                  <i class="fas fa-chevron-left"></i> Previous
                </button>
              <?php endif; ?>
              
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                  <button class="<?= $i == $page ? 'active' : '' ?>" onclick="goToPage(<?= $i ?>)">
                    <?= $i ?>
                  </button>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                  <span>...</span>
                <?php endif; ?>
              <?php endfor; ?>
              
              <?php if ($page < $totalPages): ?>
                <button onclick="goToPage(<?= $page + 1 ?>)">
                  Next <i class="fas fa-chevron-right"></i>
                </button>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-calendar"></i></div>
            <div class="empty-state-title">No Events Found</div>
            <div class="empty-state-text">
              <?php if (!empty($search) || !empty($type) || !empty($status) || !empty($chapter)): ?>
                Try adjusting your filters
              <?php else: ?>
                Create your first event to get started
              <?php endif; ?>
            </div>
            <a href="<?= SITE_URL ?>/admin/events/create" class="btn btn-primary">
              <i class="fas fa-plus"></i> Create Event
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- Registrations Modal -->
<div class="admin-modal" id="registrationsModal">
  <div class="admin-modal-content">
    <div class="admin-modal-header">
      <h2>Event Registrations</h2>
      <button class="admin-modal-close" onclick="closeRegistrationsModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="admin-modal-body" id="registrationsModalBody">
      <!-- Content loaded dynamically -->
    </div>
  </div>
</div>

<script>
function toggleAdminSidebar() {
  const sidebar = document.getElementById('adminSidebar');
  if (sidebar) {
    sidebar.classList.toggle('mobile-visible');
  }
}

// View Toggle
function toggleView(view) {
  const gridView = document.getElementById('gridView');
  const listView = document.getElementById('listView');
  const gridBtn = document.getElementById('gridViewBtn');
  const listBtn = document.getElementById('listViewBtn');
  
  if (view === 'grid') {
    gridView.style.display = 'grid';
    listView.style.display = 'none';
    gridBtn.classList.remove('btn-outline');
    gridBtn.classList.add('btn-secondary');
    listBtn.classList.remove('btn-secondary');
    listBtn.classList.add('btn-outline');
  } else {
    gridView.style.display = 'none';
    listView.style.display = 'block';
    listBtn.classList.remove('btn-outline');
    listBtn.classList.add('btn-secondary');
    gridBtn.classList.remove('btn-secondary');
    gridBtn.classList.add('btn-outline');
  }
}

// Filters
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
});

document.getElementById('typeFilter').addEventListener('change', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);
document.getElementById('chapterFilter').addEventListener('change', applyFilters);

function applyFilters() {
  const search = document.getElementById('searchInput').value;
  const type = document.getElementById('typeFilter').value;
  const status = document.getElementById('statusFilter').value;
  const chapter = document.getElementById('chapterFilter').value;
  
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (type) params.set('type', type);
  if (status) params.set('status', status);
  if (chapter) params.set('chapter', chapter);
  
  window.location.href = '<?= SITE_URL ?>/admin/events' + (params.toString() ? '?' + params.toString() : '');
}

function resetFilters() {
  window.location.href = '<?= SITE_URL ?>/admin/events';
}

function goToPage(page) {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  window.location.href = '<?= SITE_URL ?>/admin/events?' + params.toString();
}

// View Registrations
async function viewRegistrations(eventId) {
  const modal = document.getElementById('registrationsModal');
  const body = document.getElementById('registrationsModalBody');
  
  body.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #6B46C1;"></i></div>';
  modal.classList.add('active');
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=get_event_registrations&event_id=' + eventId);
    const result = await response.json();
    
    if (result.success) {
      body.innerHTML = renderRegistrations(result.registrations, result.event);
    } else {
      body.innerHTML = '<p style="text-align: center; color: var(--gray-600);">Failed to load registrations</p>';
    }
  } catch (error) {
    console.error('Error:', error);
    body.innerHTML = '<p style="text-align: center; color: var(--primary-coral);">An error occurred</p>';
  }
}

function renderRegistrations(registrations, event) {
  if (registrations.length === 0) {
    return `
      <div style="text-align: center; padding: 3rem;">
        <i class="fas fa-user-slash" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
        <h3 style="color: var(--gray-700);">No Registrations Yet</h3>
        <p style="color: var(--gray-600);">This event hasn't received any registrations.</p>
      </div>
    `;
  }
  
  let html = `
    <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--gray-100); border-radius: 12px;">
      <h3 style="margin: 0 0 0.5rem 0;">${event.title}</h3>
      <p style="margin: 0; color: var(--gray-600);">
        <strong>${registrations.length}</strong> ${registrations.length === 1 ? 'registration' : 'registrations'}
        ${event.registration_limit ? ` out of ${event.registration_limit}` : ''}
      </p>
    </div>
    
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
      <button class="btn btn-secondary btn-sm" onclick="exportRegistrations(${event.id})">
        <i class="fas fa-download"></i> Export CSV
      </button>
    </div>
    
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Registered</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
  `;
  
  registrations.forEach(reg => {
    html += `
      <tr>
        <td>
          <div style="font-weight: 600; color: var(--dark-bg);">${reg.name}</div>
        </td>
        <td>${reg.email}</td>
        <td>${reg.phone || '-'}</td>
        <td>${new Date(reg.registered_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td>
        <td>
          <span class="status-badge ${reg.attendance_confirmed ? 'confirmed' : 'pending'}">
            ${reg.attendance_confirmed ? 'Confirmed' : 'Pending'}
          </span>
        </td>
      </tr>
    `;
  });
  
  html += `
        </tbody>
      </table>
    </div>
  `;
  
  return html;
}

function closeRegistrationsModal() {
  document.getElementById('registrationsModal').classList.remove('active');
}

async function deleteEvent(eventId, eventTitle) {
  if (!confirm(`Are you sure you want to delete "${eventTitle}"? This will also delete all registrations. This action cannot be undone.`)) {
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('event_id', eventId);
    
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=delete_event', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Event deleted successfully');
      window.location.reload();
    } else {
      alert(result.message || 'Failed to delete event');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

async function exportRegistrations(eventId) {
  window.location.href = '<?= SITE_URL ?>/api/admin.php?action=export_registrations&event_id=' + eventId;
}

// Initialize AOS
if (typeof AOS !== 'undefined') {
  AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true,
    offset: 100
  });
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('adminSidebar');
  const toggle = document.querySelector('.mobile-admin-toggle');
  
  if (window.innerWidth <= 768 && 
      sidebar &&
      !sidebar.contains(e.target) && 
      toggle &&
      !toggle.contains(e.target) &&
      sidebar.classList.contains('mobile-visible')) {
    sidebar.classList.remove('mobile-visible');
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>