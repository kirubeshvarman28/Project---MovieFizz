<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) redirect('login.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Get file paths to delete them from server
    $stmt = $pdo->prepare("SELECT poster, backdrop FROM tv_shows WHERE id = ?");
    $stmt->execute([$id]);
    $show = $stmt->fetch();
    
    if ($show) {
        // Delete files if they are local
        if (strpos($show['poster'], 'uploads/') === 0) @unlink('../' . $show['poster']);
        if (strpos($show['backdrop'], 'uploads/') === 0) @unlink('../' . $show['backdrop']);
        
        // Delete from DB (Seasons and Episodes should cascade if foreign keys are set)
        $stmt = $pdo->prepare("DELETE FROM tv_shows WHERE id = ?");
        if ($stmt->execute([$id])) {
            redirect('manage_shows.php?msg=deleted');
        } else {
            redirect('manage_shows.php?error=db_fail');
        }
    } else {
        redirect('manage_shows.php?error=not_found');
    }
}

redirect('manage_shows.php');
?>
