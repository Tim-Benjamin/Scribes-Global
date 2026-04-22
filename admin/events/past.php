<?php
$pageTitle = 'Past Events Management - Admin - Scribes Global';
$pageDescription = 'Manage past events media';
$noSplash = true;
$noNav = true;
$noFooter = true;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

if (!isAdmin()) {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: ' . SITE_URL . '/pages/dashboard');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get filters
$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT e.*, c.name as chapter_name,
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
          FROM events e
          LEFT JOIN chapters c ON e.chapter_id = c.id
          WHERE e.status = 'completed'";
$params = [];

if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($year)) {
    $query .= " AND YEAR(e.start_date) = ?";
    $params[] = $year;
}

$query .= " ORDER BY e.start_date DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM events e WHERE e.status = 'completed'";
$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (e.title LIKE ? OR e.description LIKE ?)";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
}
if (!empty($year)) {
    $countQuery .= " AND YEAR(e.start_date) = ?";
    $countParams[] = $year;
}

$countStmt = $conn->prepare($countQuery);
$countStmt->execute($countParams);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Get available years
$yearsStmt = $conn->query("
    SELECT DISTINCT YEAR(start_date) as year 
    FROM events 
    WHERE status = 'completed'
    ORDER BY year DESC
");
$years = $yearsStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
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
  transition: margin var(--transition);
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

/* ─── Stats Grid ────────────────────────────────────────── */
.admin-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
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
}

/* ─── Filters ───────────────────────────────────────────── */
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
  background: white;
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

.filter-select {
  min-width: 150px;
}

/* ─── Admin Card ────────────────────────────────────────── */
.admin-card {
  background: white;
  border-radius: 12px;
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

.admin-card-title i {
  color: var(--primary-purple);
}

.admin-card-body {
  padding: 1.5rem;
}

/* ─── Table Styles ──────────────────────────────────────── */
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
  border-color: var(--gray-300);
  color: var(--primary-purple);
}

/* ─── Media Preview ────────────────────────────────────── */
.media-preview {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.media-preview-item {
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  position: relative;
  border: 1px solid var(--gray-200);
}

.media-preview-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.media-count {
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  color: white;
  padding: 0.35rem 0.85rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

/* ─── Pagination ────────────────────────────────────────– */
.pagination {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--gray-200);
  flex-wrap: wrap;
}

.pagination button,
.pagination span {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--gray-200);
  background: white;
  color: var(--gray-700);
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all var(--transition);
}

.pagination button:hover {
  background: var(--gray-100);
  border-color: var(--primary-purple);
  color: var(--primary-purple);
}

.pagination button.active {
  background: var(--primary-purple);
  color: white;
  border-color: var(--primary-purple);
}

.pagination span {
  cursor: default;
}

/* ─── Empty State ───────────────────────────────────────– */
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
}

.empty-state-text {
  color: var(--gray-600);
  margin: 0;
  font-size: 0.95rem;
}

/* ─── Modal ─────────────────────────────────────────────– */
.admin-modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.admin-modal.active {
  display: flex;
}

.admin-modal-content {
  background: white;
  border-radius: 12px;
  max-width: 900px;
  width: 100%;
  max-height: 90vh;
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
  color: var(--dark-bg);
}

.admin-modal-close {
  width: 36px;
  height: 36px;
  border: 1px solid var(--gray-200);
  background: white;
  border-radius: 6px;
  cursor: pointer;
  color: var(--gray-700);
  font-size: 1.1rem;
  transition: all var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
}

.admin-modal-close:hover {
  background: var(--gray-100);
  border-color: var(--primary-purple);
  color: var(--primary-purple);
}

.admin-modal-body {
  padding: 1.5rem;
}

