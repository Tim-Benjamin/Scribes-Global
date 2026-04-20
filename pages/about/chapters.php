<?php
$pageTitle = 'Our Chapters - Scribes Global';
$pageDescription = 'Explore Scribes Global chapters across Ghana and around the world. Join a community of creative believers near you.';
$pageCSS = 'chapters';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get Ghana chapters
$ghanaStmt = $conn->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM users WHERE chapter_id = c.id) as member_count,
           u.first_name as leader_first_name,
           u.last_name as leader_last_name
    FROM chapters c
    LEFT JOIN users u ON c.leader_id = u.id
    WHERE c.status = 'active'
    ORDER BY c.is_campus DESC, c.name ASC
");
$ghanaChapters = $ghanaStmt->fetchAll();

// Separate campus and regular chapters
$campusChapters = array_filter($ghanaChapters, function($ch) { return $ch['is_campus'] == 1; });
$regularChapters = array_filter($ghanaChapters, function($ch) { return $ch['is_campus'] == 0; });

// Calculate total stats
$totalMembers = array_sum(array_column($ghanaChapters, 'member_count'));
$totalChapters = count($ghanaChapters);

require_once __DIR__ . '/../../includes/header.php';
?>

<style>


/* =========================================
   CHAPTERS/CAMPUSES PAGE - SCRIBES GLOBAL
   Interactive Map Version
   ========================================= */

.chapters-hero {
  background: linear-gradient(135deg, #1A1A2E 0%, #16213E 100%);
  padding: 6rem 0 4rem;
  position: relative;
  overflow: hidden;
}

.chapters-hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(circle at 20% 50%, rgba(107, 70, 193, 0.15) 0%, transparent 50%),
    radial-gradient(circle at 80% 50%, rgba(45, 156, 219, 0.15) 0%, transparent 50%),
    url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M 60 0 L 0 0 0 60" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
}

.chapters-hero-content {
  position: relative;
  z-index: 1;
  text-align: center;
  max-width: 900px;
  margin: 0 auto;
}

.chapters-hero h1 {
  font-size: 3.5rem;
  font-family: var(--font-heading);
  color: white;
  margin-bottom: 1.5rem;
  line-height: 1.2;
}

.chapters-hero-description {
  font-size: 1.125rem;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.8;
  margin-bottom: 2.5rem;
}

.chapters-hero-stats {
  display: flex;
  justify-content: center;
  gap: 3rem;
  margin-top: 2rem;
}

.hero-stat {
  text-align: center;
}

.hero-stat-value {
  font-size: 3rem;
  font-weight: 900;
  background: linear-gradient(135deg, #D4AF37 0%, #F2D97A 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: block;
  margin-bottom: 0.5rem;
}

.hero-stat-label {
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.7);
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
}

/* Map Section */
.map-section {
  padding: 4rem 0 6rem;
  background: linear-gradient(180deg, #f5f7fa 0%, white 100%);
}

.section-header {
  text-align: center;
  max-width: 800px;
  margin: 0 auto 3rem;
}

.section-tag {
  display: inline-block;
  padding: 0.5rem 1.25rem;
  background: linear-gradient(135deg, rgba(107, 70, 193, 0.1) 0%, rgba(45, 156, 219, 0.1) 100%);
  color: #6B46C1;
  border-radius: var(--radius-full);
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 2.75rem;
  font-family: var(--font-heading);
  color: var(--dark-bg);
  margin-bottom: 1rem;
  line-height: 1.2;
}

.section-description {
  font-size: 1.125rem;
  color: var(--gray-600);
  line-height: 1.8;
}

/* Map Container */
.map-container {
  background: white;
  border-radius: var(--radius-2xl);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 3rem;
}

#chaptersMap {
  height: 600px;
  width: 100%;
}

.map-legend {
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(107, 70, 193, 0.03) 0%, rgba(45, 156, 219, 0.03) 100%);
  display: flex;
  justify-content: center;
  gap: 2rem;
  flex-wrap: wrap;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.95rem;
  color: var(--gray-700);
  font-weight: 600;
}

.legend-marker {
  width: 30px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

/* Custom Leaflet Popup */
.leaflet-popup-content-wrapper {
  border-radius: var(--radius-xl);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.leaflet-popup-content {
  margin: 1.5rem;
  min-width: 280px;
}

.popup-header {
  margin-bottom: 1rem;
}

.popup-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--dark-bg);
  margin-bottom: 0.5rem;
  font-family: var(--font-heading);
}

