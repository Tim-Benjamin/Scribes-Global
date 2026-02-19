<?php
$pageTitle = 'Chapters Management - Admin - Scribes Global';
$pageDescription = 'Manage chapters and locations';
$pageCSS = 'admin';
$noSplash = true;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

if (!isAdmin()) {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: ' . SITE_URL . '/pages/dashboard');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT c.*, 
          u.first_name as leader_first_name, 
          u.last_name as leader_last_name,
          (SELECT COUNT(*) FROM users WHERE chapter_id = c.id) as member_count
          FROM chapters c
          LEFT JOIN users u ON c.leader_id = u.id
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.location LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($status)) {
    $query .= " AND c.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$chapters = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM chapters WHERE 1=1";
$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (name LIKE ? OR location LIKE ?)";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
}
if (!empty($status)) {
    $countQuery .= " AND status = ?";
    $countParams[] = $status;
}

$countStmt = $conn->prepare($countQuery);
$countStmt->execute($countParams);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Get stats
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM chapters WHERE status = 'active') as active_chapters,
        (SELECT COUNT(*) FROM chapters WHERE status = 'inactive') as inactive_chapters,
        (SELECT COUNT(*) FROM users WHERE chapter_id IS NOT NULL) as total_members
";
$statsStmt = $conn->query($statsQuery);
$stats = $statsStmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Chapters Management</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Manage chapters and their locations</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <button class="btn btn-primary" onclick="openCreateChapterModal()">
          <i class="fas fa-plus"></i> Create Chapter
        </button>
      </div>
    </div>
    
    <!-- Stats -->
    <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
      <div class="admin-stat-card purple" data-aos="fade-up">
        <div class="admin-stat-header">
          <div class="admin-stat-icon purple">
            <i class="fas fa-map-marker-alt"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['active_chapters']) ?></div>
        <div class="admin-stat-label">Active Chapters</div>
      </div>
      
      <div class="admin-stat-card gold" data-aos="fade-up" data-aos-delay="100">
        <div class="admin-stat-header">
          <div class="admin-stat-icon gold">
            <i class="fas fa-users"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['total_members']) ?></div>
        <div class="admin-stat-label">Total Members</div>
      </div>
      
      <div class="admin-stat-card coral" data-aos="fade-up" data-aos-delay="200">
        <div class="admin-stat-header">
          <div class="admin-stat-icon coral">
            <i class="fas fa-ban"></i>
          </div>
        </div>
        <div class="admin-stat-value"><?= number_format($stats['inactive_chapters']) ?></div>
        <div class="admin-stat-label">Inactive Chapters</div>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
      <input 
        type="text" 
        class="search-input" 
        placeholder="Search chapters by name or location..." 
        value="<?= htmlspecialchars($search) ?>"
        id="searchInput"
      >
      
      <select class="filter-select" id="statusFilter">
        <option value="">All Statuses</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
      
      <button class="btn btn-outline" onclick="resetFilters()">
        <i class="fas fa-redo"></i> Reset
      </button>
    </div>
    
    <!-- Chapters Grid -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-map-marked-alt"></i>
          All Chapters (<?= number_format($total) ?>)
        </h3>
      </div>
      <div class="admin-card-body">
        <?php if (count($chapters) > 0): ?>
          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
            <?php foreach ($chapters as $chapter): ?>
              <div class="card" style="cursor: pointer; transition: all var(--transition-base);" data-aos="fade-up">
                <div style="padding: 1.5rem;">
                  <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <h3 style="margin: 0; font-size: 1.25rem; color: var(--dark-bg);">
                      <?= htmlspecialchars($chapter['name']) ?>
                    </h3>
                    <span class="status-badge <?= $chapter['status'] ?>">
                      <?= ucfirst($chapter['status']) ?>
                    </span>
                  </div>
                  
                  <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem; color: var(--gray-600); font-size: 0.95rem;">
                    <div>
                      <i class="fas fa-map-marker-alt" style="width: 20px; color: #6B46C1;"></i>
                      <?= htmlspecialchars($chapter['location']) ?>
                    </div>
                    
                    <?php if ($chapter['leader_first_name']): ?>
                      <div>
                        <i class="fas fa-user-tie" style="width: 20px; color: #D4AF37;"></i>
                        <?= htmlspecialchars($chapter['leader_first_name'] . ' ' . $chapter['leader_last_name']) ?>
                      </div>
                    <?php endif; ?>
                    
                    <div>
                      <i class="fas fa-users" style="width: 20px; color: #2D9CDB;"></i>
                      <?= number_format($chapter['member_count']) ?> members
                    </div>
                    
                    <?php if ($chapter['meeting_schedule']): ?>
                      <div>
                        <i class="fas fa-calendar" style="width: 20px; color: #51CF66;"></i>
                        <?= htmlspecialchars($chapter['meeting_schedule']) ?>
                      </div>
                    <?php endif; ?>
                    
                    <?php if ($chapter['contact_email']): ?>
                      <div>
                        <i class="fas fa-envelope" style="width: 20px; color: #EB5757;"></i>
                        <?= htmlspecialchars($chapter['contact_email']) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  
                  <div style="display: flex; gap: 0.5rem; padding-top: 1rem; border-top: 1px solid var(--gray-200);">
                    <button class="btn btn-primary btn-sm" style="flex: 1;" onclick="viewChapter(<?= $chapter['id'] ?>)">
                      <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn btn-secondary btn-sm" style="flex: 1;" onclick="editChapter(<?= $chapter['id'] ?>)">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn-icon btn-delete" onclick="deleteChapter(<?= $chapter['id'] ?>, '<?= htmlspecialchars($chapter['name']) ?>')">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top: 2rem;">
              <?php if ($page > 1): ?>
                <button onclick="goToPage(<?= $page - 1 ?>)">
                  <i class="fas fa-chevron-left"></i> Previous
                </button>
              <?php endif; ?>
              
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                  <button class="<?= $i == $page ? 'active' : '' ?>" onclick="goToPage(<?= $i ?>)">
                    <?= $i ?>
                  </button>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                  <span>...</span>
                <?php endif; ?>
              <?php endfor; ?>
              
              <?php if ($page < $totalPages): ?>
                <button onclick="goToPage(<?= $page + 1 ?>)">
                  Next <i class="fas fa-chevron-right"></i>
                </button>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-map-marked-alt"></i></div>
            <div class="empty-state-title">No Chapters Found</div>
            <div class="empty-state-text">
              <?php if (!empty($search) || !empty($status)): ?>
                Try adjusting your filters
              <?php else: ?>
                Create your first chapter to get started
              <?php endif; ?>
            </div>
            <?php if (empty($search) && empty($status)): ?>
              <button class="btn btn-primary" onclick="openCreateChapterModal()">
                <i class="fas fa-plus"></i> Create First Chapter
              </button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- Create/Edit Chapter Modal -->
