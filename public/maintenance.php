<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$settings = get_all_settings();
$site_name = $settings['site_name'] ?? SITE_NAME;

// If maintenance is OFF, redirect back to home
if (!is_maintenance_mode()) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo $site_name; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #000;
            color: #fff;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .maintenance-container {
            max-width: 600px;
            padding: 40px;
        }
        .icon {
            font-size: 5rem;
            color: #E50914;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }
        h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }
        p {
            color: #888;
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .brand {
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="icon">
            <i class="fas fa-tools"></i>
        </div>
        <h1>Under <span style="color: #E50914;">Maintenance</span></h1>
        <p>
            We're currently performing some scheduled updates to improve your viewing experience on <span class="brand"><?php echo $site_name; ?></span>.
            We'll be back online shortly!
        </p>
        <div style="color: #444; font-size: 0.9rem;">
            &copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. Admin can still login via <code>/admin</code>.
        </div>
    </div>
</body>
</html>
