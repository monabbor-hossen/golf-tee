# 📱 Pine Valley Golf Club — Mobile App (Flutter + Riverpod)

A high-fidelity mobile application for the Pine Valley Golf Club, matching the modern golf club specification, powered by **Flutter** with **Riverpod 3** state management and connected to the `admin-php` REST API and MySQL database (`golf_admin`).

---

## ⚙️ How to Set the API URL

The mobile app has a single, centralized configuration file for all backend networking:

> **Configuration File:** `lib/services/api_service.dart` (Line 34)

### Method 1: In Code (`lib/services/api_service.dart`)
Open [lib/services/api_service.dart](file:///c:/xampp/htdocs/golf-tee/mobile_app/lib/services/api_service.dart) and set the `liveApiUrl` constant:
```dart
static const String liveApiUrl = 'https://your-domain.com/admin-php/api/mobile.php';
```

### Method 2: At Build Time via `--dart-define` (Recommended for CI/CD)
You do not need to modify any source files when building for production. Simply pass `--dart-define=API_URL=...` during build:
```bash
flutter build apk --release --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
```

Priority: --dart-define=API_URL > liveApiUrl constant > localhost fallback (development only)

### Local Development Auto-Fallback
When `liveApiUrl` and `--dart-define` are empty, `ApiService` automatically falls back to:
- **Web Browser:** `http://localhost/golf-tee/admin-php/api/mobile.php`
- **Android Emulator:** Automatically routes to `http://10.0.2.2/golf-tee/admin-php/api/mobile.php` to access host machine XAMPP
- **Physical Device:** LAN IP auto-detection

### Verify API Connectivity
Test API connectivity:
```bash
curl http://localhost/golf-tee/admin-php/api/mobile.php?action=weather
```
*(Should return JSON `{"status":"ok","conditions":{...}}`)*

---

## 🏗️ How to Build Android APK & iOS IPA

### Prerequisites
- **Backend API & Database:** The backend REST API must be deployed and configured. See [admin-php/deploy.md](../admin-php/deploy.md) and [HowToSetup.md](../HowToSetup.md). Ensure the database is initialized using [database/schema.sql](../database/schema.sql) and seeded with [database/seed.sql](../database/seed.sql).
- **Flutter SDK:** Version 3.27.1 (pinned in .flutter-version, verify with `flutter --version`)
- **Android Studio / Android SDK:** (API Level 34+)
- **Xcode 15+:** (for iOS builds on macOS)

### 1. Restore Dependencies
The repository includes a pinned `pubspec.lock` to guarantee clean, reproducible builds without unexpected dependency updates:
```bash
flutter pub get
```
WARNING: pubspec.lock is pinned for reproducibility. Do not run flutter pub upgrade without full regression testing.

*Note: If you encounter plugin errors, delete `.flutter-plugins-dependencies` and re-run `flutter pub get` to regenerate platform plugin bindings.*

### Pre-Build Verification: Test API Connectivity
Before building, verify that your backend API is reachable:
```bash
# Test API: curl https://your-domain.com/admin-php/api/mobile.php?action=weather (should return JSON with status:ok)
curl https://your-domain.com/admin-php/api/mobile.php?action=weather
```

### 2. Build for Android

**Build Release APK:**
```bash
flutter build apk --release --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
```
- **Output File:** `build/app/outputs/flutter-apk/app-release.apk`

**Build Android App Bundle (AAB for Google Play Store):**
```bash
flutter build appbundle --release --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
```
- **Output File:** `build/app/outputs/bundle/release/app-release.aab`

### 3. Build for iOS (macOS required)

**Build Release Archive / IPA:**
```bash
flutter build ipa --release --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
```
- **Output File:** `build/ios/archive/Runner.xcarchive`

---

## 🎨 Where the App Icons Live

All app icon assets are organized in their platform-standard directories:

### 1. Android Icons
- `android/app/src/main/res/mipmap-mdpi/ic_launcher.png` (48x48)
- `android/app/src/main/res/mipmap-hdpi/ic_launcher.png` (72x72)
- `android/app/src/main/res/mipmap-xhdpi/ic_launcher.png` (96x96)
- `android/app/src/main/res/mipmap-xxhdpi/ic_launcher.png` (144x144)
- `android/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png` (192x192)

### 2. iOS Icons
- `ios/Runner/Assets.xcassets/AppIcon.appiconset/`
  - `Icon-App-1024x1024@1x.png` (App Store Marketing)
  - `Icon-App-60x60@2x.png` & `@3x.png` (iPhone App Icons)
  - `Icon-App-76x76@1x.png` & `@2x.png` (iPad App Icons)
  - `Icon-App-83.5x83.5@2x.png` (iPad Pro)
  - `Contents.json` (Xcode asset catalog declaration)

### 3. Web & PWA Icons
- `web/icons/Icon-192.png`
- `web/icons/Icon-512.png`
- `web/favicon.png`

---

## 📱 Features & Screens

1. **Shell & Navigation (`lib/screens/shell_screen.dart`):**
   - Bottom navigation bar with animated pill selector (Home, Book, Bookings, Score, Profile).
   - Global Toast overlay with Pine Valley dark styling.

2. **Home Screen (`lib/screens/home_screen.dart`):**
   - Pine Valley Gold Crest & live weather conditions pill (`68° Sunny · 6 mph`).
   - **Next Round Card:** Displays upcoming confirmed tee time with 1-tap navigation to digital pass.
   - **Quick Action Pills:** "Book tee time", "Active waitlist", "Digital scorecard", "Member perks".
   - **Live Course Conditions:** Stimp reading (`10.5`), NW wind speed, Cart rule (`90° Rule`), and refresh status.
   - **Live Pace of Play Tracker:** Status meter (`4h 12m avg round · 6m ahead of pace`).
   - **Upcoming Club Events:** Carousel with badges (Member-Guest, Twilight Scramble, Ladies Invitational).
   - **Club Announcements:** Live bulletin board loaded from MySQL database.

3. **Tee Time Booking (`lib/screens/book_screen.dart`):**
   - 14-day horizontal calendar with auto-scroll and weekend indicators.
   - 9-hole vs 18-hole segment toggle.
   - 1 to 4 player counter.
   - Time of Day filters: `All`, `Morning`, `Midday`, `Twilight (−35%)`.
   - Dynamic tee slot chips displaying state: **Available**, **Selected**, **Waitlist**, **Booked**, **Locked**, along with pricing ($85, $65, $55).
   - Sticky bottom bar calculating total price and direct navigation to Checkout or Join Waitlist.

4. **Checkout & Payment (`lib/screens/checkout_screen.dart`):**
   - Booking summary card with date, time, and player count.
   - **Playing Partners Selector:** Modal to add fellow club members.
   - **Course Amenities:** Cart rental ($20/player) and Caddie reservation ($50/group) toggles.
   - **Promo Code:** Coupon system with `BIRDIE10` ($10 discount) validation.
   - **Saved Cards Selector:** Visa, Mastercard with "Add new card" modal.
   - **Cancellation Policy:** Full refund warning (> 24 hours).
   - Full price itemization & "Confirm & Pay" button with loading feedback.

5. **Booking Confirmation (`lib/screens/success_screen.dart`):**
   - Animated checkmark & confetti banner.
   - Digital ticket reference badge (`#PV-2026-XXXX`).
   - Real-time QR Code rendering powered by `qr_flutter`.
   - "View digital pass", "Add to calendar", and "Back to home" quick actions.

6. **My Bookings (`lib/screens/bookings_screen.dart`):**
   - 3-segment switcher: **Upcoming**, **Waitlist**, and **Past Rounds**.
   - Upcoming booking cards with date badge and 1-tap pass access.
   - Live waitlist cards with position (#1 in line, #3 in line) and "Leave waitlist" functionality.
   - Past rounds history with final scores.

7. **Digital Pass & Tee Time Detail (`lib/screens/detail_screen.dart`):**
   - Digital membership ticket with Pine Valley watermark.
   - QR Code for bag drop check-in and starter station scanning.
   - Game day weather forecast.
   - Course location & Clubhouse contact phone/email links.
   - Self-service booking cancellation with modal and automatic refund calculation.

8. **Digital Scorecard (`lib/screens/scorecard_screen.dart`):**
   - 18-hole interactive scorecard with par tracking (Holes 1–18, Pars 4/5/3).
   - Dynamic stroke increment/decrement (+ / -).
   - Real-time score-to-par calculation (`+1`, `E`, `-2`) with color-coded chips.
   - Front 9 & Back 9 score breakdown summary table.
   - "Finish round" button that saves scores to MySQL database and calculates revised handicap index.

9. **Member Profile (`lib/screens/profile_screen.dart`):**
   - Member card with James Carter (`#PV-84291`, Full Golf Member).
   - Handicap Index card (`12.4`) with **CustomPainter Handicap Trend Line Chart**.
   - Rounds played, average score, member since statistics.
   - Saved payment methods manager (set default, delete card, add new card).
   - Notification preference switches.

---

## 🛠 Tech Stack

- **Framework:** Flutter 3.47+ / Dart 3.13+
- **State Management:** Riverpod 3 (`flutter_riverpod: ^3.4.3`) using `Notifier` and `NotifierProvider`
- **Typography:** Google Fonts Inter
- **QR Codes:** `qr_flutter: ^4.1.0`
- **HTTP Client:** `http: ^1.6.0`
- **Backend API:** PHP REST API at `admin-php/api/mobile.php` connecting to MySQL `golf_admin`
