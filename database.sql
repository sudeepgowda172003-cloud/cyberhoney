-- ============================================================
-- HoneyGuard — MySQL Database Schema
-- Compatible with InfinityFree (MySQL 8.0 / MariaDB 11.4)
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Users table — bcrypt password hashing
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'analyst', 'viewer') NOT NULL DEFAULT 'viewer',
    `avatar_url` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Alerts table — populated by Python agent via API
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `level` ENUM('INFO', 'WARNING', 'ALERT', 'CRITICAL') NOT NULL DEFAULT 'INFO',
    `action` VARCHAR(50) DEFAULT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `file_name` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `hostname` VARCHAR(100) DEFAULT NULL,
    `username` VARCHAR(100) DEFAULT NULL,
    `pid` INT DEFAULT NULL,
    `process_name` VARCHAR(100) DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `extra_data` TEXT DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_alerts_level` (`level`),
    INDEX `idx_alerts_created` (`created_at`),
    INDEX `idx_alerts_is_read` (`is_read`),
    INDEX `idx_alerts_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- User sessions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `session_token` VARCHAR(64) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sessions_token` (`session_token`),
    INDEX `idx_sessions_user` (`user_id`),
    INDEX `idx_sessions_expires` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- API keys — for Python agent authentication
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `key_hash` VARCHAR(64) NOT NULL UNIQUE,
    `key_prefix` VARCHAR(8) DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL DEFAULT 'Default',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_used` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_apikeys_hash` (`key_hash`),
    INDEX `idx_apikeys_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Audit log — login/logout/admin actions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Honeyfile registry
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `honeyfiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(50) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `access_count` INT NOT NULL DEFAULT 0,
    `last_accessed` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_honeyfiles_active` (`is_active`),
    INDEX `idx_honeyfiles_name` (`file_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default admin user (password: admin123 — CHANGE THIS!)
-- bcrypt hash of 'admin123'
-- --------------------------------------------------------
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@honeyguard.com', '$2y$12$LJ3m4yPnMDEgRBJKijK.duAHR7BKQF0eVdWBXcYl2n1aqGfS4oFOm', 'admin');
