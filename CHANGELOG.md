# 📜 Changelog

All notable changes to the **GolfTee** project will be documented in this file.  
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.4] - 2026-10-24

### 🛡️ Security Audit & CVE Remediations
- **Dependency CVE Audits & Security Remediations:**
  - **PHPMailer CVE-2024-48679 (Moderate XSS):** Remediated by upgrading `phpmailer/phpmailer` to `^6.9.3` in both `composer.json` and `admin-php/composer.json`, updating `composer.lock`.
  - **Vite CVE-2024-45812:** Updated `vite` in `package.json` to `^6.0.11` (with `pnpm-lock.yaml` pinned).
  - **React Router Dom:** Updated `react-router-dom` to `^7.1.1` to ensure latest stability and security fixes.
  - **MySQL Container:** Updated and pinned Docker image to `mysql:8.0.41`.
- **Authorization & Ownership Hardening:**
  - Added strict member ownership verification for booking details, payment methods, waitlist entries, and profile mutations.
  - Enforced role hierarchy verification preventing lower-privileged staff from modifying higher-privileged staff.
  - Implemented token age validation (30-day expiry) and rate limiting on mobile API endpoints using `login_attempts`.
  - Added single-use token invalidation and 24-hour expiry check on `set-password.php`.
- **Database Schema Hardening:**
  - Added unique index `idx_members_email` on `members(email)`.
  - Added indexes `idx_bookings_status` and `idx_bookings_tee_date` on `bookings`.
  - Configured `ON DELETE SET NULL` on `transactions(member_id)` and `ON DELETE CASCADE` on `waitlist(member_id)` and `notifications(member_id)`.
  - Scoped `login_attempts` cleanup queries by IP and timestamp.
  - Moved demo member seed queries from `schema.sql` to separate `demo-data.sql`.

---

## [1.0.1] - 2026-10-23

### 🛡️ Security, Performance & Quality Hardening (Audit v1.0)
- **Secrets & Credentials:**
  - Removed all default placeholder secrets from `.env.example` and `admin-php/.env.example` with instructions to generate cryptographically secure keys via `openssl rand -hex 32`.
  - Removed all credential hints from SQL files (`database/seed.sql`, `admin-php/database.sql`, `infinityfree_database.sql`).
  - Added `password_must_change = 1` flag for initial staff accounts to enforce first-login password rotation.
- **API Authorization & Access Control:**
  - Implemented strict authentication checks returning `401 Unauthorized` on missing tokens/credentials for protected endpoints (`book`, `cancel_booking`, `profile`, `cards`, `bookings`, `waitlist`).
  - Implemented strict member-ownership authorization returning `403 Forbidden` if a member attempts to access or mutate another member's resources.
  - Enforced POST-only methods on authentication and mutation endpoints (`login`, `forgot-password`, `set-password`).
  - Added rate limiting (max 5 requests/hour per IP) on password reset endpoints.
- **Database Optimization:**
  - Added missing indexes: `idx_events_created_by`, `idx_transactions_member_id`, `idx_transactions_booking_id`, and `idx_waitlist_member_id`.
  - Resolved N+1 queries in `BookingRepository` and `EventRepository` by joining related tables.
- **Dependencies & Reproducibility:**
  - Added standard `composer.json` and `composer.lock` with PSR-4 autoloading definitions and required PHP extensions (`pdo`, `mbstring`).
  - Pinned Flutter SDK environment (`>=3.22.0 <3.48.0`) and created `.flutter-version` pinning `3.47.7`.
  - Cleaned up Flutter analyzer warnings to achieve 0 analyzer issues.
- **Architecture & Clean Code:**
  - Created standalone `TokenGenerator` service producing 64-char cryptographically secure tokens.
  - Extracted weather background tasks into reusable `SchedulerService`.
  - Removed obsolete `admin-php/scratch/` directory and updated `.gitignore`.

---

## [1.0.0] - 2026-10-22

### 🎉 Initial Commercial Release

#### 🖥️ Web Admin Panel (`admin-php`)
- **Master Grid Tee Sheet:** Real-time visual 10-minute interval tee matrix with quick-block, unblock, party inspection, and modal booking management.
- **Member Management:** Member roster with search, tier categorization (*Gold*, *Silver*, *Standard*), status controls (*Active*, *Suspended*, *Pending*), and spending analytics.
- **Invitation Engine:** Automated member onboard workflow sending customized invitation emails with secure, single-use password setup tokens.
- **Club News Publisher:** Rich announcement manager with categorization, image previews, and published status toggles.
- **Caddie Roster & Dispatch:** Tracking of registered club caddies, experience levels, ratings, loop fees, and availability.
- **Waitlist Operations:** Priority waitlist queue with automated priority offering when slots open.
- **Financial Ledger & Reports:** Transaction logging with invoice codes, payment methods, refund processing, and revenue summaries.
- **Course Settings & Pricing Matrix:** Full control over weekday, weekend, and twilight green fees, operating hours, interval step, green speed stimpmeter, and cart rules.
- **Automated Weather System:** 1-hour auto-refreshing live course weather engine powered by `wttr.in` with GPS geolocation and standalone `cron_weather.php` script.
- **Security:** Full CSRF token validation on mutating actions, PDO prepared statements, `password_hash` bcrypt security, and Content-Security-Policy headers.

#### 📱 Golfer Mobile App (`mobile_app`)
- **Flutter & Riverpod 3 Architecture:** Reactive, high-performance state management using `NotifierProvider`.
- **Interactive Tee Sheet:** 14-day horizontal date selector, 9 vs 18 holes selector, 1-4 player party counter, time-of-day filters (*Morning*, *Midday*, *Twilight*), and dynamic slot chips.
- **Checkout & Payment:** In-app saved card management with Luhn validation, itemized fee summary, and instant reservation confirmation.
- **Digital QR Ticket Passes:** Scannable check-in passes with dynamic high-contrast QR generation.
- **Live Course Weather:** Real-time temperature, condition icon, and wind speed widget with live change notifications.
- **Member Profile & Handicap Curve:** Custom handicap progression trend line chart, rounds played, and club spend metrics.
- **Waitlist Alerts:** In-app notification banner when waitlisted tee times become available.
- **Multi-platform Support:** Compiles to Android APK/AAB, iOS IPA, and responsive Web.

#### 🗄️ Database & DevOps
- **Complete Relational Schema (`database/schema.sql`):** Clean, foreign-key linked schema for MySQL 8.0+ and MariaDB.
- **Comprehensive Demo Data (`database/seed.sql`):** Pre-loaded with members, cards, bookings, caddies, news, events, and transactions.
- **Docker Support (`docker-compose.yml`):** Zero-config containerized deployment for PHP 8.2 Apache + MySQL 8.0 + phpMyAdmin.
