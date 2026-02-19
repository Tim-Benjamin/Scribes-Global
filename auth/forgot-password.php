<?php
$pageTitle = 'Forgot Password - Scribes Global';
$pageDescription = 'Reset your Scribes Global password';
$pageCSS = 'auth';
$noSplash = true;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/pages/dashboard');
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
        <h1>Forgot Password?</h1>
        <p>Enter your email to reset your password</p>
      </div>
      
      <div id="successMessage" class="alert alert-success" style="display: none;">
        <i class="fas fa-check-circle"></i> <span id="successText"></span>
      </div>
      
      <form id="forgotForm" class="auth-form" method="POST" action="<?= SITE_URL ?>/api/auth.php?action=forgot_password">
        <div class="form-group">
          <label for="email" class="form-label">Email Address</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control" 
            placeholder="your.email@example.com"
            required
          >
          <div class="form-error" id="emailError"></div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i class="fas fa-paper-plane"></i> Send Reset Link
        </button>
      </form>
      
      <div class="auth-footer">
        Remember your password? <a href="<?= SITE_URL ?>/auth/login">Login</a>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('forgotForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
  document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));
  document.getElementById('successMessage').style.display = 'none';
  
  const btn = this.querySelector('button[type="submit"]');
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
      document.getElementById('successText').textContent = result.message;
      document.getElementById('successMessage').style.display = 'block';
      this.reset();
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
    }
    
    btn.classList.remove('btn-loading');
    btn.disabled = false;
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
    btn.classList.remove('btn-loading');
    btn.disabled = false;
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>