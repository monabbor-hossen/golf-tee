-- ========================================================
-- Migration: 002_add_column.sql
-- Add password reset tokens, expiry columns, indexes, and FK constraints
-- ========================================================

USE `golf_admin`;

ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `reset_token` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `token_expires_at` DATETIME DEFAULT NULL AFTER `reset_token`,
  ADD COLUMN IF NOT EXISTS `password_reset_token` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `password_reset_expires` DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `setup_token` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `setup_token_expires` DATETIME DEFAULT NULL;

-- Foreign key constraints and indexes
ALTER TABLE `bookings`
  ADD INDEX IF NOT EXISTS `idx_member` (`member_id`),
  ADD CONSTRAINT `fk_bookings_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE;

CREATE UNIQUE INDEX IF NOT EXISTS `idx_members_email` ON `members`(`email`);
CREATE INDEX IF NOT EXISTS `idx_bookings_member_id` ON `bookings`(`member_id`);
CREATE INDEX IF NOT EXISTS `idx_bookings_status` ON `bookings`(`status`);
CREATE INDEX IF NOT EXISTS `idx_bookings_tee_date` ON `bookings`(`tee_date`);
