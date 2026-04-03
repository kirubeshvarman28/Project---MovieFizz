<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dirs = [UPLOADS_PATH . '/posters', UPLOADS_PATH . '/backdrops', UPLOADS_PATH . '/movies'];
    foreach ($dirs as $dir) { if (!is_dir($dir)) @mkdir($dir, 0755, true); }

    try {
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $genre = clean_input($_POST['genre']);
    $rating = $_POST['rating'];
    $year = $_POST['release_year'];
    $runtime = $_POST['runtime'];
    $trailer = clean_input($_POST['trailer_url']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    $poster_path = '';
    $backdrop_path = '';
    $tmdb_data_raw = $_POST['tmdb_data'] ?? '';
    
    // Poster Upload
    if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] == 0) {
        $ext = pathinfo($_FILES['poster_file']['name'], PATHINFO_EXTENSION);
        $name = time() . '_poster.' . $ext;
        move_uploaded_file($_FILES['poster_file']['tmp_name'], UPLOADS_PATH . '/posters/' . $name);
        $poster_path = 'uploads/posters/' . $name;
    } elseif (!empty($_POST['poster_url'])) {
        $poster_url = $_POST['poster_url'];
        if (strpos($poster_url, 'image.tmdb.org') !== false) {
            $ext = pathinfo($poster_url, PATHINFO_EXTENSION) ?: 'jpg';
            $name = time() . '_poster.' . $ext;
            if (download_image($poster_url, UPLOADS_PATH . '/posters/' . $name)) {
                $poster_path = 'uploads/posters/' . $name;
            } else {
                $poster_path = clean_input($poster_url);
            }
        } else {
            $poster_path = clean_input($poster_url);
        }
    }

    // Backdrop Upload
    if (isset($_FILES['backdrop_file']) && $_FILES['backdrop_file']['error'] == 0) {
        $ext = pathinfo($_FILES['backdrop_file']['name'], PATHINFO_EXTENSION);
        $name = time() . '_backdrop.' . $ext;
        move_uploaded_file($_FILES['backdrop_file']['tmp_name'], UPLOADS_PATH . '/backdrops/' . $name);
        $backdrop_path = 'uploads/backdrops/' . $name;
    } elseif (!empty($_POST['backdrop_url'])) {
        $backdrop_url = $_POST['backdrop_url'];
        if (strpos($backdrop_url, 'image.tmdb.org') !== false) {
            $ext = pathinfo($backdrop_url, PATHINFO_EXTENSION) ?: 'jpg';
            $name = time() . '_backdrop.' . $ext;
            if (download_image($backdrop_url, UPLOADS_PATH . '/backdrops/' . $name)) {
                $backdrop_path = 'uploads/backdrops/' . $name;
            } else {
                $backdrop_path = clean_input($backdrop_url);
            }
        } else {
            $backdrop_path = clean_input($backdrop_url);
        }
    }

    if (empty($error)) {
        $language = clean_input($_POST['language'] ?? 'English');
        // Insert into DB
        $stmt = $pdo->prepare("INSERT INTO movies (title, description, genre, rating, release_year, runtime, language, poster, backdrop, trailer_url, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $params = [$title, $description, $genre, $rating, $year, $runtime, $language, $poster_path, $backdrop_path, $trailer, $is_featured];
        if ($stmt->execute($params)) {
            $movie_id = $pdo->lastInsertId();
            
            $needs_extraction = false;
            $video_to_extract = '';

            // Handle Sources
            if (isset($_POST['sources']) && is_array($_POST['sources'])) {
                foreach ($_POST['sources'] as $index => $source) {
                    $label = clean_input($source['label']);
                    $stype = clean_input($source['type']);
                    $surl = clean_input($source['url'] ?? '');
                    
                    if ($stype === 'file' && isset($_FILES['source_files']['name'][$index]) && $_FILES['source_files']['error'][$index] == 0) {
                        $ext = strtolower(pathinfo($_FILES['source_files']['name'][$index], PATHINFO_EXTENSION));
                        $name = time() . "_movie_source_$index." . $ext;
                        if (move_uploaded_file($_FILES['source_files']['tmp_name'][$index], UPLOADS_PATH . '/movies/' . $name)) {
                            $surl = 'uploads/movies/' . $name;
                            $needs_extraction = true; $video_to_extract = $surl;
                        }
                    } elseif (strpos($surl, 'http') === 0) {
                        $needs_extraction = true;
                        $video_to_extract = $surl;
                    }
                    if (!empty($surl)) {
                        $pdo->prepare("INSERT INTO movie_sources (movie_id, label, source_type, source_url) VALUES (?, ?, ?, ?)")->execute([$movie_id, $label, $stype, $surl]);
                    }
                }
            }

            // Sync Cast & Language from TMDB (Already exists in file, keeping it)
            if (!empty($tmdb_data_raw)) {
                $tmdb_data = json_decode($tmdb_data_raw, true);
                if (isset($tmdb_data['original_language'])) {
                    $l_code = $tmdb_data['original_language'];
                    $pdo->prepare("INSERT IGNORE INTO languages (name, code, status) VALUES (?, ?, 1)")->execute([strtoupper($l_code), $l_code]);
                }
                if (isset($tmdb_data['credits']['cast'])) {
                    $cast = array_slice($tmdb_data['credits']['cast'], 0, 10);
                    foreach ($cast as $c) {
                        $c_name = clean_input($c['name']);
                        $c_tmdb_id = $c['id'];
                        $c_image = $c['profile_path'] ? 'https://image.tmdb.org/t/p/w200' . $c['profile_path'] : '';
                        $stmt_c = $pdo->prepare("SELECT id FROM cast_crew WHERE tmdb_id = ?");
                        $stmt_c->execute([$c_tmdb_id]);
                        $c_id_row = $stmt_c->fetch();
                        if ($c_id_row) { $cast_id = $c_id_row['id']; } 
                        else { $pdo->prepare("INSERT INTO cast_crew (name, image, tmdb_id, type) VALUES (?, ?, ?, 'Acting')")->execute([$c_name, $c_image, $c_tmdb_id]); $cast_id = $pdo->lastInsertId(); }
                        $pdo->prepare("INSERT IGNORE INTO movie_cast (movie_id, cast_id, role) VALUES (?, ?, ?)")->execute([$movie_id, $cast_id, $c['character'] ?? '']);
                    }
                }
            }

            // SUCCESS FLOW
            if ($needs_extraction) {
                // Show Extraction UI
                $show_extraction_ui = true;
                $extraction_video = $video_to_extract;
                $extraction_id = $movie_id;
                $extraction_type = 'movie';
            } else {
                $success = "Movie added successfully!";
                header("Location: manage_movies.php?success=1");
                exit;
            }
            }
        }
    } catch (Exception $e) {
        $error = "Failed to add movie: " . $e->getMessage();
    }
}
$page_title = "Add New Movie";
include INCLUDES_PATH . '/header.php';
?>

