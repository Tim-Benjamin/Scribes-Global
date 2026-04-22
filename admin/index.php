<?php
$pageTitle = 'Admin Dashboard - Scribes Global';
$pageDescription = 'Scribes Global Admin Panel';
$pageCSS = 'admin';
$noSplash = true;
$noNav = true;        // Don't show navigation
$noFooter = true;     // Don't show footer content

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

<style>
/* ═══════════════════════════════════════════════════════════
   ADMIN DASHBOARD REDESIGN - Modern UI
   ═══════════════════════════════════��═══════════════════════ */

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

/* ─── Stats Grid (Modern Cards) ────────────────────────– */
.admin-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
.admin-stat-card.green { color: #51CF66; }

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

.admin-stat-card.green .admin-stat-icon {
  background: rgba(81, 207, 102, 0.1);
  color: #51CF66;
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

.status-badge.pending {
  background: rgba(212, 175, 55, 0.15);
  color: var(--primary-gold);
}

.status-badge.upcoming {
  background: rgba(45, 156, 219, 0.15);
  color: #2D9CDB;
}

.status-badge.completed {
  background: rgba(81, 207, 102, 0.15);
  color: #51CF66;
}

/* ─── Button Styles ────────��───────────────────────────– */
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
  margin: 0;
}

/* ─── Responsive Design ────────────────────────────────– */
@media (max-width: 768px) {
  .admin-main {
    padding: 1.25rem;
  }

  .mobile-admin-toggle {
    display: flex;
  }

  .admin-stats-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .admin-page-title {
    font-size: 1.5rem;
  }

  .admin-top-bar {
    margin-bottom: 1.5rem;
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
  .admin-stat-card {
    padding: 1rem;
  }

  .admin-stat-value {
    font-size: 1.75rem;
  }

  .admin-card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .admin-card-body {
    padding: 1rem;
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
        <h1 class="admin-page-title">Dashboard Overview</h1>
        <p class="admin-page-subtitle">
          Welcome back, <strong><?= htmlspecialchars($user['first_name']) ?></strong>! Here's your latest activity.
        </p>
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
        <div style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--gray-600); font-family: var(--font-body);">
          <span style="color: #51CF66; font-weight: 700;">+<?= $stats['new_users'] ?></span> new this month
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
        <div style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--gray-600); font-family: var(--font-body);">
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
        <div style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--gray-600); font-family: var(--font-body);">
          <span style="color: #51CF66; font-weight: 700;">+<?= $stats['month_posts'] ?></span> new this month
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
        <div style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--gray-600); font-family: var(--font-body);">
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
        <div style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--gray-600); font-family: var(--font-body);">
          All time
        </div>
      </div>
    </div>
    
    <!-- Content Grid (2 Column on Desktop) -->
    <div style="display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
      
      <!-- Recent Users -->
      <div class="admin-card" data-aos="fade-up">
        <div class="admin-card-header">
          <h3 class="admin-card-title">
            <i class="fas fa-user-plus"></i>
            Recent Users
          </h3>
          <a href="<?= SITE_URL ?>/admin/users" style="color: var(--primary-purple); font-weight: 700; font-size: 0.9rem; text-decoration: none; font-family: var(--font-body); transition: all var(--transition);" onmouseover="this.style.transform='translateX(4px)'" onmouseout="this.style.transform='none'">
            View All →
          </a>
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
                    <th style="width: 60px;">Actions</th>
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
                      <td style="font-size: 0.85rem;"><?= htmlspecialchars($recentUser['email']) ?></td>
                      <td><span class="role-badge"><?= substr(ucfirst(str_replace('_', ' ', $recentUser['role'])), 0, 7) ?></span></td>
                      <td style="font-size: 0.85rem;"><?= date('M d', strtotime($recentUser['created_at'])) ?></td>
                      <td><span class="status-badge <?= $recentUser['status'] ?>"><?= ucfirst($recentUser['status']) ?></span></td>
                      <td>
                        <div class="action-buttons">
                          <button class="btn-icon btn-view" onclick="viewUser(<?= $recentUser['id'] ?>)" title="View">
                            <i class="fas fa-eye"></i>
                          </button>
                          <button class="btn-icon btn-edit" onclick="editUser(<?= $recentUser['id'] ?>)" title="Edit">
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
          <h3 class="admin-card-title">
            <i class="fas fa-tasks"></i>
            Pending Approvals
          </h3>
        </div>
        <div class="admin-card-body">
          <?php if (count($pendingApprovals) > 0): ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
              <?php foreach ($pendingApprovals as $approval): ?>
                <div style="padding: 1rem; background: var(--gray-100); border-radius: 8px; border-left: 4px solid <?= $approval['type'] === 'media' ? '#2D9CDB' : '#D4AF37' ?>;">
                  <div style="margin-bottom: 0.5rem;">
                    <div style="font-weight: 700; color: var(--dark-bg); font-size: 0.9rem; margin-bottom: 0.25rem; font-family: var(--font-body);">
                      <?= htmlspecialchars(substr($approval['title'], 0, 35)) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--gray-600); font-family: var(--font-body);">
                      <i class="fas fa-<?= $approval['type'] === 'media' ? 'photo-video' : 'praying-hands' ?>"></i>
                      <?= ucfirst($approval['type']) ?> • <?= timeAgo($approval['created_at']) ?>
                    </div>
                  </div>
                  <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                    <button class="btn btn-primary btn-sm" style="flex: 1; font-size: 0.75rem;" onclick="approveItem('<?= $approval['type'] ?>', <?= $approval['id'] ?>)">
                      <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn btn-outline btn-sm" style="font-size: 0.75rem;" onclick="rejectItem('<?= $approval['type'] ?>', <?= $approval['id'] ?>)">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
              <i class="fas fa-check-circle" style="font-size: 2.5rem; color: #51CF66; margin-bottom: 1rem;"></i>
              <p style="color: var(--gray-600); margin: 0; font-family: var(--font-body);">All caught up!</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Recent Events -->
    <div class="admin-card" data-aos="fade-up">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-calendar"></i>
          Recent Events
        </h3>
        <a href="<?= SITE_URL ?>/admin/events" style="color: var(--primary-purple); font-weight: 700; font-size: 0.9rem; text-decoration: none; font-family: var(--font-body); transition: all var(--transition);" onmouseover="this.style.transform='translateX(4px)'" onmouseout="this.style.transform='none'">
          View All →
        </a>
      </div>
      <div class="admin-card-body">
        <?php if (count($recentEvents) > 0): ?>
          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
            <?php foreach ($recentEvents as $event): ?>
              <div style="background: var(--gray-50); border-radius: 10px; overflow: hidden; cursor: pointer; transition: all var(--transition); border: 1px solid var(--gray-200);" onclick="window.location.href='<?= SITE_URL ?>/admin/events'">
                <?php if ($event['hero_image']): ?>
                  <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($event['hero_image']) ?>" alt="Event" style="width: 100%; height: 140px; object-fit: cover;">
                <?php else: ?>
                  <div style="width: 100%; height: 140px; background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                    <i class="fas fa-calendar-alt"></i>
                  </div>
                <?php endif; ?>
                <div style="padding: 1.25rem;">
                  <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem; gap: 0.5rem;">
                    <h4 style="margin: 0; font-size: 1rem; color: var(--dark-bg); font-weight: 700; font-family: var(--font-heading);"><?= htmlspecialchars(substr($event['title'], 0, 30)) ?></h4>
                    <span class="status-badge <?= $event['status'] ?>" style="font-size: 0.7rem; white-space: nowrap;"><?= ucfirst($event['status']) ?></span>
                  </div>
                  <div style="font-size: 0.8rem; color: var(--gray-600); display: flex; flex-direction: column; gap: 0.4rem; font-family: var(--font-body);">
                    <div><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($event['start_date'])) ?></div>
                    <div><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars(substr($event['location'], 0, 25)) ?></div>
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
            <a href="<?= SITE_URL ?>/admin/events/create" class="btn btn-primary" style="margin-top: 1rem;">Create First Event</a>
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