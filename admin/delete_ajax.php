<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bulk = $_POST['bulk'] ?? 'false';
    $ids = ($bulk === 'true') ? json_decode($_POST['ids'] ?? '[]', true) : [$_POST['id'] ?? null];
    $type = $_POST['type'] ?? 'movie';

    if (empty($ids) || is_null($ids[0])) {
        echo json_encode(['success' => false, 'message' => 'Missing IDs']);
        exit;
    }

    try {
        foreach ($ids as $id) {
            if (!$id) continue;

            if ($type === 'movie') {
                $stmt = $pdo->prepare("SELECT poster, backdrop FROM movies WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();
                if ($item) {
                    if (strpos($item['poster'], 'uploads/') === 0) @unlink('../' . $item['poster']);
                    if (strpos($item['backdrop'], 'uploads/') === 0) @unlink('../' . $item['backdrop']);
                }
                $pdo->prepare("DELETE FROM movies WHERE id = ?")->execute([$id]);
            } elseif ($type === 'tv') {
                $stmt = $pdo->prepare("SELECT poster, backdrop FROM tv_shows WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();
                if ($item) {
                    if (strpos($item['poster'], 'uploads/') === 0) @unlink('../' . $item['poster']);
                    if (strpos($item['backdrop'], 'uploads/') === 0) @unlink('../' . $item['backdrop']);
                }
                $pdo->prepare("DELETE FROM tv_shows WHERE id = ?")->execute([$id]);
            } elseif ($type === 'anime') {
                $stmt = $pdo->prepare("SELECT poster, backdrop FROM anime_shows WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();
                if ($item) {
                    if (strpos($item['poster'], 'uploads/') === 0) @unlink('../' . $item['poster']);
                    if (strpos($item['backdrop'], 'uploads/') === 0) @unlink('../' . $item['backdrop']);
                }
                $pdo->prepare("DELETE FROM anime_shows WHERE id = ?")->execute([$id]);
            } elseif ($type === 'season') {
                $pdo->prepare("DELETE FROM seasons WHERE id = ?")->execute([$id]);
            } elseif ($type === 'episode') {
                $pdo->prepare("DELETE FROM episodes WHERE id = ?")->execute([$id]);
            } elseif ($type === 'anime_season') {
                $pdo->prepare("DELETE FROM anime_seasons WHERE id = ?")->execute([$id]);
            } elseif ($type === 'anime_episode') {
                $pdo->prepare("DELETE FROM anime_episodes WHERE id = ?")->execute([$id]);
            }
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
 else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
}