<style>
    .fetch-box {
        background: #252525;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        gap: 15px;
        align-items: center;
        border: 1px solid #333;
    }
    .fetch-box input { 
        flex: 1; 
        padding: 12px;
        background: #1a1a1a;
        border: 1px solid #444;
        color: #fff;
        border-radius: 6px;
    }
    .source-row { 
        background: #2a2a2a; 
        padding: 15px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        border-left: 4px solid var(--primary-color);
    }
    .source-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .remove-source { color: #ff3e3e; cursor: pointer; font-size: 18px; }
    
    /* Layout Fixes */
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
</style>

<div class="top-nav">
    <h2><i class="fas fa-plus-circle"></i> Add New Movie</h2>
</div>

            <?php if($success): ?><div class="success-msg"><?php echo $success; ?></div><?php endif; ?>
            <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

            <div class="fetch-box">
                <input type="text" id="tmdb_search" placeholder="Search Movie on TMDB...">
                <button type="button" class="btn btn-primary" id="btn_fetch">Fetch TMDB</button>
                <input type="text" id="imdb_id" placeholder="IMDb ID (tt1234567)...">
                <button type="button" class="btn btn-primary" id="btn_imdb">Fetch IMDb</button>
            </div>
            
            <!-- TMDB ID Fetch Hint -->
            <div style="background: rgba(229, 9, 20, 0.08); padding: 12px; border-radius: 8px; border: 1px solid rgba(229, 9, 20, 0.3); margin: 15px 0;">
                <p style="font-size: 13px; color: #fff; margin: 0;">
                    <i class="fas fa-lightbulb" style="color: #e50914; margin-right: 8px;"></i>
                    <strong>TMDB ID:</strong> The number in the URL (e.g., .../movie/<strong>927342</strong>)
                </p>
            </div>

            <div class="form-container">
                <form id="movie_form" method="POST" enctype="multipart/form-data" class="admin-form">
                    <input type="hidden" name="tmdb_data" id="tmdb_data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" id="title" required style="width:100%; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px;">
                        </div>
                        <div class="form-group">
                            <label>Genre</label>
                            <input type="text" name="genre" id="genre" style="width:100%; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px;">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Description</label>
                        <textarea name="description" id="description" rows="4" style="width:100%; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px;"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label>Rating</label>
                            <input type="number" step="0.1" name="rating" id="rating" style="width:100%; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px;">
                        </div>
                        <div class="form-group">
                            <label>Release Year</label>
                            <input type="number" name="release_year" id="year" style="width:100%; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px;">
                        </div>
                        <div class="form-group">
                            <label>Runtime (Mins)</label>
                            <input type="number" name="runtime" id="runtime" style="width:100%; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                         <div class="form-group">
                            <label>Language</label>
                            <input type="text" name="language" id="language" placeholder="e.g. English, Hindi" style="width:100%; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px;">
                        </div>
                        <div class="form-group">
                            <label>Trailer URL (YouTube)</label>
                            <input type="text" name="trailer_url" id="trailer_url" style="width:100%; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px;">
                        </div>
                    </div>

                    <div class="form-group" id="cast_preview_section" style="display:none; background:#1a1a1a; padding:15px; border-radius:8px; margin-top:10px;">
                        <label style="display:block; margin-bottom:10px;"><i class="fas fa-users"></i> Fetched Cast</label>
                        <div id="cast_list" style="display:flex; gap:10px; overflow-x:auto; padding-bottom:10px;">
                            <!-- Cast members will appear here -->
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label>Poster URL</label>
                            <input type="text" name="poster_url" id="poster_url" placeholder="URL" style="width:100%; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px; margin-bottom:10px;">
                            <input type="file" name="poster_file">
                        </div>
                        <div class="form-group">
                            <label>Backdrop URL</label>
                            <input type="text" name="backdrop_url" id="backdrop_url" placeholder="URL" style="width:100%; padding:12px; background:#000; border:1px solid #333; color:#fff; border-radius:6px; margin-bottom:10px;">
                            <input type="file" name="backdrop_file">
                        </div>
                    </div>

                    <div class="section-card" style="border-top: 3px solid #e50914; margin-top:20px;">
                        <h3 style="margin-bottom:10px;"><i class="fas fa-play-circle"></i> Video Sources</h3>
                        <button type="button" class="btn btn-secondary btn-sm" id="add_source_btn" style="margin-bottom:15px;">+ Add Source</button>
                        <div id="sources_container">
                            <!-- Sources will be added here -->
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:20px;">
                        <label><input type="checkbox" name="is_featured"> Featured Movie</label>
                    </div>

                    <button type="submit" class="btn btn-primary" id="save_btn">Save Movie</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Sources Management
        let sourceIndex = 0;
        const container = document.getElementById('sources_container');
        
        function addSourceRow() {
            const html = `
                <div class="source-row" id="source_${sourceIndex}" style="position:relative; border-left:4px solid #e50914;">
                    <div class="source-header" style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-weight:bold; color:#e50914;">Source #${sourceIndex + 1}</span>
                        <i class="fas fa-times remove-source" style="color:#e50914; cursor:pointer;" onclick="removeSource(${sourceIndex})"></i>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Label (e.g. 1080p, 4K)</label>
                            <input type="text" name="sources[${sourceIndex}][label]" placeholder="Quality Tag" required style="padding:10px;">
                        </div>
                        <div class="form-group">
                            <label>Source Type</label>
                            <select name="sources[${sourceIndex}][type]" onchange="toggleSourceType(${sourceIndex}, this.value)" style="padding:10px;">
                                <option value="url">External URL</option>
                                <option value="embed">Embed Code</option>
                                <option value="file">Direct Upload (MP4)</option>
                            </select>
                        </div>
                        <div class="form-group" id="url_input_${sourceIndex}">
                            <label>Source URL / Embed</label>
                            <input type="text" name="sources[${sourceIndex}][url]" placeholder="Enter Link" style="padding:10px;">
                        </div>
                        <div class="form-group" id="file_input_${sourceIndex}" style="display:none;">
                            <label>Select File</label>
                            <input type="file" name="source_files[${sourceIndex}]">
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            sourceIndex++;
        }

        function removeSource(index) {
            document.getElementById(`source_${index}`).remove();
        }

        function toggleSourceType(index, type) {
            const urlInp = document.getElementById(`url_input_${index}`);
            const fileInp = document.getElementById(`file_input_${index}`);
            if (type === 'file') {
                urlInp.style.display = 'none';
                fileInp.style.display = 'block';
            } else {
                urlInp.style.display = 'block';
                fileInp.style.display = 'none';
            }
        }

        document.getElementById('add_source_btn').addEventListener('click', addSourceRow);
        addSourceRow();


        // TMDB Fetch logic with Search & Select
        function populateMovieForm(data) {
            document.getElementById('title').value = data.title;
            document.getElementById('description').value = data.overview;
            document.getElementById('year').value = data.release_date ? data.release_date.split('-')[0] : '';
            document.getElementById('rating').value = data.vote_average;
            document.getElementById('poster_url').value = 'https://image.tmdb.org/t/p/w500' + data.poster_path;
            document.getElementById('backdrop_url').value = 'https://image.tmdb.org/t/p/original' + data.backdrop_path;
            document.getElementById('genre').value = data.genres ? data.genres.map(g => g.name).join(', ') : '';
            document.getElementById('runtime').value = data.runtime;
            document.getElementById('tmdb_data').value = JSON.stringify(data);

            // Populate Language
            if(data.original_language) {
                document.getElementById('language').value = data.original_language.toUpperCase();
            }

            // Populate Cast Preview
            if(data.credits && data.credits.cast) {
                const castList = document.getElementById('cast_list');
                const castSection = document.getElementById('cast_preview_section');
                if (castList && castSection) {
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

            // Populate Trailer
            if(data.videos && data.videos.results) {
                const trailer = data.videos.results.find(v => v.type === 'Trailer');
                if(trailer) {
                    const trailerInp = document.getElementById('trailer') || document.getElementById('trailer_url');
                    if(trailerInp) trailerInp.value = 'https://www.youtube.com/watch?v=' + trailer.key;
                }
            }
        }

        // TMDB Logic
        function showTMDBResults(results) {
            const list = document.getElementById('tmdb_results_list');
            list.innerHTML = '';
            results.forEach(item => {
                const year = item.release_date ? item.release_date.split('-')[0] : 'N/A';
                const poster = item.poster_path ? 'https://image.tmdb.org/t/p/w200' + item.poster_path : 'assets/img/no-poster.png';
                const div = document.createElement('div');
                div.className = 'tmdb-item';
                div.innerHTML = `
                    <img src="${poster}">
                    <div class="tmdb-info">
                        <h4>${item.title}</h4>
                        <p>${year}</p>
                    </div>
                `;
                div.onclick = () => {
                    selectTMDBItem(item.id);
                    closeTMDBModal();
                };
                list.appendChild(div);
            });
            const modal = document.getElementById('tmdb_modal');
            modal.style.display = 'flex';
            modal.classList.add('active');
        }

        function selectTMDBItem(id) {
            const btn = document.getElementById('btn_fetch');
            const originalText = btn.innerText;
            btn.innerText = 'Fetching...';
            fetch(`api_fetch.php?id=${id}&type=movie`)
                .then(res => res.json())
                .then(data => {
                    btn.innerText = originalText;
                    populateMovieForm(data);
                })
                .catch(err => {
                    btn.innerText = originalText;
                    alert('Error fetching details');
                });
        }

        function closeTMDBModal() {
            const modal = document.getElementById('tmdb_modal');
            modal.style.display = 'none';
            modal.classList.remove('active');
        }

        document.getElementById('btn_fetch').addEventListener('click', function() {
            const query = document.getElementById('tmdb_search').value;
            if(!query) return alert('Enter movie title');
            
            const btn = this;
            btn.innerText = 'Searching...';
            fetch('api_fetch.php?q=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    btn.innerText = 'Fetch TMDB';
                    if(data.error) return alert(data.error);

                    if(data.id && !data.results) {
                        populateMovieForm(data);
                    } else if(data.results) {
                        showTMDBResults(data.results);
                    }
                })
                .catch(err => {
                    btn.innerText = 'Fetch TMDB';
                    alert('Network error');
                });
        });

        document.getElementById('tmdb_search').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('btn_fetch').click();
            }
        });

        // IMDb Logic
        document.getElementById('btn_imdb').addEventListener('click', function() {
            const query = document.getElementById('imdb_id').value;
            if(!query) return alert('Enter IMDb ID (e.g. tt1375666)');
            
            this.innerText = 'Scraping...';
            fetch('imdb_fetch.php?imdb_id=' + query)
                .then(res => res.json())
                .then(data => {
                    if(data.rating) {
                        document.getElementById('rating').value = data.rating;
                    } else {
                        alert('Failed to scrape IMDb. Check ID.');
                    }
                })
                .catch(err => alert('Error scraping IMDb'))
                .finally(() => { this.innerText = 'Fetch IMDb'; });
        });

        // Close Modal Events
        document.querySelector('.close-modal').onclick = closeTMDBModal;
        window.onclick = (e) => {
            if(e.target == document.getElementById('tmdb_modal')) {
                closeTMDBModal();
            }
        };

        // Form Submit with Progress
        const form = document.getElementById('movie_form');
        form.onsubmit = function(e) {
            const videoFile = document.getElementById('video_file').files[0];
            if (!videoFile) return true; // Let normal submit happen if no file

            e.preventDefault();
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();
            
            document.getElementById('progress_wrapper').style.display = 'block';
            document.getElementById('save_btn').disabled = true;

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    document.getElementById('progress_bar').style.width = percent + '%';
                    document.getElementById('progress_text').innerText = percent + '%';
                }
            });

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        alert('Movie uploaded and saved successfully!');
                        window.location.href = 'manage_movies.php';
                    } else {
                        alert('Upload failed.');
                        document.getElementById('save_btn').disabled = false;
                    }
                }
            };

            xhr.open('POST', 'add_movie.php', true);
            xhr.send(formData);
        };
    </script>
<?php if (isset($show_extraction_ui) && $show_extraction_ui): ?>
<!-- Extraction Overlay -->
<div id="extractionOverlay" class="extraction-overlay">
    <div class="extraction-box" style="width: 600px;">
        <div class="extraction-header">
            <h3><i class="fas fa-magic"></i> AI Media Extraction</h3>
            <p>Processing your video for subtitles and audio tracks...</p>
        </div>
        
        <div class="extraction-body">
            <div id="extractionProgressUI">
                <div class="loader-container">
                    <div class="pulse-loader"></div>
                    <div class="loader-text" id="extractionStatus">Initializing FFmpeg...</div>
                </div>

                <div class="progress-bar-container" style="background:#333; height:12px; border-radius:6px; margin:20px 0; overflow:hidden;">
                    <div id="extractionProgressBar" style="width:0%; height:100%; background:#e50914; transition: width 0.3s;"></div>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:14px; color:#888; margin-bottom:20px;">
                    <span id="extractionPercentage">0%</span>
                    <span id="extractionETA">ETA: --:--</span>
                </div>
            </div>
            
            <div class="progress-steps">
                <div class="step" id="step_probe"><i class="fas fa-search"></i> Probing Video File</div>
                <div class="step" id="step_subs"><i class="fas fa-closed-captioning"></i> Extracting Subtitles</div>
                <div class="step" id="step_audio"><i class="fas fa-volume-up"></i> Extracting Audio Tracks</div>
            </div>

            <div id="extractionResults" class="extraction-results" style="display:none;">
                <h4>Extraction Complete!</h4>
                <div class="results-grid">
                    <div class="results-col">
                        <h5>Subtitles</h5>
                        <ul id="subsList"></ul>
                    </div>
                    <div class="results-col">
                        <h5>Audio Tracks</h5>
                        <ul id="audioList"></ul>
                    </div>
                </div>
                <button onclick="window.location.href='manage_movies.php?success=1'" class="btn btn-primary" style="margin-top:20px; width:100%;">Continue to Dashboard</button>
            </div>

            <div id="extractionControls" style="margin-top:20px;">
                <button onclick="window.location.href='manage_movies.php?success=1'" class="btn btn-secondary" style="background:#444; width:100%;"><i class="fas fa-forward"></i> Skip & Continue to Dashboard</button>
                <p style="font-size:12px; color:#666; margin-top:10px;">Skipping won't stop the server task, but lets you continue working.</p>
            </div>
        </div>
    </div>
</div>

<style>
/* Base Admin Form Grid - Fix cramped UI */
.admin-form .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; width: 100%; }
.admin-form .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; width: 100%; }
.admin-form .form-group { margin-bottom: 15px; width: 100%; }
.admin-form label { display: block; margin-bottom: 8px; font-weight: 500; color: #ccc; font-size: 14px; }
.admin-form input[type="text"], 
.admin-form input[type="number"], 
.admin-form textarea, 
.admin-form select {
    width: 100% !important; padding: 12px; background: #0a0a0a; border: 1px solid #333; border-radius: 6px; color: #fff; transition: border-color 0.3s; box-sizing: border-box;
}
.admin-form input:focus { border-color: #e50914; outline: none; }
@media (max-width: 900px) { .admin-form .grid-3 { grid-template-columns: 1fr 1fr; } }
@media (max-width: 600px) { .admin-form .grid-2, .admin-form .grid-3 { grid-template-columns: 1fr !important; } }

.extraction-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    color: #fff;
}
.extraction-box {
    background: #1a1a1a;
    width: 500px;
    padding: 40px;
    border-radius: 12px;
    border: 1px solid #333;
    text-align: center;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
}
.extraction-header h3 { color: #e50914; margin-bottom: 5px; }
.extraction-header p { color: #888; font-size: 14px; margin-bottom: 30px; }

.loader-container { margin-bottom: 40px; }
.pulse-loader {
    width: 60px; height: 60px;
    background: #e50914;
    border-radius: 50%;
    margin: 0 auto 20px;
    animation: pulse 1.5s infinite ease-in-out;
}
@keyframes pulse {
    0% { transform: scale(0.8); opacity: 0.5; box-shadow: 0 0 0 0 rgba(229, 9, 20, 0.7); }
    70% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 20px rgba(229, 9, 20, 0); }
    100% { transform: scale(0.8); opacity: 0.5; box-shadow: 0 0 0 0 rgba(229, 9, 20, 0); }
}
.loader-text { font-weight: bold; font-size: 18px; color: #fff; }

.progress-steps { text-align: left; margin: 30px 0; border-top: 1px solid #333; padding-top: 20px; }
.step { margin-bottom: 15px; color: #555; display: flex; align-items: center; gap: 10px; font-size: 15px; transition: color 0.3s; }
.step.active { color: #e50914; font-weight: 600; }
.step.done { color: #2ecc71; }
.step.done i { color: #2ecc71; }

.extraction-results { border-top: 1px solid #333; padding-top: 20px; text-align: left; animation: fadeIn 0.5s; }
.results-grid { display: flex; gap: 20px; margin-top: 15px; }
.results-col { flex: 1; background: #000; padding: 10px; border-radius: 4px; font-size: 13px; }
.results-col h5 { margin-bottom: 8px; color: #888; border-bottom: 1px solid #222; padding-bottom: 5px; }
.results-col ul { list-style: none; padding: 0; }
.results-col li { color: #2ecc71; margin-bottom: 3px; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Admin Form Grid Global Fix */
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const status = document.getElementById('extractionStatus');
    const progressBar = document.getElementById('extractionProgressBar');
    const percentageText = document.getElementById('extractionPercentage');
    const etaText = document.getElementById('extractionETA');
    const steps = {
        probe: document.getElementById('step_probe'),
        subs: document.getElementById('step_subs'),
        audio: document.getElementById('step_audio')
    };

    let progressInterval;

    function pollProgress() {
        fetch(`ajax_progress.php?video_path=<?php echo urlencode($extraction_video); ?>&content_id=<?php echo $extraction_id; ?>`)
        .then(res => res.json())
        .then(data => {
            if (data.status) {
                status.innerText = data.status;
                progressBar.style.width = data.percent + '%';
                percentageText.innerText = data.percent + '%';
                etaText.innerText = 'ETA: ' + data.eta;
                
                if (data.status.toLowerCase().includes('subtitle')) steps.subs.classList.add('active');
                if (data.status.toLowerCase().includes('audio')) {
                    steps.subs.classList.add('done');
                    steps.audio.classList.add('active');
                }
            }
        });
    }

    function startExtraction() {
        steps.probe.classList.add('active');
        status.innerText = "Analyzing video streams...";

        progressInterval = setInterval(pollProgress, 1000);

        const formData = new FormData();
        formData.append('video_path', '<?php echo $extraction_video; ?>');
        formData.append('content_id', '<?php echo $extraction_id; ?>');
        formData.append('type', '<?php echo $extraction_type; ?>');

        fetch('ajax_extract.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            clearInterval(progressInterval);
            if (data.success) {
                steps.probe.classList.add('done');
                steps.subs.classList.add('done');
                steps.audio.classList.add('done');
                status.innerText = "Processing Complete!";
                progressBar.style.width = '100%';
                percentageText.innerText = '100%';
                etaText.innerText = 'Finished';
                
                document.getElementById('extractionProgressUI').style.display = 'none';
                document.getElementById('extractionControls').style.display = 'none';
                document.getElementById('extractionResults').style.display = 'block';
                const subsList = document.getElementById('subsList');
                const audioList = document.getElementById('audioList');
                
                if (data.subtitles.length > 0) {
                    data.subtitles.forEach(s => {
                        const li = document.createElement('li');
                        li.innerHTML = `<i class="fas fa-check"></i> ${s.language} (${s.label})`;
                        subsList.appendChild(li);
                    });
                } else {
                    subsList.innerHTML = '<li style="color:#888;">No subtitles found.</li>';
                }

                if (data.audio.length > 0) {
                    data.audio.forEach(a => {
                        const li = document.createElement('li');
                        li.innerHTML = `<i class="fas fa-check"></i> ${a.language} (${a.label})`;
                        audioList.appendChild(li);
                    });
                } else {
                    audioList.innerHTML = '<li style="color:#888;">No extra audio found.</li>';
                }
            } else {
                status.innerText = "Extraction Failed: " + data.message;
                status.style.color = "#ff3e3e";
                document.getElementById('extractionResults').style.display = 'block';
                document.getElementById('extractionResults').innerHTML = `
                    <p style="color:#ff3e3e;">${data.message}</p>
                    <button onclick="window.location.href='manage_movies.php?success=1'" class="btn btn-secondary" style="margin-top:20px; width:100%;">Skip and Continue</button>
                `;
            }
        })
        .catch(err => {
            clearInterval(progressInterval);
            status.innerText = "Extraction Error: " + err;
            status.style.color = "#ff3e3e";
        });
    }

    setTimeout(startExtraction, 1000);
});
</script>
<?php endif; ?>

<?php include INCLUDES_PATH . '/footer.php'; ?>
