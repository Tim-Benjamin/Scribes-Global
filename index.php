<?php
$pageTitle = 'Scribes Global - Creative Arts Ministry';
$pageDescription = 'A creative community of poets, worship leaders, and artists spreading the Gospel through creative arts.';
$pageCSS = 'home';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

// Fetch latest content
$db = new Database();
$conn = $db->connect();

// Get upcoming events
$eventsStmt = $conn->prepare("
    SELECT * FROM events 
    WHERE status = 'upcoming' AND start_date > NOW()
    ORDER BY start_date ASC 
    LIMIT 6
");
$eventsStmt->execute();
$events = $eventsStmt->fetchAll();

// Get latest blog posts
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

// Get stats
$statsStmt = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE status = 'active') as total_members,
        (SELECT COUNT(*) FROM events WHERE status != 'cancelled') as total_events,
        (SELECT COUNT(*) FROM chapters WHERE status = 'active') as total_chapters,
        (SELECT COUNT(*) FROM media_content WHERE status = 'approved') as total_content
");
$stats = $statsStmt->fetch();
?>

<style>
/* Inline styles for video modal */
.video-modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.95);
  z-index: 10000;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.video-modal.active {
  display: flex;
}

.video-modal-content {
  position: relative;
  width: 100%;
  max-width: 1200px;
  aspect-ratio: 16/9;
}

.video-modal-close {
  position: absolute;
  top: -50px;
  right: 0;
  background: white;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-modal iframe {
  width: 100%;
  height: 100%;
  border-radius: 1rem;
}
</style>

<!-- Hero Section -->
<section class="hero-section">
  <div class="hero-background">
    <div class="hero-shapes">
      <div class="hero-shape hero-shape-1"></div>
      <div class="hero-shape hero-shape-2"></div>
      <div class="hero-shape hero-shape-3"></div>
    </div>
  </div>
  
  <div class="container hero-content">
    <div class="hero-badge" data-aos="fade-up">Creative Arts Ministry</div>
    
    <h1 class="hero-title" data-aos="fade-up" data-aos-delay="100">
      Spreading The Gospel Through <span class="gradient-text">Creative Expression</span>
    </h1>
    
    <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="200">
      Join a global community of poets, worship leaders, and creatives using their gifts to impact lives and share the love of Christ.
    </p>
    
    <div class="hero-cta" data-aos="fade-up" data-aos-delay="300">
      <a href="<?= SITE_URL ?>/pages/connect/volunteer" class="btn btn-primary btn-lg">
        <i class="fas fa-hands-helping"></i> Join Us Today
      </a>
      <a href="<?= SITE_URL ?>/pages/about/scribes-global" class="btn btn-outline btn-lg">
        <i class="fas fa-info-circle"></i> Learn More
      </a>
    </div>
    
    <div class="hero-stats" data-aos="fade-up" data-aos-delay="400">
      <div class="stat-item">
        <div class="stat-number"><?= number_format($stats['total_members'] ?? 0) ?>+</div>
        <div class="stat-label">Active Members</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= number_format($stats['total_events'] ?? 0) ?>+</div>
        <div class="stat-label">Events Hosted</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= number_format($stats['total_chapters'] ?? 0) ?>+</div>
        <div class="stat-label">Global Chapters</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= number_format($stats['total_content'] ?? 0) ?>+</div>
        <div class="stat-label">Creative Works</div>
      </div>
    </div>
  </div>
</section>

<!-- Videos Section -->
<section class="video-section">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-badge">Latest Content</span>
      <h2 class="section-title">Featured Videos</h2>
      <p class="section-description">
        Experience powerful performances, worship sessions, and testimonies from our community
      </p>
    </div>
    
    <div class="video-grid">
      <!-- Sample Video 1 -->
      <div class="video-card" data-aos="fade-up" data-video-id="dQw4w9WgXcQ">
        <div class="video-thumbnail">
          <img src="https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg" alt="Video">
          <div class="video-play-btn">
            <i class="fas fa-play"></i>
          </div>
        </div>
        <div class="video-info">
          <h3 class="video-title">Powerful Spoken Word Performance</h3>
          <div class="video-meta">
            <span><i class="fas fa-eye"></i> 12K views</span>
            <span><i class="fas fa-clock"></i> 2 days ago</span>
          </div>
        </div>
      </div>
      
      <!-- Sample Video 2 -->
      <div class="video-card" data-aos="fade-up" data-aos-delay="100" data-video-id="dQw4w9WgXcQ">
        <div class="video-thumbnail">
          <img src="https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg" alt="Video">
          <div class="video-play-btn">
            <i class="fas fa-play"></i>
          </div>
        </div>
        <div class="video-info">
          <h3 class="video-title">Worship Night Highlights</h3>
          <div class="video-meta">
            <span><i class="fas fa-eye"></i> 8K views</span>
            <span><i class="fas fa-clock"></i> 1 week ago</span>
          </div>
        </div>
      </div>
      
      <!-- Sample Video 3 -->
      <div class="video-card" data-aos="fade-up" data-aos-delay="200" data-video-id="dQw4w9WgXcQ">
        <div class="video-thumbnail">
          <img src="https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg" alt="Video">
          <div class="video-play-btn">
            <i class="fas fa-play"></i>
          </div>
        </div>
        <div class="video-info">
          <h3 class="video-title">Testimony: How God Changed My Life</h3>
          <div class="video-meta">
            <span><i class="fas fa-eye"></i> 15K views</span>
            <span><i class="fas fa-clock"></i> 2 weeks ago</span>
          </div>
        </div>
      </div>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
      <a href="<?= SITE_URL ?>/pages/media" class="btn btn-primary">
        View All Videos <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- Events Section -->
<section class="events-section">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-badge">What's Happening</span>
      <h2 class="section-title">Upcoming Events</h2>
      <p class="section-description">
        Join us for powerful gatherings, workshops, and community events
      </p>
    </div>
    
    <div class="events-grid">
      <?php if (count($events) > 0): ?>
        <?php foreach ($events as $index => $event): ?>
          <div class="event-card" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
            <img src="<?= $event['hero_image'] ? ASSETS_PATH . 'images/uploads/' . $event['hero_image'] : ASSETS_PATH . 'images/placeholder-event.jpg' ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="event-image">
            
            <div class="event-content">
              <div class="event-date">
                <i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($event['start_date'])) ?>
              </div>
              
              <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
              
              <p class="event-description"><?= htmlspecialchars(substr($event['description'], 0, 150)) ?>...</p>
              
              <div class="event-meta">
                <div class="event-meta-item">
                  <i class="fas fa-clock"></i>
                  <span><?= date('g:i A', strtotime($event['start_date'])) ?></span>
                </div>
                <div class="event-meta-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <span><?= htmlspecialchars($event['location']) ?></span>
                </div>
              </div>
              
              <a href="<?= SITE_URL ?>/pages/events/details?id=<?= $event['id'] ?>" class="btn btn-primary btn-sm" style="width: 100%; margin-top: auto;">
                View Details <i class="fas fa-arrow-right"></i>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="grid-column: 1/-1; text-align: center; color: var(--gray-600);">No upcoming events at the moment. Check back soon!</p>
      <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
      <a href="<?= SITE_URL ?>/pages/events" class="btn btn-secondary">
        View All Events <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- Blog Section -->
