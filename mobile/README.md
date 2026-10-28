# 📱 Pine Valley Golf Club — Mobile App (Flutter + Riverpod)

> **Note:** The complete Flutter mobile application source code is located at `../mobile_app/` (one level up from this mobile/ folder, at the project root).

A high-fidelity mobile application for the Pine Valley Golf Club, matching the modern golf club specification, powered by **Flutter** with **Riverpod 3** state management and connected to the `admin-php` REST API and MySQL database (`golf_admin`).

---

## ⚙️ How to Set the API URL

The mobile app has a single, centralized configuration file for all backend networking:

> **Configuration File:** `mobile_app/lib/services/api_service.dart` (Line 34)

### Method 1: In Code (`mobile_app/lib/services/api_service.dart`)
Open `mobile_app/lib/services/api_service.dart` and set the `liveApiUrl` constant:
```dart
static const String liveApiUrl = 'https://your-domain.com/admin-php/api/mobile.php';
```

### Method 2: At Build Time via `--dart-define` (Recommended for CI/CD)
You do not need to modify any source files when building for production. Simply pass `--dart-define=API_URL=...` during build:
```bash
flutter build apk --release --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
```

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
- Flutter SDK (3.22+ / Dart 3.4+)
- Android Studio / Android SDK (API Level 34+)
- Xcode 15+ (for iOS builds on macOS)

### 1. Restore Dependencies
Navigate into `mobile_app/`. The project includes a committed `pubspec.lock` for clean, deterministic builds:
```bash
cd mobile_app
flutter pub get
```

### 2. Build for Android

**Build Release APK:**
```bash
flutter build apk --release --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
```
- **Output File:** `mobile_app/build/app/outputs/flutter-apk/app-release.apk`

**Build Android App Bundle (AAB for Google Play Store):**
```bash
flutter build appbundle --release --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
```
- **Output File:** `mobile_app/build/app/outputs/bundle/release/app-release.aab`

### 3. Build for iOS (macOS required)

**Build Release Archive / IPA:**
```bash
flutter build ipa --release --dart-define=API_URL=https://your-domain.com/admin-php/api/mobile.php
```
- **Output File:** `mobile_app/build/ios/archive/Runner.xcarchive`

---

## 🎨 Where the App Icons Live

All app icon assets are organized in their platform-standard directories:

### 1. Android Icons
- `mobile_app/android/app/src/main/res/mipmap-mdpi/ic_launcher.png` (48x48)
- `mobile_app/android/app/src/main/res/mipmap-hdpi/ic_launcher.png` (72x72)
- `mobile_app/android/app/src/main/res/mipmap-xhdpi/ic_launcher.png` (96x96)
- `mobile_app/android/app/src/main/res/mipmap-xxhdpi/ic_launcher.png` (144x144)
- `mobile_app/android/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png` (192x192)

### 2. iOS Icons
- `mobile_app/ios/Runner/Assets.xcassets/AppIcon.appiconset/`
  - `Icon-App-1024x1024@1x.png` (App Store Marketing)
  - `Icon-App-60x60@2x.png` & `@3x.png` (iPhone App Icons)
  - `Icon-App-76x76@1x.png` & `@2x.png` (iPad App Icons)
  - `Icon-App-83.5x83.5@2x.png` (iPad Pro)
  - `Contents.json` (Xcode asset catalog declaration)

### 3. Web & PWA Icons
- `mobile_app/web/icons/Icon-192.png`
- `mobile_app/web/icons/Icon-512.png`
- `mobile_app/web/favicon.png`

---

## 🛠 Tech Stack

- **Framework:** Flutter 3.47+ / Dart 3.13+
- **State Management:** Riverpod 3 (`flutter_riverpod: ^3.4.3`) using `Notifier` and `NotifierProvider`
- **Typography:** Google Fonts Inter
- **QR Codes:** `qr_flutter: ^4.1.0`
- **HTTP Client:** `http: ^1.6.0`
- **Backend API:** PHP REST API at `admin-php/api/mobile.php` connecting to MySQL `golf_admin`
