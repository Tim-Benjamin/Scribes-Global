<?php
$pageTitle = 'Scribes Global - Creative Arts Ministry';
$pageDescription = 'A creative community of poets, worship leaders, and artists spreading the Gospel through creative arts.';
$pageCSS = 'home';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';


// UPDATED: More flexible event queries that show events from DB
$db = new Database();
$conn = $db->connect();

// ✅ FIXED: Get upcoming/current events (more flexible)
$eventsStmt = $conn->prepare("
    SELECT * FROM events 
    WHERE status IN ('upcoming', 'ongoing')
    AND start_date > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY start_date ASC 
    LIMIT 6
");
$eventsStmt->execute();
$events = $eventsStmt->fetchAll();

// If no upcoming events, get recently created ones
if (empty($events)) {
    $eventsStmt = $conn->prepare("
        SELECT * FROM events 
        WHERE status IN ('upcoming', 'ongoing', 'completed')
        ORDER BY created_at DESC
        LIMIT 6
    ");
    $eventsStmt->execute();
    $events = $eventsStmt->fetchAll();
}

$blogStmt = $conn->prepare("
    SELECT bp.*, u.first_name, u.last_name, u.profile_photo 
    FROM blog_posts bp
    JOIN users u ON bp.author_id = u.id
    WHERE bp.status = 'published'
    ORDER BY bp.published_at DESC
    LIMIT 6
");
$blogStmt->execute();
$blogPosts = $blogStmt->fetchAll();

$statsStmt = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE status = 'active') as total_members,
        (SELECT COUNT(*) FROM events WHERE status IN ('upcoming', 'ongoing', 'completed')) as total_events,
        (SELECT COUNT(*) FROM chapters WHERE status = 'active') as total_chapters,
        (SELECT COUNT(*) FROM media_content WHERE status = 'approved') as total_content
");
$stats = $statsStmt->fetch();

//NEW HOMEPAGE VIDEOS LOGIC

$videosStmt = $conn->prepare("
    SELECT * FROM homepage_videos 
    WHERE status = 'active'
    ORDER BY row_title ASC, sort_order ASC
");
$videosStmt->execute();
$allVideos = $videosStmt->fetchAll();

// Group videos by row_title
$videosByRow = [];
foreach ($allVideos as $video) {
    $row = $video['row_title'] ?? 'Featured Videos';
    if (!isset($videosByRow[$row])) {
        $videosByRow[$row] = [];
    }
    $videosByRow[$row][] = $video;
}
?>

<style>
/* =============================================
   GOOGLE FONTS
   ============================================= */
@import url('https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700;900&family=Outfit:wght@300;400;500;600;700;900&display=swap');

/* =============================================
   CSS VARIABLES & RESET
   ============================================= */
:root {
    --deep-navy: #121026;
    --royal-blue: #092573;
    --blue-mid: #1A3FA8;
    --blue-light: #E8ECFA;
    --white: #ffffff;
    --off-white: #F8F9FF;
    --light-bg: #F0F2FB;
    --gray-muted: #6B7280;
    --gray-light: #9CA3AF;
    --border: rgba(9, 37, 115, 0.1);
    --text-dark: #0D0E1A;
    --text-mid: #374151;
    --font-display: 'Cinzel Decorative', serif;
    --font-body: 'Outfit', sans-serif;
    --transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
    --glow-blue: rgba(9, 37, 115, 0.15);
    --shadow-card: 0 2px 20px rgba(9, 37, 115, 0.07);
    --shadow-hover: 0 12px 40px rgba(9, 37, 115, 0.16);
}

*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: var(--font-body);
    background: var(--white);
    color: var(--text-dark);
    overflow-x: hidden;
}



/* =============================================
   THREE.JS CANVAS (fixed background)
   ============================================= */


/* =============================================
   HERO SECTION — VIDEO BACKGROUND
   ============================================= */
.hero-section {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    z-index: 1;
    background: var(--white);
}

/* Video bg */
.hero-video-wrap {
    position: absolute;
    inset: 0;
    z-index: 0;
}

.hero-video-wrap video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Hero video overlay — bright white tint so navy text reads clearly */
.hero-video-wrap::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(160deg,
            rgba(255, 255, 255, 0.19) 0%,
            rgba(240, 242, 251, 0.15) 40%,
            rgba(255, 255, 255, 0.16) 100%);
    z-index: 1;
}

.hero-video-wrap::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 100%, rgba(9, 37, 115, 0.06) 0%, transparent 70%);
    z-index: 2;
}

/* Subtle dot texture overlay */
.hero-scanlines {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(9, 37, 115, 0.25) 1px, transparent 1px);
    background-size: 28px 28px;
    z-index: 3;
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 4;
    text-align: center;
    padding: 2rem 1.5rem;
    max-width: 1000px;
    margin: 0 auto;
}

.hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1.5rem;
    border: 1px solid rgba(9, 37, 115, 0.4);
    border-radius: 100px;
    color: var(--royal-blue);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
    background: rgba(9, 37, 115, 0.07);
    opacity: 0;
    animation: fadeSlideUp 0.8s 0.3s forwards;
}

.hero-eyebrow::before,
.hero-eyebrow::after {
    content: '';
    width: 20px;
    height: 1px;
    background: var(--royal-blue);
}

.hero-title {
    font-family: var(--font-display);
    font-size: clamp(2rem, 6vw, 5rem);
    font-weight: 900;
    line-height: 1.1;
    color: var(--deep-navy);
    margin-bottom: 1.5rem;
    opacity: 0;
    animation: fadeSlideUp 0.9s 0.5s forwards;
}

