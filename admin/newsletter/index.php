<?php
$pageTitle = 'Newsletter Subscribers - Admin - Scribes Global';
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

// Get subscribers
$status = $_GET['status'] ?? 'active';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// ✅ FIX: Use raw SQL with inline values instead of parameterized for LIMIT/OFFSET
$subscribersStmt = $conn->prepare("
    SELECT * FROM newsletter_subscribers 
    WHERE status = ?
    ORDER BY created_at DESC
    LIMIT " . $limit . " OFFSET " . $offset . "
");
$subscribersStmt->execute([$status]);
$subscribers = $subscribersStmt->fetchAll();

// Get total count for pagination
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM newsletter_subscribers WHERE status = ?");
$countStmt->execute([$status]);
$countResult = $countStmt->fetch();
$totalSubscribers = $countResult['total'];
$totalPages = ceil($totalSubscribers / $limit);

// Get stats
$statsStmt = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active') as active_count,
        (SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'pending') as pending_count,
        (SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'unsubscribed') as unsubscribed_count
");
$stats = $statsStmt->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
  .stat-box {
    background: white;
    border-radius: var(--radius-2xl);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid #6B46C1;
  }
  
  .stat-number {
    font-size: 2rem;
    font-weight: 900;
    color: #6B46C1;
    margin-bottom: 0.25rem;
  }
  
  .stat-label {
    font-size: 0.875rem;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .subscribers-table {
    background: white;
    border-radius: var(--radius-2xl);
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  }
  
  .subscribers-table table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .subscribers-table th {
    background: #f9f9f9;
    padding: 1rem;
    text-align: left;
    font-weight: 700;
    color: var(--dark-bg);
    border-bottom: 2px solid var(--gray-200);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .subscribers-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
  }
  
  .subscribers-table tr:hover {
    background: #f9f9f9;
  }
  
  .status-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .status-active {
    background: #D4EDDA;
    color: #155724;
  }
  
  .status-pending {
    background: #FFF3CD;
    color: #856404;
  }
  
  .status-unsubscribed {
    background: #F8D7DA;
    color: #721C24;
  }
  
  .btn-small {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-remove {
    background: #EB5757;
    color: white;
  }
  
  .btn-remove:hover {
    background: #C92A2A;
  }

  .pagination {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 2rem;
    flex-wrap: wrap;
  }

  .pagination a,
  .pagination span {
    padding: 0.5rem 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
    transition: all 0.2s;
  }

  .pagination a:hover {
    background: #6B46C1;
    color: white;
    border-color: #6B46C1;
  }

  .pagination .active {
    background: #6B46C1;
    color: white;
    border-color: #6B46C1;
  }

  .pagination .disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
</style>

<div class="admin-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  
  <main class="admin-main">
    <div class="admin-top-bar">
      <div>
        <h1 class="admin-page-title">Newsletter Subscribers</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Manage your newsletter subscription list</p>
      </div>
      <div class="admin-actions">
        <button class="mobile-admin-toggle" onclick="toggleAdminSidebar()">
          <i class="fas fa-bars"></i>
        </button>
      </div>
    </div>
    
    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
      <div class="stat-box">
        <div class="stat-number"><?= $stats['active_count'] ?></div>
        <div class="stat-label">Active Subscribers</div>
      </div>
      <div class="stat-box" style="border-left-color: #FFA500;">
        <div class="stat-number" style="color: #FFA500;"><?= $stats['pending_count'] ?></div>
        <div class="stat-label">Pending Confirmation</div>
      </div>
      <div class="stat-box" style="border-left-color: #EB5757;">
        <div class="stat-number" style="color: #EB5757;"><?= $stats['unsubscribed_count'] ?></div>
        <div class="stat-label">Unsubscribed</div>
      </div>
    </div>
    
    <!-- Filter Tabs -->
    <div style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--gray-200); flex-wrap: wrap;">
      <a href="?status=active" class="btn <?= $status === 'active' ? 'btn-primary' : 'btn-outline' ?>" style="border: none; padding: 1rem;">
        Active (<?= $stats['active_count'] ?>)
      </a>
      <a href="?status=pending" class="btn <?= $status === 'pending' ? 'btn-primary' : 'btn-outline' ?>" style="border: none; padding: 1rem;">
        Pending (<?= $stats['pending_count'] ?>)
      </a>
      <a href="?status=unsubscribed" class="btn <?= $status === 'unsubscribed' ? 'btn-primary' : 'btn-outline' ?>" style="border: none; padding: 1rem;">
        Unsubscribed (<?= $stats['unsubscribed_count'] ?>)
      </a>
    </div>
    
    <!-- Subscribers Table -->
    <div class="subscribers-table">
      <table>
        <thead>
          <tr>
            <th>Email</th>
            <th>Name</th>
            <th>Status</th>
            <th>Subscribed</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($subscribers) > 0): ?>
            <?php foreach ($subscribers as $subscriber): ?>
              <tr>
                <td><?= htmlspecialchars($subscriber['email']) ?></td>
                <td><?= htmlspecialchars($subscriber['name'] ?? 'N/A') ?></td>
                <td>
                  <span class="status-badge status-<?= $subscriber['status'] ?>">
                    <?= ucfirst($subscriber['status']) ?>
                  </span>
                </td>
                <td><?= date('M d, Y', strtotime($subscriber['created_at'])) ?></td>
                <td>
                  <button class="btn-small btn-remove" onclick="removeSubscriber(<?= $subscriber['id'] ?>, '<?= htmlspecialchars($subscriber['email']) ?>')">
                    Remove
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center; padding: 2rem; color: var(--gray-600);">
                <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                No subscribers in this category
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?status=<?= $status ?>&page=1">First</a>
        <a href="?status=<?= $status ?>&page=<?= $page - 1 ?>">Previous</a>
      <?php else: ?>
        <span class="disabled">First</span>
        <span class="disabled">Previous</span>
      <?php endif; ?>

      <?php 
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        
        if ($start > 1): ?>
          <span>...</span>
        <?php endif;

        for ($i = $start; $i <= $end; $i++): ?>
          <a href="?status=<?= $status ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor;

        if ($end < $totalPages): ?>
          <span>...</span>
        <?php endif;

        if ($page < $totalPages): ?>
          <a href="?status=<?= $status ?>&page=<?= $page + 1 ?>">Next</a>
          <a href="?status=<?= $status ?>&page=<?= $totalPages ?>">Last</a>
        <?php else: ?>
          <span class="disabled">Next</span>
          <span class="disabled">Last</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<script>
function toggleAdminSidebar() {
  document.getElementById('adminSidebar').classList.toggle('mobile-visible');
}

async function removeSubscriber(id, email) {
  if (!confirm('Remove ' + email + ' from newsletter?')) return;
  
  try {
    const response = await fetch('<?= SITE_URL ?>/api/newsletter.php?action=remove_subscriber', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + id
    });
    
    const result = await response.json();
    if (result.success) {
      alert('Subscriber removed');
      location.reload();
    } else {
      alert('Error: ' + result.message);
    }
  } catch (error) {
    alert('An error occurred');
  }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>