<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

// Add Coupon
if (isset($_POST['add_coupon'])) {
    $code = strtoupper(clean_input($_POST['code']));
    $type = $_POST['discount_type'];
    $value = $_POST['value'];
    $expiry = $_POST['expiry_date'];
    $status = $_POST['status'];

    if (!empty($code) && !empty($value)) {
        $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, value, expiry_date, status) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$code, $type, $value, $expiry, $status])) {
            $success = "Coupon created successfully!";
        } else {
            $error = "Failed to create coupon.";
        }
    }
}

// Delete Coupon
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$_GET['delete']]);
    redirect('manage_coupons.php');
}

$stmt = $pdo->query("SELECT * FROM coupons ORDER BY id DESC");
$coupons = $stmt->fetchAll();

$page_title = "Coupons & Offers";
include 'includes/header.php';
?>

<div class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-ticket-alt"></i> Coupons & Offers</h2>
    </div>
</div>

<div class="dashboard-grid grid-50-50">
    <!-- Add Coupon Form -->
    <div class="form-container">
        <h3><i class="fas fa-plus-circle"></i> Create New Coupon</h3>
        <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="status-badge published" style="display:block; margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>
        
        <form method="POST" class="admin-form">
            <div class="form-group">
                <label>Coupon Code</label>
                <input type="text" name="code" required placeholder="e.g. SAVE50" style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Discount Type</label>
                <select name="discount_type">
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount ($)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Discount Value</label>
                <input type="number" step="0.01" name="value" required placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" name="expiry_date" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" name="add_coupon" class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Create Coupon</button>
        </form>
    </div>

    <!-- Coupons List -->
    <div class="recent-section">
        <h3><i class="fas fa-list"></i> Existing Coupons</h3>
        <table class="refined-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Discount</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $c): ?>
                <tr>
                    <td><strong><?php echo $c['code']; ?></strong></td>
                    <td><?php echo ($c['discount_type'] == 'percentage') ? $c['value'].'%' : '$'.$c['value']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($c['expiry_date'])); ?></td>
                    <td>
                        <span class="status-badge <?php echo ($c['status'] == 'active') ? 'published' : 'unpublished'; ?>">
                            <?php echo ucfirst($c['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex; gap:10px;">
                            <a href="?delete=<?php echo $c['id']; ?>" class="action-icon delete" onclick="return confirm('Delete this coupon?')" title="Delete"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($coupons)): ?>
                    <tr><td colspan="5" style="text-align:center; padding:30px;">No coupons found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
