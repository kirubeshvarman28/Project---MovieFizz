<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

// Add TV Show
if (isset($_POST['add_show'])) {
    $dirs = [UPLOADS_PATH . '/posters', UPLOADS_PATH . '/backdrops'];
    foreach ($dirs as $dir) { if (!is_dir($dir)) @mkdir($dir, 0755, true); }

    try {
    $tmdb_id = clean_input($_POST['tmdb_id']);
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $genre = clean_input($_POST['genre']);
    $rating = clean_input($_POST['rating']);
    $poster = clean_input($_POST['poster']);
    $backdrop = clean_input($_POST['backdrop']);

    if (!empty($title)) {
        $language = clean_input($_POST['language'] ?? 'English');
        // Download images locally if from TMDB
        if (strpos($poster, 'image.tmdb.org') !== false) {
            $ext = pathinfo($poster, PATHINFO_EXTENSION) ?: 'jpg';
            $p_name = time() . '_poster.' . $ext;
            if (download_image($poster, UPLOADS_PATH . '/posters/' . $p_name)) {
                $poster = 'uploads/posters/' . $p_name;
            }
        }
        if (strpos($backdrop, 'image.tmdb.org') !== false) {
            $ext = pathinfo($backdrop, PATHINFO_EXTENSION) ?: 'jpg';
            $b_name = time() . '_backdrop.' . $ext;
            if (download_image($backdrop, UPLOADS_PATH . '/backdrops/' . $b_name)) {
                $backdrop = 'uploads/backdrops/' . $b_name;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO tv_shows (tmdb_id, title, description, genre, rating, language, poster, backdrop) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$tmdb_id, $title, $description, $genre, $rating, $language, $poster, $backdrop])) {
            $show_id = $pdo->lastInsertId();
            $success = "TV Show added successfully!";

            // Sync Cast & Language from TMDB
            $tmdb_data_raw = $_POST['tmdb_data'] ?? '';
            if (!empty($tmdb_data_raw)) {
                $tmdb_data = json_decode($tmdb_data_raw, true);
                
                // 1. Language Sync
                if (isset($tmdb_data['original_language'])) {
                    $l_code = $tmdb_data['original_language'];
                    $stmt_l = $pdo->prepare("SELECT name FROM languages WHERE code = ?");
                    $stmt_l->execute([$l_code]);
                    $l_exists = $stmt_l->fetch();
                    if (!$l_exists) {
                        $pdo->prepare("INSERT IGNORE INTO languages (name, code, status) VALUES (?, ?, 1)")->execute([strtoupper($l_code), $l_code]);
                    }
                }

                // 2. Cast Sync
                if (isset($tmdb_data['credits']['cast'])) {
                    $cast = array_slice($tmdb_data['credits']['cast'], 0, 10);
                    foreach ($cast as $c) {
                        $c_name = clean_input($c['name']);
                        $c_tmdb_id = $c['id'];
                        $c_image = $c['profile_path'] ? 'https://image.tmdb.org/t/p/w200' . $c['profile_path'] : '';
                        
                        $stmt_c = $pdo->prepare("SELECT id FROM cast_crew WHERE tmdb_id = ?");
                        $stmt_c->execute([$c_tmdb_id]);
                        $c_id_row = $stmt_c->fetch();
                        
                        if ($c_id_row) {
                            $cast_id = $c_id_row['id'];
                        } else {
                            $pdo->prepare("INSERT INTO cast_crew (name, image, tmdb_id, type) VALUES (?, ?, ?, 'Acting')")->execute([$c_name, $c_image, $c_tmdb_id]);
                            $cast_id = $pdo->lastInsertId();
                        }
                        
                        $pdo->prepare("INSERT IGNORE INTO tv_show_cast (tv_show_id, cast_id, role) VALUES (?, ?, ?)")->execute([$show_id, $cast_id, $c['character'] ?? '']);
                    }
                }
                }
            }
        }
    } catch (Exception $e) {
        $error = "Failed to add series: " . $e->getMessage();
    }
}

