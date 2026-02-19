<?php
require_once __DIR__ . '/../config/session.php';
$user = getCurrentUser();
$firstName = $user ? $user['first_name'] : '';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;700&family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap');

:root {
  --splash-bg-1: #0a0a12;
  --splash-bg-2: #0f0f20;
  --splash-gold: #c9a96e;
  --splash-gold-light: #e8cfa0;
  --splash-white: #f0eee8;
  --splash-muted: rgba(240, 238, 232, 0.4);
}

/* Splash Screen Styles */
.splash-screen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  background: var(--splash-bg-1);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  opacity: 1;
  transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.splash-screen.fade-out {
  opacity: 0;
  pointer-events: none;
}

/* Ambient background orbs */
.splash-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  animation: orbDrift 8s ease-in-out infinite alternate;
  pointer-events: none;
}

.splash-orb-1 {
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(100, 60, 180, 0.25) 0%, transparent 70%);
  top: -150px;
  right: -100px;
  animation-delay: 0s;
}

.splash-orb-2 {
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(201, 169, 110, 0.15) 0%, transparent 70%);
  bottom: -100px;
  left: -80px;
  animation-delay: -3s;
}

.splash-orb-3 {
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(45, 156, 219, 0.12) 0%, transparent 70%);
  top: 40%;
  left: 10%;
  animation-delay: -6s;
}

@keyframes orbDrift {
  0% { transform: translate(0, 0) scale(1); }
  100% { transform: translate(30px, 20px) scale(1.1); }
}

/* Grid noise texture overlay */
.splash-screen::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: 
    repeating-linear-gradient(
      0deg,
      transparent,
      transparent 60px,
      rgba(255,255,255,0.012) 60px,
      rgba(255,255,255,0.012) 61px
    ),
    repeating-linear-gradient(
      90deg,
      transparent,
      transparent 60px,
      rgba(255,255,255,0.012) 60px,
      rgba(255,255,255,0.012) 61px
    );
  pointer-events: none;
}

/* Content wrapper */
.splash-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 2;
}

/* Decorative top line */
.splash-line-top {
  width: 1px;
  height: 0;
  background: linear-gradient(to bottom, transparent, var(--splash-gold));
  margin-bottom: 2.5rem;
  animation: lineGrow 0.8s ease-out 0.1s forwards;
}

@keyframes lineGrow {
  to { height: 60px; }
}

/* Logo */
.splash-logo {
  width: 72px;
  height: 72px;
  margin-bottom: 2rem;
  opacity: 0;
  filter: drop-shadow(0 0 20px rgba(201, 169, 110, 0.4));
  animation: logoReveal 0.9s cubic-bezier(0.16, 1, 0.3, 1) 0.4s forwards;
}

@keyframes logoReveal {
  from {
    opacity: 0;
    transform: scale(0.6) rotate(-8deg);
  }
  to {
    opacity: 1;
    transform: scale(1) rotate(0deg);
  }
}

/* Eyebrow label */
.splash-eyebrow {
  font-family: 'DM Sans', sans-serif;
  font-size: 0.65rem;
  font-weight: 500;
  letter-spacing: 0.35em;
  text-transform: uppercase;
  color: var(--splash-gold);
  margin-bottom: 1rem;
  opacity: 0;
  animation: fadeSlideUp 0.7s ease-out 0.7s forwards;
}

/* Welcome text */
.splash-welcome {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.1rem;
  font-weight: 300;
  color: var(--splash-muted);
  letter-spacing: 0.2em;
  text-transform: uppercase;
  margin-bottom: 0.4rem;
  opacity: 0;
  animation: fadeSlideUp 0.7s ease-out 0.85s forwards;
}

/* Name / Brand */
.splash-name {
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(3.5rem, 10vw, 6rem);
  font-weight: 400;
  color: var(--splash-white);
  letter-spacing: 0.06em;
  line-height: 1;
  margin-bottom: 0.3rem;
  opacity: 0;
  background: linear-gradient(135deg, var(--splash-white) 40%, var(--splash-gold-light) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: nameReveal 1s cubic-bezier(0.16, 1, 0.3, 1) 1s forwards;
}

@keyframes nameReveal {
  from {
    opacity: 0;
    transform: translateY(24px) scaleY(0.85);
    filter: blur(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0) scaleY(1);
    filter: blur(0);
  }
}

@keyframes fadeSlideUp {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Gold divider */
.splash-divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 1.8rem 0 2rem;
  opacity: 0;
  animation: fadeSlideUp 0.6s ease-out 1.3s forwards;
}

.splash-divider-line {
  width: 60px;
  height: 1px;
  background: linear-gradient(to right, transparent, var(--splash-gold));
}

.splash-divider-line:last-child {
  background: linear-gradient(to left, transparent, var(--splash-gold));
}

.splash-divider-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--splash-gold);
  box-shadow: 0 0 8px var(--splash-gold);
}

