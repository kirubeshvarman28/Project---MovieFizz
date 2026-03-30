<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

// Add Genre
if (isset($_POST['add_genre'])) {
    $name = clean_input($_POST['name']);
    $slug = strtolower(str_replace(' ', '-', $name));
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO genres (name, slug) VALUES (?, ?)");
        if ($stmt->execute([$name, $slug])) {
            $success = "Genre added successfully!";
        } else {
            $error = "Failed to add genre.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

// Delete Genre
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM genres WHERE id = ?");
    $stmt->execute([$id]);
    redirect('manage_genres.php');
}

// Toggle Status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE genres SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->execute([$id]);
    redirect('manage_genres.php');
}

$stmt = $pdo->query("SELECT * FROM genres ORDER BY name ASC");
$genres = $stmt->fetchAll();

$page_title = "Manage Genres";
include 'includes/header.php';


// Handle Sync
if (isset($_POST['sync_tmdb'])) {
    $tmdb_key = get_setting('tmdb_api_key', TMDB_API_KEY);
    $url = "https://api.themoviedb.org/3/genre/movie/list?api_key=" . $tmdb_key;
    $data = fetch_from_api($url);
    if ($data && isset($data['genres'])) {
        $count = 0;
        foreach ($data['genres'] as $g) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO genres (name, slug) VALUES (?, ?)");
            $stmt->execute([$g['name'], strtolower(str_replace(' ', '-', $g['name']))]);
            if ($stmt->rowCount() > 0) $count++;
        }
        $success = "Synced $count new genres from TMDB!";
    } else {
        $error = "Failed to fetch from TMDB. Check your API key.";
    }
}
?>


    <div class="top-nav">
        <h2>Manage Genres</h2>
        <div class="user-info">
            <form method="POST" style="display:inline;">
                <button type="submit" name="sync_tmdb" class="btn btn-secondary" style="background:#00d573;"><i class="fas fa-sync"></i> Sync from TMDB</button>
            </form>
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="dashboard-grid grid-50-50">
        <!-- Add Genre Form -->
        <div class="form-container">
            <h3>Add New Genre</h3>
            <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>
            <?php if($success): ?><div class="status-badge published" style="display:block; margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>
            
            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label>Genre Name</label>
                    <input type="text" name="name" placeholder="e.g. Action" required>
                </div>
                <button type="submit" name="add_genre" class="btn btn-primary">Add Genre</button>
            </form>
        </div>

        <!-- Genres List -->
        <div class="recent-section">
            <h3>Existing Genres</h3>
            <table class="refined-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($genres as $genre): ?>
                    <tr>
                        <td><?php echo $genre['id']; ?></td>
                        <td><?php echo $genre['name']; ?></td>
                        <td><?php echo $genre['slug']; ?></td>
                        <td>
                            <a href="?toggle=<?php echo $genre['id']; ?>" class="status-badge <?php echo $genre['status'] == 'active' ? 'published' : 'unpublished'; ?>" style="text-decoration:none;">
                                <?php echo ucfirst($genre['status']); ?>
                            </a>
                        </td>
                        <td>
                            <a href="?delete=<?php echo $genre['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')" style="padding: 5px 10px; font-size: 12px;"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>
