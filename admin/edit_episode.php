<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

$id = $_GET['id'] ?? null;
if (!$id) redirect('manage_shows.php');

$stmt = $pdo->prepare("SELECT e.*, s.season_number, s.id as season_id, t.title as show_title FROM episodes e JOIN seasons s ON e.season_id = s.id JOIN tv_shows t ON s.tv_show_id = t.id WHERE e.id = ?");
$stmt->execute([$id]);
$episode = $stmt->fetch();

if (!$episode) redirect('manage_shows.php');

// Fetch existing sources
$stmt_sources = $pdo->prepare("SELECT * FROM episode_sources WHERE episode_id = ?");
$stmt_sources->execute([$id]);
$existing_sources = $stmt_sources->fetchAll();

// Fetch existing subtitles
$stmt_subs = $pdo->prepare("SELECT * FROM episode_subtitles WHERE episode_id = ?");
$stmt_subs->execute([$id]);
$existing_subtitles = $stmt_subs->fetchAll();

// Fetch existing audio
$stmt_aud = $pdo->prepare("SELECT * FROM episode_audio WHERE episode_id = ?");
$stmt_aud->execute([$id]);
$existing_audio = $stmt_aud->fetchAll();

$success = ''; $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $episode_number = $_POST['episode_number'];
        $title = clean_input($_POST['title']);
        $description = clean_input($_POST['description']);
        $duration = clean_input($_POST['duration']);
        $main_audio_label = clean_input($_POST['main_audio_label'] ?? 'Original Audio');

        $stmt = $pdo->prepare("UPDATE episodes SET episode_number=?, title=?, description=?, duration=?, main_audio_label=? WHERE id=?");
        if ($stmt->execute([$episode_number, $title, $description, $duration, $main_audio_label, $id])) {
            
            $needs_extraction = false;
            $video_to_extract = '';

            // Handle Multiple Sources
            $pdo->prepare("DELETE FROM episode_sources WHERE episode_id = ?")->execute([$id]);
            
            if (isset($_POST['sources']) && is_array($_POST['sources'])) {
                foreach ($_POST['sources'] as $index => $source) {
                    $label = clean_input($source['label']);
                    $stype = clean_input($source['type']);
                    $surl = clean_input($source['url']);
                    
                    if ($stype === 'file' && !empty($source['existing_url']) && (!isset($_FILES['source_files']['name'][$index]) || $_FILES['source_files']['error'][$index] != 0)) {
                        $surl = $source['existing_url'];
                    }
                    
                    if ($stype === 'file' && isset($_FILES['source_files']['name'][$index]) && $_FILES['source_files']['error'][$index] == 0) {
                        $file_name = $_FILES['source_files']['name'][$index];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed = ['mp4', 'mkv', 'webm', 'avi', 'mov'];
                        if (in_array($ext, $allowed)) {
                            $name = time() . "_ep_source_edit_$index." . $ext;
                            if (move_uploaded_file($_FILES['source_files']['tmp_name'][$index], 'uploads/movies/' . $name)) {
                                $surl = 'uploads/movies/' . $name;
                                $needs_extraction = true;
                                $video_to_extract = $surl;
                            }
                        }
                    } elseif (strpos($surl, 'http') === 0) {
                        $needs_extraction = true;
                        $video_to_extract = $surl;
                    }
                    
                    if (!empty($surl)) {
                        $pdo->prepare("INSERT INTO episode_sources (episode_id, label, source_type, source_url) VALUES (?, ?, ?, ?)")->execute([$id, $label, $stype, $surl]);
                    }
                }
            }

            // Handle Subtitles (Enhanced for Local Files)
            $pdo->prepare("DELETE FROM episode_subtitles WHERE episode_id = ?")->execute([$id]);
            if (isset($_POST['subtitles']) && is_array($_POST['subtitles'])) {
                foreach ($_POST['subtitles'] as $index => $sub) {
                    $stype = clean_input($sub['type'] ?? 'url');
                    $surl = clean_input($sub['url'] ?? '');
                    
                    if ($stype === 'file' && !empty($sub['existing_url']) && (!isset($_FILES['sub_files']['name'][$index]) || $_FILES['sub_files']['error'][$index] != 0)) {
                        $surl = $sub['existing_url'];
                    }
                    
                    if ($stype === 'file' && isset($_FILES['sub_files']['name'][$index]) && $_FILES['sub_files']['error'][$index] == 0) {
                        $ext = strtolower(pathinfo($_FILES['sub_files']['name'][$index], PATHINFO_EXTENSION));
                        $name = time() . "_ep_sub_edit_$index." . $ext;
                        if (move_uploaded_file($_FILES['sub_files']['tmp_name'][$index], 'uploads/subtitles/' . $name)) {
                            $surl = 'uploads/subtitles/' . $name;
                        }
                    }
                    if (!empty($surl)) {
                        $pdo->prepare("INSERT INTO episode_subtitles (episode_id, language, label, file_url) VALUES (?, ?, ?, ?)")
                            ->execute([$id, clean_input($sub['language']), clean_input($sub['label']), $surl]);
                    }
                }
            }

            // Handle Audio Tracks (Enhanced for Local Files)
            $pdo->prepare("DELETE FROM episode_audio WHERE episode_id = ?")->execute([$id]);
            if (isset($_POST['audio_tracks']) && is_array($_POST['audio_tracks'])) {
                foreach ($_POST['audio_tracks'] as $index => $aud) {
                    $atype = clean_input($aud['type'] ?? 'url');
                    $aurl = clean_input($aud['url'] ?? '');
                    
                    if ($atype === 'file' && !empty($aud['existing_url']) && (!isset($_FILES['audio_files']['name'][$index]) || $_FILES['audio_files']['error'][$index] != 0)) {
                        $aurl = $aud['existing_url'];
                    }
                    
                    if ($atype === 'file' && isset($_FILES['audio_files']['name'][$index]) && $_FILES['audio_files']['error'][$index] == 0) {
                        $ext = strtolower(pathinfo($_FILES['audio_files']['name'][$index], PATHINFO_EXTENSION));
                        $name = time() . "_ep_audio_edit_$index." . $ext;
                        if (move_uploaded_file($_FILES['audio_files']['tmp_name'][$index], 'uploads/audio/' . $name)) {
                            $aurl = 'uploads/audio/' . $name;
                        }
                    }
                    if (!empty($aurl)) {
                        $pdo->prepare("INSERT INTO episode_audio (episode_id, language, label, file_url) VALUES (?, ?, ?, ?)")
                            ->execute([$id, clean_input($aud['language']), clean_input($aud['label']), $aurl]);
                    }
                }
            }

            if ($needs_extraction) {
                $show_extraction_ui = true;
                $extraction_video = $video_to_extract;
                $extraction_id = $id;
                $extraction_type = 'episode';
            } else {
                $success = "Episode updated successfully!";
                // Refresh
                $stmt->execute([$id]); $episode = $stmt->fetch();
                $stmt_sources->execute([$id]); $existing_sources = $stmt_sources->fetchAll();
            }
        } else {
            $error = "Failed to update episode.";
        }
    }

