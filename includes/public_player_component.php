<?php 
// Resolve Terabox or cloud links at runtime
$resolution = resolve_media_url($video_url);
$video_url = $resolution['stream'] ?? $video_url;
$download_url = $resolution['download'] ?? $video_url;

// Enhanced Terabox Proxy Logic (Worker-based with local fallback)
if (is_terabox_url($video_url) || strpos($video_url, 'd.terabox.app') !== false || strpos($video_url, 'terabox.com') !== false) {
    // If it's already proxied by local, extract the real URL to try worker proxy first
    $real_url = $video_url;
    if (strpos($video_url, 'proxy_terabox.php?url=') !== false) {
        $real_url = urldecode(explode('url=', $video_url)[1]);
    }
    
    // Cloudflare Worker Proxy (Primary)
    $worker_proxy = "https://terabox.hnn.workers.dev/?url=" . base64_encode($real_url);
    // Local Proxy (Fallback)
    $local_proxy = "includes/proxy_terabox.php?url=" . urlencode($real_url);
    
    // Set the source to worker first, frontend JS can handle fallback if needed, 
    // but for now we set the most robust one.
    $video_url = $worker_proxy; 
}

// Initial source type detection
$is_iframe = (strpos($video_url, '<iframe') !== false || strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false || (isset($source_type) && $source_type === 'cloud') || $video_url === 'cloud');

if ($is_iframe) {
    if ($video_url === 'cloud' || (isset($source_type) && $source_type === 'cloud')) {
        // Resolve Cloud URL
        $tmdb_id_player = $tmdb_id ?? ($movie['tmdb_id'] ?? ($episode['show_tmdb_id'] ?? ($anime['anilist_id'] ?? null)));
        $media_type_player = $media_type ?? (isset($movie) ? 'movie' : (isset($anime) ? 'anime' : 'tv'));
        $s_num = $season_number ?? ($episode['season_number'] ?? 1);
        $e_num = $episode_number ?? ($episode['episode_number'] ?? 1);
        $dub_val = $_GET['dub'] ?? 0;
        
        $video_url = get_cloud_player_url($tmdb_id_player, $media_type_player, $s_num, $e_num, $dub_val);
    } elseif (strpos($video_url, 'youtube.com/watch?v=') !== false) {
        $vid = explode('v=', $video_url)[1];
        if (strpos($vid, '&') !== false) $vid = explode('&', $vid)[0];
        $video_url = "https://www.youtube.com/embed/" . $vid;
    } elseif (strpos($video_url, 'youtu.be/') !== false) {
        $vid = basename(parse_url($video_url, PHP_URL_PATH));
        $video_url = "https://www.youtube.com/embed/" . $vid;
    } elseif (strpos($video_url, '<iframe') !== false) {
        preg_match('/src="([^"]+)"/', $video_url, $match);
        $video_url = $match[1] ?? $video_url;
    }
}
?>

