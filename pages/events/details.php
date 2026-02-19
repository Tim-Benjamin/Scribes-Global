<?php
$eventId = $_GET['id'] ?? 0;

if (!$eventId) {
    header('Location: ' . SITE_URL . '/pages/events');
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

// Get event details
$stmt = $conn->prepare("
    SELECT e.*, c.name as chapter_name, c.location as chapter_location,
           (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count,
           u.first_name as creator_first_name, u.last_name as creator_last_name
    FROM events e
    LEFT JOIN chapters c ON e.chapter_id = c.id
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.id = ?
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: ' . SITE_URL . '/pages/events');
    exit;
}

// Check if user is already registered
$isRegistered = false;
if (isLoggedIn()) {
    $user = getCurrentUser();
    $regCheckStmt = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $regCheckStmt->execute([$eventId, $user['id']]);
    $isRegistered = $regCheckStmt->fetch() ? true : false;
}

// Get related events
$relatedStmt = $conn->prepare("
    SELECT * FROM events 
    WHERE id != ? AND status = 'upcoming' AND start_date > NOW()
    ORDER BY start_date ASC
    LIMIT 3
");
$relatedStmt->execute([$eventId]);
$relatedEvents = $relatedStmt->fetchAll();

$pageTitle = htmlspecialchars($event['title']) . ' - Events - Scribes Global';
$pageDescription = htmlspecialchars(substr($event['description'], 0, 150));
$pageCSS = 'events';

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* Additional inline styles for event details */
.share-buttons {
  display: flex;
  gap: 0.5rem;
}

.share-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--gray-300);
  background: white;
  color: var(--gray-600);
  cursor: pointer;
  transition: all var(--transition-base);
}

.share-btn:hover {
  border-color: var(--primary-purple);
  background: var(--primary-purple);
  color: white;
  transform: translateY(-2px);
}

#map {
  height: 300px;
  border-radius: var(--radius-lg);
  margin-top: 1rem;
}

