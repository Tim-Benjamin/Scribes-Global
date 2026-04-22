<?php
$pageTitle = 'Create Post - Admin - Scribes Global';
$pageDescription = 'Create a new blog post';
$noSplash = true;
$noNav = true;
$noFooter = true;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

if (!isAdmin()) {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: ' . SITE_URL . '/pages/dashboard');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* ════════════════��══════════════════════════════════════════
   POST EDITOR - MODERN DESIGN
   ═══════════════════════════════════════════════════════════ */

:root {
  --primary-purple: #6B46C1;
  --primary-gold: #D4AF37;
  --dark-bg: #1A1A2E;
  --white: #FFFFFF;
  --gray-50: #F9FAFB;
  --gray-100: #F3F4F6;
  --gray-200: #E5E7EB;
  --gray-300: #D1D5DB;
  --gray-400: #9CA3AF;
  --gray-600: #4B5563;
  --gray-700: #374151;
  --gray-800: #1F2937;
  --font-heading: 'Fraunces', Georgia, serif;
  --font-body: 'DM Sans', sans-serif;
  --transition: 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

body {
  margin: 0;
  padding: 0;
  background: var(--gray-50);
  font-family: var(--font-body);
}

.admin-layout {
  display: flex;
  background: var(--gray-50);
  min-height: 100vh;
}

.admin-main {
  flex: 1;
  margin-left: 260px;
  padding: 2rem;
  overflow-y: auto;
}

.admin-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.admin-page-title {
  margin: 0;
  font-size: clamp(1.75rem, 4vw, 2.25rem);
  font-family: var(--font-heading);
  font-weight: 700;
  color: var(--dark-bg);
  letter-spacing: -0.5px;
}

.admin-page-subtitle {
  color: var(--gray-600);
  margin-top: 0.5rem;
  font-size: 0.95rem;
  font-family: var(--font-body);
}

.admin-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.mobile-admin-toggle {
  display: none;
  background: var(--white);
  border: 1px solid var(--gray-200);
  width: 40px;
  height: 40px;
  border-radius: 8px;
  cursor: pointer;
  color: var(--dark-bg);
  font-size: 1.25rem;
  transition: all var(--transition);
}

.mobile-admin-toggle:hover {
  background: var(--gray-100);
  border-color: var(--gray-300);
}

/* ─── Editor Layout ────────────────────────────────────────– */
.post-editor-wrapper {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 2rem;
}

.post-editor {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.editor-section {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid var(--gray-200);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all var(--transition);
}

.editor-section:hover {
  border-color: var(--gray-300);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.editor-section-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin: 0 0 1.5rem 0;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-family: var(--font-heading);
}

.editor-section-title i {
  color: var(--primary-purple);
  font-size: 1.1rem;
}

/* ─── Form Elements ────────────────────────────────────────– */
.form-group {
  margin-bottom: 1.5rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-label {
  display: block;
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 0.625rem;
  font-size: 0.9375rem;
  font-family: var(--font-body);
}

.form-label span {
  color: #EB5757;
  margin-left: 0.25rem;
}

.form-control {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 1.5px solid var(--gray-300);
  border-radius: 8px;
  font-family: var(--font-body);
  font-size: 0.9375rem;
  transition: all var(--transition);
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-purple);
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
  background: rgba(107, 70, 193, 0.02);
}

textarea.form-control {
  resize: vertical;
  min-height: 120px;
  font-family: var(--font-body);
}

.content-editor {
  min-height: 400px;
  border: 1.5px solid var(--gray-300);
  border-radius: 8px;
  padding: 1rem;
  font-family: 'Monaco', 'Courier New', monospace;
  font-size: 0.9375rem;
  background: white;
  transition: all var(--transition);
}

.content-editor:focus {
  outline: none;
  border-color: var(--primary-purple);
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

/* ─── Image Upload ────────────────────────────────────────– */
.image-upload-area {
  border: 2px dashed var(--gray-300);
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  cursor: pointer;
  transition: all var(--transition);
  background: var(--gray-50);
}

.image-upload-area:hover {
  border-color: var(--primary-purple);
  background: rgba(107, 70, 193, 0.03);
  transform: translateY(-2px);
}

.image-upload-area.has-image {
  border-style: solid;
  padding: 1rem;
}

.image-preview {
  max-width: 100%;
  border-radius: 8px;
  margin-bottom: 1rem;
  max-height: 300px;
  object-fit: cover;
}

.upload-icon {
  font-size: 3rem;
  color: var(--gray-400);
  margin-bottom: 1rem;
}

/* ─── Character Counter ────────────────────────────────────– */
.char-counter {
  font-size: 0.8125rem;
  color: var(--gray-600);
  text-align: right;
  margin-top: 0.5rem;
  font-weight: 500;
}

.char-counter.warning {
  color: #FFA500;
}

.char-counter.danger {
  color: #EB5757;
  font-weight: 700;
}

/* ─── Action Buttons ────────────────────────────────────────– */
.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  border: 1px solid var(--gray-200);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* ─── Preview Pane ────────────────────────────────────────– */
.preview-pane {
  position: sticky;
  top: 2rem;
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid var(--gray-200);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  height: fit-content;
}

.preview-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin: 0;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--gray-200);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: var(--font-heading);
}

.preview-title i {
  color: var(--primary-purple);
}

.preview-card {
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  overflow: hidden;
  background: var(--gray-50);
}

.preview-image {
  width: 100%;
  height: 150px;
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2.5rem;
}

.preview-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-content {
  padding: 1rem;
}

.preview-category {
  display: inline-block;
  padding: 0.35rem 0.75rem;
  background: rgba(107, 70, 193, 0.1);
  color: var(--primary-purple);
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.75rem;
}

.preview-post-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin: 0 0 0.5rem 0;
  line-height: 1.3;
  font-family: var(--font-heading);
}

.preview-excerpt {
  color: var(--gray-600);
  font-size: 0.8125rem;
  line-height: 1.6;
  margin: 0;
}

.pro-tips {
  padding: 1rem;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.08) 0%, rgba(212, 175, 55, 0.04) 100%);
  border-radius: 8px;
  border-left: 3px solid var(--primary-gold);
}

