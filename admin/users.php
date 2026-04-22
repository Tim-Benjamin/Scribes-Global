<?php
$pageTitle = 'Users Management - Admin - Scribes Global';
$pageDescription = 'Manage users';
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
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($role)) {
    $query .= " AND role = ?";
    $params[] = $role;
}

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
}

// Add ORDER BY and LIMIT directly (not as parameters)
$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
}
if (!empty($role)) {
    $countQuery .= " AND role = ?";
    $countParams[] = $role;
}
if (!empty($status)) {
    $countQuery .= " AND status = ?";
    $countParams[] = $status;
}

$countStmt = $conn->prepare($countQuery);
$countStmt->execute($countParams);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════
   ADMIN USERS PAGE - MODERN UI
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
  flex: 1;
  min-width: 150px;
  padding: 0.75rem 1rem;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  font-family: var(--font-body);
  font-size: 0.9rem;
  transition: all var(--transition);
  background: white;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: var(--primary-purple);
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.search-input::placeholder {
  color: var(--gray-400);
}

/* ─── Admin Cards ──────────────────────────────────────────– */
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

/* ─── Table Styles ─────────────────────────────────────────– */
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

.role-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  background: rgba(107, 70, 193, 0.1);
  color: var(--primary-purple);
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
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

.status-badge.suspended {
  background: rgba(212, 175, 55, 0.15);
  color: var(--primary-gold);
}

.status-badge.banned {
  background: rgba(235, 87, 87, 0.15);
  color: var(--primary-coral);
}

/* ─── Action Buttons ───────────────────────────────────────– */
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

.btn-icon.btn-delete:hover {
  color: var(--primary-coral);
  border-color: var(--primary-coral);
}

/* ─── Pagination ───────────────────────────────────────────– */
.pagination {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  margin-top: 2rem;
  flex-wrap: wrap;
}

.pagination button,
.pagination span {
  padding: 0.6rem 1rem;
  border: 1px solid var(--gray-200);
  background: white;
  border-radius: 6px;
  cursor: pointer;
  font-family: var(--font-body);
  font-size: 0.9rem;
  transition: all var(--transition);
  color: var(--gray-700);
}

.pagination button:hover {
  background: var(--gray-100);
  border-color: var(--primary-purple);
  color: var(--primary-purple);
}

.pagination button.active {
  background: var(--primary-purple);
  border-color: var(--primary-purple);
  color: white;
}

.pagination span {
  cursor: default;
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
  margin-top: 0.5rem;
}

/* ─── Modal Styles ─────────────────────────────────────────– */
.admin-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.admin-modal.active {
  display: flex;
}

.admin-modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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
  font-family: var(--font-heading);
  font-weight: 700;
  color: var(--dark-bg);
}

.admin-modal-close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.5rem;
  color: var(--gray-600);
  transition: all var(--transition);
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.admin-modal-close:hover {
  background: var(--gray-100);
  color: var(--dark-bg);
}

.admin-modal-body {
  padding: 1.5rem;
}

/* ─── Form Styles ──────────────────────────────────────────– */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 0.5rem;
  font-family: var(--font-heading);
  font-size: 0.95rem;
}

.form-control {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  font-family: var(--font-body);
  font-size: 0.9rem;
  transition: all var(--transition);
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-purple);
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

