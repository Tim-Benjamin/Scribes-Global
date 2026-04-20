<?php
$pageTitle = 'Past Events - Scribes Global';
$pageDescription = 'View past events and memories from Scribes Global';
$pageCSS = 'events';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

$db = new Database();
$conn = $db->connect();

// Get filters
$search = $_GET['search'] ?? '';
$chapter = $_GET['chapter'] ?? '';
$year = $_GET['year'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
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

if (!empty($chapter)) {
    $query .= " AND e.chapter_id = ?";
    $params[] = $chapter;
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
if (!empty($chapter)) {
    $countQuery .= " AND e.chapter_id = ?";
    $countParams[] = $chapter;
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

// Get all chapters
$chaptersStmt = $conn->query("SELECT * FROM chapters WHERE status = 'active' ORDER BY name ASC");
$chapters = $chaptersStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.past-events-hero {
  background: linear-gradient(135deg, #1A1A2E 0%, #16213E 100%);
  padding: 5rem 0 3rem;
  text-align: center;
  color: white;
  position: relative;
  overflow: hidden;
}

.past-events-hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
  opacity: 0.5;
}

.past-events-hero-content {
  position: relative;
  z-index: 1;
}

.memory-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(212, 175, 55, 0.2);
  border: 2px solid #D4AF37;
  color: #D4AF37;
  padding: 0.5rem 1.5rem;
  border-radius: var(--radius-full);
  font-weight: 700;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.past-event-card {
  position: relative;
  cursor: pointer;
  transition: all var(--transition-base);
}

.past-event-card::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, transparent 50%);
  border-radius: var(--radius-xl);
  pointer-events: none;
}

.past-event-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 1.5rem;
  color: white;
  z-index: 1;
}

.past-event-date-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(26, 26, 46, 0.9);
  backdrop-filter: blur(10px);
  padding: 0.75rem 1.25rem;
  border-radius: var(--radius-lg);
  text-align: center;
  z-index: 2;
}

.year-divider {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin: 3rem 0 2rem;
}

.year-divider-line {
  flex: 1;
  height: 2px;
  background: linear-gradient(90deg, transparent 0%, var(--gray-300) 50%, transparent 100%);
}

.year-divider-text {
  font-size: 1.75rem;
  font-weight: 900;
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
</style>

<!-- <div id="three-canvas-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: -1; pointer-events: none;"></div> -->

<!-- Hero Section -->
<section class="past-events-hero">
  <div class="past-events-hero-content">
    <div class="container">
      <div class="memory-badge">
        <i class="fas fa-history"></i>
        <span>Memories & Moments</span>
      </div>
      <h1 style="font-size: 3rem; margin-bottom: 1rem; font-weight: 900;">Past Events</h1>
      <p style="font-size: 1.25rem; opacity: 0.9; max-width: 600px; margin: 0 auto;">
        Relive the amazing moments and celebrate the impact of our past events
      </p>
    </div>
  </div>
</section>

<!-- Filters Section -->
<section style="padding: 2rem 0; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
  <div class="container">
    <form method="GET" class="filters-bar" style="margin: 0;">
      <input 
        type="text" 
        name="search"
        class="search-input" 
        placeholder="Search past events..." 
        value="<?= htmlspecialchars($search) ?>"
      >
      
      <select name="year" class="filter-select">
        <option value="">All Years</option>
        <?php foreach ($years as $y): ?>
          <option value="<?= $y['year'] ?>" <?= $year == $y['year'] ? 'selected' : '' ?>>
            <?= $y['year'] ?>
          </option>
        <?php endforeach; ?>
      </select>
      
      <select name="chapter" class="filter-select">
        <option value="">All Chapters</option>
        <?php foreach ($chapters as $chap): ?>
          <option value="<?= $chap['id'] ?>" <?= $chapter == $chap['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($chap['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-search"></i> Search
      </button>
      
      <a href="<?= SITE_URL ?>/pages/events/past" class="btn btn-outline">
        <i class="fas fa-redo"></i> Reset
      </a>
    </form>
  </div>
</section>

<!-- Events Section -->
<section style="padding: 4rem 0;">
  <div class="container">
    <?php if (count($events) > 0): ?>
      <?php
      $currentYear = null;
      foreach ($events as $event):
        $eventYear = date('Y', strtotime($event['start_date']));
        
        // Display year divider
        if ($currentYear !== $eventYear):
          $currentYear = $eventYear;
      ?>
          <div class="year-divider">
            <div class="year-divider-line"></div>
            <div class="year-divider-text"><?= $eventYear ?></div>
            <div class="year-divider-line"></div>
          </div>
      <?php endif; ?>
      
      <div class="event-card" style="margin-bottom: 2rem;" data-aos="fade-up">
        <a href="<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>" style="text-decoration: none; color: inherit; display: block; position: relative; height: 350px; border-radius: var(--radius-xl); overflow: hidden;">
          <img 
            src="<?= $event['hero_image'] ? ASSETS_PATH . 'images/uploads/' . $event['hero_image'] : ASSETS_PATH . 'images/placeholder-event.jpg' ?>" 
            alt="<?= htmlspecialchars($event['title']) ?>"
            style="width: 100%; height: 100%; object-fit: cover;"
          >
          
          <div class="past-event-date-badge">
            <div style="font-size: 1.5rem; font-weight: 900; color: white; line-height: 1;">
              <?= date('d', strtotime($event['start_date'])) ?>
            </div>
            <div style="font-size: 0.75rem; color: rgba(255,255,255,0.8); text-transform: uppercase;">
              <?= date('M', strtotime($event['start_date'])) ?>
            </div>
          </div>
          
          <div class="past-event-overlay">
            <h3 style="color: white; font-size: 1.5rem; margin-bottom: 0.5rem;">
              <?= htmlspecialchars($event['title']) ?>
            </h3>
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; font-size: 0.875rem; opacity: 0.9;">
              <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?></span>
              <?php if ($event['chapter_name']): ?>
                <span><i class="fas fa-users"></i> <?= htmlspecialchars($event['chapter_name']) ?></span>
              <?php endif; ?>
              <span><i class="fas fa-user-check"></i> <?= $event['registration_count'] ?> attended</span>
            </div>
          </div>
        </a>
      </div>
      
      <?php endforeach; ?>
      
      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top: 3rem;">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $year ? '&year=' . $year : '' ?><?= $chapter ? '&chapter=' . $chapter : '' ?>" class="btn btn-outline">
              <i class="fas fa-chevron-left"></i> Previous
            </a>
          <?php endif; ?>
          
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
              <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $year ? '&year=' . $year : '' ?><?= $chapter ? '&chapter=' . $chapter : '' ?>" class="btn <?= $i == $page ? 'btn-primary' : 'btn-outline' ?>">
                <?= $i ?>
              </a>
            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
              <span>...</span>
            <?php endif; ?>
          <?php endfor; ?>
          
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $year ? '&year=' . $year : '' ?><?= $chapter ? '&chapter=' . $chapter : '' ?>" class="btn btn-outline">
              Next <i class="fas fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-calendar-times"></i></div>
        <div class="empty-state-title">No Past Events Found</div>
        <div class="empty-state-text">
          <?php if (!empty($search) || !empty($year) || !empty($chapter)): ?>
            Try adjusting your filters to see more events
          <?php else: ?>
            Check back later for archived events
          <?php endif; ?>
        </div>
        <a href="<?= SITE_URL ?>/pages/events" class="btn btn-primary">
          <i class="fas fa-calendar"></i> View Upcoming Events
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>