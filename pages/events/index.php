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

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,600;0,9..144,700;1,9..144,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />

<style>
/* ─── Root Variables ──────────────────────────────────────────── */
:root {
  --primary-purple: #6B46C1;
  --primary-gold: #D4AF37;
  --secondary-gold-light: #F2D97A;
  --primary-coral: #EB5757;
  --pulse-purple: #a855f7;
  --dark-bg: #1A1A2E;
  --white: #FFFFFF;
  --gray-100: #F7FAFC;
  --gray-200: #EDF2F7;
  --gray-600: #718096;
  --gray-700: #4A5568;
  --font-heading: 'Fraunces', Georgia, serif;
  --font-body: 'DM Sans', sans-serif;
  --radius-full: 9999px;
  --radius-2xl: 24px;
  --radius-xl: 16px;
  --radius-lg: 12px;
  --radius-md: 8px;
  --transition-base: 300ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* ─── Bento Hero Wrapper ──────────────────────────────────────── */
#sg-bento-hero {
  background: #F5F0E8;
  padding: 3rem 1.5rem 2rem;
  font-family: 'DM Sans', sans-serif;
}

#sg-bento-hero *,
#sg-bento-hero *::before,
#sg-bento-hero *::after {
  box-sizing: border-box;
}

/* ─── Page header inside hero ─────────────────────────────────── */
.sg-bento-header {
  max-width: 72rem;
  margin: 0 auto 2rem;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.sg-bento-header-eyebrow {
  font-family: 'DM Sans', sans-serif;
  font-size: 0.7rem;
  font-weight: 400;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  color: rgba(26, 26, 26, 0.4);
  margin: 0 0 0.25rem;
}

.sg-bento-header-title {
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: clamp(24px, 2.8vw, 40px);
  letter-spacing: -1px;
  color: #1A1A1A;
  margin: 0;
  line-height: 1.1;
}

.sg-bento-header-title em {
  font-style: normal;
  font-weight: 300;
}

.sg-bento-header-hint {
  font-family: 'DM Sans', sans-serif;
  font-size: 0.75rem;
  color: rgba(26, 26, 26, 0.35);
  white-space: nowrap;
}

/* ─── Bento Grid ──────────────────────────────────────────────── */
#sg-bento-wrap {
  max-width: 72rem;
  margin: 0 auto;
  position: relative;
}

#sg-bento-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  grid-template-rows: 1fr 1fr;
  gap: 14px;
  height: 580px;
}

#sg-card-main  { grid-column: 1; grid-row: 1 / 3; }
#sg-card-red   { grid-column: 2; grid-row: 1 / 2; }
#sg-card-black { grid-column: 2; grid-row: 2 / 3; }
#sg-card-gold  { grid-column: 3; grid-row: 1 / 3; }

/* ─── Cards shared ────────────────────────────────────────────── */
.sg-bento-card {
  border-radius: 20px;
  overflow: hidden;
  position: relative;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  opacity: 0;
  animation: sg-slideUp 0.55s forwards;
  cursor: pointer;
}

.sg-bento-card::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
}

.sg-bento-card:not(#sg-card-main):hover,
#sg-card-gold:hover {
  transform: scale(1.015);
  box-shadow: 0 12px 40px rgba(0,0,0,0.18);
}

@keyframes sg-slideUp {
  from { opacity: 0; transform: translateY(22px); }
  to   { opacity: 1; transform: translateY(0); }
}

#sg-card-main  { animation-delay: 0.05s; }
#sg-card-red   { animation-delay: 0.18s; }
#sg-card-black { animation-delay: 0.30s; }
#sg-card-gold  { animation-delay: 0.42s; }

/* ─── Card: Main (White) ──────────────────────────────────────── */
.sg-card-main-inner {
  background: #fff url('<?= ASSETS_PATH ?>images/sos.jpg') center/cover;
  background-attachment: fixed;
  height: 100%;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
}

.sg-card-main-inner::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.92) 0%, rgba(255, 255, 255, 0.88) 100%);
  z-index: 1;
  pointer-events: none;
}

.sg-pill-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(232, 221, 208, 0.95);
  padding: 0.375rem 1rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-family: 'DM Sans', sans-serif;
  font-weight: 500;
  color: rgba(26,26,26,0.7);
  align-self: flex-start;
  z-index: 2;
  position: relative;
}

.sg-pulse-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background: #C0392B;
  animation: sg-pulse 2s ease-in-out infinite;
}

@keyframes sg-pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.6; transform: scale(0.85); }
}

.sg-card-title {
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: clamp(26px, 3vw, 44px);
  letter-spacing: -1.5px;
  line-height: 1;
  color: #1A1A1A;
  margin: 0 0 1.5rem;
  z-index: 2;
  position: relative;
}

.sg-card-title em {
  font-style: normal;
  font-weight: 300;
}

.sg-btn-dark {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #1A1A1A;
  color: #F5F0E8;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.75rem 1.5rem;
  border-radius: 9999px;
  border: none;
  cursor: pointer;
  transition: background 0.2s;
  text-decoration: none;
  z-index: 2;
  position: relative;
}

