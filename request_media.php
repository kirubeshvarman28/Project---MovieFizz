<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once INCLUDES_PATH . '/functions.php';

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
    $mail_sent = false;
    try {
        $username = $_SESSION['username'] ?? 'Guest';
        $data = [
            'title' => $media_title,
            'type' => $media_type,
            'email' => $user_email,
            'username' => $username
        ];
        
        if (send_media_request_notification($data)) {
            $mail_sent = true;
        }
    } catch (Exception $e) {}

    ob_clean();
    $response = ['status' => 'success', 'message' => 'Request submitted successfully.'];
    if (!$mail_sent) {
        $response['message'] .= " (Admin was not notified by email, but the request was saved)";
    }
    echo json_encode($response);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Failed to save request.']);
}
?>
