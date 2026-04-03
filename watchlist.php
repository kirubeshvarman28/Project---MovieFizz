<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

if (!is_logged_in()) redirect('login.php');

$page_title = "My List";
include INCLUDES_PATH . '/header.php';

$user_id = $_SESSION['user_id'];
$watchlist = [];

// Unified query for standardized schema
try {
    $stmt = $pdo->prepare("
        (SELECT m.id, m.title, m.poster, m.release_year, m.rating, 'movie' as type, w.added_at 
         FROM movies m JOIN watchlist w ON m.id = w.media_id 
         WHERE w.user_id = ? AND w.media_type = 'movie')
        UNION ALL
        (SELECT s.id, s.title, s.poster, s.genre as release_year, s.rating, 'show' as type, w.added_at 
         FROM tv_shows s JOIN watchlist w ON s.id = w.media_id 
         WHERE w.user_id = ? AND w.media_type = 'show')
        ORDER BY added_at DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $watchlist = $stmt->fetchAll();
} catch (Exception $e) {
    // If this fails, the user likely hasn't run the latest SQL fix
    $error_message = "Please run the database_final_fix.sql to update your watchlist system.";
}

function img_path($p) {
    if(empty($p)) return get_placeholder();
    return strpos($p,'http')===0 ? $p : '../'.$p;
}
?>

<main class="container" style="padding-top: calc(var(--header-height) + 30px);">
    <h1 class="section-title">My List</h1>
    
    <?php if (!empty($watchlist)): ?>
    <div class="movie-grid">
        <?php foreach ($watchlist as $item): ?>
        <a href="<?php echo $item['type'] === 'show' ? 'show.php' : 'movie.php'; ?>?id=<?php echo $item['id']; ?>" class="movie-card">
            <img src="<?php echo img_path($item['poster']); ?>" onerror="this.src='<?php echo get_placeholder(); ?>'" class="movie-poster" alt="<?php echo $item['title']; ?>">
            <div class="movie-info">
                <h3><?php echo $item['title']; ?></h3>
                <div class="meta">
                    <span><?php echo $item['release_year']; ?></span>
                    <span><i class="fas fa-star" style="color:#f5c518;"></i> <?php echo format_rating($item['rating']); ?></span>
                    <?php if($item['type'] === 'show'): ?><span class="badge" style="background: var(--primary); padding: 2px 6px; border-radius: 4px; font-size: 10px;">TV</span><?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-bookmark"></i>
        <p>Your list is empty. Start adding movies and TV shows you want to watch!</p>
    </div>
    <?php endif; ?>
</main>

<?php include INCLUDES_PATH . '/footer.php'; ?>