.sg-btn-dark:hover { background: rgba(26,26,26,0.8); }

.sg-btn-dark svg {
  transition: transform 0.2s;
}

.sg-btn-dark:hover svg {
  transform: translateX(3px);
}

/* Stats strip */
.sg-stats-row {
  display: flex;
  gap: 1.5rem;
  margin-top: 1rem;
  z-index: 2;
  position: relative;
}

.sg-stat-divider {
  width: 1px;
  background: rgba(26,26,26,0.1);
  align-self: stretch;
}

.sg-stat-value {
  font-family: var(--font-heading);
  font-weight: 600;
  font-size: 1.5rem;
  letter-spacing: -0.5px;
  color: #1A1A1A;
  margin: 0;
}

.sg-stat-label {
  font-family: 'DM Sans', sans-serif;
  font-size: 0.7rem;
  color: rgba(26,26,26,0.4);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 0.125rem;
}

/* ─── Card: Red ───────────────────────────────────────────────── */
.sg-card-red-inner {
  background: linear-gradient(135deg, rgba(192, 57, 43, 0.92) 0%, rgba(192, 57, 43, 0.88) 100%), 
              url('<?= ASSETS_PATH ?>images/exhale.png') center/cover;
  height: 100%;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
}

/* ─── Card: Black ─────────────────────────────────────────────── */
.sg-card-black-inner {
  background: linear-gradient(135deg, rgba(26, 26, 26, 0.93) 0%, rgba(26, 26, 26, 0.90) 100%), 
              url('<?= ASSETS_PATH ?>images/TRUTH.jpg') center/cover;
  height: 100%;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
}

/* ─── Card: Gold ──────────────────────────────────────────────── */
.sg-card-gold-inner {
  background: linear-gradient(160deg, rgba(44, 24, 16, 0.94) 0%, rgba(26, 15, 8, 0.92) 100%), 
              url('<?= ASSETS_PATH ?>images/rekindle.jpg') center/cover;
  height: 100%;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
}

.sg-gold-lines {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background-image: repeating-linear-gradient(
    135deg,
    rgba(255,255,255,0.03) 0px,
    rgba(255,255,255,0.03) 1px,
    transparent 1px,
    transparent 12px
  );
}

/* ─── Shared card text helpers ────────────────────────────────── */
.sg-card-eyebrow {
  font-family: 'DM Sans', sans-serif;
  font-size: 0.7rem;
  font-weight: 400;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  margin: 0 0 0.75rem;
  position: relative;
  z-index: 2;
}

.sg-card-heading {
  font-family: var(--font-heading);
  font-weight: 600;
  line-height: 1.15;
  margin: 0;
  letter-spacing: -0.5px;
  position: relative;
  z-index: 2;
}

.sg-card-heading em {
  font-style: normal;
}

/* Expand hint arrow */
.sg-expand-hint {
  position: absolute;
  bottom: 16px;
  right: 16px;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  z-index: 2;
}

/* Avatars */
.sg-avatar {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  border: 2px solid #1A1A1A;
  margin-left: -10px;
  position: relative;
  z-index: 2;
}

.sg-avatar:first-child { margin-left: 0; }

/* ─── Expanded Clone ──────────────────────────────────────────── */
#sg-expand-clone {
  position: absolute;
  border-radius: 20px;
  overflow: hidden;
  z-index: 50;
  pointer-events: none;
}

#sg-expand-clone.sg-settled { pointer-events: auto; }

.sg-card-ghost { opacity: 0 !important; pointer-events: none; }

.sg-expanded-content {
  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.35s 0.25s ease, transform 0.35s 0.25s ease;
}

.sg-expanded-content.sg-visible {
  opacity: 1;
  transform: translateY(0);
}

.sg-chart-line {
  stroke-dasharray: 600;
  stroke-dashoffset: 600;
  transition: stroke-dashoffset 1.1s 0.4s cubic-bezier(0.4,0,0.2,1);
}

.sg-chart-line.sg-drawn { stroke-dashoffset: 0; }

/* Close button */
#sg-close-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  border: none;
  transition: transform 0.2s, background 0.2s;
  backdrop-filter: blur(10px);
}

#sg-close-btn:hover { transform: scale(1.1); }

/* ─── MOBILE RESPONSIVE ───────────────────────────────────────── */

@media (max-width: 1024px) {
  #sg-bento-grid {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto auto;
    height: auto;
    gap: 12px;
  }
  
  #sg-card-main  { grid-column: 1 / 3; grid-row: 1; min-height: 280px; }
  #sg-card-red   { grid-column: 1;     grid-row: 2; min-height: 200px; }
  #sg-card-black { grid-column: 2;     grid-row: 2; min-height: 200px; }
  #sg-card-gold  { grid-column: 1 / 3; grid-row: 3; min-height: 200px; }

  .sg-card-main-inner { padding: 1.75rem; }
  .sg-stats-row { flex-wrap: wrap; gap: 1rem; }
}

