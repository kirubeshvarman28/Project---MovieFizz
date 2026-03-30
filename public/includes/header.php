<?php
$settings = get_all_settings();
$site_name = $settings['site_name'] ?? SITE_NAME;
$site_logo = $settings['site_logo'] ?? '';

// Maintenance Mode Check
if (is_maintenance_mode() && !is_admin() && basename($_SERVER['PHP_SELF']) !== 'maintenance.php') {
    header("Location: maintenance.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . $site_name : $site_name; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($settings['description'] ?? "$site_name — Stream unlimited movies and TV shows."); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($settings['keywords'] ?? ''); ?>">
    <link rel="stylesheet" href="../assets/css/style.css?v=2.5">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <?php if(!empty($settings['ad_header'])): ?>
    <!-- Header Ad Code -->
    <?php echo $settings['ad_header']; ?>
    <?php endif; ?>
</head>
<body>
    <header class="main-header" id="mainHeader">
        <div class="container navbar">
            <div class="nav-left">
                <a href="index.php" class="logo">
                    <?php if(!empty($site_logo)): ?>
                        <img src="<?php echo $site_logo; ?>" alt="<?php echo $site_name; ?>" style="max-height: 40px; vertical-align: middle;">
                    <?php else: ?>
                        <?php echo $site_name; ?>
                    <?php endif; ?>
                </a>
                <nav class="desktop-nav">
                    <ul>
                        <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF'])=='index.php'?'class="active"':''; ?>>Home</a></li>
                        <li><a href="movies.php" <?php echo basename($_SERVER['PHP_SELF'])=='movies.php'?'class="active"':''; ?>>Movies</a></li>
                        <li><a href="shows.php" <?php echo basename($_SERVER['PHP_SELF'])=='shows.php'?'class="active"':''; ?>>TV Shows</a></li>

                        <li><a href="categories.php" <?php echo basename($_SERVER['PHP_SELF'])=='categories.php'?'class="active"':''; ?>>Genres</a></li>
                        <?php if(is_logged_in()): ?>
                        <li><a href="watchlist.php" <?php echo basename($_SERVER['PHP_SELF'])=='watchlist.php'?'class="active"':''; ?>>My List</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <div class="nav-right">
                <div class="search-container" id="searchBox">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="ajax_search" placeholder="Search..." autocomplete="off">
                    <div id="search_results" class="search-results"></div>
                </div>
                <?php if(is_logged_in()): ?>
                    <div class="user-menu">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                        <span style="font-size:14px;"><?php echo $_SESSION['username']; ?></span>
                        <div class="dropdown">
                            <?php if(is_admin()): ?>
                            <a href="../admin/dashboard.php"><i class="fas fa-cog"></i> Admin Panel</a>
                            <?php endif; ?>
                            <a href="watchlist.php"><i class="fas fa-bookmark"></i> My List</a>
                            <a href="plans.php"><i class="fas fa-crown"></i> Plans</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-primary-cta">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Media Request Modal -->
    <div class="modal-overlay" id="requestModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(10px);">
        <div class="modal-content">
            <div class="modal-close" id="closeRequestModal">&times;</div>
            
            <div id="requestFormContent">
                <h2 style="color: var(--primary); margin-bottom: 20px;">Request a Movie/Show</h2>
                <form class="request-form" id="mediaRequestForm">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">Movie or TV Show Title*</label>
                        <input type="text" name="media_title" id="req_title" placeholder="e.g. Inception, Stranger Things" required style="width: 100%; padding: 12px; background: #252525; border: 1px solid var(--border-subtle); border-radius: 4px; color: #fff;">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">Category</label>
                        <select name="media_type" id="req_type" style="width: 100%; padding: 12px; background: #252525; border: 1px solid var(--border-subtle); border-radius: 4px; color: #fff;">
                            <option value="movie">Movie</option>
                            <option value="show">TV Show</option>
                        </select>
                    </div>
                    <?php if(!is_logged_in()): ?>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 5px; color: var(--text-secondary);">Your Email* (For notification)</label>
                        <input type="email" name="user_email" id="req_email" placeholder="your@email.com" required style="width: 100%; padding: 12px; background: #252525; border: 1px solid var(--border-subtle); border-radius: 4px; color: #fff;">
                    </div>
                    <?php endif; ?>
                    <button type="submit" id="submitRequestBtn" style="width: 100%; padding: 14px; background: var(--primary); color: #fff; border: none; border-radius: 4px; font-weight: 700; cursor: pointer; margin-top: 10px;">Send Request</button>
                </form>
            </div>

            <div class="request-success" id="requestSuccessContent" style="text-align: center; display: none;">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: #2ecc71; margin-bottom: 20px;"></i>
                <h3>Request Submitted!</h3>
                <p style="color: var(--text-secondary); margin-top: 10px;">Thank you for your request. The movie or TV show will be uploaded within 2 - 3 hrs.</p>
                <button type="button" class="btn btn-primary" id="closeSuccessBtn" style="background:var(--primary); color:#fff; border:none; padding: 12px 30px; margin-top:25px; border-radius:4px; cursor:pointer; font-weight:700;">Close</button>
            </div>
        </div>
    </div>
