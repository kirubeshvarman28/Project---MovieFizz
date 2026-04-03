<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

// Add Language
if (isset($_POST['add_language'])) {
    $name = clean_input($_POST['name']);
    $code = clean_input($_POST['code']);
    
    if (!empty($name) && !empty($code)) {
        $stmt = $pdo->prepare("INSERT INTO languages (name, code) VALUES (?, ?)");
        if ($stmt->execute([$name, $code])) {
            $success = "Language added successfully!";
        } else {
            $error = "Failed to add language.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

// Delete Language
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM languages WHERE id = ?");
    $stmt->execute([$id]);
    redirect('manage_languages.php');
}

// Toggle Status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE languages SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->execute([$id]);
    redirect('manage_languages.php');
}

$stmt = $pdo->query("SELECT * FROM languages ORDER BY name ASC");
$languages = $stmt->fetchAll();

$page_title = "Manage Languages";
include INCLUDES_PATH . '/header.php';


// Handle Sync
if (isset($_POST['sync_tmdb'])) {
    $tmdb_key = get_setting('tmdb_api_key', TMDB_API_KEY);
    $url = "https://api.themoviedb.org/3/configuration/languages?api_key=" . $tmdb_key;
    $data = fetch_from_api($url);
    if ($data && is_array($data)) {
        $count = 0;
        foreach ($data as $l) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO languages (name, code) VALUES (?, ?)");
            $stmt->execute([$l['english_name'], $l['iso_639_1']]);
            if ($stmt->rowCount() > 0) $count++;
        }
        $success = "Synced $count new languages from TMDB!";
    } else {
        $error = "Failed to fetch from TMDB. Check your API key.";
    }
}
?>

    <div class="top-nav">
        <h2>Manage Languages</h2>
        <div class="user-info">
            <form method="POST" style="display:inline;">
                <button type="submit" name="sync_tmdb" class="btn btn-secondary" style="background:#00d573;"><i class="fas fa-sync"></i> Sync from TMDB</button>
            </form>
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="dashboard-grid grid-50-50">
        <!-- Add Language Form -->
        <div class="form-container">
            <h3>Add New Language</h3>
            <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>
            <?php if($success): ?><div class="status-badge published" style="display:block; margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>
            
            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label>Language Name</label>
                    <input type="text" name="name" placeholder="e.g. English" required>
                </div>
                <div class="form-group">
                    <label>Language Code</label>
                    <input type="text" name="code" placeholder="e.g. en" required>
                </div>
                <button type="submit" name="add_language" class="btn btn-primary">Add Language</button>
            </form>
        </div>

        <!-- Languages List -->
        <div class="recent-section">
            <h3>Existing Languages</h3>
            <table class="refined-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($languages as $lang): ?>
                    <tr>
                        <td><?php echo $lang['id']; ?></td>
                        <td><?php echo $lang['name']; ?></td>
                        <td><?php echo $lang['code']; ?></td>
                        <td>
                            <a href="?toggle=<?php echo $lang['id']; ?>" class="status-badge <?php echo $lang['status'] == 'active' ? 'published' : 'unpublished'; ?>" style="text-decoration:none;">
                                <?php echo ucfirst($lang['status']); ?>
                            </a>
                        </td>
                        <td>
                            <a href="?delete=<?php echo $lang['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')" style="padding: 5px 10px; font-size: 12px;"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php include INCLUDES_PATH . '/footer.php'; ?>
