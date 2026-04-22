<?php
$pageTitle = 'Chapters Management - Admin - Scribes Global';
$pageDescription = 'Manage chapters and locations';
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
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? ''; // campus or regular
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT c.*, 
          u.first_name as leader_first_name, 
          u.last_name as leader_last_name,
          (SELECT COUNT(*) FROM users WHERE chapter_id = c.id) as member_count,
          (SELECT COUNT(*) FROM chapter_join_requests WHERE chapter_id = c.id AND status = 'pending') as pending_requests
          FROM chapters c
          LEFT JOIN users u ON c.leader_id = u.id
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.location LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($status)) {
    $query .= " AND c.status = ?";
    $params[] = $status;
}

if ($type === 'campus') {
    $query .= " AND c.is_campus = 1";
} elseif ($type === 'regular') {
    $query .= " AND c.is_campus = 0";
}

$query .= " ORDER BY c.is_campus DESC, c.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$chapters = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM chapters WHERE 1=1";
$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (name LIKE ? OR location LIKE ?)";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
}
if (!empty($status)) {
    $countQuery .= " AND status = ?";
    $countParams[] = $status;
}
if ($type === 'campus') {
    $countQuery .= " AND is_campus = 1";
} elseif ($type === 'regular') {
    $countQuery .= " AND is_campus = 0";
}

$countStmt = $conn->prepare($countQuery);
$countStmt->execute($countParams);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Get stats
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM chapters WHERE status = 'active') as active_chapters,
        (SELECT COUNT(*) FROM chapters WHERE status = 'active' AND is_campus = 1) as campus_chapters,
        (SELECT COUNT(*) FROM chapters WHERE status = 'active' AND is_campus = 0) as regular_chapters,
        (SELECT COUNT(*) FROM users WHERE chapter_id IS NOT NULL) as total_members,
        (SELECT COUNT(*) FROM chapter_join_requests WHERE status = 'pending') as pending_requests
";
$statsStmt = $conn->query($statsQuery);
$stats = $statsStmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
:root {
  --primary-purple: #6B46C1;
  --primary-gold: #D4AF37;
  --secondary-gold-light: #F2D97A;
  --primary-coral: #EB5757;
  --dark-bg: #1A1A2E;
  --white: #FFFFFF;
  --gray-50: #F9FAFB;
  --gray-100: #F3F4F6;
  --gray-200: #E5E7EB;
  --gray-300: #D1D5DB;
  --gray-600: #4B5563;
  --gray-700: #374151;
  --font-heading: 'Fraunces', Georgia, serif;
  --font-body: 'DM Sans', sans-serif;
  --transition: 300ms cubic-bezier(0.4, 0, 0.2, 1);
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
  transition: margin 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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

.admin-page-subtitle {
  color: var(--gray-600);
  margin-top: 0.5rem;
  font-size: 0.95rem;
  font-family: var(--font-body);
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

/* ─── Stats Grid ──────────────────────────────────────────– */
.admin-stats-grid {
  display: grid;
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.admin-stat-card {
  background: white;
  border-radius: 12px;
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
  margin-bottom: 1.25rem;
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
  font-family: var(--font-body);
}

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
}

/* ─── Filters Bar ──────────────────────────────────────────– */
.filters-bar {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  align-items: center;
}

.search-input,
.filter-select {
  padding: 0.75rem 1rem;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  font-family: var(--font-body);
  font-size: 0.95rem;
  transition: all var(--transition);
}

.search-input {
  flex: 1;
  min-width: 250px;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: var(--primary-purple);
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

/* ─── Admin Card ───────────────────────────────────────────– */
.admin-card {
  background: white;
  border-radius: 12px;
  border: 1px solid var(--gray-200);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all var(--transition);
  overflow: hidden;
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

.admin-card-title i {
  color: var(--primary-purple);
}

.admin-card-body {
  padding: 1.5rem;
}

/* ─── Chapter Grid ─────────────────────────────────────────– */
.chapter-card {
  background: white;
  border-radius: 12px;
  border: 1px solid var(--gray-200);
  overflow: hidden;
  transition: all var(--transition);
  position: relative;
}

.chapter-card:hover {
  border-color: var(--primary-purple);
  box-shadow: 0 8px 24px rgba(107, 70, 193, 0.15);
  transform: translateY(-4px);
}

.chapter-card-image {
  width: 100%;
  height: 180px;
  object-fit: cover;
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
}

.chapter-badge {
  position: absolute;
  top: 1rem;
  left: 1rem;
  background: rgba(45, 156, 219, 0.95);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  backdrop-filter: blur(10px);
}

.chapter-requests-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(235, 87, 87, 0.95);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  backdrop-filter: blur(10px);
}

.chapter-content {
  padding: 1.5rem;
}

.chapter-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--dark-bg);
  font-family: var(--font-heading);
  margin-bottom: 1rem;
}

.chapter-meta {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  color: var(--gray-600);
  font-size: 0.95rem;
}

.chapter-meta i {
  width: 20px;
  text-align: center;
  color: var(--primary-purple);
}

.status-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-badge.active {
  background: rgba(81, 207, 102, 0.15);
  color: #51CF66;
}

.status-badge.inactive {
  background: rgba(212, 175, 55, 0.15);
  color: var(--primary-gold);
}

.chapter-actions {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--gray-200);
}

