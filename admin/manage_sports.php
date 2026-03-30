<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) redirect('login.php');

$page_title = "Sports";
include 'includes/header.php';
?>

<div class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-basketball-ball"></i> Sports Management</h2>
    </div>
</div>

<div class="form-container" style="margin: 20px;">
    <p>Manage your sports categories and videos here.</p>
    <div class="status-badge published" style="display:inline-block; margin-top:20px;"><i class="fas fa-check-circle"></i> Module Active</div>
</div>

<?php include 'includes/footer.php'; ?>