/* ─── Responsive ────────────────────────────────────────– */
@media (max-width: 768px) {
  .admin-main {
    margin-left: 0;
    padding: 1.25rem;
  }

  .mobile-admin-toggle {
    display: flex;
  }

  .admin-stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .admin-page-title {
    font-size: 1.5rem;
  }

  .filters-bar {
    flex-direction: column;
  }

  .search-input {
    min-width: 100%;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
    font-size: 0.85rem;
  }
}

@media (max-width: 480px) {
  .admin-stats-grid {
    grid-template-columns: 1fr;
  }

  .admin-stat-value {
    font-size: 1.5rem;
  }

  .admin-modal-content {
    max-height: 95vh;
  }
}
</style>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Past Events Management</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem; font-family: var(--font-body); font-size: 0.95rem;">
          Add photos and videos to past events
        </p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <a href="<?= SITE_URL ?>/admin/events" class="btn btn-outline">
          <i class="fas fa-arrow-left"></i> Back to Events
        </a>
      </div>
    </div>
    
    <!-- Stats Overview -->
    <div class="admin-stats-grid" style="margin-bottom: 2rem;">
      <?php
      $statsQuery = "
        SELECT 
          COUNT(*) as total_past,
          SUM(CASE WHEN gallery IS NOT NULL AND gallery != '[]' THEN 1 ELSE 0 END) as with_photos,
          SUM(CASE WHEN videos IS NOT NULL AND videos != '[]' THEN 1 ELSE 0 END) as with_videos,
          SUM((SELECT COUNT(*) FROM event_registrations WHERE event_id = events.id)) as total_attendees
        FROM events
        WHERE status = 'completed'
      ";
      $statsStmt = $conn->query($statsQuery);
      $pastStats = $statsStmt->fetch();
      ?>
      
      <div class="admin-stat-card purple" data-aos="fade-up">
        <div class="admin-stat-header">
          <div class="admin-stat-icon purple">
            <i class="fas fa-history"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= $pastStats['total_past'] ?></div>
        <div class="admin-stat-label">Past Events</div>
      </div>
      
      <div class="admin-stat-card gold" data-aos="fade-up" data-aos-delay="100">
        <div class="admin-stat-header">
          <div class="admin-stat-icon gold">
            <i class="fas fa-images"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= $pastStats['with_photos'] ?></div>
        <div class="admin-stat-label">Events with Photos</div>
      </div>
      
      <div class="admin-stat-card teal" data-aos="fade-up" data-aos-delay="200">
        <div class="admin-stat-header">
          <div class="admin-stat-icon teal">
            <i class="fas fa-video"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= $pastStats['with_videos'] ?></div>
        <div class="admin-stat-label">Events with Videos</div>
      </div>
      
      <div class="admin-stat-card coral" data-aos="fade-up" data-aos-delay="300">
        <div class="admin-stat-header">
          <div class="admin-stat-icon coral">
            <i class="fas fa-user-check"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($pastStats['total_attendees'] ?? 0) ?></div>
        <div class="admin-stat-label">Total Attendees</div>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
      <input 
        type="text" 
        class="search-input" 
        placeholder="Search past events..." 
        value="<?= htmlspecialchars($search) ?>"
        id="searchInput"
      >
      
      <select class="filter-select" id="yearFilter">
        <option value="">All Years</option>
        <?php foreach ($years as $y): ?>
          <option value="<?= $y['year'] ?>" <?= $year == $y['year'] ? 'selected' : '' ?>>
            <?= $y['year'] ?>
          </option>
        <?php endforeach; ?>
      </select>
      
      <button class="btn btn-outline" onclick="resetFilters()">
        <i class="fas fa-redo"></i> Reset
      </button>
    </div>
    
    <!-- Events Table -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-calendar-check"></i>
          Past Events (<?= number_format($total) ?>)
        </h3>
      </div>
      <div class="admin-card-body">
        <?php if (count($events) > 0): ?>
          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>Date</th>
                  <th>Attendees</th>
                  <th>Media</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($events as $event): 
                  $gallery = !empty($event['gallery']) ? json_decode($event['gallery'], true) : [];
                  $videos = !empty($event['videos']) ? json_decode($event['videos'], true) : [];
                  $photoCount = is_array($gallery) ? count($gallery) : 0;
                  $videoCount = is_array($videos) ? count($videos) : 0;
                ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <?php if ($event['hero_image']): ?>
                          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($event['hero_image']) ?>" alt="Event" class="user-avatar">
                        <?php else: ?>
                          <div class="user-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                            <i class="fas fa-calendar"></i>
                          </div>
                        <?php endif; ?>
                        <div class="user-info">
                          <h4><?= htmlspecialchars(substr($event['title'], 0, 30)) ?></h4>
                          <p><?= $event['chapter_name'] ?? 'No Chapter' ?></p>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div style="font-size: 0.875rem;">
                        <div style="font-weight: 600; color: var(--dark-bg);">
                          <?= date('M d, Y', strtotime($event['start_date'])) ?>
                        </div>
                        <div style="color: var(--gray-600); font-size: 0.75rem;">
                          <?= date('g:i A', strtotime($event['start_date'])) ?>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div style="font-weight: 700; color: var(--primary-purple);">
                        <?= $event['registration_count'] ?>
                      </div>
                    </td>
                    <td>
                      <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <span class="media-count" style="<?= $photoCount > 0 ? '' : 'background: var(--gray-300); color: var(--gray-600);' ?>">
                          <i class="fas fa-images"></i>
                          <?= $photoCount ?> <?= $photoCount === 1 ? 'Photo' : 'Photos' ?>
                        </span>
                        <span class="media-count" style="<?= $videoCount > 0 ? 'background: linear-gradient(135deg, #EB5757 0%, #FF8787 100%);' : 'background: var(--gray-300); color: var(--gray-600);' ?>">
                          <i class="fas fa-video"></i>
                          <?= $videoCount ?> <?= $videoCount === 1 ? 'Video' : 'Videos' ?>
                        </span>
                      </div>
                      
                      <?php if ($photoCount > 0): ?>
                        <div class="media-preview">
                          <?php 
                          $previewCount = min(3, $photoCount);
                          for ($i = 0; $i < $previewCount; $i++): 
                          ?>
                            <div class="media-preview-item">
                              <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($gallery[$i]) ?>" alt="Preview">
                            </div>
                          <?php endfor; ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <a href="<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>" class="btn-icon" title="View Event" target="_blank">
                          <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn-icon" onclick="openMediaManager(<?= $event['id'] ?>)" title="Manage Media" style="background: rgba(212, 175, 55, 0.1); color: #D4AF37;">
                          <i class="fas fa-photo-video"></i>
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
            <div class="empty-state-icon"><i class="fas fa-calendar-times"></i></div>
            <div class="empty-state-title">No Past Events Found</div>
            <div class="empty-state-text">
              <?php if (!empty($search) || !empty($year)): ?>
                Try adjusting your filters
              <?php else: ?>
                Past events will appear here once they're completed
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- Media Manager Modal -->
<div class="admin-modal" id="mediaManagerModal">
  <div class="admin-modal-content" style="max-width: 900px;">
    <div class="admin-modal-header">
      <h2 id="modalEventTitle">Manage Event Media</h2>
      <button class="admin-modal-close" onclick="closeMediaManager()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="admin-modal-body" id="mediaManagerBody">
      <!-- Content loaded dynamically -->
    </div>
  </div>