.registration-success {
  background: linear-gradient(135deg, #51CF66 0%, #2F9E44 100%);
  color: white;
  padding: 2rem;
  border-radius: var(--radius-xl);
  text-align: center;
  margin-bottom: 2rem;
}

.registration-success h3 {
  color: white;
  margin-bottom: 0.5rem;
}
</style>

<!-- Event Hero -->
<section class="event-detail-hero">
  <img 
    src="<?= $event['hero_image'] ? ASSETS_PATH . 'images/uploads/' . $event['hero_image'] : ASSETS_PATH . 'images/placeholder-event.jpg' ?>" 
    alt="<?= htmlspecialchars($event['title']) ?>" 
    class="event-detail-hero-image"
  >
  <div class="event-detail-hero-overlay">
    <div class="container">
      <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
        <div>
          <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <span class="event-category" style="background: rgba(255,255,255,0.2); color: white;">
              <i class="fas fa-<?= $event['event_type'] === 'virtual' ? 'video' : ($event['event_type'] === 'hybrid' ? 'globe' : 'map-marker-alt') ?>"></i>
              <?= ucfirst($event['event_type']) ?>
            </span>
            <?php if ($event['featured']): ?>
              <span class="event-category" style="background: var(--primary-gold); color: var(--dark-bg);">
                <i class="fas fa-star"></i> Featured
              </span>
            <?php endif; ?>
          </div>
          <h1 style="color: white; font-size: 2.5rem; margin-bottom: 1rem;">
            <?= htmlspecialchars($event['title']) ?>
          </h1>
          <div style="display: flex; gap: 2rem; flex-wrap: wrap; color: rgba(255,255,255,0.9);">
            <div>
              <i class="fas fa-calendar"></i>
              <?= date('l, F j, Y', strtotime($event['start_date'])) ?>
            </div>
            <div>
              <i class="fas fa-clock"></i>
              <?= date('g:i A', strtotime($event['start_date'])) ?>
            </div>
            <div>
              <i class="fas fa-map-marker-alt"></i>
              <?= htmlspecialchars($event['location']) ?>
            </div>
          </div>
        </div>
        
        <div class="share-buttons">
          <button class="share-btn" onclick="shareEvent('facebook')" title="Share on Facebook">
            <i class="fab fa-facebook-f"></i>
          </button>
          <button class="share-btn" onclick="shareEvent('twitter')" title="Share on Twitter">
            <i class="fab fa-twitter"></i>
          </button>
          <button class="share-btn" onclick="shareEvent('whatsapp')" title="Share on WhatsApp">
            <i class="fab fa-whatsapp"></i>
          </button>
          <button class="share-btn" onclick="copyLink()" title="Copy Link">
            <i class="fas fa-link"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Event Content -->
<section class="event-detail-content">
  <div class="container">
    <div class="event-detail-grid">
      <!-- Main Content -->
      <div class="event-detail-main">
        <?php if ($isRegistered): ?>
          <div class="registration-success" data-aos="fade-up">
            <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h3>You're Registered! 🎉</h3>
            <p style="margin: 0;">We've sent a confirmation email with event details. See you there!</p>
          </div>
        <?php endif; ?>
        
        <h2 style="margin-bottom: 1.5rem;">About This Event</h2>
        <div style="color: var(--gray-700); line-height: 1.8; font-size: 1.125rem;">
          <?= nl2br(htmlspecialchars($event['description'])) ?>
        </div>
        
        <?php if ($event['latitude'] && $event['longitude']): ?>
          <h2 style="margin-top: 3rem; margin-bottom: 1.5rem;">Location</h2>
          <div id="map"></div>
        <?php endif; ?>
        
        <?php if ($event['gallery']): ?>
          <h2 style="margin-top: 3rem; margin-bottom: 1.5rem;">Event Gallery</h2>
          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <?php 
            $gallery = json_decode($event['gallery'], true);
            if ($gallery):
              foreach ($gallery as $image):
            ?>
              <img 
                src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($image) ?>" 
                alt="Event Image" 
                style="width: 100%; height: 200px; object-fit: cover; border-radius: var(--radius-lg); cursor: pointer;"
                onclick="openLightbox(this.src)"
              >
            <?php 
              endforeach;
            endif;
            ?>
          </div>
        <?php endif; ?>
        
        <!-- Related Events -->
        <?php if (count($relatedEvents) > 0): ?>
          <h2 style="margin-top: 3rem; margin-bottom: 1.5rem;">Related Events</h2>
          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
            <?php foreach ($relatedEvents as $related): ?>
              <div class="card" onclick="window.location.href='<?= SITE_URL ?>/pages/events/details?id=<?= $related['id'] ?>'" style="cursor: pointer;">
                <img 
                  src="<?= $related['hero_image'] ? ASSETS_PATH . 'images/uploads/' . $related['hero_image'] : ASSETS_PATH . 'images/placeholder-event.jpg' ?>" 
                  alt="<?= htmlspecialchars($related['title']) ?>" 
                  style="width: 100%; height: 150px; object-fit: cover;"
                >
                <div style="padding: 1.5rem;">
                  <h4 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($related['title']) ?></h4>
                  <p style="font-size: 0.875rem; color: var(--gray-600); margin: 0;">
                    <i class="fas fa-calendar"></i>
                    <?= date('M j, Y', strtotime($related['start_date'])) ?>
                  </p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      
      <!-- Sidebar -->
      <div class="event-detail-sidebar">
        <!-- Event Info Card -->
        <div class="event-info-card" data-aos="fade-left">
          <h3 style="margin-bottom: 1.5rem;">Event Details</h3>
          
          <div class="event-info-item">
            <div class="event-info-icon">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="event-info-details">
              <h4>Date & Time</h4>
              <p><?= date('F j, Y', strtotime($event['start_date'])) ?></p>
              <p style="font-size: 0.875rem; font-weight: 400;"><?= date('g:i A', strtotime($event['start_date'])) ?></p>
            </div>
          </div>
          
          <div class="event-info-item">
            <div class="event-info-icon">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="event-info-details">
              <h4>Location</h4>
              <p><?= htmlspecialchars($event['location']) ?></p>
            </div>
          </div>
          
          <?php if ($event['chapter_name']): ?>
            <div class="event-info-item">
              <div class="event-info-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="event-info-details">
                <h4>Chapter</h4>
                <p><?= htmlspecialchars($event['chapter_name']) ?></p>
                <p style="font-size: 0.875rem; font-weight: 400;"><?= htmlspecialchars($event['chapter_location']) ?></p>
              </div>
            </div>
          <?php endif; ?>
          
          <div class="event-info-item">
            <div class="event-info-icon">
              <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="event-info-details">
              <h4>Registration</h4>
              <p>
                <?= $event['registration_count'] ?> registered
                <?php if ($event['registration_limit']): ?>
                  / <?= $event['registration_limit'] ?> spots
                <?php endif; ?>
              </p>
            </div>
          </div>
          
          <?php if ($event['virtual_link'] && $event['event_type'] !== 'physical'): ?>
            <div class="event-info-item">
              <div class="event-info-icon">
                <i class="fas fa-video"></i>
              </div>
              <div class="event-info-details">
                <h4>Virtual Link</h4>
                <p style="font-size: 0.875rem;">Link will be sent upon registration</p>
              </div>
            </div>
          <?php endif; ?>
          
          <?php if (!$isRegistered && $event['registration_enabled']): ?>
            <?php
            $isFull = $event['registration_limit'] && $event['registration_count'] >= $event['registration_limit'];
            $isPast = strtotime($event['start_date']) < time();
            ?>
            
            <?php if ($isFull): ?>
              <button class="btn btn-secondary" style="width: 100%; margin-top: 1.5rem;" disabled>
                <i class="fas fa-users"></i> Event Full
              </button>
            <?php elseif ($isPast): ?>
              <button class="btn btn-secondary" style="width: 100%; margin-top: 1.5rem;" disabled>
                <i class="fas fa-clock"></i> Event Ended
              </button>
            <?php else: ?>
              <button class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;" onclick="openRegistrationModal()">
                <i class="fas fa-user-plus"></i> Register for Event
              </button>
            <?php endif; ?>
          <?php elseif ($isRegistered): ?>
            <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(81, 207, 102, 0.1); border-radius: var(--radius-md); text-align: center;">
              <i class="fas fa-check-circle" style="color: #2F9E44; font-size: 2rem; margin-bottom: 0.5rem;"></i>
              <p style="color: #2F9E44; font-weight: 600; margin: 0;">You're Registered!</p>
            </div>
          <?php endif; ?>
          
          <button class="btn btn-outline" style="width: 100%; margin-top: 1rem;" onclick="addToCalendar()">
            <i class="fas fa-calendar-plus"></i> Add to Calendar
          </button>
        </div>
        
        <!-- Organizer Card -->
        <?php if ($event['creator_first_name']): ?>
          <div class="event-info-card" data-aos="fade-left" data-aos-delay="100">
            <h3 style="margin-bottom: 1.5rem;">Organized By</h3>
            <div style="text-align: center;">
              <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary-purple); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; margin: 0 auto 1rem;">
                <?= strtoupper(substr($event['creator_first_name'], 0, 1)) ?>
              </div>
              <h4 style="margin-bottom: 0.5rem;">
                <?= htmlspecialchars($event['creator_first_name'] . ' ' . $event['creator_last_name']) ?>
              </h4>
              <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0;">Event Organizer</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Registration Modal -->
