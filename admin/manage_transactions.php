<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) redirect('login.php');

// Handle Filtering
$where = "1=1";
$params = [];

if (isset($_GET['gateway']) && !empty($_GET['gateway'])) {
    $where .= " AND t.gateway = ?";
    $params[] = $_GET['gateway'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where .= " AND (t.payment_id LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
    $search_term = "%" . $_GET['search'] . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql = "SELECT t.*, u.username, u.email, p.name as plan_name 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        JOIN subscription_plans p ON t.plan_id = p.id 
        WHERE $where 
        ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$page_title = "Billing & Transactions";
include 'includes/header.php';
?>

<div class="top-nav">
    <h2><i class="fas fa-file-invoice-dollar"></i> Billing & Transactions</h2>
    <div class="user-info">
        <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Transactions Table Full Width -->
    <div class="recent-section" style="grid-column: 1 / -1;">
        <div class="filter-bar" style="display:grid; grid-template-columns: 1fr 1.5fr 1fr 1fr; gap:15px; background:transparent; padding:0; margin-bottom:20px;">
            <select id="filterGateway" class="form-control">
                <option value="">Filter by Gateway</option>
                <option value="Cashfree" <?php echo (isset($_GET['gateway']) && $_GET['gateway'] == 'Cashfree') ? 'selected' : ''; ?>>Cashfree</option>
                <option value="Paystack" <?php echo (isset($_GET['gateway']) && $_GET['gateway'] == 'Paystack') ? 'selected' : ''; ?>>Paystack</option>
                <option value="Apple" <?php echo (isset($_GET['gateway']) && $_GET['gateway'] == 'Apple') ? 'selected' : ''; ?>>Apple</option>
            </select>
            <div style="position:relative;">
                <input type="text" id="transSearch" placeholder="Search By Payment ID OR Email..." style="width:100%; padding-left:35px;" value="<?php echo $_GET['search'] ?? ''; ?>">
                <i class="fas fa-search" style="position:absolute; left:12px; top:12px; color:#888;"></i>
            </div>
            <input type="date" id="filterDate">
            <button class="btn btn-secondary" style="background:#00d573;"><i class="fas fa-file-export"></i> Export CSV</button>
        </div>

        <table class="refined-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Gateway</th>
                    <th>Payment ID</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><strong><?php echo $t['username']; ?></strong></td>
                    <td><?php echo $t['email']; ?></td>
                    <td><span class="status-badge published"><?php echo $t['plan_name']; ?></span></td>
                    <td><strong>$<?php echo number_format($t['amount'], 2); ?></strong></td>
                    <td><?php echo $t['gateway'] ?: 'N/A'; ?></td>
                    <td><code style="background:#333; padding:2px 5px; border-radius:3px;"><?php echo $t['payment_id'] ?: 'N/A'; ?></code></td>
                    <td><?php echo date('M d, Y h:i A', strtotime($t['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($transactions)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:30px;">No transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination Placeholder -->
        <div style="margin-top:20px; display:flex; gap:10px; justify-content: flex-end;">
            <button class="btn btn-sm" style="background:#333;"><i class="fas fa-chevron-left"></i></button>
            <button class="btn btn-sm btn-primary">1</button>
            <button class="btn btn-sm" style="background:#333;">2</button>
            <button class="btn btn-sm" style="background:#333;"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

<script>
    document.getElementById('transSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') updateFilters();
    });
    document.getElementById('filterGateway').addEventListener('change', updateFilters);

    function updateFilters() {
        const search = document.getElementById('transSearch').value;
        const gateway = document.getElementById('filterGateway').value;
        window.location.href = `manage_transactions.php?search=${search}&gateway=${gateway}`;
    }
</script>

<?php include 'includes/footer.php'; ?>
