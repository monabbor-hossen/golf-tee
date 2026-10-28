-- ========================================================
-- GolfTee Migration: 001_initial_schema.sql
-- Base table definitions, foreign keys, and indexes for GolfTee
-- ========================================================

CREATE DATABASE IF NOT EXISTS `golf_admin` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `golf_admin`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: members
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt hashed via password_hash(), never plain text',
  `password_hash` VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt hashed via password_hash(), never plain text',
  `phone` VARCHAR(30) DEFAULT '',
  `tier` ENUM('Gold','Silver','Standard') NOT NULL DEFAULT 'Standard',
  `hcp` DECIMAL(4,1) DEFAULT 36.0,
  `rounds` INT DEFAULT 0,
  `last_play` VARCHAR(40) DEFAULT '',
  `joined` VARCHAR(40) DEFAULT '',
  `status` ENUM('Active','Suspended','Pending','pending_verification') NOT NULL DEFAULT 'Pending',
  `spend` DECIMAL(10,2) DEFAULT 0.00,
  `next_tee` VARCHAR(120) DEFAULT '',
  `auth_provider` VARCHAR(50) DEFAULT 'email',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_member_email` (`email`),
  INDEX `idx_member_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: member_cards
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_cards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT NOT NULL,
  `brand` VARCHAR(30) NOT NULL DEFAULT 'Visa',
  `card_holder` VARCHAR(100) NOT NULL DEFAULT '',
  `last4` VARCHAR(4) NOT NULL,
  `exp_month` VARCHAR(2) NOT NULL DEFAULT '12',
  `exp_year` VARCHAR(4) NOT NULL DEFAULT '2028',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_member_cards_member` (`member_id`),
  CONSTRAINT `fk_member_cards_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: payment_cards
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_cards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT NOT NULL,
  `brand` VARCHAR(30) NOT NULL DEFAULT 'Visa',
  `card_holder` VARCHAR(100) NOT NULL DEFAULT '',
  `last4` VARCHAR(4) NOT NULL,
  `exp_month` VARCHAR(2) NOT NULL DEFAULT '12',
  `exp_year` VARCHAR(4) NOT NULL DEFAULT '2028',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_payment_cards_member` (`member_id`),
  CONSTRAINT `fk_payment_cards_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: bookings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tee_date` DATE NOT NULL,
  `tee_slot` INT NOT NULL,
  `tee` TINYINT NOT NULL DEFAULT 0,
  `name` VARCHAR(100) NOT NULL,
  `party` TINYINT NOT NULL DEFAULT 2,
  `holes` TINYINT NOT NULL DEFAULT 18,
  `status` ENUM('Booked','Checked in','No-show','Cancelled') NOT NULL DEFAULT 'Booked',
  `notes` TEXT NULL,
  `member_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_bookings_date` (`tee_date`),
  INDEX `idx_bookings_slot` (`tee_date`, `tee_slot`, `tee`),
  INDEX `idx_bookings_member` (`member_id`),
  CONSTRAINT `fk_bookings_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: blocked_slots
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blocked_slots` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tee_date` DATE NOT NULL,
  `tee_slot` INT NOT NULL,
  `tee` TINYINT NOT NULL DEFAULT 0,
  `reason` VARCHAR(255) DEFAULT 'Course maintenance',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_blocked_date` (`tee_date`),
  UNIQUE KEY `idx_blocked_unique` (`tee_date`, `tee_slot`, `tee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: waitlist
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `waitlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `tee_date` DATE NOT NULL,
  `tee_slot` INT NOT NULL,
  `party` TINYINT NOT NULL DEFAULT 2,
  `holes` TINYINT NOT NULL DEFAULT 18,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Waiting','Offered','Accepted','Cancelled') NOT NULL DEFAULT 'Waiting',
  `offered_slot` INT NULL,
  `member_id` INT NULL,
  INDEX `idx_waitlist_date` (`tee_date`),
  INDEX `idx_waitlist_member` (`member_id`),
  CONSTRAINT `fk_waitlist_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: caddies
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `caddies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `experience_years` TINYINT NOT NULL DEFAULT 1,
  `specialty` VARCHAR(200) DEFAULT NULL,
  `rate_per_round` DECIMAL(8,2) NOT NULL DEFAULT 50.00,
  `rating` DECIMAL(2,1) NOT NULL DEFAULT 4.0,
  `status` ENUM('Available','Unavailable','On Leave') NOT NULL DEFAULT 'Available',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: caddie_bookings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `caddie_bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `caddie_id` INT NOT NULL,
  `booking_id` INT DEFAULT NULL,
  `member_name` VARCHAR(100) NOT NULL,
  `tee_date` DATE NOT NULL,
  `tee_slot` INT NOT NULL,
  `holes` TINYINT NOT NULL DEFAULT 18,
  `fee` DECIMAL(8,2) NOT NULL DEFAULT 50.00,
  `rating` TINYINT DEFAULT NULL,
  `status` ENUM('Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Confirmed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_caddie_bookings_caddie` (`caddie_id`),
  INDEX `idx_caddie_bookings_booking` (`booking_id`),
  CONSTRAINT `fk_caddie_bookings_caddie` FOREIGN KEY (`caddie_id`) REFERENCES `caddies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_caddie_bookings_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: club_news
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `club_news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'General',
  `badge_text` VARCHAR(50) NULL,
  `summary` VARCHAR(255) NOT NULL,
  `content` TEXT NULL,
  `image_url` VARCHAR(500) NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `published_at` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_news_published` (`is_published`, `published_at`),
  INDEX `idx_news_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: staff
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'bcrypt hashed via password_hash(), never plain text',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed via password_hash(), never plain text',
  `role` ENUM('Owner','Pro','Admin','Staff') NOT NULL DEFAULT 'Staff',
  `avatar` VARCHAR(200) DEFAULT '',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `password_must_change` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: events
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `event_date` DATE NOT NULL,
  `start_slot` INT NOT NULL,
  `hours` TINYINT NOT NULL DEFAULT 4,
  `format` VARCHAR(50) NOT NULL DEFAULT 'Stroke play',
  `capacity` SMALLINT NOT NULL DEFAULT 72,
  `fee` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `is_open` TINYINT(1) NOT NULL DEFAULT 1,
  `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_events_created_by` (`created_by`),
  CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: event_registrations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `event_registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `member_id` INT NULL,
  `member_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `hcp` DECIMAL(4,1) DEFAULT 0.0,
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_event_registrations_event` (`event_id`),
  INDEX `idx_event_registrations_member_id` (`member_id`),
  CONSTRAINT `fk_event_registrations_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_registrations_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: transactions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` VARCHAR(20) PRIMARY KEY,
  `txn_date` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `txn_time` INT NOT NULL DEFAULT 660,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `type` ENUM('Green fee','Pro shop','Food & bev','Event','Membership') NOT NULL DEFAULT 'Green fee',
  `method` VARCHAR(50) DEFAULT 'Visa',
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Paid','Pending','Refunded') NOT NULL DEFAULT 'Paid',
  `ref` VARCHAR(100) DEFAULT NULL,
  `member_id` INT NULL,
  `booking_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_txn_date` (`txn_date`),
  INDEX `idx_txn_member` (`member_id`),
  INDEX `idx_transactions_booking_id` (`booking_id`),
  CONSTRAINT `fk_transactions_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transactions_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(100) PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `tone` ENUM('forest','wait','danger','sand','gray') NOT NULL DEFAULT 'gray',
  `page` VARCHAR(50) NOT NULL DEFAULT 'Dashboard',
  `focus_data` VARCHAR(255) DEFAULT '',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notifications_member_id` (`member_id`),
  CONSTRAINT `fk_notifications_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_log
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `tone` ENUM('forest','wait','danger','sand','gray') NOT NULL DEFAULT 'gray',
  `actor` VARCHAR(100) NOT NULL DEFAULT 'System',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: login_attempts (IP-based brute-force protection)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `attempt_time` INT NOT NULL,
  INDEX `idx_login_attempts_ip_time` (`ip`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- View: member_history
-- --------------------------------------------------------
DROP VIEW IF EXISTS `member_history`;
CREATE VIEW `member_history` AS
  SELECT b.member_id, b.tee_date, b.tee_slot, b.party, b.holes,
         t.amount, b.status
  FROM   bookings b
  LEFT JOIN transactions t ON t.ref = CONCAT(b.tee_date,'|',b.tee,'-',b.tee_slot)
  WHERE  b.member_id IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;
