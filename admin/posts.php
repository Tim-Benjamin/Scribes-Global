<?php
$pageTitle = 'Posts Management - Admin - Scribes Global';
$pageDescription = 'Manage blog posts';
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

// Get statistics - FIXED
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

<div class="admin-layout">
  <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Blog Posts Management</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Create and manage all blog posts</p>
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
                          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($post['featured_image']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: var(--radius-md);">
                        <?php else: ?>
                          <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            <i class="fas fa-newspaper"></i>
                          </div>
                        <?php endif; ?>
                        <div style="max-width: 300px;">
                          <div style="font-weight: 700; color: var(--dark-bg); margin-bottom: 0.25rem;">
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
                      <span style="padding: 0.35rem 0.85rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; background: rgba(107, 70, 193, 0.1); color: #6B46C1;">
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>