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
           (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND response = 'yes') as rsvp_yes_count,
           (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND response = 'maybe') as rsvp_maybe_count,
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

// Increment views
$viewStmt = $conn->prepare("UPDATE events SET views = views + 1 WHERE id = ?");
$viewStmt->execute([$eventId]);

// Check if user is registered or RSVPd
$isRegistered = false;
$hasRSVPd = false;
$userRSVP = null;
$user = null;

if (isLoggedIn()) {
    $user = getCurrentUser();
    
    // Check registration
    $regCheckStmt = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $regCheckStmt->execute([$eventId, $user['id']]);
    $isRegistered = $regCheckStmt->fetch() ? true : false;
    
    // Check RSVP
    $rsvpCheckStmt = $conn->prepare("SELECT response FROM event_rsvps WHERE event_id = ? AND user_id = ?");
    $rsvpCheckStmt->execute([$eventId, $user['id']]);
    $rsvpResult = $rsvpCheckStmt->fetch();
    if ($rsvpResult) {
        $hasRSVPd = true;
        $userRSVP = $rsvpResult['response'];
    }
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

// Parse gallery
$galleryImages = [];
if (!empty($event['gallery'])) {
    $galleryImages = json_decode($event['gallery'], true) ?? [];
}

// Parse videos
$videos = [];
if (!empty($event['videos'])) {
    $videos = json_decode($event['videos'], true) ?? [];
}

$eventTime = strtotime($event['start_date']);
$currentTime = time();

$pageTitle = htmlspecialchars($event['title']) . ' - Events - Scribes Global';
$pageDescription = htmlspecialchars(substr($event['description'], 0, 150));
$pageCSS = 'events';

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* Countdown Timer */
.countdown-section {
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  padding: 3rem 2rem;
  border-radius: var(--radius-2xl);
  margin-bottom: 2rem;
  color: white;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.countdown-section::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
  border-radius: 50%;
}

.countdown-title {
  font-size: 1.5rem;
  margin-bottom: 2rem;
  font-weight: 700;
  position: relative;
  z-index: 1;
}

.countdown-timer {
  display: flex;
  justify-content: center;
  gap: 2rem;
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}

.countdown-block {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  padding: 1.5rem 2rem;
  border-radius: var(--radius-xl);
  min-width: 100px;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.countdown-number {
  font-size: 3rem;
  font-weight: 900;
  display: block;
  line-height: 1;
  margin-bottom: 0.5rem;
  color: #D4AF37;
  text-shadow: 0 2px 10px rgba(212, 175, 55, 0.5);
}

.countdown-label {
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  opacity: 0.9;
}

/* RSVP Buttons */
.rsvp-section {
  background: white;
  padding: 2rem;
  border-radius: var(--radius-xl);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.rsvp-buttons {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.rsvp-btn {
  flex: 1;
  padding: 1rem;
  border: 2px solid var(--gray-300);
  background: white;
  border-radius: var(--radius-lg);
  cursor: pointer;
  transition: all var(--transition-base);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.rsvp-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.rsvp-btn.active.yes {
  border-color: #51CF66;
  background: rgba(81, 207, 102, 0.1);
}

.rsvp-btn.active.no {
  border-color: #EB5757;
  background: rgba(235, 87, 87, 0.1);
}

.rsvp-btn.active.maybe {
  border-color: #FFA500;
  background: rgba(255, 165, 0, 0.1);
}

.rsvp-icon {
  font-size: 2rem;
}

.rsvp-count {
  font-size: 0.75rem;
  color: var(--gray-600);
  font-weight: 600;
}

/* Share Buttons */
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
  text-decoration: none;
}

.share-btn:hover {
  transform: translateY(-2px);
}

.share-btn.facebook:hover {
  border-color: #1877F2;
  background: #1877F2;
  color: white;
}

.share-btn.twitter:hover {
  border-color: #1DA1F2;
  background: #1DA1F2;
  color: white;
}

.share-btn.whatsapp:hover {
  border-color: #25D366;
  background: #25D366;
  color: white;
}

.share-btn.linkedin:hover {
  border-color: #0A66C2;
  background: #0A66C2;
  color: white;
}

.share-btn.copy:hover {
  border-color: #6B46C1;
  background: #6B46C1;
  color: white;
}

/* Gallery Grid */
.event-gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  margin-top: 1.5rem;
}

.event-gallery-item {
  aspect-ratio: 1;
  border-radius: var(--radius-lg);
  overflow: hidden;
  cursor: pointer;
  position: relative;
}

.event-gallery-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--transition-base);
}

.event-gallery-item:hover img {
  transform: scale(1.1);
}

/* Video Grid */
.video-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.video-item {
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.video-item iframe {
  width: 100%;
  height: 200px;
  border: none;
}

/* Registration Success */
.registration-success {
  background: linear-gradient(135deg, #51CF66 0%, #2F9E44 100%);
  color: white;
  padding: 2rem;
  border-radius: var(--radius-xl);
  text-align: center;
  margin-bottom: 2rem;
  box-shadow: 0 4px 20px rgba(81, 207, 102, 0.3);
}

.registration-success h3 {
  color: white;
  margin-bottom: 0.5rem;
}

/* Modal Styles */
.registration-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  z-index: 10000;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  overflow-y: auto;
}

.registration-modal.active {
  display: flex;
}

.registration-modal-content {
  background: white;
  border-radius: var(--radius-2xl);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-50px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.registration-modal-header {
  padding: 2rem;
  border-bottom: 2px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, rgba(107, 70, 193, 0.05) 0%, rgba(45, 156, 219, 0.05) 100%);
}

.registration-modal-close {
  background: var(--gray-100);
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-base);
  font-size: 1.25rem;
  color: var(--gray-600);
}

.registration-modal-close:hover {
  background: #EB5757;
  color: white;
  transform: rotate(90deg);
}

.registration-modal-body {
  padding: 2rem;
}

@media (max-width: 768px) {
  .countdown-timer {
    gap: 1rem;
  }
  
  .countdown-block {
    padding: 1rem 1.5rem;
    min-width: 80px;
  }
  
  .countdown-number {
    font-size: 2rem;
  }
  
  .rsvp-buttons {
    flex-direction: column;
  }
  
  .share-buttons {
    flex-wrap: wrap;
  }
}
</style>

<!-- <div id="three-canvas-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: -1; pointer-events: none;"></div> -->

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
          <button class="share-btn facebook" onclick="shareEvent('facebook')" title="Share on Facebook">
            <i class="fab fa-facebook-f"></i>
          </button>
          <button class="share-btn twitter" onclick="shareEvent('twitter')" title="Share on Twitter">
            <i class="fab fa-twitter"></i>
          </button>
          <button class="share-btn whatsapp" onclick="shareEvent('whatsapp')" title="Share on WhatsApp">
            <i class="fab fa-whatsapp"></i>
          </button>
          <button class="share-btn linkedin" onclick="shareEvent('linkedin')" title="Share on LinkedIn">
            <i class="fab fa-linkedin-in"></i>
          </button>
          <button class="share-btn copy" onclick="copyLink()" title="Copy Link">
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
    <!-- Countdown Timer -->
    <?php if ($eventTime > $currentTime && $event['status'] === 'upcoming'): ?>
      <div class="countdown-section" data-aos="fade-up">
        <h3 class="countdown-title">⏰ Event Starts In</h3>
        <div class="countdown-timer" id="countdown" data-event-time="<?= $eventTime ?>">
          <div class="countdown-block">
            <span class="countdown-number" id="days">00</span>
            <span class="countdown-label">Days</span>
          </div>
          <div class="countdown-block">
            <span class="countdown-number" id="hours">00</span>
            <span class="countdown-label">Hours</span>
          </div>
          <div class="countdown-block">
            <span class="countdown-number" id="minutes">00</span>
            <span class="countdown-label">Minutes</span>
          </div>
          <div class="countdown-block">
            <span class="countdown-number" id="seconds">00</span>
            <span class="countdown-label">Seconds</span>
          </div>
        </div>
      </div>
    <?php endif; ?>
    
    <div class="event-detail-grid">
      <!-- Main Content -->
      <div class="event-detail-main">
        <!-- Registration Success -->
        <?php if ($isRegistered): ?>
          <div class="registration-success" data-aos="fade-up">
            <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h3>You're Registered! 🎉</h3>
            <p style="margin: 0;">We've sent a confirmation email with event details. See you there!</p>
          </div>
        <?php endif; ?>
        
        <!-- RSVP Section -->
        <?php if ($event['rsvp_enabled'] && !$isRegistered && strtotime($event['start_date']) > time()): ?>
          <div class="rsvp-section" data-aos="fade-up">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
              <div>
                <h3 style="margin: 0 0 0.5rem 0;">Will you attend this event?</h3>
                <p style="color: var(--gray-600); margin: 0;">Let us know your response</p>
              </div>
              <div style="text-align: right;">
                <div style="font-size: 1.5rem; font-weight: 900; color: #6B46C1;">
                  <?= ($event['rsvp_yes_count'] + $event['rsvp_maybe_count']) ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--gray-600); text-transform: uppercase; font-weight: 600;">
                  Responses
                </div>
              </div>
            </div>
            
            <div class="rsvp-buttons">
              <button class="rsvp-btn <?= $userRSVP === 'yes' ? 'active yes' : '' ?>" onclick="submitRSVP('yes')">
                <div class="rsvp-icon" style="color: #51CF66;">👍</div>
                <div style="font-weight: 700; font-size: 1rem;">Yes</div>
                <div class="rsvp-count"><?= $event['rsvp_yes_count'] ?> going</div>
              </button>
              
              <button class="rsvp-btn <?= $userRSVP === 'maybe' ? 'active maybe' : '' ?>" onclick="submitRSVP('maybe')">
                <div class="rsvp-icon" style="color: #FFA500;">🤔</div>
                <div style="font-weight: 700; font-size: 1rem;">Maybe</div>
                <div class="rsvp-count"><?= $event['rsvp_maybe_count'] ?> interested</div>
              </button>
              
              <button class="rsvp-btn <?= $userRSVP === 'no' ? 'active no' : '' ?>" onclick="submitRSVP('no')">
                <div class="rsvp-icon" style="color: #EB5757;">👎</div>
                <div style="font-weight: 700; font-size: 1rem;">No</div>
                <div class="rsvp-count">Can't make it</div>
              </button>
            </div>
          </div>
        <?php endif; ?>
        
        <h2 style="margin-bottom: 1.5rem;">About This Event</h2>
        <div style="color: var(--gray-700); line-height: 1.8; font-size: 1.125rem;">
          <?= nl2br(htmlspecialchars($event['description'])) ?>
        </div>
        
        <!-- Event Gallery -->
        <?php if (count($galleryImages) > 0): ?>
          <h2 style="margin-top: 3rem; margin-bottom: 1.5rem;">Event Gallery</h2>
          <div class="event-gallery-grid">
            <?php foreach ($galleryImages as $image): ?>
              <div class="event-gallery-item" onclick="openLightbox('<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($image) ?>')">
                <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($image) ?>" alt="Event Gallery">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <!-- Event Videos -->
        <?php if (count($videos) > 0): ?>
          <h2 style="margin-top: 3rem; margin-bottom: 1.5rem;">Event Videos</h2>
          <div class="video-grid">
            <?php foreach ($videos as $video): ?>
              <div class="video-item">
                <iframe src="<?= htmlspecialchars($video) ?>" allowfullscreen></iframe>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <!-- Location -->
        <?php if ($event['latitude'] && $event['longitude']): ?>
          <h2 style="margin-top: 3rem; margin-bottom: 1.5rem;">
            <i class="fas fa-map-marker-alt"></i> Location
          </h2>
          <div style="background: var(--gray-100); padding: 1.5rem; border-radius: var(--radius-xl); margin-bottom: 1rem;">
            <div style="display: flex; align-items: start; gap: 1rem;">
              <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; flex-shrink: 0;">
                <i class="fas fa-location-arrow"></i>
              </div>
              <div style="flex: 1;">
                <h4 style="margin: 0 0 0.5rem 0; color: var(--dark-bg);">Event Venue</h4>
                <p style="margin: 0; color: var(--gray-700); font-size: 1.125rem; line-height: 1.6;">
                  <?= htmlspecialchars($event['location']) ?>
                </p>
                <div style="margin-top: 0.75rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                  <a href="https://www.google.com/maps?q=<?= $event['latitude'] ?>,<?= $event['longitude'] ?>" target="_blank" class="btn btn-outline btn-sm">
                    <i class="fas fa-directions"></i> Get Directions
                  </a>
                  <button class="btn btn-outline btn-sm" onclick="copyCoordinates(<?= $event['latitude'] ?>, <?= $event['longitude'] ?>)">
                    <i class="fas fa-copy"></i> Copy Coordinates
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div id="eventMap" style="height: 350px; border-radius: var(--radius-xl); overflow: hidden; border: 3px solid var(--gray-200); box-shadow: 0 4px 20px rgba(0,0,0,0.1);"></div>
        <?php else: ?>
          <h2 style="margin-top: 3rem; margin-bottom: 1.5rem;">
            <i class="fas fa-map-marker-alt"></i> Location
          </h2>
          <div style="background: var(--gray-100); padding: 1.5rem; border-radius: var(--radius-xl);">
            <div style="display: flex; align-items: start; gap: 1rem;">
              <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; flex-shrink: 0;">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div style="flex: 1;">
                <h4 style="margin: 0 0 0.5rem 0; color: var(--dark-bg);">Event Venue</h4>
                <p style="margin: 0; color: var(--gray-700); font-size: 1.125rem; line-height: 1.6;">
                  <?= htmlspecialchars($event['location']) ?>
                </p>
              </div>
            </div>
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
          
          <?php if ($event['registration_enabled']): ?>
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
          <?php endif; ?>
          
          <?php if (!$isRegistered && $event['registration_enabled'] && strtotime($event['start_date']) > time()): ?>
            <?php
            $isFull = $event['registration_limit'] && $event['registration_count'] >= $event['registration_limit'];
            ?>
            
            <?php if ($isFull): ?>
              <button class="btn btn-secondary" style="width: 100%; margin-top: 1.5rem;" disabled>
                <i class="fas fa-users"></i> Event Full
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
      <button class="registration-modal-close" onclick="closeRegistrationModal()" type="button">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="registration-modal-body">
      <?php if (isLoggedIn()): ?>
        <!-- Logged in user options -->
        <div style="background: linear-gradient(135deg, rgba(107, 70, 193, 0.05) 0%, rgba(45, 156, 219, 0.05) 100%); padding: 1.5rem; border-radius: var(--radius-xl); margin-bottom: 1.5rem; text-align: center;">
          <i class="fas fa-user-check" style="font-size: 2.5rem; color: #6B46C1; margin-bottom: 1rem;"></i>
          <h3 style="margin: 0 0 0.5rem 0; color: var(--dark-bg);">Welcome back, <?= htmlspecialchars($user['first_name']) ?>!</h3>
          <p style="margin: 0; color: var(--gray-600);">We can use your account information to register you quickly</p>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
          <button type="button" class="btn btn-primary" style="flex: 1;" onclick="showQuickRegister()">
            <i class="fas fa-bolt"></i> Quick Register
          </button>
          <button type="button" class="btn btn-outline" style="flex: 1;" onclick="showCustomRegister()">
            <i class="fas fa-edit"></i> Custom Info
          </button>
        </div>
      <?php endif; ?>
      
      <!-- Registration Form -->
      <form id="registrationForm">
        <input type="hidden" name="event_id" value="<?= $eventId ?>">
        
        <div id="quickRegisterFields" style="<?= !isLoggedIn() ? 'display: none;' : '' ?>">
          <div style="padding: 1rem; background: var(--gray-100); border-radius: var(--radius-lg); margin-bottom: 1.5rem;">
            <p style="margin: 0 0 0.75rem 0; font-weight: 600; color: var(--dark-bg);">
              <i class="fas fa-info-circle"></i> Registration Details:
            </p>
            <div style="display: grid; gap: 0.5rem; font-size: 0.875rem; color: var(--gray-700);">
              <div><strong>Name:</strong> <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></div>
              <div><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '') ?></div>
              <div><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not provided') ?></div>
            </div>
          </div>
          
          <input type="hidden" name="use_account_info" value="1">
        </div>
        
        <div id="customRegisterFields">
          <div class="form-group">
            <label for="reg_name" class="form-label">
              Full Name <span style="color: #EB5757;">*</span>
            </label>
            <input 
              type="text" 
              id="reg_name" 
              name="name" 
              class="form-control" 
              placeholder="Enter your full name"
              value="<?= isLoggedIn() ? htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) : '' ?>"
              required
            >
          </div>
          
          <div class="form-group">
            <label for="reg_email" class="form-label">
              Email Address <span style="color: #EB5757;">*</span>
            </label>
            <input 
              type="email" 
              id="reg_email" 
              name="email" 
              class="form-control" 
              placeholder="your.email@example.com"
              value="<?= isLoggedIn() ? htmlspecialchars($user['email'] ?? '') : '' ?>"
              required
            >
          </div>
          
          <div class="form-group">
            <label for="reg_phone" class="form-label">
              Phone Number <span style="color: #EB5757;">*</span>
            </label>
            <input 
              type="tel" 
              id="reg_phone" 
              name="phone" 
              class="form-control" 
              placeholder="+233 123 456 789"
              value="<?= isLoggedIn() ? htmlspecialchars($user['phone'] ?? '') : '' ?>"
              required
            >
          </div>
          
          <div class="form-group">
            <label for="reg_chapter" class="form-label">
              Chapter (Optional)
            </label>
            <input 
              type="text" 
              id="reg_chapter" 
              name="chapter" 
              class="form-control" 
              placeholder="Your local chapter"
            >
          </div>
        </div>
        
        <div class="form-group">
          <label for="reg_dietary" class="form-label">
            Dietary Needs/Restrictions (Optional)
          </label>
          <textarea 
            id="reg_dietary" 
            name="dietary_needs" 
            class="form-control" 
            rows="2"
            placeholder="Any food allergies or dietary restrictions?"
          ></textarea>
        </div>
        
        <div class="form-group">
          <label for="reg_additional" class="form-label">
            Additional Information (Optional)
          </label>
          <textarea 
            id="reg_additional" 
            name="additional_info" 
            class="form-control" 
            rows="2"
            placeholder="Anything else we should know?"
          ></textarea>
        </div>
        
        <div class="form-group">
          <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
            <input type="checkbox" id="reg_terms" name="terms" required style="width: 20px; height: 20px;">
            <span style="font-size: 0.875rem; color: var(--gray-700);">
              I agree to the event <a href="<?= SITE_URL ?>/pages/legal/terms" target="_blank" style="color: #6B46C1; font-weight: 600;">terms and conditions</a>
            </span>
          </label>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
          <i class="fas fa-check"></i> Complete Registration
        </button>
      </form>
    </div>
  </div>
