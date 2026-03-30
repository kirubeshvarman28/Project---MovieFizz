<?php
// Configuration File Template (Rename to config.php)

// Site Settings
define('SITE_NAME', 'MovieFizz');
define('SITE_URL', 'http://yourdomain.com'); 

// Database Settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_pass');

// API Keys
define('TMDB_API_KEY', 'Get yours from themoviedb.org');
define('APIFY_TOKEN', 'Get yours from apify.com'); 

// Upload Paths
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('POSTER_DIR', 'uploads/posters/');
define('BACKDROP_DIR', 'uploads/backdrops/');
define('MOVIE_DIR', 'uploads/movies/');

// Session Security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

session_start();
?>
