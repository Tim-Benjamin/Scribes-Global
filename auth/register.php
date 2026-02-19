<?php
$pageTitle = 'Create Account - Scribes Global';
$pageDescription = 'Join the Scribes Global community';
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
        <h1>Join Scribes Global</h1>
        <p>Start your creative journey with us</p>
      </div>
      
      <form id="registerForm" class="auth-form" method="POST" action="<?= SITE_URL ?>/api/auth.php?action=register">
        <div class="form-group">
          <label for="first_name" class="form-label">First Name</label>
          <input 
            type="text" 
            id="first_name" 
            name="first_name" 
            class="form-control" 
            placeholder="Kiki"
            required
          >
          <div class="form-error" id="first_nameError"></div>
        </div>
        
        <div class="form-group">
          <label for="last_name" class="form-label">Last Name</label>
          <input 
            type="text" 
            id="last_name" 
            name="last_name" 
            class="form-control" 
            placeholder="Darko"
            required
          >
          <div class="form-error" id="last_nameError"></div>
        </div>
        
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
        
        <div class="form-group">
          <label for="phone" class="form-label">Phone Number (Optional)</label>
          <input 
            type="tel" 
            id="phone" 
            name="phone" 
            class="form-control" 
            placeholder="+233 123 456 789"
          >
          <div class="form-error" id="phoneError"></div>
        </div>
        
        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <div class="password-toggle">
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-control" 
              placeholder="Create a strong password"
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
          <div class="form-error" id="passwordError"></div>
        </div>
        
        <div class="form-group">
          <label for="confirm_password" class="form-label">Confirm Password</label>
          <div class="password-toggle">
            <input 
              type="password" 
              id="confirm_password" 
              name="confirm_password" 
              class="form-control" 
              placeholder="Re-enter your password"
              required
            >
            <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">
              <i class="fas fa-eye" id="confirm_password-icon"></i>
            </button>
          </div>
          <div class="form-error" id="confirm_passwordError"></div>
        </div>
        
        <div class="form-group">
          <div class="checkbox-group">
            <input type="checkbox" id="terms" name="terms" required>
            <label for="terms">
              I agree to the <a href="<?= SITE_URL ?>/pages/legal/terms" target="_blank">Terms of Service</a> 
              and <a href="<?= SITE_URL ?>/pages/legal/privacy" target="_blank">Privacy Policy</a>
            </label>
          </div>
          <div class="form-error" id="termsError"></div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i class="fas fa-user-plus"></i> Create Account
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
        Already have an account? <a href="<?= SITE_URL ?>/auth/login">Login</a>
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

function checkPasswordStrength(password) {
  const strengthBar = document.getElementById('strengthBar');
  const strengthText = document.getElementById('strengthText');
  
  let strength = 0;
  
  if (password.length >= 8) strength++;
  if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
  if (password.match(/[0-9]/)) strength++;
  if (password.match(/[^a-zA-Z0-9]/)) strength++;
  
  strengthBar.className = 'password-strength-bar';
  
  if (strength === 0 || strength === 1) {
    strengthBar.classList.add('weak');
    strengthText.textContent = 'Weak password';
    strengthText.style.color = 'var(--primary-coral)';
  } else if (strength === 2 || strength === 3) {
    strengthBar.classList.add('medium');
    strengthText.textContent = 'Medium strength';
    strengthText.style.color = '#FFA500';
  } else {
    strengthBar.classList.add('strong');
    strengthText.textContent = 'Strong password';
    strengthText.style.color = '#51CF66';
  }
}

// Form validation and submission
document.getElementById('registerForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  // Clear previous errors
  document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
  document.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));
  
  // Client-side validation
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirm_password').value;
  
  if (password !== confirmPassword) {
    document.getElementById('confirm_passwordError').textContent = 'Passwords do not match';
    document.getElementById('confirm_password').classList.add('error');
    return;
  }
  
  if (password.length < 8) {
    document.getElementById('passwordError').textContent = 'Password must be at least 8 characters';
    document.getElementById('password').classList.add('error');
    return;
  }
  
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
      window.location.href = result.redirect || '<?= SITE_URL ?>/auth/login?registered=1';
    } else {
      // Show errors
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
    console.error('Registration error:', error);
    alert('An error occurred. Please try again.');
    btn.classList.remove('btn-loading');
    btn.disabled = false;
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>