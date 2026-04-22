<?php
$pageTitle = 'Blog - Scribes Global';
$pageDescription = 'Read the latest posts from Scribes Global';
$pageCSS = 'blog';
$noSplash = true;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/badge-svg.php';

$db = new Database();
$conn = $db->connect();

// Get filter parameters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$query = "
    SELECT 
        bp.*,
        u.first_name,
        u.last_name,
        u.profile_photo,
        u.custom_tag,
        (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id AND status = 'approved') as comments_count,
        (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as likes_count
    FROM blog_posts bp
    JOIN users u ON bp.author_id = u.id
    WHERE bp.status = 'published'
";
$params = [];

if (!empty($search)) {
    $query .= " AND (bp.title LIKE ? OR bp.content LIKE ? OR bp.excerpt LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($category)) {
    $query .= " AND bp.category = ?";
    $params[] = $category;
}

$query .= " ORDER BY bp.published_at DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get categories with counts
$categoriesStmt = $conn->query("
    SELECT category, COUNT(*) as count 
    FROM blog_posts 
    WHERE status = 'published' AND category IS NOT NULL
    GROUP BY category 
    ORDER BY count DESC
");
$categories = $categoriesStmt->fetchAll();

// Get trending topics
$trendingStmt = $conn->query("
    SELECT 
        bp.category,
        COUNT(*) as post_count,
        SUM(bp.views) as total_views
    FROM blog_posts bp
    WHERE bp.status = 'published' 
    AND bp.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY bp.category
    ORDER BY post_count DESC, total_views DESC
    LIMIT 5
");
$trending = $trendingStmt->fetchAll();

$currentUser = isLoggedIn() ? getCurrentUser() : null;

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Hero Section -->
<section class="blog-hero">
  <div class="blog-hero-content">
    <h1>Scribes Global Blog</h1>
    <p>Inspiring stories, teachings, and testimonies from our community</p>
  </div>
</section>

<div class="blog-container">
  <div class="blog-layout-grid">
    <!-- Main Content -->
    <div class="blog-main">
      <!-- Filters -->
      <div class="blog-filters" data-aos="fade-up">
        <div class="blog-search">
          <form method="GET" action="">
            <input 
              type="text" 
              name="search" 
              placeholder="Search articles..."
              value="<?= htmlspecialchars($search) ?>"
            >
          </form>
        </div>
        
        <div class="blog-category-filter">
          <button class="category-btn <?= empty($category) ? 'active' : '' ?>" onclick="filterCategory('')">
            All
          </button>
          <?php foreach ($categories as $cat): ?>
            <button class="category-btn <?= $category === $cat['category'] ? 'active' : '' ?>" onclick="filterCategory('<?= htmlspecialchars($cat['category']) ?>')">
              <?= htmlspecialchars($cat['category']) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
      
      <!-- Blog Grid -->
      <div class="blog-grid">
        <?php if (count($posts) > 0): ?>
          <?php foreach ($posts as $post): ?>
            <?php
            $authorBadges = getUserBadges($post['author_id']);
            $timeAgo = timeAgo($post['published_at']);
            $isLiked = false;
            
            // Check if user has liked (for logged-in users only)
            if ($currentUser) {
                $likeCheckStmt = $conn->prepare("SELECT id FROM blog_likes WHERE post_id = ? AND user_id = ?");
                $likeCheckStmt->execute([$post['id'], $currentUser['id']]);
                $isLiked = $likeCheckStmt->fetch() ? true : false;
            }
            ?>
            
            <article class="blog-card" data-aos="fade-up" onclick="window.location.href='<?= SITE_URL ?>/pages/blog/post?id=<?= $post['id'] ?>'">
              <div class="blog-card-image">
                <?php if ($post['featured_image']): ?>
                  <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                <?php else: ?>
                  <i class="fas fa-newspaper"></i>
                <?php endif; ?>
              </div>
              
              <div class="blog-card-content">
                <span class="blog-card-category"><?= htmlspecialchars($post['category']) ?></span>
                
                <h2 class="blog-card-title"><?= htmlspecialchars($post['title']) ?></h2>
                
                <p class="blog-card-excerpt"><?= htmlspecialchars($post['excerpt']) ?></p>
                
                <div class="blog-card-meta">
                  <div class="blog-card-author">
                    <?php if ($post['profile_photo']): ?>
                      <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($post['profile_photo']) ?>" class="blog-card-avatar" alt="Author">
                    <?php else: ?>
                      <div class="blog-card-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1rem;">
                        <?= strtoupper(substr($post['first_name'], 0, 1)) ?>
                      </div>
                    <?php endif; ?>
                    
                    <div class="blog-card-author-info">
                      <div class="blog-card-author-name">
                        <?= renderUserNameWithBadges($post['first_name'], $post['last_name'], $authorBadges, 14) ?>
                      </div>
                      <div class="blog-card-date"><?= $timeAgo ?></div>
                    </div>
                  </div>
                </div>
                
                <!-- Actions -->
                <div class="blog-card-actions" onclick="event.stopPropagation()">
                  <button class="blog-action-btn views">
                    <i class="far fa-eye"></i>
                    <span><?= number_format($post['views']) ?></span>
                  </button>
                  
                  <button class="blog-action-btn likes <?= $isLiked ? 'liked' : '' ?>" onclick="toggleLike(<?= $post['id'] ?>, this, <?= $currentUser ? 'true' : 'false' ?>)">
                    <i class="<?= $isLiked ? 'fas' : 'far' ?> fa-heart"></i>
                    <span><?= number_format($post['likes_count']) ?></span>
                  </button>
                  
                  <button class="blog-action-btn comments">
                    <i class="far fa-comment"></i>
                    <span><?= number_format($post['comments_count']) ?></span>
                  </button>
                  
                  <button class="blog-action-btn share" onclick="openShareModal(<?= $post['id'] ?>, '<?= addslashes($post['title']) ?>')">
                    <i class="fas fa-share"></i>
                  </button>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-state-icon">
              <i class="far fa-newspaper"></i>
            </div>
            <h2 class="empty-state-title">No posts found</h2>
            <p class="empty-state-text">Try adjusting your search or filters</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Sidebar -->
    <aside class="blog-sidebar">
      <!-- Trending -->
      <?php if (count($trending) > 0): ?>
        <div class="sidebar-widget" data-aos="fade-left">
          <h3 class="sidebar-widget-title">Trending Topics</h3>
          <?php foreach ($trending as $trend): ?>
            <div class="trending-item" onclick="filterCategory('<?= htmlspecialchars($trend['category']) ?>')">
              <div class="trending-category">Trending</div>
              <div class="trending-title">#<?= htmlspecialchars($trend['category']) ?></div>
              <div class="trending-count"><?= number_format($trend['total_views']) ?> views</div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      
      <!-- Categories -->
      <?php if (count($categories) > 0): ?>
        <div class="sidebar-widget" data-aos="fade-left" data-aos-delay="100">
          <h3 class="sidebar-widget-title">Categories</h3>
          <?php 
          $categoryIcons = [
              'Poetry' => 'fa-feather-alt',
              'Worship' => 'fa-music',
              'Teaching' => 'fa-book',
              'Testimony' => 'fa-heart',
              'Prayer' => 'fa-praying-hands',
              'Creative' => 'fa-palette',
              'Leadership' => 'fa-users',
              'Youth' => 'fa-user-friends'
          ];
          
          foreach ($categories as $cat): 
            $icon = $categoryIcons[$cat['category']] ?? 'fa-folder';
          ?>
            <div class="category-item" onclick="filterCategory('<?= htmlspecialchars($cat['category']) ?>')">
              <div class="category-icon">
                <i class="fas <?= $icon ?>"></i>
              </div>
              <div class="category-info">
                <div class="category-name"><?= htmlspecialchars($cat['category']) ?></div>
                <div class="category-count"><?= number_format($cat['count']) ?> posts</div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>

<!-- Share Modal -->
<div class="share-modal" id="shareModal">
  <div class="share-modal-content">
    <div class="share-modal-header">
      <button class="share-modal-close" onclick="closeShareModal()">
        <i class="fas fa-times"></i>
      </button>
      <h3 class="share-modal-title">Share post</h3>
    </div>
    
    <div class="share-options">
      <a href="#" class="share-option" id="shareTwitter" target="_blank">
        <div class="share-option-icon twitter">
          <i class="fab fa-twitter"></i>
        </div>
        <div class="share-option-text">
          <h4>Share to Twitter</h4>
          <p>Post to your timeline</p>
        </div>
      </a>
      
      <a href="#" class="share-option" id="shareFacebook" target="_blank">
        <div class="share-option-icon facebook">
          <i class="fab fa-facebook-f"></i>
        </div>
        <div class="share-option-text">
          <h4>Share to Facebook</h4>
          <p>Post to your wall</p>
        </div>
      </a>
      
      <a href="#" class="share-option" id="shareWhatsApp" target="_blank">
        <div class="share-option-icon whatsapp">
          <i class="fab fa-whatsapp"></i>
        </div>
        <div class="share-option-text">
          <h4>Share to WhatsApp</h4>
          <p>Send via WhatsApp</p>
        </div>
      </a>
      
      <a href="#" class="share-option" id="shareLinkedIn" target="_blank">
        <div class="share-option-icon linkedin">
          <i class="fab fa-linkedin-in"></i>
        </div>
        <div class="share-option-text">
          <h4>Share to LinkedIn</h4>
          <p>Post to your network</p>
        </div>
      </a>
      
      <button class="share-option" onclick="copyLink()">
        <div class="share-option-icon copy">
          <i class="fas fa-link"></i>
        </div>
        <div class="share-option-text">
          <h4>Copy link</h4>
          <p>Copy link to clipboard</p>
        </div>
      </button>
    </div>
  </div>
</div>

<script>
let currentPostId = null;
let currentPostTitle = '';

// Filter by category
function filterCategory(category) {
  const params = new URLSearchParams(window.location.search);
  if (category) {
    params.set('category', category);
  } else {
    params.delete('category');
  }
  window.location.href = '<?= SITE_URL ?>/pages/blog' + (params.toString() ? '?' + params.toString() : '');
}

// Toggle like - No login required
async function toggleLike(postId, button, isLoggedIn) {
  const icon = button.querySelector('i');
  const count = button.querySelector('span');
  const currentCount = parseInt(count.textContent.replace(/,/g, ''));
  const isLiked = button.classList.contains('liked');
  
  try {
    const formData = new FormData();
    formData.append('post_id', postId);
    
    const response = await fetch('<?= SITE_URL ?>/api/blog.php?action=toggle_like', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      if (isLiked) {
        button.classList.remove('liked');
        icon.classList.remove('fas');
        icon.classList.add('far');
        count.textContent = (currentCount - 1).toLocaleString();
      } else {
        button.classList.add('liked');
        icon.classList.remove('far');
        icon.classList.add('fas');
        count.textContent = (currentCount + 1).toLocaleString();
      }
    } else {
      alert(result.message || 'Unable to process like');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred while processing your like');
  }
}

// Share modal
function openShareModal(postId, title) {
  currentPostId = postId;
  currentPostTitle = title;
  
  const postUrl = encodeURIComponent('<?= SITE_URL ?>/pages/blog/post?id=' + postId);
  const postTitle = encodeURIComponent(title);
  
  document.getElementById('shareTwitter').href = `https://twitter.com/intent/tweet?url=${postUrl}&text=${postTitle}`;
  document.getElementById('shareFacebook').href = `https://www.facebook.com/sharer/sharer.php?u=${postUrl}`;
  document.getElementById('shareWhatsApp').href = `https://wa.me/?text=${postTitle}%20${postUrl}`;
  document.getElementById('shareLinkedIn').href = `https://www.linkedin.com/sharing/share-offsite/?url=${postUrl}`;
  
  document.getElementById('shareModal').classList.add('active');
}

function closeShareModal() {
  document.getElementById('shareModal').classList.remove('active');
}

function copyLink() {
  const url = '<?= SITE_URL ?>/pages/blog/post?id=' + currentPostId;
  navigator.clipboard.writeText(url).then(() => {
    alert('Link copied to clipboard!');
    closeShareModal();
  });
}

// Close modal on overlay click
document.getElementById('shareModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeShareModal();
  }
});
</script>

<?php
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 3600) {
        return floor($difference / 60) . ' minutes ago';
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . ' hours ago';
    } elseif ($difference < 604800) {
        return floor($difference / 86400) . ' days ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

require_once __DIR__ . '/../../includes/footer.php';
?>