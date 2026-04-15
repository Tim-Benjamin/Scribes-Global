<?php
$postId = $_GET['id'] ?? 0;

if (!$postId) {
    header('Location: ' . SITE_URL . '/pages/blog');
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/badge-svg.php';

$db = new Database();
$conn = $db->connect();

// Get post details
$stmt = $conn->prepare("
    SELECT 
        bp.*,
        u.first_name,
        u.last_name,
        u.profile_photo,
        u.custom_tag,
        u.bio,
        (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id AND status = 'approved') as comments_count,
        (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as likes_count
    FROM blog_posts bp
    JOIN users u ON bp.author_id = u.id
    WHERE bp.id = ? AND bp.status = 'published'
");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: ' . SITE_URL . '/pages/blog');
    exit;
}

// Increment view count
$viewStmt = $conn->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
$viewStmt->execute([$postId]);
$post['views'] = $post['views'] + 1;

// Check if current user liked the post
$isLiked = false;
$currentUser = isLoggedIn() ? getCurrentUser() : null;
if ($currentUser) {
    $likeCheckStmt = $conn->prepare("SELECT id FROM blog_likes WHERE post_id = ? AND user_id = ?");
    $likeCheckStmt->execute([$postId, $currentUser['id']]);
    $isLiked = $likeCheckStmt->fetch() ? true : false;
}

// Get comments
$commentsStmt = $conn->prepare("
    SELECT 
        bc.*,
        u.first_name,
        u.last_name,
        u.profile_photo,
        u.custom_tag
    FROM blog_comments bc
    JOIN users u ON bc.user_id = u.id
    WHERE bc.post_id = ? AND bc.status = 'approved' AND bc.parent_id IS NULL
    ORDER BY bc.created_at DESC
");
$commentsStmt->execute([$postId]);
$comments = $commentsStmt->fetchAll();

// Get author badges
$authorBadges = getUserBadges($post['author_id']);

// Get related posts
$relatedStmt = $conn->prepare("
    SELECT 
        bp.*,
        u.first_name,
        u.last_name,
        (SELECT COUNT(*) FROM blog_likes WHERE post_id = bp.id) as likes_count
    FROM blog_posts bp
    JOIN users u ON bp.author_id = u.id
    WHERE bp.id != ? AND bp.status = 'published' AND bp.category = ?
    ORDER BY bp.published_at DESC
    LIMIT 3
");
$relatedStmt->execute([$postId, $post['category']]);
$relatedPosts = $relatedStmt->fetchAll();

$pageTitle = htmlspecialchars($post['title']) . ' - Blog - Scribes Global';
$pageDescription = htmlspecialchars($post['excerpt']);
$pageCSS = 'blog';
$noSplash = true;

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.post-detail-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 3rem 2rem;
}

.post-detail-grid {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 3rem;
}

.post-detail-main {
  min-width: 0;
}

.post-back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--gray-600);
  text-decoration: none;
  margin-bottom: 2rem;
  padding: 0.5rem 0;
  transition: all var(--transition-base);
  font-weight: 600;
}

.post-back-link:hover {
  color: #6B46C1;
  gap: 0.75rem;
}

.post-detail-card {
  background: white;
  border-radius: var(--radius-2xl);
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.post-detail-image {
  width: 100%;
  height: 400px;
  object-fit: cover;
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 5rem;
}

.post-detail-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.post-detail-content {
  padding: 3rem;
}

.post-category {
  display: inline-block;
  padding: 0.5rem 1.25rem;
  background: rgba(107, 70, 193, 0.1);
  color: #6B46C1;
  border-radius: var(--radius-full);
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 1.5rem;
}

.post-title {
  font-size: 2.5rem;
  font-weight: 900;
  color: var(--dark-bg);
  line-height: 1.2;
  margin-bottom: 1.5rem;
  font-family: var(--font-heading);
}

.post-meta {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding-bottom: 2rem;
  border-bottom: 2px solid var(--gray-200);
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.post-author {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.post-author-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--gray-300);
}

.post-author-info {
  flex: 1;
}

.post-author-name {
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.post-date {
  font-size: 0.875rem;
  color: var(--gray-500);
}

.post-stats {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  font-size: 0.875rem;
  color: var(--gray-600);
}

.post-stat {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.post-body {
  font-size: 1.125rem;
  line-height: 1.8;
  color: var(--gray-700);
  margin-bottom: 3rem;
}

.post-body p {
  margin-bottom: 1.5rem;
}

.post-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 0;
  border-top: 2px solid var(--gray-200);
  border-bottom: 2px solid var(--gray-200);
}

.post-actions {
  display: flex;
  gap: 1rem;
}

.post-action-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  background: white;
  border: 2px solid var(--gray-300);
  border-radius: var(--radius-lg);
  color: var(--gray-700);
  cursor: pointer;
  transition: all var(--transition-base);
  font-size: 1rem;
  font-weight: 600;
}

.post-action-btn:hover {
  background: rgba(107, 70, 193, 0.05);
  border-color: #6B46C1;
  color: #6B46C1;
  transform: translateY(-2px);
}

.post-action-btn i {
  font-size: 1.25rem;
}

.post-action-btn.likes:hover {
  border-color: #F91880;
  color: #F91880;
  background: rgba(249, 24, 128, 0.05);
}

.post-action-btn.likes.liked {
  border-color: #F91880;
  color: #F91880;
  background: rgba(249, 24, 128, 0.1);
}

.post-action-btn.share:hover {
  border-color: #00BA7C;
  color: #00BA7C;
  background: rgba(0, 186, 124, 0.05);
}

/* Comments Section */
.comments-section {
  padding: 3rem;
  background: var(--gray-50);
  border-radius: var(--radius-2xl);
  margin-top: 3rem;
}

.comments-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 2rem;
}

.comment-form {
  background: white;
  padding: 2rem;
  border-radius: var(--radius-xl);
  margin-bottom: 2rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.comment-textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid var(--gray-300);
  border-radius: var(--radius-lg);
  font-family: var(--font-primary);
  font-size: 0.9375rem;
  resize: vertical;
  min-height: 120px;
  transition: all var(--transition-base);
}

.comment-textarea:focus {
  outline: none;
  border-color: #6B46C1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.comment-submit {
  margin-top: 1rem;
}

.comment {
  background: white;
  padding: 1.5rem;
  border-radius: var(--radius-xl);
  margin-bottom: 1rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  transition: all var(--transition-base);
}

.comment:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.comment-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.comment-avatar {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--gray-300);
}

