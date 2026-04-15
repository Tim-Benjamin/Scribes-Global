<?php
$pageTitle = 'Past Events Management - Admin - Scribes Global';
$pageDescription = 'Manage past events media';
$pageCSS = 'admin';
$noSplash = true;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

if (!isAdmin()) {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: ' . SITE_URL . '/pages/dashboard');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get filters
$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT e.*, c.name as chapter_name,
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
          FROM events e
          LEFT JOIN chapters c ON e.chapter_id = c.id
          WHERE e.status = 'completed'";
$params = [];

if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($year)) {
    $query .= " AND YEAR(e.start_date) = ?";
    $params[] = $year;
}

$query .= " ORDER BY e.start_date DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM events e WHERE e.status = 'completed'";
$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (e.title LIKE ? OR e.description LIKE ?)";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
}
if (!empty($year)) {
    $countQuery .= " AND YEAR(e.start_date) = ?";
    $countParams[] = $year;
}

$countStmt = $conn->prepare($countQuery);
$countStmt->execute($countParams);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Get available years
$yearsStmt = $conn->query("
    SELECT DISTINCT YEAR(start_date) as year 
    FROM events 
    WHERE status = 'completed'
    ORDER BY year DESC
");
$years = $yearsStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.media-preview {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.media-preview-item {
  aspect-ratio: 1;
  border-radius: var(--radius-md);
  overflow: hidden;
  position: relative;
}

.media-preview-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.media-count {
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  color: white;
  padding: 0.35rem 0.85rem;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
</style>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Past Events Management</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Add photos and videos to past events</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <a href="<?= SITE_URL ?>/admin/events" class="btn btn-outline">
          <i class="fas fa-arrow-left"></i> Back to Events
        </a>
      </div>
    </div>
    
    <!-- Stats Overview -->
    <div class="admin-stats-grid" style="margin-bottom: 2rem;">
      <?php
      $statsQuery = "
        SELECT 
          COUNT(*) as total_past,
          SUM(CASE WHEN gallery IS NOT NULL AND gallery != '[]' THEN 1 ELSE 0 END) as with_photos,
          SUM(CASE WHEN videos IS NOT NULL AND videos != '[]' THEN 1 ELSE 0 END) as with_videos,
          SUM((SELECT COUNT(*) FROM event_registrations WHERE event_id = events.id)) as total_attendees
        FROM events
        WHERE status = 'completed'
      ";
      $statsStmt = $conn->query($statsQuery);
      $pastStats = $statsStmt->fetch();
      ?>
      
      <div class="admin-stat-card purple" data-aos="fade-up">
        <div class="admin-stat-header">
          <div class="admin-stat-icon purple">
            <i class="fas fa-history"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= $pastStats['total_past'] ?></div>
        <div class="admin-stat-label">Past Events</div>
      </div>
      
      <div class="admin-stat-card gold" data-aos="fade-up" data-aos-delay="100">
        <div class="admin-stat-header">
          <div class="admin-stat-icon gold">
            <i class="fas fa-images"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= $pastStats['with_photos'] ?></div>
        <div class="admin-stat-label">Events with Photos</div>
      </div>
      
      <div class="admin-stat-card teal" data-aos="fade-up" data-aos-delay="200">
        <div class="admin-stat-header">
          <div class="admin-stat-icon teal">
            <i class="fas fa-video"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= $pastStats['with_videos'] ?></div>
        <div class="admin-stat-label">Events with Videos</div>
      </div>
      
      <div class="admin-stat-card coral" data-aos="fade-up" data-aos-delay="300">
        <div class="admin-stat-header">
          <div class="admin-stat-icon coral">
            <i class="fas fa-user-check"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($pastStats['total_attendees'] ?? 0) ?></div>
        <div class="admin-stat-label">Total Attendees</div>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
      <input 
        type="text" 
        class="search-input" 
        placeholder="Search past events..." 
        value="<?= htmlspecialchars($search) ?>"
        id="searchInput"
      >
      
      <select class="filter-select" id="yearFilter">
        <option value="">All Years</option>
        <?php foreach ($years as $y): ?>
          <option value="<?= $y['year'] ?>" <?= $year == $y['year'] ? 'selected' : '' ?>>
            <?= $y['year'] ?>
          </option>
        <?php endforeach; ?>
      </select>
      
      <button class="btn btn-outline" onclick="resetFilters()">
        <i class="fas fa-redo"></i> Reset
      </button>
    </div>
    
    <!-- Events Table -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-calendar-check"></i>
          Past Events (<?= number_format($total) ?>)
        </h3>
      </div>
      <div class="admin-card-body">
        <?php if (count($events) > 0): ?>
          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>Date</th>
                  <th>Attendees</th>
                  <th>Media</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($events as $event): 
                  $gallery = !empty($event['gallery']) ? json_decode($event['gallery'], true) : [];
                  $videos = !empty($event['videos']) ? json_decode($event['videos'], true) : [];
                  $photoCount = is_array($gallery) ? count($gallery) : 0;
                  $videoCount = is_array($videos) ? count($videos) : 0;
                ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <?php if ($event['hero_image']): ?>
                          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($event['hero_image']) ?>" alt="Event" class="user-avatar" style="border-radius: var(--radius-md);">
                        <?php else: ?>
                          <div class="user-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; border-radius: var(--radius-md);">
                            <i class="fas fa-calendar"></i>
                          </div>
                        <?php endif; ?>
                        <div class="user-info">
                          <h4><?= htmlspecialchars($event['title']) ?></h4>
                          <p><?= $event['chapter_name'] ?? 'No Chapter' ?></p>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div style="font-size: 0.875rem;">
                        <div style="font-weight: 600; color: var(--dark-bg);">
                          <?= date('M d, Y', strtotime($event['start_date'])) ?>
                        </div>
                        <div style="color: var(--gray-600); font-size: 0.75rem;">
                          <?= date('g:i A', strtotime($event['start_date'])) ?>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div style="font-weight: 700; color: var(--primary-purple);">
                        <?= $event['registration_count'] ?>
                      </div>
                    </td>
                    <td>
                      <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <span class="media-count" style="<?= $photoCount > 0 ? '' : 'background: var(--gray-300); color: var(--gray-600);' ?>">
                          <i class="fas fa-images"></i>
                          <?= $photoCount ?> <?= $photoCount === 1 ? 'Photo' : 'Photos' ?>
                        </span>
                        <span class="media-count" style="<?= $videoCount > 0 ? 'background: linear-gradient(135deg, #EB5757 0%, #FF8787 100%);' : 'background: var(--gray-300); color: var(--gray-600);' ?>">
                          <i class="fas fa-video"></i>
                          <?= $videoCount ?> <?= $videoCount === 1 ? 'Video' : 'Videos' ?>
                        </span>
                      </div>
                      
                      <?php if ($photoCount > 0): ?>
                        <div class="media-preview">
                          <?php 
                          $previewCount = min(3, $photoCount);
                          for ($i = 0; $i < $previewCount; $i++): 
                          ?>
                            <div class="media-preview-item">
                              <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($gallery[$i]) ?>" alt="Preview">
                            </div>
                          <?php endfor; ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <a href="<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>" class="btn-icon btn-view" title="View Event" target="_blank">
                          <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn-icon btn-edit" onclick="openMediaManager(<?= $event['id'] ?>)" title="Manage Media" style="background: rgba(212, 175, 55, 0.1); color: #D4AF37;">
                          <i class="fas fa-photo-video"></i>
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
            <div class="empty-state-icon"><i class="fas fa-calendar-times"></i></div>
            <div class="empty-state-title">No Past Events Found</div>
            <div class="empty-state-text">
              <?php if (!empty($search) || !empty($year)): ?>
                Try adjusting your filters
              <?php else: ?>
                Past events will appear here once they're completed
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- Media Manager Modal -->
<div class="admin-modal" id="mediaManagerModal">
  <div class="admin-modal-content" style="max-width: 900px;">
    <div class="admin-modal-header">
      <h2 id="modalEventTitle">Manage Event Media</h2>
      <button class="admin-modal-close" onclick="closeMediaManager()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="admin-modal-body" id="mediaManagerBody">
      <!-- Content loaded dynamically -->
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

document.getElementById('yearFilter').addEventListener('change', applyFilters);

function applyFilters() {
  const search = document.getElementById('searchInput').value;
  const year = document.getElementById('yearFilter').value;
  
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (year) params.set('year', year);
  
  window.location.href = '<?= SITE_URL ?>/admin/events/past' + (params.toString() ? '?' + params.toString() : '');
}

function resetFilters() {
  window.location.href = '<?= SITE_URL ?>/admin/events/past';
}

function goToPage(page) {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  window.location.href = '<?= SITE_URL ?>/admin/events/past?' + params.toString();
}

// Media Manager
async function openMediaManager(eventId) {
  const modal = document.getElementById('mediaManagerModal');
  const body = document.getElementById('mediaManagerBody');
  
  body.innerHTML = '<div style="text-align: center; padding: 3rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #6B46C1;"></i></div>';
  modal.classList.add('active');
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=get_event_media&event_id=' + eventId);
    const result = await response.json();
    
    if (result.success) {
      document.getElementById('modalEventTitle').textContent = result.event.title + ' - Media Manager';
      body.innerHTML = renderMediaManager(result);
    } else {
      body.innerHTML = '<p style="text-align: center; color: var(--gray-600);">Failed to load event media</p>';
    }
  } catch (error) {
    console.error('Error:', error);
    body.innerHTML = '<p style="text-align: center; color: var(--primary-coral);">An error occurred</p>';
  }
}

function renderMediaManager(data) {
  const event = data.event;
  const gallery = data.gallery || [];
  const videos = data.videos || [];
  
  let html = `
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
      <!-- Photos Section -->
      <div>
        <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fas fa-images" style="color: #6B46C1;"></i>
          Event Photos
          <span style="background: var(--gray-200); color: var(--gray-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.75rem; margin-left: auto;">
            ${gallery.length} / 20
          </span>
        </h3>
        
        <div style="border: 2px dashed var(--gray-300); border-radius: var(--radius-xl); padding: 2rem; text-align: center; background: var(--gray-50); margin-bottom: 1rem; cursor: pointer;" onclick="document.getElementById('galleryUpload').click()">
          <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: var(--gray-400); margin-bottom: 0.5rem;"></i>
          <p style="margin: 0; color: var(--gray-600); font-weight: 600;">Click to upload photos</p>
          <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: var(--gray-500);">Max 20 images, 5MB each</p>
          <input type="file" id="galleryUpload" accept="image/*" multiple style="display: none;" onchange="uploadGalleryImages(${event.id}, this.files)">
        </div>
        
        <div id="galleryGrid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; max-height: 400px; overflow-y: auto;">
          ${gallery.map((img, idx) => `
            <div style="position: relative; aspect-ratio: 1; border-radius: var(--radius-lg); overflow: hidden; border: 2px solid var(--gray-200);">
              <img src="<?= ASSETS_PATH ?>images/uploads/${img}" style="width: 100%; height: 100%; object-fit: cover;">
              <button onclick="deleteGalleryImage(${event.id}, '${img}')" style="position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(235, 87, 87, 0.9); color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          `).join('')}
        </div>
      </div>
      
      <!-- Videos Section -->
      <div>
        <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fas fa-video" style="color: #EB5757;"></i>
          Event Videos
          <span style="background: var(--gray-200); color: var(--gray-700); padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.75rem; margin-left: auto;">
            ${videos.length} / 10
          </span>
        </h3>
        
        <div style="margin-bottom: 1rem;">
          <input type="text" id="videoUrl" placeholder="YouTube video URL (e.g., https://www.youtube.com/watch?v=...)" style="width: 100%; padding: 0.875rem; border: 2px solid var(--gray-300); border-radius: var(--radius-lg); font-family: var(--font-primary); margin-bottom: 0.5rem;">
          <button class="btn btn-primary" style="width: 100%;" onclick="addVideo(${event.id})">
            <i class="fas fa-plus"></i> Add Video
          </button>
          <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: var(--gray-600);">
            <i class="fas fa-info-circle"></i> Paste YouTube video URL or embed link
          </p>
        </div>
        
        <div id="videosGrid" style="display: flex; flex-direction: column; gap: 1rem; max-height: 400px; overflow-y: auto;">
          ${videos.map((video, idx) => `
            <div style="position: relative; border-radius: var(--radius-lg); overflow: hidden; border: 2px solid var(--gray-200);">
              <iframe src="${video}" style="width: 100%; height: 200px; border: none;"></iframe>
              <button onclick="deleteVideo(${event.id}, '${encodeURIComponent(video)}')" style="position: absolute; top: 0.5rem; right: 0.5rem; background: rgba(235, 87, 87, 0.9); color: white; border: none; padding: 0.5rem 1rem; border-radius: var(--radius-md); cursor: pointer; font-weight: 700;">
                <i class="fas fa-trash"></i> Remove
              </button>
            </div>
          `).join('')}
        </div>
      </div>
    </div>
    
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid var(--gray-200); text-align: right;">
      <button class="btn btn-outline" onclick="closeMediaManager()">
        <i class="fas fa-times"></i> Close
      </button>
    </div>
  `;
  
  return html;
}

function closeMediaManager() {
  document.getElementById('mediaManagerModal').classList.remove('active');
}

async function uploadGalleryImages(eventId, files) {
  if (files.length === 0) return;
  
  if (files.length > 20) {
    alert('Maximum 20 images allowed');
    return;
  }
  
  const formData = new FormData();
  formData.append('event_id', eventId);
  
  for (let i = 0; i < files.length; i++) {
    if (files[i].size > 5 * 1024 * 1024) {
      alert(`File ${files[i].name} is too large. Max 5MB per image.`);
      return;
    }
    formData.append('gallery_images[]', files[i]);
  }
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=upload_event_gallery', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('✅ Photos uploaded successfully!');
      openMediaManager(eventId); // Reload
    } else {
      alert('❌ ' + (result.message || 'Failed to upload photos'));
    }
  } catch (error) {
    console.error('Upload error:', error);
    alert('❌ An error occurred while uploading');
  }
}

async function deleteGalleryImage(eventId, imageName) {
  if (!confirm('Are you sure you want to delete this photo?')) return;
  
  try {
    const formData = new FormData();
    formData.append('event_id', eventId);
    formData.append('image_name', imageName);
    
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=delete_event_gallery_image', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      openMediaManager(eventId); // Reload
    } else {
      alert('❌ ' + (result.message || 'Failed to delete photo'));
    }
  } catch (error) {
    console.error('Delete error:', error);
    alert('❌ An error occurred');
  }
}

async function addVideo(eventId) {
  const videoUrl = document.getElementById('videoUrl').value.trim();
  
  if (!videoUrl) {
    alert('Please enter a YouTube video URL');
    return;
  }
  
  // Convert YouTube URL to embed format
  let embedUrl = videoUrl;
  
  // Handle various YouTube URL formats
  if (videoUrl.includes('youtube.com/watch?v=')) {
    const videoId = videoUrl.split('v=')[1].split('&')[0];
    embedUrl = `https://www.youtube.com/embed/${videoId}`;
  } else if (videoUrl.includes('youtu.be/')) {
    const videoId = videoUrl.split('youtu.be/')[1].split('?')[0];
    embedUrl = `https://www.youtube.com/embed/${videoId}`;
  } else if (!videoUrl.includes('youtube.com/embed/')) {
    alert('Please enter a valid YouTube URL');
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('event_id', eventId);
    formData.append('video_url', embedUrl);
    
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=add_event_video', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      document.getElementById('videoUrl').value = '';
      openMediaManager(eventId); // Reload
    } else {
      alert('❌ ' + (result.message || 'Failed to add video'));
    }
  } catch (error) {
    console.error('Add video error:', error);
    alert('❌ An error occurred');
  }
}

async function deleteVideo(eventId, videoUrl) {
  if (!confirm('Are you sure you want to remove this video?')) return;
  
  try {
    const formData = new FormData();
    formData.append('event_id', eventId);
    formData.append('video_url', decodeURIComponent(videoUrl));
    
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=delete_event_video', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      openMediaManager(eventId); // Reload
    } else {
      alert('❌ ' + (result.message || 'Failed to remove video'));
    }
  } catch (error) {
    console.error('Delete error:', error);
    alert('❌ An error occurred');
  }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>