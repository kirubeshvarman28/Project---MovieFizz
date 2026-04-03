<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (!is_admin()) exit;

header('Content-Type: application/json');

$video_path = $_GET['video_path'] ?? '';
$content_id = $_GET['content_id'] ?? '';

if (empty($video_path) || empty($content_id)) {
    echo json_encode(['percent' => 0, 'status' => 'waiting', 'eta' => '--:--']);
    exit;
}

$progress_dir = __DIR__ . '/../uploads/progress/';
$progress_file = $progress_dir . md5($video_path . $content_id) . ".txt";

if (!file_exists($progress_file)) {
    echo json_encode(['percent' => 0, 'status' => 'initializing', 'eta' => '--:--']);
    exit;
}

$content = file_get_contents($progress_file);
$lines = explode("\n", $content);
$data = [];
foreach ($lines as $line) {
    if (strpos($line, '=') !== false) {
        list($key, $val) = explode('=', $line, 2);
        $data[trim($key)] = trim($val);
    }
}

$total_duration = isset($data['total_duration']) ? (float)$data['total_duration'] : 0;
$out_time_us = isset($data['out_time_us']) ? (float)$data['out_time_us'] : 0;
$status = $data['status'] ?? 'processing';
$speed = isset($data['speed']) ? (float)str_replace('x', '', $data['speed']) : 1;

$percent = 0;
if ($total_duration > 0) {
    $percent = round(($out_time_us / ($total_duration * 1000000)) * 100, 1);
}

// Ensure percent doesn't exceed 100
if ($percent > 100) $percent = 100;

// ETA calculation
$eta = "Calculating...";
if ($speed > 0 && $total_duration > 0) {
    $remaining_seconds = ($total_duration - ($out_time_us / 1000000)) / $speed;
    if ($remaining_seconds > 0) {
        $mins = floor($remaining_seconds / 60);
        $secs = round($remaining_seconds % 60);
        $eta = sprintf("%02d:%02d", $mins, $secs);
    } else {
        $eta = "Wrapping up...";
    }
}

echo json_encode([
    'percent' => $percent,
    'status' => $status,
    'eta' => $eta,
    'debug' => $data // Optional for debugging
]);
?>