<div class="admin-modal" id="chapterModal">
  <div class="admin-modal-content">
    <div class="admin-modal-header">
      <h2 id="modalTitle">Create Chapter</h2>
      <button class="admin-modal-close" onclick="closeChapterModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="admin-modal-body">
      <form id="chapterForm">
        <input type="hidden" id="chapter_id" name="chapter_id">
        
        <div class="form-row">
          <div class="form-group">
            <label for="name" class="form-label">Chapter Name <span style="color: #EB5757;">*</span></label>
            <input 
              type="text" 
              id="name" 
              name="name" 
              class="form-control" 
              placeholder="e.g., Accra Central Chapter"
              required
            >
          </div>
          
          <div class="form-group">
            <label for="location" class="form-label">Location <span style="color: #EB5757;">*</span></label>
            <input 
              type="text" 
              id="location" 
              name="location" 
              class="form-control" 
              placeholder="e.g., Accra, Ghana"
              required
            >
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="latitude" class="form-label">Latitude</label>
            <input 
              type="number" 
              id="latitude" 
              name="latitude" 
              class="form-control" 
              step="any"
              placeholder="5.6037"
            >
            <small style="color: var(--gray-600); font-size: 0.875rem;">For map display</small>
          </div>
          
          <div class="form-group">
            <label for="longitude" class="form-label">Longitude</label>
            <input 
              type="number" 
              id="longitude" 
              name="longitude" 
              class="form-control" 
              step="any"
              placeholder="-0.1870"
            >
            <small style="color: var(--gray-600); font-size: 0.875rem;">For map display</small>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="contact_email" class="form-label">Contact Email</label>
            <input 
              type="email" 
              id="contact_email" 
              name="contact_email" 
              class="form-control" 
              placeholder="accra@scribesglobal.com"
            >
          </div>
          
          <div class="form-group">
            <label for="contact_phone" class="form-label">Contact Phone</label>
            <input 
              type="tel" 
              id="contact_phone" 
              name="contact_phone" 
              class="form-control" 
              placeholder="+233 123 456 789"
            >
          </div>
        </div>
        
        <div class="form-group">
          <label for="meeting_schedule" class="form-label">Meeting Schedule</label>
          <input 
            type="text" 
            id="meeting_schedule" 
            name="meeting_schedule" 
            class="form-control" 
            placeholder="Every Sunday at 10:00 AM"
          >
        </div>
        
        <div class="form-group">
          <label for="description" class="form-label">Description</label>
          <textarea 
            id="description" 
            name="description" 
            class="form-control" 
            rows="4"
            placeholder="Brief description about this chapter..."
          ></textarea>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="leader_id" class="form-label">Chapter Leader</label>
            <select id="leader_id" name="leader_id" class="form-control">
              <option value="">Select a leader</option>
              <?php
              // Get potential leaders (ministry leaders and admins)
              $leadersStmt = $conn->query("
                SELECT id, first_name, last_name, email 
                FROM users 
                WHERE role IN ('ministry_leader', 'administrator', 'super_admin') 
                AND status = 'active'
                ORDER BY first_name ASC
              ");
              $leaders = $leadersStmt->fetchAll();
              
              foreach ($leaders as $leader):
              ?>
                <option value="<?= $leader['id'] ?>">
                  <?= htmlspecialchars($leader['first_name'] . ' ' . $leader['last_name']) ?> 
                  (<?= htmlspecialchars($leader['email']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
          <button type="button" class="btn btn-outline" onclick="closeChapterModal()" style="flex: 1;">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary" style="flex: 2;">
            <i class="fas fa-save"></i> Save Chapter
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Chapter Modal -->
<div class="admin-modal" id="viewChapterModal">
  <div class="admin-modal-content">
    <div class="admin-modal-header">
      <h2>Chapter Details</h2>
      <button class="admin-modal-close" onclick="closeViewChapterModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="admin-modal-body" id="viewChapterBody">
      <!-- Content loaded dynamically -->
    </div>
  </div>
</div>

<script>
function toggleAdminSidebar() {
  document.getElementById('adminSidebar').classList.toggle('mobile-visible');
}

// Filters
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
});

document.getElementById('statusFilter').addEventListener('change', applyFilters);

function applyFilters() {
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;
  
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (status) params.set('status', status);
  
  window.location.href = '<?= SITE_URL ?>/admin/chapters' + (params.toString() ? '?' + params.toString() : '');
}

function resetFilters() {
  window.location.href = '<?= SITE_URL ?>/admin/chapters';
}

function goToPage(page) {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  window.location.href = '<?= SITE_URL ?>/admin/chapters?' + params.toString();
}

// Create Chapter Modal
function openCreateChapterModal() {
  document.getElementById('modalTitle').textContent = 'Create Chapter';
  document.getElementById('chapterForm').reset();
  document.getElementById('chapter_id').value = '';
  document.getElementById('chapterModal').classList.add('active');
}

function closeChapterModal() {
  document.getElementById('chapterModal').classList.remove('active');
}

// View Chapter
async function viewChapter(chapterId) {
  const modal = document.getElementById('viewChapterModal');
  const body = document.getElementById('viewChapterBody');
  
  body.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #6B46C1;"></i></div>';
  modal.classList.add('active');
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/chapters.php?action=get_chapter&id=' + chapterId);
    const result = await response.json();
    
    if (result.success) {
      body.innerHTML = renderChapterDetails(result.chapter);
    } else {
      body.innerHTML = '<p style="text-align: center; color: var(--gray-600);">Failed to load chapter details</p>';
    }
  } catch (error) {
    console.error('Error:', error);
    body.innerHTML = '<p style="text-align: center; color: var(--primary-coral);">An error occurred</p>';
  }
}

