<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

// Add Plan
if (isset($_POST['add_plan'])) {
    $name = clean_input($_POST['name']);
    $price = $_POST['price'];
    $duration_val = $_POST['duration_val'];
    $duration_unit = $_POST['duration_unit'];
    $device_limit = $_POST['device_limit'];
    $ads_status = $_POST['ads_status'];
    
    $duration = "$duration_val $duration_unit";

    if (!empty($name) && !empty($price)) {
        $stmt = $pdo->prepare("INSERT INTO subscription_plans (name, price, duration, device_limit, ads_status) VALUES (?, ?, ?, ?, ?)");
        if($stmt->execute([$name, $price, $duration, $device_limit, $ads_status])) {
            $success = "Plan added successfully!";
        } else {
            $error = "Failed to add plan.";
        }
    }
}

// Delete Plan
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM subscription_plans WHERE id = ?")->execute([$_GET['delete']]);
    redirect('manage_plans.php');
}

$stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC");
$plans = $stmt->fetchAll();

$page_title = "Subscription Plans";
include INCLUDES_PATH . '/header.php';
?>

<div class="top-nav">
    <h2><i class="fas fa-membership"></i> Subscription Plans</h2>
    <div class="user-info">
        <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="dashboard-grid grid-50-50">
    <!-- Add Plan Form -->
    <div class="form-container">
        <h3><i class="fas fa-plus-circle"></i> Create New Plan</h3>
        <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="status-badge published" style="display:block; margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>
        
        <form method="POST" class="admin-form">
            <div class="form-group">
                <label>Plan Name</label>
                <input type="text" name="name" required placeholder="e.g. Premium Monthly">
            </div>
            <div class="form-group">
                <label>Price ($)</label>
                <input type="number" step="0.01" name="price" required placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Duration</label>
                <div style="display:flex; gap:10px;">
                    <input type="number" name="duration_val" value="1" style="width:70px;">
                    <select name="duration_unit">
                        <option value="Month">Month</option>
                        <option value="Year">Year</option>
                        <option value="Days">Days</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Device Limit</label>
                <input type="number" name="device_limit" value="1">
            </div>
            <div class="form-group">
                <label>Ads Status</label>
                <select name="ads_status">
                    <option value="ON">ON (Show Ads)</option>
                    <option value="OFF">OFF (No Ads)</option>
                </select>
            </div>
            <button type="submit" name="add_plan" class="btn btn-primary" style="width:100%"><i class="fas fa-save"></i> Save Plan</button>
        </form>
    </div>

    <!-- Plans List -->
    <div class="recent-section">
        <h3><i class="fas fa-list"></i> Existing Plans</h3>
        <table class="refined-table">
            <thead>
                <tr>
                    <th>Plan Name</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Devices</th>
                    <th>Ads</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $p): ?>
                <tr>
                    <td><strong><?php echo $p['name']; ?></strong></td>
                    <td><?php echo $p['duration']; ?></td>
                    <td>$<?php echo number_format($p['price'], 2); ?></td>
                    <td><?php echo $p['device_limit']; ?></td>
                    <td><?php echo ($p['ads_status'] == 'ON') ? '<span class="status-badge unpublished">Ads ON</span>' : '<span class="status-badge published">Ads OFF</span>'; ?></td>
                    <td>
                        <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this plan?')" style="padding: 5px 10px;"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($plans)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px;">No plans found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