// Delete TV Show
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM tv_shows WHERE id = ?");
    $stmt->execute([$id]);
    redirect('manage_shows.php');
}

$stmt = $pdo->query("SELECT * FROM tv_shows ORDER BY created_at DESC");
$shows = $stmt->fetchAll();

$msg = $_GET['msg'] ?? '';
$err_type = $_GET['error'] ?? '';

$page_title = "TV Shows";
include INCLUDES_PATH . '/header.php';
?>

<div class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-tv"></i> Manage TV Shows</h2>
    </div>
    <div class="nav-right" style="display:flex; align-items:center; gap:20px;">
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
</div>

<?php if($msg === 'deleted'): ?><div class="success-msg">TV Show deleted successfully!</div><?php endif; ?>
<?php if($err_type): ?><div class="error-msg">Error: <?php echo $err_type; ?></div><?php endif; ?>
<?php if($success): ?><div class="success-msg"><?php echo $success; ?></div><?php endif; ?>
<?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

<div class="dashboard-grid grid-50-50">

    <!-- Add Show Form -->
    <div class="form-container">
        <h3><i class="fas fa-plus-circle"></i> Add New TV Show (from TMDB)</h3>
        <div class="admin-form" style="background: rgba(229, 9, 20, 0.05); padding: 20px; border-radius: 12px; border: 1px solid rgba(229, 9, 20, 0.2); margin-bottom: 30px;">
            <div style="display:flex; gap:10px; margin-bottom:12px;">
                <input type="text" id="tmdb_query" placeholder="Enter Series Title..." style="flex:1; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px;">
                <button type="button" id="btn_fetch" class="btn btn-primary" style="min-width:100px;">Fetch</button>
            </div>
            <!-- TMDB ID Fetch Hint -->
            <div style="background: rgba(229, 9, 20, 0.08); padding: 12px; border-radius: 8px; border: 1px solid rgba(229, 9, 20, 0.3); margin-top: 10px;">
                <p style="font-size: 13px; color: #fff; margin: 0;">
                    <i class="fas fa-lightbulb" style="color: #e50914; margin-right: 8px;"></i>
                    <strong>TMDB ID:</strong> The number in the URL (e.g., .../tv/<strong>60735</strong>)
                </p>
            </div>
        </div>
        
        <form method="POST" class="admin-form">
            <input type="hidden" name="tmdb_id" id="show_tmdb_id">
            <input type="hidden" name="tmdb_data" id="show_tmdb_data">
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" id="show_title" required>
                </div>
                <div class="form-group">
                    <label>Genre</label>
                    <input type="text" name="genre" id="show_genre">
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="show_description" rows="4"></textarea>
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label>Rating</label>
                    <input type="number" step="0.1" name="rating" id="show_rating">
                </div>
                <div class="form-group">
                    <label>Language</label>
                    <input type="text" name="language" id="show_language" placeholder="e.g. English, Hindi">
                </div>
                <div class="form-group">
                    <label>Release Year</label>
                    <input type="number" name="release_year" id="show_year">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Poster URL</label>
                    <input type="text" name="poster" id="show_poster">
                </div>
                <div class="form-group">
                    <label>Backdrop URL</label>
                    <input type="text" name="backdrop" id="show_backdrop">
                </div>
            </div>

            <div class="form-group" id="cast_preview_section" style="display:none; background:#1a1a1a; padding:15px; border-radius:8px; margin-top:10px;">
                <label style="display:block; margin-bottom:10px;"><i class="fas fa-users"></i> Fetched Cast</label>
                <div id="show_cast_list" style="display:flex; gap:10px; overflow-x:auto; padding-bottom:10px;">
                </div>
            </div>
            <button type="submit" name="add_show" class="btn btn-primary" style="width:100%; padding:15px; font-weight:bold;">Add Series</button>
        </form>
    </div>

    <!-- Shows List -->
    <div class="recent-section">
        <h3><i class="fas fa-history"></i> Recent Series</h3>
        <table class="refined-table">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" disabled></th>
                    <th>Poster</th>
                    <th>Title</th>
                    <th>Genre</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shows as $show): ?>
                <tr>
                    <td><input type="checkbox" class="show-select" value="<?php echo $show['id']; ?>"></td>
                    <td>
                        <?php 
                            $poster_src = $show['poster'];
                            if (strpos($poster_src, 'http') === 0) {
                                $img_url = $poster_src;
                            } else {
                                $img_url = SITE_URL . '/' . $poster_src;
                            }
                        ?>
                        <img src="<?php echo $img_url; ?>" width="50" height="75" style="border-radius:4px; object-fit:cover;">
                    </td>
                    <td><strong><?php echo $show['title']; ?></strong></td>
                    <td><span style="font-size:12px; color:#888;"><?php echo $show['genre']; ?></span></td>
                    <td><i class="fas fa-star" style="color:#f1c40f;"></i> <?php echo number_format($show['rating'], 1); ?></td>
                    <td>
                        <div class="switch <?php echo ($show['status'] ?? 1) ? 'active' : ''; ?>" 
                             onclick="toggleStatus(<?php echo $show['id']; ?>, this)" 
                             title="Status">
                        </div>
                    </td>
                    <td>
                        <div style="display:flex; gap:10px;">
                            <a href="manage_seasons.php?show_id=<?php echo $show['id']; ?>" class="action-icon edit" title="Seasons"><i class="fas fa-list"></i></a>
                            <div class="action-icon delete" onclick="deleteItem(<?php echo $show['id']; ?>, 'tv', this)" title="Delete"><i class="fas fa-trash"></i></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function populateShowForm(data) {
    document.getElementById('show_tmdb_id').value = data.id;
    document.getElementById('show_title').value = data.name || data.original_name;
    document.getElementById('show_description').value = data.overview;
    document.getElementById('show_genre').value = data.genres ? data.genres.map(g => g.name).join(', ') : '';
    document.getElementById('show_rating').value = data.vote_average;
    document.getElementById('show_poster').value = 'https://image.tmdb.org/t/p/w500' + data.poster_path;
    document.getElementById('show_backdrop').value = 'https://image.tmdb.org/t/p/original' + data.backdrop_path;
    document.getElementById('show_tmdb_data').value = JSON.stringify(data);
    
    // Populate Language
    if(data.original_language) {
        document.getElementById('show_language').value = data.original_language.toUpperCase();
    }

    // Populate Cast Preview
    if(data.credits && data.credits.cast) {
        const castList = document.getElementById('show_cast_list');
        const castSection = document.getElementById('cast_preview_section');
        castList.innerHTML = '';
        castSection.style.display = 'block';
        
        data.credits.cast.slice(0, 10).forEach(person => {
            const div = document.createElement('div');
            div.style.textAlign = 'center';
            div.style.minWidth = '80px';
            const img = person.profile_path ? `https://image.tmdb.org/t/p/w200${person.profile_path}` : 'assets/img/no-cast.png';
            div.innerHTML = `
                <img src="${img}" style="width:60px; height:60px; border-radius:50%; object-fit:cover; border:2px solid var(--primary-color);">
                <p style="font-size:10px; margin-top:5px; color:#ccc;">${person.name.split(' ')[0]}</p>
            `;
            castList.appendChild(div);
        });
    }
}

