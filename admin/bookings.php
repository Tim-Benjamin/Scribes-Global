<?php
$pageTitle = 'Booking Requests - Admin - Scribes Global';
$pageDescription = 'Manage booking requests';
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

$user = getCurrentUser();
$db = new Database();
$conn = $db->connect();

// Get filters
$status = $_GET['status'] ?? '';
$eventType = $_GET['event_type'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT * FROM booking_invitations WHERE 1=1";
$params = [];

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
}

if (!empty($eventType)) {
    $query .= " AND event_type = ?";
    $params[] = $eventType;
}

$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM booking_invitations WHERE 1=1";
$countParams = [];

if (!empty($status)) {
    $countQuery .= " AND status = ?";
    $countParams[] = $status;
}
if (!empty($eventType)) {
    $countQuery .= " AND event_type = ?";
    $countParams[] = $eventType;
}

$countStmt = $conn->prepare($countQuery);
$countStmt->execute($countParams);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════
   ADMIN BOOKINGS PAGE - MODERN UI
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
  --gray-400: #9CA3AF;
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

/* ─── Admin Layout ──────────────────────────────────────── */
.admin-layout {
  display: flex;
  background: var(--gray-50);
  min-height: 100vh;
}

/* ─── Main Content Area ────────────────────────────────– */
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

/* ─── Filters Bar ──────────────────────────────────────────– */
.filters-bar {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  align-items: center;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  background: white;
  color: var(--gray-700);
  font-family: var(--font-body);
  font-size: 0.95rem;
  cursor: pointer;
  transition: all var(--transition);
}

.filter-select:hover {
  border-color: var(--gray-300);
}

.filter-select:focus {
  outline: none;
  border-color: var(--primary-purple);
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

/* ─── Admin Cards ──────────────────────────────────────– */
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

/* ─── Table Styles ─────────────────────────────────────– */
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

/* ─── Status Badge ──────────────────────────────────────– */
.status-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-badge.pending {
  background: rgba(212, 175, 55, 0.15);
  color: var(--primary-gold);
}

.status-badge.approved {
  background: rgba(45, 156, 219, 0.15);
  color: #2D9CDB;
}

.status-badge.confirmed {
  background: rgba(81, 207, 102, 0.15);
  color: #51CF66;
}

.status-badge.rejected {
  background: rgba(235, 87, 87, 0.15);
  color: var(--primary-coral);
}

/* ─── Action Buttons ────────────────────────────────────– */
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

.btn-icon.btn-view:hover {
  background: rgba(107, 70, 193, 0.1);
  border-color: var(--primary-purple);
  color: var(--primary-purple);
}

.btn-icon.btn-edit:hover {
  background: rgba(81, 207, 102, 0.1);
  border-color: #51CF66;
  color: #51CF66;
}

/* ─── Pagination ────────────────────────────────────────– */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
  flex-wrap: wrap;
}

.pagination button,
.pagination span {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--gray-200);
  background: white;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--gray-700);
  transition: all var(--transition);
  font-family: var(--font-body);
}

.pagination button:hover:not(.active) {
  border-color: var(--primary-purple);
  color: var(--primary-purple);
}

.pagination button.active {
  background: var(--primary-purple);
  color: white;
  border-color: var(--primary-purple);
}

.pagination span {
  border: none;
  cursor: default;
}

/* ─── Empty State ──────────────────────────────────────– */
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
  margin: 0 0 0.5rem 0;
}

.empty-state-text {
  color: var(--gray-600);
  font-size: 0.95rem;
  margin: 0;
}

/* ─── Responsive Design ────────────────────────────────– */
@media (max-width: 768px) {
  .admin-main {
    margin-left: 0;
    padding: 1.25rem;
  }

  .mobile-admin-toggle {
    display: flex;
  }

  .admin-page-title {
    font-size: 1.5rem;
  }

  .filters-bar {
    flex-direction: column;
  }

  .filter-select {
    width: 100%;
  }

  .data-table {
    font-size: 0.85rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
  }
}

@media (max-width: 480px) {
  .admin-main {
    padding: 1rem;
  }

  .admin-card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .admin-card-body {
    padding: 1rem;
  }

  .table-wrapper {
    font-size: 0.8rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.5rem;
  }

  .action-buttons {
    width: 100%;
    justify-content: center;
  }
}
</style>