.popup-location {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--gray-600);
  font-size: 0.9rem;
}

.popup-location i {
  color: #6B46C1;
}

.popup-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem 0;
  border-top: 2px solid var(--gray-200);
  border-bottom: 2px solid var(--gray-200);
  margin-bottom: 1rem;
}

.popup-info-item {
  display: flex;
  align-items: start;
  gap: 0.75rem;
  font-size: 0.9rem;
}

.popup-info-icon {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 0.85rem;
}

.popup-info-icon.purple {
  background: linear-gradient(135deg, rgba(107, 70, 193, 0.1) 0%, rgba(107, 70, 193, 0.15) 100%);
  color: #6B46C1;
}

.popup-info-icon.gold {
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.1) 0%, rgba(212, 175, 55, 0.15) 100%);
  color: #D4AF37;
}

.popup-info-icon.teal {
  background: linear-gradient(135deg, rgba(45, 156, 219, 0.1) 0%, rgba(45, 156, 219, 0.15) 100%);
  color: #2D9CDB;
}

.popup-info-text strong {
  display: block;
  color: var(--dark-bg);
  margin-bottom: 0.25rem;
}

.popup-actions {
  display: grid;
  gap: 0.75rem;
}

.popup-btn {
  padding: 0.75rem 1rem;
  border-radius: var(--radius-md);
  font-weight: 700;
  text-align: center;
  text-decoration: none;
  transition: all var(--transition-base);
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.popup-btn-primary {
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(107, 70, 193, 0.3);
}

.popup-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(107, 70, 193, 0.4);
}

