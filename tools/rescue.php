<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// New Admin Credentials
$new_user = 'admin_kirubesh';
$new_pass = 'MovieFizz@2026';
$new_email = 'admin@moviefizz.com';
$hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
    $stmt->execute([$new_user, $new_email, $hashed_pass]);
    echo "<h3>Success! New Admin Created.</h3>";
    echo "<p>Username: <b>$new_user</b></p>";
    echo "<p>Password: <b>$new_pass</b></p>";
    echo "<p>Try logging in at <a href='admin/login.php'>admin_login.php</a> now.</p>";
} catch (Exception $e) {
    echo "<h3>Error: " . $e->getMessage() . "</h3>";
    echo "<p>The user might already exist, try using a different username in the script.</p>";
}
?>