<div class="registration-modal" id="registrationModal">
  <div class="registration-modal-content">
    <div class="registration-modal-header">
      <h2 style="margin: 0;">Register for Event</h2>
      <button class="registration-modal-close" onclick="closeRegistrationModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="registration-modal-body">
      <form id="registrationForm">
        <input type="hidden" name="event_id" value="<?= $eventId ?>">
        
        <?php if (isLoggedIn()): ?>
          <div class="form-row">
            <div class="form-group">
              <label for="name" class="form-label">Full Name *</label>
              <input 
                type="text" 
                id="name" 
                name="name" 
                class="form-control" 
                value="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>"
                required
              >
            </div>
            
            <div class="form-group">
              <label for="email" class="form-label">Email *</label>
              <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control" 
                value="<?= htmlspecialchars($user['email']) ?>"
                required
              >
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="phone" class="form-label">Phone Number *</label>
              <input 
                type="tel" 
                id="phone" 
                name="phone" 
                class="form-control" 
                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                required
              >
            </div>
            
            <div class="form-group">
              <label for="chapter" class="form-label">Chapter</label>
              <input 
                type="text" 
                id="chapter" 
                name="chapter" 
                class="form-control" 
                placeholder="Your local chapter"
              >
            </div>
          </div>
        <?php else: ?>
          <div class="form-row">
            <div class="form-group">
              <label for="name" class="form-label">Full Name *</label>
              <input 
                type="text" 
                id="name" 
                name="name" 
                class="form-control" 
                required
              >
            </div>
            
            <div class="form-group">
              <label for="email" class="form-label">Email *</label>
              <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control" 
                required
              >
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="phone" class="form-label">Phone Number *</label>
              <input 
                type="tel" 
                id="phone" 
                name="phone" 
                class="form-control" 
                required
              >
            </div>
            
            <div class="form-group">
              <label for="chapter" class="form-label">Chapter</label>
              <input 
                type="text" 
                id="chapter" 
                name="chapter" 
                class="form-control" 
                placeholder="Your local chapter"
              >
            </div>
          </div>
        <?php endif; ?>
        
        <div class="form-group">
          <label for="dietary_needs" class="form-label">Dietary Needs/Restrictions</label>
          <textarea 
            id="dietary_needs" 
            name="dietary_needs" 
            class="form-control" 
            rows="2"
            placeholder="Any food allergies or dietary restrictions?"
          ></textarea>
        </div>
        
        <div class="form-group">
          <label for="additional_info" class="form-label">Additional Information</label>
          <textarea 
            id="additional_info" 
            name="additional_info" 
            class="form-control" 
            rows="3"
            placeholder="Anything else we should know?"
          ></textarea>
        </div>
        
        <div class="form-group">
          <div class="checkbox-group">
            <input type="checkbox" id="terms" name="terms" required>
            <label for="terms">
              I agree to the event <a href="<?= SITE_URL ?>/pages/legal/terms" target="_blank">terms and conditions</a>
            </label>
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i class="fas fa-check"></i> Complete Registration
        </button>
      </form>
    </div>
  </div>
