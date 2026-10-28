-- ========================================================
-- GolfTee - Golf Club Booking & Course Management System
-- DATABASE SCHEMA (DDL)
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+
-- Security & Encryption-at-rest: Passwords hashed with bcrypt via PHP password_hash().
-- Authentication tokens are cryptographically random, time-bounded, single-use.
-- ========================================================

CREATE DATABASE IF NOT EXISTS `golf_admin` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `golf_admin`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: members
-- --------------------------------------------------------
DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt hashed via password_hash(), never plain text',
  `password_hash` VARCHAR(255) DEFAULT NULL COMMENT 'bcrypt hashed via password_hash(), never plain text',
  `reset_token` VARCHAR(100) DEFAULT NULL COMMENT 'Single-use password reset token, nulled on consumption',
  `token_expires_at` DATETIME DEFAULT NULL COMMENT 'Password reset token expiration timestamp',
  `password_reset_token` VARCHAR(100) DEFAULT NULL,
  `password_reset_expires` DATETIME DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT '',
  `tier` ENUM('Gold','Silver','Standard') NOT NULL DEFAULT 'Standard',
  `hcp` DECIMAL(4,1) DEFAULT 36.0,
  `rounds` INT DEFAULT 0,
  `last_play` VARCHAR(40) DEFAULT '',
  `joined` VARCHAR(40) DEFAULT '',
  `status` ENUM('Active','Suspended','Pending','pending_verification') NOT NULL DEFAULT 'Pending',
  `spend` DECIMAL(10,2) DEFAULT 0.00,
  `next_tee` VARCHAR(120) DEFAULT '',
  `setup_token` VARCHAR(100) DEFAULT NULL,
  `setup_token_expires` DATETIME DEFAULT NULL,
  `auth_provider` VARCHAR(50) DEFAULT 'email',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_member_email` (`email`),
  INDEX `idx_member_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE UNIQUE INDEX idx_members_email ON members(email);
ALTER TABLE members ADD CONSTRAINT chk_password_hash_length CHECK (CHAR_LENGTH(password_hash) >= 60);

-- --------------------------------------------------------
-- Table: member_cards
-- --------------------------------------------------------
DROP TABLE IF EXISTS `member_cards`;
CREATE TABLE `member_cards` (
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
CREATE INDEX idx_member_cards_member_id ON member_cards(member_id);

-- --------------------------------------------------------
-- Table: payment_cards (Member Payment Methods)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payment_cards`;
CREATE TABLE `payment_cards` (
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
CREATE INDEX idx_payment_cards_member_id ON payment_cards(member_id);

-- --------------------------------------------------------
-- Table: bookings
-- --------------------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
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
  INDEX `idx_booking_member_id` (`member_id`),
  INDEX `idx_bookings_member_date` (`member_id`, `tee_date`),
  INDEX `idx_bookings_date_status` (`tee_date`, `status`),
  INDEX `idx_bookings_member_status` (`member_id`, `status`),
  INDEX `idx_bookings_date_member` (`tee_date`, `member_id`),
  CONSTRAINT `fk_bookings_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_bookings_member_id ON bookings(member_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_tee_date ON bookings(tee_date);
CREATE INDEX idx_bookings_member_date ON bookings(member_id, tee_date);

-- --------------------------------------------------------
-- Table: blocked_slots
-- --------------------------------------------------------
DROP TABLE IF EXISTS `blocked_slots`;
CREATE TABLE `blocked_slots` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tee_date` DATE NOT NULL,
  `tee_slot` INT NOT NULL,
  `tee` TINYINT NOT NULL DEFAULT 0,
  `reason` VARCHAR(255) DEFAULT 'Course maintenance',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_blocked_date` (`tee_date`),
  UNIQUE KEY `idx_blocked_unique` (`tee_date`, `tee_slot`, `tee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_blocked_slots_date_slot ON blocked_slots(tee_date, tee_slot);

-- --------------------------------------------------------
-- Table: waitlist
-- --------------------------------------------------------
DROP TABLE IF EXISTS `waitlist`;
CREATE TABLE `waitlist` (
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
  INDEX `idx_waitlist_member_id` (`member_id`),
  INDEX `idx_waitlist_date_status` (`tee_date`, `status`),
  CONSTRAINT `fk_waitlist_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_waitlist_member_id ON waitlist(member_id);
CREATE INDEX idx_waitlist_member ON waitlist(member_id);
CREATE INDEX idx_waitlist_date_holes ON waitlist(tee_date, holes);

-- --------------------------------------------------------
-- Table: caddies
-- --------------------------------------------------------
DROP TABLE IF EXISTS `caddies`;
CREATE TABLE `caddies` (
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
DROP TABLE IF EXISTS `caddie_bookings`;
CREATE TABLE `caddie_bookings` (
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
DROP TABLE IF EXISTS `club_news`;
CREATE TABLE `club_news` (
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
-- Table: events
-- --------------------------------------------------------
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
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
DROP TABLE IF EXISTS `event_registrations`;
CREATE TABLE `event_registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `member_id` INT NULL,
  `member_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `hcp` DECIMAL(4,1) DEFAULT 0.0,
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_event_registration_event_id` (`event_id`),
  INDEX `idx_event_registrations_event` (`event_id`),
  INDEX `idx_event_registrations_member_id` (`member_id`),
  CONSTRAINT `fk_event_registrations_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_registrations_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_event_registrations_member_id ON event_registrations(member_id);
CREATE INDEX idx_event_registrations_event_id ON event_registrations(event_id);
CREATE INDEX idx_event_reg_member ON event_registrations(member_id);

-- --------------------------------------------------------
-- Table: transactions
-- --------------------------------------------------------
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
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
  INDEX `idx_transactions_member_id` (`member_id`),
  INDEX `idx_transaction_booking_id` (`booking_id`),
  INDEX `idx_transactions_booking_id` (`booking_id`),
  INDEX `idx_transactions_member_created` (`member_id`, `created_at`),
  CONSTRAINT `fk_transactions_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transactions_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_transactions_member_id ON transactions(member_id);

-- --------------------------------------------------------
-- Table: settings
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key` VARCHAR(100) PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: staff
-- --------------------------------------------------------
DROP TABLE IF EXISTS `staff`;
CREATE TABLE `staff` (
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
-- Table: notifications
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
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
CREATE INDEX idx_notifications_member_id ON notifications(member_id);

-- --------------------------------------------------------
-- Table: activity_log
-- --------------------------------------------------------
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `tone` ENUM('forest','wait','danger','sand','gray') NOT NULL DEFAULT 'gray',
  `actor` VARCHAR(100) NOT NULL DEFAULT 'System',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

-- --------------------------------------------------------
-- Table: login_attempts (IP-based brute-force protection)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `attempt_time` INT NOT NULL,
  INDEX `idx_login_attempts_ip_time` (`ip`, `attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_login_attempts_ip_time ON login_attempts(ip, attempt_time);

SET FOREIGN_KEY_CHECKS = 1;
