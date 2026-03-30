<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check admin auth
if (!is_admin()) {
    redirect('login.php');
}

// Fetch stats
$movies_count = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
$users_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$featured_count = $pdo->query("SELECT COUNT(*) FROM movies WHERE is_featured = 1")->fetchColumn();

// Fetch recent movies
$stmt = $pdo->query("SELECT * FROM movies ORDER BY created_at DESC LIMIT 5");
$recent_movies = $stmt->fetchAll();

// Fetch recent users
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

$page_title = "Admin Dashboard";
include 'includes/header.php';
?>

<div class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-home"></i> Admin Dashboard</h2>
    </div>
    <div class="nav-right">
        <span style="margin-right:20px; color:var(--text-muted);">Welcome back, <strong><?php echo $_SESSION['username']; ?></strong></span>
        <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-video"></i></div>
        <div class="stat-info">
            <h3>Total Movies</h3>
            <p><?php echo $movies_count; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(46, 204, 113, 0.1); color: #2ecc71;"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>Total Users</h3>
            <p><?php echo $users_count; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background-color: rgba(52, 152, 219, 0.1); color: #3498db;"><i class="fas fa-star"></i></div>
        <div class="stat-info">
            <h3>Featured</h3>
            <p><?php echo $featured_count; ?></p>
        </div>
    </div>
</div>

<div class="dashboard-grid grid-50-50">
    <div class="recent-section">
        <h3><i class="fas fa-history"></i> Recent Uploads</h3>
        <table class="refined-table">
            <thead>
                <tr>
                    <th>Movie</th>
                    <th>Genre</th>
                    <th>Year</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_movies as $movie): ?>
                <tr>
                    <td><strong><?php echo $movie['title']; ?></strong></td>
                    <td><span style="font-size:12px; color:var(--text-muted);"><?php echo $movie['genre']; ?></span></td>
                    <td><?php echo $movie['release_year']; ?></td>
                    <td><?php echo $movie['is_published'] ? '<span class="status-badge published">Published</span>' : '<span class="status-badge unpublished">No</span>'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:20px; text-align:right;">
            <a href="manage_movies.php" style="color:var(--primary-color); text-decoration:none; font-size:14px;">View All Movies <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>

    <div class="recent-section">
        <h3><i class="fas fa-user-plus"></i> New Users</h3>
        <table class="refined-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Join Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_users as $user): ?>
                <tr>
                    <td><strong><?php echo $user['username']; ?></strong></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:20px; text-align:right;">
            <a href="manage_users.php" style="color:var(--primary-color); text-decoration:none; font-size:14px;">View All Users <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