function showTMDBResults(results) {
    const list = document.getElementById('tmdb_results_list');
    list.innerHTML = '';
    results.forEach(item => {
        const year = item.first_air_date ? item.first_air_date.split('-')[0] : 'N/A';
        const poster = item.poster_path ? 'https://image.tmdb.org/t/p/w200' + item.poster_path : 'assets/img/no-poster.png';
        const div = document.createElement('div');
        div.className = 'tmdb-item';
        div.innerHTML = `
            <img src="${poster}">
            <div class="tmdb-info">
                <h4>${item.name || item.original_name}</h4>
                <p>${year}</p>
            </div>
        `;
        div.onclick = () => selectTMDBItem(item.id);
        list.appendChild(div);
    });
    document.getElementById('tmdb_modal').style.display = 'block';
}

function selectTMDBItem(id) {
    document.getElementById('tmdb_modal').style.display = 'none';
    const btn = document.getElementById('btn_fetch');
    btn.innerText = 'Fetching Details...';
    fetch(`api_fetch.php?id=${id}&type=tv`)
        .then(res => res.json())
        .then(data => {
            btn.innerText = 'Fetch';
            populateShowForm(data);
        })
        .catch(err => {
            btn.innerText = 'Fetch';
            alert('Error fetching details');
        });
}

