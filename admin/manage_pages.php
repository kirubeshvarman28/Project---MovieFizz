<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

// Add Page
if (isset($_POST['add_page'])) {
    $title = clean_input($_POST['title']);
    $slug = strtolower(str_replace(' ', '-', $title));
    $content = $_POST['content'];

    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO static_pages (title, slug, content) VALUES (?, ?, ?)");
        $stmt->execute([$title, $slug, $content]);
        $success = "Page created!";
    }
}

// Delete Page
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM static_pages WHERE id = ?")->execute([$_GET['delete']]);
    redirect('manage_pages.php');
}

$stmt = $pdo->query("SELECT * FROM static_pages ORDER BY id DESC");
$pages = $stmt->fetchAll();

$page_title = "Static Pages";
include INCLUDES_PATH . '/header.php';
?>

<div class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-file-alt"></i> Manage Static Pages</h2>
    </div>
</div>

<div class="dashboard-grid grid-50-50">
    <div class="form-container">
        <h3>Add New Page</h3>
        <form method="POST" class="admin-form">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="e.g. Privacy Policy" required>
            </div>
            <div class="form-group">
                <label>Content (HTML allowed)</label>
                <textarea name="content" rows="6" placeholder="Page content here..."></textarea>
            </div>
            <button type="submit" name="add_page" class="btn btn-primary">Create Page</button>
        </form>
    </div>

    <div class="recent-section">
        <h3>Site Pages</h3>
        <table class="refined-table">
            <thead><tr><th>Title</th><th>Slug</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <tr>
                    <td><strong><?php echo $p['title']; ?></strong></td>
                    <td><span style="font-size:12px; color:var(--text-muted);">/page/<?php echo $p['slug']; ?></span></td>
                    <td>
                        <div style="display:flex; gap:10px;">
                            <a href="?delete=<?php echo $p['id']; ?>" class="action-icon delete" onclick="return confirm('Delete this page?')" title="Delete"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>
