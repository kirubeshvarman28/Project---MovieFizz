<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) redirect('login.php');

$error = '';
$success = '';

$season_id = $_GET['season_id'] ?? 0;

// Add Episode
if (isset($_POST['add_episode'])) {
    $season_id = $_POST['season_id'];
    $episode_number = $_POST['episode_number'];
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $duration = clean_input($_POST['duration']);

    if (!empty($season_id) && !empty($episode_number) && !empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO episodes (season_id, episode_number, title, description, duration) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$season_id, $episode_number, $title, $description, $duration])) {
            $episode_id = $pdo->lastInsertId();
            
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
                        $name = time() . "_ep_source_$index." . $ext;
                        if (move_uploaded_file($_FILES['source_files']['tmp_name'][$index], '../uploads/movies/' . $name)) {
                            $surl = 'uploads/movies/' . $name;
                            $needs_extraction = true;
                            $video_to_extract = $surl;
                        }
                    } elseif (strpos($surl, 'http') === 0) {
                        $needs_extraction = true;
                        $video_to_extract = $surl;
                    }
                    if (!empty($surl)) {
                        $pdo->prepare("INSERT INTO episode_sources (episode_id, label, source_type, source_url) VALUES (?, ?, ?, ?)")->execute([$episode_id, $label, $stype, $surl]);
                    }
                }
            }

            // Handle Subtitles (Enhanced for Local Files)
            if (isset($_POST['subtitles']) && is_array($_POST['subtitles'])) {
                foreach ($_POST['subtitles'] as $index => $sub) {
                    $stype = clean_input($sub['type'] ?? 'url');
                    $surl = clean_input($sub['url'] ?? '');
                    
                    if ($stype === 'file' && isset($_FILES['sub_files']['name'][$index]) && $_FILES['sub_files']['error'][$index] == 0) {
                        $ext = strtolower(pathinfo($_FILES['sub_files']['name'][$index], PATHINFO_EXTENSION));
                        $name = time() . "_ep_sub_$index." . $ext;
                        if (move_uploaded_file($_FILES['sub_files']['tmp_name'][$index], '../uploads/subtitles/' . $name)) {
                            $surl = 'uploads/subtitles/' . $name;
                        }
                    }
                    if (!empty($surl)) {
                        $pdo->prepare("INSERT INTO episode_subtitles (episode_id, language, label, file_url) VALUES (?, ?, ?, ?)")
                            ->execute([$episode_id, clean_input($sub['language']), clean_input($sub['label']), $surl]);
                    }
                }
            }

            // Handle Audio Tracks (Enhanced for Local Files)
            if (isset($_POST['audio_tracks']) && is_array($_POST['audio_tracks'])) {
                foreach ($_POST['audio_tracks'] as $index => $aud) {
                    $atype = clean_input($aud['type'] ?? 'url');
                    $aurl = clean_input($aud['url'] ?? '');
                    
                    if ($atype === 'file' && isset($_FILES['audio_files']['name'][$index]) && $_FILES['audio_files']['error'][$index] == 0) {
                        $ext = strtolower(pathinfo($_FILES['audio_files']['name'][$index], PATHINFO_EXTENSION));
                        $name = time() . "_ep_audio_$index." . $ext;
                        if (move_uploaded_file($_FILES['audio_files']['tmp_name'][$index], '../uploads/audio/' . $name)) {
                            $aurl = 'uploads/audio/' . $name;
                        }
                    }
                    if (!empty($aurl)) {
                        $pdo->prepare("INSERT INTO episode_audio (episode_id, language, label, file_url) VALUES (?, ?, ?, ?)")
                            ->execute([$episode_id, clean_input($aud['language']), clean_input($aud['label']), $aurl]);
                    }
                }
            }

            if ($needs_extraction) {
                $show_extraction_ui = true;
                $extraction_video = $video_to_extract;
                $extraction_id = $episode_id;
                $extraction_type = 'episode';
            } else {
                $success = "Episode added successfully!";
            }
        } else {
            $error = "Failed to add episode.";
        }
    }
 else {
        $error = "Season, Episode Number and Title are required.";
    }
}


$stmt = $pdo->query("SELECT s.*, t.title as show_title FROM seasons s JOIN tv_shows t ON s.tv_show_id = t.id ORDER BY t.title ASC, s.season_number ASC");
$all_seasons = $stmt->fetchAll();

$episodes = [];
if ($season_id) {
    $stmt = $pdo->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number ASC");
    $stmt->execute([$season_id]);
    $episodes = $stmt->fetchAll();
}

