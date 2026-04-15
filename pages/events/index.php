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

/* Hero Carousel Styles */
.events-hero {
  position: relative;
  height: 80vh;
  min-height: 600px;
  overflow: hidden;
  padding: 0;
  margin-bottom: 2rem;
}

.hero-carousel-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.hero-carousel {
  position: relative;
  width: 100%;
  height: 100%;
}

.carousel-slide {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.8s ease-in-out, visibility 0.8s ease-in-out;
}

.carousel-slide.active {
  opacity: 1;
  visibility: visible;
}

.carousel-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.carousel-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, rgba(106, 13, 173, 0.9) 0%, rgba(0, 0, 0, 0.7) 100%);
  z-index: 1;
}

.carousel-content {
  position: relative;
  z-index: 2;
  color: white;
  padding-top: 100px;
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.carousel-tag {
  display: inline-block;
  background: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 30px;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 1rem;
  backdrop-filter: blur(5px);
}

.carousel-title {
  font-size: 4rem;
  font-weight: 900;
  margin-bottom: 1rem;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
  line-height: 1.2;
}

.carousel-description {
  font-size: 1.125rem;
  margin-bottom: 1.5rem;
  opacity: 0.9;
  max-width: 600px;
  line-height: 1.8;
}

.carousel-meta {
  display: flex;
  gap: 2rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  justify-content: center;
}

.carousel-meta span {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
  background: rgba(255, 255, 255, 0.15);
  padding: 0.5rem 1rem;
  border-radius: 30px;
  backdrop-filter: blur(5px);
}

.carousel-meta i {
  color: var(--primary-purple-light, #c084fc);
}

/* Carousel Controls */
.carousel-control {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  color: white;
  font-size: 1.2rem;
  cursor: pointer;
  z-index: 10;
  transition: all 0.3s ease;
  backdrop-filter: blur(5px);
}

.carousel-control:hover {
  background: white;
  color: var(--primary-purple);
  border-color: white;
}

.carousel-control.prev {
  left: 30px;
}

.carousel-control.next {
  right: 30px;
}

/* Carousel Indicators */
.carousel-indicators {
  position: absolute;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 12px;
  z-index: 10;
}

.indicator {
  width: 40px;
  height: 4px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 2px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.indicator.active {
  background: white;
  width: 60px;
}

.indicator:hover {
  background: rgba(255, 255, 255, 0.6);
}

/* Pause/Play Button */
.carousel-pause {
  position: absolute;
  bottom: 30px;
  right: 30px;
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  color: white;
  cursor: pointer;
  z-index: 10;
  backdrop-filter: blur(5px);
  transition: all 0.3s ease;
}

.carousel-pause:hover {
  background: white;
  color: var(--primary-purple);
}

/* Responsive Design */
@media (max-width: 768px) {
  .events-hero {
    height: 70vh;
    min-height: 500px;
  }
  
  .carousel-title {
    font-size: 2.5rem;
  }
  
  .carousel-description {
    font-size: 1rem;
  }
  
  .carousel-meta {
    gap: 1rem;
  }
  
  .carousel-meta span {
    font-size: 0.875rem;
    padding: 0.3rem 0.8rem;
  }
  
  .carousel-control {
    width: 40px;
    height: 40px;
    font-size: 1rem;
  }
  
  .carousel-control.prev {
    left: 15px;
  }
  
  .carousel-control.next {
    right: 15px;
  }
  
  .indicator {
    width: 30px;
  }
  
  .indicator.active {
    width: 45px;
  }
}

@media (max-width: 480px) {
  .carousel-title {
    font-size: 2rem;
  }
  
  .carousel-meta {
    flex-direction: column;
    gap: 0.5rem;
  }
}



@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap');

/* ── CSS Variables ────────────────────────────────────────── */
:root {
  --pp: #6A0DAD;
  --pp-deep: #3d0070;
  --pp-light: #c084fc;
  --gold: #f5c842;
  --cream: #faf7f2;
  --hero-h: 95vh;
}

/* ── Hero Wrapper ─────────────────────────────────────────── */
.events-hero {
  position: relative;
  height: var(--hero-h);
  min-height: 640px;
  overflow: hidden;
  margin-bottom: 3rem;
}

/* ── Slide Stack ──────────────────────────────────────────── */
.hc-track {
  position: relative;
  width: 100%;
  height: 100%;
}

.hc-slide {
  position: absolute;
  inset: 0;
  display: grid;
  grid-template-columns: 1fr 1fr;
  opacity: 0;
  pointer-events: none;
  transition: none;
}

.hc-slide.entering  { animation: slideEnter .85s cubic-bezier(.77,0,.18,1) forwards; }
.hc-slide.leaving   { animation: slideLeave .85s cubic-bezier(.77,0,.18,1) forwards; }
.hc-slide.active    { opacity: 1; pointer-events: auto; }

@keyframes slideEnter {
  from { opacity: 0; transform: translateX(60px) scale(.97); }
  to   { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes slideLeave {
  from { opacity: 1; transform: translateX(0) scale(1); }
  to   { opacity: 0; transform: translateX(-60px) scale(.97); }
}

/* ── Left Panel (text) ────────────────────────────────────── */
.hc-left {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 6rem 5rem 6rem 7vw;
  background: #0a0010;
  overflow: hidden;
  z-index: 1;
}

/* Animated noise grain */
.hc-left::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.04'/%3E%3C/svg%3E");
  background-size: 200px;
  opacity: .35;
  pointer-events: none;
}

/* Glowing orb behind text */
.hc-left::after {
  content: '';
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(106,13,173,.55) 0%, transparent 70%);
  bottom: -100px;
  left: -80px;
  pointer-events: none;
}

.hc-tag {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  font-family: 'DM Sans', sans-serif;
  font-size: .72rem;
  font-weight: 500;
  letter-spacing: .18em;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 1.4rem;
  position: relative;
}
.hc-tag::before {
  content: '';
  display: block;
  width: 28px;
  height: 2px;
  background: var(--gold);
  flex-shrink: 0;
}

.hc-title {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: clamp(2.8rem, 5vw, 5rem);
  font-weight: 900;
  line-height: 1.05;
  color: #fff;
  margin: 0 0 1.4rem;
  letter-spacing: -.02em;
  position: relative;
}

.hc-title em {
  font-style: normal;
  color: var(--pp-light);
}

.hc-desc {
  font-family: 'DM Sans', sans-serif;
  font-size: 1rem;
  line-height: 1.85;
  color: rgba(255,255,255,.62);
  max-width: 480px;
  margin-bottom: 2rem;
  position: relative;
}

.hc-pills {
  display: flex;
  flex-wrap: wrap;
  gap: .6rem;
  margin-bottom: 2.4rem;
  position: relative;
}
.hc-pill {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  font-family: 'DM Sans', sans-serif;
  font-size: .8rem;
  color: rgba(255,255,255,.75);
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.1);
  padding: .35rem .85rem;
  border-radius: 100px;
  backdrop-filter: blur(4px);
}
.hc-pill i { color: var(--pp-light); font-size: .75rem; }

.hc-cta {
  display: inline-flex;
  align-items: center;
  gap: .7rem;
  font-family: 'DM Sans', sans-serif;
  font-size: .9rem;
  font-weight: 500;
  color: #000;
  background: var(--gold);
  padding: .85rem 2rem;
  border-radius: 100px;
  text-decoration: none;
  position: relative;
  transition: transform .25s, box-shadow .25s;
  width: fit-content;
}
.hc-cta::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 100px;
  background: white;
  opacity: 0;
  transition: opacity .25s;
}
.hc-cta:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(245,200,66,.35); }
.hc-cta:hover::after { opacity: .08; }
.hc-cta i { font-size: .8rem; transition: transform .25s; }
.hc-cta:hover i { transform: translateX(4px); }