</div>

<script>
// Countdown Timer
<?php if ($eventTime > $currentTime && $event['status'] === 'upcoming'): ?>
const countdown = document.getElementById('countdown');
if (countdown) {
  const eventTime = parseInt(countdown.getAttribute('data-event-time')) * 1000;

  function updateCountdown() {
    const now = new Date().getTime();
    const distance = eventTime - now;
    
    if (distance < 0) {
      countdown.innerHTML = '<div class="countdown-block"><span class="countdown-number">🎉</span><span class="countdown-label">Event Started!</span></div>';
      return;
    }
    
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    document.getElementById('days').textContent = String(days).padStart(2, '0');
    document.getElementById('hours').textContent = String(hours).padStart(2, '0');
    document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
    document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
  }

  updateCountdown();
  setInterval(updateCountdown, 1000);
}
<?php endif; ?>

// Registration Modal Functions
function openRegistrationModal() {
  const modal = document.getElementById('registrationModal');
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    <?php if (isLoggedIn()): ?>
      showQuickRegister();
    <?php else: ?>
      showCustomRegister();
    <?php endif; ?>
  }
}

function closeRegistrationModal() {
  const modal = document.getElementById('registrationModal');
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
  }
}

function showQuickRegister() {
  const quickFields = document.getElementById('quickRegisterFields');
  const customFields = document.getElementById('customRegisterFields');
  
  if (quickFields && customFields) {
    quickFields.style.display = 'block';
    customFields.style.display = 'none';
    
    // Disable custom field requirements
    document.getElementById('reg_name').required = false;
    document.getElementById('reg_email').required = false;
    document.getElementById('reg_phone').required = false;
  }
}