.btn-icon {
  width: 32px;
  height: 32px;
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

/* ─── Pagination ───────────────────────────────────────────– */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.pagination button,
.pagination span {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--gray-200);
  background: white;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--gray-700);
  transition: all var(--transition);
}

.pagination button:hover {
  border-color: var(--primary-purple);
  color: var(--primary-purple);
  background: rgba(107, 70, 193, 0.05);
}

.pagination button.active {
  background: var(--primary-purple);
  color: white;
  border-color: var(--primary-purple);
}

.pagination span {
  border: none;
  background: none;
  cursor: default;
  color: var(--gray-400);
}

/* ─── Empty State ──────────────────────────────────────────– */
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
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--gray-700);
  margin: 0;
}

.empty-state-text {
  color: var(--gray-600);
  margin: 0.5rem 0;
}

/* ─── Modal Styles ─────────────────────────────────────────– */
.admin-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
}

.admin-modal.active {
  display: flex;
}

.admin-modal-content {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  width: 90%;
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
  font-size: 1.5rem;
  font-family: var(--font-heading);
  font-weight: 700;
}

.admin-modal-close {
  width: 36px;
  height: 36px;
  border: none;
  background: var(--gray-100);
  border-radius: 8px;
  cursor: pointer;
  font-size: 1.25rem;
  color: var(--gray-700);
  transition: all var(--transition);
}

.admin-modal-close:hover {
  background: var(--gray-200);
  color: var(--dark-bg);
}

.admin-modal-body {
  padding: 1.5rem;
}

/* ─── Form Styles ──────────────────────────────────────────– */
.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.form-label {
  display: block;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: var(--dark-bg);
  font-size: 0.95rem;
}

.form-control {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  font-family: var(--font-body);
  font-size: 0.95rem;
  transition: all var(--transition);
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-purple);
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

textarea.form-control {
  resize: vertical;
}

/* ─── Buttons ──────────────────────────────────────────────– */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.9rem;
  font-family: var(--font-body);
  cursor: pointer;
  transition: all var(--transition);
  text-decoration: none;
  white-space: nowrap;
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary-purple) 0%, #2D9CDB 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(107, 70, 193, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(107, 70, 193, 0.4);
}

.btn-secondary {
  background: var(--gray-100);
  color: var(--gray-700);
  border: 1px solid var(--gray-200);
}

.btn-secondary:hover {
  background: var(--gray-200);
  border-color: var(--gray-300);
}

.btn-outline {
  background: transparent;
  color: var(--primary-purple);
  border: 2px solid var(--primary-purple);
}

