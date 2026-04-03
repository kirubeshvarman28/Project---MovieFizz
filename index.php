<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

$page_title = "Home";
include INCLUDES_PATH . '/header.php';

// Fetch Latest 10 Items for Hero Slider (Movies and TV Shows combined)
$hero_items = [];
try {
    $stmt = $pdo->query("
        (SELECT id, title, description, backdrop, rating, release_year, 'movie' as type, created_at FROM movies WHERE is_published = 1)
        UNION ALL
        (SELECT id, title, description, backdrop, rating, 'TV Show' as release_year, 'show' as type, created_at FROM tv_shows)
        ORDER BY created_at DESC LIMIT 10
    ");
    $hero_items = $stmt->fetchAll();
}
catch (Exception $e) {
}

// Fetch Latest Movies
$latest_movies = [];
try {
    $stmt = $pdo->query("SELECT * FROM movies WHERE is_published = 1 ORDER BY created_at DESC LIMIT 20");
    $latest_movies = $stmt->fetchAll();
}
catch (Exception $e) {
}

// Fetch TV Shows
$tv_shows = [];
try {
    $stmt = $pdo->query("SELECT * FROM tv_shows ORDER BY created_at DESC LIMIT 20");
    $tv_shows = $stmt->fetchAll();
}
catch (Exception $e) {
}

// Fetch genre-based rows dynamically
$genre_rows = [];
try {
    $stmt = $pdo->query("SELECT name FROM genres WHERE status='active' ORDER BY RAND() LIMIT 3");
    $genres_list = $stmt->fetchAll();
    foreach ($genres_list as $g) {
        $stmt2 = $pdo->prepare("SELECT * FROM movies WHERE genre LIKE ? AND is_published = 1 LIMIT 12");
        $stmt2->execute(['%' . $g['name'] . '%']);
        $genre_movies = $stmt2->fetchAll();
        if (count($genre_movies) > 0) {
            $genre_rows[] = ['name' => $g['name'], 'movies' => $genre_movies];
        }
    }
}
catch (Exception $e) {
}

// Helper to resolve image path
function img_path($path)
{
    if (empty($path))
        return get_placeholder();
    return (strpos($path, 'http') === 0) ? $path : $path;
}
?>

<!-- Hero Slider -->
<section class="hero-slider-container">
    <div class="hero-slider">
        <?php if (!empty($hero_items)): ?>
            <?php foreach ($hero_items as $index => $item): ?>
                <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                     style="background-image: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(20,20,20,1)), url('<?php echo img_path($item['backdrop']); ?>');">
                    <div class="container">
                        <div class="hero-content">
                            <span class="badge"><i class="fas fa-fire"></i> New Release</span>
                            <h1><?php echo $item['title']; ?></h1>
                            <div class="hero-meta">
                                <span class="rating"><i class="fas fa-star"></i> <?php echo format_rating($item['rating']); ?></span>
                                <span><?php echo $item['release_year']; ?></span>
                                <span class="badge-type"><?php echo ucfirst($item['type']); ?></span>
                            </div>
                            <p><?php echo substr($item['description'], 0, 180) . (strlen($item['description']) > 180 ? '...' : ''); ?></p>
                            <div class="hero-btns">
                                <a href="<?php echo $item['type']; ?>.php?id=<?php echo $item['id']; ?>" class="btn-play">
                                    <i class="fas fa-play"></i> Play Now
                                </a>
                                <a href="<?php echo $item['type']; ?>.php?id=<?php echo $item['id']; ?>" class="btn-more-info">
                                    <i class="fas fa-info-circle"></i> More Info
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
    endforeach; ?>
        <?php
else: ?>
            <!-- Fallback Hero for empty database -->
            <div class="hero-slide active" style="background: linear-gradient(135deg, #141414 0%, #e50914 300%); height: 100vh;">
                <div class="container">
                    <div class="hero-content">
                        <h1>Unlimited movies, TV <br>shows, and more.</h1>
                        <p>Watch anywhere. Cancel at any time. Populate your library via admin panel.</p>
                        <div class="hero-btns">
                            <a href="movies.php" class="btn-play"><i class="fas fa-play"></i> Browse Movies</a>
                            <a href="shows.php" class="btn-more-info"><i class="fas fa-tv"></i> TV Shows</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php
endif; ?>
    </div>
    
    <?php if (count($hero_items) > 1): ?>
        <div class="hero-indicators">
            <?php foreach ($hero_items as $index => $item): ?>
                <button class="indicator <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></button>
            <?php
    endforeach; ?>
        </div>
    <?php
endif; ?>
</section>

<main class="container">
    <!-- Latest Movies Carousel -->
    <?php if (!empty($latest_movies)): ?>
    <div class="content-row">
        <div class="row-header">
            <h2>Latest Movies</h2>
            <a href="movies.php" class="see-all">See All <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="carousel-wrapper">
            <button class="carousel-btn left"><i class="fas fa-chevron-left"></i></button>
            <div class="carousel-track">
                <?php foreach ($latest_movies as $movie): ?>
                <a href="movie.php?id=<?php echo $movie['id']; ?>" class="card">
                    <img src="<?php echo img_path($movie['poster']); ?>" onerror="this.src='<?php echo get_placeholder(); ?>'" class="card-poster" alt="<?php echo $movie['title']; ?>">
                    <div class="card-overlay">
                        <div class="play-icon"><i class="fas fa-play"></i></div>
                    </div>
                    <div class="card-body">
                        <h3><?php echo $movie['title']; ?></h3>
                        <div class="card-meta">
                            <span><?php echo $movie['release_year']; ?></span>
                            <span class="rating"><i class="fas fa-star"></i> <?php echo format_rating($movie['rating']); ?></span>
                        </div>
                    </div>
                </a>
                <?php
    endforeach; ?>
            </div>
            <button class="carousel-btn right"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
    <?php
endif; ?>

    <!-- TV Shows Carousel -->
    <?php if (!empty($tv_shows)): ?>
    <div class="content-row">
        <div class="row-header">
            <h2>Popular TV Shows</h2>
            <a href="shows.php" class="see-all">See All <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="carousel-wrapper">
            <button class="carousel-btn left"><i class="fas fa-chevron-left"></i></button>
            <div class="carousel-track">
                <?php foreach ($tv_shows as $show): ?>
                <a href="show.php?id=<?php echo $show['id']; ?>" class="card">
                    <img src="<?php echo img_path($show['poster']); ?>" onerror="this.src='<?php echo get_placeholder(); ?>'" class="card-poster" alt="<?php echo $show['title']; ?>">
                    <div class="card-overlay">
                        <div class="play-icon"><i class="fas fa-play"></i></div>
                    </div>
                    <div class="card-body">
                        <h3><?php echo $show['title']; ?></h3>
                        <div class="card-meta">
                            <span><?php echo $show['genre']; ?></span>
                            <span class="rating"><i class="fas fa-star"></i> <?php echo format_rating($show['rating']); ?></span>
                        </div>
                    </div>
                </a>
                <?php
    endforeach; ?>
            </div>
            <button class="carousel-btn right"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
    <?php
endif; ?>

    <!-- Dynamic Genre Rows -->
    <?php foreach ($genre_rows as $row): ?>
    <div class="content-row">
        <div class="row-header">
            <h2><?php echo $row['name']; ?></h2>
            <a href="movies.php?genre=<?php echo urlencode($row['name']); ?>" class="see-all">See All <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="carousel-wrapper">
            <button class="carousel-btn left"><i class="fas fa-chevron-left"></i></button>
            <div class="carousel-track">
                <?php foreach ($row['movies'] as $movie): ?>
                <a href="movie.php?id=<?php echo $movie['id']; ?>" class="card">
                    <img src="<?php echo img_path($movie['poster']); ?>" onerror="this.src='<?php echo get_placeholder(); ?>'" class="card-poster" alt="<?php echo $movie['title']; ?>">
                    <div class="card-overlay">
                        <div class="play-icon"><i class="fas fa-play"></i></div>
                    </div>
                    <div class="card-body">
                        <h3><?php echo $movie['title']; ?></h3>
                        <div class="card-meta">
                            <span><?php echo $movie['release_year']; ?></span>
                        </div>
                    </div>
                </a>
                <?php
    endforeach; ?>
            </div>
            <button class="carousel-btn right"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
    <?php
endforeach; ?>
</main>

<?php include INCLUDES_PATH . '/footer.php'; ?>