<div class="admin-layout">
  <!-- Include Sidebar from admin/includes/sidebar.php -->
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  
  <!-- Admin Main Content -->
  <main class="admin-main" id="adminMain">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Booking Requests</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Manage event booking requests</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
      <select class="filter-select" id="statusFilter">
        <option value="">All Statuses</option>
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
      
      <select class="filter-select" id="eventTypeFilter">
        <option value="">All Event Types</option>
        <option value="church_service" <?= $eventType === 'church_service' ? 'selected' : '' ?>>Church Service</option>
        <option value="conference" <?= $eventType === 'conference' ? 'selected' : '' ?>>Conference</option>
        <option value="youth_event" <?= $eventType === 'youth_event' ? 'selected' : '' ?>>Youth Event</option>
        <option value="wedding" <?= $eventType === 'wedding' ? 'selected' : '' ?>>Wedding</option>
        <option value="corporate" <?= $eventType === 'corporate' ? 'selected' : '' ?>>Corporate Event</option>
        <option value="outreach" <?= $eventType === 'outreach' ? 'selected' : '' ?>>Outreach</option>
        <option value="other" <?= $eventType === 'other' ? 'selected' : '' ?>>Other</option>
      </select>
      
      <button class="btn btn-outline" onclick="resetFilters()">
        <i class="fas fa-redo"></i> Reset
      </button>
    </div>
    
    <!-- Bookings Table -->
    <div class="admin-card" data-aos="fade-up">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-calendar-check"></i>
          All Booking Requests (<?= number_format($total) ?>)
        </h3>
      </div>
      <div class="admin-card-body">
        <?php if (count($bookings) > 0): ?>
          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Contact</th>
                  <th>Event Type</th>
                  <th>Event Date</th>
                  <th>Venue</th>
                  <th>Performance</th>
                  <th>Submitted</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($bookings as $booking): ?>
                  <tr>
                    <td><strong>#<?= $booking['id'] ?></strong></td>
                    <td>
                      <div>
                        <strong><?= htmlspecialchars($booking['name']) ?></strong><br>
                        <small style="color: var(--gray-600);"><?= htmlspecialchars($booking['email']) ?></small><br>
                        <small style="color: var(--gray-600);"><?= htmlspecialchars($booking['phone']) ?></small>
                      </div>
                    </td>
                    <td><?= ucfirst(str_replace('_', ' ', $booking['event_type'])) ?></td>
                    <td>
                      <strong><?= date('M d, Y', strtotime($booking['event_date'])) ?></strong><br>
                      <?php if ($booking['event_time']): ?>
                        <small style="color: var(--gray-600);"><?= date('g:i A', strtotime($booking['event_time'])) ?></small>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($booking['venue']) ?></td>
                    <td>
                      <small style="color: var(--gray-600);">
                        <?= htmlspecialchars(substr($booking['performance_type'], 0, 30)) ?>...
                      </small>
                    </td>
                    <td><?= date('M d, Y', strtotime($booking['created_at'])) ?></td>
                    <td>
                      <span class="status-badge <?= $booking['status'] ?>">
                        <?= ucfirst($booking['status']) ?>
                      </span>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-icon btn-view" onclick="viewBooking(<?= $booking['id'] ?>)" title="View Details">
                          <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($booking['status'] === 'pending'): ?>
                          <button class="btn-icon btn-edit" onclick="approveBooking(<?= $booking['id'] ?>)" title="Approve">
                            <i class="fas fa-check"></i>
                          </button>
                        <?php endif; ?>
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
            <div class="empty-state-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="empty-state-title">No Booking Requests Found</div>
            <div class="empty-state-text">Try adjusting your filters</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script>
function toggleAdminSidebar() {
  document.getElementById('adminSidebar').classList.toggle('mobile-visible');
}

document.getElementById('statusFilter').addEventListener('change', applyFilters);
document.getElementById('eventTypeFilter').addEventListener('change', applyFilters);

function applyFilters() {
  const status = document.getElementById('statusFilter').value;
  const eventType = document.getElementById('eventTypeFilter').value;
  
  const params = new URLSearchParams();
  if (status) params.set('status', status);
  if (eventType) params.set('event_type', eventType);
  
  window.location.href = '<?= SITE_URL ?>/admin/bookings' + (params.toString() ? '?' + params.toString() : '');
}

function resetFilters() {
  window.location.href = '<?= SITE_URL ?>/admin/bookings';
}

function goToPage(page) {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  window.location.href = '<?= SITE_URL ?>/admin/bookings?' + params.toString();
}

function viewBooking(id) {
  window.location.href = '<?= SITE_URL ?>/admin/bookings/view?id=' + id;
}

function approveBooking(id) {
  if (!confirm('Approve this booking request?')) return;
  
  // Add approval logic here
  alert('Booking approval functionality will be implemented');
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>