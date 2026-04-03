<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Get file paths to delete them from server
    $stmt = $pdo->prepare("SELECT poster, backdrop, video_url FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    $movie = $stmt->fetch();
    
    if ($movie) {
        // Delete files if they are local
        if (strpos($movie['poster'], 'uploads/') === 0) @unlink($movie['poster']);
        if (strpos($movie['backdrop'], 'uploads/') === 0) @unlink('../' . $movie['backdrop']);
        if (strpos($movie['video_url'], 'uploads/') === 0) @unlink('../' . $movie['video_url']);
        
        // Delete from DB
        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
        if ($stmt->execute([$id])) {
            redirect('manage_movies.php?msg=deleted');
        } else {
            redirect('manage_movies.php?error=db_fail');
        }
    } else {
        redirect('manage_movies.php?error=not_found');
    }
}

redirect('manage_movies.php');
?>
