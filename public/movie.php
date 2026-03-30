<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) redirect('index.php');

$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ? AND is_published = 1");
$stmt->execute([$id]);
$movie = $stmt->fetch();
if (!$movie) redirect('index.php');

$page_title = $movie['title'];

// Fetch cast
$cast = [];
try {
    $stmt = $pdo->prepare("SELECT cc.name, cc.image, mc.role FROM movie_cast mc JOIN cast_crew cc ON mc.cast_id = cc.id WHERE mc.movie_id = ? LIMIT 12");
    $stmt->execute([$id]);
    $cast = $stmt->fetchAll();
} catch(Exception $e) {}

// Fetch video sources
$sources = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM movie_sources WHERE movie_id = ? ORDER BY label DESC");
    $stmt->execute([$id]);
    $sources = $stmt->fetchAll();
} catch(Exception $e) {}

// Fetch subtitles
$subtitles = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM movie_subtitles WHERE movie_id = ?");
    $stmt->execute([$id]);
    $subtitles = $stmt->fetchAll();
} catch(Exception $e) {}

// Fetch alternative audio
$audio_tracks = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM movie_audio WHERE movie_id = ?");
    $stmt->execute([$id]);
    $audio_tracks = $stmt->fetchAll();
} catch(Exception $e) {}

// Watchlist check
$in_watchlist = false;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT id FROM watchlist WHERE user_id = ? AND movie_id = ?");
    $stmt->execute([$_SESSION['user_id'], $id]);
    $in_watchlist = $stmt->fetch() ? true : false;
}

// Helper
function img_path($p) {
    if(empty($p)) return '../assets/images/no-poster.png';
    return strpos($p,'http')===0 ? $p : '../'.$p;
}

// Primary video
$video_url = $movie['video_url'];

// Fallback to first source if primary is empty
if (empty($video_url) && !empty($sources)) {
    $video_url = $sources[0]['source_url'];
}

if (strpos($video_url, 'http') !== 0 && !empty($video_url) && strpos($video_url, '<iframe') === false) {
    $video_url = '../' . $video_url;
}

include 'includes/header.php';
?>

<!-- Cinematic Backdrop -->
<section class="detail-hero" style="background-image: url('<?php echo img_path($movie['backdrop']); ?>');">
    <div class="container">
        <div class="detail-info">
            <h1><?php echo $movie['title']; ?></h1>
            <div class="detail-meta">
                <span class="rating"><i class="fas fa-star"></i> <?php echo format_rating($movie['rating']); ?></span>
                <span><?php echo $movie['release_year']; ?></span>
                <?php if($movie['runtime']): ?><span><?php echo $movie['runtime']; ?> min</span><?php endif; ?>
                <span class="tag"><?php echo $movie['genre']; ?></span>
                <?php if($movie['language'] ?? ''): ?><span class="tag"><?php echo $movie['language']; ?></span><?php endif; ?>
            </div>
            <p class="detail-description"><?php echo $movie['description']; ?></p>
            <div class="detail-actions">
                <button class="btn-play" onclick="document.getElementById('playerSection').scrollIntoView({behavior:'smooth'})"><i class="fas fa-play"></i> Play</button>
                <?php if (is_logged_in()): ?>
                <button id="watchlist_btn" data-id="<?php echo $id; ?>" class="btn-more-info">
                    <?php echo $in_watchlist ? '<i class="fas fa-check"></i> In My List' : '<i class="fas fa-plus"></i> My List'; ?>
                </button>
                <?php else: ?>
                <a href="login.php" class="btn-more-info"><i class="fas fa-plus"></i> My List</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<main class="container">
    <!-- Player -->
    <div class="player-section expanded-player" id="playerSection">
        <?php 
        $title_main = $movie['title'];
        $poster_url = img_path($movie['backdrop']);
        
        // --- COMPREHENSIVE DIRECT PLAYBACK DETECTION ---
        $is_direct_embed = false;
        $direct_url = '';

        // Condition 1: Cloud-Imported (TMDB ID present + Cloud Source)
        $has_cloud_source = false;
        foreach ($sources as $src) {
            if ($src['source_type'] === 'cloud') {
                $has_cloud_source = true;
                $direct_url = ($src['source_url'] === 'cloud') ? get_cloud_player_url($movie['tmdb_id'], 'movie') : $src['source_url'];
                break;
            }
        }

        if ($has_cloud_source && !empty($movie['tmdb_id'])) {
            $is_direct_embed = true;
        } 
        // Condition 2: Manual Embed Code or direct Vidrock link in video_url
        elseif (strpos($video_url, '<iframe') !== false || strpos($video_url, 'vidrock.net') !== false || strpos($video_url, 'vidsrc.icu') !== false) {
            $is_direct_embed = true;
            if (strpos($video_url, '<iframe') !== false) {
                // Extract src if it's a full iframe tag
                preg_match('/src="([^"]+)"/', $video_url, $match);
                $direct_url = $match[1] ?? $video_url;
            } else {
                $direct_url = $video_url;
            }
        } // End of embed detection logic
        ?>

        <?php if(!empty($settings['ad_player'])): ?>
        <div class="player-ad-container" style="margin-bottom: 20px; text-align: center;">
            <?php echo $settings['ad_player']; ?>
        </div>
        <?php endif; ?>

        <?php
        if ($is_direct_embed):
            echo '<div class="player-container"><iframe src="' . $direct_url . '" allowfullscreen allow="autoplay; encrypted-media"></iframe></div>';
        else:
            include 'includes/player_component.php';
        endif;
        ?>

        <!-- Multiple Sources -->
        <?php if (!empty($sources)): ?>
        <div class="source-selector">
            <?php foreach ($sources as $i => $src): 
                $s_url = $src['source_url'];
                if ($src['source_type'] === 'cloud' && $s_url === 'cloud') {
                    $s_url = get_cloud_player_url($movie['tmdb_id'], 'movie');
                }
                if (strpos($s_url, 'http') !== 0 && strpos($s_url, '<iframe') === false) {
                    $s_url = '../' . $s_url;
                }
            ?>
            <button class="source-btn <?php echo $i===0?'active':''; ?>" 
                    data-url="<?php echo $s_url; ?>" 
                    data-type="<?php echo $src['source_type']; ?>">
                <?php echo $src['label'] ?? 'Source'; ?> 
                <?php if(isset($src['quality']) && $src['quality']): ?><span class="badge-quality"><?php echo $src['quality']; ?></span><?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <!-- Trailer -->
    <?php if ($movie['trailer_url']): ?>
    <div style="margin-top:40px;">
        <h3 style="margin-bottom:16px;">Official Trailer</h3>
        <div class="player-container">
            <?php 
            $trailer_id = strpos($movie['trailer_url'], 'v=') !== false ? explode('v=', $movie['trailer_url'])[1] : basename($movie['trailer_url']);
            if (strpos($trailer_id, '&') !== false) $trailer_id = explode('&', $trailer_id)[0];
            ?>
            <iframe src="https://www.youtube.com/embed/<?php echo $trailer_id; ?>" allowfullscreen></iframe>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cast -->
    <?php if (!empty($cast)): ?>
    <div class="cast-section">
        <h3>Cast & Crew</h3>
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

<?php include 'includes/footer.php'; ?>
