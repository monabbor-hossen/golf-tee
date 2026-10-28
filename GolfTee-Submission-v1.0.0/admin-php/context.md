# Golf Tee Admin (PHP + MySQL) — Complete Application Context

> **System:** Pine Valley Golf Club — Course Operations & Administration  
> **Location:** `c:\xampp\htdocs\golf-tee\admin-php`  
> **Tech Stack:** PHP 8+ (Strict Types), MySQL/MariaDB (PDO), Vanilla ES6+ JavaScript, Vanilla CSS (Design Tokens)  
> **Architecture:** Clean Architecture (Domain-Driven, Layered / Hexagonal)

---

## 1. Executive Summary & Overview

The **Golf Tee Course Admin** is a enterprise-grade golf course management portal designed to streamline all daily clubhouse operations. It replaces static or disconnected spreadsheets with an integrated, secure, and reactive web application.

### Key Capabilities
- **Tee Sheet Management (Master Grid):** Dual-tee visual matrix (Hole 1 & Hole 10), slot blocking, real-time check-in, and party management.
- **Dynamic Pricing Engine:** Automatic calculation of 9-hole vs. 18-hole green fees, weekend premiums, and twilight discounts based on minutes-from-midnight slot calculations.
- **Waitlist Pipeline:** Automated queue prioritizing golfers for cancelled or newly opened tee times.
- **Tournament & Events Hub:** Event scheduling, registration caps, entry fee handling, and roster tracking.
- **Caddie Dispatch System:** Caddie directory, experience ratings, fees, and booking assignments tied directly to tee times.
- **Club News & Bulletins:** Editorial workflow for internal staff and club announcements with draft/published lifecycles.
- **Member Directory:** Member tier levels (Regular, Premium, VIP), handicap indexes, member balance, lifetime spend, and CSV exports.
- **Financial Ledger & Payments:** Full transaction accounting, payment statuses, partial/full refunds, and exportable ledgers.
- **Executive Analytics:** Revenue pacing, rounds played, utilization rate, hourly demand curves, and top spending members.
- **Club Settings & Staff Roles:** Course operating hours, base fees, twilight trigger times, and role-based staff accounts.

---

## 2. Clean Architecture & Request Pipeline

The application adheres strictly to Clean Architecture separation of concerns:

```text
HTTP Request (Browser / AJAX)
       │
       ▼
[Entrypoint Script]  (e.g., index.php, caddies.php, api/bookings.php)
       │  Initializes bootstrap.php (Security headers, Autoloader, Config, Session)
       ▼
[Presentation Layer: Controllers]  (App\Controllers\...)
       │  Validates Request, authenticates staff session, extracts typed input
       ▼
[Application Layer: Services]     (App\Services\...)
       │  Executes workflows, coordinates multiple repositories, manages transactions
       ├──► [Domain Layer: Pure Logic] (App\Domain\PricingCalculator, SlotFormatter)
       ▼
[Data Layer: Repositories]         (App\Repositories\...)
       │  100% Parameterized PDO queries against MySQL database
       ▼
[Presentation Layer: Views / JSON] (views/... or App\Core\Response::json)
       │  Escapes output via e() helper; renders layouts/header.php & layouts/footer.php
       ▼
HTTP Response (HTML Document / JSON Payload / CSV Stream)
```

---

## 3. Directory & File Dictionary