/* ─── Responsive ───────────────────────────────────────────– */
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

  .search-input,
  .filter-select {
    width: 100%;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
    font-size: 0.85rem;
  }

  .action-buttons {
    flex-direction: column;
  }

  .btn-icon {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .admin-main {
    padding: 1rem;
  }

  .admin-card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .admin-modal-content {
    width: 95%;
    max-height: 90vh;
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
        <h1 class="admin-page-title">Users Management</h1>
        <p class="admin-page-subtitle">Manage all users and their roles</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <button class="btn btn-secondary" onclick="openNotificationModal()">
          <i class="fas fa-envelope"></i> Send Notification
        </button>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
      <input 
        type="text" 
        class="search-input" 
        placeholder="Search users by name or email..." 
        value="<?= htmlspecialchars($search) ?>"
        id="searchInput"
      >
      
      <select class="filter-select" id="roleFilter">
        <option value="">All Roles</option>
        <option value="super_admin" <?= $role === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
        <option value="administrator" <?= $role === 'administrator' ? 'selected' : '' ?>>Administrator</option>
        <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor</option>
        <option value="ministry_leader" <?= $role === 'ministry_leader' ? 'selected' : '' ?>>Ministry Leader</option>
        <option value="member" <?= $role === 'member' ? 'selected' : '' ?>>Member</option>
      </select>
      
      <select class="filter-select" id="statusFilter">
        <option value="">All Statuses</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
        <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>Banned</option>
      </select>
      
      <button class="btn btn-outline" onclick="resetFilters()">
        <i class="fas fa-redo"></i> Reset
      </button>
    </div>
    
    <!-- Users Table -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-users"></i>
          All Users (<?= number_format($total) ?>)
        </h3>
      </div>
      <div class="admin-card-body">
        <?php if (count($users) > 0): ?>
          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Primary Role</th>
                  <th>Joined</th>
                  <th>Profile</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $user): ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <?php if ($user['profile_photo']): ?>
                          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="User" class="user-avatar">
                        <?php else: ?>
                          <div class="user-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                          </div>
                        <?php endif; ?>
                        <div class="user-info">
                          <h4><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?></h4>
                          <p><?= $user['email_verified'] ? '<i class="fas fa-check-circle" style="color: #51CF66;"></i> Verified' : '<i class="fas fa-clock" style="color: #FFA500;"></i> Unverified' ?></p>
                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><span class="role-badge"><?= ucfirst(str_replace('_', ' ', $user['role'])) ?></span></td>
                    <td><?= ucfirst(str_replace('_', ' ', $user['primary_role'])) ?></td>
                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                      <div style="width: 100px; background: var(--gray-200); height: 8px; border-radius: 10px; overflow: hidden;">
                        <div style="width: <?= $user['profile_completion'] ?>%; height: 100%; background: linear-gradient(90deg, #6B46C1 0%, #2D9CDB 100%);"></div>
                      </div>
                      <small style="font-size: 0.75rem; color: var(--gray-600);"><?= $user['profile_completion'] ?>%</small>
                    </td>
                    <td><span class="status-badge <?= $user['status'] ?>"><?= ucfirst($user['status']) ?></span></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-icon btn-view" onclick="viewUser(<?= $user['id'] ?>)" title="View User">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-icon btn-edit" onclick="editUser(<?= $user['id'] ?>)" title="Edit User">
                          <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                          <button class="btn-icon btn-delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')" title="Delete User">
                            <i class="fas fa-trash"></i>
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
            <div class="empty-state-icon"><i class="fas fa-users"></i></div>
            <div class="empty-state-title">No Users Found</div>
            <div class="empty-state-text">Try adjusting your filters</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- User Details Modal -->
<div class="admin-modal" id="userModal">
  <div class="admin-modal-content">
    <div class="admin-modal-header">
      <h2>User Details</h2>
      <button class="admin-modal-close" onclick="closeUserModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="admin-modal-body" id="userModalBody">
      <!-- Content loaded dynamically -->
    </div>
  </div>
</div>

<!-- Send Notification Modal -->
<div class="admin-modal" id="notificationModal">
  <div class="admin-modal-content">
    <div class="admin-modal-header">
      <h2>Send Notification</h2>
      <button class="admin-modal-close" onclick="closeNotificationModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="admin-modal-body">
      <form id="notificationForm">
        <div class="form-group">
          <label for="recipients" class="form-label">Send To</label>
          <select id="recipients" name="recipients" class="form-control">
            <option value="all">All Active Users</option>
            <option value="role">Specific Role</option>
            <option value="chapter">Specific Chapter</option>
          </select>
        </div>
        
        <div class="form-group" id="roleSelectGroup" style="display: none;">
          <label for="roleSelect" class="form-label">Select Role</label>
          <select id="roleSelect" name="role" class="form-control">
            <option value="member">Members</option>
            <option value="ministry_leader">Ministry Leaders</option>
            <option value="editor">Editors</option>
            <option value="administrator">Administrators</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="subject" class="form-label">Subject</label>
          <input type="text" id="subject" name="subject" class="form-control" required>
        </div>
        
        <div class="form-group">
          <label for="message" class="form-label">Message</label>
          <textarea id="message" name="message" class="form-control" rows="6" required></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i class="fas fa-paper-plane"></i> Send Notification
        </button>
      </form>
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

document.getElementById('roleFilter').addEventListener('change', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);

function applyFilters() {
  const search = document.getElementById('searchInput').value;
  const role = document.getElementById('roleFilter').value;
  const status = document.getElementById('statusFilter').value;
  
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (role) params.set('role', role);
  if (status) params.set('status', status);
  
  window.location.href = '<?= SITE_URL ?>/admin/users' + (params.toString() ? '?' + params.toString() : '');
}

function resetFilters() {
  window.location.href = '<?= SITE_URL ?>/admin/users';
}

function goToPage(page) {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  window.location.href = '<?= SITE_URL ?>/admin/users?' + params.toString();
}

async function viewUser(userId) {
  const modal = document.getElementById('userModal');
  const body = document.getElementById('userModalBody');
  
  body.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #6B46C1;"></i></div>';
  modal.classList.add('active');
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=get_user_details&id=' + userId);
    const result = await response.json();
    
    if (result.success) {
      // Render user details
      body.innerHTML = renderUserDetails(result.user);
    } else {
      body.innerHTML = '<p style="text-align: center; color: var(--gray-600);">Failed to load user details</p>';
    }
  } catch (error) {
    console.error('Error:', error);
    body.innerHTML = '<p style="text-align: center; color: #EB5757;">An error occurred</p>';
  }
}

function closeUserModal() {
  document.getElementById('userModal').classList.remove('active');
}

function editUser(userId) {
  window.location.href = '<?= SITE_URL ?>/admin/users/edit?id=' + userId;
}

async function deleteUser(userId, userName) {
  if (!confirm(`Are you sure you want to delete ${userName}? This action cannot be undone.`)) {
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('user_id', userId);
    
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=delete_user', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('User deleted successfully');
      window.location.reload();
    } else {
      alert(result.message || 'Failed to delete user');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

// Notification Modal
function openNotificationModal() {
  document.getElementById('notificationModal').classList.add('active');
}

function closeNotificationModal() {
  document.getElementById('notificationModal').classList.remove('active');
}

document.getElementById('recipients').addEventListener('change', function() {
  const roleGroup = document.getElementById('roleSelectGroup');
  roleGroup.style.display = this.value === 'role' ? 'block' : 'none';
});

document.getElementById('notificationForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
  
  const formData = new FormData(this);
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=send_notification', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(result.message);
      closeNotificationModal();
      this.reset();
    } else {
      alert(result.message || 'Failed to send notification');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// Close sidebar on mobile when clicking outside
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