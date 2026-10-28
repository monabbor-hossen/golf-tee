# 🚀 HowToSetup: Complete Installation & Deployment Guide

This document provides step-by-step installation instructions for setting up **GolfTee** on two deployment targets:
1. **Target 1: VPS / Traditional Server (Ubuntu, Apache/Nginx, XAMPP, or cPanel)**
2. **Target 2: Docker & Docker Compose (Zero-Config Container Setup)**

Followed by **Database Setup**, **First Admin Login**, **Mobile App Configuration**, and **Branding Changes**.

---

## ⚙️ Step 1: Environment Configuration

Before installing prerequisites or starting services, configure your environment files:
1. Copy the example configuration template:
   ```bash
   cp .env.example .env
   cp .env.example admin-php/.env
   ```
2. Generate a secure `SESSION_SECRET` (minimum 32 characters):
   ```bash
   openssl rand -base64 32
   ```
   Generate SESSION_SECRET: `openssl rand -base64 32` and paste into `.env` `SESSION_SECRET=<value>` (minimum 32 chars required per `Config.php`).
3. Generate secure MySQL passwords for Docker (minimum 16 characters):
   ```bash
   openssl rand -base64 24
   ```
   Create `.env` in project root with `MYSQL_ROOT_PASSWORD` and `MYSQL_PASSWORD` (minimum 16 chars, use `openssl rand -base64 24`) before `docker-compose up`.

---

## 📋 System Requirements & Prerequisites

### 1. Backend Requirements
- **PHP Version Verification:** Run `php -v` to confirm PHP 8.2.0 or higher is installed (`php -v` must show 8.2.0 or higher. XAMPP 8.2.12 recommended).
- **PHP Extensions:** `pdo_mysql`, `curl`, `json`, `mbstring`, `openssl`
- **Database:** MySQL 8.0+ or MariaDB 10.3+
- **Web Server:** Apache (with `mod_rewrite` enabled) or Nginx
- **Composer:** For installing backend dependencies: `cd admin-php && composer install`

