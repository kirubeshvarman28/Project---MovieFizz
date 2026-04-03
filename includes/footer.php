<?php
// Fetch settings if not already available
if (!isset($settings)) {
    $settings = get_all_settings();
}
$site_name = $settings['site_name'] ?? SITE_NAME;

// Fetch static pages for footer
$footer_pages = [];
try {
    $stmt_fp = $pdo->query("SELECT title, slug FROM static_pages ORDER BY id ASC LIMIT 5");
    $footer_pages = $stmt_fp->fetchAll();
} catch(Exception $e) {}
?>
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="index.php" class="logo">
                        <?php if(!empty($settings['site_logo'])): ?>
                            <img src="<?php echo $settings['site_logo']; ?>" alt="<?php echo $site_name; ?>" style="max-height: 30px; vertical-align: middle;">
                        <?php else: ?>
                            <?php echo $site_name; ?>
                        <?php endif; ?>
                    </a>
                    <p>Stream unlimited movies and TV shows on your phone, tablet, laptop, and TV.</p>
                    <div class="footer-social">
                        <?php if(!empty($settings['facebook_url'])): ?>
                            <a href="<?php echo $settings['facebook_url']; ?>" target="_blank"><i class="fab fa-facebook"></i></a>
                        <?php endif; ?>
                        <?php if(!empty($settings['twitter_url'])): ?>
                            <a href="<?php echo $settings['twitter_url']; ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if(!empty($settings['instagram_url'])): ?>
                            <a href="<?php echo $settings['instagram_url']; ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if(!empty($settings['youtube_url'])): ?>
                            <a href="<?php echo $settings['youtube_url']; ?>" target="_blank"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Browse</h4>
                    <a href="movies.php">Movies</a>
                    <a href="shows.php">TV Shows</a>
                    <a href="categories.php">Genres</a>
                    <a href="plans.php">Plans & Pricing</a>
                </div>
                <div class="footer-col">
                    <h4>Account</h4>
                    <?php if(is_logged_in()): ?>
                    <a href="watchlist.php">My List</a>
                    <a href="logout.php">Sign Out</a>
                    <?php else: ?>
                    <a href="login.php">Sign In</a>
                    <a href="register.php">Register</a>
                    <?php endif; ?>
                </div>
                <div class="footer-col">
                    <h4>Info</h4>
                    <?php if(!empty($footer_pages)): ?>
                        <?php foreach($footer_pages as $fp): ?>
                        <a href="page.php?slug=<?php echo $fp['slug']; ?>"><?php echo $fp['title']; ?></a>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="contact.php">Contact Us</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. All rights reserved.
            </div>
        </div>
    </footer>
    <?php if(!empty($settings['ad_footer'])): ?>
    <!-- Footer Ad Code -->
    <?php echo $settings['ad_footer']; ?>
    <?php endif; ?>

    <script src="assets/js/main.js?v=1.2"></script>
</body>
</html>
