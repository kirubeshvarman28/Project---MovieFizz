<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) redirect('login.php');

// Simple settings handler (in a real app, this would update a single-row table or config file)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logic to save settings
    $success = "Settings updated!";
}

$page_title = "Player Settings";
include 'includes/header.php';
?>

<div class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-play-circle"></i> Video Player Configuration</h2>
    </div>
</div>

<div class="form-container" style="max-width: 700px;">
    <h3>Player & Ad Settings</h3>
    <form method="POST" class="admin-form">
        <div class="form-group">
            <label>Player Watermark URL</label>
            <input type="text" name="watermark_image" placeholder="Full image URL">
        </div>
        <div class="form-group">
            <label>Watermark Link</label>
            <input type="text" name="watermark_link" placeholder="https://...">
        </div>
        <hr style="margin:20px 0; border:0; border-top:1px solid #333;">
        <div class="form-group">
            <label>Advertisement Type</label>
            <select name="ad_type">
                <option value="none">None</option>
                <option value="vast">VAST (XML)</option>
                <option value="custom">Custom URL</option>
            </select>
        </div>
        <div class="form-group">
            <label>Ad URL / VAST URL</label>
            <input type="text" name="ad_url" placeholder="https://...">
        </div>
        <button type="submit" class="btn btn-primary">Save Player Settings</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
