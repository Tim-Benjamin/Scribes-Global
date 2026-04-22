<?php
$pageTitle = 'Create Event - Admin - Scribes Global';
$pageDescription = 'Create a new event';
$pageCSS = 'admin';
$noSplash = true;
$noNav = true;        // Don't show main navigation
$noFooter = true;     // Don't show footer content but keeps closing HTML

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
    --font-heading: 'Fraunces', Georgia, serif;
    --font-body: 'DM Sans', sans-serif;
    --transition-base: 300ms ease-in-out;
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

.form-section {
    background: white;
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
    position: relative;
    overflow: hidden;
    transition: all var(--transition-base);
}

.form-section:hover {
    border-color: var(--gray-300);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
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
    margin: 0 0 1.5rem 0;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-family: var(--font-heading);
}

.form-section h3 i {
    color: #6B46C1;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-label {
    display: block;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--dark-bg);
    margin-bottom: 0.5rem;
    font-family: var(--font-body);
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: var(--font-body);
    transition: all var(--transition-base);
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-purple);
    box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.image-upload-area {
    border: 2px dashed var(--gray-300);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    background: linear-gradient(135deg, rgba(107, 70, 193, 0.02) 0%, rgba(45, 156, 219, 0.02) 100%);
    transition: all var(--transition-base);
    cursor: pointer;
    position: relative;
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
    border-radius: 8px;
    display: block;
    margin: 0 auto;
}

.upload-icon {
    font-size: 3rem;
    color: var(--gray-400);
    margin-bottom: 1rem;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.gallery-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1;
    border: 2px solid var(--gray-200);
    transition: all var(--transition-base);
}

.gallery-item:hover {
    border-color: var(--primary-purple);
    box-shadow: 0 4px 12px rgba(107, 70, 193, 0.15);
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-item-remove {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: rgba(235, 87, 87, 0.9);
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    transition: all var(--transition-base);
}

.gallery-item-remove:hover {
    background: #C92A2A;
    transform: scale(1.1);
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
    transition: 0.4s;
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
    transition: 0.4s;
    border-radius: 50%;
}

input:checked+.toggle-slider {
    background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%);
}

input:checked+.toggle-slider:before {
    transform: translateX(26px);
}

.char-counter {
    font-size: 0.875rem;
    color: var(--gray-600);
    text-align: right;
    margin-top: 0.5rem;
}

.char-counter.warning {
    color: #FFA500;
}

.char-counter.danger {
    color: #EB5757;
}

.sticky-save-bar {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 1.5rem 2rem;
    border-radius: 12px;
    border: 1px solid var(--gray-200);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    z-index: 10;
    margin-top: 2rem;
}

.sticky-save-bar h4 {
    margin: 0 0 0.25rem 0;
    color: var(--dark-bg);
    font-family: var(--font-heading);
}

.sticky-save-bar p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--gray-600);
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
    transition: all var(--transition-base);
}

.mobile-admin-toggle:hover {
    background: var(--gray-100);
    border-color: var(--gray-300);
}

