<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

header('Content-Type: application/json');

$video_path = $_POST['video_path'] ?? '';
$content_id = $_POST['content_id'] ?? '';
$type = $_POST['type'] ?? 'movie'; // movie or episode

if (empty($video_path) || empty($content_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Ensure $video_path doesn't have the '../' prefix if it was passed from a page that already added it
// But usually we pass the DB path: uploads/movies/file.mp4
if (strpos($video_path, '../') === 0) {
    $video_path = substr($video_path, 3);
}

// Clear old tracks first to avoid duplicates if re-extracting
if ($type === 'movie') {
    $pdo->prepare("DELETE FROM movie_subtitles WHERE movie_id = ?")->execute([$content_id]);
    $pdo->prepare("DELETE FROM movie_audio WHERE movie_id = ?")->execute([$content_id]);
} else {
    $pdo->prepare("DELETE FROM episode_subtitles WHERE episode_id = ?")->execute([$content_id]);
    $pdo->prepare("DELETE FROM episode_audio WHERE episode_id = ?")->execute([$content_id]);
}

// Call extraction function
try {
    $result_count = extract_subtitles_from_video($video_path, $content_id, $type);
    
    // Fetch newly created tracks to return them
    if ($type === 'movie') {
        $subs = $pdo->prepare("SELECT language, label FROM movie_subtitles WHERE movie_id = ?");
        $auds = $pdo->prepare("SELECT language, label FROM movie_audio WHERE movie_id = ?");
    } else {
        $subs = $pdo->prepare("SELECT language, label FROM episode_subtitles WHERE episode_id = ?");
        $auds = $pdo->prepare("SELECT language, label FROM episode_audio WHERE episode_id = ?");
    }
    
    $subs->execute([$content_id]);
    $sub_list = $subs->fetchAll();
    
    $auds->execute([$content_id]);
    $aud_list = $auds->fetchAll();

    echo json_encode([
        'success' => true, 
        'count' => $result_count,
        'subtitles' => $sub_list,
        'audio' => $aud_list
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