@media (max-width: 768px) {
  #sg-bento-hero {
    padding: 2rem 1rem 1.5rem;
  }

  .sg-bento-header {
    margin: 0 auto 1.5rem;
    gap: 0.5rem;
    justify-content: center;
    flex-direction: column;
    text-align: center;
  }

  .sg-bento-header-hint {
    display: none;
  }

  #sg-bento-grid {
    grid-template-columns: 1fr;
    grid-template-rows: auto;
    gap: 10px;
  }

  #sg-card-main,
  #sg-card-red,
  #sg-card-black,
  #sg-card-gold  { 
    grid-column: 1; 
    grid-row: auto; 
    min-height: 220px; 
  }

  #sg-card-main { min-height: 260px; }

  .sg-card-main-inner {  
    padding: 1.5rem; 
  }

  .sg-card-red-inner,
  .sg-card-black-inner,
  .sg-card-gold-inner {
    padding: 1.25rem;
  }

  .sg-card-title {
    font-size: clamp(20px, 2.5vw, 28px);
    margin-bottom: 1rem;
  }

  .sg-card-heading {
    font-size: clamp(16px, 2vw, 22px);
  }

  .sg-btn-dark {
    padding: 0.65rem 1.25rem;
    font-size: 0.8rem;
  }

  .sg-stats-row {
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.85rem;
  }

  .sg-stat-value {
    font-size: 1.25rem;
  }

  .sg-pill-badge {
    font-size: 0.7rem;
    padding: 0.35rem 0.9rem;
  }

  .sg-card-eyebrow {
    font-size: 0.65rem;
  }

  .sg-expand-hint {
    width: 24px;
    height: 24px;
    bottom: 12px;
    right: 12px;
  }

  .sg-expand-hint svg {
    width: 10px;
    height: 10px;
  }

  p[style*="text-align:center"] {
    font-size: 0.8rem;
    margin-top: 1rem;
  }
}

@media (max-width: 480px) {
  #sg-bento-hero {
    padding: 1.5rem 0.75rem 1rem;
  }

  .sg-bento-header {
    margin: 0 auto 1rem;
  }

  .sg-bento-header-title {
    font-size: clamp(20px, 2.2vw, 24px);
  }

  .sg-bento-header-eyebrow {
    font-size: 0.65rem;
  }

  #sg-bento-grid {
    gap: 8px;
  }

  #sg-card-main { min-height: 240px; }
  #sg-card-red,
  #sg-card-black,
  #sg-card-gold { min-height: 180px; }

  .sg-card-main-inner,
  .sg-card-red-inner,
  .sg-card-black-inner,
  .sg-card-gold-inner {
    padding: 1rem;
  }

  .sg-card-title {
    font-size: clamp(18px, 2vw, 22px);
    margin-bottom: 0.75rem;
  }

  .sg-card-heading {
    font-size: clamp(14px, 1.8vw, 18px);
  }

  .sg-btn-dark {
    padding: 0.6rem 1rem;
    font-size: 0.75rem;
  }

  .sg-stats-row {
    gap: 0.75rem;
    margin-top: 0.75rem;
  }

  .sg-stat-value {
    font-size: 1.1rem;
  }

  .sg-stat-label {
    font-size: 0.6rem;
  }

  .sg-pill-badge {
    font-size: 0.65rem;
    padding: 0.3rem 0.8rem;
  }
}

/* ─── Other page styles ────────────────────────────────────────── */
.countdown-timer {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin: 1rem 0;
}

.countdown-item { text-align: center; }

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

