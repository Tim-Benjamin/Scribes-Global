<?php
$pageTitle = 'Dashboard - Scribes Global';
$pageDescription = 'Your Scribes Global Dashboard';
$pageCSS = 'dashboard';
$noSplash = true;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
  $_SESSION['error_message'] = 'Please login to access your dashboard.';
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

// Get user stats
$statsStmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM blog_posts WHERE author_id = ? AND status = 'published') as posts_count,
        (SELECT COUNT(*) FROM blog_comments WHERE user_id = ?) as comments_count,
        (SELECT COUNT(*) FROM event_registrations WHERE user_id = ?) as events_count,
        (SELECT COUNT(*) FROM prayer_interactions WHERE user_id = ?) as prayers_count
");
$statsStmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$stats = $statsStmt->fetch();

// Calculate profile completion
$completion = 0;
$checklist = [
  ['label' => 'Profile Photo', 'completed' => !empty($user['profile_photo']), 'points' => 15, 'icon' => 'fa-camera'],
  ['label' => 'Bio', 'completed' => !empty($user['bio']), 'points' => 15, 'icon' => 'fa-align-left'],
  ['label' => 'Custom Tag', 'completed' => !empty($user['custom_tag']), 'points' => 15, 'icon' => 'fa-tag'],
  ['label' => 'Primary Role', 'completed' => !empty($user['primary_role']) && $user['primary_role'] !== 'member', 'points' => 15, 'icon' => 'fa-user-tag'],
  ['label' => 'Chapter', 'completed' => !empty($user['chapter_id']), 'points' => 10, 'icon' => 'fa-map-marker-alt'],
  ['label' => 'Ministry Team', 'completed' => !empty($user['ministry_team']), 'points' => 10, 'icon' => 'fa-users'],
  ['label' => 'Phone Number', 'completed' => !empty($user['phone']), 'points' => 10, 'icon' => 'fa-phone'],
  ['label' => 'Email Verified', 'completed' => $user['email_verified'], 'points' => 10, 'icon' => 'fa-check-circle'],
];

foreach ($checklist as $item) {
  if ($item['completed']) {
    $completion += $item['points'];
  }
}

// Update completion in database
$updateCompletionStmt = $conn->prepare("UPDATE users SET profile_completion = ? WHERE id = ?");
$updateCompletionStmt->execute([$completion, $user['id']]);

