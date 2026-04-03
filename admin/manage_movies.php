<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

// Fetch filters
$languages = $pdo->query("SELECT * FROM languages WHERE status = 1")->fetchAll();
$genres = $pdo->query("SELECT * FROM genres WHERE status = 1")->fetchAll();

// Handle basic filtering (logic can be expanded)
$where = "1=1";
$params = [];

if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $where .= " AND language = ?";
    $params[] = $_GET['lang'];
}

if (isset($_GET['genre']) && !empty($_GET['genre'])) {
    $where .= " AND genre LIKE ?";
    $params[] = "%" . $_GET['genre'] . "%";
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where .= " AND title LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}

$stmt = $pdo->prepare("SELECT * FROM movies WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$movies = $stmt->fetchAll();

$page_title = "Movies";
$msg = $_GET['msg'] ?? '';
$error_type = $_GET['error'] ?? '';

include INCLUDES_PATH . '/header.php';

?>

            <header class="top-nav">
                <div class="nav-left"><h2>Movies</h2></div>
                <div class="nav-right">
                    <a href="add_movie.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Movie</a>
                </div>
            </header>

            <?php if($msg === 'deleted'): ?><div class="success-msg">Movie deleted successfully!</div><?php endif; ?>
            <?php if($error_type): ?><div class="error-msg">Error: <?php echo $error_type; ?></div><?php endif; ?>

            <div class="filter-bar">
                <div style="flex: 1; position: relative;">
                    <input type="text" id="movieSearch" placeholder="Search By Title..." style="width: 100%; padding-left: 35px;" value="<?php echo $_GET['search'] ?? ''; ?>">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 12px; color: #888;"></i>
                </div>
                <select id="filterLang">
                    <option value="">Filter by Language</option>
                    <?php foreach($languages as $lang): ?>
                    <option value="<?php echo $lang['name']; ?>" <?php echo (isset($_GET['lang']) && $_GET['lang'] == $lang['name']) ? 'selected' : ''; ?>><?php echo $lang['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterGenre">
                    <option value="">Filter by genres</option>
                    <?php foreach($genres as $genre): ?>
                    <option value="<?php echo $genre['name']; ?>" <?php echo (isset($_GET['genre']) && $_GET['genre'] == $genre['name']) ? 'selected' : ''; ?>><?php echo $genre['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="display:flex; align-items:center; gap:10px; color:#ccc;">
                    <input type="checkbox" id="selectAll"> <label for="selectAll" style="cursor:pointer">Select All</label>
                    <div class="dropdown">
                        <button class="btn btn-secondary btn-sm" id="bulkActionBtn">Action <i class="fas fa-chevron-down"></i></button>
                        <div class="dropdown-content" id="bulkDropdown">
                            <a href="#" onclick="bulkDelete()"><i class="fas fa-trash"></i> Delete Selected</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="movie-grid">
                <?php foreach ($movies as $movie): ?>
                <div class="movie-card">
                    <div class="poster-wrapper">
                        <input type="checkbox" class="movie-select" value="<?php echo $movie['id']; ?>" style="position:absolute; top:10px; left:10px; z-index:10;">
                        <img src="../<?php echo $movie['poster']; ?>" alt="<?php echo $movie['title']; ?>">
                        <div class="lang-tag"><?php echo $movie['language'] ?? 'English'; ?></div>
                    </div>
                        <div class="card-info">
                            <h4><?php echo $movie['title']; ?></h4>
                            <div class="card-actions">
                                <a href="edit_movie.php?id=<?php echo $movie['id']; ?>" class="action-icon edit"><i class="fas fa-edit"></i></a>
                                <div class="action-icon delete" onclick="deleteItem(<?php echo $movie['id']; ?>, 'movie', this)" title="Delete"><i class="fas fa-times"></i></div>
                                <div class="switch <?php echo $movie['is_published'] ? 'active' : ''; ?>" 
                                     onclick="toggleStatus(<?php echo $movie['id']; ?>, this)" 
                                     title="Publish Status">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <script>
            function toggleStatus(id, element) {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('type', 'movie');

                fetch('toggle_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.new_status == 1) {
                            element.classList.add('active');
                        } else {
                            element.classList.remove('active');
                        }
                    } else {
                        alert(data.message || 'Error updating status');
                    }
                })
                .catch(err => alert('Network error'));
            }

            function deleteItem(id, type, element) {
            openDeleteModal(`Are you sure you want to permanently delete this movie?`, () => {
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
                        element.closest('.movie-card').remove();
                    } else {
                        alert(data.message || 'Error deleting item');
                    }
                })
                .catch(err => alert('Network error'));
            });
        }


            // Simple filter handling
        document.getElementById('movieSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') updateFilters();
        });
        document.getElementById('filterLang').addEventListener('change', updateFilters);
        document.getElementById('filterGenre').addEventListener('change', updateFilters);

        function updateFilters() {
            const search = document.getElementById('movieSearch').value;
            const lang = document.getElementById('filterLang').value;
            const genre = document.getElementById('filterGenre').value;
            window.location.href = `manage_movies.php?search=${search}&lang=${lang}&genre=${genre}`;
        }

        // --- Bulk Actions ---
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.movie-select');
        
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        function bulkDelete() {
            const selected = Array.from(document.querySelectorAll('.movie-select:checked')).map(cb => cb.value);
            if (selected.length === 0) return alert('Please select at least one movie');
            
            openDeleteModal(`Are you sure you want to permanently delete ${selected.length} movies?`, () => {
                const formData = new FormData();
                formData.append('ids', JSON.stringify(selected));
                formData.append('type', 'movie');
                formData.append('bulk', 'true');

                fetch('delete_ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error deleting items');
                    }
                })
                .catch(err => alert('Network error'));
            });
        }

        // Toggle bulk dropdown
        document.getElementById('bulkActionBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('bulkDropdown').classList.toggle('show');
        });
        window.onclick = function() {
            document.getElementById('bulkDropdown').classList.remove('show');
        }
    </script>
    <style>
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #2a2a2a;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 100;
            border-radius: 4px;
            right: 0;
        }
        .dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
        }
        .dropdown-content a:hover { background-color: #333; }
        .show { display: block; }
    </style>
<?php include INCLUDES_PATH . '/footer.php'; ?>