.comment-author-info {
  flex: 1;
}

.comment-author-name {
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.comment-time {
  font-size: 0.875rem;
  color: var(--gray-500);
}

.comment-text {
  color: var(--gray-700);
  line-height: 1.6;
  margin-bottom: 1rem;
}

.comment-actions {
  display: flex;
  gap: 1rem;
}

.comment-action {
  padding: 0.5rem 1rem;
  background: var(--gray-100);
  border: none;
  border-radius: var(--radius-md);
  color: var(--gray-600);
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all var(--transition-base);
}

.comment-action:hover {
  background: rgba(107, 70, 193, 0.1);
  color: #6B46C1;
}

/* Sidebar */
.post-sidebar {
  position: sticky;
  top: 2rem;
  height: fit-content;
}

.sidebar-widget {
  background: white;
  padding: 2rem;
  border-radius: var(--radius-2xl);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.sidebar-widget-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
}

.author-card {
  text-align: center;
}

.author-card-avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid var(--gray-200);
  margin: 0 auto 1rem;
}

.author-card-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.author-card-bio {
  color: var(--gray-600);
  font-size: 0.9375rem;
  line-height: 1.6;
}

.related-post-item {
  padding: 1rem;
  border-radius: var(--radius-lg);
  transition: all var(--transition-base);
  cursor: pointer;
  margin-bottom: 1rem;
}

.related-post-item:hover {
  background: var(--gray-100);
}

.related-post-title {
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 0.5rem;
  font-size: 0.9375rem;
}

.related-post-meta {
  font-size: 0.8125rem;
  color: var(--gray-500);
}

/* Responsive */
@media (max-width: 1024px) {
  .post-detail-grid {
    grid-template-columns: 1fr;
  }
  
  .post-sidebar {
    position: static;
  }
}

@media (max-width: 768px) {
  .post-detail-container {
    padding: 2rem 1rem;
  }
  
  .post-detail-content {
    padding: 2rem 1.5rem;
  }
  
  .post-title {
    font-size: 1.75rem;
  }
  
  .post-body {
    font-size: 1rem;
  }
  
  .post-actions {
    flex-wrap: wrap;
  }
  
  .post-action-btn {
    flex: 1;
    min-width: 120px;
    justify-content: center;
  }
  
  .comments-section {
    padding: 1.5rem;
  }
}
</style>