function closeViewChapterModal() {
  document.getElementById('viewChapterModal').classList.remove('active');
}

function renderChapterDetails(chapter) {
  return `
    <div style="padding: 1rem;">
      <div style="margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem; font-size: 1.75rem; color: var(--dark-bg);">${chapter.name}</h3>
        <span class="status-badge ${chapter.status}">${chapter.status}</span>
      </div>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
        <div style="background: var(--gray-100); padding: 1.5rem; border-radius: var(--radius-lg);">
          <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem; font-weight: 600;">LOCATION</div>
          <div style="font-size: 1.125rem; color: var(--dark-bg);"><i class="fas fa-map-marker-alt" style="color: #6B46C1; margin-right: 0.5rem;"></i>${chapter.location}</div>
        </div>
        
        <div style="background: var(--gray-100); padding: 1.5rem; border-radius: var(--radius-lg);">
          <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem; font-weight: 600;">MEMBERS</div>
          <div style="font-size: 1.125rem; color: var(--dark-bg);"><i class="fas fa-users" style="color: #2D9CDB; margin-right: 0.5rem;"></i>${chapter.member_count} members</div>
        </div>
        
        ${chapter.leader_name ? `
        <div style="background: var(--gray-100); padding: 1.5rem; border-radius: var(--radius-lg);">
          <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem; font-weight: 600;">CHAPTER LEADER</div>
          <div style="font-size: 1.125rem; color: var(--dark-bg);"><i class="fas fa-user-tie" style="color: #D4AF37; margin-right: 0.5rem;"></i>${chapter.leader_name}</div>
        </div>
        ` : ''}
        
        ${chapter.meeting_schedule ? `
        <div style="background: var(--gray-100); padding: 1.5rem; border-radius: var(--radius-lg);">
          <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem; font-weight: 600;">MEETING SCHEDULE</div>
          <div style="font-size: 1.125rem; color: var(--dark-bg);"><i class="fas fa-calendar" style="color: #51CF66; margin-right: 0.5rem;"></i>${chapter.meeting_schedule}</div>
        </div>
        ` : ''}
        
        ${chapter.contact_email ? `
        <div style="background: var(--gray-100); padding: 1.5rem; border-radius: var(--radius-lg);">
          <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem; font-weight: 600;">EMAIL</div>
          <div style="font-size: 1.125rem; color: var(--dark-bg);"><i class="fas fa-envelope" style="color: #EB5757; margin-right: 0.5rem;"></i><a href="mailto:${chapter.contact_email}" style="color: var(--primary-purple);">${chapter.contact_email}</a></div>
        </div>
        ` : ''}
        
        ${chapter.contact_phone ? `
        <div style="background: var(--gray-100); padding: 1.5rem; border-radius: var(--radius-lg);">
          <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.5rem; font-weight: 600;">PHONE</div>
          <div style="font-size: 1.125rem; color: var(--dark-bg);"><i class="fas fa-phone" style="color: #2D9CDB; margin-right: 0.5rem;"></i>${chapter.contact_phone}</div>
        </div>
        ` : ''}
      </div>
      
      ${chapter.description ? `
      <div style="margin-top: 2rem; padding: 1.5rem; background: linear-gradient(135deg, rgba(107, 70, 193, 0.05) 0%, rgba(45, 156, 219, 0.05) 100%); border-radius: var(--radius-lg); border-left: 4px solid #6B46C1;">
        <div style="font-size: 0.875rem; color: var(--gray-600); margin-bottom: 0.75rem; font-weight: 600;">DESCRIPTION</div>
        <p style="margin: 0; line-height: 1.6; color: var(--gray-700);">${chapter.description}</p>
      </div>
      ` : ''}
      
      <div style="display: flex; gap: 1rem; margin-top: 2rem;">
        <button class="btn btn-secondary" onclick="closeViewChapterModal(); editChapter(${chapter.id});" style="flex: 1;">
          <i class="fas fa-edit"></i> Edit Chapter
        </button>
        <button class="btn btn-outline" onclick="closeViewChapterModal()" style="flex: 1;">
          Close
        </button>
      </div>
    </div>
  `;
}