document.getElementById('btn_fetch').addEventListener('click', function() {
    const q = document.getElementById('tmdb_query').value;
    if (!q) return alert('Enter series title');
    
    const btn = this;
    btn.innerText = 'Searching...';
    fetch(`api_fetch.php?q=${encodeURIComponent(q)}&type=tv`)
        .then(res => res.json())
        .then(data => {
            btn.innerText = 'Fetch';
            if (data.error) return alert(data.error);

            if(data.id && !data.results) {
                populateShowForm(data);
            } else if(data.results) {
                showTMDBResults(data.results);
            }
        })
        .catch(err => {
            btn.innerText = 'Fetch';
            alert('Network error');
        });
});

document.getElementById('tmdb_query').addEventListener('keyup', function(event) {
    if (event.key === 'Enter') {
        document.getElementById('btn_fetch').click();
    }
});

// Close Modal
document.querySelector('.close-modal').onclick = () => {
    document.getElementById('tmdb_modal').style.display = 'none';
};
window.onclick = (e) => {
    if(e.target == document.getElementById('tmdb_modal')) {
        document.getElementById('tmdb_modal').style.display = 'none';
    }
};

function toggleStatus(id, element) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('type', 'tv');

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
    openDeleteModal(`Are you sure you want to permanently delete this series?`, () => {
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

// --- Bulk Actions ---
const selectAll = document.getElementById('selectAll');
const checkboxes = document.querySelectorAll('.show-select');

selectAll.addEventListener('change', function() {
    checkboxes.forEach(cb => cb.checked = this.checked);
});

function bulkDelete() {
    const selected = Array.from(document.querySelectorAll('.show-select:checked')).map(cb => cb.value);
    if (selected.length === 0) return alert('Please select at least one series');
    
    openDeleteModal(`Are you sure you want to permanently delete ${selected.length} series?`, () => {
        const formData = new FormData();
        formData.append('ids', JSON.stringify(selected));
        formData.append('type', 'tv');
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
<?php include INCLUDES_PATH . '/footer.php'; ?>

<style>
/* Admin Form Logic Grid - Fix cramped UI */
.admin-form .grid-2 { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 20px !important; margin-bottom: 25px !important; width: 100% !important; }
.admin-form .grid-3 { display: grid !important; grid-template-columns: 1fr 1fr 1fr !important; gap: 20px !important; margin-bottom: 25px !important; width: 100% !important; }
.admin-form .form-group { margin-bottom: 20px !important; width: 100% !important; }
.admin-form label { display: block !important; margin-bottom: 10px !important; font-weight: 600 !important; color: #fff !important; font-size: 14px !important; }
.admin-form input, .admin-form textarea, .admin-form select {
    width: 100% !important; padding: 14px !important; background: #000 !important; border: 1px solid #333 !important; border-radius: 8px !important; color: #fff !important; box-sizing: border-box !important; font-size: 15px !important;
}
.admin-form input:focus { border-color: #e50914 !important; }
@media (max-width: 900px) { .admin-form .grid-3 { grid-template-columns: 1fr 1fr !important; } }
@media (max-width: 600px) { .admin-form .grid-2, .admin-form .grid-3 { grid-template-columns: 1fr !important; } }

.dropdown { position: relative; display: inline-block; }
.dropdown-content { display: none; position: absolute; background-color: #2a2a2a; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 100; border-radius: 4px; right: 0; }
.dropdown-content a { color: white; padding: 12px 16px; text-decoration: none; display: block; font-size: 14px; }
.dropdown-content a:hover { background-color: #333; }
.dropdown-content.show { display: block; }
</style>
