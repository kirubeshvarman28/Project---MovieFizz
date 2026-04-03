<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) redirect('index.php');

// Fetch Episode, Season, and Show details in one (or two) goes
try {
    $stmt = $pdo->prepare("
        SELECT e.*, s.season_number, s.tv_show_id, t.title as show_title, t.backdrop as show_backdrop, t.tmdb_id as show_tmdb_id
        FROM episodes e
        JOIN seasons s ON e.season_id = s.id
        JOIN tv_shows t ON s.tv_show_id = t.id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $episode = $stmt->fetch();
} catch (Exception $e) { $episode = null; }

if (!$episode) redirect('index.php');

$page_title = "Watching: " . $episode['show_title'] . " S" . $episode['season_number'] . " E" . $episode['episode_number'];

// Fetch Next Episode
$next_episode = null;
try {
    // Look for next episode in same season
    $stmt = $pdo->prepare("SELECT id FROM episodes WHERE season_id = ? AND episode_number > ? ORDER BY episode_number ASC LIMIT 1");
    $stmt->execute([$episode['season_id'], $episode['episode_number']]);
    $next_episode = $stmt->fetch();
    
    if (!$next_episode) {
        // Look for first episode of next season
        $stmt = $pdo->prepare("SELECT e.id FROM episodes e JOIN seasons s ON e.season_id = s.id WHERE s.tv_show_id = ? AND s.season_number > ? ORDER BY s.season_number ASC, e.episode_number ASC LIMIT 1");
        $stmt->execute([$episode['tv_show_id'], $episode['season_number']]);
        $next_episode = $stmt->fetch();
    }
    
    $next_episode_url = $next_episode ? "watch_episode.php?id=" . $next_episode['id'] : null;
} catch (Exception $e) {}

// Fetch subtitles
$subtitles = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM episode_subtitles WHERE episode_id = ?");
    $stmt->execute([$id]);
    $subtitles = $stmt->fetchAll();
} catch(Exception $e) {}

// Fetch alternative audio
$audio_tracks = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM episode_audio WHERE episode_id = ?");
    $stmt->execute([$id]);
    $audio_tracks = $stmt->fetchAll();
} catch(Exception $e) {}

function img_path($p) {
    if(empty($p)) return 'assets/images/no-poster.png';
    return strpos($p,'http')===0 ? $p : '../'.$p;
}

include INCLUDES_PATH . '/header.php';
?>