.hero-title .gold {
    background: linear-gradient(135deg, var(--royal-blue) 0%, #1A3FA8 60%, var(--deep-navy) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative;
}

.hero-subtitle {
    font-size: clamp(1rem, 2vw, 1.25rem);
    line-height: 1.8;
    color: var(--gray-muted);
    max-width: 680px;
    margin: 0 auto 2.5rem;
    opacity: 0;
    animation: fadeSlideUp 0.9s 0.7s forwards;
}

.hero-cta {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 4rem;
    opacity: 0;
    animation: fadeSlideUp 0.9s 0.9s forwards;
}

.btn-primary-gold {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.85rem 2rem;
    background: linear-gradient(135deg, var(--deep-navy), var(--royal-blue));
    color: var(--white);
    border-radius: 100px;
    font-weight: 700;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
    text-decoration: none;
    transition: var(--transition);
    box-shadow: 0 4px 20px var(--glow-blue);
    border: none;
    cursor: pointer;
}

.btn-primary-gold:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 8px 40px rgba(9, 37, 115, 0.4);
}

.btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.85rem 2rem;
    background: rgba(18, 16, 38, 0.06);
    color: var(--deep-navy);
    border-radius: 100px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    border: 1.5px solid rgba(18, 16, 38, 0.2);
    transition: var(--transition);
}

.btn-ghost:hover {
    background: var(--deep-navy);
    color: var(--white);
    border-color: var(--deep-navy);
    transform: translateY(-2px);
}

/* Hero Stats */
.hero-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    flex-wrap: wrap;
    padding-top: 2.5rem;
    border-top: 1px solid rgba(18, 16, 38, 0.12);
    opacity: 0;
    animation: fadeSlideUp 0.9s 1.1s forwards;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-family: var(--font-display);
    font-size: 2rem;
    font-weight: 900;
    background: linear-gradient(135deg, var(--deep-navy), var(--royal-blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: block;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--gray-muted);
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-top: 0.25rem;
}

/* Scroll indicator */
.scroll-indicator {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 4;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    opacity: 0;
    animation: fadeIn 1s 2s forwards;
}

.scroll-indicator span {
    font-size: 0.65rem;
    letter-spacing: 3px;
    color: var(--gray-muted);
    text-transform: uppercase;
}

.scroll-mouse {
    width: 26px;
    height: 40px;
    border: 2px solid rgba(18, 16, 38, 0.25);
    border-radius: 13px;
    display: flex;
    justify-content: center;
    padding-top: 6px;
}

.scroll-mouse::before {
    content: '';
    width: 4px;
    height: 8px;
    background: var(--royal-blue);
    border-radius: 2px;
    animation: scrollDot 1.5s ease-in-out infinite;
}

/* =============================================
   SECTION COMMON STYLES
   ============================================= */
.section-wrapper {
    position: relative;
    z-index: 1;
    padding: 6rem 0;
}

.section-wrapper.dark {
    background: var(--white);
}

.section-wrapper.darker {
    background: var(--off-white);
}

.section-wrapper.blue-tint {
    background: var(--blue-light);
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
}

.section-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--royal-blue);
    margin-bottom: 1rem;
}

.section-eyebrow::before {
    content: '';
    width: 24px;
    height: 2px;
    background: var(--royal-blue);
    border-radius: 1px;
}

.section-title {
    font-family: var(--font-display);
    font-size: clamp(1.8rem, 4vw, 3rem);
    font-weight: 700;
    color: var(--deep-navy);
    margin-bottom: 1rem;
    line-height: 1.15;
}

.section-desc {
    color: var(--gray-muted);
    font-size: 1.05rem;
    line-height: 1.7;
    max-width: 600px;
}

.section-header {
    margin-bottom: 3rem;
}

.section-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 3rem;
}

/* =============================================
   HORIZONTAL SCROLL TRACK
   ============================================= */
.h-scroll-outer {
    overflow: hidden;
    margin: 0 -2rem;
    padding: 0 2rem;
    -webkit-mask-image: linear-gradient(to right, transparent 0%, black 4%, black 96%, transparent 100%);
    mask-image: linear-gradient(to right, transparent 0%, black 4%, black 96%, transparent 100%);
}

.h-scroll-track {
    display: flex;
    gap: 1.5rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 1.5rem;
    cursor: grab;
}

.h-scroll-track:active {
    cursor: grabbing;
}

.h-scroll-track::-webkit-scrollbar {
    display: none;
}

/* Scroll nav buttons */
.scroll-nav {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
    justify-content: flex-end;
}

.scroll-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--white);
    border: 1.5px solid rgba(18, 16, 38, 0.15);
    color: var(--deep-navy);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow-card);
    font-size: 0.9rem;
}

.scroll-btn:hover {
    background: var(--deep-navy);
    color: var(--white);
    border-color: var(--deep-navy);
    transform: scale(1.1);
}

/* Scroll dots */
.scroll-dots {
    display: flex;
    gap: 0.5rem;
    margin-top: 1.25rem;
    justify-content: center;
}

.scroll-dot {
    width: 6px;
    height: 6px;
    border-radius: 3px;
    background: rgba(18, 16, 38, 0.15);
    transition: all 0.3s ease;
    cursor: pointer;
}

.scroll-dot.active {
    width: 20px;
    background: var(--royal-blue);
}

/* =============================================
   VIDEO CARDS
   ============================================= */