<section class="blog-section">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-badge">Inspiration & Insights</span>
      <h2 class="section-title">Latest From Our Blog</h2>
      <p class="section-description">
        Read inspiring stories, devotionals, and creative insights from our community
      </p>
    </div>
    
    <div class="blog-grid">
      <?php if (count($blogPosts) > 0): ?>
        <?php foreach ($blogPosts as $index => $post): ?>
          <div class="blog-card" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
            <img src="<?= $post['featured_image'] ? ASSETS_PATH . 'images/uploads/' . $post['featured_image'] : ASSETS_PATH . 'images/placeholder-blog.jpg' ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="blog-image">
            
            <div class="blog-content">
              <?php if ($post['category']): ?>
                <span class="blog-category"><?= htmlspecialchars($post['category']) ?></span>
              <?php endif; ?>
              
              <h3 class="blog-title">
                <a href="<?= SITE_URL ?>/pages/blog/post?slug=<?= $post['slug'] ?>" style="color: inherit;">
                  <?= htmlspecialchars($post['title']) ?>
                </a>
              </h3>
              
              <p class="blog-excerpt"><?= htmlspecialchars(substr(strip_tags($post['content']), 0, 150)) ?>...</p>
              
              <div class="blog-footer">
                <div class="blog-author">
                  <?php if ($post['profile_photo']): ?>
                    <img src="<?= ASSETS_PATH ?>images/uploads/<?= $post['profile_photo'] ?>" alt="<?= htmlspecialchars($post['first_name']) ?>" class="blog-author-img">
                  <?php else: ?>
                    <div class="blog-author-img" style="background: var(--primary-purple); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                      <?= strtoupper(substr($post['first_name'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                  <div class="blog-author-info">
                    <div class="blog-author-name"><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></div>
                    <div class="blog-date"><?= date('M d, Y', strtotime($post['published_at'])) ?></div>
                  </div>
                </div>
                <div class="blog-stats">
                  <span class="blog-stat"><i class="fas fa-heart"></i> <?= $post['likes'] ?></span>
                  <span class="blog-stat"><i class="fas fa-eye"></i> <?= $post['views'] ?></span>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="grid-column: 1/-1; text-align: center; color: var(--gray-600);">No blog posts yet. Stay tuned!</p>
      <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
      <a href="<?= SITE_URL ?>/pages/blog" class="btn btn-primary">
        View All Posts <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-badge">Community Voices</span>
      <h2 class="section-title">What People Are Saying</h2>
    </div>
    
    <div class="testimonial-slider swiper" data-aos="fade-up" data-aos-delay="200">
      <div class="swiper-wrapper">
        <div class="swiper-slide">
          <div class="testimonial-card">
            <p class="testimonial-quote">
              Scribes Global has transformed my life. I've found a community that encourages me to use my creative gifts for God's glory. The support and mentorship I've received here is invaluable.
            </p>
            <div class="testimonial-author">
              <img src="<?= ASSETS_PATH ?>images/testimonials/person1.jpg" alt="Author" class="testimonial-author-img" onerror="this.src='<?= ASSETS_PATH ?>images/avatar-placeholder.jpg'">
              <div class="testimonial-author-info">
                <h5>Sarah Mensah</h5>
                <p class="testimonial-author-role">Spoken Word Artist</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="swiper-slide">
          <div class="testimonial-card">
            <p class="testimonial-quote">
              Being part of Scribes Worship has helped me grow both as a musician and as a worshipper. The level of excellence and passion for God here is inspiring.
            </p>
            <div class="testimonial-author">
              <img src="<?= ASSETS_PATH ?>images/testimonials/person2.jpg" alt="Author" class="testimonial-author-img" onerror="this.src='<?= ASSETS_PATH ?>images/avatar-placeholder.jpg'">
              <div class="testimonial-author-info">
                <h5>David Osei</h5>
                <p class="testimonial-author-role">Worship Leader</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="swiper-slide">
          <div class="testimonial-card">
            <p class="testimonial-quote">
              The prayer wall feature has been such a blessing. Knowing that my brothers and sisters in Christ are praying for me during difficult times has strengthened my faith immensely.
            </p>
            <div class="testimonial-author">
              <img src="<?= ASSETS_PATH ?>images/testimonials/person3.jpg" alt="Author" class="testimonial-author-img" onerror="this.src='<?= ASSETS_PATH ?>images/avatar-placeholder.jpg'">
              <div class="testimonial-author-info">
                <h5>Grace Addo</h5>
                <p class="testimonial-author-role">Community Member</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="swiper-pagination"></div>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
  <div class="container">
    <div class="cta-content" data-aos="zoom-in">
      <h2 class="cta-title">Ready To Use Your Gifts For God's Glory?</h2>
      <p class="cta-description">
        Join thousands of creatives around the world who are making an impact through their art, worship, and testimony.
      </p>
      <div class="cta-buttons">
        <a href="<?= SITE_URL ?>/auth/register" class="btn btn-white btn-lg">
          <i class="fas fa-user-plus"></i> Create Account
        </a>
        <a href="<?= SITE_URL ?>/pages/connect/volunteer" class="btn btn-outline-white btn-lg">
          <i class="fas fa-hands-helping"></i> Volunteer With Us
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Video Modal -->
<div class="video-modal" id="videoModal">
  <div class="video-modal-content">
    <button class="video-modal-close" onclick="closeVideoModal()">
      <i class="fas fa-times"></i>
    </button>
    <iframe id="videoFrame" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
  </div>
