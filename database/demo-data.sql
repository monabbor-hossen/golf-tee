-- WARNING: demo-data.sql is for development only and must not be used in production.
-- A setup flag prevents demo data import in production environments.
-- ========================================================
-- GolfTee - Demo Member Data for Development / Testing
-- ========================================================

USE `golf_admin`;

-- Check setup environment flag before allowing demo import
-- In production, DEMO_MODE must be set to 0 to prevent unauthorized imports
SET @DEMO_ALLOWED = 1;

-- Demo Member Accounts (Development only)
-- Passwords must be reset immediately upon account activation.
