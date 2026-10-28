# 📜 Changelog

All notable changes to the **GolfTee** project will be documented in this file.  
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-10-26

### 🛡️ Quality Audit & Warning Remediation (Audit Report Run #1034 - 100/100 Target)
- **Secrets & Credentials (Section 1):**
  - Standardized `.env.example` placeholders with explicit generation commands (`CHANGE_ME_RUN_openssl_rand_base64_32`, `CHANGE_ME_RUN_openssl_rand_base64_48`, `CHANGE_ME_GOOGLE_APP_PASSWORD`).
  - Removed all hard-coded credentials from setup guides and documentation; documented first-login setup workflow via `admin-php/set-password.php`.
- **Dependencies (Section 2):**
  - Pinned Flutter SDK constraint to `>=3.24.0 <4.0.0` with `.flutter-version` locked to `3.24.5`.
  - Cleaned Flutter package dependencies (`http: ^1.2.2`, `provider: ^6.1.2`, `qr_flutter: ^4.1.0`).
  - Updated web frontend dependencies in `package.json` (`react ^19.0.0`, `react-router-dom ^7.2.0`, `vite ^6.2.0`, `typescript ^5.7.3`).
- **Authorization & Access Control (Section 3):**
  - Scoped mobile `notifications` endpoint to authenticated member (`WHERE member_id = ?`).
  - Consolidated mobile REST action routing into `MobileApiController::dispatch()`.
- **Database Access Rules (Section 4):**
  - Added indexes in `database/schema.sql`: `idx_bookings_member_date`, `idx_waitlist_member`, `idx_waitlist_date_holes`, and `idx_event_reg_member`.
- **Architecture & Code Structure (Section 5):**
  - Separated environment file loading into single-responsibility `EnvLoader` class; streamlined `Config` as read-only accessor.
  - Organized PHP services into domain subdirectories (`Booking/`, `Payment/`, `Member/`, `Reports/`, `Auth/`, `Api/`) with PSR-4 autoloader backward compatibility.
  - Purged internal audit comments and ticket references across all shipped code.
- **Setup & Build Reproducibility (Section 6):**
  - Added explicit prerequisites for Flutter SDK, Android Studio Hedgehog (2023.1.1+), and Gradle 8.0+.
  - Documented `API_BASE_URL` setup steps before `flutter pub get`.
  - Added `.env` generation checks before Docker and XAMPP deployments in `HowToSetup.md`.

## [1.0.8] - 2026-10-25

### 🛡️ Complete Audit Fixes (Audit Report Run #968 - 100/100 Target)
- **Dependency Vulnerabilities & Versions (Section 2 & 6.1):**
  - Updated Flutter SDK constraint in `pubspec.yaml` to `sdk: ">=3.0.0 <4.0.0"` and `flutter: ">=3.24.0"`.
  - Updated Flutter dependencies to stable releases: `http: ^1.2.0`, `shared_preferences: ^2.3.2`, `provider: ^6.1.2`, and `qr_flutter: ^4.1.0`.
  - Regenerated `pubspec.lock` with zero analyzer warnings or CVE vulnerabilities.
  - Verified `phpmailer/phpmailer` at `6.9.3` in `composer.lock` to remediate CVE-2024-4603.
- **Authorization & Access Control (Section 3):**
  - Added strict ownership validation in `ApiController::handleNotifications` and `ApiController::handlePayments` (`action=history`).
  - Added `requireOwnership($memberId, 401)` guard to mobile `notifications()` endpoint.
  - Enforced validated `$authId` consistency across all member queries in `home()`.
  - Added role-level checks within each report action (`Session::getRoleLevel() >= Session::getRoleLevel('manager')`).
  - Replaced loose OR role checks with clean role hierarchy enforcement in `MemberController` and `MemberActionHandler`.
  - Enforced `Session::requireStaff()` in `BaseApiController::requireOwnership()`.
- **Database Access Rules (Section 4):**
  - Enforced `DELETE FROM members WHERE id = ? AND id > 0` with validation in `MemberService` and `MemberRepository`.
  - Added temporal scoping `created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)` on `UPDATE notifications SET is_read = 1`.
  - Added indexes: `idx_bookings_member_id`, `idx_transactions_member_id`, `idx_waitlist_member_id`, `idx_event_registrations_event_id`, and `idx_blocked_slots_date_slot`.
  - Added `chk_password_hash_length` CHECK constraint on `members.password_hash` column.
  - Added whitelist validation on `$sort` parameter in `BookingRepository`.
  - Added `AND member_id = ?` scoping to booking cancellations.
  - Added `LIMIT 100` to member transaction history queries.
- **Code Structure & Duplication (Section 5):**
  - Consolidated booking validation into `BookingValidator` used by `BookingService`.
  - Consolidated payment processing into `PaymentProcessor` used by `PaymentService`.
  - Split profile logic into `MemberProfileService`, `HandicapCalculationService`, and `ScorecardService`.
  - Created `MemberActionHandler` extracting inline actions from `MemberController`.
  - Consolidated member authentication and token refresh into `AuthService::authenticateAndRefreshToken`.
  - Created unified `RateLimiter` with configurable limits per operation type.
  - Consolidated password extraction and constants into `PasswordExtractor`.
  - Created `EmailTemplateRenderer` for centralized template rendering with context variables.
  - Created `OwnershipValidator` for reusable ownership validation.
  - Split session responsibilities into `SessionManager`, `CsrfValidator`, and `FlashMessageBag`.
  - Extracted `AppHttpClient`, `BookingApiClient`, `PaymentApiClient`, and `ProfileApiClient` in mobile app.
- **Build Reproducibility (Section 6):**
  - Restructured `HowToSetup.md` into distinct mutually exclusive sections: `## Option A: Docker Setup (Recommended)` and `## Option B: Manual XAMPP Setup`.
  - Documented migration execution order in `HowToSetup.md`.
  - Cross-referenced `mobile_app/README.md` and added API URL configuration instructions.
  - Updated SESSION_SECRET generation command to `openssl rand -base64 48` (64+ chars).
  - Added critical warning regarding secret regeneration before `docker-compose up`.
  - Added `## Production Deployment` section referencing `admin-php/deploy.md`.

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
  - Pinned Flutter SDK environment (`>=3.24.0 <4.0.0`) and created `.flutter-version` pinning `3.24.5`.
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
