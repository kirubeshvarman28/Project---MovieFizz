-- FINAL DATABASE FIX FOR MOVIEFIZZ ADMIN
-- This script ensures all missing tables and columns required by the new admin panel exist.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. FIX CAST_CREW TABLE (Align with TMDB Import)
-- Adding missing columns use in admin_import_tmdb.php and admin_add_movie.php
ALTER TABLE `cast_crew` ADD COLUMN IF NOT EXISTS `tmdb_id` int(11) DEFAULT NULL;
ALTER TABLE `cast_crew` ADD COLUMN IF NOT EXISTS `image` varchar(255) DEFAULT NULL;
ALTER TABLE `cast_crew` ADD COLUMN IF NOT EXISTS `type` varchar(50) DEFAULT 'Acting';

-- 2. CREATE MOVIE SOURCES TABLE
CREATE TABLE IF NOT EXISTS `movie_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_url` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movie_id` (`movie_id`),
  CONSTRAINT `movie_sources_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. CREATE MOVIE SUBTITLES TABLE
CREATE TABLE IF NOT EXISTS `movie_subtitles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `file_url` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movie_id` (`movie_id`),
  CONSTRAINT `movie_subtitles_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. CREATE MOVIE AUDIO TRACKS TABLE
CREATE TABLE IF NOT EXISTS `movie_audio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `file_url` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movie_id` (`movie_id`),
  CONSTRAINT `movie_audio_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. CREATE EPISODE SOURCES TABLE
CREATE TABLE IF NOT EXISTS `episode_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_url` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `episode_id` (`episode_id`),
  CONSTRAINT `episode_sources_ibfk_1` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5a. CREATE EPISODE SUBTITLES TABLE
CREATE TABLE IF NOT EXISTS `episode_subtitles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `file_url` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `episode_id` (`episode_id`),
  CONSTRAINT `episode_subtitles_ibfk_1` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5b. CREATE EPISODE AUDIO TRACKS TABLE
CREATE TABLE IF NOT EXISTS `episode_audio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `file_url` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `episode_id` (`episode_id`),
  CONSTRAINT `episode_audio_ibfk_1` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. CREATE MOVIE CAST JOIN TABLE
CREATE TABLE IF NOT EXISTS `movie_cast` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) DEFAULT NULL,
  `cast_id` int(11) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movie_id` (`movie_id`),
  KEY `cast_id` (`cast_id`),
  CONSTRAINT `movie_cast_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movie_cast_ibfk_2` FOREIGN KEY (`cast_id`) REFERENCES `cast_crew` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. CREATE TV SHOW CAST JOIN TABLE
CREATE TABLE IF NOT EXISTS `tv_show_cast` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tv_show_id` int(11) DEFAULT NULL,
  `cast_id` int(11) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tv_show_id` (`tv_show_id`),
  KEY `cast_id` (`cast_id`),
  CONSTRAINT `tv_show_cast_ibfk_1` FOREIGN KEY (`tv_show_id`) REFERENCES `tv_shows` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tv_show_cast_ibfk_2` FOREIGN KEY (`cast_id`) REFERENCES `cast_crew` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. ADD VIEWS AND TMDB_ID TO MOVIES IF MISSING
ALTER TABLE `movies` ADD COLUMN IF NOT EXISTS `views` int(11) DEFAULT 0;
ALTER TABLE `movies` ADD COLUMN IF NOT EXISTS `language` varchar(50) DEFAULT 'English';
ALTER TABLE `movies` ADD COLUMN IF NOT EXISTS `main_audio_label` varchar(100) DEFAULT 'Original Audio';

-- 9. ADD LANGUAGE TO TV_SHOWS IF MISSING
ALTER TABLE `tv_shows` ADD COLUMN IF NOT EXISTS `language` varchar(50) DEFAULT 'English';

-- 10. ADD TMDB_ID AND METADATA TO EPISODES IF MISSING
ALTER TABLE `episodes` ADD COLUMN IF NOT EXISTS `tmdb_id` int(11) DEFAULT NULL;
ALTER TABLE `episodes` ADD COLUMN IF NOT EXISTS `poster` varchar(255) DEFAULT NULL;
ALTER TABLE `episodes` ADD COLUMN IF NOT EXISTS `main_audio_label` varchar(100) DEFAULT 'Original Audio';

-- 11. CREATE SETTINGS TABLE IF MISSING
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(255) DEFAULT 'MovieFizz',
  `site_logo` varchar(255) DEFAULT '',
  `site_icon` varchar(255) DEFAULT '',
  `email` varchar(255) DEFAULT NULL,
  `description` text,
  `keywords` text,
  `timezone` varchar(100) DEFAULT 'UTC',
  `currency_code` varchar(10) DEFAULT 'USD',
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `tmdb_api_key` varchar(255) DEFAULT NULL,
  `tmdb_language` varchar(10) DEFAULT 'en-US',
  `facebook_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_user` varchar(255) DEFAULT NULL,
  `smtp_pass` varchar(255) DEFAULT NULL,
  `smtp_port` varchar(10) DEFAULT '587',
  `smtp_crypto` varchar(10) DEFAULT 'tls',
  `default_provider` varchar(50) DEFAULT 'vidrock',
  `autoplay` tinyint(1) DEFAULT 0,
  `ad_header` text,
  `ad_footer` text,
  `ad_player` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11a. ENSURE ICON COLUMN EXISTS IN SETTINGS
ALTER TABLE `settings` ADD COLUMN IF NOT EXISTS `site_icon` varchar(255) DEFAULT '' AFTER `site_logo`;

INSERT IGNORE INTO `settings` (id, site_name) VALUES (1, 'MovieFizz');

-- 12. CREATE MEDIA REQUESTS TABLE
CREATE TABLE IF NOT EXISTS `media_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(255) NOT NULL,
  `media_title` varchar(255) NOT NULL,
  `media_type` varchar(50) DEFAULT 'movie',
  `status` enum('pending','completed','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. ADD PASSWORD RESET & VERIFICATION COLUMNS TO USERS
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `reset_code` varchar(10) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `reset_expires_at` datetime DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `is_verified` tinyint(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `verification_code` varchar(10) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `verification_expires_at` datetime DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `profile_pic` varchar(255) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `bio` text DEFAULT NULL;

-- 14. Unified Watchlist - CLEAN SLATE
-- The user requested a clean start. We will drop the old table and create a new standardized one.
DROP TABLE IF EXISTS `watchlist`;

CREATE TABLE `watchlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `media_id` INT NOT NULL,
    `media_type` ENUM('movie', 'show') NOT NULL DEFAULT 'movie',
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `user_id` (`user_id`),
    CONSTRAINT `watchlist_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clean up any secondary table from previous attempts
DROP TABLE IF EXISTS `watchlist_new`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;
