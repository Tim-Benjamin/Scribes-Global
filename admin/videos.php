<?php
$pageTitle = 'Videos - Admin Dashboard';
$pageDescription = 'Manage videos';
$noSplash = true;
$noNav = true;        // Don't show main navigation
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

$db = new Database();
$conn = $db->connect();

// Get all videos grouped by row
$stmt = $conn->prepare("
    SELECT * FROM homepage_videos 
    ORDER BY row_title ASC, sort_order ASC
");
$stmt->execute();
$allVideos = $stmt->fetchAll();

// Group by row_title
$grouped = [];
foreach ($allVideos as $video) {
    $row = $video['row_title'] ?? 'Featured Videos';
    if (!isset($grouped[$row])) {
        $grouped[$row] = [];
    }
    $grouped[$row][] = $video;
}


?>

<style>
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

/* ─── Videos Container ─────────────────────────────────────– */
.admin-videos-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  margin-top: 2rem;
}

.admin-video-section {
  background: white;
  border: 1px solid var(--gray-200);
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all var(--transition);
}

.admin-video-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 2px solid var(--gray-100);
}

.admin-video-section-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.admin-video-section-title i {
  color: var(--primary-purple);
}

.admin-video-count {
  background: var(--gray-100);
  padding: 0.5rem 1rem;
  border-radius: 100px;
  font-size: 0.875rem;
  color: var(--gray-700);
  font-weight: 600;
}

.admin-videos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.5rem;
}

.admin-video-card {
  background: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  overflow: hidden;
  transition: all var(--transition);
  position: relative;
}

.admin-video-card:hover {
  box-shadow: 0 10px 25px rgba(107, 70, 193, 0.15);
  transform: translateY(-4px);
  border-color: var(--primary-purple);
}

.admin-video-thumbnail {
  position: relative;
  aspect-ratio: 16/9;
  overflow: hidden;
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
}

.admin-video-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--transition);
}

.admin-video-card:hover .admin-video-thumbnail img {
  transform: scale(1.05);
}

.admin-video-play-badge {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 50px;
  height: 50px;
  background: rgba(0, 0, 0, 0.7);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
  opacity: 0;
  transition: opacity var(--transition);
}

.admin-video-card:hover .admin-video-play-badge {
  opacity: 1;
}

.admin-video-info {
  padding: 1rem;
}

.admin-video-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin: 0 0 0.5rem 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.admin-video-date {
  font-size: 0.8rem;
  color: var(--gray-600);
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.admin-video-desc {
  font-size: 0.8rem;
  color: var(--gray-600);
  margin: 0 0 1rem 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.admin-video-status {
  margin-bottom: 1rem;
}

.admin-video-actions {
  display: flex;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--gray-200);
  background: white;
  justify-content: space-between;
  opacity: 0;
  transition: opacity var(--transition);
}

.admin-video-card:hover .admin-video-actions {
  opacity: 1;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  border: 1px solid var(--gray-200);
  background: white;
  color: var(--gray-700);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition);
  font-size: 0.875rem;
}

.btn-icon:hover {
  background: var(--gray-100);
}

.btn-icon.btn-edit:hover {
  color: #2563eb;
  border-color: #2563eb;
}

.btn-icon.btn-delete:hover {
  color: var(--primary-coral);
  border-color: var(--primary-coral);
}

/* ─── Modal ────────────────────────────────────────────────– */
.modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal.active {
  display: flex;
}

.modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
  font-family: var(--font-heading);
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: var(--gray-600);
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition);
}

.modal-close:hover {
  color: var(--dark-bg);
}

#videoForm {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: var(--dark-bg);
  font-size: 0.95rem;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--gray-300);
  border-radius: 6px;
  font-size: 0.95rem;
  font-family: inherit;
  transition: all var(--transition);
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--primary-purple);
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.form-group small {
  display: block;
  margin-top: 0.25rem;
  color: var(--gray-600);
  font-size: 0.8rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid var(--gray-200);
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition);
  font-size: 0.95rem;
}

.btn-primary {
  background: var(--primary-purple);
  color: white;
}

.btn-primary:hover {
  background: #5a3a99;
  transform: translateY(-2px);
}