<!-- ══ BENTO HERO ════════════════════════════════════════════════ -->
<section id="sg-bento-hero">

  <!-- Header -->
  <div class="sg-bento-header">
    <div>
      <p class="sg-bento-header-eyebrow">Scribes Global</p>
      <h1 class="sg-bento-header-title">
        Our <em>Events</em>
      </h1>
    </div>
    <p class="sg-bento-header-hint">Click any card to explore ↗</p>
  </div>

  <!-- Bento grid wrapper -->
  <div id="sg-bento-wrap">
    <div id="sg-bento-grid">

      <!-- ══ CARD 1: WHITE — Scripts on Scrolls ════════════════ -->
      <div id="sg-card-main" class="sg-bento-card"
           data-sg-expandable="true"
           data-sg-expand-bg="#FFFFFF"
           data-sg-expand-bg-image="<?= ASSETS_PATH ?>images/events/scripts-on-scrolls.jpg"
           data-sg-expand-theme="white">
        <div class="sg-card-main-inner">

          <div class="sg-pill-badge">
            <span class="sg-pulse-dot"></span>
            Annual Initiative · September
          </div>

          <div>
            <h2 class="sg-card-title">
              Scripts on<br/>
              <em>Scrolls</em>
            </h2>
            <button id="sg-main-cta" class="sg-btn-dark">
              Discover More
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M1 7h12M7 1l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <!-- Stats -->
          <div class="sg-stats-row">
            <div>
              <p class="sg-stat-value">Sept</p>
              <p class="sg-stat-label">Annually held</p>
            </div>
            <div class="sg-stat-divider"></div>
            <div>
              <p class="sg-stat-value">Accra</p>
              <p class="sg-stat-label">Ghana</p>
            </div>
            <div class="sg-stat-divider"></div>
            <div>
              <p class="sg-stat-value">All</p>
              <p class="sg-stat-label">Chapters welcome</p>
            </div>
          </div>

        </div>
      </div><!-- /sg-card-main -->


      <!-- ══ CARD 2: RED — Exhale ═══════════════════════════════ -->
      <div id="sg-card-red" class="sg-bento-card"
           data-sg-expandable="true"
           data-sg-expand-bg="#C0392B"
           data-sg-expand-bg-image="<?= ASSETS_PATH ?>images/events/exhale-bg.jpg"
           data-sg-expand-theme="light">
        <div class="sg-card-red-inner">

          <div>
            <p class="sg-card-eyebrow" style="color:rgba(255,255,255,0.7);">Commemorative Event</p>
            <h2 class="sg-card-heading" style="color:#fff; font-size:clamp(18px,1.8vw,26px);">
              Exhale
            </h2>
          </div>

          <div class="sg-expand-hint" style="background:rgba(255,255,255,0.2);">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
              <path d="M2 10L10 2M10 2H4M10 2v6" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>

        </div>
      </div><!-- /sg-card-red -->


      <!-- ══ CARD 3: BLACK — The T.R.U.T.H ═════════════════════ -->
      <div id="sg-card-black" class="sg-bento-card"
           data-sg-expandable="true"
           data-sg-expand-bg="#1A1A1A"
           data-sg-expand-bg-image="<?= ASSETS_PATH ?>images/events/truth-bg.jpg"
           data-sg-expand-theme="dark">
        <div class="sg-card-black-inner">

          <div>
            <p class="sg-card-eyebrow" style="color:rgba(255,255,255,0.4);">Tri-Annual Bible Study</p>
            <h2 class="sg-card-heading" style="color:#fff; font-size:clamp(16px,1.6vw,22px);">
              The <em>T.R.U.T.H</em>
            </h2>
          </div>

          <div>
            <div style="display:flex; position: relative; z-index: 2;">
              <div class="sg-avatar" style="background:linear-gradient(135deg,#34d399,#059669);">M</div>
              <div class="sg-avatar" style="background:linear-gradient(135deg,#a78bfa,#7c3aed);">J</div>
              <div class="sg-avatar" style="background:linear-gradient(135deg,#fb923c,#ea580c);">N</div>
              <div class="sg-avatar" style="background:rgba(255,255,255,0.1);">+</div>
            </div>
            <p style="color:rgba(255,255,255,0.4); font-size:0.7rem; font-family:'DM Sans',sans-serif; margin-top:0.5rem; position: relative; z-index: 2;">Mar · Jul · Nov</p>
          </div>

          <div class="sg-expand-hint" style="background:rgba(255,255,255,0.1);">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
              <path d="M2 10L10 2M10 2H4M10 2v6" stroke="rgba(255,255,255,0.6)" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>

        </div>
      </div><!-- /sg-card-black -->


      <!-- ══ CARD 4: GOLD — Rekindle ═══════════════════════════ -->
      <div id="sg-card-gold" class="sg-bento-card"
           data-sg-expandable="true"
           data-sg-expand-bg="#2C1810"
           data-sg-expand-bg-image="<?= ASSETS_PATH ?>images/events/rekindle-bg.jpg"
           data-sg-expand-theme="dark">
        <div class="sg-card-gold-inner">
          <div class="sg-gold-lines"></div>

          <div style="position:relative; z-index:1;">
            <p class="sg-card-eyebrow" style="color:rgba(251,191,36,0.7);">Intimate Worship</p>
            <h2 class="sg-card-heading" style="color:#fef3c7; font-size:clamp(18px,1.8vw,26px);">
              Rekindle
            </h2>
          </div>

          <div class="sg-expand-hint" style="background:rgba(251,191,36,0.2);">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
              <path d="M2 10L10 2M10 2H4M10 2v6" stroke="#fbbf24" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>

        </div>
      </div><!-- /sg-card-gold -->

    </div><!-- /sg-bento-grid -->
  </div><!-- /sg-bento-wrap -->

  <p style="text-align:center; color:rgba(26,26,26,0.35); font-size:0.875rem; font-family:'DM Sans',sans-serif; margin-top:1.5rem;">
    Click any card to explore each event ↑
  </p>

</section><!-- /#sg-bento-hero -->


<!-- ══ EXPANDED CONTENT TEMPLATES ════════════════════════════════ -->