.video-card {
    flex: 0 0 320px;
    scroll-snap-align: start;
    background: var(--white);
    border: 1px solid rgba(18, 16, 38, 0.08);
    border-radius: 20px;
    overflow: hidden;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    box-shadow: var(--shadow-card);
}

@media (min-width: 768px) {
    .video-card {
        flex: 0 0 400px;
    }
}

@media (min-width: 1024px) {
    .video-card {
        flex: 0 0 460px;
    }
}

.video-card:hover {
    border-color: rgba(9, 37, 115, 0.3);
    transform: translateY(-6px);
    box-shadow: var(--shadow-hover);
}

.video-thumb {
    position: relative;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.video-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.video-card:hover .video-thumb img {
    transform: scale(1.05);
}

.video-thumb-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(18, 16, 38, 0.9) 0%, transparent 50%);
}

.play-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.9);
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--royal-blue);
    font-size: 1.25rem;
    transition: var(--transition);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.video-card:hover .play-btn {
    transform: translate(-50%, -50%) scale(1);
    background: var(--deep-navy);
    color: var(--white);
}

.video-duration {
    position: absolute;
    bottom: 0.75rem;
    right: 0.75rem;
    background: rgba(0, 0, 0, 0.75);
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    letter-spacing: 0.5px;
}

.video-body {
    padding: 1.25rem;
}

.video-body h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--deep-navy);
    margin-bottom: 0.5rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.video-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.8rem;
    color: var(--gray-muted);
}

.video-meta i {
    color: var(--royal-blue);
    font-size: 0.7rem;
}

/* =============================================
   EVENT CARDS
   ============================================= */
.event-card {
    flex: 0 0 300px;
    scroll-snap-align: start;
    background: var(--white);
    border: 1px solid rgba(18, 16, 38, 0.08);
    border-radius: 20px;
    overflow: hidden;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-card);
}

@media (min-width: 768px) {
    .event-card {
        flex: 0 0 380px;
    }
}

.event-card:hover {
    border-color: rgba(9, 37, 115, 0.3);
    transform: translateY(-6px);
    box-shadow: var(--shadow-hover);
}

.event-img-wrap {
    position: relative;
    height: 220px;
    overflow: hidden;
}

.event-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.event-card:hover .event-img-wrap img {
    transform: scale(1.05);
}

.event-date-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: var(--deep-navy);
    color: var(--white);
    padding: 0.4rem 0.9rem;
    border-radius: 100px;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.event-body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.event-body h3 {
    font-family: var(--font-display);
    font-size: 1.1rem;
    color: var(--deep-navy);
    margin-bottom: 0.75rem;
    line-height: 1.3;
}

.event-body p {
    color: var(--gray-muted);
    font-size: 0.875rem;
    line-height: 1.6;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 1rem;
}

.event-meta-row {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    margin-bottom: 1.25rem;
}

.event-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--gray-muted);
}

.event-meta-item i {
    color: var(--royal-blue);
    width: 14px;
}

.btn-event {
    display: block;
    text-align: center;
    padding: 0.7rem 1.5rem;
    background: var(--deep-navy);
    border: 1.5px solid var(--deep-navy);
    color: var(--white);
    border-radius: 100px;
    font-weight: 600;
    font-size: 0.85rem;
    text-decoration: none;
    transition: var(--transition);
}

.btn-event:hover {
    background: var(--royal-blue);
    border-color: var(--royal-blue);
    box-shadow: 0 4px 20px rgba(9, 37, 115, 0.3);
}

/* =============================================
   BLOG CARDS
   ============================================= */
.blog-card {
    flex: 0 0 290px;
    scroll-snap-align: start;
    background: var(--white);
    border: 1px solid rgba(18, 16, 38, 0.08);
    border-radius: 20px;
    overflow: hidden;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-card);
}

@media (min-width: 768px) {
    .blog-card {
        flex: 0 0 360px;
    }
}

.blog-card:hover {
    border-color: rgba(9, 37, 115, 0.25);
    transform: translateY(-6px);
    box-shadow: var(--shadow-hover);
}

.blog-img-wrap {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.blog-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.blog-card:hover .blog-img-wrap img {
    transform: scale(1.06);
}

.blog-cat {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: var(--deep-navy);
    color: var(--white);
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 0.3rem 0.75rem;
    border-radius: 100px;
}

.blog-body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.blog-body h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--deep-navy);
    margin-bottom: 0.75rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.blog-body h3 a {
    color: inherit;
    text-decoration: none;
    transition: color 0.2s;
}

.blog-body h3 a:hover {
    color: var(--royal-blue);
}

.blog-excerpt {
    color: var(--gray-muted);
    font-size: 0.85rem;
    line-height: 1.6;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 1.25rem;
}

.blog-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1rem;
    border-top: 1px solid rgba(18, 16, 38, 0.07);
}

.blog-author {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.blog-author-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
    background: var(--royal-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.75rem;
    color: white;
    overflow: hidden;
}

.blog-author-info .name {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--deep-navy);
}

.blog-author-info .date {
    font-size: 0.7rem;
    color: var(--gray-light);
}

.blog-stats {
    display: flex;
    gap: 0.75rem;
    font-size: 0.75rem;
    color: var(--gray-muted);
}

.blog-stats span {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.blog-stats i {
    color: var(--royal-blue);
    font-size: 0.65rem;
}

/* =============================================
   VIEW ALL LINK
   ============================================= */
.view-all-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--royal-blue);
    font-weight: 700;
    font-size: 0.875rem;
    text-decoration: none;
    letter-spacing: 0.5px;
    transition: var(--transition);
}

