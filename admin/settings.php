<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) redirect('login.php');

$success = '';
$error = '';

// Fetch current settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    // Basic Settings
    $site_name = clean_input($_POST['site_name'] ?? '');
    $site_logo = clean_input($_POST['site_logo'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $keywords = clean_input($_POST['keywords'] ?? '');
    $timezone = clean_input($_POST['timezone'] ?? 'UTC');
    $currency_code = clean_input($_POST['currency_code'] ?? 'USD');
    
    // Feature Toggles
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    // API & Social
    $tmdb_api_key = clean_input($_POST['tmdb_api_key'] ?? '');
    $tmdb_language = clean_input($_POST['tmdb_language'] ?? 'en-US');
    $facebook_url = clean_input($_POST['facebook_url'] ?? '');
    $twitter_url = clean_input($_POST['twitter_url'] ?? '');
    $instagram_url = clean_input($_POST['instagram_url'] ?? '');
    $youtube_url = clean_input($_POST['youtube_url'] ?? '');

    // SMTP Settings
    $smtp_host = clean_input($_POST['smtp_host'] ?? '');
    $smtp_user = clean_input($_POST['smtp_user'] ?? '');
    $smtp_pass = clean_input($_POST['smtp_pass'] ?? '');
    $smtp_port = clean_input($_POST['smtp_port'] ?? '587');
    $smtp_crypto = clean_input($_POST['smtp_crypto'] ?? 'tls');

    // Player Preferences
    $default_provider = clean_input($_POST['default_provider'] ?? 'vidrock');
    $autoplay = isset($_POST['autoplay']) ? 1 : 0;

    // Ad Management
    $ad_header = $_POST['ad_header'] ?? '';
    $ad_footer = $_POST['ad_footer'] ?? '';
    $ad_player = $_POST['ad_player'] ?? '';

    $sql = "UPDATE settings SET 
            site_name = ?, site_logo = ?, email = ?, description = ?, keywords = ?, 
            timezone = ?, currency_code = ?, maintenance_mode = ?,
            tmdb_api_key = ?, tmdb_language = ?, 
            facebook_url = ?, twitter_url = ?, instagram_url = ?, youtube_url = ?,
            smtp_host = ?, smtp_user = ?, smtp_pass = ?, smtp_port = ?, smtp_crypto = ?,
            default_provider = ?, autoplay = ?,
            ad_header = ?, ad_footer = ?, ad_player = ?
            WHERE id = 1";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([
        $site_name, $site_logo, $email, $description, $keywords, 
        $timezone, $currency_code, $maintenance_mode,
        $tmdb_api_key, $tmdb_language,
        $facebook_url, $twitter_url, $instagram_url, $youtube_url,
        $smtp_host, $smtp_user, $smtp_pass, $smtp_port, $smtp_crypto,
        $default_provider, $autoplay,
        $ad_header, $ad_footer, $ad_player
    ])) {
        $success = "Settings updated successfully!";
        // Refresh local settings variable
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $settings = $stmt->fetch();
    } else {
        $error = "Failed to update settings.";
    }
}

$page_title = "Settings";
include 'includes/header.php';

