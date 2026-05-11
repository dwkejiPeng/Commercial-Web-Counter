-- Commercial Web Counter SaaS
-- MySQL 5.7+ / MariaDB 10.2+

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `username` VARCHAR(80) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `site_name` VARCHAR(120) NOT NULL,
  `site_domain` VARCHAR(190) NOT NULL,
  `base_url` VARCHAR(500) NOT NULL,
  `site_key` CHAR(32) NOT NULL,
  `status` ENUM('pending','active','disabled') NOT NULL DEFAULT 'pending',
  `display_mode` ENUM('text','number','badge','hidden','custom') NOT NULL DEFAULT 'text',
  `display_label` VARCHAR(80) NOT NULL DEFAULT '访问量',
  `theme` ENUM('light','dark','primary','custom') NOT NULL DEFAULT 'light',
  `custom_css` TEXT NULL,
  `allowed_domains` TEXT NULL,
  `total_views` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `verified_at` DATETIME NULL DEFAULT NULL,
  `last_seen_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_key` (`site_key`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_sites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `page_counters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` BIGINT UNSIGNED NOT NULL,
  `page_key` VARCHAR(255) NOT NULL,
  `page_url` VARCHAR(800) NOT NULL,
  `page_title` VARCHAR(255) NULL DEFAULT NULL,
  `views` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_page` (`site_id`, `page_key`),
  KEY `idx_site_views` (`site_id`, `views`),
  CONSTRAINT `fk_page_counters_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `visit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` BIGINT UNSIGNED NOT NULL,
  `page_key` VARCHAR(255) NOT NULL,
  `page_url` VARCHAR(800) NOT NULL,
  `page_title` VARCHAR(255) NULL DEFAULT NULL,
  `visitor_ip` VARCHAR(45) NULL DEFAULT NULL,
  `visitor_hash` CHAR(64) NOT NULL,
  `user_agent` VARCHAR(500) NULL DEFAULT NULL,
  `referer` VARCHAR(800) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_site_created` (`site_id`, `created_at`),
  KEY `idx_site_page_created` (`site_id`, `page_key`, `created_at`),
  KEY `idx_visitor_hash` (`visitor_hash`),
  CONSTRAINT `fk_visit_logs_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_stats` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` BIGINT UNSIGNED NOT NULL,
  `stat_date` DATE NOT NULL,
  `page_key` VARCHAR(255) NOT NULL,
  `page_url` VARCHAR(800) NOT NULL,
  `pv` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `uv` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_date_page` (`site_id`, `stat_date`, `page_key`),
  KEY `idx_site_date` (`site_id`, `stat_date`),
  CONSTRAINT `fk_daily_stats_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