.view-all-link:hover {
    gap: 0.85rem;
}

.view-all-link i {
    font-size: 0.75rem;
}

/* =============================================
   TESTIMONIALS SECTION
   ============================================= */
.testimonials-section {
    position: relative;
    z-index: 1;
    padding: 6rem 0;
    background: var(--light-bg);
}

.testimonial-slider {
    max-width: 960px;
    margin: 0 auto;
}

.testimonial-card {
    background: var(--white);
    border: 1px solid rgba(18, 16, 38, 0.07);
    border-radius: 24px;
    padding: 3rem 2.5rem;
    text-align: center;
    box-shadow: var(--shadow-card);
    position: relative;
}

.testimonial-card::before {
    content: '"';
    font-family: var(--font-display);
    font-size: 8rem;
    color: var(--royal-blue);
    opacity: 0.08;
    position: absolute;
    top: -1rem;
    left: 2rem;
    line-height: 1;
}

.testimonial-quote {
    font-size: 1.15rem;
    line-height: 1.8;
    color: var(--text-mid);
    font-style: italic;
    margin-bottom: 2.5rem;
    position: relative;
    z-index: 1;
}

.testimonial-author {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.testimonial-author img {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--royal-blue);
}

.testimonial-author h5 {
    font-weight: 700;
    color: var(--deep-navy);
    margin-bottom: 0.2rem;
}

.testimonial-author p {
    font-size: 0.8rem;
    color: var(--royal-blue);
    letter-spacing: 1px;
}

/* =============================================
   CTA SECTION
   ============================================= */
.cta-section {
    position: relative;
    z-index: 1;
    padding: 8rem 2rem;
    text-align: center;
    overflow: hidden;
}

.cta-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--deep-navy) 0%, var(--royal-blue) 55%, #1A3FA8 100%);
    z-index: 0;
}

.cta-bg::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 15% 50%, rgba(255, 255, 255, 0.06) 0%, transparent 55%),
        radial-gradient(circle at 85% 50%, rgba(255, 255, 255, 0.04) 0%, transparent 55%);
}

.cta-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px);
    background-size: 32px 32px;
}

.cta-content {
    position: relative;
    z-index: 1;
    max-width: 780px;
    margin: 0 auto;
}

.cta-title {
    font-family: var(--font-display);
    font-size: clamp(1.8rem, 4vw, 3rem);
    color: var(--white);
    margin-bottom: 1.5rem;
}

.cta-desc {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.75);
    line-height: 1.7;
    margin-bottom: 2.5rem;
}

.cta-btns {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* CTA buttons — inverted for the dark bg */
.cta-section .btn-primary-gold {
    background: var(--white);
    color: var(--deep-navy);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.cta-section .btn-primary-gold:hover {
    background: var(--off-white);
    box-shadow: 0 8px 40px rgba(0, 0, 0, 0.3);
}

.cta-section .btn-ghost {
    background: rgba(255, 255, 255, 0.08);
    color: var(--white);
    border-color: rgba(255, 255, 255, 0.3);
}

.cta-section .btn-ghost:hover {
    background: rgba(255, 255, 255, 0.18);
    color: var(--white);
    border-color: rgba(255, 255, 255, 0.6);
}

/* =============================================
   VIDEO MODAL
   ============================================= */
.video-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.96);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    backdrop-filter: blur(8px);
}

.video-modal.active {
    display: flex;
}

.video-modal-inner {
    position: relative;
    width: 100%;
    max-width: 1100px;
    aspect-ratio: 16/9;
}

.video-modal-close {
    position: absolute;
    top: -52px;
    right: 0;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.video-modal-close:hover {
    background: var(--royal-blue);
    color: var(--white);
    border-color: var(--royal-blue);
}

.video-modal iframe {
    width: 100%;
    height: 100%;
    border-radius: 16px;
    border: none;
}

/* =============================================
   PARALLAX DIVIDER
   ============================================= */
.parallax-divider {
    position: relative;
    z-index: 1;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgb(0, 20, 75), transparent);
    margin: 0;
}

/* =============================================
   ANIMATIONS
   ============================================= */
@keyframes fadeSlideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 0.8;
    }
}

@keyframes scrollDot {

    0%,
    100% {
        transform: translateY(0);
        opacity: 1;
    }

    50% {
        transform: translateY(8px);
        opacity: 0;
    }
}

/* Reveal on scroll */
.reveal {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}

.reveal.visible {
    opacity: 1;
    transform: translateY(0);
}