/* ── Right Panel (image) ──────────────────────────────────── */
.hc-right {
  position: relative;
  overflow: hidden;
}

.hc-img {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  transform: scale(1.08);
  transition: transform 6s ease;
}
.hc-slide.active .hc-img { transform: scale(1); }

/* Dark-to-transparent vignette on the left edge of the image */
.hc-right::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, #0a0010 0%, transparent 35%);
  z-index: 1;
  pointer-events: none;
}

/* Diagonal decorative stripe */
.hc-stripe {
  position: absolute;
  top: 0;
  left: -40px;
  width: 80px;
  height: 100%;
  background: linear-gradient(180deg, var(--pp) 0%, var(--pp-deep) 100%);
  clip-path: polygon(40px 0, 100% 0, calc(100% - 40px) 100%, 0 100%);
  z-index: 2;
  opacity: .85;
}

/* Floating event number chip */
.hc-chip {
  position: absolute;
  bottom: 2.5rem;
  right: 2.5rem;
  z-index: 3;
  background: rgba(10,0,16,.8);
  border: 1px solid rgba(255,255,255,.12);
  backdrop-filter: blur(16px);
  border-radius: 16px;
  padding: 1.2rem 1.5rem;
  color: white;
  font-family: 'DM Sans', sans-serif;
  text-align: center;
  min-width: 110px;
}
.hc-chip-num {
  display: block;
  font-family: 'Playfair Display', serif;
  font-size: 2.8rem;
  font-weight: 900;
  line-height: 1;
  color: var(--gold);
}
.hc-chip-label {
  font-size: .72rem;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: rgba(255,255,255,.5);
}

