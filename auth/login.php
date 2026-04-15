<?php
$pageTitle = 'Login - Scribes Global';
$pageDescription = 'Login to your Scribes Global account';
$pageCSS = 'auth';
$noSplash = true;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/pages/dashboard');
    exit;
}

$error = '';
$success = '';

// Check for messages from other pages
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div id="three-canvas-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: -1; pointer-events: none;"></div>

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
        <h1>Welcome back</h1>
        <p>Enter your credentials to access your account</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form id="loginForm" class="auth-form" method="POST" action="<?= SITE_URL ?>/api/auth.php?action=login">

        <div class="form-group">
          <div class="clay-input-wrapper">
            <span class="clay-input-icon">
              <i class="fas fa-envelope"></i>
            </span>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control clay-input"
              placeholder="Enter your email"
              required
            >
          </div>
          <div class="form-error" id="emailError"></div>
        </div>

        <div class="form-group">
          <div class="clay-input-wrapper">
            <span class="clay-input-icon">
              <i class="fas fa-lock"></i>
            </span>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control clay-input"
              placeholder="Enter your password"
              required
            >
            <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
              <i class="fas fa-eye" id="password-icon"></i>
            </button>
          </div>
          <div class="form-error" id="passwordError"></div>
        </div>

        <div class="remember-forgot">
          <div class="checkbox-group">
            <input type="checkbox" id="remember" name="remember" value="1">
            <label for="remember">Remember me</label>
          </div>
          <a href="<?= SITE_URL ?>/auth/forgot-password">Forgot Password?</a>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-sign-in-alt"></i> Sign in
        </button>
      </form>

      <div class="auth-divider">
        <span>OR</span>
      </div>

      <div class="social-auth">
        <a href="<?= SITE_URL ?>/auth/google-oauth.php" class="btn-google">
          <img src="https://www.google.com/favicon.ico" alt="Google">
          Continue with Google
        </a>
      </div>

      <div class="auth-footer">
        Don't have an account? <a href="<?= SITE_URL ?>/auth/register">Create Account</a>
      </div>

    </div>
  </div>
</div>

<script>
function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  const icon = document.getElementById(fieldId + '-icon');

  if (field.type === 'password') {
    field.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    field.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

// Form validation and submission
document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  // Clear previous errors
  document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
  document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));

  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.classList.add('btn-loading');
  btn.disabled = true;

  const formData = new FormData(this);

  try {
    const response = await fetch(this.action, {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      window.location.href = result.redirect || '<?= SITE_URL ?>/pages/dashboard';
    } else {
      if (result.errors) {
        for (const [field, message] of Object.entries(result.errors)) {
          const errorEl = document.getElementById(field + 'Error');
          const inputEl = document.getElementById(field);

          if (errorEl && inputEl) {
            errorEl.textContent = message;
            inputEl.classList.add('error');
          }
        }
      } else if (result.message) {
        alert(result.message);
      }

      btn.classList.remove('btn-loading');
      btn.disabled = false;
    }
  } catch (error) {
    console.error('Login error:', error);
    alert('An error occurred. Please try again.');
    btn.classList.remove('btn-loading');
    btn.disabled = false;
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>