<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Suppress any warnings from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$media_title = clean_input($_POST['media_title'] ?? '');
$media_type = clean_input($_POST['media_type'] ?? 'movie');
$user_id = is_logged_in() ? $_SESSION['user_id'] : null;
$user_email = clean_input($_POST['user_email'] ?? '');

if (is_logged_in()) {
    if (isset($_SESSION['email']) && !empty($_SESSION['email'])) {
        $user_email = $_SESSION['email'];
    } else {
        // Fallback: Fetch from DB
        $stmt_email = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
        $stmt_email->execute([$user_id]);
        $user_email = $stmt_email->fetchColumn();
    }
}

if (empty($media_title) || empty($user_email)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Title and email are required.']);
    exit();
}

try {
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO media_requests (user_id, user_email, media_title, media_type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_email, $media_title, $media_type]);

    // Send email notification to admin
    $data = [
        'title' => $media_title,
        'type' => $media_type,
        'email' => $user_email,
        'username' => $_SESSION['username'] ?? 'Guest'
    ];
    
    // Catch email errors so they don't break the JSON response
    try {
        send_media_request_notification($data);
    } catch (Exception $e) {}

    ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Request submitted successfully.']);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Failed to save request.']);
}
?>