include 'includes/header.php';
?>

<style>
    .source-row { background: #111; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #333; position: relative; }
    .remove-source { position: absolute; top: 10px; right: 10px; color: #ff3e3e; cursor: pointer; z-index: 5; }
    .section-label { font-size: 13px; font-weight: 700; color: #888; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; display: block; }
    .dashboard-grid { display: grid; grid-template-columns: 450px 1fr; gap: 30px; padding: 20px; align-items: start; }
    .form-container { background: #1a1a1a; padding: 25px; border-radius: 12px; border: 1px solid #333; height: fit-content; }
    @media (max-width: 1100px) { .dashboard-grid { grid-template-columns: 1fr; } }
</style>

<div class="top-nav">
    <h2><i class="fas fa-list-ol"></i> Manage Episodes</h2>
    <div class="user-info"><a href="logout.php" class="logout-link">Logout</a></div>
</div>

<div class="dashboard-grid">
    <div class="form-container">
        <h3 style="margin-top:0; margin-bottom:20px; color:#e50914;"><i class="fas fa-plus-circle"></i> Add New Episode</h3>
        <?php if(isset($_GET['success']) && $_GET['success'] == 1): ?><div class="success-msg" style="background:#2ecc71; color:#fff; padding:10px; border-radius:5px; margin-bottom:15px;">Episode added successfully!</div><?php endif; ?>
        <?php if($error): ?><div class="error-msg" style="background:#ff3e3e; color:#fff; padding:10px; border-radius:5px; margin-bottom:15px;"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="form-group">
                <label>Select Season</label>
                <div class="searchable-select" style="position: relative;">
                    <?php 
                        $current_season_text = '';
                        if ($season_id) {
                            foreach($all_seasons as $s) {
                                if($s['id'] == $season_id) {
                                    $current_season_text = $s['show_title'] . " - S" . $s['season_number'];
                                    break;
                                }
                            }
                        }
                    ?>
                    <input type="text" id="season_search" placeholder="Search & Select Season..." value="<?php echo htmlspecialchars($current_season_text); ?>" autocomplete="off" style="width: 100%; padding: 12px; background: #000; border: 1px solid #333; border-radius: 8px; color: #fff;">
                    <input type="hidden" name="season_id" id="season_id_hidden" value="<?php echo $season_id; ?>">
                    <div id="season_options" class="options-list" style="display: none; position: absolute; width: 100%; max-height: 250px; overflow-y: auto; background: #1a1a1a; border: 1px solid #333; z-index: 100; border-radius: 0 0 8px 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                        <?php foreach($all_seasons as $s): ?>
                            <div class="option-item" data-id="<?php echo $s['id']; ?>" style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #222; transition: background 0.2s;">
                                <?php echo htmlspecialchars($s['show_title'] . " - S" . $s['season_number']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group"><label>Episode #</label><input type="number" name="episode_number" required></div>
                <div class="form-group"><label>Duration</label><input type="text" name="duration" placeholder="45 min"></div>
            </div>

            <div class="form-group">
                 <label>Episode Title</label>
                 <input type="text" name="title" required>
            </div>

            <div style="margin-top:20px; padding:15px; background:#000; border-radius:8px; border:1px solid #222;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <span class="section-label" style="margin:0; color:#00d573;"><i class="fas fa-play"></i> Video Sources</span>
                    <button type="button" class="btn btn-secondary btn-sm" id="add_source_btn" style="padding:4px 8px;"><i class="fas fa-plus"></i></button>
                </div>
                <div id="sources_container"></div>
            </div>

            <div style="margin-top:15px; padding:15px; background:#000; border-radius:8px; border:1px solid #222;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <span class="section-label" style="margin:0; color:#3498db;"><i class="fas fa-closed-captioning"></i> Subtitles</span>
                    <button type="button" class="btn btn-secondary btn-sm" id="add_subtitle_btn" style="padding:4px 8px;"><i class="fas fa-plus"></i></button>
                </div>
                <div id="subtitles_container"></div>
            </div>

            <div style="margin-top:15px; padding:15px; background:#000; border-radius:8px; border:1px solid #222;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <span class="section-label" style="margin:0; color:#f1c40f;"><i class="fas fa-volume-up"></i> Audio</span>
                    <button type="button" class="btn btn-secondary btn-sm" id="add_audio_btn" style="padding:4px 8px;"><i class="fas fa-plus"></i></button>
                </div>
                <div id="audio_tracks_container"></div>
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label>Description (Optional)</label>
                <textarea name="description" rows="2" style="font-size:13px;"></textarea>
            </div>

            <button type="submit" name="add_episode" class="btn btn-primary" style="width:100%; margin-top:10px; padding:12px;"><i class="fas fa-save"></i> Save Episode</button>
        </form>
    </div>

    <div class="recent-section" style="background: #1a1a1a; padding: 25px; border-radius: 12px; border: 1px solid #333; min-height: 400px;">
        <h3 style="margin-top:0; margin-bottom:20px;"><i class="fas fa-th-list"></i> Episodes Listing</h3>
        <?php if(!$season_id): ?>
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:300px; color:#555;">
                <i class="fas fa-tv fa-3x" style="margin-bottom:15px;"></i>
                <p>Select a season from the left to manage episodes.</p>
            </div>
        <?php else: ?>
            <table class="refined-table">
                <thead>
                    <tr>
                        <th width="80">#</th>
                        <th>Episode Title</th>
                        <th width="100">Duration</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($episodes as $episode): ?>
                    <tr>
                        <td style="font-weight:bold; color:#e50914;">Ep <?php echo $episode['episode_number']; ?></td>
                        <td><?php echo $episode['title']; ?></td>
                        <td><span style="background:#222; padding:3px 8px; border-radius:4px; font-size:12px;"><?php echo $episode['duration'] ?: '--'; ?></span></td>
                        <td>
                            <div style="display:flex; gap:10px;">
                                <a href="edit_episode.php?id=<?php echo $episode['id']; ?>" class="action-icon edit" title="Edit"><i class="fas fa-edit"></i></a>
                                <div class="action-icon delete" onclick="deleteItem(<?php echo $episode['id']; ?>, 'episode', this)" title="Delete"><i class="fas fa-trash"></i></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($episodes)): ?><tr><td colspan="4" style="text-align:center; padding:30px; color:#555;">No episodes found for this season.</td></tr><?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>

    <script>
        // Searchable Select Logic
        const seasonSearch = document.getElementById('season_search');
        const seasonOptions = document.getElementById('season_options');
        const seasonHidden = document.getElementById('season_id_hidden');
        const sOptions = seasonOptions.querySelectorAll('.option-item');

        seasonSearch.addEventListener('focus', () => {
            seasonOptions.style.display = 'block';
        });

        document.addEventListener('click', (e) => {
            if (!seasonSearch.contains(e.target) && !seasonOptions.contains(e.target)) {
                seasonOptions.style.display = 'none';
            }
        });

        seasonSearch.addEventListener('keyup', () => {
            const query = seasonSearch.value.toLowerCase();
            sOptions.forEach(opt => {
                const text = opt.innerText.toLowerCase();
                opt.style.display = text.includes(query) ? 'block' : 'none';
            });
            seasonOptions.style.display = 'block';
        });

        sOptions.forEach(opt => {
            opt.addEventListener('click', () => {
                const id = opt.getAttribute('data-id');
                const text = opt.innerText;
                seasonHidden.value = id;
                seasonSearch.value = text;
                seasonOptions.style.display = 'none';
                window.location.href = '?season_id=' + id;
            });
            opt.addEventListener('mouseover', () => opt.style.background = '#e50914');
            opt.addEventListener('mouseout', () => opt.style.background = 'transparent');
        });

        let sourceIndex = 0;
        let subIndex = 0;
        let audioIndex = 0;
        
        function addSourceRow() {
            const html = `
                <div class="source-row" id="source_${sourceIndex}">
                    <i class="fas fa-times remove-source" onclick="document.getElementById('source_${sourceIndex}').remove()"></i>
                    <div class="grid-2">
                        <div class="form-group"><label>Label</label><input type="text" name="sources[${sourceIndex}][label]" placeholder="HD..." required style="padding:6px;"></div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="sources[${sourceIndex}][type]" onchange="toggleT('source', ${sourceIndex}, this.value)" style="padding:6px;">
                                <option value="url">URL</option><option value="file">Upload</option>
                            </select>
                        </div>
                    </div>
                    <div id="source_url_inp_${sourceIndex}"><input type="text" name="sources[${sourceIndex}][url]" placeholder="Link" style="padding:6px;"></div>
                    <div id="source_file_inp_${sourceIndex}" style="display:none;"><input type="file" name="source_files[${sourceIndex}]"></div>
                </div>
            `;
            document.getElementById('sources_container').insertAdjacentHTML('beforeend', html);
            sourceIndex++;
        }

        function addSubtitleRow() {
            const html = `
                <div class="source-row" id="sub_${subIndex}" style="border-left:3px solid #3498db;">
                    <i class="fas fa-times remove-source" onclick="document.getElementById('sub_${subIndex}').remove()"></i>
                    <div class="grid-2">
                        <div class="form-group"><label>Lang Code</label><input type="text" name="subtitles[${subIndex}][language]" placeholder="en" style="padding:6px;"></div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="subtitles[${subIndex}][type]" onchange="toggleT('sub', ${subIndex}, this.value)" style="padding:6px;">
                                <option value="url">URL</option><option value="file">Upload</option>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="subtitles[${subIndex}][label]" value="Subtitle">
                    <div id="sub_url_inp_${subIndex}"><input type="text" name="subtitles[${subIndex}][url]" placeholder="URL" style="padding:6px;"></div>
                    <div id="sub_file_inp_${subIndex}" style="display:none;"><input type="file" name="sub_files[${subIndex}]"></div>
                </div>
            `;
            document.getElementById('subtitles_container').insertAdjacentHTML('beforeend', html);
            subIndex++;
        }

        function addAudioRow() {
            const html = `
                <div class="source-row" id="audio_${audioIndex}" style="border-left:3px solid #f1c40f;">
                    <i class="fas fa-times remove-source" onclick="document.getElementById('audio_${audioIndex}').remove()"></i>
                    <div class="grid-2">
                        <div class="form-group"><label>Lang</label><input type="text" name="audio_tracks[${audioIndex}][language]" placeholder="HIN" style="padding:6px;"></div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="audio_tracks[${audioIndex}][type]" onchange="toggleT('audio', ${audioIndex}, this.value)" style="padding:6px;">
                                <option value="url">URL</option><option value="file">Upload</option>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="audio_tracks[${audioIndex}][label]" value="Audio">
                    <div id="audio_url_inp_${audioIndex}"><input type="text" name="audio_tracks[${audioIndex}][url]" placeholder="URL" style="padding:6px;"></div>
                    <div id="audio_file_inp_${audioIndex}" style="display:none;"><input type="file" name="audio_files[${audioIndex}]"></div>
                </div>
            `;
            document.getElementById('audio_tracks_container').insertAdjacentHTML('beforeend', html);
            audioIndex++;
        }

        function toggleT(p, i, v) {
            document.getElementById(`${p}_url_inp_${i}`).style.display = v === 'file' ? 'none' : 'block';
            document.getElementById(`${p}_file_inp_${i}`).style.display = v === 'file' ? 'block' : 'none';
        }

        document.getElementById('add_source_btn').addEventListener('click', addSourceRow);
        document.getElementById('add_subtitle_btn').addEventListener('click', addSubtitleRow);
        document.getElementById('add_audio_btn').addEventListener('click', addAudioRow);
        
        // Auto-add first source row
        addSourceRow();


        function deleteItem(id, type, element) {
            openDeleteModal(`Are you sure you want to permanently delete this episode?`, () => {
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
<?php if (isset($show_extraction_ui) && $show_extraction_ui): ?>
<!-- Extraction Overlay -->
<div id="extractionOverlay" class="extraction-overlay">
    <div class="extraction-box" style="width: 600px;">
        <div class="extraction-header">
            <h3><i class="fas fa-magic"></i> AI Media Extraction</h3>
            <p>Processing your new episode for subtitles and audio tracks...</p>
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
                <h4>Episode Ready!</h4>
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
                <button onclick="window.location.href='manage_episodes.php?season_id=<?php echo $season_id; ?>&success=1'" class="btn btn-primary" style="margin-top:20px; width:100%;">Continue</button>
            </div>

            <div id="extractionControls" style="margin-top:20px;">
                <button onclick="window.location.href='manage_episodes.php?season_id=<?php echo $season_id; ?>&success=1'" class="btn btn-secondary" style="background:#444; width:100%;"><i class="fas fa-forward"></i> Skip & Continue</button>
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
        status.innerText = "Analyzing episode streams...";

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
                status.innerText = "Extraction Finished!";
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
                    <button onclick="window.location.href='manage_episodes.php?season_id=<?php echo $season_id; ?>&success=1'" class="btn btn-secondary" style="margin-top:20px; width:100%;">Skip</button>
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
<?php include 'includes/footer.php'; ?>
