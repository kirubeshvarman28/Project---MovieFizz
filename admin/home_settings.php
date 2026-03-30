<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) redirect('login.php');

$page_title = "Home Settings";
include 'includes/header.php';
?>

<div class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-home"></i> Home Settings</h2>
    </div>
</div>

<div class="form-container" style="margin: 20px;">
    <p>Manage your homepage sliders and sections here.</p>
    <div class="status-badge published" style="display:inline-block; margin-top:20px;"><i class="fas fa-check-circle"></i> Feature Coming Soon</div>
</div>

<?php include 'includes/footer.php'; ?>