<main class="container player-page">
    <div class="player-header">
        <a href="show.php?id=<?php echo $episode['tv_show_id']; ?>" class="back-link">
            <i class="fas fa-chevron-left"></i> Back to Show
        </a>
        <div class="player-title-info">
            <h1><?php echo $episode['show_title']; ?></h1>
            <p>Season <?php echo $episode['season_number']; ?> • Episode <?php echo $episode['episode_number']; ?>: <?php echo $episode['title']; ?></p>
        </div>
    </div>

    <div class="main-player-wrapper expanded-player">
        <?php 
        // Set variables for player component
        $title_main = $episode['show_title'];
        $title_sub = $episode['title'];
        $meta_info = "S" . $episode['season_number'];
        $poster_url = img_path($episode['show_backdrop']);
        $main_audio_label = $episode['main_audio_label'] ?? 'Original Audio';
        
        $video_url = $episode['video_url'];
        
        // Fetch all sources for this episode
        $stmt_src = $pdo->prepare("SELECT * FROM episode_sources WHERE episode_id = ? ORDER BY id ASC");
        $stmt_src->execute([$id]);
        $sources = $stmt_src->fetchAll();

        // --- COMPREHENSIVE DIRECT PLAYBACK DETECTION ---
        $is_direct_embed = false;
        $direct_url = '';

        // Condition 1: Cloud-Imported (Explicit 'cloud' value or source type)
        $has_cloud_source = false;
        foreach ($sources as $src) {
            if ($src['source_type'] === 'cloud') {
                $has_cloud_source = true;
                $direct_url = ($src['source_url'] === 'cloud') ? get_cloud_player_url($episode['show_tmdb_id'], 'tv', $episode['season_number'], $episode['episode_number']) : $src['source_url'];
                break;
            }
        }

        if (($has_cloud_source || $video_url === 'cloud') && !empty($episode['show_tmdb_id'])) {
            $is_direct_embed = true;
            if (empty($direct_url)) {
                $direct_url = get_cloud_player_url($episode['show_tmdb_id'], 'tv', $episode['season_number'], $episode['episode_number']);
            }
        } 
        // Condition 2: Manual Embed Code or direct Vidrock link
        elseif (strpos($video_url, '<iframe') !== false || strpos($video_url, 'vidrock.net') !== false || strpos($video_url, 'vidsrc.icu') !== false) {
            $is_direct_embed = true;
            if (strpos($video_url, '<iframe') !== false) {
                preg_match('/src="([^"]+)"/', $video_url, $match);
                $direct_url = $match[1] ?? $video_url;
            } else {
                $direct_url = $video_url;
            }
        }
        ?>

        <?php if(!empty($settings['ad_player'])): ?>
        <div class="player-ad-container" style="margin-bottom: 20px; text-align: center;">
            <?php echo $settings['ad_player']; ?>
        </div>
        <?php endif; ?>

        <?php if (is_logged_in()): ?>
            <?php
            if ($is_direct_embed):
                echo '<div class="player-container"><iframe src="' . $direct_url . '" allowfullscreen allow="autoplay; encrypted-media"></iframe></div>';
            else:
                // Manual fallback logic
                if (empty($video_url) && !empty($sources)) {
                    $video_url = $sources[0]['source_url'];
                }
                if (strpos($video_url, 'http') !== 0 && !empty($video_url) && strpos($video_url, '<iframe') === false) {
                    $video_url = '../' . $video_url;
                }
                include 'includes/player_component.php';
            endif;
            ?>

            <!-- Multiple Sources -->
            <?php 
            // DYNAMIC CLOUD SOURCES (Ensures options show even for older imports)
            $cloud_sources = [];
            if (!empty($episode['show_tmdb_id'])) {
                if ($settings['player_vidrock'] ?? 1) $cloud_sources[] = ['label' => 'VidRock', 'url' => "https://vidrock.net/tv/{$episode['show_tmdb_id']}/{$episode['season_number']}/{$episode['episode_number']}", 'type' => 'cloud'];
                if ($settings['player_superembed'] ?? 1) $cloud_sources[] = ['label' => 'SuperEmbed', 'url' => "https://vidsrc.cc/v2/embed/tv/{$episode['show_tmdb_id']}/{$episode['season_number']}/{$episode['episode_number']}", 'type' => 'cloud'];
                if ($settings['player_vidlink'] ?? 1) $cloud_sources[] = ['label' => 'VidLink', 'url' => "https://vidlink.pro/tv/{$episode['show_tmdb_id']}/{$episode['season_number']}/{$episode['episode_number']}", 'type' => 'cloud'];
            }

            // Combine with manual sources
            $final_sources = [];
            foreach ($cloud_sources as $cs) {
                $final_sources[$cs['label']] = $cs;
            }
            if (!empty($sources)) {
                foreach ($sources as $s) {
                    if ($s['label'] === 'Cloud Server' && !empty($cloud_sources)) continue;
                    if (isset($final_sources[$s['label']])) continue;
                    $final_sources[$s['label']] = [
                        'label' => $s['label'],
                        'url' => (strpos($s['source_url'], 'http') !== 0 && strpos($s['source_url'], '<iframe') === false) ? '../' . $s['source_url'] : $s['source_url'],
                        'type' => $s['source_type']
                    ];
                }
            }
            ?>

            <?php if (!empty($final_sources)): ?>
            <div class="source-selector" style="padding: 20px; background: #141414; border-top: 1px solid #333;">
                <?php $idx = 0; foreach ($final_sources as $src): ?>
                <button class="source-btn <?php echo ($idx === 0) ? 'active' : ''; ?>" 
                        data-url="<?php echo $src['url']; ?>" 
                        data-type="<?php echo $src['type']; ?>">
                    <?php echo $src['label']; ?>
                </button>
                <?php $idx++; endforeach; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="login-to-watch">
                <div class="lock-icon"><i class="fas fa-lock"></i></div>
                <h3>Login to Watch</h3>
                <p>Only registered users can watch movies and TV shows on <?php echo SITE_NAME; ?>.</p>
                <div class="auth-buttons">
                    <a href="login.php" class="btn-play">Login</a>
                    <a href="register.php" class="btn-more-info">Register</a>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <div class="player-footer">
        <div class="episode-info-box">
            <h3><?php echo $episode['title']; ?></h3>
            <p><?php echo $episode['description']; ?></p>
        </div>
        
        <?php if ($next_episode): ?>
        <div class="next-up">
            <a href="watch_episode.php?id=<?php echo $next_episode['id']; ?>" class="btn-next">
                Next Episode <i class="fas fa-step-forward"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
</main>

<style>
.player-page {
    padding-top: 100px;
    max-width: 100%;
}
.player-header {
    margin-bottom: 20px;
}
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 16px;
    transition: color 0.3s;
}
.back-link:hover {
    color: var(--primary);
}
.player-title-info h1 {
    font-size: 24px;
    margin-bottom: 4px;
}
.player-title-info p {
    color: var(--text-muted);
    font-size: 15px;
}
.main-player-wrapper {
    background: #000;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    margin-bottom: 30px;
}
.player-container {
    aspect-ratio: 16/9;
    width: 100%;
}
.player-container iframe, .player-container video {
    width: 100%;
    height: 100%;
    border: none;
}
.player-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 40px;
    padding-bottom: 60px;
}
.episode-info-box {
    flex: 1;
}
.episode-info-box h3 {
    font-size: 20px;
    margin-bottom: 12px;
}
.episode-info-box p {
    color: var(--text-secondary);
    line-height: 1.6;
}
.btn-next {
    background: var(--primary);
    color: #fff;
    padding: 12px 24px;
    border-radius: 4px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: background 0.3s;
}
.btn-next:hover {
    background: var(--primary-dark);
}
@media (max-width: 768px) {
    .player-footer {
        flex-direction: column;
    }
}
</style>

<?php include INCLUDES_PATH . '/footer.php'; ?>