$page_title = "Edit Episode";
include INCLUDES_PATH . '/header.php';

?>

<style>
    .source-row { background: #2a2a2a; padding: 15px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid var(--primary-color); }
    .source-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .remove-source { color: #ff3e3e; cursor: pointer; }
</style>


    <div class="top-nav">
        <h2>Edit Episode: <?php echo $episode['show_title']; ?> - S<?php echo $episode['season_number']; ?> E<?php echo $episode['episode_number']; ?></h2>
        <div class="user-info">
            <a href="manage_episodes.php?season_id=<?php echo $episode['season_id']; ?>" class="btn btn-secondary">Back to Season</a>
        </div>
    </div>

    <div class="form-container">
        <?php if($success): ?><div class="success-msg"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="grid-2">
                <div class="form-group">
                    <label>Episode Number</label>
                    <input type="number" name="episode_number" value="<?php echo $episode['episode_number']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" value="<?php echo $episode['title']; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"><?php echo $episode['description']; ?></textarea>
            </div>

            <div class="form-group">
                <label>Duration</label>
                <input type="text" name="duration" value="<?php echo $episode['duration']; ?>">
            </div>

            <div class="form-group">
                <label>Original Audio Label (e.g. English, Tamil)</label>
                <input type="text" name="main_audio_label" value="<?php echo htmlspecialchars($episode['main_audio_label'] ?? 'Original Audio'); ?>" placeholder="Original Audio">
            </div>

                <div id="sources_container"></div>
            </div>

            <!-- Subtitles Section -->
            <div class="form-group" style="background: #1a1a1a; padding: 20px; border-radius: 10px; margin-top: 20px; border-top: 2px solid #3498db;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="margin:0;"><i class="fas fa-closed-captioning"></i> Subtitles</h3>
                    <button type="button" class="btn btn-secondary" id="add_subtitle_btn" style="background:#3498db;"><i class="fas fa-plus"></i> Add Subtitle</button>
                </div>
                <div id="subtitles_container"></div>
            </div>

            <!-- Audio Tracks Section -->
            <div class="form-group" style="background: #1a1a1a; padding: 20px; border-radius: 10px; margin-top: 20px; border-top: 2px solid #f1c40f;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="margin:0;"><i class="fas fa-volume-up"></i> Audio Tracks</h3>
                    <button type="button" class="btn btn-secondary" id="add_audio_btn" style="background:#f1c40f; color:#000;"><i class="fas fa-plus"></i> Add Audio Track</button>
                </div>
                <div id="audio_tracks_container"></div>
            </div>

            <button type="submit" class="btn btn-primary">Update Episode</button>
        </form>
    </div>
</div>

<script>
    let sourceIndex = 0;
    const container = document.getElementById('sources_container');
    const existingSources = <?php echo json_encode($existing_sources); ?>;
    
    function addSourceRow(data = null) {
        const html = `
            <div class="source-row" id="source_${sourceIndex}">
                <div class="source-header">
                    <span style="font-weight:bold; color:var(--primary-color);">Source #${sourceIndex + 1}</span>
                    <i class="fas fa-times remove-source" onclick="removeSource(${sourceIndex})"></i>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label>Label</label>
                        <input type="text" name="sources[${sourceIndex}][label]" value="${data ? data.label : ''}" placeholder="720p, 1080p..." required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="sources[${sourceIndex}][type]" onchange="toggleSourceType(${sourceIndex}, this.value)">
                            <option value="url" ${data && data.source_type === 'url' ? 'selected' : ''}>URL</option>
                            <option value="embed" ${data && data.source_type === 'embed' ? 'selected' : ''}>Embed</option>
                            <option value="file" ${data && data.source_type === 'file' ? 'selected' : ''}>Upload</option>
                        </select>
                    </div>
                    <div class="form-group" id="url_input_${sourceIndex}" style="${data && data.source_type === 'file' ? 'display:none;' : ''}">
                        <label>Link / Embed</label>
                        <input type="text" name="sources[${sourceIndex}][url]" value="${data ? data.source_url : ''}" placeholder="Enter Link">
                    </div>
                    <div class="form-group" id="file_input_${sourceIndex}" style="${data && data.source_type === 'file' ? '' : 'display:none;'}">
                        <label>Select File</label>
                        <input type="file" name="source_files[${sourceIndex}]">
                        ${data && data.source_type === 'file' ? `<small style="color:#888;">Current: ${data.source_url}</small><input type="hidden" name="sources[${sourceIndex}][existing_url]" value="${data.source_url}">` : ''}
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

    document.getElementById('add_source_btn').addEventListener('click', () => addSourceRow());
    if (existingSources.length > 0) { existingSources.forEach(s => addSourceRow(s)); } else { addSourceRow(); }

    // Subtitles & Audio Management
    let subIndex = 0;
    let audioIndex = 0;
    const subContainer = document.getElementById('subtitles_container');
    const audioContainer = document.getElementById('audio_tracks_container');
    const existingSubs = <?php echo json_encode($existing_subtitles); ?>;
    const existingAuds = <?php echo json_encode($existing_audio); ?>;

    function addSubtitleRow(data = null) {
        const type = (data && data.file_url && data.file_url.startsWith('uploads/subtitles/')) ? 'file' : 'url';
        const html = `
            <div class="source-row" id="sub_${subIndex}" style="border-left:3px solid #3498db;">
                <i class="fas fa-times remove-source" onclick="document.getElementById('sub_${subIndex}').remove()"></i>
                <div class="grid-2">
                    <div class="form-group"><label>Lang Code</label><input type="text" name="subtitles[${subIndex}][language]" value="${data ? data.language : ''}" placeholder="en" style="padding:6px;"></div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="subtitles[${subIndex}][type]" onchange="toggleT('sub', ${subIndex}, this.value)" style="padding:6px;">
                            <option value="url" ${type === 'url' ? 'selected' : ''}>URL</option>
                            <option value="file" ${type === 'file' ? 'selected' : ''}>Upload</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Label</label><input type="text" name="subtitles[${subIndex}][label]" value="${data ? data.label : 'Subtitle'}" placeholder="e.g. English" style="padding:6px;"></div>
                <div id="sub_url_inp_${subIndex}" style="${type === 'file' ? 'display:none;' : ''}"><input type="text" name="subtitles[${subIndex}][url]" value="${data ? data.file_url : ''}" placeholder="URL" style="padding:6px;"></div>
                <div id="sub_file_inp_${subIndex}" style="${type === 'file' ? '' : 'display:none;'}">
                    <input type="file" name="sub_files[${subIndex}]">
                    ${type === 'file' ? `<small style="color:#888; display:block; margin-top:5px;">Current: ${data.file_url}</small><input type="hidden" name="subtitles[${subIndex}][existing_url]" value="${data.file_url}">` : ''}
                </div>
            </div>
        `;
        subContainer.insertAdjacentHTML('beforeend', html);
        subIndex++;
    }

    function addAudioRow(data = null) {
        const type = (data && data.file_url && data.file_url.startsWith('uploads/audio/')) ? 'file' : 'url';
        const html = `
            <div class="source-row" id="audio_${audioIndex}" style="border-left:3px solid #f1c40f;">
                <i class="fas fa-times remove-source" onclick="document.getElementById('audio_${audioIndex}').remove()"></i>
                <div class="grid-2">
                    <div class="form-group"><label>Lang</label><input type="text" name="audio_tracks[${audioIndex}][language]" value="${data ? data.language : ''}" placeholder="HIN" style="padding:6px;"></div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="audio_tracks[${audioIndex}][type]" onchange="toggleT('audio', ${audioIndex}, this.value)" style="padding:6px;">
                            <option value="url" ${type === 'url' ? 'selected' : ''}>URL</option>
                            <option value="file" ${type === 'file' ? 'selected' : ''}>Upload</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Label</label><input type="text" name="audio_tracks[${audioIndex}][label]" value="${data ? data.label : 'Audio'}" placeholder="e.g. Hindi Audio" style="padding:6px;"></div>
                <div id="audio_url_inp_${audioIndex}" style="${type === 'file' ? 'display:none;' : ''}"><input type="text" name="audio_tracks[${audioIndex}][url]" value="${data ? data.file_url : ''}" placeholder="URL" style="padding:6px;"></div>
                <div id="audio_file_inp_${audioIndex}" style="${type === 'file' ? '' : 'display:none;'}">
                    <input type="file" name="audio_files[${audioIndex}]">
                    ${type === 'file' ? `<small style="color:#888; display:block; margin-top:5px;">Current: ${data.file_url}</small><input type="hidden" name="audio_tracks[${audioIndex}][existing_url]" value="${data.file_url}">` : ''}
                </div>
            </div>
        `;
        audioContainer.insertAdjacentHTML('beforeend', html);
        audioIndex++;
    }

    function toggleT(p, i, v) {
        document.getElementById(`${p}_url_inp_${i}`).style.display = v === 'file' ? 'none' : 'block';
        document.getElementById(`${p}_file_inp_${i}`).style.display = v === 'file' ? 'block' : 'none';
    }

    document.getElementById('add_subtitle_btn').addEventListener('click', () => addSubtitleRow());
    document.getElementById('add_audio_btn').addEventListener('click', () => addAudioRow());

    if (existingSubs.length > 0) existingSubs.forEach(s => addSubtitleRow(s));
    if (existingAuds.length > 0) existingAuds.forEach(a => addAudioRow(a));
</script>

<?php if (isset($show_extraction_ui) && $show_extraction_ui): ?>
<!-- Extraction Overlay -->
<div id="extractionOverlay" class="extraction-overlay">
    <div class="extraction-box" style="width: 600px;">
        <div class="extraction-header">
            <h3><i class="fas fa-magic"></i> AI Media Extraction</h3>
            <p>Processing updated episode for subtitles and audio tracks...</p>
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
                <h4>Episode Updated!</h4>
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
                <button onclick="window.location.href='manage_episodes.php?season_id=<?php echo $episode['season_id']; ?>&success=1'" class="btn btn-primary" style="margin-top:20px; width:100%;">Continue</button>
            </div>

            <div id="extractionControls" style="margin-top:20px;">
                <button onclick="window.location.href='manage_episodes.php?season_id=<?php echo $episode['season_id']; ?>&success=1'" class="btn btn-secondary" style="background:#444; width:100%;"><i class="fas fa-forward"></i> Skip & Continue</button>
            </div>
        </div>
    </div>
</div>

<style>
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
    width: 600px;
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
                status.innerText = "Update Finished!";
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
                } else { subsList.innerHTML = '<li style="color:#888;">None found.</li>'; }

                if (data.audio.length > 0) {
                    data.audio.forEach(a => {
                        const li = document.createElement('li');
                        li.innerHTML = `<i class="fas fa-check"></i> ${a.language} (${a.label})`;
                        audioList.appendChild(li);
                    });
                } else { audioList.innerHTML = '<li style="color:#888;">None found.</li>'; }
            } else {
                status.innerText = "Error: " + data.message;
                status.style.color = "#ff3e3e";
                document.getElementById('extractionResults').style.display = 'block';
                document.getElementById('extractionResults').innerHTML = `
                    <p style="color:#ff3e3e;">${data.message}</p>
                    <button onclick="window.location.href='manage_episodes.php?season_id=<?php echo $episode['season_id']; ?>&success=1'" class="btn btn-secondary" style="margin-top:20px; width:100%;">Skip</button>
                `;
            }
        })
        .catch(err => {
            clearInterval(progressInterval);
            status.innerText = "Fetch Error: " + err;
        });
    }

    setTimeout(startExtraction, 1000);
});
</script>
<?php endif; ?>

<?php include INCLUDES_PATH . '/footer.php'; ?>