### 2. Install Flutter SDK
- **Flutter SDK 3.22.0 (install via https://docs.flutter.dev/get-started/install or use fvm: fvm install 3.22.0 && fvm use 3.22.0)**
- **Extract SDK:** Extract to `/opt/flutter` (Linux/macOS) or `C:\flutter` (Windows)
- **Configure PATH:** Add `/opt/flutter/bin` or `C:\flutter\bin` to your system `PATH`
- **Verify Installation:** Run `flutter doctor` to confirm toolchain readiness
- **Flutter SDK Version:** Version 3.22.0 (pinned in `mobile_app/.flutter-version`)
  ```bash
  cd mobile_app && flutter --version
  ```
- **Android Studio / Xcode:** For compiling to physical devices or emulators

---

## 🎯 Target 1: Traditional Server Setup (VPS / Ubuntu / XAMPP / cPanel)

### Step 1: Upload / Clone Project
Extract the zip archive or clone the repository to your web server root:
- **Ubuntu/Debian Apache:** `/var/www/html/golf-tee`
- **XAMPP (Windows):** `C:\xampp\htdocs\golf-tee`
- **cPanel:** `/home/username/public_html/golf-tee`

After extraction, verify the folder structure contains: admin-php/, mobile_app/, database/, docker-compose.yml, and README.md. If missing, re-extract the archive.

### Step 2: Configure Environment Variables (.env)
Copy `.env.example` to `.env` in both the project root and `admin-php/` directory:
```bash
cp .env.example .env
cp .env.example admin-php/.env
```

Generate SESSION_SECRET with:
```bash
openssl rand -base64 32
```
Paste the generated output as `SESSION_SECRET` in both `.env` files.

**Environment Validation Step:**
Verify `.env` does not contain unconfigured placeholders:
```bash
# Verify .env for placeholder values - exits with error if found
if grep -E "<run:|<your-" .env; then echo "Error: Unresolved placeholders in .env"; exit 1; fi
```

### Step 2.5: Install PHP Dependencies (Composer)
Run dependency installation in `admin-php/`:
```bash
cd admin-php && composer install
```
> **Note:** Root composer.json is optional. Production deployments should run composer install only in `admin-php/`.

**Verify Dependencies:**
`ls admin-php/vendor/phpmailer/phpmailer/src/PHPMailer.php` should exist:
```bash
ls admin-php/vendor/phpmailer/phpmailer/src/PHPMailer.php
```

### Step 3: Create MySQL Database & Import Clean Schema
Import database/schema.sql, then database/seed.sql (production) OR database/demo-data.sql (demo with sample bookings). Do not import both seed files.

> **IMPORTANT:** Buyers must run password reset for all seeded accounts before production deployment.

Execute the explicit MySQL command sequence:
```bash
mysql -u root -p -e "DROP DATABASE IF EXISTS golf_admin; CREATE DATABASE golf_admin;"
mysql -u root -p golf_admin < database/schema.sql
mysql -u root -p golf_admin < database/seed.sql
```

### Step 3.5: Generate Secure Session Secret
Generate a secure session secret:
```bash
openssl rand -base64 32
```
Paste the output as `SESSION_SECRET` in your `.env` file. This is mandatory for security before first run.

Edit `admin-php/.env` with your actual database and server details:
```ini
DB_HOST=localhost
DB_NAME=golf_admin
DB_USER=your_db_user
DB_PASS=your_db_password
DB_CHARSET=utf8mb4

APP_NAME="Pine Valley Golf Club"
APP_URL=http://localhost/golf-tee/admin-php
# Paste your generated 32-character key below:
SESSION_SECRET=<run: openssl rand -base64 32>

# SMTP Email Configuration (Optional - for member invitations)
# Obtain a 16-character Google App Password from https://myaccount.google.com/apppasswords
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=<your-google-app-password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Pine Valley Golf Club"
```

### Step 4: Set Directory Permissions
Ensure the web server user has write permissions to runtime folders:
```bash
chmod -R 775 admin-php/uploads/
```

### Step 5: Test Web Admin in Browser
Verify Apache mod_rewrite is enabled: `sudo a2enmod rewrite && sudo systemctl restart apache2` (Linux) or check `httpd.conf` `LoadModule rewrite_module` (Windows XAMPP).

Visit your admin panel URL:
```
http://your-server-ip/golf-tee/admin-php/
```
*(or `http://localhost/golf-tee/admin-php/` on local XAMPP)*

**Default Admin Credentials:**
- Email: `admin@pinevalley.com`
- Password: `${MYSQL_ROOT_PASSWORD}` (configured in your `.env` file; default demo seed password: `password123`)

## 🚀 Production Deployment
For production hosting (cPanel, VPS, cloud), see [admin-php/deploy.md](admin-php/deploy.md) for detailed deployment instructions.

---

## 🐳 Target 2: Docker & Docker Compose Setup

A ready-to-run `docker-compose.yml` is included in the project root for one-command deployment.

### Step 1: Configure Environment Variables
Create .env in project root with MYSQL_ROOT_PASSWORD and MYSQL_PASSWORD (minimum 16 chars, use openssl rand -base64 24) before docker-compose up:
```bash
cp .env.example .env
```
Generate credentials:
```bash
openssl rand -base64 24
```
Configure `.env`:
```ini
MYSQL_ROOT_PASSWORD=<generated-base64-secret>
MYSQL_PASSWORD=<generated-base64-secret>
```

### Step 2: Start Containers
From the project root, run:
```bash
docker-compose up -d
```

This starts:
- **`golf-web`:** PHP 8.2 with Apache, PDO MySQL, and GD extensions (Port `8080`)
- **`golf-db`:** MySQL 8.0.42 server with automated database initialization (Port `3306`)
- **`golf-phpmyadmin`:** Web database manager (Port `8081`)

### Step 2.5: Verify API Reachability
Test API: `curl http://localhost:8080/admin-php/api/mobile.php?action=weather` (expect `{"status":"ok"}`)

### Step 3: Access the Application
- **Web Admin Panel:** [http://localhost:8080/admin-php/](http://localhost:8080/admin-php/)
- **phpMyAdmin Database Browser:** [http://localhost:8081/](http://localhost:8081/)
  - *User:* `root`
  - *Password:* `${MYSQL_ROOT_PASSWORD}` (configured in your root `.env` file)

---

## 🔑 First Admin & Member Login

### 1. Web Admin Panel Credentials
- **URL:** `http://your-domain/admin-php/login.php`
- **Email:** `admin@pinevalley.com`
- **Password:** `password123`
- **Role:** Owner (Full system access)

### 2. Mobile App (Member Login)
- **Email:** `mdmonabborhossen@gmail.com`
- **Password:** `password123`
*(Alternative demo member: `james.carter@mail.com` / `password123`)*

---

## 📱 Mobile App Setup & Running

The Flutter mobile application is located in `mobile_app/`.

### Step 3.5: Install Flutter SDK
Install Flutter SDK 3.22.0 from https://docs.flutter.dev/get-started/install, verify with flutter --version, ensure it matches .flutter-version file.

### Step 4: Install Dependencies & Verify
```bash
cd mobile_app
flutter pub get
```
Verify with: flutter doctor -v (should show no errors for Flutter, Android toolchain, and connected devices)

### Step 2: Point Mobile App to your API
Open `mobile_app/lib/services/api_service.dart`:

- **For Local Testing:**
  By default, `api_service.dart` automatically scans and resolves local network addresses (`http://10.0.2.2/` for Android Emulators, `http://localhost/` for Web/Desktop, and `http://192.168.0.x/` for physical Wi-Fi devices).
  > **Note:** For local development, ApiService automatically uses `http://localhost/golf-tee/admin-php/api/mobile.php`. Override with `--dart-define=API_URL=...` if needed.

- **For Production / Live Deployment:**
  Set line 33 in `mobile_app/lib/services/api_service.dart`:
  ```dart
  static const String liveApiUrl = 'https://your-domain.com/admin-php/api/mobile.php';
  ```
  *Or pass it during build without modifying code:*
  ```bash
  flutter build apk --release --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
  ```

Restore dependencies: `cd mobile_app && flutter pub get`

### Step 3: Run the App
List available devices: `flutter devices`, then run: `flutter run -d <device-id>` or `flutter run -d chrome for web`:
- **Android Emulator / Phone:** `flutter run -d android`
- **Chrome / Web Browser:** `flutter run -d chrome`
- **Windows Desktop:** `flutter run -d windows`

---

## 🎨 Branding & Customization Changes

### 1. Course Name, Phone & Address
Log into the Web Admin Panel, go to **Settings ➔ General**:
- Change **Course name**, **Phone**, **Club address**, and **Timezone**.
- Changes immediately take effect on all admin pages and mobile app screens.

### 2. Green Fees & Twilight Pricing
Go to **Settings ➔ Pricing**:
- Customize 18-Hole and 9-Hole rates for **Weekdays**, **Weekends**, and **Twilight**.
- Set the **Twilight start time** (e.g., 3:00 PM). The mobile app automatically applies the discount to afternoon tee times.

### 3. Course Weather Location & Stimpmeter
Go to **Settings ➔ Course**:
- Enter your golf club city or click **"Detect Live GPS"**.
- Set the current **Green speed** (Stimpmeter pace) and **Cart rules** (*Open*, *90-Degree Rule*, *Cart Path Only*).

### 4. Mobile App Colors & Theme
To change mobile app brand colors, edit `mobile_app/lib/constants/colors.dart`:
```dart
static const Color forest = Color(0xFF1E5631); // Primary Brand Green
static const Color sand   = Color(0xFFE8DCC4); // Secondary Accent
static const Color gold   = Color(0xFFD4AF37); // Member Badge Gold
```

### 5. App Icons
- **Android App Icons:** `mobile_app/android/app/src/main/res/mipmap-*/ic_launcher.png`
- **iOS App Icons:** `mobile_app/ios/Runner/Assets.xcassets/AppIcon.appiconset/`
- Replace these assets with your custom club crest or logo.

---

## 🔑 Demo Credentials (Live Demo & Local Testing)

The pre-loaded database seed (`database/seed.sql`) provides the following accounts for immediate testing:

### 1. Staff Admin Accounts (`admin-php/login.php`)
- **Owner / Superadmin:** `admin@pinevalley.com` / `password123`
- **Head Pro:** `sjenkins@pinevalley.com` / `password123`
- **Club Admin:** `dtorres@pinevalley.com` / `password123`

### 2. Golfer Mobile App Accounts (`mobile_app`)
- **Member 1 (James Carter):** `james.carter@mail.com` / `password123` (Member ID: 1)
- **Member 2 (Mina Lee):** `mina.lee@mail.com` / `password123` (Member ID: 2)
- **Member 3 (Monabbor Hossen):** `mdmonabborhossen@gmail.com` / `password123` (Member ID: 15)

---

## ⏱️ Hourly Weather Cron Setup

To ensure live course weather auto-refreshes 24/7 on your server:

Add a cron job in cPanel or your Linux server (`crontab -e`):
```cron
0 * * * * curl -s "https://your-domain.com/admin-php/cron_weather.php" > /dev/null 2>&1
```

---

*Need assistance? Contact our technical team via the Vibe96 buyer support portal.*