.reveal-left {
    opacity: 0;
    transform: translateX(-40px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}

.reveal-left.visible {
    opacity: 1;
    transform: translateX(0);
}

/* Glitch text effect on hero */
@keyframes glitch1 {

    0%,
    100% {
        clip-path: inset(0 0 98% 0);
        transform: translate(-2px, 0);
    }

    20% {
        clip-path: inset(30% 0 58% 0);
        transform: translate(2px, 0);
    }

    40% {
        clip-path: inset(60% 0 28% 0);
        transform: translate(-1px, 0);
    }
}

/* Swiper custom */
.swiper-pagination-bullet {
    background: rgba(18, 16, 38, 0.2) !important;
    width: 6px !important;
    height: 6px !important;
    border-radius: 3px !important;
    transition: all 0.3s !important;
}

.swiper-pagination-bullet-active {
    background: var(--royal-blue) !important;
    width: 20px !important;
}

/* Responsive tweaks */
@media (max-width: 768px) {
    .hero-stats {
        gap: 2rem;
    }

    .stat-number {
        font-size: 1.6rem;
    }

    .section-wrapper {
        padding: 4rem 0;
    }
}

/* =============================================
   HOMEPAGE VIDEO CARDS (6 per row, smaller)
   ============================================= */
.homepage-video-track .video-card {
    flex: 0 0 calc((100% - 7.5rem) / 6);
    min-width: 180px;
    max-width: 220px;
}

@media (max-width: 1400px) {
    .homepage-video-track .video-card {
        flex: 0 0 calc((100% - 6rem) / 5);
        max-width: 240px;
    }
}

@media (max-width: 1024px) {
    .homepage-video-track .video-card {
        flex: 0 0 calc((100% - 4.5rem) / 4);
        max-width: 240px;
    }
}

@media (max-width: 768px) {
    .homepage-video-track .video-card {
        flex: 0 0 calc((100% - 3rem) / 3);
        max-width: 200px;
    }
}

@media (max-width: 480px) {
    .homepage-video-track .video-card {
        flex: 0 0 calc((100% - 1.5rem) / 2);
        max-width: 160px;
    }
}

.video-description {
    font-size: 0.75rem;
    color: var(--gray-muted);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin: 0.5rem 0;
}

.video-body h3 {
    font-size: 0.9rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.video-card .play-btn {
    width: 48px;
    height: 48px;
    font-size: 1rem;
}

.video-card:hover .play-btn {
    width: 56px;
    height: 56px;
}
</style>


<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="hero-section">
    <!-- Video Background -->
    <div class="hero-video-wrap">
        <video autoplay muted loop playsinline poster="<?= ASSETS_PATH ?>images/hero-poster.jpg">
            <!-- Add your video sources here: -->
            <source src="<?= ASSETS_PATH ?>videos/scribes.mp4" type="video/mp4">
            <!-- Fallback: the CSS overlay handles graceful no-video display -->
        </video>
    </div>
    <div class="hero-scanlines"></div>

    <div class="scroll-indicator">
        <span>Scroll</span>
        <div class="scroll-mouse"></div>
    </div>
</section>

<div class="parallax-divider"></div>


<?php foreach ($videosByRow as $rowTitle => $rowVideos): ?>
<section class="section-wrapper dark" id="videos-section">
    <div class="container">
        <div class="section-header-row reveal">
            <div>
                <div class="section-eyebrow">Latest Content</div>
                <h2 class="section-title"><?= htmlspecialchars($rowTitle) ?></h2>
                <!-- <p class="section-desc">Powerful performances, worship sessions, and testimonies from our community</p> -->
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:1rem;">
                <a href="<?= SITE_URL ?>/pages/media" class="view-all-link">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
                <div class="scroll-nav" data-track-id="video-track-<?= sanitizeId($rowTitle) ?>">
                    <button class="scroll-btn" onclick="scrollTrack('video-track-<?= sanitizeId($rowTitle) ?>', -1)"><i
                            class="fas fa-chevron-left"></i></button>
                    <button class="scroll-btn" onclick="scrollTrack('video-track-<?= sanitizeId($rowTitle) ?>', 1)"><i
                            class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <div class="h-scroll-outer">
            <div class="h-scroll-track homepage-video-track" id="video-track-<?= sanitizeId($rowTitle) ?>">
                <?php if (count($rowVideos) > 0): ?>
                <?php foreach ($rowVideos as $video): ?>
                <?php
                        // Extract YouTube ID
                        $youtubeId = $video['youtube_url'];
                        ?>
                <div class="video-card" data-video-id="<?= htmlspecialchars($youtubeId) ?>">
                    <div class="video-thumb">
                        <img src="https://img.youtube.com/vi/<?= htmlspecialchars($youtubeId) ?>/mqdefault.jpg"
                            alt="<?= htmlspecialchars($video['title']) ?>" loading="lazy"
                            onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22320%22 height=%22180%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22320%22 height=%22180%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2216%22 fill=%22%23999%22%3EVideo Thumbnail%3C/text%3E%3C/svg%3E'">
                        <div class="video-thumb-overlay"></div>
                        <div class="play-btn"><i class="fas fa-play"></i></div>
                    </div>
                    <div class="video-body">
                        <h3><?= htmlspecialchars($video['title']) ?></h3>
                        <p class="video-description"><?= htmlspecialchars(substr($video['description'], 0, 80)) ?>...
                        </p>
                        <div class="video-meta">
                            <span><i class="fas fa-calendar"></i>
                                <?= date('M d, Y', strtotime($video['video_date'])) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div style="padding: 2rem; text-align: center; color: #999;">
                    No videos available
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="scroll-dots" id="video-dots-<?= sanitizeId($rowTitle) ?>"></div>
    </div>
</section>

<div class="parallax-divider"></div>
<?php endforeach; ?>

<?php
// Helper function to sanitize ID
function sanitizeId($str) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '-', strtolower($str));
}
?>



<!-- ============================================================
     EVENTS SECTION — HORIZONTAL SCROLL
     ============================================================ -->
