-- ========================================================
-- GolfTee Migration: 002_add_security_tokens_and_indexes.sql
-- Add password reset tokens, setup tokens, indexes, and FK constraints
-- ========================================================

USE `golf_admin`;

-- Ensure members table has all token fields
ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `reset_token` VARCHAR(100) DEFAULT NULL COMMENT 'Single-use password reset token',
  ADD COLUMN IF NOT EXISTS `token_expires_at` DATETIME DEFAULT NULL AFTER `reset_token`,
  ADD COLUMN IF NOT EXISTS `password_reset_token` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `password_reset_expires` DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `setup_token` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `setup_token_expires` DATETIME DEFAULT NULL;

-- Additional performance indexes on foreign key and status columns
CREATE UNIQUE INDEX IF NOT EXISTS `idx_members_email` ON `members`(`email`);
CREATE INDEX IF NOT EXISTS `idx_bookings_member_id` ON `bookings`(`member_id`);
CREATE INDEX IF NOT EXISTS `idx_bookings_status` ON `bookings`(`status`);
CREATE INDEX IF NOT EXISTS `idx_bookings_tee_date` ON `bookings`(`tee_date`);
CREATE INDEX IF NOT EXISTS `idx_transactions_member_id` ON `transactions`(`member_id`);
CREATE INDEX IF NOT EXISTS `idx_waitlist_member_id` ON `waitlist`(`member_id`);
CREATE INDEX IF NOT EXISTS `idx_event_registrations_event_id` ON `event_registrations`(`event_id`);
CREATE INDEX IF NOT EXISTS `idx_blocked_slots_date_slot` ON `blocked_slots`(`tee_date`, `tee_slot`);
ALTER TABLE `members` ADD CONSTRAINT `chk_password_hash_length` CHECK (CHAR_LENGTH(`password_hash`) >= 60);
