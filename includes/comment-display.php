<?php
function renderComment($comment) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/badge-svg.php';
    
    $db = new Database();
    $conn = $db->connect();
    
    // Get commenter badges
    $badgesStmt = $conn->prepare("SELECT badge_type FROM user_badges WHERE user_id = ?");
    $badgesStmt->execute([$comment['user_id']]);
    $badges = $badgesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $badgeColors = [
        'verified' => 'gold',
        'founder' => 'red',
        'ministry_leader' => 'purple',
        'featured' => 'pink',
        'certified' => 'green',
        'active' => 'blue',
        'elite' => 'teal',
        'supporter' => 'orange',
        'veteran' => 'silver',
        'premium' => 'black'
    ];
    ?>
    
    <div class="comment" id="comment-<?= $comment['id'] ?>">
      <div class="comment-avatar">
        <?php if ($comment['profile_photo']): ?>
          <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($comment['profile_photo']) ?>" alt="Avatar">
        <?php else: ?>
          <div class="comment-avatar-placeholder">
            <?= strtoupper(substr($comment['first_name'], 0, 1)) ?>
          </div>
        <?php endif; ?>
      </div>
      
      <div class="comment-content">
        <div class="comment-header">
          <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <span class="comment-author">
              <?= htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) ?>
            </span>
            
            <!-- Badges -->
            <?php if (!empty($badges)): ?>
              <div style="display: flex; gap: 0.25rem; align-items: center;">
                <?php foreach (array_slice($badges, 0, 3) as $badge): ?>
                  <?php if (isset($badgeColors[$badge])): ?>
                    <span style="display: inline-flex; width: 16px; height: 16px;" title="<?= ucfirst(str_replace('_', ' ', $badge)) ?>">
                      <?= renderBadgeSVG($badgeColors[$badge], 16) ?>
                    </span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            
            <!-- Custom Tag -->
            <?php if (!empty($comment['custom_tag'])): ?>
              <span style="background: rgba(107, 70, 193, 0.1); color: var(--primary-purple); padding: 0.15rem 0.5rem; border-radius: 10px; font-size: 0.75rem; font-weight: 600;">
                <?= htmlspecialchars($comment['custom_tag']) ?>
              </span>
            <?php endif; ?>
          </div>
          
          <span class="comment-time">
            <?= timeAgo($comment['created_at']) ?>
          </span>
        </div>
        
        <div class="comment-text">
          <?= nl2br(htmlspecialchars($comment['content'])) ?>
        </div>
        
        <div class="comment-actions">
          <button class="comment-action-btn" onclick="likeComment(<?= $comment['id'] ?>)">
            <i class="far fa-heart"></i> Like
          </button>
          <button class="comment-action-btn" onclick="replyToComment(<?= $comment['id'] ?>)">
            <i class="far fa-comment"></i> Reply
          </button>
        </div>
      </div>
    </div>
    
    <?php
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}
?>