<section class="section-wrapper darker" id="events-section">
    <div class="container">
        <div class="section-header-row reveal">
            <div>
                <div class="section-eyebrow">What's Happening</div>
                <h2 class="section-title">Upcoming Events</h2>
                <p class="section-desc">Join us for powerful gatherings, workshops, and community events</p>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:1rem;">
                <a href="<?= SITE_URL ?>/pages/events" class="view-all-link">
                    All Events <i class="fas fa-arrow-right"></i>
                </a>
                <div class="scroll-nav">
                    <button class="scroll-btn" onclick="scrollTrack('event-track', -1)"><i
                            class="fas fa-chevron-left"></i></button>
                    <button class="scroll-btn" onclick="scrollTrack('event-track', 1)"><i
                            class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <div class="h-scroll-outer">
            <div class="h-scroll-track" id="event-track">
                <?php if (count($events) > 0): ?>
                <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div class="event-img-wrap">
                        <img src="<?= $event['hero_image'] ? ASSETS_PATH . 'images/uploads/' . $event['hero_image'] : ASSETS_PATH . 'images/placeholder-event.jpg' ?>"
                            alt="<?= htmlspecialchars($event['title']) ?>" loading="lazy">
                        <div class="event-date-badge">
                            <?= date('M d, Y', strtotime($event['start_date'])) ?>
                        </div>
                    </div>
                    <div class="event-body">
                        <h3><?= htmlspecialchars($event['title']) ?></h3>
                        <p><?= htmlspecialchars(substr($event['description'], 0, 120)) ?>...</p>
                        <div class="event-meta-row">
                            <div class="event-meta-item">
                                <i class="fas fa-clock"></i>
                                <span><?= date('g:i A', strtotime($event['start_date'])) ?></span>
                            </div>
                            <div class="event-meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($event['location']) ?></span>
                            </div>
                        </div>
                        <a href="<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>" class="btn-event">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <!-- Placeholder cards when no events -->
                <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="event-card">
                    <div class="event-img-wrap"
                        style="background: var(--blue-light); display:flex; align-items:center; justify-content:center; height:220px;">
                        <i class="fas fa-calendar-star" style="font-size:3rem; color: rgba(9,37,115,0.2);"></i>
                    </div>
                    <div class="event-body">
                        <h3>Coming Soon</h3>
                        <p>Exciting events are being planned. Check back soon for updates on upcoming gatherings and
                            workshops.</p>
                        <a href="<?= SITE_URL ?>/pages/events" class="btn-event">Browse Events</a>
                    </div>
                </div>
                <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="scroll-dots" id="event-dots"></div>
    </div>
</section>

<div class="parallax-divider"></div>

<!-- ============================================================
     BLOG SECTION — HORIZONTAL SCROLL
     ============================================================ -->
