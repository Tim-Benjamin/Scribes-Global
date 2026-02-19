<?php
$pageTitle = 'Events Management - Admin - Scribes Global';
$pageDescription = 'Manage events';
$pageCSS = 'admin';
$noSplash = true;

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
.event-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}

.event-admin-card {
  background: white;
  border-radius: var(--radius-xl);
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  transition: all var(--transition-base);
  cursor: pointer;
  position: relative;
}

.event-admin-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.event-admin-image {
  width: 100%;
  height: 180px;
  object-fit: cover;
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
}

.event-admin-image.placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 3rem;
}

.event-admin-body {
  padding: 1.5rem;
}

.event-admin-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
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
}

.event-admin-actions {
  display: flex;
  gap: 0.5rem;
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
}
</style>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Events Management</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Manage all events, registrations and schedules</p>
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
    <div class="admin-stats-grid" style="margin-bottom: 2rem;">
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
    
    <!-- Events Grid -->
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
                  <div class="event-admin-image placeholder">
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
                          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($event['hero_image']) ?>" alt="Event" class="user-avatar" style="border-radius: var(--radius-md);">
                        <?php else: ?>
                          <div class="user-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; border-radius: var(--radius-md);">
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
                      <span style="padding: 0.35rem 0.85rem; background: rgba(45, 156, 219, 0.1); color: #2D9CDB; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700;">
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
  document.getElementById('adminSidebar').classList.toggle('mobile-visible');
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
    <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--gray-100); border-radius: var(--radius-lg);">
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
      <button class="btn btn-outline btn-sm" onclick="emailRegistrants(${event.id})">
        <i class="fas fa-envelope"></i> Email All
      </button>
    </div>
    
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Chapter</th>
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
          ${reg.dietary_needs ? `<small style="color: var(--gray-600);">Dietary: ${reg.dietary_needs}</small>` : ''}
        </td>
        <td>${reg.email}</td>
        <td>${reg.phone || '-'}</td>
        <td>${reg.chapter || '-'}</td>
        <td>${new Date(reg.registered_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td>
        <td>
          <span class="status-badge ${reg.attendance_confirmed ? 'active' : 'pending'}">
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

async function emailRegistrants(eventId) {
  const subject = prompt('Email Subject:');
  if (!subject) return;
  
  const message = prompt('Email Message:');
  if (!message) return;
  
  // Implementation for sending email to all registrants
  alert('Email functionality will be implemented');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>