.pro-tips-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.75rem 0;
  font-weight: 700;
  font-size: 0.875rem;
  color: var(--dark-bg);
}

.pro-tips-title i {
  color: var(--primary-gold);
  font-size: 1rem;
}

.pro-tips-list {
  margin: 0;
  padding-left: 1.25rem;
  font-size: 0.8125rem;
  color: var(--gray-700);
  line-height: 1.8;
}

.pro-tips-list li {
  margin-bottom: 0.5rem;
}

/* ─── Responsive Design ────────────────────────────────────– */
@media (max-width: 1024px) {
  .post-editor-wrapper {
    grid-template-columns: 1fr;
  }

  .preview-pane {
    position: static;
    order: -1;
  }
}

@media (max-width: 768px) {
  .admin-main {
    margin-left: 0;
    padding: 1.25rem;
  }

  .mobile-admin-toggle {
    display: flex;
  }

  .editor-section {
    padding: 1.5rem;
  }

  .action-buttons {
    flex-direction: column;
  }

  .action-buttons button {
    width: 100%;
  }

  .admin-page-title {
    font-size: 1.5rem;
  }
}

@media (max-width: 480px) {
  .admin-main {
    padding: 1rem;
  }

  .editor-section {
    padding: 1rem;
  }

  .editor-section-title {
    font-size: 1rem;
  }

  .image-upload-area {
    padding: 2rem 1rem;
  }

  .upload-icon {
    font-size: 2rem;
  }

  .content-editor {
    min-height: 250px;
    font-size: 0.875rem;
  }
}
</style>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Create New Post</h1>
        <p class="admin-page-subtitle">Share inspiring content with the community</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <a href="<?= SITE_URL ?>/admin/posts" class="btn btn-outline">
          <i class="fas fa-arrow-left"></i> Back to Posts
        </a>
      </div>
    </div>
    
    <div class="post-editor-wrapper">
      <!-- Editor -->
      <div class="post-editor">
        <form id="postForm" enctype="multipart/form-data">
          
          <!-- Basic Info Section -->
          <div class="editor-section" data-aos="fade-up">
            <h2 class="editor-section-title">
              <i class="fas fa-pen"></i> Basic Information
            </h2>
            
            <div class="form-group">
              <label for="title" class="form-label">
                Title <span>*</span>
              </label>
              <input 
                type="text" 
                id="title" 
                name="title" 
                class="form-control" 
                placeholder="Enter post title..."
                required
                maxlength="200"
                oninput="updatePreview(); updateCharCount(this, 200, 'titleCount')"
              >
              <div class="char-counter" id="titleCount">0 / 200 characters</div>
            </div>
            
            <div class="form-group">
              <label for="excerpt" class="form-label">
                Excerpt <span>*</span>
              </label>
              <textarea 
                id="excerpt" 
                name="excerpt" 
                class="form-control"
                placeholder="Brief summary of your post (shown in previews)..."
                rows="3"
                required
                maxlength="280"
                oninput="updatePreview(); updateCharCount(this, 280, 'excerptCount')"
              ></textarea>
              <div class="char-counter" id="excerptCount">0 / 280 characters</div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
              <div class="form-group">
                <label for="category" class="form-label">
                  Category <span>*</span>
                </label>
                <select id="category" name="category" class="form-control" required onchange="updatePreview()">
                  <option value="">Select category</option>
                  <option value="Poetry">Poetry</option>
                  <option value="Worship">Worship</option>
                  <option value="Teaching">Teaching</option>
                  <option value="Testimony">Testimony</option>
                  <option value="Prayer">Prayer</option>
                  <option value="Creative">Creative</option>
                  <option value="Leadership">Leadership</option>
                  <option value="Youth">Youth</option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="status" class="form-label">
                  Status <span>*</span>
                </label>
                <select id="status" name="status" class="form-control" required>
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                </select>
              </div>
            </div>
            
            <div class="form-group">
              <label for="tags" class="form-label">Tags</label>
              <input 
                type="text" 
                id="tags" 
                name="tags" 
                class="form-control" 
                placeholder="Separate tags with commas (e.g., faith, worship, inspiration)"
              >
              <small style="color: var(--gray-600); font-size: 0.8125rem; display: block; margin-top: 0.5rem;">
                <i class="fas fa-info-circle"></i> Tags help readers find related content
              </small>
            </div>
          </div>
          
          <!-- Featured Image Section -->
          <div class="editor-section" data-aos="fade-up" data-aos-delay="100">
            <h2 class="editor-section-title">
              <i class="fas fa-image"></i> Featured Image
            </h2>
            
            <div class="image-upload-area" id="imageUploadArea" onclick="document.getElementById('featuredImage').click()">
              <input 
                type="file" 
                id="featuredImage" 
                name="featured_image" 
                accept="image/*"
                style="display: none;"
                onchange="previewImage(this)"
              >
              <div id="imagePreviewContainer">
                <div class="upload-icon">
                  <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div style="font-weight: 600; color: var(--dark-bg); margin-bottom: 0.5rem;">
                  Click to upload featured image
                </div>
                <div style="font-size: 0.8125rem; color: var(--gray-600);">
                  JPG, PNG or GIF (Max 5MB)
                </div>
              </div>
            </div>
          </div>
          
          <!-- Content Section -->
          <div class="editor-section" data-aos="fade-up" data-aos-delay="200">
            <h2 class="editor-section-title">
              <i class="fas fa-align-left"></i> Content
            </h2>
            
            <div class="form-group">
              <label for="content" class="form-label">
                Post Content <span>*</span>
              </label>
              <textarea 
                id="content" 
                name="content" 
                class="content-editor"
                placeholder="Write your post content here...

