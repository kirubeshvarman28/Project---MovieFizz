<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$page_title = "Genres";
include 'includes/header.php';

// Fetch genres from DB with movie counts
$genres = [];
try {
    $stmt = $pdo->query("SELECT g.name, g.slug, 
        (SELECT COUNT(*) FROM movies WHERE genre LIKE CONCAT('%', g.name, '%') AND is_published = 1) as movie_count 
        FROM genres g WHERE g.status = 'active' ORDER BY g.name ASC");
    $genres = $stmt->fetchAll();
} catch(Exception $e) {
    // Fallback to hardcoded if table doesn't exist
    $fallback = ['Action', 'Comedy', 'Drama', 'Horror', 'Sci-Fi', 'Thriller', 'Romance', 'Anime'];
    foreach ($fallback as $f) {
        $genres[] = ['name' => $f, 'slug' => strtolower($f), 'movie_count' => 0];
    }
}
?>

<main class="container">
    <h1 class="section-title">Browse by Genre</h1>
    
    <div class="genre-grid">
        <?php foreach ($genres as $genre): ?>
        <a href="movies.php?genre=<?php echo urlencode($genre['name']); ?>" class="genre-card">
            <?php echo $genre['name']; ?>
            <?php if ($genre['movie_count'] > 0): ?>
            <span class="genre-count"><?php echo $genre['movie_count']; ?> titles</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