/* Progress bar */
.splash-progress-track {
  width: 180px;
  height: 1px;
  background: rgba(255,255,255,0.08);
  border-radius: 1px;
  overflow: hidden;
  opacity: 0;
  animation: fadeSlideUp 0.5s ease-out 1.5s forwards;
}

.splash-progress-fill {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, var(--splash-gold), var(--splash-gold-light));
  border-radius: 1px;
  animation: progressLoad 2s cubic-bezier(0.4, 0, 0.2, 1) 1.6s forwards;
  box-shadow: 0 0 8px rgba(201, 169, 110, 0.6);
}

@keyframes progressLoad {
  0% { width: 0%; }
  60% { width: 70%; }
  100% { width: 100%; }
}

/* Tagline */
.splash-tagline {
  font-family: 'DM Sans', sans-serif;
  font-size: 0.65rem;
  font-weight: 300;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: var(--splash-muted);
  margin-top: 1.4rem;
  opacity: 0;
  animation: fadeSlideUp 0.6s ease-out 1.8s forwards;
}

/* Bottom decorative line */
.splash-line-bottom {
  width: 1px;
  height: 0;
  background: linear-gradient(to bottom, var(--splash-gold), transparent);
  margin-top: 2.5rem;
  animation: lineGrow 0.8s ease-out 1.9s forwards;
}

/* Floating particles */
.splash-particles {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
}

.particle {
  position: absolute;
  width: 2px;
  height: 2px;
  border-radius: 50%;
  background: var(--splash-gold);
  opacity: 0;
  animation: particleFloat linear infinite;
}

@keyframes particleFloat {
  0% {
    opacity: 0;
    transform: translateY(100vh) scale(0);
  }
  10% { opacity: 0.6; }
  90% { opacity: 0.2; }
  100% {
    opacity: 0;
    transform: translateY(-10vh) scale(1.5);
  }
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .splash-logo {
    width: 56px;
    height: 56px;
  }

  .splash-divider-line {
    width: 40px;
  }

  .splash-progress-track {
    width: 130px;
  }
}
</style>

<div class="splash-screen" id="splashScreen">
  <!-- Ambient orbs -->
  <div class="splash-orb splash-orb-1"></div>
  <div class="splash-orb splash-orb-2"></div>
  <div class="splash-orb splash-orb-3"></div>

  <!-- Particles -->
  <div class="splash-particles" id="splashParticles"></div>

  <!-- Main content -->
  <div class="splash-content">
    <div class="splash-line-top"></div>

    <img src="<?= ASSETS_PATH ?>images/logo/logo.svg" alt="Scribes Global" class="splash-logo">

    <?php if ($firstName): ?>
      <div class="splash-eyebrow">Welcome back</div>
      <div class="splash-welcome">Good to see you</div>
      <div class="splash-name"><?= htmlspecialchars($firstName) ?></div>
    <?php else: ?>
      <div class="splash-eyebrow">Est. Excellence</div>
      <div class="splash-welcome">Welcome to</div>
      <div class="splash-name">Scribes Global</div>
    <?php endif; ?>

    <div class="splash-divider">
      <div class="splash-divider-line"></div>
      <div class="splash-divider-dot"></div>
      <div class="splash-divider-line"></div>
    </div>

    <div class="splash-progress-track">
      <div class="splash-progress-fill"></div>
    </div>

    <div class="splash-tagline">Preparing your experience</div>

    <div class="splash-line-bottom"></div>
  </div>
</div>

<script>
// Generate floating particles
(function() {
  const container = document.getElementById('splashParticles');
  if (!container) return;
  const count = 18;
  for (let i = 0; i < count; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    p.style.left = Math.random() * 100 + '%';
    p.style.animationDuration = (6 + Math.random() * 10) + 's';
    p.style.animationDelay = (Math.random() * 6) + 's';
    p.style.width = p.style.height = (1 + Math.random() * 2) + 'px';
    p.style.opacity = '0';
    container.appendChild(p);
  }
})();

// Splash screen dismiss
document.addEventListener('DOMContentLoaded', function() {
  const splashScreen = document.getElementById('splashScreen');

  // Hide splash after 2.5 seconds
  setTimeout(() => {
    splashScreen.classList.add('fade-out');

    // Remove from DOM after fade
    setTimeout(() => {
      splashScreen.remove();
    }, 500);
  }, 3500);
});
</script>