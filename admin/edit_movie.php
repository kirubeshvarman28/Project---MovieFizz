<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$id]);
$movie = $stmt->fetch();

if (!$movie) redirect('manage_movies.php');

$success = '';
$error = '';

$stmt_sources = $pdo->prepare("SELECT * FROM movie_sources WHERE movie_id = ?");
$stmt_subs = $pdo->prepare("SELECT * FROM movie_subtitles WHERE movie_id = ?");
$stmt_auds = $pdo->prepare("SELECT * FROM movie_audio WHERE movie_id = ?");

$stmt_sources->execute([$id]); $existing_sources = $stmt_sources->fetchAll();
$stmt_subs->execute([$id]); $existing_subs = $stmt_subs->fetchAll();
$stmt_auds->execute([$id]); $existing_auds = $stmt_auds->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $genre = clean_input($_POST['genre']);
    $rating = $_POST['rating'];
    $year = $_POST['release_year'];
    $runtime = $_POST['runtime'];
    $trailer = clean_input($_POST['trailer_url']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $main_audio_label = clean_input($_POST['main_audio_label'] ?? 'Original Audio');
    $language = clean_input($_POST['language'] ?? 'English');

        $stmt = $pdo->prepare("UPDATE movies SET title=?, description=?, genre=?, rating=?, release_year=?, runtime=?, trailer_url=?, is_featured=?, is_published=?, main_audio_label=?, language=? WHERE id=?");
        if ($stmt->execute([$title, $description, $genre, $rating, $year, $runtime, $trailer, $is_featured, $is_published, $main_audio_label, $language, $id])) {
            
            // Check if any local files were uploaded that need extraction
            $needs_extraction = false;
            $video_to_extract = '';

            // Handle Multiple Sources
            $pdo->prepare("DELETE FROM movie_sources WHERE movie_id = ?")->execute([$id]);
            if (isset($_POST['sources']) && is_array($_POST['sources'])) {
                foreach ($_POST['sources'] as $index => $source) {
                    $label = clean_input($source['label']);
                    $stype = clean_input($source['type']);
                    $surl = clean_input($source['url'] ?? '');
                    
                    if ($stype === 'file') {
                        if (isset($_FILES['source_files']['name'][$index]) && $_FILES['source_files']['error'][$index] == 0) {
                            $file_name = $_FILES['source_files']['name'][$index];
                            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            if (in_array($ext, ['mp4', 'mkv', 'webm', 'avi', 'mov'])) {
                                $name = time() . "_source_edit_$index." . $ext;
                                if (move_uploaded_file($_FILES['source_files']['tmp_name'][$index], 'uploads/movies/' . $name)) {
                                    $surl = 'uploads/movies/' . $name;
                                    $needs_extraction = true; $video_to_extract = $surl;
                                }
                            }
                        } elseif (strpos($surl, 'http') === 0) {
                            $needs_extraction = true;
                            $video_to_extract = $surl;
                        } elseif (!empty($source['existing_url'])) {
                            $surl = $source['existing_url'];
                        }
                    }
                    if (!empty($surl)) {
                        $pdo->prepare("INSERT INTO movie_sources (movie_id, label, source_type, source_url) VALUES (?, ?, ?, ?)")->execute([$id, $label, $stype, $surl]);
                    }
                }
            }

            // Handle Subtitles (Enhanced for Local Files)
            $pdo->prepare("DELETE FROM movie_subtitles WHERE movie_id = ?")->execute([$id]);
            if (isset($_POST['subtitles']) && is_array($_POST['subtitles'])) {
                foreach ($_POST['subtitles'] as $index => $sub) {
                    $stype = clean_input($sub['type'] ?? 'url');
                    $surl = clean_input($sub['url'] ?? '');
                    
                    if ($stype === 'file') {
                        if (isset($_FILES['sub_files']['name'][$index]) && $_FILES['sub_files']['error'][$index] == 0) {
                            $file_name = $_FILES['sub_files']['name'][$index];
                            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            if (in_array($ext, ['vtt', 'srt'])) {
                                $name = time() . "_sub_edit_$index." . $ext;
                                if (move_uploaded_file($_FILES['sub_files']['tmp_name'][$index], 'uploads/subtitles/' . $name)) {
                                    $surl = 'uploads/subtitles/' . $name;
                                }
                            }
                        } elseif (!empty($sub['existing_url'])) {
                            $surl = $sub['existing_url'];
                        }
                    }
                    if (!empty($surl)) {
                        $pdo->prepare("INSERT INTO movie_subtitles (movie_id, language, label, file_url) VALUES (?, ?, ?, ?)")
                            ->execute([$id, clean_input($sub['language']), clean_input($sub['label']), $surl]);
                    }
                }
            }

            // Handle Audio Tracks (Enhanced for Local Files)
            $pdo->prepare("DELETE FROM movie_audio WHERE movie_id = ?")->execute([$id]);
            if (isset($_POST['audio_tracks']) && is_array($_POST['audio_tracks'])) {
                foreach ($_POST['audio_tracks'] as $index => $aud) {
                    $atype = clean_input($aud['type'] ?? 'url');
                    $aurl = clean_input($aud['url'] ?? '');
                    
                    if ($atype === 'file') {
                        if (isset($_FILES['audio_files']['name'][$index]) && $_FILES['audio_files']['error'][$index] == 0) {
                            $file_name = $_FILES['audio_files']['name'][$index];
                            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            if (in_array($ext, ['mp3', 'm4a', 'aac', 'wav'])) {
                                $name = time() . "_audio_edit_$index." . $ext;
                                if (move_uploaded_file($_FILES['audio_files']['tmp_name'][$index], 'uploads/audio/' . $name)) {
                                    $aurl = 'uploads/audio/' . $name;
                                }
                            }
                        } elseif (!empty($aud['existing_url'])) {
                            $aurl = $aud['existing_url'];
                        }
                    }
                    if (!empty($aurl)) {
                        $pdo->prepare("INSERT INTO movie_audio (movie_id, language, label, file_url) VALUES (?, ?, ?, ?)")
                            ->execute([$id, clean_input($aud['language']), clean_input($aud['label']), $aurl]);
                    }
                }
            }

            // SUCCESS FLOW
            if ($needs_extraction) {
                // Show Extraction UI
                $show_extraction_ui = true;
                $extraction_video = $video_to_extract;
                $extraction_id = $id;
                $extraction_type = 'movie';
            } else {
                $success = "Movie updated successfully!";
                // Refresh data for non-extraction redirect
                $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?"); $stmt->execute([$id]); $movie = $stmt->fetch();
                $stmt_sources->execute([$id]); $existing_sources = $stmt_sources->fetchAll();
                $stmt_subs->execute([$id]); $existing_subs = $stmt_subs->fetchAll();
                $stmt_auds->execute([$id]); $existing_auds = $stmt_auds->fetchAll();
            }
        } else {
            $error = "Failed to update movie.";
    }
}