// Get recent activity
$activityStmt = $conn->prepare("
    SELECT * FROM activity_log 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$activityStmt->execute([$user['id']]);
$activities = $activityStmt->fetchAll();

// Get upcoming events user registered for
$eventsStmt = $conn->prepare("
    SELECT e.* FROM events e
    JOIN event_registrations er ON e.id = er.event_id
    WHERE er.user_id = ? AND e.start_date > NOW() AND e.status = 'upcoming'
    ORDER BY e.start_date ASC
    LIMIT 5
");
$eventsStmt->execute([$user['id']]);
$upcomingEvents = $eventsStmt->fetchAll();

// Get user's ministries
$ministriesStmt = $conn->prepare("
    SELECT m.* FROM ministries m
    JOIN user_ministries um ON m.id = um.ministry_id
    WHERE um.user_id = ?
");
$ministriesStmt->execute([$user['id']]);
$userMinistries = $ministriesStmt->fetchAll();

// Get chapter info
$chapterInfo = null;
if ($user['chapter_id']) {
  $chapterStmt = $conn->prepare("SELECT * FROM chapters WHERE id = ?");
  $chapterStmt->execute([$user['chapter_id']]);
  $chapterInfo = $chapterStmt->fetch();
}

require_once __DIR__ . '/../../includes/header.php';
?>

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
        <a href="<?= SITE_URL ?>/pages/dashboard" class="sidebar-nav-item active">
          <i class="fas fa-th-large"></i>
          <span>Dashboard</span>
        </a>
        <a href="<?= SITE_URL ?>/pages/dashboard/profile" class="sidebar-nav-item">
          <i class="fas fa-user"></i>
          <span>My Profile</span>
          <?php if ($completion < 100): ?>
            <span class="sidebar-nav-badge"><?= $completion ?>%</span>
          <?php endif; ?>
        </a>
        <a href="<?= SITE_URL ?>/pages/dashboard/ministries" class="sidebar-nav-item">
          <i class="fas fa-users"></i>
          <span>My Ministries</span>
          <?php if (count($userMinistries) > 0): ?>
            <span class="sidebar-nav-badge"><?= count($userMinistries) ?></span>
          <?php endif; ?>
        </a>
      </div>

      <div class="sidebar-nav-section">
        <div class="sidebar-nav-title">Content</div>
        <a href="<?= SITE_URL ?>/pages/dashboard/posts" class="sidebar-nav-item">
          <i class="fas fa-pen"></i>
          <span>My Posts</span>
          <?php if ($stats['posts_count'] > 0): ?>
            <span class="sidebar-nav-badge"><?= $stats['posts_count'] ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= SITE_URL ?>/pages/dashboard/media" class="sidebar-nav-item">
          <i class="fas fa-photo-video"></i>
          <span>My Media</span>
        </a>
        <a href="<?= SITE_URL ?>/pages/dashboard/events" class="sidebar-nav-item">
          <i class="fas fa-calendar"></i>
          <span>My Events</span>
          <?php if ($stats['events_count'] > 0): ?>
            <span class="sidebar-nav-badge"><?= $stats['events_count'] ?></span>
          <?php endif; ?>
        </a>
      </div>

      <div class="sidebar-nav-section">
        <div class="sidebar-nav-title">Community</div>
        <a href="<?= SITE_URL ?>/pages/dashboard/saved" class="sidebar-nav-item">
          <i class="fas fa-bookmark"></i>
          <span>Saved Items</span>
        </a>
        <a href="<?= SITE_URL ?>/pages/dashboard/prayer" class="sidebar-nav-item">
          <i class="fas fa-praying-hands"></i>
          <span>Prayer Requests</span>
        </a>
      </div>

      <div class="sidebar-nav-section">
        <div class="sidebar-nav-title">Account</div>
        <a href="<?= SITE_URL ?>/pages/dashboard/settings" class="sidebar-nav-item">
          <i class="fas fa-cog"></i>
          <span>Settings</span>
        </a>
        <?php if (isAdmin()): ?>
          <a href="<?= SITE_URL ?>/admin" class="sidebar-nav-item">
            <i class="fas fa-shield-alt"></i>
            <span>Admin Panel</span>
          </a>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>/auth/logout" class="sidebar-nav-item">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </a>
      </div>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="dashboard-main" id="dashboardMain">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Welcome back, <?= htmlspecialchars($user['first_name']) ?>! 👋</h1>
        <p style="color: var(--gray-600); margin-top: 0.5rem;">Here's what's happening with your account today.</p>
      </div>
      <div class="dashboard-actions">
        <button class="mobile-menu-toggle" onclick="toggleSidebar()">
          <i class="fas fa-bars"></i>
        </button>
        <a href="<?= SITE_URL ?>/pages/blog/create" class="btn btn-primary">
          <i class="fas fa-plus"></i> Create Post
        </a>
      </div>
    </div>

    <!-- Profile Completion Card -->
    <?php if ($completion < 100): ?>
      <div class="profile-completion-card" data-aos="fade-up">
        <div class="profile-completion-content">
          <div class="completion-header">
            <h3 class="completion-title">
              <i class="fas fa-chart-line"></i>
              Complete Your Profile
            </h3>
            <div class="completion-percentage"><?= $completion ?>%</div>
          </div>

          <div class="progress-bar-container">
            <div class="progress-bar" style="width: <?= $completion ?>%"></div>
          </div>

          <p style="margin-bottom: 1.5rem; font-size: 0.95rem; opacity: 0.95;">
            Complete your profile to unlock the <strong style="color: #D4AF37;">Active Member Badge</strong>
            and get featured in the community!
          </p>

          <div class="completion-checklist">
            <?php foreach ($checklist as $item): ?>
              <div class="checklist-item <?= $item['completed'] ? 'completed' : 'incomplete' ?>">
                <div class="checklist-icon">
                  <?php if ($item['completed']): ?>
                    <i class="fas fa-check" style="color: white; font-size: 0.75rem;"></i>
                  <?php else: ?>
                    <i class="fas <?= $item['icon'] ?>" style="color: rgba(255,255,255,0.5); font-size: 0.7rem;"></i>
                  <?php endif; ?>
                </div>
                <span><?= $item['label'] ?> (+<?= $item['points'] ?>%)</span>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="badge-reward">
            <div class="badge-icon">🏆</div>
            <div>
              <strong style="display: block; margin-bottom: 0.25rem;">Reward:</strong>
              <span style="font-size: 0.875rem; opacity: 0.9;">
                Complete 100% to earn the Active Member Badge and unlock all features
              </span>
            </div>
          </div>

          <a href="<?= SITE_URL ?>/pages/dashboard/profile" class="btn" style="margin-top: 1.5rem; background: white; color: #6B46C1; width: 100%; font-weight: 700;">
            <i class="fas fa-user-edit"></i> Complete Profile Now
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="profile-completion-card" data-aos="fade-up" style="background: linear-gradient(135deg, #51CF66 0%, #2F9E44 100%);">
        <div class="profile-completion-content" style="text-align: center;">
          <i class="fas fa-trophy" style="font-size: 4rem; margin-bottom: 1rem; display: block; animation: rotate 3s linear infinite;"></i>
          <h3 style="margin-bottom: 0.5rem; font-size: 1.75rem;">Profile 100% Complete! 🎉</h3>
          <p style="margin: 0; font-size: 1.125rem;">You've unlocked all features and earned the Active Member Badge!</p>
        </div>
      </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card purple" data-aos="fade-up">
        <div class="stat-card-header">
          <div class="stat-icon purple">
            <i class="fas fa-pen"></i>
          </div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 12%
          </div>
        </div>
        <div class="stat-label">Published Posts</div>
        <div class="stat-value"><?= $stats['posts_count'] ?></div>
      </div>

      <div class="stat-card gold" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-card-header">
          <div class="stat-icon gold">
            <i class="fas fa-calendar-check"></i>
          </div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 8%
          </div>
        </div>
        <div class="stat-label">Events Attended</div>
        <div class="stat-value"><?= $stats['events_count'] ?></div>
      </div>

      <div class="stat-card teal" data-aos="fade-up" data-aos-delay="200">
        <div class="stat-card-header">
          <div class="stat-icon teal">
            <i class="fas fa-comments"></i>
          </div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 24%
          </div>
        </div>
        <div class="stat-label">Comments</div>
        <div class="stat-value"><?= $stats['comments_count'] ?></div>
      </div>

      <div class="stat-card coral" data-aos="fade-up" data-aos-delay="300">
        <div class="stat-card-header">
          <div class="stat-icon coral">
            <i class="fas fa-praying-hands"></i>
          </div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i> 15%
          </div>
        </div>
        <div class="stat-label">Prayers</div>
        <div class="stat-value"><?= $stats['prayers_count'] ?></div>
      </div>
    </div>

    <!-- Content Grid -->
    <div class="dashboard-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 2rem;">
      <!-- Recent Activity -->
      <div class="dashboard-card" style="background: white; border-radius: var(--radius-2xl); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden;">
        <div class="dashboard-card-header" style="padding: 1.5rem; border-bottom: 1px solid var(--gray-200);">
          <h2 style="margin: 0; font-size: 1.5rem; color: var(--dark-bg);">Recent Activity</h2>
          <a href="<?= SITE_URL ?>/pages/dashboard/activity" style="color: var(--primary-purple); font-weight: 600; font-size: 0.875rem;">View All</a>
        </div>
        <div style="padding: 1.5rem;">
          <?php if (count($activities) > 0): ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
              <?php foreach ($activities as $activity): ?>
                <div style="display: flex; gap: 1rem; padding: 1rem; background: var(--gray-100); border-radius: var(--radius-lg); transition: all var(--transition-base);" onmouseover="this.style.background='var(--gray-200)'" onmouseout="this.style.background='var(--gray-100)'">
                  <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= getActivityColor($activity['action']) ?>; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i class="fas <?= getActivityIcon($activity['action']) ?>"></i>
                  </div>
                  <div style="flex: 1;">
                    <div style="color: var(--gray-700); margin-bottom: 0.25rem; font-weight: 500;">
                      <?= formatActivityText($activity) ?>
                    </div>
                    <div style="font-size: 0.875rem; color: var(--gray-500);">
                      <i class="far fa-clock"></i> <?= timeAgo($activity['created_at']) ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div style="text-align: center; padding: 3rem 1rem;">
              <div style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;">
                <i class="fas fa-history"></i>
              </div>
              <div style="font-size: 1.125rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">No Activity Yet</div>
              <div style="color: var(--gray-600);">Start engaging with the community to see your activity here.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Profile Card -->
      <div>
        <div class="profile-card" data-aos="fade-left">
          <div class="profile-avatar-container">
            <?php if (!empty($user['profile_photo'])): ?>
              <img src="<?= ASSETS_PATH ?>images/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" class="profile-avatar-large">
            <?php else: ?>
              <div class="profile-avatar-large" style="background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 900;">
                <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="profile-name">
            <?php
            $userBadges = getUserBadges($user['id']);
            echo renderUserNameWithBadges($user['first_name'], $user['last_name'], $userBadges, 24);
            ?>
          </div>

          <div class="profile-tags">
            <?php if (!empty($user['custom_tag']) && $user['show_role']): ?>
              <span class="custom-tag">
                <i class="fas fa-crown"></i>
                <?= htmlspecialchars($user['custom_tag']) ?>
              </span>
            <?php endif; ?>

            <?php if (!empty($user['primary_role']) && $user['show_role']): ?>
              <span class="role-tag">
                <?php
                $roleIcons = [
                  'poet' => '🎤',
                  'worship_leader' => '🎵',
                  'teacher' => '📖',
                  'intercessor' => '🙏',
                  'writer' => '✍️',
                  'creative' => '🎬',
                  'evangelist' => '📢',
                  'ministry_leader' => '💼',
                  'volunteer' => '👥',
                  'member' => '❤️'
                ];
                echo $roleIcons[$user['primary_role']] ?? '👤';
                ?>
                <?= ucfirst(str_replace('_', ' ', $user['primary_role'])) ?>
              </span>
            <?php endif; ?>

            <?php if ($chapterInfo && $user['show_chapter']): ?>
              <span class="chapter-tag">
                <i class="fas fa-map-marker-alt"></i>
                <?= htmlspecialchars($chapterInfo['name']) ?>
              </span>
            <?php endif; ?>

            <?php if (!empty($user['ministry_team']) && $user['show_team']): ?>
              <span class="team-tag">
                <i class="fas fa-users"></i>
                <?= htmlspecialchars($user['ministry_team']) ?>
              </span>
            <?php endif; ?>
          </div>

          <?php if (!empty($user['bio'])): ?>
            <div class="profile-bio">
              <?= nl2br(htmlspecialchars($user['bio'])) ?>
            </div>
          <?php endif; ?>

          <div class="profile-stats">
            <div class="profile-stat">
              <span class="profile-stat-value"><?= $stats['posts_count'] ?></span>
              <span class="profile-stat-label">Posts</span>
            </div>
            <div class="profile-stat">
              <span class="profile-stat-value"><?= $stats['events_count'] ?></span>
              <span class="profile-stat-label">Events</span>
            </div>
            <div class="profile-stat">
              <span class="profile-stat-value"><?= $stats['comments_count'] ?></span>
              <span class="profile-stat-label">Comments</span>
            </div>
          </div>

          <a href="<?= SITE_URL ?>/pages/dashboard/profile" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">
            <i class="fas fa-user-edit"></i> Edit Profile
          </a>
        </div>

        <!-- Upcoming Events -->
        <div class="dashboard-card" style="background: white; border-radius: var(--radius-2xl); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-top: 1.5rem;" data-aos="fade-left" data-aos-delay="100">
          <div class="dashboard-card-header" style="padding: 1.5rem; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 1.25rem; color: var(--dark-bg);">Upcoming Events</h2>
            <a href="<?= SITE_URL ?>/pages/events" style="color: var(--primary-purple); font-weight: 600; font-size: 0.875rem;">Browse</a>
          </div>
          <div style="padding: 1.5rem;">
            <?php if (count($upcomingEvents) > 0): ?>
              <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($upcomingEvents as $event): ?>
                  <div style="display: flex; gap: 1rem; cursor: pointer; transition: all var(--transition-base);" onclick="window.location.href='<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>'" onmouseover="this.style.transform='translateX(5px)'" onmouseout="this.style.transform='translateX(0)'">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%); border-radius: var(--radius-md); display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                      <div style="font-size: 1.5rem; font-weight: 700; line-height: 1;"><?= date('d', strtotime($event['start_date'])) ?></div>
                      <div style="font-size: 0.75rem; text-transform: uppercase;"><?= date('M', strtotime($event['start_date'])) ?></div>
                    </div>
                    <div style="flex: 1;">
                      <div style="font-weight: 700; color: var(--dark-bg); margin-bottom: 0.25rem;"><?= htmlspecialchars($event['title']) ?></div>
                      <div style="font-size: 0.875rem; color: var(--gray-600); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-clock"></i>
                        <?= date('g:i A', strtotime($event['start_date'])) ?>
                        <span style="margin: 0 0.5rem;">•</span>
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars(substr($event['location'], 0, 20)) ?>...
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div style="text-align: center; padding: 2rem 1rem;">
                <div style="font-size: 2.5rem; color: var(--gray-400); margin-bottom: 1rem;">
                  <i class="fas fa-calendar"></i>
                </div>
                <div style="font-size: 1rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">No Upcoming Events</div>
                <div style="color: var(--gray-600); font-size: 0.875rem; margin-bottom: 1rem;">Register for events to see them here.</div>
                <a href="<?= SITE_URL ?>/pages/events" class="btn btn-primary btn-sm">Browse Events</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="dashboard-card" style="background: white; border-radius: var(--radius-2xl); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); margin-top: 2rem; overflow: hidden;" data-aos="fade-up">
      <div class="dashboard-card-header" style="padding: 1.5rem; border-bottom: 1px solid var(--gray-200);">
        <h2 style="margin: 0; font-size: 1.5rem; color: var(--dark-bg);">Quick Actions</h2>
      </div>
      <div style="padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
          <a href="<?= SITE_URL ?>/pages/blog/create" style="padding: 1.5rem; text-align: center; text-decoration: none; background: linear-gradient(135deg, rgba(107, 70, 193, 0.05) 0%, rgba(107, 70, 193, 0.1) 100%); border-radius: var(--radius-lg); transition: all var(--transition-base); border: 2px solid transparent;" onmouseover="this.style.borderColor='#6B46C1'; this.style.transform='translateY(-5px)'" onmouseout="this.style.borderColor='transparent'; this.style.transform='translateY(0)'">
            <i class="fas fa-pen" style="font-size: 2rem; color: var(--primary-purple); margin-bottom: 0.75rem; display: block;"></i>
            <h4 style="margin-bottom: 0.5rem; color: var(--dark-bg);">Write a Post</h4>
            <p style="font-size: 0.875rem; color: var(--gray-600); margin: 0;">Share your thoughts</p>
          </a>

          <a href="<?= SITE_URL ?>/pages/media/upload" style="padding: 1.5rem; text-align: center; text-decoration: none; background: linear-gradient(135deg, rgba(45, 156, 219, 0.05) 0%, rgba(45, 156, 219, 0.1) 100%); border-radius: var(--radius-lg); transition: all var(--transition-base); border: 2px solid transparent;" onmouseover="this.style.borderColor='#2D9CDB'; this.style.transform='translateY(-5px)'" onmouseout="this.style.borderColor='transparent'; this.style.transform='translateY(0)'">
            <i class="fas fa-upload" style="font-size: 2rem; color: var(--primary-teal); margin-bottom: 0.75rem; display: block;"></i>
            <h4 style="margin-bottom: 0.5rem; color: var(--dark-bg);">Upload Media</h4>
            <p style="font-size: 0.875rem; color: var(--gray-600); margin: 0;">Share your work</p>
          </a>

          <a href="<?= SITE_URL ?>/pages/prayer/create" style="padding: 1.5rem; text-align: center; text-decoration: none; background: linear-gradient(135deg, rgba(212, 175, 55, 0.05) 0%, rgba(212, 175, 55, 0.1) 100%); border-radius: var(--radius-lg); transition: all var(--transition-base); border: 2px solid transparent;" onmouseover="this.style.borderColor='#D4AF37'; this.style.transform='translateY(-5px)'" onmouseout="this.style.borderColor='transparent'; this.style.transform='translateY(0)'">
            <i class="fas fa-praying-hands" style="font-size: 2rem; color: var(--primary-gold); margin-bottom: 0.75rem; display: block;"></i>
            <h4 style="margin-bottom: 0.5rem; color: var(--dark-bg);">Submit Prayer</h4>
            <p style="font-size: 0.875rem; color: var(--gray-600); margin: 0;">Request prayer</p>
          </a>

          <a href="<?= SITE_URL ?>/pages/events" style="padding: 1.5rem; text-align: center; text-decoration: none; background: linear-gradient(135deg, rgba(235, 87, 87, 0.05) 0%, rgba(235, 87, 87, 0.1) 100%); border-radius: var(--radius-lg); transition: all var(--transition-base); border: 2px solid transparent;" onmouseover="this.style.borderColor='#EB5757'; this.style.transform='translateY(-5px)'" onmouseout="this.style.borderColor='transparent'; this.style.transform='translateY(0)'">
            <i class="fas fa-calendar-plus" style="font-size: 2rem; color: var(--primary-coral); margin-bottom: 0.75rem; display: block;"></i>
            <h4 style="margin-bottom: 0.5rem; color: var(--dark-bg);">Register Event</h4>
            <p style="font-size: 0.875rem; color: var(--gray-600); margin: 0;">Join events</p>
          </a>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('dashboardSidebar');
    sidebar.classList.toggle('mobile-visible');
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('dashboardSidebar');
    const toggle = document.querySelector('.mobile-menu-toggle');

    if (window.innerWidth <= 768 &&
      !sidebar.contains(e.target) &&
      !toggle.contains(e.target) &&
      sidebar.classList.contains('mobile-visible')) {
      sidebar.classList.remove('mobile-visible');
    }
  });
