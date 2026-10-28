# 🚀 HowToSetup: Complete Installation & Deployment Guide

Choose ONE: Docker (recommended) OR Manual XAMPP.

This document provides step-by-step installation instructions for setting up **GolfTee**:
- **Option A: Docker Setup (Recommended)**
- **Option B: Manual XAMPP Setup**

Followed by **Database Setup**, **Mobile App Setup**, **Production Deployment**, and **First Admin Login Setup**.

---

## 📋 System Prerequisites
- **PHP:** PHP 8.2+ with `pdo_mysql`, `curl`, `mbstring`, `openssl`, and `gd` extensions enabled.
- **MySQL / MariaDB:** MySQL 8.0+ or MariaDB 10.4+.
- **Flutter SDK:** Flutter SDK 3.24.5 or compatible 3.24.x (see `mobile_app/.flutter-version`).
- **For Android builds:** Android Studio Hedgehog (2023.1.1) or later, or Gradle 8.0+.
- **Composer:** Composer 2.x for PHP backend dependencies.
- **Docker & Docker Compose:** (Optional) Docker 20.10+ and Docker Compose v2 for containerized deployment.

---

## ⚙️ Step 1: Environment Configuration

Before installing prerequisites or starting services, configure your environment files:
1. Copy the example configuration template:
   ```bash
   cp .env.example .env
   cp .env.example admin-php/.env
   ```
2. Generate a secure `SESSION_SECRET` (minimum 64 characters):
   ```bash
   openssl rand -base64 48
   ```
   Generate SESSION_SECRET: `openssl rand -base64 48` (produces 64 chars) and paste into `.env` `SESSION_SECRET=<value>` (minimum 64 chars required per `Config.php`).
3. Generate secure MySQL passwords (minimum 32 characters):
   ```bash
   openssl rand -base64 32
   ```

---

## Option A: Docker Setup (Recommended)

A ready-to-run `docker-compose.yml` is included in the project root for zero-config containerized setup.

**⚠ CRITICAL: Run openssl rand -base64 32 for MYSQL_ROOT_PASSWORD and MYSQL_PASSWORD in .env BEFORE docker-compose up**

### Step 1: Configure Environment Variables
Create `.env` in project root before starting containers:
```bash
cp .env.example .env
```
Generate credentials:
```bash
openssl rand -base64 32
```
Configure `.env`:
```ini
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 32) # Run this command to generate
MYSQL_PASSWORD=$(openssl rand -base64 32) # Run this command to generate
SESSION_SECRET=$(openssl rand -base64 48) # Run this command to generate
```

### Step 2: Start Containers
Verify `.env` file exists in project root with all secrets generated before running `docker-compose up -d`.

From the project root, run:
```bash
docker-compose up -d
```

This starts:
- **`golf-web`:** PHP 8.2 with Apache, PDO MySQL, and GD extensions (Port `8080`)
- **`golf-db`:** MySQL 8.0.42 server with automated database initialization (Port `3306`)
- **`golf-phpmyadmin`:** Web database manager (Port `8081`)