<template id="sg-tpl-card-main">
  <div style="position:absolute;inset:0;display:flex;flex-direction:column;padding:2.5rem;padding-top:3.5rem;background-image: url('<?= ASSETS_PATH ?>images/sos.jpg'); background-size: cover; background-position: center;">
    <div style="position:absolute;inset:0;background: linear-gradient(135deg, rgba(255, 255, 255, 0.94) 0%, rgba(255, 255, 255, 0.92) 100%); z-index: 0;"></div>
    <div class="sg-expanded-content" style="position: relative; z-index: 1;">
      <p style="color:rgba(26,26,26,0.5);font-size:0.875rem;font-family:'DM Sans',sans-serif;text-transform:uppercase;letter-spacing:0.1em;margin:0 0 0.25rem;">Annual Initiative</p>
      <h2 style="font-family:var(--font-heading);font-weight:600;font-size:clamp(28px,3.5vw,52px);letter-spacing:-1.5px;line-height:1.05;color:#1A1A1A;margin:0 0 0.5rem;">
        Scripts on<br/><span style="font-weight:300;">Scrolls</span>
      </h2>
      <p style="color:rgba(26,26,26,0.55);font-family:'DM Sans',sans-serif;font-size:0.875rem;max-width:32rem;line-height:1.7;margin:0 0 2rem;">
        An annual initiative of Scribes Poetry organized in September at Accra, Ghana. Harboring young talents, all chapters and ministries propagating the gospel through poetry, spoken word, and worship.
      </p>
    </div>
    <div class="sg-expanded-content" style="flex:1;position:relative;border-radius:1rem;overflow:hidden;background:#F5F0E8; z-index: 1;">
      <img 
        src="<?= ASSETS_PATH ?>images/events/scripts-on-scrolls-detailed.jpg"
        alt="Scripts on Scrolls"
        style="width:100%;height:100%;object-fit:cover;"
        onerror="this.style.display='none'"
      >
    </div>
    <div class="sg-expanded-content" style="display:flex;gap:2rem;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid rgba(26,26,26,0.1);align-items:flex-end; position: relative; z-index: 1;">
      <div>
        <p style="font-family:var(--font-heading);font-weight:600;font-size:1.5rem;letter-spacing:-0.5px;color:#1A1A1A;margin:0;">September</p>
        <p style="color:rgba(26,26,26,0.4);font-size:0.7rem;font-family:'DM Sans',sans-serif;text-transform:uppercase;letter-spacing:0.1em;margin:0.125rem 0 0;">Annual date</p>
      </div>
      <div>
        <p style="font-family:var(--font-heading);font-weight:600;font-size:1.5rem;letter-spacing:-0.5px;color:#1A1A1A;margin:0;">All chapters</p>
        <p style="color:rgba(26,26,26,0.4);font-size:0.7rem;font-family:'DM Sans',sans-serif;text-transform:uppercase;letter-spacing:0.1em;margin:0.125rem 0 0;">Open to all</p>
      </div>
      <div>
        <p style="font-family:var(--font-heading);font-weight:600;font-size:1.5rem;letter-spacing:-0.5px;color:#1A1A1A;margin:0;">Poetry + Worship</p>
        <p style="color:rgba(26,26,26,0.4);font-size:0.7rem;font-family:'DM Sans',sans-serif;text-transform:uppercase;letter-spacing:0.1em;margin:0.125rem 0 0;">Format</p>
      </div>
      <div style="margin-left:auto;">
        <button class="sg-btn-dark">Learn More →</button>
      </div>
    </div>
  </div>
</template>

<template id="sg-tpl-card-red">
  <div style="position:absolute;inset:0;display:flex;flex-direction:column;padding:2.5rem;padding-top:3.5rem;background-image: url('<?= ASSETS_PATH ?>images/exhale.png'); background-size: cover; background-position: center;">
    <div style="position:absolute;inset:0;background: linear-gradient(135deg, rgba(192, 57, 43, 0.94) 0%, rgba(192, 57, 43, 0.92) 100%); z-index: 0;"></div>
    <div class="sg-expanded-content" style="position: relative; z-index: 1;">
      <p style="color:rgba(255,255,255,0.6);font-size:0.875rem;font-family:'DM Sans',sans-serif;text-transform:uppercase;letter-spacing:0.1em;margin:0 0 0.25rem;">Commemorative Event</p>
      <h2 style="font-family:var(--font-heading);font-weight:600;font-size:clamp(28px,3.5vw,52px);letter-spacing:-1.5px;line-height:1.05;color:#fff;margin:0 0 0.5rem;">
        Exhale
      </h2>
      <p style="color:rgba(255,255,255,0.55);font-family:'DM Sans',sans-serif;font-size:0.875rem;max-width:32rem;line-height:1.7;margin:0 0 1.5rem;">
        Commemorating Ghana's Independence Day, Scribes came together to speak about freedom in Christ — highlighting mental health awareness and depression, climaxed with medleys of music and testimonies.
      </p>
    </div>
    <div class="sg-expanded-content" style="flex:1;position:relative; z-index: 1;">
      <svg viewBox="0 0 580 200" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:100%;position:absolute;inset:0;">
        <line x1="0" y1="160" x2="580" y2="160" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
        <line x1="0" y1="120" x2="580" y2="120" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
        <line x1="0" y1="80"  x2="580" y2="80"  stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
        <line x1="0" y1="40"  x2="580" y2="40"  stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
        <polygon points="0,160 70,140 140,148 230,100 320,115 410,55 500,70 580,60 580,200 0,200"
                 fill="rgba(255,255,255,0.08)"/>
        <polyline class="sg-chart-line"
                  points="0,160 70,140 140,148 230,100 320,115 410,55 500,70 580,60"
                  stroke="white" stroke-width="3"
                  stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        <circle cx="410" cy="55" r="6" fill="white"/>
      </svg>
    </div>
    <div class="sg-expanded-content" style="display:flex;gap:2rem;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,0.15);align-items:flex-end; position: relative; z-index: 1;">
      <div>
        <p style="font-family:var(--font-heading);font-weight:600;font-size:1.5rem;letter-spacing:-0.5px;color:#fff;margin:0;">March 6</p>
        <p style="color:rgba(255,255,255,0.45);font-size:0.7rem;font-family:'DM Sans',sans-serif;text-transform:uppercase;letter-spacing:0.1em;margin:0.125rem 0 0;">Independence Day</p>
      </div>
      <div>
        <p style="font-family:var(--font-heading);font-weight:600;font-size:1.5rem;letter-spacing:-0.5px;color:#fff;margin:0;">Mental Health</p>
        <p style="color:rgba(255,255,255,0.45);font-size:0.7rem;font-family:'DM Sans',sans-serif;text-transform:uppercase;letter-spacing:0.1em;margin:0.125rem 0 0;">Core theme</p>
      </div>
      <div>
        <p style="font-family:var(--font-heading);font-weight:600;font-size:1.5rem;letter-spacing:-0.5px;color:#fff;margin:0;">Freedom</p>
        <p style="color:rgba(255,255,255,0.45);font-size:0.7rem;font-family:'DM Sans',sans-serif;text-transform:uppercase;letter-spacing:0.1em;margin:0.125rem 0 0;">In Christ</p>
      </div>
      <div style="margin-left:auto;">
        <button style="background:#fff;color:#C0392B;font-family:'DM Sans',sans-serif;font-size:0.875rem;font-weight:500;padding:0.75rem 1.5rem;border-radius:9999px;border:none;cursor:pointer;">Explore Event →</button>
      </div>
    </div>
  </div>