/* ── Progress Bar ─────────────────────────────────────────── */
.hc-progress-rail {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: rgba(255,255,255,.08);
  z-index: 20;
}
.hc-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--pp-light), var(--gold));
  width: 0%;
  transition: width linear;
}

/* ── Navigation ───────────────────────────────────────────── */
.hc-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 20;
  display: flex;
  flex-direction: column;
  gap: .6rem;
  left: calc(50% - 28px);  /* sits on the seam between panels */
}

.hc-nav-btn {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 1.5px solid rgba(255,255,255,.22);
  background: rgba(10,0,16,.6);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  backdrop-filter: blur(8px);
  transition: background .25s, border-color .25s, transform .2s;
  font-size: .9rem;
}
.hc-nav-btn:hover {
  background: var(--pp);
  border-color: var(--pp);
  transform: scale(1.1);
}

/* ── Dot Indicators ───────────────────────────────────────── */
.hc-dots {
  position: absolute;
  left: 7vw;
  bottom: 2rem;
  display: flex;
  gap: .5rem;
  z-index: 20;
}
.hc-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(255,255,255,.25);
  cursor: pointer;
  transition: all .3s;
}
.hc-dot.active {
  background: var(--gold);
  transform: scale(1.4);
}

/* ── Slide Counter ────────────────────────────────────────── */
.hc-counter {
  position: absolute;
  right: 2.5rem;
  top: 2rem;
  z-index: 20;
  font-family: 'DM Sans', sans-serif;
  font-size: .8rem;
  color: rgba(255,255,255,.4);
}
.hc-counter strong {
  font-family: 'Playfair Display', serif;
  font-size: 1.6rem;
  color: rgba(255,255,255,.9);
  vertical-align: middle;
  line-height: 1;
}

/* ── Pause Button ─────────────────────────────────────────── */
.hc-pause {
  position: absolute;
  right: 2.5rem;
  bottom: 1.5rem;
  z-index: 20;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1.5px solid rgba(255,255,255,.18);
  background: rgba(10,0,16,.5);
  color: rgba(255,255,255,.6);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  backdrop-filter: blur(8px);
  transition: all .25s;
  font-size: .75rem;
}
.hc-pause:hover { color: white; border-color: white; }

/* ── Stagger animation on text elements when slide activates ─ */
.hc-slide.entering .hc-tag,
.hc-slide.entering .hc-title,
.hc-slide.entering .hc-desc,
.hc-slide.entering .hc-pills,
.hc-slide.entering .hc-cta {
  animation: textReveal .6s cubic-bezier(.22,1,.36,1) both;
}
.hc-slide.entering .hc-title  { animation-delay: .1s; }
.hc-slide.entering .hc-desc   { animation-delay: .18s; }
.hc-slide.entering .hc-pills  { animation-delay: .24s; }
.hc-slide.entering .hc-cta    { animation-delay: .32s; }