You can use:
- Line breaks for paragraphs
- Bullet points for lists
- Clear formatting for readability

Share your message with passion and clarity!"
                required
              ></textarea>
              <small style="color: var(--gray-600); font-size: 0.8125rem; display: block; margin-top: 0.5rem;">
                <i class="fas fa-info-circle"></i> Minimum 100 characters recommended
              </small>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="action-buttons">
            <button type="button" class="btn btn-outline" onclick="saveDraft()">
              <i class="fas fa-save"></i> Save Draft
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-paper-plane"></i> Publish Post
            </button>
          </div>
        </form>
      </div>
      
      <!-- Preview Sidebar -->
      <div class="preview-pane" data-aos="fade-left">
        <h3 class="preview-title">
          <i class="fas fa-eye"></i> Live Preview
        </h3>
        
        <div class="preview-card">
          <div class="preview-image" id="previewImage">
            <i class="far fa-image"></i>
          </div>
          
          <div class="preview-content">
            <span class="preview-category" id="previewCategory">Category</span>
            <h3 class="preview-post-title" id="previewTitle">Your post title will appear here</h3>
            <p class="preview-excerpt" id="previewExcerpt">Your excerpt will appear here...</p>
          </div>
        </div>
        
        <div class="pro-tips">
          <h4 class="pro-tips-title">
            <i class="fas fa-lightbulb"></i> Pro Tips
          </h4>
          <ul class="pro-tips-list">
            <li>Use a compelling title (40-60 characters)</li>
            <li>Write a clear, engaging excerpt</li>
            <li>Add a high-quality featured image</li>
            <li>Format content for easy reading</li>
            <li>Include relevant tags</li>
          </ul>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
