<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!is_admin()) {
    die("Access denied. Admin login required.");
}

$settings = get_all_settings();
$updated_movies = 0;
$updated_episodes = 0;

echo "<h1>Auto-Repair Cloud Sources</h1>";
echo "<p>Starting the repair process... This will add VidRock, SuperEmbed, and VidLink to all existing TMDB-linked items.</p>";

// 1. Repair Movies
try {
    $movies = $pdo->query("SELECT id, tmdb_id, title FROM movies WHERE tmdb_id IS NOT NULL AND tmdb_id != ''")->fetchAll();
    foreach ($movies as $movie) {
        $movie_id = $movie['id'];
        $tmdb_id = $movie['tmdb_id'];
        
        $providers = [
            ['label' => 'VidRock', 'url' => "https://vidrock.net/movie/$tmdb_id"],
            ['label' => 'SuperEmbed', 'url' => "https://vidsrc.cc/v2/embed/movie/$tmdb_id"],
            ['label' => 'VidLink', 'url' => "https://vidlink.pro/movie/$tmdb_id"]
        ];

        foreach ($providers as $p) {
            // Check if source already exists to avoid duplicates
            $stmt = $pdo->prepare("SELECT id FROM movie_sources WHERE movie_id = ? AND label = ?");
            $stmt->execute([$movie_id, $p['label']]);
            if (!$stmt->fetch()) {
                $pdo->prepare("INSERT INTO movie_sources (movie_id, label, source_type, source_url) VALUES (?, ?, 'cloud', ?)")
                    ->execute([$movie_id, $p['label'], $p['url']]);
                $updated_movies++;
            }
        }
    }
    echo "<p style='color:green;'>Successfully checked movies. Added $updated_movies movie sources.</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Error repairing movies: " . $e->getMessage() . "</p>";
}

// 2. Repair Episodes
try {
    $episodes = $pdo->query("
        SELECT e.id, e.episode_number, s.season_number, t.tmdb_id, t.title 
        FROM episodes e 
        JOIN seasons s ON e.season_id = s.id 
        JOIN tv_shows t ON s.tv_show_id = t.id 
        WHERE t.tmdb_id IS NOT NULL AND t.tmdb_id != ''
    ")->fetchAll();

    foreach ($episodes as $episode) {
        $ep_id = $episode['id'];
        $tmdb_id = $episode['tmdb_id'];
        $s_num = $episode['season_number'];
        $e_num = $episode['episode_number'];

        $providers = [
            ['label' => 'VidRock', 'url' => "https://vidrock.net/tv/$tmdb_id/$s_num/$e_num"],
            ['label' => 'SuperEmbed', 'url' => "https://vidsrc.cc/v2/embed/tv/$tmdb_id/$s_num/$e_num"],
            ['label' => 'VidLink', 'url' => "https://vidlink.pro/tv/$tmdb_id/$s_num/$e_num"]
        ];

        foreach ($providers as $p) {
            $stmt = $pdo->prepare("SELECT id FROM episode_sources WHERE episode_id = ? AND label = ?");
            $stmt->execute([$ep_id, $p['label']]);
            if (!$stmt->fetch()) {
                $pdo->prepare("INSERT INTO episode_sources (episode_id, label, source_type, source_url) VALUES (?, ?, 'cloud', ?)")
                    ->execute([$ep_id, $p['label'], $p['url']]);
                $updated_episodes++;
            }
        }
    }
    echo "<p style='color:green;'>Successfully checked episodes. Added $updated_episodes episode sources.</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Error repairing episodes: " . $e->getMessage() . "</p>";
}

echo "<h2>Repair Finished!</h2>";
echo "<p>Your player should now show multiple server options for all TMDB content.</p>";
echo "<p><a href='admin/dashboard.php'>Back to Admin Dashboard</a></p>";
?>
