<?php
$pageTitle = 'Reset Password - Scribes Global';
$pageCSS = 'auth';
$noSplash = true;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: ' . SITE_URL . '/auth/login');
    exit;
}

// Verify token
$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("
    SELECT id, first_name FROM users 
    WHERE reset_token = ? AND reset_token_expiry > NOW()
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = 'Invalid or expired reset link.';
    header('Location: ' . SITE_URL . '/auth/forgot-password');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
  <div class="auth-background">
    <div class="auth-shape auth-shape-1"></div>
    <div class="auth-shape auth-shape-2"></div>
    <div class="auth-shape auth-shape-3"></div>
  </div>
  
  <div class="auth-wrapper">
    <div class="auth-card" data-aos="zoom-in">
      <div class="auth-logo">
        <img src="<?= ASSETS_PATH ?>images/logo/logo.svg" alt="Scribes Global">
        <h1>Reset Password</h1>
        <p>Enter your new password</p>
      </div>
      
      <form id="resetForm" method="POST" action="<?= SITE_URL ?>/api/auth.php?action=reset_password">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        
        <div class="form-group">
          <label for="password" class="form-label">New Password</label>
          <div class="password-toggle">
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-control" 
              placeholder="Enter new password"
              required
              oninput="checkPasswordStrength(this.value)"
            >
            <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
              <i class="fas fa-eye" id="password-icon"></i>
            </button>
          </div>
          <div class="password-strength">
            <div class="password-strength-bar" id="strengthBar"></div>
          </div>
          <div class="password-strength-text" id="strengthText"></div>
        </div>
        
        <div class="form-group">
          <label for="confirm_password" class="form-label">Confirm Password</label>
          <div class="password-toggle">
            <input 
              type="password" 
              id="confirm_password" 
              name="confirm_password" 
              class="form-control" 
              placeholder="Re-enter new password"
              required
            >
            <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">
              <i class="fas fa-eye" id="confirm_password-icon"></i>
            </button>
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i class="fas fa-key"></i> Reset Password
        </button>
      </form>
    </div>
  </div>
</div>

<script src="<?= ASSETS_PATH ?>js/auth.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>