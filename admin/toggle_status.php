<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $type = $_POST['type'] ?? 'movie'; // Default to movie

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
        exit;
    }

    $table = ($type === 'tv') ? 'tv_shows' : 'movies';
    
    // Toggle status
    $stmt = $pdo->prepare("UPDATE $table SET is_published = 1 - is_published WHERE id = ?");
    if ($stmt->execute([$id])) {
        // Fetch new status
        $stmt = $pdo->prepare("SELECT is_published FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $status = $stmt->fetchColumn();
        echo json_encode(['success' => true, 'new_status' => $status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
