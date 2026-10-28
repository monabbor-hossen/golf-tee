-- ========================================================
-- Migration: 001_initial_schema.sql
-- Base table definitions for GolfTee system
-- ========================================================

USE `golf_admin`;

CREATE TABLE IF NOT EXISTS `members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt hashed, never plain text',
  `password_hash` VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt hashed, never plain text',
  `phone` VARCHAR(30) DEFAULT '',
  `tier` ENUM('Gold','Silver','Standard') NOT NULL DEFAULT 'Standard',
  `hcp` DECIMAL(4,1) DEFAULT 36.0,
  `status` ENUM('Active','Suspended','Pending') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tee_date` DATE NOT NULL,
  `tee_slot` INT NOT NULL,
  `tee` TINYINT NOT NULL DEFAULT 0,
  `name` VARCHAR(100) NOT NULL,
  `party` TINYINT NOT NULL DEFAULT 2,
  `holes` TINYINT NOT NULL DEFAULT 18,
  `status` ENUM('Booked','Checked in','No-show') NOT NULL DEFAULT 'Booked',
  `member_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
