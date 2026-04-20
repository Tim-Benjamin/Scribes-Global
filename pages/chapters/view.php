<?php
$chapterId = $_GET['id'] ?? 0;

if (!$chapterId) {
    header('Location: ' . SITE_URL . '/pages/about/chapters');
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

// Get chapter details
$stmt = $conn->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM users WHERE chapter_id = c.id) as member_count,
           u.first_name as leader_first_name,
           u.last_name as leader_last_name,
           u.email as leader_email
    FROM chapters c
    LEFT JOIN users u ON c.leader_id = u.id
    WHERE c.id = ? AND c.status = 'active'
");
$stmt->execute([$chapterId]);
$chapter = $stmt->fetch();

if (!$chapter) {
    header('Location: ' . SITE_URL . '/pages/about/chapters');
    exit;
}

// Get upcoming events for this chapter
$eventsStmt = $conn->prepare("
    SELECT * FROM events 
    WHERE chapter_id = ? AND status = 'upcoming' AND start_date > NOW()
    ORDER BY start_date ASC
    LIMIT 5
");
$eventsStmt->execute([$chapterId]);
$upcomingEvents = $eventsStmt->fetchAll();

$pageTitle = htmlspecialchars($chapter['name']) . ' - Scribes Global';
$pageDescription = htmlspecialchars($chapter['description'] ?? 'Join the ' . $chapter['name'] . ' community');
$pageCSS = 'chapter-detail';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- <div id="three-canvas-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: -1; pointer-events: none;"></div> -->

<!-- Hero Section -->
<section class="chapter-detail-hero">
  <?php if ($chapter['hero_image']): ?>
    <img 
      src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($chapter['hero_image']) ?>" 
      alt="<?= htmlspecialchars($chapter['name']) ?>" 
      class="chapter-detail-hero-image"
    >
  <?php else: ?>
    <div class="chapter-detail-hero-image" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);"></div>
  <?php endif; ?>
  
  <div class="chapter-detail-hero-overlay">
    <div class="container">
      <div class="chapter-hero-content" data-aos="fade-up">
        <?php if ($chapter['is_campus']): ?>
          <span class="chapter-badge">
            <i class="fas fa-university"></i>
            Campus Chapter
          </span>
        <?php endif; ?>
        
        <h1 class="chapter-hero-title"><?= htmlspecialchars($chapter['name']) ?></h1>
        
        <div class="chapter-hero-meta">
          <div class="chapter-hero-meta-item">
            <i class="fas fa-map-marker-alt"></i>
            <span><?= htmlspecialchars($chapter['location']) ?></span>
          </div>
          
          <?php if ($chapter['is_campus'] && $chapter['campus_university']): ?>
            <div class="chapter-hero-meta-item">
              <i class="fas fa-university"></i>
              <span><?= htmlspecialchars($chapter['campus_university']) ?></span>
            </div>
          <?php endif; ?>
          
          <!-- <div class="chapter-hero-meta-item">
            <i class="fas fa-users"></i>
            <span><?= number_format($chapter['member_count']) ?> Members</span>
          </div> -->
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="chapter-detail-content">
  <div class="container">
    <div class="chapter-content-grid">
      <!-- Main Content -->
      <div class="chapter-main-content">
        <!-- About Section -->
        <div class="chapter-section" data-aos="fade-up">
          <h2 class="chapter-section-title">About <?= htmlspecialchars($chapter['name']) ?></h2>
          <div class="chapter-about-text">
            <?php if ($chapter['about_text']): ?>
              <?= nl2br(htmlspecialchars($chapter['about_text'])) ?>
            <?php elseif ($chapter['description']): ?>
              <p><?= htmlspecialchars($chapter['description']) ?></p>
            <?php else: ?>
              <p>Welcome to <?= htmlspecialchars($chapter['name']) ?>! We're a vibrant community of creative believers passionate about using our gifts for God's glory.</p>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Gallery Section -->
        <?php if ($chapter['gallery']): ?>
          <div class="chapter-section" data-aos="fade-up" data-aos-delay="100">
            <h2 class="chapter-section-title">Gallery</h2>
            <div class="chapter-gallery-grid">
              <?php 
              $gallery = json_decode($chapter['gallery'], true);
              if ($gallery):
                foreach ($gallery as $image):
              ?>
                <div class="gallery-item" onclick="openLightbox('<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($image) ?>')">
                  <img 
                    src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($image) ?>" 
                    alt="<?= htmlspecialchars($chapter['name']) ?> Gallery"
                  >
                </div>
              <?php 
                endforeach;
              endif;
              ?>
            </div>
          </div>
        <?php endif; ?>
        
        <!-- Upcoming Events Section -->
        <div class="chapter-section" data-aos="fade-up" data-aos-delay="200">
          <h2 class="chapter-section-title">Upcoming Events</h2>
          
          <?php if (count($upcomingEvents) > 0): ?>
            <div class="chapter-events-list">
              <?php foreach ($upcomingEvents as $event): ?>
                <div class="chapter-event-card" onclick="window.location.href='<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>'">
                  <div class="event-card-date">
                    <div class="event-date-badge">
                      <div class="event-date-day"><?= date('d', strtotime($event['start_date'])) ?></div>
                      <div class="event-date-month"><?= date('M', strtotime($event['start_date'])) ?></div>
                    </div>
                    <div style="flex: 1;">
                      <h3 class="event-card-title"><?= htmlspecialchars($event['title']) ?></h3>
                      <div class="event-card-meta">
                        <span>
                          <i class="fas fa-clock"></i>
                          <?= date('g:i A', strtotime($event['start_date'])) ?>
                        </span>
                        <span>
                          <i class="fas fa-map-marker-alt"></i>
                          <?= htmlspecialchars($event['location']) ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            
            <a href="<?= SITE_URL ?>/pages/events?chapter=<?= $chapter['id'] ?>" class="btn btn-outline" style="margin-top: 1.5rem;">
              <i class="fas fa-calendar"></i> View All Chapter Events
            </a>
          <?php else: ?>
            <div class="empty-state-inline">
              <i class="fas fa-calendar"></i>
              <h4>No Upcoming Events</h4>
              <p>Check back soon for new events from this chapter</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Sidebar -->
      <div class="chapter-sidebar">
        <!-- Quick Info Card -->
        <div class="chapter-info-card" data-aos="fade-left">
          <h3 class="info-card-title">
            <i class="fas fa-info-circle"></i>
            Chapter Information
          </h3>
          
          <?php if ($chapter['meeting_schedule']): ?>
            <div class="info-item">
              <div class="info-icon purple">
                <i class="fas fa-calendar"></i>
              </div>
              <div class="info-content">
                <h4>Meeting Schedule</h4>
                <p><?= htmlspecialchars($chapter['meeting_schedule']) ?></p>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if ($chapter['leader_first_name']): ?>
            <div class="info-item">
              <div class="info-icon gold">
                <i class="fas fa-user-tie"></i>
              </div>
              <div class="info-content">
                <h4>Chapter Leader</h4>
                <p><?= htmlspecialchars($chapter['leader_first_name'] . ' ' . $chapter['leader_last_name']) ?></p>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if ($chapter['contact_email']): ?>
            <div class="info-item">
              <div class="info-icon green">
                <i class="fas fa-envelope"></i>
              </div>
              <div class="info-content">
                <h4>Contact Email</h4>
                <p><a href="mailto:<?= htmlspecialchars($chapter['contact_email']) ?>"><?= htmlspecialchars($chapter['contact_email']) ?></a></p>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if ($chapter['contact_phone']): ?>
            <div class="info-item">
              <div class="info-icon teal">
                <i class="fas fa-phone"></i>
              </div>
              <div class="info-content">
                <h4>Contact Phone</h4>
                <p><a href="tel:<?= htmlspecialchars($chapter['contact_phone']) ?>"><?= htmlspecialchars($chapter['contact_phone']) ?></a></p>
              </div>
            </div>
          <?php endif; ?>
          
          <button class="join-chapter-btn" onclick="openJoinModal()">
            <i class="fas fa-user-plus"></i>
            Request to Join Chapter
          </button>
        </div>
        
        <!-- Location Map -->
        <?php if ($chapter['latitude'] && $chapter['longitude']): ?>
          <div class="chapter-info-card" data-aos="fade-left" data-aos-delay="100">
            <h3 class="info-card-title">
              <i class="fas fa-map-marked-alt"></i>
              Location
            </h3>
            <div id="chapterMap" style="height: 300px; border-radius: var(--radius-lg); overflow: hidden;"></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Join Modal -->
