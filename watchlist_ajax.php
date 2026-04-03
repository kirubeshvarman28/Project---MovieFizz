<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first']);
    exit();
}

$media_id = $_GET['media_id'] ?? $_GET['movie_id'] ?? null;
$item_type = $_GET['media_type'] ?? 'movie';
$user_id = $_SESSION['user_id'];

if ($media_id) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM watchlist WHERE user_id = ? AND media_id = ? AND media_type = ?");
        $stmt->execute([$user_id, $media_id, $item_type]);
        $row = $stmt->fetch();

        if ($row) {
            // Remove
            $stmt = $pdo->prepare("DELETE FROM watchlist WHERE id = ?");
            $stmt->execute([$row['id']]);
            echo json_encode(['status' => 'removed']);
        } else {
            // Add
            $stmt = $pdo->prepare("INSERT INTO watchlist (user_id, media_id, media_type) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $media_id, $item_type]);
            echo json_encode(['status' => 'added']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error. Please run database_final_fix.sql.']);
    }
}
?>
