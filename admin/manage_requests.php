<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

// Delete Request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM media_requests WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Request deleted successfully.";
    } else {
        $error = "Failed to delete request.";
    }
}

// Update Status
if (isset($_GET['complete'])) {
    $id = $_GET['complete'];
    $stmt = $pdo->prepare("UPDATE media_requests SET status = 'completed' WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Request marked as completed.";
    }
}

// Fetch Requests
$stmt = $pdo->query("SELECT * FROM media_requests ORDER BY created_at DESC");
$requests = $stmt->fetchAll();

$page_title = "Manage Media Requests";
include INCLUDES_PATH . '/header.php';
?>

<div class="dashboard-grid">
    <div class="recent-section" style="grid-column: 1 / -1;">
        <div class="table-header-custom" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3><i class="fas fa-list"></i> Media Requests List</h3>
        </div>

        <?php if($success): ?><div class="status-badge published" style="display:block; margin-bottom: 20px;"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="status-badge draft" style="display:block; margin-bottom: 20px; background: #ff4444;"><?php echo $error; ?></div><?php endif; ?>

        <div class="table-responsive">
            <table class="refined-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User / Email</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="7" style="text-align:center; padding: 30px;">No requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>#<?php echo $request['id']; ?></td>
                                <td><?php echo htmlspecialchars($request['user_email']); ?></td>
                                <td><strong><?php echo htmlspecialchars($request['media_title']); ?></strong></td>
                                <td><span class="badge-on" style="background:#555;"><?php echo ucfirst($request['media_type']); ?></span></td>
                                <td>
                                    <?php if ($request['status'] === 'completed'): ?>
                                        <span class="status-badge published">Completed</span>
                                    <?php else: ?>
                                        <span class="status-badge draft">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                <td style="text-align: right;">
                                    <div class="actions" style="justify-content: flex-end;">
                                        <?php if ($request['status'] !== 'completed'): ?>
                                            <a href="manage_requests.php?complete=<?php echo $request['id']; ?>" class="action-icon edit" title="Mark Complete"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                        <a href="manage_requests.php?delete=<?php echo $request['id']; ?>" class="action-icon delete" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