```text
admin-php/
├── .env / .env.example             # Database credentials and environment settings
├── bootstrap.php                   # Security headers, PSR-4 autoloader, config, helpers
├── database.sql                    # Base database schema, indexes, and seed records
├── caddie_migration.sql            # Caddie roster & booking tables schema + seed data
│
├── api/                            # Thin REST / AJAX JSON API Endpoints
│   ├── bookings.php                # Check-in, quick-booking, cancel slot
│   ├── news.php                    # News status toggles, quick edits
│   ├── notifications.php           # Mark notifications as read / read all
│   ├── payments.php                # Process manual payments & trigger refunds
│   ├── reports.php                 # Analytics metrics query by date range
│   └── search.php                  # Global instant search across members & bookings
│
├── app/
│   ├── Core/                       # Framework Primitives (Zero External Dependencies)
│   │   ├── Autoloader.php          # PSR-4 namespace-to-path resolver
│   │   ├── Config.php              # Environment parser and type-safe settings reader
│   │   ├── Database.php            # PDO singleton with strict prepared statement execution
│   │   ├── Request.php             # HTTP request wrapper (sanitization, verbs, inputs)
│   │   ├── Response.php            # HTTP response helper (redirects, json, csv streams)
│   │   ├── Session.php             # Secure session management, CSRF tokens, flash messages
│   │   └── View.php                # Template renderer with layout nesting and escaping
│   │
│   ├── Domain/                     # Pure Business Logic
│   │   ├── CurrencyHelper.php      # Consistent financial currency formatting
│   │   ├── PricingCalculator.php   # Dynamic green fee logic (weekend, twilight, 9/18 holes)
│   │   ├── SlotFormatter.php       # Time conversions (minutes from midnight <-> 12h/24h)
│   │   └── TimezoneHelper.php      # Course timezone handling
│   │
│   ├── Repositories/               # Data Access Objects (Strict Prepared Statements)
│   │   ├── ActivityRepository.php  # Audit trail log entries
│   │   ├── BlockedSlotRepository.php # Maintenance / blocked tee intervals
│   │   ├── BookingRepository.php   # Tee time reservation records and check-ins
│   │   ├── CaddieRepository.php    # Caddie profiles, availability, and assignments
│   │   ├── EventRegistrationRepository.php # Player registrations for events
│   │   ├── EventRepository.php     # Tournament schedules and formats
│   │   ├── MemberRepository.php    # Member directory, handicaps, tiers
│   │   ├── NewsRepository.php      # News articles and publication state
│   │   ├── NotificationRepository.php # System and operational alerts
│   │   ├── SettingRepository.php   # Key-value persistent settings in MySQL
│   │   ├── StaffRepository.php     # Staff accounts and bcrypt password authentication
│   │   ├── TransactionRepository.php # Financial ledger entries
│   │   └── WaitlistRepository.php  # Waitlist queues and promotion tracking
│   │
│   ├── Services/                   # Application Orchestration & Use Cases
│   │   ├── AuthService.php         # Authentication, credential verification, sessions
│   │   ├── BookingService.php      # Reservation booking, slot conflicts, auto-billing
│   │   ├── CaddieService.php       # Caddie scheduling, booking assignments, stats
│   │   ├── DashboardService.php    # Metric aggregation, alerts, daily operations
│   │   ├── EventService.php        # Tournament rosters, player caps, fee billing
│   │   ├── LayoutService.php       # Global badges, pending counts, unread alerts
│   │   ├── MemberService.php       # Member search, profile inspection, CSV exports
│   │   ├── NewsService.php         # Article lifecycle, category filters, publish states
│   │   ├── PaymentService.php      # Transaction queries, refunds, CSV ledger generation
│   │   ├── ReportService.php       # Revenue charts, utilization rates, member spend
│   │   └── WaitlistService.php     # Queue re-ordering and promotion into bookings
│   │
│   └── Controllers/                # Presentation Controllers
│       ├── ApiController.php       # Shared API base controller
│       ├── AuthController.php      # Sign-in and sign-out logic
│       ├── CaddieController.php    # Caddie directory and booking dispatcher
│       ├── DashboardController.php # Operational overview and quick check-in
│       ├── EventController.php     # Tournament management and registration
│       ├── MasterGridController.php# Tee sheet interactive grid and blocking
│       ├── MemberController.php    # Member management and profile viewing
│       ├── NewsController.php      # Newsroom and bulletin management
│       ├── PaymentController.php   # Financial ledger and refund controller
│       ├── ReportController.php    # Business intelligence and reporting
│       ├── SettingController.php   # Club configuration and staff roles
│       └── WaitlistController.php  # Waitlist controller
│
├── assets/
│   ├── css/
│   │   └── main.css                # Central design system (all tokens in :root)
│   ├── js/
│   │   └── app.js                  # Frontend client runtime (modals, drawer, AJAX, search)
│   └── img/
│       ├── favicon.svg             # Club crest favicon
│       └── logo.svg                # Brand emblem
│
├── views/                          # Clean PHP Presentation Templates
│   ├── layouts/
│   │   ├── header.php              # Global desktop sidebar, top searchbar, notifications
│   │   └── footer.php              # Mobile bottom navigation bar, toast container, script tag
│   ├── caddies.php                 # Caddie directory and tee-time assignment view
│   ├── dashboard.php               # KPI cards, quick actions, check-in queue
│   ├── events.php                  # Tournament list, player rosters, new event modal
│   ├── login.php                   # Staff authentication form
│   ├── mastergrid.php              # Dual-tee interactive matrix with slot blocking
│   ├── members.php                 # Member directory with search, filter, and drawer
│   ├── news.php                    # Newsroom list and article editor modal
│   ├── payments.php                # Ledger table with status filters and refund actions
│   ├── reports.php                 # Charts, utilization tables, and export options
│   ├── settings.php                # Course operating hours, staff list, rate configs
│   └── waitlist.php                # Waitlist priority queue and tee promotion
│
└── [Page Entrypoints]              # Thin root scripts directing to controllers
    ├── index.php                   # -> DashboardController::index
    ├── caddies.php                 # -> CaddieController::index
    ├── events.php                  # -> EventController::index
    ├── login.php                   # -> AuthController::login
    ├── logout.php                  # -> AuthController::logout
    ├── mastergrid.php              # -> MasterGridController::index
    ├── members.php                 # -> MemberController::index
    ├── news.php                    # -> NewsController::index
    ├── payments.php                # -> PaymentController::index
    ├── reports.php                 # -> ReportController::index
    ├── settings.php                # -> SettingController::index
    └── waitlist.php                # -> WaitlistController::index
```