function toggleAdminSidebar() {
  const sidebar = document.getElementById('adminSidebar');
  if (sidebar) {
    sidebar.classList.toggle('mobile-visible');
  }
}

// Character counter
function updateCharCount(input, maxLength, counterId) {
  const counter = document.getElementById(counterId);
  const length = input.value.length;
  counter.textContent = length + ' / ' + maxLength + ' characters';
  
  counter.classList.remove('warning', 'danger');
  if (length > maxLength * 0.9) {
    counter.classList.add('danger');
  } else if (length > maxLength * 0.75) {
    counter.classList.add('warning');
  }
}

// Image preview
function previewImage(input) {
  const uploadArea = document.getElementById('imageUploadArea');
  const previewContainer = document.getElementById('imagePreviewContainer');
  const previewImage = document.getElementById('previewImage');
  
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
      uploadArea.classList.add('has-image');
      previewContainer.innerHTML = `
        <img src="${e.target.result}" class="image-preview">
        <button type="button" class="btn btn-outline btn-sm" onclick="removeImage(); event.stopPropagation();">
          <i class="fas fa-times"></i> Remove Image
        </button>
      `;
      
      previewImage.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
    }
    
    reader.readAsDataURL(input.files[0]);
  }
}

function removeImage() {
  const uploadArea = document.getElementById('imageUploadArea');
  const previewContainer = document.getElementById('imagePreviewContainer');
  const previewImage = document.getElementById('previewImage');
  const input = document.getElementById('featuredImage');
  
  input.value = '';
  uploadArea.classList.remove('has-image');
  previewContainer.innerHTML = `
    <div class="upload-icon">
      <i class="fas fa-cloud-upload-alt"></i>
    </div>
    <div style="font-weight: 600; color: var(--dark-bg); margin-bottom: 0.5rem;">
      Click to upload featured image
    </div>
    <div style="font-size: 0.8125rem; color: var(--gray-600);">
      JPG, PNG or GIF (Max 5MB)
    </div>
  `;
  
  previewImage.innerHTML = '<i class="far fa-image"></i>';
}

// Live preview
function updatePreview() {
  const title = document.getElementById('title').value || 'Your post title will appear here';
  const excerpt = document.getElementById('excerpt').value || 'Your excerpt will appear here...';
  const category = document.getElementById('category').value || 'Category';
  
  document.getElementById('previewTitle').textContent = title;
  document.getElementById('previewExcerpt').textContent = excerpt;
  document.getElementById('previewCategory').textContent = category;
}

// Form submission
document.getElementById('postForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const title = document.getElementById('title').value.trim();
  const excerpt = document.getElementById('excerpt').value.trim();
  const content = document.getElementById('content').value.trim();
  const category = document.getElementById('category').value;
  
  if (!title || !excerpt || !content || !category) {
    alert('Please fill in all required fields');
    return;
  }
  
  if (content.length < 100) {
    alert('Post content should be at least 100 characters');
    return;
  }
  
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
  
  const formData = new FormData(this);
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/blog.php?action=create_post', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Post published successfully!');
      window.location.href = '<?= SITE_URL ?>/admin/posts';
    } else {
      alert(result.message || 'Failed to publish post');
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// Save draft
async function saveDraft() {
  const form = document.getElementById('postForm');
  const title = document.getElementById('title').value.trim();
  const excerpt = document.getElementById('excerpt').value.trim();
  
  if (!title || !excerpt) {
    alert('Please fill in title and excerpt');
    return;
  }
  
  const formData = new FormData(form);
  formData.set('status', 'draft');
  
  const btn = event.target;
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/blog.php?action=create_post', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Draft saved successfully!');
      window.location.href = '<?= SITE_URL ?>/admin/posts';
    } else {
      alert(result.message || 'Failed to save draft');
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
}

// Close sidebar on mobile when clicking outside
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('adminSidebar');
  const toggle = document.querySelector('.mobile-admin-toggle');
  
  if (window.innerWidth <= 768 && 
      sidebar &&
      !sidebar.contains(e.target) && 
      toggle &&
      !toggle.contains(e.target) &&
      sidebar.classList.contains('mobile-visible')) {
    sidebar.classList.remove('mobile-visible');
  }
});

// Initialize AOS
if (typeof AOS !== 'undefined') {
  AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true,
    offset: 100
  });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>