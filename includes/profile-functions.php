<?php
/**
 * Profile Completion Functions
 */

function calculateProfileCompletion($user) {
    $score = 0;
    $items = [];
    
    // Profile Photo (20%)
    if (!empty($user['profile_photo'])) {
        $score += 20;
        $items['photo'] = true;
    } else {
        $items['photo'] = false;
    }
    
    // Bio (15%)
    if (!empty($user['bio']) && strlen($user['bio']) >= 50) {
        $score += 15;
        $items['bio'] = true;
    } else {
        $items['bio'] = false;
    }
    
    // Phone Number (10%)
    if (!empty($user['phone'])) {
        $score += 10;
        $items['phone'] = true;
    } else {
        $items['phone'] = false;
    }
    
    // Primary Role (15%)
    if (!empty($user['primary_role']) && $user['primary_role'] !== 'member') {
        $score += 15;
        $items['role'] = true;
    } else {
        $items['role'] = false;
    }
    
    // Custom Tag (10%)
    if (!empty($user['custom_tag'])) {
        $score += 10;
        $items['custom_tag'] = true;
    } else {
        $items['custom_tag'] = false;
    }
    
    // Chapter (10%)
    if (!empty($user['chapter_id'])) {
        $score += 10;
        $items['chapter'] = true;
    } else {
        $items['chapter'] = false;
    }
    
    // Ministry Team (10%)
    if (!empty($user['ministry_team'])) {
        $score += 10;
        $items['team'] = true;
    } else {
        $items['team'] = false;
    }
    
    // Email Verified (10%)
    if ($user['email_verified']) {
        $score += 10;
        $items['verified'] = true;
    } else {
        $items['verified'] = false;
    }
    
    return [
        'score' => $score,
        'items' => $items
    ];
}

function awardProfileCompletionBadge($conn, $userId) {
    // Check if already has badge
    $checkStmt = $conn->prepare("
        SELECT id FROM user_badges 
        WHERE user_id = ? AND badge_type = 'active'
    ");
    $checkStmt->execute([$userId]);
    
    if (!$checkStmt->fetch()) {
        // Award badge
        $stmt = $conn->prepare("
            INSERT INTO user_badges (user_id, badge_type, auto_earned, awarded_at)
            VALUES (?, 'active', 1, NOW())
        ");
        $stmt->execute([$userId]);
        
        return true;
    }
    
    return false;
}

function getRoleDisplayName($role) {
    $roles = [
        'poet' => '🎤 Poet',
        'worship_leader' => '🎵 Worship Leader',
        'teacher' => '📖 Teacher',
        'intercessor' => '🙏 Intercessor',
        'writer' => '✍️ Writer',
        'creative' => '🎬 Creative',
        'evangelist' => '📢 Evangelist',
        'ministry_leader' => '💼 Ministry Leader',
        'volunteer' => '👥 Volunteer',
        'member' => '❤️ Member'
    ];
    
    return $roles[$role] ?? '❤️ Member';
}

function getRoleClass($role) {
    return 'role-' . str_replace('_', '-', $role);
}
?>