<?php
// pages/projects/ -> two levels up to project root
$root = dirname(dirname(__DIR__));

require_once $root . '/config/config.php';
require_once $root . '/config/session.php';

$pageTitle       = "Project H.E.A.L | Scribes Global";
$pageDescription = "Project H.E.A.L — Help, Educate, and Love. Scribes Global's outreach initiative extending love to the less privileged through education, resources, and compassion.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="icon" type="image/png" href="<?= ASSETS_PATH ?>images/logo/favicon.png">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600&family=Caveat:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- AOS -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

  <!-- Alpine.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- GSAP -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            purple:    { DEFAULT:'#6B46C1', light:'#9B7EDE', dark:'#4A2F8A', faint:'#F3EEFF' },
            gold:      { DEFAULT:'#D4AF37', light:'#F2D97A', dark:'#A8871A' },
            teal:      { DEFAULT:'#2D9CDB', light:'#56CCF2' },
            coral:     '#EB5757',
            ink:       '#1A1A2E',
            parchment: '#FAF7F2',
            heal: {
              green:  '#2D9B6F',
              blue:   '#2D6BB5',
              orange: '#E07B3A',
              red:    '#C0392B',
            }
          },
          fontFamily: {
            serif: ['Cormorant Garamond','Georgia','serif'],
            sans:  ['Poppins','system-ui','sans-serif'],
            body:  ['DM Sans','system-ui','sans-serif'],
            hand:  ['Caveat','cursive'],
          },
        }
      }
    }
  </script>

  <style>
    /* ══════════════════════════════════════════════
       NAV — self-contained, mirrors main.css
    ══════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body { -webkit-font-smoothing: antialiased;

}

    .navbar {
      position: sticky; top: 0; width: 100%;
      background: rgba(255,255,255,0.97);
      backdrop-filter: blur(12px);
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08);
      z-index: 1000;
      transition: all 300ms ease-in-out;
      font-family: 'Poppins', sans-serif;
    }
    .navbar-container {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0.85rem 1.5rem;
      max-width: 1400px; margin: 0 auto;
    }
    .navbar-logo {
      display: flex; align-items: center; gap: 0.5rem;
      font-size: 1.4rem; font-weight: 800;
      font-family: 'Cormorant Garamond', serif;
      color: #6B46C1; text-decoration: none;
    }
    .navbar-logo img { height: 46px; width: auto; }
    .navbar-menu {
      display: none; list-style: none;
      gap: 1.75rem; align-items: center;
    }
    .navbar-menu a {
      color: #4A5568; font-weight: 500; font-size: 0.88rem;
      padding: 0.4rem 0; text-decoration: none;
      transition: color 300ms ease;
    }
    .navbar-menu a:hover { color: #6B46C1; }
    .dropdown { position: relative; }
    .dropdown-menu {
      position: absolute; top: calc(100% + 8px); left: 0;
      background: #fff;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.12);
      border-radius: 0.75rem; padding: 0.5rem 0;
      min-width: 210px;
      opacity: 0; visibility: hidden; transform: translateY(-8px);
      transition: all 250ms ease;
    }
    .dropdown:hover .dropdown-menu { opacity:1; visibility:visible; transform:translateY(0); }
    .dropdown-menu a {
      display: block; padding: 0.65rem 1.25rem;
      color: #4A5568; font-size: 0.875rem;
      transition: all 150ms ease; text-decoration: none;
    }
    .dropdown-menu a:hover { background:#F7FAFC; color:#6B46C1; padding-left:1.75rem; }
    .navbar-cta {
      background: linear-gradient(135deg,#D4AF37,#F2D97A) !important;
      color: #1A1A2E !important; padding: 0.45rem 1.35rem !important;
      border-radius: 9999px !important; font-weight: 600 !important;
      font-size: 0.875rem !important; transition: all 200ms ease !important;
      text-decoration: none;
    }
    .navbar-cta:hover { transform:scale(1.05); box-shadow:0 10px 15px -3px rgba(0,0,0,0.15); }
    .hamburger { display:flex; flex-direction:column; gap:4px; cursor:pointer; padding:0.4rem; }
    .hamburger span { width:24px; height:3px; background:#6B46C1; border-radius:2px; transition:all 300ms ease; }
    .hamburger.active span:nth-child(1) { transform:rotate(45deg) translate(7px,7px); }
    .hamburger.active span:nth-child(2) { opacity:0; }
    .hamburger.active span:nth-child(3) { transform:rotate(-45deg) translate(7px,-7px); }
    .mobile-menu {
      position: fixed; top:0; right:-100%;
      width: 82%; max-width: 380px; height: 100vh;
      background:#fff; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);
      padding: 2rem 1.5rem; overflow-y:auto;
      transition: right 500ms ease; z-index:999;
      font-family: 'Poppins', sans-serif;
    }
    .mobile-menu.active { right:0; }
    .mobile-overlay {
      position:fixed; top:0; left:0; width:100%; height:100vh;
      background:rgba(0,0,0,0.5); opacity:0; visibility:hidden;
      transition:all 300ms ease; z-index:998;
    }
    .mobile-overlay.active { opacity:1; visibility:visible; }
    .mobile-menu-list { list-style:none; margin-top:2rem; }
    .mobile-menu-list li { margin-bottom:0.5rem; }
    .mobile-menu-list a {
      display:block; padding:0.7rem 0; font-size:1rem;
      color:#4A5568; font-weight:500; text-decoration:none;
      border-bottom:1px solid #EDF2F7;
    }
    .mobile-dropdown-toggle {
      display:flex; justify-content:space-between; align-items:center;
      cursor:pointer; padding:0.7rem 0;
      border-bottom:1px solid #EDF2F7; color:#4A5568; font-weight:500;
    }
    .mobile-dropdown-content { max-height:0; overflow:hidden; transition:max-height 300ms ease; padding-left:1rem; }
    .mobile-dropdown-content.active { max-height:500px; }
    .mobile-dropdown-content a { font-size:0.9rem; }
    @media (min-width: 768px) {
      .navbar-menu { display: flex; }
      .hamburger   { display: none; }
    }

    /* ══════════════════════════════════════════════
       PAGE STYLES
    ══════════════════════════════════════════════ */

    /* Hero */
    .hero-section {
      position: relative; min-height: 88vh;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden; padding: 6rem 1rem 5rem;
    }
    .hero-bg {
      position: absolute; inset: 0;
      background-image: url('https://static.wixstatic.com/media/98ecda_5109a8759c904a118b7f0ea155526479~mv2.jpg/v1/fill/w_940,h_600,al_c,q_85,enc_avif,quality_auto/98ecda_5109a8759c904a118b7f0ea155526479~mv2.jpg');
      background-size: cover; background-position: center top;
      transform: scale(1.04);
      transition: transform 8s ease;
    }
    .hero-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(160deg,
        rgba(26,26,46,0.82) 0%,
        rgba(45,155,111,0.55) 40%,
        rgba(26,26,46,0.88) 100%);
    }

    /* Gold shimmer */
    .gold-line {
      height: 3px;
      background: linear-gradient(90deg,transparent,#D4AF37,#F2D97A,#D4AF37,transparent);
      animation: shimmer 3s ease-in-out infinite;
    }
    @keyframes shimmer { 0%,100%{opacity:.55} 50%{opacity:1} }

    /* HEAL acronym letters */
    .heal-letter {
      display: inline-flex; align-items: center; justify-content: center;
      width: 72px; height: 72px; border-radius: 1rem;
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.4rem; font-weight: 700; color: #fff;
      flex-shrink: 0;
      transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
    }
    .heal-letter:hover { transform: translateY(-6px) rotate(-3deg); }

    /* Project card */
    .project-card {
      position: relative; border-radius: 1.5rem; overflow: hidden;
      background: #fff;
      box-shadow: 0 4px 24px -8px rgba(0,0,0,0.1);
      transition: all 0.38s cubic-bezier(0.34,1.56,0.64,1);
    }
    .project-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 24px 48px -12px rgba(45,155,111,0.22);
    }
    .project-card-img {
      width: 100%; height: 260px;
      object-fit: cover; display: block;
      transition: transform 0.5s ease;
    }
    .project-card:hover .project-card-img { transform: scale(1.06); }
    .project-card::after {
      content: ''; position: absolute; bottom: 0; left: 0; right: 0;
      height: 4px; transform: scaleX(0); transform-origin: left;
      transition: transform 0.38s ease;
    }
    .project-card:hover::after { transform: scaleX(1); }

    /* Gallery grid */
    .gallery-grid { display: grid; gap: 0.75rem; }
    .gallery-item {
      border-radius: 0.875rem; overflow: hidden;
      position: relative; cursor: pointer;
    }
    .gallery-item img {
      width: 100%; height: 100%; object-fit: cover; display: block;
      transition: transform 0.45s ease;
    }
    .gallery-item:hover img { transform: scale(1.08); }
    .gallery-item::after {
      content: ''; position: absolute; inset: 0; border-radius: 0.875rem;
      background: rgba(45,155,111,0);
      transition: background 0.3s ease;
    }
    .gallery-item:hover::after { background: rgba(45,155,111,0.15); }

    /* Pull quote */
    .pull-quote {
      border-left: 4px solid #2D9B6F;
      background: linear-gradient(135deg,rgba(45,155,111,0.07),rgba(45,107,181,0.04));
      border-radius: 0 1rem 1rem 0;
    }

    /* Stat chip */
    .stat-num {
      background: linear-gradient(135deg,#D4AF37,#F2D97A);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Lift card */
    .lift-card { transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.35s ease; }
    .lift-card:hover { transform: translateY(-7px); box-shadow: 0 20px 40px -12px rgba(45,155,111,0.2); }

    /* Image frame */
    .img-frame { position: relative; border-radius: 1.5rem; overflow: hidden; }
    .img-frame img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .img-frame::after {
      content: ''; position: absolute; inset: 0; border-radius: 1.5rem;
      box-shadow: inset 0 0 0 2px rgba(45,155,111,0.22);
    }

    /* CTA band */
    .cta-band {
      background: linear-gradient(135deg,#2D9B6F 0%,#1A6EA8 50%,#1A1A2E 100%);
      position: relative; overflow: hidden;
    }
    .cta-band::before {
      content: ''; position: absolute; inset: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E") repeat;
    }

    /* Ink drop */
    .ink-drop {
      position: absolute; border-radius: 50%;
      animation: breathe 5s ease-in-out infinite; pointer-events: none;
    }
    @keyframes breathe { 0%,100%{transform:scale(1);opacity:.4} 50%{transform:scale(1.1);opacity:.75} }

    /* Bounce */
    @keyframes bounceY { 0%,100%{transform:translateY(0)} 50%{transform:translateY(8px)} }
    .bounce-y { animation: bounceY 1.6s ease-in-out infinite; }

    /* Lightbox */
    #lightbox {
      position: fixed; inset: 0; z-index: 9999;
      background: rgba(0,0,0,0.92);
      display: none; align-items: center; justify-content: center;
      padding: 1rem;
    }
    #lightbox.open { display: flex; }
    #lightbox img {
      max-width: 90vw; max-height: 88vh;
      border-radius: 1rem; object-fit: contain;
      box-shadow: 0 30px 60px rgba(0,0,0,0.5);
    }
    #lightbox-close {
      position: absolute; top: 1.5rem; right: 1.5rem;
      width: 44px; height: 44px; border-radius: 50%;
      background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2);
      color: #fff; font-size: 1.1rem;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: background 0.2s ease;
    }
    #lightbox-close:hover { background: rgba(255,255,255,0.25); }
  </style>