---

## 4. Core Infrastructure (`App\Core`)

- **`Autoloader`**: Zero-overhead autoloader mapping `App\SubNamespace\Class` directly to `app/SubNamespace/Class.php`.
- **`Config`**: Parses `.env` or falls back to sensible defaults. Provides type-safe getters: `Config::get('KEY', 'default')`.
- **`Database`**: Implements a PDO Singleton connection configured with `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` and `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`. Includes transaction helper closures (`Database::transaction(callable)`).
- **`Request`**: Encapsulates `$_GET`, `$_POST`, `$_SERVER`, and raw `php://input` JSON. Provides typed sanitization helpers: `getInt()`, `getString()`, `getFloat()`, `isPost()`, `isAjax()`.
- **`Response`**: Standardized response factory for:
  - `Response::redirect(string $url)`
  - `Response::json(mixed $data, int $status = 200)`
  - `Response::csv(array $headers, array $rows, string $filename)` (streams with zero memory bloat)
- **`Session`**: Hardens session security using `HttpOnly`, `SameSite=Lax`, and session rotation. Manages CSRF token generation and validation, current logged-in staff state, and flash notifications (`success`, `error`, `info`).
- **`View`**: Handles view inclusion within the layout envelope (`layouts/header.php` and `layouts/footer.php`) and exposes contextual variables.

---

## 5. Domain Logic & Calculations (`App\Domain`)

### Slot Arithmetic (`SlotFormatter`)
Tee times are stored in the database as **minutes from midnight** (integers) to simplify indexing, comparison, and arithmetic:
- `420` = 07:00 AM (`7 * 60`)
- `780` = 01:00 PM (`13 * 60`)
- `1080` = 06:00 PM (`18 * 60`)

