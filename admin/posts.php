<?php
$pageTitle = 'Posts Management - Admin - Scribes Global';
$pageDescription = 'Manage blog posts';
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
$category = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$query = "
    SELECT 
        bp.*,
        u.first_name,
        u.last_name,
        (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id) as comments_count,
        (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as likes_count
    FROM blog_posts bp
    JOIN users u ON bp.author_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (bp.title LIKE ? OR bp.content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($status)) {
    $query .= " AND bp.status = ?";
    $params[] = $status;
}

if (!empty($category)) {
    $query .= " AND bp.category = ?";
    $params[] = $category;
}

$query .= " ORDER BY bp.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM blog_posts WHERE 1=1";
$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (title LIKE ? OR content LIKE ?)";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
}
if (!empty($status)) {
    $countQuery .= " AND status = ?";
    $countParams[] = $status;
}
if (!empty($category)) {
    $countQuery .= " AND category = ?";
    $countParams[] = $category;
}

$countStmt = $conn->prepare($countQuery);
$countStmt->execute($countParams);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_posts,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_posts,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_posts,
        COALESCE(SUM(views), 0) as total_views,
        COALESCE(SUM(likes), 0) as total_likes
    FROM blog_posts
";
$statsStmt = $conn->query($statsQuery);
$stats = $statsStmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════
   ADMIN POSTS PAGE - MODERN UI
   ═══════════════════════════════════════════════════════════ */

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

