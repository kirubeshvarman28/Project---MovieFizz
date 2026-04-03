<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

$genre = $_GET['genre'] ?? '';
$page_title = $genre ? $genre . " Movies" : "All Movies";
include INCLUDES_PATH . '/header.php';

$sql = "SELECT * FROM movies WHERE is_published = 1";
$params = [];

if ($genre) {
    $sql .= " AND genre LIKE ?";
    $params[] = "%$genre%";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll();

function img_path($p) {
    if(empty($p)) return get_placeholder();
    return strpos($p,'http')===0 ? $p : '../'.$p;
}
?>

<main class="container">
    <h1 class="section-title"><?php echo $page_title; ?></h1>
    
    <?php if ($genre): ?>
    <p style="color:var(--text-muted); margin-bottom:20px;">Showing movies in the <strong style="color:#fff;"><?php echo $genre; ?></strong> genre.</p>
    <?php endif; ?>

    <?php if (!empty($movies)): ?>
    <div class="movie-grid">
        <?php foreach ($movies as $movie): ?>
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
        <i class="fas fa-film"></i>
        <p>No movies found<?php echo $genre ? " in $genre" : ''; ?>.</p>
    </div>
    <?php endif; ?>
</main>

<?php include INCLUDES_PATH . '/footer.php'; ?>