function showCustomRegister() {
  const quickFields = document.getElementById('quickRegisterFields');
  const customFields = document.getElementById('customRegisterFields');
  
  if (quickFields && customFields) {
    quickFields.style.display = 'none';
    customFields.style.display = 'block';
    
    // Enable custom field requirements
    document.getElementById('reg_name').required = true;
    document.getElementById('reg_email').required = true;
    document.getElementById('reg_phone').required = true;
  }
}

// Close modal on overlay click
const modal = document.getElementById('registrationModal');
if (modal) {
  modal.addEventListener('click', function(e) {
    if (e.target === this) {
      closeRegistrationModal();
    }
  });
}

// Registration Form Submit
const regForm = document.getElementById('registrationForm');
if (regForm) {
  regForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.classList.add('btn-loading');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
    
    const formData = new FormData(this);
    
    try {
      const response = await fetch('<?= SITE_URL ?>/api/events.php?action=register', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        alert('✅ ' + result.message);
        window.location.reload();
      } else {
        alert('❌ ' + (result.message || 'Registration failed. Please try again.'));
        btn.classList.remove('btn-loading');
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    } catch (error) {
      console.error('Registration error:', error);
      alert('❌ An error occurred. Please try again.');
      btn.classList.remove('btn-loading');
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  });
}