.btn-outline:hover {
  background: rgba(107, 70, 193, 0.05);
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
    grid-template-columns: 1fr 1fr;
  }

  .filters-bar {
    flex-direction: column;
  }

  .search-input,
  .filter-select {
    width: 100%;
  }

  .admin-page-title {
    font-size: 1.5rem;
  }

  .chapter-actions {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .admin-main {
    padding: 1rem;
  }

  .admin-stats-grid {
    grid-template-columns: 1fr;
  }

  .admin-stat-value {
    font-size: 1.75rem;
  }

  .filters-bar {
    flex-direction: column;
  }

  .chapter-actions {
    grid-template-columns: 1fr;
  }

  .admin-modal-content {
    width: 100%;
    max-height: 100vh;
    border-radius: 12px 12px 0 0;
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
        <h1 class="admin-page-title">Chapters Management</h1>
        <p class="admin-page-subtitle">Manage chapters, campuses, and join requests</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <a href="<?= SITE_URL ?>/admin/chapters/requests" class="btn btn-secondary" style="margin-right: 1rem;">
          <i class="fas fa-user-clock"></i> Join Requests
          <?php if ($stats['pending_requests'] > 0): ?>
            <span class="admin-nav-badge"><?= $stats['pending_requests'] ?></span>
          <?php endif; ?>
        </a>
        <button class="btn btn-primary" onclick="openCreateChapterModal()">
          <i class="fas fa-plus"></i> Create Chapter
        </button>
      </div>
    </div>
    
    <!-- Stats -->
    <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
      <div class="admin-stat-card purple" data-aos="fade-up">
        <div class="admin-stat-header">
          <div class="admin-stat-icon purple">
            <i class="fas fa-map-marker-alt"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['active_chapters']) ?></div>
        <div class="admin-stat-label">Total Active</div>
      </div>
      
      <div class="admin-stat-card teal" data-aos="fade-up" data-aos-delay="100">
        <div class="admin-stat-header">
          <div class="admin-stat-icon teal">
            <i class="fas fa-university"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['campus_chapters']) ?></div>
        <div class="admin-stat-label">Campus Chapters</div>
      </div>
      
      <div class="admin-stat-card gold" data-aos="fade-up" data-aos-delay="200">
        <div class="admin-stat-header">
          <div class="admin-stat-icon gold">
            <i class="fas fa-users"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['total_members']) ?></div>
        <div class="admin-stat-label">Total Members</div>
      </div>
      
      <div class="admin-stat-card coral" data-aos="fade-up" data-aos-delay="300">
        <div class="admin-stat-header">
          <div class="admin-stat-icon coral">
            <i class="fas fa-user-clock"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['pending_requests']) ?></div>
        <div class="admin-stat-label">Pending Requests</div>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
      <input 
        type="text" 
        class="search-input" 
        placeholder="Search chapters by name or location..." 
        value="<?= htmlspecialchars($search) ?>"
        id="searchInput"
      >
      
      <select class="filter-select" id="typeFilter">
        <option value="">All Types</option>
        <option value="campus" <?= $type === 'campus' ? 'selected' : '' ?>>Campus Chapters</option>
        <option value="regular" <?= $type === 'regular' ? 'selected' : '' ?>>Regular Chapters</option>
      </select>
      
      <select class="filter-select" id="statusFilter">
        <option value="">All Statuses</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
      
      <button class="btn btn-outline" onclick="resetFilters()">
        <i class="fas fa-redo"></i> Reset
      </button>
    </div>
    
    <!-- Chapters Grid -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-map-marked-alt"></i>
          All Chapters (<?= number_format($total) ?>)
        </h3>
      </div>
      <div class="admin-card-body">
        <?php if (count($chapters) > 0): ?>
          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
            <?php foreach ($chapters as $chapter): ?>
              <div class="chapter-card" data-aos="fade-up">
                <?php if ($chapter['hero_image']): ?>
                  <img 
                    src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($chapter['hero_image']) ?>" 
                    alt="<?= htmlspecialchars($chapter['name']) ?>" 
                    class="chapter-card-image"
                  >
                <?php else: ?>
                  <div class="chapter-card-image" style="display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                    <i class="fas fa-<?= $chapter['is_campus'] ? 'university' : 'map-marker-alt' ?>"></i>
                  </div>
                <?php endif; ?>
                
                <?php if ($chapter['is_campus']): ?>
                  <div class="chapter-badge">
                    <i class="fas fa-university"></i> CAMPUS
                  </div>
                <?php endif; ?>
                
                <?php if ($chapter['pending_requests'] > 0): ?>
                  <div class="chapter-requests-badge">
                    <?= $chapter['pending_requests'] ?> Requests
                  </div>
                <?php endif; ?>
                
                <div class="chapter-content">
                  <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <h3 class="chapter-title"><?= htmlspecialchars($chapter['name']) ?></h3>
                    <span class="status-badge <?= $chapter['status'] ?>">
                      <?= ucfirst($chapter['status']) ?>
                    </span>
                  </div>
                  
                  <div class="chapter-meta">
                    <div>
                      <i class="fas fa-map-marker-alt"></i>
                      <?= htmlspecialchars($chapter['location']) ?>
                    </div>
                    
                    <?php if ($chapter['is_campus'] && $chapter['campus_university']): ?>
                      <div>
                        <i class="fas fa-university"></i>
                        <?= htmlspecialchars($chapter['campus_university']) ?>
                      </div>
                    <?php endif; ?>
                    
                    <?php if ($chapter['leader_first_name']): ?>
                      <div>
                        <i class="fas fa-user-tie"></i>
                        <?= htmlspecialchars($chapter['leader_first_name'] . ' ' . $chapter['leader_last_name']) ?>
                      </div>
                    <?php endif; ?>
                    
                    <div>
                      <i class="fas fa-users"></i>
                      <?= number_format($chapter['member_count']) ?> members
                    </div>
                    
                    <?php if ($chapter['meeting_schedule']): ?>
                      <div>
                        <i class="fas fa-calendar"></i>
                        <?= htmlspecialchars($chapter['meeting_schedule']) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  
                  <div class="chapter-actions">
                    <button class="btn btn-primary btn-sm" onclick="viewChapter(<?= $chapter['id'] ?>)" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="editChapter(<?= $chapter['id'] ?>)" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-delete" onclick="deleteChapter(<?= $chapter['id'] ?>, '<?= htmlspecialchars($chapter['name']) ?>')" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top: 2rem;">
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
            <div class="empty-state-icon"><i class="fas fa-map-marked-alt"></i></div>
            <div class="empty-state-title">No Chapters Found</div>
            <div class="empty-state-text">
              <?php if (!empty($search) || !empty($status) || !empty($type)): ?>
                Try adjusting your filters
              <?php else: ?>
                Create your first chapter to get started
              <?php endif; ?>
            </div>
            <?php if (empty($search) && empty($status) && empty($type)): ?>
              <button class="btn btn-primary" onclick="openCreateChapterModal()" style="margin-top: 1.5rem;">
                <i class="fas fa-plus"></i> Create First Chapter
              </button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- Create/Edit Chapter Modal -->
