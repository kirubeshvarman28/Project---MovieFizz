<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Prevent self-deletion
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $success = "User deleted successfully!";
    }
}

// Fetch users
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$page_title = "User Management";
include 'includes/header.php';
?>

<div class="top-nav">
    <h2><i class="fas fa-users-cog"></i> User Management</h2>
    <div class="user-info">
        <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Users Table Full Width -->
    <div class="recent-section" style="grid-column: 1 / -1;">
        <div class="table-header-custom" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3><i class="fas fa-list"></i> Registered Users</h3>
            <div style="display:flex; gap:10px;">
                <input type="text" placeholder="Search Users..." style="padding:8px 15px; border-radius:5px; background:#333; border:1px solid #444; color:#fff;">
                <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <?php if($error): ?><div class="error-msg" style="margin-bottom:20px;"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="status-badge published" style="display:block; margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>

        <table class="refined-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Join Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?php echo $u['username']; ?></strong></td>
                    <td><?php echo $u['email']; ?></td>
                    <td><span style="color:#aaa; font-size:12px;"><?php echo ucfirst($u['role']); ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                    <td><span class="status-badge published">Active</span></td>
                    <td>
                        <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-danger" onclick="return confirm('Permanently delete this user?')" style="padding: 5px 10px;"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($users)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px;">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