</template>

<template id="sg-tpl-card-black">
  <div style="position:absolute;inset:0;display:flex;flex-direction:column;padding:2.5rem;padding-top:3.5rem;overflow:hidden;background-image: url('<?= ASSETS_PATH ?>images/TRUTH.jpg'); background-size: cover; background-position: center;">
    <div style="position:absolute;inset:0;background: linear-gradient(135deg, rgba(26, 26, 26, 0.94) 0%, rgba(26, 26, 26, 0.92) 100%); z-index: 0;"></div>
    <div class="sg-expanded-content" style="position:relative;z-index:1;">
      <p style="color:rgba(255,255,255,0.5);font-size:0.875rem;font-family:'DM Sans',sans-serif;text-transform:uppercase;letter-spacing:0.1em;margin:0 0 0.25rem;">Tri-Annual Bible Study</p>
      <h2 style="font-family:var(--font-heading);font-weight:600;font-size:clamp(26px,3vw,48px);letter-spacing:-1.5px;line-height:1.05;color:#fff;margin:0 0 1rem;">
        The <em style="font-style:normal;">T.R.U.T.H</em>
      </h2>
      <p style="color:rgba(255,255,255,0.45);font-family:'DM Sans',sans-serif;font-size:0.875rem;max-width:18rem;line-height:1.7;margin:0;">
        Take it. Read it. Understand it. Teach it. Heed it. — A tri-annual Bible feast aimed at deepening knowledge of the Word.
      </p>
    </div>
    <!-- Acronym breakdown -->
    <div class="sg-expanded-content" style="position:relative;z-index:1;margin-top:1.5rem;display:grid;grid-template-columns:repeat(5,1fr);gap:0.5rem;max-width:20rem;">
      <div style="text-align:center;"><span style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;color:#fff;">T</span><p style="color:rgba(255,255,255,0.3);font-size:0.65rem;font-family:'DM Sans',sans-serif;margin:0.25rem 0 0;">Take it</p></div>
      <div style="text-align:center;"><span style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;color:#fff;">R</span><p style="color:rgba(255,255,255,0.3);font-size:0.65rem;font-family:'DM Sans',sans-serif;margin:0.25rem 0 0;">Read it</p></div>
      <div style="text-align:center;"><span style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;color:#fff;">U</span><p style="color:rgba(255,255,255,0.3);font-size:0.65rem;font-family:'DM Sans',sans-serif;margin:0.25rem 0 0;">Understand</p></div>
      <div style="text-align:center;"><span style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;color:#fff;">T</span><p style="color:rgba(255,255,255,0.3);font-size:0.65rem;font-family:'DM Sans',sans-serif;margin:0.25rem 0 0;">Teach it</p></div>
      <div style="text-align:center;"><span style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;color:#fff;">H</span><p style="color:rgba(255,255,255,0.3);font-size:0.65rem;font-family:'DM Sans',sans-serif;margin:0.25rem 0 0;">Heed it</p></div>
    </div>
    <div class="sg-expanded-content" style="position:relative;z-index:1;margin-top:auto;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:space-between;">
      <div>
        <p style="font-family:var(--font-heading);font-weight:600;font-size:1.125rem;letter-spacing:-0.3px;color:#fff;margin:0;">March · July · November</p>
        <p style="color:rgba(255,255,255,0.4);font-size:0.7rem;font-family:'DM Sans',sans-serif;margin:0.125rem 0 0;">Open to the public</p>
      </div>
      <button style="background:#fff;color:#1A1A1A;font-family:'DM Sans',sans-serif;font-size:0.875rem;font-weight:500;padding:0.75rem 1.5rem;border-radius:9999px;border:none;cursor:pointer;">Join a Session →</button>
    </div>
  </div>