<!-- Netflix-Style Custom Player Component -->
<div class="netflix-player <?php echo $is_iframe ? 'is-iframe' : ''; ?>" id="netflixPlayer">
    <div id="mediaContainer" style="width:100%; height:100%;">
        <?php if ($is_iframe): ?>
            <iframe src="<?php echo $video_url; ?>" style="width:100%; height:100%; border:none;" allowfullscreen allow="autoplay; encrypted-media"></iframe>
        <?php else: ?>
            <video id="mainVideo" src="<?php echo $video_url; ?>" data-main-audio-label="<?php echo htmlspecialchars($main_audio_label ?? 'Original Audio'); ?>" poster="<?php echo $poster_url ?? ''; ?>" preload="metadata" style="width:100%; height:100%; object-fit:contain;">
                <?php if (isset($subtitles) && is_array($subtitles)): ?>
                    <?php foreach($subtitles as $sub): ?>
                        <!-- Ensure path is correct for public facing pages -->
                        <track kind="subtitles" src="<?php echo (strpos($sub['file_url'], 'http') === 0) ? $sub['file_url'] : '../' . $sub['file_url']; ?>" srclang="<?php echo strtolower(substr($sub['language'], 0, 2)); ?>" label="<?php echo htmlspecialchars($sub['label'] ?? $sub['language']); ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
                Your browser does not support the video tag.
            </video>
            <!-- Hidden Audio Tracks (Alternative Languages) -->
            <?php if (isset($audio_tracks) && is_array($audio_tracks)): ?>
                <div id="altAudioContainer" style="display:none;">
                    <?php foreach($audio_tracks as $i => $aud): ?>
                        <audio class="alt-audio-track" data-id="<?php echo $i; ?>" data-label="<?php echo htmlspecialchars($aud['label']); ?>" src="<?php echo (strpos($aud['file_url'], 'http') === 0) ? $aud['file_url'] : '../' . $aud['file_url']; ?>" preload="metadata"></audio>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Top Exit Button -->
    <button class="player-exit-btn" title="Exit Player">
        <i class="fas fa-arrow-left"></i>
    </button>

    <!-- Controls Overlay -->
    <div class="player-controls">
        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-bar-bg">
                <div class="progress-current"></div>
            </div>
        </div>

        <div class="controls-bottom">
            <div class="controls-left">
                <button class="play-btn"><i class="fas fa-play"></i></button>
                
                <?php if (isset($next_episode_url) && !empty($next_episode_url)): ?>
                    <button class="next-ep-btn" title="Next Episode" onclick="window.location.href='<?php echo $next_episode_url; ?>'">
                        <i class="fas fa-step-forward"></i>
                        <span style="font-size: 12px; margin-left: 5px; font-weight: bold;">NEXT</span>
                    </button>
                <?php endif; ?>
                
                <div class="volume-container">
                    <button class="volume-btn"><i class="fas fa-volume-up"></i></button>
                    <div class="volume-slider">
                        <input type="range" min="0" max="1" step="0.1" value="1">
                    </div>
                </div>

                <div class="time-display">0:00 / 0:00</div>
            </div>

            <!-- Media Info -->
            <div class="player-info-center">
                <?php if (!empty($meta_info)): ?>
                    <span class="info-season-ep"><?php echo $meta_info; ?></span>
                <?php endif; ?>
                <span class="info-show-title"><?php echo $title_main; ?></span>
                <?php if (!empty($title_sub)): ?>
                    <span class="info-ep-title">— <?php echo $title_sub; ?></span>
                <?php endif; ?>
            </div>

            <div class="controls-right">
                <!-- Single Settings Icon as requested -->
                <?php if (!$is_iframe): ?>
                    <a href="<?php echo $download_url; ?>" class="player-control-btn download-btn" title="Download" download style="text-decoration:none; color:inherit; margin-right:15px; font-size:18px;"><i class="fas fa-download"></i></a>
                <?php endif; ?>
                <button class="settings-btn" title="Settings"><i class="fas fa-comments"></i></button>
                <button class="full-screen-btn"><i class="fas fa-expand"></i></button>
            </div>
        </div>

        <!-- Settings Modal (Resolution, Audio, Subtitles) -->
        <div class="audio-subs-menu">
            <div class="menu-header">
                <div class="menu-tab active" data-tab="res" title="Resolution"><i class="fas fa-signal"></i></div>
                <div class="menu-tab" data-tab="audio" title="Audio / Language"><i class="fas fa-headphones"></i></div>
                <div class="menu-tab" data-tab="subs" title="Subtitles"><i class="fas fa-align-center"></i></div>
            </div>
            <div class="menu-content">
                <div class="menu-list" id="menuResList">
                    <div class="menu-item active" data-quality="Auto">Auto</div>
                </div>
                <div class="menu-list" id="menuAudioList" style="display:none;">
                    <div class="menu-item active">Default Audio</div>
                </div>
                <div class="menu-list" id="menuSubsList" style="display:none;">
                    <div class="menu-item active">Off</div>
                </div>
            </div>
        </div>
    </div>
</div>
