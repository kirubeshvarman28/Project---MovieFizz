<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin())
    redirect('login.php');

$success = '';
$error = '';

// Fetch current settings
$settings = get_all_settings();

if (empty($settings)) {
    // If table exists but no row 1, or table missing
    try {
        $pdo->query("INSERT IGNORE INTO settings (id, site_name) VALUES (1, 'MovieFizz')");
        $settings = get_all_settings() ?: [];
    }
    catch (Exception $e) {
        $settings = [];
        $error = "Configuration error: " . $e->getMessage();
    }
}

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

    // Logo & Icon Upload Logic
    $site_logo = clean_input($_POST['site_logo_url'] ?? '');
    $site_icon = clean_input($_POST['site_icon_url'] ?? '');
    
    if (!is_dir('uploads/site')) mkdir('uploads/site', 0777, true);
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'];
    
    // Process Logo
    if (isset($_FILES['site_logo_file']) && $_FILES['site_logo_file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['site_logo_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $name = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['site_logo_file']['tmp_name'], 'uploads/site/' . $name)) {
                $site_logo = 'uploads/site/' . $name;
            }
        }
    }
    
    // Process Icon
    if (isset($_FILES['site_icon_file']) && $_FILES['site_icon_file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['site_icon_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $name = 'icon_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['site_icon_file']['tmp_name'], 'uploads/site/' . $name)) {
                $site_icon = 'uploads/site/' . $name;
            }
        }
    }

    // SMTP Settings
    $smtp_host = clean_input($_POST['smtp_host'] ?? '');
    $smtp_user = clean_input($_POST['smtp_user'] ?? '');
    $smtp_pass = clean_input($_POST['smtp_pass'] ?? '');
    $smtp_port = clean_input($_POST['smtp_port'] ?? '587');
    $smtp_crypto = clean_input($_POST['smtp_crypto'] ?? 'tls');

    // Player Preferences
    $default_provider = clean_input($_POST['default_provider'] ?? 'vidrock');
    $autoplay = isset($_POST['autoplay']) ? 1 : 0;
    $player_vidrock = isset($_POST['player_vidrock']) ? 1 : 0;
    $player_superembed = isset($_POST['player_superembed']) ? 1 : 0;
    $player_vidlink = isset($_POST['player_vidlink']) ? 1 : 0;

    // Ad Management
    $ad_header = $_POST['ad_header'] ?? '';
    $ad_footer = $_POST['ad_footer'] ?? '';
    $ad_player = $_POST['ad_player'] ?? '';

    $sql = "UPDATE settings SET 
            site_name = ?, site_logo = ?, site_icon = ?, email = ?, description = ?, keywords = ?, 
            timezone = ?, currency_code = ?, maintenance_mode = ?,
            tmdb_api_key = ?, tmdb_language = ?, 
            facebook_url = ?, twitter_url = ?, instagram_url = ?, youtube_url = ?,
            smtp_host = ?, smtp_user = ?, smtp_pass = ?, smtp_port = ?, smtp_crypto = ?,
            default_provider = ?, autoplay = ?, 
            player_vidrock = ?, player_superembed = ?, player_vidlink = ?,
            ad_header = ?, ad_footer = ?, ad_player = ?
            WHERE id = 1";

    try {
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([
        $site_name, $site_logo, $site_icon, $email, $description, $keywords,
        $timezone, $currency_code, $maintenance_mode,
        $tmdb_api_key, $tmdb_language,
        $facebook_url, $twitter_url, $instagram_url, $youtube_url,
        $smtp_host, $smtp_user, $smtp_pass, $smtp_port, $smtp_crypto,
        $default_provider, $autoplay,
        $player_vidrock, $player_superembed, $player_vidlink,
        $ad_header, $ad_footer, $ad_player
        ])) {
            $success = "Settings updated successfully!";
            // Refresh local settings variable
            $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
            $settings = $stmt->fetch();
        }
        else {
            $error = "Failed to update settings.";
        }
    } catch (Exception $e) {
        $error = "Database Error: " . $e->getMessage() . ". Please ensure you have run the update script (database_final_fix.sql).";
    }
}