.popup-btn-secondary {
  background: white;
  color: #6B46C1;
  border: 2`px solid #6B46C1;
}

.popup-btn-secondary:hover {
  background: #6B46C1;
  color: white;
}

/* Ghana Content */
.ghana-content {
  padding: 4rem 0;
  background: white;
}

.intro-text {
  max-width: 900px;
  margin: 0 auto 3rem;
  font-size: 1.125rem;
  line-height: 1.8;
  color: var(--gray-700);
  text-align: center;
}

/* International Section */
.international-section {
  background: linear-gradient(135deg, #1A1A2E 0%, #16213E 100%);
  padding: 6rem 0;
  position: relative;
  overflow: hidden;
}

.international-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid2" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M 60 0 L 0 0 0 60" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid2)"/></svg>');
}

.international-section .section-tag {
  background: rgba(212, 175, 55, 0.2);
  color: #D4AF37;
}

.international-section .section-title,
.international-section .section-description {
  color: white;
}

.international-section .section-description {
  color: rgba(255, 255, 255, 0.8);
}

.international-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2.5rem;
  position: relative;
  z-index: 1;
}

.international-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--radius-2xl);
  padding: 3rem 2.5rem;
  transition: all var(--transition-base);
}

.international-card:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(212, 175, 55, 0.4);
  transform: translateY(-8px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

.international-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.international-flag {
  font-size: 4rem;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.international-card h3 {
  font-size: 2rem;
  font-weight: 900;
  color: white;
  margin: 0;
}

.international-description {
  color: rgba(255, 255, 255, 0.85);
  line-height: 1.8;
  margin-bottom: 2rem;
  font-size: 1.05rem;
}

.international-contact {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
}

.international-contact i {
  color: #D4AF37;
}

.international-card .popup-btn {
  width: 100%;
}

/* Join CTA */
.join-cta {
  padding: 6rem 0;
  background: linear-gradient(135deg, rgba(107, 70, 193, 0.05) 0%, rgba(45, 156, 219, 0.05) 100%);
}

.cta-content {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.cta-icon {
  width: 100px;
  height: 100px;
  margin: 0 auto 2rem;
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  color: white;
  box-shadow: 0 10px 40px rgba(107, 70, 193, 0.3);
}

.cta-content h2 {
  font-size: 2.5rem;
  font-family: var(--font-heading);
  color: var(--dark-bg);
  margin-bottom: 1.5rem;
}

.cta-content p {
  font-size: 1.25rem;
  color: var(--gray-600);
  line-height: 1.8;
  margin-bottom: 2.5rem;
}

.cta-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

/* Responsive */
@media (max-width: 768px) {
  .chapters-hero {
    padding: 4rem 0 3rem;
  }
  
  .chapters-hero h1 {
    font-size: 2.25rem;
  }
  
  .chapters-hero-stats {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .hero-stat-value {
    font-size: 2.5rem;
  }
  
  .section-title {
    font-size: 2rem;
  }
  
  #chaptersMap {
    height: 400px;
  }
  
  .map-legend {
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }
  
  .international-grid {
    grid-template-columns: 1fr;
  }
  
  .international-header {
    flex-direction: column;
    text-align: center;
  }
  
  .cta-buttons {
    flex-direction: column;
  }
  
  .cta-content h2 {
    font-size: 1.75rem;
  }
}


/* Quick Links Styles */
.map-quick-links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: center;
  margin-top: 1.5rem;
}

.quick-link-btn {
  padding: 0.75rem 1.25rem;
  background: white;
  border: 2px solid var(--gray-300);
  border-radius: var(--radius-lg);
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--gray-700);
  cursor: pointer;
  transition: all var(--transition-base);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.quick-link-btn:hover {
  border-color: #6B46C1;
  background: rgba(107, 70, 193, 0.05);
  color: #6B46C1;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(107, 70, 193, 0.15);
}

.quick-link-btn.campus {
  border-color: #2D9CDB;
}

.quick-link-btn.campus:hover {
  border-color: #2D9CDB;
  background: rgba(45, 156, 219, 0.05);
  color: #2D9CDB;
}

/* Chapter Cards List */
.chapters-list-section {
  padding: 4rem 0;
  background: white;
}

.chapters-category {
  margin-bottom: 4rem;
}

.category-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 3px solid var(--gray-200);
}

.category-icon {
  width: 60px;
  height: 60px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
}

.category-icon.campus {
  background: linear-gradient(135deg, #2D9CDB 0%, #56CCF2 100%);
}

.category-icon.regular {
  background: linear-gradient(135deg, #6B46C1 0%, #9B7EDE 100%);
}

.category-title {
  flex: 1;
}

.category-title h3 {
  font-size: 2rem;
  font-family: var(--font-heading);
  color: var(--dark-bg);
  margin: 0 0 0.5rem 0;
}

.category-title p {
  color: var(--gray-600);
  margin: 0;
  font-size: 1rem;
}

.chapter-list-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.chapter-list-card {
  background: white;
  border: 2px solid var(--gray-200);
  border-radius: var(--radius-xl);
  padding: 1.5rem;
  transition: all var(--transition-base);
  cursor: pointer;
}

.chapter-list-card:hover {
  border-color: rgba(107, 70, 193, 0.4);
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(107, 70, 193, 0.15);
}

.chapter-list-header {
  display: flex;
  align-items: start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.chapter-list-icon {
  width: 50px;
  height: 50px;
  border-radius: var(--radius-md);
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.chapter-list-title {
  flex: 1;
}

.chapter-list-title h4 {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--dark-bg);
  margin: 0 0 0.25rem 0;
}

.chapter-list-location {
  font-size: 0.9rem;
  color: var(--gray-600);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.chapter-list-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  color: var(--gray-600);
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.chapter-list-meta span {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.chapter-list-description {
  color: var(--gray-700);
  font-size: 0.9rem;
  line-height: 1.6;
  margin-bottom: 1rem;
}

.chapter-list-actions {
  display: flex;
  gap: 0.75rem;
}

.chapter-list-actions .btn {
  flex: 1;
  padding: 0.75rem;
  font-size: 0.875rem;
}
</style>

<!-- <div id="three-canvas-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: -1; pointer-events: none;"></div> -->
<!-- Hero Section -->
<section class="chapters-hero">
  <div class="container">
    <div class="chapters-hero-content" data-aos="fade-up">
      <h1>Our Chapters & Campuses</h1>
      <p class="chapters-hero-description">
        Scribes Global has expanded across Ghana and internationally, creating vibrant communities 
        where creative believers gather to worship, learn, and grow together. Explore our chapters 
        on the map below and find a community near you.
      </p>
      
      <div class="chapters-hero-stats">
        <div class="hero-stat">
          <span class="hero-stat-value"><?= $totalChapters ?>+</span>
          <span class="hero-stat-label">Ghana Chapters</span>
        </div>
        <div class="hero-stat">
          <span class="hero-stat-value"><?= number_format($totalMembers) ?>+</span>
          <span class="hero-stat-label">Members</span>
        </div>
        <div class="hero-stat">
          <span class="hero-stat-value">3</span>
          <span class="hero-stat-label">Countries</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Interactive Map Section -->
<section class="map-section">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag">🇬🇭 Ghana Chapters</span>
      <h2 class="section-title">Find A Chapter Near You</h2>
      <p class="section-description">
        Click on any marker to see chapter details, meeting times, and contact information.
      </p>
    </div>
    
    <div class="map-container" data-aos="fade-up" data-aos-delay="100">
      <div id="chaptersMap"></div>
      <div class="map-legend">
        <div class="legend-item">
          <div class="legend-marker">📍</div>
          <span>Chapter Location</span>
        </div>
        <div class="legend-item">
          <div class="legend-marker">⭐</div>
          <span>Click marker for details</span>
        </div>
      </div>
    </div>
    
    <!-- Quick Links to Chapters -->
    <div class="map-quick-links" data-aos="fade-up" data-aos-delay="200">
      <div style="width: 100%; text-align: center; margin-bottom: 0.5rem; color: var(--gray-600); font-size: 0.95rem; font-weight: 600;">
        <i class="fas fa-map-pin"></i> Quick Jump to Chapter:
      </div>
      <?php foreach ($ghanaChapters as $chapter): ?>
        <?php if ($chapter['latitude'] && $chapter['longitude']): ?>
          <button 
            class="quick-link-btn <?= $chapter['is_campus'] ? 'campus' : '' ?>" 
            onclick="jumpToChapter(<?= $chapter['latitude'] ?>, <?= $chapter['longitude'] ?>)"
          >
            <?php if ($chapter['is_campus']): ?>
              <i class="fas fa-university"></i>
            <?php else: ?>
              <i class="fas fa-map-marker-alt"></i>
            <?php endif; ?>
            <?= htmlspecialchars($chapter['name']) ?>
          </button>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- All Chapters List -->
<section class="chapters-list-section">
  <div class="container">
    <!-- Campus Chapters -->
    <?php if (count($campusChapters) > 0): ?>
      <div class="chapters-category" data-aos="fade-up">
        <div class="category-header">
          <div class="category-icon campus">
            <i class="fas fa-university"></i>
          </div>
          <div class="category-title">
            <h3>Campus Chapters</h3>
            <p>Student-led chapters at universities across Ghana</p>
          </div>
        </div>
        
        <div class="chapter-list-grid">
          <?php foreach ($campusChapters as $chapter): ?>
            <div class="chapter-list-card">
              <div class="chapter-list-header">
                <div class="chapter-list-icon">
                  <i class="fas fa-university"></i>
                </div>
                <div class="chapter-list-title">
                  <h4><?= htmlspecialchars($chapter['name']) ?></h4>
                  <div class="chapter-list-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <?= htmlspecialchars($chapter['location']) ?>
                  </div>
                </div>
              </div>
              
              <?php if ($chapter['campus_university']): ?>
                <div style="padding: 0.5rem 1rem; background: rgba(45, 156, 219, 0.1); border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.875rem; color: #2D9CDB; font-weight: 600;">
                  <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($chapter['campus_university']) ?>
                </div>
              <?php endif; ?>
              
              <div class="chapter-list-meta">
                <?php if ($chapter['meeting_schedule']): ?>
                  <span>
                    <i class="fas fa-calendar"></i>
                    <?= htmlspecialchars($chapter['meeting_schedule']) ?>
                  </span>
                <?php endif; ?>
                <span>
                  <i class="fas fa-users"></i>
                  <?= number_format($chapter['member_count']) ?> members
                </span>
              </div>
              
              <?php if ($chapter['description']): ?>
                <p class="chapter-list-description">
                  <?= htmlspecialchars($chapter['description']) ?>
                </p>
              <?php endif; ?>
              
              <div class="chapter-list-actions">
                <a href="<?= SITE_URL ?>/pages/chapters/view?id=<?= $chapter['id'] ?>" class="btn btn-primary btn-sm">
                  <i class="fas fa-info-circle"></i> View Details
                </a>
                <?php if ($chapter['latitude'] && $chapter['longitude']): ?>
                  <button class="btn btn-outline btn-sm" onclick="jumpToChapter(<?= $chapter['latitude'] ?>, <?= $chapter['longitude'] ?>)">
                    <i class="fas fa-map-marked-alt"></i> On Map
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    
    <!-- Regular Chapters -->
    <?php if (count($regularChapters) > 0): ?>
      <div class="chapters-category" data-aos="fade-up" data-aos-delay="100">
        <div class="category-header">
          <div class="category-icon regular">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <div class="category-title">
            <h3>City & Regional Chapters</h3>
            <p>Community chapters serving cities and regions</p>
          </div>
        </div>
        
        <div class="chapter-list-grid">
          <?php foreach ($regularChapters as $chapter): ?>
            <div class="chapter-list-card">
              <div class="chapter-list-header">
                <div class="chapter-list-icon">
                  <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="chapter-list-title">
                  <h4><?= htmlspecialchars($chapter['name']) ?></h4>
                  <div class="chapter-list-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <?= htmlspecialchars($chapter['location']) ?>
                  </div>
                </div>
              </div>
              
              <div class="chapter-list-meta">
                <?php if ($chapter['meeting_schedule']): ?>
                  <span>
                    <i class="fas fa-calendar"></i>
                    <?= htmlspecialchars($chapter['meeting_schedule']) ?>
                  </span>
                <?php endif; ?>
                <span>
                  <i class="fas fa-users"></i>
                  <?= number_format($chapter['member_count']) ?> members
                </span>
              </div>
              
              <?php if ($chapter['description']): ?>
                <p class="chapter-list-description">
                  <?= htmlspecialchars($chapter['description']) ?>
                </p>
              <?php endif; ?>
              
              <div class="chapter-list-actions">
                <a href="<?= SITE_URL ?>/pages/chapters/view?id=<?= $chapter['id'] ?>" class="btn btn-primary btn-sm">
                  <i class="fas fa-info-circle"></i> View Details
                </a>
                <?php if ($chapter['latitude'] && $chapter['longitude']): ?>
                  <button class="btn btn-outline btn-sm" onclick="jumpToChapter(<?= $chapter['latitude'] ?>, <?= $chapter['longitude'] ?>)">
                    <i class="fas fa-map-marked-alt"></i> On Map
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- International Section -->
<section class="international-section">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag">🌍 International</span>
      <h2 class="section-title">Scribes Around The World</h2>
      <p class="section-description">
        The Scribes movement has crossed borders, establishing chapters in the United Kingdom 
        and United States. Each international chapter carries the same DNA of creativity, 
        worship, and excellence while embracing their unique cultural context.
      </p>
    </div>
    
    <div class="international-grid">
      <!-- Scribes UK -->
      <div class="international-card" data-aos="fade-up">
        <div class="international-header">
          <div class="international-flag">🇬🇧</div>
          <h3>Scribes UK</h3>
        </div>
        <p class="international-description">
          Scribes UK is a dynamic community of creative believers based in the United Kingdom. 
          We bring together poets, musicians, artists, and creatives from diverse backgrounds 
          to worship, create, and impact the UK with the gospel through excellence in the arts. 
          Our chapter serves both British and diaspora creatives, providing a space for spiritual 
          growth, artistic development, and kingdom impact.
        </p>
        <div class="international-contact">
          <i class="fas fa-envelope"></i>
          <span>uk@scribesglobal.com</span>
        </div>
        <div class="popup-actions">
          <a href="mailto:uk@scribesglobal.com" class="popup-btn popup-btn-secondary">
            <i class="fas fa-envelope"></i> Contact Chapter
          </a>
        </div>
      </div>
      
      <!-- Scribes USA -->
      <div class="international-card" data-aos="fade-up" data-aos-delay="100">
        <div class="international-header">
          <div class="international-flag">🇺🇸</div>
          <h3>Scribes USA</h3>
        </div>
        <p class="international-description">
          Scribes USA is mobilizing American creatives and the African diaspora across the United 
          States to use their gifts for kingdom impact. We're building a movement of artists, 
          poets, musicians, and creative professionals who are committed to excellence and biblical 
          foundations. From coast to coast, we're hosting creative gatherings, training sessions, 
          and ministry opportunities that empower believers to impact their communities through creativity.
        </p>
        <div class="international-contact">
          <i class="fas fa-envelope"></i>
          <span>usa@scribesglobal.com</span>
        </div>
        <div class="popup-actions">
          <a href="mailto:usa@scribesglobal.com" class="popup-btn popup-btn-secondary">
            <i class="fas fa-envelope"></i> Contact Chapter
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Join CTA -->
<section class="join-cta">
  <div class="container">
    <div class="cta-content" data-aos="fade-up">
      <div class="cta-icon">
        <i class="fas fa-users"></i>
      </div>
      <h2>Ready to Join A Chapter?</h2>
      <p>
        Take the next step in your creative journey. Join a Scribes chapter near you and become 
        part of a community that will challenge, inspire, and equip you to use your gifts for 
        God's glory. Whether you're in Ghana, the UK, USA, or anywhere else in the world, 
        we'd love to connect with you.
      </p>
      <div class="cta-buttons">
        <a href="<?= SITE_URL ?>/auth/register" class="btn btn-primary btn-lg">
          <i class="fas fa-user-plus"></i> Create Account
        </a>
        <a href="<?= SITE_URL ?>/pages/contact" class="btn btn-outline btn-lg">
          <i class="fas fa-envelope"></i> Contact Us
        </a>
      </div>
    </div>
  </div>
</section>

<script>
// Initialize map
const map = L.map('chaptersMap').setView([7.9465, -1.0232], 7); // Center on Ghana

// Add tile layer
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors',
  maxZoom: 18
}).addTo(map);

// Custom marker icon
const customIcon = L.divIcon({
  className: 'custom-marker',
  html: '<div style="font-size: 2rem; text-align: center; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));">📍</div>',
  iconSize: [40, 40],
  iconAnchor: [20, 40],
  popupAnchor: [0, -40]
});

// Store markers for quick jump
const markers = {};

// Add chapter markers
const chapters = <?= json_encode($ghanaChapters) ?>;

chapters.forEach(chapter => {
  if (chapter.latitude && chapter.longitude) {
    const marker = L.marker([parseFloat(chapter.latitude), parseFloat(chapter.longitude)], {icon: customIcon})
      .addTo(map);
    
    // Store marker reference
    const markerKey = `${chapter.latitude}_${chapter.longitude}`;
    markers[markerKey] = marker;
    
    // Create popup content
    const popupContent = `
      <div class="popup-header">
        <h3 class="popup-title">${chapter.name}</h3>
        <div class="popup-location">
          <i class="fas fa-map-marker-alt"></i>
          <span>${chapter.location}</span>
        </div>
      </div>
      
      <div class="popup-info">
        ${chapter.leader_first_name ? `
        <div class="popup-info-item">
          <div class="popup-info-icon gold">
            <i class="fas fa-user-tie"></i>
          </div>
          <div class="popup-info-text">
            <strong>Chapter Leader</strong>
            ${chapter.leader_first_name} ${chapter.leader_last_name}
          </div>
        </div>
        ` : ''}
        
        ${chapter.meeting_schedule ? `
        <div class="popup-info-item">
          <div class="popup-info-icon purple">
            <i class="fas fa-calendar"></i>
          </div>
          <div class="popup-info-text">
            <strong>Meetings</strong>
            ${chapter.meeting_schedule}
          </div>
        </div>
        ` : ''}
        
        <div class="popup-info-item">
          <div class="popup-info-icon teal">
            <i class="fas fa-users"></i>
          </div>
          <div class="popup-info-text">
            <strong>Members</strong>
            ${chapter.member_count} active members
          </div>
        </div>
      </div>
      
      <div class="popup-actions">
        <a href="<?= SITE_URL ?>/pages/chapters/view?id=${chapter.id}" class="popup-btn popup-btn-primary">
          <i class="fas fa-info-circle"></i> View Chapter Page
        </a>
        ${chapter.contact_email ? `
        <a href="mailto:${chapter.contact_email}" class="popup-btn popup-btn-secondary">
          <i class="fas fa-envelope"></i> Contact Chapter
        </a>
        ` : ''}
      </div>
    `;
    
    marker.bindPopup(popupContent, {
      maxWidth: 300,
      className: 'custom-popup'
    });
  }
});

// Jump to chapter function
function jumpToChapter(lat, lng) {
  // Scroll to map
  document.getElementById('chaptersMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
  
  // Zoom to marker and open popup
  setTimeout(() => {
    map.setView([lat, lng], 15, { animate: true });
    
    // Find and open the marker popup
    const markerKey = `${lat}_${lng}`;
    if (markers[markerKey]) {
      markers[markerKey].openPopup();
    }
  }, 500);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>