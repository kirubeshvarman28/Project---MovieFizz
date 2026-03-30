<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in()) redirect('login.php');

$page_title = "My List";
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT m.* FROM movies m JOIN watchlist w ON m.id = w.movie_id WHERE w.user_id = ?");
$stmt->execute([$user_id]);
$watchlist = $stmt->fetchAll();

function img_path($p) {
    if(empty($p)) return get_placeholder();
    return strpos($p,'http')===0 ? $p : '../'.$p;
}
?>

<main class="container" style="padding-top: calc(var(--header-height) + 30px);">
    <h1 class="section-title">My List</h1>
    
    <?php if (!empty($watchlist)): ?>
    <div class="movie-grid">
        <?php foreach ($watchlist as $movie): ?>
        <a href="movie.php?id=<?php echo $movie['id']; ?>" class="movie-card">
            <img src="<?php echo img_path($movie['poster']); ?>" onerror="this.src='<?php echo get_placeholder(); ?>'" class="movie-poster" alt="<?php echo $movie['title']; ?>">
            <div class="movie-info">
                <h3><?php echo $movie['title']; ?></h3>
                <div class="meta">
                    <span><?php echo $movie['release_year']; ?></span>
                    <span><i class="fas fa-star" style="color:#f5c518;"></i> <?php echo format_rating($movie['rating']); ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-bookmark"></i>
        <p>Your list is empty. Start adding movies you want to watch!</p>
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
