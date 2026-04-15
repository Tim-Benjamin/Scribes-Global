<?php
// pages/about/ -> two levels up to project root
$root = dirname(dirname(__DIR__));

require_once $root . '/config/config.php';
require_once $root . '/config/session.php';

$pageTitle       = "About Scribes Global | Restorers of Truth";
$pageDescription = "Scribes Global is a non-profit, non-denominational evangelistic ministry preaching the gospel through creative arts — poetry, music, and spoken word.";
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
                        purple: {
                            DEFAULT: '#6B46C1',
                            light: '#9B7EDE',
                            dark: '#4A2F8A',
                            faint: '#F3EEFF'
                        },
                        gold: {
                            DEFAULT: '#D4AF37',
                            light: '#F2D97A',
                            dark: '#A8871A'
                        },
                        teal: {
                            DEFAULT: '#2D9CDB',
                            light: '#56CCF2'
                        },
                        coral: '#EB5757',
                        ink: '#1A1A2E',
                        parchment: '#FAF7F2',
                    },
                    fontFamily: {
                        serif: ['Cormorant Garamond', 'Georgia', 'serif'],
                        sans: ['Poppins', 'system-ui', 'sans-serif'],
                        body: ['DM Sans', 'system-ui', 'sans-serif'],
                        hand: ['Caveat', 'cursive'],
                    },
                }
            }
        }
    </script>

    <style>
        /* ══════════════════════════════════════════════
       NAV — pulled from main.css, fully self-contained
    ══════════════════════════════════════════════ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            -webkit-font-smoothing: antialiased;
        }

        .navbar {
            position: sticky;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            transition: all 300ms ease-in-out;
            font-family: 'Poppins', sans-serif;
        }

        .navbar-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .navbar-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.4rem;
            font-weight: 800;
            font-family: 'Cormorant Garamond', serif;
            color: #6B46C1;
            text-decoration: none;
        }

        .navbar-logo img {
            height: 46px;
            width: auto;
        }

        .navbar-menu {
            display: none;
            list-style: none;
            gap: 1.75rem;
            align-items: center;
        }

        .navbar-menu a {
            color: #4A5568;
            font-weight: 500;
            font-size: 0.88rem;
            padding: 0.4rem 0;
            text-decoration: none;
            transition: color 300ms ease;
        }

        .navbar-menu a:hover {
            color: #6B46C1;
        }

        .dropdown {
            position: relative;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            background: #fff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.12);
            border-radius: 0.75rem;
            padding: 0.5rem 0;
            min-width: 210px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 250ms ease;
        }

        .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: block;
            padding: 0.65rem 1.25rem;
            color: #4A5568;
            font-size: 0.875rem;
            transition: all 150ms ease;
            text-decoration: none;
        }

        .dropdown-menu a:hover {
            background: #F7FAFC;
            color: #6B46C1;
            padding-left: 1.75rem;
        }

        .navbar-cta {
            background: linear-gradient(135deg, #D4AF37, #F2D97A) !important;
            color: #1A1A2E !important;
            padding: 0.45rem 1.35rem !important;
            border-radius: 9999px !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            transition: all 200ms ease !important;
            text-decoration: none;
        }

        .navbar-cta:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15);
        }

        .hamburger {
            display: flex;
            flex-direction: column;
            gap: 4px;
            cursor: pointer;
            padding: 0.4rem;
        }

        .hamburger span {
            width: 24px;
            height: 3px;
            background: #6B46C1;
            border-radius: 2px;
            transition: all 300ms ease;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(7px, 7px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 82%;
            max-width: 380px;
            height: 100vh;
            background: #fff;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 2rem 1.5rem;
            overflow-y: auto;
            transition: right 500ms ease;
            z-index: 999;
            font-family: 'Poppins', sans-serif;
        }

        .mobile-menu.active {
            right: 0;
        }

        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 300ms ease;
            z-index: 998;
        }

        .mobile-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .mobile-menu-list {
            list-style: none;
            margin-top: 2rem;
        }

        .mobile-menu-list li {
            margin-bottom: 0.5rem;
        }

        .mobile-menu-list a {
            display: block;
            padding: 0.7rem 0;
            font-size: 1rem;
            color: #4A5568;
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px solid #EDF2F7;
        }

        .mobile-dropdown-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 0.7rem 0;
            border-bottom: 1px solid #EDF2F7;
            color: #4A5568;
            font-weight: 500;
        }

        .mobile-dropdown-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 300ms ease;
            padding-left: 1rem;
        }

        .mobile-dropdown-content.active {
            max-height: 500px;
        }

        .mobile-dropdown-content a {
            font-size: 0.9rem;
        }

        @media (min-width: 768px) {
            .navbar-menu {
                display: flex;
            }

            .hamburger {
                display: none;
            }
        }

        /* ══════════════════════════════════════════════
       PAGE STYLES
    ══════════════════════════════════════════════ */

        /* Hero */
        .hero-section {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1A1A2E;
            overflow: hidden;
            padding: 5rem 1rem 4rem;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            background-image: url('https://static.wixstatic.com/media/521bf8_7d622c1e53064c06ab56a08a6e19e1fa~mv2.jpg/v1/fill/w_940,h_600,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/_MG_3829.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.15;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            /* background: linear-gradient(160deg, rgba(107, 70, 193, 0.55) 0%, rgba(26, 26, 46, 0.88) 50%, rgba(26, 26, 46, 1) 100%); */
        }

        /* Gold shimmer */
        .gold-line {
            height: 3px;
            background: linear-gradient(90deg, transparent, #D4AF37, #F2D97A, #D4AF37, transparent);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {

            0%,
            100% {
                opacity: .55
            }

            50% {
                opacity: 1
            }
        }

        /* Ink blob */
        .ink-drop {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(107, 70, 193, 0.18) 0%, transparent 70%);
            animation: breathe 5s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes breathe {

            0%,
            100% {
                transform: scale(1);
                opacity: .4
            }

            50% {
                transform: scale(1.1);
                opacity: .75
            }
        }

        /* Lift card */
        .lift-card {
            transition: transform .35s cubic-bezier(.34, 1.56, .64, 1), box-shadow .35s ease;
        }

        .lift-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 48px -12px rgba(107, 70, 193, 0.22);
        }

        /* Image frame */
        .img-frame {
            position: relative;
            border-radius: 1.5rem;
            overflow: hidden;
        }

        .img-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .img-frame::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 1.5rem;
            box-shadow: inset 0 0 0 2px rgba(212, 175, 55, 0.2);
        }

        /* Ministry card */
        .ministry-card {
            position: relative;
            overflow: hidden;
            border-radius: 1.5rem;
            transition: all .35s ease;
        }

        .ministry-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform .5s ease;
            display: block;
        }

        .ministry-card:hover img {
            transform: scale(1.07);
        }

        .ministry-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6B46C1, #D4AF37);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .35s ease;
        }

        .ministry-card:hover::after {
            transform: scaleX(1);
        }

        /* Pull quote */
        .pull-quote {
            border-left: 4px solid #D4AF37;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.09), rgba(107, 70, 193, 0.05));
            border-radius: 0 1rem 1rem 0;
        }

        /* Chapter card */
        .chapter-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.25rem;
            transition: all .3s ease;
            padding: 2rem;
            text-align: center;
        }

        .chapter-card:hover {
            background: rgba(212, 175, 55, 0.1);
            border-color: rgba(212, 175, 55, 0.45);
            transform: translateY(-5px);
        }

        /* Stat number gradient text */
        .stat-num {
            background: linear-gradient(135deg, #D4AF37, #F2D97A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Identity pill */
        .id-pill {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            padding: .55rem 1.3rem;
            border-radius: 9999px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: .85rem;
        }

        /* Dark shaphan block */
        .shaphan-block {
            background: linear-gradient(135deg, #1A1A2E 0%, #16213E 100%);
            position: relative;
            overflow: hidden;
        }

        .shaphan-block::before {
            content: '"';
            position: absolute;
            top: -60px;
            left: -20px;
            font-family: 'Cormorant Garamond', serif;
            font-size: 22rem;
            line-height: 1;
            color: rgba(212, 175, 55, 0.05);
            pointer-events: none;
            user-select: none;
        }

        /* CTA band */
        .cta-band {
            background: linear-gradient(135deg, #6B46C1 0%, #4A2F8A 45%, #1A1A2E 100%);
            position: relative;
            overflow: hidden;
        }

        .cta-band::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23D4AF37' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E") repeat;
        }

        @keyframes bounceY {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(8px)
            }
        }

        .bounce-y {
            animation: bounceY 1.6s ease-in-out infinite;
        }
    </style>
</head>


<div id="three-canvas-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: -1; pointer-events: none;"></div>

<body style="font-family:'Poppins',sans-serif;background:#FAF7F2;color:#1A1A2E;overflow-x:hidden;">

    <?php
    $root = dirname(dirname(__DIR__));
    include $root . '/includes/splash.php';
    include $root . '/includes/nav.php';
    ?>


    <!-- ══════════════════════════════════
       HERO
  ══════════════════════════════════ -->
    <section class="hero-section text-center">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="ink-drop" style="width:480px;height:480px;top:-60px;right:-100px;"></div>
        <div class="ink-drop" style="width:300px;height:300px;bottom:-40px;left:-50px;animation-delay:2.5s;background:radial-gradient(circle,rgba(45,156,219,0.16) 0%,transparent 70%);"></div>

        <div class="relative z-10 max-w-4xl mx-auto px-4">
            <!-- Founded badge -->
            <div class="mb-7" data-aos="fade-down">
                <span class="id-pill" style="background:rgba(212,175,55,0.15);border:1px solid rgba(212,175,55,0.4);color:#F2D97A;">
                    <i class="fas fa-calendar-alt text-xs"></i> Established 22nd May, 2017
                </span>
            </div>

            <!-- Headline -->
            <h1 id="hero-headline" class="font-serif font-bold text-white mb-4"
                style="font-size:clamp(3.4rem,9vw,7rem);line-height:1.05;">
                Scribes Global
            </h1>

            <!-- Acronym -->
            <p class="font-hand text-gold mb-8" style="font-size:clamp(1.1rem,2.8vw,1.55rem);"
                data-aos="fade-up" data-aos-delay="200">
                <strong>S</strong>peaking <strong>C</strong>hrist's <strong>R</strong>edemption
                <strong>I</strong>n <strong>B</strong>iblically-<strong>E</strong>dified <strong>S</strong>peech
            </p>

            <div class="gold-line max-w-xs mx-auto mb-9"></div>

            <!-- Identity pills -->
            <div class="flex flex-wrap justify-center gap-3 mb-10" data-aos="fade-up" data-aos-delay="320">
                <span class="id-pill" style="background:rgba(107, 70, 193, 0.16);border:1px solid rgba(155,126,222,0.45);color:#C4B5FD;">
                    <i class="fas fa-book-open text-xs"></i> Restorers of Truth
                </span>
                <span class="id-pill" style="background:rgba(212, 175, 55, 0.18);border:1px solid rgba(212,175,55,0.45);color:#F2D97A;">
                    <i class="fas fa-microphone text-xs"></i> Unashamed to Speak
                </span>
                <span class="id-pill" style="background:rgba(45, 155, 219, 0.12);border:1px solid rgba(86,204,242,0.45);color:#7DD3FC;">
                    <i class="fas fa-hands-praying text-xs"></i> Grace unto us
                </span>
            </div>

            <p class="text-gray-300 font-body font-light text-lg leading-relaxed max-w-2xl mx-auto mb-12"
                data-aos="fade-up" data-aos-delay="420">
                A non-profit, non-denominational evangelistic ministry preaching the gospel through creative arts — poetry, music, and spoken word.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center" data-aos="fade-up" data-aos-delay="520">
                <a href="<?= SITE_URL ?>/pages/connect/volunteer"
                    class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full font-sans font-semibold text-ink text-base transition-all hover:scale-105 hover:shadow-2xl"
                    style="background:linear-gradient(135deg,#D4AF37,#F2D97A);">
                    <i class="fas fa-pen-nib"></i> Join / Volunteer
                </a>
                <!-- <a href="<?= SITE_URL ?>/pages/give"
                    class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full font-sans font-semibold text-gold text-base border-2 transition-all hover:bg-gold hover:text-ink"
                    style="border-color:#D4AF37;">
                    <i class="fas fa-heart"></i> Give
                </a> -->
            </div>
        </div>

        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-10 flex flex-col items-center gap-2 opacity-40">
            <span class="text-white text-xs tracking-widest uppercase" style="font-family:'Poppins',sans-serif;">Scroll</span>
            <div class="w-px h-10 bg-gradient-to-b from-white to-transparent bounce-y"></div>
        </div>
    </section>


    <!-- ══════════════════════════════════
       WHO WE ARE — Image + Text
  ══════════════════════════════════ -->
    <section class="bg-white py-24 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-14 items-center">

                <!-- Image panel -->
                <div class="img-frame" style="height:520px;" data-aos="fade-right" data-aos-duration="900">
                    <img src="https://static.wixstatic.com/media/521bf8_7d622c1e53064c06ab56a08a6e19e1fa~mv2.jpg/v1/fill/w_940,h_600,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/_MG_3829.jpg" alt="Scribes Global community">
                    <!-- Floating chips -->
                    <div class="absolute bottom-6 left-6 bg-white rounded-2xl px-5 py-4 shadow-2xl flex items-center gap-4"
                        style="backdrop-filter:blur(10px);">
                        <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0"
                            style="background:linear-gradient(135deg,#6B46C1,#9B7EDE);">
                            <i class="fas fa-users text-white text-sm"></i>
                        </div>
                        <div>
                            <div class="font-serif text-2xl font-bold stat-num leading-none">100+</div>
                            <div class="text-gray-500 text-xs font-sans mt-0.5">Members Worldwide</div>
                        </div>
                    </div>
                    <div class="absolute top-6 right-6 bg-white rounded-2xl px-5 py-3 shadow-2xl text-center">
                        <div class="font-serif text-2xl font-bold stat-num leading-none">2017</div>
                        <div class="text-gray-500 text-xs font-sans mt-0.5">Founded</div>
                    </div>
                </div>

                <!-- Text -->
                <div data-aos="fade-left" data-aos-duration="900" data-aos-delay="100">
                    <span class="font-hand text-gold text-2xl block mb-2">Who We Are</span>
                    <h2 class="font-serif text-4xl sm:text-5xl font-bold text-ink leading-tight mb-6">
                        A Community<br>
                        <span class="italic font-light" style="color:#6B46C1;">of Creatives</span>
                    </h2>

                    <div class="space-y-4 text-gray-600 font-body font-light text-base leading-relaxed mb-8">
                        <p>Scribes Global is a <strong class="text-ink font-semibold">non-profit, non-denominational organization</strong> set up as an evangelistic ministry to preach the gospel through creative arts — poetry, music, and spoken word.</p>
                        <p>Established on <strong class="text-ink font-semibold">22nd May, 2017</strong>, we have grown from a group of <strong class="text-ink font-semibold">8 members</strong> to over <strong class="text-ink font-semibold">100 members</strong> in different parts of the world, organising events, workshops, conferences and outreach programs.</p>
                        <p>Scribes Global now has chapters in <strong class="text-ink font-semibold">KNUST, University of Cape Coast and University of Ghana</strong> with a global audience to its events and fellowship meetings.</p>
                        <p>As a community of creatives, our ministries are commissioned to preach Jesus Christ and inspire hope for purity and holiness. They include <strong class="text-ink font-semibold">Scribes Poetry, Scribes Worship and Scribes Kids</strong>.</p>
                    </div>

                    <!-- Mini stats -->
                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach (
                            [
                                ['n' => '3',  'l' => 'Ministries',    'i' => 'fa-church'],
                                ['n' => '4',  'l' => 'Campuses',       'i' => 'fa-graduation-cap'],
                                ['n' => '7+', 'l' => 'Years Active',   'i' => 'fa-fire-flame-curved'],
                            ] as $s
                        ): ?>
                            <div class="text-center p-4 rounded-2xl lift-card"
                                style="background:#FAF7F2;border:1px solid rgba(107,70,193,0.1);">
                                <i class="fas <?= $s['i'] ?> mb-2 block" style="color:#6B46C1;font-size:1.2rem;"></i>
                                <div class="font-serif text-2xl font-bold stat-num leading-none"><?= $s['n'] ?></div>
                                <div class="text-gray-500 text-xs font-sans mt-1"><?= $s['l'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- ══════════════════════════════════
       BIBLICAL PERSPECTIVE
  ══════════════════════════════════ -->
    <section class="shaphan-block py-24 px-4">
        <div class="max-w-6xl mx-auto relative z-10">
            <div class="grid lg:grid-cols-2 gap-14 items-center">

                <!-- Text -->
                <div data-aos="fade-right" data-aos-duration="900">
                    <span class="font-hand text-gold text-2xl block mb-2">2 Kings 22–23</span>
                    <h2 class="font-serif text-4xl sm:text-5xl font-bold text-white leading-tight mb-8">
                        Biblical<br>
                        <span class="italic font-light" style="color:#9B7EDE;">Perspective</span>
                    </h2>

                    <div class="space-y-5 text-gray-300 font-body font-light text-base leading-relaxed">
                        <p>Josiah, King of Jerusalem, sent <strong class="text-white font-semibold">Shaphan, a Scribe</strong>, to the high priest — who gave him the Book of the Law. Shaphan read it, saw God's impending wrath, and immediately brought the message to the king. It led to the <strong class="text-gold font-semibold">deliverance of Jerusalem</strong>.</p>
                        <p>We believe like Shaphan, we have been called as Scribes — <strong class="text-white font-semibold">messengers</strong> — to bring the message of <strong class="text-gold font-semibold">REDEMPTION</strong> through our gifts and talents in poetry, music and spoken word to the world.</p>
                    </div>

                    <div class="pull-quote p-6 mt-8" data-aos="fade-up" data-aos-delay="200">
                        <p class="font-serif text-xl italic text-white font-light leading-relaxed">
                            "And you shall know the truth, and the truth shall make you free."
                        </p>
                        <p class="font-hand text-gold text-lg mt-2">— John 8:32</p>
                    </div>
                </div>

                <!-- Image with identity overlays -->
                <div class="img-frame" style="height:460px;" data-aos="fade-left" data-aos-duration="900" data-aos-delay="150">
                    <img src="https://static.wixstatic.com/media/521bf8_ca1f56b295d54b0aa5b5e4f0665b674d~mv2.jpg/v1/fill/w_966,h_644,al_c,q_85,usm_0.66_1.00_0.01,enc_auto/521bf8_ca1f56b295d54b0aa5b5e4f0665b674d~mv2.jpg"
                        alt="Biblical Perspective" style="opacity:.65;">
                    <div class="absolute inset-0 flex flex-col justify-end p-6 gap-3"
                        style="background:linear-gradient(to top,rgba(26,26,46,0.7) 0%,transparent 60%);">
                        <?php foreach (
                            [
                                ['Restorers of TRUTH', 'rgba(107,70,193,0.88)'],
                                ['Unashamed to Speak', 'rgba(168,135,26,0.88)'],
                                ['Grace unto us',      'rgba(26,106,168,0.88)'],
                            ] as $id
                        ): ?>
                            <span class="inline-flex items-center gap-2 self-start px-4 py-2 rounded-full font-sans font-semibold text-white text-sm"
                                style="background:<?= $id[1] ?>;backdrop-filter:blur(6px);">
                                <i class="fas fa-check-circle text-xs"></i> <?= $id[0] ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- ══════════════════════════════════
       MISSION & VISION
  ══════════════════════════════════ -->
    <section class="bg-white py-24 px-4">
        <div class="max-w-6xl mx-auto">

            <div class="text-center mb-16" data-aos="fade-up">
                <span class="font-hand text-gold text-2xl">What Drives Us</span>
                <h2 class="font-serif text-4xl sm:text-5xl font-bold text-ink mt-1">Mission &amp; Vision</h2>
                <div class="gold-line max-w-xs mx-auto mt-5"></div>
            </div>

            <div class="grid lg:grid-cols-2 gap-8">

                <!-- Mission -->
                <div class="lift-card rounded-3xl overflow-hidden shadow-sm"
                    style="border:1px solid rgba(107, 70, 193, 0);" data-aos="fade-up" data-aos-delay="0">
                    <div class="relative h-52 overflow-hidden" style="background:linear-gradient(135deg,#6B46C1,#4A2F8A);">
                        <img src="https://static.wixstatic.com/media/521bf8_513d5db66b9740378bcbdb1b6fb0789c~mv2.jpg/v1/fill/w_1145,h_644,al_c,q_85,usm_0.66_1.00_0.01,enc_auto/521bf8_513d5db66b9740378bcbdb1b6fb0789c~mv2.jpg"
                            alt="Mission" class="w-full h-full object-cover opacity-20">
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-4">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center mb-3"
                                style="background:rgba(255,255,255,0.18);border:2px solid rgba(255,255,255,0.35);">
                                <i class="fas fa-bullseye text-white text-2xl"></i>
                            </div>
                            <h3 class="font-serif text-3xl font-bold text-white">Mission</h3>
                            <p class="font-hand text-gold-light text-sm mt-1 opacity-90">Matthew 28:19–20 · 1 Timothy 2:4</p>
                        </div>
                    </div>
                    <div class="p-8 bg-white">
                        <p class="text-gray-600 font-body font-light text-base leading-relaxed mb-5">
                            To propagate the message of <strong class="text-ink font-semibold">REDEMPTION</strong> for all to be saved and be prepared for the coming of our Lord and Savior Jesus Christ through the creative arts — poetry, music, spoken word.
                        </p>
                        <div class="space-y-3">
                            <div class="flex gap-3 items-start p-4 rounded-xl" style="background:#FAF7F2;">
                                <i class="fas fa-sun text-gold mt-1 flex-shrink-0"></i>
                                <p class="text-gray-600 font-body text-sm leading-relaxed">In this increasingly dark world, we exist to restore light (truth) through the preaching of the Gospel, that men will be redeemed from sin, self and the systems of the world.</p>
                            </div>
                            <div class="flex gap-3 items-start p-4 rounded-xl" style="background:#FAF7F2;">
                                <i class="fas fa-cross text-purple mt-1 flex-shrink-0"></i>
                                <p class="text-gray-600 font-body text-sm leading-relaxed">"Our Lord and Saviour Jesus Christ is coming again! The church must prepare, souls must be saved!" <strong class="text-ink">THIS IS OUR MESSAGE!</strong></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vision -->
                <div class="lift-card rounded-3xl overflow-hidden shadow-sm"
                    style="border:1px solid rgba(212,175,55,0.2);" data-aos="fade-up" data-aos-delay="150">
                    <div class="relative h-52 overflow-hidden" style="background:linear-gradient(135deg,#A8871A,#D4AF37);">
                        <img src="https://static.wixstatic.com/media/521bf8_5b5283b848854b6087436506c9b352bf~mv2.jpg/v1/fill/w_1145,h_644,al_c,q_85,usm_0.66_1.00_0.01,enc_auto/521bf8_5b5283b848854b6087436506c9b352bf~mv2.jpg"
                            alt="Vision" class="w-full h-full object-cover opacity-20">
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-4">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center mb-3"
                                style="background:rgba(255,255,255,0.22);border:2px solid rgba(255,255,255,0.4);">
                                <i class="fas fa-eye text-white text-2xl"></i>
                            </div>
                            <h3 class="font-serif text-3xl font-bold text-white">Vision</h3>
                            <p class="font-hand text-white text-sm mt-1 opacity-85">Isaiah 40:3 · Galatians 4:19</p>
                        </div>
                    </div>
                    <div class="p-8 bg-white">
                        <p class="text-gray-600 font-body font-light text-base leading-relaxed mb-4">
                            To become a <strong class="text-ink font-semibold">voice (Isaiah 40:3)</strong>, effectively evangelizing the gospel through discipleship — raising believers to become ministers of redemption, taking the world with the Christ-message through creative arts.
                        </p>
                        <p class="text-gray-600 font-body font-light text-base leading-relaxed">
                            Through training and instruction, we aim to raise all believers to live the fullness of the Christ-life (Galatians 4:19) — becoming an <strong class="text-ink font-semibold">agency of redemption and restoration</strong>, projecting Christ to all men, touching lives and empowering people to lead and impact in every sphere of life.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- ══════════════════════════════════
       MINISTRIES
  ══════════════════════════════════ -->
    <section class="py-24 px-4" style="background:#FAF7F2;">
        <div class="max-w-6xl mx-auto">

            <div class="text-center mb-16" data-aos="fade-up">
                <span class="font-hand text-gold text-2xl">Our Creative Arms</span>
                <h2 class="font-serif text-4xl sm:text-5xl font-bold text-ink mt-1">Our Ministries</h2>
                <p class="text-gray-500 font-body font-light text-base mt-4 max-w-xl mx-auto">
                    As a community of creatives, our ministries are commissioned to preach Jesus Christ — the salvation of mankind — and inspire hope for purity and holiness.
                </p>
                <div class="gold-line max-w-xs mx-auto mt-6"></div>
            </div>

            <div class="grid sm:grid-cols-3 gap-8">
                <?php
                $mins = [
                    [
                        'img'   => 'https://static.wixstatic.com/media/521bf8_dda9d495fdf54dffaeb6b90d070166e7~mv2.jpg/v1/fill/w_966,h_644,al_c,q_85,usm_0.66_1.00_0.01,enc_auto/521bf8_dda9d495fdf54dffaeb6b90d070166e7~mv2.jpg',
                        'alt'   => 'Scribes Poetry',
                        'icon'  => 'fa-microphone-lines',
                        'grad'  => 'linear-gradient(135deg,#6B46C1,#4A2F8A)',
                        'title' => 'Scribes Poetry',
                        'desc'  => 'Established as the initial step in fulfilling our mission, spreading the gospel through poetry, spoken word and short stories. The first official meeting was held on May 22nd, 2017.',
                        'col'   => '#6B46C1',
                    ],
                    [
                        'img'   => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=700&q=75',
                        'alt'   => 'Scribes Worship',
                        'icon'  => 'fa-music',
                        'grad'  => 'linear-gradient(135deg,#D4AF37,#A8871A)',
                        'title' => 'Scribes Worship',
                        'desc'  => 'Overwhelmed by the gift of salvation found in Jesus, we have a heart for authentic worship — making room for believers to intimately fellowship with God through music and song.',
                        'col'   => '#A8871A',
                    ],
                    [
                        'img'   => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=700&q=75',
                        'alt'   => 'Scribes Khoros',
                        'icon'  => 'fa-choir',
                        'grad'  => 'linear-gradient(135deg,#2D9CDB,#1A6EA8)',
                        'title' => 'Scribes Khoros',
                        'desc'  => '',
                        'col'   => '#2D9CDB',
                    ],
                ];
                foreach ($mins as $i => $m): ?>
                    <div class="ministry-card bg-white shadow-sm lift-card" data-aos="fade-up" data-aos-delay="<?= $i * 110 ?>">
                        <div class="overflow-hidden" style="height:220px;">
                            <img src="<?= $m['img'] ?>" alt="<?= $m['alt'] ?>">
                        </div>
                        <div class="p-7">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4"
                                style="background:<?= $m['grad'] ?>">
                                <i class="fas <?= $m['icon'] ?> text-white text-lg"></i>
                            </div>
                            <h3 class="font-serif text-2xl font-bold text-ink mb-3"><?= $m['title'] ?></h3>
                            <p class="text-gray-500 font-body font-light text-sm leading-relaxed mb-5"><?= $m['desc'] ?></p>
                            <a href="<?= SITE_URL ?>/pages/about/ministries"
                                class="inline-flex items-center gap-2 font-sans font-semibold text-sm transition-all hover:gap-3"
                                style="color:<?= $m['col'] ?>;">
                                Learn more <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <!-- ══════════════════════════════════
       CAMPUS CHAPTERS
  ══════════════════════════════════ -->
    <section class="py-24 px-4 relative overflow-hidden" style="background:#1A1A2E;">

        <!-- Subtle BG -->
        <div class="absolute inset-0" style="background-image:url('https://static.wixstatic.com/media/98ecda_cce9f04b679046bb80afa0f2b60dac4e~mv2.jpg/v1/fill/w_1505,h_443,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/98ecda_cce9f04b679046bb80afa0f2b60dac4e~mv2.jpg');background-size:cover;background-position:center;opacity:0.2;"></div>
        <div class="gold-line absolute top-0 left-0 right-0" style="position:absolute;"></div>

        <div class="max-w-6xl mx-auto relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="font-hand text-gold text-2xl">By God's Grace</span>
                <h2 class="font-serif text-4xl sm:text-5xl font-bold text-white mt-1">Campus Chapters</h2>
                <p class="text-gray-400 font-body font-light text-base mt-4 max-w-2xl mx-auto">
                    We have extended our ministry to a number of universities in Ghana. Our campus chapters harbour several young talents, serving as a platform to nurture their gifts and propagate the gospel of Christ.
                </p>
                <div class="gold-line max-w-xs mx-auto mt-6"></div>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php
                $chs = [
                    ['name' => 'Scribes KNUST',  'school' => 'Kwame Nkrumah Univ. of Science & Technology', 'icon' => 'fa-flask',       'col' => '#9B7EDE'],
                    ['name' => 'Scribes UCC',    'school' => 'University of Cape Coast',                     'icon' => 'fa-anchor',      'col' => '#56CCF2'],
                    // ['name' => 'Scribes UHAS',   'school' => 'University of Health & Allied Sciences',       'icon' => 'fa-heart-pulse', 'col' => '#EB5757'],
                    ['name' => 'Scribes USA',   'school' => 'United States OF America',       'icon' => 'fa-heart-pulse', 'col' => '#EB5757'],
                    ['name' => 'Scribes LEGON',  'school' => 'University of Ghana, UPSA, GIMPA & Wisconsin', 'icon' => 'fa-landmark',    'col' => '#D4AF37'],
                ];
                foreach ($chs as $i => $ch): ?>
                    <div class="chapter-card" data-aos="fade-up" data-aos-delay="<?= $i * 90 ?>">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-5"
                            style="background:<?= $ch['col'] ?>1A;border:1.5px solid <?= $ch['col'] ?>55;">
                            <i class="fas <?= $ch['icon'] ?> text-xl" style="color:<?= $ch['col'] ?>"></i>
                        </div>
                        <h3 class="font-serif text-xl font-bold text-white mb-2"><?= $ch['name'] ?></h3>
                        <p class="text-gray-400 font-body text-xs font-light leading-relaxed"><?= $ch['school'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="text-center text-gray-500 font-body font-light text-sm mt-10 italic" data-aos="fade-up">
                We accept members from all schools, universities or campuses.
            </p>

            <div class="text-center mt-8" data-aos="fade-up">
                <a href="<?= SITE_URL ?>/pages/about/chapters"
                    class="inline-flex items-center gap-2 px-8 py-4 rounded-full font-sans font-semibold text-ink text-base transition-all hover:scale-105"
                    style="background:linear-gradient(135deg,#D4AF37,#F2D97A);">
                    <i class="fas fa-map-marker-alt"></i> View All Chapters
                </a>
            </div>
        </div>
    </section>


    <!-- ══════════════════════════════════
       CTA
  ══════════════════════════════════ -->
    <section class="cta-band py-28 px-4 relative">
        <div class="relative z-10 max-w-2xl mx-auto text-center" data-aos="fade-up">
            <span class="font-hand text-gold text-3xl block mb-4">Unashamed to Speak</span>
            <h2 class="font-serif text-4xl sm:text-5xl font-bold text-white leading-tight mb-6">
                Ready to be a<br>
                <span class="italic font-light" style="color:#F2D97A;">Restorer of Truth?</span>
            </h2>
            <p class="text-gray-300 font-body font-light text-lg leading-relaxed mb-10 max-w-xl mx-auto">
                Whether you're a poet, musician, or simply someone who loves the Lord — there is a place for you in this community. Join us as we take the Christ-message to the world.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?= SITE_URL ?>/pages/connect/volunteer"
                    class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full font-sans font-semibold text-ink text-base transition-all hover:scale-105"
                    style="background:linear-gradient(135deg,#D4AF37,#F2D97A);">
                    <i class="fas fa-user-plus"></i> Join / Volunteer
                </a>
                <a href="<?= SITE_URL ?>/pages/connect/invite"
                    class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full font-sans font-semibold text-white text-base border-2 border-white transition-all hover:bg-white hover:text-ink">
                    <i class="fas fa-paper-plane"></i> Invite Scribes Global
                </a>
            </div>
        </div>
    </section>


    <!-- ══════════════════════════════════
       FOOTER
  ══════════════════════════════════ -->
    <footer style="background:#16213E;" class="text-gray-400 py-16 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">

                <div class="md:col-span-1">
                    <div class="font-serif text-2xl font-bold text-white mb-3">Scribes Global</div>
                    <p class="text-sm font-body font-light leading-relaxed text-gray-500 mb-4">
                        A non-profit, non-denominational evangelistic ministry preaching the gospel through creative arts.
                    </p>
                    <div class="space-y-1 text-sm font-body font-light mb-5">
                        <p><a href="mailto:info@scribesglobal.com" class="hover:text-gold transition-colors">info@scribesglobal.com</a></p>
                        <p><a href="tel:+233546296188" class="hover:text-gold transition-colors">054 629 6188</a></p>
                        <p><a href="tel:+233209315447" class="hover:text-gold transition-colors">020 931 5447</a></p>
                    </div>
                    <div class="flex gap-3">
                        <?php foreach (
                            [
                                ['fa-facebook-f', 'https://www.facebook.com/scribespoetry'],
                                ['fa-twitter',   'https://twitter.com/scribes_poetry'],
                                ['fa-instagram', 'https://www.instagram.com/scribes_poetry/'],
                                ['fa-youtube',   'https://www.youtube.com/channel/UCFfEhYBSqsSu7Bg2nZUGsbg'],
                                ['fa-tiktok',    'https://bit.ly/ScribesTiktok'],
                            ] as [$ic, $url]
                        ): ?>
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
                        <li><a href="<?= SITE_URL ?>/pages/about/ministries" class="hover:text-gold transition-colors">Ministries</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/about/chapters" class="hover:text-gold transition-colors">Chapters / Campuses</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-serif text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm font-body font-light">
                        <li><a href="<?= SITE_URL ?>/pages/events" class="hover:text-gold transition-colors">Events</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/projects/heal" class="hover:text-gold transition-colors">Project H.E.A.L</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/media" class="hover:text-gold transition-colors">Media</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/give" class="hover:text-gold transition-colors">Give</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-serif text-lg font-semibold mb-4">Connect</h4>
                    <ul class="space-y-2 text-sm font-body font-light">
                        <li><a href="<?= SITE_URL ?>/pages/connect/invite" class="hover:text-gold transition-colors">Invite Scribes Global</a></li>
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
        AOS.init({
            once: true,
            duration: 860,
            easing: 'ease-out-quart'
        });

        gsap.registerPlugin(ScrollTrigger);
        gsap.from('#hero-headline', {
            y: 80,
            opacity: 0,
            duration: 1.4,
            ease: 'power4.out',
            delay: 0.15,
        });

        window.addEventListener('scroll', () => {
            const y = window.scrollY;
            document.querySelectorAll('.ink-drop').forEach((el, i) => {
                el.style.transform = `translateY(${y * (0.06 + i * 0.03)}px)`;
            });
        });
    </script>

</body>

</html>