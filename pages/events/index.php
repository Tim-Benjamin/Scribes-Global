<?php
$pageTitle = 'Events - Scribes Global';
$pageDescription = 'Discover and register for upcoming Scribes Global events';
$pageCSS = 'events';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

// Get filter parameters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$chapter = $_GET['chapter'] ?? '';
$month = $_GET['month'] ?? '';

// Build query
$query = "SELECT e.*, c.name as chapter_name, 
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
          FROM events e
          LEFT JOIN chapters c ON e.chapter_id = c.id
          WHERE e.status = 'upcoming' AND e.start_date > NOW()";

$params = [];

if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($type)) {
    $query .= " AND e.event_type = ?";
    $params[] = $type;
}

if (!empty($chapter)) {
    $query .= " AND e.chapter_id = ?";
    $params[] = $chapter;
}

if (!empty($month)) {
    $query .= " AND DATE_FORMAT(e.start_date, '%Y-%m') = ?";
    $params[] = $month;
}

$query .= " ORDER BY e.start_date ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get all chapters for filter
$chaptersStmt = $conn->query("SELECT * FROM chapters WHERE status = 'active' ORDER BY name ASC");
$chapters = $chaptersStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* Page-specific inline styles */
.countdown-timer {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin: 1rem 0;
}

.countdown-item {
  text-align: center;
}

.countdown-value {
  font-size: 2rem;
  font-weight: 900;
  color: var(--primary-purple);
  display: block;
}

.countdown-label {
  font-size: 0.75rem;
  color: var(--gray-600);
  text-transform: uppercase;
}
</style>

<!-- Hero Section -->
<section class="events-hero">
  <div class="container events-hero-content" data-aos="fade-up">
    <h1 class="events-hero-title">Upcoming Events</h1>
    <p class="events-hero-subtitle">
      Join us for powerful gatherings, worship nights, creative workshops, and community events
    </p>
  </div>
</section>