The `SlotFormatter` class provides conversion utilities:
- `toTime(420)` $\rightarrow$ `'07:00'`
- `toDisplayTime(420)` $\rightarrow$ `'7:00 AM'`
- `toMinutes('07:00')` $\rightarrow$ `420`

### Dynamic Pricing (`PricingCalculator`)
Green fee calculation accounts for multiple business rules:
1. **Base Rate:** Sourced from `settings.base_green_fee` (default: $85.00 for 18 holes; $50.00 for 9 holes).
2. **Weekend Multiplier:** If booking date falls on Saturday or Sunday, rate is multiplied by `1.25` (+25%).
3. **Twilight Rate:** If tee time is $\ge$ `twilight_time` (default: 3:00 PM / 900 minutes), rate is discounted by `0.70` (-30%).
4. **Member Discounts:** VIP members receive automatic discounts on green fees.

---

## 6. Database Schema & Data Models

### Core Tables
| Table | Description | Primary Key | Key Foreign Keys |
|---|---|---|---|
| `staff` | Clubhouse administrators, managers, front desk | `id` | None |
| `members` | Registered golfers with membership tiers & handicaps | `id` | None |
| `bookings` | Primary tee time reservations | `id` | `member_id` $\rightarrow$ `members(id)` |
| `blocked_slots` | Maintenance or closure blocks on tee times | `id` | None |
| `waitlist` | Golfers queued for full or high-demand slots | `id` | `member_id` $\rightarrow$ `members(id)` |
| `events` | Club tournaments and championships | `id` | None |
| `event_registrations` | Player sign-ups for club events | `id` | `event_id` $\rightarrow$ `events(id)`, `member_id` |
| `transactions` | Financial ledger records (Green fees, entry fees, refunds) | `id` | `booking_id`, `member_id` |
| `caddies` | Certified club caddies roster | `id` | None |
| `caddie_bookings` | Caddie round assignments and scheduled loops | `id` | `caddie_id` $\rightarrow$ `caddies(id)`, `member_id` |
| `news` | Articles, announcements, and club news | `id` | `author_id` $\rightarrow$ `staff(id)` |
| `activities` | Audit trail of all actions performed by staff | `id` | `staff_id` $\rightarrow$ `staff(id)` |
| `notifications` | System and operational event alerts | `id` | None |
| `settings` | Key-value application and course configurations | `id` (unique `setting_key`) | None |

---

## 7. Application Modules & Workflows

### 1. Dashboard (`index.php`)
- **Key Metrics:** Real-time summary cards: Today's Revenue, Total Rounds, Check-in Rate, Active Waitlist count.
- **Operational Status:** Live course condition widget (Open/Closed, Carts on Fairways rule).
- **Today's Tee Sheet Snapshot:** Quick view of active morning and afternoon flights.
- **Quick Check-In:** Instant check-in action via AJAX updating booking status to `Checked-in` and refreshing UI.

### 2. Master Grid / Tee Sheet (`mastergrid.php`)
- **Dual-Tee Matrix:** Simultaneous scheduling on Hole 1 (Front 9) and Hole 10 (Back 9).
- **Time Slots:** Default 10-minute intervals from opening (07:00 AM) to sunset.
- **Interactive Slots:** Displays player count (1 to 4 golfers), lead golfer name, round type (18 or 9 holes), and check-in indicator.
- **Slot Blocking:** Single-click toggle to block times for course maintenance, frost delays, or private outings.
- **Tee Time Booking Modal:** Allows staff to select member or walk-up guest, choose party size, assign holes, and calculate fees on the fly.

### 3. Waitlist System (`waitlist.php`)
- **Queue Prioritization:** Orders waitlisted players by desired time window and registration timestamp.
- **Direct Slot Promotion:** When a cancellation occurs, staff can click "Promote to Tee Sheet", which books the player and removes them from the waitlist in a single transaction.