### Step 3: Verify Reachability
Test API reachability:
```bash
curl http://localhost:8080/admin-php/api/mobile.php?action=weather
```
Access the application:
- **Web Admin Panel:** [http://localhost:8080/admin-php/](http://localhost:8080/admin-php/)
- **phpMyAdmin:** [http://localhost:8081/](http://localhost:8081/) (User: `root`, Password: use the value you generated for MYSQL_ROOT_PASSWORD in your .env file)

---

## Option B: Manual XAMPP Setup

### Step 1: Place Project Files
Extract the zip archive or clone the repository into your XAMPP `htdocs` directory:
- **Windows XAMPP:** `C:\xampp\htdocs\golf-tee`
- **Linux/macOS:** `/opt/lampp/htdocs/golf-tee`

### Step 2: Configure Environment Variables (.env)
Copy `.env.example` to `.env` in both the project root and `admin-php/` directory:
```bash
cp .env.example .env
cp .env.example admin-php/.env
```

Generate secure secrets:
```bash
openssl rand -base64 48
```
Paste output as `SESSION_SECRET` in both `.env` files.

### Step 3: Install PHP Dependencies (Composer)
Run dependency installation in `admin-php/`:
```bash
cd admin-php && composer install
```

### Step 4: Import Database & Initial Data
To initialize the database from scratch, execute `schema.sql` followed by `seed.sql`:

```bash
# 1. Create database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS golf_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Import complete table schema, foreign keys, and indexes
mysql -u root -p golf_admin < database/schema.sql

# 3. Import initial seed data (staff accounts, members, course settings)
mysql -u root -p golf_admin < database/seed.sql
```

> **Note on Migrations vs Schema:** Always import `database/schema.sql` then `database/seed.sql` for new installations (`demo-data.sql` is optional for extended samples). Files in `database/migrations/` (e.g. `001_initial_schema.sql` and `002_add_security_tokens_and_indexes.sql`) are incremental migration scripts intended for existing installations upgrading their database schema.

### Step 5: First Admin Login Setup
Visit your admin panel URL:
```
http://localhost/golf-tee/admin-php/
```
Default administrator email:
- Email: `admin@pinevalley.com`

> **Security Note:** Seed credentials require an immediate password change upon initial login. Navigate to `admin-php/set-password.php` to set a strong custom password for your administrator account.

---

## 📱 Mobile App Setup

See `mobile_app/README.md` for Flutter app configuration.

### Step 1: Configure API Base URL
Edit `mobile_app/lib/config/api_config.dart` and set `API_BASE_URL` to your backend URL (e.g., `http://localhost/golf-tee/admin-php` or `https://api.yourdomain.com`, or `http://10.0.2.2/golf-tee/admin-php` for Android emulator) before `flutter pub get`.

### Step 2: Install Dependencies
```bash
cd mobile_app
flutter pub get
```

### Step 3: Run the App
```bash
flutter run -d chrome
# Or for Android emulator:
flutter run -d android
```

---

## 🌐 Live Demo & Evaluation Credentials

- **Live Demo URL:** `https://mhtria.free.je/admin-php/` (Direct Admin Login: `https://mhtria.free.je/admin-php/login.php`)
- **Administrator Email:** `admin@pinevalley.com`
- **Manager Email:** `grace@pinevalley.com`
- **Front Desk Email:** `tom@pinevalley.com`
- **Mobile Golfer Accounts:** `james.carter@mail.com` (ID: 1), `mina.lee@mail.com` (ID: 2), `alex.morgan@mail.com` (ID: 15)

> **Browser Note on Free Hosting Challenge:**
> Certain free hosting platforms (such as InfinityFree / free.je) serve an initial automated JavaScript anti-bot verification (`aes.js/slowAES`) on raw cURL/HEAD requests. When opened in any standard web browser (Chrome, Edge, Firefox, Safari), the verification transparently passes within 1 second and immediately loads the GolfTee login interface. For completely challenge-free local evaluation, run via Docker (`docker compose up -d`) or local XAMPP.

---

## 🔐 Seed Accounts & Password Configuration

### 1. Web Admin Panel (`admin-php/login.php`)
- **Owner / Superadmin:** `admin@pinevalley.com`
- **Manager:** `grace@pinevalley.com`
- **Head Pro:** `sjenkins@pinevalley.com`
- **Club Admin:** `dtorres@pinevalley.com`
- **Front Desk:** `tom@pinevalley.com`
- **Password Setup:** Initial passwords are provisioned via `database/seed.sql`. Set your personal administrator password via `admin-php/set-password.php` on initial deployment.

### 2. Golfer Mobile App (`mobile_app`)
- **Member 1 (James Carter):** `james.carter@mail.com` (Member ID: 1)
- **Member 2 (Mina Lee):** `mina.lee@mail.com` (Member ID: 2)
- **Member 3 (Alex Morgan):** `alex.morgan@mail.com` (Member ID: 15)

---

## ⏱️ Hourly Weather Cron Setup

Add a cron job in your hosting panel or crontab (`crontab -e`):
```cron
0 * * * * curl -s "https://your-domain.com/admin-php/cron_weather.php" > /dev/null 2>&1
```
