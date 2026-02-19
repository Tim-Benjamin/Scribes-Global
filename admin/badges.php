<?php
$pageTitle = 'Badges Management - Admin - Scribes Global';
$pageDescription = 'Manage user badges';
$pageCSS = 'admin';
$noSplash = true;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/badge-svg.php';

if (!isAdmin()) {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: ' . SITE_URL . '/pages/dashboard');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get badge statistics - FIXED
$badgeStatsQuery = "
    SELECT 
        badge_type,
        COUNT(*) as count
    FROM user_badges
    GROUP BY badge_type
";
$badgeStatsStmt = $conn->query($badgeStatsQuery);
$badgeStatsRaw = $badgeStatsStmt->fetchAll(PDO::FETCH_ASSOC);

// Convert to key-pair array manually
$badgeStats = [];
foreach ($badgeStatsRaw as $row) {
    $badgeStats[$row['badge_type']] = $row['count'];
}

// Get users with badges
$search = $_GET['search'] ?? '';
$badgeFilter = $_GET['badge'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$query = "
    SELECT 
        u.*,
        GROUP_CONCAT(DISTINCT ub.badge_type ORDER BY ub.badge_type SEPARATOR ',') as badges,
        COUNT(DISTINCT ub.id) as badge_count
    FROM users u
    LEFT JOIN user_badges ub ON u.id = ub.user_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$query .= " GROUP BY u.id";

if (!empty($badgeFilter)) {
    $query .= " HAVING FIND_IN_SET(?, badges)";
    $params[] = $badgeFilter;
}

$query .= " ORDER BY badge_count DESC, u.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(DISTINCT u.id) as total FROM users u LEFT JOIN user_badges ub ON u.id = ub.user_id WHERE 1=1";
$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
}

$countStmt = $conn->prepare($countQuery);
$countStmt->execute($countParams);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Badge definitions with more colors
$badgeDefinitions = [
    'verified' => [
        'name' => 'Verified Artist',
        'color' => 'gold',
        'description' => 'Official verified artist or creator',
        'icon' => '⭐',
        'auto' => false
    ],
    'founder' => [
        'name' => 'Founder',
        'color' => 'red',
        'description' => 'Founding member or leadership',
        'icon' => '👑',
        'auto' => false
    ],
    'ministry_leader' => [
        'name' => 'Ministry Leader',
        'color' => 'purple',
        'description' => 'Leading a ministry team',
        'icon' => '💼',
        'auto' => false
    ],
    'featured' => [
        'name' => 'Featured Creator',
        'color' => 'pink',
        'description' => 'Featured content creator',
        'icon' => '✨',
        'auto' => false
    ],
    'certified' => [
        'name' => 'Certified Graduate',
        'color' => 'green',
        'description' => 'Completed Scribes Academy',
        'icon' => '🎓',
        'auto' => true
    ],
    'active' => [
        'name' => 'Active Member',
        'color' => 'blue',
        'description' => '100% profile completion',
        'icon' => '🏆',
        'auto' => true
    ],
    'elite' => [
        'name' => 'Elite Member',
        'color' => 'teal',
        'description' => 'Top contributor',
        'icon' => '💎',
        'auto' => false
    ],
    'supporter' => [
        'name' => 'Supporter',
        'color' => 'orange',
        'description' => 'Financial supporter',
        'icon' => '❤️',
        'auto' => false
    ],
    'veteran' => [
        'name' => 'Veteran',
        'color' => 'silver',
        'description' => '5+ years member',
        'icon' => '🛡️',
        'auto' => true
    ],
    'premium' => [
        'name' => 'Premium',
        'color' => 'black',
        'description' => 'Premium member',
        'icon' => '⚡',
        'auto' => false
    ]
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.badge-card {
  background: white;
  padding: 2rem;
  border-radius: var(--radius-xl);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  transition: all var(--transition-base);
  position: relative;
  overflow: hidden;
  border-top: 4px solid var(--badge-color);
}

.badge-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.badge-card.gold { --badge-color: #FFD700; }
.badge-card.red { --badge-color: #FF6B6B; }
.badge-card.purple { --badge-color: #9B7EDE; }
.badge-card.pink { --badge-color: #FF69B4; }
.badge-card.green { --badge-color: #51CF66; }
.badge-card.blue { --badge-color: #4A9EFF; }
.badge-card.teal { --badge-color: #56CCF2; }
.badge-card.orange { --badge-color: #FF922B; }
.badge-card.silver { --badge-color: #C0C0C0; }
.badge-card.black { --badge-color: #2C2C2C; }

.badge-icon-large {
  font-size: 3rem;
  margin-bottom: 1rem;
  display: block;
}

.badge-stats-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px solid var(--gray-200);
}

.badge-visual {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  position: relative;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
  transition: all var(--transition-base);
}

.badge-visual:hover {
  transform: scale(1.1) rotate(5deg);
}

.user-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.award-badge-btn {
  padding: 0.5rem 1rem;
  border: 2px solid var(--primary-purple);
  background: white;
  color: var(--primary-purple);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-base);
  font-weight: 600;
  font-size: 0.875rem;
}

.award-badge-btn:hover {
  background: var(--primary-purple);
  color: white;
  transform: translateY(-2px);
}

.badge-selection {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.badge-option {
  padding: 1rem;
  border: 2px solid var(--gray-300);
  border-radius: var(--radius-lg);
  cursor: pointer;
  transition: all var(--transition-base);
  text-align: center;
}

.badge-option:hover {
  border-color: var(--primary-purple);
  background: rgba(107, 70, 193, 0.05);
  transform: translateY(-2px);
}

.badge-option.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.badge-option.has-badge {
  background: rgba(81, 207, 102, 0.1);
  border-color: #51CF66;
}
</style>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  
  <!-- Main Content -->
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Badges Management</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Award and manage user badges</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
      </div>
    </div>
    
    <!-- Badge Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
      <?php foreach ($badgeDefinitions as $type => $badge): ?>
        <div class="badge-card <?= $badge['color'] ?>" data-aos="fade-up">
          <div class="badge-icon-large"><?= $badge['icon'] ?></div>
          <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; color: var(--dark-bg);">
            <?= $badge['name'] ?>
          </h3>
          <p style="margin: 0 0 1rem 0; font-size: 0.875rem; color: var(--gray-600);">
            <?= $badge['description'] ?>
          </p>
          
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <?php if ($badge['auto']): ?>
              <span style="background: rgba(45, 156, 219, 0.15); color: #1971C2; padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700;">
                <i class="fas fa-magic"></i> AUTO EARNED
              </span>
            <?php else: ?>
              <span style="background: rgba(212, 175, 55, 0.15); color: #CC8400; padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700;">
                <i class="fas fa-user-shield"></i> ADMIN AWARDED
              </span>
            <?php endif; ?>
          </div>
          
          <div class="badge-stats-row">
            <div>
              <div style="font-size: 2rem; font-weight: 900; color: var(--dark-bg); font-family: var(--font-heading);">
                <?= isset($badgeStats[$type]) ? $badgeStats[$type] : 0 ?>
              </div>
              <div style="font-size: 0.75rem; color: var(--gray-600); text-transform: uppercase; font-weight: 600;">
                Users Awarded
              </div>
            </div>
            <div class="badge-visual">
              <?= renderBadgeSVG($badge['color'], 40) ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <!-- Filters -->
    <div class="filters-bar">
      <input 
        type="text" 
        class="search-input" 
        placeholder="Search users..." 
        value="<?= htmlspecialchars($search) ?>"
        id="searchInput"
      >
      
      <select class="filter-select" id="badgeFilter">
        <option value="">All Badges</option>
        <?php foreach ($badgeDefinitions as $type => $badge): ?>
          <option value="<?= $type ?>" <?= $badgeFilter === $type ? 'selected' : '' ?>>
            <?= $badge['icon'] ?> <?= $badge['name'] ?>
          </option>
        <?php endforeach; ?>
      </select>
      
      <button class="btn btn-outline" onclick="resetFilters()">
        <i class="fas fa-redo"></i> Reset
      </button>
    </div>
    
    <!-- Users with Badges Table -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3 class="admin-card-title">
          <i class="fas fa-award"></i>
          Users & Badges (<?= number_format($total) ?>)
        </h3>
      </div>
      <div class="admin-card-body">
        <?php if (count($users) > 0): ?>
          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Email</th>
                  <th>Current Badges</th>
                  <th>Badge Count</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $user): ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <?php if ($user['profile_photo']): ?>
                          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="User" class="user-avatar">
                        <?php else: ?>
                          <div class="user-avatar" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                          </div>
                        <?php endif; ?>
                        <div class="user-info">
                          <h4><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?></h4>
                          <p><?= ucfirst(str_replace('_', ' ', $user['primary_role'])) ?></p>
                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                      <div class="user-badges">
                        <?php 
                        $userBadges = !empty($user['badges']) ? explode(',', $user['badges']) : [];
                        if (count($userBadges) > 0):
                          foreach ($userBadges as $badgeType):
                            if (isset($badgeDefinitions[$badgeType])):
                        ?>
                          <span class="badge-visual" title="<?= $badgeDefinitions[$badgeType]['name'] ?>">
                            <?= renderBadgeSVG($badgeDefinitions[$badgeType]['color'], 24) ?>
                          </span>
                        <?php 
                            endif;
                          endforeach;
                        else:
                        ?>
                          <span style="color: var(--gray-500); font-size: 0.875rem; font-style: italic;">No badges</span>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <span style="font-weight: 700; font-size: 1.125rem; color: var(--primary-purple);">
                        <?= $user['badge_count'] ?>
                      </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                      <button class="award-badge-btn" onclick="manageBadges(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>', '<?= $user['badges'] ?? '' ?>')">
                        <i class="fas fa-award"></i> Manage Badges
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div class="pagination">
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
            <div class="empty-state-icon"><i class="fas fa-award"></i></div>
            <div class="empty-state-title">No Users Found</div>
            <div class="empty-state-text">Try adjusting your filters</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- Manage Badges Modal -->
<div class="admin-modal" id="badgesModal">
  <div class="admin-modal-content">
    <div class="admin-modal-header">
      <h2>Manage Badges - <span id="modalUserName"></span></h2>
      <button class="admin-modal-close" onclick="closeBadgesModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="admin-modal-body">
      <input type="hidden" id="modalUserId">
      
      <p style="color: var(--gray-600); margin-bottom: 1.5rem;">
        Click badges to award or remove them. Auto-earned badges cannot be manually removed.
      </p>
      
      <div class="badge-selection" id="badgeSelection">
        <!-- Badges will be populated dynamically -->
      </div>
    </div>
  </div>
</div>

<script>
const badgeDefinitions = <?= json_encode($badgeDefinitions) ?>;

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

document.getElementById('badgeFilter').addEventListener('change', applyFilters);

function applyFilters() {
  const search = document.getElementById('searchInput').value;
  const badge = document.getElementById('badgeFilter').value;
  
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (badge) params.set('badge', badge);
  
  window.location.href = '<?= SITE_URL ?>/admin/badges' + (params.toString() ? '?' + params.toString() : '');
}

function resetFilters() {
  window.location.href = '<?= SITE_URL ?>/admin/badges';
}

function goToPage(page) {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  window.location.href = '<?= SITE_URL ?>/admin/badges?' + params.toString();
}

function renderBadgeSVG(color, size = 48) {
  const colors = {
    gold: { outer: '#FFD700', inner: '#FFA500' },
    red: { outer: '#FF6B6B', inner: '#C92A2A' },
    purple: { outer: '#9B7EDE', inner: '#6B46C1' },
    pink: { outer: '#FF69B4', inner: '#FF1493' },
    green: { outer: '#51CF66', inner: '#2F9E44' },
    blue: { outer: '#4A9EFF', inner: '#1971C2' },
    teal: { outer: '#56CCF2', inner: '#2D9CDB' },
    orange: { outer: '#FF922B', inner: '#F76707' },
    silver: { outer: '#C0C0C0', inner: '#808080' },
    black: { outer: '#2C2C2C', inner: '#1A1A1A' }
  };
  
  const colorScheme = colors[color] || colors.silver;
  
  return `
    <svg width="${size}" height="${size}" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
      <g>
        <path d="M256 0L289.2 83.8L378.1 51.9L371.3 144.7L470.1 141.9L427.4 225.7L512 256L427.4 286.3L470.1 370.1L371.3 367.3L378.1 460.1L289.2 428.2L256 512L222.8 428.2L133.9 460.1L140.7 367.3L41.9 370.1L84.6 286.3L0 256L84.6 225.7L41.9 141.9L140.7 144.7L133.9 51.9L222.8 83.8L256 0Z" fill="${colorScheme.outer}"/>
        <circle cx="256" cy="256" r="160" fill="${colorScheme.inner}"/>
        <path d="M369 190L233 326L143 236" stroke="white" stroke-width="40" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
      </g>
    </svg>
  `;
}

function manageBadges(userId, userName, currentBadges) {
  const modal = document.getElementById('badgesModal');
  const userNameEl = document.getElementById('modalUserName');
  const userIdEl = document.getElementById('modalUserId');
  const badgeSelection = document.getElementById('badgeSelection');
  
  userNameEl.textContent = userName;
  userIdEl.value = userId;
  
  const userBadgesArray = currentBadges ? currentBadges.split(',') : [];
  
  // Build badge options
  let html = '';
  for (const [type, badge] of Object.entries(badgeDefinitions)) {
    const hasBadge = userBadgesArray.includes(type);
    const isDisabled = badge.auto && !hasBadge;
    
    html += `
      <div class="badge-option ${hasBadge ? 'has-badge' : ''} ${isDisabled ? 'disabled' : ''}" 
           onclick="${!isDisabled ? `toggleBadge('${type}', ${userId}, ${hasBadge})` : ''}"
           title="${badge.description}${badge.auto ? ' (Auto-earned)' : ''}">
        <div style="width: 60px; height: 60px; margin: 0 auto 0.75rem;">
          ${renderBadgeSVG(badge.color, 60)}
        </div>
        <strong style="display: block; margin-bottom: 0.25rem; color: var(--dark-bg); font-size: 0.9rem;">
          ${badge.name}
        </strong>
        <small style="color: var(--gray-600); font-size: 0.75rem;">
          ${hasBadge ? '✓ Awarded' : 'Click to award'}
        </small>
      </div>
    `;
  }
  
  badgeSelection.innerHTML = html;
  modal.classList.add('active');
}

function closeBadgesModal() {
  document.getElementById('badgesModal').classList.remove('active');
}

async function toggleBadge(badgeType, userId, currentlyHas) {
  const action = currentlyHas ? 'remove_badge' : 'award_badge';
  const actionText = currentlyHas ? 'remove' : 'award';
  
  if (!confirm(`Are you sure you want to ${actionText} this badge?`)) {
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('badge_type', badgeType);
    
    const response = await fetch('<?= SITE_URL ?>/api/admin.php?action=' + action, {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert(result.message);
      window.location.reload();
    } else {
      alert(result.message || 'Operation failed');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred');
  }
}

// Close modal on overlay click
document.getElementById('badgesModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeBadgesModal();
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>