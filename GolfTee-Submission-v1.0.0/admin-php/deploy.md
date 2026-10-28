# 🚀 Deployment Guide: Live API & Production Setup

This guide provides step-by-step instructions for deploying the **Golf Tee Admin Backend** to a live production server, and switching the **Flutter Mobile App** from local development to your live production API.

---

## 📋 Table of Contents
1. [Architecture Overview](#1-architecture-overview)
2. [Security & CVE Remediations](#security--cve-remediations)
3. [Step 1: Deploy Backend & Database (`admin-php`)](#step-1-deploy-backend--database-admin-php)
4. [Step 2: Switch Mobile App from Local to Live API (`mobile_app`)](#step-2-switch-mobile-app-from-local-to-live-api-mobile_app)
5. [Step 3: Build & Release the Mobile App](#step-3-build--release-the-mobile-app)
6. [Step 4: Production Cron Job (1-Hour Weather Auto-Refresh)](#step-4-production-cron-job-1-hour-weather-auto-refresh)
7. [Troubleshooting & FAQs](#troubleshooting--faqs)

---

## Security & CVE Remediations
- **PHPMailer (CVE-2024-48679):** Remediated by pinning `phpmailer/phpmailer: ^6.9.3` in `composer.json` and `composer.lock`.
- **Vite (CVE-2024-45812):** Remediated by upgrading Vite to `^6.0.11` in `package.json` and `pnpm-lock.yaml`.
- **React Router Dom:** Pinned to `^7.1.1` for latest security and compatibility patches.
- **MySQL Container:** Standardized to `mysql:8.0.42` in `docker-compose.yml`.

---

## Database Migrations Process
Versioned SQL migrations are organized in `database/migrations/`:
- `001_initial_schema.sql`: Core schema table definitions.
- `002_add_column.sql`: Incremental token columns and security indexing.

To apply migrations manually or via CI/CD pipeline:
```bash
mysql -u root -p golf_admin < database/migrations/001_initial_schema.sql
mysql -u root -p golf_admin < database/migrations/002_add_column.sql
```

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Flutter Mobile App                       │
│    (Android APK/AAB, iOS, Web: mobile_app)                  │
└──────────────────────────────┬──────────────────────────────┘
                               │ HTTPS API Calls (JSON)
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                 Live PHP Backend (admin-php)                │
│   Endpoints: https://your-domain.com/admin-php/api/mobile.php│
│   Web Admin: https://your-domain.com/admin-php/             │
└──────────────────────────────┬──────────────────────────────┘
                               │ PDO MySQL Connection
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                 Production MySQL Database                   │
│   Database Name: golf_admin (Tables: members, bookings, etc)│
└─────────────────────────────────────────────────────────────┘
```

---

## Step 1: Deploy Backend & Database (`admin-php`)

### 1.1 Import Database
1. Open your production **phpMyAdmin** or terminal MySQL console.
2. Create a database (e.g. `golf_admin` or `yourcpaneluser_golf`).
3. Import the clean database schema and seed data:
   - **Database Schema:** [`database/schema.sql`](../database/schema.sql)
   - **Database Seed Data:** [`database/seed.sql`](../database/seed.sql)
   - **Combined Schema (Backend):** [`admin-php/database.sql`](database.sql)
   - Command line execution:
     ```bash
     mysql -u your_db_user -p your_db_name < database/schema.sql
     mysql -u your_db_user -p your_db_name < database/seed.sql
     # Or alternatively using the unified bundle:
     # mysql -u your_db_user -p your_db_name < admin-php/database.sql
     ```

### 1.2 Upload Files to Live Web Server
Upload the contents of `admin-php` to your web server (via FTP, cPanel File Manager, Git, or SSH).
- **Option A (Subdirectory):** `https://your-domain.com/admin-php/`
- **Option B (Subdomain - Recommended):** Point `api.your-domain.com` directly to the `admin-php` folder.

### 1.3 Configure Production Environment (`.env`)
In your live `admin-php/` directory, create or edit the `.env` file with your live credentials:

```ini
# Production Database Credentials
DB_HOST=localhost
DB_NAME=your_production_db_name
DB_USER=your_production_db_user
DB_PASS=your_production_db_password
DB_CHARSET=utf8mb4

# Live Application URLs
APP_NAME="Pine Valley Golf Club"
APP_URL=https://your-domain.com/admin-php

# WARNING: Default value is insecure! You MUST generate a production key:
# Run in terminal: openssl rand -base64 32
SESSION_SECRET=<run: openssl rand -base64 32>

# Mail / SMTP Configuration (for password resets & invites)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
# Generate at https://myaccount.google.com/apppasswords - NEVER commit real password
MAIL_PASSWORD=<generate-at-https://myaccount.google.com/apppasswords>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Pine Valley Golf Club"
```

> [!WARNING]
> Default or empty `SESSION_SECRET` values are strictly insecure and rejected in production mode. Always generate a unique random 32-byte secret using `openssl rand -base64 32`.

> [!IMPORTANT]
> - Always use **HTTPS** (`https://...`) in production.
> - Never commit your production `.env` with real database passwords to a public Git repository.

### 1.4 Set Directory Permissions
Make sure the server web user (`www-data`, `nobody`, or your cPanel user) has write permissions to:
```bash
chmod -R 775 uploads/
chmod -R 775 scratch/
```

### 1.5 Verify Live API Response
Test your live backend in a browser or terminal:
```bash
curl -s "https://your-domain.com/admin-php/api/mobile.php?action=weather"
```
**Expected Response:**
```json
{
  "status": "ok",
  "conditions": {
    "weather": "72°",
    "weather_desc": "Sunny",
    "wind": "5 mph",
    "updated_at": "Updated ..."
  }
}
```

---

## Step 2: Switch Mobile App from Local to Live API (`mobile_app`)

The mobile app has a centralized configuration file in [`mobile_app/lib/config/api_config.dart`](../mobile_app/lib/config/api_config.dart).

Choose either **Method A** (Configure in `api_config.dart`) or **Method B** (Command-line flag).

### Method A: Set `liveApiUrl` in Code (Simplest)

1. Open [`mobile_app/lib/config/api_config.dart`](../mobile_app/lib/config/api_config.dart).
2. Locate `liveApiUrl`:
   ```dart
   static const String liveApiUrl = '';
   ```
3. Change it to your live API endpoint:
   ```dart
   static const String liveApiUrl = 'https://your-domain.com/admin-php/api/mobile.php';
   ```

*When `liveApiUrl` is filled in, the app automatically prioritizes your live server over local IP addresses.*

---

### Method B: Build-time Flag `--dart-define` (Best for CI/CD)

You don't need to change any code! You can pass the live URL when running or compiling:

#### Run with live API:
```bash
flutter run --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
```

#### Build release APK with live API:
```bash
flutter build apk --release --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
```

---

## Step 3: Build & Release the Mobile App

Navigate into the `mobile_app` folder:
```bash
cd mobile_app
```

### 3.1 Build Android APK (for Direct Download or Testing)
```bash
flutter build apk --release
```
- **Output file:** `mobile_app/build/app/outputs/flutter-apk/app-release.apk`
- You can transfer this APK directly to any Android smartphone or tablet to install and test with live data.

### 3.2 Build Android App Bundle (for Google Play Store)
```bash
flutter build appbundle --release
```
- **Output file:** `mobile_app/build/app/outputs/bundle/release/app-release.aab`
- Upload this `.aab` file to your **Google Play Console**.

### 3.3 Build iOS (for Apple App Store / TestFlight)
```bash
flutter build ipa --release
```
- Requires a Mac with Xcode and an active Apple Developer account.

---

## Step 4: Production Cron Job (1-Hour Weather Auto-Refresh)

The admin backend features an automated 1-hour live weather caching cycle. To ensure conditions stay fresh 24/7 even when no admin is logged in:

### Setting up Linux / cPanel Cron Job
In your hosting control panel (cPanel -> **Cron Jobs**) or Linux server terminal (`crontab -e`):

Add this line to execute once every hour at minute 0:
```cron
0 * * * * curl -s "https://your-domain.com/admin-php/cron_weather.php" > /dev/null 2>&1
```

Or via direct PHP CLI:
```cron
0 * * * * /usr/bin/php /home/username/public_html/admin-php/cron_weather.php > /dev/null 2>&1
```

---

## 🔍 Troubleshooting & FAQs

### Q1: App shows "Network Error" or cannot connect after deployment
- **Solution 1:** Check if your live domain has a valid **SSL certificate** (`https://`). Android & iOS block insecure HTTP requests by default.
- **Solution 2:** Test the URL in your phone's web browser: `https://your-domain.com/admin-php/api/mobile.php?action=weather`. If the browser shows a JSON response, the backend is working.
- **Solution 3:** Ensure your `.htaccess` on the live server allows GET/POST requests without redirect loops.

### Q2: How do I switch back to local testing after deploying?
Simply set `liveApiUrl` back to empty:
```dart
static const String liveApiUrl = '';
```
The app will immediately revert to scanning local development addresses (`192.168.0.x`, `10.0.2.2`, `localhost`).

### Q3: Member password reset emails are not sending
- Check your live `admin-php/.env`.
- Ensure `MAIL_USERNAME` is your Gmail address and `MAIL_PASSWORD` is a **16-character Google App Password** (generated from [Google Account Security -> App Passwords](https://myaccount.google.com/apppasswords)), **not** your personal login password.

---

*Documentation created for Pine Valley Golf Club Mobile & Admin Suite.*
