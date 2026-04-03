<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$success = '';

// In a real app, these would be saved to a 'settings' or 'gateways' table.
// For now, we'll simulate saving to config/db.

$page_title = "Payment Gateways";
include INCLUDES_PATH . '/header.php';
?>

<div class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-credit-card"></i> Payment Gateway Settings</h2>
    </div>
</div>

<div class="form-container" style="max-width: 600px;">
    <h3>Configure Gateways</h3>
    <form method="POST" class="admin-form">
        <div class="form-group">
            <label>Razorpay Key ID</label>
            <input type="text" name="razorpay_key" placeholder="rzp_test_...">
        </div>
        <div class="form-group">
            <label>Razorpay Secret</label>
            <input type="password" name="razorpay_secret" placeholder="********">
        </div>
        <hr style="margin:20px 0; border:0; border-top:1px solid #333;">
        <div class="form-group">
            <label>PayPal Client ID</label>
            <input type="text" name="paypal_id" placeholder="Client ID">
        </div>
        <div class="form-group">
            <label>Stripe Publishable Key</label>
            <input type="text" name="stripe_key" placeholder="pk_test_...">
        </div>
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
