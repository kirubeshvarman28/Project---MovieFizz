<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin())
    redirect('login.php');

$tmdb_key = get_setting('tmdb_api_key', TMDB_API_KEY);

$success = '';
$error = '';

// Helper to ensure directories exist
function ensure_upload_dirs() {
    $root = __DIR__ . '/..';
    $dirs = [
        $root . '/uploads/posters', 
        $root . '/uploads/backdrops', 
        $root . '/uploads/episodes'
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

// Handle Import Request
if (isset($_POST['import'])) {
    ensure_upload_dirs();
    $tmdb_id = $_POST['tmdb_id'];
    $type = $_POST['type']; // 'movie' or 'tv'

    // Fetch full details from TMDB
    $details_url = "https://api.themoviedb.org/3/$type/$tmdb_id?api_key=$tmdb_key&append_to_response=videos,credits";
    $data = fetch_from_api($details_url);

    if ($data && isset($data['id'])) {
        // Prepare Trailer
        $trailer_url = '';
        if (isset($data['videos']['results'])) {
            foreach ($data['videos']['results'] as $vid) {
                if ($vid['site'] === 'YouTube' && ($vid['type'] === 'Trailer' || $vid['type'] === 'Teaser')) {
                    $trailer_url = "https://www.youtube.com/watch?v=" . $vid['key'];
                    break;
                }
            }
        }

        if ($type === 'movie') {
            // Import Movie
            $title = clean_input($data['title']);
            $desc = clean_input($data['overview']);
            $year = substr($data['release_date'] ?? '0000', 0, 4);
            $runtime = $data['runtime'] ?? 0;
            $rating = $data['vote_average'] ?? 0;
            $genres = isset($data['genres']) ? implode(', ', array_column($data['genres'], 'name')) : '';
            $lang = strtoupper($data['original_language'] ?? 'EN');

            // Download Images
            $poster = '';
            $backdrop = '';
            if (!empty($data['poster_path'])) {
                $p_url = "https://image.tmdb.org/t/p/w500" . $data['poster_path'];
                $p_name = time() . "_" . md5($p_url) . "_poster.jpg";
                if (download_image($p_url, '../uploads/posters/' . $p_name))
                    $poster = 'uploads/posters/' . $p_name;
            }
            if (!empty($data['backdrop_path'])) {
                $b_url = "https://image.tmdb.org/t/p/original" . $data['backdrop_path'];
                $b_name = time() . "_" . md5($b_url) . "_backdrop.jpg";
                if (download_image($b_url, '../uploads/backdrops/' . $b_name))
                    $backdrop = 'uploads/backdrops/' . $b_name;
            }

            $stmt = $pdo->prepare("INSERT INTO movies (tmdb_id, title, description, genre, rating, release_year, runtime, language, poster, backdrop, trailer_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$tmdb_id, $title, $desc, $genres, $rating, $year, $runtime, $lang, $poster, $backdrop, $trailer_url])) {
                $movie_id = $pdo->lastInsertId();

                // Add Cloud Source
                $pdo->prepare("INSERT INTO movie_sources (movie_id, label, source_type, source_url) VALUES (?, 'Cloud Server', 'cloud', 'https://vidrock.net/movie/$tmdb_id')")->execute([$movie_id]);

                // Import Cast
                if (isset($data['credits']['cast'])) {
                    $cast_limit = 10;
                    foreach (array_slice($data['credits']['cast'], 0, $cast_limit) as $c) {
                        $c_tmdb_id = $c['id'];
                        $c_name = clean_input($c['name']);
                        $c_role = clean_input($c['character'] ?? '');
                        $c_img = $c['profile_path'] ? "https://image.tmdb.org/t/p/w200" . $c['profile_path'] : '';

                        // Check if cast exists
                        $stmt_c = $pdo->prepare("SELECT id FROM cast_crew WHERE tmdb_id = ?");
                        $stmt_c->execute([$c_tmdb_id]);
                        $cast_id = $stmt_c->fetchColumn();

                        if (!$cast_id) {
                            $stmt_cc = $pdo->prepare("INSERT INTO cast_crew (tmdb_id, name, image) VALUES (?, ?, ?)");
                            $stmt_cc->execute([$c_tmdb_id, $c_name, $c_img]);
                            $cast_id = $pdo->lastInsertId();
                        }

                        $pdo->prepare("INSERT INTO movie_cast (movie_id, cast_id, role) VALUES (?, ?, ?)")->execute([$movie_id, $cast_id, $c_role]);
                    }
                }
                $success = "Movie '$title' imported successfully!";
            }
        }
        else {
            // Import TV Show
            $title = clean_input($data['name']);
            $desc = clean_input($data['overview']);
            $genres = isset($data['genres']) ? implode(', ', array_column($data['genres'], 'name')) : '';
            $rating = $data['vote_average'] ?? 0;
            $lang = strtoupper($data['original_language'] ?? 'EN');

            $poster = '';
            $backdrop = '';
            if (!empty($data['poster_path'])) {
                $p_url = "https://image.tmdb.org/t/p/w500" . $data['poster_path'];
                $p_name = time() . "_" . md5($p_url) . "_tv_poster.jpg";
                if (download_image($p_url, '../uploads/posters/' . $p_name))
                    $poster = 'uploads/posters/' . $p_name;
            }
            if (!empty($data['backdrop_path'])) {
                $b_url = "https://image.tmdb.org/t/p/original" . $data['backdrop_path'];
                $b_name = time() . "_" . md5($b_url) . "_tv_backdrop.jpg";
                if (download_image($b_url, '../uploads/backdrops/' . $b_name))
                    $backdrop = 'uploads/backdrops/' . $b_name;
            }

            $stmt = $pdo->prepare("INSERT INTO tv_shows (tmdb_id, title, description, genre, rating, language, poster, backdrop, trailer_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$tmdb_id, $title, $desc, $genres, $rating, $lang, $poster, $backdrop, $trailer_url])) {
                $show_id = $pdo->lastInsertId();

                // Import Cast
                if (isset($data['credits']['cast'])) {
                    $cast_limit = 10;
                    foreach (array_slice($data['credits']['cast'], 0, $cast_limit) as $c) {
                        $c_tmdb_id = $c['id'];
                        $c_name = clean_input($c['name']);
                        $c_role = clean_input($c['character'] ?? '');
                        $c_img = $c['profile_path'] ? "https://image.tmdb.org/t/p/w200" . $c['profile_path'] : '';

                        $stmt_c = $pdo->prepare("SELECT id FROM cast_crew WHERE tmdb_id = ?");
                        $stmt_c->execute([$c_tmdb_id]);
                        $cast_id = $stmt_c->fetchColumn();

                        if (!$cast_id) {
                            $stmt_cc = $pdo->prepare("INSERT INTO cast_crew (tmdb_id, name, image) VALUES (?, ?, ?)");
                            $stmt_cc->execute([$c_tmdb_id, $c_name, $c_img]);
                            $cast_id = $pdo->lastInsertId();
                        }

                        $pdo->prepare("INSERT INTO tv_show_cast (tv_show_id, cast_id, role) VALUES (?, ?, ?)")->execute([$show_id, $cast_id, $c_role]);
                    }
                }

                $success = "TV Show '$title' imported! Now fetching seasons...";

                // Fetch and Import Seasons/Episodes
                if (isset($data['seasons'])) {
                    foreach ($data['seasons'] as $s) {
                        $s_num = $s['season_number'];
                        if ($s_num == 0)
                            continue; // Skip specials usually

                        $stmt_s = $pdo->prepare("INSERT INTO seasons (tv_show_id, season_number, title) VALUES (?, ?, ?)");
                        $stmt_s->execute([$show_id, $s_num, "Season $s_num"]);
                        $season_id = $pdo->lastInsertId();

                        // Fetch episodes for this season
                        $ep_url = "https://api.themoviedb.org/3/tv/$tmdb_id/season/$s_num?api_key=$tmdb_key";
                        $ep_data = fetch_from_api($ep_url);
                        if ($ep_data && isset($ep_data['episodes'])) {
                            foreach ($ep_data['episodes'] as $e) {
                                $e_num = $e['episode_number'];
                                $e_title = clean_input($e['name']);
                                $e_desc = clean_input($e['overview']);
                                $e_tmdb_id = $e['id'];

                                // Episode Image Fetching
                                $e_poster = '';
                                if (!empty($e['still_path'])) {
                                    $ep_img_url = "https://image.tmdb.org/t/p/w500" . $e['still_path'];
                                    $ep_img_name = time() . "_ep_{$e_tmdb_id}.jpg";
                                    if (download_image($ep_img_url, '../uploads/episodes/' . $ep_img_name)) $e_poster = 'uploads/episodes/' . $ep_img_name;
                                }

                                $stmt_e = $pdo->prepare("INSERT INTO episodes (season_id, episode_number, title, description, video_url, tmdb_id, poster) VALUES (?, ?, ?, ?, 'cloud', ?, ?)");
                                $stmt_e->execute([$season_id, $e_num, $e_title, $e_desc, $e_tmdb_id, $e_poster]);
                                $episode_id = $pdo->lastInsertId();

                                // Add Cloud Source
                                $pdo->prepare("INSERT INTO episode_sources (episode_id, label, source_type, source_url) VALUES (?, 'Cloud Server', 'cloud', 'https://vidrock.net/tv/$tmdb_id/$s_num/$e_num')")->execute([$episode_id]);
                            }
                        }
                    }
                }
            }
        }
    }
    else {
        $error = "Failed to fetch data from TMDB.";
    }
}

$page_title = "Cloud Importer";
include 'includes/header.php';
?>

<!-- NATIVE ADMIN HEADER -->
<header class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-cloud-download-alt me-2"></i>Cloud Importer</h2>
    </div>
    <div class="nav-right">
        <span class="text-muted small">Powered by TMDB API v3</span>
    </div>
</header>

<!-- FEEDBACK MESSAGES (NATIVE STYLE) -->
<?php if ($success): ?>
    <div class="success-msg"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="error-msg"><?php echo $error; ?></div>
<?php endif; ?>

<!-- NATIVE FILTER BAR FOR SEARCH -->
<div class="filter-bar">
    <div style="flex: 1; position: relative;">
        <input type="text" id="tmdb-search-input" placeholder="Search Movie or TV Show Title..." style="width: 100%; padding-left: 35px;" autocomplete="off">
        <i class="fas fa-search" style="position: absolute; left: 12px; top: 12px; color: #888;"></i>
    </div>
    <select id="tmdb-type-select" style="min-width: 150px;">
        <option value="movie">Movies</option>
        <option value="tv">TV Shows</option>
    </select>
    <button class="btn btn-primary" onclick="searchTMDB()"><i class="fas fa-search pe-1"></i> Search TMDB</button>
</div>

<!-- NATIVE MOVIE GRID FOR RESULTS -->
<div id="search-results" class="movie-grid" style="min-height: 400px; padding: 20px;">
    <!-- Initial State -->
    <div id="initial-message" style="grid-column: 1 / -1; text-align: center; padding: 100px 0; color: #666;">
        <i class="fas fa-cloud fa-4x mb-3" style="opacity: 0.2;"></i>
        <h3>Ready to Import?</h3>
        <p>Enter a title above to fetch content from the cloud.</p>
    </div>
</div>

<script>
function searchTMDB() {
    const query = document.getElementById('tmdb-search-input').value;
    const type = document.getElementById('tmdb-type-select').value;
    const resultsDiv = document.getElementById('search-results');
    
    if (query.trim() === '') return;

    resultsDiv.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 100px 0;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">Searching TMDB Database...</p>
        </div>
    `;

    fetch(`api_fetch.php?q=${encodeURIComponent(query)}&type=${type}`)
        .then(response => response.json())
        .then(data => {
            resultsDiv.innerHTML = '';
            let results = [];
            if (data.results && data.results.length > 0) results = data.results;
            else if (data.id) results = [data];

            if (results.length > 0) {
                results.forEach(item => {
                    const id = item.id;
                    const title = item.title || item.name;
                    const date = item.release_date || item.first_air_date || 'N/A';
                    const year = date !== 'N/A' ? date.split('-')[0] : 'TBA';
                    const poster = item.poster_path ? 'https://image.tmdb.org/t/p/w400' + item.poster_path : 'https://placehold.co/400x600/1a1a1a/ffffff?text=No+Poster';
                    const rating = item.vote_average ? item.vote_average.toFixed(1) : '0.0';
                    
                    resultsDiv.innerHTML += `
                        <div class="movie-card">
                            <div class="poster-wrapper">
                                <img src="${poster}" alt="${title}">
                            </div>
                            <div class="card-info">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <h4 title="${title}" style="margin:0; flex:1;">${title}</h4>
                                    <span class="tmdb-rating-badge"><i class="fas fa-star text-warning"></i> ${rating}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span class="text-muted small">${year}</span>
                                    <span class="badge bg-secondary small">${type.toUpperCase()}</span>
                                </div>
                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="tmdb_id" value="${id}">
                                    <input type="hidden" name="type" value="${type}">
                                    <button type="submit" name="import" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-file-import pe-1"></i> Import
                                    </button>
                                </form>
                            </div>
                        </div>
                    `;
                });
            } else {
                resultsDiv.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 100px 0; color: #666;">
                        <i class="fas fa-exclamation-circle fa-4x mb-3" style="opacity: 0.2;"></i>
                        <h3>No results found</h3>
                        <p>Try a different keyword or check the TMDB ID.</p>
                    </div>
                `;
            }
        });
}