<div class="join-modal" id="joinModal">
  <div class="join-modal-content">
    <div class="join-modal-header">
      <h2>Join <?= htmlspecialchars($chapter['name']) ?></h2>
      <button class="join-modal-close" onclick="closeJoinModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="join-modal-body">
      <form id="joinForm">
        <input type="hidden" name="chapter_id" value="<?= $chapter['id'] ?>">
        
        <?php if (isLoggedIn()): ?>
          <?php $user = getCurrentUser(); ?>
          <div class="form-row">
            <div class="form-group">
              <label for="first_name" class="form-label">First Name <span style="color: #EB5757;">*</span></label>
              <input 
                type="text" 
                id="first_name" 
                name="first_name" 
                class="form-control" 
                value="<?= htmlspecialchars($user['first_name']) ?>"
                required
              >
            </div>
            
            <div class="form-group">
              <label for="last_name" class="form-label">Last Name <span style="color: #EB5757;">*</span></label>
              <input 
                type="text" 
                id="last_name" 
                name="last_name" 
                class="form-control" 
                value="<?= htmlspecialchars($user['last_name']) ?>"
                required
              >
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="email" class="form-label">Email <span style="color: #EB5757;">*</span></label>
              <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control" 
                value="<?= htmlspecialchars($user['email']) ?>"
                required
              >
            </div>
            
            <div class="form-group">
              <label for="phone" class="form-label">Phone Number</label>
              <input 
                type="tel" 
                id="phone" 
                name="phone" 
                class="form-control" 
                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                placeholder="+233 123 456 789"
              >
            </div>
          </div>
        <?php else: ?>
          <div class="form-row">
            <div class="form-group">
              <label for="first_name" class="form-label">First Name <span style="color: #EB5757;">*</span></label>
              <input 
                type="text" 
                id="first_name" 
                name="first_name" 
                class="form-control" 
                required
              >
            </div>
            
            <div class="form-group">
              <label for="last_name" class="form-label">Last Name <span style="color: #EB5757;">*</span></label>
              <input 
                type="text" 
                id="last_name" 
                name="last_name" 
                class="form-control" 
                required
              >
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="email" class="form-label">Email <span style="color: #EB5757;">*</span></label>
              <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control" 
                required
              >
            </div>
            
            <div class="form-group">
              <label for="phone" class="form-label">Phone Number</label>
              <input 
                type="tel" 
                id="phone" 
                name="phone" 
                class="form-control" 
                placeholder="+233 123 456 789"
              >
            </div>
          </div>
        <?php endif; ?>
        
        <div class="form-group">
          <label for="message" class="form-label">Why do you want to join this chapter?</label>
          <textarea 
            id="message" 
            name="message" 
            class="form-control" 
            rows="4"
            placeholder="Tell us a bit about yourself and why you'd like to join..."
          ></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
          <i class="fas fa-paper-plane"></i> Submit Request
        </button>
      </form>
    </div>
  </div>