</div>

<script>
// Video Modal
document.querySelectorAll('.video-card').forEach(card => {
  card.addEventListener('click', function() {
    const videoId = this.dataset.videoId;
    const modal = document.getElementById('videoModal');
    const frame = document.getElementById('videoFrame');
    
    frame.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  });
});

function closeVideoModal() {
  const modal = document.getElementById('videoModal');
  const frame = document.getElementById('videoFrame');
  
  frame.src = '';
  modal.classList.remove('active');
  document.body.style.overflow = 'auto';
}

// Close modal on overlay click
document.getElementById('videoModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeVideoModal();
  }
});

// Testimonial Swiper
const testimonialSwiper = new Swiper('.testimonial-slider', {
  slidesPerView: 1,
  spaceBetween: 30,
  loop: true,
  autoplay: {
    delay: 5000,
    disableOnInteraction: false,
  },
  pagination: {
    el: '.swiper-pagination',
    clickable: true,
  },
});

// GSAP Animations
gsap.registerPlugin(ScrollTrigger);

// Animate stats on scroll
gsap.from('.stat-number', {
  scrollTrigger: {
    trigger: '.hero-stats',
    start: 'top 80%',
  },
  textContent: 0,
  duration: 2,
  ease: 'power1.inOut',
  snap: { textContent: 1 },
  stagger: 0.2,
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>