@keyframes textReveal {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 900px) {
  .hc-slide { grid-template-columns: 1fr; grid-template-rows: 1fr 1fr; }
  .hc-left  { padding: 3rem 2rem 2rem; justify-content: flex-end; }
  .hc-right { grid-row: 1; }
  .hc-right::before { background: linear-gradient(0deg, #0a0010 0%, transparent 50%); }
  .hc-stripe { display: none; }
  .hc-nav { left: 50%; transform: translate(-50%, -50%); flex-direction: row; }
  .hc-dots { left: 50%; transform: translateX(-50%); }
  .hc-chip { bottom: calc(50% + 1rem); right: 1.5rem; }
  .events-hero { height: 100vh; }
}

@media (max-width: 480px) {
  .hc-title { font-size: 2.2rem; }
  .hc-left { padding: 2rem 1.5rem 2rem; }
}
</style>

<!-- Hero Section
<section class="events-hero">
  <div class="container events-hero-content" data-aos="fade-up">
    <h1 class="events-hero-title">Upcoming Events</h1>
    <p class="events-hero-subtitle">
      Join us for powerful gatherings, worship nights, creative workshops, and community events
    </p>
  </div>

  In the hero section, after the subtitle
<div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
  <a href="<?= SITE_URL ?>/pages/events" class="btn btn-primary">
    <i class="fas fa-calendar-alt"></i> Upcoming Events
  </a>
  <a href="<?= SITE_URL ?>/pages/events/past" class="btn btn-outline" style="color: white; border-color: white;">
    <i class="fas fa-history"></i> Past Events
  </a>
</div>
</section> -->

<!-- ═══ HERO SECTION ═══════════════════════════════════════════ -->
 <div id="three-canvas-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: -1; pointer-events: none;"></div>
<section class="events-hero">

  <!-- Slide Track -->
  <div class="hc-track" id="hcTrack">

    <!-- ── Slide 1: Scripts on Scrolls ── -->
    <div class="hc-slide active" data-index="0">
      <div class="hc-left">
        <span class="hc-tag">Annual Initiative</span>
        <h2 class="hc-title">Scripts on<br><em>Scrolls</em></h2>
        <p class="hc-desc">An annual initiative of Scribes Poetry organized in September at Accra, Ghana. Harboring young talents, all chapters and ministries propagating the gospel through poetry, spoken word, and worship.</p>
        <div class="hc-pills">
          <span class="hc-pill"><i class="fas fa-calendar-alt"></i> September 2024</span>
          <span class="hc-pill"><i class="fas fa-map-marker-alt"></i> Accra, Ghana</span>
          <span class="hc-pill"><i class="fas fa-users"></i> All Chapters</span>
        </div>
        <a href="<?= SITE_URL ?>/pages/events/scripts-on-scrolls" class="hc-cta">
          Discover More <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      <div class="hc-right">
        <div class="hc-img" style="background-image: url('<?= ASSETS_PATH ?>/images/sos.jpg');"></div>
        <div class="hc-stripe"></div>
        <div class="hc-chip">
          <span class="hc-chip-num">01</span>
          <span class="hc-chip-label">Event</span>
        </div>
      </div>
    </div>

    <!-- ── Slide 2: Exhale ── -->
    <div class="hc-slide" data-index="1">
      <div class="hc-left">
        <span class="hc-tag">Commemorative Event</span>
        <h2 class="hc-title">Exhale</h2>
        <p class="hc-desc">Commemorating Ghana's Independence Day, Scribes came together to speak about freedom in Christ. Highlighting mental health awareness and depression, climaxed with medleys of music and testimonies.</p>
        <div class="hc-pills">
          <span class="hc-pill"><i class="fas fa-calendar-alt"></i> March 6, 2024</span>
          <span class="hc-pill"><i class="fas fa-flag"></i> Independence Celebration</span>
          <span class="hc-pill"><i class="fas fa-heart"></i> Mental Health</span>
        </div>
        <a href="<?= SITE_URL ?>/pages/events/exhale" class="hc-cta">
          Discover More <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      <div class="hc-right">
        <div class="hc-img" style="background-image: url('<?= ASSETS_PATH ?>/images/exhale.png');"></div>
        <div class="hc-stripe"></div>
        <div class="hc-chip">
          <span class="hc-chip-num">02</span>
          <span class="hc-chip-label">Event</span>
        </div>
      </div>
    </div>

    <!-- ── Slide 3: The TRUTH ── -->
    <div class="hc-slide" data-index="2">
      <div class="hc-left">
        <span class="hc-tag">Tri-Annual Bible Study</span>
        <h2 class="hc-title">The <em>T.R.U.T.H</em></h2>
        <p class="hc-desc">A tri-annual Bible feast aimed at deepening knowledge of the Word. Take it, Read it, Understand it, Teach it and Heed it — organized in March, July &amp; November.</p>
        <div class="hc-pills">
          <span class="hc-pill"><i class="fas fa-calendar-alt"></i> March · July · Nov</span>
          <span class="hc-pill"><i class="fas fa-bible"></i> Bible Study</span>
          <span class="hc-pill"><i class="fas fa-users"></i> Open to Public</span>
        </div>
        <a href="<?= SITE_URL ?>/pages/events/the-truth" class="hc-cta">
          Discover More <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      <div class="hc-right">
        <div class="hc-img" style="background-image: url('<?= ASSETS_PATH ?>/images/TRUTH.jpg');"></div>
        <div class="hc-stripe"></div>
        <div class="hc-chip">
          <span class="hc-chip-num">03</span>
          <span class="hc-chip-label">Event</span>
        </div>
      </div>
    </div>

    <!-- ── Slide 4: Rekindle ── -->
    <div class="hc-slide" data-index="3">
      <div class="hc-left">
        <span class="hc-tag">Intimate Worship</span>
        <h2 class="hc-title">Rekindle</h2>
        <p class="hc-desc">An intimate worship experience geared at revival and repositioning. Intentional worship where the spontaneous happens — reigniting dwindled fire at the start of each year.</p>
        <div class="hc-pills">
          <span class="hc-pill"><i class="fas fa-calendar-alt"></i> Early 2024</span>
          <span class="hc-pill"><i class="fas fa-music"></i> Worship Night</span>
          <span class="hc-pill"><i class="fas fa-fire"></i> Spiritual Revival</span>
        </div>
        <a href="<?= SITE_URL ?>/pages/events/rekindle" class="hc-cta">
          Discover More <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      <div class="hc-right">
        <div class="hc-img" style="background-image: url('<?= ASSETS_PATH ?>/images/rekindle.jpg');"></div>
        <div class="hc-stripe"></div>
        <div class="hc-chip">
          <span class="hc-chip-num">04</span>
          <span class="hc-chip-label">Event</span>
        </div>
      </div>
    </div>

  </div><!-- /hc-track -->

  <!-- Navigation (on the seam) -->
  <div class="hc-nav">
    <button class="hc-nav-btn" id="hcPrev" aria-label="Previous"><i class="fas fa-chevron-up"></i></button>
    <button class="hc-nav-btn" id="hcNext" aria-label="Next"><i class="fas fa-chevron-down"></i></button>
  </div>

  <!-- Dot indicators -->
  <div class="hc-dots" id="hcDots">
    <span class="hc-dot active" data-i="0"></span>
    <span class="hc-dot" data-i="1"></span>
    <span class="hc-dot" data-i="2"></span>
    <span class="hc-dot" data-i="3"></span>
  </div>

  <!-- Slide counter (top right) -->
  <div class="hc-counter">
    <strong id="hcCurrent">01</strong> / 04
  </div>

  <!-- Pause button -->
  <button class="hc-pause" id="hcPause" aria-label="Pause slideshow">
    <i class="fas fa-pause"></i>
  </button>

  <!-- Progress bar -->
  <div class="hc-progress-rail">
    <div class="hc-progress-bar" id="hcBar"></div>
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



<script>
(function () {
  'use strict';

  const DURATION = 6000; // ms per slide
  const slides   = document.querySelectorAll('.hc-slide');
  const dots     = document.querySelectorAll('.hc-dot');
  const bar      = document.getElementById('hcBar');
  const counter  = document.getElementById('hcCurrent');
  const pauseBtn = document.getElementById('hcPause');
  const total    = slides.length;

  let current   = 0;
  let playing   = true;
  let timer     = null;
  let barTimer  = null;
  let barStart  = null;
  let paused    = false;

  // ── helpers ─────────────────────────────────────────────────
  function pad(n) { return String(n + 1).padStart(2, '0'); }

  function updateDots(idx) {
    dots.forEach((d, i) => d.classList.toggle('active', i === idx));
  }

  function updateCounter(idx) {
    counter.textContent = pad(idx);
  }

  // Progress bar animation
  function startBar() {
    bar.style.transition = 'none';
    bar.style.width = '0%';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        bar.style.transition = `width ${DURATION}ms linear`;
        bar.style.width = '100%';
      });
    });
  }

  function pauseBar() {
    const computed = getComputedStyle(bar).width;
    const pct = (parseFloat(computed) / bar.parentElement.offsetWidth) * 100;
    bar.style.transition = 'none';
    bar.style.width = pct + '%';
  }

  function resumeBar(remaining) {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        bar.style.transition = `width ${remaining}ms linear`;
        bar.style.width = '100%';
      });
    });
  }

  // ── core transition ──────────────────────────────────────────
  function goTo(idx, noReset) {
    if (idx === current) return;
    const prev = current;
    current = (idx + total) % total;

    slides[prev].classList.remove('active', 'entering');
    slides[prev].classList.add('leaving');
    slides[prev].addEventListener('animationend', function handler() {
      slides[prev].classList.remove('leaving');
      slides[prev].removeEventListener('animationend', handler);
    });

    slides[current].classList.add('active', 'entering');
    slides[current].addEventListener('animationend', function handler() {
      slides[current].classList.remove('entering');
      slides[current].removeEventListener('animationend', handler);
    }, { once: true });

    updateDots(current);
    updateCounter(current);

    if (!noReset) {
      resetTimer();
      startBar();
    }
  }

  // ── auto-play ────────────────────────────────────────────────
  let sliceStart = null;
  let sliceElapsed = 0;

  function resetTimer() {
    clearTimeout(timer);
    sliceElapsed = 0;
    sliceStart = Date.now();
    if (playing) {
      timer = setTimeout(advance, DURATION);
    }
  }

  function advance() {
    goTo(current + 1);
  }

  function pausePlay() {
    if (!playing) return;
    sliceElapsed += Date.now() - sliceStart;
    clearTimeout(timer);
    pauseBar();
    playing = false;
    pauseBtn.innerHTML = '<i class="fas fa-play"></i>';
  }

  function resumePlay() {
    if (playing) return;
    playing = true;
    sliceStart = Date.now();
    const remaining = DURATION - sliceElapsed;
    timer = setTimeout(advance, remaining);
    resumeBar(remaining);
    pauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
  }

  // ── wire events ──────────────────────────────────────────────
  document.getElementById('hcNext').addEventListener('click', () => { goTo(current + 1); });
  document.getElementById('hcPrev').addEventListener('click', () => { goTo(current - 1); });

  dots.forEach(dot => {
    dot.addEventListener('click', () => goTo(+dot.dataset.i));
  });

  pauseBtn.addEventListener('click', () => playing ? pausePlay() : resumePlay());

  // Pause on hover
  const hero = document.querySelector('.events-hero');
  hero.addEventListener('mouseenter', pausePlay);
  hero.addEventListener('mouseleave', resumePlay);

  // Touch swipe support
  let touchStartX = 0;
  hero.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
  hero.addEventListener('touchend', e => {
    const diff = touchStartX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) goTo(diff > 0 ? current + 1 : current - 1);
  });

  // Keyboard support
  document.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') goTo(current + 1);
    if (e.key === 'ArrowLeft'  || e.key === 'ArrowUp')   goTo(current - 1);
  });

  // ── boot ─────────────────────────────────────────────────────
  resetTimer();
  startBar();
  updateDots(0);
  updateCounter(0);

})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>