<div class="post-detail-container">
  <a href="<?= SITE_URL ?>/pages/blog" class="post-back-link">
    <i class="fas fa-arrow-left"></i>
    Back to Blog
  </a>
  
  <div class="post-detail-grid">
    <!-- Main Content -->
    <div class="post-detail-main">
      <article class="post-detail-card" data-aos="fade-up">
        <div class="post-detail-image">
          <?php if ($post['featured_image']): ?>
            <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
          <?php else: ?>
            <i class="fas fa-newspaper"></i>
          <?php endif; ?>
        </div>
        
        <div class="post-detail-content">
          <span class="post-category"><?= htmlspecialchars($post['category']) ?></span>
          
          <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
          
          <div class="post-meta">
            <div class="post-author">
              <?php if ($post['profile_photo']): ?>
                <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($post['profile_photo']) ?>" class="post-author-avatar" alt="Author">
              <?php else: ?>
                <div class="post-author-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem;">
                  <?= strtoupper(substr($post['first_name'], 0, 1)) ?>
                </div>
              <?php endif; ?>
              
              <div class="post-author-info">
                <div class="post-author-name">
                  <?= renderUserNameWithBadges($post['first_name'], $post['last_name'], $authorBadges, 18) ?>
                </div>
                <div class="post-date">
                  <?= date('F j, Y', strtotime($post['published_at'])) ?>
                  <?php if ($post['reading_time']): ?>
                    · <?= $post['reading_time'] ?> min read
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
            <div class="post-stats">
              <div class="post-stat">
                <i class="far fa-eye"></i>
                <span><?= number_format($post['views']) ?></span>
              </div>
              <div class="post-stat">
                <i class="far fa-heart"></i>
                <span><?= number_format($post['likes_count']) ?></span>
              </div>
              <div class="post-stat">
                <i class="far fa-comment"></i>
                <span><?= number_format($post['comments_count']) ?></span>
              </div>
            </div>
          </div>
          
          <div class="post-body">
            <?= nl2br(htmlspecialchars($post['content'])) ?>
          </div>
          
          <div class="post-actions-bar">
            <div class="post-actions">
              <button class="post-action-btn likes <?= $isLiked ? 'liked' : '' ?>" onclick="toggleLike(<?= $post['id'] ?>, this)">
                <i class="<?= $isLiked ? 'fas' : 'far' ?> fa-heart"></i>
                <span><?= number_format($post['likes_count']) ?> Likes</span>
              </button>
              
              <button class="post-action-btn" onclick="document.getElementById('commentContent').focus()">
                <i class="far fa-comment"></i>
                <span><?= number_format($post['comments_count']) ?> Comments</span>
              </button>
            </div>
            
            <button class="post-action-btn share" onclick="openShareModal(<?= $post['id'] ?>, '<?= addslashes($post['title']) ?>')">
              <i class="fas fa-share"></i>
              <span>Share</span>
            </button>
          </div>
        </div>
      </article>
      
      <!-- Comments Section -->
      <div class="comments-section" data-aos="fade-up" data-aos-delay="100">
        <h2 class="comments-title">
          <i class="far fa-comments"></i>
          Comments (<?= number_format($post['comments_count']) ?>)
        </h2>
        
        <?php if ($currentUser): ?>
          <!-- Comment Form -->
          <div class="comment-form">
            <textarea 
              class="comment-textarea" 
              placeholder="Share your thoughts..."
              id="commentContent"
            ></textarea>
            <button class="btn btn-primary comment-submit" onclick="addComment()">
              <i class="fas fa-paper-plane"></i> Post Comment
            </button>
          </div>
        <?php else: ?>
          <div class="comment-form" style="text-align: center;">
            <p style="color: var(--gray-600); margin-bottom: 1rem;">
              <i class="fas fa-lock"></i> Login to join the conversation
            </p>
            <a href="<?= SITE_URL ?>/auth/login" class="btn btn-primary">
              <i class="fas fa-sign-in-alt"></i> Login
            </a>
          </div>
        <?php endif; ?>
        
        <!-- Comments List -->
        <?php if (count($comments) > 0): ?>
          <?php foreach ($comments as $comment): ?>
            <?php
            $commentBadges = getUserBadges($comment['user_id']);
            $commentTime = timeAgo($comment['created_at']);
            ?>
            
            <div class="comment">
              <div class="comment-header">
                <?php if ($comment['profile_photo']): ?>
                  <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($comment['profile_photo']) ?>" class="comment-avatar" alt="Commenter">
                <?php else: ?>
                  <div class="comment-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">
                    <?= strtoupper(substr($comment['first_name'], 0, 1)) ?>
                  </div>
                <?php endif; ?>
                
                <div class="comment-author-info">
                  <div class="comment-author-name">
                    <?= renderUserNameWithBadges($comment['first_name'], $comment['last_name'], $commentBadges, 14) ?>
                  </div>
                  <div class="comment-time"><?= $commentTime ?></div>
                </div>
              </div>
              
              <div class="comment-text">
                <?= nl2br(htmlspecialchars($comment['content'])) ?>
              </div>
              
              <div class="comment-actions">
                <button class="comment-action">
                  <i class="far fa-heart"></i> Like
                </button>
                <?php if ($currentUser && ($currentUser['id'] == $comment['user_id'] || isAdmin())): ?>
                  <button class="comment-action" onclick="deleteComment(<?= $comment['id'] ?>)" style="color: #EB5757;">
                    <i class="far fa-trash-alt"></i> Delete
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
            <i class="far fa-comments" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
            <p>No comments yet. Be the first to share your thoughts!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Sidebar -->
    <aside class="post-sidebar">
      <!-- Author Card -->
      <div class="sidebar-widget author-card" data-aos="fade-left">
        <h3 class="sidebar-widget-title">About the Author</h3>
        
        <?php if ($post['profile_photo']): ?>
          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($post['profile_photo']) ?>" class="author-card-avatar" alt="Author">
        <?php else: ?>
          <div class="author-card-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 900; font-size: 2.5rem;">
            <?= strtoupper(substr($post['first_name'], 0, 1)) ?>
          </div>
        <?php endif; ?>
        
        <div class="author-card-name">
          <?= renderUserNameWithBadges($post['first_name'], $post['last_name'], $authorBadges, 18) ?>
        </div>
        
        <?php if ($post['bio']): ?>
          <p class="author-card-bio"><?= nl2br(htmlspecialchars($post['bio'])) ?></p>
        <?php endif; ?>
      </div>
      
      <!-- Related Posts -->
      <?php if (count($relatedPosts) > 0): ?>
        <div class="sidebar-widget" data-aos="fade-left" data-aos-delay="100">
          <h3 class="sidebar-widget-title">Related Posts</h3>
          
          <?php foreach ($relatedPosts as $related): ?>
            <div class="related-post-item" onclick="window.location.href='<?= SITE_URL ?>/pages/blog/post?id=<?= $related['id'] ?>'">
              <div class="related-post-title"><?= htmlspecialchars($related['title']) ?></div>
              <div class="related-post-meta">
                By <?= htmlspecialchars($related['first_name'] . ' ' . $related['last_name']) ?> · 
                <?= number_format($related['likes_count']) ?> likes
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</div>

