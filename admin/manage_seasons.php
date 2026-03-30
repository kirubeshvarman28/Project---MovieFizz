<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

$show_id = $_GET['show_id'] ?? 0;

// Add Season
if (isset($_POST['add_season'])) {
    $tv_show_id = $_POST['tv_show_id'];
    $season_number = $_POST['season_number'];
    $title = clean_input($_POST['title']);
    $poster = clean_input($_POST['poster']);

    if (!empty($tv_show_id) && !empty($season_number)) {
        $stmt = $pdo->prepare("INSERT INTO seasons (tv_show_id, season_number, title, poster) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$tv_show_id, $season_number, $title, $poster])) {
            $success = "Season added successfully!";
        } else {
            $error = "Failed to add season.";
        }
    } else {
        $error = "Show and Season Number are required.";
    }
}


$stmt = $pdo->query("SELECT * FROM tv_shows ORDER BY title ASC");
$all_shows = $stmt->fetchAll();

$seasons = [];
if ($show_id) {
    $stmt = $pdo->prepare("SELECT s.*, t.title as show_title FROM seasons s JOIN tv_shows t ON s.tv_show_id = t.id WHERE s.tv_show_id = ? ORDER BY s.season_number ASC");
    $stmt->execute([$show_id]);
    $seasons = $stmt->fetchAll();
}

include 'includes/header.php';
?>

<div class="top-nav">
        <h2>Manage Seasons</h2>
        <div class="user-info">
            <a href="logout.php" class="logout-link">Logout</a>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="form-container">
            <h3>Add New Season</h3>
            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label>Select Show</label>
                    <div class="searchable-select" style="position: relative;">
                        <input type="text" id="show_search" placeholder="Search & Select Show..." value="<?php echo $show_id ? htmlspecialchars(array_column($all_shows, 'title', 'id')[$show_id] ?? '') : ''; ?>" autocomplete="off" style="width: 100%; padding: 12px; background: #000; border: 1px solid #333; border-radius: 8px; color: #fff;">
                        <input type="hidden" name="tv_show_id" id="tv_show_id_hidden" value="<?php echo $show_id; ?>">
                        <div id="show_options" class="options-list" style="display: none; position: absolute; width: 100%; max-height: 250px; overflow-y: auto; background: #1a1a1a; border: 1px solid #333; z-index: 100; border-radius: 0 0 8px 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                            <?php foreach($all_shows as $s): ?>
                                <div class="option-item" data-id="<?php echo $s['id']; ?>" style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #222; transition: background 0.2s;">
                                    <?php echo htmlspecialchars($s['title']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Season Number</label>
                    <input type="number" name="season_number" required>
                </div>
                <div class="form-group">
                    <label>Title (Optional)</label>
                    <input type="text" name="title" placeholder="e.g. Season One">
                </div>
                <div class="form-group">
                    <label>Poster URL</label>
                    <input type="text" name="poster">
                </div>
                <button type="submit" name="add_season" class="btn btn-primary">Add Season</button>
            </form>
        </div>

        <div class="recent-section">
            <h3>Seasons Listing</h3>
            <?php if(!$show_id): ?><p>Please select a show to view seasons.</p><?php else: ?>
            <table class="refined-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Series</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seasons as $season): ?>
                    <tr>
                        <td>S<?php echo $season['season_number']; ?></td>
                        <td><?php echo $season['title'] ?: 'Season '.$season['season_number']; ?></td>
                        <td><?php echo $season['show_title']; ?></td>
                        <td>
                            <div style="display:flex; gap:10px;">
                                <a href="manage_episodes.php?season_id=<?php echo $season['id']; ?>" class="action-icon edit" title="Episodes"><i class="fas fa-list"></i></a>
                                <div class="action-icon delete" onclick="deleteItem(<?php echo $season['id']; ?>, 'season', this)" title="Delete"><i class="fas fa-trash"></i></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>

<script>
        // Searchable Select Logic
        const searchInput = document.getElementById('show_search');
        const optionsList = document.getElementById('show_options');
        const hiddenInput = document.getElementById('tv_show_id_hidden');
        const options = optionsList.querySelectorAll('.option-item');

        searchInput.addEventListener('focus', () => {
            optionsList.style.display = 'block';
        });

        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !optionsList.contains(e.target)) {
                optionsList.style.display = 'none';
            }
        });

        searchInput.addEventListener('keyup', () => {
            const query = searchInput.value.toLowerCase();
            options.forEach(opt => {
                const text = opt.innerText.toLowerCase();
                opt.style.display = text.includes(query) ? 'block' : 'none';
            });
            optionsList.style.display = 'block';
        });

        options.forEach(opt => {
            opt.addEventListener('click', () => {
                const id = opt.getAttribute('data-id');
                const text = opt.innerText;
                hiddenInput.value = id;
                searchInput.value = text;
                optionsList.style.display = 'none';
                window.location.href = '?show_id=' + id;
            });
            opt.addEventListener('mouseover', () => opt.style.background = '#e50914');
            opt.addEventListener('mouseout', () => opt.style.background = 'transparent');
        });

        function deleteItem(id, type, element) {
            openDeleteModal(`Are you sure you want to permanently delete this season? All episodes will be lost!`, () => {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('type', type);

                fetch('delete_ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        element.closest('tr').remove();
                    } else {
                        alert(data.message || 'Error deleting item');
                    }
                })
                .catch(err => alert('Network error'));
            });
        }
</script>

<?php include 'includes/footer.php'; ?>
