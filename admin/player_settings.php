<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$success = '';
$error = '';

// Fetch current settings
$settings = get_all_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_player_settings'])) {
    $player_vidrock = isset($_POST['player_vidrock']) ? 1 : 0;
    $player_superembed = isset($_POST['player_superembed']) ? 1 : 0;
    $player_vidlink = isset($_POST['player_vidlink']) ? 1 : 0;
    $default_provider = clean_input($_POST['default_provider'] ?? 'vidrock');
    $autoplay = isset($_POST['autoplay']) ? 1 : 0;
    
    // Ad settings (if any were added previously)
    $ad_player = $_POST['ad_player'] ?? '';

    try {
        $stmt = $pdo->prepare("UPDATE settings SET 
            player_vidrock = ?, 
            player_superembed = ?, 
            player_vidlink = ?, 
            default_provider = ?, 
            autoplay = ?,
            ad_player = ?
            WHERE id = 1");
        
        if ($stmt->execute([$player_vidrock, $player_superembed, $player_vidlink, $default_provider, $autoplay, $ad_player])) {
            $success = "Player settings updated successfully!";
            $settings = get_all_settings(); // Refresh
        } else {
            $error = "Failed to update settings.";
        }
    } catch (Exception $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

$page_title = "Player Settings";
include INCLUDES_PATH . '/header.php';
?>

<div class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-play-circle"></i> Video Player Configuration</h2>
    </div>
</div>

<?php if ($success): ?><div class="status-badge published" style="display:block; margin: 20px;"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="status-badge draft" style="display:block; margin: 20px; background: #ff4444;"><?php echo $error; ?></div><?php endif; ?>

<div class="dashboard-grid">
    <div class="form-sections-column">
        <form method="POST" class="admin-form">
            <div class="form-container" style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;">
                    <i class="fas fa-cloud"></i> Cloud Importer Players
                </h3>
                <p class="text-muted small mb-4">Select which players should be automatically added when using the Cloud Importer.</p>
                
                <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; background: #252525; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                    <div>
                        <strong>VidRock.net</strong>
                        <small style="display: block; color: #888;">Primary cloud provider (Recommended)</small>
                    </div>
                    <div class="switch <?php echo ($settings['player_vidrock'] ?? 1) ? 'active' : ''; ?>" onclick="this.querySelector('input').click(); this.classList.toggle('active')">
                        <input type="checkbox" name="player_vidrock" <?php echo ($settings['player_vidrock'] ?? 1) ? 'checked' : ''; ?> style="display:none;">
                    </div>
                </div>

                <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; background: #252525; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                    <div>
                        <strong>SuperEmbed</strong>
                        <small style="display: block; color: #888;">Secondary multi-player embed</small>
                    </div>
                    <div class="switch <?php echo ($settings['player_superembed'] ?? 1) ? 'active' : ''; ?>" onclick="this.querySelector('input').click(); this.classList.toggle('active')">
                        <input type="checkbox" name="player_superembed" <?php echo ($settings['player_superembed'] ?? 1) ? 'checked' : ''; ?> style="display:none;">
                    </div>
                </div>

                <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; background: #252525; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <div>
                        <strong>VidLink.pro</strong>
                        <small style="display: block; color: #888;">Fallback cloud provider</small>
                    </div>
                    <div class="switch <?php echo ($settings['player_vidlink'] ?? 1) ? 'active' : ''; ?>" onclick="this.querySelector('input').click(); this.classList.toggle('active')">
                        <input type="checkbox" name="player_vidlink" <?php echo ($settings['player_vidlink'] ?? 1) ? 'checked' : ''; ?> style="display:none;">
                    </div>
                </div>

                <div class="form-group">
                    <label>Default Fallback Provider</label>
                    <select name="default_provider" class="form-control">
                        <option value="vidrock" <?php echo ($settings['default_provider'] ?? '') === 'vidrock' ? 'selected' : ''; ?>>VidRock</option>
                        <option value="superembed" <?php echo ($settings['default_provider'] ?? '') === 'superembed' ? 'selected' : ''; ?>>SuperEmbed</option>
                        <option value="vidlink" <?php echo ($settings['default_provider'] ?? '') === 'vidlink' ? 'selected' : ''; ?>>VidLink</option>
                        <option value="vidsrc" <?php echo ($settings['default_provider'] ?? '') === 'vidsrc' ? 'selected' : ''; ?>>vidsrc.icu</option>
                    </select>
                    <small class="text-muted">Used as the main player if no manual source is set.</small>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 15px;">
                    <div class="switch <?php echo ($settings['autoplay'] ?? 0) ? 'active' : ''; ?>" onclick="this.querySelector('input').click(); this.classList.toggle('active')">
                        <input type="checkbox" name="autoplay" <?php echo ($settings['autoplay'] ?? 0) ? 'checked' : ''; ?> style="display:none;">
                    </div>
                    <label style="margin-bottom: 0; cursor: pointer;">Enable Video Autoplay</label>
                </div>
            </div>

            <div class="form-container">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;">
                    <i class="fas fa-ad"></i> Player Ads & Overlays
                </h3>
                <div class="form-group">
                    <label>Player Overlay Ads (HTML)</label>
                    <textarea name="ad_player" rows="4" placeholder="HTML for ads over the video player..."><?php echo htmlspecialchars($settings['ad_player'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="save_player_settings" class="btn btn-primary premium-save-btn">
                    <i class="fas fa-save"></i> Save Player Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
