# 🤖 GolfTee — AI Coding Agent & Developer Guide

This file provides architectural context, directory mappings, design conventions, and instructions for AI coding assistants and developers contributing to **GolfTee**.

---

## 🏛️ System Architecture

GolfTee consists of two primary applications operating together:
1. **`admin-php` (Web Admin & REST API):**
   - **Architecture:** Clean native PHP 8.2+ MVC architecture without bulky external framework dependencies.
   - **Autoloading:** PSR-4 compliant autoloader (`app/Core/Autoloader.php`).
   - **Database Layer:** Singleton PDO wrapper (`app/Core/Database.php`) strictly enforcing prepared statements to guarantee SQL-injection immunity.
   - **Routing:** Explicit controller mapping via clean query-action router and friendly front-controllers.
   - **Mobile API:** `admin-php/api/mobile.php` acts as the unified REST endpoint dispatching actions (`home`, `slots`, `book`, `profile`, `weather`, `cards`, `bookings`, `waitlist`).

2. **`mobile_app` (Golfer Cross-Platform App):**
   - **Framework:** Flutter 3.47+ / Dart 3.13+ using Material 3 design tokens.
   - **State Management:** Riverpod 3 (`flutter_riverpod: ^3.4.3`) using `Notifier` and `NotifierProvider`.
   - **Networking:** Resilient HTTP service (`api_service.dart`) with auto-detecting multi-candidate fallbacks for localhost, Android emulator (`10.0.2.2`), and production URLs.

---

## 📁 Repository Directory Map

```text
├── admin-php/                      # PHP Backend & Web Admin
│   ├── api/
│   │   └── mobile.php              # REST API Router & JSON endpoints
│   ├── app/
│   │   ├── Controllers/            # Admin page controllers (Member, Setting, etc.)
│   │   ├── Core/                   # Autoloader, Database PDO, Config, Session
│   │   ├── Domain/                 # Domain logic (SlotFormatter, PricingCalculator)
│   │   ├── Repositories/           # Data access objects (Member, Booking, Setting)
│   │   └── Services/               # WeatherService, AuthService, SmtpClient
│   ├── assets/                     # Admin CSS and JavaScript (app.js)
│   ├── views/                      # PHP Admin view templates & layouts
│   ├── bootstrap.php               # System bootstrap & security headers
│   └── cron_weather.php            # 1-hour automated weather refresh task
│
├── mobile_app/                     # Flutter Mobile App
│   ├── lib/
│   │   ├── constants/              # AppColors, AppStrings, Dimens
│   │   ├── models/                 # Member, CourseCondition, TeeSlot, PaymentCard
│   │   ├── providers/              # Riverpod State Notifiers (member, course, booking)
│   │   ├── screens/                # Home, Book, Checkout, Profile, Scorecard
│   │   ├── services/               # ApiService (HTTP client & endpoints)
│   │   └── widgets/                # UI components (QRTicketView, HandicapChart)
│   └── pubspec.yaml                # Dependencies & Flutter assets
│
├── database/
│   ├── schema.sql                  # Complete clean database schema (DDL)
│   └── seed.sql                    # Demo data (members, cards, bookings, news)
│
├── docs/                           # Documentation
│   └── API_DOCUMENTATION.md        # Complete REST API reference
```

---

## 📝 Coding Conventions & Guidelines

### PHP (`admin-php`)
- Strict types must be declared at the top of every file: `declare(strict_types=1);`.
- Always use prepared statements with parameter binding: `Database::query($sql, [$param])` or `Database::execute($sql, [$param])`. Never concatenate raw variables into SQL queries.
- Validate CSRF tokens on all mutating POST requests: `Session::validateRequestCsrf($request);`.
- Output escaping: Always wrap dynamic HTML outputs in `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`.

### Flutter & Dart (`mobile_app`)
- Use Riverpod `NotifierProvider` and immutable state objects with `.copyWith()`.
- Use double quotes for strings with apostrophes or escape properly.
- All colors and styling must derive from `AppColors` (`lib/constants/colors.dart`).
- When adding network requests in `ApiService`, ensure graceful error handling with `try / catch` blocks to prevent unhandled exceptions.

---

## 🧪 Testing & Linting Commands

### Lint PHP Backend
```bash
# Verify PHP syntax across the codebase
php -l admin-php/bootstrap.php
php -l admin-php/api/mobile.php
php -l admin-php/app/Services/WeatherService.php
```

### Analyze Flutter Mobile App
```bash
cd mobile_app
flutter analyze
```

### Test API Response
```bash
curl -s "http://localhost/golf-tee/admin-php/api/mobile.php?action=weather"
curl -s "http://localhost/golf-tee/admin-php/api/mobile.php?action=home&member_id=15"
```
