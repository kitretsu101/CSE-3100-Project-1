-- ============================================================
-- Bit2Byte Database Setup Script
-- Run this in phpMyAdmin or MySQL CLI if auto-setup fails
-- ============================================================

CREATE DATABASE IF NOT EXISTS `bit2byte` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bit2byte`;

-- ─── Users Table ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `student_id` VARCHAR(50) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Contact Messages Table ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Events Table ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `event_date` DATE NOT NULL,
    `image` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Seed Admin Account ─────────────────────────────────────
-- Password: admin123 (hashed with PHP password_hash)
-- You can also let the application auto-seed this on first load
INSERT INTO `users` (`full_name`, `username`, `email`, `phone`, `department`, `student_id`, `password`, `role`)
SELECT 'Admin', 'admin', 'admin@gmail.com', '', 'Administration', 'ADMIN-001',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'admin@gmail.com');
