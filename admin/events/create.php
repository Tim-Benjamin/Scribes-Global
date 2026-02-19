<?php
$pageTitle = 'Create Event - Admin - Scribes Global';
$pageDescription = 'Create a new event';
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

$db = new Database();
$conn = $db->connect();

// Get all chapters for dropdown
$chaptersStmt = $conn->query("SELECT * FROM chapters WHERE status = 'active' ORDER BY name ASC");
$chapters = $chaptersStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.form-section {
  background: white;
  border-radius: var(--radius-2xl);
  padding: 2.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  position: relative;
  overflow: hidden;
}

.form-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(180deg, #6B46C1 0%, #2D9CDB 100%);
}

.form-section h3 {
  font-size: 1.5rem;
  color: var(--dark-bg);
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--gray-200);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.form-section h3 i {
  color: #6B46C1;
}

.image-upload-area {
  border: 2px dashed var(--gray-300);
  border-radius: var(--radius-xl);
  padding: 2rem;
  text-align: center;
  background: linear-gradient(135deg, rgba(107, 70, 193, 0.02) 0%, rgba(45, 156, 219, 0.02) 100%);
  transition: all var(--transition-base);
  cursor: pointer;
}

.image-upload-area:hover {
  border-color: #6B46C1;
  background: linear-gradient(135deg, rgba(107, 70, 193, 0.05) 0%, rgba(45, 156, 219, 0.05) 100%);
}

.image-upload-area.has-image {
  border-style: solid;
  border-color: #51CF66;
  padding: 0;
}

