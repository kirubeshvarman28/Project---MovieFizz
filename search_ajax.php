<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

$q = $_GET['q'] ?? '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

$results = [];

// Search movies
$stmt = $pdo->prepare("SELECT id, title, poster, release_year FROM movies WHERE title LIKE ? AND is_published = 1 LIMIT 5");
$stmt->execute(['%'.$q.'%']);
$movies = $stmt->fetchAll();

foreach ($movies as $m) {
    $poster = $m['poster'];
    // Poster path is correctly relative to root
    $results[] = [
        'id' => $m['id'],
        'title' => $m['title'],
        'poster' => $poster ?: get_placeholder(),
        'year' => $m['release_year'],
        'type' => 'movie'
    ];
}

// Search TV Shows
try {
    $stmt = $pdo->prepare("SELECT id, title, poster, genre FROM tv_shows WHERE title LIKE ? LIMIT 5");
    $stmt->execute(['%'.$q.'%']);
    $shows = $stmt->fetchAll();

    foreach ($shows as $s) {
        $poster = $s['poster'];
        // Poster path is correctly relative to root
        $results[] = [
            'id' => $s['id'],
            'title' => $s['title'],
            'poster' => $poster ?: get_placeholder(),
            'year' => $s['genre'] ?? '',
            'type' => 'show'
        ];
    }
} catch(Exception $e) {}

header('Content-Type: application/json');
echo json_encode($results);
?>
