<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first']);
    exit();
}

$movie_id = $_GET['movie_id'] ?? null;
$user_id = $_SESSION['user_id'];

if ($movie_id) {
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM watchlist WHERE user_id = ? AND movie_id = ?");
    $stmt->execute([$user_id, $movie_id]);
    $row = $stmt->fetch();

    if ($row) {
        // Remove
        $stmt = $pdo->prepare("DELETE FROM watchlist WHERE id = ?");
        $stmt->execute([$row['id']]);
        echo json_encode(['status' => 'removed']);
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO watchlist (user_id, movie_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $movie_id]);
        echo json_encode(['status' => 'added']);
    }
}
?>