// Get all timezones
$timezones = DateTimeZone::listIdentifiers();
?>

    <div class="top-nav">
        <h2>Advanced Site Settings</h2>
        <div class="user-info">
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <?php if($success): ?><div class="status-badge published" style="display:block; margin: 20px;"><?php echo $success; ?></div><?php endif; ?>
    <?php if($error): ?><div class="status-badge draft" style="display:block; margin: 20px; background: #ff4444;"><?php echo $error; ?></div><?php endif; ?>

    <form method="POST" class="admin-form">
        <div class="dashboard-grid grid-50-50">
            <!-- Left Column: Site Info & Features -->
            <div class="form-container">
                <h3><i class="fas fa-info-circle"></i> Site Information & Maintenance</h3>
                
                <div class="form-group" style="background: rgba(229, 9, 20, 0.1); padding: 15px; border-radius: 10px; border: 1px solid rgba(229, 9, 20, 0.3); margin-bottom: 25px;">
                    <label style="display: flex; align-items: center; cursor: pointer; justify-content: space-between;">
                        <span><i class="fas fa-tools"></i> <strong>Maintenance Mode</strong></span>
                        <input type="checkbox" name="maintenance_mode" value="1" <?php echo ($settings['maintenance_mode'] == 1) ? 'checked' : ''; ?> style="width: 20px; height: 20px; cursor: pointer;">
                    </label>
                    <small style="display: block; margin-top: 5px; color: #888;">If enabled, public visitors will see a maintenance page. Admins can still browse.</small>
                </div>

                <div class="form-group">
                    <label>Site Name*</label>
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Site Logo (URL or Path)</label>
                    <input type="text" name="site_logo" value="<?php echo htmlspecialchars($settings['site_logo']); ?>" placeholder="../assets/images/logo.png">
                </div>
                <div class="form-group">
                    <label>Admin Email*</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($settings['email']); ?>" required>
                </div>

                <h4 style="margin: 25px 0 10px; color: var(--primary-color); border-bottom: 1px solid #333; padding-bottom: 5px;">Player Preferences</h4>
                <div class="form-group">
                    <label>Default Cloud Provider</label>
                    <select name="default_provider">
                        <option value="vidrock" <?php echo ($settings['default_provider'] == 'vidrock') ? 'selected' : ''; ?>>Vidrock.net (Primary)</option>
                        <option value="vidsrc" <?php echo ($settings['default_provider'] == 'vidsrc') ? 'selected' : ''; ?>>vidsrc.icu</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="autoplay" <?php echo ($settings['autoplay'] == 1) ? 'checked' : ''; ?> style="width: 18px; height: 18px; margin-right: 10px;">
                        <span>Enable Video Autoplay</span>
                    </label>
                </div>

                <h4 style="margin: 25px 0 10px; color: var(--primary-color); border-bottom: 1px solid #333; padding-bottom: 5px;">SMTP Configuration (Email)</h4>
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" placeholder="smtp.gmail.com">
                </div>
                <div class="dashboard-grid grid-50-50" style="gap: 15px; margin-bottom: 0;">
                    <div class="form-group">
                        <label>SMTP Port</label>
                        <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port']); ?>" placeholder="587">
                    </div>
                    <div class="form-group">
                        <label>Encryption</label>
                        <select name="smtp_crypto">
                            <option value="tls" <?php echo ($settings['smtp_crypto'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo ($settings['smtp_crypto'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                            <option value="none" <?php echo ($settings['smtp_crypto'] == 'none') ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>SMTP Username</label>
                    <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user']); ?>">
                </div>
                <div class="form-group">
                    <label>SMTP Password</label>
                    <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass']); ?>">
                </div>
            </div>

            <!-- Right Column: SEO, Ads & Social -->
            <div class="form-container">
                <h3><i class="fas fa-bullhorn"></i> SEO, Ads & Social</h3>
                
                <div class="form-group">
                    <label>Default Timezone</label>
                    <select name="timezone" class="select2">
                        <?php foreach($timezones as $tz): ?>
                            <option value="<?php echo $tz; ?>" <?php echo ($settings['timezone'] == $tz) ? 'selected' : ''; ?>><?php echo $tz; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h4 style="margin: 25px 0 10px; color: var(--primary-color); border-bottom: 1px solid #333; padding-bottom: 5px;">TMDB Configuration</h4>
                <div class="form-group">
                    <label>TMDB API Key (V3)</label>
                    <input type="text" name="tmdb_api_key" value="<?php echo htmlspecialchars($settings['tmdb_api_key']); ?>">
                </div>
                <div class="form-group">
                    <label>TMDB Language</label>
                    <input type="text" name="tmdb_language" value="<?php echo htmlspecialchars($settings['tmdb_language']); ?>" placeholder="en-US">
                </div>

                <h4 style="margin: 25px 0 10px; color: var(--primary-color); border-bottom: 1px solid #333; padding-bottom: 5px;">Social Media Links</h4>
                <div class="form-group">
                    <label><i class="fab fa-facebook"></i> Facebook URL</label>
                    <input type="text" name="facebook_url" value="<?php echo htmlspecialchars($settings['facebook_url']); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-twitter"></i> Twitter URL</label>
                    <input type="text" name="twitter_url" value="<?php echo htmlspecialchars($settings['twitter_url']); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-instagram"></i> Instagram URL</label>
                    <input type="text" name="instagram_url" value="<?php echo htmlspecialchars($settings['instagram_url']); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-youtube"></i> YouTube URL</label>
                    <input type="text" name="youtube_url" value="<?php echo htmlspecialchars($settings['youtube_url']); ?>">
                </div>

                <h4 style="margin: 20px 0 10px; color: var(--primary-color); border-bottom: 1px solid #333; padding-bottom: 5px;">Ad Management</h4>
                <div class="form-group">
                    <label>Header Ads (Inside &lt;head&gt;)</label>
                    <textarea name="ad_header" rows="3" placeholder="Paste Ad scripts here..."><?php echo htmlspecialchars($settings['ad_header']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Footer Ads (Before &lt;/body&gt;)</label>
                    <textarea name="ad_footer" rows="3" placeholder="Paste Ad scripts here..."><?php echo htmlspecialchars($settings['ad_footer']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Player Overlay Ads</label>
                    <textarea name="ad_player" rows="3" placeholder="Paste Ad scripts here..."><?php echo htmlspecialchars($settings['ad_player']); ?></textarea>
                </div>

                <h4 style="margin: 25px 0 10px; color: var(--primary-color); border-bottom: 1px solid #333; padding-bottom: 5px;">SEO Meta</h4>
                <div class="form-group">
                    <label>Meta Description</label>
                    <textarea name="description" rows="3"><?php echo htmlspecialchars($settings['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Meta Keywords</label>
                    <textarea name="keywords" rows="2"><?php echo htmlspecialchars($settings['keywords']); ?></textarea>
                </div>

                <div style="margin-top: 30px; text-align: right;">
                    <button type="submit" name="save_settings" class="btn btn-primary" style="padding: 12px 40px; width:100%;"><i class="fas fa-save"></i> Save All Advanced Settings</button>
                </div>
            </div>
        </div>
    </form>
<?php include 'includes/footer.php'; ?>