</template>

<template id="sg-tpl-card-gold">
  <div style="position:absolute;inset:0;display:flex;flex-direction:column;padding:2.5rem;padding-top:3.5rem;overflow:hidden;background-image: url('<?= ASSETS_PATH ?>images/rekindle.jpg'); background-size: cover; background-position: center;">
    <div style="position:absolute;inset:0;background: linear-gradient(160deg, rgba(44, 24, 16, 0.95) 0%, rgba(26, 15, 8, 0.93) 100%); z-index: 0;"></div>
    <div style="position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at 75% 50%, rgba(249,115,22,0.15) 0%, transparent 60%);"></div>
    <div class="sg-expanded-content" style="position:relative;z-index:1;">
      <p style="color:rgba(251,191,36,0.6);font-size:0.875rem;font-family:'DM Sans',sans-serif;text-transform:uppercase;letter-spacing:0.1em;margin:0 0 0.25rem;">Intimate Worship</p>
      <h2 style="font-family:var(--font-heading);font-weight:600;font-size:clamp(26px,3vw,48px);letter-spacing:-1.5px;line-height:1.05;color:#fef3c7;margin:0 0 1rem;">
        Rekindle
      </h2>
      <p style="color:rgba(253,230,138,0.45);font-family:'DM Sans',sans-serif;font-size:0.875rem;max-width:18rem;line-height:1.7;margin:0;">
        An intimate worship experience geared at revival and repositioning. Intentional worship where the spontaneous happens — reigniting dwindled fire at the start of each year.
      </p>
    </div>
    <!-- Tags -->
    <div class="sg-expanded-content" style="position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:0.5rem;margin-top:1rem;">
      <span style="background:rgba(251,191,36,0.15);color:#fcd34d;font-size:0.75rem;font-family:'DM Sans',sans-serif;padding:0.375rem 0.75rem;border-radius:9999px;border:1px solid rgba(251,191,36,0.2);">🔥 Spiritual Revival</span>
      <span style="background:rgba(251,191,36,0.15);color:#fcd34d;font-size:0.75rem;font-family:'DM Sans',sans-serif;padding:0.375rem 0.75rem;border-radius:9999px;border:1px solid rgba(251,191,36,0.2);">🎵 Worship Night</span>
      <span style="background:rgba(251,191,36,0.15);color:#fcd34d;font-size:0.75rem;font-family:'DM Sans',sans-serif;padding:0.375rem 0.75rem;border-radius:9999px;border:1px solid rgba(251,191,36,0.2);">📅 Early Year</span>
    </div>
    <div class="sg-expanded-content" style="position:relative;z-index:1;margin-top:auto;padding-top:1.5rem;border-top:1px solid rgba(251,191,36,0.15);display:flex;align-items:center;justify-content:space-between;">
      <div>
        <p style="font-family:var(--font-heading);font-weight:600;font-size:1.125rem;letter-spacing:-0.3px;color:#fef3c7;margin:0;">Start of each year</p>
        <p style="color:rgba(251,191,36,0.4);font-size:0.7rem;font-family:'DM Sans',sans-serif;margin:0.125rem 0 0;">Reignite your fire</p>
      </div>
      <button style="background:#F97316;color:#fff;font-family:'DM Sans',sans-serif;font-size:0.875rem;font-weight:500;padding:0.75rem 1.5rem;border-radius:9999px;border:none;cursor:pointer;">Get Involved →</button>
    </div>
  </div>
</template>

