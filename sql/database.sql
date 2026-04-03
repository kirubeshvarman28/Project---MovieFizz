-- MovieFizz - Complete Database Schema
-- Last Updated: 2026-04-03

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 1. SETTINGS
-- ----------------------------
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
  `smtp_user?` varchar(255) DEFAULT NULL,
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

INSERT IGNORE INTO `settings` (id, site_name) VALUES (1, 'MovieFizz');

-- ----------------------------
-- 2. USERS
-- ----------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('active','blocked') NOT NULL DEFAULT 'active',
  `reset_code` varchar(10) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_code` varchar(10) DEFAULT NULL,
  `verification_expires_at` datetime DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial Admin Account (Password: admin123)
-- Replace with $2y$10$U.yR98B75xLpT.7u.511SeZ2U3R6X6.O5mY5wK/l0Kq23L3/m/6y6
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`, `status`) 
VALUES ('admin', 'admin@moviefizz.com', '$2y$10$U.yR98B75xLpT.7u.511SeZ2U3R6X6.O5mY5wK/l0Kq23L3/m/6y6', 'admin', 'active');

-- ----------------------------
-- 3. LANGUAGES & GENRES
-- ----------------------------
CREATE TABLE IF NOT EXISTS `languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `genres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 4. STATIC PAGES
-- ----------------------------
CREATE TABLE IF NOT EXISTS `static_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `content` text,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 5. MOVIES
-- ----------------------------
CREATE TABLE IF NOT EXISTS `movies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tmdb_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `genre` varchar(100) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT '0.0',
  `release_year` int(4) DEFAULT NULL,
  `runtime` int(11) DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `backdrop` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `trailer_url` varchar(255) DEFAULT NULL,
  `cast` text,
  `is_featured` tinyint(1) DEFAULT '0',
  `is_published` tinyint(1) DEFAULT '1',
  `views` int(11) DEFAULT 0,
  `language` varchar(50) DEFAULT 'English',
  `main_audio_label` varchar(100) DEFAULT 'Original Audio',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- ----------------------------
-- 6. TV SHOWS, SEASONS & EPISODES
-- ----------------------------
CREATE TABLE IF NOT EXISTS `tv_shows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tmdb_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `poster` varchar(255) DEFAULT NULL,
  `backdrop` varchar(255) DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT '0.0',
  `status` enum('published','unpublished') DEFAULT 'published',
  `language` varchar(50) DEFAULT 'English',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `seasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tv_show_id` int(11) NOT NULL,
  `season_number` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `status` enum('published','unpublished') DEFAULT 'published',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tv_show_id`) REFERENCES `tv_shows`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `episodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `season_id` int(11) NOT NULL,
  `episode_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `video_url` varchar(255) NOT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `tmdb_id` int(11) DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `main_audio_label` varchar(100) DEFAULT 'Original Audio',
  `status` enum('published','unpublished') DEFAULT 'published',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`season_id`) REFERENCES `seasons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `episode_sources` (
  `id" int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_url` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `episode_id` (`episode_id`),
  CONSTRAINT `episode_sources_ibfk_1` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- ----------------------------
-- 7. CAST & CREW
-- ----------------------------
CREATE TABLE IF NOT EXISTS `cast_crew` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `bio` text,
  `tmdb_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'Acting',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- ----------------------------
-- 8. SPORTS & LIVE TV
-- ----------------------------
CREATE TABLE IF NOT EXISTS `sports_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sports_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `video_url` varchar(255) NOT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `status` enum('published','unpublished') DEFAULT 'published',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `sports_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `live_tv_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name" varchar(50) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `live_tv_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `stream_url` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `status` enum('published','unpublished') DEFAULT 'published',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `live_tv_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 9. USER INTERACTIONS
-- ----------------------------
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

CREATE TABLE IF NOT EXISTS `watchlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `media_id` INT NOT NULL,
    `media_type` ENUM('movie', 'show') NOT NULL DEFAULT 'movie',
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `user_id` (`user_id`),
    CONSTRAINT `watchlist_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 10. MONETIZATION
-- ----------------------------
CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL, -- in days
  `features` text,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 11. PLAYER SETTINGS
-- ----------------------------
CREATE TABLE IF NOT EXISTS `player_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `watermark_image` varchar(255) DEFAULT NULL,
  `watermark_link` varchar(255) DEFAULT NULL,
  `advertisement_type` enum('none','vast','custom') DEFAULT 'none',
  `ad_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
