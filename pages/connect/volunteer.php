<?php
$pageTitle = 'Volunteer - Scribes Global';
$pageDescription = 'Join our volunteer team and make a difference';
$pageCSS = 'connect';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get volunteer opportunities
$opportunitiesStmt = $conn->query("
    SELECT * FROM volunteer_opportunities 
    WHERE status = 'active' 
    ORDER BY priority DESC, created_at DESC
");
$opportunities = $opportunitiesStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.volunteer-hero {
  background: linear-gradient(135deg, #51CF66 0%, #2F9E44 100%);
  padding: 6rem 0 4rem;
  text-align: center;
  color: white;
  position: relative;
  overflow: hidden;
}

.volunteer-hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><path d="M50 10 L60 40 L90 40 L65 60 L75 90 L50 70 L25 90 L35 60 L10 40 L40 40 Z" fill="rgba(255,255,255,0.1)"/></svg>');
  background-size: 100px 100px;
}

.volunteer-hero-content {
  position: relative;
  z-index: 1;
}

.volunteer-hero h1 {
  font-size: 3rem;
  margin-bottom: 1rem;
  font-weight: 900;
}

.volunteer-card {
  background: white;
  padding: 2rem;
  border-radius: var(--radius-2xl);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transition: all var(--transition-base);
  border-left: 4px solid var(--card-color);
  margin-bottom: 2rem;
}

.volunteer-card:hover {
  transform: translateX(8px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.volunteer-card.high-priority { --card-color: #EB5757; }
.volunteer-card.medium-priority { --card-color: #F2994A; }
.volunteer-card.normal-priority { --card-color: #51CF66; }

.volunteer-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
}

.volunteer-badge {
  padding: 0.5rem 1rem;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.volunteer-badge.urgent {
  background: rgba(235, 87, 87, 0.15);
  color: #C92A2A;
}

.volunteer-badge.ongoing {
  background: rgba(242, 153, 74, 0.15);
  color: #CC6600;
}

.volunteer-badge.flexible {
  background: rgba(81, 207, 102, 0.15);
  color: #2F9E44;
}

.volunteer-meta {
  display: flex;
  gap: 1.5rem;
  margin: 1.5rem 0;
  flex-wrap: wrap;
}

.volunteer-meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--gray-600);
  font-size: 0.875rem;
}

.volunteer-meta-item i {
  color: #51CF66;
}

.volunteer-form {
  background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
  padding: 3rem;
  border-radius: var(--radius-2xl);
  margin: 4rem 0;
}

.why-volunteer {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  margin: 4rem 0;
}

.why-card {
  background: white;
  padding: 2rem;
  border-radius: var(--radius-xl);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  text-align: center;
}

.why-icon {
  width: 70px;
  height: 70px;
  margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, var(--icon-color-1) 0%, var(--icon-color-2) 100%);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: white;
}

.why-card:nth-child(1) { --icon-color-1: #51CF66; --icon-color-2: #2F9E44; }
.why-card:nth-child(2) { --icon-color-1: #6B46C1; --icon-color-2: #9B7EDE; }
.why-card:nth-child(3) { --icon-color-1: #2D9CDB; --icon-color-2: #56CCF2; }
.why-card:nth-child(4) { --icon-color-1: #D4AF37; --icon-color-2: #F2D97A; }

@media (max-width: 768px) {
  .volunteer-hero h1 {
    font-size: 2rem;
  }
  
  .volunteer-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .volunteer-form {
    padding: 2rem 1.5rem;
  }
}
</style>
<div id="three-canvas-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: -1; pointer-events: none;"></div>
<!-- Hero Section -->
<section class="volunteer-hero">
  <div class="volunteer-hero-content">
    <div class="container">
      <h1 data-aos="fade-down">Volunteer With Us</h1>
      <p style="font-size: 1.25rem; opacity: 0.95; max-width: 600px; margin: 0 auto;" data-aos="fade-up" data-aos-delay="100">
        Use your gifts and talents to serve the creative community. Join our team of passionate volunteers making a difference.
      </p>
    </div>
  </div>
</section>

<section style="padding: 4rem 0;">
  <div class="container">
    <!-- Why Volunteer -->
    <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem;" data-aos="fade-up">
      Why Volunteer?
    </h2>
    
    <div class="why-volunteer">
      <div class="why-card" data-aos="fade-up">
        <div class="why-icon">
          <i class="fas fa-heart"></i>
        </div>
        <h3 style="margin-bottom: 1rem;">Serve Others</h3>
        <p style="color: var(--gray-600); margin: 0;">
          Use your talents to bless and encourage fellow creatives in their journey.
        </p>
      </div>
      
      <div class="why-card" data-aos="fade-up" data-aos-delay="100">
        <div class="why-icon">
          <i class="fas fa-users"></i>
        </div>
        <h3 style="margin-bottom: 1rem;">Build Community</h3>
        <p style="color: var(--gray-600); margin: 0;">
          Connect with like-minded believers and build lasting relationships.
        </p>
      </div>
      
      <div class="why-card" data-aos="fade-up" data-aos-delay="200">
        <div class="why-icon">
          <i class="fas fa-chart-line"></i>
        </div>
        <h3 style="margin-bottom: 1rem;">Develop Skills</h3>
        <p style="color: var(--gray-600); margin: 0;">
          Gain valuable experience and develop new skills in ministry and leadership.
        </p>
      </div>
      
      <div class="why-card" data-aos="fade-up" data-aos-delay="300">
        <div class="why-icon">
          <i class="fas fa-award"></i>
        </div>
        <h3 style="margin-bottom: 1rem;">Earn Recognition</h3>
        <p style="color: var(--gray-600); margin: 0;">
          Receive volunteer badges and recognition for your dedicated service.
        </p>
      </div>
    </div>
    
    <!-- Current Opportunities -->
    <h2 style="text-align: center; font-size: 2.5rem; margin: 4rem 0 3rem;" data-aos="fade-up">
      Current Opportunities
    </h2>
    
    <?php if (count($opportunities) > 0): ?>
      <?php foreach ($opportunities as $opp): ?>
        <div class="volunteer-card <?= $opp['priority'] ?>-priority" data-aos="fade-up">
          <div class="volunteer-header">
            <div>
              <h3 style="font-size: 1.75rem; margin: 0 0 0.5rem 0; color: var(--dark-bg);">
                <?= htmlspecialchars($opp['title']) ?>
              </h3>
              <p style="color: var(--gray-600); margin: 0; font-size: 1rem;">
                <?= htmlspecialchars($opp['department']) ?>
              </p>
            </div>
            <span class="volunteer-badge <?= $opp['urgency'] ?>">
              <?= ucfirst($opp['urgency']) ?>
            </span>
          </div>
          
          <div class="volunteer-meta">
            <div class="volunteer-meta-item">
              <i class="fas fa-clock"></i>
              <span><?= htmlspecialchars($opp['time_commitment']) ?></span>
            </div>
            <div class="volunteer-meta-item">
              <i class="fas fa-map-marker-alt"></i>
              <span><?= htmlspecialchars($opp['location_type']) ?></span>
            </div>
            <div class="volunteer-meta-item">
              <i class="fas fa-users"></i>
              <span><?= $opp['volunteers_needed'] ?> volunteers needed</span>
            </div>
          </div>
          
          <p style="color: var(--gray-700); line-height: 1.8; margin: 1.5rem 0;">
            <?= nl2br(htmlspecialchars($opp['description'])) ?>
          </p>
          
          <?php if (!empty($opp['requirements'])): ?>
            <div style="background: rgba(81, 207, 102, 0.1); padding: 1rem; border-radius: var(--radius-md); margin: 1.5rem 0;">
              <strong style="display: block; margin-bottom: 0.5rem; color: var(--dark-bg);">
                <i class="fas fa-check-circle" style="color: #51CF66;"></i> Requirements:
              </strong>
              <p style="margin: 0; color: var(--gray-700);">
                <?= htmlspecialchars($opp['requirements']) ?>
              </p>
            </div>
          <?php endif; ?>
          
          <button class="btn btn-primary" onclick="applyForOpportunity(<?= $opp['id'] ?>, '<?= htmlspecialchars($opp['title']) ?>')">
            <i class="fas fa-hand-paper"></i> Apply to Volunteer
          </button>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: var(--radius-2xl); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);">
        <i class="fas fa-info-circle" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1.5rem;"></i>
        <h3 style="font-size: 1.75rem; margin-bottom: 1rem; color: var(--dark-bg);">No Open Positions Right Now</h3>
        <p style="color: var(--gray-600); max-width: 600px; margin: 0 auto 2rem;">
          We don't have any specific volunteer openings at the moment, but we'd still love to hear from you!
        </p>
        <button class="btn btn-primary" onclick="openGeneralApplication()">
          <i class="fas fa-envelope"></i> Express Interest
        </button>
      </div>
    <?php endif; ?>
    
    <!-- Volunteer Application Form -->
    <div class="volunteer-form" id="volunteerForm" style="display: none;" data-aos="fade-up">
      <h2 style="text-align: center; font-size: 2rem; margin-bottom: 2rem;">Volunteer Application</h2>
      
      <form id="applicationForm">
        <input type="hidden" name="opportunity_id" id="opportunityId">
        
        <div class="form-row">
          <div class="form-group">
            <label for="first_name" class="form-label">First Name *</label>
            <input type="text" id="first_name" name="first_name" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="last_name" class="form-label">Last Name *</label>
            <input type="text" id="last_name" name="last_name" class="form-control" required>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="email" class="form-label">Email Address *</label>
            <input type="email" id="email" name="email" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="phone" class="form-label">Phone Number *</label>
            <input type="tel" id="phone" name="phone" class="form-control" required>
          </div>
        </div>
        
        <div class="form-group">
          <label for="availability" class="form-label">Availability *</label>
          <textarea id="availability" name="availability" class="form-control" rows="3" placeholder="Please describe your availability (days, times, hours per week)" required></textarea>
        </div>
        
        <div class="form-group">
          <label for="skills" class="form-label">Relevant Skills & Experience *</label>
          <textarea id="skills" name="skills" class="form-control" rows="4" placeholder="Tell us about your relevant skills, experience, and why you'd be a great fit" required></textarea>
        </div>
        
        <div class="form-group">
          <label for="motivation" class="form-label">Why do you want to volunteer? *</label>
          <textarea id="motivation" name="motivation" class="form-control" rows="4" placeholder="Share what motivates you to serve in this capacity" required></textarea>
        </div>
        
        <div class="form-group">
          <label for="chapter" class="form-label">Your Chapter (if applicable)</label>
          <input type="text" id="chapter" name="chapter" class="form-control">
        </div>
        
        <div class="form-group">
          <div style="display: flex; align-items: start; gap: 0.75rem;">
            <input type="checkbox" id="terms" name="terms" required style="width: 20px; height: 20px; margin-top: 2px;">
            <label for="terms" style="cursor: pointer;">
              I agree to the volunteer <a href="<?= SITE_URL ?>/pages/legal/terms" target="_blank">terms and conditions</a> and commit to serving with excellence and integrity.
            </label>
          </div>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
          <button type="button" class="btn btn-outline" onclick="closeApplication()">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary" style="min-width: 200px;">
            <i class="fas fa-paper-plane"></i> Submit Application
          </button>
        </div>
      </form>
    </div>
  </div>
</section>

<script>
function applyForOpportunity(id, title) {
  document.getElementById('opportunityId').value = id;
  document.getElementById('volunteerForm').style.display = 'block';
  document.getElementById('volunteerForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function openGeneralApplication() {
  document.getElementById('opportunityId').value = '';
  document.getElementById('volunteerForm').style.display = 'block';
  document.getElementById('volunteerForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeApplication() {
  document.getElementById('volunteerForm').style.display = 'none';
  document.getElementById('applicationForm').reset();
}

document.getElementById('applicationForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
  
  const formData = new FormData(this);
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/volunteer.php?action=submit_application', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Application submitted successfully! We\'ll be in touch soon.');
      closeApplication();
    } else {
      alert(result.message || 'Failed to submit application');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>