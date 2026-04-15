<?php
$pageTitle = 'Booking Requests - Admin - Scribes Global';
$pageDescription = 'Manage booking requests';
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

<div class="admin-layout">
  <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
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
    <div class="admin-card">
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>