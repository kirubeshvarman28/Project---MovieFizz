<?php
require_once realpath(__DIR__ . '/../includes/db_connect.php');
require_once INCLUDES_PATH . '/functions.php';

if (is_admin()) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
?>
