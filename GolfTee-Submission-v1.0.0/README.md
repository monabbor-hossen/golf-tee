# ⛳ GolfTee — Golf Club Booking & Course Management System

> **A modern, full-stack golf course management platform & mobile tee-time booking app.**  
> Built with a responsive **PHP 8 + MySQL** Web Admin Panel and a cross-platform **Flutter (Riverpod)** mobile app for Android, iOS, and Web.

---

## 📌 Product Overview

**GolfTee** is a golf operations suite designed for modern golf clubs, resorts, pro shops, and their members. It replaces outdated spreadsheets and legacy systems with an interactive real-time visual tee sheet, dynamic twilight pricing, automatic GPS course weather tracking, caddie dispatch, smart waitlists, digital check-in QR passes, and in-app payment card vaulting.

---

## 👥 Who It Is For

- **Golf Clubs & Country Clubs:** Private, semi-private, and public golf courses requiring real-time tee time reservation and back-office management.
- **Golf Club Managers & Pro Shop Staff:** For managing daily tee sheets, blocking maintenance windows, tracking caddies, monitoring revenues, and broadcasting club bulletins.
- **Golfers & Club Members:** For booking 9- or 18-hole rounds in seconds, managing party members, tracking their USGA handicap progression, storing cards, and showing digital QR check-in passes at the clubhouse.

---

## 🌟 Key Features

### 📱 1. Golfer Mobile App (Flutter)
- **Interactive Tee Sheet Booking:**
  - 14-day horizontal scrollable calendar selector.
  - 9-hole vs 18-hole segment toggles.
  - Party size selector (1 to 4 players).
  - Time-of-day filters: *Morning*, *Midday*, *Twilight*.
  - Real-time slot chips (*Available*, *Selected*, *Waitlist*, *Booked*).
  - Dynamic pricing calculation with twilight discounts.
- **Smart Checkout & Card Vault:**
  - Tokenized card storage (Visa, Mastercard, Amex, Discover).
  - Instant Luhn-algorithm card validation.
  - Itemized price breakdown (Greens fee, cart fee, tax).
- **Digital QR Ticket Passes:**
  - High-contrast, scannable QR ticket pass for fast pro shop check-in.
- **Live Course Conditions & Weather:**
  - Real-time temperature, condition description, wind speed & direction.
  - Automated 1-hour background refresh cycle.
  - Dynamic toast notifications when weather updates while using the app.
  - Optional device GPS auto-detection.
- **Member Profile & Handicap Tracking:**
  - Member tier badge (*Gold*, *Silver*, *Standard*) and Member ID.
  - Interactive Handicap Index progression curve (*CustomPainter* chart).
  - Total rounds played, club spend, and verified best score.
- **Waitlist Notification System:**
  - In-app alerts when waitlisted tee times become available.
- **Club News & Announcements Feed:**
  - High-resolution banners for upcoming tournaments, clinics, and pro shop promotions.

### 🖥️ 2. Web Admin Panel (PHP 8 + MySQL)
- **Master Grid Tee Sheet:**
  - Full-day tee time matrix in 10-minute intervals.
  - 1-click slot blocking for maintenance or private tournaments.
  - Instant party and player detail inspection.
- **Member Management:**
  - Member roster with search, tier filters, and spending analytics.
  - Member status controls (*Active*, *Suspended*, *Pending*).
  - Automated email invitations with secure, single-use password setup tokens.
- **Caddie Roster & Dispatch:**
  - Track registered caddies, experience levels, ratings, and loop fees.
- **Waitlist Engine:**
  - Priority queue with automated slot offering when bookings cancel.
- **Financial Transactions & Ledger:**
  - Auditable transaction logs, payment methods, refund processing, and reference tracking.
- **Club News Publisher:**
  - Rich announcement manager with categorization, image previews, and instant publishing.
- **Course Settings & Pricing Matrix:**
  - Weekday, weekend, and twilight green fees.
  - Operating hours and tee interval settings.
  - Green speed stimpmeter slider & daily cart rule broadcasts.
  - SMTP mail server configuration with Gmail App Password support.
  - Automated hourly weather cron task (`cron_weather.php`).