</div>

<script>
// Join Modal
function openJoinModal() {
  document.getElementById('joinModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeJoinModal() {
  document.getElementById('joinModal').classList.remove('active');
  document.body.style.overflow = 'auto';
}

// Close modal on overlay click
document.getElementById('joinModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeJoinModal();
  }
});

// Join Form Submit
document.getElementById('joinForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
  
  const formData = new FormData(this);
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/chapters.php?action=submit_join_request', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(result.message);
      closeJoinModal();
      this.reset();
    } else {
      alert(result.message || 'Failed to submit request');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// Lightbox
function openLightbox(src) {
  const lightbox = document.createElement('div');
  lightbox.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 2rem; cursor: pointer;';
  lightbox.onclick = () => lightbox.remove();
  
  const img = document.createElement('img');
  img.src = src;
  img.style.cssText = 'max-width: 100%; max-height: 100%; border-radius: 1rem;';
  
  lightbox.appendChild(img);
  document.body.appendChild(lightbox);
}

// Initialize Map
<?php if ($chapter['latitude'] && $chapter['longitude']): ?>
document.addEventListener('DOMContentLoaded', function() {
  const map = L.map('chapterMap').setView([<?= $chapter['latitude'] ?>, <?= $chapter['longitude'] ?>], 15);
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);
  
  L.marker([<?= $chapter['latitude'] ?>, <?= $chapter['longitude'] ?>])
    .addTo(map)
    .bindPopup('<strong><?= addslashes($chapter['name']) ?></strong><br><?= addslashes($chapter['location']) ?>')
    .openPopup();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>