.btn-outline {
  background: white;
  color: var(--gray-700);
  border: 1px solid var(--gray-300);
}

.btn-outline:hover {
  background: var(--gray-100);
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 100px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.active {
  background: rgba(81, 207, 102, 0.15);
  color: #51CF66;
}

.status-badge.inactive {
  background: rgba(235, 87, 87, 0.15);
  color: var(--primary-coral);
}

/* ─── Empty State ──────────────────────────────────────────– */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
  border: 1px solid var(--gray-200);
}

.empty-state-icon {
  font-size: 4rem;
  color: var(--gray-300);
  margin-bottom: 1rem;
}

.empty-state-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin: 0 0 0.5rem 0;
  font-family: var(--font-heading);
}

.empty-state-text {
  color: var(--gray-600);
  margin-bottom: 1.5rem;
}

/* ─── Responsive Design ────────────────────────────────────– */
@media (max-width: 768px) {
  .admin-main {
    margin-left: 0;
    padding: 1.25rem;
  }

  .admin-videos-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .admin-video-section {
    padding: 1.5rem;
  }

  .admin-page-title {
    font-size: 1.5rem;
  }
}

@media (max-width: 480px) {
  .admin-videos-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .form-actions .btn {
    width: 100%;
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
        <h1 class="admin-page-title">Videos</h1>
        <p class="admin-page-subtitle">Manage videos displayed on the homepage</p>
      </div>
      <div>
        <button class="btn btn-primary" onclick="openAddVideoModal()">
          <i class="fas fa-plus"></i> Add Video
        </button>
      </div>
    </div>
    
    <!-- Videos Container -->
    <div class="admin-videos-container">
      <?php if (count($grouped) > 0): ?>
        <?php foreach ($grouped as $rowTitle => $videos): ?>
          <div class="admin-video-section" data-row="<?= htmlspecialchars($rowTitle) ?>" data-aos="fade-up">
            <div class="admin-video-section-header">
              <h2 class="admin-video-section-title">
                <i class="fas fa-folder-open"></i>
                <?= htmlspecialchars($rowTitle) ?>
              </h2>
              <span class="admin-video-count"><?= count($videos) ?> videos</span>
            </div>
            
            <div class="admin-videos-grid">
              <?php foreach ($videos as $video): ?>
                <div class="admin-video-card" data-id="<?= $video['id'] ?>" data-aos="fade-up">
                  <div class="admin-video-thumbnail">
                    <img src="https://img.youtube.com/vi/<?= htmlspecialchars($video['youtube_url']) ?>/mqdefault.jpg" 
                         alt="<?= htmlspecialchars($video['title']) ?>"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22320%22 height=%22180%22%3E%3Crect fill=%22%236B46C1%22 width=%22320%22 height=%22180%22/%3E%3C/svg%3E'">
                    <div class="admin-video-play-badge">
                      <i class="fas fa-play"></i>
                    </div>
                  </div>
                  
                  <div class="admin-video-info">
                    <h3 class="admin-video-title"><?= htmlspecialchars($video['title']) ?></h3>
                    <p class="admin-video-date">
                      <i class="fas fa-calendar"></i>
                      <?= date('M d, Y', strtotime($video['video_date'])) ?>
                    </p>
                    <p class="admin-video-desc" title="<?= htmlspecialchars($video['description']) ?>">
                      <?= htmlspecialchars(substr($video['description'], 0, 60)) ?>...
                    </p>
                    <div class="admin-video-status">
                      <span class="status-badge <?= $video['status'] ?>">
                        <?= ucfirst($video['status']) ?>
                      </span>
                    </div>
                  </div>
                  
                  <div class="admin-video-actions">
                    <button class="btn-icon btn-edit" onclick="editVideo(<?= $video['id'] ?>, event)" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-delete" onclick="deleteVideo(<?= $video['id'] ?>, event)" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-state-icon">
            <i class="fas fa-video"></i>
          </div>
          <h2 class="empty-state-title">No Videos Yet</h2>
          <p class="empty-state-text">Start by adding your first video to the homepage</p>
          <button class="btn btn-primary" onclick="openAddVideoModal()">
            <i class="fas fa-plus"></i> Add First Video
          </button>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Add/Edit Video Modal -->
<div class="modal" id="videoModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 class="modal-title" id="videoModalTitle">Add Video</h2>
      <button class="modal-close" onclick="closeVideoModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <form id="videoForm" onsubmit="submitVideo(event)">
      <div class="form-group">
        <label for="videoYoutubeUrl">YouTube URL or Video ID *</label>
        <input type="text" id="videoYoutubeUrl" placeholder="https://youtube.com/watch?v=..." required>
        <small>Paste the full URL or just the video ID</small>
      </div>
      
      <div class="form-group">
        <label for="videoTitle">Title *</label>
        <input type="text" id="videoTitle" placeholder="Video title" required>
      </div>
      
      <div class="form-group">
        <label for="videoDescription">Description</label>
        <textarea id="videoDescription" placeholder="Brief description..." rows="4"></textarea>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="videoDate">Date</label>
          <input type="date" id="videoDate" value="<?= date('Y-m-d') ?>">
        </div>
        
        <div class="form-group">
          <label for="videoRowTitle">Row/Category</label>
          <input type="text" id="videoRowTitle" placeholder="e.g., Featured Videos" value="Featured Videos">
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="videoStatus">Status</label>
          <select id="videoStatus">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="videoSortOrder">Sort Order</label>
          <input type="number" id="videoSortOrder" placeholder="0" value="0" min="0">
        </div>
      </div>
      
      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="closeVideoModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Video</button>
      </div>
    </form>
  </div>
</div>

<script>
let editingVideoId = null;

function openAddVideoModal() {
  editingVideoId = null;
  document.getElementById('videoModalTitle').textContent = 'Add Video';
  document.getElementById('videoForm').reset();
  document.getElementById('videoYoutubeUrl').value = '';
  document.getElementById('videoTitle').value = '';
  document.getElementById('videoDescription').value = '';
  document.getElementById('videoDate').value = new Date().toISOString().split('T')[0];
  document.getElementById('videoRowTitle').value = 'Featured Videos';
  document.getElementById('videoStatus').value = 'active';
  document.getElementById('videoSortOrder').value = '0';
  document.getElementById('videoModal').classList.add('active');
}

function editVideo(id, event) {
  event.stopPropagation();
  editingVideoId = id;
  
  // Get video data via API
  fetch('<?= SITE_URL ?>/api/videos.php?action=get&id=' + id)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const video = data.data;
        document.getElementById('videoModalTitle').textContent = 'Edit Video';
        document.getElementById('videoYoutubeUrl').value = video.youtube_url;
        document.getElementById('videoTitle').value = video.title;
        document.getElementById('videoDescription').value = video.description;
        document.getElementById('videoDate').value = video.video_date;
        document.getElementById('videoRowTitle').value = video.row_title;
        document.getElementById('videoStatus').value = video.status;
        document.getElementById('videoSortOrder').value = video.sort_order;
        document.getElementById('videoModal').classList.add('active');
      }
    });
}

function closeVideoModal() {
  document.getElementById('videoModal').classList.remove('active');
  editingVideoId = null;
}

async function submitVideo(e) {
  e.preventDefault();
  
  const data = {
    youtube_url: document.getElementById('videoYoutubeUrl').value,
    title: document.getElementById('videoTitle').value,
    description: document.getElementById('videoDescription').value,
    video_date: document.getElementById('videoDate').value,
    row_title: document.getElementById('videoRowTitle').value,
    status: document.getElementById('videoStatus').value,
    sort_order: parseInt(document.getElementById('videoSortOrder').value)
  };
  
  if (editingVideoId) {
    data.id = editingVideoId;
  }
  
  const action = editingVideoId ? 'update' : 'add';
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/videos.php?action=' + action, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(result.message);
      closeVideoModal();
      location.reload();
    } else {
      alert(result.message || 'An error occurred');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

function deleteVideo(id, event) {
  event.stopPropagation();
  
  if (!confirm('Are you sure you want to delete this video?')) return;
  
  fetch('<?= SITE_URL ?>/api/videos.php?action=delete', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('Video deleted successfully');
      location.reload();
    } else {
      alert(data.message || 'Failed to delete video');
    }
  });
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