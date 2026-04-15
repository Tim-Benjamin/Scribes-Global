<?php
$pageTitle = 'Create Post - Admin - Scribes Global';
$pageDescription = 'Create a new blog post';
$pageCSS = 'admin';
$noSplash = true;

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
.post-editor {
  max-width: 900px;
  margin: 0 auto;
}

.editor-section {
  background: white;
  padding: 2rem;
  border-radius: var(--radius-2xl);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.editor-section-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 600;
  color: var(--dark-bg);
  margin-bottom: 0.5rem;
  font-size: 0.9375rem;
}

.form-control {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid var(--gray-300);
  border-radius: var(--radius-lg);
  font-family: var(--font-primary);
  font-size: 0.9375rem;
  transition: all var(--transition-base);
}

.form-control:focus {
  outline: none;
  border-color: #6B46C1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

textarea.form-control {
  resize: vertical;
  min-height: 120px;
  font-family: inherit;
}

.content-editor {
  min-height: 400px;
  border: 2px solid var(--gray-300);
  border-radius: var(--radius-lg);
  padding: 1rem;
  font-family: var(--font-primary);
}

.content-editor:focus {
  outline: none;
  border-color: #6B46C1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.image-upload-area {
  border: 3px dashed var(--gray-300);
  border-radius: var(--radius-xl);
  padding: 3rem;
  text-align: center;
  cursor: pointer;
  transition: all var(--transition-base);
  background: var(--gray-50);
}

.image-upload-area:hover {
  border-color: #6B46C1;
  background: rgba(107, 70, 193, 0.02);
}

.image-upload-area.has-image {
  border-style: solid;
  padding: 1rem;
}

.image-preview {
  max-width: 100%;
  border-radius: var(--radius-lg);
  margin-bottom: 1rem;
}

.upload-icon {
  font-size: 3rem;
  color: var(--gray-400);
  margin-bottom: 1rem;
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

.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  position: sticky;
  bottom: 2rem;
  background: white;
  padding: 1.5rem;
  border-radius: var(--radius-xl);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
}

.preview-pane {
  position: sticky;
  top: 2rem;
  background: white;
  padding: 2rem;
  border-radius: var(--radius-2xl);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.preview-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
}

.preview-card {
  border: 2px solid var(--gray-200);
  border-radius: var(--radius-xl);
  overflow: hidden;
}

.preview-image {
  width: 100%;
  height: 200px;
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 3rem;
}

.preview-content {
  padding: 1.5rem;
}

.preview-category {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: rgba(107, 70, 193, 0.1);
  color: #6B46C1;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 0.75rem;
}

.preview-post-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--dark-bg);
  margin-bottom: 0.5rem;
  line-height: 1.3;
}

.preview-excerpt {
  color: var(--gray-600);
  font-size: 0.875rem;
  line-height: 1.6;
}
</style>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Create New Post</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Share inspiring content with the community</p>
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
    
    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem;">
      <!-- Editor -->
      <div class="post-editor">
        <form id="postForm" enctype="multipart/form-data">
          <!-- Basic Info -->
          <div class="editor-section" data-aos="fade-up">
            <h2 class="editor-section-title">
              <i class="fas fa-pen"></i> Basic Information
            </h2>
            
            <div class="form-group">
              <label for="title" class="form-label">
                Title <span style="color: #EB5757;">*</span>
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
                Excerpt <span style="color: #EB5757;">*</span>
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
                  Category <span style="color: #EB5757;">*</span>
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
                  Status <span style="color: #EB5757;">*</span>
                </label>
                <select id="status" name="status" class="form-control" required>
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                </select>
              </div>
            </div>
            
            <div class="form-group">
              <label for="tags" class="form-label">
                Tags
              </label>
              <input 
                type="text" 
                id="tags" 
                name="tags" 
                class="form-control" 
                placeholder="Separate tags with commas (e.g., faith, worship, inspiration)"
              >
              <small style="color: var(--gray-600); font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                <i class="fas fa-info-circle"></i> Tags help readers find related content
              </small>
            </div>
          </div>
          
          <!-- Featured Image -->
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
                <div style="font-size: 0.875rem; color: var(--gray-600);">
                  JPG, PNG or GIF (Max 5MB)
                </div>
              </div>
            </div>
          </div>
          
          <!-- Content -->
          <div class="editor-section" data-aos="fade-up" data-aos-delay="200">
            <h2 class="editor-section-title">
              <i class="fas fa-align-left"></i> Content
            </h2>
            
            <div class="form-group">
              <label for="content" class="form-label">
                Post Content <span style="color: #EB5757;">*</span>
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
              <small style="color: var(--gray-600); font-size: 0.875rem; display: block; margin-top: 0.5rem;">
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
      
      <!-- Preview -->
      <div class="preview-pane" data-aos="fade-left">
        <div class="preview-title">
          <i class="fas fa-eye"></i> Live Preview
        </div>
        
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
        
        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--gray-100); border-radius: var(--radius-lg);">
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <i class="fas fa-lightbulb" style="color: #D4AF37;"></i>
            <strong style="font-size: 0.875rem; color: var(--dark-bg);">Pro Tips</strong>
          </div>
          <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.875rem; color: var(--gray-600); line-height: 1.8;">
            <li>Use a compelling title (40-60 characters is ideal)</li>
            <li>Write a clear excerpt that hooks readers</li>
            <li>Add a high-quality featured image</li>
            <li>Format content for easy reading</li>
            <li>Include relevant tags for discoverability</li>
          </ul>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
  // Enable detailed error logging
document.getElementById('postForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  // Validation
  const title = document.getElementById('title').value.trim();
  const excerpt = document.getElementById('excerpt').value.trim();
  const content = document.getElementById('content').value.trim();
  const category = document.getElementById('category').value;
  
  console.log('Form data:', { title, excerpt, content, category }); // Debug log
  
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
  
  // Debug: Log form data
  console.log('Sending form data...');
  for (let [key, value] of formData.entries()) {
    if (value instanceof File) {
      console.log(key + ':', value.name, value.size + ' bytes');
    } else {
      console.log(key + ':', value);
    }
  }
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/blog.php?action=create_post', {
      method: 'POST',
      body: formData
    });
    
    console.log('Response status:', response.status); // Debug log
    
    const text = await response.text();
    console.log('Response text:', text); // Debug log
    
    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error('JSON parse error:', e);
      alert('Server error: Invalid response format. Check console for details.');
      btn.disabled = false;
      btn.innerHTML = originalText;
      return;
    }
    
    console.log('Parsed result:', result); // Debug log
    
    if (result.success) {
      alert(result.message || 'Post published successfully!');
      window.location.href = '<?= SITE_URL ?>/admin/posts';
    } else {
      alert('Error: ' + (result.message || 'Failed to publish post'));
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  } catch (error) {
    console.error('Fetch error:', error);
    alert('Network error: ' + error.message);
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});
</script>

<script>
function toggleAdminSidebar() {
  document.getElementById('adminSidebar').classList.toggle('mobile-visible');
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
    <div style="font-size: 0.875rem; color: var(--gray-600);">
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

// Save draft
async function saveDraft() {
  const form = document.getElementById('postForm');
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

// Submit form
document.getElementById('postForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  // Validation
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
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>