</script>

<?php
// Helper functions
function getActivityIcon($action)
{
  $icons = [
    'login' => 'fa-sign-in-alt',
    'register' => 'fa-user-plus',
    'post_created' => 'fa-pen',
    'comment_added' => 'fa-comment',
    'event_registered' => 'fa-calendar-check',
    'prayer_submitted' => 'fa-praying-hands',
    'media_uploaded' => 'fa-upload',
    'profile_updated' => 'fa-user-edit',
  ];
  return $icons[$action] ?? 'fa-circle';
}

function getActivityColor($action)
{
  $colors = [
    'post_created' => 'linear-gradient(135deg, #6B46C1 0%, #9B7EDE 100%)',
    'comment_added' => 'linear-gradient(135deg, #2D9CDB 0%, #56CCF2 100%)',
    'event_registered' => 'linear-gradient(135deg, #D4AF37 0%, #F2D97A 100%)',
    'prayer_submitted' => 'linear-gradient(135deg, #51CF66 0%, #2F9E44 100%)',
    'media_uploaded' => 'linear-gradient(135deg, #EB5757 0%, #FF8787 100%)',
  ];
  return $colors[$action] ?? 'linear-gradient(135deg, #6B46C1 0%, #2D9CDB 100%)';
}

function formatActivityText($activity)
{
  $texts = [
    'login' => 'You logged in',
    'register' => 'You created your account',
    'post_created' => 'You published a new <strong>blog post</strong>',
    'comment_added' => 'You commented on a <strong>post</strong>',
    'event_registered' => 'You registered for an <strong>event</strong>',
    'prayer_submitted' => 'You submitted a <strong>prayer request</strong>',
    'media_uploaded' => 'You uploaded new <strong>media</strong>',
    'profile_updated' => 'You updated your <strong>profile</strong>',
  ];
  return $texts[$activity['action']] ?? 'Activity';
}

function timeAgo($datetime)
{
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

require_once __DIR__ . '/../../includes/footer.php';
?>