---

## 🛠️ Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Mobile App** | Flutter 3.24.5, Dart 3.5.4, Riverpod 2.x, Provider 6.x, Material Design 3 |
| **Backend API & Admin** | Native PHP 8.2+, Clean MVC Architecture, PSR-4 Autoloading |
| **Database** | MySQL 8.0+ / MariaDB 10.3+, InnoDB, UTF8mb4 |
| **Security** | CSRF Tokens, PDO Prepared Statements (SQL-injection immune), `password_hash` bcrypt |
| **External APIs** | `wttr.in` (Free Live Weather), `ipwho.is` (GPS Geolocation), SMTP Mail |
| **Containerization** | Docker, Docker Compose (PHP 8.2 Apache + MySQL 8.0) |

---

## 📁 Repository Structure

```text
├── HowToSetup.md             # Step-by-step installation (VPS + Docker)
├── README.md                 # Product overview and documentation
├── CLAUDE.md                 # AI agent coding instructions & architecture map
├── AGENTS.md                 # Agent guidelines and project standards
├── CHANGELOG.md              # Semantic version history (starting at 1.0.0)
├── LICENSE-NOTE.md           # Commercial license & resale terms
├── docker-compose.yml        # One-command Docker development setup
├── .env.example              # Configuration template with placeholder values
│
├── admin-php/                # PHP Web Admin Panel & Mobile REST API
│   ├── api/mobile.php        # JSON REST API for Flutter Mobile App
│   ├── app/                  # Controllers, Services, Repositories, Core
│   ├── views/                # Admin views (Master Grid, Members, Settings)
│   ├── assets/               # Admin CSS, JavaScript, and icons
│   ├── cron_weather.php      # Hourly background weather refresh task
│   └── bootstrap.php         # Application bootstrapper & security headers
│
├── mobile_app/               # Cross-platform Flutter Mobile Application
│   ├── lib/                  # Dart source code (screens, providers, models)
│   ├── android/              # Native Android wrapper and manifest
│   ├── ios/                  # Native iOS wrapper and configuration
│   ├── pubspec.yaml          # Flutter dependencies & metadata
│   └── README.md             # Mobile setup, API URL config & build guide
│
├── database/                 # Database Scripts
│   ├── schema.sql            # Clean DDL schema (creates all tables & views)
│   └── seed.sql              # Rich demo data (members, bookings, cards, news)
│
└── docs/                     # Additional documentation
    └── API_DOCUMENTATION.md  # Detailed REST API endpoints reference
```

---

## 🔐 Initial Setup & First-Time Authentication

When first initializing the database using `database/seed.sql`, default demo accounts are provisioned for evaluation. Per security best practices, change seed credentials on initial deployment:

### 1. Staff Admin Access (`admin-php/login.php`)
- **URL:** `http://localhost/golf-tee/admin-php/` (or your live domain)
- **Default Owner Email:** `admin@pinevalley.com`
- **Setup & Password Change:** On first deployment, access `admin-php/set-password.php` or change your administrator password immediately upon first login following the setup instructions in [HowToSetup.md](HowToSetup.md#first-admin-login-setup).

### 2. Golfer Mobile App Testing (`mobile_app`)
- **Demo Member Accounts:** `james.carter@mail.com`, `mina.lee@mail.com`, `jon@gmail.com`
- **First-Time Setup:** Member passwords can be configured via admin invitation or password reset at `admin-php/set-password.php`. For local testing, seed configuration applies default evaluation credentials.

---

## Setup

### 1. Prerequisites & Version Requirements
Ensure your development environment or server meets the following minimum versions:
- **PHP:** `8.2.0` or higher (with `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `json` extensions enabled)
- **MySQL / MariaDB:** MySQL `8.0+` or MariaDB `10.3+` (InnoDB engine, `utf8mb4` charset)
- **Flutter SDK:** Flutter `3.24.5` (Dart `3.5.4`, pinned in `.flutter-version`)
- **Composer:** `2.x` (for PHP dependency validation)
- **Web Server:** Apache (with `mod_rewrite` enabled) or Nginx

---

### 2. Database Initialization
Initialize the `golf_admin` database by importing the DDL schema and demo seed data:

```bash
# 1. Create database and tables
mysql -u root -p < database/schema.sql

# 2. Populate demo seed data (members, staff, pricing, news, slots)
mysql -u root -p golf_admin < database/seed.sql
```
*(Combined standalone schema for hosting panels is also available at [`admin-php/database.sql`](admin-php/database.sql)).*

---

### 3. Backend Deployment (`admin-php`)
1. Copy `.env.example` to `admin-php/.env`:
   ```bash
   cp admin-php/.env.example admin-php/.env
   ```
2. Open `admin-php/.env` and configure your database credentials and secret:
   ```ini
   DB_HOST=localhost
   DB_NAME=golf_admin
   DB_USER=root
   DB_PASS=
   # Run: openssl rand -base64 32 and paste result here
   SESSION_SECRET=<run: openssl rand -base64 32>
   ```
3. For full deployment, virtual host setup, Apache/Nginx rules, and automated crontabs, see:
   👉 **[admin-php/deploy.md](admin-php/deploy.md)** and **[HowToSetup.md](HowToSetup.md)**.

---

### 4. Golfer Mobile App Setup (`mobile_app`)
1. Navigate into the mobile app directory and fetch dependencies:
   ```bash
   cd mobile_app
   flutter pub get
   ```
2. Run on your connected device, emulator, or Chrome:
   ```bash
   # Run against local backend
   flutter run

   # Or specify live production backend URL
   flutter run --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
   ```
3. For Android APK/AAB release builds and iOS packaging, see:
   👉 **[mobile_app/README.md](mobile_app/README.md)**.

---

## 🌐 Live Demo & Evaluation Credentials

- **Live Demo URL:** `https://mhtria.free.je/admin-php/` (Direct Admin Login: `https://mhtria.free.je/admin-php/login.php`)
- **Administrator Email:** `admin@pinevalley.com`
- **Manager Email:** `grace@pinevalley.com`
- **Front Desk Email:** `tom@pinevalley.com`
- **Mobile Golfer Accounts:** `james.carter@mail.com` (ID: 1), `mina.lee@mail.com` (ID: 2), `alex.morgan@mail.com` (ID: 15)

> **Browser Note:** When opening the free hosting domain (`https://mhtria.free.je/`) in any standard browser (Chrome, Edge, Firefox, Safari), the standard anti-bot verification completes automatically within 1 second and opens the login page.

---

## 🔐 Evaluation Accounts & Initial Password Configuration

The pre-loaded database seed (`database/seed.sql`) provides the following accounts for immediate evaluation:

### 1. Staff Admin Accounts (`admin-php/login.php`)
- **Owner / Superadmin:** `admin@pinevalley.com`
- **Manager:** `grace@pinevalley.com`
- **Head Pro:** `sjenkins@pinevalley.com`
- **Club Admin:** `dtorres@pinevalley.com`
- **Front Desk:** `tom@pinevalley.com`
- **First Login Password Change:** Seed accounts require password setup on first login per security best practices. See [HowToSetup.md](HowToSetup.md#first-admin-login-setup) for password setup instructions.

### 2. Golfer Mobile App Accounts (`mobile_app`)
- **Member 1 (James Carter):** `james.carter@mail.com` (Member ID: 1)
- **Member 2 (Mina Lee):** `mina.lee@mail.com` (Member ID: 2)
- **Member 3 (Alex Morgan):** `alex.morgan@mail.com` (Member ID: 15)

---

## 📄 License & Commercial Terms

Copyright © 2026. All rights reserved.  
Distributed under the commercial software license provided upon purchase on Vibe96.  
See [LICENSE-NOTE.md](LICENSE-NOTE.md) for full license details.