</div>

<script>
function toggleAdminSidebar() {
  document.getElementById('adminSidebar').classList.toggle('mobile-visible');
}

// Filters
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
});

document.getElementById('yearFilter').addEventListener('change', applyFilters);

function applyFilters() {
  const search = document.getElementById('searchInput').value;
  const year = document.getElementById('yearFilter').value;
  
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (year) params.set('year', year);
  
  window.location.href = '<?= SITE_URL ?>/admin/events/past' + (params.toString() ? '?' + params.toString() : '');
}

function resetFilters() {
  window.location.href = '<?= SITE_URL ?>/admin/events/past';
}

function goToPage(page) {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  window.location.href = '<?= SITE_URL ?>/admin/events/past?' + params.toString();
}

// Media Manager
async function openMediaManager(eventId) {
  const modal = document.getElementById('mediaManagerModal');
  const body = document.getElementById('mediaManagerBody');
  
  body.innerHTML = '<div style="text-align: center; padding: 3rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #6B46C1;"></i></div>';
  modal.classList.add('active');
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=get_event_media&event_id=' + eventId);
    const result = await response.json();
    
    if (result.success) {
      document.getElementById('modalEventTitle').textContent = result.event.title + ' - Media Manager';
      body.innerHTML = renderMediaManager(result);
    } else {
      body.innerHTML = '<p style="text-align: center; color: var(--gray-600);">Failed to load event media</p>';
    }
  } catch (error) {
    console.error('Error:', error);
    body.innerHTML = '<p style="text-align: center; color: var(--primary-coral);">An error occurred</p>';
  }
}