<!-- ══ BENTO HERO JAVASCRIPT ════════════════════════════════════ -->
<script>
(function () {
  'use strict';

  const EXPAND_DURATION   = 480;
  const COLLAPSE_DURATION = 400;
  const EASING_OUT = 'cubic-bezier(0.22, 1, 0.36, 1)';
  const EASING_IN  = 'cubic-bezier(0.4, 0, 0.8, 0.6)';

  let isAnimating = false;
  let activeCard  = null;

  const bentoWrap = document.getElementById('sg-bento-wrap');

  if (!bentoWrap) return;

  const templateMap = {
    'sg-card-main':  'sg-tpl-card-main',
    'sg-card-red':   'sg-tpl-card-red',
    'sg-card-black': 'sg-tpl-card-black',
    'sg-card-gold':  'sg-tpl-card-gold',
  };

  function closeBtnHtml(theme) {
    const cls = (theme === 'white')
      ? 'background:rgba(26,26,26,0.1);color:#1A1A1A;'
      : 'background:rgba(255,255,255,0.15);color:#fff;';
    return `<button id="sg-close-btn"
                    style="${cls}"
                    onclick="(function(){document.getElementById('sg-close-btn').dispatchEvent(new CustomEvent('sg-collapse',{bubbles:true}))})()">
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
        <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>`;
  }

  function expandCard(sourceCard) {
    if (isAnimating) return;
    isAnimating = true;
    activeCard  = sourceCard;

    const cardId = sourceCard.id;
    const theme  = sourceCard.dataset.sgExpandTheme;
    const bg     = sourceCard.dataset.sgExpandBg;

    const firstRect = sourceCard.getBoundingClientRect();
    const wrapRect  = bentoWrap.getBoundingClientRect();

    const firstRel = {
      top:    firstRect.top  - wrapRect.top,
      left:   firstRect.left - wrapRect.left,
      width:  firstRect.width,
      height: firstRect.height,
    };

    const tpl = document.getElementById(templateMap[cardId]);
    const tplContent = tpl ? tpl.innerHTML : '';

    const clone = document.createElement('div');
    clone.id = 'sg-expand-clone';
    clone.style.cssText = [
      'position:absolute',
      `top:${firstRel.top}px`,
      `left:${firstRel.left}px`,
      `width:${firstRel.width}px`,
      `height:${firstRel.height}px`,
      `background:${bg}`,
      'border-radius:20px',
      'overflow:hidden',
      'z-index:50',
      'pointer-events:none',
    ].join(';');
    clone.innerHTML = closeBtnHtml(theme) + tplContent;
    bentoWrap.appendChild(clone);

    clone.addEventListener('sg-collapse', collapseCard);

    sourceCard.classList.add('sg-card-ghost');

    const lastW = wrapRect.width;
    const lastH = wrapRect.height;
    const scaleX = firstRel.width  / lastW;
    const scaleY = firstRel.height / lastH;

    const anim = clone.animate([
      {
        transform: `translate(${firstRel.left}px,${firstRel.top}px) scale(${scaleX},${scaleY})`,
        borderRadius: '20px',
        top: '0px', left: '0px',
        width: `${lastW}px`, height: `${lastH}px`,
      },
      {
        transform: 'translate(0,0) scale(1,1)',
        borderRadius: '20px',
        top: '0px', left: '0px',
        width: `${lastW}px`, height: `${lastH}px`,
      },
    ], { duration: EXPAND_DURATION, easing: EASING_OUT, fill: 'forwards' });

    anim.finished.then(() => {
      clone.style.cssText = [
        'position:absolute',
        'top:0', 'left:0',
        `width:${lastW}px`,
        `height:${lastH}px`,
        'transform:none',
        'border-radius:20px',
        'overflow:hidden',
        'z-index:50',
        'pointer-events:auto',
      ].join(';');
      clone.classList.add('sg-settled');
      clone.querySelectorAll('.sg-expanded-content').forEach(el => el.classList.add('sg-visible'));
      clone.querySelectorAll('.sg-chart-line').forEach(el => el.classList.add('sg-drawn'));
      isAnimating = false;
    });
  }

  function collapseCard() {
    if (isAnimating || !activeCard) return;
    isAnimating = true;

    const clone    = document.getElementById('sg-expand-clone');
    if (!clone) { isAnimating = false; return; }

    const wrapRect = bentoWrap.getBoundingClientRect();
    const cardRect = activeCard.getBoundingClientRect();

    const cardRel = {
      top:    cardRect.top  - wrapRect.top,
      left:   cardRect.left - wrapRect.left,
      width:  cardRect.width,
      height: cardRect.height,
    };

    clone.querySelectorAll('.sg-expanded-content').forEach(el => el.classList.remove('sg-visible'));

    const scaleX = cardRel.width  / wrapRect.width;
    const scaleY = cardRel.height / wrapRect.height;

    const anim = clone.animate([
      { transform: 'translate(0,0) scale(1,1)', borderRadius: '20px' },
      { transform: `translate(${cardRel.left}px,${cardRel.top}px) scale(${scaleX},${scaleY})`, borderRadius: '20px' },
    ], { duration: COLLAPSE_DURATION, easing: EASING_IN, fill: 'forwards' });

    anim.finished.then(() => {
      clone.remove();
      activeCard.classList.remove('sg-card-ghost');
      activeCard  = null;
      isAnimating = false;
    });
  }

  document.querySelectorAll('[data-sg-expandable="true"]').forEach(card => {
    card.addEventListener('click', () => expandCard(card));
  });

  const mainCta = document.getElementById('sg-main-cta');
  if (mainCta) {
    mainCta.addEventListener('click', (e) => {
      e.stopPropagation();
      expandCard(document.getElementById('sg-card-main'));
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') collapseCard();
  });

})();
</script>

<!-- ══ Page-specific styles ════════════════════════════════════════ -->
<style>
.countdown-timer {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin: 1rem 0;
}

.countdown-item { text-align: center; }

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
document.querySelectorAll('.filter-select, .search-input').forEach(function(element) {
  if (element.classList.contains('search-input')) {
    var timeout;
    element.addEventListener('input', function() {
      clearTimeout(timeout);
      timeout = setTimeout(function() {
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
  var grid = document.getElementById('eventsGrid');
  var buttons = document.querySelectorAll('.view-toggle-btn');

  buttons.forEach(function(btn) { btn.classList.remove('active'); });
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