</head>
<body style="font-family:'Poppins',sans-serif;background:#FAF7F2;color:#1A1A2E;overflow-x:hidden;">

  <!-- Lightbox -->
  <div id="lightbox" onclick="closeLightbox(event)">
    <button id="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
    <img id="lightbox-img" src="" alt="">
  </div>

  <?php
  $root = dirname(dirname(__DIR__));
  include $root . '/includes/nav.php';
  ?>

  <?php require_once $root . '/includes/universal-splash.php'; ?>


  <!-- ══════════════════════════════════════════
       HERO
  ══════════════════════════════════════════ -->
  <section class="hero-section text-center">
    <div class="hero-bg" id="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="ink-drop" style="width:420px;height:420px;top:-60px;right:-80px;background:radial-gradient(circle,rgba(45,155,111,0.2) 0%,transparent 70%);"></div>
    <div class="ink-drop" style="width:300px;height:300px;bottom:-40px;left:-50px;animation-delay:2.5s;background:radial-gradient(circle,rgba(45,107,181,0.18) 0%,transparent 70%);"></div>

    <div class="relative z-10 max-w-4xl mx-auto px-4">

      <!-- Badge -->
      <div class="mb-7" data-aos="fade-down">
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-sans font-medium"
              style="background:rgba(45,155,111,0.18);border:1px solid rgba(45,155,111,0.45);color:#6EE7B7;">
          <i class="fas fa-heart text-xs"></i> Scribes Global Initiative
        </span>
      </div>

      <!-- Title -->
      <h1 id="hero-headline" class="font-serif font-bold text-white mb-3"
          style="font-size:clamp(3.2rem,9vw,6.5rem);line-height:1.05;">
        Project H.E.A.L
      </h1>

      <!-- Acronym spelled out -->
      <p class="font-hand text-2xl sm:text-3xl mb-8" style="color:#6EE7B7;" data-aos="fade-up" data-aos-delay="200">
        <strong>H</strong>elp · <strong>E</strong>ducate · <strong>A</strong>nd · <strong>L</strong>ove
      </p>

      <div class="gold-line max-w-xs mx-auto mb-9"></div>

      <p class="text-gray-200 font-body font-light text-lg leading-relaxed max-w-2xl mx-auto mb-12"
         data-aos="fade-up" data-aos-delay="350">
        An initiative borne by <strong class="text-white font-semibold">Aseye Adonu</strong>, a member of Scribes. The aim is to extend love to all — by helping the less privileged with gifts and resources, and empowering them through education.
      </p>

      <div class="flex flex-col sm:flex-row gap-4 justify-center" data-aos="fade-up" data-aos-delay="450">
        <a href="<?= SITE_URL ?>/pages/give"
           class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full font-sans font-semibold text-ink text-base transition-all hover:scale-105 hover:shadow-2xl"
           style="background:linear-gradient(135deg,#D4AF37,#F2D97A);">
          <i class="fas fa-heart"></i> Give to H.E.A.L
        </a>
        <a href="#projects"
           class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full font-sans font-semibold text-white text-base border-2 transition-all hover:bg-white hover:text-ink"
           style="border-color:rgba(255,255,255,0.5);">
          <i class="fas fa-arrow-down"></i> See Our Projects
        </a>
      </div>
    </div>

    <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-10 flex flex-col items-center gap-2 opacity-40">
      <span class="text-white text-xs tracking-widest uppercase" style="font-family:'Poppins',sans-serif;">Scroll</span>
      <div class="w-px h-10 bg-gradient-to-b from-white to-transparent bounce-y"></div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       HEAL ACRONYM BREAKDOWN
  ══════════════════════════════════════════ -->
  <section class="bg-white py-20 px-4">
    <div class="max-w-5xl mx-auto">
      <div class="text-center mb-14" data-aos="fade-up">
        <span class="font-hand text-green-600 text-2xl" style="color:#2D9B6F;">What H.E.A.L Means</span>
        <h2 class="font-serif text-4xl sm:text-5xl font-bold text-ink mt-1">Our Four Pillars</h2>
        <div class="gold-line max-w-xs mx-auto mt-5"></div>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $pillars = [
          ['letter'=>'H', 'word'=>'Help',    'desc'=>'Providing practical help — food, resources, and essential materials — to those who need it most.', 'grad'=>'linear-gradient(135deg,#2D9B6F,#1A7050)', 'delay'=>0],
          ['letter'=>'E', 'word'=>'Educate', 'desc'=>'Empowering communities through education, workshops, and tools that open doors to a better future.', 'grad'=>'linear-gradient(135deg,#2D6BB5,#1A4A8A)', 'delay'=>100],
          ['letter'=>'A', 'word'=>'And',     'desc'=>'A bridge between compassion and action — connecting our community to those who need care most.', 'grad'=>'linear-gradient(135deg,#E07B3A,#B55A1A)', 'delay'=>200],
          ['letter'=>'L', 'word'=>'Love',    'desc'=>'Every project is rooted in genuine, unconditional love — the kind that transforms lives and reflects Christ.', 'grad'=>'linear-gradient(135deg,#C0392B,#8B0000)', 'delay'=>300],
        ];
        foreach ($pillars as $p): ?>
          <div class="lift-card text-center p-8 rounded-2xl border border-gray-100"
               data-aos="fade-up" data-aos-delay="<?= $p['delay'] ?>">
            <div class="heal-letter mx-auto mb-5" style="background:<?= $p['grad'] ?>;">
              <?= $p['letter'] ?>
            </div>
            <h3 class="font-serif text-2xl font-bold text-ink mb-3"><?= $p['word'] ?></h3>
            <p class="text-gray-500 font-body font-light text-sm leading-relaxed"><?= $p['desc'] ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       ABOUT THE PROJECT — Image + Text
  ══════════════════════════════════════════ -->
  <section class="py-24 px-4" style="background: #bebebe94;">
    <div class="max-w-6xl mx-auto">
      <div class="grid lg:grid-cols-2 gap-14 items-center">

        <!-- Real Wix image -->
        <div class="img-frame" style="height:500px;" data-aos="fade-right" data-aos-duration="900">
          <img src="https://static.wixstatic.com/media/521bf8_0506857373b34a04b48b15c7e9f50d01~mv2.jpg/v1/fill/w_600,h_800,al_c,q_80,usm_0.66_1.00_0.01,enc_avif,quality_auto/521bf8_0506857373b34a04b48b15c7e9f50d01~mv2.jpg"
               alt="Project H.E.A.L community visit">
          <!-- Floating founder chip -->
          <div class="absolute bottom-6 left-6 bg-white rounded-2xl px-5 py-4 shadow-2xl flex items-center gap-3"
               style="backdrop-filter:blur(10px);">
            <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0"
                 style="background:linear-gradient(135deg,#2D9B6F,#1A7050);">
              <i class="fas fa-leaf text-white text-sm"></i>
            </div>
            <div>
              <div class="font-sans font-semibold text-ink text-sm leading-none">Aseye Adonu</div>
              <div class="text-gray-400 text-xs mt-0.5 font-body">Founder, H.E.A.L</div>
            </div>
          </div>
        </div>

        <!-- Text -->
        <div data-aos="fade-left" data-aos-duration="900" data-aos-delay="100">
          <span class="font-hand text-2xl block mb-2" style="color:#2D9B6F;">The Initiative</span>
          <h2 class="font-serif text-4xl sm:text-5xl font-bold text-ink leading-tight mb-6">
            Extending Love<br>
            <span class="italic font-light" style="color:#2D9B6F;">to All</span>
          </h2>

          <div class="space-y-4 text-gray-600 font-body font-light text-base leading-relaxed mb-8">
            <p>
              Project H.E.A.L stands for <strong class="text-ink font-semibold">Help, Educate, and Love</strong> — an initiative borne by <strong class="text-ink font-semibold">Aseye Adonu</strong>, a member of Scribes Global.
            </p>
            <p>
              The aim is to extend love to all; by helping the less privileged with the gifts and resources, and empowering them through education.
            </p>
            <p>
              Through each project under H.E.A.L, we go beyond words — we act. From visiting children's homes and autism centres to empowering the deaf and supporting street children, every initiative is driven by Christ's love in action.
            </p>
          </div>

          <!-- Sub-projects list -->
          <div class="space-y-3">
            <?php foreach ([
              ['label'=>'Nikasemɔ',        'year'=>'2017', 'col'=>'#2D9B6F'],
              ['label'=>'BRAVE',            'year'=>'2018', 'col'=>'#2D6BB5'],
              ['label'=>'Love Spectrum',    'year'=>'2021', 'col'=>'#E07B3A'],
              ['label'=>'Project Care',     'year'=>'2023', 'col'=>'#C0392B'],
            ] as $sp): ?>
              <div class="flex items-center gap-4 p-4 rounded-xl bg-white border border-gray-100 lift-card">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background:<?= $sp['col'] ?>1A;border:1.5px solid <?= $sp['col'] ?>44;">
                  <i class="fas fa-heart text-sm" style="color:<?= $sp['col'] ?>"></i>
                </div>
                <div class="flex-1">
                  <span class="font-sans font-semibold text-ink text-sm"><?= $sp['label'] ?></span>
                </div>
                <span class="font-hand text-lg" style="color:<?= $sp['col'] ?>;"><?= $sp['year'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       PROJECTS — Individual Cards
  ══════════════════════════════════════════ -->
  <section id="projects" class="bg-white py-24 px-4">
    <div class="max-w-6xl mx-auto">

      <div class="text-center mb-16" data-aos="fade-up">
        <span class="font-hand text-2xl" style="color:#2D9B6F;">What We've Done</span>
        <h2 class="font-serif text-4xl sm:text-5xl font-bold text-ink mt-1">Our Projects</h2>
        <p class="text-gray-500 font-body font-light text-base mt-4 max-w-xl mx-auto">
          Each sub-project under H.E.A.L targets a specific community in need — bringing education, resources, and most importantly, love.
        </p>
        <div class="gold-line max-w-xs mx-auto mt-6"></div>
      </div>

      <!-- PROJECT 1: Project Care -->
      <div class="mb-20">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

          <!-- Image -->
          <div class="img-frame" style="height:440px;" data-aos="fade-right" data-aos-duration="900">
            <img src="https://static.wixstatic.com/media/3c82a7_0cb4093d7f5740adbfdeb0c6af537ce9~mv2.jpg/v1/fill/w_600,h_800,al_c,q_80,usm_0.66_1.00_0.01,enc_avif,quality_auto/WhatsApp%20Image%202023-12-15%20at%2000_33_23_e0f1a76a.jpg"
                 alt="Project Care - Nyamedua Children's Home">
            <div class="absolute top-5 left-5">
              <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full font-sans font-semibold text-white text-sm"
                    style="background:rgba(192,57,43,0.88);backdrop-filter:blur(6px);">
                <i class="fas fa-child text-xs"></i> 2023
              </span>
            </div>
          </div>

          <!-- Text -->
          <div data-aos="fade-left" data-aos-duration="900" data-aos-delay="100">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-sans font-semibold mb-4"
                 style="background:#C0392B1A;color:#C0392B;border:1px solid #C0392B33;">
              <i class="fas fa-house-chimney-heart"></i> Project Care
            </div>
            <h3 class="font-serif text-3xl sm:text-4xl font-bold text-ink mb-5 leading-tight">
              Nyamedua<br>Children's Home
            </h3>
            <div class="space-y-4 text-gray-600 font-body font-light text-base leading-relaxed">
              <p>As part of our ongoing Project HEAL initiative, this year, we had the privilege of visiting the <strong class="text-ink font-semibold">Nyamedua Children's Home in Adenta</strong>. During our visit, we contributed essential educational materials such as books, pens, erasers, and more, aiming to support their learning endeavors.</p>
              <p>Additionally, we provided much-needed food items to assist in meeting their nutritional needs.</p>
              <p>This year's edition, termed <strong class="text-ink font-semibold">'Project Care,'</strong> resonates deeply with our commitment to extend care and compassion to orphans. Our primary goal is to demonstrate heartfelt care through these contributions, furthering our mission to positively impact the lives of these children.</p>
            </div>
            <!-- What we gave -->
            <div class="flex flex-wrap gap-2 mt-6">
              <?php foreach (['Books & Pens','Erasers','Food Items','Care & Love'] as $tag): ?>
                <span class="px-3 py-1.5 rounded-full text-xs font-sans font-medium"
                      style="background:#C0392B12;color:#C0392B;border:1px solid #C0392B2A;">
                  <i class="fas fa-check text-xs mr-1"></i><?= $tag ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- PROJECT 2: Love Spectrum -->
      <div class="mb-20">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

          <!-- Text (left on this one) -->
          <div data-aos="fade-right" data-aos-duration="900" class="order-2 lg:order-1">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-sans font-semibold mb-4"
                 style="background:#E07B3A1A;color:#E07B3A;border:1px solid #E07B3A33;">
              <i class="fas fa-rainbow"></i> Love Spectrum
            </div>
            <h3 class="font-serif text-3xl sm:text-4xl font-bold text-ink mb-5 leading-tight">
              HopeSetters<br>Autism Center, Tema
            </h3>
            <div class="space-y-4 text-gray-600 font-body font-light text-base leading-relaxed">
              <p>During the COVID period in 2020, we had plans of assisting people with autism but we couldn't do so due to the restrictions. We then had to reschedule it.</p>
              <p>In <strong class="text-ink font-semibold">2021</strong>, we visited the <strong class="text-ink font-semibold">HopeSetters Autism Center in Tema</strong> and donated educational materials to the institution. We also spent the day having fun with the kids and learning more about Autism and on how best we can be of help to people living with autism.</p>
            </div>
            <div class="flex flex-wrap gap-2 mt-6">
              <?php foreach (['Educational Materials','Fun Activities','Autism Awareness','2021'] as $tag): ?>
                <span class="px-3 py-1.5 rounded-full text-xs font-sans font-medium"
                      style="background:#E07B3A12;color:#E07B3A;border:1px solid #E07B3A2A;">
                  <i class="fas fa-check text-xs mr-1"></i><?= $tag ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Images collage -->
          <div class="order-1 lg:order-2" data-aos="fade-left" data-aos-duration="900" data-aos-delay="100">
            <div class="grid grid-cols-2 gap-3" style="height:440px;">
              <div class="img-frame" style="height:100%;" onclick="openLightbox(this.querySelector('img').src)">
                <img src="https://static.wixstatic.com/media/521bf8_21fcb2f6a17d450991ab7560d54122c5~mv2.jpg/v1/fill/w_435,h_580,al_c,q_80,enc_avif,quality_auto/521bf8_21fcb2f6a17d450991ab7560d54122c5~mv2.jpg"
                     alt="Love Spectrum visit 1" style="cursor:zoom-in;">
              </div>
              <div class="grid gap-3">
                <div class="img-frame" style="height:210px;" onclick="openLightbox(this.querySelector('img').src)">
                  <img src="https://static.wixstatic.com/media/521bf8_0506857373b34a04b48b15c7e9f50d01~mv2.jpg/v1/fill/w_396,h_527,al_c,q_80,enc_avif,quality_auto/521bf8_0506857373b34a04b48b15c7e9f50d01~mv2.jpg"
                       alt="Love Spectrum visit 2" style="cursor:zoom-in;">
                </div>
                <div class="img-frame" style="height:210px;" onclick="openLightbox(this.querySelector('img').src)">
                  <img src="https://static.wixstatic.com/media/521bf8_85b349b240614917885d2480f9fe582d~mv2.jpg/v1/fill/w_659,h_494,al_c,q_80,enc_avif,quality_auto/521bf8_85b349b240614917885d2480f9fe582d~mv2.jpg"
                       alt="Love Spectrum visit 3" style="cursor:zoom-in;">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PROJECT 3: BRAVE -->
      <div class="mb-20">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

          <!-- Gallery -->
          <div data-aos="fade-right" data-aos-duration="900">
            <!-- Masonry-style grid using real Wix images -->
            <div style="display:grid;grid-template-columns:1fr 1fr;grid-template-rows:200px 200px;gap:0.75rem;height:420px;">
              <div class="img-frame" style="grid-row:span 2;" onclick="openLightbox(this.querySelector('img').src)">
                <img src="https://static.wixstatic.com/media/98ecda_0375a4192c7e43589cc065b7e7721a21~mv2.jpg/v1/fill/w_483,h_600,al_c,q_80,enc_avif,quality_auto/IMG_3886.jpg"
                     alt="BRAVE visit" style="cursor:zoom-in;">
              </div>
              <div class="img-frame" onclick="openLightbox(this.querySelector('img').src)">
                <img src="https://static.wixstatic.com/media/98ecda_3f4106faffd04ba3bdcf6e3ac858c1f9~mv2.jpg/v1/fill/w_390,h_260,al_c,q_80,enc_avif,quality_auto/IMG_3894.jpg"
                     alt="BRAVE visit 2" style="cursor:zoom-in;">
              </div>
              <div class="img-frame" onclick="openLightbox(this.querySelector('img').src)">
                <img src="https://static.wixstatic.com/media/98ecda_ef55ae332d6d46d3b9c160a7e873904f~mv2.jpg/v1/fill/w_390,h_260,al_c,q_80,enc_avif,quality_auto/IMG_3888.jpg"
                     alt="BRAVE visit 3" style="cursor:zoom-in;">
              </div>
            </div>
          </div>

          <!-- Text -->
          <div data-aos="fade-left" data-aos-duration="900" data-aos-delay="100">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-sans font-semibold mb-4"
                 style="background:#2D6BB51A;color:#2D6BB5;border:1px solid #2D6BB533;">
              <i class="fas fa-hands-asl-interpreting"></i> BRAVE
            </div>
            <h3 class="font-serif text-3xl sm:text-4xl font-bold text-ink mb-5 leading-tight">
              State School for<br>Deaf, Ashaiman
            </h3>
            <div class="space-y-4 text-gray-600 font-body font-light text-base leading-relaxed">
              <p>In <strong class="text-ink font-semibold">2018</strong>, we took the initiative once again, this time to help students who are deaf and dumb. We believe that, regardless of their limitation, they can still make impact and so must be heard.</p>
              <p>We visited the <strong class="text-ink font-semibold">State School for Deaf at Ashaiman</strong> and donated educational materials, food, toiletries, etc. to the school to help in their good work.</p>
              <p>A student was also <strong class="text-ink font-semibold">trained in poetry and was invited to perform a piece in sign language</strong> at our annual poetry event, Script on Scrolls.</p>
            </div>

            <!-- Pull quote -->
            <div class="pull-quote p-5 mt-6">
              <p class="font-serif text-lg italic text-ink font-light leading-relaxed">
                "Regardless of their limitation, they can still make impact — and so must be heard."
              </p>
            </div>

            <div class="flex flex-wrap gap-2 mt-5">
              <?php foreach (['Educational Materials','Food','Toiletries','Poetry Training','Sign Language Performance'] as $tag): ?>
                <span class="px-3 py-1.5 rounded-full text-xs font-sans font-medium"
                      style="background:#2D6BB512;color:#2D6BB5;border:1px solid #2D6BB52A;">
                  <i class="fas fa-check text-xs mr-1"></i><?= $tag ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- PROJECT 4: Nikasemɔ -->
      <div>
        <div class="grid lg:grid-cols-2 gap-12 items-center">

          <!-- Text -->
          <div data-aos="fade-right" data-aos-duration="900" class="order-2 lg:order-1">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-sans font-semibold mb-4"
                 style="background:#2D9B6F1A;color:#2D9B6F;border:1px solid #2D9B6F33;">
              <i class="fas fa-children"></i> Nikasemɔ
            </div>
            <h3 class="font-serif text-3xl sm:text-4xl font-bold text-ink mb-5 leading-tight">
              Street Children,<br>Bukom — Accra
            </h3>
            <div class="space-y-4 text-gray-600 font-body font-light text-base leading-relaxed">
              <p>In <strong class="text-ink font-semibold">2017</strong>, we partnered with <strong class="text-ink font-semibold">SCEF International (Street Children Empowerment Fund)</strong> to educate and help street children in and around Bukom, a suburb of Accra Central.</p>
              <p>In this project, we taught the kids for a month, provided them with educational materials and other important items.</p>
              <p>Finally, we <strong class="text-ink font-semibold">trained two of the kids in poetry</strong> and invited them to our annual event (Scripts on Scrolls) to perform.</p>
            </div>

            <!-- Pull quote -->
            <div class="pull-quote p-5 mt-6">
              <p class="font-serif text-lg italic text-ink font-light leading-relaxed">
                "We taught, equipped, and then gave them a stage — because every voice deserves to be heard."
              </p>
            </div>

            <div class="flex flex-wrap gap-2 mt-5">
              <?php foreach (['SCEF International','Monthly Teaching','Educational Materials','Poetry Training','Scripts on Scrolls'] as $tag): ?>
                <span class="px-3 py-1.5 rounded-full text-xs font-sans font-medium"
                      style="background:#2D9B6F12;color:#2D9B6F;border:1px solid #2D9B6F2A;">
                  <i class="fas fa-check text-xs mr-1"></i><?= $tag ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Images -->
          <div class="order-1 lg:order-2" data-aos="fade-left" data-aos-duration="900" data-aos-delay="100">
            <div style="display:grid;grid-template-columns:1fr 1fr;grid-template-rows:200px 200px;gap:0.75rem;height:420px;">
              <div class="img-frame" onclick="openLightbox(this.querySelector('img').src)">
                <img src="https://static.wixstatic.com/media/98ecda_3c30f35296e746c8bc01a6f3931b2ffa~mv2.jpg/v1/fill/w_474,h_316,al_c,q_80,enc_avif,quality_auto/IMG_3845.jpg"
                     alt="Nikasemo 1" style="cursor:zoom-in;">
              </div>
              <div class="img-frame" onclick="openLightbox(this.querySelector('img').src)">
                <img src="https://static.wixstatic.com/media/98ecda_ca0bad468f3c4ed2a61f1d2fc1acb0d2~mv2.jpg/v1/fill/w_366,h_244,al_c,q_80,enc_avif,quality_auto/IMG_3841.jpg"
                     alt="Nikasemo 2" style="cursor:zoom-in;">
              </div>
              <div class="img-frame" style="grid-column:span 2;" onclick="openLightbox(this.querySelector('img').src)">
                <img src="https://static.wixstatic.com/media/98ecda_915b43721e064fdf9910313bed620c8a~mv2.jpg/v1/fill/w_562,h_375,al_c,q_80,enc_avif,quality_auto/IMG_3819.jpg"
                     alt="Nikasemo 3" style="cursor:zoom-in;">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       PHOTO GALLERY — All real Wix images
  ══════════════════════════════════════════ -->
  <section class="py-24 px-4" style="background:#1A1A2E;">
    <div class="absolute top-0 left-0 right-0 gold-line" style="position:relative;"></div>
    <div class="max-w-6xl mx-auto">
      <div class="text-center mb-14" data-aos="fade-up">
        <span class="font-hand text-2xl" style="color:#6EE7B7;">Moments in Action</span>
        <h2 class="font-serif text-4xl sm:text-5xl font-bold text-white mt-1">Photo Gallery</h2>
        <div class="gold-line max-w-xs mx-auto mt-5"></div>
        <p class="text-gray-400 font-body font-light text-sm mt-4">Click any image to enlarge</p>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        <?php
        $gallery = [
          ['src'=>'https://static.wixstatic.com/media/521bf8_21fcb2f6a17d450991ab7560d54122c5~mv2.jpg/v1/fill/w_435,h_580,al_c,q_80,enc_avif,quality_auto/521bf8_21fcb2f6a17d450991ab7560d54122c5~mv2.jpg', 'alt'=>'H.E.A.L visit', 'span'=>'row-span-2'],
          ['src'=>'https://static.wixstatic.com/media/521bf8_0506857373b34a04b48b15c7e9f50d01~mv2.jpg/v1/fill/w_396,h_527,al_c,q_80,enc_avif,quality_auto/521bf8_0506857373b34a04b48b15c7e9f50d01~mv2.jpg', 'alt'=>'Community members', 'span'=>''],
          ['src'=>'https://static.wixstatic.com/media/521bf8_85b349b240614917885d2480f9fe582d~mv2.jpg/v1/fill/w_659,h_494,al_c,q_80,enc_avif,quality_auto/521bf8_85b349b240614917885d2480f9fe582d~mv2.jpg', 'alt'=>'Scribes members', 'span'=>'col-span-2'],
          ['src'=>'https://static.wixstatic.com/media/98ecda_0375a4192c7e43589cc065b7e7721a21~mv2.jpg/v1/fill/w_483,h_322,al_c,q_80,enc_avif,quality_auto/IMG_3886.jpg', 'alt'=>'BRAVE visit', 'span'=>''],
          ['src'=>'https://static.wixstatic.com/media/98ecda_3c30f35296e746c8bc01a6f3931b2ffa~mv2.jpg/v1/fill/w_474,h_316,al_c,q_80,enc_avif,quality_auto/IMG_3845.jpg', 'alt'=>'Nikasemo', 'span'=>''],
          ['src'=>'https://static.wixstatic.com/media/98ecda_3f4106faffd04ba3bdcf6e3ac858c1f9~mv2.jpg/v1/fill/w_390,h_260,al_c,q_80,enc_avif,quality_auto/IMG_3894.jpg', 'alt'=>'BRAVE school visit', 'span'=>''],
          ['src'=>'https://static.wixstatic.com/media/98ecda_ef55ae332d6d46d3b9c160a7e873904f~mv2.jpg/v1/fill/w_390,h_260,al_c,q_80,enc_avif,quality_auto/IMG_3888.jpg', 'alt'=>'School donation', 'span'=>''],
          ['src'=>'https://static.wixstatic.com/media/98ecda_ca0bad468f3c4ed2a61f1d2fc1acb0d2~mv2.jpg/v1/fill/w_366,h_244,al_c,q_80,enc_avif,quality_auto/IMG_3841.jpg', 'alt'=>'Street children', 'span'=>''],
          ['src'=>'https://static.wixstatic.com/media/98ecda_9fe2395ed97f4946b1578c9fb9b31e32~mv2.jpg/v1/fill/w_354,h_236,al_c,q_80,enc_avif,quality_auto/IMG_3850.jpg', 'alt'=>'Kids learning', 'span'=>''],
          ['src'=>'https://static.wixstatic.com/media/98ecda_94e76c6df3a44bb791f3b8c70df990f4~mv2.jpg/v1/fill/w_353,h_236,al_c,q_80,enc_avif,quality_auto/IMG_3840.jpg', 'alt'=>'Group activity', 'span'=>''],
          ['src'=>'https://static.wixstatic.com/media/98ecda_915b43721e064fdf9910313bed620c8a~mv2.jpg/v1/fill/w_562,h_375,al_c,q_80,enc_avif,quality_auto/IMG_3819.jpg', 'alt'=>'Poetry performance', 'span'=>'col-span-2'],
          ['src'=>'https://static.wixstatic.com/media/98ecda_7a99052261094830bc94e066aae2ea0c~mv2.jpg/v1/fill/w_478,h_318,al_c,q_80,enc_avif,quality_auto/IMG_3836.jpg', 'alt'=>'BRAVE group', 'span'=>''],
        ];
        foreach ($gallery as $i => $g): ?>
          <div class="gallery-item <?= $g['span'] ?>"
               style="height:<?= str_contains($g['span'],'row-span') ? '420px' : '190px' ?>;"
               onclick="openLightbox('<?= $g['src'] ?>')"
               data-aos="fade-up" data-aos-delay="<?= ($i % 4) * 60 ?>">
            <img src="<?= $g['src'] ?>" alt="<?= $g['alt'] ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       CTA — GIVE
  ══════════════════════════════════════════ -->
  <section class="cta-band py-28 px-4 relative">
    <div class="relative z-10 max-w-2xl mx-auto text-center" data-aos="fade-up">
      <span class="font-hand text-3xl block mb-4" style="color:#6EE7B7;">Every Gift Matters</span>
      <h2 class="font-serif text-4xl sm:text-5xl font-bold text-white leading-tight mb-6">
        Help Us<br>
        <span class="italic font-light" style="color:#F2D97A;">Help, Educate &amp; Love</span>
      </h2>
      <p class="text-gray-300 font-body font-light text-lg leading-relaxed mb-10 max-w-xl mx-auto">
        Your support enables us to reach more children, more communities, and more lives with the love of Christ in practical, tangible ways.
      </p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="<?= SITE_URL ?>/pages/give"
           class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full font-sans font-semibold text-ink text-base transition-all hover:scale-105"
           style="background:linear-gradient(135deg,#D4AF37,#F2D97A);">
          <i class="fas fa-hand-holding-heart"></i> Give to H.E.A.L
        </a>
        <a href="<?= SITE_URL ?>/pages/connect/volunteer"
           class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full font-sans font-semibold text-white text-base border-2 border-white transition-all hover:bg-white hover:text-ink">
          <i class="fas fa-user-plus"></i> Volunteer
        </a>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       FOOTER
  ══════════════════════════════════════════ -->
  <footer style="background:#16213E;" class="text-gray-400 py-16 px-4">
    <div class="max-w-6xl mx-auto">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">

        <div class="md:col-span-1">
          <div class="font-serif text-2xl font-bold text-white mb-3">Scribes Global</div>
          <p class="text-sm font-body font-light leading-relaxed text-gray-500 mb-4">
            A non-profit, non-denominational evangelistic ministry preaching the gospel through creative arts.
          </p>
          <div class="space-y-1 text-sm font-body font-light mb-5">
            <p><a href="mailto:info@scribesglobal.com" class="hover:text-gold transition-colors" style="--tw-text-opacity:1;">info@scribesglobal.com</a></p>
            <p><a href="tel:+233546296188" class="hover:text-gold transition-colors">054 629 6188</a></p>
            <p><a href="tel:+233209315447" class="hover:text-gold transition-colors">020 931 5447</a></p>
          </div>
          <div class="flex gap-3">
            <?php foreach ([
              ['fa-facebook-f','https://www.facebook.com/scribespoetry'],
              ['fa-twitter',   'https://twitter.com/scribes_poetry'],
              ['fa-instagram', 'https://www.instagram.com/scribes_poetry/'],
              ['fa-youtube',   'https://www.youtube.com/channel/UCFfEhYBSqsSu7Bg2nZUGsbg'],
              ['fa-tiktok',    'https://bit.ly/ScribesTiktok'],
            ] as [$ic,$url]): ?>
              <a href="<?= $url ?>" target="_blank" rel="noopener"
                 class="w-9 h-9 rounded-full flex items-center justify-center text-gray-500 transition-all hover:text-white"
                 style="background:rgba(255,255,255,0.07);"
                 onmouseover="this.style.background='#6B46C1'" onmouseout="this.style.background='rgba(255,255,255,0.07)'">
                <i class="fab <?= $ic ?> text-sm"></i>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <h4 class="text-white font-serif text-lg font-semibold mb-4">About Us</h4>
          <ul class="space-y-2 text-sm font-body font-light">
            <li><a href="<?= SITE_URL ?>/pages/about/scribes-global" class="hover:text-gold transition-colors">Scribes Global</a></li>
            <li><a href="<?= SITE_URL ?>/pages/about/ministries"     class="hover:text-gold transition-colors">Ministries</a></li>
            <li><a href="<?= SITE_URL ?>/pages/about/chapters"       class="hover:text-gold transition-colors">Chapters</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-white font-serif text-lg font-semibold mb-4">Quick Links</h4>
          <ul class="space-y-2 text-sm font-body font-light">
            <li><a href="<?= SITE_URL ?>/pages/events"        class="hover:text-gold transition-colors">Events</a></li>
            <li><a href="<?= SITE_URL ?>/pages/projects/heal" class="hover:text-gold transition-colors">Project H.E.A.L</a></li>
            <li><a href="<?= SITE_URL ?>/pages/media"         class="hover:text-gold transition-colors">Media</a></li>
            <li><a href="<?= SITE_URL ?>/pages/give"          class="hover:text-gold transition-colors">Give</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-white font-serif text-lg font-semibold mb-4">Connect</h4>
          <ul class="space-y-2 text-sm font-body font-light">
            <li><a href="<?= SITE_URL ?>/pages/connect/invite"    class="hover:text-gold transition-colors">Invite Scribes Global</a></li>
            <li><a href="<?= SITE_URL ?>/pages/connect/volunteer" class="hover:text-gold transition-colors">Join / Volunteer</a></li>
          </ul>
        </div>

      </div>

      <div class="border-t pt-8 flex flex-col sm:flex-row justify-between items-center gap-4"
           style="border-color:rgba(255,255,255,0.08);">
        <p class="text-xs font-body text-gray-600">&copy;<?= date('Y') ?> by Scribes Global. All rights reserved.</p>
      </div>
    </div>
  </footer>


  <script>
    AOS.init({ once: true, duration: 860, easing: 'ease-out-quart' });

    gsap.registerPlugin(ScrollTrigger);
    gsap.from('#hero-headline', {
      y: 80, opacity: 0, duration: 1.4, ease: 'power4.out', delay: 0.15,
    });

    // Subtle hero image scale on scroll
    gsap.to('#hero-bg', {
      scale: 1.12, ease: 'none',
      scrollTrigger: { trigger: '.hero-section', start: 'top top', end: 'bottom top', scrub: true }
    });

    // Lightbox
    function openLightbox(src) {
      document.getElementById('lightbox-img').src = src;
      document.getElementById('lightbox').classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeLightbox(e) {
      if (!e || e.target === document.getElementById('lightbox') || e.currentTarget.id === 'lightbox-close') {
        document.getElementById('lightbox').classList.remove('open');
        document.getElementById('lightbox-img').src = '';
        document.body.style.overflow = '';
      }
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
  </script>

</body>
</html>