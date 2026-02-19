<?php
$pageTitle = 'Admin Dashboard - Scribes Global';
$pageDescription = 'Scribes Global Admin Panel';
$pageCSS = 'admin';
$noSplash = true;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is admin
if (!isAdmin()) {
    $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
    header('Location: ' . SITE_URL . '/pages/dashboard');
    exit;
}

$user = getCurrentUser();
$db = new Database();
$conn = $db->connect();

// Get statistics
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users,
        (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_users,
        (SELECT COUNT(*) FROM events WHERE status = 'upcoming') as upcoming_events,
        (SELECT COUNT(*) FROM events WHERE start_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as week_events,
        (SELECT COUNT(*) FROM blog_posts WHERE status = 'published') as total_posts,
        (SELECT COUNT(*) FROM blog_posts WHERE published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as month_posts,
        (SELECT COUNT(*) FROM event_registrations WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as month_registrations,
        (SELECT COUNT(*) FROM media_content WHERE status = 'pending') as pending_media,
        (SELECT COUNT(*) FROM prayer_requests WHERE status = 'pending') as pending_prayers,
        (SELECT SUM(amount) FROM donations WHERE status = 'completed') as total_donations
";

$statsStmt = $conn->query($statsQuery);
$stats = $statsStmt->fetch();

// Get recent users
$recentUsersStmt = $conn->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentUsers = $recentUsersStmt->fetchAll();

// Get recent events
$recentEventsStmt = $conn->query("
    SELECT * FROM events 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentEvents = $recentEventsStmt->fetchAll();

// Get pending approvals
$pendingApprovalsStmt = $conn->query("
    SELECT 
        'media' as type, 
        id, 
        title, 
        user_id, 
        created_at 
    FROM media_content 
    WHERE status = 'pending'
    UNION ALL
    SELECT 
        'prayer' as type, 
        id, 
        title, 
        user_id, 
        created_at 
    FROM prayer_requests 
    WHERE status = 'pending'
    ORDER BY created_at DESC
    LIMIT 10
");
$pendingApprovals = $pendingApprovalsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <!-- Admin Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-header-section">
      <div class="admin-logo">
        <div class="admin-logo-icon">
          <i class="fas fa-shield-alt"></i>
        </div>
        <div class="admin-logo-text">
          <h2>Admin Panel</h2>
          <p>Scribes Global</p>
        </div>
      </div>
    </div>
    
    <nav class="admin-nav">
      <div class="admin-nav-section">
        <div class="admin-nav-title">Main</div>
        <a href="<?= SITE_URL ?>/admin" class="admin-nav-item active">
          <i class="fas fa-chart-line"></i>
          <span>Dashboard</span>
        </a>
        <a href="<?= SITE_URL ?>/admin/analytics" class="admin-nav-item">
          <i class="fas fa-chart-bar"></i>
          <span>Analytics</span>
        </a>
      </div>
      
      <div class="admin-nav-section">
        <div class="admin-nav-title">Content</div>
        <a href="<?= SITE_URL ?>/admin/events" class="admin-nav-item">
          <i class="fas fa-calendar-alt"></i>
          <span>Events</span>
          <?php if ($stats['week_events'] > 0): ?>
            <span class="admin-nav-badge"><?= $stats['week_events'] ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= SITE_URL ?>/admin/posts" class="admin-nav-item">
          <i class="fas fa-newspaper"></i>
          <span>Blog Posts</span>
        </a>
        <a href="<?= SITE_URL ?>/admin/media" class="admin-nav-item">
          <i class="fas fa-photo-video"></i>
          <span>Media</span>
          <?php if ($stats['pending_media'] > 0): ?>
            <span class="admin-nav-badge"><?= $stats['pending_media'] ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= SITE_URL ?>/admin/prayers" class="admin-nav-item">
          <i class="fas fa-praying-hands"></i>
          <span>Prayer Requests</span>
          <?php if ($stats['pending_prayers'] > 0): ?>
            <span class="admin-nav-badge"><?= $stats['pending_prayers'] ?></span>
          <?php endif; ?>
        </a>
      </div>
      
      <div class="admin-nav-section">
        <div class="admin-nav-title">Users & Community</div>
        <a href="<?= SITE_URL ?>/admin/users" class="admin-nav-item">
          <i class="fas fa-users"></i>
          <span>Users</span>
        </a>
        <a href="<?= SITE_URL ?>/admin/badges" class="admin-nav-item">
          <i class="fas fa-award"></i>
          <span>Badges</span>
        </a>
        <a href="<?= SITE_URL ?>/admin/chapters" class="admin-nav-item">
          <i class="fas fa-map-marked-alt"></i>
          <span>Chapters</span>
        </a>
        <a href="<?= SITE_URL ?>/admin/ministries" class="admin-nav-item">
          <i class="fas fa-hands-helping"></i>
          <span>Ministries</span>
        </a>
      </div>
      
      <div class="admin-nav-section">
        <div class="admin-nav-title">Financial</div>
        <a href="<?= SITE_URL ?>/admin/donations" class="admin-nav-item">
          <i class="fas fa-donate"></i>
          <span>Donations</span>
        </a>
        <a href="<?= SITE_URL ?>/admin/reports" class="admin-nav-item">
          <i class="fas fa-file-invoice-dollar"></i>
          <span>Reports</span>
        </a>
      </div>
      
      <div class="admin-nav-section">
        <div class="admin-nav-title">System</div>
        <a href="<?= SITE_URL ?>/admin/settings" class="admin-nav-item">
          <i class="fas fa-cog"></i>
          <span>Settings</span>
        </a>
        <a href="<?= SITE_URL ?>/pages/dashboard" class="admin-nav-item">
          <i class="fas fa-arrow-left"></i>
          <span>Back to Site</span>
        </a>
      </div>
    </nav>
  </aside>
  
  <!-- Admin Main Content -->
  <main class="admin-main" id="adminMain">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Dashboard Overview</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Welcome back, <?= htmlspecialchars($user['first_name']) ?>! Here's what's happening.</p>
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
    
    <!-- Stats Grid -->
    <div class="admin-stats-grid">
      <div class="admin-stat-card purple" data-aos="fade-up">
        <div class="admin-stat-header">
          <div class="admin-stat-icon purple">
            <i class="fas fa-users"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['total_users']) ?></div>
        <div class="admin-stat-label">Total Users</div>
        <div style="margin-top: 0.75rem; font-size: 0.875rem; color: var(--gray-600);">
          <span style="color: #51CF66; font-weight: 700;">+<?= $stats['new_users'] ?></span> this month
        </div>
      </div>
      
      <div class="admin-stat-card gold" data-aos="fade-up" data-aos-delay="100">
        <div class="admin-stat-header">
          <div class="admin-stat-icon gold">
            <i class="fas fa-calendar-check"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['upcoming_events']) ?></div>
        <div class="admin-stat-label">Upcoming Events</div>
        <div style="margin-top: 0.75rem; font-size: 0.875rem; color: var(--gray-600);">
          <span style="color: #2D9CDB; font-weight: 700;"><?= $stats['week_events'] ?></span> this week
        </div>
      </div>
      
      <div class="admin-stat-card teal" data-aos="fade-up" data-aos-delay="200">
        <div class="admin-stat-header">
          <div class="admin-stat-icon teal">
            <i class="fas fa-newspaper"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['total_posts']) ?></div>
        <div class="admin-stat-label">Published Posts</div>
        <div style="margin-top: 0.75rem; font-size: 0.875rem; color: var(--gray-600);">
          <span style="color: #51CF66; font-weight: 700;">+<?= $stats['month_posts'] ?></span> this month
        </div>
      </div>
      
      <div class="admin-stat-card coral" data-aos="fade-up" data-aos-delay="300">
        <div class="admin-stat-header">
          <div class="admin-stat-icon coral">
            <i class="fas fa-ticket-alt"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['month_registrations']) ?></div>
        <div class="admin-stat-label">Event Registrations</div>
        <div style="margin-top: 0.75rem; font-size: 0.875rem; color: var(--gray-600);">
          This month
        </div>
      </div>
      
      <div class="admin-stat-card green" data-aos="fade-up" data-aos-delay="400">
        <div class="admin-stat-header">
          <div class="admin-stat-icon green">
            <i class="fas fa-donate"></i>
          </div>
        </div>
        <div class="admin-stat-value">GH₵<?= number_format($stats['total_donations'] ?? 0, 2) ?></div>
        <div class="admin-stat-label">Total Donations</div>
        <div style="margin-top: 0.75rem; font-size: 0.875rem; color: var(--gray-600);">
          All time
        </div>
      </div>
    </div>
    
    <!-- Content Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
      <!-- Recent Users -->
      <div class="admin-card" data-aos="fade-up">
        <div class="admin-card-header">
          <h3 class="admin-card-title">
            <i class="fas fa-user-plus"></i>
            Recent Users
          </h3>
          <a href="<?= SITE_URL ?>/admin/users" style="color: var(--primary-purple); font-weight: 600;">View All</a>
        </div>
        <div class="admin-card-body">
          <?php if (count($recentUsers) > 0): ?>
            <div class="table-wrapper">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentUsers as $recentUser): ?>
                    <tr>
                      <td>
                        <div class="user-cell">
                          <?php if ($recentUser['profile_photo']): ?>
                            <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($recentUser['profile_photo']) ?>" alt="User" class="user-avatar">
                          <?php else: ?>
                            <div class="user-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                              <?= strtoupper(substr($recentUser['first_name'], 0, 1)) ?>
                            </div>
                          <?php endif; ?>
                          <div class="user-info">
                            <h4><?= htmlspecialchars($recentUser['first_name']) ?> <?= htmlspecialchars($recentUser['last_name']) ?></h4>
                            <p><?= ucfirst(str_replace('_', ' ', $recentUser['primary_role'])) ?></p>
                          </div>
                        </div>
                      </td>
                      <td><?= htmlspecialchars($recentUser['email']) ?></td>
                      <td><span class="role-badge"><?= ucfirst(str_replace('_', ' ', $recentUser['role'])) ?></span></td>
                      <td><?= date('M d, Y', strtotime($recentUser['created_at'])) ?></td>
                      <td><span class="status-badge <?= $recentUser['status'] ?>"><?= ucfirst($recentUser['status']) ?></span></td>
                      <td>
                        <div class="action-buttons">
                          <button class="btn-icon btn-view" onclick="viewUser(<?= $recentUser['id'] ?>)" title="View User">
                            <i class="fas fa-eye"></i>
                          </button>
                          <button class="btn-icon btn-edit" onclick="editUser(<?= $recentUser['id'] ?>)" title="Edit User">
                            <i class="fas fa-edit"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fas fa-users"></i></div>
              <div class="empty-state-title">No Users Yet</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Pending Approvals -->
      <div class="admin-card" data-aos="fade-up" data-aos-delay="100">
        <div class="admin-card-header">
          <h3 class="admin-card-title" style="font-size: 1.25rem;">
            <i class="fas fa-tasks"></i>
            Pending Approvals
          </h3>
        </div>
        <div class="admin-card-body" style="padding: 1.5rem;">
          <?php if (count($pendingApprovals) > 0): ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
              <?php foreach ($pendingApprovals as $approval): ?>
                <div style="padding: 1rem; background: var(--gray-100); border-radius: var(--radius-lg); border-left: 4px solid <?= $approval['type'] === 'media' ? '#2D9CDB' : '#D4AF37' ?>;">
                  <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 0.5rem;">
                    <div style="flex: 1;">
                      <div style="font-weight: 600; color: var(--dark-bg); margin-bottom: 0.25rem;">
                        <?= htmlspecialchars($approval['title']) ?>
                      </div>
                      <div style="font-size: 0.875rem; color: var(--gray-600);">
                        <i class="fas fa-<?= $approval['type'] === 'media' ? 'photo-video' : 'praying-hands' ?>"></i>
                        <?= ucfirst($approval['type']) ?> • <?= timeAgo($approval['created_at']) ?>
                      </div>
                    </div>
                  </div>
                  <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                    <button class="btn btn-primary btn-sm" style="flex: 1;" onclick="approveItem('<?= $approval['type'] ?>', <?= $approval['id'] ?>)">
                      <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn btn-outline btn-sm" onclick="rejectItem('<?= $approval['type'] ?>', <?= $approval['id'] ?>)">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
              <i class="fas fa-check-circle" style="font-size: 3rem; color: #51CF66; margin-bottom: 1rem;"></i>
              <p style="color: var(--gray-600); margin: 0;">All caught up! No pending approvals.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Recent Events -->
    <div class="admin-card" data-aos="fade-up" style="margin-top: 2rem;">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-calendar"></i>
          Recent Events
        </h3>
        <a href="<?= SITE_URL ?>/admin/events" style="color: var(--primary-purple); font-weight: 600;">View All</a>
      </div>
      <div class="admin-card-body">
        <?php if (count($recentEvents) > 0): ?>
          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($recentEvents as $event): ?>
              <div style="background: var(--gray-100); border-radius: var(--radius-xl); overflow: hidden; cursor: pointer; transition: all var(--transition-base);" onclick="window.location.href='<?= SITE_URL ?>/admin/events/edit?id=<?= $event['id'] ?>'" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                <?php if ($event['hero_image']): ?>
                  <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($event['hero_image']) ?>" alt="Event" style="width: 100%; height: 150px; object-fit: cover;">
                <?php else: ?>
                  <div style="width: 100%; height: 150px; background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                    <i class="fas fa-calendar-alt"></i>
                  </div>
                <?php endif; ?>
                <div style="padding: 1.5rem;">
                  <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                    <h4 style="margin: 0; font-size: 1.125rem; color: var(--dark-bg);"><?= htmlspecialchars($event['title']) ?></h4>
                    <span class="status-badge <?= $event['status'] ?>"><?= ucfirst($event['status']) ?></span>
                  </div>
                  <div style="font-size: 0.875rem; color: var(--gray-600); display: flex; flex-direction: column; gap: 0.5rem;">
                    <div><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($event['start_date'])) ?></div>
                    <div><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?></div>
                    <div><i class="fas fa-users"></i> <?= $event['registration_count'] ?? 0 ?> registered</div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-calendar"></i></div>
            <div class="empty-state-title">No Events Yet</div>
            <a href="<?= SITE_URL ?>/admin/events/create" class="btn btn-primary">Create First Event</a>
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