$page_title = "Settings";
include INCLUDES_PATH . '/header.php';

// Get all timezones
$timezones = DateTimeZone::listIdentifiers();
?>

    <div class="top-nav">
        <h2>Advanced Site Settings</h2>
        <div class="user-info">
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <?php if ($success): ?><div class="status-badge published" style="display:block; margin: 20px;"><?php echo $success; ?></div><?php
endif; ?>
    <?php if ($error): ?><div class="status-badge draft" style="display:block; margin: 20px; background: #ff4444;"><?php echo $error; ?></div><?php
endif; ?>

    <form method="POST" class="admin-form" enctype="multipart/form-data">
        <div class="dashboard-grid">
            <!-- Left Column: Site Info & Features -->
            <div class="form-sections-column">
                <div class="form-container" style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;"><i class="fas fa-info-circle"></i> General Settings</h3>
                    
                    <div class="form-group maintenance-box" style="background: rgba(229, 9, 20, 0.05); padding: 20px; border-radius: 12px; border: 1px solid rgba(229, 9, 20, 0.2); margin-bottom: 25px;">
                        <label style="display: flex; align-items: center; cursor: pointer; justify-content: space-between; margin-bottom: 0;">
                            <span><i class="fas fa-tools" style="color: var(--primary-color);"></i> <strong>Maintenance Mode</strong></span>
                            <div class="switch <?php echo(isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == 1) ? 'active' : ''; ?>" onclick="this.querySelector('input').click(); this.classList.toggle('active')">
                                <input type="checkbox" name="maintenance_mode" value="1" <?php echo(isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == 1) ? 'checked' : ''; ?> style="display:none;">
                            </div>
                        </label>
                        <small style="display: block; margin-top: 10px; color: #888;">If enabled, visitors will see a maintenance page. Admins can still browse.</small>
                    </div>

                    <div class="form-group">
                        <label>Site Name*</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required placeholder="e.g. MovieFizz">
                    </div>

                    <div class="form-group">
                        <label>Admin Email*</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>" required placeholder="admin@example.com">
                    </div>

                    <div class="form-group">
                        <label>Default Timezone</label>
                        <select name="timezone" class="select2">
                            <?php foreach ($timezones as $tz): ?>
                                <option value="<?php echo $tz; ?>" <?php echo(isset($settings['timezone']) && $settings['timezone'] == $tz) ? 'selected' : ''; ?>><?php echo $tz; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-container" style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;"><i class="fas fa-image"></i> Branding & Logos</h3>
                    <div class="form-group">
                        <label>Site Logo</label>
                        <div style="display: flex; gap: 15px; align-items: start; margin-bottom: 15px; background: #252525; padding: 15px; border-radius: 8px;">
                            <div style="flex: 0 0 60px; text-align: center;">
                                <?php if(!empty($settings['site_logo'])): ?>
                                    <img src="<?php echo $settings['site_logo']; ?>" alt="Current Logo" style="max-width: 100%; max-height: 40px; border: 1px solid #444; border-radius: 4px; background: #000;">
                                <?php else: ?>
                                    <div style="width:40px; height:40px; background:#333; border-radius:4px; display:flex; align-items:center; justify-content:center;"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <input type="file" name="site_logo_file" accept="image/*" style="border: none; padding: 0; background: transparent;">
                                <small style="color: #666; display: block; margin-top: 5px;">Upload a new PNG or SVG logo.</small>
                            </div>
                        </div>
                        <input type="text" name="site_logo_url" value="<?php echo htmlspecialchars($settings['site_logo'] ?? ''); ?>" placeholder="Or enter Logo URL">
                    </div>

                    <div class="form-group">
                        <label>Site Icon / Favicon</label>
                        <div style="display: flex; gap: 15px; align-items: start; margin-bottom: 15px; background: #252525; padding: 15px; border-radius: 8px;">
                            <div style="flex: 0 0 60px; text-align: center;">
                                <?php if(!empty($settings['site_icon'])): ?>
                                    <img src="<?php echo $settings['site_icon']; ?>" alt="Current Icon" style="width: 32px; height: 32px; border: 1px solid #444; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width:32px; height:32px; background:#333; border-radius:4px; display:flex; align-items:center; justify-content:center; margin: 0 auto;"><i class="fas fa-icons"></i></div>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1;">
                                <input type="file" name="site_icon_file" accept="image/*" style="border: none; padding: 0; background: transparent;">
                                <small style="color: #666; display: block; margin-top: 5px;">Upload a 32x32 or 64x64 .ico or .png favicon.</small>
                            </div>
                        </div>
                        <input type="text" name="site_icon_url" value="<?php echo htmlspecialchars($settings['site_icon'] ?? ''); ?>" placeholder="Or enter Icon URL">
                    </div>
                </div>

                <div class="form-container">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;"><i class="fas fa-envelope"></i> SMTP Email Configuration</h3>
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" placeholder="e.g. smtp.gmail.com">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>" placeholder="587">
                        </div>
                        <div class="form-group">
                            <label>Encryption</label>
                            <select name="smtp_crypto">
                                <option value="tls" <?php echo(isset($settings['smtp_crypto']) && $settings['smtp_crypto'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo(isset($settings['smtp_crypto']) && $settings['smtp_crypto'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo(isset($settings['smtp_crypto']) && $settings['smtp_crypto'] == 'none') ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>SMTP Username</label>
                        <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>" placeholder="email@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>" placeholder="••••••••">
                    </div>
                </div>
            </div>

            <!-- Right Column: TMDB, SEO & Ads -->
            <div class="form-sections-column">
                <div class="form-container" style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;"><i class="fas fa-database"></i> External APIs</h3>
                    <div class="form-group">
                        <label>TMDB API Key (v3)</label>
                        <input type="text" name="tmdb_api_key" value="<?php echo htmlspecialchars($settings['tmdb_api_key'] ?? ''); ?>" placeholder="Paste your TMDB API Key">
                    </div>
                    <div class="form-group">
                        <label>TMDB Language</label>
                        <input type="text" name="tmdb_language" value="<?php echo htmlspecialchars($settings['tmdb_language'] ?? 'en-US'); ?>" placeholder="en-US">
                    </div>

                    <h4 style="margin: 25px 0 10px; color: var(--primary-color);">Player Preferences</h4>
                    <div class="form-group">
                        <label>Default Source Provider</label>
                        <select name="default_provider">
                            <option value="vidrock" <?php echo(isset($settings['default_provider']) && $settings['default_provider'] == 'vidrock') ? 'selected' : ''; ?>>Vidrock.net</option>
                            <option value="vidsrc" <?php echo(isset($settings['default_provider']) && $settings['default_provider'] == 'vidsrc') ? 'selected' : ''; ?>>vidsrc.icu</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 15px;">
                        <div class="switch <?php echo(isset($settings['autoplay']) && $settings['autoplay'] == 1) ? 'active' : ''; ?>" onclick="this.querySelector('input').click(); this.classList.toggle('active')">
                            <input type="checkbox" name="autoplay" <?php echo(isset($settings['autoplay']) && $settings['autoplay'] == 1) ? 'checked' : ''; ?> style="display:none;">
                        </div>
                        <label style="margin-bottom: 0; cursor: pointer;">Enable Video Autoplay</label>
                    </div>

                    <h4 style="margin: 25px 0 10px; color: var(--primary-color);">Cloud Importer Settings</h4>
                    <div style="display: flex; flex-direction: column; gap: 10px; background: #252525; padding: 15px; border-radius: 8px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0;">
                            <div class="switch <?php echo(isset($settings['player_vidrock']) ? $settings['player_vidrock'] : 1) ? 'active' : ''; ?>" onclick="this.querySelector('input').click(); this.classList.toggle('active')">
                                <input type="checkbox" name="player_vidrock" <?php echo(isset($settings['player_vidrock']) ? $settings['player_vidrock'] : 1) ? 'checked' : ''; ?> style="display:none;">
                            </div>
                            <span>Auto-add VidRock Source</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0;">
                            <div class="switch <?php echo(isset($settings['player_superembed']) ? $settings['player_superembed'] : 1) ? 'active' : ''; ?>" onclick="this.querySelector('input').click(); this.classList.toggle('active')">
                                <input type="checkbox" name="player_superembed" <?php echo(isset($settings['player_superembed']) ? $settings['player_superembed'] : 1) ? 'checked' : ''; ?> style="display:none;">
                            </div>
                            <span>Auto-add SuperEmbed Source</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0;">
                            <div class="switch <?php echo(isset($settings['player_vidlink']) ? $settings['player_vidlink'] : 1) ? 'active' : ''; ?>" onclick="this.querySelector('input').click(); this.classList.toggle('active')">
                                <input type="checkbox" name="player_vidlink" <?php echo(isset($settings['player_vidlink']) ? $settings['player_vidlink'] : 1) ? 'checked' : ''; ?> style="display:none;">
                            </div>
                            <span>Auto-add VidLink Source</span>
                        </label>
                    </div>
                </div>

                <div class="form-container" style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;"><i class="fas fa-search"></i> SEO & Meta Data</h3>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="description" rows="3" placeholder="Describe your site for Google..."><?php echo htmlspecialchars($settings['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Meta Keywords</label>
                        <textarea name="keywords" rows="2" placeholder="movies, streaming, tv shows..."><?php echo htmlspecialchars($settings['keywords'] ?? ''); ?></textarea>
                    </div>
                    
                    <h4 style="margin: 20px 0 10px; color: var(--primary-color);">Social Links</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <input type="text" name="facebook_url" value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>" placeholder="Facebook URL">
                        <input type="text" name="twitter_url" value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>" placeholder="Twitter URL">
                        <input type="text" name="instagram_url" value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>" placeholder="Instagram URL">
                        <input type="text" name="youtube_url" value="<?php echo htmlspecialchars($settings['youtube_url'] ?? ''); ?>" placeholder="YouTube URL">
                    </div>
                </div>

                <div class="form-container">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;"><i class="fas fa-ad"></i> Ad Management</h3>
                    <div class="form-group">
                        <label>Header Scripts (Inside &lt;head&gt;)</label>
                        <textarea name="ad_header" rows="2" placeholder="Google Analytics, AdSense auto-ads..."><?php echo htmlspecialchars($settings['ad_header'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Footer Scripts (Before &lt;/body&gt;)</label>
                        <textarea name="ad_footer" rows="2" placeholder="Pop-under ads, analytics trackers..."><?php echo htmlspecialchars($settings['ad_footer'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Player Overlay Ads (HTML)</label>
                        <textarea name="ad_player" rows="2" placeholder="HTML for ads over the video player..."><?php echo htmlspecialchars($settings['ad_player'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div style="position: sticky; bottom: 20px; text-align: center; margin-top: 30px; z-index: 100;">
            <button type="submit" name="save_settings" class="btn btn-primary premium-save-btn" style="padding: 15px 60px; box-shadow: 0 10px 25px rgba(229, 9, 20, 0.4); border-radius: 30px; font-size: 16px; width: auto;">
                <i class="fas fa-save"></i> Save All Site Settings
            </button>
        </div>
    </form>
<?php include INCLUDES_PATH . '/footer.php'; ?>