document.getElementById('tmdb-search-input').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') searchTMDB();
});
</script>

<style>
/* LOCAL TWEAKS FOR IMPOSTER UI TO ENSURE PERFECT ALIGNMENT */
.filter-bar {
    display: flex;
    gap: 15px;
    background: #1a1a1a;
    padding: 15px 25px;
    border-bottom: 1px solid #333;
    align-items: center;
}

.filter-bar input, .filter-bar select {
    background: #2a2a2a;
    border: 1px solid #444;
    color: white;
    padding: 10px 15px;
    border-radius: 4px;
}

.movie-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 25px;
    padding: 25px !important;
}

.movie-card {
    background: #1a1a1a;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s;
    border: 1px solid #333;
}

.movie-card:hover {
    transform: translateY(-5px);
    border-color: #555;
}

.poster-wrapper {
    position: relative;
    aspect-ratio: 2/3;
}

.poster-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.tmdb-rating-badge {
    background: rgba(255,193,7,0.1);
    color: #ffc107;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    border: 1px solid rgba(255,193,7,0.2);
}

.card-info {
    padding: 15px;
}

.card-info h4 {
    margin: 0;
    font-size: 15px;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Spinner style if not in global css */
.spinner-border {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    vertical-align: text-bottom;
    border: .25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border .75s linear infinite;
}
@keyframes spinner-border {
    to { transform: rotate(360deg); }
}
</style>

<?php include 'includes/footer.php'; ?>
