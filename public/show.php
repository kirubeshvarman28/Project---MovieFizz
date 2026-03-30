<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) redirect('shows.php');

$stmt = $pdo->prepare("SELECT * FROM tv_shows WHERE id = ?");
$stmt->execute([$id]);
$show = $stmt->fetch();
if (!$show) redirect('shows.php');

$page_title = $show['title'];

// Fetch seasons
$seasons = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM seasons WHERE tv_show_id = ? ORDER BY season_number ASC");
    $stmt->execute([$id]);
    $seasons = $stmt->fetchAll();
} catch(Exception $e) {}

// Fetch episodes for each season
$episodes_by_season = [];
foreach ($seasons as $s) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number ASC");
        $stmt->execute([$s['id']]);
        $episodes_by_season[$s['id']] = $stmt->fetchAll();
    } catch(Exception $e) {
        $episodes_by_season[$s['id']] = [];
    }
}

// Fetch cast
$cast = [];
try {
    $stmt = $pdo->prepare("SELECT cc.name, cc.image, tsc.role FROM tv_show_cast tsc JOIN cast_crew cc ON tsc.cast_id = cc.id WHERE tsc.tv_show_id = ? LIMIT 12");
    $stmt->execute([$id]);
    $cast = $stmt->fetchAll();
} catch(Exception $e) {}

function img_path($p) {
    if(empty($p)) return '../assets/images/no-poster.png';
    return strpos($p,'http')===0 ? $p : '../'.$p;
}

include 'includes/header.php';
?>

<!-- Cinematic Backdrop -->
<section class="detail-hero" style="background-image: url('<?php echo img_path($show['backdrop']); ?>');">
    <div class="container">
        <div class="detail-info">
            <h1><?php echo $show['title']; ?></h1>
            <div class="detail-meta">
                <span class="rating"><i class="fas fa-star"></i> <?php echo format_rating($show['rating']); ?></span>
                <?php if($show['genre']): ?><span class="tag"><?php echo $show['genre']; ?></span><?php endif; ?>
                <?php if($show['language'] ?? ''): ?><span class="tag"><?php echo $show['language']; ?></span><?php endif; ?>
                <span><?php echo count($seasons); ?> Season<?php echo count($seasons)!=1?'s':''; ?></span>
            </div>
            <p class="detail-description"><?php echo $show['description']; ?></p>
        </div>
    </div>
</section>

<main class="container">
    <!-- Season Selector & Episodes Section Header -->
    <div class="season-selector-wrapper">
        <h3>Episodes</h3>
        <?php if (!empty($seasons)): ?>
        <select class="season-select" id="seasonSelect" onchange="switchSeason(this.value)">
            <?php foreach ($seasons as $s): ?>
            <option value="<?php echo $s['id']; ?>">Season <?php echo $s['season_number']; ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>

    <!-- Episode Lists -->
    <?php if (!empty($seasons)): ?>
    <?php foreach ($seasons as $i => $s): ?>
    <div class="episode-list season-episodes" id="season-<?php echo $s['id']; ?>" style="<?php echo $i>0?'display:none':''; ?>">
        <?php if(empty($episodes_by_season[$s['id']])): ?>
            <div class="empty-state"><p>No episodes added yet.</p></div>
        <?php else: ?>
            <?php foreach ($episodes_by_season[$s['id']] as $ep): ?>
            <div class="episode-row" onclick="location.href='watch_episode.php?id=<?php echo $ep['id']; ?>'">
                <div class="ep-number"><?php echo $ep['episode_number']; ?></div>
                <div class="ep-thumb-container">
                    <img src="<?php echo img_path($show['backdrop']); ?>" alt="<?php echo $ep['title']; ?>" onerror="this.src='../assets/images/no-poster.png'">
                    <div class="ep-play-overlay"><i class="fas fa-play"></i></div>
                </div>
                <div class="ep-details">
                    <div class="ep-title-row">
                        <h4><?php echo $ep['title']; ?></h4>
                        <?php if($ep['duration']): ?><span class="ep-duration"><?php echo $ep['duration']; ?>m</span><?php endif; ?>
                    </div>
                    <?php if($ep['description']): ?>
                    <p class="ep-description"><?php echo $ep['description']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="empty-state" style="margin-top:30px;">
        <i class="fas fa-video"></i>
        <p>No seasons available yet.</p>
    </div>
    <?php endif; ?>

    <!-- Player (hidden until an episode is selected) -->
    <div id="episodePlayer" style="display:none; margin-top:30px;">
        <div class="player-container">
            <video controls id="epVideo"></video>
        </div>
    </div>

    <!-- Cast -->
    <?php if (!empty($cast)): ?>
    <div class="cast-section">
        <h3>Cast</h3>
        <div class="cast-grid">
            <?php foreach ($cast as $c): ?>
            <div class="cast-card">
                <img src="<?php echo !empty($c['image']) ? $c['image'] : '../assets/images/no-cast.png'; ?>" alt="<?php echo $c['name']; ?>" onerror="this.src='../assets/images/no-cast.png'">
                <div class="cast-name"><?php echo $c['name']; ?></div>
                <?php if($c['role']): ?><div class="cast-role"><?php echo $c['role']; ?></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
function switchSeason(seasonId) {
    document.querySelectorAll('.season-episodes').forEach(el => el.style.display = 'none');
    document.getElementById('season-' + seasonId).style.display = 'flex';
}
</script>

<?php include 'includes/footer.php'; ?>