function viewUser(userId) {
  window.location.href = '<?= SITE_URL ?>/admin/users/view?id=' + userId;
}

function editUser(userId) {
  window.location.href = '<?= SITE_URL ?>/admin/users/edit?id=' + userId;
}

async function approveItem(type, id) {
  if (!confirm('Are you sure you want to approve this item?')) return;
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=approve_content', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type, id })
    });
    
    const result = await response.json();
    if (result.success) {
      alert('Item approved successfully!');
      window.location.reload();
    } else {
      alert(result.message || 'Failed to approve item');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

async function rejectItem(type, id) {
  if (!confirm('Are you sure you want to reject this item?')) return;
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=reject_content', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type, id })
    });
    
    const result = await response.json();
    if (result.success) {
      alert('Item rejected successfully!');
      window.location.reload();
    } else {
      alert(result.message || 'Failed to reject item');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

function timeAgo(datetime) {
  const timestamp = new Date(datetime).getTime();
  const now = new Date().getTime();
  const difference = now - timestamp;
  
  const minutes = Math.floor(difference / 60000);
  const hours = Math.floor(difference / 3600000);
  const days = Math.floor(difference / 86400000);
  
  if (minutes < 60) return minutes + ' min ago';
  if (hours < 24) return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
  return days + ' day' + (days > 1 ? 's' : '') + ' ago';
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('adminSidebar');
  const toggle = document.querySelector('.mobile-admin-toggle');
  
  if (window.innerWidth <= 768 && 
      !sidebar.contains(e.target) && 
      !toggle.contains(e.target) &&
      sidebar.classList.contains('mobile-visible')) {
    sidebar.classList.remove('mobile-visible');
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>