<!-- Share Modal (reuse from blog index) -->
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
let currentPostId = <?= $postId ?>;
let currentPostTitle = '<?= addslashes($post['title']) ?>';

// Toggle like
async function toggleLike(postId, button) {
  <?php if (!$currentUser): ?>
    window.location.href = '<?= SITE_URL ?>/auth/login';
    return;
  <?php endif; ?>
  
  const icon = button.querySelector('i');
  const countSpan = button.querySelector('span');
  const currentCount = parseInt(countSpan.textContent.replace(/[^0-9]/g, ''));
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
        countSpan.textContent = (currentCount - 1).toLocaleString() + ' Likes';
      } else {
        button.classList.add('liked');
        icon.classList.remove('far');
        icon.classList.add('fas');
        countSpan.textContent = (currentCount + 1).toLocaleString() + ' Likes';
      }
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Add comment
async function addComment() {
  const content = document.getElementById('commentContent').value.trim();
  
  if (!content) {
    alert('Please write a comment');
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('post_id', <?= $postId ?>);
    formData.append('content', content);
    
    const response = await fetch('<?= SITE_URL ?>/api/blog.php?action=add_comment', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      window.location.reload();
    } else {
      alert(result.message || 'Failed to add comment');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

// Delete comment
async function deleteComment(commentId) {
  if (!confirm('Are you sure you want to delete this comment?')) return;
  
  try {
    const formData = new FormData();
    formData.append('comment_id', commentId);
    
    const response = await fetch('<?= SITE_URL ?>/api/blog.php?action=delete_comment', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      window.location.reload();
    } else {
      alert(result.message || 'Failed to delete comment');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

// Share modal
function openShareModal(postId, title) {
  const postUrl = encodeURIComponent(window.location.href);
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
  navigator.clipboard.writeText(window.location.href).then(() => {
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
    
    if ($difference < 60) return 'just now';
    elseif ($difference < 3600) return floor($difference / 60) . ' minutes ago';
    elseif ($difference < 86400) return floor($difference / 3600) . ' hours ago';
    elseif ($difference < 604800) return floor($difference / 86400) . ' days ago';
    else return date('M d, Y', $timestamp);
}

require_once __DIR__ . '/../../includes/footer.php';
?>