<section class="section-wrapper dark" id="blog-section">
    <div class="container">
        <div class="section-header-row reveal">
            <div>
                <div class="section-eyebrow">Inspiration & Insights</div>
                <h2 class="section-title">Latest From Our Blog</h2>
                <p class="section-desc">Inspiring stories, devotionals, and creative insights from our community</p>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:1rem;">
                <a href="<?= SITE_URL ?>/pages/blog" class="view-all-link">
                    All Posts <i class="fas fa-arrow-right"></i>
                </a>
                <div class="scroll-nav">
                    <button class="scroll-btn" onclick="scrollTrack('blog-track', -1)"><i
                            class="fas fa-chevron-left"></i></button>
                    <button class="scroll-btn" onclick="scrollTrack('blog-track', 1)"><i
                            class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <div class="h-scroll-outer">
            <div class="h-scroll-track" id="blog-track">
                <?php if (count($blogPosts) > 0): ?>
                <?php foreach ($blogPosts as $post): ?>
                <div class="blog-card">
                    <div class="blog-img-wrap">
                        <img src="<?= $post['featured_image'] ? ASSETS_PATH . 'images/uploads/' . $post['featured_image'] : ASSETS_PATH . 'images/placeholder-blog.jpg' ?>"
                            alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                        <?php if ($post['category']): ?>
                        <div class="blog-cat"><?= htmlspecialchars($post['category']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="blog-body">
                        <h3>
                            <a href="<?= SITE_URL ?>/pages/blog/post?slug=<?= $post['slug'] ?>">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </h3>
                        <p class="blog-excerpt"><?= htmlspecialchars(substr(strip_tags($post['content']), 0, 120)) ?>...
                        </p>
                        <div class="blog-foot">
                            <div class="blog-author">
                                <?php if ($post['profile_photo']): ?>
                                <img class="blog-author-avatar"
                                    src="<?= ASSETS_PATH ?>images/uploads/<?= $post['profile_photo'] ?>" alt="">
                                <?php else: ?>
                                <div class="blog-author-avatar"><?= strtoupper(substr($post['first_name'], 0, 1)) ?>
                                </div>
                                <?php endif; ?>
                                <div class="blog-author-info">
                                    <div class="name">
                                        <?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></div>
                                    <div class="date"><?= date('M d, Y', strtotime($post['published_at'])) ?></div>
                                </div>
                            </div>
                            <div class="blog-stats">
                                <span><i class="fas fa-heart"></i> <?= $post['likes'] ?></span>
                                <span><i class="fas fa-eye"></i> <?= $post['views'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="blog-card">
                    <div class="blog-img-wrap"
                        style="background: var(--blue-light); height:200px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-pen-nib" style="font-size:2.5rem; color: rgba(9,37,115,0.2);"></i>
                    </div>
                    <div class="blog-body">
                        <h3><a href="#">Coming Soon</a></h3>
                        <p class="blog-excerpt">Blog posts from our community are coming. Stay tuned for inspiring
                            stories, devotionals, and insights.</p>
                        <div class="blog-foot">
                            <span style="color: var(--gray-muted); font-size:0.8rem;">Coming soon</span>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="scroll-dots" id="blog-dots"></div>
    </div>
</section>

<div class="parallax-divider"></div>

<!-- ============================================================
     TESTIMONIALS
     ============================================================ -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-header" style="text-align:center;" data-reveal>
            <div class="section-eyebrow" style="justify-content:center;">Community Voices</div>
            <h2 class="section-title">What People Are Saying</h2>
        </div>

        <div class="testimonial-slider swiper reveal">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="testimonial-card">
                        <p class="testimonial-quote">Scribes Global has transformed my life. I've found a community that
                            encourages me to use my creative gifts for God's glory. The support and mentorship I've
                            received here is invaluable.</p>
                        <div class="testimonial-author">
                            <img src="<?= ASSETS_PATH ?>images/testimonials/person1.jpg" alt="Sarah Mensah"
                                onerror="this.style.display='none'">
                            <div>
                                <h5>Sarah Mensah</h5>
                                <p>Spoken Word Artist</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="testimonial-card">
                        <p class="testimonial-quote">Being part of Scribes Worship has helped me grow both as a musician
                            and as a worshipper. The level of excellence and passion for God here is truly inspiring.
                        </p>
                        <div class="testimonial-author">
                            <img src="<?= ASSETS_PATH ?>images/testimonials/person2.jpg" alt="David Osei"
                                onerror="this.style.display='none'">
                            <div>
                                <h5>David Osei</h5>
                                <p>Worship Leader</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="testimonial-card">
                        <p class="testimonial-quote">The prayer wall feature has been such a blessing. Knowing that my
                            brothers and sisters in Christ are praying for me during difficult times has strengthened my
                            faith immensely.</p>
                        <div class="testimonial-author">
                            <img src="<?= ASSETS_PATH ?>images/testimonials/person3.jpg" alt="Grace Addo"
                                onerror="this.style.display='none'">
                            <div>
                                <h5>Grace Addo</h5>
                                <p>Community Member</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination" style="margin-top:2rem;position:relative;bottom:auto;"></div>
        </div>
    </div>
</section>

<!-- ============================================================
     CTA SECTION - NEWSLETTER
     ============================================================ -->
<section class="cta-section">
    <div class="cta-bg"></div>
    <div class="cta-content reveal">
        <div class="section-eyebrow" style="justify-content:center; margin-bottom:1.5rem;">Stay Connected</div>
        <h2 class="cta-title">Get Updates On New Events & Content</h2>
        <p class="cta-desc">
            Subscribe to our newsletter and never miss inspiring stories, worship sessions, and creative events from Scribes Global.
        </p>
        
        <div style="max-width: 500px; margin: 2rem auto;">
            <form id="newsletterForm" style="display: flex; gap: 0.75rem; flex-wrap: wrap; justify-content: center;">
                <input 
                    type="text" 
                    id="newsletterName" 
                    name="name" 
                    placeholder="Your name" 
                    required
                    style="padding: 0.85rem 1.5rem; border-radius: 25px; border: none; font-size: 0.9rem; min-width: 200px;"
                >
                <input 
                    type="email" 
                    id="newsletterEmail" 
                    name="email" 
                    placeholder="Your email" 
                    required
                    style="padding: 0.85rem 1.5rem; border-radius: 25px; border: none; font-size: 0.9rem; min-width: 200px;"
                >
                <button type="submit" class="btn-primary-gold" style="min-width: 150px;">
                    <i class="fas fa-envelope"></i> Subscribe
                </button>
            </form>
            <p style="font-size: 0.8rem; color: rgba(255,255,255,0.7); margin-top: 1rem; text-align: center;">
                We respect your privacy. Unsubscribe at any time.
            </p>
        </div>
        
        <div class="cta-btns" style="margin-top: 1.5rem;">
            <a href="<?= SITE_URL ?>/pages/connect/volunteer" class="btn-ghost">
                <i class="fas fa-hands-helping"></i> Volunteer With Us
            </a>
        </div>
    </div>
</section>

<script>
// Newsletter subscription
document.getElementById('newsletterForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = this.querySelector('button[type="submit"]');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subscribing...';
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('<?= SITE_URL ?>/api/newsletter.php?action=subscribe', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            document.getElementById('newsletterForm').reset();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
});
</script>


<!-- ============================================================
     VIDEO MODAL
     ============================================================ -->
<div class="video-modal" id="videoModal">
    <div class="video-modal-inner">
        <button class="video-modal-close" onclick="closeVideoModal()">
            <i class="fas fa-times"></i>
        </button>
        <iframe id="videoFrame" src="" frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen>
        </iframe>
    </div>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<!-- Three.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<!-- GSAP + ScrollTrigger -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<!-- Swiper -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/8.4.7/swiper-bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/8.4.7/swiper-bundle.min.css">

<script>
/* =============================================
   THREE.JS — 3D BACKGROUND
   ============================================= */


/* =============================================
   GSAP SCROLL REVEAL ANIMATIONS
   ============================================= */
gsap.registerPlugin(ScrollTrigger);

// Animate section headers
document.querySelectorAll('.reveal').forEach(el => {
    gsap.fromTo(el, {
        opacity: 0,
        y: 50
    }, {
        opacity: 1,
        y: 0,
        duration: 0.9,
        ease: 'power3.out',
        scrollTrigger: {
            trigger: el,
            start: 'top 85%',
            once: true,
        }
    });
});

// Stagger cards in viewport on scroll
document.querySelectorAll('.video-card, .event-card, .blog-card').forEach((card, i) => {
    gsap.fromTo(card, {
        opacity: 0,
        scale: 0.95,
        y: 20
    }, {
        opacity: 1,
        scale: 1,
        y: 0,
        duration: 0.6,
        ease: 'power2.out',
        delay: (i % 4) * 0.1,
        scrollTrigger: {
            trigger: card.closest('.section-wrapper'),
            start: 'top 75%',
            once: true,
        }
    });
});

// Parallax effect on section backgrounds
gsap.utils.toArray('.section-wrapper').forEach(section => {
    gsap.fromTo(section, {
        backgroundPositionY: '0%'
    }, {
        backgroundPositionY: '20%',
        ease: 'none',
        scrollTrigger: {
            trigger: section,
            start: 'top bottom',
            end: 'bottom top',
            scrub: true,
        }
    });
});

/* =============================================
   COUNTER ANIMATION FOR STATS
   ============================================= */
document.querySelectorAll('.stat-number[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count) || 0;
    const formatted = el.innerText;

    if (target > 0) {
        ScrollTrigger.create({
            trigger: el,
            start: 'top 90%',
            once: true,
            onEnter: () => {
                const obj = {
                    count: 0
                };
                gsap.to(obj, {
                    count: target,
                    duration: 2.5,
                    ease: 'power2.out',
                    onUpdate: () => {
                        el.textContent = Math.round(obj.count).toLocaleString() + '+';
                    }
                });
            }
        });
    }
});

/* =============================================
   HORIZONTAL SCROLL LOGIC
   ============================================= */
function scrollTrack(trackId, direction) {
    const track = document.getElementById(trackId);
    if (!track) return;
    const cardWidth = track.querySelector('> *')?.offsetWidth + 24 || 400;
    track.scrollBy({
        left: direction * cardWidth,
        behavior: 'smooth'
    });
}

// Drag to scroll
document.querySelectorAll('.h-scroll-track').forEach(track => {
    let isDown = false,
        startX, scrollLeft;

    track.addEventListener('mousedown', e => {
        isDown = true;
        track.style.cursor = 'grabbing';
        startX = e.pageX - track.offsetLeft;
        scrollLeft = track.scrollLeft;
    });

    document.addEventListener('mouseup', () => {
        isDown = false;
        track.style.cursor = 'grab';
    });

    track.addEventListener('mousemove', e => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - track.offsetLeft;
        const walk = (x - startX) * 1.5;
        track.scrollLeft = scrollLeft - walk;
    });

    // Touch support
    let touchStartX = 0,
        touchScrollLeft = 0;
    track.addEventListener('touchstart', e => {
        touchStartX = e.touches[0].pageX;
        touchScrollLeft = track.scrollLeft;
    }, {
        passive: true
    });

    track.addEventListener('touchmove', e => {
        const diff = touchStartX - e.touches[0].pageX;
        track.scrollLeft = touchScrollLeft + diff;
    }, {
        passive: true
    });
});