### 4. Events & Tournaments (`events.php`)
- **Tournament Manager:** Tracks upcoming scrambles, invitationals, and medal plays.
- **Capacity Enforcement:** Enforces maximum player limits and calculates remaining roster slots.
- **Roster Management:** View registered golfers, handicap indexes, entry fee payment status, and tee allocations.

### 5. Caddie Management (`caddies.php`)
- **Roster Overview:** Caddie cards displaying rating, years of experience, specialty (greens reading, club selection), and status (`Available`, `Unavailable`, `On Leave`).
- **Assignment Matrix:** Assigns caddies to specific booking tee times, calculates caddie fees, and tracks total rounds looped.

### 6. Club News & Communications (`news.php`)
- **Article Manager:** Editorial dashboard for club announcements, course conditions, and tournament recaps.
- **Category Taxonomy:** Organizes bulletins into `Course Updates`, `Tournaments`, `Member News`, and `General`.
- **Publication States:** Instant switching between `Draft`, `Published`, and `Archived`.

### 7. Members Hub (`members.php`)
- **Member Directory:** Searchable by name, email, phone, or member ID.
- **Tier Badges:** Visual indicators for `Regular`, `Premium`, and `VIP` tiers.
- **Handicap & Spend Tracking:** Displays current GHIN/handicap index, account balance, and lifetime spend.
- **CSV Export:** Complete member database export formatted for external accounting or handicap systems.

### 8. Payments & Ledger (`payments.php`)
- **Unified Ledger:** Comprehensive transaction log of all green fees, cart rentals, event entries, and pro shop charges.
- **Status Filtering:** Filter by `Completed`, `Pending`, `Refunded`, or `Failed`.
- **One-Click Refunds:** Full or partial refund workflow that updates ledger records and recalculates daily revenue metrics.
- **Financial Export:** Direct CSV download of ledger for bookkeeping.

### 9. Business Analytics & Reports (`reports.php`)
- **Revenue Trends:** Visual charts showing daily, weekly, and monthly revenue pacing.
- **Tee Sheet Utilization:** Percentage utilization breakdown by morning, twilight, and weekend hours.
- **Member Spend Leaderboard:** Highlighting top spending members and frequent players.

### 10. Settings & Club Configuration (`settings.php`)
- **Operating Parameters:** Configure course opening time, sunset/closing time, and twilight boundary.
- **Financial Parameters:** Base green fees for 9 and 18 holes, weekend multipliers, and tax rates.
- **Staff Accounts:** Role management (`Admin`, `Manager`, `Front Desk`), account creation, and password resets.

---

## 8. Frontend Architecture & Design System

### Design Tokens (`assets/css/main.css`)
All styling is strictly managed via CSS custom properties in `:root`:
- **Forest Palette:** `--forest: #1E5631;`, `--forest-dark: #164523;`, `--sidebar-bg: #0f2418;`
- **Surface Tiers:** `--offwhite: #F7F7F5;`, `--white: #ffffff;`, `--bg-gray: #f3f4f6;`
- **Status Accents:** `--success: #4ade80;`, `--wait: #F4A261;`, `--danger: #DC2626;`
- **Sleek Scrollbars:** Custom 5px translucent scrollbars with zero background tracks or native arrow buttons (`--sidebar-scrollbar`, `--scrollbar-thumb`).

### JavaScript Runtime (`assets/js/app.js`)
- **Zero Framework Footprint:** Fast, lightweight native ES6+ JavaScript.
- **Modals:** Accessible modal manager with escape key, backdrop click, and focus trapping.
- **Notifications & Drawer:** Dropdown notification drawer and mobile slide-out navigation.
- **Toast Engine:** Asynchronous, auto-dismissing toast notifications (`showToast('Message', 'success')`).
- **Live Search:** Debounced real-time global search query against `/api/search.php`.

---

## 9. Security & Hardening Protocols