// RSVP Function
async function submitRSVP(response) {
  <?php if (!isLoggedIn()): ?>
    alert('Please login to RSVP for this event');
    window.location.href = '<?= SITE_URL ?>/auth/login?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>';
    return;
  <?php endif; ?>
  
  try {
    const formData = new FormData();
    formData.append('event_id', '<?= $eventId ?>');
    formData.append('response', response);
    
    const res = await fetch('<?= SITE_URL ?>/api/events.php?action=rsvp', {
      method: 'POST',
      body: formData
    });
    
    const result = await res.json();
    
    if (result.success) {
      window.location.reload();
    } else {
      alert(result.message || 'Failed to submit RSVP');
    }
  } catch (error) {
    console.error('RSVP error:', error);
    alert('An error occurred. Please try again.');
  }
}

// Share Functions
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
    case 'linkedin':
      shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
      break;
  }
  
  if (shareUrl) {
    window.open(shareUrl, '_blank', 'width=600,height=400');
  }
}

function copyLink() {
  navigator.clipboard.writeText(window.location.href).then(() => {
    alert('✅ Link copied to clipboard!');
  }).catch(err => {
    console.error('Failed to copy:', err);
    alert('❌ Failed to copy link');
  });
}

function copyCoordinates(lat, lng) {
  const coords = `${lat}, ${lng}`;
  navigator.clipboard.writeText(coords).then(() => {
    alert('✅ Coordinates copied to clipboard!');
  }).catch(err => {
    console.error('Failed to copy:', err);
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
  if (typeof L !== 'undefined') {
    try {
      const map = L.map('eventMap').setView([<?= $event['latitude'] ?>, <?= $event['longitude'] ?>], 15);
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
      }).addTo(map);
      
      const marker = L.marker([<?= $event['latitude'] ?>, <?= $event['longitude'] ?>]).addTo(map);
      
      marker.bindPopup(`
        <div style="text-align: center; padding: 0.5rem;">
          <strong style="display: block; margin-bottom: 0.5rem; color: #1A1A2E;"><?= addslashes($event['title']) ?></strong>
          <p style="margin: 0; font-size: 0.875rem; color: #4A5568;"><?= addslashes($event['location']) ?></p>
          <a href="https://www.google.com/maps?q=<?= $event['latitude'] ?>,<?= $event['longitude'] ?>" target="_blank" style="display: inline-block; margin-top: 0.5rem; color: #6B46C1; font-weight: 600; font-size: 0.875rem;">
            <i class="fas fa-directions"></i> Get Directions
          </a>
        </div>
      `).openPopup();
      
    } catch (error) {
      console.error('Map initialization error:', error);
    }
  }
});
<?php endif; ?>

// Lightbox for gallery
function openLightbox(src) {
  const lightbox = document.createElement('div');
  lightbox.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 2rem; cursor: pointer;';
  lightbox.onclick = () => lightbox.remove();
  
  const img = document.createElement('img');
  img.src = src;
  img.style.cssText = 'max-width: 100%; max-height: 100%; border-radius: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,0.5);';
  img.onclick = (e) => e.stopPropagation();
  
  const closeBtn = document.createElement('button');
  closeBtn.innerHTML = '<i class="fas fa-times"></i>';
  closeBtn.style.cssText = 'position: absolute; top: 2rem; right: 2rem; background: white; border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; font-size: 1.5rem; color: #1A1A2E; box-shadow: 0 4px 15px rgba(0,0,0,0.3); transition: all 0.3s;';
  closeBtn.onmouseover = () => closeBtn.style.transform = 'scale(1.1) rotate(90deg)';
  closeBtn.onmouseout = () => closeBtn.style.transform = 'scale(1) rotate(0deg)';
  closeBtn.onclick = () => lightbox.remove();
  
  lightbox.appendChild(img);
  lightbox.appendChild(closeBtn);
  document.body.appendChild(lightbox);
}
</script>

<!-- Leaflet CSS & JS for Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>