// Scroll dots
function initDots(trackId, dotsId) {
    const track = document.getElementById(trackId);
    const dotsContainer = document.getElementById(dotsId);
    if (!track || !dotsContainer) return;

    const cards = track.querySelectorAll(':scope > *');
    if (cards.length === 0) return;

    // Create dots
    cards.forEach((_, i) => {
        const dot = document.createElement('div');
        dot.className = 'scroll-dot' + (i === 0 ? ' active' : '');
        dot.addEventListener('click', () => {
            const cardWidth = cards[0].offsetWidth + 24;
            track.scrollTo({
                left: i * cardWidth,
                behavior: 'smooth'
            });
        });
        dotsContainer.appendChild(dot);
    });

    // Update active dot on scroll
    track.addEventListener('scroll', () => {
        const cardWidth = cards[0].offsetWidth + 24;
        const activeIndex = Math.round(track.scrollLeft / cardWidth);
        dotsContainer.querySelectorAll('.scroll-dot').forEach((dot, i) => {
            dot.classList.toggle('active', i === activeIndex);
        });
    }, {
        passive: true
    });
}

initDots('video-track', 'video-dots');
initDots('event-track', 'event-dots');
initDots('blog-track', 'blog-dots');

/* =============================================
   VIDEO MODAL
   ============================================= */
document.querySelectorAll('.video-card').forEach(card => {
    card.addEventListener('click', () => {
        const id = card.dataset.videoId;
        document.getElementById('videoFrame').src = `https://www.youtube.com/embed/${id}?autoplay=1`;
        document.getElementById('videoModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    });
});

function closeVideoModal() {
    document.getElementById('videoFrame').src = '';
    document.getElementById('videoModal').classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('videoModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeVideoModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeVideoModal();
});

/* =============================================
   TESTIMONIAL SWIPER
   ============================================= */
new Swiper('.testimonial-slider', {
    slidesPerView: 1,
    spaceBetween: 30,
    loop: true,
    autoplay: {
        delay: 5000,
        disableOnInteraction: false
    },
    pagination: {
        el: '.swiper-pagination',
        clickable: true
    },
    effect: 'fade',
    fadeEffect: {
        crossFade: true
    },
});

/* =============================================
   SECTION PARALLAX DIVIDER PULSE
   ============================================= */
gsap.utils.toArray('.parallax-divider').forEach(div => {
    gsap.fromTo(div, {
        scaleX: 0,
        opacity: 0
    }, {
        scaleX: 1,
        opacity: 0.3,
        duration: 1.2,
        ease: 'power3.out',
        scrollTrigger: {
            trigger: div,
            start: 'top 90%',
            once: true,
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>