</div>

<script>
// Registration Modal
function openRegistrationModal() {
  document.getElementById('registrationModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeRegistrationModal() {
  document.getElementById('registrationModal').classList.remove('active');
  document.body.style.overflow = 'auto';
}

// Close modal on overlay click
document.getElementById('registrationModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeRegistrationModal();
  }
});

// Registration Form Submit
document.getElementById('registrationForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.classList.add('btn-loading');
  btn.disabled = true;
  
  const formData = new FormData(this);
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/events.php?action=register', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Show success message and reload
      alert('Registration successful! Check your email for confirmation.');
      window.location.reload();
    } else {
      alert(result.message || 'Registration failed. Please try again.');
      btn.classList.remove('btn-loading');
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  } catch (error) {
    console.error('Registration error:', error);
    alert('An error occurred. Please try again.');
    btn.classList.remove('btn-loading');
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// Share Event
function shareEvent(platform) {
  const url = encodeURIComponent(window.location.href);
  const title = encodeURIComponent('<?= addslashes($event['title']) ?>');
  const text = encodeURIComponent('Check out this event: <?= addslashes($event['title']) ?>');
  
  let shareUrl = '';
  
  switch(platform) {
    case 'facebook':
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
      break;
    case 'twitter':
      shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
      break;
    case 'whatsapp':
      shareUrl = `https://wa.me/?text=${text}%20${url}`;
      break;
  }
  
  if (shareUrl) {
    window.open(shareUrl, '_blank', 'width=600,height=400');
  }
}

function copyLink() {
  navigator.clipboard.writeText(window.location.href).then(() => {
    alert('Link copied to clipboard!');
  });
}

// Add to Calendar
function addToCalendar() {
  const title = '<?= addslashes($event['title']) ?>';
  const description = '<?= addslashes($event['description']) ?>';
  const location = '<?= addslashes($event['location']) ?>';
  const startDate = '<?= date('Ymd\THis', strtotime($event['start_date'])) ?>';
  const endDate = '<?= date('Ymd\THis', strtotime($event['end_date'] ?? $event['start_date'] . ' +2 hours')) ?>';
  
  const calendarUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(title)}&dates=${startDate}/${endDate}&details=${encodeURIComponent(description)}&location=${encodeURIComponent(location)}`;
  
  window.open(calendarUrl, '_blank');
}

// Initialize Map
<?php if ($event['latitude'] && $event['longitude']): ?>
document.addEventListener('DOMContentLoaded', function() {
  const map = L.map('map').setView([<?= $event['latitude'] ?>, <?= $event['longitude'] ?>], 15);
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);
  
  L.marker([<?= $event['latitude'] ?>, <?= $event['longitude'] ?>])
    .addTo(map)
    .bindPopup('<strong><?= addslashes($event['title']) ?></strong><br><?= addslashes($event['location']) ?>')
    .openPopup();
});
<?php endif; ?>

// Lightbox
function openLightbox(src) {
  const lightbox = document.createElement('div');
  lightbox.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 2rem;';
  lightbox.onclick = () => lightbox.remove();
  
  const img = document.createElement('img');
  img.src = src;
  img.style.cssText = 'max-width: 100%; max-height: 100%; border-radius: 1rem;';
  
  lightbox.appendChild(img);
  document.body.appendChild(lightbox);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>