@media (max-width: 768px) {
    .admin-main {
        margin-left: 0;
        padding: 1.25rem;
    }

    .mobile-admin-toggle {
        display: flex;
    }

    .form-section {
        padding: 1.5rem;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .sticky-save-bar {
        flex-direction: column;
        gap: 1rem;
    }

    .sticky-save-bar div:last-child {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .sticky-save-bar .btn {
        width: 100%;
    }
}
</style>

<div class="admin-layout">
    <!-- Include Sidebar -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-top-bar">
            <div>
                <h1 class="admin-page-title">Create Event</h1>
                <p style="color: var(--gray-600); margin-top: 0.5rem; font-family: var(--font-body);">Fill in the
                    details to create a new event</p>
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
                    <input type="text" id="title" name="title" class="form-control"
                        placeholder="e.g., Scribes Poetry Night 2024" required maxlength="200">
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">
                        Event Description <span style="color: #EB5757;">*</span>
                    </label>
                    <textarea id="description" name="description" class="form-control" rows="6"
                        placeholder="Provide a detailed description of the event..." required maxlength="2000"
                        oninput="updateCharCount(this, 2000, 'descCount')"></textarea>
                    <div class="char-counter" id="descCount">0 / 2000 characters</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="event_type" class="form-label">
                            Event Type <span style="color: #EB5757;">*</span>
                        </label>
                        <select id="event_type" name="event_type" class="form-control" required
                            onchange="toggleVirtualLink()">
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
                                <?= htmlspecialchars($chapter['name']) ?> -
                                <?= htmlspecialchars($chapter['location']) ?>
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
                        <input type="datetime-local" id="start_date" name="start_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date" class="form-label">
                            End Date & Time
                        </label>
                        <input type="datetime-local" id="end_date" name="end_date" class="form-control">
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
                        Full Address / Venue <span style="color: #EB5757;">*</span>
                    </label>
                    <input type="text" id="location" name="location" class="form-control"
                        placeholder="e.g., National Theatre of Ghana, Liberia Road, Accra, Greater Accra Region, Ghana"
                        required>
                    <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                        <i class="fas fa-info-circle"></i> Provide complete address including city and country
                    </small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude" class="form-label">
                            Latitude <span style="color: var(--gray-600);">(Optional)</span>
                        </label>
                        <input type="text" id="latitude" name="latitude" class="form-control"
                            placeholder="e.g., 5.6037">
                    </div>

                    <div class="form-group">
                        <label for="longitude" class="form-label">
                            Longitude <span style="color: var(--gray-600);">(Optional)</span>
                        </label>
                        <input type="text" id="longitude" name="longitude" class="form-control"
                            placeholder="e.g., -0.1870">
                    </div>
                </div>

                <div
                    style="background: linear-gradient(135deg, rgba(107, 70, 193, 0.05) 0%, rgba(45, 156, 219, 0.05) 100%); padding: 1.5rem; border-radius: 8px; border: 2px dashed rgba(107, 70, 193, 0.2);">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <i class="fas fa-lightbulb" style="font-size: 1.5rem; color: #D4AF37;"></i>
                        <div>
                            <strong
                                style="display: block; color: var(--dark-bg); margin-bottom: 0.25rem; font-family: var(--font-heading);">Pro
                                Tip: Get Accurate Coordinates</strong>
                            <p style="margin: 0; font-size: 0.875rem; color: var(--gray-600);">
                                1. Go to <a href="https://www.google.com/maps" target="_blank"
                                    style="color: #6B46C1; font-weight: 600;">Google Maps</a><br>
                                2. Right-click on your venue location<br>
                                3. Click the coordinates to copy them<br>
                                4. Paste them above and click "Preview Location"
                            </p>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" style="width: 100%;" onclick="previewMapLocation()">
                        <i class="fas fa-eye"></i> Preview Location on Map
                    </button>
                </div>

                <!-- Map Preview -->
                <div id="mapPreviewContainer" style="display: none; margin-top: 1.5rem;">
                    <div
                        style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); border: 1px solid var(--gray-200);">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h4
                                style="margin: 0; color: var(--dark-bg); display: flex; align-items: center; gap: 0.5rem; font-family: var(--font-heading);">
                                <i class="fas fa-map" style="color: #6B46C1;"></i>
                                Location Preview
                            </h4>
                            <button type="button" class="btn btn-outline btn-sm" onclick="closeMapPreview()">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                        <div id="adminMapPreview"
                            style="height: 300px; border-radius: 8px; overflow: hidden; border: 2px solid var(--gray-200);">
                        </div>
                        <p style="margin: 1rem 0 0 0; font-size: 0.875rem; color: var(--gray-600); text-align: center;">
                            <i class="fas fa-info-circle"></i> This is how the location will appear to users
                        </p>
                    </div>
                </div>

                <div class="form-group" id="virtualLinkGroup" style="display: none; margin-top: 1.5rem;">
                    <label for="virtual_link" class="form-label">
                        Virtual Meeting Link
                    </label>
                    <input type="url" id="virtual_link" name="virtual_link" class="form-control"
                        placeholder="https://zoom.us/j/...">
                </div>
            </div>

            <!-- Registration & RSVP Settings -->
            <div class="form-section" data-aos="fade-up">
                <h3>
                    <i class="fas fa-ticket-alt"></i>
                    Registration & RSVP Settings
                </h3>

                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; gap: 1rem;">
                        <label class="toggle-switch">
                            <input type="checkbox" id="registration_enabled" name="registration_enabled" checked
                                onchange="toggleRegistrationSettings()">
                            <span class="toggle-slider"></span>
                        </label>
                        Enable Registration
                    </label>
                    <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                        Allow users to register and receive confirmation emails
                    </small>
                </div>

                <div id="registrationSettings">
                    <div class="form-group">
                        <label for="registration_limit" class="form-label">
                            Registration Limit
                        </label>
                        <input type="number" id="registration_limit" name="registration_limit" class="form-control"
                            placeholder="Leave blank for unlimited" min="1">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 1rem;">
                        <label class="toggle-switch">
                            <input type="checkbox" id="rsvp_enabled" name="rsvp_enabled">
                            <span class="toggle-slider"></span>
                        </label>
                        Enable RSVP
                    </label>
                    <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                        Allow users to respond Yes/No/Maybe without full registration
                    </small>
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
                    <div class="image-upload-area" id="heroUploadArea"
                        onclick="document.getElementById('hero_image').click()">
                        <div id="heroPlaceholder">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h4 style="margin-bottom: 0.5rem; color: var(--dark-bg); font-family: var(--font-heading);">
                                Upload Hero Image</h4>
                            <p style="color: var(--gray-600); margin: 0;">
                                Click to upload or drag and drop<br>
                                <small>JPG, PNG or GIF (Max 5MB)</small>
                            </p>
                        </div>
                        <img id="heroPreview" class="image-preview" style="display: none;">
                        <input type="file" id="hero_image" name="hero_image" accept="image/*" style="display: none;"
                            onchange="previewImage(this, 'heroPreview', 'heroPlaceholder', 'heroUploadArea')" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 2rem;">
                    <label class="form-label">
                        Additional Gallery Images (Optional)
                    </label>
                    <div class="image-upload-area" onclick="document.getElementById('gallery_images').click()">
                        <div class="upload-icon" style="font-size: 2rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-images"></i>
                        </div>
                        <p style="color: var(--gray-600); margin: 0;">
                            Add multiple images (Max 10 images, 5MB each)
                        </p>
                        <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" multiple
                            style="display: none;" onchange="previewGallery(this)">
                    </div>

                    <div id="galleryPreview" class="gallery-grid" style="display: none;"></div>
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

                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; gap: 1rem;">
                        <label class="toggle-switch">
                            <input type="checkbox" id="notify_users" name="notify_users" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        Notify All Users
                    </label>
                    <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                        Send email notification about this event to all active users
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" style="display: flex; align-items: center; gap: 1rem;">
                    <label class="toggle-switch">
                        <input type="checkbox" id="notify_newsletter" name="notify_newsletter">
                        <span class="toggle-slider"></span>
                    </label>
                    Notify Newsletter Subscribers
                </label>
                <small style="color: var(--gray-600); font-size: 0.875rem; margin-top: 0.5rem; display: block;">
                    Send email notification about this event to all active newsletter subscribers
                </small>
            </div>
            

            <!-- Sticky Save Bar -->
            <div class="sticky-save-bar">
                <div>
                    <h4>Ready to create event?</h4>
                    <p>All users will be notified if enabled</p>
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

let galleryFiles = [];

function previewGallery(input) {
    const preview = document.getElementById('galleryPreview');
    const files = Array.from(input.files);

    if (files.length > 10) {
        alert('Maximum 10 images allowed');
        return;
    }

    galleryFiles = files;
    preview.innerHTML = '';
    preview.style.display = 'grid';

    files.forEach((file, index) => {
        const reader = new FileReader();

        reader.onload = function(e) {
            const item = document.createElement('div');
            item.className = 'gallery-item';
            item.innerHTML = `
          <img src="${e.target.result}" alt="Gallery">
          <button type="button" class="gallery-item-remove" onclick="removeGalleryItem(${index})">
            <i class="fas fa-times"></i>
          </button>
        `;
            preview.appendChild(item);
        }

        reader.readAsDataURL(file);
    });
}

function removeGalleryItem(index) {
    galleryFiles.splice(index, 1);

    const dataTransfer = new DataTransfer();
    galleryFiles.forEach(file => dataTransfer.items.add(file));
    document.getElementById('gallery_images').files = dataTransfer.files;

    const preview = document.getElementById('galleryPreview');
    preview.innerHTML = '';

    if (galleryFiles.length === 0) {
        preview.style.display = 'none';
        return;
    }

    galleryFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const item = document.createElement('div');
            item.className = 'gallery-item';
            item.innerHTML = `
          <img src="${e.target.result}" alt="Gallery">
          <button type="button" class="gallery-item-remove" onclick="removeGalleryItem(${index})">
            <i class="fas fa-times"></i>
          </button>
        `;
            preview.appendChild(item);
        }
        reader.readAsDataURL(file);
    });
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
            alert('Event created successfully! ' + (result.notifications_sent ? result.notifications_sent +
                ' users notified.' : ''));
            window.location.href = '<?= SITE_URL ?>/admin/events';
        } else {
            alert('Error: ' + result.message);
            console.error('Full error:', result);
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

// Map Preview Functions
let adminMap = null;
let adminMapInitialized = false;

function previewMapLocation() {
    const latInput = document.getElementById('latitude').value.trim();
    const lngInput = document.getElementById('longitude').value.trim();

    if (!latInput || !lngInput) {
        alert('Please enter both latitude and longitude coordinates');
        return;
    }

    const lat = parseFloat(latInput);
    const lng = parseFloat(lngInput);

    if (isNaN(lat) || isNaN(lng)) {
        alert('Please enter valid numeric coordinates');
        return;
    }

    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
        alert('Please enter valid coordinate ranges:\nLatitude: -90 to 90\nLongitude: -180 to 180');
        return;
    }

    const container = document.getElementById('mapPreviewContainer');
    container.style.display = 'block';

    container.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });

    setTimeout(() => {
        if (!adminMapInitialized) {
            try {
                adminMap = L.map('adminMapPreview').setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(adminMap);

                const marker = L.marker([lat, lng]).addTo(adminMap);
                marker.bindPopup('<strong>Event Location</strong>').openPopup();

                adminMapInitialized = true;
            } catch (error) {
                console.error('Map error:', error);
                alert('Failed to load map. Please try again.');
            }
        } else {
            adminMap.setView([lat, lng], 15);
            adminMap.eachLayer(function(layer) {
                if (layer instanceof L.Marker) {
                    layer.setLatLng([lat, lng]);
                }
            });
        }

        setTimeout(() => {
            if (adminMap) {
                adminMap.invalidateSize();
            }
        }, 100);
    }, 100);
}

function closeMapPreview() {
    const container = document.getElementById('mapPreviewContainer');
    container.style.display = 'none';
}

// Initialize AOS
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true,
    offset: 100
});

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
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>