<!-- Filters Section -->
<section class="container">
  <div class="events-filters" data-aos="fade-up">
    <form method="GET" action="" id="filterForm">
      <div class="filters-grid">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input 
            type="text" 
            name="search" 
            class="search-input" 
            placeholder="Search events..."
            value="<?= htmlspecialchars($search) ?>"
          >
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Event Type</label>
          <select name="type" class="filter-select">
            <option value="">All Types</option>
            <option value="physical" <?= $type === 'physical' ? 'selected' : '' ?>>Physical</option>
            <option value="virtual" <?= $type === 'virtual' ? 'selected' : '' ?>>Virtual</option>
            <option value="hybrid" <?= $type === 'hybrid' ? 'selected' : '' ?>>Hybrid</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Chapter</label>
          <select name="chapter" class="filter-select">
            <option value="">All Chapters</option>
            <?php foreach ($chapters as $chap): ?>
              <option value="<?= $chap['id'] ?>" <?= $chapter == $chap['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($chap['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Month</label>
          <select name="month" class="filter-select">
            <option value="">All Months</option>
            <?php
            for ($i = 0; $i < 12; $i++) {
                $monthValue = date('Y-m', strtotime("+{$i} months"));
                $monthLabel = date('F Y', strtotime("+{$i} months"));
                echo "<option value='{$monthValue}'" . ($month === $monthValue ? ' selected' : '') . ">{$monthLabel}</option>";
            }
            ?>
          </select>
        </div>
      </div>
      
      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
        <div class="view-toggle">
          <button type="button" class="view-toggle-btn active" onclick="switchView('grid')">
            <i class="fas fa-th"></i> Grid
          </button>
          <button type="button" class="view-toggle-btn" onclick="switchView('list')">
            <i class="fas fa-list"></i> List
          </button>
        </div>
        
        <div style="display: flex; gap: 1rem;">
          <button type="button" onclick="document.getElementById('filterForm').reset(); window.location.href='<?= SITE_URL ?>/pages/events';" class="btn btn-outline btn-sm">
            <i class="fas fa-redo"></i> Reset
          </button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-filter"></i> Apply Filters
          </button>
        </div>
      </div>
    </form>
  </div>
</section>

<!-- Events Grid -->
<section class="events-container">
  <div class="container">
    <?php if (count($events) > 0): ?>
      <div class="events-grid grid-view" id="eventsGrid">
        <?php foreach ($events as $event): ?>
          <div class="event-card" data-aos="fade-up" onclick="window.location.href='<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>'">
            <div class="event-image-container">
              <img 
                src="<?= $event['hero_image'] ? ASSETS_PATH . 'images/uploads/' . $event['hero_image'] : ASSETS_PATH . 'images/placeholder-event.jpg' ?>" 
                alt="<?= htmlspecialchars($event['title']) ?>" 
                class="event-image"
              >
              
              <?php if ($event['featured']): ?>
                <div class="event-status-badge featured">
                  <i class="fas fa-star"></i> Featured
                </div>
              <?php else: ?>
                <div class="event-status-badge upcoming">
                  <i class="fas fa-calendar"></i> Upcoming
                </div>
              <?php endif; ?>
              
              <div class="event-date-badge">
                <div class="event-date-day"><?= date('d', strtotime($event['start_date'])) ?></div>
                <div class="event-date-month"><?= date('M', strtotime($event['start_date'])) ?></div>
              </div>
            </div>
            
            <div class="event-content">
              <span class="event-category">
                <i class="fas fa-<?= $event['event_type'] === 'virtual' ? 'video' : ($event['event_type'] === 'hybrid' ? 'globe' : 'map-marker-alt') ?>"></i>
                <?= ucfirst($event['event_type']) ?>
              </span>
              
              <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
              
              <p class="event-description"><?= htmlspecialchars($event['description']) ?></p>
              
              <div class="event-meta">
                <div class="event-meta-item">
                  <i class="fas fa-calendar-alt"></i>
                  <span><?= date('l, F j, Y', strtotime($event['start_date'])) ?></span>
                </div>
                
                <div class="event-meta-item">
                  <i class="fas fa-clock"></i>
                  <span><?= date('g:i A', strtotime($event['start_date'])) ?></span>
                </div>
                
                <div class="event-meta-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <span><?= htmlspecialchars($event['location']) ?></span>
                </div>
                
                <?php if ($event['chapter_name']): ?>
                  <div class="event-meta-item">
                    <i class="fas fa-users"></i>
                    <span><?= htmlspecialchars($event['chapter_name']) ?></span>
                  </div>
                <?php endif; ?>
              </div>
              
              <div class="event-footer">
                <div class="event-attendees">
                  <i class="fas fa-user-check" style="color: var(--primary-purple);"></i>
                  <span class="attendees-count">
                    <?= $event['registration_count'] ?> registered
                    <?php if ($event['registration_limit']): ?>
                      / <?= $event['registration_limit'] ?> spots
                    <?php endif; ?>
                  </span>
                </div>
                
                <a href="<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>" class="btn btn-primary btn-sm" onclick="event.stopPropagation()">
                  View Details <i class="fas fa-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-events">
        <div class="empty-events-icon">
          <i class="fas fa-calendar-times"></i>
        </div>
        <h2 class="empty-events-title">No Events Found</h2>
        <p class="empty-events-text">
          <?php if (!empty($search) || !empty($type) || !empty($chapter) || !empty($month)): ?>
            No events match your filters. Try adjusting your search criteria.
          <?php else: ?>
            There are no upcoming events at the moment. Check back soon!
          <?php endif; ?>
        </p>
        <?php if (!empty($search) || !empty($type) || !empty($chapter) || !empty($month)): ?>
          <a href="<?= SITE_URL ?>/pages/events" class="btn btn-primary">
            <i class="fas fa-redo"></i> View All Events
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
  <div class="container">
    <div class="cta-content" data-aos="zoom-in">
      <h2 class="cta-title">Want to Host an Event?</h2>
      <p class="cta-description">
        Have an idea for a creative gathering, worship night, or community event? We'd love to hear from you!
      </p>
      <div class="cta-buttons">
        <a href="<?= SITE_URL ?>/pages/connect/invite" class="btn btn-white btn-lg">
          <i class="fas fa-plus-circle"></i> Propose an Event
        </a>
        <a href="<?= SITE_URL ?>/pages/connect/volunteer" class="btn btn-outline-white btn-lg">
          <i class="fas fa-hands-helping"></i> Volunteer
        </a>
      </div>
    </div>
  </div>
</section>

<script>
// Auto-submit form when filters change
document.querySelectorAll('.filter-select, .search-input').forEach(element => {
  if (element.classList.contains('search-input')) {
    let timeout;
    element.addEventListener('input', function() {
      clearTimeout(timeout);
      timeout = setTimeout(() => {
        document.getElementById('filterForm').submit();
      }, 500);
    });
  } else {
    element.addEventListener('change', function() {
      document.getElementById('filterForm').submit();
    });
  }
});

// View switcher
function switchView(view) {
  const grid = document.getElementById('eventsGrid');
  const buttons = document.querySelectorAll('.view-toggle-btn');
  
  buttons.forEach(btn => btn.classList.remove('active'));
  event.target.closest('.view-toggle-btn').classList.add('active');
  
  if (view === 'grid') {
    grid.classList.remove('list-view');
    grid.classList.add('grid-view');
  } else {
    grid.classList.remove('grid-view');
    grid.classList.add('list-view');
  }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>