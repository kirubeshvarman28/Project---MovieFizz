<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

// Add Cast
if (isset($_POST['add_cast'])) {
    $name = clean_input($_POST['name']);
    $role = clean_input($_POST['role']);
    $photo = clean_input($_POST['photo']);
    $bio = clean_input($_POST['bio']);

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO cast_crew (name, role, photo, bio) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $role, $photo, $bio]);
        $success = "Cast member added!";
    }
}

// Delete Cast
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM cast_crew WHERE id = ?")->execute([$_GET['delete']]);
    redirect('manage_cast.php');
}

$stmt = $pdo->query("SELECT * FROM cast_crew ORDER BY id DESC");
$cast_list = $stmt->fetchAll();

$page_title = "Manage Cast & Crew";
include INCLUDES_PATH . '/header.php';

?>


    <div class="top-nav">
        <h2>Manage Cast & Crew</h2>
        <div class="user-info"><a href="logout.php">Logout</a></div>
    </div>

    <div class="dashboard-grid grid-50-50">
        <div class="form-container">
            <h3>Add Member</h3>
            <form method="POST" class="admin-form">
                <input type="text" name="name" placeholder="Name" required>
                <input type="text" name="role" placeholder="Role (e.g. Actor, Director)">
                <input type="text" name="photo" placeholder="Photo URL">
                <textarea name="bio" placeholder="Biography" rows="3"></textarea>
                <button type="submit" name="add_cast" class="btn btn-primary">Add Member</button>
            </form>
        </div>

        <div class="recent-section">
            <h3>Cast & Crew List</h3>
            <table>
                <thead><tr><th>Photo</th><th>Name</th><th>Role</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($cast_list as $c): ?>
                    <tr>
                        <td><img src="<?php echo $c['photo']; ?>" width="40" style="border-radius:50%;"></td>
                        <td><?php echo $c['name']; ?></td>
                        <td><?php echo $c['role']; ?></td>
                        <td><a href="?delete=<?php echo $c['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete?')">Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php include INCLUDES_PATH . '/footer.php'; ?>
