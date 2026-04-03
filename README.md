# MovieFizz - Premium Movie Streaming Platform

MovieFizz is a high-performance movie streaming application built with Core PHP, MySQL, and Vanilla JavaScript. Optimized for **InfinityFree** and other shared hosting environments, it features a modern Netflix-style interface, automated TMDB integration, and a sophisticated Terabox video resolver.

**🌐 Live Demo:** [https://moviefizz.xo.je](https://moviefizz.xo.je)

## 🎥 Visual Preview

![Homepage Preview](assets/img/readme/homepage.png)
*Modern Netflix-style home page with featured content and categories.*

![Movie Page Preview](assets/img/readme/movie_page.png)
*Detailed movie information with integrated video player and metadata.*

![Admin Dashboard](assets/img/readme/admin_dashboard.png)
*Comprehensive admin panel for content and user management.*

## 🚀 Key Features

### 💎 Premium User Experience
- **Netflix-Style UI**: Sleek, dark-themed, and highly responsive interface designed for visual excellence.
- **Live AJAX Search**: Instant, real-time search results as users type.
- **Personal Watchlist**: Users can save their favorite movies and shows for later viewing.
- **Advanced HTML5 Player**: Custom player with support for trailers, multiple sources, and direct playback.
- **Movie-Focused Experience**: Clean, streamlined interface focused on high-quality cinematic content.

### 🛠️ Advanced Technology
- **Direct Terabox Streaming**: Integrated proxy-based resolver to play Terabox videos directly without redirects.
- **TMDB Integration**: One-click automated fetching of movie/show metadata, posters, and backdrops.
- **Organized Architecture**: Clean, professional directory structure with centralized path management.
- **FFmpeg Integration**: (VPS required) Automated extraction of subtitles and secondary audio tracks from video streams.

### 🔐 Secure Authentication
- **Modern Auth Flow**: Beautifully designed Login and Registration pages.
- **Account Security**: Secure password hashing and robust session management.
- **Password Recovery**: Integrated forgot password system.

---

## ⚙️ Administration Panel

The MovieFizz admin panel provides full control over every aspect of the platform:

- **Powerful Dashboard**: Real-time statistics for movies, users, and featured content at a glance.
- **Content Management**:
    - **Movies & TV Shows**: Full CRUD support for managing a diverse media library.
    - **Seasons & Episodes**: Granular control over TV show structures.
    - **Genre & Language Management**: Organize content with custom categories.
- **Automated Importers**: 
    - **TMDB Fetcher**: Import all movie details in seconds using just an ID.
    - **Terabox Link Handling**: Easily add and manage direct streaming links.
- **User & Finance**:
    - **User Management**: Control user roles, status, and view join history.
    - **Plans & Coupons**: Create subscription tiers and promotional codes.
- **Site Configuration**:
    - **Player Settings**: Customize the look and feel of the video player.
    - **Home Settings**: Manage featured sliders and home layout.
    - **API & SEO**: Configure TMDB, Apify tokens, and site-wide SEO settings.

---

## 🛠️ Requirements

- **PHP**: 7.4 or 8.x
- **MySQL**: 5.7+ or MariaDB
- **FFmpeg**: Optional (Required for audio/subtitle extraction; Linux VPS recommended).
- **Extensions**: `curl`, `pdo_mysql`, `mbstring`, `gd`.

---

## 📦 Installation (Localhost/Shared Hosting)

Because MovieFizz uses a **flattened structure**, installation is simpler than ever:

1. **Upload**: Move all files directly to your web root (e.g., `C:/xampp/htdocs/MovieFizz` or `public_html/`).
2. **Database**:
   - Create a database (e.g., `moviefizz_db`).
   - Import the comprehensive `sql/database.sql` via phpMyAdmin to initialize all tables and features.
3. **Configuration**:
    - Edit `includes/config.php` (DB settings only, paths are automated):
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'moviefizz_db');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```
4. **Access**:
   - **Frontend**: `http://localhost/MovieFizz`
   - **Admin**: `http://localhost/MovieFizz/admin/login.php` (Default: `admin` / `admin123`)

---

## 🌐 Deployment (InfinityFree Guide)

1. **Upload**: Use an FTP client to upload all files to the `htdocs` folder.
2. **Database**: Create a MySQL DB in your Control Panel and import the `sql/database.sql` file.
3. **Config**: Update `includes/config.php` with the DB Host, Name, User, and Password provided by InfinityFree.
4. **Done!**: Your site is live at your InfinityFree domain. **Admin access is at `/admin/login.php`**.

---

## 📄 License
This project is licensed and reserved by [Kirubesh Varman](https://github.com/kirubeshvarman28). All rights reserved.
