<?php if (!isset($noFooter) || !$noFooter): ?>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- About Section -->
                <div class="footer-section">
                    <h4>About Scribes Global</h4>
                    <p style="color: var(--gray-400); line-height: 1.8;">
                        A creative community of poets, worship leaders, and artists spreading the Gospel through creative arts and authentic expression.
                    </p>
                    <div class="social-links">
                        <a href="https://facebook.com/scribesglobal" target="_blank" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://instagram.com/scribesglobal" target="_blank" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://twitter.com/scribesglobal" target="_blank" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://youtube.com/scribesglobal" target="_blank" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="https://tiktok.com/@scribesglobal" target="_blank" aria-label="TikTok">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/pages/about/scribes-global">About Us</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/about/ministries">Ministries</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/about/chapters">Chapters</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/events">Events</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/media">Media</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/blog">Blog</a></li>
                    </ul>
                </div>
                
                <!-- Get Involved -->
                <div class="footer-section">
                    <h4>Get Involved</h4>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/pages/connect/volunteer">Volunteer</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/give">Give</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/connect/invite">Invite Us</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/prayer">Prayer Requests</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/about/chapters#start-chapter">Start a Chapter</a></li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div class="footer-section">
                    <h4>Contact Us</h4>
                    <ul>
                        <li>
                            <i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--primary-gold);"></i>
                            <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a>
                        </li>
                        <li>
                            <i class="fas fa-phone" style="margin-right: 0.5rem; color: var(--primary-gold);"></i>
                            <a href="tel:+233123456789">+233 123 456 789</a>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: var(--primary-gold);"></i>
                            Accra, Ghana
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Scribes Global. All rights reserved.</p>
                <div style="margin-top: 0.5rem;">
                    <a href="<?= SITE_URL ?>/pages/legal/privacy" style="margin: 0 1rem;">Privacy Policy</a>
                    <a href="<?= SITE_URL ?>/pages/legal/terms" style="margin: 0 1rem;">Terms of Service</a>
                    <a href="<?= SITE_URL ?>/pages/legal/safeguarding" style="margin: 0 1rem;">Safeguarding</a>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Main JavaScript -->
    <script src="<?= ASSETS_PATH ?>js/main.js"></script>
    
    <!-- Page Specific JS -->
    <?php if (isset($pageJS)): ?>
        <script src="<?= ASSETS_PATH ?>js/<?= $pageJS ?>.js"></script>
    <?php endif; ?>
    
    <!-- Initialize AOS -->
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });
    </script>
    
    <!-- Back to Top Button -->
    <button id="backToTop" style="
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-teal) 100%);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        box-shadow: var(--shadow-lg);
        z-index: 100;
        transition: all 0.3s ease;
    ">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <script>
        // Back to Top functionality
        const backToTop = document.getElementById('backToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.style.display = 'flex';
            } else {
                backToTop.style.display = 'none';
            }
        });
        
        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>