/* ─── Stats Grid ─────────────────────────────────────────– */
.admin-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
.admin-stat-card.green { color: #51CF66; }
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

.admin-stat-card.green .admin-stat-icon {
  background: rgba(81, 207, 102, 0.1);
  color: #51CF66;
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

/* ─── Filters Bar ─────────────────────────────────────────– */
.filters-bar {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  align-items: center;
}

.search-input,
.filter-select {
  padding: 0.875rem 1.25rem;
  border: 1px solid var(--gray-200);
  border-radius: 10px;
  font-family: var(--font-body);
  font-size: 0.95rem;
  background: white;
  transition: all var(--transition);
  min-height: 44px;
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

/* ─── Table Styles ──────────────��──────────────────────────– */
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

.status-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-badge.published {
  background: rgba(81, 207, 102, 0.15);
  color: #51CF66;
}

.status-badge.draft {
  background: rgba(212, 175, 55, 0.15);
  color: var(--primary-gold);
}

/* ─── Action Buttons ────────────────────────────────────────– */
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
  text-decoration: none;
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

/* ─── Pagination ────────────────────────────────────────────– */
.pagination {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  margin-top: 2rem;
  flex-wrap: wrap;
}

.pagination button,
.pagination span {
  padding: 0.625rem 1rem;
  border-radius: 6px;
  border: 1px solid var(--gray-200);
  background: white;
  color: var(--gray-700);
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all var(--transition);
}

.pagination button:hover {
  border-color: var(--primary-purple);
  background: var(--gray-100);
  color: var(--primary-purple);
}

.pagination button.active {
  background: var(--primary-purple);
  color: white;
  border-color: var(--primary-purple);
}

.pagination span {
  padding: 0.625rem 0.75rem;
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
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--gray-700);
  margin: 0 0 0.5rem 0;
}

.empty-state-text {
  color: var(--gray-600);
  margin: 0;
}

/* ─── Responsive Design ────────────────────────────────────– */
@media (max-width: 1024px) {
  .admin-stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
  }

  .data-table {
    font-size: 0.85rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
  }
}

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
    gap: 1rem;
  }

  .admin-page-title {
    font-size: 1.5rem;
  }

  .admin-top-bar {
    margin-bottom: 1.5rem;
  }

  .filters-bar {
    flex-direction: column;
    gap: 0.75rem;
  }

  .search-input,
  .filter-select {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .admin-stat-card {
    padding: 1rem;
  }

  .admin-stat-value {
    font-size: 1.5rem;
  }

  .admin-card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .admin-card-body {
    padding: 1rem;
  }

  .admin-stats-grid {
    grid-template-columns: 1fr;
  }

  .data-table {
    font-size: 0.75rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.5rem 0.25rem;
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
        <h1 class="admin-page-title">Blog Posts Management</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem; font-family: var(--font-body);">Create and manage all blog posts</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <a href="<?= SITE_URL ?>/admin/posts/create" class="btn btn-primary">
          <i class="fas fa-plus"></i> Create Post
        </a>
      </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="admin-stats-grid">
      <div class="admin-stat-card purple" data-aos="fade-up">
        <div class="admin-stat-header">
          <div class="admin-stat-icon purple">
            <i class="fas fa-newspaper"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['total_posts']) ?></div>
        <div class="admin-stat-label">Total Posts</div>
      </div>
      
      <div class="admin-stat-card green" data-aos="fade-up" data-aos-delay="100">
        <div class="admin-stat-header">
          <div class="admin-stat-icon green">
            <i class="fas fa-check-circle"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['published_posts']) ?></div>
        <div class="admin-stat-label">Published</div>
      </div>
      
      <div class="admin-stat-card gold" data-aos="fade-up" data-aos-delay="200">
        <div class="admin-stat-header">
          <div class="admin-stat-icon gold">
            <i class="fas fa-edit"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['draft_posts']) ?></div>
        <div class="admin-stat-label">Drafts</div>
      </div>
      
      <div class="admin-stat-card teal" data-aos="fade-up" data-aos-delay="300">
        <div class="admin-stat-header">
          <div class="admin-stat-icon teal">
            <i class="fas fa-eye"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['total_views']) ?></div>
        <div class="admin-stat-label">Total Views</div>
      </div>
      
      <div class="admin-stat-card coral" data-aos="fade-up" data-aos-delay="400">
        <div class="admin-stat-header">
          <div class="admin-stat-icon coral">
            <i class="fas fa-heart"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['total_likes']) ?></div>
        <div class="admin-stat-label">Total Likes</div>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
      <input 
        type="text" 
        class="search-input" 
        placeholder="Search posts..." 
        value="<?= htmlspecialchars($search) ?>"
        id="searchInput"
      >
      
      <select class="filter-select" id="statusFilter">
        <option value="">All Status</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
      
      <select class="filter-select" id="categoryFilter">
        <option value="">All Categories</option>
        <option value="Poetry" <?= $category === 'Poetry' ? 'selected' : '' ?>>Poetry</option>
        <option value="Worship" <?= $category === 'Worship' ? 'selected' : '' ?>>Worship</option>
        <option value="Teaching" <?= $category === 'Teaching' ? 'selected' : '' ?>>Teaching</option>
        <option value="Testimony" <?= $category === 'Testimony' ? 'selected' : '' ?>>Testimony</option>
        <option value="Prayer" <?= $category === 'Prayer' ? 'selected' : '' ?>>Prayer</option>
        <option value="Creative" <?= $category === 'Creative' ? 'selected' : '' ?>>Creative</option>
        <option value="Leadership" <?= $category === 'Leadership' ? 'selected' : '' ?>>Leadership</option>
        <option value="Youth" <?= $category === 'Youth' ? 'selected' : '' ?>>Youth</option>
      </select>
      
      <button class="btn btn-outline" onclick="resetFilters()">
        <i class="fas fa-redo"></i> Reset
      </button>
    </div>
    
    <!-- Posts Table -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-newspaper"></i>
          All Posts (<?= number_format($total) ?>)
        </h3>
      </div>
      <div class="admin-card-body">
        <?php if (count($posts) > 0): ?>
          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Post</th>
                  <th>Author</th>
                  <th>Category</th>
                  <th>Status</th>
                  <th>Stats</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($posts as $post): ?>
                  <tr>
                    <td>
                      <div style="display: flex; align-items: center; gap: 1rem;">
                        <?php if ($post['featured_image']): ?>
                          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($post['featured_image']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <?php else: ?>
                          <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; flex-shrink: 0;">
                            <i class="fas fa-newspaper"></i>
                          </div>
                        <?php endif; ?>
                        <div style="max-width: 300px;">
                          <div style="font-weight: 700; color: var(--dark-bg); margin-bottom: 0.25rem; font-family: var(--font-heading);">
                            <?= htmlspecialchars($post['title']) ?>
                          </div>
                          <div style="font-size: 0.875rem; color: var(--gray-600);">
                            <?= htmlspecialchars(substr($post['excerpt'], 0, 60)) ?>...
                          </div>
                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></td>
                    <td>
                      <span style="padding: 0.35rem 0.85rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; background: rgba(107, 70, 193, 0.1); color: #6B46C1;">
                        <?= htmlspecialchars($post['category']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="status-badge <?= $post['status'] ?>">
                        <?= ucfirst($post['status']) ?>
                      </span>
                    </td>
                    <td>
                      <div style="font-size: 0.875rem; color: var(--gray-600);">
                        <div><i class="far fa-eye"></i> <?= number_format($post['views']) ?></div>
                        <div><i class="far fa-heart"></i> <?= number_format($post['likes_count']) ?></div>
                        <div><i class="far fa-comment"></i> <?= number_format($post['comments_count']) ?></div>
                      </div>
                    </td>
                    <td><?= date('M d, Y', strtotime($post['created_at'])) ?></td>
                    <td>
                      <div class="action-buttons">
                        <a href="<?= SITE_URL ?>/pages/blog/post?id=<?= $post['id'] ?>" class="btn-icon btn-view" title="View Post" target="_blank">
                          <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?= SITE_URL ?>/admin/posts/edit?id=<?= $post['id'] ?>" class="btn-icon btn-edit" title="Edit Post">
                          <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn-icon btn-delete" onclick="deletePost(<?= $post['id'] ?>, '<?= addslashes($post['title']) ?>')" title="Delete Post">
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
            <div class="empty-state-icon"><i class="fas fa-newspaper"></i></div>
            <div class="empty-state-title">No Posts Found</div>
            <div class="empty-state-text">Create your first blog post to get started</div>
            <a href="<?= SITE_URL ?>/admin/posts/create" class="btn btn-primary" style="margin-top: 1rem;">
              <i class="fas fa-plus"></i> Create Post
            </a>
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

// Filters
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
});

document.getElementById('statusFilter').addEventListener('change', applyFilters);
document.getElementById('categoryFilter').addEventListener('change', applyFilters);

function applyFilters() {
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;
  const category = document.getElementById('categoryFilter').value;
  
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  if (category) params.set('category', category);
  
  window.location.href = '<?= SITE_URL ?>/admin/posts' + (params.toString() ? '?' + params.toString() : '');
}

function resetFilters() {
  window.location.href = '<?= SITE_URL ?>/admin/posts';
}

function goToPage(page) {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  window.location.href = '<?= SITE_URL ?>/admin/posts?' + params.toString();
}

async function deletePost(postId, title) {
  if (!confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('post_id', postId);
    
    const response = await fetch('<?= SITE_URL ?>/api/blog.php?action=delete_post', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Post deleted successfully');
      window.location.reload();
    } else {
      alert(result.message || 'Failed to delete post');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

// Initialize AOS
AOS.init({
  duration: 800,
  easing: 'ease-in-out',
  once: true,
  offset: 100
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>