$page_title = "Edit Movie: " . ($movie['title'] ?? '');
include INCLUDES_PATH . '/header.php';
?>

<style>
    .source-row { background: #111; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #333; position: relative; }
    .source-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .remove-source { color: #ff3e3e; cursor: pointer; font-size: 20px; transition: 0.3s; }
    .remove-source:hover { transform: rotate(90deg); }
    
    .admin-form label { font-weight: 600; color: #ccc; margin-bottom: 10px; display: block; font-size: 14px; }
    .admin-form input, .admin-form select, .admin-form textarea {
        width: 100%; padding: 12px; background: #000; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 15px; box-sizing: border-box;
    }
    .admin-form input:focus { border-color: #e50914; outline: none; }
    
    .section-card { background: #1a1a1a; padding: 30px; border-radius: 12px; border: 1px solid #222; margin-top: 30px; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .section-header h3 { margin: 0; font-size: 20px; display: flex; align-items: center; gap: 12px; }
    .section-header h3 i { color: #e50914; }
</style>

<div class="top-nav">
    <div class="nav-left">
        <h2><i class="fas fa-edit"></i> Edit Movie: <span style="color:#e50914;"><?php echo $movie['title']; ?></span></h2>
    </div>
    <div class="nav-right">
        <a href="manage_movies.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Movies</a>
    </div>
</div>

<div class="form-container" style="max-width: 1200px; margin: 0 auto; padding: 30px;">
    <?php if($success): ?><div class="success-msg" style="background:#2ecc71; color:#fff; padding:15px; border-radius:8px; margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>
    <?php if($error): ?><div class="error-msg" style="background:#ff3e3e; color:#fff; padding:15px; border-radius:8px; margin-bottom:20px;"><?php echo $error; ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" value="<?php echo $movie['title']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Genre</label>
                            <input type="text" name="genre" value="<?php echo $movie['genre']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?php echo $movie['description']; ?></textarea>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Rating</label>
                            <input type="number" step="0.1" name="rating" value="<?php echo $movie['rating']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Release Year</label>
                            <input type="number" name="release_year" value="<?php echo $movie['release_year']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Runtime (Mins)</label>
                            <input type="number" name="runtime" value="<?php echo $movie['runtime']; ?>">
                        </div>
                    </div>

                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="fas fa-play-circle"></i> Video Sources</h3>
                            <button type="button" class="btn btn-secondary btn-sm" id="add_source_btn" style="background:#00d573; border:none;"><i class="fas fa-plus"></i> Add Source</button>
                        </div>
                        <div id="sources_container"></div>
                    </div>

                    <div class="section-card" style="border-top: 3px solid #3498db;">
                        <div class="section-header">
                            <h3><i class="fas fa-closed-captioning"></i> Subtitles</h3>
                            <button type="button" class="btn btn-secondary btn-sm" id="add_subtitle_btn" style="background:#3498db; border:none;"><i class="fas fa-plus"></i> Add Subtitle</button>
                        </div>
                        <div id="subtitles_container"></div>
                    </div>

                    <div class="section-card" style="border-top: 3px solid #f1c40f;">
                        <div class="section-header">
                            <h3><i class="fas fa-volume-up"></i> Alternative Audio Tracks</h3>
                            <button type="button" class="btn btn-secondary btn-sm" id="add_audio_btn" style="background:#f1c40f; color:#000; border:none;"><i class="fas fa-plus"></i> Add Audio Track</button>
                        </div>
                        <div id="audio_tracks_container"></div>
                    </div>

                    <div class="section-card">
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Trailer URL (YouTube)</label>
                                <input type="text" name="trailer_url" value="<?php echo $movie['trailer_url']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Original Audio Label</label>
                                <input type="text" name="main_audio_label" value="<?php echo htmlspecialchars($movie['main_audio_label'] ?? 'Original Audio'); ?>">
                            </div>
                        </div>

                        <div class="grid-2" style="margin-top:20px;">
                            <div class="form-group" style="display:flex; align-items:center; gap:10px; background:#222; padding:15px; border-radius:8px;">
                                <input type="checkbox" name="is_featured" style="width:20px; height:20px;" <?php echo $movie['is_featured'] ? 'checked' : ''; ?>>
                                <label style="margin:0;">Featured Movie</label>
                            </div>
                            <div class="form-group" style="display:flex; align-items:center; gap:10px; background:#222; padding:15px; border-radius:8px;">
                                <input type="checkbox" name="is_published" style="width:20px; height:20px;" <?php echo $movie['is_published'] ? 'checked' : ''; ?>>
                                <label style="margin:0;">Published</label>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:40px; display:flex; gap:20px;">
                        <button type="submit" class="btn btn-primary" style="flex:2; padding:15px; font-size:18px; font-weight:700;"><i class="fas fa-save"></i> Save All Changes</button>
                        <a href="manage_movies.php" class="btn btn-secondary" style="flex:1; padding:15px; display:flex; align-items:center; justify-content:center; background:#444; color:#fff;">Cancel</a>
                    </div>
                </form>
            </div>

    <script>
        let sourceIndex = 0;
        let subIndex = 0;
        let audioIndex = 0;

        const sourcesContainer = document.getElementById('sources_container');
        const subContainer = document.getElementById('subtitles_container');
        const audioContainer = document.getElementById('audio_tracks_container');

        // Sources Management
        function addSourceRow(data = null) {
            const html = `
                <div class="source-row" id="source_${sourceIndex}">
                    <div class="source-header">
                        <span style="font-weight:bold; color:#00d573;"><i class="fas fa-play"></i> Source #${sourceIndex + 1}</span>
                        <i class="fas fa-times remove-source" onclick="document.getElementById('source_${sourceIndex}').remove()"></i>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Label</label>
                            <input type="text" name="sources[${sourceIndex}][label]" value="${data ? data.label : ''}" placeholder="1080p, 4K..." required>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="sources[${sourceIndex}][type]" onchange="toggleType('source', ${sourceIndex}, this.value)">
                                <option value="url" ${data && data.source_type === 'url' ? 'selected' : ''}>External URL</option>
                                <option value="embed" ${data && data.source_type === 'embed' ? 'selected' : ''}>Embed Code</option>
                                <option value="file" ${data && data.source_type === 'file' ? 'selected' : ''}>Direct Upload</option>
                            </select>
                        </div>
                        <div class="form-group" id="source_url_inp_${sourceIndex}" style="${data && data.source_type === 'file' ? 'display:none;' : ''}">
                            <label>URL / Embed</label>
                            <input type="text" name="sources[${sourceIndex}][url]" value="${data ? data.source_url : ''}">
                        </div>
                        <div class="form-group" id="source_file_inp_${sourceIndex}" style="${data && data.source_type === 'file' ? '' : 'display:none;'}">
                            <label>Select File</label>
                            <input type="file" name="source_files[${sourceIndex}]">
                            ${data && data.source_type === 'file' ? `<small style="color:#888;">Current: ${data.source_url}</small><input type="hidden" name="sources[${sourceIndex}][existing_url]" value="${data.source_url}">` : ''}
                        </div>
                    </div>
                </div>
            `;
            sourcesContainer.insertAdjacentHTML('beforeend', html);
            sourceIndex++;
        }

        // Subtitles Management
        function addSubtitleRow(data = null) {
            const isFile = data && data.file_url && data.file_url.startsWith('uploads/');
            const html = `
                <div class="source-row" id="sub_${subIndex}" style="border-left-color:#3498db;">
                    <div class="source-header">
                        <span style="font-weight:bold; color:#3498db;"><i class="fas fa-closed-captioning"></i> Subtitle #${subIndex + 1}</span>
                        <i class="fas fa-times remove-source" onclick="document.getElementById('sub_${subIndex}').remove()"></i>
                    </div>
                    <div class="grid-2">
                        <div class="form-group"><label>Language Code</label><input type="text" name="subtitles[${subIndex}][language]" value="${data ? data.language : ''}" placeholder="en, hi, ta"></div>
                        <div class="form-group"><label>Label</label><input type="text" name="subtitles[${subIndex}][label]" value="${data ? data.label : ''}" placeholder="English, Tamil"></div>
                    </div>
                    <div class="grid-2" style="margin-top:10px;">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="subtitles[${subIndex}][type]" onchange="toggleType('sub', ${subIndex}, this.value)">
                                <option value="url" ${!isFile ? 'selected' : ''}>External URL</option>
                                <option value="file" ${isFile ? 'selected' : ''}>Local File (.vtt/.srt)</option>
                            </select>
                        </div>
                        <div class="form-group" id="sub_url_inp_${subIndex}" style="${isFile ? 'display:none;' : ''}">
                            <label>URL</label>
                            <input type="text" name="subtitles[${subIndex}][url]" value="${data && !isFile ? data.file_url : ''}">
                        </div>
                        <div class="form-group" id="sub_file_inp_${subIndex}" style="${isFile ? '' : 'display:none;'}">
                            <label>Upload File</label>
                            <input type="file" name="sub_files[${subIndex}]">
                            ${isFile ? `<small style="color:#888;">Current: ${data.file_url}</small><input type="hidden" name="subtitles[${subIndex}][existing_url]" value="${data.file_url}">` : ''}
                        </div>
                    </div>
                </div>
            `;
            subContainer.insertAdjacentHTML('beforeend', html);
            subIndex++;
        }

        // Audio Tracks Management
        function addAudioRow(data = null) {
            const isFile = data && data.file_url && data.file_url.startsWith('uploads/');
            const html = `
                <div class="source-row" id="audio_${audioIndex}" style="border-left-color:#f1c40f;">
                    <div class="source-header">
                        <span style="font-weight:bold; color:#f1c40f;"><i class="fas fa-volume-up"></i> Audio #${audioIndex + 1}</span>
                        <i class="fas fa-times remove-source" onclick="document.getElementById('audio_${audioIndex}').remove()"></i>
                    </div>
                    <div class="grid-2">
                        <div class="form-group"><label>Language</label><input type="text" name="audio_tracks[${audioIndex}][language]" value="${data ? data.language : ''}" placeholder="TAM, HIN"></div>
                        <div class="form-group"><label>Label</label><input type="text" name="audio_tracks[${audioIndex}][label]" value="${data ? data.label : ''}" placeholder="Tamil Audio"></div>
                    </div>
                    <div class="grid-2" style="margin-top:10px;">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="audio_tracks[${audioIndex}][type]" onchange="toggleType('audio', ${audioIndex}, this.value)">
                                <option value="url" ${!isFile ? 'selected' : ''}>External URL</option>
                                <option value="file" ${isFile ? 'selected' : ''}>Local File (Audio)</option>
                            </select>
                        </div>
                        <div class="form-group" id="audio_url_inp_${audioIndex}" style="${isFile ? 'display:none;' : ''}">
                            <label>URL</label>
                            <input type="text" name="audio_tracks[${audioIndex}][url]" value="${data && !isFile ? data.file_url : ''}">
                        </div>
                        <div class="form-group" id="audio_file_inp_${audioIndex}" style="${isFile ? '' : 'display:none;'}">
                            <label>Upload File</label>
                            <input type="file" name="audio_files[${audioIndex}]">
                            ${isFile ? `<small style="color:#888;">Current: ${data.file_url}</small><input type="hidden" name="audio_tracks[${audioIndex}][existing_url]" value="${data.file_url}">` : ''}
                        </div>
                    </div>
                </div>
            `;
            audioContainer.insertAdjacentHTML('beforeend', html);
            audioIndex++;
        }

        function toggleType(prefix, index, value) {
            const urlInp = document.getElementById(`${prefix}_url_inp_${index}`);
            const fileInp = document.getElementById(`${prefix}_file_inp_${index}`);
            if (value === 'file') {
                urlInp.style.display = 'none';
                fileInp.style.display = 'block';
            } else {
                urlInp.style.display = 'block';
                fileInp.style.display = 'none';
            }
        }

        document.getElementById('add_source_btn').addEventListener('click', () => addSourceRow());
        document.getElementById('add_subtitle_btn').addEventListener('click', () => addSubtitleRow());
        document.getElementById('add_audio_btn').addEventListener('click', () => addAudioRow());

        // Initialize with data
        <?php echo json_encode($existing_sources); ?>.forEach(s => addSourceRow(s));
        <?php echo json_encode($existing_subs); ?>.forEach(s => addSubtitleRow(s));
        <?php echo json_encode($existing_auds); ?>.forEach(a => addAudioRow(a));
        
        if (sourceIndex === 0) addSourceRow();
    </script>

<?php if (isset($show_extraction_ui) && $show_extraction_ui): ?>
<div class="extraction-overlay">
    <div class="extraction-box" style="width: 600px; padding:40px; background:#111; border-radius:15px; border:1px solid #333;">
        <h3 style="color:#e50914;"><i class="fas fa-magic"></i> AI Extraction</h3>
        <p style="color:#888;">Processing updated video...</p>
        <div id="extractionStatus" style="font-weight:bold; margin-bottom:10px;">Initializing...</div>
        <div style="background:#222; height:10px; border-radius:5px; overflow:hidden; margin-bottom:20px;">
            <div id="extractionProgressBar" style="width:0%; height:100%; background:#e50914; transition:0.3s;"></div>
        </div>
        <button onclick="window.location.href='manage_movies.php?success=1'" class="btn btn-secondary" style="width:100%;">Skip and Finish</button>
    </div>
</div>
<script>
    // Simplified polling for the overview - works same as add_movie
    function poll() {
        fetch(`ajax_progress.php?video_path=<?php echo urlencode($extraction_video); ?>&content_id=<?php echo $extraction_id; ?>`)
        .then(res => res.json())
        .then(data => {
            if (data.status) {
                document.getElementById('extractionStatus').innerText = data.status;
                document.getElementById('extractionProgressBar').style.width = data.percent + '%';
            }
        });
    }
    setInterval(poll, 1500);
    
    // Start extraction
    const fd = new FormData();
    fd.append('video_path', '<?php echo $extraction_video; ?>');
    fd.append('content_id', '<?php echo $extraction_id; ?>');
    fd.append('type', 'movie');
    fetch('ajax_extract.php', { method: 'POST', body: fd })
    .then(() => window.location.href='manage_movies.php?success=1');
</script>
<?php endif; ?>

<?php include INCLUDES_PATH . '/footer.php'; ?>
