<!-- Admin Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <?php if(!empty($site_logo)): ?>
            <img src="<?php echo $site_logo; ?>" alt="<?php echo $site_name; ?>" style="max-height: 50px; width: auto; margin-bottom: 10px;">
        <?php else: ?>
            <h1><?php echo htmlspecialchars($site_name); ?></h1>
        <?php endif; ?>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_requests.php') ? 'active' : ''; ?>">
                <a href="manage_requests.php"><i class="fas fa-bullhorn"></i> <span>Media Requests</span></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'import_tmdb.php') ? 'active' : ''; ?>">
                <a href="import_tmdb.php" style="color: #ffc107;"><i class="fas fa-cloud-upload-alt"></i> <span>Cloud Importer</span></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_languages.php') ? 'active' : ''; ?>">
                <a href="manage_languages.php"><i class="fas fa-language"></i> <span>Language</span></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_genres.php') ? 'active' : ''; ?>">
                <a href="manage_genres.php"><i class="fas fa-list-ul"></i> <span>Genres</span></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_movies.php' || basename($_SERVER['PHP_SELF']) == 'add_movie.php') ? 'active' : ''; ?>">
                <a href="manage_movies.php"><i class="fas fa-video"></i> <span>Movies</span></a>
            </li>
            
            <!-- TV Shows Submenu -->
            <li class="has-submenu <?php
$current_page = basename($_SERVER['PHP_SELF']);
echo(strpos($current_page, 'show') !== false ||
    strpos($current_page, 'season') !== false ||
    strpos($current_page, 'episode') !== false) ? 'open' : ''; ?>">
                <a href="javascript:void(0)" class="submenu-toggle"><i class="fas fa-tv"></i> <span>TV Shows</span> <i class="fas fa-chevron-right arrow"></i></a>
                <ul class="submenu">
                    <li><a href="manage_shows.php"><i class="fas fa-image"></i> Shows</a></li>
                    <li><a href="manage_seasons.php"><i class="fas fa-seedling"></i> Seasons</a></li>
                    <li><a href="manage_episodes.php"><i class="fas fa-microphone"></i> Episodes</a></li>
                </ul>
            </li>



            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_sports.php') ? 'active' : ''; ?>">
                <a href="manage_sports.php"><i class="fas fa-basketball-ball"></i> <span>Sports</span> <i class="fas fa-chevron-right arrow"></i></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_live_tv.php') ? 'active' : ''; ?>">
                <a href="manage_live_tv.php"><i class="fas fa-laptop"></i> <span>Live TV</span> <i class="fas fa-chevron-right arrow"></i></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_cast.php') ? 'active' : ''; ?>">
                <a href="manage_cast.php"><i class="fas fa-users-cog"></i> <span>Cast & Crew</span> <i class="fas fa-chevron-right arrow"></i></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'home_settings.php') ? 'active' : ''; ?>">
                <a href="home_settings.php"><i class="fas fa-home"></i> <span>Home</span> <i class="fas fa-chevron-right arrow"></i></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_users.php') ? 'active' : ''; ?>">
                <a href="manage_users.php"><i class="fas fa-users"></i> <span>Users</span> <i class="fas fa-chevron-right arrow"></i></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_plans.php') ? 'active' : ''; ?>">
                <a href="manage_plans.php"><i class="fas fa-dollar-sign"></i> <span>Subscription Plan</span></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_coupons.php') ? 'active' : ''; ?>">
                <a href="manage_coupons.php"><i class="fas fa-shopping-basket"></i> <span>Coupons</span></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_gateways.php') ? 'active' : ''; ?>">
                <a href="manage_gateways.php"><i class="fas fa-credit-card"></i> <span>Payment Gateway</span></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_transactions.php') ? 'active' : ''; ?>">
                <a href="manage_transactions.php"><i class="fas fa-list-alt"></i> <span>Transactions</span></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'manage_pages.php') ? 'active' : ''; ?>">
                <a href="manage_pages.php"><i class="fas fa-file-alt"></i> <span>Pages</span> <i class="fas fa-chevron-right arrow"></i></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'player_settings.php') ? 'active' : ''; ?>">
                <a href="player_settings.php"><i class="fas fa-play-circle"></i> <span>Player Settings</span></a>
            </li>
            <li class="<?php echo(basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
                <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span> <i class="fas fa-chevron-right arrow"></i></a>
            </li>
        </ul>
    </nav>
</aside>

