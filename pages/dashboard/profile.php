<?php
$pageTitle = 'My Profile - Scribes Global';
$pageDescription = 'Edit your Scribes Global profile';
$pageCSS = 'dashboard';
$noSplash = true;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/auth/login');
    exit;
}

$user = getCurrentUser();
if (!$user) {
    header('Location: ' . SITE_URL . '/auth/logout');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get all chapters for dropdown
$chaptersStmt = $conn->query("SELECT * FROM chapters WHERE status = 'active' ORDER BY name ASC");
$chapters = $chaptersStmt->fetchAll();

// Calculate profile completion
$completion = 0;
$checklist = [
    ['label' => 'Profile Photo', 'completed' => !empty($user['profile_photo']), 'points' => 15],
    ['label' => 'Bio', 'completed' => !empty($user['bio']), 'points' => 15],
    ['label' => 'Custom Tag', 'completed' => !empty($user['custom_tag']), 'points' => 15],
    ['label' => 'Primary Role', 'completed' => !empty($user['primary_role']) && $user['primary_role'] !== 'member', 'points' => 15],
    ['label' => 'Chapter', 'completed' => !empty($user['chapter_id']), 'points' => 10],
    ['label' => 'Ministry Team', 'completed' => !empty($user['ministry_team']), 'points' => 10],
    ['label' => 'Phone Number', 'completed' => !empty($user['phone']), 'points' => 10],
    ['label' => 'Email Verified', 'completed' => $user['email_verified'], 'points' => 10],
];

foreach ($checklist as $item) {
    if ($item['completed']) {
        $completion += $item['points'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* Additional Profile Edit Styles */
.profile-edit-section {
  background: white;
  border-radius: var(--radius-2xl);
  padding: 2.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  transition: all var(--transition-base);
  position: relative;
  overflow: hidden;
}

.profile-edit-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(180deg, #6B46C1 0%, #2D9CDB 100%);
}

.profile-edit-section:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.profile-edit-section h3 {
  font-size: 1.5rem;
  color: var(--dark-bg);
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.profile-edit-section h3 i {
  color: #6B46C1;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.avatar-upload {
  display: flex;
  align-items: center;
  gap: 2rem;
  margin-bottom: 2rem;
  padding: 2rem;
  background: linear-gradient(135deg, rgba(107, 70, 193, 0.05) 0%, rgba(45, 156, 219, 0.05) 100%);
  border-radius: var(--radius-xl);
  border: 2px dashed rgba(107, 70, 193, 0.3);
}

.avatar-preview {
  width: 130px;
  height: 130px;
  border-radius: 50%;
  object-fit: cover;
  border: 5px solid white;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  transition: all var(--transition-base);
}

.avatar-preview:hover {
  transform: scale(1.05);
  box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
}

.avatar-upload-btn {
  position: relative;
  overflow: hidden;
  cursor: pointer;
}

.avatar-upload-btn input[type="file"] {
  position: absolute;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
  left: 0;
  top: 0;
}

.tag-selector {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.tag-option {
  padding: 0.75rem 1.25rem;
  border: 2px solid var(--gray-300);
  border-radius: var(--radius-full);
  cursor: pointer;
  transition: all var(--transition-base);
  background: white;
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tag-option:hover {
  border-color: #6B46C1;
  background: rgba(107, 70, 193, 0.05);
  transform: translateY(-2px);
}

.tag-option.selected {
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  color: white;
  border-color: #6B46C1;
  box-shadow: 0 4px 15px rgba(107, 70, 193, 0.3);
}

.tag-option input[type="radio"] {
  display: none;
}

.preview-section {
  background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
  padding: 2rem;
  border-radius: var(--radius-xl);
  margin-top: 2rem;
  border-left: 4px solid #6B46C1;
}

.preview-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tag-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1rem;
}

.char-counter {
  font-size: 0.875rem;
  color: var(--gray-500);
  text-align: right;
  margin-top: 0.5rem;
}

.char-counter.warning {
  color: #FFA500;
}

.char-counter.danger {
  color: #EB5757;
}

.save-button-container {
  position: sticky;
  bottom: 2rem;
  z-index: 10;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding: 1.5rem;
  background: white;
  border-radius: var(--radius-xl);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .avatar-upload {
    flex-direction: column;
    text-align: center;
  }
  
  .profile-edit-section {
    padding: 1.5rem;
  }
  
  .save-button-container {
    flex-direction: column;
  }
  
  .save-button-container .btn {
    width: 100%;
  }
}
</style>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <aside class="dashboard-sidebar" id="dashboardSidebar">
    <div class="sidebar-header">
      <div class="sidebar-user">
        <?php if (!empty($user['profile_photo'])): ?>
          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" class="sidebar-user-avatar">
        <?php else: ?>
          <div class="sidebar-user-avatar-placeholder">
            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
          </div>
        <?php endif; ?>
        <div class="sidebar-user-info">
          <h3><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?></h3>
          <div class="sidebar-user-role">
            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
            <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
          </div>
        </div>
      </div>
    </div>
    
    <nav class="sidebar-nav">
      <div class="sidebar-nav-section">
        <div class="sidebar-nav-title">Main</div>
        <a href="<?= SITE_URL ?>/pages/dashboard" class="sidebar-nav-item">
          <i class="fas fa-th-large"></i>
          <span>Dashboard</span>
        </a>
        <a href="<?= SITE_URL ?>/pages/dashboard/profile" class="sidebar-nav-item active">
          <i class="fas fa-user"></i>
          <span>My Profile</span>
          <?php if ($completion < 100): ?>
            <span class="sidebar-nav-badge"><?= $completion ?>%</span>
          <?php endif; ?>
        </a>
        <a href="<?= SITE_URL ?>/pages/dashboard/ministries" class="sidebar-nav-item">
          <i class="fas fa-users"></i>
          <span>My Ministries</span>
        </a>
      </div>
      
      <div class="sidebar-nav-section">
        <div class="sidebar-nav-title">Content</div>
        <a href="<?= SITE_URL ?>/pages/dashboard/posts" class="sidebar-nav-item">
          <i class="fas fa-pen"></i>
          <span>My Posts</span>
        </a>
        <a href="<?= SITE_URL ?>/pages/dashboard/media" class="sidebar-nav-item">
          <i class="fas fa-photo-video"></i>
          <span>My Media</span>
        </a>
        <a href="<?= SITE_URL ?>/pages/dashboard/events" class="sidebar-nav-item">
          <i class="fas fa-calendar"></i>
          <span>My Events</span>
        </a>
      </div>
      
      <div class="sidebar-nav-section">
        <div class="sidebar-nav-title">Account</div>
        <a href="<?= SITE_URL ?>/pages/dashboard/settings" class="sidebar-nav-item">
          <i class="fas fa-cog"></i>
          <span>Settings</span>
        </a>
        <a href="<?= SITE_URL ?>/auth/logout" class="sidebar-nav-item">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </a>
      </div>
    </nav>
  </aside>
  
  <!-- Main Content -->
  <main class="dashboard-main">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">My Profile</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Manage your personal information and preferences</p>
      </div>
      <div class="dashboard-actions">
        <button class="mobile-menu-toggle" onclick="toggleSidebar()">
          <i class="fas fa-bars"></i>
        </button>
      </div>
    </div>
    
    <!-- Profile Completion Progress -->
    <?php if ($completion < 100): ?>
    <div class="profile-completion-card" data-aos="fade-up" style="margin-bottom: 2rem;">
      <div class="profile-completion-content">
        <div class="completion-header">
          <h3 class="completion-title">
            <i class="fas fa-tasks"></i>
            Profile Completion
          </h3>
          <div class="completion-percentage"><?= $completion ?>%</div>
        </div>
        
        <div class="progress-bar-container">
          <div class="progress-bar" style="width: <?= $completion ?>%"></div>
        </div>
        
        <p style="margin-top: 1rem; font-size: 0.95rem; opacity: 0.95;">
          You're <?= 100 - $completion ?>% away from earning the <strong style="color: #D4AF37;">Active Member Badge</strong>!
        </p>
      </div>
    </div>
    <?php endif; ?>
    
    <div id="successAlert" class="alert alert-success" style="display: none; margin-bottom: 2rem; padding: 1rem 1.5rem; background: linear-gradient(135deg, #51CF66 0%, #2F9E44 100%); color: white; border-radius: var(--radius-xl); box-shadow: 0 4px 15px rgba(81, 207, 102, 0.3);">
      <i class="fas fa-check-circle"></i> <span id="successMessage"></span>
    </div>
    
    <form id="profileForm" enctype="multipart/form-data">
      <!-- Basic Information -->
      <div class="profile-edit-section" data-aos="fade-up">
        <h3>
          <i class="fas fa-user-circle"></i>
          Basic Information
        </h3>
        
        <div class="avatar-upload">
          <?php if (!empty($user['profile_photo'])): ?>
            <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" class="avatar-preview" id="avatarPreview">
          <?php else: ?>
            <div class="avatar-preview" id="avatarPreview" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 3.5rem; font-weight: 900;">
              <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
            </div>
          <?php endif; ?>
          
          <div style="flex: 1;">
            <h4 style="margin-bottom: 0.5rem; color: var(--dark-bg);">Profile Photo</h4>
            <p style="color: var(--gray-600); font-size: 0.875rem; margin-bottom: 1rem;">
              Upload a clear photo of yourself. JPG, PNG or GIF. Max size 5MB.
            </p>
            <label class="btn btn-secondary avatar-upload-btn" style="display: inline-block;">
              <i class="fas fa-camera"></i> Change Photo
              <input type="file" name="profile_photo" accept="image/*" onchange="previewAvatar(this)">
            </label>
            <p style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--gray-500);">
              <i class="fas fa-info-circle"></i> Completing this earns you +15% completion
            </p>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="first_name" class="form-label">
              First Name <span style="color: #EB5757;">*</span>
            </label>
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
            <label for="last_name" class="form-label">
              Last Name <span style="color: #EB5757;">*</span>
            </label>
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
            <label for="email" class="form-label">
              Email Address <span style="color: #EB5757;">*</span>
            </label>
            <input 
              type="email" 
              id="email" 
              name="email" 
              class="form-control" 
              value="<?= htmlspecialchars($user['email']) ?>"
              required
            >
            <?php if (!$user['email_verified']): ?>
              <small style="color: #FFA500; margin-top: 0.5rem; display: block;">
                <i class="fas fa-exclamation-triangle"></i> Email not verified. Check your inbox.
              </small>
            <?php endif; ?>
          </div>
          
          <div class="form-group">
            <label for="phone" class="form-label">
              Phone Number
              <span style="font-size: 0.75rem; color: var(--gray-500);">(+10% completion)</span>
            </label>
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
        
        <div class="form-group">
          <label for="bio" class="form-label">
            Bio / About Me
            <span style="font-size: 0.75rem; color: var(--gray-500);">(+15% completion)</span>
          </label>
          <textarea 
            id="bio" 
            name="bio" 
            class="form-control" 
            rows="5"
            maxlength="500"
            placeholder="Tell the community about yourself, your passion, and your journey..."
            oninput="updateCharCount(this, 500, 'bioCount')"
          ><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
          <div class="char-counter" id="bioCount"><?= strlen($user['bio'] ?? '') ?> / 500 characters</div>
        </div>
      </div>
      
      <!-- Identity & Tags -->
      <div class="profile-edit-section" data-aos="fade-up" data-aos-delay="100">
        <h3>
          <i class="fas fa-tags"></i>
          Identity & Display Tags
        </h3>
        
        <div class="form-group">
          <label for="custom_tag" class="form-label">
            Custom Display Tag
            <span style="font-size: 0.75rem; color: var(--gray-500);">(+15% completion)</span>
          </label>
          <input 
            type="text" 
            id="custom_tag" 
            name="custom_tag" 
            class="form-control" 
            value="<?= htmlspecialchars($user['custom_tag'] ?? '') ?>"
            placeholder="e.g., Founder, Lead Pastor, Creative Director"
            maxlength="50"
            oninput="updatePreview()"
          >
          <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
            This will appear next to your name in comments and posts
          </small>
        </div>
        
        <div class="form-group">
          <label class="form-label">
            Primary Role <span style="color: #EB5757;">*</span>
            <span style="font-size: 0.75rem; color: var(--gray-500);">(+15% completion)</span>
          </label>
          <div class="tag-selector">
            <?php 
            $roles = [
              'poet' => ['icon' => '🎤', 'label' => 'Poet'],
              'worship_leader' => ['icon' => '🎵', 'label' => 'Worship Leader'],
              'teacher' => ['icon' => '📖', 'label' => 'Teacher'],
              'intercessor' => ['icon' => '🙏', 'label' => 'Intercessor'],
              'writer' => ['icon' => '✍️', 'label' => 'Writer'],
              'creative' => ['icon' => '🎬', 'label' => 'Creative'],
              'evangelist' => ['icon' => '📢', 'label' => 'Evangelist'],
              'ministry_leader' => ['icon' => '💼', 'label' => 'Ministry Leader'],
              'volunteer' => ['icon' => '👥', 'label' => 'Volunteer'],
              'member' => ['icon' => '❤️', 'label' => 'Member']
            ];
            foreach ($roles as $value => $role):
            ?>
              <label class="tag-option <?= $user['primary_role'] === $value ? 'selected' : '' ?>" onclick="updatePreview()">
                <input type="radio" name="primary_role" value="<?= $value ?>" <?= $user['primary_role'] === $value ? 'checked' : '' ?>>
                <span><?= $role['icon'] ?> <?= $role['label'] ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        
        <div class="form-group">
          <label for="chapter_id" class="form-label">
            Chapter / Location
            <span style="font-size: 0.75rem; color: var(--gray-500);">(+10% completion)</span>
          </label>
          <select id="chapter_id" name="chapter_id" class="form-control" onchange="updatePreview()">
            <option value="">Select a chapter</option>
            <?php foreach ($chapters as $chapter): ?>
              <option value="<?= $chapter['id'] ?>" <?= $user['chapter_id'] == $chapter['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($chapter['name']) ?> - <?= htmlspecialchars($chapter['location']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="ministry_team" class="form-label">
            Ministry Team
            <span style="font-size: 0.75rem; color: var(--gray-500);">(+10% completion)</span>
          </label>
          <select id="ministry_team" name="ministry_team" class="form-control" onchange="updatePreview()">
            <option value="">Select a team</option>
            <?php 
            $teams = [
              'Performance Team',
              'Media Team',
              'Admin Team',
              'Prayer Team',
              'Social Media Team',
              'Design Team',
              'Logistics Team',
              'Hospitality Team'
            ];
            foreach ($teams as $team):
            ?>
              <option value="<?= $team ?>" <?= $user['ministry_team'] === $team ? 'selected' : '' ?>>
                <?= $team ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Display Preferences</label>
          <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 0.75rem;">
            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 1rem; background: var(--gray-100); border-radius: var(--radius-md); transition: all var(--transition-base);" onmouseover="this.style.background='var(--gray-200)'" onmouseout="this.style.background='var(--gray-100)'">
              <input type="checkbox" name="show_role" value="1" <?= $user['show_role'] ? 'checked' : '' ?> onchange="updatePreview()" style="width: 20px; height: 20px;">
              <div>
                <strong style="display: block; color: var(--dark-bg); margin-bottom: 0.25rem;">Show my role in profile</strong>
                <small style="color: var(--gray-600);">Display your primary role tag next to your name</small>
              </div>
            </label>
            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 1rem; background: var(--gray-100); border-radius: var(--radius-md); transition: all var(--transition-base);" onmouseover="this.style.background='var(--gray-200)'" onmouseout="this.style.background='var(--gray-100)'">
              <input type="checkbox" name="show_chapter" value="1" <?= $user['show_chapter'] ? 'checked' : '' ?> onchange="updatePreview()" style="width: 20px; height: 20px;">
              <div>
                <strong style="display: block; color: var(--dark-bg); margin-bottom: 0.25rem;">Show my chapter in profile</strong>
                <small style="color: var(--gray-600);">Display your chapter location tag</small>
              </div>
            </label>
            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 1rem; background: var(--gray-100); border-radius: var(--radius-md); transition: all var(--transition-base);" onmouseover="this.style.background='var(--gray-200)'" onmouseout="this.style.background='var(--gray-100)'">
              <input type="checkbox" name="show_team" value="1" <?= $user['show_team'] ? 'checked' : '' ?> onchange="updatePreview()" style="width: 20px; height: 20px;">
              <div>
                <strong style="display: block; color: var(--dark-bg); margin-bottom: 0.25rem;">Show my ministry team in profile</strong>
                <small style="color: var(--gray-600);">Display your team affiliation tag</small>
              </div>
            </label>
          </div>
        </div>
        
        <!-- Live Preview -->
        <div class="preview-section">
          <div class="preview-title">
            <i class="fas fa-eye"></i>
            Live Preview - How others will see you
          </div>
          <div style="text-align: center; padding: 1.5rem;">
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--dark-bg); margin-bottom: 1rem;">
              <?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?>
            </div>
            <div class="tag-preview" id="tagPreview">
              <!-- Tags will be dynamically added here -->
            </div>
          </div>
        </div>
      </div>
      
      <!-- Save Button Container -->
      <div class="save-button-container">
        <a href="<?= SITE_URL ?>/pages/dashboard" class="btn btn-outline">
          <i class="fas fa-times"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary" style="min-width: 200px;">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </form>
  </main>
</div>

<script>
function toggleSidebar() {
  document.getElementById('dashboardSidebar').classList.toggle('mobile-visible');
}

function previewAvatar(input) {
  const preview = document.getElementById('avatarPreview');
  
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
      if (preview.tagName === 'IMG') {
        preview.src = e.target.result;
      } else {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.className = 'avatar-preview';
        img.id = 'avatarPreview';
        preview.parentNode.replaceChild(img, preview);
      }
    }
    
    reader.readAsDataURL(input.files[0]);
  }
}

function updateCharCount(textarea, maxLength, counterId) {
  const counter = document.getElementById(counterId);
  const length = textarea.value.length;
  counter.textContent = length + ' / ' + maxLength + ' characters';
  
  counter.classList.remove('warning', 'danger');
  if (length > maxLength * 0.9) {
    counter.classList.add('danger');
  } else if (length > maxLength * 0.75) {
    counter.classList.add('warning');
  }
}

function updatePreview() {
  const customTag = document.getElementById('custom_tag').value;
  const roleSelect = document.querySelector('input[name="primary_role"]:checked');
  const chapterSelect = document.getElementById('chapter_id');
  const teamSelect = document.getElementById('ministry_team');
  const showRole = document.querySelector('input[name="show_role"]').checked;
  const showChapter = document.querySelector('input[name="show_chapter"]').checked;
  const showTeam = document.querySelector('input[name="show_team"]').checked;
  
  const preview = document.getElementById('tagPreview');
  preview.innerHTML = '';
  
  // Custom Tag
  if (customTag && showRole) {
    const tag = document.createElement('span');
    tag.className = 'custom-tag';
    tag.innerHTML = '<i class="fas fa-crown"></i> ' + customTag;
    preview.appendChild(tag);
  }
  
  // Role Tag
  if (roleSelect && showRole) {
    const roleLabel = roleSelect.parentElement.querySelector('span').textContent;
    const tag = document.createElement('span');
    tag.className = 'role-tag';
    tag.textContent = roleLabel;
    preview.appendChild(tag);
  }
  
  // Chapter Tag
  if (chapterSelect.value && showChapter) {
    const chapterName = chapterSelect.options[chapterSelect.selectedIndex].text.split(' - ')[0];
    const tag = document.createElement('span');
    tag.className = 'chapter-tag';
    tag.innerHTML = '<i class="fas fa-map-marker-alt"></i> ' + chapterName;
    preview.appendChild(tag);
  }
  
  // Team Tag
  if (teamSelect.value && showTeam) {
    const tag = document.createElement('span');
    tag.className = 'team-tag';
    tag.innerHTML = '<i class="fas fa-users"></i> ' + teamSelect.value;
    preview.appendChild(tag);
  }
  
  if (preview.children.length === 0) {
    preview.innerHTML = '<p style="color: var(--gray-500); font-style: italic;">No tags selected to display</p>';
  }
}

// Tag selector interaction
document.querySelectorAll('.tag-option').forEach(option => {
  option.addEventListener('click', function() {
    document.querySelectorAll('.tag-option').forEach(opt => opt.classList.remove('selected'));
    this.classList.add('selected');
    this.querySelector('input').checked = true;
  });
});

// Form submission
document.getElementById('profileForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.classList.add('btn-loading');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
  
  const formData = new FormData(this);
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/users.php?action=update_profile', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      const successAlert = document.getElementById('successAlert');
      const successMessage = document.getElementById('successMessage');
      successMessage.textContent = result.message;
      successAlert.style.display = 'block';
      
      window.scrollTo({ top: 0, behavior: 'smooth' });
      
      setTimeout(() => {
        successAlert.style.display = 'none';
        // Optionally reload to show updated profile
        window.location.reload();
      }, 3000);
    } else {
      alert(result.message || 'An error occurred');
    }
    
    btn.classList.remove('btn-loading');
    btn.disabled = false;
    btn.innerHTML = originalText;
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
    btn.classList.remove('btn-loading');
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', function() {
  updatePreview();
  
  // Initialize bio character count
  const bioTextarea = document.getElementById('bio');
  if (bioTextarea) {
    updateCharCount(bioTextarea, 500, 'bioCount');
  }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>