// Edit Chapter
async function editChapter(chapterId) {
  try {
    const response = await fetch('<?= SITE_URL ?>/api/chapters.php?action=get_chapter&id=' + chapterId);
    const result = await response.json();
    
    if (result.success) {
      const chapter = result.chapter;
      
      document.getElementById('modalTitle').textContent = 'Edit Chapter';
      document.getElementById('chapter_id').value = chapter.id;
      document.getElementById('name').value = chapter.name;
      document.getElementById('location').value = chapter.location;
      document.getElementById('latitude').value = chapter.latitude || '';
      document.getElementById('longitude').value = chapter.longitude || '';
      document.getElementById('contact_email').value = chapter.contact_email || '';
      document.getElementById('contact_phone').value = chapter.contact_phone || '';
      document.getElementById('meeting_schedule').value = chapter.meeting_schedule || '';
      document.getElementById('description').value = chapter.description || '';
      document.getElementById('leader_id').value = chapter.leader_id || '';
      document.getElementById('status').value = chapter.status;
      
      document.getElementById('chapterModal').classList.add('active');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Failed to load chapter data');
  }
}

// Form submission
document.getElementById('chapterForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
  
  const formData = new FormData(this);
  const chapterId = document.getElementById('chapter_id').value;
  const action = chapterId ? 'update_chapter' : 'create_chapter';
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/chapters.php?action=' + action, {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(result.message);
      window.location.reload();
    } else {
      alert(result.message || 'An error occurred');
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

// Delete Chapter
async function deleteChapter(chapterId, chapterName) {
  if (!confirm(`Are you sure you want to delete "${chapterName}"? This action cannot be undone.`)) {
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('chapter_id', chapterId);
    
    const response = await fetch('<?= SITE_URL ?>/api/chapters.php?action=delete_chapter', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Chapter deleted successfully');
      window.location.reload();
    } else {
      alert(result.message || 'Failed to delete chapter');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

// Close modal on overlay click
document.querySelectorAll('.admin-modal').forEach(modal => {
  modal.addEventListener('click', function(e) {
    if (e.target === this) {
      this.classList.remove('active');
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>