.image-preview {
  max-width: 100%;
  max-height: 400px;
  border-radius: var(--radius-lg);
  display: block;
  margin: 0 auto;
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

.datetime-inputs {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1rem;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: var(--gray-300);
  transition: .4s;
  border-radius: 34px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .toggle-slider {
  background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
}

input:checked + .toggle-slider:before {
  transform: translateX(26px);
}

.map-container {
  height: 300px;
  border-radius: var(--radius-lg);
  margin-top: 1rem;
  border: 2px solid var(--gray-200);
}

.sticky-save-bar {
  position: sticky;
  bottom: 0;
  background: white;
  padding: 1.5rem 2rem;
  border-radius: var(--radius-2xl);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  z-index: 10;
}

@media (max-width: 768px) {
  .form-section {
    padding: 1.5rem;
  }
  
  .datetime-inputs {
    grid-template-columns: 1fr;
  }
  
  .sticky-save-bar {
    flex-direction: column;
  }
  
  .sticky-save-bar > div {
    width: 100%;
  }
}
</style>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Create Event</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Fill in the details to create a new event</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <a href="<?= SITE_URL ?>/admin/events" class="btn btn-outline">
          <i class="fas fa-arrow-left"></i> Back to Events
        </a>
      </div>
    </div>
    
    <form id="eventForm" enctype="multipart/form-data">
      <!-- Basic Information -->
      <div class="form-section" data-aos="fade-up">
        <h3>
          <i class="fas fa-info-circle"></i>
          Basic Information
        </h3>
        
        <div class="form-group">
          <label for="title" class="form-label">
            Event Title <span style="color: #EB5757;">*</span>
          </label>
          <input 
            type="text" 
            id="title" 
            name="title" 
            class="form-control" 
            placeholder="e.g., Scribes Poetry Night 2024"
            required
            maxlength="200"
          >
        </div>
        
        <div class="form-group">
          <label for="description" class="form-label">
            Event Description <span style="color: #EB5757;">*</span>
          </label>
          <textarea 
            id="description" 
            name="description" 
            class="form-control" 
            rows="6"
            placeholder="Provide a detailed description of the event, what attendees can expect, and any special information..."
            required
            maxlength="2000"
            oninput="updateCharCount(this, 2000, 'descCount')"
          ></textarea>
          <div class="char-counter" id="descCount">0 / 2000 characters</div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="event_type" class="form-label">
              Event Type <span style="color: #EB5757;">*</span>
            </label>
            <select id="event_type" name="event_type" class="form-control" required onchange="toggleVirtualLink()">
              <option value="">Select event type</option>
              <option value="physical">Physical (In-Person)</option>
              <option value="virtual">Virtual (Online)</option>
              <option value="hybrid">Hybrid (Both)</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="chapter_id" class="form-label">
              Chapter / Host
            </label>
            <select id="chapter_id" name="chapter_id" class="form-control">
              <option value="">No specific chapter</option>
              <?php foreach ($chapters as $chapter): ?>
                <option value="<?= $chapter['id'] ?>">
                  <?= htmlspecialchars($chapter['name']) ?> - <?= htmlspecialchars($chapter['location']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      
      <!-- Date & Time -->
      <div class="form-section" data-aos="fade-up">
        <h3>
          <i class="fas fa-calendar-alt"></i>
          Date & Time
        </h3>
        
        <div class="form-row">
          <div class="form-group">
            <label for="start_date" class="form-label">
              Start Date & Time <span style="color: #EB5757;">*</span>
            </label>
            <input 
              type="datetime-local" 
              id="start_date" 
              name="start_date" 
              class="form-control" 
              required
            >
          </div>
          
          <div class="form-group">
            <label for="end_date" class="form-label">
              End Date & Time
            </label>
            <input 
              type="datetime-local" 
              id="end_date" 
              name="end_date" 
              class="form-control"
            >
            <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
              Leave blank if same day event
            </small>
          </div>
        </div>
      </div>
      
      <!-- Location -->
      <div class="form-section" data-aos="fade-up">
        <h3>
          <i class="fas fa-map-marker-alt"></i>
          Location Details
        </h3>
        
        <div class="form-group">
          <label for="location" class="form-label">
            Physical Location / Venue <span style="color: #EB5757;">*</span>
          </label>
          <input 
            type="text" 
            id="location" 
            name="location" 
            class="form-control" 
            placeholder="e.g., National Theatre of Ghana, Accra"
            required
          >
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="latitude" class="form-label">
              Latitude (Optional)
            </label>
            <input 
              type="number" 
              id="latitude" 
              name="latitude" 
              class="form-control" 
              step="any"
              placeholder="5.6037"
            >
          </div>
          
          <div class="form-group">
            <label for="longitude" class="form-label">
              Longitude (Optional)
            </label>
            <input 
              type="number" 
              id="longitude" 
              name="longitude" 
              class="form-control" 
              step="any"
              placeholder="-0.1870"
            >
          </div>
        </div>
        
        <div class="form-group" id="virtualLinkGroup" style="display: none;">
          <label for="virtual_link" class="form-label">
            Virtual Meeting Link
          </label>
          <input 
            type="url" 
            id="virtual_link" 
            name="virtual_link" 
            class="form-control" 
            placeholder="https://zoom.us/j/..."
          >
          <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
            <i class="fas fa-info-circle"></i> Link will be sent to registered attendees
          </small>
        </div>
        
        <div id="mapContainer" class="map-container" style="display: none;"></div>
      </div>
      
      <!-- Registration Settings -->
      <div class="form-section" data-aos="fade-up">
        <h3>
          <i class="fas fa-ticket-alt"></i>
          Registration Settings
        </h3>
        
        <div class="form-group">
          <label class="form-label" style="display: flex; align-items: center; gap: 1rem;">
            <label class="toggle-switch">
              <input type="checkbox" id="registration_enabled" name="registration_enabled" checked onchange="toggleRegistrationSettings()">
              <span class="toggle-slider"></span>
            </label>
            Enable Registration
          </label>
          <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
            Allow users to register for this event
          </small>
        </div>
        
        <div id="registrationSettings">
          <div class="form-group">
            <label for="registration_limit" class="form-label">
              Registration Limit
            </label>
            <input 
              type="number" 
              id="registration_limit" 
              name="registration_limit" 
              class="form-control" 
              placeholder="Leave blank for unlimited"
              min="1"
            >
          </div>
        </div>
      </div>
      
      <!-- Media -->
      <div class="form-section" data-aos="fade-up">
        <h3>
          <i class="fas fa-images"></i>
          Event Media
        </h3>
        
        <div class="form-group">
          <label class="form-label">
            Hero Image <span style="color: #EB5757;">*</span>
          </label>
          <div class="image-upload-area" id="heroUploadArea" onclick="document.getElementById('hero_image').click()">
            <div id="heroPlaceholder">
              <div class="upload-icon">
                <i class="fas fa-cloud-upload-alt"></i>
              </div>
              <h4 style="margin-bottom: 0.5rem; color: var(--dark-bg);">Upload Hero Image</h4>
              <p style="color: var(--gray-600); margin: 0;">
                Click to upload or drag and drop<br>
                <small>JPG, PNG or GIF (Max 5MB)</small>
              </p>
            </div>
            <img id="heroPreview" class="image-preview" style="display: none;">
            <input type="file" id="hero_image" name="hero_image" accept="image/*" style="display: none;" onchange="previewImage(this, 'heroPreview', 'heroPlaceholder', 'heroUploadArea')" required>
          </div>
        </div>
      </div>
      
      <!-- Additional Settings -->
      <div class="form-section" data-aos="fade-up">
        <h3>
          <i class="fas fa-cog"></i>
          Additional Settings
        </h3>
        
        <div class="form-row">
          <div class="form-group">
            <label for="status" class="form-label">
              Event Status <span style="color: #EB5757;">*</span>
            </label>
            <select id="status" name="status" class="form-control" required>
              <option value="upcoming">Upcoming</option>
              <option value="ongoing">Ongoing</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label" style="display: flex; align-items: center; gap: 1rem;">
              <label class="toggle-switch">
                <input type="checkbox" id="featured" name="featured">
                <span class="toggle-slider"></span>
              </label>
              Featured Event
            </label>
            <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
              Display prominently on homepage
            </small>
          </div>
        </div>
      </div>
      
      <!-- Sticky Save Bar -->
      <div class="sticky-save-bar">
        <div>
          <h4 style="margin: 0 0 0.25rem 0; color: var(--dark-bg);">Ready to create event?</h4>
          <p style="margin: 0; font-size: 0.875rem; color: var(--gray-600);">Make sure all required fields are filled</p>
        </div>
        <div style="display: flex; gap: 1rem;">
          <a href="<?= SITE_URL ?>/admin/events" class="btn btn-outline">
            <i class="fas fa-times"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary" style="min-width: 200px;">
            <i class="fas fa-save"></i> Create Event
          </button>
        </div>
      </div>
    </form>
  </main>
</div>

<script>
function toggleAdminSidebar() {
  document.getElementById('adminSidebar').classList.toggle('mobile-visible');
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

function toggleVirtualLink() {
  const eventType = document.getElementById('event_type').value;
  const virtualGroup = document.getElementById('virtualLinkGroup');
  
  if (eventType === 'virtual' || eventType === 'hybrid') {
    virtualGroup.style.display = 'block';
  } else {
    virtualGroup.style.display = 'none';
  }
}

function toggleRegistrationSettings() {
  const enabled = document.getElementById('registration_enabled').checked;
  const settings = document.getElementById('registrationSettings');
  
  if (enabled) {
    settings.style.display = 'block';
  } else {
    settings.style.display = 'none';
  }
}

function previewImage(input, previewId, placeholderId, areaId) {
  const preview = document.getElementById(previewId);
  const placeholder = document.getElementById(placeholderId);
  const area = document.getElementById(areaId);
  
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
      placeholder.style.display = 'none';
      area.classList.add('has-image');
    }
    
    reader.readAsDataURL(input.files[0]);
  }
}

// Form submission
document.getElementById('eventForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.classList.add('btn-loading');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Event...';
  
  const formData = new FormData(this);
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=create_event', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Event created successfully!');
      window.location.href = '<?= SITE_URL ?>/admin/events';
    } else {
      alert(result.message || 'Failed to create event');
      btn.classList.remove('btn-loading');
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
    btn.classList.remove('btn-loading');
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// Initialize map if coordinates are provided
document.getElementById('latitude').addEventListener('input', updateMap);
document.getElementById('longitude').addEventListener('input', updateMap);

function updateMap() {
  const lat = parseFloat(document.getElementById('latitude').value);
  const lng = parseFloat(document.getElementById('longitude').value);
  const container = document.getElementById('mapContainer');
  
  if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
    container.style.display = 'block';
    
    // Initialize map if not already initialized
    if (!window.eventMap) {
      window.eventMap = L.map('mapContainer').setView([lat, lng], 15);
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(window.eventMap);
      
      window.eventMarker = L.marker([lat, lng]).addTo(window.eventMap);
    } else {
      window.eventMap.setView([lat, lng], 15);
      window.eventMarker.setLatLng([lat, lng]);
    }
  } else {
    container.style.display = 'none';
  }
}
</script>

<!-- Leaflet CSS & JS for Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>