function renderMediaManager(data) {
  const event = data.event;
  const gallery = data.gallery || [];
  const videos = data.videos || [];
  
  let html = `
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
      <!-- Photos Section -->
      <div>
        <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fas fa-images" style="color: #6B46C1;"></i>
          Event Photos
          <span style="background: var(--gray-200); color: var(--gray-700); padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; margin-left: auto;">
            ${gallery.length} / 20
          </span>
        </h3>
        
        <div style="border: 2px dashed var(--gray-300); border-radius: 12px; padding: 2rem; text-align: center; background: var(--gray-50); margin-bottom: 1rem; cursor: pointer;" onclick="document.getElementById('galleryUpload').click()">
          <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: var(--gray-400); margin-bottom: 0.5rem;"></i>
          <p style="margin: 0; color: var(--gray-600); font-weight: 600;">Click to upload photos</p>
          <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: var(--gray-500);">Max 20 images, 5MB each</p>
          <input type="file" id="galleryUpload" accept="image/*" multiple style="display: none;" onchange="uploadGalleryImages(${event.id}, this.files)">
        </div>
        
        <div id="galleryGrid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; max-height: 400px; overflow-y: auto;">
          ${gallery.map((img, idx) => `
            <div style="position: relative; aspect-ratio: 1; border-radius: 8px; overflow: hidden; border: 2px solid var(--gray-200);">
              <img src="<?= ASSETS_PATH ?>images/uploads/${img}" style="width: 100%; height: 100%; object-fit: cover;">
              <button onclick="deleteGalleryImage(${event.id}, '${img}')" style="position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(235, 87, 87, 0.9); color: white; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          `).join('')}
        </div>
      </div>
      
      <!-- Videos Section -->
      <div>
        <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fas fa-video" style="color: #EB5757;"></i>
          Event Videos
          <span style="background: var(--gray-200); color: var(--gray-700); padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; margin-left: auto;">
            ${videos.length} / 10
          </span>
        </h3>
        
        <div style="margin-bottom: 1rem;">
          <input type="text" id="videoUrl" placeholder="YouTube video URL (e.g., https://www.youtube.com/watch?v=...)" style="width: 100%; padding: 0.875rem; border: 2px solid var(--gray-300); border-radius: 8px; font-family: var(--font-body); margin-bottom: 0.75rem;">
          <button class="btn btn-primary" style="width: 100%;" onclick="addVideo(${event.id})">
            <i class="fas fa-plus"></i> Add Video
          </button>
          <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: var(--gray-600);">
            <i class="fas fa-info-circle"></i> Paste YouTube video URL or embed link
          </p>
        </div>
        
        <div id="videosGrid" style="display: flex; flex-direction: column; gap: 1rem; max-height: 400px; overflow-y: auto;">
          ${videos.map((video, idx) => `
            <div style="position: relative; border-radius: 8px; overflow: hidden; border: 2px solid var(--gray-200);">
              <iframe src="${video}" style="width: 100%; height: 200px; border: none;"></iframe>
              <button onclick="deleteVideo(${event.id}, '${encodeURIComponent(video)}')" style="position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(235, 87, 87, 0.9); color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-trash"></i> Remove
              </button>
            </div>
          `).join('')}
        </div>
      </div>
    </div>
    
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid var(--gray-200); text-align: right;">
      <button class="btn btn-outline" onclick="closeMediaManager()">
        <i class="fas fa-times"></i> Close
      </button>
    </div>
  `;
  
  return html;
}

function closeMediaManager() {
  document.getElementById('mediaManagerModal').classList.remove('active');
}

async function uploadGalleryImages(eventId, files) {
  if (files.length === 0) return;
  
  if (files.length > 20) {
    alert('Maximum 20 images allowed');
    return;
  }
  
  const formData = new FormData();
  formData.append('event_id', eventId);
  
  for (let i = 0; i < files.length; i++) {
    if (files[i].size > 5 * 1024 * 1024) {
      alert(`File ${files[i].name} is too large. Max 5MB per image.`);
      return;
    }
    formData.append('gallery_images[]', files[i]);
  }
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=upload_event_gallery', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('✅ Photos uploaded successfully!');
      openMediaManager(eventId);
    } else {
      alert('❌ ' + (result.message || 'Failed to upload photos'));
    }
  } catch (error) {
    console.error('Upload error:', error);
    alert('❌ An error occurred while uploading');
  }
}

async function deleteGalleryImage(eventId, imageName) {
  if (!confirm('Are you sure you want to delete this photo?')) return;
  
  try {
    const formData = new FormData();
    formData.append('event_id', eventId);
    formData.append('image_name', imageName);
    
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=delete_event_gallery_image', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      openMediaManager(eventId);
    } else {
      alert('❌ ' + (result.message || 'Failed to delete photo'));
    }
  } catch (error) {
    console.error('Delete error:', error);
    alert('❌ An error occurred');
  }
}

async function addVideo(eventId) {
  const videoUrl = document.getElementById('videoUrl').value.trim();
  
  if (!videoUrl) {
    alert('Please enter a YouTube video URL');
    return;
  }
  
  let embedUrl = videoUrl;
  
  if (videoUrl.includes('youtube.com/watch?v=')) {
    const videoId = videoUrl.split('v=')[1].split('&')[0];
    embedUrl = `https://www.youtube.com/embed/${videoId}`;
  } else if (videoUrl.includes('youtu.be/')) {
    const videoId = videoUrl.split('youtu.be/')[1].split('?')[0];
    embedUrl = `https://www.youtube.com/embed/${videoId}`;
  } else if (!videoUrl.includes('youtube.com/embed/')) {
    alert('Please enter a valid YouTube URL');
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('event_id', eventId);
    formData.append('video_url', embedUrl);
    
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=add_event_video', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      document.getElementById('videoUrl').value = '';
      openMediaManager(eventId);
    } else {
      alert('❌ ' + (result.message || 'Failed to add video'));
    }
  } catch (error) {
    console.error('Add video error:', error);
    alert('❌ An error occurred');
  }
}

async function deleteVideo(eventId, videoUrl) {
  if (!confirm('Are you sure you want to remove this video?')) return;
  
  try {
    const formData = new FormData();
    formData.append('event_id', eventId);
    formData.append('video_url', decodeURIComponent(videoUrl));
    
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=delete_event_video', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      openMediaManager(eventId);
    } else {
      alert('❌ ' + (result.message || 'Failed to remove video'));
    }
  } catch (error) {
    console.error('Delete error:', error);
    alert('❌ An error occurred');
  }
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

// Initialize AOS
AOS.init({
  duration: 800,
  easing: 'ease-in-out',
  once: true,
  offset: 100
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>