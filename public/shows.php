<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$page_title = "TV Shows";
include 'includes/header.php';

$shows = [];
try {
    $stmt = $pdo->query("SELECT * FROM tv_shows ORDER BY created_at DESC");
    $shows = $stmt->fetchAll();
} catch(Exception $e) {}

function img_path($p) {
    if(empty($p)) return get_placeholder();
    return strpos($p,'http')===0 ? $p : '../'.$p;
}
?>

<main class="container">
    <h1 class="section-title">TV Shows</h1>

    <?php if (!empty($shows)): ?>
    <div class="movie-grid">
        <?php foreach ($shows as $show): ?>
        <a href="show.php?id=<?php echo $show['id']; ?>" class="movie-card">
            <img src="<?php echo img_path($show['poster']); ?>" onerror="this.src='<?php echo get_placeholder(); ?>'" class="movie-poster" alt="<?php echo $show['title']; ?>">
            <div class="movie-info">
                <h3><?php echo $show['title']; ?></h3>
                <div class="meta">
                    <span><?php echo $show['genre'] ?? ''; ?></span>
                    <span><i class="fas fa-star" style="color:#f5c518;"></i> <?php echo format_rating($show['rating']); ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-tv"></i>
        <p>No TV shows available yet.</p>
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