<div class="admin-modal" id="chapterModal">
  <div class="admin-modal-content" style="max-width: 900px;">
    <div class="admin-modal-header">
      <h2 id="modalTitle">Create Chapter</h2>
      <button class="admin-modal-close" onclick="closeChapterModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="admin-modal-body">
      <form id="chapterForm" enctype="multipart/form-data">
        <input type="hidden" id="chapter_id" name="chapter_id">
        
        <!-- Chapter Type -->
        <div class="form-group">
          <label class="form-label">Chapter Type</label>
          <div style="display: flex; gap: 1rem;">
            <label id="regularTypeLabel" style="flex: 1; padding: 1rem; border: 2px solid var(--gray-300); border-radius: 8px; cursor: pointer; transition: all var(--transition);">
              <input type="radio" name="is_campus" value="0" checked onchange="toggleChapterType(0)" style="margin-right: 0.5rem;">
              <strong>Regular Chapter</strong>
              <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: var(--gray-600);">City or regional chapter</p>
            </label>
            <label id="campusTypeLabel" style="flex: 1; padding: 1rem; border: 2px solid var(--gray-300); border-radius: 8px; cursor: pointer; transition: all var(--transition);">
              <input type="radio" name="is_campus" value="1" onchange="toggleChapterType(1)" style="margin-right: 0.5rem;">
              <strong>Campus Chapter</strong>
              <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: var(--gray-600);">University or college chapter</p>
            </label>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="name" class="form-label">Chapter Name <span style="color: #EB5757;">*</span></label>
            <input 
              type="text" 
              id="name" 
              name="name" 
              class="form-control" 
              placeholder="e.g., Scribes Accra or Scribes KNUST"
              required
            >
          </div>
          
          <div class="form-group">
            <label for="location" class="form-label">Location <span style="color: #EB5757;">*</span></label>
            <input 
              type="text" 
              id="location" 
              name="location" 
              class="form-control" 
              placeholder="e.g., Accra, Ghana"
              required
            >
          </div>
        </div>
        
        <!-- Campus University (shown only for campus chapters) -->
        <div class="form-group" id="campusUniversityGroup" style="display: none;">
          <label for="campus_university" class="form-label">University/College Name</label>
          <input 
            type="text" 
            id="campus_university" 
            name="campus_university" 
            class="form-control" 
            placeholder="e.g., Kwame Nkrumah University of Science and Technology"
          >
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="latitude" class="form-label">Latitude</label>
            <input 
              type="number" 
              id="latitude" 
              name="latitude" 
              class="form-control" 
              step="any"
              placeholder="5.6037"
            >
            <small style="color: var(--gray-600); font-size: 0.875rem;">For map display</small>
          </div>
          
          <div class="form-group">
            <label for="longitude" class="form-label">Longitude</label>
            <input 
              type="number" 
              id="longitude" 
              name="longitude" 
              class="form-control" 
              step="any"
              placeholder="-0.1870"
            >
            <small style="color: var(--gray-600); font-size: 0.875rem;">For map display</small>
          </div>
        </div>
        
        <div class="form-group">
          <label for="description" class="form-label">Short Description</label>
          <textarea 
            id="description" 
            name="description" 
            class="form-control" 
            rows="2"
            placeholder="Brief one-line description (shown on map popup)"
          ></textarea>
          <small style="color: var(--gray-600); font-size: 0.875rem;">Keep it brief - this appears in map popups</small>
        </div>
        
        <div class="form-group">
          <label for="about_text" class="form-label">About Chapter</label>
          <textarea 
            id="about_text" 
            name="about_text" 
            class="form-control" 
            rows="5"
            placeholder="Detailed information about the chapter (shown on chapter page)"
          ></textarea>
          <small style="color: var(--gray-600); font-size: 0.875rem;">This appears on the chapter's dedicated page</small>
        </div>
        
        <!-- Hero Image -->
        <div class="form-group">
          <label for="hero_image" class="form-label">Hero Image</label>
          <input 
            type="file" 
            id="hero_image" 
            name="hero_image" 
            class="form-control" 
            accept="image/*"
            onchange="previewHeroImage(this)"
          >
          <small style="color: var(--gray-600); font-size: 0.875rem;">Main banner image for chapter page (JPG, PNG, WEBP - Max 5MB)</small>
          <div id="heroImagePreview" style="margin-top: 1rem;"></div>
        </div>
        
        <!-- Gallery Images -->
        <div class="form-group">
          <label for="gallery" class="form-label">Gallery Images</label>
          <input 
            type="file" 
            id="gallery" 
            name="gallery[]" 
            class="form-control" 
            accept="image/*"
            multiple
            onchange="previewGalleryImages(this)"
          >
          <small style="color: var(--gray-600); font-size: 0.875rem;">Multiple images for chapter gallery (Max 10 images, 5MB each)</small>
          <div id="galleryPreview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem; margin-top: 1rem;"></div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="contact_email" class="form-label">Contact Email</label>
            <input 
              type="email" 
              id="contact_email" 
              name="contact_email" 
              class="form-control" 
              placeholder="chapter@scribesglobal.com"
            >
          </div>
          
          <div class="form-group">
            <label for="contact_phone" class="form-label">Contact Phone</label>
            <input 
              type="tel" 
              id="contact_phone" 
              name="contact_phone" 
              class="form-control" 
              placeholder="+233 123 456 789"
            >
          </div>
        </div>
        
        <div class="form-group">
          <label for="meeting_schedule" class="form-label">Meeting Schedule</label>
          <input 
            type="text" 
            id="meeting_schedule" 
            name="meeting_schedule" 
            class="form-control" 
            placeholder="Every Sunday at 10:00 AM"
          >
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="leader_id" class="form-label">Chapter Leader</label>
            <select id="leader_id" name="leader_id" class="form-control">
              <option value="">Select a leader</option>
              <?php
              $leadersStmt = $conn->query("
                SELECT id, first_name, last_name, email 
                FROM users 
                WHERE role IN ('ministry_leader', 'administrator', 'super_admin') 
                AND status = 'active'
                ORDER BY first_name ASC
              ");
              $leaders = $leadersStmt->fetchAll();
              
              foreach ($leaders as $leader):
              ?>
                <option value="<?= $leader['id'] ?>">
                  <?= htmlspecialchars($leader['first_name'] . ' ' . $leader['last_name']) ?> 
                  (<?= htmlspecialchars($leader['email']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
          <button type="button" class="btn btn-outline" onclick="closeChapterModal()" style="flex: 1;">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary" style="flex: 2;">
            <i class="fas fa-save"></i> Save Chapter
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleAdminSidebar() {
  document.getElementById('adminSidebar').classList.toggle('mobile-visible');
}

function toggleChapterType(isCampus) {
  const campusGroup = document.getElementById('campusUniversityGroup');
  const regularLabel = document.getElementById('regularTypeLabel');
  const campusLabel = document.getElementById('campusTypeLabel');
  
  if (isCampus) {
    campusGroup.style.display = 'block';
    campusLabel.style.borderColor = '#6B46C1';
    campusLabel.style.background = 'rgba(107, 70, 193, 0.05)';
    regularLabel.style.borderColor = 'var(--gray-300)';
    regularLabel.style.background = 'white';
  } else {
    campusGroup.style.display = 'none';
    regularLabel.style.borderColor = '#6B46C1';
    regularLabel.style.background = 'rgba(107, 70, 193, 0.05)';
    campusLabel.style.borderColor = 'var(--gray-300)';
    campusLabel.style.background = 'white';
  }
}

function previewHeroImage(input) {
  const preview = document.getElementById('heroImagePreview');
  preview.innerHTML = '';
  
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px;">`;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function previewGalleryImages(input) {
  const preview = document.getElementById('galleryPreview');
  preview.innerHTML = '';
  
  if (input.files) {
    Array.from(input.files).forEach((file, index) => {
      if (index < 10) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const div = document.createElement('div');
          div.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 8px;">`;
          preview.appendChild(div);
        };
        reader.readAsDataURL(file);
      }
    });
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

document.getElementById('statusFilter').addEventListener('change', applyFilters);
document.getElementById('typeFilter').addEventListener('change', applyFilters);

function applyFilters() {
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;
  const type = document.getElementById('typeFilter').value;
  
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  if (type) params.set('type', type);
  
  window.location.href = '<?= SITE_URL ?>/admin/chapters' + (params.toString() ? '?' + params.toString() : '');
}

function resetFilters() {
  window.location.href = '<?= SITE_URL ?>/admin/chapters';
}

function goToPage(page) {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  window.location.href = '<?= SITE_URL ?>/admin/chapters?' + params.toString();
}

function openCreateChapterModal() {
  document.getElementById('modalTitle').textContent = 'Create Chapter';
  document.getElementById('chapterForm').reset();
  document.getElementById('chapter_id').value = '';
  document.getElementById('heroImagePreview').innerHTML = '';
  document.getElementById('galleryPreview').innerHTML = '';
  toggleChapterType(0);
  document.getElementById('chapterModal').classList.add('active');
}

function closeChapterModal() {
  document.getElementById('chapterModal').classList.remove('active');
}

async function viewChapter(chapterId) {
  window.open('<?= SITE_URL ?>/pages/chapters/view?id=' + chapterId, '_blank');
}

async function editChapter(chapterId) {
  try {
    const response = await fetch('<?= SITE_URL ?>/api/chapters.php?action=get_chapter&id=' + chapterId);
    const result = await response.json();
    
    if (result.success) {
      const chapter = result.chapter;
      
      document.getElementById('modalTitle').textContent = 'Edit Chapter';
      document.getElementById('chapter_id').value = chapter.id;
      document.getElementById('name').value = chapter.name;
      document.getElementById('location').value = chapter.location;
      document.getElementById('latitude').value = chapter.latitude || '';
      document.getElementById('longitude').value = chapter.longitude || '';
      document.getElementById('contact_email').value = chapter.contact_email || '';
      document.getElementById('contact_phone').value = chapter.contact_phone || '';
      document.getElementById('meeting_schedule').value = chapter.meeting_schedule || '';
      document.getElementById('description').value = chapter.description || '';
      document.getElementById('about_text').value = chapter.about_text || '';
      document.getElementById('leader_id').value = chapter.leader_id || '';
      document.getElementById('status').value = chapter.status;
      
      const isCampus = chapter.is_campus == 1;
      document.querySelector(`input[name="is_campus"][value="${isCampus ? '1' : '0'}"]`).checked = true;
      toggleChapterType(isCampus ? 1 : 0);
      
      if (isCampus) {
        document.getElementById('campus_university').value = chapter.campus_university || '';
      }
      
      if (chapter.hero_image) {
        document.getElementById('heroImagePreview').innerHTML = `
          <div style="position: relative;">
            <img src="<?= ASSETS_PATH ?>images/uploads/${chapter.hero_image}" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px;">
            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray-600);">Current hero image</div>
          </div>
        `;
      }
      
      if (chapter.gallery) {
        const gallery = JSON.parse(chapter.gallery);
        const galleryHTML = gallery.map(img => `
          <div style="position: relative;">
            <img src="<?= ASSETS_PATH ?>images/uploads/${img}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 8px;">
          </div>
        `).join('');
        document.getElementById('galleryPreview').innerHTML = galleryHTML;
      }
      
      document.getElementById('chapterModal').classList.add('active');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Failed to load chapter data');
  }
}

document.getElementById('chapterForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
  
  const formData = new FormData(this);
  const chapterId = document.getElementById('chapter_id').value;
  const action = chapterId ? 'update_chapter' : 'create_chapter';
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/chapters.php?action=' + action, {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(result.message);
      window.location.reload();
    } else {
      alert(result.message || 'An error occurred');
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

async function deleteChapter(chapterId, chapterName) {
  if (!confirm(`Are you sure you want to delete "${chapterName}"?\n\nThis will also delete:\n- All join requests for this chapter\n- Chapter from all member profiles\n\nThis action cannot be undone.`)) {
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('chapter_id', chapterId);
    
    const response = await fetch('<?= SITE_URL ?>/api/chapters.php?action=delete_chapter', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Chapter deleted successfully');
      window.location.reload();
    } else {
      alert(result.message || 'Failed to delete chapter');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

document.querySelectorAll('.admin-modal').forEach(modal => {
  modal.addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.remove('active');
    }
  });
});

AOS.init({
  duration: 800,
  easing: 'ease-in-out',
  once: true,
  offset: 100
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>