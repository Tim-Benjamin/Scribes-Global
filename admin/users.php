<?php
$pageTitle = 'Users Management - Admin - Scribes Global';
$pageDescription = 'Manage users';
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

<!-- Rest of the file remains the same -->

<div class="admin-layout">
  <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Users Management</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Manage all users and their roles</p>
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
      // Render user details (you'll need to create this template)
      body.innerHTML = renderUserDetails(result.user);
    } else {
      body.innerHTML = '<p style="text-align: center; color: var(--gray-600);">Failed to load user details</p>';
    }
  } catch (error) {
    console.error('Error:', error);
    body.innerHTML = '<p style="text-align: center; color: var(--primary-coral);">An error occurred</p>';
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>