1. **SQL Injection Defense:** Zero raw query concatenation. 100% of database interactions run through PDO prepared statements with strongly typed bound parameters.
2. **Cross-Site Scripting (XSS) Prevention:** Output rendering uses the mandatory `e($value)` escaping helper (`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`).
3. **Cross-Site Request Forgery (CSRF):** All POST/PUT/DELETE forms and AJAX actions validate cryptographic CSRF tokens stored in the user session.
4. **Content Security Policy (CSP):** HTTP headers enforced in `bootstrap.php` restrict unauthorized script execution and frame embedding (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`).
5. **Password Encryption:** Staff passwords hashed using PHP's native `password_hash()` with `PASSWORD_DEFAULT` (bcrypt).

---

## 10. Developer Guide & Setup

### Requirements
- **PHP:** 8.1 or higher with `pdo_mysql`, `mbstring`, `json`, `session` extensions enabled.
- **MySQL / MariaDB:** 5.7+ or 10.3+.
- **Web Server:** Apache (via XAMPP / WampServer) or PHP built-in server.

### Installation Steps
1. Place the repository in your web root: `C:\xampp\htdocs\golf-tee\admin-php`
2. Create the database in MySQL:
   ```sql
   CREATE DATABASE golf_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the schemas and seeds:
   ```bash
   mysql -u root golf_admin < database.sql
   mysql -u root golf_admin < caddie_migration.sql
   ```
4. Verify `.env` matches your local database credentials:
   ```ini
   DB_HOST=localhost
   DB_NAME=golf_admin
   DB_USER=root
   DB_PASS=
   APP_URL=http://localhost/golf-tee/admin-php
   ```
5. Access the application in your browser:  
   **URL:** `http://localhost/golf-tee/admin-php/`  
   **Default Login:** `admin@pinevalley.com` / `password`

---

## 11. Mobile Application REST API (`api/mobile.php`)

To support the **Pine Valley Golf Club Mobile Application** (`mobile_app`, built in Flutter + Riverpod), a dedicated JSON REST API is provided at `api/mobile.php`.

### Key Features:
- **CORS Enabled:** Accepts cross-origin requests from emulators (`10.0.2.2`), web browsers (`localhost`), and physical mobile devices.
- **Direct Database Integration:** Directly queries and updates the `golf_admin` MySQL tables (`members`, `bookings`, `tee_slots`, `waitlist`, `news`, `handicap_history`).
- **Dynamic Pricing Integration:** Seamlessly utilizes `App\Domain\PricingCalculator` and `App\Domain\SlotFormatter`.
- **Fault-Tolerant Fallbacks:** If the database connection is interrupted, the API gracefully falls back to structured offline responses to ensure seamless mobile UI testing.

### Supported Endpoints:
- `GET ?endpoint=home`: Returns member profile (James Carter), next upcoming booking, live weather/course conditions (Stimp 10.5, Cart 90° Rule), and latest club news bulletins.
- `GET ?endpoint=slots&date=YYYY-MM-DD&holes=18`: Returns the full day of tee slots (Available, Booked, Locked, Waitlist) with real-time dynamic pricing.
- `POST ?endpoint=book`: Confirms a new tee time booking with player count, hole selection, cart/caddie options, and promo code discounts.
- `GET ?endpoint=bookings`: Returns lists of upcoming confirmed bookings, active waitlist entries, and past played rounds.
- `GET ?endpoint=booking_detail&id={id}`: Returns complete digital ticket data, QR code reference, weather forecast, and clubhouse contact.
- `POST ?endpoint=cancel_booking`: Cancels an upcoming booking and calculates the refund.
- `POST ?endpoint=join_waitlist`: Adds a golfer to the waitlist queue for a desired tee time slot.
- `POST ?endpoint=leave_waitlist`: Removes a golfer from the waitlist.
- `POST ?endpoint=save_score`: Records an 18-hole score, updates handicap differential, and returns the golfer's revised handicap index.

